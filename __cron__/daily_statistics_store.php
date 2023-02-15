<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'daily_statistics');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
//初始化部分参数
$current_date = time();
//$current_year = date('Y', $current_date);//当前年份
//$current_month = date('m', $current_date);//当前月份
$yesterday_time = strtotime(date("Y-m-d" , strtotime("-1 day")));
$today_time = strtotime(date("Y-m-d" , time()));
//查找库存快照，用于获取用于统计的时间段
$start_statistics_time = 0;//开始统计的时间戳
$end_statistics_time = 0;//结束统计的时间戳
//统计门店信息
//门店库存查询
$stockSql = "select GS.store_id,GC.id as cate_id,SUM(GS.num) as stock_num, ";
$stockSql .= "SUM(GS.num*(CASE WHEN GS.price is not null THEN GS.price WHEN GS.shequ_price is not null THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
$stockSql .= "from hii_goods_store GS ";
$stockSql .= "INNER JOIN hii_goods G on G.id=GS.goods_id ";
$stockSql .= "INNER JOIN hii_goods_cate GC on GC.id = G.cate_id ";
$stockSql .= "WHERE GS.num>0 GROUP BY GS.store_id , GC.id";
$stockResult = $db->query($stockSql)->fetchAll(PDO::FETCH_ASSOC);
if($stockResult){
    //根据指定key汇总数量
    $storeStockTotal = array();
    foreach ($stockResult as $key => $value){
        $storeStockTotal[$value['store_id']]['stock_num'] = $value['stock_num'];//门店总库存数量
        $storeStockTotal[$value['store_id']]['g_amounts'] = $value['g_amounts'];//门店总库存价值
    }
}else{
    //执行失败
    $end_statistics_time = $start_statistics_time = date('Y-m-d H:i:s' ,time());
}
//门店销售查询
/*$orderSql = "SELECT count(orders.id) as total,orders.store_id as store_id, SUM(orders.money) as money , SUM(order_detail.num) as num ";
$orderSql .= "FROM hii_order_{$current_year} as orders INNER JOIN hii_order_detail_{$current_year} as order_detail on orders.order_sn = order_detail.order_sn ";
$orderSql .= "WHERE orders.create_time > {$yesterday_time} AND orders.create_time < {$today_time} GROUP BY orders.store_id";
$orderResult = $db->query($orderSql)->fetchAll(PDO::FETCH_ASSOC);
$orderResult = valueToKey($orderResult,'store_id' ,true);*/
$orderSql1 = "SELECT count(orders.id) as total , orders.store_id as store_id, SUM(orders.money) as money , SUM(order_detail.num) as num FROM hii_order as orders INNER JOIN hii_order_detail as order_detail on orders.order_sn = order_detail.order_sn WHERE orders.create_time >= {$yesterday_time} AND orders.create_time <= {$today_time} GROUP BY orders.store_id";
$orderResult1 = $db->query($orderSql1)->fetchAll(PDO::FETCH_ASSOC);
if($orderResult1){
    $orderResultArr = array();
    foreach ($orderResult1 as $key => $value){
        $orderResultArr[$value['store_id']]['total'] = $value['total'];//订单数量
        $orderResultArr[$value['store_id']]['num'] = $value['num'];//订单商品总数
        $orderResultArr[$value['store_id']]['money'] = $value['money'];//订单总额
    }
}
//门店出入库查询
//说明：0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
$inSource = array(
    '0' => '仓库出库',
    '1' => '门店调拨',
    '2' => '盘盈入库',
    '3' => '其它',
    '4' => '采购',
    '5' => '寄售'
);
$inStockSql = "SELECT SIS.s_in_s_type , SIS.store_id2 , SISD.g_num , SISD.g_price FROM hii_store_in_stock AS SIS ";
$inStockSql .= "LEFT JOIN hii_store_in_stock_detail AS SISD ON SIS.s_in_s_id = SISD.s_in_s_id ";
$inStockSql .= "WHERE SIS.ctime >= {$yesterday_time} AND SIS.ctime <= {$today_time}";
$inStockResult1 = $db->query($inStockSql)->fetchAll(PDO::FETCH_ASSOC);
if($inStockResult1){
    $inStockResultArr = array();
    foreach ($inStockResult1 as $key => $value){
        $inStockResultArr[$value['store_id2']]['total_num'] += $value['g_num'];//入库商品总数
        $inStockResultArr[$value['store_id2']]['total_price'] += $value['g_num'] * $value['g_price'];//入库总额
        $inStockResultArr[$value['store_id2']]['type'][$value['s_in_s_type']]['total_num'] += $value['g_num'];//入库商品总数
        $inStockResultArr[$value['store_id2']]['type'][$value['s_in_s_type']]['total_price'] += $value['g_num'] * $value['g_price'];//入库总额
    }
}
//门店出库查询
//来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它,5.寄售出库
$outSource = array(
    '0' => '仓库调拨',
    '1' => '门店申请',
    '3' => '盘亏出库',
    '4' => '其它',
    '5' => '寄售出库'
);
$outStockSql = "SELECT SOS.s_out_s_type , SOS.store_id2 , SSD.g_num , SSD.g_price FROM hii_store_out_stock AS SOS ";
$outStockSql .= "LEFT JOIN hii_store_stock_detail AS SSD ON SOS.s_out_s_id = SSD.s_out_s_id ";
$outStockSql .= "WHERE SOS.ctime >= {$yesterday_time} AND SOS.ctime <= {$today_time}";
$outStockResult1 = $db->query($outStockSql)->fetchAll(PDO::FETCH_ASSOC);
if($outStockResult1){
    $outStockResultArr = array();
    foreach ($outStockResult1 as $key => $value){
        $outStockResultArr[$value['store_id2']]['total_num'] += $value['g_num'];//入库商品总数
        $outStockResultArr[$value['store_id2']]['total_price'] += $value['g_num'] * $value['g_price'];//入库总额
        $outStockResultArr[$value['store_id2']]['type'][$value['s_out_s_type']]['total_num'] += $value['g_num'];//入库商品总数
        $outStockResultArr[$value['store_id2']]['type'][$value['s_out_s_type']]['total_price'] += $value['g_num'] * $value['g_price'];//入库总额
    }
}
//循环门店管理员，一一对应 入表（消息表）
$storeAdminSql = "SELECT MS.store_id , MS.uid , S.title FROM hii_member_store AS MS LEFT JOIN hii_store AS S on MS.store_id = S.id WHERE MS.type = 1  AND S.id IS NOT NULL";
$storeResult = $db->query($storeAdminSql)->fetchAll(PDO::FETCH_ASSOC);
$storeMessageValue = "";
if($storeResult){
    foreach ($storeResult as $key => $value){
        $message = array();
        //门店库存
        $message['stock_total'] = !empty($storeStockTotal[$value['store_id']]['stock_num']) ? $storeStockTotal[$value['store_id']]['stock_num'] : 0;
        $message['g_amounts'] = !empty($storeStockTotal[$value['store_id']]['g_amounts']) ? $storeStockTotal[$value['store_id']]['g_amounts'] : 0;
        //门店每日销售
        $message['order_total'] = !empty($orderResultArr[$value['store_id']]['total']) ? $orderResultArr[$value['store_id']]['total'] : 0;
        $message['order_goods_total'] = !empty($orderResultArr[$value['store_id']]['num']) ? $orderResultArr[$value['store_id']]['num'] : 0;
        //门店每日入库
        $message['stock_in'] = !empty($inStockResultArr[$value['store_id']]['total_num']) ? $inStockResultArr[$value['store_id']]['total_num'] : 0;
        $message['stock_in_price'] = !empty($inStockResultArr[$value['store_id']]['total_price']) ? $inStockResultArr[$value['store_id']]['total_price'] : 0;
        //入库类型细分
        if(!empty($inStockResultArr[$value['store_id']]['type'])){
            foreach ($inStockResultArr[$value['store_id']]['type'] as $k => $v){
                $message['stock_in_details'][$k]['total_num'] = $v['total_num'];
                $message['stock_in_details'][$k]['total_price'] = $v['total_price'];
            }
        }else{
            $message['stock_in_details'] = array();
        }
        //门店每日出库
        $message['stock_out'] = !empty($outStockResultArr[$value['store_id']]['total_num']) ? $outStockResultArr[$value['store_id']]['total_num'] : 0;
        $message['stock_out_price'] = !empty($outStockResultArr[$value['store_id']]['total_price']) ? $outStockResultArr[$value['store_id']]['total_price'] : 0;
        //入库类型细分
        if(!empty($outStockResultArr[$value['store_id']]['type'])){
            foreach ($outStockResultArr[$value['store_id']]['type'] as $k => $v){
                $message['stock_out_details'][$k]['total_num'] = $v['total_num'];
                $message['stock_out_details'][$k]['total_price'] = $v['total_price'];
            }
        }else{
            $message['stock_out_details'] = array();
        }
        $title = $value['title']."每日汇总";
        //组合数据
        $message = json_encode($message);
        $storeMessageValue .= "(".implode(',' , array(3 , 1 , time() , 1 , $value['uid'] , $value['store_id'] , "'$title'" , "'$message'"))."),";
    }
    $storeMessageValue = substr($storeMessageValue,0,strlen($storeMessageValue)-1);
    $messageInsterSql = "INSERT INTO hii_message_warn (m_type , m_other_type , ctime , from_admin_id , to_admin_id , to_store_id , message_title , message_content) VALUES {$storeMessageValue}";
    $storeData = $db->query($messageInsterSql);
    if (!$storeData) {
        echo 'add store message error';
        set_log('插入门店日报信息失败');
        exit;
    }else{
        echo 'add store message success';
        set_log('插入门店日报信息成功！');
        exit;
    }
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