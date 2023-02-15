<?php

namespace Addons\Shop;

use Common\Controller\Addon;

class ShopAddon extends Addon {

    public $info = array(
        'name' => 'Shop',
        'title' => '店铺管理',
        'description' => '',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1',
        'adminlist_url' => 'Addons/execute?_addons=Shop&_controller=Shop&_action=index',
    );
    public $admin_list = array(
        'listKey' => array(
            'title' => '标题',
            'create_time_text' => '添加时间',
            'create_time_text' => '更新时间',
        ),
        'model' => 'Shop',
        'order' => 'update_time desc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $install_sql = './Addons/Shop/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/Shop/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
