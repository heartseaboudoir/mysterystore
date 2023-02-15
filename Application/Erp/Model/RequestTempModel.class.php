<?php


namespace Erp\Model;
use Think\Model;

class RequestTempModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */

    //提交临时申请
    /**
     * 参数
     * @data array 临时申请数组
     */
    public function saveRequestTemp($data, $log){
        $this->startTrans(); //开启事务
        foreach($data as $k=>$v){
            $id = $v['id'];
            if( 0 < $id ) {  //编辑计划
                $v['ctime'] = time();
                $ok = M("RequestTemp")->where('id='.$id)->save($v);
                if(!$ok) {
                    $this->rollback();
                    return array("status" => "0", "msg" => "提交临时申请单失败");
                }
            } else { //新增临时申请
                $ok = M("RequestTemp")->add($v);
                if(!$ok){
                    $this->rollback();
                    return array("status" => "0", "msg" => "保存临时申请单商品失败");
                }
            }
            if($log) {
                $log['for_id'] = $id;
                $ok = M("BillLog")->add($log);
                if(!$ok){
                    $this->rollback();
                    return array("status" => "0", "msg" => "保存日志失败");
                }
            }
        }
        $this->commit(); //提交事物
        return array("status" => "1", "msg" => "提交临时申请单成功");
    }

}