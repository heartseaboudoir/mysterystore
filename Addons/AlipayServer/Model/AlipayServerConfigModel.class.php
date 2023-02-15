<?php
namespace Addons\AlipayServer\Model;
use Think\Model;

class AlipayServerConfigModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
	protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
                if(isset($result['status'])){
                    switch($result['status']){
                        case 1:
                            $result['status_text'] = '启用';
                            break;
                        default:
                            $result['status_text'] = '禁用';
                            break;
                    }
                }
                if(isset($result['config'])){
                    $result['config'] = json_decode($result['config'], true);
                }
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        

        protected function _before_insert(&$data, $options) {
            parent::_before_insert($data, $options);
            if(isset($data['config'])){
                empty($data['config']['msgset']['default']['content']) && $data['config']['msgset']['default']['content'] = "";
                empty($data['config']['msgset']['subscribe']['content']) && $data['config']['msgset']['subscribe']['content'] = "";
                $data['config'] = json_encode($data['config']);
            }
        }
        
        protected function _before_update(&$data, $options) {
            parent::_before_insert($data, $options);
            if(isset($data['config'])){
                empty($data['config']['msgset']['default']['content']) && $data['config']['msgset']['default']['content'] = "";
                empty($data['config']['msgset']['subscribe']['content']) && $data['config']['msgset']['subscribe']['content'] = "";
                $data['config'] = json_encode($data['config']);
            }
        }
        
        public function get_info(){
            return $this->find();
        }
        
}