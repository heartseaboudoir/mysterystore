<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseInoutModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交入库审核
    public function saveWarehouseInout($inout_id, $data, $log){
        $this->startTrans(); //开启事务
        if( 0 < $inout_id ) {  //编辑
            $ok = M("WarehouseInout")->where('inout_id='.$inout_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交入库批次失败');
                return false;
            }
        } else { //新增
            $inout_id = $this->add($data);
            if(!$inout_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存入库批次失败');
                return false;
            }
        }
        if($log) {
            $log['for_id'] = $inout_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $inout_id;
    }
    //数组入库审核
    public function saveWarehouseInoutList($data){
        $this->startTrans(); //开启事务
        foreach($data as $k=>$v) {
            //$res = $this->saveWarehouseInout($data[$k]['inout_id'], $data[$k],false);

            if( 0 < $data[$k]['inout_id'] ) {  //编辑
                $ok = M("WarehouseInout")->where('inout_id='.$data[$k]['inout_id'])->save($data[$k]);
                if(!$ok) {
                    $this->rollback();
                    $this->err = array('code'=>1,'msg'=>'提交入库批次失败');
                    return false;
                }
            } else { //新增
                $inout_id = $this->add($data[$k]);
                if(!$inout_id){
                    $this->rollback();
                    $this->err = array('code'=>3,'msg'=>'保存入库批次失败');
                    return false;
                }
            }
        }
        $this->commit(); //提交事物
        return $data;
    }

    //获得平均入库价
    public function getWarehouseInoutAvgPrice($goods_id,$shequ_id){
        if(is_array($goods_id) && count($goods_id) > 0){
            $goods_id_ary_to_str = implode(',',$goods_id);
        }else{
            if($goods_id == '' || $goods_id == 0){
                return array();
            }else{
                $goods_id_ary_to_str = $goods_id;
            }
        }
        $sql = "select shequ_id,goods_id,cast((sum(num * inprice) / sum(num)) as decimal(10,2)) AS stock_price,
          sum(num * inprice) AS stock_amount from hii_warehouse_inout where goods_id in ($goods_id_ary_to_str) and shequ_id = $shequ_id group by goods_id";
        $backary = $this->query($sql);
        return $backary;
    }

}