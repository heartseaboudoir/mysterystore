<?php

namespace Addons\CashCoupon\Model;
use Admin\Model\UcModel;

class CashCouponUserModel extends UcModel{
    
        protected $_validate = array(
        );
	protected $_auto = array(
		array('code', 'get_code', self::MODEL_INSERT, 'callback'),
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
		array('receive_ip', 'get_client_ip', self::MODEL_INSERT, 'function'),
	);
        
        protected function get_code($lv = 0){
            $lv = intval($lv);
            $code = substr(md5('CashCouponUser'.mt_rand(10000, 99999).$lv), 10, 10);
            if($this->where(array('code' => $code))->find()){
                $code = $this->get_code($lv+1);
            }
            return $code;
        }
        
        protected function _after_find(&$result, $options) {
            parent::_after_find($result, $options);
            if(isset($result['last_time']) && isset($result['status']) && isset($result['code'])){
                if($result['status'] == 1 && $result['last_time'] > 0 && $result['last_time'] - time() <= 0){
                    $this->where(array('code' => $result['code']))->save(array('status' => 3));
                    $result['status'] = 3;
                }
            }
        }
        
	protected function _after_select(&$result,$options){
            foreach($result as &$record){
                $this->_after_find($record,$options);
            }
	}
}