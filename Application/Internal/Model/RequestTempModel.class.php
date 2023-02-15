<?php
/**
 * 临时申请表模型
 * User: zzy
 * Date: 2018-04-28
 * Time: 15:12
 */
namespace Internal\Model;
use Think\Model;
class RequestTempModel extends Model{
    /**
     * 获取发货临时申请商品列表
     * @param $store_id 门店id  必须
     * @param $w_id 选择要发货的仓库 必须
     * @param $admin_id 管理员id
     */
    public function get_deliver_goods_request_temp($store_id, $w_id, $admin_id){
        $array=array();
        $array['RT.admin_id'] = $admin_id;
        $array['RT.temp_type'] = 2;
        $array['RT.store_id'] = $store_id;
        $field = "G.id as goods_id,G.title as goods_name,G.cover_id,G.cate_id,GC.title as cate_name,RT.id as re_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,RT.g_num,ifnull(GS.num,0) as gs_num,ifnull(WS.num,0) as ws_num,
        case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END price,RT.remark";
        $data = $this->field($field)
            ->alias('RT')
            ->join("left join hii_goods G on G.id=RT.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=RT.goods_id and GS.store_id={$store_id}")
            ->join("left join hii_warehouse_stock WS on WS.value_id=RT.value_id and WS.w_id={$w_id}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->where($array)
            ->select();
        if($data === false){
            return false;
        }
        return $data;
    }
    /**
     * 获取调拨临时申请商品列表
     * @param $store_id 门店id  必须
     * @param $store_id_allocation 选择要发货的门店 必须
     * @param $admin_id 管理员id
     */
    public function get_deliver_goods_request_temp_allocation($store_id, $store_id_allocation, $admin_id){
        $array=array();
        $array['RT.admin_id'] = $admin_id;
        $array['RT.temp_type'] = 6;
        $array['RT.store_id'] = $store_id;
        $field = "G.id as goods_id,G.title as goods_name,G.cover_id,G.cate_id,GC.title as cate_name,RT.id as re_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,RT.g_num,ifnull(GS.num,0) as gs_num,ifnull(GS1.num,0) as allocation_num,
        case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END price,RT.remark";
        $data = $this->field($field)
            ->alias('RT')
            ->join("left join hii_goods G on G.id=RT.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=RT.goods_id and GS.store_id={$store_id}")
            ->join("left join hii_goods_store GS1 on GS1.goods_id=RT.goods_id and GS1.store_id={$store_id_allocation}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->where($array)
            ->select();
        if($data === false){
            return false;
        }
        return $data;
    }

    /**
     * 加入临时申请表
     * @param $admin_id  管理员id
     * @param $store_id  门店id
     * @param $w_id     仓库id
     * @param $detail_array  数据
     * @param $temp_type  来源 类型:1.采购申请,2.发货申请,3.仓库调拨申请,4.退货申请,5.临时采购单,6门店调拨申请,7.盘点,8.门店盘点,9.门店寄售,10.门店返仓,11.仓库出库验货,12.门店寄售出库
     */
    public function add_deliver_goods_request_temp($admin_id, $store_id, $w_id, $detail_array, $temp_type){
        $this->startTrans();
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $StoreModel = M("Store");
        //获取区域id
        $shequ_id = $StoreModel->where(array('id'=>$store_id))->getField("shequ_id");
        //获取g_price
        $array = array();
        foreach($detail_array as $k=>$v){
            $info = $this->where(array('admin_id'=>$admin_id,'store_id'=>$store_id,'goods_id'=>$v['goods_id'],'temp_type'=>$temp_type))->find();
            $array1 = array();
            if(!empty($info)){
                $array1['g_num'] = $v['g_num'];
                $array1['remark'] = $v['remark'];
                $info = $this->where(array('admin_id'=>$admin_id,'store_id'=>$store_id,'goods_id'=>$v['goods_id'],'temp_type'=>$temp_type))->save($array1);
                if($info === false){
                    $this->rollback();
                    return array('code'=>0,'msg'=>'加入临时申请表失败');
                }
            }else{
                $array[$k]['admin_id'] = $admin_id;
                $array[$k]['warehouse_id'] = $w_id;
                $array[$k]['store_id'] = $store_id;
                $array[$k]['temp_type'] = $temp_type;
                $array[$k]['goods_id'] = $v['goods_id'];
                $array[$k]['ctime'] = time();
                $array[$k]['status'] = 0;
                $array[$k]['b_n_num'] = 0;
                $array[$k]['b_num'] = 0;
                $array[$k]['b_price'] = 0;
                $array[$k]['g_num'] = $v['g_num'];
                $array[$k]['remark'] = $v['remark'];
                $array[$k]['g_pirce'] = $WarehouseInoutViewModel->where(array('goods_id'=>$v['goods_id'],'shequ_id'=>$shequ_id))->getField('ifnull(stock_price,0)');
            }
        }
        if(!empty($array)){
            $info = $this->addAll($array);
        }
        if($info === false){
            $this->rollback();
            return array('code'=>0,'msg'=>'加入临时申请表失败1');
        }

        $this->commit();
        return array('code'=>200,'msg'=>'操作成功');
    }
}