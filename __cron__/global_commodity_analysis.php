<?php
/**
 * 订单数据 当有会员下单并成功支付后，需要发送的记录信息 每天执行前一天的数据
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
$db->beginTransaction();//开启事物
try{
    $sql = "INSERT into hii_global_commodity_analysis(store_id,cate_id,goods_id,goods_name,buynum,buymoney,ctime) 
        SELECT O.store_id, G.cate_id,OD.d_id as goods_id,G.title as goods_name,sum(OD.num) as buynum,sum(OD.num * OD.price) buymoney,unix_timestamp(FROM_UNIXTIME(O.create_time, '%Y-%m-%d')) days
        FROM hii_order O
        LEFT JOIN hii_order_detail OD ON OD.order_sn = O.order_sn
        LEFT JOIN hii_goods G ON G.id = OD.d_id
        WHERE O. STATUS = 5 AND O.`type` = 'store' AND O.create_time >= {$s_time} AND O.create_time < {$e_time} AND G.cate_id IS NOT NULL
        GROUP BY OD.d_id, days, O.store_id
        order by days,O.store_id,OD.d_id";
    $query = $db->exec($sql);
    $db->commit();
}catch (PDOException $e){
    exit('入库---Error! '.$e->getMessage());
    $db->rollBack();
}

die;



function set_log($data, $time = 1)
{
    $log_dir = ROOT_PATH . 'Runtime/' . CAIJI_NAME . '_logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_dir . date('Y-m') . '.txt', ($time ? "[" . date('Y-m-d H:i:s') . "] " : '') . $data . "\r\n", FILE_APPEND);
}