<?php
/**
 * 获取门店关联用户的数据 每天执行
 */
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');

set_time_limit(0);
$time = time();
$e_time = strtotime(date('Y-m-d 00:00:00'));
$s_time = $e_time-3600*24;
// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);

$limit = 0;    
   while (true) {
       $order_sql = "SELECT uid,store_id,create_time
	                       FROM hii_order
                            where create_time >= {$s_time}
                             and create_time < {$e_time}
                             and type ='store'
                             and `status` =5
                             and uid !=0
                              group by uid,store_id limit {$limit},1000";
       $query = $db->query($order_sql);
            if($query !== false){
           		 $query = $query->fetchAll(PDO::FETCH_ASSOC);
           		 if(!empty($query)){
           		     $limit +=1000;
           		     foreach($query as $val){
           		         $sql = "INSERT INTO hii_user_store (uid, store_id,ctime) 
                                    SELECT {$val['uid']},{$val['store_id']},{$val['create_time']} FROM DUAL  
                                    WHERE NOT EXISTS ( SELECT	uid FROM hii_user_store	WHERE uid={$val['uid']} and store_id={$val['store_id']})";
           		         	$db->exec($sql);
           		     }
           		    
           		 }else{
           		        break;
                   	}
               }else{
                   break;
               }
   }
   echo true;die;