<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-08
 * Time: 15:37
 */

namespace Internal\Model;

use Think\Model;

class StoreStockModel extends Model
{
    /**********
     * 新增普通盘点单
     * @param $uid 会员ID
     * @param $store_id 门店ID
     * @param $remark  主表备注
     * @param $detail_array 子表信息数组
     */
    public function add_normal_inventory($uid, $store_id, $remark, $detail_array)
    {
        $this->startTrans();
        $StoreModel = M("Store");
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $current_time = time();
        $g_type = 0;
        $g_nums = 0;

        $store_data = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($store_data) || empty($store_data) || count($store_data) == 0) {
            return array("status" => 0, "msg" => "门店不存在");
        }

        foreach ($detail_array as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
        }

        //新增盘点主表信息
        $store_inventory_entity = array();
        $si_sn = get_new_order_no("PD", "hii_store_inventory", "si_sn");
        $store_inventory_entity["si_sn"] = $si_sn;
        $store_inventory_entity["si_status"] = 0;
        $store_inventory_entity["si_type"] = 0;
        $store_inventory_entity["ctime"] = $current_time;
        $store_inventory_entity["admin_id"] = $uid;
        $store_inventory_entity["store_id"] = $store_id;
        $store_inventory_entity["remark"] = $remark;
        $store_inventory_entity["g_type"] = $g_type;
        $store_inventory_entity["g_nums"] = $g_nums;
        $si_id = $StoreInventoryModel->add($store_inventory_entity);
        if ($si_id === false) {
            $this->rollback();
            return array("status" => 0, "msg" => "新增盘点主表信息失败");
        }

