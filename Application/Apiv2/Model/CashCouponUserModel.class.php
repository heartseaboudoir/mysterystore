<?php

namespace Apiv2\Model;
use Think\Model;

class CashCouponUserModel extends Model{

	protected $_auto = array(
		array('code', 'get_code', self::MODEL_INSERT, 'callback'),
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
		array('status', '1', self::MODEL_INSERT),
	);
        
        protected function get_code($lv = 0){
            $lv = intval($lv);
            $code = substr(md5('CashCouponUser'.mt_rand(10000, 99999).$lv), 10, 10);
            if($this->where(array('code' => $code))->find()){
                $code = $this->get_code($lv+1);
            }
            return $code;
        }
}
