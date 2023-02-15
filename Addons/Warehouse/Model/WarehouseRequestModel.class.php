<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-10-27
 * Time: 10:27
 */

namespace Addons\Warehouse\Model;

use Think\Model;
use User\Api\UserApi;

/*************************
 * 调拨申请
 ******************************/
class WarehouseRequestModel extends Model
{
    /*************************************
     * 提交调拨申请
     * @param $admin_id 操作人ID
     * @param $warehouse_id1 申请仓库ID
     * @param  $warehouse_id2 发货仓库ID
     * @param $remark 备注
     * 注意：提交前检测发货仓库库存是否充足，提交前需删除库存不充足部分才能提交
     ***************************************/
    public function saveWarehouseRequest($admin_id, $warehouse_id1, $warehouse_id2, $remark)
    {
        $this->startTrans();
        $temp_type = 3;
        $RequestTempModel = M("RequestTemp");
        $WarehouseRequestModel = M("WarehouseRequest");
        $WarehouseRequestDetailModel = M("WarehouseRequestDetail");

        $sql = "select RT.goods_id,RT.g_num,ifnull(WS.num,0) as stock_num,RT.remark,RT.value_id ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_warehouse_stock WS on WS.value_id=RT.value_id and WS.w_id={$warehouse_id2} ";
        $sql .= "where RT.admin_id={$admin_id} and RT.warehouse_id={$warehouse_id1} and RT.temp_type={$temp_type} and RT.status=0 order by RT.id desc ";
        $datas = $RequestTempModel->query($sql);
        $g_type = 0;
        $g_nums = 0;
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        foreach ($datas as $key => $val) {
            if(array_key_exists($val['goods_id'],$goods_num_array)){
                $goods_num_array[$val['goods_id']] += $val["g_num"];
            }else{
                $goods_num_array[$val['goods_id']] = $val["g_num"];
            }

            if ($goods_num_array[$val['goods_id']] > $val["stock_num"]) {
                return array("status" => "0", "msg" => "ID为{$val["goods_id"]}的商品发货仓库库存不足，无法提交申请");
            }
            $g_type++;
            $g_nums += $val["g_num"];
        }

        //生成申请主表信息
        $WarehouseRequestEntity = array();
        $WarehouseRequestEntity["w_r_sn"] = get_new_order_no('SQ', 'hii_warehouse_request', 'w_r_sn');//申请单号
        $WarehouseRequestEntity["w_r_type"] = 0;
        $WarehouseRequestEntity["w_r_status"] = 0;
        $WarehouseRequestEntity["ctime"] = time();
        $WarehouseRequestEntity["admin_id"] = $admin_id;
        $WarehouseRequestEntity["warehouse_id1"] = $warehouse_id1;
        $WarehouseRequestEntity["warehouse_id2"] = $warehouse_id2;
        $WarehouseRequestEntity["remark"] = $remark;
        $WarehouseRequestEntity["g_type"] = $g_type;
        $WarehouseRequestEntity["g_nums"] = $g_nums;
        $w_r_id = $WarehouseRequestModel->add($WarehouseRequestEntity);
        $ModelMsg = D('Erp/MessageWarn');
        $msg = $ModelMsg->pushMessageWarn(UID  ,$WarehouseRequestEntity['warehouse_id2'] , 0 ,0  , $WarehouseRequestEntity ,6);
        if ($w_r_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增调拨申请主表信息失败");
        }

        foreach ($datas as $key => $val) {
            $WarehouseRequestDetailEntity = array();
            $WarehouseRequestDetailEntity["w_r_id"] = $w_r_id;
            $WarehouseRequestDetailEntity["goods_id"] = $val["goods_id"];
            $WarehouseRequestDetailEntity["g_num"] = $val["g_num"];
            $WarehouseRequestDetailEntity["is_pass"] = 0;
            $WarehouseRequestDetailEntity["pass_num"] = 0;
            $WarehouseRequestDetailEntity["remark"] = $val["remark"];
            $WarehouseRequestDetailEntity["value_id"] = $val["value_id"];
            $w_r_d_id = $WarehouseRequestDetailModel->add($WarehouseRequestDetailEntity);
            if ($w_r_d_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增调拨申请子表信息失败");
            }
        }

        //删除临时申请表信息
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and warehouse_id={$warehouse_id1} and temp_type={$temp_type} and status=0 ")->order(" id desc ")->delete();

        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请数据失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /**********************
     * 拒绝调拨明细
     * @param $WarehouseRequestEntity hii_warehouse_request的实体
     * @param $w_r_d_id hii_warehouse_request_detail的ID
     */
    public function rejectWarehouseRequestAssgin($WarehouseRequestEntity, $w_r_d_id)
    {
        $this->startTrans();
        $w_r_id = $WarehouseRequestEntity['w_r_id'];
        $g_type = $WarehouseRequestEntity['g_type'];//申请调拨的商品种类
        //hii_warehouse_request的w_r_status包含状态：0新增 1已审核申请【所有申请已处理】 2部分通过申请 3全部拒绝 4作废【暂时未用到】
        $WarehouseRequestDetailModel = M("WarehouseRequestDetail");

        //保存拒绝的明细状态
        $saveData = array();
        $saveData["is_pass"] = 1;
        $ok = $WarehouseRequestDetailModel->where(" w_r_d_id={$w_r_d_id} ")->save($saveData);
        if ($ok === false) {
            $this->rollback();
            $this->err = array('code' => 1, 'msg' => '调拨明细状态更新失败');
            return false;
        }

        $WarehouseRequestDetailList = $WarehouseRequestDetailModel->where(" w_r_id={$w_r_id} ")->order(" w_r_d_id desc ")->select();
        $isPassAmount = 0;//审核状态总和
        $isSomePassed = false;
        if (!is_null($WarehouseRequestDetailList) && !empty($WarehouseRequestDetailList)) {
            for ($i = 0; $i < count($WarehouseRequestDetailList); $i++) {
                $isPassAmount += $WarehouseRequestDetailList[$i]['is_pass'];
                if ($WarehouseRequestDetailList[$i]['is_pass'] == 2) {
                    $isSomePassed = true;
                }
            }
            //echo $isPassAmount;
            $WarehouseRequestModel = M("WarehouseRequest");
            /**********通过isPassAmount的值决定w_r_status的值*****************/
            $WarehouseRequestUpdateStatus = 0;
            if ($isPassAmount == 0) {
                $WarehouseRequestUpdateStatus = 0;//新增
            } elseif ($isPassAmount == ($g_type * 2)) {
                $WarehouseRequestUpdateStatus = 3;//全部拒绝
            } elseif ($isPassAmount >= $g_type) {
                $WarehouseRequestUpdateStatus = 1;//全部已处理
            } else {
                $WarehouseRequestUpdateStatus = 2;//部分通过
            }
            $saveData = array();
            $saveData['w_r_status'] = $WarehouseRequestUpdateStatus;
            $ok = $WarehouseRequestModel->where(" w_r_id={$w_r_id} ")->save($saveData);
            if ($ok === false) {
                $this->rollback();
                $this->err = array('code' => 1, 'msg' => '调拨单状态更新失败');
                return false;
            }
            $this->commit();
            return $w_r_id;
        }
    }


    /**************************
     * 生成出库验货单【hii_warehouse_out,hii_warehouse_out_detail】
     * @param $admin_id 管理员ID
     * @param $w_r_d_id_array 仓库调拨申请子表ID数组
     * @param $remark 备注
     * 注意1：不存在g_price的请求不能提交
     * 注意2：生成成功后修改hii_warehouse_request的状态为1
     */
    public function generateWarehouseRequestOutOrder($warehouse_id, $admin_id, $w_r_d_id_array, $remark)
    {
        $this->startTrans();
        $isUpdateRollback = true;//是否更新hii_warehouse_request
        $WarehouseRequestModel = M("WarehouseRequest");
        $WarehouseRequestDetailModel = M("WarehouseRequestDetail");
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        $WarehouseModel = M("Warehouse");
        $WarehouseInoutViewModel = M("WarehouseInoutView");//用于查询g_price

        $shequ_id = 0;
        $warehouse_datas = $WarehouseModel->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        if (!is_null($warehouse_datas) && !empty($warehouse_datas) && count($warehouse_datas) > 0) {
            $shequ_id = $warehouse_datas[0]["shequ_id"];
        }

        //查询含有g_price的申请单ID
        $in = implode(",", $w_r_d_id_array);
        $sql = "select A1.w_r_d_id ";
        $sql .= "from hii_warehouse_inout_view WIV ";
        $sql .= "inner join hii_warehouse_request_detail A1 on A1.goods_id=WIV.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "WHERE A1.w_r_d_id in ({$in}) ";
        $w_r_d_id_with_gprice_array = $WarehouseInoutViewModel->query($sql);
        if (is_null($w_r_d_id_with_gprice_array) || empty($w_r_d_id_with_gprice_array) || count($w_r_d_id_with_gprice_array) == 0) {
            return array("status" => "0", "msg" => "所有申请没有入库价，无法生成出库验货单");
        }
        //按仓库,发货仓库划分单【一个申请仓库对应一个发货仓库对应多张单】
        $in = "";
        foreach ($w_r_d_id_with_gprice_array as $key => $val) {
            $in .= $val["w_r_d_id"] . ",";
        }
        $in = substr($in, 0, strlen($in) - 1);

        $sql = "select A.warehouse_id1,A.warehouse_id2 ";
        $sql .= "from hii_warehouse_request A ";
        $sql .= "left join hii_warehouse_request_detail A1 on A1.w_r_id=A.w_r_id ";
        $sql .= "where A1.w_r_d_id in ({$in}) group by warehouse_id1,warehouse_id2 ";
        $datas = $WarehouseRequestModel->query($sql);
        $w_r_id_array = array();//调拨申请主表ID数组，用于最后更新调拨申请主表状态

        $UpdateWarehouseRequestEntitys = array();
        $UpdateWarehouseRequestDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $sql = "select A.w_r_id,A1.w_r_d_id,A1.goods_id,A1.g_num,ifnull(WIV.stock_price,0) as g_price,A1.remark,A1.value_id ";
            $sql .= "from hii_warehouse_request_detail A1 ";
            $sql .= "left join hii_warehouse_request A on A1.w_r_id=A.w_r_id ";
            $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id and WIV.shequ_id={$shequ_id} ";
            $sql .= "where A.warehouse_id1={$val["warehouse_id1"]} and A.warehouse_id2={$val["warehouse_id2"]} ";
            $sql .= "and WIV.shequ_id={$shequ_id} and A1.w_r_d_id in ({$in})";
            $RequestDatas = $WarehouseRequestModel->query($sql);
            $w_r_ids = "";
            //整理hii_warehouse_out信息
            $WarehouseOutEntity = array();
            $WarehouseOutEntity["w_out_sn"] = get_new_order_no("CY", "hii_warehouse_out", "w_out_sn");
            $WarehouseOutEntity["w_out_status"] = 0;
            $WarehouseOutEntity["w_out_type"] = 0;
            //$WarehouseOutEntity["w_r_id"] = substr($w_r_ids, 0, strlen($w_r_ids) - 1);
            $WarehouseOutEntity["ctime"] = time();
            $WarehouseOutEntity["admin_id"] = $admin_id;
            $WarehouseOutEntity["warehouse_id1"] = $val["warehouse_id1"];
            $WarehouseOutEntity["warehouse_id2"] = $val["warehouse_id2"];
            $WarehouseOutEntity["remark"] = $remark;
            $WarehouseOutEntity["g_type"] = 0;
            $WarehouseOutEntity["g_nums"] = 0;
            $w_out_id = $WarehouseOutModel->add($WarehouseOutEntity);
            if ($w_out_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "出库验货单主表新增失败");
            }
            //增加明细信息
            $g_type = 0;
            $g_nums = 0;
            $w_r_id_tmp_array = array();
            foreach ($RequestDatas as $k => $v) {
                $UpdateWarehouseRequestDetailEntitys[] = array("w_r_d_id" => $v["w_r_d_id"], "is_pass" => 2);
                if (!in_array($v["w_r_id"], $w_r_id_array)) {
                    array_push($w_r_id_array, $v["w_r_id"]);
                }
                if (!in_array($v["w_r_id"], $w_r_id_tmp_array)) {
                    array_push($w_r_id_tmp_array, $v["w_r_id"]);
                }
                $g_type++;
                $g_nums += $v["g_num"];
                $WarehouseOutDetailEntity = array();
                $WarehouseOutDetailEntity["w_out_id"] = $w_out_id;
                $WarehouseOutDetailEntity["goods_id"] = $v["goods_id"];
                $WarehouseOutDetailEntity["g_num"] = $v["g_num"];
                $WarehouseOutDetailEntity["g_price"] = $v["g_price"];
                $WarehouseOutDetailEntity["w_r_d_id"] = $v["w_r_d_id"];
                $WarehouseOutDetailEntity["remark"] = $v["remark"];
                $WarehouseOutDetailEntity["value_id"] = $v["value_id"];
                $w_out_d_id = $WarehouseOutDetailModel->add($WarehouseOutDetailEntity);
                if ($w_out_d_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "出库验货单子表新增失败");
                }
            }
            //更新主表g_type，g_nums
            $w_r_id_tmp_str = implode(",", $w_r_id_tmp_array);
            $ok = $WarehouseOutModel->where(" w_out_id={$w_out_id} ")->limit(1)->save(array(
                "g_type" => $g_type,
                "g_nums" => $g_nums,
                "w_r_id" => $w_r_id_tmp_str
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新出库验货单主表数量种类失败");
            }
        }
        if ($isUpdateRollback) {
            //更新申请表【hii_warehouse_request】状态:0.新增,1.发货中,2.部分发货,3全部发货,4.全部拒绝,5.已作废
            foreach ($w_r_id_array as $key => $val) {
                $UpdateWarehouseRequestEntitys[] = array("w_r_id" => $val, "w_r_status" => 1);
            }
            $result = $this->saveAll("hii_warehouse_request", $UpdateWarehouseRequestEntitys, "w_r_id", $WarehouseRequestModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新仓库调拨主表信息失败");
            }
            $result = $this->saveAll("hii_warehouse_request_detail", $UpdateWarehouseRequestDetailEntitys, "w_r_d_id", $WarehouseRequestDetailModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新仓库调拨子表信息失败");
            }
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /*************************************
     * 根据出库验货单  更改  仓库调拨申请状态
     * @param $w_out_id 出库验货单id
     * BY:WDM
     ***************************************/
    public function saveWarehouseOutToWarehouseRequest($w_out_id)
    {
        $sql = "
        Select A.w_out_id,A.s_r_id,A.w_out_status,B.w_out_d_id,B.w_r_d_id,B.g_num,B.in_num,B.out_num
        From hii_warehouse_out A left join hii_warehouse_out_detail B on A.w_out_id=B.w_out_id
        Where A.w_out_id =  $w_out_id and w_out_type = 0
        ";
        $Model = M('WarehouseOut');
        $list = $Model->query($sql);
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $k1 => $v1) {
                if ($list[$k1]['g_num'] == $list[$k1]['in_num']) {
                    //全部有货
                    $sql = "Update hii_warehouse_request_detail set is_pass = 2,pass_num = " . $list[$k1]['in_num'] . "  where w_r_d_id = " . $list[$k1]['w_r_d_id'];
                } else {
                    if ($list[$k1]['g_num'] == $list[$k1]['out_num']) {
                        //全部拒绝
                        $sql = "Update hii_warehouse_request_detail set is_pass = 1,pass_num = 0  where w_r_d_id = " . $list[$k1]['w_r_d_id'];
                    } else {
                        //部分有货，部分拒绝
                        $sql = "Update hii_warehouse_request_detail set is_pass = 2,pass_num = " . $list[$k1]['in_num'] . "  where w_r_d_id = " . $list[$k1]['w_r_d_id'];
                    }
                }
                $Model1 = M('WarehouseRequestDetail');
                $UpdateS = $Model1->execute($sql);
                if (!$UpdateS) {
                    $error = $Model1->getError();
                    $this->err = array('code' => 1, 'msg' => '修改仓库发货申请出错' . $error);
                }
                $sql = "Select * from hii_warehouse_request_detail where w_r_d_id = " . $list[$k1]['w_r_d_id'];
                $ModelMain = M('WarehouseRequestDetail');
                $listMain = $ModelMain->query($sql);
                $w_r_id = $listMain[0]['w_r_id'];
                $res = $this->checkWarehouseRequest($w_r_id);
                if ($res > 0) {
                    //$this->success('入库批次成功', Cookie('__forward__'));
                } else {
                    $this->err = array('code' => 1, 'msg' => '更改门店发货申请单状态失败：' . $res->err['msg']);
                }
            }
        } else {
            $this->err = array('code' => 1, 'msg' => '没有找到该单据：' . $w_out_id);
        }
        return $w_out_id;
    }

    /*************************************
     * 检测仓库发货申请单状态，更改状态
     * @param $s_r_id 仓库发货申请单id
     * BY:WDM
     ***************************************/
    public function checkWarehouseRequest($w_r_id)
    {
        $pass0 = 0;
        $pass1 = 0;
        $pass2 = 0;
        $sql = "
        Select A.*,B.w_r_d_id,B.goods_id,B.g_num,B.is_pass,B.pass_num
        From hii_warehouse_request A left join hii_warehouse_request_detail B on A.w_r_id=B.w_r_id
        Where A.w_r_id =  $w_r_id
        ";
        $Model = M('WarehouseRequest');
        $list = $Model->query($sql);
        if (is_array($list) && count($list) > 0) {
            $sumcount = count($list);
            $pass2 = $list[0]['g_nums'];
            foreach ($list as $k1 => $v1) {
                $pass0 += $list[$k1]['is_pass'];
                $pass1 += $list[$k1]['pass_num'];
            }
            if ($sumcount == $pass0) {
                //全部未通过
                $sql = "
                    Update hii_warehouse_request set w_r_status = 4
                    Where w_r_id =  $w_r_id
                    ";
                $res = $Model->execute($sql);
            } else {
                if ($sumcount == $pass0 * 2) {
                    //全部出库中
                    $sql = "
                    Update hii_warehouse_request set w_r_status = 1
                    Where w_r_id =  $w_r_id
                    ";
                    $res = $Model->execute($sql);
                    if ($pass2 > $pass1) {
                        //部分发货
                        $sql = "
                        Update hii_warehouse_request set w_r_status = 2
                        Where w_r_id =  $w_r_id
                        ";
                        $res = $Model->execute($sql);
                    } else {
                        //全部发货
                        $sql = "
                        Update hii_warehouse_request set w_r_status = 3
                        Where w_r_id =  $w_r_id
                        ";
                        $res = $Model->execute($sql);
                    }
                } else {
                    //部分出库中
                    $sql = "
                    Update hii_warehouse_request set w_r_status = 1
                    Where w_r_id =  $w_r_id
                    ";
                    $res = $Model->execute($sql);
                }

            }
            if ($res > 0) {
                //$this->success('入库批次成功', Cookie('__forward__'));
            } else {
                $error = $Model->getError();
                $this->err = array('code' => 1, 'msg' => '更改门店发货申请单状态失败：' . $error);
            }
        } else {
            $this->err = array('code' => 1, 'msg' => '没有找到该单据：' . $w_r_id);
        }
        return $w_r_id;
    }

    /********************************
     * 批量更新
     * @param $tableName
     * @param $datas
     * @param $pk
     * @param $model
     * @return array
     */
    private function saveAll($tableName, $datas, $pk, $model)
    {
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "200", "msg" => "没有更新数据");
        }
        $model || $model = $this->name;
        $sql = ''; //Sql
        $lists = array(); //记录集$lists
        //$pk = $this->getPk();//获取主键
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk === $key) {
                    $ids[] = $value;
                } else {
                    $lists[$key] .= sprintf("WHEN %u THEN '%s' ", $data[$pk], $value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s )', $tableName, rtrim($sql, ','), $pk, implode(',', $ids));
        if (M()->execute($sql) !== false) {
            return array("status" => "200", "msg" => "ok");
        } else {
            return array("status" => "0", "msg" => "error");
        }
    }
}