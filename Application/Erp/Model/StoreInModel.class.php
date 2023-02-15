<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-15
 * Time: 16:11
 * 入库验货处理
 */

namespace Erp\Model;

use Think\Model;

class StoreInModel extends Model
{

    /**********************
     * 更新门店验货单明细信息
     * @param $info_array 更新信息 格式：Array([0]=>Array("s_in_d_id"=>1,"in_num"=>20,"out_num"=>2),[1]=>Array("s_in_d_id"=>2,"in_num"=>20,"out_num"=>2))
     * @param $s_in_id 入库验收单主表ID
     * @param $remark 备注
     */
    public function updateStoreInDetailInfoTrans($uid, $info_array, $s_in_id, $remark)
    {
        $this->startTrans();
        $StoreInDetailModel = M("StoreInDetail");
        $UpdateStoreInDetailEntitys = array();
        foreach ($info_array as $key => $val) {
            $savedata = array();
            $savedata["s_in_d_id"] = $val["s_in_d_id"];
            $savedata["in_num"] = $val["in_num"];
            $savedata["out_num"] = $val["out_num"];
            $savedata["remark"] = addslashes($val["remark"]);
            $savedata["audit_mark"] = 1;
            if (!is_null($val["endtime"]) && !empty($val["endtime"])) {
                $savedata["endtime"] = strtotime($val["endtime"]);
            }
            $UpdateStoreInDetailEntitys[] = $savedata;
        }
        $result = $this->saveAll("hii_store_in_detail", $UpdateStoreInDetailEntitys, "s_in_d_id", $StoreInDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => '更新入库验货单子表信息失败');
        }
        //更新备注
        $StoreInModel = M("StoreIn");
        $etime = time();
        $ok = $StoreInModel->where(" s_in_id={$s_in_id} ")->limit(1)->save(array("eadmin_id" => $uid, "etime" => $etime, "remark" => $remark));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新入库验货单主表信息失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /*******************
     * 门店验货入库审核【该方法无效】
     * @param $StoreInData 主表实体
     * @param $admin_id 当前操作人员ID
     * 门店来货验收状态：0.新增,1.已审核转收货,2.已退货报损,3.部分退货报损、部分收货
     * 门店来货验收来源:0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购
     */
    public function checkForStockIn($StoreInData, $admin_id)
    {
        $this->startTrans();
        $StoreInModel = M("StoreIn");
        $StoreInDetailModel = M("StoreInDetail");
        $lossInfoArray = array();//报损明细信息
        $successInStoreStockInfoArray = array();//成功入库信息
        $StoreInDetailDatas = $StoreInDetailModel->where(" s_in_id={$StoreInData["s_in_id"]} ")->select();
        $g_type = 0;
        $g_nums = 0;

        $s_in_status = 3;//门店验货单状态
        $out_num_amount = 0;//总退货数量
        $in_num_amount = 0;//总成功验货数量

        foreach ($StoreInDetailDatas as $key => $val) {
            if ($val["out_num"] > 0) {
                $out_num_amount += $val["out_num"];
                array_push($lossInfoArray, array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $val["g_price"]
                ));
            }
            if ($val["in_num"] > 0) {
                $g_type++;
                $g_nums += $val["in_num"];
                $in_num_amount += $val["in_num"];
                array_push($successInStoreStockInfoArray, array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $val["g_price"]
                ));
            }
        }

        //存在成功验货的时候生成收货单
        if (count($successInStoreStockInfoArray) > 0) {
            //转到门店收货单
            $StoreInStockModel = M("StoreInStock");
            $StoreInStockDetailModel = M("StoreInStockDetail");

            $StoreInStockData = array();
            $StoreInStockData["s_in_s_sn"] = get_new_order_no("MS", "hii_store_in_stock", "s_in_s_sn");//门店收货单单号
            $StoreInStockData["s_in_s_status"] = 0;
            $StoreInStockData["s_in_s_type"] = $StoreInData["s_in_type"];
            $StoreInStockData["s_in_id"] = $StoreInData["s_in_id"];
            $StoreInStockData["ctime"] = time();
            $StoreInStockData["admin_id"] = $admin_id;
            $StoreInStockData["warehouse_id"] = $StoreInData["warehouse_id"];
            $StoreInStockData["store_id1"] = $StoreInData["store_id1"];
            $StoreInStockData["store_id2"] = $StoreInData["store_id2"];
            $StoreInStockData["remark"] = $StoreInData["remark"];
            $StoreInStockData["g_type"] = $g_type;
            $StoreInStockData["g_nums"] = $g_nums;

            //保存主表
            $s_in_s_id = $StoreInStockModel->add($StoreInStockData);

            if ($s_in_s_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "门店收货单保存失败");
            }

            //保存明细
            foreach ($successInStoreStockInfoArray as $key => $val) {
                $StoreInStockDetailData = array();
                $StoreInStockDetailData["s_in_s_id"] = $s_in_s_id;
                $StoreInStockDetailData["goods_id"] = $val["goods_id"];
                $StoreInStockDetailData["g_num"] = $val["in_num"];
                $StoreInStockDetailData["g_price"] = $val["g_price"];
                $StoreInStockDetailData["s_in_d_id"] = $val["s_in_d_id"];
                $s_in_s_d_id = $StoreInStockDetailModel->add($StoreInStockDetailData);
                if ($s_in_s_d_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "门店收货单明细保存失败");
                }
            }
        }

        //生成报损单或采购退货单
        //逻辑：当入库单来自仓库发货和门店调拨时候生成报损单，当入库单来自采购的时候生成采购退货单
        if (count($lossInfoArray) > 0) {
            //入库验货单来源：0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
            if ($StoreInData["s_in_type"] == 4 || $StoreInData["s_in_type"] == 5) {
                //来自采购,生成采购退货单
                $PurchaseOutModel = M("PurchaseOut");
                $PurchaseOutDetailModel = M("PurchaseOutDetail");

                $g_type = 0;
                $g_nums = 0;
                foreach ($lossInfoArray as $key => $val) {
                    $g_type++;
                    $g_nums += $val["out_num"];
                }

                $p_o_type = 0;
                if ($StoreInData["s_in_type"] == 4) {
                    $p_o_type = 0;
                } elseif ($StoreInData["s_in_type"] == 5) {
                    $p_o_type = 1;
                }

                $PurchaseOutEntity = array();
                $PurchaseOutEntity["p_o_sn"] = get_new_order_no("CG", "hii_purchase_out", "p_o_sn");
                $PurchaseOutEntity["p_o_status"] = 0;
                $PurchaseOutEntity["p_o_type"] = $p_o_type;
                $PurchaseOutEntity["p_id"] = $StoreInData["p_id"];
                $PurchaseOutEntity["ctime"] = time();
                $PurchaseOutEntity["admin_id"] = $admin_id;
                $PurchaseOutEntity["supply_id"] = $StoreInData["supply_id"];
                $PurchaseOutEntity["store_id"] = $StoreInData["store_id2"];
                $PurchaseOutEntity["s_in_id"] = $StoreInData["s_in_id"];
                $PurchaseOutEntity["remark"] = $StoreInData["remark"];
                $PurchaseOutEntity["g_type"] = $g_type;
                $PurchaseOutEntity["g_nums"] = $g_nums;
                $p_o_id = $PurchaseOutModel->add($PurchaseOutEntity);
                if ($p_o_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增采购退货单主表信息失败");
                }
                foreach ($lossInfoArray as $key => $val) {
                    $PurchaseOutDetailEntity = array();
                    $PurchaseOutDetailEntity["p_o_id"] = $p_o_id;
                    $PurchaseOutDetailEntity["goods_id"] = $val["goods_id"];
                    $PurchaseOutDetailEntity["g_num"] = $val["out_num"];
                    $PurchaseOutDetailEntity["g_price"] = $val["g_price"];
                    $PurchaseOutDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                    $p_o_d_id = $PurchaseOutDetailModel->add($PurchaseOutDetailEntity);
                    if ($p_o_d_id === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增采购退货单子表信息失败");
                    }
                }
            } elseif ($StoreInData["s_in_type"] == 0 || $StoreInData["s_in_type"] == 1 || $StoreInData["s_in_type"] == 2) {
                $StoreOtherOutModel = M("StoreOtherOut");
                $StoreOtherOutDetailModel = M("StoreOtherOutDetail");
                $g_type = 0;
                $g_nums = 0;
                foreach ($lossInfoArray as $key => $val) {
                    $g_type++;
                    $g_nums += $val["out_num"];
                }
                $StoreOtherOutEntity = array();
                $StoreOtherOutEntity["s_o_out_sn"] = get_new_order_no("MB", "hii_store_other_out", "s_o_out_sn");
                $StoreOtherOutEntity["s_o_out_status"] = 0;
                $StoreOtherOutEntity["s_o_out_type"] = $StoreInData["s_in_type"]; //s_o_out_type类型:0.入库验收【仓库发货】报损,1.入库验收【门店调拨】报损,2.盘亏报损,3.商品过期，4.其它报损'
                $StoreOtherOutEntity["s_in_id"] = $StoreInData["s_in_id"];
                $StoreOtherOutEntity["ctime"] = time();
                $StoreOtherOutEntity["admin_id"] = $admin_id;
                $StoreOtherOutEntity["warehouse_id"] = $StoreInData["warehouse_id"];
                $StoreOtherOutEntity["store_id1"] = $StoreInData["store_id1"];
                $StoreOtherOutEntity["store_id2"] = $StoreInData["store_id2"];
                $StoreOtherOutEntity["remark"] = $StoreInData["remark"];
                $StoreOtherOutEntity["g_type"] = $g_type;
                $StoreOtherOutEntity["g_nums"] = $g_nums;
                $s_o_out_id = $StoreOtherOutModel->add($StoreOtherOutEntity);
                if ($s_o_out_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "添加门店退货单主表信息失败");
                }
                foreach ($lossInfoArray as $key => $val) {
                    $StoreOtherOutDetailEntity = array();
                    $StoreOtherOutDetailEntity["s_o_out_id"] = $s_o_out_id;
                    $StoreOtherOutDetailEntity["goods_id"] = $val["goods_id"];
                    $StoreOtherOutDetailEntity["g_num"] = $val["out_num"];
                    $StoreOtherOutDetailEntity["g_price"] = $val["g_price"];
                    $StoreOtherOutDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                    $s_o_out_d_id = $StoreOtherOutDetailModel->add($StoreOtherOutDetailEntity);
                    if ($s_o_out_d_id === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "添加门店退货单子表信息失败");
                    }
                }
            }
        }

        //更新门店收货表状态
        if ($out_num_amount == $StoreInData["g_nums"]) {
            $s_in_status = 2;
        }
        if ($in_num_amount == $StoreInData["g_nums"]) {
            $s_in_status = 1;
        }
        $ptime = time();
        $res = $StoreInModel->where(" s_in_id={$StoreInData["s_in_id"]} ")->limit(1)->save(
            array("s_in_status" => $s_in_status, "ptime" => $ptime, "padmin_id" => $admin_id)
        );
        if ($res === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "门店来货验收主表状态修改失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "审核成功");
    }


    /*********************
     * 所有退货
     * @param $StoreInData 主表实体
     * @param $admin_id 当前操作人员ID
     */
    public function allReject($StoreInData, $admin_id)
    {
        $this->startTrans();
        $StoreInModel = M("StoreIn");
        $StoreInDetailModel = M("StoreInDetail");
        $isRollWrite = true;//是否回写报损单或采购退货单
        $sql = "select * from hii_store_in_detail where s_in_id={$StoreInData["s_in_id"]} ";
        $datas = $StoreInDetailModel->query($sql);
        //更新门店验货单子表的入库数量和退货数量
        $g_type = 0;
        $g_nums = 0;
        $UpdateStoreInDetailEntitys = array();
        $lossInfoArray = array();
        foreach ($datas as $key => $val) {
            if ($val["g_num"] != ($val["in_num"] + $val["out_num"])) {
                return array("status" => "0", "msg" => "ID为【{$val["goods_id"]}】的商品验收数量与退货数量之和不等于提交数量");
            }
            $g_type++;
            $g_nums += $val["g_num"];
            $lossInfoArray[] = array(
                "goods_id" => $val["goods_id"],
                "out_num" => $val["g_num"],
                "s_in_d_id" => $val["s_in_d_id"],
                "g_price" => $val["g_price"],
                "remark" => $val["remark"],
                "value_id" => $val["value_id"],
            );
            $UpdateStoreInDetailEntitys[] = array(
                "s_in_d_id" => $val["s_in_d_id"],
                "in_num" => 0,
                "out_num" => $val["g_num"]
            );
        }

        $result = $this->saveAll("hii_store_in_detail", $UpdateStoreInDetailEntitys, "s_in_d_id", $StoreInDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新入库验货单子表信息失败");
        }

        if ($isRollWrite) {
            //入库验货单来源：0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购
            if ($StoreInData["s_in_type"] == 4) {
                $PurchaseOutModel = M("PurchaseOut");
                $PurchaseOutDetailModel = M("PurchaseOutDetail");

                $PurchaseOutEntity = array();
                $PurchaseOutEntity["p_o_sn"] = get_new_order_no("CG", "hii_purchase_out", "p_o_sn");
                $PurchaseOutEntity["p_o_status"] = 0;
                $PurchaseOutEntity["p_id"] = $StoreInData["p_id"];
                $PurchaseOutEntity["ctime"] = time();
                $PurchaseOutEntity["admin_id"] = $admin_id;
                $PurchaseOutEntity["supply_id"] = $StoreInData["supply_id"];
                $PurchaseOutEntity["store_id"] = $StoreInData["store_id2"];
                $PurchaseOutEntity["s_in_id"] = $StoreInData["s_in_id"];
                $PurchaseOutEntity["remark"] = $StoreInData["remark"];
                $PurchaseOutEntity["g_type"] = $g_type;
                $PurchaseOutEntity["g_nums"] = $g_nums;
                $p_o_id = $PurchaseOutModel->add($PurchaseOutEntity);
                if ($p_o_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增采购退货单主表信息失败");
                }
                $NewPurchaseOutDetailEntitys = array();
                foreach ($lossInfoArray as $key => $val) {
                    $PurchaseOutDetailEntity = array();
                    $PurchaseOutDetailEntity["p_o_id"] = $p_o_id;
                    $PurchaseOutDetailEntity["goods_id"] = $val["goods_id"];
                    $PurchaseOutDetailEntity["g_num"] = $val["out_num"];
                    $PurchaseOutDetailEntity["g_price"] = $val["g_price"];
                    $PurchaseOutDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                    $PurchaseOutDetailEntity["remark"] = $val["remark"];
                    $PurchaseOutDetailEntity["value_id"] = $val["value_id"];
                    $NewPurchaseOutDetailEntitys[] = $PurchaseOutDetailEntity;
                }
                if (count($NewPurchaseOutDetailEntitys) > 0) {
                    $ok = $PurchaseOutDetailModel->addAll($NewPurchaseOutDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增采购退货单子表信息失败");
                    }
                }
            } elseif ($StoreInData["s_in_type"] == 0 || $StoreInData["s_in_type"] == 1) {
                $StoreOtherOutModel = M("StoreOtherOut");
                $StoreOtherOutDetailModel = M("StoreOtherOutDetail");
                $StoreOtherOutEntity = array();
                $StoreOtherOutEntity["s_o_out_sn"] = get_new_order_no("MB", "hii_store_other_out", "s_o_out_sn");
                $StoreOtherOutEntity["s_o_out_status"] = 0;
                $StoreOtherOutEntity["s_o_out_type"] = $StoreInData["s_in_type"]; //s_o_out_type类型:0.入库验收【仓库发货】报损,1.入库验收【门店调拨】报损,2.盘亏报损,3.商品过期，4.其它报损'
                $StoreOtherOutEntity["s_in_id"] = $StoreInData["s_in_id"];
                $StoreOtherOutEntity["ctime"] = time();
                $StoreOtherOutEntity["admin_id"] = $admin_id;
                $StoreOtherOutEntity["warehouse_id"] = $StoreInData["warehouse_id"];
                $StoreOtherOutEntity["store_id1"] = $StoreInData["store_id1"];
                $StoreOtherOutEntity["store_id2"] = $StoreInData["store_id2"];
                $StoreOtherOutEntity["remark"] = $StoreInData["remark"];
                $StoreOtherOutEntity["g_type"] = $g_type;
                $StoreOtherOutEntity["g_nums"] = $g_nums;
                $s_o_out_id = $StoreOtherOutModel->add($StoreOtherOutEntity);
                if ($s_o_out_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店退货单主表信息失败");
                }
                $NewStoreOtherOutDetailEntitys = array();
                foreach ($lossInfoArray as $key => $val) {
                    $StoreOtherOutDetailEntity = array();
                    $StoreOtherOutDetailEntity["s_o_out_id"] = $s_o_out_id;
                    $StoreOtherOutDetailEntity["goods_id"] = $val["goods_id"];
                    $StoreOtherOutDetailEntity["g_num"] = $val["out_num"];
                    $StoreOtherOutDetailEntity["g_price"] = $val["g_price"];
                    $StoreOtherOutDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                    $StoreOtherOutDetailEntity["remark"] = $val["remark"];
                    $StoreOtherOutDetailEntity["value_id"] = $val["value_id"];
                    $NewStoreOtherOutDetailEntitys[] = $StoreOtherOutDetailEntity;
                }
                if (count($NewStoreOtherOutDetailEntitys) > 0) {
                    $ok = $StoreOtherOutDetailModel->addAll($NewStoreOtherOutDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增门店退货单子表信息失败");
                    }
                }
                if ($StoreInData["s_in_type"] == 0) {
                    //退回仓库
                    $WarehouseOtherOutModel = M("WarehouseOtherOut");
                    $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");

                    $WarehouseOtherOutEntity = array();
                    $WarehouseOtherOutEntity["w_o_out_sn"] = get_new_order_no("MB", "hii_warehouse_other_out", "w_o_out_sn");
                    $WarehouseOtherOutEntity["w_o_out_status"] = 0;
                    $WarehouseOtherOutEntity["w_o_out_type"] = 4;//门店向仓库退货
                    $WarehouseOtherOutEntity["s_o_out_id"] = $s_o_out_id;
                    $WarehouseOtherOutEntity["ctime"] = time();
                    $WarehouseOtherOutEntity["admin_id"] = $admin_id;
                    $WarehouseOtherOutEntity["ptime"] = time();
                    $WarehouseOtherOutEntity["padmin_id"] = $admin_id;
                    $WarehouseOtherOutEntity["store_id"] = $StoreInData["store_id2"];
                    $WarehouseOtherOutEntity["warehouse_id2"] = $StoreInData["warehouse_id"];
                    $WarehouseOtherOutEntity["remark"] = $StoreInData["remark"];
                    $WarehouseOtherOutEntity["g_type"] = $g_type;
                    $WarehouseOtherOutEntity["g_nums"] = $g_nums;
                    $w_o_out_id = $WarehouseOtherOutModel->add($WarehouseOtherOutEntity);
                    if ($w_o_out_id === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增仓库退货主表信息失败");
                    }
                    $NewWarehouseOtherOutDetailEntitys = array();
                    foreach ($lossInfoArray as $key => $val) {
                        $WarehouseOtherOutDetailEntity = array();
                        $WarehouseOtherOutDetailEntity["w_o_out_id"] = $w_o_out_id;
                        $WarehouseOtherOutDetailEntity["goods_id"] = $val["goods_id"];
                        $WarehouseOtherOutDetailEntity["g_num"] = $val["out_num"];
                        $WarehouseOtherOutDetailEntity["g_price"] = $val["g_price"];
                        $WarehouseOtherOutDetailEntity["remark"] = $val["remark"];
                        $WarehouseOtherOutDetailEntity["value_id"] = $val["value_id"];
                        $NewWarehouseOtherOutDetailEntitys[] = $WarehouseOtherOutDetailEntity;
                    }
                    if (count($NewWarehouseOtherOutDetailEntitys) > 0) {
                        $ok = $WarehouseOtherOutDetailModel->addAll($NewWarehouseOtherOutDetailEntitys);
                        if ($ok === false) {
                            $this->rollback();
                            return array("status" => "0", "msg" => "新增仓库退货子表信息失败");
                        }
                    }
                }
            }
        }

        //更新门店验货单主表状态
        $s_in_status = 2;
        $ptime = time();
        $ok = $StoreInModel->where(" s_in_id={$StoreInData["s_in_id"]} ")->limit(1)->save(
            array("s_in_status" => $s_in_status, "ptime" => $ptime, "padmin_id" => $admin_id)
        );
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "门店验货单主表状态更新失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "拒绝成功");
    }


    /***********************************
     *入库验货单审核
     * @param $admin_id 管理员ID
     * @param $store_id 门店ID
     * @param $s_in_id  入库验货单ID
     * 逻辑：审核后直接入库，但要生成入库单，但入库单默认通过审核
     * 来源：1.仓库发货，2.门店调拨，3.采购入库
     **********************************/
    public function pass($admin_id, $store_id, $s_in_id)
    {
        $this->startTrans();
        $StoreInModel = M("StoreIn");
        $StoreInDetailModel = M("StoreInDetail");
        $StoreInStockModel = M("StoreInStock");
        $StoreInStockDetailModel = M("StoreInStockDetail");
        $GoodsStoreModel = M("GoodsStore");
        $WarehouseInoutModel = M("WarehouseInout");
        $StoreModel = M("Store");

        $datas = $StoreInModel->where(" s_in_id={$s_in_id} and store_id2={$store_id} and s_in_status=0 ")->order(" s_in_id desc ")->limit(1)->lock(true)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "该入库验货单不能审核或已经处理  不能重复审核");
        }
        $StoreInData = $datas[0];
        $s_in_type = $StoreInData["s_in_type"];

        $lossInfoArray = array();//退货明细信息
        $successInStoreStockInfoArray = array();//成功入库信息
        $g_type = 0;
        $g_nums = 0;

        $s_in_status = 0;//门店验货单状态
        $out_num_amount = 0;//总退货数量
        $in_num_amount = 0;//总成功验货数量

        //$StoreInDetailDatas = $StoreInDetailModel->where(" s_in_id={$s_in_id} ")->select();
        $sql = "select SID.s_in_d_id,SID.s_in_id,SID.goods_id,SID.g_num,SID.in_num,SID.out_num,SID.g_price,SID.endtime,SID.remark,G.title as goods_name,SID.value_id ";
        $sql .= "from hii_store_in_detail SID ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "where SID.s_in_id={$s_in_id} ";
        $StoreInDetailDatas = $StoreInDetailModel->query($sql);
        foreach ($StoreInDetailDatas as $key => $val) {
            if ((($val["in_num"] + $val["out_num"]) != $val["g_num"]) && $StoreInData["s_in_type"] != 4) {
                return array("status" => "0", "msg" => "验收数量与退货数量之和不等于提交数量");
            }
            if ((($val["in_num"] + $val["out_num"]) < $val["g_num"]) && $StoreInData["s_in_type"] == 4) {
                return array("status" => "0", "msg" => "验收数量与退货数量之和不能少于提交数量");
            }
            if ($val["out_num"] > 0) {
                $out_num_amount += $val["out_num"];
                $lossInfoArray[] = array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $val["g_price"],
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "goods_name" => $val["goods_name"],
                    "value_id" => $val["value_id"]
                );
            }
            if ($val["in_num"] > 0) {
                $g_type++;
                $g_nums += $val["in_num"];
                $in_num_amount += $val["in_num"];
                $g_price = $val["g_price"];
                if ($s_in_type == 4 && $val["in_num"] > $val["g_num"]) {
                    //采购入库，入库验收数量大于申请数量【有赠品】，分摊均价
                    $g_price = round(($g_price * $val["g_num"]) / $val["in_num"], 2);
                }
                $successInStoreStockInfoArray[] = array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $g_price,
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"]
                );
            }
        }

        if (count($successInStoreStockInfoArray) > 0 && count($lossInfoArray) == 0) {
            $s_in_status = 1;
        }
        if (count($successInStoreStockInfoArray) == 0 && count($lossInfoArray) > 0) {
            $s_in_status = 2;
        }
        if (count($successInStoreStockInfoArray) > 0 && count($lossInfoArray) > 0) {
            $s_in_status = 3;
        }

        //存在成功验货的时候生成收货单，并直接设置为已审核
        if (count($successInStoreStockInfoArray) > 0) {
            //转到门店收货单
            $StoreInStockEntity = array();
            $StoreInStockEntity["s_in_s_sn"] = get_new_order_no("MS", "hii_store_in_stock", "s_in_s_sn");//门店收货单单号
            $StoreInStockEntity["s_in_s_status"] = 1;
            $StoreInStockEntity["s_in_s_type"] = $StoreInData["s_in_type"];
            $StoreInStockEntity["s_in_id"] = $StoreInData["s_in_id"];
            $StoreInStockEntity["ctime"] = time();
            $StoreInStockEntity["admin_id"] = $admin_id;
            $StoreInStockEntity["ptime"] = time();
            $StoreInStockEntity["padmin_id"] = $admin_id;
            $StoreInStockEntity["supply_id"] = $StoreInData["supply_id"];
            $StoreInStockEntity["warehouse_id"] = $StoreInData["warehouse_id"];
            $StoreInStockEntity["store_id1"] = $StoreInData["store_id1"];
            $StoreInStockEntity["store_id2"] = $StoreInData["store_id2"];
            $StoreInStockEntity["remark"] = $StoreInData["remark"];
            $StoreInStockEntity["g_type"] = $g_type;
            $StoreInStockEntity["g_nums"] = $g_nums;

            //保存主表
            $s_in_s_id = $StoreInStockModel->add($StoreInStockEntity);
            if ($s_in_s_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店收货单主表信息失败");
            }

            //保存明细
            $StoreInStockDetailEntitys = array();
            foreach ($successInStoreStockInfoArray as $key => $val) {
                $StoreInStockDetailEntity = array();
                $StoreInStockDetailEntity["s_in_s_id"] = $s_in_s_id;
                $StoreInStockDetailEntity["goods_id"] = $val["goods_id"];
                $StoreInStockDetailEntity["g_num"] = $val["in_num"];
                $StoreInStockDetailEntity["g_price"] = $val["g_price"];
                $StoreInStockDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                $StoreInStockDetailEntity["endtime"] = $val["endtime"];
                $StoreInStockDetailEntity["remark"] = $val["remark"];
                $StoreInStockDetailEntity["value_id"] = $val["value_id"];
                $StoreInStockDetailEntitys[] = $StoreInStockDetailEntity;
            }
            if (count($StoreInStockDetailEntitys) > 0) {
                $ok = $StoreInStockDetailModel->addAll($StoreInStockDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店收货单子表信息失败");
                }
            }
            $StoreInStockDetailEntitys = $StoreInStockDetailModel->where(" s_in_s_id={$s_in_s_id} ")->select();
            /**********************************写入批次表和库存*******************************************************************************************/
            $InoutStockType = array(2, 4, 5);//加入入库批次表的来源 来源:0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售

            //增加或更新库存
            $UpdateGoodsStoreEntitys = array();
            $NewGoodsStoreEntitys = array();
            $updateTime = time();
            foreach ($StoreInStockDetailEntitys as $key => $val) {
                $goodsstoredatas = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (is_null($goodsstoredatas) || empty($goodsstoredatas) || count($goodsstoredatas) == 0) {
                    //新增时候 获取该商品的其他门店的shequ_price
                    //获取门店同区域其他门店价格 更新自身
                    $storeModel = D('Store');
                    $getInfo = $storeModel->field('shequ_id')->where(array('id'=> $store_id)) ->find();
					$shequ_price = M('GoodsShequ')->field('price')->where(array('goods_id'=>$val['goods_id'],'shequ_id'=>$getInfo['shequ_id'],'status'=>1))->order('ctime desc')->find();
                    $savedata = array(
                        "goods_id" => $val["goods_id"],
                        "store_id" => $store_id,
                        "num" => $val["g_num"],
                        "shequ_price" => empty($shequ_price) ? null: $shequ_price['price'],
                        "update_time" => $updateTime
                    );
                    $GoodsStoreModel->add($savedata);
                } else {
                    $savedata = array(
                        "num" => $val["g_num"] + $goodsstoredatas[0]["num"],
                        "update_time" => $updateTime
                    );
                    $GoodsStoreModel->where(" id={$goodsstoredatas[0]["id"]} ")->limit(1)->save($savedata);
                }
            }
            /***************************判断是否需要添加批次信息*************************************************************/
            if (in_array($StoreInStockEntity["s_in_s_type"], $InoutStockType)) {
                //查找门店属于那个社区shequ_id
                $storedatas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
                $shequ_id = 0;
                if (!is_null($storedatas) && !empty($storedatas) && count($storedatas) > 0) {
                    $shequ_id = $storedatas[0]["shequ_id"];
                }
                $WarehouseInoutEntitys = array();
                foreach ($StoreInStockDetailEntitys as $key => $val) {
                    $endtime = 0;
                    if ($StoreInStockEntity["s_in_s_type"] == 2) {
                        $ctype = 1;
                    } elseif ($StoreInStockEntity["s_in_s_type"] == 4) {
                        $ctype = 0;
                        $endtime = $val["endtime"];
                    } elseif ($StoreInStockEntity["s_in_stype"] == 5) {
                        $ctype = 2;
                        $endtime = $val["endtime"];
                    }
                    $WarehouseInoutEntity = array();
                    $WarehouseInoutEntity["goods_id"] = $val["goods_id"];
                    $WarehouseInoutEntity["innum"] = $val["g_num"];
                    $WarehouseInoutEntity["inprice"] = $val["g_price"];
                    $WarehouseInoutEntity["outnum"] = 0;
                    $WarehouseInoutEntity["num"] = $val["g_num"];
                    $WarehouseInoutEntity["ctime"] = time();
                    $WarehouseInoutEntity["ctype"] = 2;//$ctype;
                    $WarehouseInoutEntity["endtime"] = $endtime;
                    $WarehouseInoutEntity["store_id"] = $store_id;
                    $WarehouseInoutEntity["shequ_id"] = $shequ_id;
                    $WarehouseInoutEntity["s_in_s_d_id"] = $val["s_in_s_d_id"];
                    $WarehouseInoutEntity["value_id"] = $val["value_id"];
                    $WarehouseInoutEntitys[] = $WarehouseInoutEntity;
                }
                if (count($WarehouseInoutEntitys) > 0) {
                    $ok = $WarehouseInoutModel->addAll($WarehouseInoutEntitys);
                    if ($ok == false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增入库批次信息失败");
                    }
                }
            }
        }

        //生成报损单或采购退货单
        //逻辑：当入库单来自仓库发货和门店调拨时候生成报损单，当入库单来自采购的时候生成采购退货单
        if (count($lossInfoArray) > 0) {
            //入库验货单来源：0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
            if ($StoreInData["s_in_type"] == 4 || $StoreInData["s_in_type"] == 5) {
                //来自采购,生成采购退货单
                $PurchaseOutModel = M("PurchaseOut");
                $PurchaseOutDetailModel = M("PurchaseOutDetail");

                $g_type = 0;
                $g_nums = 0;
                foreach ($lossInfoArray as $key => $val) {
                    $g_type++;
                    $g_nums += $val["out_num"];
                }

                $p_o_type = 0;
                if ($StoreInData["s_in_type"] == 4) {
                    $p_o_type = 0;
                } elseif ($StoreInData["s_in_type"] == 5) {
                    $p_o_type = 1;
                }

                $PurchaseOutEntity = array();
                $PurchaseOutEntity["p_o_sn"] = get_new_order_no("CG", "hii_purchase_out", "p_o_sn");
                $PurchaseOutEntity["p_o_status"] = 0;
                $PurchaseOutEntity["p_o_type"] = $p_o_type;
                $PurchaseOutEntity["p_id"] = $StoreInData["p_id"];
                $PurchaseOutEntity["ctime"] = time();
                $PurchaseOutEntity["admin_id"] = $admin_id;
                $PurchaseOutEntity["supply_id"] = $StoreInData["supply_id"];
                $PurchaseOutEntity["store_id"] = $StoreInData["store_id2"];
                $PurchaseOutEntity["s_in_id"] = $StoreInData["s_in_id"];
                $PurchaseOutEntity["remark"] = $StoreInData["remark"];
                $PurchaseOutEntity["g_type"] = $g_type;
                $PurchaseOutEntity["g_nums"] = $g_nums;
                $p_o_id = $PurchaseOutModel->add($PurchaseOutEntity);
                if ($p_o_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增采购退货单主表信息失败");
                }
                $PurchaseOutDetailEntitys = array();
                foreach ($lossInfoArray as $key => $val) {
                    $PurchaseOutDetailEntity = array();
                    $PurchaseOutDetailEntity["p_o_id"] = $p_o_id;
                    $PurchaseOutDetailEntity["goods_id"] = $val["goods_id"];
                    $PurchaseOutDetailEntity["g_num"] = $val["out_num"];
                    $PurchaseOutDetailEntity["g_price"] = $val["g_price"];
                    $PurchaseOutDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                    $PurchaseOutDetailEntity["remark"] = $val["remark"];
                    $PurchaseOutDetailEntity["value_id"] = $val["value_id"];
                    $PurchaseOutDetailEntitys[] = $PurchaseOutDetailEntity;
                }
                if (count($PurchaseOutDetailEntitys) > 0) {
                    $ok = $PurchaseOutDetailModel->addAll($PurchaseOutDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增采购退货单子表信息失败");
                    }
                }
            } elseif ($StoreInData["s_in_type"] == 0 || $StoreInData["s_in_type"] == 1 || $StoreInData["s_in_type"] == 2) {
                $StoreOtherOutModel = M("StoreOtherOut");
                $StoreOtherOutDetailModel = M("StoreOtherOutDetail");
                $g_type = 0;
                $g_nums = 0;
                foreach ($lossInfoArray as $key => $val) {
                    $g_type++;
                    $g_nums += $val["out_num"];
                }
                $StoreOtherOutEntity = array();
                $StoreOtherOutEntity["s_o_out_sn"] = get_new_order_no("MB", "hii_store_other_out", "s_o_out_sn");
                $StoreOtherOutEntity["s_o_out_status"] = 0;
                $StoreOtherOutEntity["s_o_out_type"] = $StoreInData["s_in_type"]; //s_o_out_type类型:0.入库验收【仓库发货】报损,1.入库验收【门店调拨】报损,2.盘亏报损,3.商品过期，4.其它报损'
                $StoreOtherOutEntity["s_in_id"] = $StoreInData["s_in_id"];
                $StoreOtherOutEntity["ctime"] = time();
                $StoreOtherOutEntity["admin_id"] = $admin_id;
                $StoreOtherOutEntity["warehouse_id"] = $StoreInData["warehouse_id"];
                $StoreOtherOutEntity["store_id1"] = $StoreInData["store_id1"];
                $StoreOtherOutEntity["store_id2"] = $StoreInData["store_id2"];
                $StoreOtherOutEntity["remark"] = $StoreInData["remark"];
                $StoreOtherOutEntity["g_type"] = $g_type;
                $StoreOtherOutEntity["g_nums"] = $g_nums;
                $s_o_out_id = $StoreOtherOutModel->add($StoreOtherOutEntity);
                if ($s_o_out_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店退货单主表信息失败");
                }
                $StoreOtherOutDetailEntitys = array();
                foreach ($lossInfoArray as $key => $val) {
                    $StoreOtherOutDetailEntity = array();
                    $StoreOtherOutDetailEntity["s_o_out_id"] = $s_o_out_id;
                    $StoreOtherOutDetailEntity["goods_id"] = $val["goods_id"];
                    $StoreOtherOutDetailEntity["g_num"] = $val["out_num"];
                    $StoreOtherOutDetailEntity["g_price"] = $val["g_price"];
                    $StoreOtherOutDetailEntity["s_in_d_id"] = $val["s_in_d_id"];
                    $StoreOtherOutDetailEntity["remark"] = $val["remark"];
                    $StoreOtherOutDetailEntity["value_id"] = $val["value_id"];
                    $StoreOtherOutDetailEntitys[] = $StoreOtherOutDetailEntity;
                }
                if (count($StoreOtherOutDetailEntitys) > 0) {
                    $ok = $StoreOtherOutDetailModel->addAll($StoreOtherOutDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "添加门店退货单子表信息失败");
                    }
                }

                /****************************向仓库退货 start********************************************************/
                if ($StoreInData["s_in_type"] == 0) {
                    $WarehouseOtherOutModel = M("WarehouseOtherOut");
                    $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");

                    $WarehouseOtherOutEntity = array();
                    $w_o_out_sn = get_new_order_no("MB", "hii_warehouse_other_out", "w_o_out_sn");
                    $WarehouseOtherOutEntity["w_o_out_sn"] = $w_o_out_sn;
                    $WarehouseOtherOutEntity["w_o_out_status"] = 0;
                    $WarehouseOtherOutEntity["w_o_out_type"] = 4;//门店向仓库退货
                    $WarehouseOtherOutEntity["s_o_out_id"] = $s_o_out_id;
                    $WarehouseOtherOutEntity["ctime"] = time();
                    $WarehouseOtherOutEntity["admin_id"] = $admin_id;
                    $WarehouseOtherOutEntity["store_id"] = $store_id;
                    $WarehouseOtherOutEntity["warehouse_id2"] = $StoreInData["warehouse_id"];
                    $WarehouseOtherOutEntity["remark"] = $StoreInData["remark"];
                    $WarehouseOtherOutEntity["g_type"] = $g_type;
                    $WarehouseOtherOutEntity["g_nums"] = $g_nums;
                    $w_o_out_id = $WarehouseOtherOutModel->add($WarehouseOtherOutEntity);
                    if ($w_o_out_id === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增仓库退货主表信息失败");
                    }
                    $NewWarehouseOtherOutDetailEntitys = array();
                    foreach ($lossInfoArray as $key => $val) {
                        $WarehouseOtherOutDetailEntity = array();
                        $WarehouseOtherOutDetailEntity["w_o_out_id"] = $w_o_out_id;
                        $WarehouseOtherOutDetailEntity["goods_id"] = $val["goods_id"];
                        $WarehouseOtherOutDetailEntity["g_num"] = $val["out_num"];
                        $WarehouseOtherOutDetailEntity["g_price"] = $val["g_price"];
                        $WarehouseOtherOutDetailEntity["remark"] = $val["remark"];
                        $WarehouseOtherOutDetailEntity["value_id"] = $val["value_id"];
                        $NewWarehouseOtherOutDetailEntitys[] = $WarehouseOtherOutDetailEntity;
                    }
                    if (count($NewWarehouseOtherOutDetailEntitys) > 0) {
                        $ok = $WarehouseOtherOutDetailModel->addAll($NewWarehouseOtherOutDetailEntitys);
                        if ($ok === false) {
                            $this->rollback();
                            return array("status" => "0", "msg" => "新增仓库退货子表信息失败");
                        }
                    }

                }
                /****************************向仓库退货 end********************************************************/

            }
        }

        //************更新门店收货表状态*********************************************************************
        if ($out_num_amount == $StoreInData["g_nums"]) {
            $s_in_status = 2;
        }
        if ($in_num_amount == $StoreInData["g_nums"]) {
            $s_in_status = 1;
        }
        $ptime = time();
        $res = $StoreInModel->where(" s_in_id={$StoreInData["s_in_id"]} ")->limit(1)->save(
            array("s_in_status" => $s_in_status, "ptime" => $ptime, "padmin_id" => $admin_id)
        );
        if ($res === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新门店验货单信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /*************************
     * 批量更新数据
     * @param $tableName 表名
     * @param $datas 更新的数据
     * @param $pk 主键
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