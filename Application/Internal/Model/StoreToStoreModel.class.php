<?php
/**
 * 门店调拨申请单模型
 * User: zzy
 * Date: 2018-05-03
 * Time: 18:17
 */
namespace Internal\Model;
use Think\Model;
class StoreToStoreModel extends Model{
    /**
     * 提交门店调拨申请单
     * @param $admin_id  管理员id
     * @parame $store_id_allocation 发货门店id
     * @parame $store_id 申请id
     * @param $remark  备注
     * @param $temp  数据
     */
    public function submit_request_allocation($admin_id, $store_id, $store_id_allocation, $remark,$temp){
        $this->startTrans();
        $goodsStoreModel = M('GoodsStore');
        //判断临时申请表是否有数据
        if(empty($temp)){
            $this->rollback();
            return array('code'=>0,'msg'=>'临时调拨申请单无数据');
        }
        //判断发货门店是否有库存
        $g_type = 0;
        $g_nums = 0;
        $goods_num_array = array();//如果有重复商品  申请数量相加后再判断库存

        foreach ($temp as $key => $val) {
            $store_goods_num = $goodsStoreModel->where(array('store_id'=>$store_id_allocation,'goods_id'=>$val['goods_id']))->getField('num');
            if($store_goods_num === false || $store_goods_num === null){
                $this->rollback();
                return array('code'=>0,'msg'=>"ID {$val['goods_id']} 商品不存在 无法提交");
            }else{
                if($store_goods_num < $val["g_num"]){
                    $this->rollback();
                    return array('code'=>0,'msg'=>"ID {$val['goods_id']} 商品库存不足 无法提交");
                }
            }
            $g_type +=1;
            $g_nums += $val["g_num"];
        }
        //生成申请主表信息
        $StoreToStoreEntity = array();
        $StoreToStoreEntity["s_t_s_sn"] = get_new_order_no("SQ", "hii_store_to_store", "s_t_s_sn");
        $StoreToStoreEntity["s_t_s_type"] = 0;
        $StoreToStoreEntity["s_t_s_status"] = 0;
        $StoreToStoreEntity["ctime"] = time();
        $StoreToStoreEntity["admin_id"] = $admin_id;
        $StoreToStoreEntity["store_id1"] = $store_id;
        $StoreToStoreEntity["store_id2"] = $store_id_allocation;
        $StoreToStoreEntity["remark"] = $remark;
        $StoreToStoreEntity["g_type"] = $g_type;
        $StoreToStoreEntity["g_nums"] = $g_nums;
        $s_t_s_id = $this->add($StoreToStoreEntity);
        if (empty($s_t_s_id)) {
            $this->rollback();
            return array("code" => "0", "msg" => "新增调拨申请主表信息失败");
        }

        $StoreToStoreDetailEntitys = array();
        foreach ($temp as $key => $val) {
            $StoreToStoreDetailEntity = array();
            $StoreToStoreDetailEntity["s_t_s_id"] = $s_t_s_id;
            $StoreToStoreDetailEntity["goods_id"] = $val["goods_id"];
            $StoreToStoreDetailEntity["g_num"] = $val["g_num"];
            $StoreToStoreDetailEntity["is_pass"] = 0;
            $StoreToStoreDetailEntity["pass_num"] = 0;
            $StoreToStoreDetailEntity["remark"] = $val["remark"];
            $StoreToStoreDetailEntitys[] = $StoreToStoreDetailEntity;
        }
        $hii_store_to_store_detail = M('StoreToStoreDetail');
        if (!empty($StoreToStoreDetailEntitys)) {
            $ok = $hii_store_to_store_detail->addAll($StoreToStoreDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("code" => "0", "msg" => "新增调拨申请子表信息失败");
            }
        }else{
            $this->rollback();
            return array('code'=>0,'msg'=>'新增调拨申请子表信息失败');
        }


        //内部app直接生成门店出库验收单
        $WarehouseInoutViewModel = M("WarehouseInoutView");//用于查询g_price
        $storeModel = D('Store');
        $storeOutModel = D('StoreOut');
        $storeOutDetailModel = D('StoreOutDetail');
        //获取门店所在社区id
        $shequ_id = $storeModel->where(array('id'=>$store_id_allocation))->getField('shequ_id');
        if(empty($shequ_id)){
            $this->rollback();
            return array("code" => "0", "msg" => "获取社区id失败");
        }
        
        $g_price_array = $hii_store_to_store_detail->query("select A1.*,ifnull(VIW.stock_price,0) as g_price from hii_store_to_store_detail A1 INNER JOIN hii_warehouse_inout_view VIW on A1.goods_id=VIW.goods_id and A1.s_t_s_id={$s_t_s_id}  and VIW.shequ_id={$shequ_id}");
        if(empty($g_price_array)){
            $this->rollback();
            return array("code" => "0", "msg" => '所有申请没有入库价，无法生成出库验货单');
        }
        //生成门店出库验收单主表
        $StoreOutEntity = array();
        $StoreOutEntity["s_out_sn"] = get_new_order_no("CY", "hii_sotre_out", "s_out_sn");
        $StoreOutEntity["s_out_status"] = 0;
        $StoreOutEntity["s_out_type"] = 1;
        $StoreOutEntity["s_r_id"] = $s_t_s_id;
        $StoreOutEntity["ctime"] = time();
        $StoreOutEntity["admin_id"] = $admin_id;
        $StoreOutEntity["store_id1"] = $store_id;
        $StoreOutEntity["store_id2"] = $store_id_allocation;
        $StoreOutEntity["remark"] = '';
        $store_out_data_add =  $storeOutModel->add($StoreOutEntity);
        if(empty($store_out_data_add)){
            $this->rollback();
            return array('code'=>0,'msg'=>'新增出库验收单主表失败!');
        }
        //生成门店出库验收单子表
        $g_type = 0;
        $g_nums = 0;
        //保存调拨申请子表数据
        $store_to_store_where = array();
        $hii_store_to_store_detail_array = array();
        foreach($g_price_array as $k=>$v){
            $array = array(
                's_out_id' => $store_out_data_add,
                'goods_id' => $v['goods_id'],
                'g_num' => $v['g_num'],
                'g_price' => $v['g_price'],
                's_r_d_id' => $v['s_t_s_d_id'],
            );
            $hii_store_to_store_detail_array[] = $array;
            $g_type +=1;
            $g_nums += $v['g_num'];
            //更新调拨子表条件
            $store_to_store_where[] = $v['s_t_s_d_id'];
        }
        $store_out_detail_add = $storeOutDetailModel->addAll($hii_store_to_store_detail_array);
        if(empty($store_out_detail_add)){
            $this->rollback();
            return array('code'=>0,'msg'=>'新增出库验收单子表失败!');
        }
        //更新出库验收单主表g_type g_nums
        $array = array('g_type'=>$g_type,'g_nums'=>$g_nums);
        $store_out_data_updata = $storeOutModel->where(array('s_out_id'=>$store_out_data_add))->save($array);
        if($store_out_data_updata === false){
            $this->rollback();
            return array("code" => "0", "msg" => "更新出库验货单主表数量种类失败");
        }
        //更新调拨申请单主表状态
        $store_to_store_updata = $this->where(array('s_t_s_id'=>$s_t_s_id))->save(array('s_t_s_status'=>1));
        if($store_to_store_updata===false){
            $this->rollback();
            return array('code'=>0,'msg'=>'更新调拨申请单主表状态失败!');
        }
        //更新调拨申请单子表状态

            $store_to_store_updata = $hii_store_to_store_detail->where(array('s_t_s_d_id'=>array('in',$store_to_store_where)))->save(array('is_pass'=>2));
            if($store_to_store_updata===false){
                $this->rollback();
                return array('code'=>0,'msg'=>'更新调拨申请单子表状态失败!');
            }

        $store_to_store_updata = $hii_store_to_store_detail->where(array('s_t_s_id'=>$s_t_s_id,'s_t_s_d_id'=>array('not in',$store_to_store_where)))->save(array('is_pass'=>1));
        if($store_to_store_updata===false){
            $this->rollback();
            return array('code'=>0,'msg'=>'更新调拨申请单子表状态失败!');
        }
            $this->commit();
        return array('code'=>200,'msg'=>'操作成功','data'=>$StoreToStoreEntity);
    }

