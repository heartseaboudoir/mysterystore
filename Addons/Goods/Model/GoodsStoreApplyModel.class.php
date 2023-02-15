<?php

namespace Addons\Goods\Model;
use Think\Model;

class GoodsStoreApplyModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('store_id', '/^[1-9]\d*$/', '未知门店', self::MUST_VALIDATE),
                array('data', 'require', '请填写入库信息', self::MUST_VALIDATE),
        );
        
        protected $_auto = array(
                array('uid', UID, self::MODEL_INSERT),
                array('sn', 'uniqid', self::MODEL_INSERT, 'function'),
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);
}