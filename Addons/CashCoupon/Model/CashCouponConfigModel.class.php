<?php

namespace Addons\CashCoupon\Model;
use Admin\Model\UcModel;

class CashCouponConfigModel extends UcModel{
    
        protected $_validate = array(
        );
        
	protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);
}