        $store_inventory_detail_entitys = array();
        foreach ($detail_array as $key => $val) {
            $store_inventory_detail_entitys[] = array(
                "si_id" => $si_id,
                "goods_id" => $val["goods_id"],
                "b_num" => 0,
                "g_num" => $val["g_num"],
                "remark" => $val["remark"],
                "audit_mark" => $val['audit_mark']
            );
        }
        if (count($store_inventory_detail_entitys) > 0) {
            $ok = $StoreInventoryDetailModel->addAll($store_inventory_detail_entitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => 0, "msg" => "新增盘点子表信息失败");
            }
        }

        $this->commit();
        return array("status" => 200, "msg" => "操作成功");
    }

    /*********
     * 新增月末盘点
     * @param $uid 会员ID
     * @param $store_id 门店ID
     */
    public function add_month_inventory($uid, $store_id)
    {
        $this->startTrans();
        $GoodsModel = M("Goods");
        $StoreModel = M("Store");
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $current_time = time();
        $g_type = 0;
        $g_nums = 0;
        $shequ_id = 0;

        $store_data = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($store_data) || empty($store_data) || count($store_data) == 0) {
            return array("status" => 0, "msg" => "门店不存在");
        }
        //判断新增盘点单如果超过24小时没有审核删除
        $time = time()-24*3600;
        $query = $StoreInventoryModel->field('si_id')->where(array('si_type'=>1,'etime'=>array('ELT',$time),'si_status'=>0,'store_id'=>$store_id))->select();
        if(!empty($query)){
            foreach ($query as $key=>$val){
                $delete = $StoreInventoryModel->where(array('si_id'=>$val['si_id']))->delete();
                if(empty($delete)){
                    $this->rollback();
                    return array("status" => 0, "msg" => "删除过期月末盘点主表失败");
                }
                $delete = $StoreInventoryDetailModel->where(array('si_id'=>$val['si_id']))->delete();
                if(empty($delete)){
                    $this->rollback();
                    return array("status" => 0, "msg" => "删除过期月末盘点字表失败");
                }
            }
        }


        $shequ_id = $store_data[0]["shequ_id"];

        //判断是否存在这个月的月末盘点
        $tmp = $StoreInventoryModel->where(" store_id={$store_id} and si_type=1 and DATE_FORMAT( FROM_UNIXTIME(ctime,'%Y-%m-%d %H:%i:%s'),'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m') ")->order(" si_id desc ")->limit(1)->select();
        if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
            return array("status" => 100, "msg" => "本月份月末盘点单已存在", "si_id" => $tmp[0]["si_id"]);
        }

        //查找商品信息
        $sql = "select G.id as goods_id ,ifnull(WIV.stock_price,0) as g_price,ifnull(GS.num,0) as g_num ";
        $sql .= "from hii_goods G ";
        $sql .= "join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id} ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id and WIV.shequ_id={$shequ_id} ";
        $datas = $GoodsModel->query($sql);

        //新增月末盘点单主表信息
        $store_inventory_entity = array();
        $si_sn = get_new_order_no("PD", "hii_store_inventory", "si_sn");
        $store_inventory_entity["si_sn"] = $si_sn;
        $store_inventory_entity["si_status"] = 0;
        $store_inventory_entity["si_type"] = 1;
        $store_inventory_entity["ctime"] = $current_time;
        $store_inventory_entity["etime"] = $current_time;
        $store_inventory_entity["admin_id"] = $uid;
        $store_inventory_entity["store_id"] = $store_id;
        $store_inventory_entity["remark"] = "月末盘点单";
        $store_inventory_entity["g_type"] = count($datas);
        $store_inventory_entity["g_nums"] = $g_nums;
        $si_id = $StoreInventoryModel->add($store_inventory_entity);
        if ($si_id === false) {
            $this->rollback();
            return array("status" => 0, "msg" => "新增盘点主表信息失败");
        }

        //新增月末盘点子表信息
        $store_inventory_detail_entitys = array();
        foreach ($datas as $key => $val) {
            $store_inventory_detail_entitys[] = array(
                "si_id" => $si_id,
                "goods_id" => $val["goods_id"],
                "g_num" => 0,
                "g_price" => $val["g_price"]
            );
        }
        if (count($store_inventory_detail_entitys) > 0) {
            $ok = $StoreInventoryDetailModel->addAll($store_inventory_detail_entitys);
            if ($ok === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "新增盘点子表信息失败");
            }
        }

        $this->commit();
        return array("status" => 200, "msg" => "操作成功", "si_id" => $si_id);
    }

    /**************
     * 修改盘点信息
     * @param $uid 用户ID
     * @param $store_id 门店ID
     * @param $si_id 盘点单ID
     * @param $remark 主表备注
     * @param $detail_array 子表信息 格式：[{"si_d_id":"1","g_num":"20","remark":""},{"si_d_id":"1","g_num":"20","remark":""}]
     */
    public function update_store_inventory($uid, $store_id, $si_id, $remark, $detail_array)
    {
        $this->startTrans();
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");

        $store_inventory_data = $StoreInventoryModel->where(" `si_id`={$si_id} and `store_id`={$store_id} and `si_status`=0 ")->limit(1)->select();
        if (is_null($store_inventory_data) || empty($store_inventory_data) || count($store_inventory_data) == 0) {
            return array("status" => 0, "msg" => "无法修改");
        }

        $store_inventory_detail_entitys = array();
        foreach ($detail_array as $key => $val) {
            $store_inventory_detail_entitys[] = array(
                "si_d_id" => $val["si_d_id"],
                "g_num" => $val["g_num"],
                "status" => 1,
                "remark" => strtolower($val["remark"]) == "null" ? '' : $val["remark"],
                "audit_mark" => $val['audit_mark']
            );
        }

        if (count($store_inventory_detail_entitys) > 0) {
            $res = $this->saveAll("hii_store_inventory_detail", $store_inventory_detail_entitys, "si_d_id", $StoreInventoryDetailModel);
            if ($res["status"] == "0") {
                $this->rollback();
                return array("status" => "0", "msg" => "修改盘点子表信息失败");
            }
        }

        $store_inventory_detail_entitys = null;
        $store_inventory_detail_entitys = $StoreInventoryDetailModel->where(" `si_id`={$si_id} ")->select();
        $g_type = 0;
        $g_nums = 0;
        foreach ($store_inventory_detail_entitys as $key => $val) {
            $g_type++;
            $g_nums += $val["g_num"];
        }

        $savedata = array();
        $savedata["eadmin_id"] = $uid;
        $savedata["etime"] = time();
        $savedata["g_type"] = $g_type;
        $savedata["g_nums"] = $g_nums;
        $savedata["remark"] = $remark;
        $ok = $StoreInventoryModel->where(" `si_id`={$si_id} ")->limit(1)->save($savedata);
        if ($ok === false) {
            $this->rollback();
            return array("status" => 0, "msg" => "修改盘点单主表信息失败");
        }

        $this->commit();
        return array("status" => 200, "msg" => "操作成功");
    }

    /***************
     * 盘点审核
     * @param string $uid 会员ID
     * @param mixed $store_id 门店ID
     * @param string $si_id 盘点单ID
     */
    public function check($uid, $store_id, $si_id)
    {
        $this->startTrans();
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $GoodsStoreModel = M("GoodsStore");
        $WarehouseInoutModel = M("WarehouseInout");
        $StoreModel = M("Store");
        $StoreOutStockModel = M("StoreOutStock");
        $StoreStockDetailModel = M("StoreStockDetail");

        $store_inventory_data = $StoreInventoryModel->where(" `si_id`={$si_id} and `store_id`={$store_id} and `si_status`=0 ")->limit(1)->lock(true)->select();
        if (is_null($store_inventory_data) || empty($store_inventory_data) || count($store_inventory_data) == 0) {
            return array("status" => 0, "msg" => "无法审核");
        }
        $store_inventory_entity = $store_inventory_data[0];

        //获取门店所在社区ID
        $shequ_id = 0;
        $store_data = $StoreModel->where(" `id`={$store_id} ")->limit(1)->select();
        if (is_null($store_data) || empty($store_data) || count($store_data) == 0) {
            return array("status" => 0, "msg" => "获取门店区域ID失败");
        }
        $shequ_id = $store_data[0]["shequ_id"];

        //检测盘点单是否有重复数据
        $tmp_data = $StoreInventoryDetailModel->query("select goods_id,count(*) as count from hii_store_inventory_detail where si_id={$si_id} group by goods_id having count>1");
        if (!is_null($tmp_data) && !empty($tmp_data) && count($tmp_data) > 0) {
            return array("status" => 0, "msg" => "出现重复商品，无法审核");
        }
        //判断盘点单商品是否修改
        $audit_mark = $StoreInventoryDetailModel->field("goods_id")->where(array("si_id"=>$si_id,"audit_mark"=>0))->select();
        if (!is_null($audit_mark) || !empty($audit_mark) || count($audit_mark) > 0) {
            $audit_mark_id = $GoodsStoreModel->where(array("store_id"=>$store_id,"num"=>array("NEQ",0),"goods_id"=>array("in",array_column($audit_mark,"goods_id"))))->find();
            //echo $GoodsStoreModel->getLastSql();
            if(!is_null($audit_mark_id) || !empty($audit_mark_id) || count($audit_mark_id)!=0)
                return array("status" => 0, "msg" => "还有商品未盘点，无法审核");
        }

        //查找盈亏数量和盘点价格
        $sql = "select SID.si_d_id,ifnull(WIV.stock_price,0) as g_price,SID.goods_id,SID.g_num, ";
        $sql .= "(ifnull(SID.g_num,0)-ifnull(GS.num,0)) as io_num,ifnull(GS.num,0) as b_num ";
        $sql .= "from hii_store_inventory_detail SID ";
        $sql .= "left join hii_store_inventory SI on SI.si_id=SID.si_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=SID.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=SID.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "where SI.si_id={$si_id} and SI.store_id={$store_id} and SI.si_status=0 ";

        $pd_items = $StoreInventoryModel->query($sql);
        if (is_null($pd_items) || empty($pd_items) || count($pd_items) == 0) {
            return array("status" => 0, "msg" => "盘点单没有商品");
        }

        //盘盈数据
        $store_in_g_type = 0;
        $store_in_g_nums = 0;
        $store_in_detail_array = array();

        //盘亏数据
        $store_out_g_type = 0;
        $store_out_g_nums = 0;
        $store_out_detail_array = array();

        //盘点单子表更新数据
        $update_store_inventory_detail_entitys = array();

        foreach ($pd_items as $key => $val) {
            $update_store_inventory_detail_entitys[] = array("si_d_id" => $val["si_d_id"], "g_price" => $val["g_price"], "b_num" => $val["b_num"]);
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

        $current_time = time();

        //盘盈入库
        if ($store_in_g_type > 0) {
            $StoreInStockModel = M("StoreInStock");
            $StoreInStockDetailModel = M("StoreInStockDetail");
            //新增门店入库单主表信息
            $store_in_stock_entity = array();
            $store_in_stock_entity["s_in_s_sn"] = get_new_order_no("RK", "hii_store_in_stock", "s_in_s_sn");
            $store_in_stock_entity["s_in_s_status"] = 1;//已审核
            $store_in_stock_entity["s_in_s_type"] = 2;
            $store_in_stock_entity["si_id"] = $si_id;
            $store_in_stock_entity["ctime"] = $current_time;
            $store_in_stock_entity["admin_id"] = $uid;
            $store_in_stock_entity["ptime"] = $current_time;
            $store_in_stock_entity["padmin_id"] = $uid;
            $store_in_stock_entity["store_id2"] = $store_id;
            $store_in_stock_entity["remark"] = $store_inventory_entity["remark"];
            $store_in_stock_entity["g_type"] = $store_in_g_type;
            $store_in_stock_entity["g_nums"] = $store_in_g_nums;
            $s_in_s_id = $StoreInStockModel->add($store_in_stock_entity);
            if ($s_in_s_id === false) {
                $this->rollback();
                return array("status" => 0, "msg" => "添加入库单主表信息失败");
            }
            //新增门店入库单子表信息
            $store_in_stock_detail_entitys = array();
            foreach ($store_in_detail_array as $key => $val) {
                $store_in_stock_detail_entitys[] = array(
                    "s_in_s_id" => $s_in_s_id,
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "g_price" => $val["g_price"]
                );
            }
            if (count($store_in_stock_detail_entitys) > 0) {
                $ok = $StoreInStockDetailModel->addAll($store_in_stock_detail_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "添加入库单子表信息失败");
                }
            }

            //更新门店库存
            $new_goods_store_entitys = array();//新增库存信息
            $update_goods_store_entitys = array();//更新库存信息
            $new_warehouse_inout_entitys = array();//新增批次信息
            foreach ($store_in_detail_array as $key => $val) {
                //库存信息
                $tmp = $GoodsStoreModel->where(" `goods_id`={$val["goods_id"]} and `store_id`={$store_id} ")->limit(1)->select();
                if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                    $tmp_num = 0;
                    if (!is_null($tmp[0]["num"]) && !empty($tmp[0]["num"])) {
                        $tmp_num = $tmp[0]["num"];
                    }
                    $newnum = $val["g_num"] + $tmp_num;
                    $update_goods_store_entitys[] = array("id" => $tmp[0]["id"], "num" => $newnum, "update_time" => $current_time);
                } else {
                    $new_goods_store_entitys[] = array("goods_id" => $val["goods_id"], "store_id" => $store_id, "num" => $val["g_num"], "update_time" => $current_time);
                }
            }

            if (count($new_goods_store_entitys) > 0) {
                $ok = $GoodsStoreModel->addAll($new_goods_store_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "新增库存信息失败");
                }
            }
            $result = $this->saveAll("hii_goods_store", $update_goods_store_entitys, "id", $GoodsStoreModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => 0, "msg" => "更新门店库存信息失败");
            }

            $store_in_stock_detail_data = $StoreInStockDetailModel->where(" s_in_s_id={$s_in_s_id} ")->select();
            foreach ($store_in_stock_detail_data as $key => $val) {
                //批次信息
                $new_warehouse_inout_entitys[] = array(
                    "goods_id" => $val["goods_id"],
                    "innum" => $val["g_num"],
                    "inprice" => $val["g_price"],
                    "num" => $val["g_num"],
                    "ctime" => $current_time,
                    "ctype" => 1,
                    "shequ_id" => $shequ_id,
                    "store_id" => $store_id,
                    "s_in_s_d_id" => $val["s_in_s_d_id"]
                );
            }
            if (count($new_warehouse_inout_entitys) > 0) {
                $ok = $WarehouseInoutModel->addAll($new_warehouse_inout_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "新增入库批次信息失败");
                }
            }

        }

        //盘亏出库
        if ($store_out_g_type > 0) {
            /*******************
             * 盘亏【库存数量比实际盘点数量多】出库逻辑：
             * 1.减库存
             * 2.减批次表【hii_warehouse_inout】
             *        2.1 先判断当前门店批次是否够减
             *        2.2 当前门店不够减的话再从仓库减
             * 3.更新hii_store_out_stock的审核信息
             ********************/
            //新增出库单主表信息
            $store_out_stock_entity = array();
            $store_out_stock_entity["s_out_s_sn"] = get_new_order_no("CK", "hii_store_out_stock", "s_out_s_sn");
            $store_out_stock_entity["s_out_s_status"] = 1;//已审核转出库
            $store_out_stock_entity["s_out_s_type"] = 3;//盘亏出库
            $store_out_stock_entity["si_id"] = $si_id;
            $store_out_stock_entity["ctime"] = time();
            $store_out_stock_entity["admin_id"] = $uid;
            $store_out_stock_entity["ptime"] = $current_time;
            $store_out_stock_entity["padmin_id"] = $uid;
            $store_out_stock_entity["store_id2"] = $store_id;
            $store_out_stock_entity["remark"] = $store_inventory_entity["remark"];
            $store_out_stock_entity["g_type"] = $store_out_g_type;
            $store_out_stock_entity["g_nums"] = $store_out_g_nums;
            $s_out_s_id = $StoreOutStockModel->add($store_out_stock_entity);
            if ($s_out_s_id === false) {
                $this->rollback();
                return array("status" => "0", "msg" => "添加出库单主表信息失败");
            }
            //新增出库单子表信息
            $store_out_stock_detail_entitys = array();//出库单子表数据
            $update_goods_store_entitys = array();//更新门店库存数据
            $new_goods_store_entitys = array();//新增门店库存数据
            foreach ($store_out_detail_array as $key => $val) {
                $tmp = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
                    $newnum = $tmp[0]["num"] - $val["g_num"];
                    $update_goods_store_entitys[] = array("id" => $tmp[0]["id"], "num" => $newnum, "update_time" => $current_time);
                } else {
                    $new_goods_store_entitys[] = array(
                        "goods_id" => $val["goods_id"],
                        "store_id" => $store_id,
                        "num" => -$val["g_num"],
                        "update_time" => $current_time
                    );
                }
                $store_out_stock_detail_entitys[] = array(
                    "s_out_s_id" => $s_out_s_id,
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["g_num"],
                    "g_price" => $val["g_price"]
                );
            }

            if (count($store_out_stock_detail_entitys) > 0) {
                $ok = $StoreStockDetailModel->addAll($store_out_stock_detail_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "添加出库单子表信息失败");
                }
            }

            if (count($new_goods_store_entitys) > 0) {
                $ok = $GoodsStoreModel->addAll($new_goods_store_entitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("status" => 0, "msg" => "新增门店库存信息失败");
                }
            }

            //更新门店库存
            $result = $this->saveAll("hii_goods_store", $update_goods_store_entitys, "id", $GoodsStoreModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => 0, "msg" => "更新门店库存信息失败");
            }

            //扣减批次
            $update_warehouse_inout_entitys = array();//门店库存更新信息
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
                            $update_warehouse_inout_entitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "num" => $num,
                                "outnum" => $outnum,
                                "etime" => $current_time,
                                "etype" => 2,
                                "enum" => $g_num,
                                "e_no" => $e_no
                            );
                            $g_num = 0;
                        } else {
                            $num = 0;
                            $outnum = $v["outnum"] + $v["num"];
                            $update_warehouse_inout_entitys[] = array(
                                "inout_id" => $v["inout_id"],
                                "num" => $num,
                                "outnum" => $outnum,
                                "etime" => $current_time,
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
                if ($g_num > 0) {
                    $warehouse_pc_inout = $WarehouseInoutModel->where(" `goods_id`={$goods_id} and `warehouse_id`<>0 and `store_id`=0 and `shequ_id`={$shequ_id} and `num`>0 ")->order(" `inout_id` asc ")->select();
                    foreach ($warehouse_pc_inout as $k => $v) {
                        if ($g_num > 0) {
                            if ($v["num"] >= $g_num) {
                                $outnum = $v["outnum"] + $g_num;//已出数量
                                $num = $v["num"] - $g_num;//现有数量
                                $e_no = $v["e_no"] + 1;
                                $update_warehouse_inout_entitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $current_time,
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
                                $update_warehouse_inout_entitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $current_time,
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
                    $warehouse_pc_inout = $WarehouseInoutModel->where(" `goods_id`={$goods_id} and `warehouse_id`=0 and `store_id`>0 and `shequ_id`={$shequ_id} and `num`>0 ")->order(" `inout_id` asc ")->select();
                    foreach ($warehouse_pc_inout as $k => $v) {
                        if ($g_num > 0) {
                            if ($v["num"] >= $g_num) {
                                $outnum = $v["outnum"] + $g_num;//已出数量
                                $num = $v["num"] - $g_num;//现有数量
                                $e_no = $v["e_no"] + 1;
                                $update_warehouse_inout_entitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $current_time,
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
                                $update_warehouse_inout_entitys[] = array(
                                    "inout_id" => $v["inout_id"],
                                    "outnum" => $outnum,
                                    "num" => $num,
                                    "etime" => $current_time,
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
            $result = $this->saveAll("hii_warehouse_inout", $update_warehouse_inout_entitys, "inout_id", $WarehouseInoutModel);
            if ($result["status"] == "0") {
                $this->rollback();
                return array("status" => 0, "msg" => "扣减批次失败");
            }
        }


        //更新门店盘点表【hii_store_inventory】信息
        $ok = $StoreInventoryModel->where(" si_id={$si_id} ")->limit(1)->save(array("ptime" => $current_time, "padmin_id" => $uid, "si_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("status" => "0", "msg" => "更新盘点主表信息失败");
        }

        //更新盘点子表b_num字段
        $result = $this->saveAll("hii_store_inventory_detail", $update_store_inventory_detail_entitys, "si_d_id", $StoreInventoryDetailModel);
        if ($result["status"] == "0") {
            $this->rollback();
            return array("status" => "0", "msg" => "修改盘点子表信息失败");
        }

        $this->commit();
        return array("status" => 200, "msg" => "操作成功");
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