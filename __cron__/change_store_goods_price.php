<?php
/*---------------------------------
  | 定时修改门店商品价格任务脚本
  +--------------------------------
  | 运行：每分钟执行1次，每次执行时间为55秒。
  +--------------------------------
  | By 马东青 robbie@ude.io
  +--------------------------------
 */
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');

$config  = set_config();

set_time_limit(50);
set_log('----开始执行----');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
$now_time = time();
$max_num = 100; // 最多执行任务条数
// 获取定时生效的价格修改申请
$query = $db->query("select * from ".DB_PRE."goods_store_apply where status = 1 and type = 5 and do_action = 2 and timer_time <= {$now_time} order by id asc limit {$max_num}");
$push = array();
$no_goods = array(); // 未成功修改价格的商品
$apply_ids = array();
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    if($max_num <= 0){
        break;
    }
    $data = json_decode($row['data'], true);
    foreach($data as $d){
        $result = $db->exec("update ".DB_PRE."goods_store set price = '{$d['price']}', update_time = '{$now_time}' where store_id = '{$row['store_id']}' and goods_id = {$d['id']}");
        if($result == 0){
            $no_goods[$row['id']][] = $d['id'];
        }else{
            $item_data = json_encode(array('price' => $d['price']));
            $db->exec("insert into ".DB_PRE."goods_store_change_log  (uid,goods_id,store_id,price,data,create_time) value ('{$row['uid']}', '{$d['id']}', '{$row['store_id']}', '{$d['price']}', '{$item_data}', '{$now_time}')");
            $push[$row['store_id']][] = $d['id'];
        }
        $max_num--;
    }
    $apply_ids[] = $row['id'];
}

$apply_ids && $result = $db->exec("update ".DB_PRE."goods_store_apply set status = '2', update_time = '{$now_time}' where id in (".implode(",", $apply_ids).")");

// 加入推送更新
if($push){
    foreach($push as $k => $v){
        $code = 'goods_by_id';
        $content = json_encode(array(
            'code' => $code,
            'data' => array('id' => implode(',', $v))
        ));
        $db->exec("insert into ".DB_PRE."push (`title`,`code`,`content`,`store_id`,`create_time`,`update_time`) value('推送更新', '{$code}', '{$content}', {$k}, {$now_time}, {$now_time})");
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