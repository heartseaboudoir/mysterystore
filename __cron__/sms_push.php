<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'push');

set_time_limit(50);

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
global $db;
define('DB_PRE', $db_config['DB_PREFIX']);
// 获取前20条
$data = $db->query("select * from ".DB_PRE."sms_push where `status` = '0' order by `create_time` asc limit 20");
if(!$data) {
    echo 'no data to do!';
    exit;
}

$n_data = array();

foreach($data as $k => $v){
	$_mobile = explode(',', $v['mobile']);
	$set = true;
	// 处理同个类型中已经有相同号码发送的
	foreach($_mobile as $_v){
		if(empty($_data[$v['tpl']]) || !in_array($_v, $_data[$v['tpl']])){
			$_data[$v['tpl']][] = $_v;
		}else{
			$set = false;
			break;
		}
	}
	$set && $n_data[] = $v;
}

$do_id = array();   // 成功发送
$no_id = array();   // 未成功发送
$end_id = array();  // 发送次数已超，不再发送
foreach($n_data as $v){
	$result = send_sms($v['mobile'], $v['tpl'], $v['param']);
	
	if($result['status'] == 1){
		$do_id[] = $v['id'];
	}else{
		// 2次发送不成功而直接失败
		if($v['times'] >= 2){
			$end_id[] = $v['id'];
		}else{
			$no_id[] = $v['id'];
		}
	}
}

$do_id && $db->query("update ".DB_PRE."sms_push set `status` = 1 where `id` in(".implode(',', $do_id).")");
$no_id && $db->query("update ".DB_PRE."sms_push set `status` = 0, `times` = `times`+1 where `id` in(".implode(',', $no_id).")");
$end_id && $db->query("update ".DB_PRE."sms_push set `status` = 2 where `id` in(".implode(',', $end_id).")");

echo 'success';
exit;
function send_sms($mobile, $tpl, $param = array()){
    if(!$tpl){
        return array('status' => 0, 'info' => '短信模板未知');
    }
    $param = is_array($param) ? ($param ? json_encode($param) : '') : $param;
    $sign = '神秘商店';
    include_once dirname(dirname(__file__)).'/Addons/Alisms/Lib/SmsLib.class.php';
    $SmsLib = new Addons\Alisms\Lib\SmsLib();
    global $db;
    $result = $SmsLib->send($mobile, $param, $tpl, $sign, $db);
    
    if($result['status'] == 1){
        return array('status' => 1);
    }else{
        return array('status' => 0, 'info' => $result['msg']);
    }
}

function http($url, $params='', $method = 'GET', $header = array(), $multi = false){
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header
    );
    switch(strtoupper($method)){
        case 'GET':
            $param = is_array($params)?'?'.http_build_query($params):'';
            $opts[CURLOPT_URL] = $url . $param;
            break;
        case 'POST':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    return  $data;
}

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
        !$caiji_config && $caiji_config = array('page' => 1, 'page_len' => 3, 'type' => 'one');
        $config && $caiji_config = array_merge($caiji_config, $config);
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