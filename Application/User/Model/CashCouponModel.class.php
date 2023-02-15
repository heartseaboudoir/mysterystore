<?php

namespace User\Model;
use Think\Model;

class CashCouponModel extends Model{
    
        protected function _after_find(&$result,$options) {
		isset($result['rule']) && $result['rule'] = json_decode($result['rule'], true);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        
        /**
         * 领取代金券
         * @param string $code  券号或标识
         * @param type $uid     会员ID
         * @param type $type    方式：code = 券号  name = 标识
         * @return boolean|int
         */
        public function get_cash_coupon($code, $uid, $type = 'code'){
            $where = array();
            if($type == 'name'){
                $where['name'] = $code;
            }else{
                $where['code'] = $code;
            }
            $info = $this->where($where)->find();
            if($info['status'] == 2){
                return -2;
            }elseif($info['status'] == 3){
                return -3;
            }elseif($info['status'] != 1){
                return 0;
            }
            // 过期处理
            if($info['last_time'] > 0 && $info['last_time'] < NOW_TIME){
                $this->where($where)->save(array('status' => 2));
                return -2;
            }
            // 数量不足处理
            if($info['is_max'] && $info['get_num'] >= $info['num']){
                $this->where($where)->save(array('status' => 3));
                return -3;
            }
            $UserCash = D('CashCouponUser');
            if($info['is_one'] && $UserCash->where(array('uid' => $uid, 'p_code' => $info['code']))->find()){
                return 0;
            }
            $where['status'] = 1;
            $pwhere = $where;
            $money = $info['money'];
            $info['is_max'] && $pwhere['get_num'] = array('lt', $info['num']);
            if(!$this->where($pwhere)->setInc('get_num')){
                $this->where($where)->save(array('status' => 3));
                return -3;
            }
            $is_sp = 0;
            $description = $info['description'];
            if($info['sp_num'] > 0){
                $sp_rate = mt_rand(1, $info['num'] > 0 ? $info['num']-$info['get_num'] : 100);
                $pwhere = $where;
                $pwhere['sp_get_num'] = array('lt', $info['sp_num']);
                if($sp_rate <= $info['sp_num'] && $this->where($pwhere)->setInc('sp_get_num')){
                    $money = $info['sp_money'];
                    $is_sp = 1;
                    !empty($info['sp_description']) && $description = $info['sp_description'];
                }
            }
            $a_data = array(
                'money' => $money,
                'last_time' => $info['last_time'],
                'p_code' => $info['code'],
                'uid' => $uid,
                'title' => $info['title'],
                'description' => $description,
                'type' => $info['type'],
                'discount' => $info['discount'],
                'min_use_money' => $is_sp == 1 ? $info['sp_min_use_money'] : $info['min_use_money'],
                'max_dis_money' => $info['max_dis_money'],
            ); 
            $a_data = $UserCash->create($a_data);
            if($a_data){
                if($UserCash->add($a_data)){
                    return 1;
                }
            }else{
                $idata = array(
                    'get_num' => array('exp', 'get_num - 1')
                );
                $is_sp && $idata['sp_get_num'] = array('exp', 'sp_get_num - 1');
                $this->where($where)->save($idata);
                return 0;
            }
        }
        
        public function get_user_cash($uid, $code){
            $info = M('CashCouponUser')->where(array('uid' => $uid, 'code' => $code, 'status' => 1))->find();
            if(!$info) return false;
            if($info['last_time'] > 0 && $info['last_time'] < NOW_TIME){
                M('CashCouponUser')->where(array('uid' => $uid, 'code' => $code, 'status' => 1))->save(array('status' => 3));
                return false;
            }
            return $info;
        }
        
        public function to_use_cash($uid, $code, $money = 0){
            $Model = D('CashCouponUser');
            $cash_money = $Model->get_cash_money($code, $uid, $money);
            if($Model->where(array('uid' => $uid, 'code' => $code, 'status' => 1))->save(array('status' => 2))){
                return array('money' => $cash_money);
            }else{
                return false;
            }
        }
        // 生成抽取的红包
        public function make_lottery_coupon($uid, $order_sn, $order_money = 0){
            $Model = M('CashCouponMake');
            if($Model->where(array('action_sn' => $order_sn))->find()){
                return false;
            }
            $config =  D('CashCouponConfig')->get_info('pay_share');
            $data = array(
                'code' => uniqid('l'),
                'action_sn' => $order_sn,
                'uid' => $uid,
                'title' => $config['make_title'],
                'description' => $config['make_description'],
                'num' => $config['num'],
                'min_money' => $config['min_money'],
                'max_money' => $config['max_money'],
                'min_use_money' => $config['min_use_money'],
                'sp_money' => $config['sp_money'],
                'sp_num' => $config['sp_num'],
                'sp_min_use_money' => $config['sp_min_use_money'],
                'last_day' => $config['last_day'],
                'type' => $config['type'],
                'status' => 1,
                'create_time' => NOW_TIME,
                'update_time' => NOW_TIME,
            );
            if($Model->add($data)){
                return $data['code'];
            }else{
                return false;
            }
        }
        public function get_lottery($uid, $order_sn){
            $Model = M('CashCouponMake');
            $data = $Model->where(array('action_sn' => $order_sn, 'uid' => $uid))->find();
            if(!$data){
                return false;
            }
            $data['url'] = U('Wap/CashCoupon/lottery_coupon', array('cash_code' => $data['code']));
            return $data;
        }
        // 获取抽取的金额
        public function get_lottery_money($code, $type, $key){
            $money_temp = S('coupon_money_temp');
            if(empty($money_temp[$code][$key])){
                $config =  D('CashCouponConfig')->get_info('pay_share');
                if(!$config){
                    return 0;
                }
                $MakeModel = M('CashCouponMake');
                $make = $MakeModel->where(array('code' => $code))->find();
                
                $money = (mt_rand($config['min_money']*100, $config['max_money']*100))/100;
                $sp_last_num = $make['sp_num'] - $make['make_sp_num'];
                if($sp_last_num > 0){
                    $sp_rate = mt_rand(1, $make['num'] > 0 ? $make['num']-$make['get_num'] : 100);
                    $where = array(
                        'code' => $code,
                        'sp_get_num' => array('lt', $make['sp_num']),
                        'make_sp_num' => array('lt', $make['sp_num']),
                    );
                    // 当随机的范围符合规则且已生成的临时记录中并未完成时使用特殊金额
                    if($sp_rate <= $sp_last_num && $MakeModel->where($where)->setInc('make_sp_num')){
                        $money = $config['sp_money'];
                    }
                }
                $money_temp[$code][$key] = $money;
                
                S('coupon_money_temp', $money_temp);
            }else{
                $money = $money_temp[$code][$key];
            }
            return $money;
        }
        // 添加临时领取记录
        public function set_lottery($code, $type, $key){
            $money_temp = S('coupon_user_temp');
            if(empty($money_temp[$code]) || in_array($key, $money_temp[$code])){
                $money_temp[$code][] = $key;
                S('coupon_user_temp', $money_temp);
            }
        }
        // 移除临时领取记录
        private function remove_lottery($code){
            $money_temp = S('coupon_user_temp');
            if(isset($money_temp[$code])) unset($money_temp[$code]);
            S('coupon_user_temp', $money_temp);
        }
        // 判断是否已领取
        public function check_lottery($code, $type, $key, $uid = 0){
            $UserCash = D('Addons://CashCoupon/CashCouponUser');
        
            $where = array('p_code' => $code);
            $_string = "(receive_type = '{$type}' and receive_token = '{$key}')";
            $uid > 0 && $_string .= " or uid = {$uid}";
            $where['_string'] = "(".$_string.")";
            $data = $UserCash->where($where)->find();
            if($data){
                return $data;
            }
            return false;
        }
        /**
         * 抽取代金券
         * @param string $code  券号或标识
         * @param type $uid     会员ID
         * @param type $key     当前标识：微信 openid  支付宝 支付宝ID
         * @return boolean|int
         */
        public function lottery_cash_coupon($code, $uid, $type, $key, $user_data = array()){
            $UserCash = D('Addons://CashCoupon/CashCouponUser');
            if($UserCash->where(array('uid' => $uid, 'p_code' => $code))->find()){
                return -1;
            }
            $info = M('CashCouponMake')->where(array('code' => $code, 'status' => 1))->find();
            if(!$info){
                return false;
            }
            // 数量不足处理
            if($info['num'] <= $info['get_num']){
                $this->remove_lottery($code);
                return -2;
            }
            // 红包配置
            $config =  D('CashCouponConfig')->get_info('pay_share');
            if(!$config){
                return false;
            }
            $money = $this->get_lottery_money($code, $type, $key);
            $is_sp = 0;
            $description = $config['make_description'] ? $config['make_description'] : '';
            $sp_min_use_money = $config['min_use_money'];
            if($money == $config['sp_money']){
                $is_sp = 1;
                !empty($config['make_sp_description']) && $description = $config['make_sp_description'];
                !empty($config['sp_min_use_money']) && $sp_min_use_money = $config['sp_min_use_money'];
            }
            $item_where = array(
                'code' => $code,
                'status' => 1,
                'get_num' => array('lt', $info['num']
            ));
            $is_sp == 1 && $item_where['sp_get_num'] = array('lt', $info['sp_num']);
            $item_data = array(
                'get_num' => array('exp', 'get_num + 1')
            );
            $is_sp == 1 && $item_data['sp_get_num'] = array('exp', 'sp_get_num + 1');
            if(!M('CashCouponMake')->where($item_where)->save($item_data)){
                return false;
            }
            $last_time = 0;
            if($config['last_day'] > 0){
                $last_time = strtotime('+'.$config['last_day'].' day');
            }
            $a_data = array(
                'money' => $money,
                'last_time' => $last_time,
                'p_code' => $info['code'],
                'uid' => $uid,
                'title' => $config['make_title'] ? $config['make_title'] : ($money.'元优惠券'),
                'description' => $description,
                'min_use_money' => $sp_min_use_money,
                'type' => $config['type'],
                'receive_type' => $type,
                'receive_token' => $key,
                'receive_user' => json_encode($user_data),
            );
            $a_data = $UserCash->create($a_data);
            if($a_data){
                if($UserCash->add($a_data)){
                    if($info['num'] == ($info['get_num']+1)){
                        $this->remove_lottery($code);
                        M('CashCouponMake')->where(array('code' => $code, 'status' => 1))->save(array('status' => 2));
                    }else{
                        $this->set_lottery($code, $type, $key);
                    }
                    return true;
                }
            }
            M('CashCouponMake')->where(array('code' => $code, 'status' => 1))->setDec('get_num');
            return false;
        }
}