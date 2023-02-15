<?php
/**
 * 仓库库存模型
 * User: zzy
 * Date: 2018-04-27
 * Time: 17:31
 */
namespace Internal\Model;
use Think\Model;
class WarehouseStockModel extends Model{

    /**
     * 仓库商品列表
     * @param $store_id 仓库id
     * @param $goods_name 商品名
     * @param $page  分页
     * @paran  $pageSize 每页条数
     */
    public function get_store_all_goods($store_id, $goods_name, $page=1, $pageSize=15){
        $array=array();
        $array['WS.w_id'] = $store_id;
        if(!empty($goods_name)){
            $array['G.title'] = array('like',"%{$goods_name}%");
        }
        $data = $this->alias('WS')
            ->field('WS.w_id,WS.goods_id,WS.num as ws_num,G.title as goods_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)as price,GC.title as cate_title')
            ->join('left join hii_goods G on G.id = WS.goods_id')
            ->join('left join hii_goods_cate GC on GC.id = G.cate_id')
            ->join("left join (select goods_id,shequ_price from hii_shequ_price where shequ_id={$shequ_id}) SP on  WS.goods_id=SP.goods_id")
            ->where($array)
            ->select(false);
        $data = $this->table("{$data} as a")->page("{$page},{$pageSize}")->select();
        if($data === false){
            return false;
        }
        return $data;
    }
    /**
     * 仓库商品列表 总条数 分页用
     * @param $w_id 仓库id
     * @param $shequ_id 社区id
     * @param $goods_name 商品名
     * @param $page  分页
     * @paran  $pageSize 每页条数
     */
    public function get_store_all_goods_page_count($w_id, $goods_name, $shequ_id){
        $array=array();
        $array['WS.w_id'] = $w_id;
        if(!empty($goods_name)){
            $array['G.title'] = array('like',"%{$goods_name}%");
        }
        $data = $this->alias('WS')
            ->field('WS.w_id')
            ->join('left join hii_goods G on G.id = WS.goods_id')
            //->join('left join hii_goods_cate GC on GC.id = G.cate_id')
            //->join("left join (select goods_id,price from hii_shequ_price_snapshot where shequ_id={$shequ_id}) SP on  WS.goods_id=SP.goods_id")
            ->where($array)
            ->select(false);
        $data = $this->table("{$data} as a")->count();
        if($data === false){
            return false;
        }
        return $data;
    }
}