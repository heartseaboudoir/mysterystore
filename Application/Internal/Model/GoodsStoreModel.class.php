<?php
/**
 * 门店库存模型
 * User: zzy
 * Date: 2018-04-27
 * Time: 17:31
 */
namespace Internal\Model;
use Think\Model;
class GoodsStoreModel extends Model{

    /**
     * 门店商品列表
     * @param $store_id 门店id
     * @param $goods_name 门店id
     */
    public function get_store_all_goods($store_id,$goods_name,$stock_status=0){
        $array = array();
        $array['G.cate_id'] = array('neq',18);   
        if(!empty($goods_name)){
            $array['G.title'] = array('like',"%{$goods_name}%");      
        }
        if($stock_status){
            $shequ_id = M('Store')->where(array('id'=>$store_id))->getField('shequ_id');
            $array['G.id'] = array('exp',"in(select GS.goods_id as goods_id from hii_goods_store GS INNER JOIN hii_store S on S.id=GS.store_id and S.shequ_id = {$shequ_id} and S.status=1 union select  WS.goods_id as goods_id from hii_warehouse_stock WS INNER JOIN hii_warehouse W on W.w_id=WS.w_id and W.shequ_id = {$shequ_id} and W.w_type=0)");  
        }
        $data = $this->table('hii_goods AS G')
            ->field("ifnull(GS.num,0) as stock_num,{$store_id} as store_id,GC.title as cate_name,G.cate_id,G.id as goods_id,G.title as goods_name,G.cover_id,case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END sell_price,case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END price")
            ->join("left join hii_goods_store GS on G.id = GS.goods_id and GS.store_id = {$store_id}")
            ->join('left join hii_goods_cate GC on GC.id = G.cate_id')
            ->where($array)
            ->select();
        if($data === false){
            return false;
        }
        return $data;
    }
    /**
     * 门店商品列表调拨商品
     * @param $store_id 门店id
     * @param $store_id_allocation 门店id
     */
    public function get_store_all_goods__allocation($store_id,$store_id_allocation){
        $array = array();
        $array['G.cate_id'] = array('neq',18);  
        $data = $this->table('hii_goods AS G')
            ->field("{$store_id_allocation} as allocation_id,ifnull(GS1.num,0)allocation_num,ifnull(GS.num,0) as stock_num,{$store_id} as store_id,GC.title as cate_name,G.cate_id,G.id as goods_id,G.title as goods_name,G.cover_id,case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END sell_price,case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END price")
            ->join("left join hii_goods_store GS on G.id = GS.goods_id and GS.store_id = {$store_id}")
            ->join("inner join hii_goods_store GS1 on G.id = GS1.goods_id and GS1.store_id = {$store_id_allocation}")
            ->join('left join hii_goods_cate GC on GC.id = G.cate_id')
            ->where($array)
            ->select();
        if($data === false){
            return false;
        }
        return $data;
    }
    /**
     * 获取有门店库存的商品
     * @param $store_id
     */
    public function get_store_goods($store_id,$goods_name){
    	$array = array();
    	$array['G.cate_id'] = array('neq',18);  
    	if(!empty($goods_name)){
    		$array['G.title'] = array('like',"%{$goods_name}%");
    	}
        $data = $this->table('hii_goods AS G')
            ->field("ifnull(GS.num,0) as stock_num,{$store_id} as store_id,GC.title as cate_name,G.cate_id,G.id as goods_id,G.title as goods_name,G.cover_id,case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END sell_price,case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END price")
            ->join("inner join hii_goods_store GS on G.id = GS.goods_id and GS.store_id = {$store_id}")
            ->join('left join hii_goods_cate GC on GC.id = G.cate_id')
            ->where($array)
            ->select();
        if($data === false){
            return false;
        }
        return $data;
    }

}