<?php
/**
 * 门店报损单表模型
 * User: zzy
 * Date: 2018-05-08
 * Time: 16:30
 */
namespace Internal\Model;
use Think\Model;
class StoreOtherOutModel extends Model{
    /**
     * 获取退货单列表
     * @param $store_id 门店id
     */
    public function get_store_other_out_list($store_id){
        $data = $this->alias('SOO')
            ->field('ifnull(W.w_id,\'\')w_id,ifnull(W.w_name,\'\')w_name,ifnull(S2.id,0)store_id,ifnull(S2.title,0)store_name,ifnull(S1.id,\'\')srore_id1,ifnull(S1.title,\'\')store_name1,SOO.s_o_out_id,SOO.s_o_out_sn,SOO.s_o_out_status,SOO.s_o_out_type,SOO.ctime,SOO.remark,SOO.g_type,SOO.g_nums')
            ->join("inner join hii_store S2 on S2.id=SOO.store_id2 and SOO.store_id2={$store_id}")
            ->join("left join hii_store S1 on S1.id=SOO.store_id1")
            ->join("left join hii_warehouse W on W.w_id=SOO.warehouse_id")
            ->order('SOO.ctime desc')
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
     * 获取退货单单个
     * @param $store_id 门店id
     * @param $s_o_out_id  退货单id
     */
    public function get_store_other_out_list_detail($store_id, $s_o_out_id){
        $array = array();
        $array['SOO.s_o_out_id'] = $s_o_out_id;
        $data = $this->alias('SOO')
            ->field('M.nickname,ifnull(W.w_id,\'\')w_id,ifnull(W.w_name,\'\')w_name,ifnull(S2.id,0)store_id,ifnull(S2.title,0)store_name,ifnull(S1.id,\'\')srore_id1,ifnull(S1.title,\'\')store_name1,SOO.s_o_out_id,SOO.s_o_out_sn,SOO.s_o_out_status,SOO.s_o_out_type,SOO.ctime,SOO.remark,SOO.g_type,SOO.g_nums')
            ->join("inner join hii_store S2 on S2.id=SOO.store_id2 and SOO.store_id2={$store_id}")
            ->join("left join hii_store S1 on S1.id=SOO.store_id1")
            ->join("left join hii_warehouse W on W.w_id=SOO.warehouse_id")
            ->join("left join hii_member M on M.uid=SOO.admin_id")
            ->where($array)
            ->order('SOO.ctime desc')
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
     * 获取退货单子表详情
     * @param $store_id 门店id
     * @param $s_o_out_id  退货单id
     */
    public function get_store_other_out_detail($store_id, $s_o_out_id){
        $array = array();
        $data = $this->table('hii_store_other_out_detail SOOD')
            ->field("ifnull(AV.value_id,'')value_id,ifnull(AV.value_name,'')value_name, G.id goods_id,G.title goods_name,GC.title cate_name,SOO.ctime,SOO.s_o_out_id,ifnull(SID.g_num,0)in_num,ifnull(SOOD.g_num,0)g_num,ifnull(S1.id,'')store_id1,ifnull(S1.title,'')store_name1,SOOD.remark dremark,ifnull(W.w_id,'') w_id,ifnull(W.w_name,'') w_name,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) price")
            ->join("inner join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id and SOO.s_o_out_id={$s_o_out_id}")
            ->join("left join hii_store_in_detail SID on SOOD.s_in_d_id=SID.s_in_d_id")
            ->join("left join hii_warehouse W on W.w_id=SOO.warehouse_id")
            ->join("left join hii_store S1 on S1.id=SOO.store_id1")
            ->join("left join hii_goods G on G.id=SOOD.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->join("left join hii_attr_value AV on AV.value_id=SOOD.value_id")
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
     * 获取被退货单列表
     * @param $store_id 门店id
     * @param $s_out_status 状态:0.新增,1.已审核
     */
    public function get_cover_store_other_out_list_model($store_id, $s_out_status=0){
        $array = array();
        $array['SOO.s_o_out_status'] = $s_out_status;
        $data = $this->alias('SOO')
            ->field('ifnull(W.w_id,\'\')w_id,ifnull(W.w_name,\'\')w_name,ifnull(S1.id,0)store_id,ifnull(S1.title,0)store_name,ifnull(S2.id,\'\')srore_id2,ifnull(S2.title,\'\')store_name2,SOO.s_o_out_id,SOO.s_o_out_sn,SOO.s_o_out_status,SOO.s_o_out_type,SOO.ctime,SOO.remark,SOO.g_type,SOO.g_nums')
            ->join("inner join hii_store S1 on S1.id=SOO.store_id1 and SOO.store_id1={$store_id}")
            ->join("left join hii_store S2 on S2.id=SOO.store_id2")
            ->join("left join hii_warehouse W on W.w_id=SOO.warehouse_id")
            ->where($array)
            ->order('SOO.ctime desc')
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
     * 获取被退货单单个
     * @param $store_id 门店id
     * @param $s_o_out_id  退货单id
     */
    public function get_cover_store_other_out_list_detail_model($store_id, $s_o_out_id){
        $array = array();
        $array['SOO.s_o_out_id'] = $s_o_out_id;
        $data = $this->alias('SOO')
            ->field('M.nickname,ifnull(W.w_id,\'\')w_id,ifnull(W.w_name,\'\')w_name,ifnull(S1.id,0)store_id,ifnull(S1.title,0)store_name,ifnull(S2.id,\'\')srore_id2,ifnull(S2.title,\'\')store_name2,SOO.s_o_out_id,SOO.s_o_out_sn,SOO.s_o_out_status,SOO.s_o_out_type,SOO.ctime,SOO.remark,SOO.g_type,SOO.g_nums')
            ->join("inner join hii_store S1 on S1.id=SOO.store_id1 and SOO.store_id1={$store_id}")
            ->join("left join hii_store S2 on S2.id=SOO.store_id2")
            ->join("left join hii_warehouse W on W.w_id=SOO.warehouse_id")
            ->join("left join hii_member M on M.uid=SOO.admin_id")
            ->where($array)
            ->order('SOO.ctime desc')
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
     * 获取被退货单子表详情
     * @param $store_id 门店id
     * @param $s_o_out_id  退货单id
     */
    public function get_cover_store_other_out_detail_model($store_id, $s_o_out_id){
        $data = $this->table('hii_store_other_out_detail SOOD')
            ->field("ifnull(AV.value_id,'')value_id,ifnull(AV.value_name,'')value_name,G.id goods_id,G.title goods_name,GC.title cate_name,SOO.ctime,SOO.s_o_out_id,ifnull(SID.g_num,0)in_num,ifnull(SOOD.g_num,0)g_num,ifnull(S2.id,'')store_id2,ifnull(S2.title,'')store_name2,SOOD.remark dremark,ifnull(W.w_id,'') w_id,ifnull(W.w_name,'') w_name,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) price")
            ->join("inner join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id and SOO.s_o_out_id={$s_o_out_id}")
            ->join("left join hii_store_in_detail SID on SOOD.s_in_d_id=SID.s_in_d_id")
            ->join("left join hii_warehouse W on W.w_id=SOO.warehouse_id")
            ->join("left join hii_store S2 on S2.id=SOO.store_id2")
            ->join("left join hii_goods G on G.id=SOOD.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->join("left join hii_attr_value AV on AV.value_id=SOOD.value_id")
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
     * 被退货单审核
     * @param $admin_id  审核管理员id
     * @param $store_id 门店id
     * @param $s_out_id 被退货单id
     */
    public function check_model($admin_id,  $store_id, $s_o_out_id){
        $storeOtherOutDetailModel = M('StoreOtherOutDetail');
        $goodsStoreModel = M('GoodsStore');
        $this->startTrans();
        $data = $this->where(array('s_o_out_id'=>$s_o_out_id,'s_o_out_status'=>0))->find();
        if(empty($data)){
            $this->rollback();
            return array('code'=>0,'msg'=>'被退货单不存在或已审核 无法审核');
        }
        $data = $storeOtherOutDetailModel->where(array('s_o_out_id'=>$s_o_out_id))->select();
        foreach($data as $key=>$val){
            $tmp = $goodsStoreModel->where(array('goods_id'=>$val['goods_id'],'store_id'=>$store_id))->find();
            if($tmp === false){
                $this->rollback();
                return array('code'=>0,'msg'=>'查询商品库存内部错误!');
            }
            if ($tmp == null) {
                //新增
                $GoodsStoreEntity = array();
                $GoodsStoreEntity["store_id"] = $store_id;
                $GoodsStoreEntity["goods_id"] = $val['goods_id'];
                $GoodsStoreEntity["num"] = $val["g_num"];
                $GoodsStoreEntity["update_time"] = time();
                $ok = $goodsStoreModel->add($GoodsStoreEntity);
                if ($ok === false) {
                    $this->rollback();
                    return array("code" => "0", "msg" => "新增门店库存信息失败");
                }
            } else {
                //更新库存数量
                $num = $val["g_num"] + $tmp["num"];
                $savedata = array("num" => $num);
                $ok = $goodsStoreModel->where(array('id'=>$tmp['id']))->save($savedata);
                if ($ok === false) {
                    $this->rollback();
                    return array("code" => "0", "msg" => "更新门店库存信息失败");
                }
            }
        }
        //更新退货单主表信息
        $ptime = time();
        $ok = $this->where(array('s_o_out_id'=>$s_o_out_id))->save(array("padmin_id" => $admin_id, "ptime" => $ptime, "s_o_out_status" => 1));
        if ($ok === false) {
            $this->rollback();
            return array("code" => "0", "msg" => "更新退货单主表信息失败");
        }

        $this->commit();
        return array("code" => "200", "msg" => "审核成功");
    }
}