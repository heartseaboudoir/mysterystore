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


 //商品销售额排名
$goods_sql = "SELECT G.title , SUM(OD.num * OD.price) as total_money FROM hii_goods G ";
$goods_sql .= "LEFT JOIN hii_order_detail as OD ON OD.d_id = G.id ";
$goods_sql .= "LEFT JOIN hii_order as O ON O.order_sn = OD.order_sn ";
$goods_sql .= "WHERE O.create_time >= {$toady_time} AND O.create_time <= {$current_time} AND O.status = 5 AND O.type = 'store' ";
$goods_sql .= "GROUP BY G.id ORDER BY total_money DESC limit 0,42 ";
$getData = $db->query($goods_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$goods_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".GOODS_SALE_TYPE.",'$data')";
$g_result = $db->query($goods_data_sql);
if($g_result){
    echo 'goods_sale_data insert success <br />';
}else{
    echo 'goods_sale_data insert error <br />';
}

//商品类销售额排名
$goods_cat_sql = "SELECT GC.title , SUM(OD.num * OD.price) as total_money FROM hii_goods_cate GC ";
$goods_cat_sql .= "LEFT JOIN hii_goods as G ON GC.id = G.cate_id ";
$goods_cat_sql .= "LEFT JOIN hii_order_detail as OD ON OD.d_id = G.id ";
$goods_cat_sql .= "LEFT JOIN hii_order as O ON O.order_sn = OD.order_sn ";
$goods_cat_sql .= "WHERE O.create_time >= {$today_time} AND O.create_time <= {$current_time} AND O.status = 5 AND O.type = 'store' ";
$goods_cat_sql .= "GROUP BY GC.id ORDER BY total_money DESC limit 0,13 ";
$getData = $db->query($goods_cat_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$goods_cat_data_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".GOODS_CAT_SALE_TYPE.",'$data')";
$g_c_result = $db->query($goods_cat_data_sql);
if($g_c_result){
    echo 'goods_cat_sale_data insert success <br />';
}else{
    echo 'goods_cat_sale_data insert error <br />';
}

//门店销售排名TOP 10
$store_sale_sql = "SELECT S.id , S.title as title ,SUM(O.pay_money) as total_money  FROM hii_store as S ";
$store_sale_sql .= "LEFT JOIN hii_order as O ON S.id = O.store_id ";
$store_sale_sql .= "WHERE O.create_time >= {$today_time} AND O.create_time <= {$current_time} AND O.status = 5 AND O.type = 'store' ";
$store_sale_sql .= "GROUP BY S.id ";
$store_sale_sql .= "ORDER BY total_money DESC ";
$store_sale_sql .= "LIMIT 0 , 20";
$getData = $db->query($store_sale_sql)->fetchAll(PDO::FETCH_ASSOC);
$data = addslashes(json_encode($getData));
//写入ali_data_view
$store_sale_sql = "REPLACE INTO hii_ali_data_view (type , data) VALUES (".STORE_SALE_TYPE.",'$data')";
$g_c_result = $db->query($store_sale_sql);
if($g_c_result){
    echo 'store_sale_data insert success <br />';
}else{
    echo 'store_sale_data insert error <br />';
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