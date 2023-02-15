<?php

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */
return array(
    /* 项目相关配置 */
    'APP_TITLE' => '神秘商店',
	
    /* 数据缓存设置 */
    'DATA_CACHE_PREFIX'    => 'hiithink_', // 缓存前缀
    'DATA_CACHE_TYPE'      => 'File', // 数据缓存类型



    /* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'hiithink_admin', //session前缀
    'COOKIE_PREFIX'  => 'hiithink_admin_', // Cookie前缀 避免冲突
    'VAR_SESSION_ID' => 'session_id',	//修复uploadify插件无法传递session_id的bug

    'URL_ROUTER_ON'   => true, 
    'URL_ROUTE_RULES'=>array(
        'wx/ad/:_addons/:_controller/:_action' => 'wechat/addons/execute',
    ),
    
);
