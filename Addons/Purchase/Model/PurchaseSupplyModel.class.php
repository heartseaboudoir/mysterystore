<?php


namespace Addons\Purchase\Model;
use Think\Model;

class PurchaseSupplyModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交采购申请
    public function savePurchaseSupply($p_s_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $p_s_id ) {  //编辑计划
            $ok = M("PurchaseSupply")->where('p_s_id='.$p_s_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交采购询价失败');
                return false;
            }
            $ok = M("PurchaseSupplyDetail")->where('p_s_id='.$p_s_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'采购询价商品删除失败');
                return false;
            }
        } else { //新增采购询价
            $p_s_id = $this->add($data);
            if(!$p_s_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存采购询价失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['p_s_id'] = $p_s_id;
        }
        $ok = M("PurchaseSupplyDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存采购询价商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $p_s_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $p_s_id;
    }

}