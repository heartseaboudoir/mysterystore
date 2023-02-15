<?php

namespace Addons\WechatPay;
use Common\Controller\Addon;

/**
 * 微信支付插件
 */

    class WechatPayAddon extends Addon{

        public $info = array(
            'name'=>'WechatPay',
            'title'=>'微信支付',
            'description'=>'查看支付记录以及配置微信支付',
            'status'=>1,
            'author'=>'小马',
            'version'=>'0.1',
            'has_adminlist'=>1,
            'type'=>1         
        );
		
        public $admin_list = array(
            'listKey' => array(
                    'body'=>'描述',
                    'out_trade_no' => '订单号',
                    'openid'=>'openid',
                    'uname'=>'用户名',
                    'total_fee' => '总费用(分)',
                    'create_time' => '交易时间',
            ),
            'model'=>'WechatPayLog',
            'order'=>'update_time desc',
            'map' => array('pid' => 0),
        );
        
        public $custom_adminlist = 'adminlist.html';

	public function install() {
		$install_sql = './Addons/WechatPay/install.sql';
		if (file_exists ( $install_sql )) {
			execute_sql_file ( $install_sql );
		}
		return true;
	}
	public function uninstall() {
		$uninstall_sql = './Addons/WechatPay/uninstall.sql';
		if (file_exists ( $uninstall_sql )) {
			execute_sql_file ( $uninstall_sql );
		}
		return true;
	}

        //实现的weixin钩子方法
        public function app_begin($param){

        }

    }