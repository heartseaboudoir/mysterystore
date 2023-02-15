<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class PurchaseRequestModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交采购申请
    public function savePurchaseRequest($p_r_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $p_r_id ) {  //编辑计划
            $ok = M("PurchaseRequest")->where('p_r_id='.$p_r_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'保存采购申请失败');
                return false;
            }
            $ok = M("PurchaseRequestDetail")->where('p_r_id='.$p_r_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'采购申请商品删除失败');
                return false;
            }
        } else { //新增计划
            $p_r_id = $this->add($data);
            if(!$p_r_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存计划失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['p_r_id'] = $p_r_id;
        }
        $ok = M("PurchaseRequestDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存采购申请商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $p_r_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $p_r_id;
    }

}