<?php

namespace User\Model;
use  Think\Model;

class CashCouponConfigModel extends Model{
    
        public function get_info($name, $get_new = false){
            $data = S('CASH_COUPON_CONFING');
            if(empty($data[$name]) || $get_new == true){
                $data[$name] = $this->where(array('name' => $name))->find();
                if(!$data[$name]) return array();
                S('CASH_COUPON_CONFING', $data);
            }
            return !empty($data[$name]) ? $data[$name] : array();
        }
}