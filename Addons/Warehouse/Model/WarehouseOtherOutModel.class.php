<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class WarehouseOtherOutModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交出库验货单
    public function saveWarehouseOtherOut($w_o_out_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $w_o_out_id ) {  //编辑
            $ok = M("WarehouseOtherOut")->where('w_o_out_id='.$w_o_out_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交仓库报损单失败');
                return false;
            }
            $ok = M("WarehouseOtherOutDetail")->where('w_o_out_id='.$w_o_out_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'仓库报损单商品删除失败');
                return false;
            }
        } else { //新增
            $w_o_out_id = $this->add($data);
            if(!$w_o_out_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存仓库报损单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['w_o_out_id'] = $w_o_out_id;
        }
        $ok = M("WarehouseOtherOutDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存仓库报损单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $w_o_out_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $w_o_out_id;
    }

}