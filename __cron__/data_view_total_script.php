<?php
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'daily_statistics');

// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
//初始化部分参数
$yesterday_time = strtotime(date("Y-m-d" , strtotime("-1 day")));
$today_time = strtotime(date("Y-m-d" , time()));
//定义当前时间
$toady_time = strtotime(date('Y-m-d',time()));
$current_time  = time();
//本年开始时间
$current_year_time = mktime(0,0,0,1,1,date('Y'));
//上一年开始时间
$last_year_time = mktime(0,0,0,1,1,date('Y' , strtotime("-1 year")));
//获取当前月份的季度
$season = ceil(date('n') /3); //获取月份的季度
//本季度开始时间
$current_quarter_time = mktime(0,0,0,($season - 1) *3 +1,1,date('Y'));
//上个季度开始时间
$last_quarter_time = mktime(0,0,0,($season - 2) *3 +1,1,date('Y'));

//定义数据所属类型
define("GOODS_SALE_TYPE",1);//商品销售
define("GOODS_CAT_SALE_TYPE",2);//商品类销售
define("SHEQU_SALE_TYPE",3);//城市(门店)热点
define("STORE_SALE_TYPE",4);//门店销售
define("YEAR_SALE_RATE_TYPE",5);//年度销售
define("QUARTER_SALE_RATE_TYPE",6);//年度销售
define("SHEQU_SALE_RATE_TYPE",7);//城市增长率
define("YEAR_SALE_TOTAL_TYPE",8);//年度销售总额
define("CURRENT_SALE_TOTAL_TYPE",9);//当前销售总额
define("CURRENT_SALE_RATE_TYPE",11);//当前销售总额增长率
define("CURRENT_SALE_TIMES_TYPE",12);//当前消费次数
define("LAST_SALE_TIMES_TYPE",13);//昨天消费次数


 //年度销售总额
$year_sale_total_sql = "SELECT SUM(money) as money FROM hii_order where create_time >= {$current_year_time} AND status = 5 AND type = 'store'";
$getData = $db->query($year_sale_total_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".YEAR_SALE_TOTAL_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'year_sale_total_data insert success <br />';
}else{
    echo 'year_sale_total_data insert error <br />';
}

//当前销售总额(当天) 
$current_sale_total_sql = "SELECT SUM(money) as money FROM hii_order where create_time >= {$today_time} AND create_time <= {$current_time} AND status = 5 AND type = 'store'";
$currnet_getData = $db->query($current_sale_total_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($currnet_getData));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".CURRENT_SALE_TOTAL_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'current_sale_total_data insert success <br />';
}else{
    echo 'current_sale_total_data insert error <br />';
}

//当天销售额增长率
//昨日销售额
$b_time = $yesterday_time+($current_time-$today_time);
$last_sale_total_sql = "SELECT SUM(money) as money FROM hii_order where create_time >= {$yesterday_time} AND create_time <= {$b_time} AND status = 5 AND type = 'store'";
$last_getData = $db->query($last_sale_total_sql)->fetchAll(PDO::FETCH_ASSOC);
//根据对比值获取增长率
//$rate = sprintf("%.2f",(($currnet_getData[0]['money'] - $last_getData[0]['money']) / $currnet_getData[0]['money'])) * 100;
$rate = sprintf("%.2f",(($currnet_getData[0]['money'] - $last_getData[0]['money']) / $last_getData[0]['money'])) * 100;
if($rate > 0){
    $rate = "+".$rate;
}
$data = addslashes(json_encode(array(array('rate' => $rate))));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".CURRENT_SALE_RATE_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'current_sale_rate_data insert success <br />';
}else{
    echo 'current_sale_rate_data insert error <br />';
}


//当前消费次数
$current_sale_times_sql = "SELECT COUNT(id) as times  FROM hii_order where create_time >= {$today_time} AND create_time <= {$current_time} AND status = 5";
$getData = $db->query($current_sale_times_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".CURRENT_SALE_TIMES_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'CURRENT_SALE_TIMES_TYPE insert success <br />';
}else{
    echo 'CURRENT_SALE_TIMES_TYPE insert error <br />';
}

//昨天消费次数
$last_sale_times_sql = "SELECT COUNT(id) as times  FROM hii_order where create_time >= {$yesterday_time} AND create_time <= {$today_time} AND status = 5";
$getData = $db->query($last_sale_times_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".LAST_SALE_TIMES_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'LAST_SALE_TIMES_TYPE insert success <br />';
}else{
    echo 'LAST_SALE_TIMES_TYPE insert error <br />';
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