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
$yesterday_time = strtotime(date("Y-m-d" , strtotime("-1 day"))); ;
$today_time = strtotime(date("Y-m-d" , time()));
//查找库存快照，用于获取用于统计的时间段
$start_statistics_time = 0;//开始统计的时间戳
$end_statistics_time = 0;//结束统计的时间戳
//统计仓库信息

//仓库入库，出库记录
//测试内容
$warehouseOutStockSql1 = "SELECT WOS.warehouse_id2 ,WOSD.goods_id , WOSD.g_num ,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as price ";
$warehouseOutStockSql1 .= "FROM hii_warehouse_out_stock WOS ";
$warehouseOutStockSql1 .= "LEFT JOIN hii_warehouse W on W.w_id=WOS.warehouse_id2 ";
$warehouseOutStockSql1 .= "LEFT JOIN hii_warehouse_out_stock_detail WOSD on WOS.w_out_s_id = WOSD.w_out_s_id ";
$warehouseOutStockSql1 .= "LEFT JOIN hii_shequ_price SP on W.shequ_id=SP.shequ_id AND WOSD.goods_id = SP.goods_id ";
$warehouseOutStockSql1 .= "LEFT JOIN hii_goods G on G.id = WOSD.goods_id ";
$warehouseOutStockSql1 .= "WHERE WOS.w_out_s_status =1 AND WOS.ctime >= {$yesterday_time} AND WOS.ctime <= {$today_time} ";
$wOutStockResult1 = $db->query($warehouseOutStockSql1)->fetchAll(PDO::FETCH_ASSOC);
if($wOutStockResult1) {
    $warehouseOutStockResult1 = array();
    foreach ($wOutStockResult1 as $key => $value) {
        $warehouseOutStockResult1[$value['warehouse_id2']]['num'] += $value['g_num'];
        $warehouseOutStockResult1[$value['warehouse_id2']]['price'] += $value['g_num'] * $value['price'];
    }
}

$warehouseStockInSql1 = "SELECT WIS.warehouse_id ,WISD.goods_id , WISD.g_num ,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as price ";
$warehouseStockInSql1 .= "FROM hii_warehouse_in_stock WIS ";
$warehouseStockInSql1 .= "LEFT JOIN hii_warehouse W on W.w_id=WIS.warehouse_id ";
$warehouseStockInSql1 .= "LEFT JOIN hii_warehouse_in_stock_detail WISD on WIS.w_in_s_id = WISD.w_in_s_id ";
$warehouseStockInSql1 .= "LEFT JOIN hii_shequ_price SP on W.shequ_id=SP.shequ_id AND WISD.goods_id = SP.goods_id ";
$warehouseStockInSql1 .= "LEFT JOIN hii_goods G on G.id = WISD.goods_id ";
$warehouseStockInSql1 .= "WHERE WIS.w_in_s_status =1 AND WIS.ctime >= {$yesterday_time} AND WIS.ctime <= {$today_time} ";
$wStockInResult1 = $db->query($warehouseStockInSql1)->fetchAll(PDO::FETCH_ASSOC);
if($wStockInResult1){
    $warehouseStockInResult1 = array();
    foreach ($wStockInResult1 as $key => $value){
        $warehouseStockInResult1[$value['warehouse_id']]['num'] += $value['g_num'];
        $warehouseStockInResult1[$value['warehouse_id']]['price'] += $value['g_num'] * $value['price'];
    }
}

//仓库库存数以及库存商品金额
$warehouseStockSql = "SELECT WS.w_id as w_id , WS.goods_id as goods_id , WS.num as num, (case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as price ";
$warehouseStockSql .= "FROM hii_warehouse_stock WS ";
$warehouseStockSql .= "LEFT JOIN hii_warehouse W on WS.w_id=W.w_id ";
$warehouseStockSql .="LEFT JOIN hii_shequ_price SP on W.shequ_id=SP.shequ_id and WS.goods_id=SP.goods_id ";
$warehouseStockSql .="LEFT JOIN hii_goods G on G.id = WS.goods_id ";
$wStockResult = $db->query($warehouseStockSql)->fetchAll(PDO::FETCH_ASSOC);
if($wStockResult){
    $warehouseStockResult = array();
    foreach ($wStockResult as $key => $value){
        $warehouseStockResult[$value['w_id']]['num'] += $value['num'];
        $warehouseStockResult[$value['w_id']]['price'] += $value['num'] * $value['price'];
    }
}

//获取仓库管理员 进行循环
$warehouseMemberSql = "SELECT WM.uid , WM.warehouse_id , W.w_name FROM hii_member_warehouse AS WM LEFT JOIN hii_warehouse AS W ON WM.warehouse_id = W.w_id";
$warehouseMemberResult = $db->query($warehouseMemberSql)->fetchAll(PDO::FETCH_ASSOC);
if($warehouseMemberResult){
    foreach ($warehouseMemberResult as $key => $value){
        $message = array();
        $i = 0;
        //收货
        $message['stock_in']['num'] = !empty($warehouseStockInResult1[$value['warehouse_id']]['num']) ? $warehouseStockInResult1[$value['warehouse_id']]['num'] : 0;
        $message['stock_in']['price'] = !empty($warehouseStockInResult1[$value['warehouse_id']]['price']) ? $warehouseStockInResult1[$value['warehouse_id']]['price'] : 0;
        //出货
        $message['stock_out']['num'] = !empty($warehouseOutStockResult1[$value['warehouse_id']]['num']) ? $warehouseOutStockResult1[$value['warehouse_id']]['num'] : 0;
        $message['stock_out']['price'] = !empty($warehouseOutStockResult1[$value['warehouse_id']]['price']) ? $warehouseOutStockResult1[$value['warehouse_id']]['price'] : 0;
        //库存
        $message['stock']['num'] = !empty($warehouseStockResult[$value['warehouse_id']]['num']) ? $warehouseStockResult[$value['warehouse_id']]['num'] : 0;
        $message['stock']['price'] = !empty($warehouseStockResult[$value['warehouse_id']]['price']) ? $warehouseStockResult[$value['warehouse_id']]['price'] : 0;

        $title = $value['w_name']."每日汇总";
        //组合数据
        $message = json_encode($message);
        $warehouseMessageValue .= "(".implode(',' , array(3 , 2 , time() , 1 , $value['uid'] , $value['warehouse_id'] , "'$title'" , "'$message'"))."),";
        $i++;
    }
    $warehouseMessageValue = substr($warehouseMessageValue,0,strlen($warehouseMessageValue)-1);
    $messageInsterSql = "INSERT INTO hii_message_warn (m_type , m_other_type , ctime , from_admin_id , to_admin_id , to_warehouse_id , message_title , message_content) VALUES {$warehouseMessageValue}";

    $warehouseData = $db->query($messageInsterSql);
    if (!$warehouseData) {
        echo 'add stock message error';
        set_log('插入仓库日报信息失败！\t');
        exit;
    }else{
        echo 'add stock message success';
        set_log('插入仓库日报信息成功！\t');
        exit;
    }
}else{
    echo 'warehouse_member is null';
    set_log('获取仓库管理员列表失败\t');
    exit;
}



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