    /**
     * 获取门店调拨申请单列表 
     * @param $store_id 门店id
     * @param $s_t_s_status 审核状态 必须 默认1 已审核 0 未审核
     */
    public function get_store_to_store($store_id, $s_t_s_status){
        //以后有能用的到 /*,M.nickname,SUM(A1.g_num * (case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END)) price*/
        $array = array();
        if($s_t_s_status >0 ){
            $array['STS.s_t_s_status'] = array('NEQ',0);
        }else{
            $array['STS.s_t_s_status'] = 0;
        }
        $array['STS.store_id1'] = $store_id;
        $array['STS.s_t_s_type'] = 0;
        $data = $this->alias('STS')
            ->field("STS.*,S.title as store_name,S1.title as allocation_name")
            ->join("left join hii_store_to_store_detail A1 on STS.s_t_s_id=A1.s_t_s_id ")
            ->join("left join hii_store S on S.id=STS.store_id1")
            ->join("left join hii_store S1 on S1.id=STS.store_id2")
            /*->join("left join hii_member M on M.uid=STS.admin_id")
            ->join("left join hii_goods G on G.id=A1.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")*/
            ->where($array)
            ->group("A1.s_t_s_id")
            ->order('STS.ctime desc')
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
     * 获取门店调拨申请单单个
     * @param $s_t_s_id 调拨申请单id
     * @param $store_id 门店id
     */
    public function get_store_to_store_one($store_id, $s_t_s_id){
        $array = array();
        $array['STS.store_id1'] = $store_id;
        $array['STS.s_t_s_type'] = 0;
        $array['STS.s_t_s_id'] = $s_t_s_id;
        $data = $this->alias('STS')
            ->field("STS.s_t_s_id,STS.s_t_s_sn,STS.s_t_s_status,STS.ctime,STS.remark,M.nickname,S.id as store_id,S.title as store_name,S1.id as allocation_id,S1.title as allocation_name")
            ->join("left join hii_store S on S.id=STS.store_id1")
            ->join("left join hii_store S1 on S1.id=STS.store_id2")
            ->join("left join hii_member M on M.uid=STS.admin_id")
            ->where($array)
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
     * 获取门店调拨申请单详情查看
     * @param $s_t_s_id 调拨申请单id
     * @param $store_id 门店id
     */
    public function get_store_to_store_detail($store_id,$s_t_s_id){
        $array = array();
        $array['STS.store_id1'] = $store_id;
        $array['STS.s_t_s_type'] = 0;
        $array['STS.s_t_s_id'] = $s_t_s_id;
        $data = $this->alias('STS')
            ->field("ifnull(GS2.num,0)allocation_num,ifnull(GS.num,0)store_num,G.title goods_name,GC.title cate_name,A1.*,S.title as store_name,S1.id as allocation_id,S1.title as allocation_name,M.nickname,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) price")
            ->join("left join hii_store_to_store_detail A1 on STS.s_t_s_id=A1.s_t_s_id and STS.s_t_s_id={$s_t_s_id}")
            ->join("left join hii_store S on S.id=STS.store_id1")
            ->join("left join hii_store S1  on S1.id=STS.store_id2")
            ->join("left join hii_member M on M.uid=STS.admin_id")
            ->join("left join hii_goods G on G.id=A1.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->join("left join hii_goods_store GS2 on GS2.store_id=S1.id and GS2.goods_id=A1.goods_id and STS.store_id2 = GS2.store_id")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->where($array)
            ->group("A1.goods_id")
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
}