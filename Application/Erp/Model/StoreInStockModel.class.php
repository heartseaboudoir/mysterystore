<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-14
 * Time: 17:37
 * 入库处理
 */

namespace Erp\Model;

use Think\Model;

class StoreInStockModel extends Model
{
    /*******************
     * 入库单审核
     * @param string $padmin_id 审核人员ID
     * @param mixed $store_id 门店ID
     * @param string $s_in_s_id 入库单ID
     * 逻辑：1.更新库存
     *       2.是否需要插入批次表
     *       3.更新入库单主表信息
     */
    public function check($padmin_id, $store_id, $s_in_s_id)
    {
        $this->startTrans();
        $StoreInStockModel = M("StoreInStock");
        $StoreInStockDetailModel = M("StoreInStockDetail");
        $GoodsStoreModel = M("GoodsStore");
        $InoutStockType = array(2, 4, 5);//加入入库批次表的来源 来源:0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
        $sql = "select s_in_s_id from hii_store_in_stock where s_in_s_id={$s_in_s_id} and store_id2={$store_id} and s_in_s_status=0 order by s_in_s_id limit 1  ";
        $datas = $StoreInStockModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "不存在该入库单");
        }
        $maindata = $datas[0];
        $details = $StoreInStockDetailModel->query("select s_in_s_d_id,goods_id,g_num,g_price,endtime,value_id from hii_store_in_stock_detail where s_in_s_id={$s_in_s_id} ");
        //增加库存
        foreach ($details as $key => $val) {
            $stockdatas = $GoodsStoreModel->query("select id,num from hii_goods_store where store_id={$store_id} and goods_id={$val["goods_id"]} limit 1 ");
            if (is_null($stockdatas) || empty($stockdatas) || count($stockdatas) == 0) {
                //新增
                $GoodsStoreEntity = array();
                $GoodsStoreEntity["goods_id"] = $val["goods_id"];
                $GoodsStoreEntity["store_id"] = $store_id;
                $GoodsStoreEntity["num"] = $val["g_num"];
                $GoodsStoreEntity["update_time"] = time();
                $id = $GoodsStoreModel->add($GoodsStoreEntity);
                if ($id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增库存信息失败");
                }
            } else {
                //更新
                $GoodsStoreEntity = array();
                $GoodsStoreEntity["num"] = $stockdatas[0]["num"] + $val["g_num"];
                $GoodsStoreEntity["update_time"] = time();
                $ok = $GoodsStoreModel->where(" id={$stockdatas[0]["id"]} ")->limit(1)->save($GoodsStoreEntity);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新库存数量失败");
                }
            }
        }

        //根据来源判断是否需要加入入库批次表【hii_warehouse_inout】
        if (in_array($maindata["s_in_s_type"], $InoutStockType)) {
            $WarehouseInoutModel = M("WarehouseInout");
            $StoreModel = M("Store");

            //查找门店属于那个社区shequ_id
            $storedatas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
            $shequ_id = 0;
            if (!is_null($storedatas) && !empty($storedatas) && count($storedatas) > 0) {
                $shequ_id = $storedatas[0]["shequ_id"];
            }

            foreach ($details as $key => $val) {

                $endtime = 0;
                if ($maindata["s_in_s_type"] == 2) {
                    $ctype = 1;
                } elseif ($maindata["s_in_s_type"] == 4) {
                    $ctype = 0;
                    $endtime = $val["endtime"];
                } elseif ($maindata["s_in_stype"] == 5) {
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
                $WarehouseInoutEntity["ctype"] = $ctype;
                $WarehouseInoutEntity["endtime"] = $endtime;
                $WarehouseInoutEntity["store_id"] = $store_id;
                $WarehouseInoutEntity["shequ_id"] = $shequ_id;
                $WarehouseInoutEntity["s_in_s_d_id"] = $val["s_in_s_d_id"];
                $WarehouseInoutEntity["value_id"] = $val["value_id"];
                $inout_id = $WarehouseInoutModel->add($WarehouseInoutEntity);
                if ($inout_id === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增入库批次信息失败");
                }
            }
        }
        //更新入库单信息
        $ptime = time();
        $ok = $StoreInStockModel->where(" s_in_s_id={$maindata["s_in_s_id"]} ")->limit(1)->save(array(
            "s_in_s_status" => 1, "padmin_id" => $padmin_id, "ptime" => $ptime
        ));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "入库单主表信息更新失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /***********************
     * 修改入库单信息
     * @param $eadmin_id
     * @param $store_id
     * @param $s_in_s_id
     * @param $info_json
     * @param $remark
     */
    public function update($eadmin_id, $store_id, $s_in_s_id, $info_json, $remark)
    {
        $this->startTrans();
        $StoreInStockModel = M("StoreInStock");
        $StoreInStockDetailModel = M("StoreInStockDetail");

        $datas = $StoreInStockModel->where(" s_in_s_id={$s_in_s_id} and store_id2={$store_id} and s_in_s_status=0 ")->order(" s_in_s_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无权修改该入库单");
        }

        $ok = $StoreInStockModel->where(" s_in_s_id={$s_in_s_id} ")->order(" s_in_s_id desc ")->limit(1)->save(array("remark" => $remark));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新入库单主表信息失败");
        }

        $UpdateStoreInStockDetailEntitys = array();
        foreach ($info_json as $key => $val) {
            if (!is_null($val["endtime"]) && !empty($val["endtime"])) {
                $endtime = strtotime($val["endtime"]);
                //\Think\Log::record($StoreInStockDetailModel->_sql());
                $UpdateStoreInStockDetailEntitys[] = array("s_in_s_d_id" => $val["s_in_s_d_id"], "endtime" => $endtime);
            }
        }

        $result = $this->saveAll("hii_store_in_stock_detail", $UpdateStoreInStockDetailEntitys, "s_in_s_d_id", $StoreInStockDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新入库单子表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /***********************
     * 批量更新数据
     * @param $tableName 表明
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