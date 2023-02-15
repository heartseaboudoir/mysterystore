<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseInModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交采购申请
    public function saveWarehouseIn($w_in_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $w_in_id ) {  //编辑计划
            $ok = M("WarehouseIn")->where('w_in_id='.$w_in_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交入库验收单失败');
                return false;
            }
            $ok = M("WarehouseInDetail")->where('w_in_id='.$w_in_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'入库验收单商品删除失败');
                return false;
            }
        } else { //新增入库
            $w_in_id = $this->add($data);
            if(!$w_in_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存入库验收单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['w_in_id'] = $w_in_id;
        }
        $ok = M("WarehouseInDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存入库验收单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $w_in_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $w_in_id;
    }

    /**
     * 入库验收审核
     * @param $w_in_id 入库id
     * @param  $admin_id 用户管理员id
     * @paran $pass  审核管理员id
     * @param  warehouse_id 仓库id
     */
    public function check($w_in_id,$admin_id, $pass, $warehouse_id){
        //表变量
        $warehouseInDetail = M('WarehouseInDetail');
        $warehouseInStockMdoel = M('WarehouseInStock');
        $warehouseInStockDetailModel = M('WarehouseInStockDetail');
        $purchaseOutModel = M('PurchaseOut');
        $purchaseOutDetailModel = M('PurchaseOutDetail');
        $warehouseOtherOutModel = M('warehouseOtherOut');
        $warehouseOtherOutDetailModel = M('warehouseOtherOutDetail');
        $storeOtherOutModel = M('StoreOtherOut');
        $storeOtherOutDetailModel = M('StoreOtherOutDetail');
        $warehouseInoutModel = M('WarehouseInout');
        $warehouseStockModel = M('WarehouseStock');
        $this->startTrans();
        $is = $this->where(array('w_in_id'=>$w_in_id,'w_in_status'=>0))->lock(true)->find();
        if(empty($is)){
            $this->rollback();
            return array('status'=>0,'msg'=>'没有入库验收单 或已经处理 不能再次审核');
        }
        //获取区域id
        if(($shequ_id = M('Warehouse')->where(array('w_id'=>$warehouse_id))->getField('shequ_id')) == false){
            $this->rollback();
            return array('status'=>0,'msg'=>'获取区域错误');
        }
        $warehouseIn_array = $warehouseInDetail->field('w_in_d_id,w_in_id,goods_id,g_num,in_num,out_num,g_price,remark,endtime,p_d_id,value_id')->where(array('w_in_id'=>$w_in_id))->select();
        if(empty($warehouseIn_array)){
            $this->rollback();
            return array('status'=>0,'msg'=>'没有入库验收商品');
        }
        //入库类型
        $pcount1 = 0; //入库商品种类
        $pcount2 = 0; //退货商品种类
        $pcount3 = 0; //全部退货商品种类
        $nums1 = 0; //入库商品数量
        $nums2 = 0; //退货商品数量
        $nums3 = 0; //全部退回商品数量

        $warehouseInStock = array();//入库部分数组
        $purchaseOut = array();//退货部分数组
        foreach($warehouseIn_array as $key=>$val){
            if($val['g_price'] == 0 && $pass == '1' && $is['w_in_type'] == 0){
                $this->rollback();
                return array('status'=>0,'msg'=>'没有入库价');
            }
            if($is['w_in_type'] == 0) {
                if($val['endtime'] == 0 && $pass == '1'){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'采购商品没有过期时间');
                }
            }
            if ($val['g_num'] != ($val['in_num'] + $val['out_num'])) {
                $this->rollback();
                return array('status'=>0,'msg'=>'验收数量+退货数量 不等于 申请数量');
            }
            $tmp = array();
            $tmp1 = array();
            $tmp2 = array();
            //入库部分
            $tmp['goods_id'] = $val['goods_id'];
            $tmp['g_num'] = $val['in_num'];
            $tmp['g_price'] = $val['g_price'];
            $tmp['remark'] = $val['remark'];
            $tmp['endtime'] = $val['endtime'];
            $tmp['w_in_d_id'] = $val['w_in_d_id'];
            $tmp['value_id'] = $val['value_id'];
            if($val['in_num'] > 0) {
                $warehouseInStock[] = $tmp;
                $pcount1++;
            }
            //退货部分
            $tmp1['goods_id'] = $val['goods_id'];
            $tmp1['g_num'] = $val['g_num'];
            $tmp1['out_num'] = $val['out_num'];
            $tmp1['g_price'] = $val['g_price'];
            $tmp1['remark'] = $val['remark'];
            $tmp1['w_in_d_id'] = $val['w_in_d_id'];
            $tmp1['value_id'] = $val['value_id'];
            if($val['out_num'] > 0 || $pass == 2) {
                $purchaseOut[] = $tmp1;
                $pcount2++;
            }
            $pcount3 ++;

            $nums1 += $val['in_num'];
            $nums2 += $val['out_num'];
            $nums3 += $val['g_num'];
        }
        /*********入库部分**********************/
        if(!empty($warehouseInStock) && $pass == 1){
            $new_no = get_new_order_no('RK','hii_warehouse_in_stock','w_in_s_sn');
            $dataWI = array();
            $dataWI['w_in_s_sn'] = $new_no;
            $dataWI['w_in_s_status'] = 1;
            $dataWI['w_in_s_type'] = $is['w_in_type'];
            $dataWI['w_in_id'] = $is['w_in_id'];
            $dataWI['i_id'] = 0;
            $dataWI['ctime'] = time();
            $dataWI['admin_id'] = $admin_id;
            $dataWI['etime'] = time();
            $dataWI['eadmin_id'] = $admin_id;
            $dataWI['ptime'] = time();
            $dataWI['padmin_id'] = $admin_id;
            $dataWI['supply_id'] = $is['supply_id'];
            $dataWI['warehouse_id'] = $warehouse_id;
            $dataWI['remark'] = $is['remark'];
            $dataWI['g_type'] = $pcount1;
            $dataWI['g_nums'] = $nums1;
            if( ($warehouseInStock_id = $warehouseInStockMdoel->add($dataWI)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'新增入库单主表失败');
            }
            foreach($warehouseInStock as $key=>$val){
                /*********入库单字表数据***********/
                $dataWI_detail = array();
                $dataWI_detail['w_in_s_id'] = $warehouseInStock_id;
                $dataWI_detail['goods_id'] = $val['goods_id'];
                $dataWI_detail['g_num'] = $val['g_num'];
                $dataWI_detail['g_price'] = $val['g_price'];
                $dataWI_detail['endtime'] = $val['endtime'];
                $dataWI_detail['w_in_d_id'] = $val['w_in_d_id'];
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
                        return array('status'=>0,'msg'=>'加库存更新失败 商品id'.$val['goods_id'].'属性id'.$val['value_id']);
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
                /******采购单生成入库批次******/
                if($is['w_in_type'] == 0){
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
        }
        /******************退货部分*************************/
        if(!empty($purchaseOut)){
            /*****采购 生成采购退货单*****/
            if($is['w_in_type'] == 0){
                $new_no1 = get_new_order_no('CT', 'hii_purchase_out', 'p_o_sn');
                $data = array();
                $data['p_o_sn'] = $new_no1;
                $data['p_o_status'] = 0;
                $data['p_o_type'] = 0;
                $data['p_id'] = $is['p_id'];
                $data['w_in_id'] = $w_in_id;
                $data['ctime'] = time();
                $data['admin_id'] = $admin_id;
                $data['supply_id'] = $is['supply_id'];
                $data['warehouse_id'] = $is['warehouse_id'];
                $data['remark'] = $is['remark'];
                if($pass == 1){
                    $data['g_type'] = $pcount2;
                    $data['g_nums'] = $nums2;
                }elseif($pass == 2){
                    $data['g_type'] = $pcount3;
                    $data['g_nums'] = $nums3;
                }
                if(($purchaseOut_id = $purchaseOutModel->add($data)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'生成采购退货单失败');
                }
                $purchaseOutDetail_array = array();
                foreach($purchaseOut as $key=>$val){
                    $array = array();
                    $array['p_o_id'] = $purchaseOut_id;
                    $array['goods_id'] = $val['goods_id'];
                    if($pass == 1){
                        $array['g_num'] = $val['out_num'];
                    }elseif($pass == 2){
                        $array['g_num'] = $val['g_num'];
                    }
                    $array['g_price'] = $val['g_price'];
                    $array['w_in_d_id'] = $val['w_in_d_id'];
                    $array['remark'] = $val['remark'];
                    $array['value_id'] = $val['value_id'];
                    $purchaseOutDetail_array[] = $array;
                }
                if($purchaseOutDetailModel->addAll($purchaseOutDetail_array) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'生成采购退货单子表失败');
                }
            }elseif($is['w_in_type'] == 2){
                /**********调拨退货并新增仓库报损单*************/
                $new_no1 = get_new_order_no('CB', 'hii_warehouse_other_out', 'w_o_out_sn');
                $data = array();
                $data['w_o_out_sn'] = $new_no1;
                $data['w_o_out_status'] = 0;
                $data['w_o_out_type'] = 0;
                $data['w_in_id'] = $w_in_id;
                $data['ctime'] = time();
                $data['admin_id'] = $admin_id;
                $data['warehouse_id'] = $is['warehouse_id'];
                $data['warehouse_id2'] = $is['warehouse_id2'];
                $data['remark'] = $is['remark'];
                if($pass == 1){
                    $data['g_type'] = $pcount2;
                    $data['g_nums'] = $nums2;
                }elseif($pass == 2){
                    $data['g_type'] = $pcount3;
                    $data['g_nums'] = $nums3;
                }
                if(($warehouseOtherOut_id = $warehouseOtherOutModel->add($data)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'生成调拨退货单失败');
                }
                $warehouseOtherOutDetail_array = array();
                foreach($purchaseOut as $key=>$val){
                    $array = array();
                    $array['w_o_out_id'] = $warehouseOtherOut_id;
                    $array['goods_id'] = $val['goods_id'];
                    if($pass == 1){
                        $array['g_num'] = $val['out_num'];
                    }elseif($pass == 2){
                        $array['g_num'] = $val['g_num'];
                    }
                    $array['g_price'] = $val['g_price'];
                    $array['w_in_d_id'] = $val['w_in_d_id'];
                    $array['remark'] = $val['remark'];
                    $array['value_id'] = $val['value_id'];
                    $warehouseOtherOutDetail_array[] = $array;
                }
                if($warehouseOtherOutDetailModel->addAll($warehouseOtherOutDetail_array) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'生成调拨退货单子表失败');
                }
            }elseif($is['w_in_type'] == 4){
               /*  $this->rollback();
                return array('status'=>0,'msg'=>'暂时不支持门店返仓拒绝');//暂时不支持门店返仓拒绝 */
                /******退货并新增门店被退货单*********/
                $new_no1 = get_new_order_no('MB', 'hii_store_other_out', 's_o_out_sn');
                $data = array();
                $data['s_o_out_sn'] = $new_no1;
                $data['s_o_out_status'] = 0;
                $data['s_o_out_type'] = 5;
                $data['s_in_id'] = 0;
                $data['s_id'] = 0;
                $data['ctime'] = time();
                $data['admin_id'] = $admin_id;
                $data['warehouse_id'] = $is['warehouse_id'];
                $data['store_id1'] = $is['store_id'];
                $data['store_id2'] = 0;
                $data['remark'] = $is['remark'];
                if($pass == 1){
                    $data['g_type'] = $pcount2;
                    $data['g_nums'] = $nums2;
                }elseif($pass == 2){
                    $data['g_type'] = $pcount3;
                    $data['g_nums'] = $nums3;
                }
                if(($storeOtherOut_id = $storeOtherOutModel->add($data)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'生成门店退货单失败');
                }
                $storeOtherOutDetail_array = array();
                foreach($purchaseOut as $key=>$val){
                    $array = array();
                    $array['s_o_out_id'] = $storeOtherOut_id;
                    $array['goods_id'] = $val['goods_id'];
                    if($pass == 1){
                        $array['g_num'] = $val['out_num'];
                    }elseif($pass == 2){
                        $array['g_num'] = $val['g_num'];
                    }
                    $array['g_price'] = $val['g_price'];
                    $array['w_in_d_id'] = $val['w_in_d_id'];
                    $array['remark'] = $val['remark'];
                    $array['value_id'] = $val['value_id'];
                    $storeOtherOutDetail_array[] = $array;
                }
                if($storeOtherOutDetailModel->addAll($storeOtherOutDetail_array) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'生成门店退货单子表失败');
                }
            }
        }

       if(!empty($warehouseInStock) && !empty($purchaseOut) && $pass == 1){
            if($this->where(array('w_in_id'=>$w_in_id))->save(array('w_in_status'=>3)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'入库验收单修改状态失败');
            }
          $ModelMsg = D('Erp/MessageWarn');
           if($is['w_in_type'] == 0){
               $type = 9;
               $store = 0;
               $ware = 0;
           }elseif($is['w_in_type'] == 2){
               $type = 10;
               $store = 0;
               $ware = $is['warehouse_id'];
           }elseif($is['w_in_type'] == 4){
               $type = 11;
               $store = $is['store_id'];
               $ware = 0;
           }
            $ModelMsg->pushMessageWarn($admin_id  ,$ware , $store ,$shequ_id  , $data ,$type);
        }elseif(!empty($warehouseInStock) && empty($purchaseOut) && $pass == 1){

           //如果有出库有入库
            if($this->where(array('w_in_id'=>$w_in_id))->save(array('w_in_status'=>1)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'入库验收单修改状态失败');
            }
        }elseif(!empty($purchaseOut) && $pass == 2){
            if($this->where(array('w_in_id'=>$w_in_id))->save(array('w_in_status'=>2)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'入库验收单修改状态失败');
            }
           $ModelMsg = D('Erp/MessageWarn');
           if($is['w_in_type'] == 0){
               $type = 9;
               $store = 0;
               $ware = 0;
           }elseif($is['w_in_type'] == 2){
               $type = 10;
               $store = 0;
               $ware = $is['warehouse_id'];
           }elseif($is['w_in_type'] == 4){
               $type = 11;
               $store = $is['store_id'];
               $ware = 0;
           }
           $ModelMsg->pushMessageWarn($admin_id  ,$ware , $store ,$shequ_id  , $data ,$type);
        }
        $this->commit();
        return array('status'=>200,'msg'=>'成功');
    }
}