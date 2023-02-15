CREATE TABLE IF NOT EXISTS `tablepre_wechat_pay_log` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`data`  text NULL  DEFAULT '' COMMENT '',
`create_time` int(11) default null COMMENT '交易时间',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci CHECKSUM=0 ROW_FORMAT=DYNAMIC DELAY_KEY_WRITE=0;
CREATE TABLE IF NOT EXISTS `tablepre_wechat_pay_config` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`name`  varchar(128) NULL  COMMENT '商品描述',
`value`  varchar(128) NULL  COMMENT '附加数据',
`update_time` int(11) default null  COMMENT '更新时间',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci CHECKSUM=0 ROW_FORMAT=DYNAMIC DELAY_KEY_WRITE=0;