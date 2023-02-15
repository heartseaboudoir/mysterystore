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
//获取本月第一天
$current_month_time = strtotime(date('Y-m-01',time()));
//获取上个月第一天
$last_month_time = strtotime(date('Y-m-01',strtotime("-1 month")));

//定义数据所属类型
define("GOODS_SALE_TYPE",1);//商品销售
define("GOODS_CAT_SALE_TYPE",2);//商品类销售
define("SHEQU_SALE_TYPE",3);//城市(门店)热点
define("STORE_SALE_TYPE",4);//门店销售
define("YEAR_SALE_RATE_TYPE",5);//年度销售
define("QUARTER_SALE_RATE_TYPE",6);//年度销售
define("SHEQU_SALE_RATE_TYPE",7);//城市增长率
define("LAST_SALE_TOTAL_TYPE",10);//昨天销售总额

//年度同比增长
$current_year_rate_sale_sql = "SELECT convert(SUM(money)/10000,decimal) as money  FROM hii_order ";
$current_year_rate_sale_sql .= "WHERE create_time >= {$current_year_time} AND create_time <= {$current_time} AND status = 5 AND type = 'store' ";
$current_sale_data = $db->query($current_year_rate_sale_sql)->fetchAll(PDO::FETCH_ASSOC);
if(empty($current_sale_data) || $current_sale_data[0]['money'] <= 0){
    $data = array(array('rate' => 0));
}else{
    //获取上一年销售额度(同时间区间)
    $last_end_time = $last_year_time+($current_time-$current_year_time);
    $last_year_rate_sale_sql = "SELECT convert(SUM(money)/10000,decimal) as money  FROM hii_order ";
    $last_year_rate_sale_sql .= "WHERE create_time >= {$last_year_time} AND create_time <= {$last_end_time} AND status = 5 AND type = 'store' ";
    $last_sale_data = $db->query($last_year_rate_sale_sql)->fetchAll(PDO::FETCH_ASSOC);
    if(empty($last_sale_data) || $last_sale_data[0]['money'] <= 0){
        $data = array(array('rate' => 100));
    }else{
        $rate = sprintf("%.3f",(($current_sale_data[0]['money'] - $last_sale_data[0]['money']) / $last_sale_data[0]['money'])) * 100;
        if($rate > 0){
            $rate = "+".$rate;
        }
        $data = array(array('rate' => $rate));
    }
}
$data = addslashes(json_encode($data));
//写入ali_data_view
$store_sale_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".YEAR_SALE_RATE_TYPE.",'$data')";
$g_c_result = $db->query($store_sale_sql);
if($g_c_result){
    echo 'year_sale_rate_data insert success <br />';
}else{
    echo 'year_sale_rate_data insert error <br />';
}

//季度环比增长
$current_quarter_rate_sale_sql = "SELECT convert(SUM(money)/10000,decimal) as money  FROM hii_order ";
$current_quarter_rate_sale_sql .= "WHERE create_time >= {$current_quarter_time} AND create_time <= {$current_time} AND status = 5 AND type = 'store' ";
$current_sale_data = $db->query($current_quarter_rate_sale_sql)->fetchAll(PDO::FETCH_ASSOC);
if(empty($current_sale_data) || $current_sale_data[0]['money'] <= 0){
    $data = array(array('rate' => 0));
}else{
    //获取上一年销售额度(同时间区间)
    $last_end_time = $last_quarter_time+($current_time-$current_quarter_time);
    $last_quarter_rate_sale_sql = "SELECT convert(SUM(money)/10000,decimal) as money  FROM hii_order ";
    $last_quarter_rate_sale_sql .= "WHERE create_time >= {$last_quarter_time} AND create_time <= {$last_end_time} AND status = 5 AND type = 'store' ";
    $last_sale_data = $db->query($last_quarter_rate_sale_sql)->fetchAll(PDO::FETCH_ASSOC);
    if(empty($last_sale_data) || $last_sale_data[0]['money'] <= 0){
        $data = array(array('rate' => 100));
    }else{
        $rate = sprintf("%.3f",(($current_sale_data[0]['money'] - $last_sale_data[0]['money']) / $last_sale_data[0]['money'])* 100);
        if($rate > 0){
            $rate = "+".$rate;
        }
        $data = array(array('rate' => $rate));
    }
}
$data = addslashes(json_encode($data));
//写入ali_data_view
$store_sale_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".QUARTER_SALE_RATE_TYPE.",'$data')";
$g_c_result = $db->query($store_sale_sql);
if($g_c_result){
    echo 'quarter_sale_rate_data insert success <br />';
}else{
    echo 'quarter_sale_rate_data insert error <br />';
}



