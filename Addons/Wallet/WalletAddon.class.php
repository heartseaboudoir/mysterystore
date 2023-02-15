<?php

namespace Addons\Wallet;

use Common\Controller\Addon;

class WalletAddon extends Addon {

    public $custom_config = 'config.html';
    public $info = array(
        'name' => 'Wallet',
        'title' => '钱包管理',
        'description' => '用于钱包管理',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1'
    );
    public $admin_list = array(
        'listKey' => array(
            'uid' => '用户ID',
            'create_time_text' => '添加时间',
            'update_time_text' => '更新时间',
        ),
        'model' => 'Wallet',
        'order' => 'update_time desc'
    );

    public function install() {
        $install_sql = './Addons/Wallet/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/Wallet/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
