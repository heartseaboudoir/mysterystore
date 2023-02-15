<?php


namespace Addons\Store\Model;
use Think\Model;
use User\Api\UserApi;

class ShequModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('title', 'require', '请填写社区名', self::MUST_VALIDATE),
        );
        
        protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

        protected function _after_find(&$result,$options) {
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
}