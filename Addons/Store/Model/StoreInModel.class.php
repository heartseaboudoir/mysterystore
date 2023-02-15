<?php


namespace Addons\Store\Model;
use Think\Model;

class StoreInModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交采购申请
    public function saveStoreIn($s_in_id, $data, $dataPro, $log){
        $this->startTrans(); //开启事务
        if( 0 < $s_in_id ) {  //编辑计划
            $ok = M("StoreIn")->where('s_in_id='.$s_in_id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交门店入库验收单失败');
                return false;
            }
            $ok = M("StoreInDetail")->where('s_in_id='.$s_in_id)->delete();
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>2,'msg'=>'门店入库验收单商品删除失败');
                return false;
            }
        } else { //新增入库
            $s_in_id = $this->add($data);
            if(!$s_in_id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存门店入库验收单失败');
                return false;
            }
        }
        foreach($dataPro as $k=>$v){
            $dataPro[$k]['s_in_id'] = $s_in_id;
        }
        $ok = M("StoreInDetail")->addAll($dataPro);
        if(!$ok){
            $this->rollback();
            $this->err = array('code'=>4,'msg'=>'保存门店入库验收单商品失败');
            return false;
        }
        if($log) {
            $log['for_id'] = $s_in_id;
            $ok = M("BillLog")->add($log);
            if(!$ok){
                $this->rollback();
                $this->err = array('code'=>5,'msg'=>'保存日志失败');
                return false;
            }
        }
        $this->commit(); //提交事物
        return $s_in_id;
    }

}