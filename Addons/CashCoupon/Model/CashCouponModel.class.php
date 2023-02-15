<?php

namespace Addons\CashCoupon\Model;
use Admin\Model\UcModel;

class CashCouponModel extends UcModel{
    
        protected $_validate = array(
            array('title', 'require', '请填写优惠券标题', self::MUST_VALIDATE),
            array('title', '1,20', '标题长度不能超过20个字', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
            array('description', 'require', '请填写优惠券描述', self::MUST_VALIDATE),
            array('description', '1,80', '描述长度不能超过80个字', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
            array('type', 'check_type', '请选择优惠券类型', self::MUST_VALIDATE, 'callback'),
            array('money', 'check_money', '请填写优惠金额，并且金额值为大于0', self::MUST_VALIDATE, 'callback'),
            array('min_use_money', 'check_min_use_money', '请填写使用的最低限额，并且金额值大于0的', self::MUST_VALIDATE, 'callback'),
            array('discount', 'check_discount', '请填写折扣额度，必须大于0且小于10，保留1位小数', self::MUST_VALIDATE, 'callback'),
            array('max_dis_money', 'check_max_dis_money', '请填写最多折扣金额，并且金额值大于0', self::MUST_VALIDATE, 'callback'),
        );
	protected $_auto = array(
		array('code', 'get_code', self::MODEL_INSERT, 'callback'),
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
                array('last_time', 'set_last_time', self::MODEL_BOTH, 'callback'),
	);
        
        protected function check_type(){
            $type = I('post.type');
            if(!in_array($type, array('1','2'))){
                return false;
            }
            return true;
        }
        protected function check_money(){
            $type = I('post.type');
            $money = round(I('post.money', 0), 2);
            if($type != 1){
                return true;
            }
            if($money > 0){
                return true;
            }
            return false;
        }
        protected function check_min_use_money(){
            $type = I('post.type');
            $money = round(I('post.min_use_money', 0), 2);
            if($type != 1){
                return true;
            }
            if($money > 0){
                return true;
            }
            return false;
        }
        protected function check_discount(){
            $type = I('post.type');
            $discount = round(I('post.discount', 0), 1);
            if($type != 2){
                return true;
            }
            if($discount > 0 && $discount < 10){
                return true;
            }
            return false;
        }
        protected function check_max_dis_money(){
            $type = I('post.type');
            $money = round(I('post.max_dis_money', 0), 2);
            if($type != 2){
                return true;
            }
            if($money > 0){
                return true;
            }
            return false;
        }
        
        protected function set_last_time($param){
            if(!$param){
                return 0;
            }else{
                return strtotime($param);
            }
        }
        
        protected function get_code($lv = 0){
            $lv = intval($lv);
            $code = substr(md5('CashCoupon'.mt_rand(10000, 99999).$lv), 10, 10);
            if($this->where(array('code' => $code))->find()){
                $code = $this->get_code($lv+1);
            }
            return $code;
        }
        
        protected function _after_find(&$result,$options) {
		isset($result['rule']) && $result['rule'] = json_decode($result['rule'], true);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        
        protected function _before_insert(&$data, $options) {
            parent::_before_insert($data, $options);
            $data = $this->_set_data($data);
        }
        
        protected function _before_update(&$data, $options) {
            parent::_before_update($data, $options);
            $data = $this->_set_data($data);
        }
        
        private function _set_data($data){
            if(isset($data['rule'])){
                $data['rule'] = json_encode($data['rule']);
            }
            return $data;
        }
}