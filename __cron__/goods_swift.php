<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'swift');

set_time_limit(600);

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);

set_log('----开始执行----');
// 获取当前年份、月份
$time = time();
$in_date = strtotime('-1 month');
$year = date('Y', $in_date);
$month = date('m', $in_date);

$do_date = $time;
$do_year = date('Y', $do_date);
$do_month = date('m', $do_date);

$prev_date = strtotime('-1 month');
$prev_year = date('Y', $prev_date);
$prev_month = date('m', $prev_date);

// 结款单年表名（按年份）
$db_name = DB_PRE.'goods_store_swift_'.$year;

// 生成结款单年表（没有则创建）
$create_db_sql = "CREATE TABLE `".$db_name."` (
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
  `year` mediumint(4) DEFAULT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `store_id` int(11) DEFAULT '0',
  `create_time` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_id` (`goods_id`,`year`,`month`,`store_id`),
  KEY `store_id` (`store_id`),
  KEY `year` (`year`,`month`,`store_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
$data = $db->query($create_db_sql);

// 获取最后一个操作的门店
$data = $db->query("select * from ".DB_PRE."goods_store_swift_index where year = ".$year." and month = ".$month." order by store_id desc limit 1");
$prev_store = 0;
if($data){
    foreach($data as $v){
        if($v['status'] == 1){
			if($v['create_time'] + 600 >= $time){
				// 如果5分钟内正在进行就不操作
				echo 'the prve is doing';
				set_log('门店' .$v['store_id']. '正在进行,退出操作');
				exit;
			}else{
				//超过600秒而设为结款失败
				echo 'the prve is out time';
				set_log('门店' .$v['store_id']. ' 已超过时限未成功结款，结款失败');
				error_snapshot($db, $v['store_id'], $year, $month);
				exit;
			}
        }
        $prev_store = $v['store_id'];
    }
}
// 获取要操作的门店
$data = $db->query("select * from ".DB_PRE."store where `status` = 1 and id > ".$prev_store." order by id asc limit 1");
if(!$data) {
    echo 'get store error!';
	set_log('获取门店失败');
    exit;
}
$store_id = 0;
foreach($data as $v){
	$store_id = $v['id'];
}
if(!$store_id) {
    echo 'no store to do!';
	set_log('没有需要操作的门店');
    exit;
}
set_log('开始对 门店['.$store_id.'] 进行结款');
// 获取上次结款单记录
$data = $db->query("select * from ".DB_PRE."goods_store_swift_index where store_id = ".$store_id." order by id desc limit 1");
$last_snapshot_id = 0;
$prev_snapshot_id = 0;
if($data){
	foreach($data as $v){
		$prev_snapshot_id = $v['last_snapshot_id'];
	}
}
// 插入结款单记录
$data = $db->query("insert into ".DB_PRE."goods_store_swift_index (year, month, store_id, create_time, status) value (".$year.", ".$month.", ".$store_id.",".time().", 1)");
if(!$data){
	echo 'add swift error';
	set_log('插入结款单记录失败');
	exit;
}
$first_time = 0;
$end_time = 0;
$in_snapshot = array();
$prev_snapshot = array();
if(!$prev_snapshot_id){
	// 本月及上月快照记录（获取本月出入库的时间区间，为本月结算时间与上月结算记录之前的时间）
	$index = $db->query("select * from ".DB_PRE."goods_store_snapshot_index where store_id = ".$store_id.' order by create_time desc limit 2');
	if(!$index){
		echo 'store snapshot do not do!';
		set_log('获取快照记录失败');
		error_snapshot($db, $store_id, $year, $month);
		exit();
	}
	foreach($index as $k => $v){
		if($k == 0){
			$first_time = $v['create_time'];
			$last_snapshot_id = $v['id'];
			$in_snapshot = $v;
		}elseif($k == 1){
			// 获取上月生成快照时的最后时间
			$end_time = $v['create_time'];
			$prev_snapshot_id = $v['id'];
			$prev_snapshot = $v;
		}
	}
}else{
	// 最后的库存快照记录（获取本月出入库的时间区间，为本月结算时间与上月结算记录之前的时间）
	$index = $db->query("select * from ".DB_PRE."goods_store_snapshot_index where store_id = ".$store_id.' order by create_time desc limit 2');
	if(!$index){
		echo 'store snapshot do not do!';
		set_log('获取快照记录失败');
		error_snapshot($db, $store_id, $year, $month);
		exit();
	}
	foreach($index as $k => $v){
		if($k == 0){
			$first_time = $v['create_time'];
			$last_snapshot_id = $v['id'];
			$in_snapshot = $v;
		}elseif($k == 1 && $v['id'] == $prev_snapshot_id){
			// 获取上月生成快照时的最后时间
			$end_time = $v['create_time'];
			$prev_snapshot = $v;
		}
	}
	// 未获取上月快照时间时，则再次通过上次快照记录ID查询
	if($end_time == 0){
		$index = $db->query("select * from ".DB_PRE."goods_store_snapshot_index where store_id = ".$store_id.' and id = '.$prev_snapshot_id);
		if(!$index){
			echo 'store snapshot do not do!';
			set_log('获取快照记录失败');
			error_snapshot($db, $store_id, $year, $month);
			exit();
		}
		foreach($index as $k => $v){
			$end_time = $v['create_time'];
		}
	}
}
if($first_time == 0){
	echo 'do not find new snapshot';
	set_log('找不到最新的库存快照记录');
	error_snapshot($db, $store_id, $year, $month);
	exit;
}
// 库存快照表名（按年份）
$shot_db_name = DB_PRE.'goods_store_snapshot_'.$in_snapshot['year'];
// 本月库存快照
$now_data = $db->query("select * from ".$shot_db_name." where month = ".$in_snapshot['month']." and store_id = ".$store_id);

