<?php

namespace Addons\MessageNotice;

use Common\Controller\Addon;

class MessageNoticeAddon extends Addon {

    public $info = array(
        'name' => 'MessageNotice',
        'title' => '消息通知',
        'description' => '',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1',
        'adminlist_url' => 'Addons/execute?_addons=MessageNotice&_controller=MessageNotice&_action=index',
    );
    public $admin_list = array(
        'listKey' => array(
            'title' => '标题',
            'create_time_text' => '添加时间',
            'create_time_text' => '更新时间',
        ),
        'model' => 'MessageNotice',
        'order' => 'update_time desc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $install_sql = './Addons/MessageNotice/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/MessageNotice/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
