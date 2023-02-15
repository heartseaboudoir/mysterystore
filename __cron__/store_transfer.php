<?php
/**
 * 门店迁移
 * User: zzy
 * Date: 2018-05-16
 * Time: 15:45
 */
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');
$config  = set_config();
set_time_limit(500);
$int = microtime(true);
$time = time();
set_log('----开始执行----');
// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);
$shequ_id_out = 10; //原来的社区
$shequ_id = 17;  //要迁移去的区域
$store_id = 224;  //要迁移的门店
$admin_id = 6041; //迁移的用户 (一般时超级管理员)
$in = '224';
$ins = '194,195,196,197,221,222,223,224';

$query = $db->query("select goods_id,num,price  from hii_goods_store where store_id={$store_id} order by goods_id asc");
$query_two = $db->query("select goods_id,sum(num)num,avg(inprice)inprice from hii_warehouse_inout where store_id={$store_id} group by goods_id order by goods_id");
$query_array = array();
$query = $query->fetchAll(PDO::FETCH_ASSOC);
foreach($query as $key=>$val){

    $query_array[$val['goods_id']] = $val;
}

$query_two_array = array();
$query_two = $query_two->fetchAll(PDO::FETCH_ASSOC);
foreach($query_two as $key=>$val){
    $query_two_array[$val['goods_id']] = $val;
}
$db->beginTransaction();//开启事务处理
foreach ($query_array as $key=>$val) {

    if (!array_key_exists($key, $query_two_array) || $val['num'] > $query_two_array[$key]['num']) {
           
            if(!array_key_exists($key, $query_two_array)){
                $num = $val['num'];
            }else{
            	$num = $val['num'] - $query_two_array[$key]['num'];
            }

            //修改原先门店所在社区的批次库存  先减仓库
            $update = $db->query("select * from hii_warehouse_inout where shequ_id={$shequ_id_out} and goods_id={$val['goods_id']} and num>0 and warehouse_id >0 order by inout_id asc");
            $update = $update->fetchAll(PDO::FETCH_ASSOC);
           
            foreach ($update as $v) {
                if ($v['num'] >= $num) {
                    $update_she = $db->exec("update hii_warehouse_inout set num=num-{$num},outnum=outnum+{$num} where inout_id={$v['inout_id']}");
                    if ($update_she === false) {
                        $db->rollback();
                        set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  先减仓库25');
                    }
                    $num = 0;
                } else{
                    $num = $num - $v['num'];
                    $update_she = $db->exec("update hii_warehouse_inout set num=0,outnum=innum where inout_id={$v['inout_id']}");
                    if ($update_she === false) {
                        $db->rollback();
                        set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  先减仓库26');
                    }
                }
                if ($num == 0) {
                    break;
                }
            }
            //修改原先门店所在社区的批次库存  后减其他门店
            if ($num > 0){
                $update = $db->query("select * from hii_warehouse_inout where shequ_id={$shequ_id_out} and goods_id={$val['goods_id']} and num>0 and store_id >0 and store_id not in({$in}) order by inout_id asc");
                $update = $update->fetchAll(PDO::FETCH_ASSOC);
               
                foreach ($update as $v) {
                    if ($v['num'] >= $num) {
                        $update_she = $db->exec("update hii_warehouse_inout set num=num-{$num},outnum=outnum+{$num} where inout_id={$v['inout_id']}");
                        if ($update_she === false) {
                            $db->rollback();
                            set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  先减其他门店23');
                        }
                        $num = 0;
                    } else {
                        $num = $num - $v['num'];
                        $update_she = $db->exec("update hii_warehouse_inout set num=0,outnum=innum where inout_id={$v['inout_id']}");
                        if ($update_she === false) {
                            $db->rollback();
                            set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  先减其他门店24');
                        }
                    }
                    if ($num == 0) {
                        break;
                    }
                }
             }
            if($num > 0){
                $db->rollback();
                set_error($val['goods_id'].'库存大于批次'.$num);
            }

    }elseif($val['num'] < $query_two_array[$key]['num']){
        $num =$query_two_array[$key]['num'] - $val['num'];
        $time = time();
        $update = $db->query("select store_id from hii_goods_store where goods_id={$val['goods_id']} and store_id in (select id from hii_store where shequ_id ={$shequ_id_out} and id not in({$in})) order by num desc limit 1");
        $update = $update->fetchAll(PDO::FETCH_ASSOC);
        $update_she = $db->exec("insert into hii_warehouse_inout(goods_id,innum,inprice,outnum,num,ctime,ctype,shequ_id,store_id) values({$val['goods_id']},{$num},{$query_two_array[$key]['inprice']},0,{$num},{$time},1,{$shequ_id_out},{$update[0]['store_id']})");
        //修改原先门店所在社区的批次库存  先加其他门店
        
      /*   $update = $db->query("select * from hii_warehouse_inout where shequ_id={$shequ_id_out} and goods_id={$val['goods_id']} and outnum>0 and store_id >0 and store_id not in({$in}) order by inout_id asc");
        $update = $update->fetchAll(PDO::FETCH_ASSOC);
        foreach ($update as $v) {
            if ($v['outnum'] >= $num) {
                $update_she = $db->exec("update hii_warehouse_inout set num=num+{$num},outnum=outnum-{$num} where inout_id={$v['inout_id']}");
                if ($update_she === false) {
                    $db->rollback();
                    set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  先加其他门店33');
                }
                $num = 0;
            } else {
                $num = $num - $v['outnum'];
                $update_she = $db->exec("update hii_warehouse_inout set num=innum,outnum=0 where inout_id={$v['inout_id']}");
                if ($update_she === false) {
                    $db->rollback();
                    set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  先加其他门店34');
                }
            }
            if ($num == 0) {
                break;
            }
        }
        if($num > 0){
            //修改原先门店所在社区的批次库存  后加仓库
            $update = $db->query("select * from hii_warehouse_inout where shequ_id={$shequ_id_out} and goods_id={$val['goods_id']} and outnum>0 and warehouse_id >0 order by inout_id asc");
            $update = $update->fetchAll(PDO::FETCH_ASSOC);
            foreach ($update as $v) {
                if ($v['outnum'] >= $num) {
                    $update_she = $db->exec("update hii_warehouse_inout set num=num+{$num},outnum=outnum-{$num} where inout_id={$v['inout_id']}");
                    if ($update_she === false) {
                        $db->rollback();
                        set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  后加仓库35');
                    }
                    $num = 0;
                } else{
                    $num = $num - $v['outnum'];
                    $update_she = $db->exec("update hii_warehouse_inout set num=innum,outnum=0 where inout_id={$v['inout_id']}");
                    if ($update_she === false) {
                        $db->rollback();
                        set_error($val['goods_id'] . '修改原先门店所在社区的批次库存  后加仓库36');
                    }
                }
                if ($num == 0) {
                    break;
                }
            }
        }
        if($num > 0){
            $db->rollback();
            set_error($val['goods_id'].'库存小于批次'.$num);
        } */
    }
    //把原来该商品批次减为0
    if(array_key_exists($val['goods_id'],$query_two_array)){
        $update_she = $db->exec("update hii_warehouse_inout set num=0,outnum=innum where goods_id={$val['goods_id']} and store_id={$store_id}");
        if ($update_she === false) {
            $db->rollback();
            set_error($val['goods_id'] . '修改批次为0错误');
        }
    }
}

