<?php
/**
 * 门店返仓模型
 * User: zzy
 * Date: 2018/5/7
 * Time: 22:40
 */
namespace Internal\Model;
use Think\Model;
class StoreBackModel extends Model{
    /**
     * 提交门店返仓申请单
     * @param $admin_id  管理员id
     * @parame $store_id 门店id
     * @parame $w_id 仓库id
     * @param $remark  备注
     * @param $temp  数据
     */
    public function submit_store_back($admin_id, $store_id, $w_id, $remark,$temp){
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $StoreModel = M("Store");
        $GoodsStoreModel = M("GoodsStore");
        $this->startTrans();
        //获取区域id
        $shequ_id = $StoreModel->where(array('id'=>$store_id))->getField("shequ_id");
        //判断发货临时申请表是否有数据
        if(empty($temp)){
            return array('code'=>0,'msg'=>'发货申请无数据');
        }
        //检测提交数量是否大于当前库存数量
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        foreach ($temp as $key => $val) {
        	//判断是否选择属性
        	if(!$val['value_id']){
        		return array('code'=>0,'msg'=>$val['goods_id'].'商品未选择属性');
        	}
            if(array_key_exists($val['goods_id'],$goods_num_array)){
                $goods_num_array[$val['goods_id']] += $val["g_num"];
            }else{
                $goods_num_array[$val['goods_id']] = $val["g_num"];
            }
            $goods_store_datas = $GoodsStoreModel->where(" store_id={$store_id} and goods_id={$val["goods_id"]} ")->limit(1)->select();
            if (is_null($goods_store_datas) || empty($goods_store_datas) || count($goods_store_datas) == 0) {
                return array("code" => "0", "msg" => "ID为{$val["goods_id"]}的商品无库存，不能提交申请");
            } else {
                if ($goods_store_datas[0]["num"] < $goods_num_array[$val['goods_id']]) {
                    return array("code" => "0", "msg" => "ID为{$val["goods_id"]}的商品提交数量大于当前库存数量，不能提交申请");
                }
            }
        }
        $g_type = 0;
        $g_nums = 0;
        foreach($temp as $k=>$v){
            $g_type +=1;
            $g_nums += $v['g_num'];
        }
        //生成返仓申请单主表信息
        $StoreBackEntity = array();
        $StoreBackEntity["s_back_sn"] = get_new_order_no("FC", "hii_store_back", "s_back_sn");
        $StoreBackEntity["s_back_status"] = 0;
        $StoreBackEntity["s_back_type"] = 0;
        $StoreBackEntity["ctime"] = time();
        $StoreBackEntity["admin_id"] = $admin_id;
        $StoreBackEntity["store_id"] = $store_id;
        $StoreBackEntity["warehouse_id"] = $w_id;
        $StoreBackEntity["remark"] = $remark;
        $StoreBackEntity["g_type"] = $g_type;
        $StoreBackEntity["g_nums"] = $g_nums;
        $s_back_id = $this->add($StoreBackEntity);
        if(empty($s_back_id)){
            $this->rollback();
            return array('code'=>0,'msg'=>'生成返仓申请单主表失败');
        }
        //返仓子表信息
        $StoreBackDetailEntitys = array();
        $value_repeat = array();//如果重复提交商品 去重
        foreach ($temp as $key => $val) {
            $g_price = $WarehouseInoutViewModel->where(array('goods_id'=>$v['goods_id'],'shequ_id'=>$shequ_id))->getField('stock_price');
            if($g_price == null){
                $g_price=0.00;
            }
            if(array_key_exists($val['goods_id'].$val['value_id'],$value_repeat)){
	            $StoreBackDetailEntity = array();
	            $StoreBackDetailEntity["s_back_id"] = $s_back_id;
	            $StoreBackDetailEntity["goods_id"] = $val["goods_id"];
	            $StoreBackDetailEntity["g_num"] = $val["g_num"];
	            $StoreBackDetailEntity["g_price"] = $g_price;
	            $StoreBackDetailEntity["remark"] = $val["remark"];
	            $StoreBackDetailEntity["value_id"] = $val['value_id'];
	            $StoreBackDetailEntitys[$value_repeat[$val['goods_id'].$val['value_id']]] = $StoreBackDetailEntity;
            }else{
            	$StoreBackDetailEntity = array();
            	$StoreBackDetailEntity["s_back_id"] = $s_back_id;
            	$StoreBackDetailEntity["goods_id"] = $val["goods_id"];
            	$StoreBackDetailEntity["g_num"] = $val["g_num"];
            	$StoreBackDetailEntity["g_price"] = $g_price;
            	$StoreBackDetailEntity["remark"] = $val["remark"];
            	$StoreBackDetailEntity["value_id"] = $val['value_id'];
            	$StoreBackDetailEntitys[] = $StoreBackDetailEntity;
            }
            $value_repeat[$val['goods_id'].$val['value_id']] = $key;
        }
        $hii_store_back_detail = M('StoreBackDetail');
        if (!empty($StoreBackDetailEntitys)) {
            $ok = $hii_store_back_detail->addAll($StoreBackDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("code" => "0", "msg" => "新增返仓申请子表信息失败");
            }
        }else{
            $this->rollback();
            return array('code'=>0,'msg'=>'新增返仓申请子表信息失败1');
        }
        $this->commit();
        return array('code'=>200,'msg'=>'操作成功','data'=>$StoreBackEntity);
    }

