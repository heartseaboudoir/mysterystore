CREATE TABLE IF NOT EXISTS `tablepre_wechat_menu` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`url`  varchar(255)   NULL  COMMENT '关联URL',
`keyword`  varchar(100) NULL  COMMENT '关联关键词',
`title`  varchar(50) NOT NULL  COMMENT '菜单名',
`pid`  tinyint(2) NULL  DEFAULT 0 COMMENT '一级菜单',
`sort`  tinyint(4)  NULL  DEFAULT 0 COMMENT '排序号',
`token`  varchar(255) NOT NULL  COMMENT 'Token',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci CHECKSUM=0 ROW_FORMAT=DYNAMIC DELAY_KEY_WRITE=0;