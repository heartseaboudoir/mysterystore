<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'snapshot');

set_time_limit(600);

// 连接数据库
$db_config = @include ROOT_PATH . 'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);

// 获取当前年份、月份
$time = time();
$in_date = $time;
$year = date('Y', $in_date);
$month = date('m', $in_date);

// 库存快照表名（按年份）
$db_name = DB_PRE . 'goods_store_snapshot_' . $year;
// 生成库存快照表（没有则创建）g_price 使用批次平均价还是待定
$create_db_sql = 'CREATE TABLE `' . $db_name . '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) DEFAULT NULL,
  `cate_id` int(11) DEFAULT NULL,
  `num` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `g_price` decimal(10,2) DEFAULT NULL,
  `year` mediumint(4) DEFAULT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_id` (`goods_id`,`year`,`month`,`store_id`),
  KEY `store_id` (`store_id`),
  KEY `year` (`year`,`month`,`store_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;';

$data = $db->query($create_db_sql);

// 插入快照记录
// 获取系统商品数据
$gdata = $db->query("select id,cate_id,sell_price,status from " . DB_PRE . "goods");
foreach ($gdata as $v) {
    $_gdata[$v['id']] = $v;
}
if (!$gdata) {
    echo ' store no goods to do!';
    exit;
}
// 获取门店当前商品信息
//$data = $db->query("select * from " . DB_PRE . "goods_store");
$data = $db->query("
  select GS.id,GS.goods_id,GS.store_id,GS.num,GS.sell_num,GS.price,GS.shequ_price,GS.status,GS.update_time,GS.month_num,GS.hot_val,GS.last_num,GS.dealer_id,
  WIV.stock_price as g_price
  from hii_goods_store GS
  left join hii_store S on S.id=GS.store_id
  left join hii_warehouse_inout_view WIV on WIV.shequ_id=S.shequ_id and WIV.goods_id=GS.goods_id
");
if (!$data) {
    echo 'no goods_data to do!';
    exit;
}
// 获取快照数组
$val_arr = array();
$store_id = array();
foreach ($data as $v) {
    if (isset($_gdata[$v['goods_id']])) {
        //($v['price'] && $v['price'] > 0) ? $v['price'] : (isset($_gdata[$v['goods_id']]['sell_price']) ? $_gdata[$v['goods_id']]['sell_price'] : 0),
        $price = 0;
        if ($v["price"] && $v["price"] > 0) {
            $price = $v["price"];
        } elseif (!is_null($v["shequ_price"]) && !empty($v["shequ_price"]) && $v["shequ_price"] > 0) {
            $price = $v["shequ_price"];
        } elseif (isset($_gdata[$v['goods_id']]['sell_price'])) {
            $price = $_gdata[$v['goods_id']]['sell_price'];
        }
        $_val = array(
            $v['goods_id'],
            $_gdata[$v['goods_id']]['cate_id'],
            $v['num'],
            $price,
            $v["g_price"],
            $year,
            $month,
            $_gdata[$v['goods_id']]['status'],
            $v['store_id'],
            $time,
        );
    } else {
        // 未知的数据
        $_val = array(
            $v['goods_id'],
            0,
            $v['num'],
            $v['price'],
            $v["g_price"],
            $year,
            $month,
            0,
            $v['store_id'],
            $time,
        );
    }
    $val_arr[] = '("' . implode('","', $_val) . '")';
    !in_array($v['store_id'], $store_id) && $store_id[] = $v['store_id'];
}
if (!$val_arr) {
    echo 'no goods_data to do!';
    exit;
}
// 组合sql并添加快照
$val_str = implode(',', $val_arr);
$insert_sql = 'insert into ' . $db_name . ' (goods_id, cate_id, num, price,g_price, year, month, status, store_id, create_time) value ' . $val_str;

$data = $db->query($insert_sql);
if (!$data) {
    echo 'snapshot do error!';
    print_r($db->errorInfo());
    exit;
}
$val_arr = array();
foreach ($store_id as $v) {
    $_val = array(
        $year,
        $month,
        $v,
        $time,
    );
    $val_arr[] = '("' . implode('","', $_val) . '")';
}
// 组合sql并添加快照
$val_str = implode(',', $val_arr);
$data = $db->query("insert into " . DB_PRE . "goods_store_snapshot_index (year, month, store_id, create_time) value " . $val_str);
if ($data) {
    echo 'success';
} else {
    print_r($db->errorInfo());
}

function http($url, $params = '', $method = 'GET', $header = array(), $multi = false)
{
    $opts = array(
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $header
    );
    switch (strtoupper($method)) {
        case 'GET':
            $param = is_array($params) ? '?' . http_build_query($params) : '';
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
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
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

function set_config($config = array())
{
    $caiji_config_file = ROOT_PATH . 'Runtime/' . CAIJI_NAME . '.php';
    $dir = dirname($caiji_config_file);
    if (!is_dir($dir)) {
        mkdirp($dir);
    }
    if (file_exists($caiji_config_file)) {
        $caiji_config = @include $caiji_config_file;
        !$caiji_config && $caiji_config = array('page' => 1, 'page_len' => 3, 'type' => 'one');
        $config && $caiji_config = array_merge($caiji_config, $config);
    } else {
        $caiji_config = $config;
    }
    file_put_contents($caiji_config_file, '<?php return ' . var_export($caiji_config, true) . ';');
    return $caiji_config;
}

function set_error($str = '')
{
    set_log($str);
    set_log('--------------------------------------------------------------------------------------------------------', 0);
    set_config(array('msg' => $str, 'last_e_time' => date('Y-m-d H:i:s')));
    echo 'error: ' . $str;
    exit;
}