    /**
     * 获取门店返仓申请列表
     * @param $store_id 门店id
     * @param $s_back_status 审核状态  1已审核 0 未审核 2 已作废
     */
    public function get_store_back($store_id, $s_back_status =0){
        $array = array();
        $array['SB.s_back_status'] = $s_back_status;
        $array['SB.store_id'] = $store_id;
        $data = $this->alias('SB')
            ->field("W.w_id,W.w_name,SB.store_id,SB.s_back_id,SB.s_back_sn,SB.s_back_status,SB.s_back_type,SB.ctime,SB.remark,ifnull(SB.g_type,0)g_type,ifnull(SB.g_nums,0)g_nums")
            ->join("left join hii_warehouse W on W.w_id=SB.warehouse_id")
            ->where($array)
            ->order('SB.ctime desc')
            ->select();
        if($data === false){
            return false;
        }
        if(empty($data)){
            return array();
        }else{
            return $data;
        }
    }
    /**
     * 获取门店返仓申请列表单个
     * @param $store_id 门店id
     * @param $s_back_id 审核状态  1已审核 0 未审核 2 已作废
     */
    public function get_store_back_one($store_id, $s_back_id){
        $array = array();
        $array['SB.s_back_id'] = $s_back_id;
        $data = $this->alias('SB')
            ->field("M.nickname,W.w_id,W.w_name,SB.s_back_id,SB.s_back_sn,SB.s_back_status,SB.s_back_type,SB.ctime,SB.remark,ifnull(SB.g_type,0)g_type,ifnull(SB.g_nums,0)g_nums")
            ->join("left join hii_warehouse W on W.w_id=SB.warehouse_id")
            ->join("left join hii_member M on M.uid=SB.admin_id")
            ->where($array)
            ->order('SB.ctime desc')
            ->select();
        if($data === false){
            return false;
        }
        if(empty($data)){
            return array();
        }else{
            return $data;
        }
    }

