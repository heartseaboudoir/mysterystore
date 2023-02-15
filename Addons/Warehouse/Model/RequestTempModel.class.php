<?php


namespace Addons\Warehouse\Model;
use Think\Model;

class RequestTempModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('title', 'require', '请填写经销商名', self::MUST_VALIDATE),
        );

        protected $_auto = array(
		array('ctime', NOW_TIME, self::MODEL_INSERT),
	);

	protected function _after_find(&$result,$options) {
		isset($result['ctime']) && $result['ctime_text'] = date('Y-m-d H:i:s', $result['ctime']);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}

        public function update($data = NULL){
            $data = $this->create($data);
            if(!$data){
                return false;
            }
            if(empty($data['id'])){
                $id = $this->add();
                if(!$id){
                    $this->error = '添加出错！';
                    return false;
                }
            } else {
                $status = $this->save();
                if(false === $status){
                    $this->error = '更新出错！';
                    return false;
                }
            }
            return $data;
        }

    //提交临时申请
    public function saveRequestTemp($id, $data, $log){
        $this->startTrans(); //开启事务
        if( 0 < $id ) {  //编辑
            $ok = M("RequestTemp")->where('id='.$id)->save($data);
            if(!$ok) {
                $this->rollback();
                $this->err = array('code'=>1,'msg'=>'提交临时申请失败');
                return false;
            }
        } else { //新增
            $id = $this->add($data);
            if(!$id){
                $this->rollback();
                $this->err = array('code'=>3,'msg'=>'保存临时申请失败');
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