<?php

/**
 * 商品操作记录
 * 当人工对商品的库存进行操作时，需要进行提供的记录  类型：1 入库 2 出库 3 找回 4 丢失  每天执行前一天的数据  2017推送
 */
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');
$url = 'http://udeana.dev.hiiyun.com/api/Sale/operation_ls';
$api = array(
    'appid' => 'zhaike',
    'appkey' => 'a887ff563347d216a5d0cc0413f89gf0'
);
set_time_limit(300);


// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
$sql = "select log_time from hii_goods_log_apiwiki where id = 4 ";
$query = $db->query($sql);
if($query !== false){
    $query = $query->fetch(PDO::FETCH_ASSOC);
    if(empty($query)){
        exit('没有时间');
    }
    $time = $query['log_time'];
    $e_time = $time;
    $s_time = $e_time-3600*24;
    if(date('Ymd',$s_time) >= 20180813){
        exit('只取20180813之前的数据');
    }
}else{
    exit('没有时间');
}
/**************入库操作 1**********************/
$sql = "SELECT
        	sis.store_id2 as store_id,
        	sisd.goods_id,
        	g.title as goods_title,
        	g.cate_id,
        	gc.title as cate_title,
        	sum(sisd.g_num) as num,
        	sis.admin_id as apply_admin,
        	sis.padmin_id as check_admin,
        	1 as type,
        	sis.ctime as log_time,
            sisd.g_price as sell_price
        FROM
        	hii_store_in_stock sis
        INNER JOIN hii_store_in_stock_detail sisd on sis.s_in_s_id=sisd.s_in_s_id
        left join hii_goods g on g.id = sisd.goods_id
        left join hii_goods_cate gc on gc.id=g.cate_id
        where sis.ctime>={$s_time} and sis.ctime<{$e_time} and sis.s_in_s_type !=2 
        AND sis.store_id2 not in(select id from hii_store where shequ_id = 3)
        group by sis.store_id2,sisd.goods_id";
$goods = $db->query($sql);
$data1 = array();
if($goods !== false){
    $data1 = $goods->fetchAll(PDO::FETCH_ASSOC);
}


/**********************出库操作 2******************************/
$sql = "SELECT
        	sos.store_id2 as store_id,
        	ssd.goods_id,
        	g.title as goods_title,
        	g.cate_id,
        	gc.title as cate_title,
        	sum(ssd.g_num) as num,
        	sos.admin_id as apply_admin,
        	sos.padmin_id as check_admin,
        	2 as type,
        	sos.ctime as log_time,
             ssd.g_price as sell_price
        FROM
        	hii_store_out_stock sos
        INNER JOIN hii_store_stock_detail ssd on sos.s_out_s_id=ssd.s_out_s_id
        left join hii_goods g on g.id = ssd.goods_id
        left join hii_goods_cate gc on gc.id=g.cate_id
        where sos.ctime>={$s_time} and sos.ctime<{$e_time} and sos.s_out_s_type != 3 
         AND sos.store_id2 not in(select id from hii_store where shequ_id = 3)
        group by sos.store_id2,ssd.goods_id";
$goods = $db->query($sql);
$data2 = array();
if($goods !== false){
    $data2 = $goods->fetchAll(PDO::FETCH_ASSOC);
}
/**************盘盈入库 3**********************/
$sql = "SELECT
        	sis.store_id2 as store_id,
        	sisd.goods_id,
        	g.title as goods_title,
        	g.cate_id,
        	gc.title as cate_title,
        	sum(sisd.g_num) as num,
        	sis.admin_id as apply_admin,
        	sis.padmin_id as check_admin,
        	3 as type,
        	sis.ctime as log_time,
            sisd.g_price as sell_price
        FROM
        	hii_store_in_stock sis
        INNER JOIN hii_store_in_stock_detail sisd on sis.s_in_s_id=sisd.s_in_s_id
        left join hii_goods g on g.id = sisd.goods_id
        left join hii_goods_cate gc on gc.id=g.cate_id
        where sis.ctime>={$s_time} and sis.ctime<{$e_time} and sis.s_in_s_type =2 
         AND sis.store_id2 not in(select id from hii_store where shequ_id = 3)
        group by sis.store_id2,sisd.goods_id";
$goods = $db->query($sql);
$data3 = array();
if($goods !== false){
    $data3 = $goods->fetchAll(PDO::FETCH_ASSOC);
}


/**********************盘亏出库操作 4******************************/
$sql = "SELECT
        	sos.store_id2 as store_id,
        	ssd.goods_id,
        	g.title as goods_title,
        	g.cate_id,
        	gc.title as cate_title,
        	sum(ssd.g_num) as num,
        	sos.admin_id as apply_admin,
        	sos.padmin_id as check_admin,
        	4 as type,
        	sos.ctime as log_time,
            ssd.g_price as sell_price
        FROM
        	hii_store_out_stock sos
        INNER JOIN hii_store_stock_detail ssd on sos.s_out_s_id=ssd.s_out_s_id
        left join hii_goods g on g.id = ssd.goods_id
        left join hii_goods_cate gc on gc.id=g.cate_id
        where sos.ctime>={$s_time} and sos.ctime<{$e_time} and sos.s_out_s_type = 3 
         AND sos.store_id2 not in(select id from hii_store where shequ_id = 3)
        group by sos.store_id2,ssd.goods_id";
$goods = $db->query($sql);
$data4 = array();
if($goods !== false){
    $data4 = $goods->fetchAll(PDO::FETCH_ASSOC);
}
$data = array_merge($data1,$data2,$data3,$data4);
unset($data1,$data2,$data3,$data4);
if(empty($data)){
    $time = $time + (3600*24);
    $db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=4");
    exit('没有出入库记录');
}
$md5 = md5(strtolower($url).$api['appid'].$api['appkey'].date('Y-m-d').$time);
$header = array('st:'.$time,'utoken:'.$md5);
$int = 0;
$tiao = 200;
while (true) {
    $array = array_slice($data,$int,$tiao);
    $count = count($array);
    if(empty($array)){
        break;
    }
    $array = json_encode($array);

    $a =  http_post($url, array('data'=>$array,'row'=>$count), $header);
    $a = json_decode($a,true);
    if($a['status'] == 1){
        if(!empty($a['data']['fail'])){
            echo '推送数据未执行的门店id和商品id'.json_encode($a['data']['fail']);
        }
    }
    $int += $tiao;
}


/*********************损耗**************************************/
$time = $time + (3600*24);
$db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=4");
die;

function http_post($url,$data,$header){
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return $data;
}

function set_log($data, $time = 1)
{
    $log_dir = ROOT_PATH . 'Runtime/' . CAIJI_NAME . '_logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_dir . date('Y-m') . '.txt', ($time ? "[" . date('Y-m-d H:i:s') . "] " : '') . $data . "\r\n", FILE_APPEND);
}