    /**
     * 获取返仓申请详细数据
     * @param $store_id 门店id
     * @param $s_back_id  返仓id
     */
    public function get_store_back_detail($store_id, $s_back_id){
        $data = $this->table('hii_store_back_detail SBD')
            ->field("ifnull(AV.value_id,'')value_id,ifnull(AV.value_name,'')value_name,ifnull(GS.num,0) store_num,G.id goods_id,G.title goods_name,GC.title cate_name,SB.s_back_id,SB.s_back_sn,SB.s_back_status,SB.s_back_type,ifnull(SBD.g_num,0)g_num,SBD.remark,W.w_id as w_id,W.w_name as w_name,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) price")
            ->join("inner join hii_store_back SB on SB.s_back_id=SBD.s_back_id and SB.s_back_id={$s_back_id}")
            ->join("left join hii_warehouse W on W.w_id=SB.warehouse_id")
            ->join("left join hii_goods G on G.id=SBD.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->join("left join hii_attr_value AV on AV.value_id=SBD.value_id")
            ->select();
        if($data === false){
            return false;
        }
        if(empty($data)){
            return array();
        }else{
            return $data;
        }
    }

    /**
     * 审核
     * @param string $admin_id 审核管理员
     * @param mixed $store_id  门店id
     * @param string $s_back_id  返仓单id
     */
    public function StoreBackcheck($admin_id, $store_id, $s_back_id){
        $storeBackDetailModel = M('StoreBackDetail');
        $goodsStorelModel = M('GoodsStore');
        $warehouseInModel = M("WarehouseIn");
        $warehouseInDetailModel = M("WarehouseInDetail");
        $this->startTrans();
        $s_back_id_data  = $this->where(array('s_back_id'=>$s_back_id,'s_back_status'=>0))->find();
        if(empty($s_back_id_data)){
            $this->rollback();
            return array('code'=>0,'msg'=>'没有该返仓单 无法审核');
        }
        $s_back_detail_data= $storeBackDetailModel->where(array('s_back_id'=>$s_back_id))->select();
        if(empty($s_back_detail_data)){
            $this->rollback();
            return array('code'=>0,'msg'=>'返仓单们有商品 无法审核');
        }
        //判断返仓商品是否小于库存 否则不能申请
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存
        foreach($s_back_detail_data as $key=>$val){
            if(array_key_exists($val['goods_id'],$goods_num_array)){
                $goods_num_array[$val['goods_id']] += $val["g_num"];
            }else{
                $goods_num_array[$val['goods_id']] = $val["g_num"];
            }

            $goods_store_num = $goodsStorelModel->where(array('goods_id' => $val['goods_id'], 'store_id' => $store_id))->getField('num');
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
        //加入仓库入库单
        $WarehouseInEntity = array();
        $WarehouseInEntity["w_in_sn"] = get_new_order_no("RY", "hii_warehouse_in", "w_in_sn");
        $WarehouseInEntity["w_in_status"] = 0;
        $WarehouseInEntity["w_in_type"] = 4;
        $WarehouseInEntity["s_back_id"] = $s_back_id;
        $WarehouseInEntity["ctime"] = time();
        $WarehouseInEntity["admin_id"] = $admin_id;
        $WarehouseInEntity["warehouse_id"] = $s_back_id_data["warehouse_id"];
        $WarehouseInEntity["store_id"] = $s_back_id_data["store_id"];
        $WarehouseInEntity["remark"] = $s_back_id_data["remark"];
        $WarehouseInEntity["g_type"] = $s_back_id_data["g_type"];
        $WarehouseInEntity["g_nums"] = $s_back_id_data["g_nums"];
        $warehouseIn_id = $warehouseInModel->add($WarehouseInEntity);
        if($warehouseIn_id === false){
            $this->rollback();
            return array("status" => "0", "msg" => "新增仓库入库验货单主表信息失败");
        }
        //加入仓库入库子单
        $warehouaeInDetailEntity = array();
        foreach($s_back_detail_data as $key=>$val){
            $WarehouseInDetailEntity = array();
            $WarehouseInDetailEntity["w_in_id"] = $warehouseIn_id;
            $WarehouseInDetailEntity["goods_id"] = $val["goods_id"];
            $WarehouseInDetailEntity["g_num"] = $val["g_num"];
            $WarehouseInDetailEntity["g_price"] = $val["g_price"];
            $WarehouseInDetailEntity["remark"] = $val["remark"];
            $WarehouseInDetailEntity["value_id"] = $val["value_id"];
            $warehouaeInDetailEntity[] = $WarehouseInDetailEntity;

        }
        $warehouaeInDetail_add = $warehouseInDetailModel->addAll($warehouaeInDetailEntity);
        if($warehouaeInDetail_add === false){
            $this->rollback();
            return array('code'=>0,'msg'=>'新增仓库入库验货单子表失败');
        }
        //更新返仓单信息
        $storeBack_updata = $this->where(array('s_back_id'=>$s_back_id))->save(array('s_back_status'=>1,'ptime'=>time(),'padmin_id'=>$admin_id));
        if($storeBack_updata === false){
            $this->rollback();
            return array("status" => "0", "msg" => "更新返仓申请主表信息失败");
        }
        /**************************** 减少门店对应返仓商品的库存 start *****************************************/
        foreach($s_back_detail_data as $key=>$val){
            $ok = $goodsStorelModel->where(array('goods_id'=>$val['goods_id'],'store_id'=>$store_id))->save(array('update_time'=>time(),'num'=>array('exp',"num - {$val['g_num']}")));
            if($ok === false){
                $this->rollback();
                return array('code'=>0,'msg'=>"{$val['goods_id']} 库存信息更新失败 不能审核");
            }
        }
        $this->commit();
        return array('code'=>200,'msg'=>'审核成功');
    }
}