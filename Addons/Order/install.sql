CREATE TABLE `tablepre_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_sn` varchar(255) NOT NULL COMMENT '订单号',
  `ukey` varchar(255) NOT NULL COMMENT 'ukey',
  `openid` varchar(255) NOT NULL COMMENT 'openid',
  `uid` int(11) NOT NULL COMMENT '会员ID',
  `pay_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '支付类型：1 在线支付 2 货到付款',
  `pay_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '支付状态：1 未付款 2 已付款',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1 未处理 2 已处理',
  `pay_money` decimal(10,2) DEFAULT 0.00 COMMENT '支付总价',
  `receipt_id` int(11) NOT NULL DEFAULT '0' COMMENT '收货信息ID',
  `remark` text DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=100000 DEFAULT CHARSET=utf8;
CREATE TABLE `tablepre_order_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ukey` varchar(255) NOT NULL COMMENT 'ukey',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `order_sn` varchar(255) NOT NULL COMMENT '订单号',
  `title` varchar(100) NOT NULL COMMENT '商品名',
  `model_id` int(11) NOT NULL COMMENT '模型ID',
  `d_id` int(11) NOT NULL COMMENT '关联ID',
  `cover_id` int(11) NOT NULL COMMENT '图片',
  `num` int(11) NOT NULL COMMENT '购买数量',
  `price` decimal(10,2) DEFAULT 0.00 COMMENT '单价',
  `setting` text DEFAULT NULL COMMENT '商品信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `tablepre_order_receipt` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ukey` varchar(255) NOT NULL COMMENT 'ukey',
  `uid` int(11) NOT NULL COMMENT '会员ID',
  `openid` varchar(255) NOT NULL COMMENT 'openid',
  `name` varchar(100) NOT NULL COMMENT '收货人',
  `tel` varchar(11) NOT NULL COMMENT '手机号码',
  `address` text NOT NULL COMMENT '地址',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认',
  `zip_code` varchar(10) DEFAULT NULL COMMENT '邮编',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `tablepre_order_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ukey` varchar(255) NOT NULL COMMENT 'ukey',
  `openid` varchar(255) NOT NULL COMMENT 'openid',
  `uid` int(11) NOT NULL COMMENT '会员ID',
  `title` varchar(100) NOT NULL COMMENT '商品名',
  `model_id` int(11) NOT NULL COMMENT '模型ID',
  `d_id` int(11) NOT NULL COMMENT '关联ID',
  `cover_id` int(11) NOT NULL COMMENT '图片',
  `num` int(11) NOT NULL COMMENT '购买数量',
  `price` decimal(10,2) DEFAULT 0.00 COMMENT '单价',
  `setting` text DEFAULT NULL COMMENT '商品信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `tablepre_order_pay_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ukey` varchar(255) NOT NULL COMMENT 'ukey',
  `openid` varchar(255) NOT NULL COMMENT 'openid',
  `uid` int(11) NOT NULL COMMENT '会员ID',
  `order_sn` int(11) NOT NULL COMMENT '订单号',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;