if($now_data){
    $_now_data = array();
    foreach($now_data as $v){
        $_now_data[$v['goods_id']] = $v;
    }
    $now_data = $_now_data;
}else{
    $now_data = array();
}
if($prev_snapshot){
	// 库存快照表名（按年份）
	$shot_db_name = DB_PRE.'goods_store_snapshot_'.$prev_snapshot['year'];
	// 上月库存快照
	$prev_data = $db->query("select * from ".$shot_db_name." where month = ".$prev_snapshot['month']." and store_id = ".$store_id);
}else{
	$prev_data = array();
}
if($prev_data){
    $_prev_data = array();
    foreach($prev_data as $v){
        $_prev_data[$v['goods_id']] = $v;
    }
    $prev_data = $_prev_data;
}else{
    $prev_data = array();
}
!$end_time && $end_time = $in_date;

$log_data = $db->query("select * from ".DB_PRE."goods_store_log where create_time <= ".$first_time." and create_time >= ".$end_time." and store_id = ".$store_id. ' and type in(1,2,3,4)');
$in_log = $out_log = $find_log = $lost_log = array();
foreach($log_data as $v){
    if($v['type'] == 1){
        $in_log[] = $v;
    }elseif($v['type'] == 2){
        $out_log[] = $v;
    }elseif($v['type'] == 3){
		$find_log[] = $v;
	}elseif($v['type'] == 4){
		$lost_log[] = $v;
	}
}
if($in_log){
    foreach($in_log as $v){
		!isset($in_data[$v['goods_id']]) && $in_data[$v['goods_id']] = 0;
		$in_data[$v['goods_id']]+= $v['num'];
    }
}

if($out_log){
    foreach($out_log as $v){
		!isset($out_data[$v['goods_id']]) && $out_data[$v['goods_id']] = 0;
		$out_data[$v['goods_id']]+= $v['num'];
    }
}

if($find_log){
    foreach($find_log as $v){
		!isset($find_data[$v['goods_id']]) && $find_data[$v['goods_id']] = 0;
		$find_data[$v['goods_id']]+= $v['num'];
    }
}
if($lost_log){
    foreach($lost_log as $v){
		!isset($lost_data[$v['goods_id']]) && $lost_data[$v['goods_id']] = 0;
		$lost_data[$v['goods_id']]+= $v['num'];
    }
}


// 获取门店当前商品信息
$data = $db->query("select * from ".DB_PRE."goods_store where `store_id` = ".$store_id);
if(!$data) {
    echo 'no goods_data to do!';
    end_snapshot($db, $store_id, $year, $month);
    exit;
}
// 获取系统商品数据
$gdata = $db->query("select id,sell_price,status from ".DB_PRE."goods");
foreach($gdata as $v){
	$_gdata[$v['id']] = $v;
}
if(!$data) {
    end_snapshot($db, $store_id, $year, $month);
    echo ' store no goods to do!';
    exit;
}

