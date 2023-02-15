<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseInventoryModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交采购申请
    public function saveWarehouseInventory($i_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $i_id ) {  //编辑计划
            $ok = M("WarehouseInventory")->where('i_id='.$i_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交盘点单失败');
                return false;
            }
            $ok = M("WarehouseInventoryDetail")->where('i_id='.$i_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'盘点单商品删除失败');
                return false;
            }
        } else {
        	//新增入库
            $i_id = $this->add($data);
            if(!$i_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存盘点单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['i_id'] = $i_id;
        }
        $ok = M("WarehouseInventoryDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存盘点单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $i_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $i_id;
    }

    public function delWarehouseInventory($i_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $i_id ) {  //编辑计划
            $ok = M("WarehouseInventory")->where('i_id='.$i_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'删除盘点单失败');
                return false;
            }
            $ok = M("WarehouseInventoryDetail")->where('i_id='.$i_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'盘点单商品删除失败');
                return false;
            }
        } else {
            $this->rollback();
            $this->err = array('code'=>3,'msg'=>'删除盘点单失败:没有id');
            return false;
        }
        $this->commit(); //提交事物
        return $i_id;
    }
}