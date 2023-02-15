<?php

namespace Addons\WechatKeyword;

use Common\Controller\Addon;

class WechatKeywordAddon extends Addon {

    public $custom_config = 'config.html';
    public $info = array(
        'name' => 'WechatKeyword',
        'title' => '关键词回复',
        'description' => '用于关键词回复管理',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1',
        'adminlist_url' => 'Addons/execute?_addons=WechatKeyword&_controller=WechatKeyword&_action=index',
    );
    public $admin_list = array(
        'listKey' => array(
            'keyword' => '关键词',
            'create_time_text' => '添加时间',
            'update_time_text' => '修改时间',
        ),
        'model' => 'WechatKeyword',
        'order' => 'update_time desc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $install_sql = './Addons/WechatKeyword/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/WechatKeyword/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
