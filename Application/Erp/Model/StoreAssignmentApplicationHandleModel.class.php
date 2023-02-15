<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-27
 * Time: 15:11
 * 门店调拨申请处理
 */

namespace Erp\Model;

use Think\Model;

class StoreAssignmentApplicationHandleModel extends Model
{


    /**************
     * 拒绝生成出库发货单
     * @param $store_id 发货门店ID
     * @param $s_t_s_d_id 申请子表ID数组
     */
    public function reject($store_id, $s_t_s_d_id)
    {
        $this->startTrans();
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        //判断是否具有审核当前信息的权限
        $sql = "select A.s_t_s_id,A.g_type from hii_store_to_store_detail A1 left join hii_store_to_store A on A.store_id2={$store_id} and A1.s_t_s_d_id={$s_t_s_d_id} limit 1 ";
        $datas = $StoreToStoreDetailModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无权审核该信息");
        }
        //更新子表状态
        $ok = $StoreToStoreDetailModel->where(" s_t_s_d_id={$s_t_s_d_id} ")->limit(1)->save(array(
            "is_pass" => 1
        ));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "门店调拨申请单子表状态更新失败");
        }
        //更新主表状态 0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
        $data = array(
            's_t_s_id' =>  $datas[0]["s_t_s_id"],
            'g_type' =>  $datas[0]["g_type"],
            's_t_s_status' => 0,
            'store_id' =>  $store_id,
            's_t_s_d_id' => $s_t_s_d_id
        );
        $s_t_s_id = $datas[0]["s_t_s_id"];
        $g_type = $datas[0]["g_type"];
        $list = $StoreToStoreDetailModel->where(" s_t_s_id={$s_t_s_id} ")->select();
        $s_t_s_status = 0;
        $PassNum = 0;//is_pass的总和
        $CheckNum = 0;//已审核数量
        $SomePass = false;//是否有些通过审核
        foreach ($list as $key => $val) {
            $PassNum += $val["is_pass"];
            if ($val["is_pass"] > 0) {
                $CheckNum++;
            }
            if ($val["is_pass"] == 2) {
                $SomePass = true;
            }
        }
        if ($CheckNum > 0 && $SomePass) {
            $s_t_s_status = 2;
        }
        if ($CheckNum == $g_type) {
            $s_t_s_status = 1;
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
            return array("status" => "0", "msg" => "门店调拨申请单主表状态更新失败");
        }
        $this->commit();
        // 加入提醒,通知被拒申请门店
        $get_data = $StoreToStoreModel->where(array('s_t_s_id'=>$val["s_t_s_id"]))->find();
        $MessageWarnModel = D('MessageWarn');
        $MessageWarnModel->pushMessageWarn(UID  , 0  ,$get_data['store_id1'] ,  0 , $get_data , 15);
        return array("status" => "200", "msg" => "操作成功");
    }


    /****************
     * 生成门店出库验货单
     * @param $admin_id 操作人员ID
     * @param $store_id 当前所选门店ID
     * @param $s_t_s_d_id_array 门店对门店调拨子表ID
     * @param $remark 备注
     * @return array
     */
    public function generateStoreOutOrder($admin_id, $store_id, $s_t_s_d_id_array, $remark)
    {
        $this->startTrans();
        $isUpdateRollback = true;//是否同时更新hii_store_to_store
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        $StoreOutModel = M("StoreOut");
        $StoreOutDetailModel = M("StoreOutDetail");
        $WarehouseInoutViewModel = M("WarehouseInoutView");//用于查询g_price
        $StoreModel = M("Store");

        //获取发货门店所在区域ID
        $shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (!is_null($store_datas) && !empty($store_datas) && count($store_datas) > 0) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }

        //查询含有g_price的申请单ID
        $in = implode(",", $s_t_s_d_id_array);
        $sql = "select A1.s_t_s_d_id ";
        $sql .= "from hii_warehouse_inout_view WIV ";
        $sql .= "inner join hii_store_to_store_detail A1 on A1.goods_id=WIV.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "WHERE A1.s_t_s_d_id in ({$in}) ";
        $s_t_s_d_id_with_gprice_array = $WarehouseInoutViewModel->query($sql);
        if (is_null($s_t_s_d_id_with_gprice_array) || empty($s_t_s_d_id_with_gprice_array) || count($s_t_s_d_id_with_gprice_array) == 0) {
            return array("status" => "0", "msg" => "所有申请没有入库价，无法生成出库验货单");
        }
        //按申请门店,发货门店划分单【一个申请门店对应一个发货门店对应多张申请单】
        //store_id1:申请门店  store_id2:发货门店
        $in = "";
        foreach ($s_t_s_d_id_with_gprice_array as $key => $val) {
            $in .= $val["s_t_s_d_id"] . ",";
        }
        $in = substr($in, 0, strlen($in) - 1);
        $sql = "select A.store_id1,A.store_id2 ";
        $sql .= "from hii_store_to_store A ";
        $sql .= "left join hii_store_to_store_detail A1 on A1.s_t_s_id=A.s_t_s_id ";
        $sql .= "where A1.s_t_s_d_id in ({$in}) ";
        $sql .= "group by store_id1,store_id2 ";
        $datas = $StoreToStoreModel->query($sql);
        $s_t_s_id_array = array();


        $UpdateStoreToStoreEntitys = array();
        $UpdateStoreToStoreDetailEntitys = array();
        $return_data = array();
        foreach ($datas as $key => $val) {
            $sql = "select A.s_t_s_id,A1.s_t_s_d_id,A1.goods_id,A1.g_num,ifnull(WIV.stock_price,0) as g_price ";
            $sql .= "from hii_store_to_store_detail A1 ";
            $sql .= "left join hii_store_to_store A on A1.s_t_s_id=A.s_t_s_id ";
            $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id and WIV.shequ_id={$shequ_id} ";
            $sql .= "where A.store_id1={$val["store_id1"]} and A.store_id2={$val["store_id2"]} and A1.s_t_s_d_id in ({$in})  ";
            $RequestDatas = $StoreToStoreModel->query($sql);
            //整理hii_store_out主表信息
            $StoreOutEntity = array();
            $StoreOutEntity["s_out_sn"] = get_new_order_no("CY", "hii_sotre_out", "s_out_sn");
            $StoreOutEntity["s_out_status"] = 0;
            $StoreOutEntity["s_out_type"] = 1;
            $StoreOutEntity["ctime"] = time();
            $StoreOutEntity["admin_id"] = $admin_id;
            $StoreOutEntity["store_id1"] = $val["store_id1"];
            $StoreOutEntity["store_id2"] = $val["store_id2"];
            $StoreOutEntity["remark"] = $remark;
            $s_out_id = $StoreOutModel->add($StoreOutEntity);
            $return_data[] = $StoreOutEntity;
            if ($s_out_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "出库验货单主表新增失败");
            }
            //增加明细信息
            $g_type = 0;
            $g_nums = 0;
            $s_t_s_id_tmp_array = array();
            $StoreOutDetailEntitys = array();
            foreach ($RequestDatas as $k => $v) {
                $UpdateStoreToStoreDetailEntitys[] = array("s_t_s_d_id" => $v["s_t_s_d_id"], "is_pass" => 2);
                if (!in_array($v["s_t_s_id"], $s_t_s_id_array)) {
                    array_push($s_t_s_id_array, $v["s_t_s_id"]);
                }
                if (!in_array($v["s_t_s_id"], $s_t_s_id_tmp_array)) {
                    array_push($s_t_s_id_tmp_array, $v["s_t_s_id"]);
                }
                $g_type++;
                $g_nums += $v["g_num"];
                $StoreOutDetailEntity = array();
                $StoreOutDetailEntity["s_out_id"] = $s_out_id;
                $StoreOutDetailEntity["goods_id"] = $v["goods_id"];
                $StoreOutDetailEntity["g_num"] = $v["g_num"];
                $StoreOutDetailEntity["g_price"] = $v["g_price"];
                $StoreOutDetailEntity["s_r_d_id"] = $v["s_t_s_d_id"];
                $StoreOutDetailEntitys[] = $StoreOutDetailEntity;
            }
            if (count($StoreOutDetailEntitys) > 0) {
                $ok = $StoreOutDetailModel->addAll($StoreOutDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增出库验货单子表信息失败");
                }
            }
            //更新主表g_type，g_nums
            $s_t_s_id_tmp_str = implode(",", $s_t_s_id_tmp_array);
            $ok = $StoreOutModel->where(" s_out_id={$s_out_id} ")->limit(1)->save(array(
                "g_type" => $g_type,
                "g_nums" => $g_nums,
                "s_r_id" => $s_t_s_id_tmp_str
            ));
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "更新出库验货单主表数量种类失败");
            }
        }
        if ($isUpdateRollback) {
            //更新申请表【hii_store_to_store】状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
            foreach ($s_t_s_id_array as $key => $val) {
                $UpdateStoreToStoreEntitys[] = array("s_t_s_id" => $val, "s_t_s_status" => 1);
            }
            $result = $this->saveAll("hii_store_to_store", $UpdateStoreToStoreEntitys, "s_t_s_id", $StoreToStoreModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店调拨主表信息失败");
            }
            $result = $this->saveAll("hii_store_to_store_detail", $UpdateStoreToStoreDetailEntitys, "s_t_s_d_id", $StoreToStoreDetailModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店调拨子表信息失败");
            }
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功" , 'data' => $return_data);
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