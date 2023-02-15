<?php
namespace Addons\Alipay\Model;
use Think\Model;

class AlipayConfigModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
	protected $_auto = array(
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        
        public function update($data = NULL){
            $data = $this->create($data);
            if(empty($data['id'])){
                $id = $this->add($data);
                if(!$id){
                    $this->error = '添加出错！';
                    return false;
                }
            } else {
                $status = $this->save($data);
                if(false === $status){
                    $this->error = '更新出错！';
                    return false;
                }
            }
            return $data;
        }

}