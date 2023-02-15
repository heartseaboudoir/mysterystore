<?php
namespace User\Api;
use User\Api\Api;

class CashCouponApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        $this->model = D('CashCoupon');
    }
    
    public function info($code){
        if(!$code) return false;
        return D('CashCoupon')->where(array('code' => $code))->find();
    }
    
    public function user_coupon_list($uid, $type = 0, $page = 1, $row = 20, $order_money = 0){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        $page = intval($page);
        $page < 1 && $page = 1;
        $row = intval($row);
        $row < 0 && $row = 0;
        return D('CashCouponUser')->get_lists($uid, $type, $page, $row, $order_money);
    }
    
    public function coupon_list($p_code, $where, $order, $page, $row){
        $page = intval($page);
        is_null($page) && $page = 1;
        $page < 1 && $page = 1;
        is_null($row) && $row = 20;
        $row = intval($row);
        $row < 0 && $row = 0;
        is_null($where) && $where = array();
        !is_array($where) && $where = array();
        $where['p_code'] = $p_code;
        is_null($order) && $order = 'create_time desc';
        $lists = D('CashCouponUser')->where($where)->page($page, $row)->order($order)->select();
        !$lists && $lists = array();
        $total = D('CashCouponUser')->where($where)->count();
        $count = count($lists);
        return array('data' => $lists, 'total' => $total, 'page' => $page, 'row' => $row, 'count' => $count);
    }
    
    public function coupon_info($code, $uid, $money){
        is_null($money) && $money = 0;
        $uid = intval($uid);
        return D('CashCouponUser')->get_info($code, $uid, $money);
    }
    
    public function coupon_info_by_where($where){
        is_null($where) && $where = array();
        return D('CashCouponUser')->where($where)->find();
    }
    
    public function make_info($code){
        if(!$code){
            return false;
        }
        return M('CashCouponMake')->where(array('code' => $code))->find();
    }
    
    public function get_lottery($uid, $order_sn){
        return $this->model->get_lottery($uid, $order_sn);
    }
    
    public function check_lottery($code, $type, $key){
        is_null($uid) && $uid = 0;
        return $this->model->check_lottery($code, $type, $key, $uid);
    }
    
    public function get_lottery_money($code, $type, $key){
        return $this->model->get_lottery_money($code, $type, $key);
    }
    
    public function config_info($name, $get_new){
        is_null($get_new) && $get_new = false;
        return D('CashCouponConfig')->get_info($name, $get_new);
    }
    
    public function get_cash_coupon($code, $uid, $type){
        $uid = intval($uid);
        is_null($type) && $type = 'code';
        return $this->model->get_cash_coupon($code, $uid, $type);
    }
    
    public function make_lottery_coupon($uid, $order_sn, $order_money){
        $uid = intval($uid);
        is_null($order_money) && $order_money = 0;
        return $this->model->make_lottery_coupon($uid, $order_sn, $order_money);
    }
    
    public function to_use_cash($uid, $code, $money){
        $uid = intval($uid);
        is_null($money) && $money = 0;
        return $this->model->to_use_cash($uid, $code, $money);
    }
    
    public function lottery_cash_coupon($code, $uid, $type, $key, $user_data = array()){
        is_null($user_data) && $user_data = array();
        return $this->model->lottery_cash_coupon($code, $uid, $type, $key);
    }
    
    public function user_coupon_by_code($code, $field){
        is_null($field) && $field = '*';
        if(!$code){
            return array();
        }
        $where = array();
        $where['code'] = array('in', $code);
        return M('CashCouponUser')->where($where)->field($field)->select();
    }
}
