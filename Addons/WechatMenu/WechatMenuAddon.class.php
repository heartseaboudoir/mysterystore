<?php

namespace Addons\WechatMenu;
use Common\Controller\Addon;

/**
 * 自定义菜单插件
 */

    class WechatMenuAddon extends Addon{

        public $info = array(
            'name'=>'WechatMenu',
            'title'=>'自定义菜单',
            'description'=>'自定义菜单能够帮助公众号丰富界面，让用户更好更快地理解公众号的功能',
            'status'=>1,
            'author'=>'小马',
            'version'=>'0.1',
            'has_adminlist'=>1,
            'adminlist_url' => 'Addons/execute?_addons=WechatMenu&_controller=WechatMenu&_action=index',
            'type'=>1         
        );
		
        public $admin_list = array(
            'listKey' => array(
                    'title'=>'标题',
                    'keyword' => '关键字',
                    'url'=>'url',
            ),
            'model'=>'WechatMenu',
            'order'=>'update_time desc',
            'map' => array('pid' => 0),
        );
        
        public $custom_adminlist = 'adminlist.html';

	public function install() {
		$install_sql = './Addons/WechatMenu/install.sql';
		if (file_exists ( $install_sql )) {
			execute_sql_file ( $install_sql );
		}
		return true;
	}
	public function uninstall() {
		$uninstall_sql = './Addons/WechatMenu/uninstall.sql';
		if (file_exists ( $uninstall_sql )) {
			execute_sql_file ( $uninstall_sql );
		}
		return true;
	}

        //实现的weixin钩子方法
        public function WechatIndexLogin($param){

        }

    }