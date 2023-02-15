<?php

namespace User\Model;

use Think\Model;

class OrderModel extends Model {

    /**
     * 自动完成
     * @var array
     */
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
        array('pay_type', 'set_pay_type', self::MODEL_INSERT, 'callback'),
    );
    
    protected function set_pay_type($param){
        return $param ? $param : 2;
    }

    protected function _after_find(&$result, $options) {
        isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
        isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
        if (isset($result['status'])) {
            switch($result['status']){
                case 1:
                    $result['status_text'] = '未支付';
                    break;
                case 2:
                    $result['status_text'] = '已支付';
                    break;
                case 3:
                    $result['status_text'] = '已取消';
                    break;
                case 4:
                    $result['status_text'] = '已发货';
                    break;
                case 5:
                    $result['status_text'] = '已完成';
                    break;
                default:
                $result['status_text'] = '等待系统确认';
            }
        }

        $pay_status = array(1 => '未支付', 2 => '已支付');
        isset($result['pay_status']) && $result['pay_status_text'] = isset($pay_status[$result['pay_status']]) ? $pay_status[$result['pay_status']] : '';
        
        if(isset($result['receipt_info'])){
            $result['receipt_info'] = $result['receipt_info'] ? json_decode($result['receipt_info'], true) : array();
        }
        if(isset($result['express_info'])){
            $result['express_info'] = $result['express_info'] ? json_decode($result['express_info'], true) : array();
        }

    }

    protected function _after_select(&$result, $options) {
        foreach ($result as &$record) {
            $this->_after_find($record, $options);
        }
    }

    public function get_sn($uid = 0, $l = 0){
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $key = $uid%26;
        $order_sn = $str{$key}.substr(md5($uid.$l), 10, 2).date('ymdHis').mt_rand(100, 999);
        if($this->where(array('order_sn' => $order_sn))->field('id')->find()){
            $order_sn = $this->get_sn($uid, $l+1);
        }
        return $order_sn;
    }
    
    public function update($data = NULL) {
        $data = $this->create($data);
        if (!$data) {
            return false;
        }
        if (empty($data['id'])) {
            $id = $this->add();
            if (!$id) {
                $this->error = '添加出错！';
                return false;
            }
        } else {
            $status = $this->save();
            if (false === $status) {
                $this->error = '更新出错！';
                return false;
            }
        }
        return $data;
    }

    public function get_order($order_sn, $uid) {
        $where = array();
        $where['order_sn'] = $order_sn;
        $info = $this->where($where)->find();
        if (!$info) {
            return false;
        } elseif ($info['uid'] == 0) {
            if (!$this->bind($order_sn, $uid)) {
                return -2;
            }
        } elseif ($info['uid'] != $uid) {
            return -1;
        }
        return $info;
    }

    public function get_info($order_sn, $where = array(), $uid = 0, $field = '*', $get_detail = true) {
        $this->set_out_time($order_sn);
        $where['order_sn'] = $order_sn;
        $info = $this->where($where)->field($field)->find();
        if (!$info) {
            return false;
        }
        
        if ($uid > 0) {
            if (!$info['uid']) {
                if (!$this->bind($order_sn, $uid)) {
                    return false;
                }
                $info['uid'] = $uid;
            } elseif ($info['uid'] != $uid) {
                return -1;
            }
        }
        if($get_detail){
            $detail = M('OrderDetail')->where(array('order_sn' => $order_sn))->field('title,d_id,cover_id,num,price')->select();
            foreach ($detail as $k => $v) {
                $v['pic_url'] = get_cover_url($v['cover_id']);
                unset($v['cover_id']);
                $detail[$k] = $v;
            }
            $info['detail'] = $detail ? $detail : array();
        }
        if(isset($info['store_id'])){
            $store = M('Store')->where(array('id' => $info['store_id']))->find();
            $info['store_title'] = $store['title'];
        }
        if(isset($info['create_time']) && isset($info['status'])){
            $info['last_pay_second'] = $info['status'] == 1 ? $this->get_last_pay_time_second($info['create_time']) : 0;
            $info['last_pay_time'] = $this->get_last_pay_time($info['create_time']);
        }
        return $info;
    }
    
    public function check_goods($order_sn, $store_id, $detail){
        $pre = C('DB_PREFIX');
        foreach ($detail as $v) {
            $goods_info = M('Goods')->where(array('_string' => "{$pre}goods_store.status = 1 and {$pre}goods.id = {$v['d_id']} and {$pre}goods_store.store_id = ".$store_id))->join('__GOODS_STORE__ ON __GOODS__.id = __GOODS_STORE__.goods_id')->field("title,{$pre}goods_store.num")->find();
            if(!$goods_info || $v['num'] > $goods_info['num']){
                $this->where(array('order_sn' => $order_sn))->save(array('status' => 3));
                return false;
            }
        }
        return true;
    }
    
    public function lists($where = array(), $field = '*', $page = 1, $size = 10, $get_detail = false) {
        $this->set_out_time();
        $lists = $this->where($where)->page($page, $size)->field($field)->order('create_time desc')->select();
        !$lists && $lists = array();
        if ($get_detail && $lists) {
            foreach ($lists as $v) {
                $order_sn[] = $v['order_sn'];
            }
            $detail_arr = M('OrderDetail')->where(array('order_sn' => array('in', $order_sn)))->field('order_sn, title,d_id,cover_id,num,price')->select();
            foreach ($detail_arr as $v) {
                $v['pic_url'] = get_cover_url($v['cover_id']);
                $_item = $v;
                unset($_item['order_sn'], $_item['cover_id']);
                $item[$v['order_sn']][] = $_item;
            }
            foreach ($lists as $k => $v) {
                $v['detail'] = isset($item[$v['order_sn']]) ? $item[$v['order_sn']] : array();
                if(isset($v['create_time']) && isset($v['status'])){
                    $v['last_pay_second'] = $v['status'] == 1 ? $this->get_last_pay_time_second($v['create_time']) : 0;
                    $v['last_pay_time'] = $this->get_last_pay_time($v['create_time']);
                }
                $lists[$k] = $v;
            }
        }
        $count = $this->where($where)->count();
        return array('lists' => $lists, 'count' => $count);
    }
    /**
     * 设置订单为过期取消
     */
    public function set_out_time($order_sn = ''){
        $hours = C('ORDER_CANCEL_TIMES');
        $time = $hours*3600;
        $last_c_time = NOW_TIME-$time;
        $where = array();
        $order_sn && $where['order_sn'] = $order_sn;
        $where['status'] = 1;
        $where['pay_status'] = 1;
        $where['create_time'] = array('lt', $last_c_time);
        $this->where($where)->save(array('status' => 3));
    }

    /**
     * 用户绑定订单
     * @param type $order_sn   订单号
     * @param type $uid        会员ID
     * @return type
     */
    public function bind($order_sn, $uid) {
        return $this->where(array('order_sn' => $order_sn, 'uid' => 0))->save(array('uid' => $uid));
    }
    
    public function set_pay($order_sn, $pay_type, $out_trade_no, $pay_msg = ''){
        $order = $this->where(array('order_sn' => $order_sn))->field('uid, store_id, pos_id, pay_status, status, pay_money, money')->find();
        if($order['pay_status'] != 1 && $order['status'] != 1){
            return false;
        }
        $this->where(array('order_sn' => $order_sn))->save(array('pay_type' => $pay_type, 'pay_status' => 2, 'status' => 2, 'pay_time' => NOW_TIME));

        $pay_data = array(
            'order_sn' => $order_sn,
            'uid' => $order['uid'],
            'pay_type' => $pay_type,
            'money' => $order['pay_money'],
            'create_time' => time(),
            'store_id' => $order['store_id'],
            'pay_sn' => $out_trade_no,
            'pay_msg' => $pay_msg
        );
        M('OrderPayLog')->create($pay_data);
        M('OrderPayLog')->add();
        $detail = M('OrderDetail')->where(array('order_sn' => $order_sn))->select();
        $LogModel = M('GoodsSellLog'.$order['store_id']);
        $goods_id = array();
        foreach($detail as $v){
            $goods_id[] = $v['d_id'];
            $_where = array('store_id' => $order['store_id'], 'goods_id' => $v['d_id'], 'date' => date('Y-m-d'));
            if($LogModel->where($_where)->find()){
                $LogModel->where($_where)->save(array('num' => array('exp','num+'.$v['num']), 'money' => array('exp', 'money+'.($v['num']*$v['price']))));
            }else{
                $goods = json_decode($v['goods_log'], true);
                $_data = array(
                    'goods_id' => $v['d_id'],
                    'cate_id'  => $goods['cate_id'],
                    'store_id' => $order['store_id'],
                    'num' => $v['num'],
                    'money' => $v['num']*$v['price'], 
                    'date' => date('Y-m-d')
                );
                $LogModel->add($_data);
            }
            M('GoodsStore')->where(array('goods_id' => $v['d_id'], 'store_id' => $order['store_id']))->setInc('month_num', $v['num']);
            M('GoodsStore')->where(array('goods_id' => $v['d_id'], 'store_id' => $order['store_id'], 'num' => array('egt', $v['num'])))->save(array('sell_num' => array('exp', 'sell_num+'.$v['num']), 'num' => array('exp', 'num-'.$v['num'])));
        }
        $order['pay_status'] = 2;
        $order['status'] = 2;
        
        $UcApi = new \User\Client\Api();
        // 增加积分
        $UcApi->execute('Scorebox', 'add_score', array('uid' => $order['uid'], 'name' => 'buy', 'num' => $order['money']));
        // 生成红包
        $UcApi->execute('CashCoupon', 'make_lottery_coupon', array('uid' => $order['uid'], 'order_sn' => $order_sn));
        $goods_id && D('Addons://Goods/GoodsStore')->num_notice($goods_id, $order['store_id']);
        return $order;
    }
    /**
     * 获取订单支付金额
     * @param type $order_sn
     * @param type $uid
     * @param type $cash_code
     * @param type $level_sale
     * @return int
     */
    public function get_pay_money($order_sn, $uid, $cash_code = '', $pay_app = '', $return_all_money = false){
        $field = 'uid,pay_money,money,cash_code,cash_money,user_discount_money,pay_app';
        $order = $this->where(array('order_sn' => $order_sn, 'uid' => $uid, 'pay_status' => 1, 'status' => 1))->field($field)->find();
        if(!$order){
            return -1;
        }
        // 除去优惠券金额
        $pay_money = $order['money'];
        $cash_money = $order['cash_money'];
        if(!$order['cash_code'] && $cash_code){
            $result = $this->use_cash($order_sn, $uid, $cash_code, $order);
            $result && $cash_money = $result;
        }
        $pay_money = round($pay_money - $cash_money, 2);
        
        // 会员优惠金额
        $user_discount_money = 0;
        if($pay_money > 0){
            // 去除会员折扣
            if($pay_app == 'account_app'){
                
                $req = \User\Client\Api::execute('Scorebox', 'info', array('uid' => $uid));
                $score_data = $req['data'];
                $n_money = round($pay_money*(100-$score_data['level_sale'])/100, 2);
                $user_discount_money = round($pay_money - $n_money, 2);
                $pay_money = $n_money;
            }
        }
        $pay_money < 0 && $pay_money = 0;
        
        $money_data = array();
        ($pay_money != $order['pay_money']) && $money_data['pay_money'] = $pay_money;
        ($cash_money != $order['cash_money']) && $money_data['cash_money'] = $cash_money;
        ($user_discount_money != $order['user_discount_money']) && $money_data['user_discount_money'] = $user_discount_money;
        !$order['cash_code'] && $cash_code && $cash_money > 0 && $money_data['cash_code'] = $cash_code;
        $pay_app && $pay_app!= $order['pay_app'] && $money_data['pay_app'] = $pay_app;
        if(!$money_data){
            if($return_all_money){
                return array('pay_money' => $pay_money, 'cash_money' => $cash_money, 'user_discount_money' => $user_discount_money);
            }else{
                return $pay_money;
            }
        }
        $money_data['update_time'] = NOW_TIME;
        if($this->where(array('order_sn' => $order_sn, 'uid' => $uid, 'pay_status' => 1, 'status' => 1))->save($money_data)){
            if($return_all_money){
                return array('pay_money' => $pay_money, 'cash_money' => $cash_money, 'user_discount_money' => $user_discount_money);
            }else{
                return $pay_money;
            }
        }else{
            return -1;
        }
    }
    
    public function use_cash($order_sn, $uid, $code, $info = array()) {
        !$info && $info = $this->where(array('order_sn' => $order_sn, 'uid' => $uid))->field('order_sn, cash_code, money')->find();
        if ($info['cash_code']) {
            return false;
        }
        $cash = D('CashCoupon')->to_use_cash($uid, $code, $info['money']);
        
        if ($cash) {
            return $cash['money'];
        }
        return false;
    }
    public function get_express_money($money, $data){
        $express_money = 0;
        if(!empty($data['goods_id'])){
            $goods_info = M('Goods')->where(array('id' => $data['goods_id']))->field('express_money')->find();
            if($goods_info && $goods_info['express_money'] > 0){
                $express_money = $goods_info['express_money'];
            }
        }
        if($express_money == 0 && !empty($data['sheng'])){
            $area_info = M('ExpressMoneyArea')->where(array('sheng' => $data['sheng']))->find();
            if($area_info){
                $express_money = $area_info['money'];
            }
        }
        $express_money = round($express_money, 2);
        $express_money < 0 && $express_money = 0;
        return $express_money;
    }
    public function add_order_online($uid, $goods, $receipt_data, $cash_code, $pay_app = ''){
        // 计算总价        
        $pay_money = 0;
        $goods_ids = array();
        foreach($goods as $k => $v){
            if(empty($v['id']) || empty($v['num'])){
                unset($goods[$k]);
            }
            $v['id'] = intval($v['id']);
            $v['num'] = intval($v['num']);
            if($v['id'] < 0 || $v['num'] < 0){
                unset($goods[$k]);
            }else{
                $goods_ids[] = $v['id'];
            }
        }
        if(!$goods){
            $this->error = '未选择商品'; 
            return false;
        }
        $num = 0;
        $store_id = C('STORE_ONLINE');
        $where = array(
            'a.id' => array('in', $goods_ids),
            'a.status' => 1,
            'b.store_id' => $store_id,
        );
        $join = "__GOODS_STORE__ as b ON a.id = b.goods_id";
        $goods_lists = M('Goods')->alias('a')->where($where)->join($join)->field("a.id, a.title, a.cover_id, a.cate_id, a.sell_price, b.price, b.num")->select();
        if(!$goods_lists){
            $this->error = '购买的商品不存在或已下架';
            return false;
        }
        $_goods_data = array();
        $goods_id = array();
        foreach($goods_lists as $v){
            $v['price'] <= 0 && $v['price'] = $v['sell_price'];
            unset($v['sell_price']);
            $_goods_data[$v['id']] = $v;
        }
        foreach($goods as $k => $v){
            if(!isset($_goods_data[$v['id']])){
                unset($_goods_data[$v['id']], $goods[$k]);
                continue;
            }
            $info = $_goods_data[$v['id']];
            if($v['num'] > $info['num']){
                $this->error = '《'.$info['title'].'》库存不足';
                return false;
            }
            $pay_money += $info['price']*$v['num'];
            $goods[$k]['info'] = $info;
            $num += $v['num'];
            $goods_id = $v['id'];
        }
        if(!$goods){
            $this->error = '未选择商品';
            return false;
        }
        $order_sn = $this->get_sn($uid);
        // 快递费用
        $express_money = $this->get_express_money($pay_money, array('sheng' => $receipt_data['sheng'], 'goods_id' => $goods_id));
        $pay_money += $express_money;
        // 判断优惠券是否可用
        $cash_money = 0;
        if($cash_code){
            $cash_info = D('CashCouponUser')->get_info($cash_code, $uid, $pay_money);
            if($cash_info && $cash_info['status'] == 1){
                $cash_money = $cash_info['cash_money'];
            }
        }
        $receipt_info = array(
            'name' => $receipt_data['name'],
            'mobile' => $receipt_data['mobile'],
            'sheng' => $receipt_data['sheng'],
            'shi' => $receipt_data['shi'],
            'qu' => $receipt_data['qu'],
            'sheng_title' => isset($receipt_data['sheng_title']) ? $receipt_data['sheng_title'] : '',
            'shi_title' => isset($receipt_data['shi_title']) ? $receipt_data['shi_title'] : '',
            'qu_title' => isset($receipt_data['qu_title']) ? $receipt_data['qu_title'] : '',
            'address' => $receipt_data['address']
        );
        // 添加订单
        $data = array(
            'order_sn' => $order_sn,
            'pay_money' => $pay_money,
            'money' => $pay_money,
            'cash_money' => 0,
            'receipt_info' => json_encode($receipt_info),
            'express_money' => $express_money,
            'user_discount_money' => 0,
            'status' => 1,
            'pay_status' => 1,
            'uid' => $uid,
            'store_id' => $store_id,
            'type' => 'online',
        );
        $data = $this->create($data);
        if(!$data){
            return false;
        }
        $result = $this->add();
        if($result){
            // 使用优惠券，获取最终金额
            $money_data = $this->get_pay_money($order_sn, $uid, $cash_code, $pay_app, true);
            if($money_data){
                $data['pay_money'] = $money_data['pay_money'];
                $data['cash_money'] = $money_data['cash_money'];
                $data['user_discount_money'] = $money_data['user_discount_money'];
            }
            $goods_ids = array();
            $goods_data = array();
            foreach($goods as $v){
                $detail = array(
                    'order_sn' => $order_sn,
                    'title' => $v['info']['title'],
                    'type' => 'goods',
                    'd_id' => $v['id'],
                    'num' => $v['num'],
                    'price' => $v['info']['price'],
                    'cover_id' => $v['info']['cover_id'],
                    'setting' => '',
                    'goods_log' => json_encode($v['info'])
                );
                M('OrderDetail')->create($detail);
                M('OrderDetail')->add();
                $goods_ids[] = $v['id'];
                $goods_data[] = array(
                    'goods_id' => $v['id'],
                    'title' => $v['info']['title'],
                    'num' => $v['num'],
                    'price' => $v['info']['price'],
                    'pic_url' => get_cover_url($v['info']['cover_id']),
                );
            }
            
            $r_data = array(
                'order_sn' => $order_sn,
                'pay_money' => $data['pay_money'],
                'money' => $data['money'],
                'cash_code' => $data['cash_money'] > 0 ? $cash_code : '',
                'cash_money' => $data['cash_money'],
                'user_discount_money' => $data['user_discount_money'],
                'status' => 1,
                'status_text' => '新订单',
                'pay_status' => 1,
                'pay_status_text' => '未支付',
                'create_time' => $data['create_time'],
                'goods_data' => $goods_data,
            );
            
            return $r_data;
        }else{
            return false;
        }
    }
    /**
     * 取消订单(只有未支付的情况下才可取消)
     * @param type $order_sn
     * @param type $uid
     * @return boolean
     */
    public function cancel_order($order_sn, $uid){
        $where = array('order_sn' => $order_sn, 'uid' => $uid, 'pay_status' => 1, 'status' => 1);
        if($this->where($where)->save(array('status' => 3, 'update_time' => NOW_TIME))){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 获取订单支付的剩余时间(秒数)
     * @param type $c_time  订单创建时间
     * @return type
     */
    public function get_last_pay_time_second($c_time){
        $hours = C('ORDER_CANCEL_TIMES');
        $time = $hours*3600;
        $last_time = $c_time+$time-NOW_TIME;
        return $last_time > 0 ? $last_time : 0;
    }
    /**
     * 获取订单支付的剩余时间(时间戳)
     * @param type $c_time  订单创建时间
     * @return type
     */
    public function get_last_pay_time($c_time){
        $hours = C('ORDER_CANCEL_TIMES');
        $time = $hours*3600;
        return $c_time+$time;
    }
    /**
     * 确认收货
     * @param type $order_sn  订单号
     * @return type
     */
    public function confirm_receipt($order_sn, $uid = 0){
        $where = array();
        $where['order_sn'] = $order_sn;
        $uid > 0 && $where['uid'] = $uid;
        $where['status'] = 4;
        if($this->where($where)->save(array('status' => 5))){
            return true;
        }else{
            return false;
        }
    }
}
