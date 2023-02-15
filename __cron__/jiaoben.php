<?php
/** 昨天4点36 ---
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-09-28
 * Time: 15:38
 */
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'sale_add_sale');
$url = 'http://udeana.dev.hiiyun.com/api/Sale/add_sale_ls';
$api = array(
    'appid' => 'zhaike',
    'appkey' => 'a887ff563347d216a5d0cc0413f89gf0'
);
set_time_limit(300);

$time = time();
$e_time = strtotime(date('Y-m-d 00:00:00'));
$s_time = $e_time-3600*24;

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);

//入库
$sql = "SELECT
            SUM(sisd.g_num) AS `total_num`,
            sisd.goods_id,
            sis.store_id2 as store_id
        FROM
            hii_store_in_stock sis
        INNER JOIN hii_store_in_stock_detail sisd ON sis.s_in_s_id = sisd.s_in_s_id
        WHERE (sis.s_in_s_status = 1 or sis.s_in_s_status =3)
        and sis.ptime >= 1538080200 
        group by sis.store_id2,sisd.goods_id";
$query = $db->query($sql);
$data = array();
if($query !== false){
    $query = $query->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($query)){

        try{
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//异常模式
            $db->beginTransaction();//开启事物
            foreach($query  as $key=>$val){
                $sql = "select * from hii_goods_store_zzy where store_id={$val['store_id']} and goods_id = {$val['goods_id']}";
                $ss = $db->query($sql);
                $a = $ss->fetch(PDO::FETCH_ASSOC);
                if(empty($a)){
                    $db->exec("insert into hii_goods_store_zzy(goods_id,store_id,num,update_time) values({$val['goods_id']},{$val['store_id']},{$val['total_num']},1538132340)");
                }else{
                    $sql = "update hii_goods_store_zzy set num = num +{$val['total_num']} where store_id={$val['store_id']} and goods_id={$val['goods_id']}";
                    $db->exec($sql);
                }
            }
            $db->commit();
        }catch (PDOException $e){
            exit('入库---Error! '.$e->getMessage());
            $db->rollBack();
        }
    }
}else{
    exit('查询入库  出错'.json_encode($db->errorInfo()));
}

//出库
$sql = "SELECT
            SUM(ssd.g_num) AS `total_num`,
            ssd.goods_id,
            sos.store_id2 as store_id
        FROM
            hii_store_out_stock sos
        INNER JOIN hii_store_stock_detail ssd ON sos.s_out_s_id = ssd.s_out_s_id
        WHERE (sos.s_out_s_status = 1 or sos.s_out_s_status = 3)
        and sos.ptime >= 1538080200 
        group by sos.store_id2,ssd.goods_id";
$query = $db->query($sql);
$data = array();
if($query !== false){
    $query = $query->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($query)){

        try{
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//异常模式
            $db->beginTransaction();//开启事物
            foreach($query  as $key=>$val){
                $sql = "update hii_goods_store_zzy set num = num-{$val['total_num']} where store_id={$val['store_id']} and goods_id={$val['goods_id']}";
                $db->exec($sql);
            }
            $db->commit();
        }catch (PDOException $e){
            exit('出库---Error! '.$e->getMessage().$sql);
            $db->rollBack();
        }
    }
}else{
    exit('查询出库 出错'.json_encode($db->errorInfo()));
}

//销售
$sql = "SELECT
            SUM(od.num) AS `total_num`,
            od.d_id as goods_id,
            o.store_id
        FROM
            hii_order o
        INNER JOIN hii_order_detail od
        on o.order_sn=od.order_sn
        where o.pay_status=2 
        and o.status=5
        and o.update_time>= 1538080200 
        group by o.store_id,od.d_id";
$query = $db->query($sql);
$data = array();
if($query !== false){
    $query = $query->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($query)){

        try{
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//异常模式
            $db->beginTransaction();//开启事物
            foreach($query  as $key=>$val){
                $sql = "update hii_goods_store_zzy set num = num-{$val['total_num']},sell_num = sell_num+{$val['total_num']},month_num= month_num+{$val['total_num']} where store_id={$val['store_id']} and goods_id={$val['goods_id']}";
                $db->exec($sql);
            }
            $db->commit();
        }catch (PDOException $e){
            exit('出库销售---Error! '.$e->getMessage().$sql);
            $db->rollBack();
        }
    }
}else{
    exit('查询出库销售 出错'.json_encode($db->errorInfo()));
}
