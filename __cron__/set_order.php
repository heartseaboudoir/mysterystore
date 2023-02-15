<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'set_order');

$config  = set_config();
// 判断今日是否已完成
set_config(array('do_date' => date('Y-m-d')));
set_time_limit(50);
set_log('----开始执行----');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);

// 获取超时时间配置
$data = $db->query("select * from ".DB_PRE."config where name in ('ORDER_CANCEL_TIMES','ORDER_RECEIPT_TIMES') limit 2");
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

$now_time = time();
// 自动取消超时订单
if(isset($time_data['ORDER_CANCEL_TIMES'])){
	$out_time = $time_data['ORDER_CANCEL_TIMES']*3600;
	if($out_time > 0){
		// 最小值为1小时
		if($out_time < 3600){
			$out_time = 3600;
		}
		$last_time = $now_time - $out_time;

		$db->exec("update ".DB_PRE."order set status = 3,update_time = ".$now_time." where pay_status = 1 and status = 1 and create_time < ".$last_time);
	}
}
// 自动确认收货（针对线上订单）
if(isset($time_data['ORDER_RECEIPT_TIMES'])){
	$out_time = $time_data['ORDER_RECEIPT_TIMES']*3600*24;
	if($out_time > 0){
		// 最小值为3天
		$limit_time = 3600*24*3;
		if($out_time < $limit_time){
			$out_time = $limit_time;
		}
		$last_time = $now_time - $out_time;

        $sql = "select * from " . DB_PRE . "order where status = 4 and type in ('online', 'shop') and express_time < " . $last_time . " limit 30";
        
        //echo $sql;
        
        $stmt=$db->query($sql);
        foreach ($stmt as $key => $val) {
            
            
            $oinfo =  array(
                'order_sn' => $val['order_sn'],
                'uid' => $val['uid'],
            );
            
            $data = request('/Order/auto_receipt', $oinfo);
            
            set_log($val['order_sn']);
            
            print_r($oinfo);
            
            print_r($data);
        }    
    }
}
echo 'success';
set_log('----操作完成----');
exit;

function set_log($data, $time = 1){
	$log_dir = ROOT_PATH.'Runtime/'.CAIJI_NAME.'_logs/';
	if(!is_dir($log_dir)){
		mkdir($log_dir, 0777, true);
	}
	file_put_contents($log_dir.date('Y-m').'.txt', ($time ? "[".date('Y-m-d H:i:s')."] " : '').$data."\r\n", FILE_APPEND);
}
function set_config($config = array()){
    $caiji_config_file = ROOT_PATH.'Runtime/'.CAIJI_NAME.'.php';
    $dir = dirname($caiji_config_file);
    if(!is_dir($dir)){
            mkdirp($dir);
    }
    if(file_exists($caiji_config_file)){
        $caiji_config =  @include $caiji_config_file;
        !$caiji_config && $caiji_config = array();
        $config && $caiji_config = $caiji_config ? array_merge($caiji_config, $config) : $config;
    }else{
        $caiji_config = $config;
    }
    file_put_contents($caiji_config_file, '<?php return '.var_export($caiji_config, true).';');
    return $caiji_config;
}
function set_error($str = ''){
    set_log($str);
    set_log('--------------------------------------------------------------------------------------------------------', 0);
    set_config(array('msg' => $str, 'last_e_time' => date('Y-m-d H:i:s')));
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