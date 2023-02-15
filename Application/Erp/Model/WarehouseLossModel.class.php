<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-28
 * Time: 16:03
 */

namespace Erp\Model;

use Think\Model;

class WarehouseLossModel extends Model
{

    /**********************
     * 退货单审核【已弃用】
     * @param string $padmin_id 审核人员ID
     * @param string $warehouse_id 当前仓库ID
     * @param mixed $w_o_out_id 退货单ID
     */
    public function check($padmin_id, $warehouse_id, $w_o_out_id)
    {
        $this->startTrans();
        $WarehouseOtherOutModel = M("WarehouseOtherOut");

        $datas = $WarehouseOtherOutModel->where(" w_o_out_id={$w_o_out_id} and warehouse_id={$warehouse_id} ")->order(" w_o_out_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该退货单");
        }

        $data = $datas[0];

        //入库验货单退货，直接更加发货仓库对应商品库存
        if ($data["w_o_out_status"] == 0) {
            $WarehouseStockModel = M("WarehouseStock");
            $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");
            $fahuo_warehouse_id = $data["warehouse_id2"];//发货仓库ID
            $details = $WarehouseOtherOutDetailModel->where(" w_o_out_id={$w_o_out_id} ")->order(" w_o_out_d_id desc ")->select();
            foreach ($details as $key => $val) {
                $tmp = $WarehouseStockModel->where(" w_id={$fahuo_warehouse_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                    $WarehouseStockEntity = array();
                    $WarehouseStockEntity["w_id"] = $fahuo_warehouse_id;
                    $WarehouseStockEntity["goods_id"] = $val["goods_id"];
                    $WarehouseStockEntity["num"] = $val["g_num"];
                    $ok = $WarehouseStockModel->add($WarehouseStockEntity);
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "新增发货仓库库存信息失败");
                    }
                } else {
                    $num = $val["g_num"] + $tmp[0]["num"];
                    $ok = $WarehouseStockModel->where(" id={$tmp[0]["id"]} ")->limit(1)->save(array("num" => $num));
                    if ($ok === false) {
                        $this->rollback();
                        return array("status" => "0", "msg" => "更新发货仓库库存信息失败");
                    }
                }
            }
        }

        //更新退货单信息
        $ptime = time();
        $ok = $WarehouseOtherOutModel->where(" w_o_out_id={$w_o_out_id} ")->order(" w_o_out_id desc ")->limit(1)->save(array(
                "padmin_id" => $padmin_id,
                "ptime" => $ptime,
                "w_o_out_status" => 1)
        );

        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新退货单信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /************
     * 退货单审核
     * @param string $padmin_id 审核人员ID
     * @param string $warehouse_id 当前仓库ID
     * @param mixed $w_o_out_id 退货单ID
     */
    public function pass($padmin_id, $warehouse_id, $w_o_out_id)
    {
        $this->startTrans();
        $WarehouseOtherOutModel = M("WarehouseOtherOut");
        $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");
        $WarehouseStockModel = M("WarehouseStock");

        $ptime = time();

        $datas = $WarehouseOtherOutModel->where(" warehouse_id2={$warehouse_id} and w_o_out_id={$w_o_out_id} and w_o_out_status=0 ")->order(" w_o_out_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该退货单");
        }

        /*******************新增仓库自身库存 start************************************************/
        $details = $WarehouseOtherOutDetailModel->where(" w_o_out_id={$w_o_out_id} ")->order(" w_o_out_d_id desc ")->select();
        $NewWarehouseStockEntitys = array();
        $UpdateWarehouseStockEntitys = array();
        foreach ($details as $key => $val) {
            $tmp = $WarehouseStockModel->where(" w_id={$warehouse_id} and goods_id={$val["goods_id"]} and value_id={$val['value_id']} ")->limit(1)->select();
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                $savedata = array();
                $savedata["w_id"] = $warehouse_id;
                $savedata["goods_id"] = $val["goods_id"];
                $savedata["num"] = $val["g_num"];
                $ok = $WarehouseStockModel->add($savedata);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增仓库库存信息失败");
                }
            } else {
                $newnum = $tmp[0]["num"] + $val["g_num"];
                $savedata = array("num" => $newnum);
                $ok = $WarehouseStockModel->where(" `id`={$tmp[0]["id"]} ")->limit(1)->save($savedata);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新仓库库存信息失败");
                }
            }
        }
        $ok = $WarehouseOtherOutModel->where(" w_o_out_id={$w_o_out_id} ")->order(" w_o_out_id desc ")->limit(1)->save(array("ptime" => $ptime, "padmin_id" => $padmin_id, "w_o_out_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新仓库退货单主表信息失败");
        }
        /*******************新增仓库自身库存 end************************************************/

        /*******************门店向仓库退货，回写hii_store_other_out start******************************************************************/
        if ($datas[0]["w_o_out_type"] == 4) {
            $StoreOtherOutModel = M("StoreOtherOut");
            $s_o_out_id = $datas[0]["s_o_out_id"];
            $ok = $StoreOtherOutModel->where(" s_o_out_id={$s_o_out_id} ")->order(" s_o_out_id desc ")->limit(1)->save(array("s_o_out_status" => 1, "ptime" => $ptime, "padmin_id" => $padmin_id));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店退货单信息失败");
            }
        }
        /*******************门店向仓库退货，回写hii_store_other_out end******************************************************************/

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