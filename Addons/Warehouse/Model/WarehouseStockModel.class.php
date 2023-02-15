<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseStockModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交入库库存
    public function saveWarehouseStock($id, $data, $log){
        $this->startTrans(); //开启事务
        if( 0 < $id ) {  //编辑
            $ok = M("WarehouseStock")->where('id='.$id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交入库失败');
                return false;
            }
        } else { //新增
            $id = $this->add($data);
            if(!$id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存入库失败');
                return false;
            }
        }
        if($log) {
            $log['for_id'] = $id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $id;
    }

}