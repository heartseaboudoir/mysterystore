<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-24
 * Time: 15:01
 * 门店发货申请处理
 */

namespace Erp\Model;

use Think\Model;

class StoreRequestHandleModel extends Model
{
    /***************
     * 生成出库验货单
     * @param $admin_id 操作人员ID
     * @param $s_r_d_id_array 门店发货申请单子表ID
     * @param $remark 备注
     * 注意：不存在g_price的请求不能提交
     */
    public function generateWarehouseOutOrder($warehouse_id, $admin_id, $s_r_d_id_array, $remark)
    {
        $this->startTrans();
        $isUpdateRollback = true;//是否更新【hii_store_request】的信息
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        $WarehouseInoutViewModel = M("WarehouseInoutView");//用于查询g_price
        $WarehouseModel = M("Warehouse");

        //获取区域ID
        $shequ_id = 0;
        $warehouse_datas = $WarehouseModel->where(" w_id={$warehouse_id} ")->limit(1)->select();
        if (!is_null($warehouse_datas) && !empty($warehouse_datas) && count($warehouse_datas) > 0) {
            $shequ_id = $warehouse_datas[0]["shequ_id"];
        }


        //查询含有g_price的申请单ID
        $in = implode(",", $s_r_d_id_array);
        $sql = "select A1.s_r_d_id ";
        $sql .= "from hii_warehouse_inout_view WIV ";
        $sql .= "inner join hii_store_request_detail A1 on A1.goods_id=WIV.goods_id ";
        $sql .= "WHERE A1.s_r_d_id in ({$in}) and WIV.shequ_id={$shequ_id}  ";
        $s_r_d_id_with_gprice_array = $WarehouseInoutViewModel->query($sql);
        if (is_null($s_r_d_id_with_gprice_array) || empty($s_r_d_id_with_gprice_array) || count($s_r_d_id_with_gprice_array) == 0) {
            return array("status" => "0", "msg" => "所有申请没有入库价，无法生成出库验货单");
        }
        //按门店,发货仓库划分单【一个申请门店对应一个发货仓库对应多张单】
        $in = "";
        foreach ($s_r_d_id_with_gprice_array as $key => $val) {
            $in .= $val["s_r_d_id"] . ",";
        }
        $in = substr($in, 0, strlen($in) - 1);
        $sql = "select A.store_id,A.warehouse_id ";
        $sql .= "from hii_store_request A ";
        $sql .= "left join hii_store_request_detail A1 on A1.s_r_id=A.s_r_id ";
        $sql .= "where A1.s_r_d_id in ({$in}) ";
        $sql .= "group by store_id,warehouse_id ";
        $datas = $StoreRequestModel->query($sql);
        $s_r_id_array = array();//申请主表ID数组，用于最后更新调拨申请主表状态

        $UpdateStoreRequestEntitys = array();
        $UpdateStoreRequestDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $sql = "select A.s_r_id,A1.s_r_d_id,A1.goods_id,A1.g_num,ifnull(WIV.stock_price,0) as g_price,A1.remark,A1.value_id ";
            $sql .= "from hii_store_request_detail A1 ";
            $sql .= "left join hii_store_request A on A1.s_r_id=A.s_r_id ";
            $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id and WIV.shequ_id={$shequ_id} ";
            $sql .= "where A.store_id={$val["store_id"]} and A.warehouse_id={$val["warehouse_id"]} and A1.s_r_d_id in ({$in})  ";
            $RequestDatas = $StoreRequestModel->query($sql);
            //整理hii_warehouse_out信息
            $WarehouseOutEntity = array();
            $WarehouseOutEntity["w_out_sn"] = get_new_order_no("CY", "hii_warehouse_out", "w_out_sn");
            $WarehouseOutEntity["w_out_status"] = 0;
            $WarehouseOutEntity["w_out_type"] = 1;
            //$WarehouseOutEntity["s_r_id"] = substr($w_r_ids, 0, strlen($w_r_ids) - 1);
            $WarehouseOutEntity["ctime"] = time();
            $WarehouseOutEntity["admin_id"] = $admin_id;
            $WarehouseOutEntity["store_id"] = $val["store_id"];
            $WarehouseOutEntity["warehouse_id2"] = $val["warehouse_id"];
            $WarehouseOutEntity["remark"] = $remark;
            $WarehouseOutEntity["g_type"] = 0;
            $WarehouseOutEntity["g_nums"] = 0;
            $w_out_id = $WarehouseOutModel->add($WarehouseOutEntity);
            if ($w_out_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增出库验货单主表信息失败");
            }
            //增加明细信息
            $g_type = 0;
            $g_nums = 0;
            $s_r_id_tmp_array = array();
            $WarehouseOutDetailEntitys = array();
            foreach ($RequestDatas as $k => $v) {
                $UpdateStoreRequestDetailEntitys[] = array("s_r_d_id" => $v["s_r_d_id"], "is_pass" => 2);
                if (!in_array($v["s_r_id"], $s_r_id_array)) {
                    array_push($s_r_id_array, $v["s_r_id"]);
                }
                if (!in_array($v["s_r_id"], $s_r_id_tmp_array)) {
                    array_push($s_r_id_tmp_array, $v["s_r_id"]);
                }
                $g_type++;
                $g_nums += $v["g_num"];
                $WarehouseOutDetailEntity = array();
                $WarehouseOutDetailEntity["w_out_id"] = $w_out_id;
                $WarehouseOutDetailEntity["goods_id"] = $v["goods_id"];
                $WarehouseOutDetailEntity["g_num"] = $v["g_num"];
                $WarehouseOutDetailEntity["g_price"] = $v["g_price"];
                $WarehouseOutDetailEntity["s_r_d_id"] = $v["s_r_d_id"];
                $WarehouseOutDetailEntity["remark"] = $v["remark"];
                $WarehouseOutDetailEntity["value_id"] = $v["value_id"];
                $WarehouseOutDetailEntitys[] = $WarehouseOutDetailEntity;
            }
            if (count($WarehouseOutDetailEntitys) > 0) {
                $ok = $WarehouseOutDetailModel->addAll($WarehouseOutDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增出库验货单子表信息失败");
                }
            }
            //更新主表g_type，g_nums
            $s_r_id_tmp_str = implode(",", $s_r_id_tmp_array);
            $ok = $WarehouseOutModel->where(" w_out_id={$w_out_id} ")->limit(1)->save(array(
                "g_type" => $g_type,
                "g_nums" => $g_nums,
                "s_r_id" => $s_r_id_tmp_str
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新出库验货单主表数量种类失败");
            }
        }
        if ($isUpdateRollback) {
            //更新申请表【hii_store_request】状态:0.新增,1.出库中,2.部分发货,3.全部发货,4.全部拒绝,5.已作废,6.仓库转采购直接发门店,7.仓库转采购发仓库,8.转仓库采购和转门店采购都有
            foreach ($s_r_id_array as $key => $val) {
                $UpdateStoreRequestEntitys[] = array("s_r_id" => $val, "s_r_status" => 1);
            }
            $result = $this->saveAll("hii_store_request", $UpdateStoreRequestEntitys, "s_r_id", $StoreRequestModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新发货申请主表信息失败");
            }
            $result = $this->saveAll("hii_store_request_detail", $UpdateStoreRequestDetailEntitys, "s_r_d_id", $StoreRequestDetailModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新发货申请子表信息失败");
            }
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /*********************
     * 转门店采购【不合并】
     * @param $admin_id 管理员ID
     * @param $warehouse_id 仓库ID
     * @param $info_json [{"s_r_d_id":""},{"s_r_d_id":""}]
     * @return array
     */
    public function toStorePurchase($admin_id, $warehouse_id, $info_json, $remark)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $PurchaseRequestModel = M("PurchaseRequest");
        $PurchaseRequestDetailModel = M("PurchaseRequestDetail");
        $isUpdateStoreRequestStatus = true;//更新发货申请主表信息

        $store_with_purchase = array();//门店对应的采购信息

        /********************************** 根据门店划分采购信息 start ***************************************************/
        foreach ($info_json as $key => $val) {
            $sql = "select SR.store_id,SRD.s_r_d_id,SRD.goods_id,SRD.g_num,SRD.remark,SRD.value_id ";
            $sql .= "from hii_store_request_detail SRD ";
            $sql .= "left join hii_store_request SR on SR.s_r_id=SRD.s_r_id ";
            $sql .= "where SRD.s_r_d_id={$val["s_r_d_id"]} and SR.warehouse_id={$warehouse_id} and SRD.is_pass=0 limit 1 ";
            $tmp = $StoreRequestDetailModel->query($sql);
            //\Think\Log::record($sql);
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                return array("status" => "0", "msg" => "转门店采购失败");
            }
            $store_id = $tmp[0]["store_id"];
            if (!array_key_exists($store_id, $store_with_purchase)) {
                $store_with_purchase[$store_id][] = $tmp[0];
            } else {
                array_push($store_with_purchase[$store_id], $tmp[0]);
            }
        }
        /********************************** 根据门店划分采购信息 end ***************************************************/

        foreach ($store_with_purchase as $key => $val) {
            $store_id = $key;
            $g_type = 0;
            $g_nums = 0;
            //生成采购申请单主表信息
            $PurchaseRequestEntity = array();
            $PurchaseRequestEntity["p_r_sn"] = get_new_order_no("SQ", "hii_purchase_request", "p_r_sn");
            $PurchaseRequestEntity["p_r_type"] = 1;
            $PurchaseRequestEntity["p_r_status"] = 0;
            $PurchaseRequestEntity["ctime"] = time();
            $PurchaseRequestEntity["admin_id"] = $admin_id;
            $PurchaseRequestEntity["store_id"] = $store_id;
            $PurchaseRequestEntity["remark"] = $remark;
            $p_r_id = $PurchaseRequestModel->add($PurchaseRequestEntity);
            if ($p_r_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店采购主表信息失败");
            }
            $PurchaseRequestDetailEntitys = array();
            $s_r_d_id_str = "";
            foreach ($val as $k => $v) {
                $PurchaseRequestDetailEntity = array();
                $PurchaseRequestDetailEntity["p_r_id"] = $p_r_id;
                $PurchaseRequestDetailEntity["goods_id"] = $v["goods_id"];
                $PurchaseRequestDetailEntity["g_num"] = $v["g_num"];
                $PurchaseRequestDetailEntity["is_pass"] = 0;
                $PurchaseRequestDetailEntity["pass_num"] = 0;
                $PurchaseRequestDetailEntity["remark"] = $v["remark"];
                $PurchaseRequestDetailEntity["value_id"] = $v["value_id"];
                //array_push($PurchaseRequestDetailEntitys, $PurchaseRequestDetailEntity);
                $PurchaseRequestDetailEntitys[] = $PurchaseRequestDetailEntity;
                $s_r_d_id_str .= $v["s_r_d_id"] . ",";
                $g_type++;
                $g_nums += $v["g_num"];
            }
            if (count($PurchaseRequestDetailEntitys) > 0) {
                $ok = $PurchaseRequestDetailModel->addAll($PurchaseRequestDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店采购申请子表信息失败");
                }
            }
            /********************* 更新主表 g_type，g_nums ********************************/
            $ok = $PurchaseRequestModel->where(" p_r_id={$p_r_id} ")->order(" p_r_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店采购申请主表信息失败");
            }
        }

