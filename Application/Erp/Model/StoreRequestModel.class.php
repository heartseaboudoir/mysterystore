<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-07
 * Time: 14:16
 * 门店发货申请
 */

namespace Erp\Model;

use Think\Model;

class StoreRequestModel extends Model
{

    /*************
     * 提交门店发货申请，把临时申请表的数据转到hii_store_request，hii_store_request_detail中，然后再删除hii_request_temp对应的数据
     * @param $admin_id ：提交人用户ID
     * @param $store_id ： 门店ID
     * @param $warehouse_id ：发货仓库ID
     * @param $remark ：备注
     * 返回数据：array
     * 注意：不需要检测仓库库存是否充足
     */
    public function submitStoreRequest($admin_id, $store_id, $warehouse_id, $remark)
    {
        $this->startTrans();
        $RequestTempModel = M("RequestTemp");
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $temp_type = 2;

        $sql = "select * from hii_request_temp where admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and status=0 order by id desc  ";
        $datas = $RequestTempModel->query($sql);

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "请提交需要申请发货的商品");
        }

        $g_type = 0;
        $g_nums = 0;
        foreach ($datas as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
        }

        //生成发货申请单主表信息
        $StoreRequestEntity = array();
        $StoreRequestEntity["s_r_sn"] = get_new_order_no("SQ", "hii_store_request", "s_r_sn");
        $StoreRequestEntity["s_r_type"] = 0;
        $StoreRequestEntity["s_r_status"] = 0;
        $StoreRequestEntity["ctime"] = time();
        $StoreRequestEntity["admin_id"] = $admin_id;
        $StoreRequestEntity["store_id"] = $store_id;
        $StoreRequestEntity["warehouse_id"] = $warehouse_id;
        $StoreRequestEntity["remark"] = $remark;
        $StoreRequestEntity["g_type"] = $g_type;
        $StoreRequestEntity["g_nums"] = $g_nums;

        $s_r_id = $StoreRequestModel->add($StoreRequestEntity);
        if ($s_r_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增发货申请主表信息失败");
        }

        $NewStoreRequestDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $StoreRequestDetailEntity = array();
            $StoreRequestDetailEntity["s_r_id"] = $s_r_id;
            $StoreRequestDetailEntity["goods_id"] = $val["goods_id"];
            $StoreRequestDetailEntity["g_num"] = $val["g_num"];
            $StoreRequestDetailEntity["is_pass"] = 0;
            $StoreRequestDetailEntity["pass_num"] = 0;
            $StoreRequestDetailEntity["remark"] = $val["remark"];
            $StoreRequestDetailEntity["value_id"] = $val["value_id"];
            $NewStoreRequestDetailEntitys[] = $StoreRequestDetailEntity;
        }

        if (count($NewStoreRequestDetailEntitys) > 0) {
            $ok = $StoreRequestDetailModel->addAll($NewStoreRequestDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增发货申请子表信息失败");
            }
        }

        //删除临时申请表信息
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and status=0 ")->order(" id desc ")->delete();

        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请数据失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功" , 'data' => $StoreRequestEntity);
    }


    /***************
     * 再次提交申请
     * @param $s_r_id 原申请ID
     * @param $admin_id 当前登录人员ID
     */
    public function submitAgain($s_r_id, $admin_id)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");

        $sql = " select * from hii_store_request where s_r_id={$s_r_id} order by s_r_id desc limit 1 ";
        $StoreRequestEntityDatas = $StoreRequestModel->query($sql);
        if (is_null($StoreRequestEntityDatas) || empty($StoreRequestEntityDatas) || count($StoreRequestEntityDatas) == 0) {
            return array("status" => 0, "msg" => "不存在该申请");
        }

        $store_id = $StoreRequestEntityDatas[0]["store_id"];

        $StoreRequestDetailModel = M("StoreRequestDetail");
        $sql = " select * from hii_store_request_detail where s_r_id={$s_r_id} order by s_r_d_id DESC ";
        $StoreRequestDetailEntityDatas = $StoreRequestDetailModel->query($sql);

        $RequestTempModel = M("RequestTemp");
        $temp_type = 2;
        $status = 0;
        $b_n_num = 0;//箱规
        $b_num = 0;//箱数
        $b_price = 0;//每箱价格
        $g_price = 0;//临时采购价

        $NewRequestTempEntitys = array();
        $UpdateRequestTempEntitys = array();
        foreach ($StoreRequestDetailEntityDatas as $key => $val) {
            //判断是否已存在该商品的申请
            $sql = "select id,g_num from hii_request_temp WHERE admin_id={$admin_id} and store_id={$store_id} and goods_id={$val["goods_id"]} and temp_type={$temp_type} and value_id={$val['value_id']}  and status=0 limit 1  ";
            $data = $RequestTempModel->query($sql);
            if (is_null($data) || empty($data) || count($data) == 0) {
                $data = array();
                $data["admin_id"] = $admin_id;
                $data["store_id"] = $store_id;
                $data["temp_type"] = $temp_type;
                $data["goods_id"] = $val["goods_id"];
                $data["ctime"] = time();
                $data["status"] = $status;
                $data["b_n_num"] = $b_n_num;
                $data["b_num"] = $b_num;
                $data["b_price"] = $b_price;
                $data["g_num"] = $val["g_num"];
                $data["g_price"] = $g_price;
                $data["value_id"] = $val['value_id'];
                $NewRequestTempEntitys[] = $data;
            } else {
                $savedata = array();
                $savedata["id"] = $data[0]["id"];
                $savedata["g_num"] = $data[0]["g_num"] + $val["g_num"];
                $savedata["ctime"] = time();
                $UpdateRequestTempEntitys[] = $savedata;
            }
        }

        if (count($NewRequestTempEntitys) > 0) {
            $ok = $RequestTempModel->addAll($NewRequestTempEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "提交失败");
            }
        }
        $result = $this->saveAll("hii_request_temp", $UpdateRequestTempEntitys, "id", $RequestTempModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "提交失败");
        }

        $this->commit();
        return array("status" => 200, "msg" => "保存成功");
    }


    /******************
     * 拒绝单个申请
     * @param $warehouse_id 当前所选仓库
     * @param $s_r_d_id     门店发货申请明细表ID
     ****************/
    public function reject($warehouse_id, $s_r_d_id)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");
        $sql = " select A.s_r_id from hii_store_request A ";
        $sql .= "left join hii_store_request_detail A1 on A1.s_r_id=A.s_r_id ";
        $sql .= "where A1.s_r_d_id={$s_r_d_id} and  A.warehouse_id={$warehouse_id} order by A1.s_r_d_id desc limit 1 ";

        $data = $StoreRequestModel->query($sql);
        if (is_null($data) || empty($data) || count($data) == 0) {
            return array("status" => "0", "msg" => "无权操作该申请");
        }

        $StoreRequestDetailModel = M("StoreRequestDetail");

        //更新明细表
        $savedata = array();
        $savedata["is_pass"] = 1;
        $ok = $StoreRequestDetailModel->where(" s_r_d_id={$s_r_d_id} ")->save($savedata);
        if (!$ok) {
            $this->rollback();
            return array("status" => "0", "msg" => "明细表状态更新失败");
        }

        //更新主表状态  状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
        $s_r_id = $data[0]["s_r_id"];
        $list = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->order(" s_r_d_id desc ")->select();
        $isPassAmount = 0;//审核状态总和
        $isSomePassed = false;
        $hasCheckedNum = 0;//已审核数量

        $data = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->select();
        $g_type = $data[0]["g_type"];
        foreach ($list as $key => $val) {
            //is_pass 0.新增，1.未通过，2.已通过
            $isPassAmount += $val["is_pass"];
            if ($val["is_pass"] > 0) {
                $hasCheckedNum++;
            }
            if ($val['is_pass'] == 2) {
                $isSomePassed = true;
            }
        }
        /**********通过isPassAmount的值决定w_r_status的值*****************/
        $StoreRequestUpdateStatus = 0;

        if ($isPassAmount > 0 && $isSomePassed) {
            $StoreRequestUpdateStatus = 2;//部分通过
        }
        if ($hasCheckedNum == $g_type) {
            $StoreRequestUpdateStatus = 1;
        }
        if ($isPassAmount == 0) {
            $StoreRequestUpdateStatus = 0;//新增
        } elseif ($isPassAmount == $g_type) {
            $StoreRequestUpdateStatus = 4;//全部拒绝
        } elseif ($isPassAmount == ($g_type * 2)) {
            $StoreRequestUpdateStatus = 3;//全部通过
        }
        $savedata = array();
        $savedata["s_r_status"] = $StoreRequestUpdateStatus;
        $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->save($savedata);

        if ($ok !== false) {
            $this->commit();
            return array("status" => "200", "操作成功");
        } else {
            $this->rollback();
            return array("status" => "0", "msg" => "主表状态更新失败");
        }

    }


    /***************************
     * 批量拒绝处理
     * @param $uid
     * @param $s_r_d_id_array
     * @param $remark
     * @return array
     */
    public function batchReject($uid, $warehouse_id, $s_r_d_id_array, $remark)
    {
        $this->startTrans();
        $StoreRequestModel = M("StoreRequest");
        $StoreRequestDetailModel = M("StoreRequestDetail");

        $in_where = "";
        foreach ($s_r_d_id_array as $key => $val) {
            $in_where .= $val["s_r_d_id"] . ",";
        }
        $in_where = substr($in_where, 0, strlen($in_where) - 1);
        $sql = "select SRD.*,SR.s_r_id
                from hii_store_request_detail SRD
                left join hii_store_request SR on SRD.s_r_id=SR.s_r_id
                where SR.warehouse_id={$warehouse_id} and SRD.s_r_d_id in ({$in_where}) order by SRD.s_r_d_id desc ";
        //echo $sql;exit;
        $store_request_detail_data = $StoreRequestDetailModel->query($sql);
        if (count($store_request_detail_data) != count($s_r_d_id_array)) {
            return array("status" => "0", "msg" => "提交数据有误");
        }

        foreach ($store_request_detail_data as $key => $val) {
            $s_r_d_id = $val["s_r_d_id"];
            //更新明细表
            $savedata = array();
            $savedata["is_pass"] = 1;
            $savedata["remark"] = $remark;
            $ok = $StoreRequestDetailModel->where(" s_r_d_id={$s_r_d_id} ")->limit(1)->save($savedata);
            if (!$ok) {
                $this->rollback();
                return array("status" => "0", "msg" => "明细表状态更新失败");
            }

            //更新主表状态  状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
            $s_r_id = $val["s_r_id"];
            $list = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->order(" s_r_d_id desc ")->select();
            $isPassAmount = 0;//审核状态总和
            $isSomePassed = false;
            $hasCheckedNum = 0;//已审核数量

            $data = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->limit(1)->select();
            $g_type = $data[0]["g_type"];
            foreach ($list as $k => $v) {
                //is_pass 0.新增，1.未通过，2.已通过
                $isPassAmount += $v["is_pass"];
                if ($v["is_pass"] > 0) {
                    $hasCheckedNum++;
                }
                if ($v['is_pass'] == 2) {
                    $isSomePassed = true;
                }
            }
            /**********通过isPassAmount的值决定w_r_status的值*****************/
            $StoreRequestUpdateStatus = 0;

            if ($isPassAmount > 0 && $isSomePassed) {
                $StoreRequestUpdateStatus = 2;//部分通过
            }
            if ($hasCheckedNum == $g_type) {
                $StoreRequestUpdateStatus = 1;
            }
            if ($isPassAmount == 0) {
                $StoreRequestUpdateStatus = 0;//新增
            } elseif ($isPassAmount == $g_type) {
                $StoreRequestUpdateStatus = 4;//全部拒绝
            } elseif ($isPassAmount == ($g_type * 2)) {
                $StoreRequestUpdateStatus = 3;//全部通过
            }
            $savedata = array();
            $savedata["s_r_status"] = $StoreRequestUpdateStatus;
            $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->save($savedata);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "200", "msg" => "ID为【{$val["goods_id"]}】的商品拒绝失败");
            }
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /**************
     * 生成发货验收单成功后更新hii_store_request和hii_store_request_detail的状态
     * @param $s_r_id 申请主单id
     * @param $s_r_d_id_array 申请单明细表id 数组
     */
    public function updateStoreStatusAfterGenerateWarehouseOutOrder($s_r_id, $s_r_d_id_array)
    {
        $this->startTrans();
        $StoreRequestDetailModel = M("StoreRequestDetail");
        $StoreRequestModel = M("StoreRequest");
        $savedata["is_pass"] = 2;
        foreach ($s_r_d_id_array as $key => $val) {
            $ok = $StoreRequestDetailModel->where(" s_r_d_id={$val} ")->limit(1)->save($savedata);
            if (!$ok) {
                $this->rollback();
                return array(0, "门店发货申请明细单状态更新失败");
            }
        }
        $StoreRequestDetailDatas = $StoreRequestDetailModel->where(" s_r_id={$s_r_id} ")->select();
        /**********通过isPassAmount的值决定w_r_status的值*****************/
        $StoreRequestUpdateStatus = 0;
        $isPassAmount = 0;//审核状态总和
        $isSomePassed = false;
        $hasCheckedNum = 0;//已审核数量
        $g_type = 0;

        foreach ($StoreRequestDetailDatas as $key => $val) {
            //is_pass 0.新增，1.未通过，2.已通过
            $isPassAmount += $val["is_pass"];
            if ($val["is_pass"] > 0) {
                $hasCheckedNum++;
            }
            if ($val['is_pass'] == 2) {
                $isSomePassed = true;
            }
            $g_type++;
        }

        if ($isPassAmount > 0 && $isSomePassed) {
            $StoreRequestUpdateStatus = 2;//部分通过
        }
        if ($hasCheckedNum == $g_type) {
            $StoreRequestUpdateStatus = 1;
        }
        if ($isPassAmount == 0) {
            $StoreRequestUpdateStatus = 0;//新增
        } elseif ($isPassAmount == $g_type) {
            $StoreRequestUpdateStatus = 4;//全部拒绝
        } elseif ($isPassAmount == ($g_type * 2)) {
            $StoreRequestUpdateStatus = 3;//全部通过
        }
        $savedata = array();
        $savedata["s_r_status"] = $StoreRequestUpdateStatus;
        $ok = $StoreRequestModel->where(" s_r_id={$s_r_id} ")->save($savedata);
        if (!$ok) {
            $this->rollback();
            return array("status" => "0", "msg" => "主表状态更新失败");
        }

        $this->commit();
        return array("status" => "200", "操作成功");

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