<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-26
 * Time: 11:04
 * 门店寄售
 */

namespace Erp\Model;

use Think\Model;

class StoreConsignModel extends Model
{

    /*******************
     * 提交临时申请单
     * @param $admin_id 管理员ID
     * @param $store_id 寄售门店ID
     * @param $supply_id 供应商ID
     * @param $remark 备注
     */
    public function submitRequestTemp($admin_id, $store_id, $supply_id, $remark)
    {
        $this->startTrans();
        $temp_type = 9;
        $SupplyModel = M("Supply");
        $RequestTemp = M("RequestTemp");
        $ConsignmentModel = M("ConsignmentIn");
        $ConsignmentDetailModel = M("ConsignmentInDetail");
        $datas = $SupplyModel->where(" s_id={$supply_id} ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "供应商不存在");
        }
        $datas = $RequestTemp->where("  admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and  `status`=0  ")->order(" id desc ")->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "请填写需要寄售的商品");
        }
        $g_type = 0;
        $g_nums = 0;
        foreach ($datas as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
        }
        //hii_consignment信息
        $ConsignmentEntity = array();
        $ConsignmentEntity["c_in_sn"] = get_new_order_no("JR", "hii_consignment", "c_sn");
        $ConsignmentEntity["c_in_status"] = 0;
        $ConsignmentEntity["ctime"] = time();
        $ConsignmentEntity["admin_id"] = $admin_id;
        $ConsignmentEntity["supply_id"] = $supply_id;
        $ConsignmentEntity["store_id"] = $store_id;
        $ConsignmentEntity["remark"] = $remark;
        $ConsignmentEntity["g_type"] = $g_type;
        $ConsignmentEntity["g_nums"] = $g_nums;
        $c_in_id = $ConsignmentModel->add($ConsignmentEntity);
        if ($c_in_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增门店寄售主表信息失败");
        }
        //hii_consignment_detail信息
        $ConsignmentDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $ConsignmentDetailEntity = array();
            $ConsignmentDetailEntity["c_in_id"] = $c_in_id;
            $ConsignmentDetailEntity["goods_id"] = $val["goods_id"];
            $ConsignmentDetailEntity["b_n_num"] = $val["b_n_num"];
            $ConsignmentDetailEntity["b_num"] = $val["b_num"];
            $ConsignmentDetailEntity["b_price"] = $val["b_price"];
            $ConsignmentDetailEntity["g_num"] = $val["g_num"];
            $ConsignmentDetailEntity["g_price"] = $val["g_price"];
            $ConsignmentDetailEntity["remark"] = $val["remark"];
            $ConsignmentDetailEntity["value_id"] = $val["value_id"];
            $ConsignmentDetailEntitys[] = $ConsignmentDetailEntity;
        }
        if (count($ConsignmentDetailEntitys) > 0) {
            $ok = $ConsignmentDetailModel->addAll($ConsignmentDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店寄售子表信息失败");
            }
        }
        //删除临时申请表信息
        $ok = $RequestTemp->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and  `status`=0 ")->order(" id desc ")->delete();
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请表信息失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /*********************
     * 修改寄售单数据
     * @param $eadmin_id 编辑人ID
     * @param $store_id  门店ID
     * @param $c_id 寄售单ID
     * @param $supply_id 供应商ID
     * @param $remark 备注
     * @param $info_json 子表更新信息 格式[{"c_in_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""},
     *                                       c_in_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""}]
     ********************/
    public function updateConsignment($eadmin_id, $store_id, $c_in_id, $supply_id, $remark, $info_json)
    {
        $this->startTrans();
        $ConsignmentModel = M("ConsignmentIn");
        $SupplyModel = M("Supply");
        $ConsignmentDetailModel = M("ConsignmentInDetail");

        $where = array();
        $where["hii_consignment_in.c_in_id"] = $c_in_id;
        $where["hii_consignment_in.store_id"] = $store_id;
        $where["hii_consignment_in.c_in_status"] = 0;
        $datas = $ConsignmentModel->where($where)->order(" c_in_id desc ")->limit(1)->select();
        //echo $ConsignmentModel->_sql();exit;
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "该寄售入库单无法修改");
        }
        $datas = $SupplyModel->where(" s_id={$supply_id} ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "该供应商不存在");
        }

        $g_type = 0;
        $g_nums = 0;
        //更新寄售单子表信息
        $UpdateConsignmentInDetailEntitys = array();
        foreach ($info_json as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
            $datas = $ConsignmentDetailModel->where(" c_in_d_id={$val["c_in_d_id"]} and c_in_id={$c_in_id} ")->order(" c_in_d_id desc ")->limit(1)->select();
            if (is_null($datas) || empty($datas) || count($datas) == 0) {
                return array("status" => "0", "msg" => "该寄售入库单子表信息不存在");
            }
            $UpdateConsignmentInDetailEntitys[] = $val;
        }
        $result = $this->saveAll("hii_consignment_in_detail", $UpdateConsignmentInDetailEntitys, "c_in_d_id", $ConsignmentDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新寄售入库单子表信息失败");
        }

        //更新寄售单主表信息
        $save = array();
        $save["eadmin_id"] = $eadmin_id;
        $save["etime"] = time();
        $save["supply_id"] = $supply_id;
        $save["remark"] = $remark;
        $save["g_type"] = $g_type;
        $save["g_nums"] = $g_nums;
        $ok = $ConsignmentModel->where(" c_in_id={$c_in_id} ")->limit(1)->save($save);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新寄售入库单主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }


    /*******************
     * 寄售单审核
     * @param string $padmin_id 审核人员ID
     * @param mixed $store_id 门店ID
     * @param string $c_id 寄售单ID
     * 审核后直接入库【hii_store_in_stock，hii_store_in_stock_detail】
     */
    public function check($padmin_id, $store_id, $c_in_id)
    {
        date_default_timezone_set('PRC');
        $this->startTrans();
        $ConsignmentModel = M("ConsignmentIn");
        $ConsignmentDetailModel = M("ConsignmentInDetail");
        $StoreInStockModel = M("StoreInStock");
        $StoreInStockDetailModel = M("StoreInStockDetail");
        $StoreModel = M("Store");
        $WarehouseInoutModel = M("WarehouseInout");
        $GoodsStoreModel = M("GoodsStore");
        $GoodsModel = M("Goods");
        $default_expired_days = 30;//默认过期三十天

        $where = array();
        $where["c_in_id"] = $c_in_id;
        $where["c_in_status"] = 0;
        $where["store_id"] = $store_id;
        $datas = $ConsignmentModel->where($where)->order(" c_in_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该寄售入库单");
        }
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($store_datas) || empty($store_datas) || count($store_datas) == 0) {
            return array("status" => "0", "msg" => "门店不存在");
        }
        $shequ_id = $store_datas[0]["shequ_id"];

        $details = $ConsignmentDetailModel->where(" `c_in_id`={$c_in_id} ")->select();
        $ctime = time();
        $today = date("Y-m-d", time());

        //门店入库主表信息
        $StoreInStockEntity = array();
        $StoreInStockEntity["s_in_s_sn"] = get_new_order_no("RK", "hii_store_in_stock", "s_in_s_sn");
        $StoreInStockEntity["c_id"] = $c_in_id;
        $StoreInStockEntity["s_in_s_status"] = 1;//已审核转收货
        $StoreInStockEntity["s_in_s_type"] = 5;
        $StoreInStockEntity["ctime"] = $ctime;
        $StoreInStockEntity["admin_id"] = $padmin_id;
        $StoreInStockEntity["ptime"] = $ctime;
        $StoreInStockEntity["padmin_id"] = $padmin_id;

        $StoreInStockEntity["store_id2"] = $store_id;
        $StoreInStockEntity["supply_id"] = $datas[0]["supply_id"];
        $StoreInStockEntity["remark"] = $datas[0]["remark"];
        $StoreInStockEntity["g_type"] = $datas[0]["g_type"];
        $StoreInStockEntity["g_nums"] = $datas[0]["g_nums"];
        $s_in_s_id = $StoreInStockModel->add($StoreInStockEntity);
        if ($s_in_s_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增门店入库单主表信息失败");
        }
        //门店入库单子表信息
        $NewStoreInStockDetailEntitys = array();
        $NewWarehouseInoutEntitys = array();
        $NewGoodsStoreEntitys = array();
        $UpdateGoodsStoreEntitys = array();

        //读取商品保质期
        $goods_id_array = array_column($details, "goods_id");
        $goods_id_where = implode(",", $goods_id_array);
        $goods_expired_days_array = $GoodsModel->where(" id in ({$goods_id_where}) ")->select();

        foreach ($details as $key => $val) {
            /*************入库单子表信息********************/
            $StoreInStockDetailEntity = array();
            $StoreInStockDetailEntity["s_in_s_id"] = $s_in_s_id;
            $StoreInStockDetailEntity["goods_id"] = $val["goods_id"];
            $StoreInStockDetailEntity["g_num"] = $val["g_num"];
            $StoreInStockDetailEntity["g_price"] = $val["g_price"];
            $StoreInStockDetailEntity["remark"] = $val["remark"];
            $StoreInStockDetailEntity["value_id"] = $val["value_id"];

            $endtime = strtotime("+{$default_expired_days} day");
            foreach ($goods_expired_days_array as $gk => $gv) {
                if ($gv["id"] == $val["goods_id"]) {
                    if (!is_null($gv["expired_days"]) && !empty($gv["expired_days"])) {
                        $endtime = strtotime("+{$gv["expired_days"]} day");
                    }
                    break;
                }
            }
            $StoreInStockDetailEntity["endtime"] = $endtime;
            $NewStoreInStockDetailEntitys[] = $StoreInStockDetailEntity;
            /****************门店库存*******************************/
            $tmp = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
            if (is_null($tmp) || empty($tmp) || count($tmp) == 0) {
                //新增时候 获取该商品的其他门店的shequ_price
                //获取门店同区域其他门店价格 更新自身
                $storeModel = D('Store');
                $getInfo = $storeModel->field('shequ_id')->where(array('id'=> $store_id)) ->find();
                $getOhterStoreInfo = $GoodsStoreModel->field('hii_goods_store.shequ_price')->join('hii_store on hii_store.id = hii_goods_store.store_id')
                    ->where(array('hii_store.shequ_id' => $getInfo['shequ_id'] , 'hii_goods_store.shequ_price' => array('GT' ,0) , 'hii_goods_store.goods_id' => $val["goods_id"]))->find();
                $shequ_price = $getOhterStoreInfo ? $getOhterStoreInfo['shequ_price'] : Null;
                $NewGoodsStoreEntitys[] = array("goods_id" => $val["goods_id"], "store_id" => $store_id, "num" => $val["g_num"], "update_time" => $ctime , "shequ_price" => $shequ_price);
                
            } else {
                $newnum = $val["g_num"] + $tmp[0]["num"];
                $UpdateGoodsStoreEntitys[] = array("id" => $tmp[0]["id"], "num" => $newnum, "update_time" => $ctime);
            }
        }
        if (count($NewStoreInStockDetailEntitys) > 0) {
            $ok = $StoreInStockDetailModel->addAll($NewStoreInStockDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店入库单子表信息失败");
            }
        }

        //加入库存
        if (count($NewGoodsStoreEntitys) > 0) {
            $ok = $GoodsStoreModel->addAll($NewGoodsStoreEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店库存信息失败");
            }
        }
        $result = $this->saveAll("hii_goods_store", $UpdateGoodsStoreEntitys, "id", $GoodsStoreModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新门店库存信息失败");
        }

        /*************入库批次信息**********************/
        $details = $StoreInStockDetailModel->where(" s_in_s_id={$s_in_s_id} ")->select();
        foreach ($details as $key => $val) {
            $WarehouseInoutEntity = array();
            $WarehouseInoutEntity["goods_id"] = $val["goods_id"];
            $WarehouseInoutEntity["innum"] = $val["g_num"];
            $WarehouseInoutEntity["inprice"] = $val["g_price"];
            $WarehouseInoutEntity["num"] = $val["g_num"];
            $WarehouseInoutEntity["ctime"] = $ctime;
            $WarehouseInoutEntity["ctype"] = 2;
            $WarehouseInoutEntity["shequ_id"] = $shequ_id;
            $WarehouseInoutEntity["store_id"] = $store_id;
            $WarehouseInoutEntity["endtime"] = $val["endtime"];
            $WarehouseInoutEntity["s_in_s_d_id"] = $val["s_in_s_d_id"];
            $WarehouseInoutEntity["value_id"] = $val["value_id"];
            $NewWarehouseInoutEntitys[] = $WarehouseInoutEntity;
        }
        //加入入库批次信息
        if (count($NewWarehouseInoutEntitys) > 0) {
            $ok = $WarehouseInoutModel->addAll($NewWarehouseInoutEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店入库批次信息失败");
            }
        }

        //更新寄售单主表信息
        $ptime = time();
        $ok = $ConsignmentModel->where(" c_in_id={$c_in_id} ")->order(" c_in_id desc ")->limit(1)->save(array("padmin_id" => $padmin_id, "ptime" => $ptime, "c_in_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新寄售入库单主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /**********************
     * 提交寄售出库申请
     * @param $admin_id
     * @param $store_id
     * @param $supply_id
     * @param $remark
     * 逻辑：
     *   1.提交后直接生成门店出库单【hii_store_out_stock,hii_store_stock_detail】
     *   2.减库存
     */
    public function submitOutRequestTemp($admin_id, $store_id, $supply_id, $remark)
    {
        $this->startTrans();
        $temp_type = 12;
        $RequestTemp = M("RequestTemp");
        $ConsignmentOutModel = M("ConsignmentOut");
        $ConsignmentOutDetailModel = M("ConsignmentOutDetail");
        $SupplyModel = M("Supply");
        $GoodsStoreModel = M("GoodsStore");

        $supply_datas = $SupplyModel->where(" s_id={$supply_id} ")->limit(1)->select();
        if (is_null($supply_datas) || empty($supply_datas) || count($supply_datas) == 0) {
            return array("status" => "0", "msg" => "该供应商不存在");
        }

        $datas = $RequestTemp->where("  admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and  `status`=0  ")->order(" id desc ")->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "请填写需要出库的寄售商品");
        }

        //检测库存是否大于申请数量
        /*
        foreach ($datas as $key => $val) {
            $goods_store_datas = $GoodsStoreModel->where(" goods_id={$val["goods_id"]} and store_id={$store_id} ")->limit(1)->select();
            if (is_null($goods_store_datas) || empty($goods_store_datas) || count($goods_store_datas) == 0) {
                return array("status" => "0", "msg" => "ID为{$val["goods_id"]}的商品无库存，不能提交申请");
            } else {
                if ($goods_store_datas[0]["num"] < $val["g_num"]) {
                    return array("status" => "0", "msg" => "ID为{$val["goods_id"]}的商品提交数量大于当前库存数量，不能提交申请");
                }
            }
        }
        */

        $g_type = 0;
        $g_nums = 0;
        foreach ($datas as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
        }

        //寄售出库单主表信息
        $ConsignmentOutEntity = array();
        $ConsignmentOutEntity["c_out_sn"] = get_new_order_no("JC", "hii_consignment_out", "c_out_sn");
        $ConsignmentOutEntity["c_status"] = 0;
        $ConsignmentOutEntity["ctime"] = time();
        $ConsignmentOutEntity["admin_id"] = $admin_id;
        $ConsignmentOutEntity["supply_id"] = $supply_id;
        $ConsignmentOutEntity["store_id"] = $store_id;
        $ConsignmentOutEntity["remark"] = $remark;
        $ConsignmentOutEntity["g_type"] = $g_type;
        $ConsignmentOutEntity["g_nums"] = $g_nums;
        $c_out_id = $ConsignmentOutModel->add($ConsignmentOutEntity);
        if ($c_out_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增寄售出库单主表信息失败");
        }
        //寄售出库单子表信息
        $ConsignmentOutDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $ConsignmentOutDetailEntitys[] = array(
                "c_out_id" => $c_out_id,
                "goods_id" => $val["goods_id"],
                "b_n_num" => $val["b_n_num"],
                "b_num" => $val["b_num"],
                "b_price" => $val["b_price"],
                "g_num" => $val["g_num"],
                "g_price" => $val["g_price"],
                "remark" => $val["remark"],
            	"value_id" => $val['value_id']	
            );
        }
        $ok = $ConsignmentOutDetailModel->addAll($ConsignmentOutDetailEntitys);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增寄售出库单子表信息失败");
        }

        //删除临时申请数据
        $ok = $RequestTemp->where("  admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and  `status`=0  ")->delete();
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请数据失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "提交成功");
    }

    /*********************
     * 修改寄售出库单数据
     * @param $eadmin_id 编辑人ID
     * @param $store_id  门店ID
     * @param $c_out_id 寄售单ID
     * @param $supply_id 供应商ID
     * @param $remark 备注
     * @param $info_json 子表更新信息 格式[{"c_out_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""},
     *                                       c_out_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""}]
     ********************/
    public function updateOutConsignment($eadmin_id, $store_id, $c_out_id, $supply_id, $remark, $info_json)
    {
        $this->startTrans();
        $ConsignmentOutModel = M("ConsignmentOut");
        $SupplyModel = M("Supply");
        $ConsignmentOutDetailModel = M("ConsignmentOutDetail");

        $where = array();
        $where["hii_consignment_out.c_out_id"] = $c_out_id;
        $where["hii_consignment_out.store_id"] = $store_id;
        $where["hii_consignment_out.c_status"] = 0;
        $datas = $ConsignmentOutModel->where($where)->order(" c_out_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "该寄售出库单无法修改");
        }
        $datas = $SupplyModel->where(" s_id={$supply_id} ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "该供应商不存在");
        }

        $g_type = 0;
        $g_nums = 0;
        //更新寄售单子表信息
        $UpdateConsignmentOutDetailEntitys = array();
        foreach ($info_json as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
            $datas = $ConsignmentOutDetailModel->where(" c_out_d_id={$val["c_out_d_id"]} and c_out_id={$c_out_id} ")->order(" c_out_d_id desc ")->limit(1)->select();
            if (is_null($datas) || empty($datas) || count($datas) == 0) {
                return array("status" => "0", "msg" => "该寄售出库单子表信息不存在");
            }
            $UpdateConsignmentOutDetailEntitys[] = $val;
        }
        $result = $this->saveAll("hii_consignment_out_detail", $UpdateConsignmentOutDetailEntitys, "c_out_d_id", $ConsignmentOutDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新寄售单子表信息失败");
        }

        //更新寄售单主表信息
        $save = array();
        $save["eadmin_id"] = $eadmin_id;
        $save["etime"] = time();
        $save["supply_id"] = $supply_id;
        $save["remark"] = $remark;
        $save["g_type"] = $g_type;
        $save["g_nums"] = $g_nums;
        $ok = $ConsignmentOutModel->where(" c_out_id={$c_out_id} ")->limit(1)->save($save);
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新寄售出库单主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /**************
     * 寄售出库单审核
     * @param $padmin_id
     * @param $store_id
     * @param $c_in_id
     * @return array
     */
    public function checkOut($padmin_id, $store_id, $c_out_id)
    {
        $this->startTrans();
        $ConsignmentOutModel = M("ConsignmentOut");
        $ConsignmentOutDetailModel = M("ConsignmentOutDetail");
        $StoreOutStockModel = M("StoreOutStock");//门店出库单主表
        $StoreStockDetailModel = M("StoreStockDetail");//门店出库单子表
        $WarehouseInoutModel = M("WarehouseInout");
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $GoodsStoreModel = M("GoodsStore");
        $StoreModel = M("Store");

        $isCheckGoodsStore = true;

        $where = array();
        $where["c_out_id"] = $c_out_id;
        $where["c_status"] = 0;
        $where["store_id"] = $store_id;

        $datas = $ConsignmentOutModel->where($where)->order(" c_out_id desc ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法审核该寄售出库单");
        }

        $ConsignmentOutEntity = $datas[0];

        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($store_datas) || empty($store_datas) || count($store_datas) == 0) {
            return array("status" => "0", "msg" => "门店不存在");
        }
        $shequ_id = $store_datas[0]["shequ_id"];

        //寄售出库单子表信息
        $datas = $ConsignmentOutDetailModel->where(" c_out_id={$c_out_id} ")->order(" c_out_d_id desc ")->select();

        //检查库存数量
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        if ($isCheckGoodsStore) {
            foreach ($datas as $key => $val) {
            	if(array_key_exists($val['goods_id'],$goods_num_array)){
            		$goods_num_array[$val['goods_id']] += $val["g_num"];
            	}else{
            		$goods_num_array[$val['goods_id']] = $val["g_num"];
            	}
            	 
                $goods_store_datas = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (is_null($goods_store_datas) || empty($goods_store_datas) || count($goods_store_datas) == 0) {
                    return array("status" => "0", "msg" => "ID为【{$val["goods_id"]}】的商品无库存，无法审核");
                } else {
                    if ($goods_store_datas[0]["num"] < $goods_num_array[$val['goods_id']]) {
                        return array("status" => "0", "msg" => "ID为【{$val["goods_id"]}】的商品库存不足，无法审核");
                    }
                }
            }
        }

        //减批次
        $UpdateWarehouseInoutEntitys = array();
        $etime = time();
        foreach ($datas as $key => $val) {
            $g_num = $val["g_num"];
            $goods_id = $val["goods_id"];
            $warehouseinoutdatas = $WarehouseInoutModel->where(" `store_id`={$store_id} and `goods_id`={$goods_id} and num>0 ")->order(" `inout_id` asc ")->select();
            foreach ($warehouseinoutdatas as $k => $v) {
                if ($g_num > 0) {
                    if ($v["num"] >= $g_num) {
                        $batch_outnum = $v["outnum"] + $g_num;//已出数量
                        $batch_num = $v["num"] - $g_num;//现有数量
                        $batch_e_no = $v["e_no"] + 1;
                        $UpdateWarehouseInoutEntitys[] = array(
                            "inout_id" => $v["inout_id"],
                            "outnum" => $batch_outnum,
                            "num" => $batch_num,
                            "etime" => $etime,
                            "etype" => 3,
                            "enum" => $g_num,
                            "e_no" => $batch_e_no,
                        	"value_id" => $val['value_id']
                        );
                        $g_num = 0;
                        break;
                    } else {
                        $batch_outnum = $v["outnum"] + $v["num"];
                        $batch_num = 0;
                        $batch_e_no = $v["e_no"] + 1;
                        $UpdateWarehouseInoutEntitys[] = array(
                            "inout_id" => $v["inout_id"],
                            "outnum" => $batch_outnum,
                            "num" => $batch_num,
                            "etime" => $etime,
                            "etype" => 3,
                            "enum" => $v["num"],
                            "e_no" => $batch_e_no,
                        	"value_id" => $val['value_id']
                        );
                        $g_num = $g_num - $v["num"];
                    }
                } else {
                    break;
                }
            }
            //g_num还大于0,开始减同一区域门店批次数据
            if ($g_num > 0) {
                $warehouseinoutdatas = $WarehouseInoutModel->where(" `goods_id`={$goods_id} and `warehouse_id`=0 and `store_id`>0 and `store_id`<>{$store_id} and `shequ_id`={$shequ_id} and `num`>0 ")->order(" `inout_id` asc ")->select();
                foreach ($warehouseinoutdatas as $k => $v) {
                    if ($g_num > 0) {
                        if ($v["num"] >= $g_num) {
                            $batch_outnum = $v["outnum"] + $g_num;//已出数量
                            $batch_num = $v["num"] - $g_num;//现有数量
                            $batch_e_no = $v["e_no"] + 1;
                            $UpdateWarehouseInoutEntitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "outnum" => $batch_outnum,
                                "num" => $batch_num,
                                "etime" => $etime,
                                "etype" => 3,
                                "enum" => $g_num,
                                "e_no" => $batch_e_no,
                            	"value_id" => $val['value_id']
                            );
                            $g_num = 0;
                            break;
                        } else {
                            $batch_outnum = $v["outnum"] + $v["num"];
                            $batch_num = 0;
                            $batch_e_no = $v["e_no"] + 1;
                            $UpdateWarehouseInoutEntitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "outnum" => $batch_outnum,
                                "num" => $batch_num,
                                "etime" => $etime,
                                "etype" => 3,
                                "enum" => $v["num"],
                                "e_no" => $batch_e_no,
                            	"value_id" => $val['value_id']
                            );
                            $g_num = $g_num - $v["num"];
                        }
                    } else {
                        break;
                    }
                }
            }

            //g_num还大于0，开始减去仓库批次表数据
            if ($g_num > 0) {
                $warehouseinoutdatas = $WarehouseInoutModel->where(" `goods_id`={$goods_id} and `warehouse_id`<>0 and `store_id`=0 and `shequ_id`={$shequ_id} and `num`>0 ")->order(" `inout_id` asc ")->select();
                foreach ($warehouseinoutdatas as $k => $v) {
                    if ($g_num > 0) {
                        if ($v["num"] >= $g_num) {
                            $batch_outnum = $v["outnum"] + $g_num;//已出数量
                            $batch_num = $v["num"] - $g_num;//现有数量
                            $batch_e_no = $v["e_no"] + 1;
                            $UpdateWarehouseInoutEntitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "outnum" => $batch_outnum,
                                "num" => $batch_num,
                                "etime" => $etime,
                                "etype" => 3,
                                "enum" => $g_num,
                                "e_no" => $batch_e_no,
                            	"value_id" => $val['value_id']
                            );
                            $g_num = 0;
                            break;
                        } else {
                            $batch_outnum = $v["outnum"] + $v["num"];
                            $batch_num = 0;
                            $batch_e_no = $v["e_no"] + 1;
                            $UpdateWarehouseInoutEntitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "outnum" => $batch_outnum,
                                "num" => $batch_num,
                                "etime" => $etime,
                                "etype" => 3,
                                "enum" => $v["num"],
                                "e_no" => $batch_e_no,
                            	"value_id" => $val['value_id']
                            );
                            $g_num = $g_num - $v["num"];
                        }
                    } else {
                        break;
                    }
                }
            }
        }
        $result = $this->saveAll("hii_warehouse_inout", $UpdateWarehouseInoutEntitys, "inout_id", $WarehouseInoutModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新批次信息失败");
        }

        //门店出库单主表信息
        $StoreOutStockEntity = array();
        $StoreOutStockEntity["s_out_s_sn"] = get_new_order_no("CK", "hii_store_out_stock", "s_out_s_sn");
        $StoreOutStockEntity["s_out_s_status"] = 1;//默认已审核
        $StoreOutStockEntity["s_out_s_type"] = 5;
        $StoreOutStockEntity["c_id"] = $ConsignmentOutEntity["c_out_id"];
        $StoreOutStockEntity["ctime"] = time();
        $StoreOutStockEntity["admin_id"] = $padmin_id;
        $StoreOutStockEntity["ptime"] = time();
        $StoreOutStockEntity["padmin_id"] = $padmin_id;
        $StoreOutStockEntity["supply_id"] = $ConsignmentOutEntity["supply_id"];
        $StoreOutStockEntity["store_id2"] = $store_id;
        $StoreOutStockEntity["remark"] = $ConsignmentOutEntity["remark"];
        $StoreOutStockEntity["g_type"] = $ConsignmentOutEntity["g_type"];
        $StoreOutStockEntity["g_nums"] = $ConsignmentOutEntity["g_nums"];
        $s_out_s_id = $StoreOutStockModel->add($StoreOutStockEntity);
        if ($s_out_s_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增门店出库单主表信息失败");
        }
        //门店出库单子表信息
        $StoreStockOutDetailEntitys = array();
        $UpdateGoodsStoreEntitys = array();
        foreach ($datas as $key => $val) {
            /**************门店出库单子表信息*********************/
            $StoreStockOutDetailEntity = array();
            $StoreStockOutDetailEntity["s_out_s_id"] = $s_out_s_id;
            $StoreStockOutDetailEntity["goods_id"] = $val["goods_id"];
            $StoreStockOutDetailEntity["g_num"] = $val["g_num"];
            $StoreStockOutDetailEntity["g_price"] = $val["g_price"];
            $StoreStockOutDetailEntity["remark"] = $val["remark"];
            $StoreStockOutDetailEntity["value_id"] = $val["value_id"];
            $StoreStockOutDetailEntitys[] = $StoreStockOutDetailEntity;
            /************门店库存信息*********************/
            $tmp = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
            if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                $newnum = $tmp[0]["num"] - $val["g_num"];
                $UpdateGoodsStoreEntitys[] = array("id" => $tmp[0]["id"], "num" => $newnum);
            }
        }
        if (count($StoreStockOutDetailEntitys) > 0) {
            $ok = $StoreStockDetailModel->addAll($StoreStockOutDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增门店出库单子表信息失败");
            }
        }
        //减库存
        $result = $this->saveAll("hii_goods_store", $UpdateGoodsStoreEntitys, "id", $GoodsStoreModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "更新库存信息失败");
        }
        //更新寄售出库主表信息
        $ptime = time();
        $ok = $ConsignmentOutModel->where(" c_out_id={$c_out_id} ")->limit(1)->save(array("ptime" => $ptime, "padmin_id" => $padmin_id, "c_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新寄售出库单主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "提交成功");
    }


    /*******************
     * 批量更新
     * @param $tableName 表名
     * @param $datas 更新数据
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
            \Think\Log::record($sql);
            return array("status" => "0", "msg" => "error");
        }
    }

}