//做出入库平记录    出库单 A-A门店 类型其他  备注  门店迁移  入库单 A-A门店 类型其他 并保存新社区的入库批次

//------------------------------------------------新增出库单
$s_out_s_sn  =get_new_order_no($db,'CK','hii_store_out_stock','s_out_s_sn');
$sql = "insert into hii_store_out_stock(s_out_s_sn,s_out_s_status,s_out_s_type,ctime,admin_id,ptime,padmin_id,store_id1,store_id2,remark) 
VALUES('{$s_out_s_sn}',1,4,{$time},{$admin_id},{$time},{$admin_id},{$store_id},{$store_id},'门店迁移')";
$update_she = $db->exec($sql);
if (!$update_she) {
    $db->rollback();
    set_error( '添加出库单主表失败'.__LINE__);
}
$s_out_s_id = $db->lastInsertId();
$g_type = 0;
$g_nums = 0;
$detail_sql = "insert into hii_store_stock_detail(s_out_s_id,goods_id,g_num,g_price,remark) values";
foreach ($query_array as $k=>$v){
	if($v['num'] >0){
		if(empty($query_two_array[$v['goods_id']]['inprice'])){
			$update = $db->query("select stock_price from hii_warehouse_inout_view where shequ_id={$shequ_id_out} and goods_id={$v['goods_id']}");
			$update = $update->fetchAll(PDO::FETCH_ASSOC);
			$price = $update[0]['stock_price'];
		}else{
			$price = $query_two_array[$v['goods_id']]['inprice'];
		}
		$g_type +=1;
		$g_nums += $v['num'];
		$detail_sql .= "({$s_out_s_id},{$v['goods_id']},{$v['num']},'{$price}','门店迁移'),";
	}
}
$detail_sql = rtrim($detail_sql,',');
$update_she = $db->exec($detail_sql);
if (!$update_she) {
    $db->rollback();
    set_error( '添加出库单子表失败'.__LINE__);
}
$update_she = $db->exec("update hii_store_out_stock set g_type={$g_type},g_nums={$g_nums} where s_out_s_id={$s_out_s_id}");
if (!$update_she) {
    $db->rollback();
    set_error( '修改出库单主表数量失败'.__LINE__);
}

