<?php

namespace Addons\Goods\Model;
use Think\Model;

class GoodsStoreLogModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('goods_id', '/^[1-9]\d*$/', '未知商品', self::MUST_VALIDATE),
                array('store_id', '/^[1-9]\d*$/', '未知门店', self::MUST_VALIDATE),
                array('cate_id', '/^[1-9]\d*$/', '未知商品分类', self::MUST_VALIDATE),
                array('num', 'require', '请填写入库数量', self::MUST_VALIDATE),
                array('num', '/^[0-9][0-9]*\.{0,1}[0-9]*$/', '请正确填写入库数量', self::VALUE_VALIDATE),
        );
        
        protected $_auto = array(
                array('uid', UID, self::MODEL_INSERT),
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);
}