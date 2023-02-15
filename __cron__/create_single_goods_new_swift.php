<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-30
 * Time: 14:45
 * 根据年月单独生成某一门店的结算单
 */
header("Content-type: text/html; charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'new_swift');
date_default_timezone_set("PRC");

$time_limit = 600;
set_time_limit($time_limit);

// 连接数据库
$db_config = @include ROOT_PATH . 'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);

// 获取当前年份、月份
// 获取当前年份、月份
$time = time();

$store_id = reqInt("store_id");
$settle_year = reqInt("year");
$settle_month = reqInt("month");
if (!$store_id) {
    echo "请提交门店ID";
    exit;
}
if (!$settle_year) {
    echo "请提交结算年份";
    exit;
}
if (!$settle_month) {
    echo "请提交结算月份";
    exit;
}
$store_data = $db->query("select * from " . DB_PRE . "store where id={$store_id} and `status`=1 limit 1 ")->fetchAll(PDO::FETCH_ASSOC);
if (!$store_data) {
    echo "门店不存在或已关闭";
    exit;
}
$goods_store_new_swift_data = $db->query("select * from " . DB_PRE . "goods_store_new_swift_index where `store_id`={$store_id} and `status`=2 and `year`={$settle_year} and `month`={$settle_month} limit 1 ")->fetchAll(PDO::FETCH_ASSOC);
if ($goods_store_new_swift_data) {
    echo "该月份门店的结算单已存在";
    exit;
}
//所需快照相关资料
$start_statistics_time = 0;//开始统计的时间戳
$end_statistics_time = 0;//结束统计的时间戳
$last_snapshot_id = 0;
$prev_snapshot_id = 0;
$goods_store_new_swift_array = array();
//查找结算月库存快照
$last_snapshot_date = date("Y-m-d", strtotime("+1 months", strtotime("{$settle_year}-{$settle_month}-01")));
$last_snapshot_year = date("Y", strtotime($last_snapshot_date));
$last_snapshot_month = date("m", strtotime($last_snapshot_date));
$last_snapshot_data = $db->query("select * from " . DB_PRE . "goods_store_snapshot_index where `store_id`={$store_id} and `year`={$last_snapshot_year} and `month`={$last_snapshot_month} limit 1 ")->fetchAll(PDO::FETCH_ASSOC);
if ($last_snapshot_data) {
    $end_statistics_time = $last_snapshot_data[0]["create_time"];
    $last_snapshot_id = $last_snapshot_data[0]["id"];
} else {
    $end_statistics_time = strtotime("{$last_snapshot_year}-{$last_snapshot_month}-01 00:00:00");
}
$prev_snapshot_data = $db->query("select * from " . DB_PRE . "goods_store_snapshot_index where `store_id`={$store_id} and `year`={$settle_year} and `month`={$settle_month} limit 1 ")->fetchAll(PDO::FETCH_ASSOC);
if ($prev_snapshot_data) {
    $start_statistics_time = $prev_snapshot_data[0]["create_time"];
    $prev_snapshot_id = $prev_snapshot_data[0]["id"];
} else {
    $start_statistics_time = strtotime("{$settle_year}-{$settle_month}-01 00:00:00");
}
//删除旧信息
$ok = $db->query("delete from hii_goods_store_new_swift_{$settle_year} where `store_id`={$store_id} and `year`={$settle_year} and `month`={$settle_month} ");
if (!$ok) {
    //echo "删除旧结算明细信息失败";
    //exit;
}
$ok = $db->query("delete from hii_goods_store_new_swift_index where `store_id`={$store_id} and `year`={$settle_year} and `month`={$settle_month}  ");
if (!$ok) {
    //echo "删除旧结算索引信息失败";
   // exit;
}
// 插入结款单部分数据
$insert_sql = "insert into " . DB_PRE . "goods_store_new_swift_index(`year`, `month`, `store_id`, `create_time`, `status`,`last_snapshot_id`,`prev_snapshot_id`) ";
$insert_sql .= "value({$settle_year},{$settle_month},{$store_id}," . time() . ",1,{$last_snapshot_id},{$prev_snapshot_id})";
$ok = $db->query($insert_sql);
if (!$ok) {
    echo "结算单索引信息新增失败；SQL：" . $insert_sql;
    exit;
}
// 结算月份初库存快照
$last_snapshot_db_name = DB_PRE . 'goods_store_snapshot_' . $last_snapshot_year;
$sql = "select * from {$last_snapshot_db_name} where `store_id`={$store_id} and `month`={$last_snapshot_month} order by goods_id asc  ";
$last_month_goods_store_snapshot = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if ($last_month_goods_store_snapshot) {
    //读取结算月上个月库存快照
    $prev_month_snapshot_db_name = DB_PRE . 'goods_store_snapshot_' . $settle_year;
    $prev_month_goods_store_snapshot = $db->query("select * from {$prev_month_snapshot_db_name} where `store_id`={$store_id} and `month`={$settle_month}  ")->fetchAll(PDO::FETCH_ASSOC);

    $goods_in_sql = implode(",", _array_column($last_goods_store_snapshot, "goods_id"));
    //读取所有在销商品的入库数据【盘盈入库除外】
    $sql = "select SUM(SISD.g_num) as `total_num`,SISD.goods_id from hii_store_in_stock SIS ";
    $sql .= "left join hii_store_in_stock_detail SISD on SISD.s_in_s_id=SIS.s_in_s_id ";
    $sql .= "where SIS.store_id2={$store_id} and SIS.s_in_s_type<>2 and SIS.ptime between {$start_statistics_time} and {$end_statistics_time} ";
    $sql .= "and SISD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SISD.goods_id ";
    $goods_in_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取所有在销商品的出库数据【盘亏出库除外】
    $sql = "select SUM(SSD.g_num) as `total_num`,SSD.goods_id ";
    $sql .= "from hii_store_out_stock SOS ";
    $sql .= "left join hii_store_stock_detail SSD on SSD.s_out_s_id=SOS.s_out_s_id ";
    $sql .= "where SOS.store_id2={$store_id} and SOS.s_out_s_type<>3 and SOS.ptime between {$start_statistics_time} and {$end_statistics_time} ";
    $sql .= "and SSD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SSD.goods_id ";
    $goods_out_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取商品盘盈数据
    $sql = "select SUM(SISD.g_num) as `total_num`,SISD.goods_id from hii_store_in_stock SIS ";
    $sql .= "left join hii_store_in_stock_detail SISD on SISD.s_in_s_id=SIS.s_in_s_id ";
    $sql .= "where SIS.store_id2={$store_id} and SIS.s_in_s_type=2 and SIS.ptime between {$start_statistics_time} and {$end_statistics_time} ";
    $sql .= "and SISD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SISD.goods_id ";
    $goods_found_in_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取盘亏出库数据
    $sql = "select SUM(SSD.g_num) as `total_num`,SSD.goods_id ";
    $sql .= "from hii_store_out_stock SOS ";
    $sql .= "left join hii_store_stock_detail SSD on SSD.s_out_s_id=SOS.s_out_s_id ";
    $sql .= "where SOS.store_id2={$store_id} and SOS.s_out_s_type=3 and SOS.ptime between {$start_statistics_time} and {$end_statistics_time} ";
    $sql .= "and SSD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SSD.goods_id ";
    $goods_lost_out_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取销售数据【取快照时间段内销售的数量】
    $sql = "select OD.d_id as goods_id,SUM(num) as `total_num`,SUM(inout_price_all) as `total_inout_price_all` ";
    $sql .= "from hii_order O ";
    $sql .= "left join hii_order_detail OD on OD.order_sn=O.order_sn ";
    $sql .= "where O.store_id={$store_id} and O.pay_status=2 and O.pay_time between {$start_statistics_time} and {$end_statistics_time} ";
    $sql .= "and OD.d_id in ({$goods_in_sql}) ";
    $sql .= "group by OD.d_id ";
    $goods_sold_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $total_num = 0;
    $total_money = 0;
    $total_lost_num = 0;

    foreach ($last_month_goods_store_snapshot as $key => $val) {
        /*******
         * `goods_id`,`now_month_num`,`prev_month_num`,`in_num`,
         *`out_num`,`find_num`,`system_lost_num`,`sell_num`,
         * `result_num`,`result_money`,`lost_num`,`lost_rand`,
         * `price`,`inprice`,`year`,`month`,
         * `store_id`,`create_time`,`status`
         ********/
        $goods_id = $val["goods_id"];
        $now_month_num = 0;
        $prev_month_num = 0;
        $in_num = 0;
        $out_num = 0;
        $find_num = 0;
        $system_lost_num = 0;
        $sell_num = 0;
        $result_num = 0;
        $result_money = 0;
        $lost_num = 0;//【暂时无效】
        $lost_rand = 0;
        $price = 0;
        $inprice = 0;
        $inprice_money = 0;
        $year = $settle_year;
        $month = $settle_month;
        $create_time = time();
        $status = $val["status"];

        $goods_store_new_swift_item = array();
        //当前月份【settle_month】快照库存
        $now_month_num = $val["num"];
        //上月快照价格
        $price = $val["price"];
        //获取prev_month_num
        $res = deep_in_array($goods_id, "goods_id", $prev_month_goods_store_snapshot);
        if ($res["status"] == true) {
            $prev_month_num = $res["result"]["num"];
        }
        //获取入库数量
        $res = deep_in_array($goods_id, "goods_id", $goods_in_stock_datas);
        if ($res["status"] == true) {
            $in_num = $res["result"]["total_num"];
        }
        //获取出库数量
        $res = deep_in_array($goods_id, "goods_id", $goods_out_stock_datas);
        if ($res["status"] == true) {
            $out_num = $res["result"]["total_num"];
        }
        //获取找回数量【盘盈入库部分】
        $res = deep_in_array($goods_id, "goods_id", $goods_found_in_stock_datas);
        if ($res["status"] == true) {
            $find_num = $res["result"]["total_num"];
        }
        //获取丢失数量【盘亏出库部分】
        $res = deep_in_array($goods_id, "goods_id", $goods_lost_out_stock_datas);
        if ($res["status"] == true) {
            $system_lost_num = $res["result"]["total_num"];
        }
        //获取销售数量
        $res = deep_in_array($goods_id, "goods_id", $goods_sold_datas);
        if ($res["status"] == true) {
            echo " has sell_num";
            $sell_num = $res["result"]["total_num"];
            $inprice_money = $res["result"]["total_inout_price_all"];
            $inprice = $res["result"]["total_num"] > 0 ? (round($res["result"]["total_inout_price_all"] / $res["result"]["total_num"], 2)) : 0;
        }
        //获取应结数量【result_num】,公式：(prev_month_num-now_month_num)+(in_num-out_num)
        $result_num = ($prev_month_num - $now_month_num) + ($in_num - $out_num);
        //获取应结款【result_money】,公式：result_num*price
        $result_money = $result_num * $price;
        //获取丢失率【lost_rand】,公式：round(lost_num/result_num,4)*100
        $lost_rand = $result_num > 0 ? (round($system_lost_num / $result_num, 4) * 100) : 0;

        //缺省字段
        $goods_store_new_swift_item["lost_num"] = 0;//该字段无效

        $total_num += $result_num;
        $total_money += $result_money;
        $total_lost_num += $system_lost_num;

        $goods_store_new_swift_item = array(
            $goods_id, $now_month_num, $prev_month_num, $in_num,
            $out_num, $find_num, $system_lost_num, $sell_num,
            $result_num, $result_money, $lost_num, $lost_rand,
            $price, $inprice, $inprice_money, $year,
            $month, $store_id, $create_time, $status
        );
        $goods_store_new_swift_array[] = "(" . implode(",", $goods_store_new_swift_item) . ")";
    }
    $val_str = implode(',', $goods_store_new_swift_array);
    $fields = "`goods_id`,`now_month_num`,`prev_month_num`,`in_num`,";
    $fields .= "`out_num`,`find_num`,`system_lost_num`,`sell_num`,";
    $fields .= "`result_num`,`result_money`,`lost_num`,`lost_rand`,";
    $fields .= "`price`,`inprice`,`inprice_money`,`year`,";
    $fields .= "`month`,`store_id`,`create_time`,`status`";
    $insert_into_sql = "insert into `hii_goods_store_new_swift_{$settle_year}`({$fields}) value " . $val_str;
    $data = $db->query($insert_into_sql);
    if (!$data) {
        echo '批量新增结算明细信息失败';
        print_r($db->errorInfo());
        error_snapshot($db, $store_id, $settle_year, $settle_month);
        exit;
    }

    //更新结算索引表信息
    $end_data = array(
        'num' => $total_num,
        'money' => $total_money,
        'lost_num' => $total_lost_num,
        'lost_rand' => $total_num > 0 ? round($total_lost_num / $total_num, 4) * 100 : 0,
        'last_snapshot_id' => $last_snapshot_id,
        'prev_snapshot_id' => $prev_snapshot_id,
    );
    $data = end_snapshot($db, $store_id, $prev_year, $prev_month, $end_data);
    if (!$data) {
        echo "更新结算索引信息失败";
        exit;
    }
    echo "success done!!!";
} else {
    echo "库存快照数据获取失败或不存在;SQL：" . $sql;
}
exit;

function reqInt($name)
{
    if (!empty($_GET[$name])) {
        return intval($_GET[$name]);
    } else {
        return null;
    }
}

function end_snapshot($db, $store_id, $year, $month, $data = array())
{
    $string = array('status = 2');
    foreach ($data as $k => $v) {
        $string[] = $k . '="' . $v . '"';
    }
    $string = implode(' , ', $string);
    $data = $db->query("update " . DB_PRE . "goods_store_new_swift_index set " . $string . " where store_id = " . $store_id . " and year = " . $year . " and month = " . $month);
    //set_log('更新状态为已结款');
    return $data;
}

function error_snapshot($db, $store_id, $year, $month)
{
    $data = $db->query("update " . DB_PRE . "goods_store_new_swift_index set status = -1 where store_id = " . $store_id . " and year = " . $year . " and month = " . $month);
    set_log('更新状态为结款失败');
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

/*******************
 * 检测二维数组里面是否包含某个值
 * @param $value 要查找的值
 * @param $array 二维数组
 * @return array
 */
function deep_in_array($value, $columnName, $array)
{
    if (!is_array($array) || is_null($array) || empty($array) || count($array) == 0) {
        return array("status" => false);
    }
    foreach ($array as $key => $val) {
        if ($val[$columnName] == $value) {
            return array("status" => true, "result" => $val);
        }
    }
    return array("status" => false);
}

function _array_column($input, $columnKey, $indexKey = null)
{
    if (!function_exists('array_column')) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array)$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    } else {
        return array_column($input, $columnKey, $indexKey);
    }
}

