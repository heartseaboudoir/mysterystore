CREATE TABLE `tablepre_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) NOT NULL COMMENT '产品名',
  `sell_price` decimal(10,2) DEFAULT 0.00 COMMENT '售价',
  `num` int(10) DEFAULT 0 COMMENT '数量',
  `sizes` text NOT NULL COMMENT '尺寸',
  `cate_id` int(10) DEFAULT NULL COMMENT '分类',
  `cover_id` int(10) DEFAULT NULL COMMENT '图片',
  `listorder` int(10) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态： 1 启用 2 禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `tablepre_goods_cate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) NOT NULL COMMENT '分类名',
  `listorder` int(10) DEFAULT 0 COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;