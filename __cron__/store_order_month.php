<?php
/**
 * 门店销售统计 月表
 * User: dehuang
 * Date: 2018-09-18
 * Time: 16:58
 */

error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'sale_add_sale');

set_time_limit(300);

$time = time();

$s_time = strtotime(date('Y-m').' -1 month');  //上月 1号
$e_time = strtotime(date('Y-m'));  //本月一号;
$sql_e_time = strtotime('-1 day',$e_time);  //上月月末
$time = time();  //当前时间
$year = date('Y');  //当前年
// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
$sql = "SELECT
            sum(od.num * od.price)money,
            sum(od.inout_price_all)inout_money,
            sum(od.num)num,
            count(DISTINCT o.id) order_count,
            o.store_id,
            s.title as store_name,
            s.shequ_id as shequ_id,
            sh.title as shequ_name,
            {$s_time} as s_time,
            {$sql_e_time} as e_time,
            {$time} as ctime,
            3 as time_type
        FROM
            hii_order o
        INNER JOIN hii_order_detail od ON o.order_sn = od.order_sn
        LEFT JOIN hii_store s ON s.id = o.store_id
        LEFT JOIN hii_shequ sh ON sh.id = s.shequ_id
        WHERE
            o.create_time >= {$s_time}
        AND o.create_time < {$e_time}
        AND o.pay_type != 3
        AND o.pay_type != 4
        AND o.pay_status = 2
        AND o.status = 5
        AND o.type = 'store'
        GROUP BY o.store_id
      ";
$query = $db->query($sql);
if($query !== false){
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
    if(empty($data[0]['store_id'])){
        exit('今天没有生成订单');
    }

}else{
    exit('查询订单表 出错'.json_encode($db->errorInfo()));
}$sql = "SELECT
            sum(o.pay_money)pay_money,
            o.store_id 
        FROM
            hii_order o
        WHERE
            o.create_time >= {$s_time}
        AND o.create_time < {$e_time}
        AND o.pay_type != 3
        AND o.pay_type != 4
        AND o.pay_status = 2
        AND o.status = 5
        AND o.type = 'store'
        GROUP BY o.store_id
      ";
$query = $db->query($sql);
if($query !== false){
    $data_pay_money = $query->fetchAll(PDO::FETCH_ASSOC);
    $data_pay_money_new = array();
    foreach ($data_pay_money as $key=>$val){
        $data_pay_money_new[$val['store_id']] = $val['pay_money'];
    }
    unset($data_pay_money);
}else{
    exit('查询订单表 出错'.json_encode($db->errorInfo()));
}
foreach ($data as $key=>$val){
    $data[$key]['pay_money'] = $data_pay_money_new[$val['store_id']];
}
try{
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//异常模式
    $db->beginTransaction();//开启事物
    $sql_key_array = array_keys($data[0]); //sql添加字段名
    $sql_key_str = implode(',',$sql_key_array);
    $sql_zhan = implode(',',array_fill(0,count($sql_key_array),'?')); //占位符 ?
    $sql = "INSERT INTO hii_store_order_{$year}({$sql_key_str}) VALUES({$sql_zhan})";
    $stmt = $db->prepare($sql);
    foreach ($data as $key=>$val){
        $stmt->execute(array_values($val));
    }
    $db->commit();
}catch (PDOException $e){
    exit('store_order_week---Error! '.$e->getMessage());
    $db->rollBack();
}
