<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-11
 * Time: 11:39
 * 门店返仓处理
 */

namespace Erp\Model;

use Think\Model;

class BackToWarehouseModel extends Model
{

    /***************
     * 提交临时申请
     * @param $admin_id 管理员ID
     * @param $store_id 门店ID
     * @param $warehouse_id 仓库ID
     * @param $remark 备注
     */
    public function submitRequestTemp($admin_id, $store_id, $warehouse_id, $remark)
    {
        $this->startTrans();
        $RequestTempModel = M("RequestTemp");
        $StoreBackModel = M("StoreBack");
        $StoreBackDetailModel = M("StoreBackDetail");
        $GoodsStoreModel = M("GoodsStore");

        $temp_type = 10;
        $g_type = 0;
        $g_nums = 0;

        $datas = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and status=0 ")->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "临时申请单无数据，无法提交");
        }

        //检测提交数量是否大于当前库存数量
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        foreach ($datas as $key => $val) {
            if(array_key_exists($val['goods_id'],$goods_num_array)){
                $goods_num_array[$val['goods_id']] += $val["g_num"];
            }else{
                $goods_num_array[$val['goods_id']] = $val["g_num"];
            }

            $goods_store_datas = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
            if (is_null($goods_store_datas) || empty($goods_store_datas) || count($goods_store_datas) == 0) {
                return array("status" => "0", "msg" => "ID为{$val["goods_id"]}的商品无库存，不能提交申请");
            } else {
                if ($goods_store_datas[0]["num"] < $goods_num_array[$val['goods_id']]) {
                    return array("status" => "0", "msg" => "ID为{$val["goods_id"]}的商品提交数量大于当前库存数量，不能提交申请");
                }
            }
        }

