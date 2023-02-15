<?php
/**
 * 月结算记录 每个月门店结算产生记录时调用，将当前门店的结算数据传递过来。 每月执行 门店结款单执行完后执行   2017推送
 */
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');
$url = 'http://udeana.dev.hiiyun.com/api/Sale/settlement_ls';
$api = array(
    'appid' => 'zhaike',
    'appkey' => 'a887ff563347d216a5d0cc0413f89gf0'
);
set_time_limit(300);
$file_url = __FILE__;
// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
$sql = "select log_time from hii_goods_log_apiwiki where id = 3 ";
$query = $db->query($sql);
if($query !== false){
    $query = $query->fetch(PDO::FETCH_ASSOC);
    if(empty($query)){
        exit('没有时间');
    }
    $time_da = date('Y-m-d',$query['log_time']);
    $year = date('Y', strtotime($time_da.'-1 month'));
    $month = date('n', strtotime($time_da.'-1 month'));
    if($year.$month >= 20188){
        exit('不是2017年数据');
    }
}else{
    exit('没有时间');
}

// 获取最后一个操作的门店
$goods = $db->query("select store_id from hii_script_file_log where year={$year} and month={$month} order by store_id desc limit 1");
$data = array();
if($goods !== false){
    $data = $goods->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($data)){
        $store_id = $data[0]['store_id'];
    }else{
        $store_id = 0;
    }
}
// 获取要执行的门店并存入 hii_script_file_log表
$goods = $db->query("select id as store_id from hii_store  where shequ_id != 3 AND id > {$store_id} order by id asc limit 6");
$data = array();
if($goods !== false){
    $data = $goods->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($data)){
        $store_id_new = $data;

    }else{
        $time_da = strtotime($time_da.'+1 month');
        $db->exec("update hii_goods_log_apiwiki set log_time = {$time_da} where id=3");
        exit('没有要执行的门店');
    }
}
foreach($store_id_new as $key=>$val){
    $data = $db->exec("insert into hii_script_file_log(script_file_url,year,month,store_id) values('{$file_url}','{$year}','{$month}','{$val['store_id']}')");
    $hii_script_file_log_id = $db->lastInsertId();

    $sql = "SELECT
        	GSNS.store_id,
        	GSNS.goods_id,
        	ifnull(if(GSNS.inprice,GSNS.inprice,goods_inprice.inprice),0) AS buy_price,
        	GSNS.price AS sell_price,
        	GSNS.now_month_num,
        	GSNS.prev_month_num,
        	GSNS.in_num,
        	GSNS.out_num,
        	GSNS.find_num,
        	GSNS.system_lost_num AS lost_num,
        	GSNS.sell_num AS sy_sell_num,
        	GSNS.result_num AS sell_num,
            GSNS.`year` as `year`,
            GSNS.`month` as `month`,
            GSNS.create_time as log_time
        FROM
        	hii_goods_store_new_swift_{$year} GSNS
          LEFT JOIN (
                   select goods_id,convert(avg(supply_price),DECIMAL(10,2))inprice
                   from hii_goods_supply 
                    where shequ_id != 3
                   group by goods_id                 
                   ) goods_inprice on goods_inprice.goods_id = GSNS.goods_id
        WHERE
        	GSNS.`year` = {$year}
        AND GSNS.`month` = {$month}
        AND GSNS.store_id = {$val['store_id']}";
    $goods = $db->query($sql);
    $data = array();
    if($goods !== false){
        $goods = $goods->fetchAll(PDO::FETCH_ASSOC);
        if(!empty($goods)){
            $time = time();
            $md5 = md5(strtolower($url).$api['appid'].$api['appkey'].date('Y-m-d').$time);
            $header = array('st:'.$time,'utoken:'.$md5);
            $int = 0;
            $tiao = 200;
     while (true) {
                $array = array_slice($goods,$int,$tiao);
                $count = count($array);
                if(empty($array)){
                    break;
                }
                $array = json_encode($array);
                $a =  http_post($url, array('data'=>$array,'row'=>$count), $header);
                $a = json_decode($a,true);
                if($a['status'] == 1){
                    if(!empty($a['data']['fail']))
                        $data[] = $a['data']['fail'];

                }else{
                    $data[] = $a;
                }
                $int += $tiao;
            }
            $status_log = 2;
        }else{
            $status_log = 3;
            $data[] =urlencode( "门店没有生成结款单 没有推送数据");
        }
    }else{
        $status_log = 3;
        $data[] = urlencode( "门店没有生成结款单 没有推送数据");
    }
    $json = json_encode($data);
    $json = urldecode($json);
    $db->exec("update hii_script_file_log set `ststus`={$status_log},script_log='{$json}' where id={$hii_script_file_log_id}");

}

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
