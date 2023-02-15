<?php
/**
 * 门店发货申请单模型
 * User: dehuang
 * Date: 2018-04-28
 * Time: 17:43
 */
namespace Internal\Model;
use Think\Model;
class StoreRequestModel extends Model{

    /**
     * 提交门店发货申请单
     * @param $admin_id  管理员id
     * @parame $store_id 门店id
     * @parame $w_id 仓库id
     * @param $remark  备注
     * @param $temp  数据
     */
    public function submit_request($admin_id, $store_id, $w_id, $remark,$temp){
        $this->startTrans();
        //判断发货临时申请表是否有数据
            if(empty($temp)){
                return array('code'=>0,'msg'=>'临时发货申请单无数据');
            }
            //判断仓库是否有库存

            $g_type = 0;
            $g_nums = 0;
            foreach($temp as $k=>$v){
                $g_type +=1;
                $g_nums += $v['g_num'];
                //判断是否选择属性
               if(!$v['value_id']){
               	return array('code'=>0,'msg'=>$v['goods_id'].'商品未选择属性');
               }
            }
        //生成发货申请单主表信息
        $StoreRequestEntity = array();
        $StoreRequestEntity["s_r_sn"] = get_new_order_no("SQ", "hii_store_request", "s_r_sn");
        $StoreRequestEntity["s_r_type"] = 0;
        $StoreRequestEntity["s_r_status"] = 0;
        $StoreRequestEntity["ctime"] = time();
        $StoreRequestEntity["admin_id"] = $admin_id;
        $StoreRequestEntity["store_id"] = $store_id;
        $StoreRequestEntity["warehouse_id"] = $w_id;
        $StoreRequestEntity["remark"] = $remark;
        $StoreRequestEntity["g_type"] = $g_type;
        $StoreRequestEntity["g_nums"] = $g_nums;
        $s_r_id = $this->add($StoreRequestEntity);
        if(empty($s_r_id)){
            $this->rollback();
            return array('code'=>0,'msg'=>'生成发货申请单主表失败');
        }
        
        //生成发货申请单子表信息
        $NewStoreRequestDetailEntitys = array();
        $value_repeat = array();
        foreach ($temp as $key => $val) {
        	if(array_key_exists($val['goods_id'].$val['value_id'],$value_repeat)){
        		$NewStoreRequestDetailEntitys[$value_repeat[$val['goods_id'].$val['value_id']]] = array(
        				's_r_id'=>$s_r_id,
        				'goods_id'=>$val["goods_id"],
        				'g_num'=>$val["g_num"],
        				'is_pass'=>0,
        				'pass_num'=>0,
        				'remark'=>$val["remark"],
        				'value_id'=>$val["value_id"],
        		);
        	}else{
        		$NewStoreRequestDetailEntitys[] = array(
        				's_r_id'=>$s_r_id,
        				'goods_id'=>$val["goods_id"],
        				'g_num'=>$val["g_num"],
        				'is_pass'=>0,
        				'pass_num'=>0,
        				'remark'=>$val["remark"],
        				'value_id'=>$val["value_id"],
        		);
        	}
        	$value_repeat[$val['goods_id'].$val['value_id']] = $key;
        }
        $hii_store_request_detail = M('StoreRequestDetail');
        if (!empty($NewStoreRequestDetailEntitys)) {
            $ok = $hii_store_request_detail->addAll($NewStoreRequestDetailEntitys);
            if ($ok === false) {
                $this->rollback();
                return array("code" => "0", "msg" => "新增发货申请子表信息失败");
            }
        }else{
            $this->rollback();
            return array('code'=>0,'msg'=>'新增发货申请子表信息失败1');
        }
        $this->commit();
        return array('code'=>200,'msg'=>'操作成功','data'=>$StoreRequestEntity);
    }

    /**
     * 获取门店发货申请列表
     * @param $store_id  门店id
     * @param $s_r_status  审核状态
     */
    public  function get_store_request($store_id,$s_r_status=1){
        $array = array();
        if($s_r_status >0 ){
            $array['SR.s_r_status'] = array('NEQ',0);
        }else{
            $array['SR.s_r_status'] = 0;
        }
        $array['SR.store_id'] = $store_id;

        $data = $this->alias('SR')
            ->field("sum(A1.pass_num) as sf_nums,sum(A1.is_pass)as pass_num, SR.*,S.title as store_name,W.w_name as w_name,M.nickname,SUM(A1.g_num * (case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END)) price")
            ->join("left join hii_store_request_detail A1 on SR.s_r_id=A1.s_r_id ")
            ->join("left join hii_store S on S.id=SR.store_id")
            ->join("left join hii_warehouse W on W.w_id=SR.warehouse_id")
            ->join("left join hii_member M on M.uid=SR.admin_id")
            ->join("left join hii_goods G on G.id=A1.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->where($array)
            ->group("A1.s_r_id")
            ->order('SR.ctime desc')
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
     * 获取单个门店发货申请单
     * @param $s_r_id 发货申请单id
     * @param $store_id
     */
    public  function get_store_request_one($store_id,$s_r_id){
        $array = array();
        $array['SR.store_id'] = $store_id;
        $array['SR.s_r_id'] = $s_r_id;
        $data = $this->alias('SR')
            ->field("SR.s_r_id,SR.s_r_sn,SR.ctime,SR.s_r_status,SR.remark,SR.s_r_type,S.id as store_id,S.title as store_name,W.w_id,W.w_name as w_name,M.nickname")
            ->join("left join hii_store S on S.id=SR.store_id")
            ->join("left join hii_warehouse W on W.w_id=SR.warehouse_id")
            ->join("left join hii_member M on M.uid=SR.admin_id")
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
     * 获取门店申请单详情查看
     * @param $s_r_id 发货申请单id
     * @param $store_id
     */
    public function get_store_request_detail($store_id,$s_r_id){
        $array = array();
        $array['SR.store_id'] = $store_id;
        $array['SR.s_r_id'] = $s_r_id;
        $data = $this->alias('SR')
            ->field("ifnull(AV.value_id,'')value_id,ifnull(AV.value_name,'')value_name,ifnull(GS.num,0) store_num,ifnull(WS.num,0) warehouse_num,G.title goods_name,GC.title cate_name,A1.*,S.title as store_name,W.w_id as w_id,W.w_name as w_name,M.nickname,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) price")
            ->join("left join hii_store_request_detail A1 on SR.s_r_id=A1.s_r_id and SR.s_r_id={$s_r_id}")
            ->join("left join hii_store S on S.id=SR.store_id")
            ->join("left join hii_warehouse W on W.w_id=SR.warehouse_id")
            ->join("left join hii_warehouse_stock WS on WS.w_id=SR.warehouse_id and WS.value_id=A1.value_id")
            ->join("left join hii_member M on M.uid=SR.admin_id")
            ->join("left join hii_goods G on G.id=A1.goods_id")
            ->join("left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id}")
            ->join("left join hii_goods_cate GC on GC.id=G.cate_id")
            ->join("left join hii_attr_value AV on AV.value_id=A1.value_id")
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
}