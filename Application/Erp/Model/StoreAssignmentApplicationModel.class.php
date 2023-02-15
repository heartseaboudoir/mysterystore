<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-22
 * Time: 15:11
 * 门店调拨申请
 */

namespace Erp\Model;

use Think\Model;

class StoreAssignmentApplicationModel extends Model
{

    /******************
     * 提交门店临时申请
     * @param $admin_id 会员ID
     * @param $store_id1 申请门店ID
     * @param $store_id2 发货门店ID
     * @param $remark 备注
     */
    function submitRequestTemp($admin_id, $store_id1, $store_id2, $remark)
    {
        $this->startTrans();
        $temp_type = 6;
        $RequestTempModel = M("RequestTemp");
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");

        $sql = "select RT.g_num,RT.goods_id,ifnull(GS.num,0) as stock_num,RT.remark ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=RT.goods_id and GS.store_id={$store_id2} ";
        $sql .= "where RT.admin_id={$admin_id} and RT.store_id={$store_id1} and RT.temp_type={$temp_type} and RT.status=0 ";
        $sql .= "order by RT.id desc ";

        $datas = $RequestTempModel->query($sql);

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "请填写需要调拨的商品");
        }

        $g_type = 0;
        $g_nums = 0;
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        foreach ($datas as $key => $val) {
            if ($val["g_num"] > $val["stock_num"]) {
                return array("status" => "0", "msg" => "ID为{$val["goods_id"]}的商品发货门店库存不足，无法提交申请");
            }
            $g_type++;
            $g_nums += $val["g_num"];
        }
        //生成申请主表信息
        $StoreToStoreEntity = array();
        $StoreToStoreEntity["s_t_s_sn"] = get_new_order_no("SQ", "hii_store_to_store", "s_t_s_sn");
        $StoreToStoreEntity["s_t_s_type"] = 0;
        $StoreToStoreEntity["s_t_s_status"] = 0;
        $StoreToStoreEntity["ctime"] = time();
        $StoreToStoreEntity["admin_id"] = $admin_id;
        $StoreToStoreEntity["store_id1"] = $store_id1;
        $StoreToStoreEntity["store_id2"] = $store_id2;
        $StoreToStoreEntity["remark"] = $remark;
        $StoreToStoreEntity["g_type"] = $g_type;
        $StoreToStoreEntity["g_nums"] = $g_nums;
        $s_t_s_id = $StoreToStoreModel->add($StoreToStoreEntity);
        if ($s_t_s_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增调拨申请主表信息失败");
        }

        $StoreToStoreDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $StoreToStoreDetailEntity = array();
            $StoreToStoreDetailEntity["s_t_s_id"] = $s_t_s_id;
            $StoreToStoreDetailEntity["goods_id"] = $val["goods_id"];
            $StoreToStoreDetailEntity["g_num"] = $val["g_num"];
            $StoreToStoreDetailEntity["is_pass"] = 0;
            $StoreToStoreDetailEntity["pass_num"] = 0;
            $StoreToStoreDetailEntity["remark"] = $val["remark"];
            $StoreToStoreDetailEntitys[] = $StoreToStoreDetailEntity;
        }
        if (count($StoreToStoreDetailEntitys) > 0) {
            $ok = $StoreToStoreDetailModel->addAll($StoreToStoreDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增调拨申请子表信息失败");
            }
        }

        //删除临时申请表信息
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id1} and temp_type={$temp_type} and status=0 ")->order(" id desc ")->delete();

        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请数据失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功" , 'data' => $StoreToStoreEntity);
    }

    /*************
     * 再次提交申请，把明细表数据再次录入到hii_request_temp中
     * @param $admin_id 操作人员ID
     * @param $s_t_s_id 门店调拨申请主表ID
     */
    function requestAgain($admin_id, $s_t_s_id)
    {
        $this->startTrans();
        $temp_type = 6;
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        $RequestTempModel = M("RequestTemp");
        $WarehouseInoutView = M("WarehouseInoutView");
        $datas = $StoreToStoreModel->query("select * from hii_store_to_store where s_t_s_id={$s_t_s_id} limit 1  ");
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "该申请不存在");
        }
        $store_id = $datas[0]["store_id1"];
        $sql = "select * from hii_store_to_store_detail WHERE s_t_s_id={$s_t_s_id} ";
        $datas = $StoreToStoreDetailModel->query($sql);
        $NewRequestTempEntitys = array();
        $UpdateRequestTempEntitys = array();
        foreach ($datas as $key => $val) {
            $tmp = $RequestTempModel->where(" admin_id={$admin_id} and `store_id`={$store_id} and temp_type={$temp_type} and goods_id={$val["goods_id"]}")->order(" id desc ")->limit(1)->select();
            if (is_null($temp_type) || empty($tmp) || count($tmp) == 0) {
                //新增
                $RequestTempEntity = array();
                $RequestTempEntity["admin_id"] = $admin_id;
                $RequestTempEntity["store_id"] = $store_id;
                $RequestTempEntity["goods_id"] = $val["goods_id"];
                $RequestTempEntity["ctime"] = time();
                $RequestTempEntity["status"] = 0;
                $RequestTempEntity["b_n_num"] = 0;
                $RequestTempEntity["n_num"] = 0;
                $RequestTempEntity["b_price"] = 0;
                $RequestTempEntity["g_num"] = $val["g_num"];
                $RequestTempEntity["g_price"] = 0;
                $RequestTempEntity["temp_type"] = $temp_type;
                $NewRequestTempEntitys[] = $RequestTempEntity;
            } else {
                //更新
                $g_num = $tmp[0]["g_num"] + $val["g_num"];
                $ctime = time();
                $UpdateRequestTempEntitys[] = array("id" => $tmp[0]["id"], "g_num" => $g_num, "ctime" => $ctime);
            }
        }
        //新增申请
        if (count($NewRequestTempEntitys) > 0) {
            $ok = $RequestTempModel->addAll($NewRequestTempEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增临时调拨申请信息失败");
            }
        }
        //更新申请
        $result = $this->saveAll("hii_request_temp", $UpdateRequestTempEntitys, "id", $RequestTempModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新临时调拨申请信息失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


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
            \Think\Log::record($sql);
            return array("status" => "0", "msg" => "error");
        }
    }


}