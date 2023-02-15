<?php
/**
 * 门店出库验货单模型
 * User: zzy
 * Date: 2018-04-18
 * Time: 14:02
 */
namespace Internal\Model;
use Think\Model;
class StoreOutModel extends Model{
    /**
     * 获取门店出库验货单列表数据
     * @param $store_id 门店id
     * @param $s_out_status 状态  状态:0.新增,1.已审核转出库,2.已拒绝,3.部分拒绝
     * @return array
     */
    public function getStoreOutListModel($store_id, $s_out_status){
        $array = array();
        $array['SO.store_id2'] = $store_id;
        if($s_out_status == 0){
            $array['SO.s_out_status'] = 0;
        }else{
            $array['SO.s_out_status'] = array('NEQ',0);
        }
       $data = $this->alias('SO')->field('ifnull(W1.w_id,\'\') as w_id,ifnull(W1.w_name,\'\') as w_name,SO.ptime,ifnull(S1.id,\'\') as store_id,ifnull(S1.title,\'\') as store_name,SO.s_out_id,SO.s_out_sn,SO.s_out_status,SO.s_out_type,SO.ctime,SO.remark,SO.g_type,SO.g_nums,SO.store_id2')
           ->join("left join hii_store S1 on S1.id = SO.store_id1")
           ->join("left join hii_warehouse W1 on W1.w_id = SO.warehouse_id")
           ->where($array)
           ->order('SO.ctime desc')
           ->select();
        if($data === false){
            return false;
        }
        if($data == null){
            return array();
        }
        return $data;
    }
    /**
     * 获取门店出库验货单列表数据单个s_out_id
     * @param $store_id 门店id
     * @param $s_out_id 出库验货单id
     * @return array
     */
    public function getStoreOutList_one($store_id, $s_out_id){
        $array = array();
        $array['SO.store_id2'] = $store_id;
        $array['SO.s_out_id'] = $s_out_id;
        $data = $this->alias('SO')->field('ifnull(W1.w_id,\'\') as w_id,ifnull(W1.w_name,\'\') as w_name,M.nickname,ifnull(S1.id,\'\') as store_id,ifnull(S1.title,\'\') as store_name,SO.s_out_id,SO.s_out_sn,SO.s_out_status,SO.s_out_type,SO.ctime,ifnull(SO.remark,\'\')remark,ifnull(SO.g_type,0)g_type,ifnull(SO.g_nums,0)g_nums,SO.store_id2,SO.s_r_id')
            ->join("left join hii_store S1 on S1.id = SO.store_id1")
            ->join("left join hii_warehouse W1 on W1.w_id = SO.warehouse_id")
            ->join("left join hii_member M on M.uid=SO.admin_id")
            ->where($array)
            ->order('SO.ctime desc')
            ->select();
        if($data === false){
            return false;
        }
        if($data == null){
            return array();
        }
        return $data;
    }
    /**
     * 获取门店出库验货单详情查看
     * @param $s_out_id 出库验货单id
     * @param $store_id
     * @param $audit_mark 不填是已审核    0 未操作 1 已操作
     * @return array
     */
    public function get_store_out_detail($store_id, $s_out_id, $audit_mark=''){
        $array = array();
        if($audit_mark !== '') {
            $array['SOD.audit_mark'] = $audit_mark;
        }
        $data = $this->alias('SO')
            ->field('SO.s_out_type,SOD.s_r_d_id,SOD.audit_mark,SO.s_out_status,G.id as goods_id,G.title as goods_name,GC.title as cate_name,SOD.s_out_d_id,ifnull(SOD.g_num,0)g_num,ifnull(SOD.in_num,0)in_num,ifnull(SOD.out_num,0)out_num,ifnull(SOD.remark,\'\')remark,SO.store_id1,SO.store_id2,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) price')
            ->join("inner join hii_store_out_detail SOD on SOD.s_out_id=SO.s_out_id and SO.s_out_id={$s_out_id}")
            ->join('left join hii_goods G on G.id=SOD.goods_id')
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->where($array)
            ->select();
        if($data === false){
            return false;
        }
        if($data == null){
            return array();
        }
        return $data;
    }
    /**
     * 全部拒绝
     * @param $s_out_id  出库验货单ID
     * 逻辑：1.更新出库单子表信息
     *       2.更新出库单主表信息
     *       3.更新对应调拨子表信息
     *       4.更新调拨子表对应主表的信息
     * @return array
     */
    public function rejectAll($store_id, $s_out_id, $admin_id){
        $this->startTrans();
        $StoreOutModel = M("StoreOut");
        $StoreOutDetailModel = M("StoreOutDetail");
        $isUpdateRollback = true;//是否更新相关申请单状态
        $datas = $StoreOutModel->where("s_out_id={$s_out_id} and store_id2={$store_id} and s_out_status=0 ")->limit(1)->select();
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->rollback();
            return array("code" => "0", "msg" => "提交信息有误");
        }
        $s_out_type = $datas[0]["s_out_type"];//来源:0.仓库调拨,1.门店申请,2.退货报损
        //更新出库验货单子表信息
        $s_r_d_id_str = "";
        $w_r_d_id_str = "";
        $datas = $StoreOutDetailModel->where(" s_out_id={$s_out_id} ")->select();
        foreach ($datas as $key => $val) {
            $ok = $StoreOutDetailModel->where(" s_out_d_id={$val["s_out_d_id"]} ")->save(array(
                "in_num" => 0,
                "out_num" => $val["g_num"]
            ));
            if ($ok === false) {
                $this->rollback();
                return array("code" => "0", "msg" => "更新出库验货单子表失败");
            }
            if (!empty($val["s_r_d_id"])) {
                $s_r_d_id_str .= $val["s_r_d_id"] . ",";
            }
            if (!empty($val["w_r_d_id"])) {
                $w_r_d_id_str .= $val["w_r_d_id"];
            }
        }
        //更新出库验货单主表
        $ptime = time();
        $ok = $StoreOutModel->where(" s_out_id={$s_out_id} ")->limit(1)->save(array(
            "s_out_status" => 2,
            "padmin_id" => $admin_id,
            "ptime" => $ptime
        ));
        if ($ok === false) {
            $this->rollback();
            return array("code" => "0", "msg" => "更新出库验货单主表信息失败");
        }
        if ($isUpdateRollback) {
            if ($s_out_type == 0) {

            } elseif ($s_out_type == 1) {
                $StoreToStoreModel = M("StoreToStore");
                $StoreToStoreDetailModel = M("StoreToStoreDetail");
                //更新hii_store_request状态
                $s_r_d_id_str = substr($s_r_d_id_str, 0, strlen($s_r_d_id_str) - 1);
                $ok = $StoreToStoreDetailModel->query(" update hii_store_to_store_detail set is_pass=1,pass_num=0 where s_t_s_d_id in ({$s_r_d_id_str})  ");
                if ($ok === false) {
                    $this->rollback();
                    return array("code" => "0", "msg" => "更新门店调拨子表信息失败");
                }
                //获取更新的子表对应的主表
                $store_to_store_datas = $StoreToStoreDetailModel->query(" select s_t_s_id from hii_store_to_store_detail WHERE s_t_s_d_id in ({$s_r_d_id_str}) group by s_t_s_id  ");
                foreach ($store_to_store_datas as $key => $val) {
                    //
                    $sql = "select * from hii_store_to_store_detail where s_t_s_id={$val["s_t_s_id"]} ";
                    $details = $StoreToStoreDetailModel->query($sql);
                    $g_type = count($details);
                    $PassNum = 0;//is_pass的总和
                    $CheckNum = 0;//已审核数量
                    $SomePass = false;//是否有些通过审核
                    $s_t_s_status = 0;
                    foreach ($details as $k => $v) {
                        $PassNum += $v["is_pass"];
                        if ($v["is_pass"] > 0) {
                            $CheckNum++;
                        }
                        if ($v["is_pass"] == 2) {
                            $SomePass = true;
                        }
                    }
                    //判断审核状态
                    if ($CheckNum > 0 && $SomePass) {
                        $s_t_s_status = 2;
                    }
                    if ($CheckNum == $g_type) {
                        $s_t_s_status = 2;
                    }
                    if ($PassNum == 0) {
                        $s_t_s_status = 0;
                    } elseif ($PassNum == $g_type) {
                        $s_t_s_status = 4;
                    } elseif ($PassNum == ($g_type * 2)) {
                        $s_t_s_status = 3;
                    }
                    $ok = $StoreToStoreModel->where(" s_t_s_id={$val["s_t_s_id"]} ")->limit(1)->save(array("s_t_s_status" => $s_t_s_status));
                    if ($ok === false) {
                        $this->rollback();
                        return array("code" => "0", "msg" => "调拨申请主表状态修改失败");
                    }
                    // 加入提醒,通知被拒申请门店
                    $get_data = $StoreToStoreModel->where(array('s_t_s_id'=>$val["s_t_s_id"]))->find();
                    $MessageWarnModel = D('Erp/MessageWarn');
                    $MessageWarnModel->pushMessageWarn($admin_id  , 0  ,$get_data['store_id1'] ,  0 , $get_data , 15);
                }
            } elseif ($s_out_type == 2) {

            }
        }
        $this->commit();
        return array("code" => "200", "msg" => "操作成功");
    }

    /**
     * 修改出库验货单
     * @param $store_id  门店id
     * @param $s_out_id 出库验收单id
     * @param $detail_array  修改数据
     */
    public function save_store_out($admin_id,$s_out_id,$detail_array){
        $storeOutDetailModel = M('StoreOutDetail');
        $this->startTrans();
        foreach ($detail_array as $key=>$val){
            $save = $storeOutDetailModel->where(array('s_out_d_id'=>$val['s_out_d_id']))->save(array('in_num'=>$val['in_num'],'out_num'=>$val['out_num'],'remark'=>$val['remark'],'audit_mark'=>$val['audit_mark']));
            if($save === false){
                $this->rollback();
                return array('code'=>0,'msg'=>'修改出库验收单失败');
            }
        }
        $this->commit();
        return array('code'=>200,'msg'=>'修改成功');
    }
    /**
     * 修改全部出库验货单
     * @param $store_id  门店id
     * @param $s_out_id 出库验收单id
     * @param $detail_array  修改数据
     */
    public function save_store_out_all($s_out_id,$all=1){
        $storeOutDetailModel = M('StoreOutDetail');
        $this->startTrans();
        $info = $storeOutDetailModel->where(array('s_out_id'=>$s_out_id))->select();
        foreach ($info as $key=>$val) {
            if($all === '1'){
                $save = $storeOutDetailModel->where(array('s_out_d_id'=>$val['s_out_d_id']))->save(array('in_num'=>$val['g_num'],'out_num'=>0,'audit_mark'=>1));
                if($save === false){
                    $this->rollback();
                    return array('code'=>0,'msg'=>'修改出库验收单失败');
                }
            }elseif($all === '0'){
                $save = $storeOutDetailModel->where(array('s_out_d_id'=>$val['s_out_d_id']))->save(array('in_num'=>0,'out_num'=>$val['g_num'],'audit_mark'=>1));
                if($save === false){
                    $this->rollback();
                    return array('code'=>0,'msg'=>'修改出库验收单失败');
                }
            }
        }

        $this->commit();
        return array('code'=>200,'msg'=>'修改成功');
    }
    /**
     * 出库验货单审核
     * @param $s_out_id 出库验货单ID
     * @param $store_id 门店ID
     * @param $padmin_id 管理员ID
     * @return array
     */
    public function pass($s_out_id, $store_id, $padmin_id)
    {
        $this->startTrans();
        $StoreOutModel = M("StoreOut");
        $StoreOutDetailModel = M("StoreOutDetail");
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        $StoreModel = M("Store");

        $main = $StoreOutModel->where(" s_out_id={$s_out_id} and store_id2={$store_id} and s_out_status=0 ")->order(" s_out_id desc ")->limit(1)->select();
        if (is_null($main) || empty($main) || count($main) == 0) {
            $this->rollback();
            return array("code" => "0", "msg" => "无权审核该出库验货单");
        }

        //把成功出库的商品写入出库单【hii_store_out_stock】
        $s_out_type = $main[0]["s_out_type"];
        $sql = "select s_out_d_id,s_out_id,goods_id,ifnull(g_num,0) as g_num,ifnull(in_num,0) as in_num, ";
        $sql .= "ifnull(out_num,0) as out_num,ifnull(g_price,0) as g_price,s_r_d_id,w_r_d_id,remark ";
        $sql .= "from hii_store_out_detail ";
        $sql .= "where s_out_id={$s_out_id} ";
        $datas = $StoreOutDetailModel->query($sql);
        $SuccessOutArray = array();//成功出库部分
        $FullRejectOutArray = array();//完全拒绝部分
        $g_type = 0;//成功出库商品种类
        $g_nums = 0;//成功出库商品数量
        $app_total_g_num = 0;//原申请总数量
        $total_reject_num = 0;//总拒绝数量
        $app_store_id = $main[0]["store_id1"];//申请门店ID
        $deliver_store_id = $main[0]["store_id2"];//发货门店ID或盘亏门店ID

        //获取门店所在区域ID
        $store_shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($store_datas) || empty($store_datas) || count($store_datas) == 0) {
            return array("code" => "0", "msg" => "门店不存在");
        } else {
            $store_shequ_id = $store_datas[0]["shequ_id"];
        }

        /****************************** 获取那些商品可以出库数量和被拒绝数量 start ******************************************************************/
        foreach ($datas as $key => $val) {
            if ($val["g_num"] != ($val["in_num"] + $val["out_num"])) {
                $this->rollback();
                return array("code" => "0", "msg" => "请填写有货数量和缺货数量");
            }
            if ($val["in_num"] > 0) {
                $SuccessOutArray[] = array(
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["in_num"],
                    "g_price" => $val["g_price"],
                    "remark" => $val["remark"],
                    "s_out_d_id" => $val["s_out_d_id"],
                    "s_t_s_d_id" => $val["s_r_d_id"],//对应门店调拨申请单子表id
                );
                $g_type++;
                $g_nums += $val["in_num"];
            }
            if ($val["g_num"] == $val["out_num"]) {
                $FullRejectOutArray[] = array(
                    "goods_id" => $val["goods_id"],
                    "g_num" => $val["in_num"],
                    "g_price" => $val["g_price"],
                    "remark" => $val["remark"],
                    "s_r_d_id" => $val["s_r_d_id"],
                );
            }
            $app_total_g_num += $val["g_num"];
            $total_reject_num += $val["out_num"];
        }
        /****************************** 获取那些商品可以出库数量和被拒绝数量 end ******************************************************************/

        /****************************************  成功出库部分处理 start **************************************/
        if (count($SuccessOutArray) > 0) {
            $GoodsStoreModel = M("GoodsStore");
            $StoreOutStockModel = M("StoreOutStock");
            $StoreStockDetailModel = M("StoreStockDetail");

            /***************************************  判断库存是否足够出库 start ************************************************************************************/
            $GoodsStoreUpdateDatas = array();//需要更新的库存数据
            $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
            foreach ($SuccessOutArray as $key => $val) {
                $tmp = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
                if (is_null($tmp) || empty($tmp) || count($tmp) == 0 || $tmp[0]["num"] < $val["g_num"]) {
                    return array("code" => "0", "msg" => "ID为{$val["goods_id"]}的商品库存不足，无法出库");
                }
                $has = false;
                foreach ($GoodsStoreUpdateDatas as $tk => $tv) {
                    if ($tv["id"] == $tmp[0]["id"]) {
                        $GoodsStoreUpdateDatas[$tk]["num"] = $tv["num"] - $val["g_num"];
                        $has = true;
                        break;
                    }
                }
                if ($has == false) {
                    $num = $tmp[0]["num"] - $val["g_num"];
                    $GoodsStoreUpdateDatas[] = array("id" => $tmp[0]["id"], "num" => $num);
                }
            }
            /***************************************  判断库存是否足够出库 end ************************************************************************************/

            //整理出库单主表信息【hii_store_out_stock】
            $StoreOutStockEntity = array();
            $StoreOutStockEntity["s_out_s_sn"] = get_new_order_no("CK", "hii_store_out_stock", "s_out_s_sn");
            $StoreOutStockEntity["s_out_s_status"] = 1;//直接审核出库
            $StoreOutStockEntity["s_out_s_type"] = $s_out_type;
            $StoreOutStockEntity["s_out_id"] = $s_out_id;
            $StoreOutStockEntity["ctime"] = time();
            $StoreOutStockEntity["admin_id"] = $padmin_id;
            $StoreOutStockEntity["ptime"] = time();
            $StoreOutStockEntity["padmin_id"] = $padmin_id;
            $StoreOutStockEntity["store_id1"] = $main[0]["store_id1"];
            $StoreOutStockEntity["store_id2"] = $main[0]["store_id2"];
            $StoreOutStockEntity["remark"] = $main[0]["remark"];
            $StoreOutStockEntity["g_type"] = $g_type;
            $StoreOutStockEntity["g_nums"] = $g_nums;
            $s_out_s_id = $StoreOutStockModel->add($StoreOutStockEntity);
            if ($s_out_s_id === false) {
                $this->rollback();
                return array("code" => "0", "msg" => "新增出库单失败");
            }
            $StoreOutStockDetailEntitys = array();
            foreach ($SuccessOutArray as $key => $val) {
                $StoreOutStockDetailEntity = array();
                $StoreOutStockDetailEntity["s_out_s_id"] = $s_out_s_id;
                $StoreOutStockDetailEntity["goods_id"] = $val["goods_id"];
                $StoreOutStockDetailEntity["g_num"] = $val["g_num"];
                $StoreOutStockDetailEntity["g_price"] = $val["g_price"];
                $StoreOutStockDetailEntity["remark"] = $val["remark"];
                $StoreOutStockDetailEntity["s_out_d_id"] = $val["s_out_d_id"];
                $StoreOutStockDetailEntitys[] = $StoreOutStockDetailEntity;
            }
            if (count($StoreOutStockDetailEntitys) > 0) {
                $ok = $StoreStockDetailModel->addAll($StoreOutStockDetailEntitys);
                if ($ok === false) {
                    $this->rollback();
                    return array("code" => "0", "msg" => "新增出库单子表失败");
                }
            }
            /******************************* 直接审核出库后的处理 start ********************************************************/
            if ($StoreOutStockEntity["s_out_s_type"] == 1) {
                /*************************** 门店调拨出库处理 *******************************/
                /***************************
                 * 门店与门店调拨出库逻辑：
                 * 1.先判断库存，库存充足减库存【上面已判断库存】
                 * 2.生成申请门店的入库验货单
                 * 3.修改hii_store_out_stock的状态
                 * 4.修改hii_store_to_store,hii_store_to_store_detail的相关信息
                 *****************************/
                $isUpdateRollback = true;//是否更新hii_store_to_store，hii_store_to_store_detail
                $result = $this->saveAll("hii_goods_store", $GoodsStoreUpdateDatas, "id", $GoodsStoreModel);
                if ($result["status"] == "0") {
                    $this->rollback();
                    return array("code" => "0", "msg" => "更新门店库存信息失败");
                }
                $StoreInModel = M("StoreIn");
                $StoreInDetailModel = M("StoreInDetail");
                /************ 生成入库单主表信息 ****************/
                $StoreInEntity = array();
                $StoreInEntity["s_in_sn"] = get_new_order_no("SI", "hii_store_in", "s_in_sn");
                $StoreInEntity["s_in_status"] = 0;
                $StoreInEntity["s_in_type"] = 1;
                $StoreInEntity["s_out_s_id"] = $s_out_s_id;
                $StoreInEntity["ctime"] = time();
                $StoreInEntity["admin_id"] = $padmin_id;
                $StoreInEntity["store_id1"] = $deliver_store_id;//发货门店ID
                $StoreInEntity["store_id2"] = $app_store_id; //收货门店ID
                $StoreInEntity["g_type"] = $g_type;
                $StoreInEntity["g_nums"] = $g_nums;
                $s_in_id = $StoreInModel->add($StoreInEntity);
                if ($s_in_id === false) {
                    $this->rollback();
                    return array("code" => "0", "msg" => "新增门店入库验货单主表信息失败");
                }
                /************ 生成入库单子表信息 ****************/
                $StoreInDetailEntitys = array();
                $store_out_stock_details = $StoreStockDetailModel->where(" s_out_s_id={$s_out_s_id} ")->order(" s_out_s_d_id desc ")->select();
                foreach ($store_out_stock_details as $key => $val) {
                    $StoreInDetailEntitys[] = array(
                        "s_in_id" => $s_in_id,
                        "goods_id" => $val["goods_id"],
                        "g_num" => $val["g_num"],
                        "g_price" => $val["g_price"],
                        "s_out_s_d_id" => $val["s_out_s_d_id"],
                    );
                }
                if (count($StoreInDetailEntitys) > 0) {
                    $ok = $StoreInDetailModel->addAll($StoreInDetailEntitys);
                    if ($ok === false) {
                        $this->rollback();
                        return array("code" => "0", "msg" => "新增入库验货单子表信息失败");
                    }
                }
                /************* 回写门店调拨申请单信息 ***********************************/
                if ($isUpdateRollback) {
                    foreach ($SuccessOutArray as $key => $val) {
                        $ok = $StoreToStoreDetailModel->where(" s_t_s_d_id={$val["s_t_s_d_id"]} ")->limit(1)->save(array(
                            "is_pass" => 2,
                            "pass_num" => $val["g_num"]
                        ));
                        if ($ok === false) {
                            $this->rollback();
                            return array("code" => "0", "msg" => "更新门店调拨子表信息失败");
                        }
                        $tmp = $StoreToStoreDetailModel->where(" s_t_s_d_id={$val["s_t_s_d_id"]} ")->limit(1)->select();
                        $s_t_s_id = $tmp[0]["s_t_s_id"];
                        $store_to_store_details = $StoreToStoreDetailModel->where(" s_t_s_id={$s_t_s_id} ")->select();
                        $s_t_s_status = 0;
                        $total_g_num = 0;
                        $total_pass_num = 0;
                        $total_no_pass_num = 0;
                        $pass_num = 0;
                        $no_pass_num = 0;
                        foreach ($store_to_store_details as $k => $v) {
                            $total_g_num += $v["g_num"];
                            $total_pass_num += $v["pass_num"];
                            if ($v["is_pass"] == 2) {
                                $pass_num++;
                            }
                            if ($v["is_pass"] == 1) {
                                $no_pass_num++;
                                $total_no_pass_num += ($v["g_num"] - $v["pass_num"]);
                            }
                        }
                        //判断审核状态
                        if ($total_g_num == $total_pass_num) {
                            $s_t_s_status = 3;
                        }
                        if ($total_pass_num > 0 && $total_pass_num < $total_g_num) {
                            $s_t_s_status = 2;
                        }
                        if ($total_no_pass_num == $total_g_num) {
                            $s_t_s_status = 4;
                        }


                        $ok = $StoreToStoreModel->where(" s_t_s_id={$s_t_s_id} ")->limit(1)->save(array("s_t_s_status" => $s_t_s_status));
                        if ($ok === false) {
                            $this->rollback();
                            return array("code" => "0", "msg" => "更新调拨申请主表信息失败");
                        }
                    }
                }
            } elseif ($StoreOutStockEntity["s_out_s_type"] == 3) {
                /*************************** 盘亏出库处理 *******************************/
                /*******************
                 * 盘亏【库存数量比实际盘点数量多】出库逻辑：
                 * 1.减库存
                 * 2.减批次表【hii_warehouse_inout】
                 *        2.1 先判断当前门店批次是否够减
                 *        2.2 当前门店不够减的话再从仓库减
                 * 3.更新hii_store_out_stock的审核信息
                 ********************/
                $result = $this->saveAll("hii_goods_store", $GoodsStoreUpdateDatas, "id", $GoodsStoreModel);
                if ($result["status"] == "0") {
                    $this->rollback();
                    return array("code" => "0", "msg" => "更新门店库存信息失败");
                }
                /**********************
                 * 减批次：a.判断当前门店批次是否够减
                 *         b.当门店批次不够减的时候，往仓库批次减
                 * 扣减逻辑：先进先扣
                 **********************/
                $WarehouseInoutModel = M("WarehouseInout");
                $StoreRequestModel = M("StoreRequest");
                $WarehouseOutStockModel = M("WarehouseOutStock");
                $UpdateWarehouseInoutEntitys = array();
                $etime = time();
                foreach ($SuccessOutArray as $key => $val) {
                    $goods_id = $val["goods_id"];
                    $g_num = $val["g_num"];//盘亏数量

                    /********************* 先扣减门店入库批次，先进先扣原则 start ***********************/
                    $store_pc_inout = $WarehouseInoutModel->where(" goods_id={$goods_id} and store_id={$deliver_store_id} and num>0 ")->order(" inout_id asc ")->select();

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
                    /********************* 当门店批次扣减还剩余的话，对仓库批次进行扣减 end ***********************/
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
                    return array("code" => "0", "msg" => "批次扣减失败");
                }
            }
            /******************************* 直接审核出库后的处理 end ********************************************************/
        }
        /****************************************  成功出库部分处理 end **************************************/

        /****************************************  全部拒绝部分处理【有货数量为0】 start **************************************/
        if (count($FullRejectOutArray) > 0) {
            $s_t_s_d_id_in = "";
            foreach ($FullRejectOutArray as $key => $val) {
                $s_t_s_d_id_in .= $val["s_r_d_id"] . ",";
            }
            $s_t_s_d_id_in = substr($s_t_s_d_id_in, 0, strlen($s_t_s_d_id_in) - 1);
            $ok = $StoreToStoreDetailModel->query(" update hii_store_to_store_detail set is_pass=1,pass_num=0 where s_t_s_d_id in ({$s_t_s_d_id_in})  ");
            if ($ok === false) {
                $this->rollback();
                return array("code" => "0", "msg" => "更新门店调拨子表失败");
            }
            //获取更新的子表对应的主表
            $store_to_store_datas = $StoreToStoreDetailModel->query(" select s_t_s_id from hii_store_to_store_detail WHERE s_t_s_d_id in ({$s_t_s_d_id_in}) group by s_t_s_id  ");
            foreach ($store_to_store_datas as $key => $val) {
                //
                $sql = "select * from hii_store_to_store_detail where s_t_s_id={$val["s_t_s_id"]} ";
                $details = $StoreToStoreDetailModel->query($sql);
                $g_type = count($details);
                $PassNum = 0;//is_pass的总和
                $CheckNum = 0;//已审核数量
                $SomePass = false;//是否有些通过审核
                $s_t_s_status = 0;
                foreach ($details as $k => $v) {
                    $PassNum += $v["is_pass"];
                    if ($v["is_pass"] > 0) {
                        $CheckNum++;
                    }
                    if ($v["is_pass"] == 2) {
                        $SomePass = true;
                    }
                }
                //判断审核状态
                if ($CheckNum > 0 && $SomePass) {
                    $s_t_s_status = 2;
                }
                if ($CheckNum == $g_type) {
                    $s_t_s_status = 2;
                }
                if ($PassNum == 0) {
                    $s_t_s_status = 0;
                } elseif ($PassNum == $g_type) {
                    $s_t_s_status = 4;
                } elseif ($PassNum == ($g_type * 2)) {
                    $s_t_s_status = 3;
                }
                $ok = $StoreToStoreModel->where(" s_t_s_id={$val["s_t_s_id"]} ")->limit(1)->save(array("s_t_s_status" => $s_t_s_status));
                if ($ok === false) {
                    $this->rollback();
                    return array("code" => "0", "msg" => "调拨申请主表状态修改失败");
                }
            }
        }
        /****************************************  全部拒绝部分处理【有货数量为0】 end **************************************/

        /****************************************  更新出库验货单信息 start **************************************/
        $ptime = time();
        $s_out_status = 1;
        if ($app_total_g_num == $total_reject_num) {
            $s_out_status = 2;
        }
        $ok = $StoreOutModel->where(" s_out_id={$s_out_id} ")->limit(1)->save(array(
            "s_out_status" => $s_out_status,
            "padmin_id" => $padmin_id,
            "ptime" => $ptime
        ));
        if ($ok === false) {
            $this->rollback();
            return array("code" => "0", "msg" => "更新出库验货单主表信息失败");
        }
        /****************************************  更新出库验货单信息 end **************************************/
        $this->commit();
        return array("code" => "200", "msg" => "审核成功" , 'data' => $StoreInEntity);
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