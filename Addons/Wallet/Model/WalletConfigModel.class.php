<?php
namespace Addons\Wallet\Model;
use Admin\Model\UcModel;

class WalletConfigModel extends UcModel{

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('update_time', NOW_TIME, self::MODEL_BOTH),
    );
    
    public function get_type_data(){
        $_type = array();
        $type_data = M('WalletConfig')->where(array('name' => 'type_data'))->find();
        if($type_data){
            $type_data = explode("\r\n", $type_data['data']);
            foreach($type_data as $v){
                $v = trim($v);
                if($v){
                    $item = explode(':', $v);
                    $_type[$item[0]] = $item[1];
                }
            }
        }
        return $_type;
    }
    
    
    public function u_wallet_config(){
        $key = 'WALLET_CONFIG';
        $list = M('WalletConfig')->select();
        foreach($list as $v){
            $data[$v['name']] = $v['data'];
        }
        S($key, $data);
        return true;
    }
}
