<?php


namespace Erp\Model;
use Think\Model;

class WarehouseInventoryModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交盘点
    public function saveWarehouseInventory($i_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $i_id ) {  //编辑计划
            $ok = M("WarehouseInventory")->where('i_id='.$i_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交盘点单失败');
                return false;
            }
            $ok = M("WarehouseInventoryDetail")->where('i_id='.$i_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'盘点单商品删除失败');
                return false;
            }
        } else { //新增盘点
            $i_id = $this->add($data);
            if(!$i_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存盘点单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['i_id'] = $i_id;
        }
        $ok = M("WarehouseInventoryDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存盘点单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $i_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $i_id;
    }

    /**
     * 仓库盘点审核
     * id  盘点单id
     * warehouse_id  仓库id
     * admin_id 管理员id
     */
    public function check($id, $warehouse_id, $admin_id){
        $warehouseInventoryDetailModel = M('WarehouseInventoryDetail');
        $this->startTrans();
        $is = $this->where(array('i_id'=>$id,'i_status'=>0))->lock(true)->find();
        if(empty($is)){
            $this->rollback();
            return array('status'=>0,'msg'=>'没有盘点单 或已经处理 不能再次审核');
        }
        $is_info =$warehouseInventoryDetailModel->where(array('i_id'=>$id))->find();
        if(empty($is_info)){
            $this->rollback();
            return array('status'=>0,'msg'=>'盘点单没有商品');
        }
        //获取区域id
        if(($shequ_id = M('Warehouse')->where(array('w_id'=>$warehouse_id))->getField('shequ_id')) == false){
            $this->rollback();
            return array('status'=>0,'msg'=>'获取区域错误');
        }
        //判断盘点商品是否修改 如果库存为0 可以不判断
        $audit_mark = $warehouseInventoryDetailModel->field("goods_id,value_id")->where(array("i_id"=>$id,"audit_mark"=>0))->select();
        if(!is_null($audit_mark) || !empty($audit_mark) || count($audit_mark)!=0){
            foreach ($audit_mark as $kk=>$vv){
                $audit_mark_id = M("WarehouseStock")->where(array("w_id"=>$warehouse_id,"num"=>array("NEQ",0),"goods_id"=>$vv['goods_id'],'value_id'=>$vv['value_id']))->find();
                if(count($audit_mark_id) != 0){
                    $this->rollback();
                    return array('status'=>0,'msg'=>"盘点单商品未盘点 不能审核".$vv['goods_id']);
                }
            }

        }
        //获取盘点单 数据
        $info = $this->alias('wi')
            ->field('wid.*,(wid.g_num-ifnull(ws.num,0)) as add_num,g.expired_days')
            ->join('left join hii_warehouse_inventory_detail wid on wid.i_id = wi.i_id')
            ->join('left join hii_warehouse_stock ws on ws.goods_id=wid.goods_id and ws.value_id = wid.value_id and wi.warehouse_id=ws.w_id')
            ->join('left join hii_goods g on g.id=wid.goods_id')
            ->where('wi.i_id='.$id)
            ->order('wid.goods_id')
            ->select();
        if($info === false){
            $this->rollback();
            return array('status'=>0,'msg'=>'数据内部错误 请联系技术人员!!!');
        }
        $DetailListWI1 = array(); //盘盈数据
        $DetailListWI2 = array(); //盘亏数据
        $pcount1 = 0;  //入库商品种类数量
        $nums1 = 0; //入库商品总数量
        $pcount2 = 0; //出库商品种类数量
        $nums2 = 0; //出库商品总数量
        foreach($info as $key=>$val){
            $DetailListTmp = array();
            if($val['add_num'] >0){
                //新增仓库入库单
                $DetailListTmp['goods_id'] = $val['goods_id'];
                $DetailListTmp['g_num'] = abs($val['add_num']);
                $DetailListTmp['g_price'] = $val['g_price'];
                $DetailListTmp['remark'] = $val['remark'];
                $DetailListTmp['w_in_d_id'] = $val['i_d_id'];
                $DetailListTmp['value_id'] = $val['value_id'];
                $DetailListTmp['endtime'] = time()+($val['expired_days']*24*60*60);
                $DetailListWI1[] = $DetailListTmp;
                $pcount1 += 1;
                $nums1 += abs($val['add_num']);
            }elseif($val['add_num'] < 0){
                //盘亏写入出库单
                $DetailListTmp['goods_id'] = $val['goods_id'];
                $DetailListTmp['g_num'] = abs($val['add_num']);
                $DetailListTmp['g_price'] = $val['g_price'];
                $DetailListTmp['remark'] = $val['remark'];
                $DetailListTmp['w_in_d_id'] = $val['i_d_id'];
                $DetailListTmp['value_id'] = $val['value_id'];
                $DetailListWI2[] = $DetailListTmp;
                $pcount2 += 1;
                $nums2 += abs($val['add_num']);
            }
        }
        //表变量
        $warehouseInStockMdoel = M('WarehouseInStock');
        $warehouseInStockDetailModel = M('WarehouseInStockDetail');
        $warehouseOutStockModel = M('WarehouseOutStock');
        $warehouseOutStockDetailModel = M('WarehouseOutStockDetail');
        $warehouseInoutModel = M('WarehouseInout');
        $warehouseStockModel = M('WarehouseStock');
     /******************盘盈入库*******************/
        if(!empty($DetailListWI1)){
            //审核并新增入库单
            $new_no = get_new_order_no('RK','hii_warehouse_in_stock','w_in_s_sn');
            $dataWI = array();
            $dataWI['w_in_s_sn'] = $new_no;
            $dataWI['w_in_s_status'] = 1;
            $dataWI['w_in_s_type'] = 3;
            $dataWI['w_in_id'] = 0;
            $dataWI['i_id'] = $id;
            $dataWI['w_in_id'] = 0;
            $dataWI['ctime'] = time();
            $dataWI['admin_id'] = $admin_id;
            $dataWI['etime'] = time();
            $dataWI['eadmin_id'] = $admin_id;
            $dataWI['ptime'] = time();
            $dataWI['padmin_id'] = $admin_id;
            $dataWI['supply_id'] = 0;
            $dataWI['warehouse_id'] = $is['warehouse_id'];
            $dataWI['remark'] = $is['remark'];
            $dataWI['g_type'] = $pcount1;
            $dataWI['g_nums'] = $nums1;
            if( ($warehouseInStock_id = $warehouseInStockMdoel->add($dataWI)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'新增入库单主表失败');
            }
            foreach ($DetailListWI1 as $key=>$val){
                /*********入库单字表数据***********/
                $dataWI_detail = array();
                $dataWI_detail['w_in_s_id'] = $warehouseInStock_id;
                $dataWI_detail['goods_id'] = $val['goods_id'];
                $dataWI_detail['g_num'] = $val['g_num'];
                $dataWI_detail['g_price'] = $val['g_price'];
                $dataWI_detail['endtime'] = $val['endtime'];
                $dataWI_detail['w_in_d_id'] = 0;
                $dataWI_detail['remark'] = $val['remark'];
                $dataWI_detail['value_id'] = $val['value_id'];
                if(($warehouseInStockDetail_id = $warehouseInStockDetailModel->add($dataWI_detail)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'新增入库单子表失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                }
                //更新库存表
                if($warehouseStock_find = $warehouseStockModel->where(array('w_id'=>$warehouse_id,'goods_id'=>$val['goods_id'],'value_id'=>$val['value_id']))->find()){
                    if($warehouseStockModel->where(array('w_id'=>$warehouse_id,'goods_id'=>$val['goods_id'],'value_id'=>$val['value_id']))->setInc('num',$val['g_num']) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'库存更新失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                    }
                }else{
                    $array = array();
                    $array['w_id'] = $warehouse_id;
                    $array['goods_id'] = $val['goods_id'];
                    $array['num'] = $val['g_num'];
                    $array['value_id'] = $val['value_id'];
                    if($warehouseStockModel->add($array) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'加库存更新失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                    }
                }

                /************新增批次***********/
                $array = array();
                $array['goods_id'] = $val['goods_id'];
                $array['innum'] = $val['g_num'];
                $array['inprice'] = $val['g_price'];
                $array['num'] = $val['g_num'];
                $array['ctime'] = time();
                $array['ctype'] = 1;
                $array['shequ_id'] = $shequ_id;
                $array['endtime'] = $val['endtime'];
                $array['warehouse_id'] = $is['warehouse_id'];
                $array['w_in_s_d_id'] = $warehouseInStockDetail_id;
                $array['value_id'] = $val['value_id'];
                if($warehouseInoutModel->add($array) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'入库批次新增失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                }
            }
        }
        /******************盘亏出库*******************/
        if(!empty($DetailListWI2)){
            //审核并新增出库单
            $new_no = get_new_order_no('CK','hii_warehouse_out_stock','w_out_s_sn');
            $dataWI = array();
            $dataWI['w_out_s_sn'] = $new_no;
            $dataWI['w_out_s_status'] = 1;
            $dataWI['w_out_s_type'] = 3;
            $dataWI['i_id'] = $id;
            $dataWI['w_out_id'] = 0;
            $dataWI['ctime'] = time();
            $dataWI['admin_id'] = $admin_id;
            $dataWI['etime'] = time();
            $dataWI['eadmin_id'] = $admin_id;
            $dataWI['ptime'] = time();
            $dataWI['padmin_id'] = $admin_id;
            $dataWI['warehouse_id1'] = 0;
            $dataWI['warehouse_id2'] = $is['warehouse_id'];
            $dataWI['remark'] = $is['remark'];
            $dataWI['g_type'] = $pcount2;
            $dataWI['g_nums'] = $nums2;
            if( ($warehouseOutStock_id = $warehouseOutStockModel->add($dataWI)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'新增出库单主表失败');
            }
            foreach($DetailListWI2 as $key=>$val){
                /*********出库单字表数据***********/
                $dataWI_detail = array();
                $dataWI_detail['w_out_s_id'] = $warehouseOutStock_id;
                $dataWI_detail['goods_id'] = $val['goods_id'];
                $dataWI_detail['g_num'] = $val['g_num'];
                $dataWI_detail['g_price'] = $val['g_price'];
                $dataWI_detail['remark'] = $val['remark'];
                $dataWI_detail['value_id'] = $val['value_id'];
                if(($warehouseOutStockDetail_id = $warehouseOutStockDetailModel->add($dataWI_detail)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'新增出库单子表失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                }
                /********更新库存表***************/
                if($warehouseStock_find = $warehouseStockModel->where(array('w_id'=>$warehouse_id,'goods_id'=>$val['goods_id'],'value_id'=>$val['value_id'],'num'=>array('EGT',$val['g_num'])))->find()){
                    if($warehouseStockModel->where(array('w_id'=>$warehouse_id,'goods_id'=>$val['goods_id'],'value_id'=>$val['value_id']))->setDec('num',$val['g_num']) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'减库存更新失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                    }
                }else{
                    $this->rollback();
                    return array('status'=>0,'msg'=>'库存不够 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                }
                /************减库存批次*********/
                    //盘亏数量减掉【本仓库】入库批次的批次数量 先进先出。
                $g_num_int = $val['g_num'];
                $WarehouseInoutData = $warehouseInoutModel->where('goods_id = ' .$val['goods_id'] .' and num>0 and warehouse_id = ' .$warehouse_id)->order('ctime asc')->select();
                foreach($WarehouseInoutData as $kinout=>$vinout){
                    if($g_num_int <= 0){
                        break;
                    }
                    if($g_num_int >= $vinout['num']){
                        $g_num_int -= $vinout['num'];
                        $vinout['enum'] += $vinout['num'];
                        $vinout['num'] = 0;
                        $vinout['outnum'] = $vinout['innum'];
                    }else{
                        $vinout['num'] -= $g_num_int;
                        $vinout['outnum'] += $g_num_int;
                        $vinout['enum'] += $g_num_int;
                        $g_num_int = 0;
                    }
                    $array = array();
                    $array['inout_id'] = $vinout['inout_id'];
                    $array['outnum'] = $vinout['outnum'];
                    $array['num'] = $vinout['num'];
                    $array['etime'] = time();
                    $array['etype'] = 2;
                    $array['enum'] = $vinout['enum'];
                    $array['e_id'] = $warehouseOutStock_id;
                    $array['e_no'] = $vinout['e_no'] + 1;
                    if($warehouseInoutModel->save($array) === false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'减批次数据内部错误 请联系技术人员!!! 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                    }
                }
                //盘亏数量减掉【本社区其他仓库】入库批次的批次数量 先进先出。
                if($g_num_int > 0){
                    $WarehouseInoutData = $warehouseInoutModel->where('goods_id = ' .$val['goods_id'] .' and num>0 and shequ_id = '.$shequ_id.' and warehouse_id != 0 and  warehouse_id != ' .$warehouse_id)->order('ctime asc')->select();
                    foreach($WarehouseInoutData as $kinout=>$vinout){
                        if($g_num_int <= 0){
                            break;
                        }
                        if($g_num_int >= $vinout['num']){
                            $g_num_int -= $vinout['num'];
                            $vinout['enum'] += $vinout['num'];
                            $vinout['num'] = 0;
                            $vinout['outnum'] = $vinout['innum'];
                        }else{
                            $vinout['num'] -= $g_num_int;
                            $vinout['outnum'] += $g_num_int;
                            $vinout['enum'] += $g_num_int;
                            $g_num_int = 0;
                        }
                        $array = array();
                        $array['inout_id'] = $vinout['inout_id'];
                        $array['outnum'] = $vinout['outnum'];
                        $array['num'] = $vinout['num'];
                        $array['etime'] = time();
                        $array['etype'] = 2;
                        $array['enum'] = $vinout['enum'];
                        $array['e_id'] = $warehouseOutStock_id;
                        $array['e_no'] = $vinout['e_no'] + 1;
                        if($warehouseInoutModel->save($array) === false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'减批次数据内部错误1 请联系技术人员!!! 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                        }
                    }
                }
                //盘亏数量减掉【本社区其他门店】入库批次的批次数量 先进先出。
                if($g_num_int > 0){
                    $WarehouseInoutData = $warehouseInoutModel->where('goods_id = ' .$val['goods_id'] .' and num>0 and shequ_id = '.$shequ_id.' and store_id != 0 ')->order('ctime asc')->select();
                    foreach($WarehouseInoutData as $kinout=>$vinout){
                        if($g_num_int <= 0){
                            break;
                        }
                        if($g_num_int >= $vinout['num']){
                            $g_num_int -= $vinout['num'];
                            $vinout['enum'] += $vinout['num'];
                            $vinout['num'] = 0;
                            $vinout['outnum'] = $vinout['innum'];
                        }else{
                            $vinout['num'] -= $g_num_int;
                            $vinout['outnum'] += $g_num_int;
                            $vinout['enum'] += $g_num_int;
                            $g_num_int = 0;
                        }
                        $array = array();
                        $array['inout_id'] = $vinout['inout_id'];
                        $array['outnum'] = $vinout['outnum'];
                        $array['num'] = $vinout['num'];
                        $array['etime'] = time();
                        $array['etype'] = 2;
                        $array['enum'] = $vinout['enum'];
                        $array['e_id'] = $warehouseOutStock_id;
                        $array['e_no'] = $vinout['e_no'] + 1;
                        if($warehouseInoutModel->save($array) === false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'减批次数据内部错误2 请联系技术人员!!! 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
                        }
                    }
                }
            }
        }
        if($this->where(array('i_id'=>$id))->save(array('i_status'=>1)) == false){
            $this->rollback();
            return array('status'=>0,'msg'=>'修改盘点单状态失败!!!');
        }
        $this->commit();
        return array('status'=>200,'msg'=>'成功');
    }

    /**
     * 盘点删除
     * @param  id 盘点单 id
     */
    public function is_delete($id =0 ){
        $this->startTrans();
        $warehouseInventoryDetailModel = M('WarehouseInventoryDetail');
        if($this->where('i_id='.$id)->delete() == false){
            $this->rollback();
            return array('status'=>0,'msg'=>'盘点删除 内部错误 请联系技术人员!!!');
        }
        if($warehouseInventoryDetailModel->where('i_id='.$id)->delete() == false){
            $this->rollback();
            return array('status'=>0,'msg'=>'盘点删除子表 内部错误 请联系技术人员!!!');
        }
        $this->commit();
        return array('status'=>200,'msg'=>'删除成功');
    }
}