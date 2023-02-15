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
//统计采购信息
//发往仓库
$purchaseByWsSql = "SELECT P.p_id , P.warehouse_id , P.p_status , PD.g_num , PD.g_price , W.shequ_id , WID.in_num,WID.out_num  ";
$purchaseByWsSql .= "FROM hii_purchase AS P ";
$purchaseByWsSql .= "LEFT JOIN hii_purchase_detail AS PD on P.p_id = PD.p_id ";
$purchaseByWsSql .= "LEFT JOIN hii_warehouse AS W on W.w_id=P.warehouse_id ";
$purchaseByWsSql .= "LEFT JOIN hii_warehouse_in AS WI on WI.w_in_id = P.w_in_id ";
$purchaseByWsSql .= "LEFT JOIN hii_warehouse_in_detail AS WID on WI.w_in_id=WID.w_in_id and PD.p_d_id = WID.p_d_id ";
$purchaseByWsSql .= "WHERE (P.p_status = 1 or P.p_status = 0) AND P.ctime >= {$yesterday_time} AND P.ctime <= {$today_time} AND P.warehouse_id > 0";
$purchaseByWsResult = $db->query($purchaseByWsSql)->fetchAll(PDO::FETCH_ASSOC);
if($purchaseByWsResult){
    $purchaseByWsArr = array();
    foreach ($purchaseByWsResult as $key => $value){
        if($value['p_status'] == 1){
            $purchaseByWsArr[$value['shequ_id']]['is_pass'] = $purchaseByWsArr[$value['shequ_id']]['is_pass'] ? $purchaseByWsArr[$value['shequ_id']]['is_pass'] + 1:  1;
            $purchaseByWsArr[$value['shequ_id']]['g_num_total'] += $value['g_num'];//采购总数量
            $purchaseByWsArr[$value['shequ_id']]['g_price_total'] += $value['g_num'] * $value['g_price']; //采购总值
            $purchaseByWsArr[$value['shequ_id']]['in_num_total'] += $value['in_num'] ;//验收总数量
            $purchaseByWsArr[$value['shequ_id']]['in_price_total'] += $value['in_num'] * $value['g_price']; //验收总值
            $purchaseByWsArr[$value['shequ_id']]['out_num_total'] += $value['out_num'];//退货总数量
            $purchaseByWsArr[$value['shequ_id']]['out_price_total'] += $value['out_num'] * $value['g_price']; //退货总值
        }else{
            $purchaseByWsArr[$value['shequ_id']]['is_new'] = $purchaseByWsArr[$value['shequ_id']]['is_new'] ? $purchaseByWsArr[$value['shequ_id']]['is_new'] + 1:  1;
        }
    }
}
//发往门店
$purchaseBysSql = "SELECT P.p_id , P.store_id , P.p_status , PD.g_num , PD.g_price , S.shequ_id , SID.in_num,SID.out_num ";
$purchaseBysSql .= "FROM hii_purchase AS P ";
$purchaseBysSql .= "LEFT JOIN hii_purchase_detail AS PD on P.p_id = PD.p_id ";
$purchaseBysSql .= "LEFT JOIN hii_store AS S on S.id=P.store_id ";
$purchaseBysSql .= "LEFT JOIN hii_store_in AS SI on SI.s_in_id = P.s_in_id ";
$purchaseBysSql .= "LEFT JOIN hii_store_in_detail AS SID on SI.s_in_id=SID.s_in_id and PD.p_d_id = SID.p_d_id ";
$purchaseBysSql .= "WHERE (P.p_status = 1 or P.p_status = 0) AND P.ctime >= {$yesterday_time} AND P.ctime < {$today_time} AND P.store_id > 0";
$purchaseBysResult = $db->query($purchaseBysSql)->fetchAll(PDO::FETCH_ASSOC);
if($purchaseBysResult){
    $purchaseBysArr = array();
    foreach ($purchaseBysResult as $key => $value){
        if($value['p_status'] == 1){
            $purchaseBysArr[$value['shequ_id']]['is_pass'] = 1;
            $purchaseBysArr[$value['shequ_id']]['g_num_total'] += $value['g_num'];//采购总数量
            $purchaseBysArr[$value['shequ_id']]['g_price_total'] += $value['g_num'] * $value['g_price']; //采购总值
            $purchaseBysArr[$value['shequ_id']]['in_num_total'] += $value['in_num'] ;//验收总数量
            $purchaseBysArr[$value['shequ_id']]['in_price_total'] += $value['in_num'] * $value['g_price']; //验收总值
            $purchaseBysArr[$value['shequ_id']]['out_num_total'] += $value['out_num'];//退货总数量
            $purchaseBysArr[$value['shequ_id']]['out_price_total'] += $value['out_num'] * $value['g_price']; //退货总值
        }else{
            $purchaseBysArr[$value['shequ_id']]['is_new'] = 1;
            $purchaseBysArr[$value['shequ_id']]['g_num_total'] += 0;//采购总数量
            $purchaseBysArr[$value['shequ_id']]['g_price_total'] += 0; //采购总值
            $purchaseBysArr[$value['shequ_id']]['in_num_total'] += 0;//验收总数量
            $purchaseBysArr[$value['shequ_id']]['in_price_total'] += 0; //验收总值
            $purchaseBysArr[$value['shequ_id']]['out_num_total'] += 0;//退货总数量
            $purchaseBysArr[$value['shequ_id']]['out_price_total'] += 0; //退货总值
        }
    }
}

