<?php
namespace Addons\Wechat\Model;
use Think\Model;
use User\Api\UserApi;

class WechatConfigModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
	protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
                if(isset($result['config'])){
                    $result['config'] = json_decode($result['config'], true);
                }
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
        
        public function update($data = NULL){
            $data = $this->create($data);
            if(empty($data['id'])){
                $data['ukey'] = $data['config']['ukey'];
                $id = $this->add($data);
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