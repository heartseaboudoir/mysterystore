<?php
/**
 * 生成商品属性
 * User: zzy
 * Date: 2018-05-16
 * Time: 15:45
 */
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');

set_time_limit(0);
// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
//   $query = $db->query("select id,bar_code from hii_goods");
// 	 if($query !== false){
// 		 $query = $query->fetchAll(PDO::FETCH_ASSOC);
// 		 if(empty($query)){
// 			 exit;
// 		 }
//  	}
// $bar_code = array();

// $sql = "insert into hii_attr_value(goods_id,value_name,bar_code,ctime) values";
// foreach ($query as $k=>$v){
// 	$sql.="({$v['id']},'原味','{$v['bar_code']}',".time()."),";	 
// } 
// 	$db->exec(trim($sql,','));  

/* 	$query = $db->query("select s_in_s_d_id,goods_id from hii_store_in_stock_detail where value_id is null");
	if($query !== false){
		foreach($query as $k=>$v){
			 	 		 	$sql = "update hii_store_in_stock_detail set value_id=(select min(value_id)value_id from hii_attr_value where goods_id={$v['goods_id']}) where s_in_s_d_id={$v['s_in_s_d_id']}"; //仓库库存
			 				$s = $db->exec($sql);
		}
	}

echo 33;die; */
 $query = $db->query("select goods_id,value_id from hii_attr_value where value_name='原味'");
	 if($query !== false){
	 	foreach($query as $k=>$v){ 		
	 		
// 	 		 	$sql = "update hii_warehouse_stock set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库库存
//  				$s = $db->exec($sql);
 			
//  	$sql = "update hii_request_temp set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //临时申请表
//  	$db->exec($sql);
//  	$sql = "update hii_store_request_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //发货申请单
//  	$s = $db->exec($sql);
//  	$sql = "update hii_store_in_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //门店入库验收单
//  	$db->exec($sql);
//  	$sql = "update hii_store_in_stock_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //门店入库单
//  	$db->exec($sql);
//  	$sql = "update hii_store_back_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //门店返仓单
//  	$db->exec($sql);
//  	$sql = "update hii_store_other_out_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //门店报损单
//  	$db->exec($sql);
//  	$sql = "update hii_store_stock_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //门店出库单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_in_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库入库验收单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_in_stock_detail set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库入库单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_inventory_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库盘点单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_other_out_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库报损单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_out_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库出库验货单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_out_stock_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库出库单
//  	$db->exec($sql);
//  	$sql = "update hii_warehouse_request_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //仓库调拨申请
//  	$db->exec($sql);
//  	$sql = "update hii_purchase_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //采购单
//  	$db->exec($sql);
//  	$sql = "update hii_purchase_out_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //采购单退货
//  	$db->exec($sql);
//  	$sql = "update hii_purchase_request_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //采购单申请
//  	$db->exec($sql);
//  	$sql = "update hii_purchase_supply_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //采购询价单
//  	$db->exec($sql);
//  	$sql = "update hii_consignment_out_detail  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //寄售出库单
//  	$db->exec($sql);
//  	$sql = "update hii_goods_bar_code  set value_id={$v['value_id']} where goods_id={$v['goods_id']}"; //商品条码
//  	$db->exec($sql);
	 	}
 	}
 	echo '成功';
exit;