//--------------------------------------------新增入库单
$s_in_s_sn  =get_new_order_no($db,'MS','hii_store_in_stock','s_in_s_sn');
$sql = "insert into hii_store_in_stock(s_in_s_sn,s_in_s_status,s_in_s_type,ctime,admin_id,ptime,padmin_id,store_id1,store_id2,remark) 
VALUES('{$s_in_s_sn}',1,3,{$time},{$admin_id},{$time},{$admin_id},{$store_id},{$store_id},'门店迁移')";
$update_she = $db->exec($sql);
if (!$update_she) {
    $db->rollback();
    set_error( '添加入库单主表失败'.__LINE__);
}
$s_in_s_id = $db->lastInsertId();
$g_type = 0;
$g_nums = 0;
$detail_sql = "insert into hii_store_in_stock_detail(s_in_s_id,goods_id,g_num,g_price,remark) values";
foreach ($query_array as $k=>$v){
	if($v['num'] > 0){
		
		if(empty($query_two_array[$v['goods_id']]['inprice'])){
			$update = $db->query("select stock_price from hii_warehouse_inout_view where shequ_id={$shequ_id_out} and goods_id={$v['goods_id']}");
			$update = $update->fetchAll(PDO::FETCH_ASSOC);
			$price = $update[0]['stock_price'];
		}else{
			$price = $query_two_array[$v['goods_id']]['inprice'];
		}
		$g_type +=1;
		$g_nums += $v['num'];
		$detail_sql .= "({$s_in_s_id},{$v['goods_id']},{$v['num']},'{$price}','门店迁移'),";
	} 
}
$detail_sql = rtrim($detail_sql,',');
$update_she = $db->exec($detail_sql);
if (!$update_she) {
    $db->rollback();
    set_error( '添加入库单子表失败'.__LINE__);
}
$update_she = $db->exec("update hii_store_in_stock set g_type={$g_type},g_nums={$g_nums} where s_in_s_id={$s_in_s_id}");
if ($update_she === false) {
    $db->rollback();
    set_error( '修改入库单主表数量失败'.__LINE__);
}
//增加批次
$inout_detail_sql = "insert into hii_warehouse_inout(goods_id,innum,inprice,num,ctime,ctype,shequ_id,store_id) values";
foreach ($query_array as $k=>$v){
	if($v['num'] > 0){
		if(empty($query_two_array[$v['goods_id']]['inprice'])){
			$update = $db->query("select stock_price from hii_warehouse_inout_view where shequ_id={$shequ_id_out} and goods_id={$v['goods_id']}");
			$update = $update->fetchAll(PDO::FETCH_ASSOC);
			$price = $update[0]['stock_price'];
		}else{
			$price = $query_two_array[$v['goods_id']]['inprice'];
		}
		$g_type +=1;
		$g_nums += $v['num'];
		$inout_detail_sql .= "({$v['goods_id']},{$v['num']},'{$price}',{$v['num']},{$time},2,{$shequ_id},{$store_id}),";
	}
}
$inout_detail_sql = rtrim($inout_detail_sql,',');
$update_she = $db->exec($inout_detail_sql);
if (!$update_she) {
	$db->rollback();
	set_error( '新增批次失败'.__LINE__);
}
$update_she = $db->exec("update hii_store set shequ_id={$shequ_id} where id={$store_id}");
if ($update_she === false) {
	$db->rollback();
	set_error( '修改门店表shequ_id失败'.__LINE__);
}
$db->commit();//提交事务
$out =  microtime(true);
echo round(($out-$int),4);
exit;


