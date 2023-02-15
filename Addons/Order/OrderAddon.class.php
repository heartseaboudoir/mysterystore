<?php

namespace Addons\Order;

use Common\Controller\Addon;

class OrderAddon extends Addon {

    public $custom_config = 'config.html';
    public $info = array(
        'name' => 'Order',
        'title' => '订单管理',
        'description' => '用于订单管理',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1'
    );
    public $admin_list = array(
        'listKey' => array(
            'uid' => '会员',
            'type_text' => '订单类型',
            'status_text' => '状态',
            'create_time_text' => '添加时间',
            'update_time_text' => '更新时间',
        ),
        'model' => 'Order',
        'order' => 'update_time desc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $install_sql = './Addons/Order/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/Order/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