// 获取门店上次库存快照至本次库存快照时间内的销量（本次库存快照当天生成的时间不计，因快照时间将会是在0点时生成）
$sgdata = $db->query("select goods_id,num from ".DB_PRE.'goods_sell_log'.$store_id.' where date >= "'.date('Y-m-d', $end_time).'" and  date < "'.date('Y-m-d', $first_time).'"');
if($sgdata){
    foreach($sgdata as $v){
		!isset($_sgdata[$v['goods_id']]) && $_sgdata[$v['goods_id']] = 0;
		$_sgdata[$v['goods_id']] += $v['num'];
    }
}
// 多余的销量（开始计算的那天0点到开始的时间点） 如果上次库存快照是在0分生成的，则无需操作
if($end_time > strtotime(date('Y-m-d',$end_time))){
	// 查询要去除的订单
	$del_order_data = $db->query('select order_sn from '.DB_PRE.'order where store_id = '.$store_id.' and pay_status = 2 and pay_time >= '.strtotime(date('Y-m-d',$end_time)).' and pay_time < '.$end_time);
	$del_order_sn = array();
	if($del_order_data){
		foreach($del_order_data as $v){
			$del_order_sn[] = $v['order_sn'];
		}
	}

	// 根据订单拿商品数量
	if($del_order_sn){
		$del_num_data = $db->query('select num,d_id from '.DB_PRE.'order_detail where order_sn in ('.implode(',', $del_order_sn).')');
		if($del_num_data){
			foreach($del_num_data as $v){
				!isset($del_num[$v['d_id']]) && $del_num[$v['d_id']] = 0;
				$del_num[$v['d_id']] += $v['num'];
			}
		}
	}
}
// 获取结款数组
$val_arr = array();
$total_num = 0;
$total_money = 0;
$total_lost_num = 0;
foreach($data as $v){
	if(!isset($_gdata[$v['goods_id']])) continue;
		// 本月库存
        $now_month_num = empty($now_data[$v['goods_id']]['num']) ? 0 : intval($now_data[$v['goods_id']]['num']);
		// 上月库存
        $prev_month_num = empty($prev_data[$v['goods_id']]['num']) ? 0 : intval($prev_data[$v['goods_id']]['num']);
		// 本次入库数量
        $in_num = empty($in_data[$v['goods_id']]) ? 0 : intval($in_data[$v['goods_id']]);
		// 本次出库数量
        $out_num = empty($out_data[$v['goods_id']]) ? 0 : intval($out_data[$v['goods_id']]);
		// 本次找回数量
        $find_num = empty($find_data[$v['goods_id']]) ? 0 : intval($find_data[$v['goods_id']]);
		// 本次丢失数量
        $system_lost_num = empty($lost_data[$v['goods_id']]) ? 0 : intval($lost_data[$v['goods_id']]);
		// 多余的销量（开始计算的那天0点到开始的时间点）
		$must_del_num = isset($del_num[$v['goods_id']]) ? $del_num[$v['goods_id']] : 0;
		// 本月销量
        $month_sell_num = empty($_sgdata[$v['goods_id']]) ? 0 : intval($_sgdata[$v['goods_id']]);
		// 实际销量 = 本月销量-多余销量
        $sell_num = $month_sell_num - $must_del_num;
		// 销售价
        $price = isset($now_data[$v['goods_id']]['price']) ? $now_data[$v['goods_id']]['price'] : 0;
		// 应结数量
        //$result_num = ($prev_month_num-($now_month_num-$find_num))+($in_num-$out_num);
        $result_num = ($prev_month_num-$now_month_num)+($in_num-$out_num);
		// 应结金额
        $result_money = $result_num*$price;
		// 丢失数量
        $lost_num = $result_num-$sell_num;
        $lost_rand = $result_num > 0 ? round($lost_num/$result_num, 4)*100 : 0;
        $total_money += $result_money;
        $total_num += $result_num;
        $total_lost_num += $lost_num;
	$_val = array(
            $v['goods_id'],
            $now_month_num,
            $prev_month_num,
            $in_num,
            $out_num,
            $find_num,
            $system_lost_num,
            $sell_num,
            $result_num,
            $result_money,
            $lost_num,
            $lost_rand,
            $price,
            $year,
            $month,
            $v['store_id'],
            $time,
			$_gdata[$v['goods_id']]['status']
	);
	$val_arr[] = '("'.implode('","', $_val).'")';
}
if(!$val_arr){
    echo 'no goods_data to do!';
    end_snapshot($db, $store_id, $year, $month);
    exit;
}
// 组合sql并添加快照
$val_str = implode(',', $val_arr);
$insert_sql = 'insert into '.$db_name.' (goods_id, now_month_num, prev_month_num, in_num,out_num,find_num,system_lost_num,sell_num,result_num, result_money,lost_num,lost_rand,price, year, month, store_id, create_time, status) value '.$val_str;

$data = $db->query($insert_sql);
if(!$data) {
    echo 'snapshot do error!';print_r($db->errorInfo()); 
	error_snapshot($db, $store_id, $year, $month);
    exit;
}
$end_data = array(
    'num' => $total_num,
    'money' => $total_money,
    'lost_num' => $total_lost_num,
    'lost_rand' => $total_num > 0 ? round($total_lost_num/$total_num, 4)*100 : 0,
    'last_snapshot_id' => $last_snapshot_id,
    'prev_snapshot_id' => $prev_snapshot_id,
);
end_snapshot($db, $store_id, $year, $month, $end_data);
echo 'success';
set_log('----执行完成----');
exit;

function end_snapshot($db, $store_id, $year, $month, $data = array()){
        $string = array('status = 2');
        foreach($data as $k => $v){
            $string[] = $k .'="'.$v.'"';
        }
        $string = implode(' , ', $string);
	$data = $db->query("update ".DB_PRE."goods_store_swift_index set ".$string." where store_id = ".$store_id." and year = ".$year." and month = ".$month);
	set_log('更新状态为已结款');
}

function error_snapshot($db, $store_id, $year, $month){
	$data = $db->query("update ".DB_PRE."goods_store_swift_index set status = -1 where store_id = ".$store_id." and year = ".$year." and month = ".$month);
	set_log('更新状态为结款失败');
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