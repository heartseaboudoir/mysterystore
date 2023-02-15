<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'set_hot');
$config  = set_config();
// 判断今日是否已完成
if($config && isset($config['do_date']) && $config['do_date'] == date('Y-m-d') && isset($config['is_end']) && $config['is_end'] == true){
	set_log('----今日门店热度已更新结束，直接退出----');
	exit;
}elseif($config && isset($config['do_date']) && $config['do_date'] != date('Y-m-d')){
	$config = set_config(array('store_id' => 0, 'is_end' => false));
}
set_config(array('do_date' => date('Y-m-d')));
set_time_limit(55);
set_log('----开始执行----');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
// 获取门店ID
$pid = isset($config['store_id']) && $config['store_id'] > 0 ? $config['store_id'] : 0;
$where = $pid > 0 ? (' where id > '.$pid) :'';
$result = $db->query("select * from ".DB_PRE."store ".$where." order by id asc limit 1");
$rs = $result->fetchAll();
if(!$rs) {
	set_config(array('is_end' => true));
	set_log('----未找到任何门店，退出操作----');
	exit;
}
foreach($rs as $v){
	$store_id = $v['id'];
	$store_title = $v['title'];
}
set_config(array('store_id' => $store_id));

set_log('成功获取门店【'.$store_title.'】 id为'.$store_id);
$table_name = DB_PRE."goods_sell_log{$store_id}";
$result = $db->query("show tables like '".$table_name."';");
$rs = $result->fetchAll();
if(!$rs){
	set_log('----门店未生成记录表，退出操作----');
	exit;
}
$s_date = date('Y-m-d', strtotime('-30 day'));
$e_date = date('Y-m-d', strtotime('-1 day'));
$where = " `date` BETWEEN '".$s_date."' AND '".$e_date."'";
$result = $db->query("select * from ".$table_name." where ".$where);
$lists = $result->fetchAll();
set_log('在门店销售记录中获取条'.count($lists).'记录');
$all_num = 0;
$store_num = array();
foreach($lists as $v){
	$all_num += $v['num'];
	if(isset($store_num[$v['goods_id']])){
		$store_num[$v['goods_id']] += $v['num'];
	}else{
		$store_num[$v['goods_id']] = $v['num'];
	}
}
$db->exec("update ".DB_PRE."goods_store set `hot_val` = 1 where store_id = ".$store_id);

set_log('更新热度值 总销量为：'.$all_num);
foreach($store_num as $k => $v){
	// 热度值  当前商品30天销量/全部商号30天销量*100
	$hot_val = round($v/$all_num*100, 1);
	$hot_val > 10 && $hot_val = 10;
	$hot_val < 1 && $hot_val = 1;
	$db->exec("update ".DB_PRE."goods_store set `hot_val` = ".$hot_val.", `last_num` = ".$v." where `goods_id` = ".$k." and store_id = ".$store_id);
}
set_log('添加推送更新进度');
$db->exec("insert into ".DB_PRE."push (`title`, `code`, `status`, `store_id`, create_time, update_time) values('推送更新', 'all', 0, ".$store_id.",".time().",".time().")");

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