<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-28
 * Time: 10:17
 * 退货处理
 */

namespace Erp\Model;

use Think\Model;

class StoreLossModel extends Model
{

    /******************************
     * 审核【旧，已废弃】
     * @param string padmin_id
     * @param string $store_id
     * @param mixed $s_o_out_id
     * 逻辑：当s_o_out_type=0或s_o_out_type=1时候，直接加发货门店/仓库对应商品的库存
     */
    public function check($padmin_id, $store_id, $s_o_out_id)
    {
        $this->startTrans();
        $StoreOtherOutModel = M("StoreOtherOut");
        $StoreOtherOutDetailModel = M("StoreOtherOutDetail");

        $datas = $StoreOtherOutModel->where(" `s_o_out_id`={$s_o_out_id} and `store_id2`={$store_id} and `s_o_out_status`=0 ")->order(" s_o_out_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该退货单");
        }
        $data = $datas[0];
        if ($data["s_o_out_type"] == 0 || $data["s_o_out_type"] == 1) {
            /*************************************************
             * 0：仓库发货验货退货
             * 1：门店调拨验货退货
             *********************************************************/
            $details = $StoreOtherOutDetailModel->where(" `s_o_out_id`={$s_o_out_id} ")->select();
            if ($data["s_o_out_type"] == 0) {
                //仓库发货
                $fahuo_warehouse_id = $data["warehouse_id"];
                $WarehouseStockModel = M("WarehouseStock");

                $NewWarehouseStockEntitys = array();
                $UpdateWarehouseStockEntitys = array();
                foreach ($details as $key => $val) {
                    $tmp = $WarehouseStockModel->where(" `w_id`={$fahuo_warehouse_id} and `goods_id`={$val["goods_id"]} ")->limit(1)->select();
                    if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                        //新增
                        $WarehouseStockEntity = array();
                        $WarehouseStockEntity["w_id"] = $fahuo_warehouse_id;
                        $WarehouseStockEntity["goods_id"] = $val["goods_id"];
                        $WarehouseStockEntity["num"] = $val["g_num"];
                        $NewWarehouseStockEntitys[] = $WarehouseStockEntity;
                    } else {
                        //更新
                        $num = $tmp[0]["num"] + $val["g_num"];
                        $UpdateWarehouseStockEntitys[] = array("id" => $tmp[0]["id"], "num" => $num);
                    }
                }

                if (count($NewWarehouseStockEntitys) > 0) {
                    $ok = $WarehouseStockModel->addAll($NewWarehouseStockEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增发货仓库库存信息失败");
                    }
                }

                $result = $this->saveAll("hii_warehouse_stock", $UpdateWarehouseStockEntitys, "id", $WarehouseStockModel);
                if ($result["status"] == "0") {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新发货仓库库存信息失败");
                }

                //写入仓库被退货单
                $WarehouseOtherOutModel = M("WarehouseOtherOut");
                $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");

                $WarehouseOtherOutEntity = array();
                $WarehouseOtherOutEntity["w_o_out_sn"] = get_new_order_no("MB", "hii_warehouse_other_out", "w_o_out_sn");
                $WarehouseOtherOutEntity["w_o_out_status"] = 1;
                $WarehouseOtherOutEntity["w_o_out_type"] = 4;
                $WarehouseOtherOutEntity["ctime"] = time();
                $WarehouseOtherOutEntity["admin_id"] = $padmin_id;
                $WarehouseOtherOutEntity["ptime"] = time();
                $WarehouseOtherOutEntity["padmin_id"] = $padmin_id;
                $WarehouseOtherOutEntity["store_id"] = $store_id;
                $WarehouseOtherOutEntity["warehouse_id2"] = $data["warehouse_id"];
                $WarehouseOtherOutEntity["remark"] = $data["remark"];
                $WarehouseOtherOutEntity["g_type"] = $data["g_type"];
                $WarehouseOtherOutEntity["g_nums"] = $data["g_nums"];
                $w_o_out_id = $WarehouseOtherOutModel->add($WarehouseOtherOutEntity);
                if ($w_o_out_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库退货主表信息失败");
                }
                $NewWarehouseOtherOutDetailEntitys = array();
                foreach ($details as $key => $val) {
                    $WarehouseOtherOutDetailEntity = array();
                    $WarehouseOtherOutDetailEntity["w_o_out_id"] = $w_o_out_id;
                    $WarehouseOtherOutDetailEntity["goods_id"] = $val["goods_id"];
                    $WarehouseOtherOutDetailEntity["g_num"] = $val["g_num"];
                    $WarehouseOtherOutDetailEntity["g_price"] = $val["g_price"];
                    $NewWarehouseOtherOutDetailEntitys[] = $WarehouseOtherOutDetailEntity;
                }
                if (count($NewWarehouseOtherOutDetailEntitys) > 0) {
                    $ok = $WarehouseOtherOutDetailModel->addAll($NewWarehouseOtherOutDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增仓库退货子表信息失败");
                    }
                }
            } elseif ($data["s_o_out_type"] == 1) {
                //门店调拨
                $fahuo_store_id = $data["store_id1"];//发货门店ID
                $GoodsStoreModel = M("GoodsStore");

                $NewGoodsStoreEntitys = array();
                $UpdateGoodsStoreEntitys = array();
                foreach ($details as $key => $val) {
                    $tmp = $GoodsStoreModel->where(" `store_id`={$fahuo_store_id} and `goods_id`={$val["goods_id"]} ")->limit(1)->select();
                    if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                        //新增
                        $GoodsStoreEntity = array();
                        $GoodsStoreEntity["store_id"] = $fahuo_store_id;
                        $GoodsStoreEntity["num"] = $val["g_num"];
                        $GoodsStoreEntity["update_time"] = time();
                        $NewGoodsStoreEntitys[] = $GoodsStoreEntity;
                    } else {
                        //更新库存数量
                        $num = $val["g_num"] + $tmp[0]["num"];
                        $UpdateGoodsStoreEntitys[] = array("id" => $tmp[0]["id"], "num" => $num);
                    }
                }

