<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-25
 * Time: 11:41
 * 生成每月结算单【定期执行】
 * 操作表：hii_goods_store_new_swift_index,hii_goods_store_new_swift_年份
 */
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'new_swift');
date_default_timezone_set("PRC");

$time_limit = 10;
set_time_limit($time_limit);

//读取已开启新版ERP系统的区域
//$open_erp_datas = $db->query("select id from hii_shequ where newerp=1 order by id ASC ")->fetchAll(PDO::FETCH_ASSOC);
//$shequ_ids = implode(",", $open_erp_datas);//要结款区域门店
//$shequ_ids = "16";//要结款的区域门店,多个区域用逗号[英文]隔开

// 连接数据库
$db_config = @include ROOT_PATH . 'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);

// 获取当前年份、月份
// 获取当前年份、月份
$time = time();

$in_date = strtotime('-1 month');
//$year = date('Y', $in_date);
//$month = date('m', $in_date);

$current_date = $time;
$current_year = date('Y', $current_date);//当前年份
$current_month = date('m', $current_date);//当前月份

$prev_date = strtotime('-1 month');
$prev_year = date('Y', $prev_date);//上一月份的年份
$prev_month = date('m', $prev_date);//上一月份

$front_date = strtotime('-2 month');
$front_year = date("Y", $front_date);//上两月份的年份
$front_month = date("m", $front_date);//上两月份

// 结款单年表名（按年份）
$db_name = DB_PRE . 'goods_store_new_swift_' . $current_year;

// 生成结款单年表（没有则创建）
$create_db_sql = "CREATE TABLE `" . $db_name . "` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) DEFAULT NULL,
  `now_month_num` int(11) DEFAULT '0',
  `prev_month_num` int(11) DEFAULT '0',
  `in_num` int(11) DEFAULT '0',
  `out_num` int(11) DEFAULT '0',
  `find_num` int(11) DEFAULT '0',
  `system_lost_num` int(11) DEFAULT '0',
  `sell_num` int(10) unsigned DEFAULT '0',
  `result_num` int(11) DEFAULT '0',
  `result_money` decimal(10,2) DEFAULT '0.00',
  `lost_num` int(11) DEFAULT '0',
  `lost_rand` decimal(5,2) DEFAULT '0.00',
  `price` decimal(10,2) DEFAULT '0.00',
  `inprice` decimal(10,2) DEFAULT '0.00',
  `inprice_money` decimal(10,2) DEFAULT '0.00',
  `year` mediumint(4) DEFAULT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `store_id` int(11) DEFAULT '0',
  `create_time` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 0,
  `inout_num` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_id` (`goods_id`,`year`,`month`,`store_id`),
  KEY `store_id` (`store_id`),
  KEY `year` (`year`,`month`,`store_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
$data = $db->query($create_db_sql);