//城市增长率
//获取本月的销售排行
$current_sale_shequ_sql = "SELECT SQ.id , SQ.title , SUM(pay_money) as total_money  FROM hii_order as O ";
$current_sale_shequ_sql .= "LEFT JOIN hii_store as S ON S.id = O.store_id ";
$current_sale_shequ_sql .= "LEFT JOIN hii_shequ as SQ ON SQ.id = S.shequ_id ";
$current_sale_shequ_sql .= "WHERE O.create_time >= {$current_month_time} AND O.create_time <= {$current_time} AND SQ.newerp = 1 AND O.status = 5 AND O.type = 'store' ";
$current_sale_shequ_sql .= "GROUP BY SQ.id ORDER BY total_money";
$current_sale_shequ_data = $db->query($current_sale_shequ_sql)->fetchAll(PDO::FETCH_ASSOC);
$current_sale_shequ_data = valueToKey($current_sale_shequ_data , 'id' , true);
//获取上一月销售排行
$b_time = $last_month_time + ($current_time - $current_month_time);
$last_sale_shequ_sql = "SELECT SQ.id , SQ.title , SUM(pay_money) as total_money  FROM hii_order as O ";
$last_sale_shequ_sql .= "LEFT JOIN hii_store as S ON S.id = O.store_id ";
$last_sale_shequ_sql .= "LEFT JOIN hii_shequ as SQ ON SQ.id = S.shequ_id ";
$last_sale_shequ_sql .= "WHERE O.create_time >= {$last_month_time} AND O.create_time <= {$b_time} AND O.store_id > 0 AND SQ.newerp = 1 AND O.status = 5 AND O.type = 'store' ";
$last_sale_shequ_sql .= "GROUP BY SQ.id ORDER BY total_money";
$last_year_sale_shequ_data = $db->query($last_sale_shequ_sql)->fetchAll(PDO::FETCH_ASSOC);
$last_year_sale_shequ_data = valueToKey($last_year_sale_shequ_data , 'id' , true);

$replace_num = 0;
$data = array();
//循环获取差值
foreach($current_sale_shequ_data as $key => $value){
    if(!empty($last_year_sale_shequ_data[$key]['total_money'])){
        $dif_data = $value['total_money'] - $last_year_sale_shequ_data[$key]['total_money'];
        //获取最大值的ID
        if($dif_data > $replace_num){
            $replace_num = $dif_data;
            $start_id = $key;
        }
    }else{
        continue;
    }
}

//获取增长值
$data['id'] = $current_sale_shequ_data[$start_id]['id'];
$data['title'] = $current_sale_shequ_data[$start_id]['title'];
$data['rate'] = sprintf("%.2f",(($current_sale_shequ_data[$start_id]['total_money'] -  $last_year_sale_shequ_data[$start_id]['total_money']) / $current_sale_shequ_data[$start_id]['total_money'])* 100);
$data = addslashes(json_encode(array($data)));
//写入ali_data_view
$store_sale_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".SHEQU_SALE_RATE_TYPE.",'$data')";
$g_c_result = $db->query($store_sale_sql);
if($g_c_result){
    echo 'shequ_sale_rate_data insert success <br />';
}else{
    echo 'shequ_sale_rate_data insert error <br />';
}


//昨日销售额
$last_sale_total_sql = "SELECT SUM(money) as money FROM hii_order where create_time >= {$yesterday_time} AND create_time <= {$today_time} AND status = 5 AND type = 'store'";
$getData = $db->query($last_sale_total_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".LAST_SALE_TOTAL_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'last_sale_total_data insert success <br />';
}else{
    echo 'last_sale_total_data insert error <br />';
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