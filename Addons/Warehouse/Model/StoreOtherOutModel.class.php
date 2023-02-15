<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class StoreOtherOutModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交出库验货单
    public function saveStoreOtherOut($s_o_out_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $s_o_out_id ) {  //编辑
            $ok = M("StoreOtherOut")->where('s_o_out_id='.$s_o_out_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交门店退货单失败');
                return false;
            }
            $ok = M("StoreOtherOutDetail")->where('s_o_out_id='.$s_o_out_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'门店退货单商品删除失败');
                return false;
            }
        } else { //新增
            $s_o_out_id = $this->add($data);
            if(!$s_o_out_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存门店退货单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['s_o_out_id'] = $s_o_out_id;
        }
        $ok = M("StoreOtherOutDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存门店退货单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $s_o_out_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $s_o_out_id;
    }

}