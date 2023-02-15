<?php
/**
 * 系统配文件
 * 所有系统级别的配置
 */
return array(
    
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => array('Addons' => ONETHINK_ADDON_PATH), //扩展模块列表
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common'),
    'MODULE_ALLOW_LIST'  => array('Admin', 'Api', 'Apiv2', 'Internal', 'Home', 'Wap', 'User', 'Erp'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'chaoshipos/JcYcWqj3zbFO5~`giMeI,H*@@9+Ur>_dTl".wYb$K', //默认数据加密KEY

    /* 调试配置 */
    'SHOW_PAGE_TRACE' => false,

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => false, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 2, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符
    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 数据库配置 */
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => 'localhost',//'rm-uf63bdc922g460p2r.mysql.rds.aliyuncs.com', // 服务器地址
    'DB_NAME'   => 'zk', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'wdm803087',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'hii_', // 数据库表前缀
	
    'UC_DB_CONFIG' => array(
        'db_type'  => 'mysql',
        'db_user'  => 'root',
        'db_pwd'   => 'wdm803087',
        'db_host'  => 'localhost',//'rm-uf63bdc922g460p2r.mysql.rds.aliyuncs.com',
        'db_port'  => '3306', 
        'db_name'  => 'zk'
    ),
    'UC_DB_TABLE_PREFIX' => 'hii_', // Ucenter数据库前缀

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DOCUMENT_MODEL_TYPE' => array(2 => '主题', 1 => '目录', 3 => '段落'),

    /* 微信用户图片 */
    'WECHAT_USER_PIC' => './u/User/', //保存根路径

    /* 字体文件路径 */
    'FONT_PATH' => './resources/fonts/', //保存根路径
);
