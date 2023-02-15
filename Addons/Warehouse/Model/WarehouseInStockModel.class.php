<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseInStockModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交入库单
    public function saveWarehouseInStock($w_in_s_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $w_in_s_id ) {  //编辑计划
            $ok = M("WarehouseInStock")->where('w_in_s_id='.$w_in_s_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交入库单失败');
                return false;
            }
            $ok = M("WarehouseInStockDetail")->where('w_in_s_id='.$w_in_s_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'入库单商品删除失败');
                return false;
            }
        } else { //新增入库
            $w_in_s_id = $this->add($data);
            if(!$w_in_s_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存入库单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['w_in_s_id'] = $w_in_s_id;
        }
        $ok = M("WarehouseInStockDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存入库单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $w_in_s_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $w_in_s_id;
    }

}