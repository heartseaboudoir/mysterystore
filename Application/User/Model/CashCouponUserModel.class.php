<?php

namespace User\Model;
use Common\Model\BaseAddonsModel;

class CashCouponUserModel extends BaseAddonsModel{
    
        protected $_validate = array(
        );
	protected $_auto = array(
		array('code', 'get_code', self::MODEL_INSERT, 'callback'),
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
		array('receive_ip', 'get_client_ip', self::MODEL_INSERT, 'function'),
	);
        
        protected function get_code($lv = 0){
            $lv = intval($lv);
            $code = substr(md5('CashCouponUser'.mt_rand(10000, 99999).$lv), 10, 10);
            if($this->where(array('code' => $code))->find()){
                $code = $this->get_code($lv+1);
            }
            return $code;
        }
        
        protected function _after_find(&$result, $options) {
            parent::_after_find($result, $options);
            if(isset($result['last_time']) && isset($result['status']) && isset($result['code'])){
                if($result['status'] == 1 && $result['last_time'] > 0 && $result['last_time'] - time() <= 0){
                    $this->where(array('code' => $result['code']))->save(array('status' => 3));
                    $result['status'] = 3;
                }
            }
        }
        
	protected function _after_select(&$result,$options){
            foreach($result as &$record){
                $this->_after_find($record,$options);
            }
	}
        
        public function get_info($code, $uid, $money = 0){
            $info = $this->where(array('code' => $code, 'uid' => $uid ))->find();
            if(!$info) return false;
            if($info['status'] == 1 && $info['last_time'] > 0 && $info['last_time'] < NOW_TIME){
                $this->where(array('uid' => $uid, 'code' => $code, 'status' => 1))->save(array('status' => 3));
                $info['status'] = 3;
            }
            if($money > 0){
                $cash_money = 0;
                switch($info['type']){
                    case 1:
                        if($info['min_use_money'] > 0 && $money < $info['min_use_money']){
                            return false;
                        }
                        $cash_money = $info['money'];
                        break;
                    case 2:
                        if($info['discount'] >= 0.1 && $info['discount'] <= 9.9){
                            $cash_money = $money - round($money*($info['discount'])/10, 2);
                            if($info['max_dis_money'] > 0 && $cash_money > $info['max_dis_money']){
                                $cash_money = $info['max_dis_money'];
                            }
                        }else{
                            return false;
                        }
                        break;
                }
                $cash_money = round($cash_money, 2);
                $info['cash_money'] = $cash_money;
            }
            return $info;
        }
        
        public function get_cash_money($code, $uid, $money = 0){
            if($money <= 0) return 0;
            $info = $this->get_info($code, $uid, $money);
            if(!$info) return 0;
            return $info['cash_money'];
        }
        
        public function get_lists($uid, $status, $page = 1, $row = 20, $order_money = 0){
            $where = array();
            $where['uid'] = $uid;
            switch($status){
                case 1:
                    $where['status'] = 1;
                    $where['last_time'] = array(array('eq', 0), array('gt', NOW_TIME), 'or');
                    break;
                case 2:
                    $where['status'] = 2;
                    break;
                case 3:
                    $where['_string'] = 'status = 3 or (status = 1 and last_time > 0 and last_time <'.NOW_TIME.')';
                    break;
                case 4:
                    break;
            }
            $this->set_out($uid);
            $data = $this->where($where)->order('money desc, last_time asc')->field('title, description, code, money, last_time,status,create_time, type, discount, min_use_money, max_dis_money')->page($page, $row)->select();
            !$data && $data = array();
            foreach($data as $k => $v){
                // 优惠金额
                $v['coupon_money'] = 0;
                $v['is_usable'] = 1;
                if($v['type'] == 1){
                    unset($v['discount'], $v['max_dis_count']);
                    $v['cash_money'] = $v['money'];
                    if($order_money > 0 && $v['min_use_money'] > $order_money){
                        $v['cash_money'] = 0;
                        $v['is_usable'] = 0;
                    }
                    $v['coupon_money'] = $v['money'];
                }else{
                    unset($v['money'], $v['min_use_money']);
                    $v['cash_money'] = $v['max_dis_money'];
                    $v['coupon_money'] = $v['max_dis_money'];
                    
                    if($order_money > 0 && $v['discount'] >= 0.1 && $v['discount'] <= 9.9){
                        $v['cash_money'] = round($order_money*(10-$v['discount'])/10, 2);
                        
                        if($v['max_dis_money'] > 0 && $v['cash_money'] > $v['max_dis_money']){
                            $v['cash_money'] = $v['max_dis_money'];
                        }
                    }
                }
                if($v['status'] != 1){
                    $v['is_usable'] = 0;
                }
                $v['cash_money'] = round($v['cash_money'], 2);
                $data[$k] = $v;
            }
            $count = $this->where($where)->count();
            return array('lists' => $data, 'total' => $count);
        }
        
        public function set_out($uid){
            $this->where(array('uid' => $uid, 'status' => 1, 'last_time' => array(array('gt', 0),array('lt', NOW_TIME))))->save(array('status' => 3));
        }
}