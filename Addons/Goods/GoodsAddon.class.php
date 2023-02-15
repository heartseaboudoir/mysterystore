<?php

namespace Addons\Goods;

use Common\Controller\Addon;

class GoodsAddon extends Addon {

    public $info = array(
        'name' => 'Goods',
        'title' => '产品管理',
        'description' => '用于产品管理',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1',
        'adminlist_url' => 'Addons/execute?_addons=Goods&_controller=Goods&_action=index',
    );
    public $admin_list = array(
        'listKey' => array(
            'title' => '标题',
            'create_time_text' => '添加时间',
            'create_time_text' => '更新时间',
        ),
        'model' => 'Goods',
        'order' => 'update_time desc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $install_sql = './Addons/Goods/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/Goods/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
