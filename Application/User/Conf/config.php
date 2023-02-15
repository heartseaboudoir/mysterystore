<?php
/**
 * UCenter客户端配置文件
 * 注意：该配置文件请使用常量方式定义
 */

!defined('UC_APP_ID') && define('UC_APP_ID', 1); //应用ID
!defined('UC_API_TYPE') && define('UC_API_TYPE', 'Model'); //可选值 Model / Service
!defined('UC_AUTH_KEY') && define('UC_AUTH_KEY', 'chaoshipos/JcYcWqj3zbFO5~`giMeI,H*@@9+Ur>_dTl".wYb$K'); //加密KEY
//!defined('UC_DB_DSN') && define('UC_DB_DSN', 'mysql://zhaike:ZHaiKe888@rm-uf63bdc922g460p2r.mysql.rds.aliyuncs.com:3306/shenmi'); // 数据库连接，使用Model方式调用API必须配置此项
!defined('UC_DB_DSN') && define('UC_DB_DSN', 'mysql://root:wdm803087@localhost:3306/zk'); // 数据库连接，使用Model方式调用API必须配置此项
!defined('UC_TABLE_PREFIX') && define('UC_TABLE_PREFIX', 'hii_'); // 数据表前缀，使用Model方式调用API必须配置此项

!defined('API_SERVER') && define('API_SERVER', 'http://zk.wendingming.com/User/Api/api');