/**
 * 新单据单号
 * @param string $no_str 单据前缀
 * @param string $table 表名
 * @param string $field_str 字段名
 * 返回值：新单据号字符串
 * @author 文定明
 */
function get_new_order_no($db,$no_str, $table, $field_str){
    $pre    = $no_str .date("Ymd");

    $data = $db->query("select max($field_str) as f_str from $table where $field_str like '" . $pre . "%'");
    $data = $data->fetchAll(PDO::FETCH_ASSOC);
    if($data[0]['f_str'] == '') {
        $out_no = $pre .'00001';
    }else{
        $no_id = (int)substr($data[0]['f_str'], -5) + 1;
        $out_no = $pre .sprintf("%05d", $no_id);
    }
    return $out_no;
}

/*$query = $db->query("select si_id from  hii_store_inventory where si_type=1 and  ctime < {$now_time} and si_status = 0");
if($query !== false){
    $query = $query->fetchAll(PDO::FETCH_ASSOC);
    if(empty($query)){
        exit;
    }
}
$db->beginTransaction();//开启事务处理
foreach ($query as $key=>$val){
    $delete = $db->prepare("delete from hii_store_inventory where si_id= :si_id");
    $delete->execute(array(':si_id' => $val['si_id']));
    if($delete->rowCount() == false){
        $db->rollback();
        set_error($val['si_id'].'删除月末盘点主表失败-脚本');
    }
    $delete = $db->prepare("delete from hii_store_inventory_detail where si_id= :si_id");
    $delete->execute(array(':si_id' => $val['si_id']));
    if($delete->rowCount() == false){
        $db->rollback();
        set_error($val['si_id'].'删除月末盘点子表失败-脚本');
    }

}
$db->commit();//提交事务*/


function set_config($config = array()){
    $caiji_config_file = ROOT_PATH.'Runtime/'.CAIJI_NAME.'.php';
    $dir = dirname($caiji_config_file);
    if(!is_dir($dir)){
        mkdirp($dir);
    }
    if(file_exists($caiji_config_file)){
        $caiji_config =  @include $caiji_config_file;
        !$caiji_config && $caiji_config = array();
        $config && $caiji_config = $caiji_config ? array_merge($caiji_config, $config) : $config;
    }else{
        $caiji_config = $config;
    }
    file_put_contents($caiji_config_file, '<?php return '.var_export($caiji_config, true).';');
    return $caiji_config;
}

function set_log($data, $time = 1){
    $log_dir = ROOT_PATH.'Runtime/'.CAIJI_NAME.'_logs/';
    if(!is_dir($log_dir)){
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_dir.date('Y-m').'.txt', ($time ? "[".date('Y-m-d H:i:s')."] " : '').$data."\r\n", FILE_APPEND);
}

function set_error($str = ''){
    set_log($str);
    set_config(array('msg' => $str, 'last_e_time' => date('Y-m-d H:i:s')));
    echo 'error: '.$str;
    exit;
}