<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-20
 * Time: 10:50
 * 仓库验货
 */

namespace Erp\Model;

use Think\Model;

class ShipmentRequestProcModel extends Model
{

    /***********************
     * 更新明细表有货数量，缺货数量
     * 更新主表的备注
     * @param $w_out_id 主表ID
     * @param $remark 主表备注
     * @param $info_array 明细表有货数量和缺货数量 格式：Array([0]=>Array("w_out_d_id"=>1,"in_num"=>20,"out_num"=>2),[1]=>Array("w_out_d_id"=>2,"in_num"=>20,"out_num"=>2))
     */
    public function updateWarehouseOutDetailInfo($w_out_id, $remark, $info_array)
    {
        $this->startTrans();
        $WarehouseOutModel = M("WarehouseOut");
        $ok = $WarehouseOutModel->where(" w_out_id={$w_out_id} ")->limit(1)->save(array("remark" => $remark));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新主表失败");
        }
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        foreach ($info_array as $key => $val) {
            $ok = $WarehouseOutDetailModel->where(" w_out_d_id={$val["w_out_d_id"]} ")->limit(1)->save(array(
                "in_num" => $val["in_num"],
                "out_num" => $val["out_num"]
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新明细表失败");
            }
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /****************
     * 审核出库，成功出库部分往出库表【hii_warehouse_out_stock】写入数据
     * @param $w_out_id 出库验收表ID
     * @admin_id 当前操作人员ID
     */
    public function checkForWarehouseOut($w_out_id, $admin_id)
    {
        $this->startTrans();
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        $WarehouseOutStockModel = M("WarehouseOutStock");
        $WarehouseOutStockDetailModel = M("WarehouseOutStockDetail");
        $WarehouseOutEntity = $WarehouseOutModel->query(" select * from hii_warehouse_out where w_out_id={$w_out_id} limit 1 order by w_out_id desc ");
        //整理hii_warehouse_out_stock实体信息
        $w_out_s_type = 4;//来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它
        switch ($WarehouseOutEntity["w_out_type"]) {
            case 0: {
                $w_out_s_type = 0;
            };
                break;
            case 1: {
                $w_out_s_type = 1;
            };
                break;
            case 2: {
                $w_out_s_type = 3;
            };
                break;
        }
        $WarehouseOutStockEntity = array();
        $WarehouseOutStockEntity["w_out_s_sn"] = get_new_order_no("CK", "hii_warehouse_out_stock", "w_out_s_sn");
        $WarehouseOutStockEntity["w_out_s_status"] = 0;
        $WarehouseOutStockEntity["w_out_s_type"] = $w_out_s_type;
        $WarehouseOutStockEntity["w_out_id"] = $w_out_id;
        $WarehouseOutStockEntity["ctime"] = time();
        $WarehouseOutStockEntity["admin_id"] = $admin_id;
        $WarehouseOutStockEntity["store_id"] = $WarehouseOutEntity["store_id"];
        $WarehouseOutStockEntity["warehouse_id1"] = $WarehouseOutEntity["warehouse_id1"];
        $WarehouseOutStockEntity["warehouse_id2"] = $WarehouseOutEntity["warehouse_id2"];
        $WarehouseOutStockEntity["remark"] = $WarehouseOutEntity["remark"];
        $w_out_s_id = $WarehouseOutStockModel->add($WarehouseOutStockEntity);
        if ($w_out_s_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "主表新增失败");
        }
        $rejectNum = 0;//完全拒绝商品种类
        $WarehouseOutDetailList = $WarehouseOutDetailModel->query(" select * from hii_warehouse_out_detail where w_out_id={$w_out_id} and in_num>0 ");
        foreach ($WarehouseOutDetailList as $key => $val) {
            if ($val["out_num"] == $val["g_num"]) {
                $rejectNum++;
            }
            $WarehouseOutStockDetailEntity = array();
            $WarehouseOutStockDetailEntity["w_out_s_id"] = $w_out_s_id;
            $WarehouseOutStockDetailEntity["goods_id"] = $val["goods_id"];
            $WarehouseOutStockDetailEntity["g_num"] = $val["in_num"];
            $WarehouseOutStockDetailEntity["g_price"] = $val["g_price"];
            $WarehouseOutStockDetailEntity["w_out_d_id"] = $val["w_out_d_id"];
            $ok = $WarehouseOutStockDetailModel->add($WarehouseOutStockDetailEntity);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "明细表新增失败");
            }
        }
        //更新出库验货单状态
        $w_out_status = 1;
        if ($rejectNum == count($WarehouseOutDetailList)) {
            $w_out_status = 2;
        }
        if ($rejectNum > 0 && $rejectNum < count($WarehouseOutDetailList)) {
            $w_out_status = 3;
        }
        $ok = $WarehouseOutModel->where(" w_out_id={$w_out_id} ")->limit(1)->save(array(
            "w_out_status" => $w_out_status,
            "padmin_id" => $admin_id,
            "ptime" => time()
        ));
        if ($ok === false) {
            return array("status" => "0", "msg" => "出库验货单主表状态更新失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /****************
     * 全部缺货,只修该出库验货单的主表状态和明细表的缺货数量
     * @param $w_out_id 出库验收表ID
     */
    public function checkForAllOutOfStock($w_out_id, $admin_id)
    {
        $this->startTrans();
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");
        //更新主表状态
        $w_out_status = 2;
        $ok = $WarehouseOutModel->where(" w_out_id={$w_out_id} ")->limit(1)->save(array(
            "w_out_status" => $w_out_status,
            "padmin_id" => $admin_id,
            "ptime" => time()
        ));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新主表状态失败");
        }
        $WarehouseOutDetailList = $WarehouseOutDetailModel->query(" select * from hii_warehouse_out_detail where w_out_id={$w_out_id} ");
        foreach ($WarehouseOutDetailList as $key => $val) {
            $ok = $WarehouseOutDetailModel->where(" w_out_d_id={$val["w_out_d_id"]} ")->limit(1)->save(array(
                "in_num" => 0,
                "out_num" => $val["g_num"]
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新明细表缺货数量失败");
            }
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


}