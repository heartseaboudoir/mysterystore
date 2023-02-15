<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'daily_statistics');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
//初始化部分参数
$current_date = time();
$current_year = date('Y', $current_date);//当前年份
$current_month = date('m', $current_date);//当前月份
$yesterday_time = strtotime(date("Y-m-d" , strtotime("-1 day")));
$seven_days_later_time = strtotime(date("Y-m-d" , strtotime("+7 day")));
$today_time = strtotime(date("Y-m-d") , time());
//查找库存快照，用于获取用于统计的时间段
$start_statistics_time = 0;//开始统计的时间戳
$end_statistics_time = 0;//结束统计的时间戳
//库存批次信息 处理门店
$storeWarehouseInOutsql = "SELECT WI.goods_id ,SUM(WI.num) as total_num, WI.store_id , WI.ctype , G.title , G.unit FROM hii_warehouse_inout AS WI ";
$storeWarehouseInOutsql .= "LEFT JOIN hii_goods AS G ON WI.goods_id = G.id ";
$storeWarehouseInOutsql .= "WHERE WI.endtime >= {$today_time} and WI.endtime <= {$seven_days_later_time} and ctype != 1 and WI.store_id > 0 and num > 0 GROUP BY WI.goods_id , WI.store_id";
$storeWarehouseInOutResult = $db->query($storeWarehouseInOutsql)->fetchAll(PDO::FETCH_ASSOC);

if($storeWarehouseInOutResult){
    $i = 1;
    $storeExpiredArr = array();
    $storeArr = array();
    foreach ($storeWarehouseInOutResult as $key => $value){
        $storeArr[] = $value['store_id'];
        //store
        $storeExpiredArr[$value['store_id']]['num'] += 1;
        $storeExpiredArr[$value['store_id']]['goods'][$value['goods_id']]['title'] = $value['title'];
        $storeExpiredArr[$value['store_id']]['goods'][$value['goods_id']]['unit'] = $value['unit'];
        $storeExpiredArr[$value['store_id']]['goods'][$value['goods_id']]['total_num'] += $value['total_num'];
        $i++;
    }
}

//分隔语句
$getStoreIdSql = "SELECT WOS.store_id FROM hii_warehouse_inout as WI ";
$getStoreIdSql .= "LEFT JOIN hii_warehouse_out_stock_detail as WOSD ON WI.goods_id = WOSD.goods_id ";
$getStoreIdSql .= "LEFT JOIN hii_warehouse_out_stock as WOS ON WOS.w_out_s_id = WOSD.w_out_s_id ";
$getStoreIdSql .= "WHERE WI.endtime >= {$today_time} and WI.endtime <= {$seven_days_later_time} ";
$getStoreIdSql .= "and WI.num > 0 and WI.ctime < WOS.ctime and (WOS.w_out_s_type = 1 or WOS.w_out_s_type = 5) GROUP BY WOS.store_id";
$getStoreIdResult = $db->query($getStoreIdSql)->fetchAll(PDO::FETCH_ASSOC);
if($getStoreIdResult){
	$storeIdArr = array();
	foreach($getStoreIdResult as $key => $value){
		$storeIdArr[] = $value['store_id'];
	}
	$idLength = count($storeIdArr);
	$fi = 15;
	for($i=0 ; $i<=ceil($idLength/$fi) ; $i++){
		$store_ids = implode(',' , array_slice($storeIdArr,$fi*$i,$fi));
		$storeByWarehouseInoutSql = "SELECT GS.goods_id , GS.store_id , GS.num , G.title , G.unit FROM hii_goods_store as GS ";
		$storeByWarehouseInoutSql .= "LEFT JOIN hii_goods as G ON GS.goods_id = G.id ";
		$storeByWarehouseInoutSql .= "WHERE GS.store_id in ({$store_ids}) AND num > 0 ";
		$storeByWarehouseInoutResult = $db->query($storeByWarehouseInoutSql)->fetchAll(PDO::FETCH_ASSOC);
		if($storeByWarehouseInoutResult){
			foreach ($storeByWarehouseInoutResult as $key => $value){
				$storeArr[] = $value['store_id'];
				//store
				$storeExpiredArr[$value['store_id']]['num'] += 1;
				$storeExpiredArr[$value['store_id']]['goods'][$value['goods_id']]['title'] = trimall($value['title']);
				$storeExpiredArr[$value['store_id']]['goods'][$value['goods_id']]['unit'] = $value['unit'];
				$storeExpiredArr[$value['store_id']]['goods'][$value['goods_id']]['total_num'] += $value['num'];
				$i++;
			}
		}	
	}
	
}

if(!empty($storeArr)){
    $storeAdminStr = implode(',' , array_unique($storeArr));
    //循环门店管理员，一一对应 入表（消息表）
    $storeAdminSql = "SELECT MS.store_id , MS.uid , S.title FROM hii_member_store AS MS LEFT JOIN hii_store AS S on MS.store_id = S.id WHERE MS.type = 1 AND MS.store_id in ({$storeAdminStr})";
    $storeResult = $db->query($storeAdminSql)->fetchAll(PDO::FETCH_ASSOC);
    $storeMessageValue = "";
    if($storeResult){
        foreach ($storeResult as $key => $value){
            $message = array();
            $i = 1;
            if(!empty($storeExpiredArr[$value['store_id']])){
                $message['total_num']=$storeExpiredArr[$value['store_id']]['num'];
                foreach ($storeExpiredArr[$value['store_id']]['goods'] as $k => $v){
					$message['details'][$i]['goods_id']= $k;
                    $message['details'][$i]['title']=urlencode($v['title']);
                    $message['details'][$i]['total_num']=$v['total_num'];
                    $message['details'][$i]['unit']=urlencode($v['unit']);
                    $i++;
                }
            }
            if($message != ''){
                $title = $value['title']."商品过期提醒";
                //组合数据
                $message = urldecode(json_encode($message));
                $storeMessageValue .= "(".implode(',' , array(0 , 0 , time() , 1 , $value['uid'] , $value['store_id'] , "'$title'" , "'$message'"))."),";
            }
        }
        $storeMessageValue = substr($storeMessageValue,0,strlen($storeMessageValue)-1);
        $messageInsterSql = "INSERT INTO hii_message_warn (m_type , m_other_type , ctime , from_admin_id , to_admin_id , to_store_id , message_title , message_content) VALUES {$storeMessageValue}";
        $storeData = $db->query($messageInsterSql);
        if (!$storeData) {
            echo 'add store goods exprid message error';
            set_log('插入门店商品过期提醒信息失败！\t');
        }else{
            echo 'add store goods exprid message success';
            set_log('插入门店商品过期提醒信息成功！\t');
        }
    }
}

