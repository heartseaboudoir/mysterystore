<?php

namespace Addons\Order\Model;
use Think\Model;

class OrderCartModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
	protected $_auto = array();

	protected function _after_find(&$result,$options) {
            isset($result['setting']) && $result['setting'] = json_decode($result['setting'], true);
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

}