<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-06
 * Time: 17:33
 * 门店入库
 */

namespace Internal\Model;

use Think\Model;


class StoreInModel extends Model
{

    /**************
     * 更新门店入库验货单信息
     * @param $store_id 门店ID
     * @param $s_in_id  入库验货单ID
     * @param $remark   备注
     * @param $detail_array 子表更新数据数组Array(Array("s_in_d_id"=>"","in_num"=>"","out_num"=>"","endtime"=>"","remark"=>""),
     *                                             Array("s_in_d_id"=>"","in_num"=>"","out_num"=>"","endtime"=>"","remark"=>""))
     */
    function updateStoreInInfo($uid, $store_id, $s_in_id, $remark, $detail_array)
    {
        $this->startTrans();
        $StoreInModel = M("StoreIn");
        $StoreInDetailModel = M("StoreInDetail");

        $store_in_data = $StoreInModel->where(" `s_in_id`={$s_in_id} and `store_id2`={$store_id} and `s_in_status`=0 ")->limit(1)->select();
        if (is_null($store_in_data) || empty($store_in_data) || count($store_in_data) == 0) {
            return array("status" => 0, "msg" => "无权修改");
        }

        /*********修改主表remark************/
        $savedata = array();
        $savedata["remark"] = $remark;
        $savedata["eadmin_id"] = $uid;
        $savedata["etime"] = time();
        $ok = $StoreInModel->where(" `s_in_id`={$s_in_id} ")->limit(1)->save($savedata);
        if ($ok === false) {
            $this->rollback();
            return array("status" => 0, "msg" => "更新主表信息失败");
        }
        /*********修改明细表信息*********************/
        $UpdateStoreInDetailEntitys = array();
        foreach ($detail_array as $key => $val) {
            $savedata = array();
            $savedata["s_in_d_id"] = $val["s_in_d_id"];
            $savedata["in_num"] = $val["in_num"];
            $savedata["out_num"] = $val["out_num"];
            $savedata["remark"] = $val["remark"];
            $savedata["audit_mark"] = 1;
            if (!is_null($val["endtime"]) && !empty($val["endtime"])) {
                $savedata["endtime"] = strtotime($val["endtime"]);
            } else {
                $savedata["endtime"] = strtotime(date('Y-m-d H:i:s', strtotime('+1 month')));//当前日期加一个月
            }
            $UpdateStoreInDetailEntitys[] = $savedata;
        }
        $result = $this->saveAll("hii_store_in_detail", $UpdateStoreInDetailEntitys, "s_in_d_id", $StoreInDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新入库验货单子表信息失败");
        }
        $this->commit();
        return array("status" => 200, "msg" => "操作成功");
    }


    /******************
     * 入库验货单审核
     * @param  string $uid 会员ID
     * @param string $store_id 门店ID
     * @param mixed $s_in_id 验货单ID
     * s_in_type：0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
     * 注意：一张单同一商品可能出现多次
     */
    function check($uid, $store_id, $s_in_id)
    {
        $StoreInModel = M("StoreIn");
        $store_in_data = $StoreInModel->where(" `s_in_id`={$s_in_id} and `s_in_status`=0 and `store_id2`={$store_id} ")->limit(1)->select();
        if (is_null($store_in_data) || empty($store_in_data) || count($store_in_data) == 0) {
            return array("status" => 0, "msg" => "无法审核该验货单");
        }
        $maindata = $store_in_data[0];
        if ($maindata["s_in_type"] == 0 || $maindata["s_in_type"] == 1) {
            return $this->storeInWithOutWarehouseInout($uid, $maindata, $s_in_id);
        } elseif ($maindata["s_in_type"] == 4) {
            return $this->storeInWithWarehouseInout($uid, $maindata, $s_in_id);
        }
        return array("status" => 0, "msg" => "没有审核的数据");
    }

