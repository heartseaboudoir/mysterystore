<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-11
 * Time: 18:17
 * 仓库直接发货到门店申请
 */

namespace Erp\Model;

use Think\Model;

class DirectToStoreModel extends Model
{

    /********************
     * 临时申请单提交
     * @param $admin_id 管理人员ID
     * @param $store_id 收货门店ID
     * @param $warehouse_id 发货仓库ID
     * @param $remark 备注
     */
    public function submitRequestTemp($admin_id, $store_id, $warehouse_id, $remark)
    {
        $this->startTrans();
        $RequestTempModel = M("RequestTemp");
        $WarehouseOutModel = M("WarehouseOut");
        $WarehouseOutDetailModel = M("WarehouseOutDetail");

        $temp_type = 11;
        $g_type = 0;
        $g_nums = 0;

        $datas = $RequestTempModel->where(" admin_id={$admin_id} and temp_type={$temp_type} and status=0 ")->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "临时申请单无数据，无法提交");
        }

        //仓库发货验货单主表
        $WarehouseOutEntity = array();
        $WarehouseOutEntity["w_out_sn"] = get_new_order_no("CY", "hii_warehouse_out", "w_out_sn");
        $WarehouseOutEntity["w_out_status"] = 0;
        $WarehouseOutEntity["w_out_type"] = 3;
        $WarehouseOutEntity["ctime"] = time();
        $WarehouseOutEntity["admin_id"] = $admin_id;
        $WarehouseOutEntity["store_id"] = $store_id;
        $WarehouseOutEntity["warehouse_id2"] = $warehouse_id;
        $WarehouseOutEntity["remark"] = $remark;
        $WarehouseOutEntity["g_type"] = $g_type;
        $WarehouseOutEntity["g_nums"] = $g_nums;
        $w_out_id = $WarehouseOutModel->add($WarehouseOutEntity);
        if ($w_out_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增仓库出库验货单主表信息失败");
        }
        //仓库发货单子表信息
        $WarehouseOutDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $WarehouseOutDetailEntity = array();
            $WarehouseOutDetailEntity["w_out_id"] = $w_out_id;
            $WarehouseOutDetailEntity["goods_id"] = $val["goods_id"];
            $WarehouseOutDetailEntity["g_num"] = $val["g_num"];
            $WarehouseOutDetailEntity["g_price"] = $val["g_price"];
            $WarehouseOutDetailEntity["remark"] = $val["remark"];
            $WarehouseOutDetailEntity["value_id"] = $val["value_id"];
            $WarehouseOutDetailEntitys[] = $WarehouseOutDetailEntity;
            $g_type++;
            $g_nums += $val["g_num"];
        }
        $ok = $WarehouseOutDetailModel->addAll($WarehouseOutDetailEntitys);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增仓库出库验货单子表信息失败");
        }
        //更新主表信息
        $ok = $WarehouseOutModel->where(" w_out_id={$w_out_id} ")->order(" w_out_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新仓库出库验货单主表信息失败");
        }

        //删除临时申请单数据
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and temp_type={$temp_type} and status=0 ")->delete();
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请单数据失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "提交成功");
    }

}