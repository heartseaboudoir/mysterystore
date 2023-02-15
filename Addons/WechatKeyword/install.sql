CREATE TABLE `tablepre_wechat_keyword` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `keyword` varchar(255) NOT NULL COMMENT '关键词',
  `content` text COMMENT '回复信息',
  `cover_id` int(11) NULL DEFAULT 0 COMMENT '图片ID',
  `ukey` varchar(255) NOT NULL COMMENT 'ukey',
  `listorder` mediumint(4) NULL DEFAULT 0 COMMENT '排序号',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;