    /****************************
     * 入库验收不增加批次，包含类型有【0：仓库发货，1：门店调拨】
     * @param $s_in_id 入库验货单ID
     */
    private function storeInWithOutWarehouseInout($uid, $maindata, $s_in_id)
    {
        $this->startTrans();
        $StoreInDetailModel = M("StoreInDetail");
        $StoreInStockModel = M("StoreInStock");
        $StoreInStockDetailModel = M("StoreInStockDetail");
        $GoodsStoreModel = M("GoodsStore");
        $StoreInModel = M("StoreIn");

        $sql = "select SID.s_in_d_id,SID.s_in_id,SID.goods_id,SID.g_num,SID.in_num,SID.out_num,SID.g_price,SID.endtime,SID.remark,G.title as goods_name,SID.value_id ";
        $sql .= "from hii_store_in_detail SID ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "where SID.s_in_id={$s_in_id} ";
        $store_in_detail_data = $StoreInDetailModel->query($sql);

        $success_in_store_data = array();//成功入库商品
        $success_in_store_g_nums = 0;//成功入库数量总和
        $fail_in_store_data = array();//退货商品
        $fail_in_store_g_nums = 0;//退货数量总和
        $current_time = time();

        //收集入库退货信息
        foreach ($store_in_detail_data as $key => $val) {
            if ($val["g_num"] != ($val["in_num"] + $val["out_num"])) {
                return array("status" => 0, "msg" => "验收数量与退货数量之和不等于提交数量");
            }
            if ($val["in_num"] > 0) {
                $success_in_store_g_nums += $val["in_num"];
                $success_in_store_data[] = array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $val["g_price"],
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"],
                );
            }
            if ($val["out_num"] > 0) {
                $fail_in_store_g_nums += $val["out_num"];
                $fail_in_store_data[] = array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $val["g_price"],
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "goods_name" => $val["goods_name"],
                    "value_id" => $val["value_id"],
                );
            }
        }

        $s_in_status = null;
        if (count($success_in_store_data) > 0 && $success_in_store_g_nums == $maindata["g_nums"] && count($fail_in_store_data) == 0) {
            $s_in_status = 1;//全部入库
        }
        if (count($success_in_store_data) == 0 && count($fail_in_store_data) > 0 && $fail_in_store_g_nums == $maindata["g_nums"]) {
            $s_in_status = 2;//全部退货
        }
        if (count($success_in_store_data) > 0 && count($fail_in_store_data) > 0) {
            $s_in_status = 3;//部分入库，部分退货
        }
        if ($s_in_status == null) {
            return array("status" => 0, "msg" => "审核状态有误");
        }

        if (count($success_in_store_data) > 0) {
            //生成入库单数据
            $store_in_stock_entity = array();
            $s_in_s_sn = get_new_order_no("MS", "hii_store_in_stock", "s_in_s_sn");//门店收货单单号
            if (is_null($s_in_s_sn) || empty($s_in_s_sn)) {
                return array("status" => 0, "msg" => "门店入库单单号生成失败");
            }
            $store_in_stock_entity["s_in_s_sn"] = $s_in_s_sn;
            $store_in_stock_entity["s_in_s_status"] = 1;
            $store_in_stock_entity["s_in_s_type"] = $maindata["s_in_type"];
            $store_in_stock_entity["s_in_id"] = $maindata["s_in_id"];
            $store_in_stock_entity["ctime"] = $current_time;
            $store_in_stock_entity["admin_id"] = $uid;
            $store_in_stock_entity["ptime"] = $current_time;
            $store_in_stock_entity["padmin_id"] = $uid;
            $store_in_stock_entity["supply_id"] = $maindata["supply_id"];
            $store_in_stock_entity["warehouse_id"] = $maindata["warehouse_id"];
            $store_in_stock_entity["store_id1"] = $maindata["store_id1"];
            $store_in_stock_entity["store_id2"] = $maindata["store_id2"];
            $store_in_stock_entity["remark"] = $maindata["remark"];
            $store_in_stock_entity["g_type"] = count($success_in_store_data);
            $store_in_stock_entity["g_nums"] = $success_in_store_g_nums;

            $s_in_s_id = $StoreInStockModel->add($store_in_stock_entity);
            if ($s_in_s_id === false) {
                $this->rollback();
                return array("status" => 0, "msg" => "新增门店收货单主表信息失败");
            }

            //保存入库单子表信息
            $store_in_stock_detail_entitys = array();
            foreach ($success_in_store_data as $key => $val) {
                $store_in_stock_detail_entitys[] = array(
                    "s_in_s_id" => $s_in_s_id,
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["in_num"],
                    "g_price" => $val["g_price"],
                    "s_in_d_id" => $val["s_in_d_id"],
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"],
                );
            }
            if (count($store_in_stock_detail_entitys) > 0) {
                $ok = $StoreInStockDetailModel->addAll($store_in_stock_detail_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "新增门店收货单子表信息失败");
                }
            }

            $store_id = $maindata["store_id2"];
            //修改库存
            foreach ($store_in_stock_detail_entitys as $key => $val) {
                $goods_store_data = $GoodsStoreModel->where(" `store_id`={$store_id} and `goods_id`={$val["goods_id"]} ")->limit(1)->select();
                if (is_null($goods_store_data) || empty($goods_store_data) || count($goods_store_data) == 0) {
                	$storeModel = D('Store');
                	$getInfo = $storeModel->field('shequ_id')->where(array('id'=> $store_id)) ->find();
                	$shequ_price = M('GoodsShequ')->field('price')->where(array('goods_id'=>$val['goods_id'],'shequ_id'=>$getInfo['shequ_id'],'status'=>1))->order('ctime desc')->find();
                    $savedata = array(
                        "goods_id" => $val["goods_id"],
                        "store_id" => $store_id,
                        "num" => $val["g_num"],
                        "shequ_price" => empty($shequ_price) ? null: $shequ_price['price'],
                        "update_time" => $current_time
                    );
                    $ok = $GoodsStoreModel->add($savedata);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => 0, "msg" => "ID为【{$val["goods_id"]}】的商品新增库存失败");
                    }
                } else {
                    $savedata = array(
                        "num" => $val["g_num"] + $goods_store_data[0]["num"],
                        "update_time" => $current_time
                    );
                    $ok = $GoodsStoreModel->where(" id={$goods_store_data[0]["id"]} ")->limit(1)->save($savedata);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => 0, "msg" => "ID为【{$val["goods_id"]}】的商品更新库存失败");
                    }
                }
            }
        }

        if (count($fail_in_store_data) > 0) {
            $StoreOtherOutModel = M("StoreOtherOut");
            $StoreOtherOutDetailModel = M("StoreOtherOutDetail");
            $g_type = 0;
            $g_nums = 0;
            foreach ($fail_in_store_data as $key => $val) {
                $g_type++;
                $g_nums += $val["out_num"];
            }

            //退回仓库

            //门店退货单主表信息
            $store_other_out_entity = array();
            $s_o_out_sn = get_new_order_no("MB", "hii_store_other_out", "s_o_out_sn");
            if (is_null($s_o_out_sn) || empty($s_o_out_sn)) {
                return array("status" => 0, "msg" => "门店退货单单号生成失败");
            }
            $store_other_out_entity["s_o_out_sn"] = $s_o_out_sn;
            $store_other_out_entity["s_o_out_status"] = 0;
            $store_other_out_entity["s_o_out_type"] = $maindata["s_in_type"];
            $store_other_out_entity["s_in_id"] = $maindata["s_in_id"];
            $store_other_out_entity["ctime"] = $current_time;
            $store_other_out_entity["admin_id"] = $uid;
            $store_other_out_entity["warehouse_id"] = $maindata["warehouse_id"];//发货仓库
            $store_other_out_entity["store_id1"] = $maindata["store_id1"];//发货门店
            $store_other_out_entity["store_id2"] = $maindata["store_id2"];//收货门店
            $store_other_out_entity["remark"] = $maindata["remark"];
            $store_other_out_entity["g_type"] = $g_type;
            $store_other_out_entity["g_nums"] = $g_nums;

            $s_o_out_id = $StoreOtherOutModel->add($store_other_out_entity);
            if ($s_o_out_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店退货单主表信息失败");
            }

            //门店退货单子表信息
            $store_other_out_detail_entitys = array();
            foreach ($fail_in_store_data as $key => $val) {
                $store_other_out_detail_entitys[] = array(
                    "s_o_out_id" => $s_o_out_id,
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["out_num"],
                    "g_price" => $val["g_price"],
                    "s_in_d_id" => $val["s_in_d_id"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"],
                );
            }
            if (count($store_other_out_detail_entitys) > 0) {
                $ok = $StoreOtherOutDetailModel->addAll($store_other_out_detail_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "新增门店退货单子表信息失败");
                }
            }

            //退回仓库还要写入hii_warehouse_other_out,hii_warehouse_other_out_detail表
            if ($maindata["s_in_type"] == 0) {
                $WarehouseOtherOutModel = M("WarehouseOtherOut");
                $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");

                //仓库被退货单主表信息
                $warehouse_other_out_entity = array();
                $warehouse_other_out_entity["w_o_out_sn"] = get_new_order_no("MB", "hii_warehouse_other_out", "w_o_out_sn");
                $warehouse_other_out_entity["w_o_out_status"] = 0;
                $warehouse_other_out_entity["w_o_out_type"] = 4;//门店向仓库退货
                $warehouse_other_out_entity["s_o_out_id"] = $s_o_out_id;
                $warehouse_other_out_entity["ctime"] = time();
                $warehouse_other_out_entity["admin_id"] = $uid;
                $warehouse_other_out_entity["store_id"] = $store_id;
                $warehouse_other_out_entity["warehouse_id2"] = $maindata["warehouse_id"];
                $warehouse_other_out_entity["remark"] = $maindata["remark"];
                $warehouse_other_out_entity["g_type"] = $g_type;
                $warehouse_other_out_entity["g_nums"] = $g_nums;
                $w_o_out_id = $WarehouseOtherOutModel->add($warehouse_other_out_entity);
                if ($w_o_out_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库退货主表信息失败");
                }

                //仓库被退货单子表信息
                $warehouse_other_out_detail_entitys = array();
                foreach ($fail_in_store_data as $key => $val) {
                    $warehouse_other_out_detail_entitys[] = array(
                        "w_o_out_id" => $w_o_out_id,
                        "goods_id" => $val["goods_id"],
                        "g_num" => $val["out_num"],
                        "g_price" => $val["g_price"],
                        "remark" => $val["remark"],
                        "value_id" => $val["value_id"],
                    );
                }

                if (count($warehouse_other_out_detail_entitys) > 0) {
                    $ok = $WarehouseOtherOutDetailModel->addAll($warehouse_other_out_detail_entitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增仓库退货子表信息失败");
                    }
                }
            }
        }

        //更新入库验货单信息
        $savedata = array();
        $savedata["s_in_status"] = $s_in_status;
        $savedata["ptime"] = $current_time;
        $savedata["padmin_id"] = $uid;
        $ok = $StoreInModel->where(" `s_in_id`={$s_in_id} ")->limit(1)->save($savedata);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新门店验货单信息失败");
        }

        $this->commit();
        return array("status" => 200, "msg" => "操作成功");
    }

    /****************************
     * 入库验收增加批次，包含类型有【4：采购】
     * 注意：1.寄售入库时候，当入库数量大于申请验收数量，要重新计算入库平均价
     */
    private function storeInWithWarehouseInout($uid, $maindata, $s_in_id)
    {
        $this->startTrans();
        $StoreInDetailModel = M("StoreInDetail");
        $StoreInStockModel = M("StoreInStock");
        $StoreInStockDetailModel = M("StoreInStockDetail");
        $GoodsStoreModel = M("GoodsStore");
        $StoreInModel = M("StoreIn");
        $StoreModel = M("Store");
        $WarehouseInoutModel = M("WarehouseInout");

        $store_id = $maindata["store_id2"];
        $shequ_id = 0;
        $store_data = $StoreModel->where(" `id`={$store_id} ")->limit(1)->select();
        if (is_null($store_data) || empty($store_data) || count($store_data) == 0) {
            return array("status" => 0, "msg" => "门店不存在");
        }
        $shequ_id = $store_data[0]["shequ_id"];

        $sql = "select SID.s_in_d_id,SID.s_in_id,SID.goods_id,SID.g_num,SID.in_num,SID.out_num,SID.g_price,SID.endtime,SID.remark,G.title as goods_name,SID.value_id ";
        $sql .= "from hii_store_in_detail SID ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "where SID.s_in_id={$s_in_id} ";
        $store_in_detail_data = $StoreInDetailModel->query($sql);

        $success_in_store_data = array();//成功入库商品
        $success_in_store_g_nums = 0;//成功入库数量总和
        $fail_in_store_data = array();//退货商品
        $fail_in_store_g_nums = 0;//退货数量总和
        $current_time = time();
        //收集入库退货信息
        foreach ($store_in_detail_data as $key => $val) {
            if ($maindata["s_in_type"] == 4 && ($val["in_num"] + $val["out_num"]) < $val["g_num"]) {
                return array("status" => 0, "msg" => "验收数量与退货数量之和不能少于提交数量");
            }
            if ($val["in_num"] > 0) {
                $g_price = $val["g_price"];
                if ($maindata["s_in_type"] == 4 && $val["in_num"] > $val["g_num"]) {
                    //采购入库，入库验收数量大于申请数量【有赠品】，分摊均价
                    $g_price = round(($g_price * $val["g_num"]) / $val["in_num"], 2);
                }
                $success_in_store_g_nums += $val["in_num"];
                $success_in_store_data[] = array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $g_price,
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"],
                );
            }
            if ($val["out_num"] > 0) {
                $fail_in_store_g_nums += $val["out_num"];
                $fail_in_store_data[] = array(
                    "s_in_d_id" => $val["s_in_d_id"],
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "in_num" => $val["in_num"],
                    "out_num" => $val["out_num"],
                    "g_price" => $val["g_price"],
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "goods_name" => $val["goods_name"],
                    "value_id" => $val["value_id"],
                );
            }
        }
        $s_in_status = null;
        if (count($success_in_store_data) > 0 && $success_in_store_g_nums == $maindata["g_nums"] && count($fail_in_store_data) == 0) {
            $s_in_status = 1;//全部入库
        }
        if (count($success_in_store_data) == 0 && count($fail_in_store_data) > 0 && $fail_in_store_g_nums == $maindata["g_nums"]) {
            $s_in_status = 2;//全部退货
        }
        if (count($success_in_store_data) > 0 && count($fail_in_store_data) > 0) {
            $s_in_status = 3;//部分入库，部分退货
        }
        if ($s_in_status == null) {
            return array("status" => 0, "msg" => "审核状态有误");
        }

        if (count($success_in_store_data) > 0) {
            //生成入库单数据
            $store_in_stock_entity = array();
            $s_in_s_sn = get_new_order_no("MS", "hii_store_in_stock", "s_in_s_sn");//门店收货单单号
            if (is_null($s_in_s_sn) || empty($s_in_s_sn)) {
                return array("status" => 0, "msg" => "门店入库单单号生成失败");
            }
            $store_in_stock_entity["s_in_s_sn"] = $s_in_s_sn;
            $store_in_stock_entity["s_in_s_status"] = 1;
            $store_in_stock_entity["s_in_s_type"] = $maindata["s_in_type"];
            $store_in_stock_entity["s_in_id"] = $maindata["s_in_id"];
            $store_in_stock_entity["ctime"] = $current_time;
            $store_in_stock_entity["admin_id"] = $uid;
            $store_in_stock_entity["ptime"] = $current_time;
            $store_in_stock_entity["padmin_id"] = $uid;
            $store_in_stock_entity["supply_id"] = $maindata["supply_id"];
            $store_in_stock_entity["warehouse_id"] = $maindata["warehouse_id"];
            $store_in_stock_entity["store_id1"] = $maindata["store_id1"];
            $store_in_stock_entity["store_id2"] = $maindata["store_id2"];
            $store_in_stock_entity["remark"] = $maindata["remark"];
            $store_in_stock_entity["g_type"] = count($success_in_store_data);
            $store_in_stock_entity["g_nums"] = $success_in_store_g_nums;

            $s_in_s_id = $StoreInStockModel->add($store_in_stock_entity);
            if ($s_in_s_id === false) {
                $this->rollback();
                return array("status" => 0, "msg" => "新增门店收货单主表信息失败");
            }

            //保存入库单子表信息
            $store_in_stock_detail_entitys = array();
            foreach ($success_in_store_data as $key => $val) {
                $store_in_stock_detail_entitys[] = array(
                    "s_in_s_id" => $s_in_s_id,
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["in_num"],
                    "g_price" => $val["g_price"],
                    "s_in_d_id" => $val["s_in_d_id"],
                    "endtime" => $val["endtime"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"],
                );
            }
            if (count($store_in_stock_detail_entitys) > 0) {
                $ok = $StoreInStockDetailModel->addAll($store_in_stock_detail_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "新增门店收货单子表信息失败");
                }
            }

            //修改库存
            foreach ($store_in_stock_detail_entitys as $key => $val) {
                $goods_store_data = $GoodsStoreModel->where(" `store_id`={$store_id} and `goods_id`={$val["goods_id"]} ")->limit(1)->select();
                if (is_null($goods_store_data) || empty($goods_store_data) || count($goods_store_data) == 0) {
                	$storeModel = D('Store');
                	$getInfo = $storeModel->field('shequ_id')->where(array('id'=> $store_id)) ->find();
                	$shequ_price = M('GoodsShequ')->field('price')->where(array('goods_id'=>$val['goods_id'],'shequ_id'=>$getInfo['shequ_id'],'status'=>1))->order('ctime desc')->find();
                    $savedata = array(
                        "goods_id" => $val["goods_id"],
                        "store_id" => $store_id,
                        "num" => $val["g_num"],
                    	"shequ_price" => empty($shequ_price) ? null: $shequ_price['price'],
                        "update_time" => $current_time
                    );
                    $ok = $GoodsStoreModel->add($savedata);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => 0, "msg" => "ID为【{$val["goods_id"]}】的商品新增库存失败");
                    }
                } else {
                    $savedata = array(
                        "num" => $val["g_num"] + $goods_store_data[0]["num"],
                        "update_time" => $current_time
                    );
                    $ok = $GoodsStoreModel->where(" id={$goods_store_data[0]["id"]} ")->limit(1)->save($savedata);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => 0, "msg" => "ID为【{$val["goods_id"]}】的商品更新库存失败");
                    }
                }
            }

            //增加批次
            $warehouse_inout_entitys = array();
            $store_in_stock_detail_entitys = $StoreInStockDetailModel->where(" s_in_s_id={$s_in_s_id} ")->select();
            foreach ($store_in_stock_detail_entitys as $key => $val) {
                $warehouse_inout_entitys[] = array(
                    "goods_id" => $val["goods_id"],
                    "innum" => $val["g_num"],
                    "inprice" => $val["g_price"],
                    "outnum" => 0,
                    "num" => $val["g_num"],
                    "ctime" => $current_time,
                    "ctype" => 2,
                    "endtime" => $val["endtime"],
                    "store_id" => $store_id,
                    "shequ_id" => $shequ_id,
                    "s_in_s_d_id" => $val["s_in_s_d_id"],
                    "value_id" => $val["value_id"],
                );
            }
            if (count($warehouse_inout_entitys) > 0) {
                $ok = $WarehouseInoutModel->addAll($warehouse_inout_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增入库批次信息失败");
                }
            }
        }

        if (count($fail_in_store_data) > 0) {
            //来自采购,生成采购退货单
            $PurchaseOutModel = M("PurchaseOut");
            $PurchaseOutDetailModel = M("PurchaseOutDetail");

            $g_type = 0;
            $g_nums = 0;
            foreach ($fail_in_store_data as $key => $val) {
                $g_type++;
                $g_nums += $val["out_num"];
            }

            //采购退货单主表信息
            $purchase_out_entity = array();
            $p_o_sn = get_new_order_no("CG", "hii_purchase_out", "p_o_sn");
            if (is_null($p_o_sn) || empty($p_o_sn)) {
                return array("status" => 0, "msg" => "采购退货单单号生成失败");
            }
            $purchase_out_entity["p_o_sn"] = $p_o_sn;
            $purchase_out_entity["p_o_status"] = 0;
            $purchase_out_entity["p_o_type"] = 0;//只来自采购
            $purchase_out_entity["p_id"] = $maindata["p_id"];
            $purchase_out_entity["ctime"] = $current_time;
            $purchase_out_entity["admin_id"] = $uid;
            $purchase_out_entity["supply_id"] = $maindata["supply_id"];
            $purchase_out_entity["store_id"] = $store_id;
            $purchase_out_entity["s_in_id"] = $maindata["s_in_id"];
            $purchase_out_entity["remark"] = $maindata["remark"];
            $purchase_out_entity["g_type"] = $g_type;
            $purchase_out_entity["g_nums"] = $g_nums;
            $p_o_id = $PurchaseOutModel->add($purchase_out_entity);
            if ($p_o_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增采购退货单主表信息失败");
            }
            //采购退货单子表信息
            $purchase_out_detail_entitys = array();
            foreach ($fail_in_store_data as $key => $val) {
                $purchase_out_detail_entitys[] = array(
                    "p_o_id" => $p_o_id,
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["out_num"],
                    "g_price" => $val["g_price"],
                    "s_in_d_id" => $val["s_in_d_id"],
                    "remark" => $val["remark"],
                    "value_id" => $val["value_id"],
                );
            }
            if (count($purchase_out_detail_entitys) > 0) {
                $ok = $PurchaseOutDetailModel->addAll($purchase_out_detail_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增采购退货单子表信息失败");
                }
            }
        }

        //更新入库验货单信息
        $savedata = array();
        $savedata["s_in_status"] = $s_in_status;
        $savedata["ptime"] = $current_time;
        $savedata["padmin_id"] = $uid;
        $ok = $StoreInModel->where(" `s_in_id`={$s_in_id} ")->limit(1)->save($savedata);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新门店验货单信息失败");
        }

        $this->commit();
        return array("status" => 200, "msg" => "操作成功");
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