//获取采购管理员 进行循环
$purchaseMemberSql = "SELECT MS.* , S.shequ_id  , SQ.title FROM hii_member_store AS MS LEFT JOIN hii_store AS S on MS.store_id = S.id LEFT JOIN hii_shequ AS SQ on S.shequ_id = SQ.id WHERE MS.group_id = 15 AND S.shequ_id > 0";
$purchaseMemberResult = $db->query($purchaseMemberSql)->fetchAll(PDO::FETCH_ASSOC);
if($purchaseMemberResult){
    foreach ($purchaseMemberResult as $key => $value){
        $message = '';
        $is_pass = 0;
        $is_new = 0;
        $g_num_total = 0;
        $g_price_total = 0;
        $in_num_total = 0;
        $in_price_total = 0;
        $out_num_total = 0;
        $out_price_total = 0;
        if(!empty($purchaseByWsArr[$value['shequ_id']])){
            $is_pass += $purchaseByWsArr[$value['shequ_id']]['is_pass'];
            $is_new += $purchaseByWsArr[$value['shequ_id']]['is_new'];
            $g_num_total += $purchaseByWsArr[$value['shequ_id']]['g_num_total'];
            $g_price_total += $purchaseByWsArr[$value['shequ_id']]['g_price_total'];
            $in_num_total += $purchaseByWsArr[$value['shequ_id']]['in_num_total'];
            $in_price_total += $purchaseByWsArr[$value['shequ_id']]['in_price_total'];
            $out_num_total += $purchaseByWsArr[$value['shequ_id']]['out_num_total'];
            $out_price_total += $purchaseByWsArr[$value['shequ_id']]['out_price_total'];
        }
        if(!empty($purchaseBysArr[$value['shequ_id']])){
            $is_pass += $purchaseBysArr[$value['shequ_id']]['is_pass'];
            $is_new += $purchaseBysArr[$value['shequ_id']]['is_new'];
            $g_num_total += $purchaseBysArr[$value['shequ_id']]['g_num_total'];
            $g_price_total += $purchaseBysArr[$value['shequ_id']]['g_price_total'];
            $in_num_total += $purchaseBysArr[$value['shequ_id']]['in_num_total'];
            $in_price_total += $purchaseBysArr[$value['shequ_id']]['in_price_total'];
            $out_num_total += $purchaseBysArr[$value['shequ_id']]['out_num_total'];
            $out_price_total += $purchaseBysArr[$value['shequ_id']]['out_price_total'];
        }
        //$message .= "未审批：".$is_new."，已审批：".$is_pass."，采购总数量：".$g_num_total.'，采购总值：'.$g_price_total . '，验收总数量：'.$in_num_total. '，验收总值：'.$in_price_total. '，退货总数量：'.$out_num_total. '，退货总值：'.$out_price_total;
        $title = $value['title']." 采购每日汇总";
        $message = json_encode(array(
            'is_new' => $is_new,
            'is_pass' => $is_pass,
            'g_num_total' => $g_num_total,
            'g_price_total' => $g_price_total,
            'in_num_total' => $in_num_total,
            'in_price_total' => $in_price_total,
            'out_num_total' => $out_num_total,
            'out_price_total' => $out_price_total,
        ));
        //组合数据
        $purchaseMessageValue .= "(".implode(',' , array(3 , 3 , time() , 1 , $value['uid']  , "'$title'" , "'$message'"))."),";
    }
    $purchaseMessageValue = substr($purchaseMessageValue,0,strlen($purchaseMessageValue)-1);
    $messageInsterSql = "INSERT INTO hii_message_warn (m_type , m_other_type , ctime , from_admin_id , to_admin_id  , message_title , message_content) VALUES {$purchaseMessageValue}";
    $purchaseData = $db->query($messageInsterSql);
    if (!$purchaseData) {
        echo 'add purchase message error';
        set_log('插入采购日报信息失败');
        exit;
    }else{
        echo 'add purchase message success';
        set_log('插入采购日报信息成功！');
        exit;
    }
}else{
    echo 'warehouse_member is null';
    set_log('获取仓库管理员列表失败');
    exit;
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