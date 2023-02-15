<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-05
 * Time: 11:32
 * 门店出库处理
 */

namespace Erp\Model;

use Think\Model;

class StoreOutStockModel extends Model
{

    /***************
     * 出库单审核
     * @param string $s_out_s_id 出库单ID
     * @param mixed $padmin_id 审核人ID
     * @param mixed $store_id 当前所选门店ID
     * 逻辑：先判断库存（库存不足不能审核）
     * 日期：2017-12-06
     */
    public function check($s_out_s_id, $padmin_id, $store_id)
    {
        $this->startTrans();
        $StoreOutStockModel = M("StoreOutStock");
        $StoreStockDetailModel = M("StoreStockDetail");
        $GoodsStoreModel = M("GoodsStore");

        $sql = "select * from hii_store_out_stock  ";
        $sql .= "where s_out_s_id={$s_out_s_id} and store_id2={$store_id} and s_out_s_status=0 order by s_out_s_id desc limit 1 ";
        $datas = $StoreOutStockModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "提交数据有误");
        }
        //`s_out_s_type` int(1) DEFAULT '0' COMMENT '来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它',
        $s_out_s_type = $datas[0]["s_out_s_type"];
        $app_store_id = $datas[0]["store_id1"];//申请门店ID
        $deliver_store_id = $datas[0]["store_id2"];//发货门店ID或盘亏门店ID
        if ($s_out_s_type == 1) {
            /***************************
             * 门店与门店调拨出库逻辑：
             * 1.先判断库存，库存充足减库存
             * 2.生成申请门店的入库验货单
             * 3.修改hii_store_out_stock的状态
             * 4.修改hii_store_to_store,hii_store_to_store_detail的相关信息
             *****************************/
            $isUpdateRollback = true;//是否更新hii_store_to_store，hii_store_to_store_detail
            //判断库存及减少库存量
            $details = $StoreStockDetailModel->query("select * from hii_store_stock_detail where s_out_s_id={$s_out_s_id} order by s_out_s_d_id desc ");
            $g_type = 0;
            $g_nums = 0;
            foreach ($details as $key => $val) {
                $sql = "select id,ifnull(num,0) as stock_num from hii_goods_store where goods_id={$val["goods_id"]} and store_id={$deliver_store_id} order by id desc limit 1 ";
                $datas = $GoodsStoreModel->query($sql);
                if (is_null($datas) || empty($datas) || count($datas) == 0 || $datas[0]["stock_num"] < $val["g_num"]) {
                    //库存不足不能审核通过
                    $this->rollback();
                    return array("status" => "0", "msg" => "库存不足，审核失败");
                } else {
                    //减少库存
                    $num = $datas[0]["stock_num"] - $val["g_num"];
                    $ok = $GoodsStoreModel->where(" id={$datas[0]["id"]} ")->limit(1)->save(array("num" => $num));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "库存修改失败");
                    }
                    $g_type++;
                    $g_nums += $val["g_num"];
                }
            }
            //生成申请门店的入库验货单【hii_store_in,hii_store_in_detail】
            $StoreInModel = M("StoreIn");
            $StoreInDetailModel = M("StoreInDetail");
            //整理hii_store_in实体数据
            $StoreInEntity = array();
            $StoreInEntity["s_in_sn"] = get_new_order_no("SI", "hii_store_in", "s_in_sn");
            $StoreInEntity["s_in_status"] = 0;
            $StoreInEntity["s_in_type"] = 1;
            $StoreInEntity["s_out_s_id"] = $s_out_s_id;
            $StoreInEntity["ctime"] = time();
            $StoreInEntity["admin_id"] = $padmin_id;
            $StoreInEntity["store_id1"] = $deliver_store_id;//发货门店ID
            $StoreInEntity["store_id2"] = $app_store_id; //收货门店ID
            $StoreInEntity["g_type"] = $g_type;
            $StoreInEntity["g_nums"] = $g_nums;
            $s_in_id = $StoreInModel->add($StoreInEntity);
            if ($s_in_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店入库单失败");
            }
            $StoreToStoreModel = M("StoreToStore");
            $StoreToStoreDetailModel = M("StoreToStoreDetail");
            $StoreOutModel = M("StoreOut");
            $StoreOutDetailModel = M("StoreOutDetail");

            foreach ($details as $key => $val) {
                $StoreInDetailEntity = array();
                $StoreInDetailEntity["s_in_id"] = $s_in_id;
                $StoreInDetailEntity["goods_id"] = $val["goods_id"];
                $StoreInDetailEntity["g_num"] = $val["g_num"];
                $StoreInDetailEntity["g_price"] = $val["g_price"];
                $StoreInDetailEntity["s_out_s_d_id"] = $val["s_out_s_d_id"];
                $StoreInDetailEntity["value_id"] = $val["value_id"];
                $s_in_d_id = $StoreInDetailModel->add($StoreInDetailEntity);
                if ($s_in_d_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店入库单子表失败");
                }
                if ($isUpdateRollback) {
                    //修改hii_store_to_store，hii_store_to_store_detail的信息
                    $sql = "select SOD.s_r_d_id,STSD.s_t_s_id from hii_store_out_detail SOD ";
                    $sql .= "inner join hii_store_to_store_detail STSD on STSD.s_t_s_d_id=SOD.s_r_d_id ";
                    $sql .= "where SOD.s_out_d_id = (select s_out_d_id from hii_store_stock_detail where s_out_s_d_id={$val["s_out_s_d_id"]} limit 1 ) ";
                    $sql .= "order by SOD.s_out_d_id desc limit 1 ";
                    $tmp = $StoreOutDetailModel->query($sql);
                    if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "审核失败，调拨申请单信息不存在");
                    }
                    $s_t_s_id = $tmp[0]["s_t_s_id"];
                    $s_t_s_d_id = $tmp[0]["s_r_d_id"];
                    //更新调拨子表发货数量
                    $ok = $StoreToStoreDetailModel->where(" s_t_s_d_id={$s_t_s_d_id} ")->limit(1)->save(array(
                        "is_pass" => 2,
                        "pass_num" => $val["g_num"]
                    ));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新调拨申请单子表出货数量失败");
                    }
                    $store_to_store_details = $StoreToStoreDetailModel->query("select * from hii_store_to_store_request where s_t_s_id={$s_t_s_id} order by s_t_s_d_id DESC  ");
                    $g_type = count($store_to_store_details);
                    $PassNum = 0;//is_pass的总和
                    $CheckNum = 0;//已审核数量
                    $SomePass = false;//是否有些通过审核
                    $s_t_s_status = 0;
                    foreach ($store_to_store_details as $k => $v) {
                        $PassNum += $v["is_pass"];
                        if ($v["is_pass"] > 0) {
                            $CheckNum++;
                        }
                        if ($v["is_pass"] == 2) {
                            $SomePass = true;
                        }
                    }
                    //判断审核状态
                    if ($CheckNum > 0 && $SomePass) {
                        $s_t_s_status = 2;
                    }
                    if ($CheckNum == $g_type) {
                        $s_t_s_status = 2;
                    }
                    if ($PassNum == 0) {
                        $s_t_s_status = 0;
                    } elseif ($PassNum == $g_type) {
                        $s_t_s_status = 4;
                    } elseif ($PassNum == ($g_type * 2)) {
                        $s_t_s_status = 3;
                    }
                    $ok = $StoreToStoreModel->where(" s_t_s_id={$s_t_s_id} ")->limit(1)->save(array("s_t_s_status" => $s_t_s_status));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "调拨申请主表状态修改失败");
                    }
                }
            }
            //修改hii_store_out_stock状态
            $ptime = time();
            $ok = $StoreOutStockModel->where(" s_out_s_id={$s_out_s_id} ")->limit(1)->save(array(
                "s_out_s_status" => 1,
                "padmin_id" => $padmin_id,
                "ptime" => $ptime
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新出库单状态失败");
            }
            $this->commit();
            return array("status" => "200", "msg" => "操作成功");
        } elseif ($s_out_s_type == 3) {
            /*******************
             * 盘亏【库存数量比实际盘点数量多】出库逻辑：
             * 1.减库存
             * 2.减批次表【hii_warehouse_inout】
             *        2.1 先判断当前门店批次是否够减
             *        2.2 当前门店不够减的话再从仓库减
             * 3.更新hii_store_out_stock的审核信息
             ********************/
            $details = $StoreStockDetailModel->query("select * from hii_store_stock_detail where s_out_s_id={$s_out_s_id} order by s_out_s_d_id desc ");
            foreach ($details as $key => $val) {
                $sql = "select id,ifnull(num,0) as stock_num from hii_goods_store where goods_id={$val["goods_id"]} and store_id={$deliver_store_id} order by id desc limit 1 ";
                $datas = $GoodsStoreModel->query($sql);
                if (is_null($datas) || empty($datas) || count($datas) == 0 || $datas[0]["stock_num"] < $val["g_num"]) {
                    //库存不足不能审核通过
                    $this->rollback();
                    return array("status" => "0", "msg" => "库存不足，审核失败");
                } else {
                    //减少库存
                    $num = $datas[0]["stock_num"] - $val["g_num"];
                    $ok = $GoodsStoreModel->where(" id={$datas[0]["id"]} ")->limit(1)->save(array("num" => $num));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "库存修改失败");
                    }
                }
            }
            /**********************
             * 减批次：a.判断当前门店批次是否够减
             *         b.当门店批次不够减的时候，往仓库批次减
             * 扣减逻辑：先进先扣
             **********************/
            $WarehouseInoutModel = M("WarehouseInout");
            $StoreRequestModel = M("StoreRequest");
            $WarehouseOutStockModel = M("WarehouseOutStock");
            foreach ($details as $key => $val) {
                $goods_id = $val["goods_id"];
                $g_num = $val["g_num"];//盘亏数量
                //先扣门店批次表数量
                //$store_pc_input门店入库批次
                $store_pc_input = $WarehouseInoutModel->query("select * from hii_warehouse_inout where goods_id={$goods_id} and store_id={$deliver_store_id} order by inout_id asc ");
                foreach ($store_pc_input as $k => $v) {
                    if ($g_num > 0) {
                        $new_innum = $v["innum"];
                        if ($v["innum"] >= $g_num) {
                            $g_num = 0;
                            $new_innum = $v["innum"] - $g_num;
                        } else {
                            $g_num = $g_num - $v["innum"];
                            $new_innum = 0;
                        }
                        $ok = $WarehouseInoutModel->where(" inout_id={$v["inout_id"]} ")->limit(1)->save(array(
                            "innum" => $new_innum
                        ));
                        if ($ok === false) {
                            $this->rollback();
                            return array("status" => "0", "msg" => "扣减门店批次数量失败");
                        }
                    } else {
                        break;
                    }
                }
                //当门店批次扣减还剩余的话，对仓库批次进行扣减
                if ($g_num > 0) {
                    //根据门店向仓库发货申请查找从那些仓库取货,直接搜索hii_store_request,hii_store_request_detail对应pass_num大于0的记录
                    $sql = "select SR.warehouse_id ";
                    $sql .= "from hii_store_request_detail SRD ";
                    $sql .= "inner join hii_store_request SR on SR.s_r_id=SRD.s_r_id ";
                    $sql .= "where SR.store_id={$deliver_store_id} and SRD.goods_id={$goods_id} and SRD.pass_num>0 ";
                    $sql .= "group by SR.warehouse_id ";
                    $Warehouses = $StoreRequestModel->query($sql);
                    $in_warehouses_str = "";
                    foreach ($Warehouses as $k => $v) {
                        $in_warehouses_str .= $val["warehouse_id"] . ",";
                    }
                    $in_warehouses_str = !empty($in_warehouses_str) ? substr($in_warehouses_str, 0, strlen($in_warehouses_str) - 1) : $in_warehouses_str;
                    //查找相关入库批次数据进行扣减
                    $sql = " select * from hii_warehouse_inout where goods_id={$goods_id} and warehouse_id in ({$in_warehouses_str}) order by inout_id asc  ";
                    $WarehouseInoutDatas = $WarehouseInoutModel->query($sql);
                    foreach ($WarehouseInoutDatas as $k => $v) {
                        if ($g_num > 0) {
                            $new_innum = $v["innum"];
                            if ($v["innum"] >= $g_num) {
                                $g_num = 0;
                                $new_innum = $v["innum"] - $g_num;
                            } else {
                                $g_num = $g_num - $v["innum"];
                                $new_innum = 0;
                            }
                            $ok = $WarehouseInoutModel->where(" inout_id={$v["inout_id"]} ")->limit(1)->save(array(
                                "innum" => $new_innum
                            ));
                            if ($ok === false) {
                                $this->rollback();
                                return array("status" => "0", "msg" => "扣减仓库批次数量失败");
                            }
                        } else {
                            break;
                        }
                    }
                }
                if ($g_num > 0) {
                    //门店，仓库都不够扣减,待处理
                }
            }
            //修改hii_store_out_stock状态
            $ptime = time();
            $ok = $StoreOutStockModel->where(" s_out_s_id={$s_out_s_id} ")->limit(1)->save(array(
                "s_out_s_status" => 1,
                "padmin_id" => $padmin_id,
                "ptime" => $ptime
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新出库单状态失败");
            }
            $this->commit();
            return array("status" => "200", "msg" => "操作成功");
        }
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