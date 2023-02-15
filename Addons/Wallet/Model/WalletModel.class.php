<?php
namespace Addons\Wallet\Model;
use Admin\Model\UcModel;

class WalletModel extends UcModel{

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('money', 0.00, self::MODEL_INSERT),
        array('frozen_money', 0.00, self::MODEL_INSERT),
        array('lock_money', 0.00, self::MODEL_INSERT),
        array('all_money', 0.00, self::MODEL_INSERT),
        array('income_money', 0.00, self::MODEL_INSERT),
        array('status', 1, self::MODEL_INSERT),
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
    );
    /**
     * 处理冻结金额，返还操作(提现失败)
     * @param type $sn
     * @return boolean
     */
    public function return_frozen($sn){
        $Model = M('WalletFrozenLog');
        $info = $Model->where(array('action_sn' => $sn, 'status' => 1))->find();
        if(!$info){
            return false;  
        }
        if($Model->where(array('id' => $info['id']))->save(array('status' => 2, 'update_time' => NOW_TIME))){
            $u_data = array(
                'frozen_money' => array('exp', 'frozen_money-'.$info['money']),
                'money' => array('exp', 'money+'.$info['money']),
                'all_money' => array('exp', 'all_money+'.$info['money']),
            );
            $this->where(array('uid' => $info['uid']))->save($u_data);
        }
    }
    /**
     * 清空冻结金额(成功提现)
     * @param type $sn
     * @return boolean
     */
    public function clear_frozen($sn){
        $Model = M('WalletFrozenLog');
        $info = $Model->where(array('action_sn' => $sn, 'status' => 1))->find();
        if(!$info){
            return false;
        }
        if($Model->where(array('id' => $info['id']))->save(array('status' => 2, 'update_time' => NOW_TIME))){
            $this->where(array('uid' => $info['uid']))->save(array('frozen_money' => array('exp', 'frozen_money-'.$info['money'])));
        }
    }
    
}
