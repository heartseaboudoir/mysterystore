<?php
/**
 * 订单数据 当有会员下单并成功支付后，需要发送的记录信息 每天执行前一天的数据  2017推送
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



// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
$sql = "select log_time from hii_goods_log_apiwiki where id = 1 ";
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
$sql = "SELECT
            o.id,
        	o.store_id,
        	o.order_sn AS sn,
        	o.uid AS `user`,
        	o.pay_type,
            case o.pay_type when 1 then '微信'
            when 2 then '支付宝'
             ELSE '余额支付'
            end  pay_type_text,
        	o.money,
        	o.pay_money,
            o.cash_money as discount_money,
            o.user_discount_money as offer_money,
        	o.create_time AS log_time,
        	od.d_id AS goods_id,
        	od.title AS goods_title,
        	gc.id AS cate_id,
        	gc.title AS cate_title,
        	od.num AS sell_num,
        	od.price AS sell_price,
        	ifnull(if(od.inout_price_one,od.inout_price_one,goods_inprice.inprice),0) AS buy_price,
        	(od.price - ifnull(if(od.inout_price_one,od.inout_price_one,goods_inprice.inprice),0)) AS profit_money,
        	0 AS discount_money_goods,
        	(od.num * od.price) AS sell_price_total,
        	(
        		(od.num * od.price) - ifnull(inout_price_all,(od.num * ifnull(goods_inprice.inprice,0)))
        	)  AS profit_money_total,
        	od.pre_store AS orgin_num,
        	od.now_store AS now_num
        FROM
        	hii_order o
        INNER JOIN hii_order_detail od ON o.order_sn = od.order_sn
        LEFT JOIN hii_goods g ON g.id = od.d_id
        LEFT JOIN hii_goods_cate gc ON g.cate_id = gc.id
       LEFT JOIN (
                   select goods_id,convert(avg(supply_price),DECIMAL(10,2))inprice
                   from hii_goods_supply 
                    where shequ_id != 3
                   group by goods_id                 
                   ) goods_inprice on goods_inprice.goods_id = od.d_id
        WHERE
        	o.create_time >= {$s_time}
        AND o.create_time < {$e_time}
        AND o.pay_type != 3 AND o.pay_type != 4
        AND o.status = 5
        AND o.store_id not in(select id from hii_store where shequ_id = 3)
        AND o.type= 'store'";
$query = $db->query($sql);
$data = array();
if($query !== false){
    $query = $query->fetchAll(PDO::FETCH_ASSOC);
    if(empty($query)){
        $time = $time + (3600*24);
        $db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=1");
        exit('今天没有生成订单');
    }
}else{
    $time = $time + (3600*24);
    $db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=1");
    exit('查询订单表 出错'.json_encode($db->errorInfo()));
}
foreach($query as $key=>$val){
    if(array_key_exists($val['id'],$data)){
        $data[$val['id']]['goods'][] = array(
            'goods_id'=>$val['goods_id'],
            'goods_title'=>$val['goods_title'],
            'cate_id'=>$val['cate_id'],
            'cate_title'=>$val['cate_title'],
            'sell_price'=>$val['sell_price'],
            'sell_num' =>$val['sell_num'],
            'buy_price'=>$val['buy_price'],
            'profit_money'=> sprintf("%.2f",$val['profit_money']),
            'discount_money'=>$val['discount_money_goods'],
            'sell_price_total'=>$val['sell_price_total'],
            'profit_money_total'=>sprintf("%.2f",$val['profit_money_total']),
            'orgin_num'=>$val['orgin_num'],
            'now_num'=>$val['now_num'],
            'remark'=>''
        );
    }else{
        $data[$val['id']]['store_id']  = $val['store_id'];
        $data[$val['id']]['sn']  = $val['sn'];
        $data[$val['id']]['user']  = $val['user'];
        $data[$val['id']]['pay_type']  = $val['pay_type'];
        $data[$val['id']]['pay_type_text']  = $val['pay_type_text'];
        $data[$val['id']]['money']  = $val['money'];
        $data[$val['id']]['pay_money']  = $val['pay_money'];
        $data[$val['id']]['discount_money']  = $val['discount_money'];
        $data[$val['id']]['offer_money']  = $val['offer_money'];
        $data[$val['id']]['log_time']  = $val['log_time'];
        $data[$val['id']]['goods'][]  = array(
            'goods_id'=>$val['goods_id'],
            'goods_title'=>$val['goods_title'],
            'cate_id'=>$val['cate_id'],
            'cate_title'=>$val['cate_title'],
            'sell_num' =>$val['sell_num'],
            'sell_price'=>$val['sell_price'],
            'buy_price'=>$val['buy_price'],
            'profit_money'=> sprintf("%.2f",$val['profit_money']),
            'discount_money'=>$val['discount_money_goods'],
            'sell_price_total'=>$val['sell_price_total'],
            'profit_money_total'=>sprintf("%.2f",$val['profit_money_total']),
            'orgin_num'=>$val['orgin_num'],
            'now_num'=>$val['now_num'],
            'remark'=>''
        );
    }
}
unset($query);
if(empty($data)){
    $time = $time + (3600*24);
    $db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=1");
    exit();
}
$data = array_merge($data);
$md5 = md5(strtolower($url).$api['appid'].$api['appkey'].date('Y-m-d').$time);
$header = array('st:'.$time,'utoken:'.$md5);
$int = 0;
$tiao = 200;
$goods_id_array = array();
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
            $goods_id_array = array_merge($goods_id_array,$a['data']['fail']);
            echo '推送数据未执行的订单号'.json_encode($a['data']['fail']);
//             set_log(json_encode($a['data']['fail']));
        }
    }
    $int += $tiao;
}
$time = $time + (3600*24);
$db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=1");

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