        $s_r_d_id_str = substr($s_r_d_id_str, 0, strlen($s_r_d_id_str) - 1);
        $ok = $StoreRequestDetailModel->where(" s_r_d_id in ({$s_r_d_id_str}) ")->save(array("is_pass" => 3));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新门店发货申请子表信息失败");
        }

        /************************************* 根据发货申请子表状态修改对应的主表状态 start ****************************************************************/
        if ($isUpdateStoreRequestStatus) {
            foreach ($info_json as $key => $val) {
                $items = $StoreRequestDetailModel->field(" s_r_id ")->where(" s_r_d_id={$val["s_r_d_id"]} ")->limit(1)->select();
                $s_r_id = $items[0]["s_r_id"];
                $store_request_details = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->select();
                $hasToWarehousePurchase = false;//采购到仓库 is_pass=4
                $hasToStorePurchase = false;//采购到门店 is_pass=3
                foreach ($store_request_details as $k => $v) {
                    if ($v["is_pass"] == 3) {
                        $hasToStorePurchase = true;
                    } elseif ($v["is_pass"] == 4) {
                        $hasToWarehousePurchase = true;
                    }
                }
                if ($hasToWarehousePurchase && !$hasToStorePurchase) {
                    //只有转仓库采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 7));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if (!$hasToWarehousePurchase && $hasToStorePurchase) {
                    //只有转门店采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 6));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if ($hasToWarehousePurchase && $hasToStorePurchase) {
                    //转仓库采购和转门店采购都有
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 8));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
            }
        }
        /************************************* 根据发货申请子表状态修改对应的主表状态 end ****************************************************************/

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /*********************
     * 转门店采购【合并】
     * @param $admin_id 管理员ID
     * @param $warehouse_id 仓库ID
     * @param $info_json [{"s_r_d_id":""},{"s_r_d_id":""}]
     * @return array
     */
    public function toStorePurchaseMerge($admin_id, $warehouse_id, $info_json, $remark)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $PurchaseRequestModel = M("PurchaseRequest");
        $PurchaseRequestDetailModel = M("PurchaseRequestDetail");
        $isUpdateStoreRequestStatus = true;//更新发货申请主表信息

        $store_with_purchase = array();//门店对应的采购信息

        /********************************** 根据门店划分采购信息 start ***************************************************/
        foreach ($info_json as $key => $val) {
            $sql = "select SR.store_id,SRD.s_r_d_id,SRD.goods_id,SRD.g_num,SRD.remark,SRD.value_id ";
            $sql .= "from hii_store_request_detail SRD ";
            $sql .= "left join hii_store_request SR on SR.s_r_id=SRD.s_r_id ";
            $sql .= "where SRD.s_r_d_id={$val["s_r_d_id"]} and SR.warehouse_id={$warehouse_id} and SRD.is_pass=0 limit 1 ";
            $tmp = $StoreRequestDetailModel->query($sql);
            //\Think\Log::record($sql);
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                return array("status" => "0", "msg" => "转门店采购失败");
            }
            $store_id = $tmp[0]["store_id"];
            if (!array_key_exists($store_id, $store_with_purchase)) {
                $store_with_purchase[$store_id][] = $tmp[0];
            } else {
                array_push($store_with_purchase[$store_id], $tmp[0]);
            }
        }
        /********************************** 根据门店划分采购信息 end ***************************************************/
        $s_r_d_id_str = "";
        foreach ($store_with_purchase as $key => $val) {
            $store_id = $key;
            //查看是否有同一门店的申请
            $where = array();
            $where["store_id"] = $store_id;
            $where["p_r_type"] = 1;
            $where["p_r_status"] = 0;
            $purchase_request_datas = $PurchaseRequestModel->where($where)->limit(1)->select();

            $g_type = 0;
            $g_nums = 0;

            if (is_null($purchase_request_datas) || empty($purchase_request_datas) || count($purchase_request_datas) == 0) {
                //\Think\Log::record("has no same");
                /****************************** 不存在来自同一门店的申请 start ***************************************************/
                //生成采购申请单主表信息
                $PurchaseRequestEntity = array();
                $PurchaseRequestEntity["p_r_sn"] = get_new_order_no("SQ", "hii_purchase_request", "p_r_sn");
                $PurchaseRequestEntity["p_r_type"] = 1;
                $PurchaseRequestEntity["p_r_status"] = 0;
                $PurchaseRequestEntity["ctime"] = time();
                $PurchaseRequestEntity["admin_id"] = $admin_id;
                $PurchaseRequestEntity["store_id"] = $store_id;
                $PurchaseRequestEntity["remark"] = $remark;
                $p_r_id = $PurchaseRequestModel->add($PurchaseRequestEntity);
                if ($p_r_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店采购主表信息失败");
                }
                $PurchaseRequestDetailEntitys = array();
                foreach ($val as $k => $v) {
                    $PurchaseRequestDetailEntity = array();
                    $PurchaseRequestDetailEntity["p_r_id"] = $p_r_id;
                    $PurchaseRequestDetailEntity["goods_id"] = $v["goods_id"];
                    $PurchaseRequestDetailEntity["g_num"] = $v["g_num"];
                    $PurchaseRequestDetailEntity["is_pass"] = 0;
                    $PurchaseRequestDetailEntity["pass_num"] = 0;
                    $PurchaseRequestDetailEntity["remark"] = $v["remark"];
                    $PurchaseRequestDetailEntity["value_id"] = $v["value_id"];
                    $PurchaseRequestDetailEntitys[] = $PurchaseRequestDetailEntity;
                    $s_r_d_id_str .= $v["s_r_d_id"] . ",";
                    $g_type++;
                    $g_nums += $v["g_num"];
                }
                if (count($PurchaseRequestDetailEntitys) > 0) {
                    $ok = $PurchaseRequestDetailModel->addAll($PurchaseRequestDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增门店采购申请子表信息失败");
                    }
                }
                /********************* 更新主表 g_type，g_nums ********************************/
                $ok = $PurchaseRequestModel->where(" p_r_id={$p_r_id} ")->order(" p_r_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新门店采购申请主表信息失败");
                }
                /****************************** 不存在来自同一门店的申请 end ***************************************************/
            } else {
                //\Think\Log::record("has same");
                /****************************** 存在来自同一门店的申请 start ***************************************************/
                $p_r_id = $purchase_request_datas[0]["p_r_id"];
                $NewPurchaseRequestDetailEntitys = array();
                $UpdatePurchaseRequestDetailEntitys = array();
                foreach ($val as $k => $v) {
                    $tmp = $PurchaseRequestDetailModel->where(" p_r_id={$p_r_id} and goods_id={$v["goods_id"]} and value_id={$v['value_id']} and is_pass=0 ")->limit(1)->select();
                    if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                        $new_g_num = $tmp[0]["g_num"] + $v["g_num"];
                        $UpdatePurchaseRequestDetailEntitys[] = array(
                            "p_r_d_id" => $tmp[0]["p_r_d_id"],
                            "g_num" => $new_g_num
                        );
                    } else {
                        $NewPurchaseRequestDetailEntitys[] = array(
                            "p_r_id" => $p_r_id,
                            "goods_id" => $v["goods_id"],
                            "g_num" => $v["g_num"],
                            "is_pass" => 0,
                            "pass_num" => 0,
                            "value_id" => $v['value_id'],
                        );
                        $g_type++;
                    }
                    $s_r_d_id_str .= $v["s_r_d_id"] . ",";
                    $g_nums += $v["g_num"];
                }
                //新增子表信息
                if (count($NewPurchaseRequestDetailEntitys) > 0) {
                    $ok = $PurchaseRequestDetailModel->addAll($NewPurchaseRequestDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增门店采购申请子表信息失败");
                    }
                }
                //更新子表信息
                $result = $this->saveAll("hii_purchase_request_detail", $UpdatePurchaseRequestDetailEntitys, "p_r_d_id", $PurchaseRequestDetailModel);
                if ($result["status"] == "0") {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新门店采购申请子表信息失败");
                }
                //更新主表信息
                $savedata = array();
                $savedata["g_type"] = $purchase_request_datas[0]["g_type"] + $g_type;
                $savedata["g_nums"] = $purchase_request_datas[0]["g_nums"] + $g_nums;
                $savedata["remark"] .= (empty($purchase_request_datas[0]["remark"]) ? "" : ";") . $remark;
                $ok = $PurchaseRequestModel->where(" p_r_id={$p_r_id} ")->limit(1)->save($savedata);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新门店采购申请主表信息失败");
                }
                /****************************** 存在来自同一门店的申请 end ***************************************************/
            }
        }
        if (!empty($s_r_d_id_str)) {
            $s_r_d_id_str = substr($s_r_d_id_str, 0, strlen($s_r_d_id_str) - 1);
            $ok = $StoreRequestDetailModel->where(" s_r_d_id in ({$s_r_d_id_str}) ")->save(array("is_pass" => 3));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店发货申请子表信息失败");
            }
        }
        /************************************* 根据发货申请子表状态修改对应的主表状态 start ****************************************************************/
        if ($isUpdateStoreRequestStatus) {
            foreach ($info_json as $key => $val) {
                $items = $StoreRequestDetailModel->field(" s_r_id ")->where(" s_r_d_id={$val["s_r_d_id"]} ")->limit(1)->select();
                $s_r_id = $items[0]["s_r_id"];
                $store_request_details = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->select();
                $hasToWarehousePurchase = false;//采购到仓库 is_pass=4
                $hasToStorePurchase = false;//采购到门店 is_pass=3
                foreach ($store_request_details as $k => $v) {
                    if ($v["is_pass"] == 3) {
                        $hasToStorePurchase = true;
                    } elseif ($v["is_pass"] == 4) {
                        $hasToWarehousePurchase = true;
                    }
                }
                if ($hasToWarehousePurchase && !$hasToStorePurchase) {
                    //只有转仓库采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 7));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if (!$hasToWarehousePurchase && $hasToStorePurchase) {
                    //只有转门店采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 6));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if ($hasToWarehousePurchase && $hasToStorePurchase) {
                    //转仓库采购和转门店采购都有
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 8));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
            }
        }
        /************************************* 根据发货申请子表状态修改对应的主表状态 end ****************************************************************/

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /***********************
     * 转仓库采购【不合并】
     * @param $admin_id 管理员ID
     * @param $warehouse_id 仓库ID
     * @param $info_json [{"s_r_d_id":""},{"s_r_d_id":""}]
     */
    public function toWarehousePurchase($admin_id, $warehouse_id, $info_json, $remark)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $PurchaseRequestModel = M("PurchaseRequest");
        $PurchaseRequestDetailModel = M("PurchaseRequestDetail");
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $isUpdateStoreRequestStatus = true;//更新发货申请主表信息
        $WarehouseModel = M("Warehouse");

        //查找仓库所在区域
        $shequ_id = 0;
        $warehouse_datas = $WarehouseModel->where(" w_id={$warehouse_id} ")->limit(1)->select();
        if (!is_null($warehouse_datas) && !empty($warehouse_datas) && count($warehouse_datas) > 0) {
            $shequ_id = $warehouse_datas[0]["shequ_id"];
        }

        $warehouse_with_purchase = array();//门店对应的采购信息

        foreach ($info_json as $key => $val) {
            $sql = "select SR.s_r_id,SR.store_id,SRD.s_r_d_id,SRD.goods_id,SRD.g_num,SRD.remark,SRD.value_id ";
            $sql .= "from hii_store_request_detail SRD ";
            $sql .= "left join hii_store_request SR on SR.s_r_id=SRD.s_r_id ";
            $sql .= "where SRD.s_r_d_id={$val["s_r_d_id"]} and SR.warehouse_id={$warehouse_id} and SRD.is_pass=0 limit 1 ";
            $tmp = $StoreRequestDetailModel->query($sql);
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                return array("status" => "0", "msg" => "转仓库采购失败");
            }
            //array_push($warehouse_with_purchase, array("s_r_d_id" => $tmp[0]["s_r_d_id"], "goods_id" => $tmp[0]["goods_id"], "g_num" => $tmp[0]["g_num"]));
            if (!array_key_exists($tmp[0]["store_id"], $warehouse_with_purchase)) {
                $warehouse_with_purchase[$tmp[0]["store_id"]][] = $tmp[0];
            } else {
                array_push($warehouse_with_purchase[$tmp[0]["store_id"]], $tmp[0]);
            }
        }

        /******************************根据门店不同进行分单处理 start ***************************************************************************/
        foreach ($warehouse_with_purchase as $key => $val) {
            $store_id = $key;

            $g_type = 0;
            $g_nums = 0;

            /*********************************** 生成采购单相关信息 start ****************************************************************/
            //生成采购申请主表信息
            $PurchaseRequestEntity = array();
            $PurchaseRequestEntity["p_r_sn"] = get_new_order_no("SQ", "hii_purchase_request", "p_r_sn");
            $PurchaseRequestEntity["p_r_type"] = 0;
            $PurchaseRequestEntity["p_r_status"] = 0;
            $PurchaseRequestEntity["ctime"] = time();
            $PurchaseRequestEntity["admin_id"] = $admin_id;
            $PurchaseRequestEntity["warehouse_id"] = $warehouse_id;
            $PurchaseRequestEntity["store_id"] = $store_id;
            $PurchaseRequestEntity["remark"] = $remark;
            $p_r_id = $PurchaseRequestModel->add($PurchaseRequestEntity);
            if ($p_r_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增仓库采购主表信息失败");
            }

            $PurchaseRequestDetailEntitys = array();
            $s_r_d_id_str = "";
            $s_r_id_array = array();
            foreach ($val as $k => $v) {
                $PurchaseRequestDetailEntity = array();
                $PurchaseRequestDetailEntity["p_r_id"] = $p_r_id;
                $PurchaseRequestDetailEntity["goods_id"] = $v["goods_id"];
                $PurchaseRequestDetailEntity["g_num"] = $v["g_num"];
                $PurchaseRequestDetailEntity["is_pass"] = 0;
                $PurchaseRequestDetailEntity["pass_num"] = 0;
                $PurchaseRequestDetailEntity["remark"] = $v["remark"];
                $PurchaseRequestDetailEntity["value_id"] = $v["value_id"];
                $PurchaseRequestDetailEntitys[] = $PurchaseRequestDetailEntity;
                $s_r_d_id_str .= $v["s_r_d_id"] . ",";
                $g_type++;
                $g_nums += $v["g_num"];
                if (!in_array($v["s_r_id"], $s_r_id_array)) {
                    array_push($s_r_id_array, $v["s_r_id"]);
                }
            }
            if (count($PurchaseRequestDetailEntitys) > 0) {
                $ok = $PurchaseRequestDetailModel->addAll($PurchaseRequestDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库采购子表信息失败");
                }
            }

            $s_r_d_id_str = substr($s_r_d_id_str, 0, strlen($s_r_d_id_str) - 1);
            $ok = $StoreRequestDetailModel->where(" s_r_d_id in ({$s_r_d_id_str}) ")->save(array("is_pass" => 4));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店发货申请子表信息失败");
            }

            /*********************************  更新 g_type g_nums  start ******************************************/
            $ok = $PurchaseRequestModel->where(" p_r_id={$p_r_id} ")->order(" p_r_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新采购申请主表信息失败");
            }
            /*********************************  更新 g_type g_nums  end ******************************************/
            /*********************************** 生成采购单相关信息 end ****************************************************************/


            /*********************************** 生成出库验货单相关信息 start ****************************************************************/
            //生成验货单主表信息
            $WarehouseOutEntity = array();
            $WarehouseOutEntity["w_out_sn"] = get_new_order_no("CY", "hii_warehouse_out", "w_out_sn");
            $WarehouseOutEntity["w_out_status"] = 0;
            $WarehouseOutEntity["w_out_type"] = 1;
            $WarehouseOutEntity["s_r_id"] = implode(",", $s_r_id_array);
            $WarehouseOutEntity["ctime"] = time();
            $WarehouseOutEntity["admin_id"] = $admin_id;
            $WarehouseOutEntity["store_id"] = $store_id;
            $WarehouseOutEntity["warehouse_id2"] = $warehouse_id;
            $WarehouseOutEntity["remark"] = "";
            $WarehouseOutEntity["g_type"] = $g_type;
            $WarehouseOutEntity["g_nums"] = $g_nums;
            $w_out_id = $WarehouseOutModel->add($WarehouseOutEntity);
            if ($w_out_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增仓库出库验货单主表信息失败");
            }
            //生成出库验货单子表信息
            $WarehouseOutDetailEntitys = array();
            foreach ($val as $k => $v) {
                $g_price = 0;
                $tmp = $WarehouseInoutViewModel->where(" goods_id={$v["goods_id"]} and shequ_id={$shequ_id} ")->limit(1)->select();
                if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                    $g_price = $tmp[0]["stock_price"];
                }
                $WarehouseOutDetailEntitys[] = array(
                    "w_out_id" => $w_out_id,
                    "goods_id" => $v["goods_id"],
                    "g_num" => $v["g_num"],
                    "in_num" => 0,
                    "out_num" => 0,
                    "g_price" => $g_price,
                    "remark" => $v["remark"],
                    "s_r_d_id" => $v["s_r_d_id"],
                    "value_id" => $v["value_id"],
                );
            }
            $ok = $WarehouseOutDetailModel->addAll($WarehouseOutDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增仓库出库验货单子表信息失败");
            }
            /*********************************** 生成出库验货单相关信息 end ****************************************************************/

        }
        /******************************根据门店不同进行分单处理 end ***************************************************************************/

        /************************************* 根据发货申请子表状态修改对应的主表状态 start ****************************************************************/
        if ($isUpdateStoreRequestStatus) {
            foreach ($info_json as $key => $val) {
                $items = $StoreRequestDetailModel->field(" s_r_id ")->where(" s_r_d_id={$val["s_r_d_id"]} ")->limit(1)->select();
                $s_r_id = $items[0]["s_r_id"];
                $store_request_details = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->select();
                $hasToWarehousePurchase = false;//采购到仓库 is_pass=4
                $hasToStorePurchase = false;//采购到门店 is_pass=3
                foreach ($store_request_details as $k => $v) {
                    if ($v["is_pass"] == 3) {
                        $hasToStorePurchase = true;
                    } elseif ($v["is_pass"] == 4) {
                        $hasToWarehousePurchase = true;
                    }
                }
                if ($hasToWarehousePurchase && !$hasToStorePurchase) {
                    //只有转仓库采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 7));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if (!$hasToWarehousePurchase && $hasToStorePurchase) {
                    //只有转门店采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 6));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if ($hasToWarehousePurchase && $hasToStorePurchase) {
                    //转仓库采购和转门店采购都有
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 8));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
            }
        }
        /************************************* 根据发货申请子表状态修改对应的主表状态 end ****************************************************************/

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /***********************
     * 转仓库采购【合并】
     * @param $admin_id 管理员ID
     * @param $warehouse_id 仓库ID
     * @param $info_json [{"s_r_d_id":""},{"s_r_d_id":""}]
     */
    public function toWarehousePurchaseMerge($admin_id, $warehouse_id, $info_json, $remark)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $PurchaseRequestModel = M("PurchaseRequest");
        $PurchaseRequestDetailModel = M("PurchaseRequestDetail");
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $WarehouseModel = M("Warehouse");
        $isUpdateStoreRequestStatus = true;//更新发货申请主表信息

        $warehouse_with_purchase = array();//门店对应的采购信息

        foreach ($info_json as $key => $val) {
            $sql = "select SR.s_r_id,SR.store_id,SRD.s_r_d_id,SRD.goods_id,SRD.g_num,SRD.remark,SRD.value_id ";
            $sql .= "from hii_store_request_detail SRD ";
            $sql .= "left join hii_store_request SR on SR.s_r_id=SRD.s_r_id ";
            $sql .= "where SRD.s_r_d_id={$val["s_r_d_id"]} and SR.warehouse_id={$warehouse_id} and SRD.is_pass=0 limit 1 ";
            $tmp = $StoreRequestDetailModel->query($sql);
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                return array("status" => "0", "msg" => "转仓库采购失败");
            }
            //array_push($warehouse_with_purchase, array("s_r_d_id" => $tmp[0]["s_r_d_id"], "goods_id" => $tmp[0]["goods_id"], "g_num" => $tmp[0]["g_num"]));
            if (!array_key_exists($tmp[0]["store_id"], $warehouse_with_purchase)) {
                $warehouse_with_purchase[$tmp[0]["store_id"]][] = $tmp[0];
            } else {
                array_push($warehouse_with_purchase[$tmp[0]["store_id"]], $tmp[0]);
            }
        }

        print_r($warehouse_with_purchase);
        exit;

        /******************************根据门店不同进行分单处理 start ***************************************************************************/
        foreach ($warehouse_with_purchase as $key => $val) {
            $store_id = $key;
            $g_type = 0;
            $g_nums = 0;
            $total_g_type = 0;
            $s_r_d_id_str = "";
            $s_r_id_array = array();

            $NewPurchaseRequestDetailEntitys = array();
            $UpdatePurchaseRequestDetailEntitys = array();

            $where = array();
            $where["p_r_type"] = 0;
            $where["p_r_status"] = 0;
            $where["warehouse_id"] = $warehouse_id;
            $where["store_id"] = $store_id;
            $purchase_request_datas = $PurchaseRequestModel->where($where)->limit(1)->select();
            if (is_null($purchase_request_datas) || empty($purchase_request_datas) || count($purchase_request_datas) == 0) {
                //新增采购申请单
                /*********************************** 生成采购单相关信息 start ****************************************************************/
                //生成采购申请主表信息
                $PurchaseRequestEntity = array();
                $PurchaseRequestEntity["p_r_sn"] = get_new_order_no("SQ", "hii_purchase_request", "p_r_sn");
                $PurchaseRequestEntity["p_r_type"] = 0;
                $PurchaseRequestEntity["p_r_status"] = 0;
                $PurchaseRequestEntity["ctime"] = time();
                $PurchaseRequestEntity["admin_id"] = $admin_id;
                $PurchaseRequestEntity["warehouse_id"] = $warehouse_id;
                $PurchaseRequestEntity["store_id"] = $store_id;
                $PurchaseRequestEntity["remark"] = $remark;
                $p_r_id = $PurchaseRequestModel->add($PurchaseRequestEntity);
                if ($p_r_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库采购主表信息失败");
                }
                foreach ($val as $k => $v) {
                    $PurchaseRequestDetailEntity = array();
                    $PurchaseRequestDetailEntity["p_r_id"] = $p_r_id;
                    $PurchaseRequestDetailEntity["goods_id"] = $v["goods_id"];
                    $PurchaseRequestDetailEntity["g_num"] = $v["g_num"];
                    $PurchaseRequestDetailEntity["is_pass"] = 0;
                    $PurchaseRequestDetailEntity["pass_num"] = 0;
                    $PurchaseRequestDetailEntity["remark"] = $v["remark"];
                    $PurchaseRequestDetailEntity["value_id"] = $v["value_id"];
                    $NewPurchaseRequestDetailEntitys[] = $PurchaseRequestDetailEntity;
                    $s_r_d_id_str .= $v["s_r_d_id"] . ",";
                    $g_type++;
                    $total_g_type++;
                    $g_nums += $v["g_num"];
                    if (!in_array($v["s_r_id"], $s_r_id_array)) {
                        array_push($s_r_id_array, $v["s_r_id"]);
                    }
                }
                /*********************************** 生成采购单相关信息 end ****************************************************************/
            } else {
                //更新采购申请单
                $PurchaseRequestEntity = $purchase_request_datas[0];
                $p_r_id = $PurchaseRequestEntity["p_r_id"];
                foreach ($val as $v => $k) {
                    $tmp = $PurchaseRequestDetailModel->where(" p_r_id={$p_r_id} and goods_id={$v["goods_id"]} and is_pass=0 ")->limit(1)->select();
                    if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                        $PurchaseRequestDetailEntity = array();
                        $PurchaseRequestDetailEntity["p_r_id"] = $p_r_id;
                        $PurchaseRequestDetailEntity["goods_id"] = $v["goods_id"];
                        $PurchaseRequestDetailEntity["g_num"] = $v["g_num"];
                        $PurchaseRequestDetailEntity["is_pass"] = 0;
                        $PurchaseRequestDetailEntity["pass_num"] = 0;
                        $PurchaseRequestDetailEntity["value_id"] = $v['value_id'];
                        $PurchaseRequestDetailEntity["remark"] = empty($PurchaseRequestDetailEntity["remark"]) ? $v["remark"] : ";" . $v["remark"];
                        $NewPurchaseRequestDetailEntitys[] = $PurchaseRequestDetailEntity;
                        $g_type++;
                    } else {
                        $new_g_num = $v["g_num"] + $tmp[0]["g_num"];
                        $UpdatePurchaseRequestDetailEntitys[] = array("p_r_d_id" => $tmp[0]["p_r_d_id"], "g_num" => $new_g_num);
                    }
                    $s_r_d_id_str .= $v["s_r_d_id"] . ",";
                    $total_g_type++;
                    $g_nums += $v["g_num"];
                    if (!in_array($v["s_r_id"], $s_r_id_array)) {
                        array_push($s_r_id_array, $v["s_r_id"]);
                    }
                }
            }

            if (count($NewPurchaseRequestDetailEntitys) > 0) {
                $ok = $PurchaseRequestDetailModel->addAll($NewPurchaseRequestDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库采购子表信息失败");
                }
            }

            $result = $this->saveAll("hii_purchase_request_detail", $UpdatePurchaseRequestDetailEntitys, "p_r_d_id", $PurchaseRequestDetailModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新仓库采购子表信息失败");
            }

            $s_r_d_id_str = substr($s_r_d_id_str, 0, strlen($s_r_d_id_str) - 1);
            $ok = $StoreRequestDetailModel->where(" s_r_d_id in ({$s_r_d_id_str}) ")->save(array("is_pass" => 4));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店发货申请子表信息失败");
            }

            /*********************************  更新 g_type g_nums  start ******************************************/
            $ok = $PurchaseRequestModel->where(" p_r_id={$p_r_id} ")->order(" p_r_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新采购申请主表信息失败");
            }
            /*********************************  更新 g_type g_nums  end ******************************************/

            /*********************************** 生成出库验货单相关信息 start ****************************************************************/
            //生成验货单主表信息
            $WarehouseOutEntity = array();
            $WarehouseOutEntity["w_out_sn"] = get_new_order_no("CY", "hii_warehouse_out", "w_out_sn");
            $WarehouseOutEntity["w_out_status"] = 0;
            $WarehouseOutEntity["w_out_type"] = 1;
            $WarehouseOutEntity["s_r_id"] = implode(",", $s_r_id_array);
            $WarehouseOutEntity["ctime"] = time();
            $WarehouseOutEntity["admin_id"] = $admin_id;
            $WarehouseOutEntity["store_id"] = $store_id;
            $WarehouseOutEntity["warehouse_id2"] = $warehouse_id;
            $WarehouseOutEntity["remark"] = "";
            $WarehouseOutEntity["g_type"] = $total_g_type;
            $WarehouseOutEntity["g_nums"] = $g_nums;
            $w_out_id = $WarehouseOutModel->add($WarehouseOutEntity);
            if ($w_out_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增仓库出库验货单主表信息失败");
            }
            //生成出库验货单子表信息
            $WarehouseOutDetailEntitys = array();
            //获取仓库所在区域ID
            $shequ_id = 0;
            $warehouse_datas = $WarehouseModel->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
            if (!is_null($warehouse_datas) && !empty($warehouse_datas) && count($warehouse_datas) > 0) {
                $shequ_id = $warehouse_datas[0]["shequ_id"];
            }
            foreach ($val as $k => $v) {
                $g_price = 0;
                $tmp = $WarehouseInoutViewModel->field(" ifnull(stock_price,0) as stock_price ")
                    ->where(" goods_id={$v["goods_id"]} and shequ_id={$shequ_id} ")
                    ->limit(1)
                    ->select();
                if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                    $g_price = $tmp[0]["stock_price"];
                }
                $WarehouseOutDetailEntitys[] = array(
                    "w_out_id" => $w_out_id,
                    "goods_id" => $v["goods_id"],
                    "g_num" => $v["g_num"],
                    "in_num" => 0,
                    "out_num" => 0,
                    "g_price" => $g_price,
                    "s_r_d_id" => $v["s_r_d_id"],
                    "value_id" => $v["value_id"],
                );
            }
            if (count($WarehouseOutDetailEntitys) > 0) {
                $ok = $WarehouseOutDetailModel->addAll($WarehouseOutDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库出库验货单子表信息失败");
                }
            }
            /*********************************** 生成出库验货单相关信息 end ****************************************************************/
        }
        /******************************根据门店不同进行分单处理 end ***************************************************************************/

        /************************************* 根据发货申请子表状态修改对应的主表状态 start ****************************************************************/
        if ($isUpdateStoreRequestStatus) {
            foreach ($info_json as $key => $val) {
                $items = $StoreRequestDetailModel->field(" s_r_id ")->where(" s_r_d_id={$val["s_r_d_id"]} ")->limit(1)->select();
                $s_r_id = $items[0]["s_r_id"];
                $store_request_details = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->select();
                $hasToWarehousePurchase = false;//采购到仓库 is_pass=4
                $hasToStorePurchase = false;//采购到门店 is_pass=3
                foreach ($store_request_details as $k => $v) {
                    if ($v["is_pass"] == 3) {
                        $hasToStorePurchase = true;
                    } elseif ($v["is_pass"] == 4) {
                        $hasToWarehousePurchase = true;
                    }
                }
                if ($hasToWarehousePurchase && !$hasToStorePurchase) {
                    //只有转仓库采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 7));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if (!$hasToWarehousePurchase && $hasToStorePurchase) {
                    //只有转门店采购
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 6));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
                if ($hasToWarehousePurchase && $hasToStorePurchase) {
                    //转仓库采购和转门店采购都有
                    $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->save(array("s_r_status" => 8));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新门店发货申请主表信息失败");
                    }
                }
            }
        }
        /************************************* 根据发货申请子表状态修改对应的主表状态 end ****************************************************************/

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
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