<?php
namespace User\Api;
use User\Api\Api;

class WalletApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        $this->model = D('Wallet');
    }

    public function info($uid){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        $info = $this->model->get_info($uid);
        $bind = M('WalletBind')->where(array('uid' => $uid))->find();
        $info['is_bind'] = 0;
        if($bind){
            $bind_data = json_decode($bind['bind_data'], true);
            $info['is_bind'] = 1;
            $info['avatar'] = !empty($bind_data['avatar']) ? $bind_data['avatar'] : '';
            $info['nick_name'] = !empty($bind_data['nick_name']) ? $bind_data['nick_name'] : '';
            $info['real_name'] = !empty($bind_data['real_name']) ? $bind_data['real_name'] : '';
            $info['mobile'] = !empty($bind_data['mobile']) ? $bind_data['mobile'] : '';
            $info['email'] = !empty($bind_data['email']) ? $bind_data['email'] : '';
        }
        return $info;
    }
    
    public function bind_alipay($uid, $bind_id, $bind_data = array()){
        return $this->model->bind_account($uid, $bind_id, $bind_data, 1);
    }
    
    public function withdraw_apply($uid, $money){
        $uid = intval($uid);
        $money = round($money, 2);
        if($uid < 1){
            return array('status' => 0, 'msg' => '用户不存在');
        }
        if($money <= 0){
            return array('status' => 0, 'msg' => '金额必须大于0');
        }
        $result = $this->model->withdraw_apply($uid, $money);
        if($result == 1){
            return array('status' => 1);
        }else{
            return array('status' => 0, 'msg' => $this->model->getError());
        }
    }
    
    public function inc_money($uid, $money, $sn, $action){
        $uid = intval($uid);
        $money = round($money, 2);
        if($uid < 1){
            return array('status' => 0, 'msg' => '用户不存在');
        }
        if($money <= 0){
            return array('status' => 0, 'msg' => '金额必须大于0');
        }
        $result = $this->model->inc_money($uid, $money, $sn, $action);
        if($result){
            return array('status' => 1);
        }else{
            $error = $this->model->getError();
            return array('status' => 0, 'msg' => $error);
        }
    }
    
    public function log($uid, $page, $row, $type, $act, $start_time, $end_time, $order){
        is_null($page) && $page = 1;
        $page = intval($page);
        $page < 1 && $page = 1;
        is_null($row) && $row = 20;
        $row = intval($row);
        $row < 0 && $row = 0;
        is_null($type) && $type = 0;
        $type = intval($type);
        $start_time = $start_time ? strtotime($start_time) : 0;
        $end_time = $end_time ? strtotime($end_time) : 0;
        is_null($order) && $order = 'id desc';
        $Model = M('WalletLog');
        $where = array();
        $where['uid'] = $uid;
        $type > 0 && $where['type'] = $type;
        if($act){
            !is_array($act) && $act = explode(',', $act);
            $where['action'] = count($act) > 1 ? array('in', $act) : $act[0];
        }
        if($start_time > 0){
            $where['create_time'] = array('egt', $start_time);
        }elseif($end_time > 0){
            $where['create_time'] = array('elt', $start_time);
        }
        if($start_time > 0 && $end_time > 0){
            $where['create_time'] = array('between', array($start_time, $end_time));
        }
        $field = 'id,type,action,action_sn,money,is_lock,unlock_time,create_time';
        $list = $Model->where($where)->page($page, $row)->order($order)->field($field)->select();
        !$list && $list = array();
        if($list){
            $type_data = $this->model->get_type_data();
            foreach($list as $k => $v){
                $v['action_title'] = isset($type_data[$v['action']]) ? $type_data[$v['action']] : '';
                $list[$k] = $v;
            }
        }
        $total = $Model->where($where)->count();
        return array('data' => $list, 'page' => $page, 'row' => $row, 'total' => $total);
    }
}
