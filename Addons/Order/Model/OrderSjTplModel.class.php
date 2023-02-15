<?php

namespace Addons\Order\Model;
use Think\Model;

class OrderSjTplModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
	protected $_validate = array(
		array('cover_id', 'require', '设计效果图不能为空！', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
	);
        protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT),
            array('update_time', NOW_TIME, self::MODEL_BOTH),
            array('status', 1, self::MODEL_INSERT),
	);

	protected function _after_find(&$result,$options) {
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
                if(isset($result['status'])){
                    $status = array( 1 => '新版设计', 2 => '用户已确认', 3 => '用户已否定');
                    $result['status_text'] = $status[$result['status']];
                }
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
                    !$this->error && $this->error = '添加出错！';
                    return false;
                }
            } else {
                $status = $this->save();
                if(false === $status){
                    !$this->error && $this->error = '更新出错！';
                    return false;
                }
            }
            return $data;
        }

}