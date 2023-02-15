<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-12
 * Time: 14:02
 * 盘点处理
 */

namespace Erp\Model;

use Think\Model;

class StoreInventoryModel extends Model
{

    /**************
     * 把某一商品种类进行盘点
     * @param $admin_id 操作人员ID
     * @param $store_id 门店ID
     * @param $cate_id  商品种类ID
     */
    public function addRequestTempByCateId($admin_id, $store_id, $cate_id)
    {
        $this->startTrans();
        $temp_type = 8;
        $GoodsStoreModel = M("GoodsStore");
        $RequestTempModel = M("RequestTemp");
        $StoreModel = M("Store");

        //获取门店所在区域
        $shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (!is_null($store_datas) && !empty($store_datas) && count($store_datas) > 0) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }

        $sql = "select GS.goods_id,GS.store_id,GS.num as stock_num,ifnull(WIV.stock_price,0) as g_price ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "where GS.store_id={$store_id} and GC.id={$cate_id} ";
        //echo $sql;exit;
        $NewRequestTempEntitys = array();
        $UpdateRequestTempEntitys = array();
        $datas = $GoodsStoreModel->query($sql);
        foreach ($datas as $key => $val) {
            $tmp = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and goods_id={$val["goods_id"]} ")->limit(1)->select();
            if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                $UpdateRequestTempEntitys[] = array("id" => $tmp[0]["id"], "g_num" => $val["stock_num"], "g_price" => $val["g_price"]);
            } else {
                $RequestTempEntity = array();
                $RequestTempEntity["admin_id"] = $admin_id;
                $RequestTempEntity["store_id"] = $store_id;
                $RequestTempEntity["temp_type"] = $temp_type;
                $RequestTempEntity["goods_id"] = $val["goods_id"];
                $RequestTempEntity["ctime"] = time();
                $RequestTempEntity["status"] = 0;
                $RequestTempEntity["g_num"] = $val["stock_num"];
                $RequestTempEntity["g_price"] = $val["g_price"];
                $NewRequestTempEntitys[] = $RequestTempEntity;
            }
        }

        if (count($NewRequestTempEntitys) > 0) {
            $ok = $RequestTempModel->addAll($NewRequestTempEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "添加失败");
            }
        }

        $result = $this->saveAll("hii_request_temp", $UpdateRequestTempEntitys, "id", $RequestTempModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "添加失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /********************
     * 把所有库存商品进行盘点
     * @param $admin_id
     * @param $store_id
     */
    public function addRequestTempByStoreStock($admin_id, $store_id)
    {
        $this->startTrans();
        $temp_type = 8;
        $GoodsStoreModel = M("GoodsStore");
        $RequestTempModel = M("RequestTemp");
        $StoreModel = M("Store");

        //获取门店所在区域
        $shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (!is_null($store_datas) && !empty($store_datas) && count($store_datas) > 0) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }

        $sql = "select GS.goods_id,GS.store_id,GS.num as stock_num,ifnull(WIV.stock_price,0) as g_price ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "where GS.store_id={$store_id} ";
        $datas = $GoodsStoreModel->query($sql);

        $NewRequestTempEntitys = array();
        $UpdateRequestTempEntitys = array();
        foreach ($datas as $key => $val) {
            $tmp = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} and goods_id={$val["goods_id"]} ")->limit(1)->select();
            if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                $UpdateRequestTempEntitys[] = array("id" => $tmp[0]["id"], "g_num" => $val["stock_num"], "g_price" => $val["g_price"]);
            } else {
                $RequestTempEntity = array();
                $RequestTempEntity["admin_id"] = $admin_id;
                $RequestTempEntity["store_id"] = $store_id;
                $RequestTempEntity["temp_type"] = $temp_type;
                $RequestTempEntity["goods_id"] = $val["goods_id"];
                $RequestTempEntity["ctime"] = time();
                $RequestTempEntity["status"] = 0;
                $RequestTempEntity["g_num"] = $val["stock_num"];
                $RequestTempEntity["g_price"] = $val["g_price"];
                $NewRequestTempEntitys[] = $RequestTempEntity;
            }
        }

        if (count($NewRequestTempEntitys) > 0) {
            $ok = $RequestTempModel->addAll($NewRequestTempEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "添加失败");
            }
        }

        $result = $this->saveAll("hii_request_temp", $UpdateRequestTempEntitys, "id", $RequestTempModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "添加失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /********************
     * 提交临时盘点
     * @param $admin_id 管理员ID
     * @param $store_id 门店ID
     * @param $remark 备注
     * 注意：写入门店盘点表【hii_store_inventory,hii_store_inventory_detail】
     */
    public function submitRequestTemp($admin_id, $store_id, $remark)
    {
        $this->startTrans();
        $temp_type = 8;
        $RequestTempModel = M("RequestTemp");
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");

        $sql = "select RT.goods_id,RT.g_num,RT.g_price,RT.remark ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "where admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} ";

        $datas = $RequestTempModel->query($sql);

        //判断商品是否在门店库存表里
        $goodsStoreModel = M("GoodsStore");
        $goodsModel = M("Goods");
        $detail_array_goods_id = array_column($datas,"goods_id");
        $storeGoods_id = $goodsStoreModel ->field("goods_id")-> where(array("goods_id"=>array("in",$detail_array_goods_id),"store_id"=>$store_id))->select();
        if($storeGoods_id === NULL){
            $goods_data = $goodsModel->field("id,title")->where(array("id"=>array("in",$detail_array_goods_id)))->select();
        }else{
            $storeGoods_id = array_column($storeGoods_id,"goods_id");
            $detail_array_goods_id = array_diff($detail_array_goods_id,$storeGoods_id);
            if(!empty($detail_array_goods_id)){
                $goods_data = $goodsModel->field("id,title")->where(array("id"=>array("in",$detail_array_goods_id)))->select();
            }
        }
        if(!empty($goods_data)){
            return array("status" => "1", "msg" => $goods_data);
        }
        $StoreInventoryEntity = array();
        $StoreInventoryEntity["si_sn"] = get_new_order_no("PD", "hii_store_inventory", "si_sn");
        $StoreInventoryEntity["si_status"] = 0;
        $StoreInventoryEntity["si_type"] = 0;
        $StoreInventoryEntity["ctime"] = time();
        $StoreInventoryEntity["admin_id"] = $admin_id;
        $StoreInventoryEntity["store_id"] = $store_id;
        $StoreInventoryEntity["remark"] = $remark;
        $si_id = $StoreInventoryModel->add($StoreInventoryEntity);
        if ($si_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "添加盘点主表信息失败");
        }

        $g_type = 0;
        $g_nums = 0;
        $NewStoreInventoryDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $StoreInventoryDetailEntity = array();
            $StoreInventoryDetailEntity["si_id"] = $si_id;
            $StoreInventoryDetailEntity["goods_id"] = $val["goods_id"];
            $StoreInventoryDetailEntity["g_num"] = $val["g_num"];
            $StoreInventoryDetailEntity["g_price"] = $val["g_price"];
            $StoreInventoryDetailEntity["remark"] = $val["remark"];
            $NewStoreInventoryDetailEntitys[] = $StoreInventoryDetailEntity;
            $g_type++;
            $g_nums += $val["g_num"];
        }

        if (count($NewStoreInventoryDetailEntitys) > 0) {
            $ok = $StoreInventoryDetailModel->addAll($NewStoreInventoryDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "添加盘点子表信息失败");
            }
        }

        //更新主表g_nums和g_type
        $ok = $StoreInventoryModel->where(" si_id={$si_id} ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新盘点主表失败");
        }
        //清空临时申请表
        $ok = $RequestTempModel->where(" temp_type=8 and store_id={$store_id} and admin_id={$admin_id} ")->delete();
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "删除临时申请表失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /**************
     * 修改盘点单信息
     * @param $eadmin_id 修改人员ID
     * @param $store_id 门店ID
     * @param $si_id 盘点单ID
     * @param $info_json_array 修改的数据 [{"si_d_id":"1","g_num":"20","g_price":"2","remark":""},{si_d_id":"1","g_num":"20","g_price":"2","remark":""}]
     * @param $remark  备注
     */
    public function updateStoreInventory($eadmin_id, $store_id, $si_id, $info_json_array, $remark)
    {
        $this->startTrans();
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $StoreModel = M("Store");

        $sql = "select si_id from hii_store_inventory where si_id={$si_id} and store_id={$store_id} and si_status=0 order by si_id limit 1 ";
        $datas = $StoreInventoryModel->query($sql);
        if (is_null($datas) || !is_array($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "无法修改");
        }
        $etime = time();
        $ok = $StoreInventoryModel->where(" si_id={$si_id} ")->limit(1)->save(array("eadmin_id" => $eadmin_id, "etime" => $etime, "remark" => $remark));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "修改盘点主表信息失败");
        }

        $StoreInventoryDetailEntitys = array();
        foreach ($info_json_array as $key => $val) {
            $StoreInventoryDetailEntitys[] = array("si_d_id" => $val["si_d_id"], "g_num" => $val["g_num"], "remark" => $val["remark"], "audit_mark"=>$val['audit_mark'],'status'=>1);
        }

        $res = $this->saveAll("hii_store_inventory_detail", $StoreInventoryDetailEntitys, "si_d_id", $StoreInventoryDetailModel);
        if ($res["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "修改盘点子表信息失败");
        }
        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /******************
     * 盘点审核
     * @param $padmin_id 审核人员ID
     * @param string $store_id 门店ID
     * @param mixed $si_id 盘点单ID
     * 逻辑：1.判断盘点单是否可以进行审核
     *       2.判断盘点单子表是否有数据
     *       3.循环判断申请单子表盘点价是否为空
     *       4.循环判断io_num是否大于0
     *           4.1当io_num大于0 写入入库单【hii_store_in_stock,hii_store_in_stock_detail】
     *           4.2当io_num小于0 写入出库单【hii_store_out_stock,hii_store_stock_detail】
     */
    public function check($padmin_id, $store_id, $si_id)
    {
        $this->startTrans();
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $GoodsStoreModel = M("GoodsStore");
        $WarehouseInoutModel = M("WarehouseInout");
        $StoreModel = M("Store");
        $StoreOutStockModel = M("StoreOutStock");
        $StoreStockDetailModel = M("StoreStockDetail");
        $StoreRequestModel = M("StoreRequest");

        $datas = $StoreInventoryModel->where(" si_id={$si_id} and store_id={$store_id} and si_status=0 ")->limit(1)->lock(true)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            return array("status" => "0", "msg" => "没有盘点单或者盘点单已处理，不能再次审核");
        }
        $StoreInventoryEntity = $datas[0];
        $details = $StoreInventoryDetailModel->where(" si_id={$si_id} ")->select();
        if (is_null($details) || empty($details) || count($details) == 0) {
            return array("status" => "0", "msg" => "盘点单没有商品");
        }

        //判断盘点商品是否修改
        $audit_mark = $StoreInventoryDetailModel->field("goods_id")->where(array("si_id"=>$si_id,"audit_mark"=>0))->select();
        if(!is_null($audit_mark) || !empty($audit_mark) || count($audit_mark)!=0){
            $audit_mark_id = $GoodsStoreModel->where(array("store_id"=>$store_id,"num"=>array("NEQ",0),"goods_id"=>array("in",array_column($audit_mark,"goods_id"))))->find();
            //echo $GoodsStoreModel->getLastSql();
            if(!is_null($audit_mark_id) || !empty($audit_mark_id) || count($audit_mark_id)!=0)
                return array("status" => "0", "msg" => "盘点单商品未盘点,无法审核");
        }

        /*******
         * 查询是否有重复商品
         ******/
        $goods_datas = $StoreInventoryDetailModel->query("select goods_id,count(*) as count from hii_store_inventory_detail where si_id={$si_id} group by goods_id having count>1");
        if (!is_null($goods_datas) && !empty($goods_datas) && count($goods_datas) > 0) {
            return array("status" => "0", "msg" => "出现重复商品，无法审核");
        }

        //判断盈亏数量，盈亏数量为0时候不能审核
        /*
        $tmp = $StoreInventoryDetailModel->query(" select count(si_d_id) as num from hii_store_inventory_detail where si_id={$si_id} and b_num<>g_num ");
        if ($tmp[0]["num"] == 0) {
            return array("status" => "0", "msg" => "无盈亏记录，审核不通过");
        }*/

        //查找盈亏数量和盘点价格
        $sql = "select SID.si_d_id,ifnull(SID.g_price,0) as g_price,SID.goods_id,SID.g_num, ";
        $sql .= "(ifnull(SID.g_num,0)-ifnull(GS.num,0)) as io_num,ifnull(GS.num,0) as b_num ";
        $sql .= "from hii_store_inventory SI ";
        $sql .= "left join hii_store_inventory_detail SID on SID.si_id=SI.si_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=SID.goods_id and GS.store_id={$store_id} ";
        $sql .= "where SI.si_id={$si_id} and SI.store_id={$store_id} and SI.si_status=0 ";
        //echo $sql;exit;
        $pd_items = $StoreInventoryModel->query($sql);
        if (is_null($pd_items) || empty($pd_items) || count($pd_items) == 0) {
            return array("status" => "0", "msg" => "盘点单没有商品");
        }

        $store_shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($store_datas) || empty($store_datas) || count($store_datas) == 0) {
            return array("status" => "0", "msg" => "门店不存在");
        } else {
            $store_shequ_id = $store_datas[0]["shequ_id"];
        }

        $store_in_g_type = 0;
        $store_in_g_nums = 0;
        $store_in_detail_array = array();

        $store_out_g_type = 0;
        $store_out_g_nums = 0;
        $store_out_detail_array = array();

        $StoreInventoryDetailEntitys = array();//最后更新hii_store_inventory的b_num字段

        foreach ($pd_items as $key => $val) {
            /*
            if ($val["io_num"] != 0 && $val["g_price"] == 0) {
                return array("status" => "0", "msg" => "ID为" . $val["goods_id"] . "的商品没有盘点价");
            }*/
            $StoreInventoryDetailEntitys[] = array("si_d_id" => $val["si_d_id"], "b_num" => $val["b_num"]);

            if ($val["io_num"] > 0) {
                //盘盈入库,写入入库单
                $store_in_g_type++;
                $store_in_g_nums += $val["io_num"];
                $store_in_detail_array[] = array("goods_id" => $val["goods_id"], "g_num" => $val["io_num"], "g_price" => $val["g_price"]);
            } elseif ($val["io_num"] < 0) {
                //盘亏出库，写入出库单
                $store_out_g_type++;
                $tmp_g_num = abs($val["io_num"]);
                $store_out_g_nums += $tmp_g_num;
                $store_out_detail_array[] = array("goods_id" => $val["goods_id"], "g_num" => $tmp_g_num, "g_price" => $val["g_price"]);
            }
        }

        $update_time = time();

        //盘盈入库
        if ($store_in_g_type > 0) {
            /***************************************** 新增门店入库单 start ********************************************************************/
            $StoreInStockModel = M("StoreInStock");
            $StoreInStockDetailModel = M("StoreInStockDetail");
            //生成入库单并自动审核
            $StoreInStockEntity = array();
            $StoreInStockEntity["s_in_s_sn"] = get_new_order_no("RK", "hii_store_in_stock", "s_in_s_sn");
            $StoreInStockEntity["s_in_s_status"] = 1;//已审核
            $StoreInStockEntity["s_in_s_type"] = 2;
            $StoreInStockEntity["si_id"] = $si_id;
            $StoreInStockEntity["ctime"] = time();
            $StoreInStockEntity["admin_id"] = $padmin_id;
            $StoreInStockEntity["ptime"] = time();
            $StoreInStockEntity["padmin_id"] = $padmin_id;
            $StoreInStockEntity["store_id2"] = $store_id;
            $StoreInStockEntity["remark"] = $StoreInventoryEntity["remark"];
            $StoreInStockEntity["g_type"] = $store_in_g_type;
            $StoreInStockEntity["g_nums"] = $store_in_g_nums;

            $s_in_s_id = $StoreInStockModel->add($StoreInStockEntity);
            if ($s_in_s_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "添加入库单主表信息失败");
            }
            $StoreInStockDetailEntitys = array();
            foreach ($store_in_detail_array as $key => $val) {
                $StoreInStockDetailEntity = array();
                $StoreInStockDetailEntity["s_in_s_id"] = $s_in_s_id;
                $StoreInStockDetailEntity["goods_id"] = $val["goods_id"];
                $StoreInStockDetailEntity["g_num"] = $val["g_num"];
                $StoreInStockDetailEntity["g_price"] = $val["g_price"];
                $StoreInStockDetailEntitys[] = $StoreInStockDetailEntity;
            }
            if (count($StoreInStockDetailEntitys) > 0) {
                $ok = $StoreInStockDetailModel->addAll($StoreInStockDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "添加入库单子表信息失败");
                }
            }
            /***************************************** 新增门店入库单 end ********************************************************************/

            /***************************************** 更新门店库存，插入批次 start ********************************************************************/
            $NewGoodsStoreEntitys = array();
            $UpdateGoodsStoreEntitys = array();
            $NewWarehouseInoutEntitys = array();

            foreach ($store_in_detail_array as $key => $val) {
                $tmp = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                    $tmp_num = 0;
                    if (!is_null($tmp[0]["num"]) && !empty($tmp[0]["num"])) {
                        $tmp_num = $tmp[0]["num"];
                    }
                    $newnum = $val["g_num"] + $tmp_num;
                    $UpdateGoodsStoreEntitys[] = array("id" => $tmp[0]["id"], "num" => $newnum, "update_time" => $update_time);
                } else {
                    $NewGoodsStoreEntitys[] = array("goods_id" => $val["goods_id"], "store_id" => $store_id, "num" => $val["g_num"], "update_time" => $update_time);
                }
            }
            if (count($NewGoodsStoreEntitys) > 0) {
                $ok = $GoodsStoreModel->addAll($NewGoodsStoreEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增库存信息失败");
                }
            }
            $result = $this->saveAll("hii_goods_store", $UpdateGoodsStoreEntitys, "id", $GoodsStoreModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店库存信息失败");
            }

            $store_in_stock_detail_data = $StoreInStockDetailModel->where(" s_in_s_id={$s_in_s_id} ")->select();
            foreach ($store_in_stock_detail_data as $key => $val) {
                $NewWarehouseInoutEntitys[] = array(
                    "goods_id" => $val["goods_id"],
                    "innum" => $val["g_num"],
                    "inprice" => $val["g_price"],
                    "num" => $val["g_num"],
                    "ctime" => $update_time,
                    "ctype" => 1,
                    "shequ_id" => $store_shequ_id,
                    "store_id" => $store_id,
                    "s_in_s_d_id" => $val["s_in_s_d_id"]
                );
            }
            if (count($NewWarehouseInoutEntitys) > 0) {
                $ok = $WarehouseInoutModel->addAll($NewWarehouseInoutEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增入库批次信息失败");
                }
            }
            /***************************************** 更新门店库存，插入批次 end ********************************************************************/
        }
        //盘亏出库
        if ($store_out_g_type > 0) {
            /*************************** 盘亏出库处理 *******************************/
            /*******************
             * 盘亏【库存数量比实际盘点数量多】出库逻辑：
             * 1.减库存
             * 2.减批次表【hii_warehouse_inout】
             *        2.1 先判断当前门店批次是否够减
             *        2.2 当前门店不够减的话再从仓库减
             * 3.更新hii_store_out_stock的审核信息
             ********************/
            /***************************************** 新增门店出库单 start ********************************************************************/
            $StoreOutStockEntity = array();
            $StoreOutStockEntity["s_out_s_sn"] = get_new_order_no("CK", "hii_store_out_stock", "s_out_s_sn");
            $StoreOutStockEntity["s_out_s_status"] = 1;//已审核转出库
            $StoreOutStockEntity["s_out_s_type"] = 3;//盘亏出库
            $StoreOutStockEntity["si_id"] = $si_id;
            $StoreOutStockEntity["ctime"] = time();
            $StoreOutStockEntity["admin_id"] = $padmin_id;
            $StoreOutStockEntity["ptime"] = time();
            $StoreOutStockEntity["padmin_id"] = $padmin_id;
            $StoreOutStockEntity["store_id2"] = $store_id;
            $StoreOutStockEntity["remark"] = $StoreInventoryEntity["remark"];
            $StoreOutStockEntity["g_type"] = $store_out_g_type;
            $StoreOutStockEntity["g_nums"] = $store_out_g_nums;

            $s_out_s_id = $StoreOutStockModel->add($StoreOutStockEntity);
            if ($s_out_s_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "添加出库单主表信息失败");
            }
            $StoreStockDetailEntitys = array();
            $UpdateGoodsStoreEntitys = array();
            $NewGoodsStoreEntitys = array();
            foreach ($store_out_detail_array as $key => $val) {
                $tmp = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                    $newnum = $tmp[0]["num"] - $val["g_num"];
                    $UpdateGoodsStoreEntitys[] = array("id" => $tmp[0]["id"], "num" => $newnum, "update_time" => $update_time);
                } else {
                    $NewGoodsStoreEntitys[] = array(
                        "goods_id" => $val["goods_id"],
                        "store_id" => $store_id,
                        "num" => -$val["g_num"],
                        "update_time" => $update_time
                    );
                }
                $StoreStockDetailEntity = array();
                $StoreStockDetailEntity["s_out_s_id"] = $s_out_s_id;
                $StoreStockDetailEntity["goods_id"] = $val["goods_id"];
                $StoreStockDetailEntity["g_num"] = $val["g_num"];
                $StoreStockDetailEntity["g_price"] = $val["g_price"];
                $StoreStockDetailEntitys[] = $StoreStockDetailEntity;
            }
            if (count($StoreStockDetailEntitys) > 0) {
                $ok = $StoreStockDetailModel->addAll($StoreStockDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "添加出库单子表信息失败");
                }
            }
            if (count($NewGoodsStoreEntitys) > 0) {
                $ok = $GoodsStoreModel->addAll($NewGoodsStoreEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "新增门店库存信息失败");
                }
            }

            //更新门店库存
            $result = $this->saveAll("hii_goods_store", $UpdateGoodsStoreEntitys, "id", $GoodsStoreModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "更新门店库存信息失败");
            }
            /***************************************** 新增门店出库单 end ********************************************************************/

            /***************************************** 减库存减批次 start ********************************************************************/
            /**********************
             * 减批次：a.判断当前门店批次是否够减
             *         b.当门店批次不够减的时候，往仓库批次减
             * 扣减逻辑：先进先扣
             **********************/
            $etime = time();
            $UpdateWarehouseInoutEntitys = array();//门店库存更新信息
            foreach ($store_out_detail_array as $key => $val) {
                $goods_id = $val["goods_id"];
                $g_num = $val["g_num"];//盘亏数量

                /********************* 先扣减门店入库批次，先进先扣原则 start ***********************/
                $store_pc_inout = $WarehouseInoutModel->where(" goods_id={$goods_id} and store_id={$store_id} and num>0 ")->order(" inout_id asc ")->select();

                foreach ($store_pc_inout as $k => $v) {
                    if ($g_num > 0) {
                        $num = $v["num"];//当前批次数量
                        $e_no = $v["e_no"] + 1;
                        if ($num >= $g_num) {
                            $num = $num - $g_num;
                            $outnum = $v["outnum"] + $g_num;
                            $UpdateWarehouseInoutEntitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "num" => $num,
                                "outnum" => $outnum,
                                "etime" => $etime,
                                "etype" => 2,
                                "enum" => $g_num,
                                "e_no" => $e_no
                            );
                            $g_num = 0;
                        } else {
                            $num = 0;
                            $outnum = $v["outnum"] + $v["num"];
                            $UpdateWarehouseInoutEntitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "num" => $num,
                                "outnum" => $outnum,
                                "etime" => $etime,
                                "etype" => 2,
                                "enum" => $v["num"],
                                "e_no" => $e_no
                            );
                            $g_num = $g_num - $v["num"];
                        }
                    } else {
                        break;
                    }
                }
                /********************* 先扣减门店入库批次，先进先扣原则 end ***********************/

                /********************* 当门店批次扣减还剩余的话，对仓库批次进行扣减 start ***********************/
                //$store_shequ_id 门店社区ID
                if ($g_num > 0) {
                    $warehouse_pc_inout = $WarehouseInoutModel->where(" `goods_id`={$goods_id} and `warehouse_id`<>0 and `store_id`=0 and `shequ_id`={$store_shequ_id} and `num`>0 ")->order(" `inout_id` asc ")->select();
                    foreach ($warehouse_pc_inout as $k => $v) {
                        if ($g_num > 0) {
                            if ($v["num"] >= $g_num) {
                                $outnum = $v["outnum"] + $g_num;//已出数量
                                $num = $v["num"] - $g_num;//现有数量
                                $e_no = $v["e_no"] + 1;
                                $UpdateWarehouseInoutEntitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $etime,
                                    "etype" => 2,
                                    "enum" => $g_num,
                                    "e_no" => $e_no
                                );
                                $g_num = 0;
                                break;
                            } else {
                                $outnum = $v["outnum"] + $v["num"];
                                $num = 0;
                                $e_no = $v["e_no"] + 1;
                                $UpdateWarehouseInoutEntitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $etime,
                                    "etype" => 2,
                                    "enum" => $v["num"],
                                    "e_no" => $e_no
                                );
                                $g_num = $g_num - $v["num"];
                            }
                        } else {
                            break;
                        }
                    }
                }
                /********************* 当门店批次扣减还剩余的话，对同一区域仓库批次进行扣减 end ***********************/

                /********************* 当门店批次扣减还剩余的话，对同一区域其他门店批次进行扣减 start ***********************/
                //$store_shequ_id 门店社区ID
                if ($g_num > 0) {
                    $warehouse_pc_inout = $WarehouseInoutModel->where(" `goods_id`={$goods_id} and `warehouse_id`=0 and `store_id`>0 and `shequ_id`={$store_shequ_id} and `num`>0 ")->order(" `inout_id` asc ")->select();
                    foreach ($warehouse_pc_inout as $k => $v) {
                        if ($g_num > 0) {
                            if ($v["num"] >= $g_num) {
                                $outnum = $v["outnum"] + $g_num;//已出数量
                                $num = $v["num"] - $g_num;//现有数量
                                $e_no = $v["e_no"] + 1;
                                $UpdateWarehouseInoutEntitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $etime,
                                    "etype" => 2,
                                    "enum" => $g_num,
                                    "e_no" => $e_no
                                );
                                $g_num = 0;
                                break;
                            } else {
                                $outnum = $v["outnum"] + $v["num"];
                                $num = 0;
                                $e_no = $v["e_no"] + 1;
                                $UpdateWarehouseInoutEntitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $etime,
                                    "etype" => 2,
                                    "enum" => $v["num"],
                                    "e_no" => $e_no
                                );
                                $g_num = $g_num - $v["num"];
                            }
                        } else {
                            break;
                        }
                    }
                }
                /********************* 当门店批次扣减还剩余的话，对仓库批次进行扣减 end ***********************/
            }
            $result = $this->saveAll("hii_warehouse_inout", $UpdateWarehouseInoutEntitys, "inout_id", $WarehouseInoutModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "扣减批次失败");
            }
            /***************************************** 减库存减批次 end ********************************************************************/

        }


        //更新门店盘点表【hii_store_inventory】信息
        $ptime = time();
        $tmp_data = $StoreInventoryDetailModel->query("select SUM(g_num) as nums from hii_store_inventory_detail where si_id={$si_id} ");
        $g_nums = 0;
        if (!is_null($tmp_data) && !empty($tmp_data) && count($tmp_data) > 0) {
            $g_nums = $tmp_data[0]["nums"];
        }
        $ok = $StoreInventoryModel->where(" si_id={$si_id} ")->limit(1)->save(array("ptime" => $ptime, "padmin_id" => $padmin_id, "g_nums" => $g_nums, "si_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新盘点主表信息失败");
        }

        //更新盘点子表b_num字段
        $result = $this->saveAll("hii_store_inventory_detail", $StoreInventoryDetailEntitys, "si_d_id", $StoreInventoryDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "修改盘点子表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
    }

    /*********************
     * 提交月盘点
     * @param $admin_id 管理员ID
     * @param $store_id 门店ID
     */
    public function monthInventoryAdd($admin_id, $store_id)
    {
        $this->startTrans();
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $StoreModel = M("Store");
        $GoodsModel = M("Goods");

        //查找门店所在社区
        $shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (!is_null($store_datas) && !empty($store_datas) && count($store_datas) > 0) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }

        $sql = "select G.id as goods_id ,ifnull(WIV.stock_price,0) as g_price,ifnull(GS.num,0) as g_num ";
        $sql .= "from hii_goods G ";
        $sql .= "join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id} ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id and WIV.shequ_id={$shequ_id} ";
        $datas = $GoodsModel->query($sql);
        $g_type = 0;
        $g_nums = 0;

        //判断是否存在这个月的月末盘点，是的话删除
        $tmp = $StoreInventoryModel->where(" store_id={$store_id} and si_type=1 and DATE_FORMAT( FROM_UNIXTIME(ctime,'%Y-%m-%d %H:%i:%s'),'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m') ")->order(" si_id desc ")->limit(1)->select();
        if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
            return array("status" => "0", "msg" => "本月份月末盘点单已存在");
        }

        //新增盘点主表信息
        $StoreInventoryEntity = array();
        $StoreInventoryEntity["si_sn"] = get_new_order_no("PD", "hii_store_inventory", "si_sn");
        $StoreInventoryEntity["si_status"] = 0;
        $StoreInventoryEntity["si_type"] = 1;
        $StoreInventoryEntity["ctime"] = time();
        $StoreInventoryEntity["etime"] = time();
        $StoreInventoryEntity["admin_id"] = $admin_id;
        $StoreInventoryEntity["store_id"] = $store_id;
        $StoreInventoryEntity["remark"] = "月末盘点单";
        $si_id = $StoreInventoryModel->add($StoreInventoryEntity);
        if ($si_id === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "新增盘点主表信息失败");
        }

        $StoreInventoryDetailEntitys = array();
        foreach ($datas as $key => $val) {
            $StoreInventoryDetailEntity = array();
            $StoreInventoryDetailEntity["si_id"] = $si_id;
            $StoreInventoryDetailEntity["goods_id"] = $val["goods_id"];
            $StoreInventoryDetailEntity["g_num"] = 0; //$val["g_num"];
            $StoreInventoryDetailEntity["g_price"] = $val["g_price"];
            $StoreInventoryDetailEntitys[] = $StoreInventoryDetailEntity;
            $g_type++;
            //$g_nums += $val["g_num"];
        }
        if (count($StoreInventoryDetailEntitys) > 0) {
            $ok = $StoreInventoryDetailModel->addAll($StoreInventoryDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增盘点子表信息失败");
            }
        }

        $ok = $StoreInventoryModel->where(" si_id={$si_id} ")->order(" si_id desc ")->limit(1)->save(array("g_type" => $g_type, "g_nums" => $g_nums));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新盘点主表信息失败");
        }

        $this->commit();
        return array("status" => "200", "msg" => "操作成功");
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