        //返仓主表信息
        $StoreBackEntity = array();
        $StoreBackEntity["s_back_sn"] = get_new_order_no("FC", "hii_store_back", "s_back_sn");
        $StoreBackEntity["s_back_status"] = 0;
        $StoreBackEntity["s_back_type"] = 0;
        $StoreBackEntity["ctime"] = time();
        $StoreBackEntity["admin_id"] = $admin_id;
        $StoreBackEntity["store_id"] = $store_id;
        $StoreBackEntity["warehouse_id"] = $warehouse_id;
        $StoreBackEntity["remark"] = $remark;
        $StoreBackEntity["g_type"] = 0;
        $StoreBackEntity["g_nums"] = 0;
        $s_back_id = $StoreBackModel->add($StoreBackEntity);
        if ($s_back_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增返仓主表信息失败");
        }
        //返仓子表信息
        $StoreBackDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $StoreBackDetailEntity = array();
            $StoreBackDetailEntity["s_back_id"] = $s_back_id;
            $StoreBackDetailEntity["goods_id"] = $val["goods_id"];
            $StoreBackDetailEntity["g_num"] = $val["g_num"];
            $StoreBackDetailEntity["g_price"] = $val["g_price"];
            $StoreBackDetailEntity["remark"] = $val["remark"];
            $StoreBackDetailEntity["value_id"] = $val["value_id"];
            $StoreBackDetailEntitys[] = $StoreBackDetailEntity;
            $g_type++;
            $g_nums += $val["g_num"];
        }

        $ok = $StoreBackDetailModel->addAll($StoreBackDetailEntitys);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增返仓子表信息失败");
        }

        //更新主表g_type，g_nums
        $ok = $StoreBackModel->where(" s_back_id={$s_back_id} ")->order(" s_back_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新返仓主表信息失败");
        }

        //删除临时申请单数据
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and status=0 ")->delete();
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请单数据失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "提交成功" , 'data' => $StoreBackEntity);
    }

    /*************
     * 审核
     * @param string $padmin_id
     * @param mixed $s_back_id
     * @param string $store_id
     */
    public function check($padmin_id, $s_back_id, $store_id)
    {
        $this->startTrans();
        $StoreBackModel = M("StoreBack");
        $StoreBackDetailModel = M("StoreBackDetail");
        $WarehouseInModel = M("WarehouseIn");
        $WarehouseInDetailModel = M("WarehouseInDetail");
        $GoodsStoreModel = M("GoodsStore");

        $datas = $StoreBackModel->where(" `s_back_id`={$s_back_id} and `s_back_status`=0 and `store_id`={$store_id} ")->limit(1)->select();

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该返仓申请单");
        }
        $details = $StoreBackDetailModel->where(" s_back_id={$s_back_id} ")->select();
        //判断返仓商品库存是否小于门店库存
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        foreach ($details as $key => $val) {
            if(array_key_exists($val['goods_id'],$goods_num_array)){
                $goods_num_array[$val['goods_id']] += $val["g_num"];
            }else{
                $goods_num_array[$val['goods_id']] = $val["g_num"];
            }

            $goods_store_num = $GoodsStoreModel->where(array('goods_id' => $val['goods_id'], 'store_id' => $store_id))->getField('num');
            if ($goods_store_num === false || is_null($goods_store_num)) {
                $this->rollback();
                return array("status" => "0", "msg" => "{$val['goods_id']} 商品没有记录不能审核!");
            } else {
                if ($goods_store_num < $goods_num_array[$val['goods_id']]) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "{$val['goods_id']} 商品返仓数量大于库存数量不能审核");
                }
            }
        }

        //仓库入库验货单主表信息
        $WarehouseInEntity = array();
        $WarehouseInEntity["w_in_sn"] = get_new_order_no("RY", "hii_warehouse_in", "w_in_sn");
        $WarehouseInEntity["w_in_status"] = 0;
        $WarehouseInEntity["w_in_type"] = 4;
        $WarehouseInEntity["s_back_id"] = $s_back_id;
        $WarehouseInEntity["ctime"] = time();
        $WarehouseInEntity["admin_id"] = $padmin_id;
        $WarehouseInEntity["warehouse_id"] = $datas[0]["warehouse_id"];
        $WarehouseInEntity["store_id"] = $datas[0]["store_id"];
        $WarehouseInEntity["remark"] = $datas[0]["remark"];
        $WarehouseInEntity["g_type"] = $datas[0]["g_type"];
        $WarehouseInEntity["g_nums"] = $datas[0]["g_nums"];
        $w_in_id = $WarehouseInModel->add($WarehouseInEntity);
        if ($w_in_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增仓库入库验货单主表信息失败");
        }
        //仓库入库验货单子表信息
        $WarehouseInDetailEntitys = array();
        foreach ($details as $key => $val) {
            $WarehouseInDetailEntity = array();
            $WarehouseInDetailEntity["w_in_id"] = $w_in_id;
            $WarehouseInDetailEntity["goods_id"] = $val["goods_id"];
            $WarehouseInDetailEntity["g_num"] = $val["g_num"];
            $WarehouseInDetailEntity["g_price"] = $val["g_price"];
            $WarehouseInDetailEntity["remark"] = $val["remark"];
            $WarehouseInDetailEntity["value_id"] = $val["value_id"];
            $WarehouseInDetailEntitys[] = $WarehouseInDetailEntity;
        }
        $ok = $WarehouseInDetailModel->addAll($WarehouseInDetailEntitys);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增仓库入库验货单子表信息失败");
        }
        //更新返仓主表信息
        $ptime = time();
        $ok = $StoreBackModel->where(" s_back_id={$s_back_id} ")->limit(1)->save(array("s_back_status" => 1, "padmin_id" => $padmin_id, "ptime" => $ptime));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新返仓申请主表信息失败");
        }
        /**************************** 减少门店对应返仓商品的库存 start *****************************************/
        foreach ($details as $key => $val) {
            $tmp = $GoodsStoreModel->where(" goods_id={$val["goods_id"]} and store_id={$store_id} ")->limit(1)->select();
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                $ok = $GoodsStoreModel->add(array("goods_id" => $val["goods_id"], "store_id" => $store_id, "num" => -$val["g_num"], "update_time" => $ptime));
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "扣减门店库存数量失败");
                }

            } else {
                $newnum = $tmp[0]["num"] - $val["g_num"];
                $result = $GoodsStoreModel->where(array('id'=>$tmp[0]['id']))->save(array("num" => $newnum, "update_time" => $ptime));
                if (!$result) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "更新门店库存数量失败");
                }
            }
        }
        /**************************** 减少门店对应返仓商品的库存 end *****************************************/
        $this->commit();
        return array("status" => "200", "msg" => "审核成功" , 'data' => $WarehouseInEntity);
    }


    /**************
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
            \Think\Log::record($sql);
            return array("status" => "0", "msg" => "error");
        }
    }

}