//仓库
$warehouseInOutSql = "SELECT WI.goods_id ,SUM(WI.num) as total_num , WI.warehouse_id , WI.ctype , G.title , G.unit FROM hii_warehouse_inout AS WI ";
$warehouseInOutSql .= "LEFT JOIN hii_goods AS G ON WI.goods_id = G.id ";
$warehouseInOutSql .= "WHERE WI.endtime >= {$today_time} and WI.endtime <= {$seven_days_later_time} and ctype != 1 and WI.warehouse_id > 0 and num > 0 GROUP BY WI.goods_id , WI.warehouse_id";
$warehouseInOutResult = $db->query($warehouseInOutSql)->fetchAll(PDO::FETCH_ASSOC);
if($warehouseInOutResult){
    $wi = 1;
    $warehouseArr = array();
    $warehouseExpiredArr = array();
    foreach ($warehouseInOutResult as $key => $value){
        $warehouseArr[] = $value['warehouse_id'];
        //store
        $warehouseExpiredArr[$value['warehouse_id']]['num'] += 1;
		$warehouseExpiredArr[$value['warehouse_id']]['goods'][$i]['goods_id'] = $value['goods_id'];
        $warehouseExpiredArr[$value['warehouse_id']]['goods'][$i]['title'] = trimall($value['title']);
        $warehouseExpiredArr[$value['warehouse_id']]['goods'][$i]['unit'] = trimall($value['unit']);
        $warehouseExpiredArr[$value['warehouse_id']]['goods'][$i]['total_num'] = $value['total_num'];
        $i++;
    }
}
if(!empty($warehouseArr)){
    $warehouseAdminStr = implode(',' , array_unique($warehouseArr));
    //获取仓库管理员 进行循环
    $warehouseMemberSql = "SELECT WM.uid , WM.warehouse_id , W.w_name FROM hii_member_warehouse AS WM LEFT JOIN hii_warehouse AS W ON WM.warehouse_id = W.w_id WHERE WM.warehouse_id in ({$warehouseAdminStr})";
    $warehouseMemberResult = $db->query($warehouseMemberSql)->fetchAll(PDO::FETCH_ASSOC);
    $warehouseMessageValue = "";
    if($warehouseMemberResult){
        foreach ($warehouseMemberResult as $key => $value){
            $message = array();
            $i = 1;
            if(!empty($warehouseExpiredArr[$value['warehouse_id']])){
                $message['total_num']=$warehouseExpiredArr[$value['warehouse_id']]['num'];
                foreach ($warehouseExpiredArr[$value['warehouse_id']]['goods'] as $k => $v){
					$message['details'][$i]['goods_id']=$v['goods_id'];
                    $message['details'][$i]['title']=urlencode($v['title']);
                    $message['details'][$i]['total_num']=$v['total_num'];
                    $message['details'][$i]['unit']=urlencode($v['unit']);
                    $i++;
                }
            }
            if($message != ''){
                $title = $value['w_name']."商品过期提醒";
                //组合数据
                $message = urldecode(json_encode($message));
                $warehouseMessageValue .= "(".implode(',' , array(0 , 0 , time() , 1 , $value['uid'] , $value['warehouse_id'] , "'$title'" , "'$message'"))."),";
            }
        }
        $warehouseMessageValue = substr($warehouseMessageValue,0,strlen($warehouseMessageValue)-1);
        $messageInsterSql = "INSERT INTO hii_message_warn (m_type , m_other_type , ctime , from_admin_id , to_admin_id , to_warehouse_id , message_title , message_content) VALUES {$warehouseMessageValue}";
        $storeData = $db->query($messageInsterSql);
        if (!$storeData) {
            echo 'add warehouse goods exprid message error';
            set_log('插入仓库商品过期提醒信息失败！\t');
        }else{
            echo 'add warehouse goods exprid message success';
            set_log('插入仓库商品过期提醒信息成功！\t');
        }
    }
}
if(empty($storeArr) && empty($warehouseArr)){
    echo 'nothing to do';
}
exit;


//日志
function set_log($data, $time = 1)
{
    $log_dir = ROOT_PATH . 'Runtime/' . CAIJI_NAME . '_logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_dir . date('Y-m') . '.txt', ($time ? "[" . date('Y-m-d H:i:s') . "] " : '') . $data . "\r\n", FILE_APPEND);
}

//valueToKey
function valueToKey($array , $key , $is_value = false){
    $newArray = array();
    if($is_value){
        foreach ($array as $k => $v){
            $newArray[$v[$key]] = $v;
        }
    }else{
        foreach ($array as $k => $v){
            $newArray[$v[$key]][] = $v;
        }
    }
    return $newArray;
}
//去除无用空格和换行
function trimall($str){
    $rs=array(" ","　","\t","\n","\r");
    return str_replace($rs, '', $str);
}