                if (count($NewGoodsStoreEntitys) > 0) {
                    $ok = $GoodsStoreModel->addAll($NewGoodsStoreEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增发货门店库存信息失败");
                    }
                }

                $result = $this->saveAll("hii_goods_store", $UpdateGoodsStoreEntitys, "id", $GoodsStoreModel);
                if ($result["status"] == "0") {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新发货门店库存信息失败");
                }

            }
        }

        //更新退货单主表信息
        $ptime = time();
        $ok = $StoreOtherOutModel->where(" `s_o_out_id`={$s_o_out_id} ")->limit(1)->save(array("padmin_id" => $padmin_id, "ptime" => $ptime, "s_o_out_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新退货单主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /********************************
     * 审核被退货单【新】
     * @param $padmin_id
     * @param $store_id
     * @param $s_o_out_id
     * 注意：发货门店被退货后审核退货申请
     */
    public function pass($padmin_id, $store_id, $s_o_out_id)
    {
        $this->startTrans();
        $StoreOtherOutModel = M("StoreOtherOut");
        $StoreOtherOutDetailModel = M("StoreOtherOutDetail");
        $GoodsStoreModel = M("GoodsStore");

        $datas = $StoreOtherOutModel->where(" store_id1={$store_id} and s_o_out_status=0 and s_o_out_type in (0,1,5) ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该退货单");
        }

        $details = $StoreOtherOutDetailModel->where(" s_o_out_id={$s_o_out_id} ")->order(" s_o_out_d_id desc ")->select();

        foreach ($details as $key => $val) {
            $tmp = $GoodsStoreModel->where(" `store_id`={$store_id} and `goods_id`={$val["goods_id"]} ")->limit(1)->select();
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                //新增
                $GoodsStoreEntity = array();
                $GoodsStoreEntity["store_id"] = $store_id;
                $GoodsStoreEntity["num"] = $val["g_num"];
                $GoodsStoreEntity["update_time"] = time();
                $ok = $GoodsStoreModel->add($GoodsStoreEntity);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店库存信息失败");
                }
            } else {
                //更新库存数量
                $num = $val["g_num"] + $tmp[0]["num"];
                $savedata = array("id" => $tmp[0]["id"], "num" => $num);
                $ok = $GoodsStoreModel->where(" `id`={$tmp[0]["id"]} ")->limit(1)->save($savedata);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新门店库存信息失败");
                }
            }
        }

        //更新退货单主表信息
        $ptime = time();
        $ok = $StoreOtherOutModel->where(" `s_o_out_id`={$s_o_out_id} ")->limit(1)->save(array("padmin_id" => $padmin_id, "ptime" => $ptime, "s_o_out_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新退货单主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "审核成功");
    }


    /****************************
     * 批量更新数据
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