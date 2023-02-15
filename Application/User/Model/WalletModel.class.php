<?php
namespace User\Model;
use Think\Model;

class WalletModel extends Model{

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
    private $sn_pre = array('withdraw' => 'w');
    public function get_info($uid = 0){
        if(!$uid){
            return false;
        }
        $info = $this->where(array('uid' => $uid))->find();
        if(!$info){
            $info = array(
                'uid' => $uid
            );
            if(!$info = $this->create($info)){
                return false;
            }
            $info['uid'] = $uid;
            if(!$this->add($info)){
                return 0;
            }
        }
        if($info['status'] != 1){
            return -1;
        }
        return $info;
    }
    
    public function get_type_data(){
        $_type = array();
        $type_data = $this->wallet_config('type_data');
        if($type_data){
            $type_data = explode("\r\n", $type_data);
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
    /**
     * 增加金额
     * @param type $uid 用户
     * @param type $money 金额
     * @param string $sn  对应操作操作编号
     * @param type $action  操作类型
     */
    public function inc_money($uid, $money, $sn, $action){
        if(!$sn){
            $this->error = '操作编号不存在';
            return false;
        }
        $type_data = $this->get_type_data();
        if(!isset($type_data[$action])){
            $this->error = '操作类型错误';
            return false;
        }
        if($money <= 0 ){
            $this->error = '金额必须大于0';
            return false;
        }
        $uid = intval($uid);
        if($uid < 0 || !$this->get_info($uid)){
            $this->error = '用户不存在';
            return false;
        }
        $lock_day = intval($this->wallet_config('lock_day'));
        $is_lock = 0;
        $unlock_time = 0;
        if($lock_day > 1){
            $is_lock = 1;
            $unlock_time = NOW_TIME + 3600*24*$lock_day;
        }
        $data = array(
            'uid' => $uid,
            'money' => $money,
            'type' => 1,
            'action' => $action,
            'action_sn' => $sn,
            'is_lock' => $is_lock,
            'unlock_time' => $unlock_time,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        if(M('WalletLog')->where(array('action' => $action, 'action_sn' => $sn, 'type' => 1))->find()){
            $this->error = '该操作已经执行过';
            return false;
        }
        if(M('WalletLog')->add($data)){
            if(in_array($action, array('withdraw_return'))){
                $this->return_frozen($sn, $uid);
            }else{
                $set_data = array(
                    'all_money' => array('exp', 'all_money+'.$money),
                    'update_time' => NOW_TIME
                );
                if($is_lock == 1){
                    $set_data['lock_money'] = array('exp', 'lock_money+'.$money);
                }else{
                    $set_data['money'] = array('exp', 'money+'.$money);
                }
                $this->where(array('uid' => $uid))->save($set_data);
            }
            return true;
        }else{
            $this->error = '操作失败';
            return false;
        }
    }
    /**
     * 支出
     * @param type $uid                 用户
     * @param type $money               减少的金额
     * @param string $sn                对应操作操作编号
     * @param type $action  操作类型
     */
    public function dec_money($uid, $money, $sn,  $action){
        if(!$sn){
            $this->error = '操作编号不存在';
            return false;
        }
        $money = round($money, 2);
        if($money <= 0 ){
            $this->error = '金额必须大于0';
            return false;
        }
        $uid = intval($uid);
        if($uid < 0){
            $this->error = '用户不存在';
            return false;
        }
        $info = $this->get_info($uid);
        if(!$info){
            $this->error = '用户不存在';
            return false;
        }
        if($info['money'] < $money){
            $this->error = '钱包余额不足';
            return false;
        }
        $data = array(
            'uid' => $uid,
            'money' => $money,
            'type' => 2,
            'action' => $action,
            'action_sn' => $sn,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME
        );
        $LogModel = M('WalletLog');
        if($LogModel->where(array('action_sn' => $sn, 'action' => $action, 'type' => 2))->find()){
            $this->error = '该操作已经执行过';
            return false;
        }
        if($LogModel->add($data)){
            // 如果是提交申请，则要做冻结操作
            if(in_array($action, array('withdraw'))){
                $res = $this->add_frozen($uid, $money, $action, $sn);
                
                if (empty($res)) {
                    $this->error = '操作失败-001';
                    return false;
                }
            }else{
                // 直接减去
                $set_data = array();
                $set_data['money'] = array('exp', 'money-'.$money);
                $set_data['all_money'] = array('exp', 'all_money-'.$money);
                $set_data['update_time'] = NOW_TIME;
                $this->where(array('uid' => $uid))->save($set_data);
            }
            return true;
        }else{
            $this->error = '操作失败';
            return false;
        }
    }
    /**
     * 冻结金额
     * @param type $uid    
     * @param type $money
     * @param type $action
     * @param type $action_sn
     * @return boolean
     */
    public function add_frozen($uid, $money, $action, $action_sn){
        $uid = intval($uid);
        $info = $this->get_info($uid);
        if(!$info){
            $this->error = '用户不存在';
            return false;
        }
        if($info['money'] < $money){
            $this->error = '账户余额不足';
            return false;
        }
        $money = round($money, 2);
        if($money <= 0){
            return false;
        }
        $Model = M('WalletFrozenLog');
        if($Model->where(array('uid' => $uid, 'action' => $action, 'action_sn' => $action_sn))->find()){
            return false;
        }
        $data = array(
            'uid' => $uid,
            'money' => $money,
            'action' => $action,
            'action_sn' => $action_sn,
            'status' => 1,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME
        );
        $log_id = $Model->add($data);
        if($log_id){
            $b_data = array(
                'money' => array('exp', 'money-'.$money), 
                'all_money' => array('exp', 'all_money-'.$money), 
                'frozen_money' => array('exp', 'frozen_money+'.$money), 
                'update_time' => NOW_TIME
            );
            //$result = $this->where(array('uid' => $uid, 'money' => array('gt', $money)))->save($b_data);
            $result = $this->where(array('uid' => $uid))->save($b_data);
            if($result){
                return $log_id;
            }else{
                $Model->delete($log_id);
                return false;
            }
        }
        return false;
    }
    /**
     * 处理冻结金额，返还操作
     * @param type $id
     * @return boolean
     */
    public function return_frozen($id, $uid = 0){
        $Model = M('WalletFrozenLog');
        $where = array();
        if(is_numeric($id)){
            $where['id'] = $id;
        }else{
            $where['sn'] = $id;
        }
        $uid > 0 && $where['uid'] = $uid;
        $where['status'] = 1;
        $info = $Model->where($where)->find();
        if(!$info){
            return false;  
        }
        if($Model->where($where)->save(array('status' => 3, 'update_time' => NOW_TIME))){
            $u_data = array(
                'frozen_money' => array('exp', 'frozen_money-'.$info['money']),
                'money' => array('exp', 'money+'.$info['money']),
                'all_money' => array('exp', 'all_money+'.$info['money']),
            );
            $this->where(array('uid' => $info['uid']))->save($u_data);
            return true;
        }
        return false;
    }
    /**
     * 清空冻结金额
     * @param type $id
     * @return boolean
     */
    public function clear_frozen($id, $uid = 0){
        $Model = M('WalletFrozenLog');
        $where = array();
        if(is_numeric($id)){
            $where['id'] = $id;
        }else{
            $where['sn'] = $id;
        }
        $uid > 0 && $where['uid'] = $uid;
        $where['status'] = 1;
        $info = $Model->where($where)->find();
        if(!$info){
            return false;
        }
        if($Model->where($where)->save(array('status' => 2, 'update_time' => NOW_TIME))){
            $this->where(array('uid' => $info['uid']))->save(array('frozen_money' => array('exp', 'frozen_money-'.$info['money'])));
        }
    }
    
    public function bind_account($uid, $bind_id, $bind_data = array(), $type = 1){
        $Model = M('WalletBind');
        if($Model->where(array('uid' => $uid, 'type' => $type))->find()){
            return -1;
        }
        $data = array(
            'uid' => $uid,
            'type' => $type,
            'bind_id' => $bind_id,
            'bind_data' => json_encode($bind_data),
            'create_time' => NOW_TIME
        );
        if($Model->create($data) && $Model->add()){
            $data['dp_uid'] = $uid;
            $data['act'] = 1;
            M('WalletBindLog')->add($data);
            return 1;
        }else{
            return 0;
        }
    }
    
    private function _get_sn($type = ''){
        $id = M('WalletSnId')->add(array('create_time' => NOW_TIME));
        $id_str = sprintf('%010s', $id);
        $pre = ($type && isset($this->sn_pre[$type])) ? $this->sn_pre[$type] : '';
        $sn = $pre.date('ymd').$id_str;
        return $sn;
    }
    protected function wallet_config($name){
        $key = 'WALLET_CONFIG';
        $data = S($key);
        if(!$data){
            $list = M('WalletConfig')->select();
            foreach($list as $v){
                $data[$v['name']] = $v['data'];
            }
            S($key, $data);
        }
        return isset($data[$name]) ? $data[$name] : '';
    }
    public function withdraw_apply($uid, $money){
        $info = $this->get_info($uid);
        $alipay = M('WalletBind')->where(array('uid' => $uid, 'type' => 1))->find();
        if(!$alipay){
            $this->error = '还未绑定支付宝账号';
            return -1;
        }
        $max_money = $this->wallet_config('user_withdraw_max_money');
        $max_money = round($max_money, 2);
        if($max_money > 0 && $money > $max_money){
            $this->error = '每次提现的金额不得大于￥'.$max_money;
            return 0;
        }
        $min_money = $this->wallet_config('user_withdraw_min_money');
        $min_money = round($min_money, 2);
        if($money < $min_money){
            $this->error = '每次提现的金额不得小于￥'.$min_money;
            return 0;
        }
        $max_times = $this->wallet_config('user_withdraw_max_times');
        $max_times = intval($max_times);
        $max_times <= 0 && $max_times = 10;
        $WithdrawModel = M('WalletWithdrawLog');
        $date = date('Y-m-d');
        $max_day_money = $this->wallet_config('user_withdraw_max_money_day');
        $max_day_money = intval($max_day_money);
        if($max_day_money > 0 && $info['day_money']+$money > $max_day_money){
            $last_money = $max_day_money-$info['day_money'];
            $this->error = $last_money > 0 ? ('你今天的提现额度只剩￥'.$last_money) : '你今天的提现额度已经用光了';
            return 0;
        }
        if($WithdrawModel->where(array('uid' => $uid, 'date' => $date))->count() >= $max_times){
            $this->error = '今日的提现次数已经用光了';
            return 0;
        }
        $sn = $this->_get_sn('withdraw');
        $frozen_result = $this->dec_money($uid, $money, $sn, 'withdraw');
        if(!$frozen_result){
            return 0;
        }
        $bank_data = array(
            'bind_id' => $alipay['bind_id'],
        );
        $withdraw_fee = round($this->wallet_config('withdraw_fee'), 2);
        if($withdraw_fee < 0 || $withdraw_fee >= 100){
            $this->error = '提现功能维护中，请过段时间再尝试。(-1000)';
            return 0;
        }
        if($withdraw_fee > 0){
            $real_money = round($money*(100-$withdraw_fee)/100, 2);
            $real_min_money = 0.1;
            if($real_money <= $real_min_money){
                $this->error = '换算后的实际提现金额为 '.$real_money.'，因小于最低金额'.$real_min_money.' 无法提现。';
                return 0;
            }
        }else{
            $real_money = $money;
        }
        $data = array(
            'sn' => $sn,
            'uid' => $uid,
            'money' => $money,
            'real_money' => $real_money,
            'status' => 1,
            'bank_data' => json_encode($bank_data),
            'date' => $date,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        if(!$WithdrawModel->create($data)){
            return 0;
        }
        $result = $WithdrawModel->add();
        if($result){
            $Api = new \Addons\Alipay\Lib\Trans\Api();
            $payer_show_name = $this->wallet_config('payer_show_name');
            $req_result = $Api->toaccount($real_money, 1, $bank_data['bind_id'], '钱包提现', '', $payer_show_name);
            $u_data = array(
                'out_biz_no' => isset($req_result['data']['out_biz_no']) ? $req_result['data']['out_biz_no'] : '',
                'update_time' => NOW_TIME,
            );
            if($req_result['status'] == 1 || $req_result['status'] == 2){
                $this->where(array('uid' => $uid))->setInc('day_withdraw', $money);
                $u_data['status'] = $req_result['status'] == 1 ? 2 : 4;
                $this->clear_frozen($sn, $uid);
                $status = 1;
            }else{
                $u_data['status'] = 3;
                $this->error = $req_result['msg'];
                $status = 0;
                $this->inc_money($uid, $money, $sn, 'withdraw_return');
            }
            $WithdrawModel->where(array('id' => $result))->save($u_data);
            return $status;
        }else{
            return 0;
        }
    }
}
