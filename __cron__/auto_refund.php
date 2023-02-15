<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'auto_refund');

set_time_limit(99999);
set_log('----开始执行----');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';




//参数设置
$opt=array(
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,//异常模式		
        //PDO::ATTR_PERSISTENT=>true,//是否开启持久连接,即程序执行完之后仍不消毁
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true,
        PDO::ATTR_AUTOCOMMIT=>true,//是否自动提交,默认开启
        PDO::ATTR_CASE=>PDO::CASE_LOWER,//表单列强制取出来不大/小/不变
        PDO::ATTR_ORACLE_NULLS=>PDO::NULL_NATURAL,//如何处理数据空白
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,//取出数据的模式
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    );
//生成数据库连接
try{
    $db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD'], $opt);
}catch(PDOException $e){
    set_log("ERROR:".$e->getMessage()."---LINE:".$e->getLine());
    exit;
} 
 


define('DB_PRE', $db_config['DB_PREFIX']);

// 获取超时时间配置
$data = $db->query("select * from ".DB_PRE."config where name in ('ORDER_REFUND_TIME') limit 1");
$out_time = 0;
$time_data = array();
if($data){
    foreach($data as $v){
		$time_data[$v['name']] = intval($v['value']);
    }
}else{
	echo 'has no config';
	set_log('----找不到系统设置值，退出----');
	exit;

}
// ----------------------------------------------------------------------------
// 自动确认收货（针对线上订单）
$now_time = time();
if(isset($time_data['ORDER_REFUND_TIME'])){
	$out_time = $time_data['ORDER_REFUND_TIME']*3600*24;
	if($out_time < 0){
        set_log('----out_time ng----');    
        exit;
    }
    
    
    $limit_time = 3600*24*1;
    if($out_time < $limit_time){
        $out_time = $limit_time;
    }
    
    
    // test
    //$out_time = 10;
    
    $last_time = $now_time - $out_time;
    
    
    
    $sql = "select * from " . DB_PRE . "order_refund r join " . DB_PRE . "order o on o.order_sn = r.order_sn where o.refund_status = 1 and r.status = 1 and o.create_time < " . $last_time . " limit 10";
    

    $stmt=$db->query($sql);
    foreach ($stmt as $key => $val) {
        
        
        $oinfo =  array(
            'order_sn' => $val['order_sn'],
            'sid' => $val['store_id'],
        );
        
        $data = request('/Order/auto_refund', $oinfo);
        
        print_r($oinfo);
        
        print_r($data);
    }
    
    echo "\n";

}



// ----------------------------------------------------------------------------







set_log('----操作完成----');
exit;

function set_log($data, $time = 1){
	$log_dir = ROOT_PATH.'Runtime/'.CAIJI_NAME.'_logs/';

	if(!is_dir($log_dir)){
		mkdir($log_dir, 0777, true);
	}
	file_put_contents($log_dir.date('Y-m').'.txt', ($time ? "[".date('Y-m-d H:i:s')."] " : '').$data."\r\n", FILE_APPEND);
}

function set_error($str = ''){
    set_log($str);
    set_log('--------------------------------------------------------------------------------------------------------', 0);
    set_log(array('msg' => $str, 'last_e_time' => date('Y-m-d H:i:s')));
    echo 'error: '.$str;
    exit;
}


function request($url, $data = array())
{
    
    $domain = 'https://v.imzhaike.com/Apiv2';
    
    
    $url = $domain . $url;
    
    $device = 0;
    $version = '';
    $key = '$ZaiKe$ByApi$';
    
    $url = trim(strtolower($url));
    $utoken = md5($url . $key . date('Y-m-d'));
    
    
    
    
    //$data = array('content' => $content);
    //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
    
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'UTOKEN: ' . $utoken,
            //'Content-Type: application/json',
            //'Content-Length: ' . strlen($json),
        )
    );
    $result = curl_exec($ch);
    
    $result = json_decode($result, true);
    return $result;
} 