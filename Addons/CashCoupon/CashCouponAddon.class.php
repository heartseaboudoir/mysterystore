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
        'version' => '0.1',
        'adminlist_url' => 'Addons/execute?_addons=Wallet&_controller=WalletAdmin&_action=index',
    );
    public $admin_list = array(
        'listKey' => array(
            'create_time_text' => '添加时间',
            'update_time_text' => '修改时间',
        ),
        'model' => 'Wallet',
        'order' => 'update_time desc',
        'map' => '',
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $install_sql = './Addons/Wallet/install.sql';
        M('Hooks')->add(array('name' => 'wallet_inc', 'type' => 2, 'description' => '钱包增加', 'update_time' => time(), 'addons' => 'Wallet'));
        M('Hooks')->add(array('name' => 'wallet_dec', 'type' => 2, 'description' => '钱包减少金额', 'update_time' => time(), 'addons' => 'Wallet'));
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        $install_sql = './Addons/Wallet/uninstall.sql';
        M('Hooks')->where(array('name' => array('in','wallet_dec,wallet_inc')))->delete();
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

    public function wallet_inc($param){
        $uid = $param['uid'];
        if(!$uid) return;
        $money = $param['money'];
        $title = isset($param['title']) ? $param['title'] : '';
        $action_sn = isset($param['sn']) ? $param['sn'] : '';
        $obj = isset($param['obj']) ? $param['obj'] : '';
        $setting = isset($param['setting']) ? $param['setting'] : array();
        $change_money = intval(isset($param['change_money']) ? $param['change_money'] : 0); // 是否更改用户总金额
        $sn = date('ymdHis').$uid.$change_money.  mt_rand(10, 99);      // 流水号
        $data = array(
            'sn' => $sn,
            'type' => 1,
            'action_title' => $title,
            'action_obj' => $obj,
            'data' => json_encode($setting),
            'uid' => $uid,
            'money' => $money,
            'action_sn' => $action_sn,
            'change_money' => $change_money,
        );
        $Model = D('Addons://Wallet/WalletLog');
        $Model->create($data);
        $Model->add();
        if($change_money){
            $where = array(
                'uid' => $uid
            );
            D('Addons://Wallet/Wallet')->check_wallet($uid);
            D('Addons://Wallet/Wallet')->where($where)->setInc('money', $money);
        }
        return true;
    }
    
    public function wallet_dec($param){
        $uid = $param['uid'];
        if(!$uid) return;
        $money = $param['money'];
        $title = isset($param['title']) ? $param['title'] : '';
        $action_sn = isset($param['sn']) ? $param['sn'] : '';
        $obj = isset($param['obj']) ? $param['obj'] : '';
        $setting = isset($param['setting']) ? $param['setting'] : array();
        $change_money = intval(isset($param['change_money']) ? $param['change_money'] : 0); // 是否更改用户总金额
        $sn = date('ymdHis').$uid.$change_money.  mt_rand(10, 99);
        $data = array(
            'type' => 2,
            'sn' => $sn,
            'action_title' => $title,
            'action_obj' => $obj,
            'action_sn' => $action_sn,
            'data' => json_encode($setting),
            'uid' => $uid,
            'money' => $money,
            'change_money' => $change_money,
        );
        $Model = D('Addons://Wallet/WalletLog');
        $Model->create($data);
        $Model->add();
        if($change_money){
            $where = array(
                'uid' => $uid
            );
            D('Addons://Wallet/Wallet')->check_wallet($uid);
            D('Addons://Wallet/Wallet')->where($where)->setDec('money', $money);
        }
        return true;
    }
}
