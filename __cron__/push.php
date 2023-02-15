<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'push');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
$data = $db->query("select * from ".DB_PRE."push where `status` = '0' order by `listorder` desc limit 500");

if(!$data) {
    echo 'no data to do!';
    exit;
}

$_data = array();
foreach($data as $v){
    $_data[$v['store_id']][$v['code']][$v['id']] = $v['content'];
}
$do_data = array();
foreach($_data as $k => $v){
    // 当有推送至全部 或 推送的类型有两个以上 则直接推送至全部
    if(isset($v['all']) || (count($v) > 1)){
        $p_ids = array();
        foreach($v as $vk => $d){
            foreach($d as $vk => $_d){
                $p_ids[] = $vk;
            }
        }
        $do_data[$k]['all'] = array(
            'p_ids' => $p_ids,
            'data' => array()
        );
        continue;
    }
    
    if(isset($v['goods_by_id'])){
        $ids = array();
        $p_ids = array();
        foreach($v['goods_by_id'] as $vk => $c){
            $p_ids[] = $vk;
            $_id = json_decode($c, true);
            $_id = explode(',', $_id['data']['id']);
            foreach($_id as $i){
                !in_array($i, $ids) && $ids[] = $i;
            }
        }
        $do_data[$k]['goods_by_id'] = array(
            'p_ids' => $p_ids,
            'data' => array('id' => $ids)
        );
    }elseif(isset($v['del_goods'])){
        $ids = array();
        $p_ids = array();
        foreach($v['del_goods'] as $vk => $c){
            $p_ids[] = $vk;
            $_id = json_decode($c, true);
            $_id = explode(',', $_id['data']['id']);
            foreach($_id as $i){
                !in_array($i, $ids) && $ids[] = $i;
            }
        }
        $do_data[$k]['del_goods'] = array(
            'p_ids' => $p_ids,
            'data' => array('id' => $ids)
        );
    }elseif(isset($v['category'])){
        $ids = array();
        $p_ids = array();
        foreach($v['category'] as $vk => $c){
            $p_ids[] = $vk;
            $_id = json_decode($c, true);
            $_id = explode(',', $_id['data']['id']);
            foreach($_id as $i){
                !in_array($i, $ids) && $ids[] = $i;
            }
        }
        $do_data[$k]['category'] = array(
                'p_ids' => $p_ids,
                'data' => array('id' => $ids)
        );
    }elseif(isset($v['del_category'])){
        $ids = array();
        $p_ids = array();
        foreach($v['del_category'] as $vk => $c){
            $p_ids[] = $vk;
            $_id = json_decode($c, true);
            $_id = explode(',', $_id['data']['id']);
            foreach($_id as $i){
                !in_array($i, $ids) && $ids[] = $i;
            }
        }
        $do_data[$k]['del_category'] = array(
            'p_ids' => $p_ids,
            'data' => array('id' => $ids)
        );
    }
}
if(!$do_data) {
    echo 'no data to do!';
    exit;
}
// 加载推送插件
require_once(ROOT_PATH.'Addons/Push/Lib/Getui/Api.class.php');
$api = new \Addons\Push\Lib\Getui\Api;
$do_id = array();
$no_id = array();
foreach($do_data as $k => $v){
    foreach($v as $vk => $c){
        $val = array(
            'code' => $vk,
            'data' => $c['data']
        );
        $result = $api->pushMessageToApp('推送更新', json_encode($val), 'store_'.$k);
        $result['result'] = 'ok';
        if($result['result'] == 'ok'){
            $do_id = !$do_id ? $c['p_ids'] : array_merge($do_id, $c['p_ids']);
        }else{
            $no_id = !$no_id ? $c['p_ids'] : array_merge($no_id, $c['p_ids']);
            $err = '错误代码为：'.$result['result'];
            set_error($err);
        }
    }
}

$do_id && $db->query("update ".DB_PRE."push set `status` = 1 where `id` in(".implode(',', $do_id).")");
$no_id && $db->query("update ".DB_PRE."push set `status` = 2 where `id` in(".implode(',', $no_id).")");

echo 'success';
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