// 获取最后一个操作的门店
$data = $db->query("select * from " . DB_PRE . "goods_store_new_swift_index where year = " . $prev_year . " and month = " . $prev_month . " order by store_id desc limit 1");
$prev_store_id = 0;
//检测上一间门店是否处理完成，假如还在处理中就退出
if ($data) {
    foreach ($data as $v) {
        if ($v['status'] == 1) {
            if ($v['create_time'] + $time_limit >= $time) {
                // 如果5分钟内正在进行就不操作
                echo 'the prve is doing';
                set_log('门店' . $v['store_id'] . ' 正在进行,退出操作');
                exit;
            } else {
                //超过600秒而设为结款失败
                echo 'the prve is out time';
                set_log('门店' . $v['store_id'] . ' 已超过时限未成功结款，结款失败');
                error_snapshot($db, $v['store_id'], $prev_year, $prev_month);
                exit;
            }
        }
        $prev_store_id = $v['store_id'];
    }
}
// 获取将要操作的门店
$data = $db->query("select * from " . DB_PRE . "store where  id > " . $prev_store_id . " order by id asc limit 1");
if (!$data) {
    echo 'get store error!';
    set_log('获取门店失败');
    exit;
}
$store_id = 0;
foreach ($data as $v) {
    $store_id = $v['id'];
}
if (!$store_id) {
    echo 'no store to do!';
    set_log('没有需要操作的门店');
    exit;
}
set_log('----开始对门店[' . $store_id . '],' . $prev_month . '月份的月结单 【开始时间：' . $time . '】 ----');
//查找库存快照，用于获取用于统计的时间段
$start_statistics_time = 0;//开始统计的时间戳
$end_statistics_time = 0;//结束统计的时间戳
$last_snapshot_id = 0;
$prev_snapshot_id = 0;
$goods_store_new_swift_array = array();
//查看最后一次快照(本月拍的)
$sql = "select * from " . DB_PRE . "goods_store_snapshot_index where `store_id`={$store_id} and `year`={$current_year} and `month`={$current_month} order by create_time desc limit 1 ";
$data = $db->query($sql);
if ($data) {
    //存在本月快照，读取记录时间
    foreach ($data as $key => $val) {
        $last_snapshot_id = $val["id"];
        $end_statistics_time = $val["create_time"];
    }
} else {
    //不存在本月拍的快照，按零时零分计算
    $end_statistics_time = strtotime("{$current_year}-{$current_month}-01 00:00:00");
}
//查看上个月拍的快照
$data = $db->query("select * from " . DB_PRE . "goods_store_snapshot_index where `store_id`={$store_id} and `year`={$prev_year} and `month`={$prev_month} order by create_time desc limit 1 ");
if ($data) {
    foreach ($data as $key => $val) {
        $prev_snapshot_id = $val["id"];
        $start_statistics_time = $val["create_time"];
    }
} else {
    $start_statistics_time = strtotime("{$prev_year}-{$prev_month}-01 00:00:00");
}
// 插入结款单部分数据
$sql = "insert into " . DB_PRE . "goods_store_new_swift_index (`year`, `month`, `store_id`, `create_time`, `status`,`last_snapshot_id`,`prev_snapshot_id`) ";
$sql .= "value ({$prev_year},{$prev_month},{$store_id}," . time() . ", 1,{$last_snapshot_id},{$prev_snapshot_id})";
$data = $db->query($sql);
if (!$data) {
    echo 'add swift error';
    set_log('插入结款单记录失败');
    exit;
}
// 本月初库存快照
$this_month_snapshot_db_name = DB_PRE . 'goods_store_snapshot_' . $current_year;
$this_month_goods_store_snapshot = $db->query("select * from {$this_month_snapshot_db_name} where `store_id`={$store_id} and `month`={$current_month} order by goods_id asc  ")->fetchAll(PDO::FETCH_ASSOC);
if ($this_month_goods_store_snapshot) {
    //有销售商品，读取上个月库存快照
    $prev_month_snapshot_db_name = DB_PRE . 'goods_store_snapshot_' . $prev_year;
    $prev_month_goods_store_snapshot = $db->query("select * from {$prev_month_snapshot_db_name} where `store_id`={$store_id} and `month`={$prev_month}  ")->fetchAll(PDO::FETCH_ASSOC);

    $goods_in_sql = implode(",", _array_column($this_month_goods_store_snapshot, "goods_id"));
    //读取所有在销商品的入库数据【盘盈入库除外】
    $sql = "select SUM(SISD.g_num) as `total_num`,SISD.goods_id from hii_store_in_stock SIS ";
    $sql .= "left join hii_store_in_stock_detail SISD on SISD.s_in_s_id=SIS.s_in_s_id ";
    $sql .= "where SIS.store_id2={$store_id} and SIS.s_in_s_type<>2 and SIS.ptime between {$start_statistics_time} and {$end_statistics_time} and SISD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SISD.goods_id ";
    $goods_in_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取所有报损退货数量 作为入库
    $sql = "select SUM(SOD.g_num) as `total_num`,SOD.goods_id from hii_store_other_out SO ";
    $sql .= "left join hii_store_other_out_detail SOD on SO.s_o_out_id=SOD.s_o_out_id ";
    $sql .= "where SO.store_id1 = {$store_id} and (s_o_out_type = 1 or s_o_out_type =5) and SO.ptime between {$start_statistics_time} and {$end_statistics_time} and SOD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SOD.goods_id ";
    $goods_store_other_out_num = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取所有在销商品的出库数据【盘亏出库除外】
    $sql = "select SUM(SSD.g_num) as `total_num`,SSD.goods_id ";
    $sql .= "from hii_store_out_stock SOS ";
    $sql .= "left join hii_store_stock_detail SSD on SSD.s_out_s_id=SOS.s_out_s_id ";
    $sql .= "where SOS.store_id2={$store_id} and SOS.s_out_s_type<>3 and SOS.ptime between {$start_statistics_time} and {$end_statistics_time} and SSD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SSD.goods_id ";
    $goods_out_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取所有返仓商品的数据作为出库数据
    $sql = "select SUM(SBD.g_num) as `total_num`,SBD.goods_id ";
    $sql .= "from hii_store_back_detail SBD ";
    $sql .= "left join hii_store_back SB on SB.s_back_id=SBD.s_back_id ";
    $sql .= "where SB.store_id={$store_id} and SB.s_back_status=1 and SB.ptime between {$start_statistics_time} and {$end_statistics_time} and SBD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SBD.goods_id ";
    $goods_back_to_warehouse_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取商品盘盈数据
    $sql = "select SUM(SISD.g_num) as `total_num`,SISD.goods_id from hii_store_in_stock SIS ";
    $sql .= "left join hii_store_in_stock_detail SISD on SISD.s_in_s_id=SIS.s_in_s_id ";
    $sql .= "where SIS.store_id2={$store_id} and SIS.s_in_s_type=2 and SIS.ptime between {$start_statistics_time} and {$end_statistics_time} and SISD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SISD.goods_id ";
    $goods_found_in_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取盘亏出库数据
    $sql = "select SUM(SSD.g_num) as `total_num`,SSD.goods_id ";
    $sql .= "from hii_store_out_stock SOS ";
    $sql .= "left join hii_store_stock_detail SSD on SSD.s_out_s_id=SOS.s_out_s_id ";
    $sql .= "where SOS.store_id2={$store_id} and SOS.s_out_s_type=3 and SOS.ptime between {$start_statistics_time} and {$end_statistics_time} and SSD.goods_id in ({$goods_in_sql}) ";
    $sql .= "group by SSD.goods_id ";
    $goods_lost_out_stock_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取销售数据【取快照时间段内销售的数量】
    $sql = "select OD.d_id as goods_id,SUM(num) as `total_num`,SUM(inout_price_all) as `total_inout_price_all` ";
    $sql .= "from hii_order O ";
    $sql .= "left join hii_order_detail OD on OD.order_sn=O.order_sn ";
    $sql .= "where O.store_id={$store_id} and O.pay_status=2 and O.pay_time between {$start_statistics_time} and {$end_statistics_time} and OD.d_id in ({$goods_in_sql}) ";
    $sql .= "group by OD.d_id ";
    $goods_sold_datas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //读取盘点盈亏数量【取快照时间段内盘点数量总和】
    $sql = "select SID.goods_id,SUM(g_num-b_num) as inout_num ";
    $sql .= "from hii_store_inventory_detail SID ";
    $sql .= "left join hii_store_inventory SI on SI.si_id=SID.si_id ";
    $sql .= "where SI.store_id={$store_id} and SI.si_status=1 and SI.ptime between {$start_statistics_time} and {$end_statistics_time} ";
    $sql .= "group by SID.goods_id ";
    $store_inventory_inout_num = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);


    $total_num = 0;
    $total_money = 0;
    $total_lost_num = 0;

    foreach ($this_month_goods_store_snapshot as $key => $val) {
        /*******
         * `goods_id`,`now_month_num`,`prev_month_num`,`in_num`,
         *`out_num`,`find_num`,`system_lost_num`,`sell_num`,
         * `result_num`,`result_money`,`lost_num`,`lost_rand`,
         * `price`,`inprice`,`year`,`month`,
         * `store_id`,`create_time`,`status`
         ********/
        $goods_id = $val["goods_id"];
        $now_month_num = 0;//本月快照库存
        $prev_month_num = 0;//上月快照库存
        $in_num = 0;//入库数量【不计算盘盈数量】
        $out_num = 0;//出库数量【不计算盘亏数量】
        $find_num = 0;//找回数量【盘盈入库】
        $system_lost_num = 0;//丢失数量【应结数量-销售数量】
        $sell_num = 0;//销售数量
        $result_num = 0;//应结数量
        $result_money = 0;//应结款
        $lost_num = 0;//【暂时无效】
        $lost_rand = 0;//丢失率【丢失数量/应结数量】
        $price = 0;//销售价【本月快照金额】
        $inprice = 0;//单件商品成本价【总成本金额/销售数量】
        $inprice_money = 0;//总成本金额
        $inout_num = 0;
        $year = $prev_year;
        $month = $prev_month;
        $create_time = time();
        $status = $val["status"];

        //echo $goods_id;
        $goods_store_new_swift_item = array();
        //上月快照库存
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
        $res = deep_in_array($goods_id, "goods_id", $goods_store_other_out_num);
        if ($res["status"] == true) {
            $in_num += $res["result"]["total_num"];
        }
        //获取出库数量【正常出库+返仓】
        $res = deep_in_array($goods_id, "goods_id", $goods_out_stock_datas);
        if ($res["status"] == true) {
            print_r($res["result"]);
            $out_num = $res["result"]["total_num"];
        }
        $res = deep_in_array($goods_id, "goods_id", $goods_back_to_warehouse_datas);
        if ($res["status"] == true) {
            $out_num += $res["result"]["total_num"];
        }
        //获取找回数量【盘盈入库部分】
        $res = deep_in_array($goods_id, "goods_id", $goods_found_in_stock_datas);
        if ($res["status"] == true) {
            $find_num = $res["result"]["total_num"];
        }
        //获取销售数量
        $res = deep_in_array($goods_id, "goods_id", $goods_sold_datas);
        print_r($res);
        if ($res["status"] == true) {
            echo " has sell_num";
            $sell_num = $res["result"]["total_num"];
            $inprice_money = $res["result"]["total_inout_price_all"];
            $inprice = $res["result"]["total_num"] > 0 ? (round($res["result"]["total_inout_price_all"] / $res["result"]["total_num"], 2)) : 0;
        }
        //获取盘点盈亏数量
        $res = deep_in_array($goods_id, "goods_id", $store_inventory_inout_num);
        if ($res["status"] == true) {
            $inout_num = $res["result"]["inout_num"];
        }

        //获取应结数量【result_num】,公式：(prev_month_num-now_month_num)+(in_num-out_num)
        $result_num = ($prev_month_num - $now_month_num) + ($in_num - $out_num);
        //获取应结款【result_money】,公式：result_num*price
        $result_money = $result_num * $price;
        //获取丢失数量【应结数量-销售数量】
        $system_lost_num = $result_num - $sell_num;
        //获取丢失率【lost_rand】,公式：round(system_lost_num/result_num,4)*100
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
            $month, $store_id, $create_time, $status, $inout_num
        );

        $goods_store_new_swift_array[] = "(" . implode(",", $goods_store_new_swift_item) . ")";
        echo "<br/>";
    }
    if (!empty($goods_store_new_swift_array) && count($goods_store_new_swift_array) > 0) {
        $val_str = implode(',', $goods_store_new_swift_array);
        $fields = "`goods_id`,`now_month_num`,`prev_month_num`,`in_num`,";
        $fields .= "`out_num`,`find_num`,`system_lost_num`,`sell_num`,";
        $fields .= "`result_num`,`result_money`,`lost_num`,`lost_rand`,";
        $fields .= "`price`,`inprice`,`inprice_money`,`year`,";
        $fields .= "`month`,`store_id`,`create_time`,`status`,`inout_num`";
        $insert_into_sql = "insert into `hii_goods_store_new_swift_{$prev_year}`({$fields}) value " . $val_str;
        $data = $db->query($insert_into_sql);
        if (!$data) {
            echo 'snapshot do error!';
            print_r($db->errorInfo());
            error_snapshot($db, $store_id, $prev_year, $prev_month);
            exit;
        }
    }
    $end_data = array(
        'num' => $total_num,
        'money' => $total_money,
        'lost_num' => $total_lost_num,
        'lost_rand' => $total_num > 0 ? round($total_lost_num / $total_num, 4) * 100 : 0,
        'last_snapshot_id' => $last_snapshot_id,
        'prev_snapshot_id' => $prev_snapshot_id,
    );
    end_snapshot($db, $store_id, $prev_year, $prev_month, $end_data);
    $endtime = time();
    set_log('----结束对门店[' . $store_id . '],' . $prev_month . '月份的月结单 【结束时间：' . $endtime . '，耗时：' . ($endtime - $time) . '毫秒】 ----');
    echo " store_id[{$store_id}] success done!";
    exit;
} else {
    //获取本月库存快照失败
    echo "snapshot get fail";
}

exit;

function end_snapshot($db, $store_id, $year, $month, $data = array())
{
    $string = array('status = 2');
    foreach ($data as $k => $v) {
        $string[] = $k . '="' . $v . '"';
    }
    $string = implode(' , ', $string);
    $data = $db->query("update " . DB_PRE . "goods_store_new_swift_index set " . $string . " where store_id = " . $store_id . " and year = " . $year . " and month = " . $month);
    set_log('更新状态为已结款');
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