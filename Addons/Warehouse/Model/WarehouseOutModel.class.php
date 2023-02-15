<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseOutModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交出库验货单
    public function saveWarehouseOut($w_out_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $w_out_id ) {  //编辑计划
            $ok = M("WarehouseOut")->where('w_out_id='.$w_out_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交出库验货单失败');
                return false;
            }
            $ok = M("WarehouseOutDetail")->where('w_out_id='.$w_out_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'出库验货单商品删除失败');
                return false;
            }
        } else { //新增入库
            $w_out_id = $this->add($data);
            if(!$w_out_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存出库验货单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['w_out_id'] = $w_out_id;
        }
        $ok = M("WarehouseOutDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存出库验货单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $w_out_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $w_out_id;
    }

    /**
     * 仓库入库验收审核
     * @param w_out_id 验收单id
     * @param pass  1 审核 2 全部退货
     * @param  admin_id 管理审核管理员
     * @param  warehouse_id 仓库id
     */
    public function check($w_out_id, $pass, $admin_id, $warehouse_id){
        //表变量
        $warehouseOutDetailModel = M('WarehouseOutDetail');
        $warehouseRequestModel = M('WarehouseRequest');
        $warehouseRequestDetailModel = M('WarehouseRequestDetail');
        $storeRequestModel = M('StoreRequest');
        $storeRequestDetailModel = M('StoreRequestDetail');
        $warehouseStockModel = M('WarehouseStock');
        $warehouseOutStockModel = M('WarehouseOutStock');
        $warehouseOutStockDetailModel = M('WarehouseOutStockDetail');
        $warehouseInModel = M('WarehouseIn');
        $warehouseInDetailModel = M('WarehouseInDetail');
        $storeInModel = M('StoreIn');
        $storeInDetailModel = M('StoreInDetail');
        $this->startTrans();
        $is = $this->where(array('w_out_id'=>$w_out_id,'w_out_status'=>0))->lock(true)->find();
        if(empty($is)){
            $this->rollback();
            return array('status'=>0,'msg'=>'没有出库验收单 或已经处理 不能再次审核');
        }
        //获取区域id
        if(($shequ_id = M('Warehouse')->where(array('w_id'=>$warehouse_id))->getField('shequ_id')) == false){
            $this->rollback();
            return array('status'=>0,'msg'=>'获取区域错误');
        }
        $warehouseOut_array = $warehouseOutDetailModel->alias('wod')
            ->field('wod.*,ifnull(ws.num,0) as stock_num')
            ->join('left join hii_warehouse_stock ws on wod.goods_id=ws.goods_id and wod.value_id=ws.value_id and ws.w_id = '.$is['warehouse_id2'])
            ->where(array('wod.w_out_id'=>$w_out_id))
            ->select();
        if(empty($warehouseOut_array)){
            $this->rollback();
            return array('status'=>0,'msg'=>'没有出库验收商品');
        }
        //入库类型
        $pcount1 = 0; //出库商品种类
        $nums1 = 0; //出库商品数量
        $nums4 = 0; //全部出库数量 用于和出库商品数量比较 确定是否全部出库

        $warehouseOutStock = array(); //出库商品
        foreach($warehouseOut_array as $key=>$val){
            if( ($val['g_num'] - $val['in_num'] - $val['out_num']) != 0){
                $this->rollback();
                return array('status'=>0,'msg'=>'出库数量+拒绝数量 不等于申请数量');
            }
            //判断库存
            if($val['stock_num'] < $val['in_num'] && $pass == 1){
                $this->rollback();
                return array('status'=>0,'msg'=>'出库商品库存不够 商品'.$val['goods_id'].' 属性'.$val['value_id']);
            }
            //出库部分
            $temp = array();
            $temp['goods_id'] = $val['goods_id'];
            $temp['g_num'] = $val['in_num'];
            $temp['g_price'] = $val['g_price'];
            $temp['remark'] = $val['remark'];
            $temp['w_out_d_id'] = $val['w_out_d_id'];
            $temp['value_id'] = $val['value_id'];
            if($val['in_num'] > 0 && $pass == 1){
                $warehouseOutStock[] = $temp;
                $pcount1 += 1;
                $nums1 += $val['in_num'];
            }
            //全部拒绝部分
            if($pass == 2){
                //仓库调拨拒绝
                if($is['w_out_type'] == 0){
                    if($warehouseRequestDetailModel->where(array('w_r_d_id'=>$val['w_r_d_id']))->save(array('is_pass'=>1,'pass_num'=>0)) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'修改仓库调拨状态失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                    }
                }elseif($is['w_out_type'] == 1){
                    //门店申请
                    if($storeRequestDetailModel->where(array('s_r_d_id'=>$val['s_r_d_id']))->save(array('is_pass'=>1,'pass_num'=>0)) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'修改门店申请状态失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                    }
                }
            }elseif($pass == 1){
                //审核部分  有出库 有拒绝
                //仓库调拨拒绝
                if($is['w_out_type'] == 0){
                    //出库is_pass 2 拒绝 is_pass1
                    if($val['in_num']>0 ){
                        if($warehouseRequestDetailModel->where(array('w_r_d_id'=>$val['w_r_d_id']))->save(array('is_pass'=>2,'pass_num'=>$val['in_num'])) == false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改仓库调拨状态失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                        }
                    }elseif($val['out_num'] >0 && $val['out_num'] == $val['g_num']){
                        if($warehouseRequestDetailModel->where(array('w_r_d_id'=>$val['w_r_d_id']))->save(array('is_pass'=>1,'pass_num'=>0)) == false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改仓库调拨状态失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                        }
                    }
                }elseif($is['w_out_type'] == 1){
                    //门店申请
                    //出库is_pass 2 拒绝 is_pass1
                    if($val['in_num']>0 ){
                        if($storeRequestDetailModel->where(array('s_r_d_id'=>$val['s_r_d_id']))->save(array('is_pass'=>2,'pass_num'=>$val['in_num'])) == false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改门店申请状态失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                        }
                    }elseif($val['out_num'] >0 && $val['out_num'] == $val['g_num']){
                        if($storeRequestDetailModel->where(array('s_r_d_id'=>$val['s_r_d_id']))->save(array('is_pass'=>1,'pass_num'=>0)) == false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改门店申请状态失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                        }
                    }
                }
            }
            $nums4 += $val['g_num'];
        }
        /**********出库部分*****************/
        $MessageWarn_is = 0; //用于判断是否有仓库入库验货单 或 门店入库验货单 发送提示消息
        if(!empty($warehouseOutStock) && $pass == 1){
            //新增出库单
            $data = array();
            $data['w_out_s_sn'] =  get_new_order_no('CK','hii_warehouse_out_stock','w_out_s_sn');;
            $data['w_out_s_status'] = 1;
            if($is['w_out_type'] == 0){
                $data['w_out_s_type'] = 0;
            }elseif($is['w_out_type'] == 1){
                $data['w_out_s_type'] = 1;
            }elseif($is['w_out_type'] == 3){
                $data['w_out_s_type'] = 5;
            }
            $data['w_out_id'] = $w_out_id;
            $data['ctime'] = time();
            $data['admin_id'] = $admin_id;
            $data['etime'] = time();
            $data['eadmin_id'] = $admin_id;
            $data['ptime'] = time();
            $data['padmin_id'] = $admin_id;
            $data['store_id'] = $is['store_id'];
            $data['warehouse_id1'] = $is['warehouse_id1'];
            $data['warehouse_id2'] = $is['warehouse_id2'];
            $data['remark'] = $is['remark'];
            $data['g_type'] = $pcount1;
            $data['g_nums'] = $nums1;
            if(($warehouseOutStock_id = $warehouseOutStockModel->add($data)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'新增仓库出库单失败');
            }
            //新增仓库入库验收单
            if($is['w_out_type'] == 0){
                $new_no = get_new_order_no('RY','hii_warehouse_in','w_in_sn');
                $dataWI = array();
                $dataWI['w_in_sn'] = $new_no;
                $dataWI['w_in_status'] = 0;
                $dataWI['w_in_type'] = 2;
                $dataWI['w_out_s_id'] = $warehouseOutStock_id;
                $dataWI['ctime'] = time();
                $dataWI['admin_id'] = $admin_id;
                $dataWI['warehouse_id'] = $is['warehouse_id1'];
                $dataWI['warehouse_id2'] = $is['warehouse_id2'];
                $dataWI['remark'] = $is['remark'];
                $dataWI['g_type'] = $pcount1;
                $dataWI['g_nums'] = $nums1;
                if(($warehouseIn_id = $warehouseInModel->add($dataWI)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'新增仓库入库验收单失败');
                }
                $MessageWarn_is = 1;
            }
            //新增门店入库验收单
            if($is['w_out_type'] = 1 || $is['w_out_type'] == 3){
                $new_no = get_new_order_no('SI','hii_store_in','s_in_sn');
                $dataWI = array();
                $dataWI['s_in_sn'] = $new_no;
                $dataWI['s_in_status'] = 0;
                $dataWI['s_in_type'] = 0;
                $dataWI['w_out_s_id'] = $warehouseOutStock_id;
                $dataWI['ctime'] = time();
                $dataWI['admin_id'] = $admin_id;
                $dataWI['warehouse_id'] = $is['warehouse_id2'];
                $dataWI['store_id2'] = $is['store_id'];
                $dataWI['remark'] = $is['remark'];
                $dataWI['g_type'] = $pcount1;
                $dataWI['g_nums'] = $nums1;
                if(($storeIn_id = $storeInModel->add($dataWI)) == false ){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'新增门店入库验收单失败');
                }
                $MessageWarn_is = 2;
            }
            foreach($warehouseOutStock as $key=>$val){
                //减库存
                if($warehouseStockModel->where(array('goods_id'=>$val['goods_id'],'value_id'=>$val['value_id'],'w_id'=>$is['warehouse_id2']))->setDec('num',$val['g_num']) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'修改库存失败 商品'.$val['goods_id'].' 属性'.$val['value_id']);
                }
                //新增出库单子表
                $array = array();
                $array['w_out_s_id'] = $warehouseOutStock_id;
                $array['goods_id'] = $val['goods_id'];
                $array['g_num'] = $val['g_num'];
                $array['g_price'] = $val['g_price'];
                $array['w_out_d_id'] = $val['w_out_d_id'];
                $array['remark'] = $val['remark'];
                $array['value_id'] = $val['value_id'];
                if(($warehouseOutStockDetail_id = $warehouseOutStockDetailModel->add($array)) == false){
                    $this->rollback();
                    return array('status'=>0,'msg'=>'新增出库单子表失败商品'.$val['goods_id'].' 属性'.$val['value_id']);
                }

                //新增仓库入库验货单子表
                if($is['w_out_type'] == 0){
                    $array = array();
                    $array['w_in_id'] = $warehouseIn_id;
                    $array['goods_id'] = $val['goods_id'];
                    $array['g_num'] = $val['g_num'];
                    $array['g_price'] = $val['g_price'];
                    $array['remark'] = $val['remark'];
                    $array['value_id'] = $val['value_id'];
                    if($warehouseInDetailModel->add($array) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'新增仓库入库验收单子表失败商品'.$val['goods_id'].' 属性'.$val['value_id']);
                    }
                }
                //新增门店入库验收单
                if($is['w_out_type'] = 1 || $is['w_out_type'] == 3){
                    $array = array();
                    $array['s_in_id'] = $storeIn_id;
                    $array['goods_id'] = $val['goods_id'];
                    $array['g_num'] = $val['g_num'];
                    $array['g_price'] = $val['g_price'];
                    $array['w_out_s_d_id'] = $warehouseOutStockDetail_id;
                    $array['remark'] = $val['remark'];
                    $array['value_id'] = $val['value_id'];
                    if($storeInDetailModel->add($array) == false){
                        $this->rollback();
                        return array('status'=>0,'msg'=>'新增门店入库验收单子表失败商品'.$val['goods_id'].' 属性'.$val['value_id']);
                    }
                }
            }
        }
        /**********修改状态**************/
        //修改仓库调拨状态
        if($is['w_out_type'] == 0){
            if(!$is['w_r_id']){
                $request = array_unique(explode(',',$is['w_r_id']));
                foreach($request as $key=>$val){
                        $array = $warehouseRequestDetailModel->field('sum(g_num)g_num,sum(pass_num)pass_num,(is_pass * 1)is_pass')->where(array('w_r_id'=>$val))->find();
                        if($array['g_num'] == $array['pass_name'] && $array['g_num'] != 0){
                            if($warehouseRequestModel->where(array('w_r_id'=>$val,'w_r_status'=>array('neq',5)))->save(array('w_r_status'=>3)) === false){
                                $this->rollback();
                                return array('status'=>0,'msg'=>'修改仓库调拨主表状态失败3');
                            }
                        }elseif($array['g_num'] != $array['pass_name'] && $array['g_num'] != 0){
                            if($warehouseRequestModel->where(array('w_r_id'=>$val,'w_r_status'=>array('neq',5)))->save(array('w_r_status'=>2)) === false){
                                $this->rollback();
                                return array('status'=>0,'msg'=>'修改仓库调拨主表状态失败2');
                            }
                        }elseif($array['is_pass'] == 1){
                            if($warehouseRequestModel->where(array('w_r_id'=>$val,'w_r_status'=>array('neq',5)))->save(array('w_r_status'=>4)) === false){
                                $this->rollback();
                                return array('status'=>0,'msg'=>'修改仓库调拨主表状态失败4');
                            }
                        }
                }
            }
        }
        //修改门店申请状态
        if($is['w_out_type'] == 1){
            if(!$is['s_r_id']){
                $request = array_unique(explode(',',$is['s_r_id']));
                foreach($request as $key=>$val){
                    $array = $storeRequestDetailModel->field('sum(g_num)g_num,sum(pass_num)pass_num,(is_pass * 1)is_pass')->where(array('s_r_id'=>$val))->find();
                    if($array['g_num'] == $array['pass_name'] && $array['g_num'] != 0){
                        if($storeRequestModel->where(array('s_r_id'=>$val,'s_r_status'=>array('neq',5)))->save(array('s_r_status'=>3)) === false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改门店申请主表状态失败3');
                        }
                    }elseif($array['g_num'] != $array['pass_name'] && $array['g_num'] != 0){
                        if($storeRequestModel->where(array('s_r_id'=>$val,'s_r_status'=>array('neq',5)))->save(array('s_r_status'=>2)) === false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改门店申请主表状态失败2');
                        }
                    }elseif($array['is_pass'] == 1){
                        if($storeRequestModel->where(array('s_r_id'=>$val,'s_r_status'=>array('neq',5)))->save(array('s_r_status'=>4)) === false){
                            $this->rollback();
                            return array('status'=>0,'msg'=>'修改门店申请主表状态失败4');
                        }
                    }
                }
            }
        }
        //修改仓库出库验货状态   如果is_pass是2 状态为2   如果is_pass是1 并且出库数量等于全部出库数量 状态为1 否则为3
        if($pass == 2){
            if($this->where(array('w_out_id'=>$w_out_id))->save(array('w_out_status'=>2)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'修改出库验货单状态失败2');
            }
        }elseif($pass == 1 && $nums4 == $nums1){
            if($this->where(array('w_out_id'=>$w_out_id))->save(array('w_out_status'=>1)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'修改出库验货单状态失败1');
            }
        }elseif($pass == 1 && $nums1 != $nums4){
            if($this->where(array('w_out_id'=>$w_out_id))->save(array('w_out_status'=>3)) == false){
                $this->rollback();
                return array('status'=>0,'msg'=>'修改出库验货单状态失败3');
            }
        }
        //发送提示消息
        if($MessageWarn_is == 1){
            $ModelMsg = D('Erp/MessageWarn');
             $ModelMsg->pushMessageWarn(UID  ,$dataWI['warehouse_id'] , 0 ,0  , $dataWI ,6);
        }elseif($MessageWarn_is == 2){
            $ModelMsg = D('Erp/MessageWarn');
            $ModelMsg->pushMessageWarn(UID  ,0 , $dataWI['store_id2'] ,0  , $dataWI ,4);
        }
        $this->commit();
        return array('status'=>200,'msg'=>'成功');
    }
}