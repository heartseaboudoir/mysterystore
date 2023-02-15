<?php

namespace Addons\Order\Model;

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
    
    protected function set_pay_type($param = 0){
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
                case 6:
                    $result['status_text'] = '已退款';
                    break;
                default:
                $result['status_text'] = '等待系统确认';
            }
            if(!empty($result['refund_status']) && in_array($result['status'], array(2,4))){
                switch($result['refund_status']){
                    case 1:
                        $result['status_text'] = '待退款';
                        break;
                    case 2:
                        $result['status_text'] = '退款中';
                        break;
                    case 3:
                        $result['status_text'] = '退款失败';
                        break;
                }
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
        if(isset($info['store_id']) && isset($info['type'])){
            if($info['type'] == 'shop'){
                $info['store_title'] = get_nickname($info['store_id']);
                $info['store_pic'] = get_header_pic($info['store_id']);
            }else{
                $store = M('Store')->where(array('id' => $info['store_id']))->find();
                $info['store_title'] = $store['title'];
                $info['store_pic'] = get_store_header($info['store_id']);
            }
        }
        if(isset($info['create_time']) && isset($info['status']) && $info['status'] == 1 ){
            $info['last_pay_second'] = $this->get_last_pay_time_second($info['create_time']);
            $info['last_pay_time'] = $this->get_last_pay_time($info['create_time']);
        }else{
            $info['last_pay_second'] = 0;
            $info['last_pay_time'] = 0;
        }
        if(isset($info['rd_time'])){
            $info['last_refund_second'] = 0;
            $info['last_refund_time'] = 0;
            $info['last_receipt_second'] = 0;
            $info['last_receipt_time'] = 0;
            if(in_array($info['status'], array(2,4)) && $info['refund_status'] == 1){
                $last_refund_time = $this->get_last_rd_time($info['rd_time'], 'refund');
                $info['last_refund_second'] = $last_refund_time['second'];
                $info['last_refund_time'] = $last_refund_time['time'];
            }
            if($info['status'] == 4 && $info['refund_status'] == 0){
                $last_receipt_time = $this->get_last_rd_time($info['rd_time'], 'receipt');
                $info['last_receipt_second'] = $last_receipt_time['second'];
                $info['last_receipt_time'] = $last_receipt_time['time'];
            }
        }
        return $info;
    }
    
    public function check_goods($order_sn, $store_id, $detail, $type = 'store'){
        $goods_ids = array();
        foreach ($detail as $v) {
            $goods_ids[] = $v['d_id'];
        }
        if($type == 'shop'){
            $goods_info = M('ShopGoods')->where(array('id' => array('in', $goods_ids), 'uid' => $store_id, 'status' => 1, 'is_shelf' => 1))->select();
            $goods_info = reset_data($goods_info, 'id');
        }else{
            $where = array(
                'b.status' => 1,
                'a.id' => array('in', $goods_ids),
                'b.store_id' => $store_id,
            );
            $goods_info = M('Goods')->alias('a')->where($where)->join('__GOODS_STORE__ as b  ON a.id = b.goods_id')->field("a.id,a.title,b.num")->select();
            $goods_info = reset_data($goods_info, 'id');
        }
        foreach ($detail as $v) {
            if(!isset($goods_info[$v['d_id']]) || $goods_info[$v['d_id']]['num'] < $v['num']){
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
        $order = $this->where(array('order_sn' => $order_sn))->field('uid, store_id, pos_id, pay_status, status, pay_money, money, type')->find();
        if($order['pay_status'] != 1 && $order['status'] != 1){
            return false;
        }
        $do_data = array(
            'pay_type' => $pay_type,
            'pay_status' => 2,
            'status' => 2,
            'pay_sn' => $out_trade_no,
            'pay_time' => NOW_TIME
        );
        // 如果为线下门店的订单，则直接已完成
        if($order['type'] == 'store'){
            $do_data['status'] = 5;
            $do_data['end_time'] = NOW_TIME;
        }
        $this->where(array('order_sn' => $order_sn))->save($do_data);

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
        $push_data = array();
        foreach($detail as $v){
            $goods_id[] = $v['d_id'];
            !$push_data && $push_data = array(
                'pic_url' => get_cover_url($v['cover_id']),
                'title' => $v['title']
            );
            // 销售结果通知
            if($order['type'] == 'shop'){
                D('Addons://Shop/ShopArticle')->sell_notify($v['d_id'], $order['store_id'], $order['uid'], $v['num'], $order_sn);
            }else{
                // 添加销售记录
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
        }
        $order['pay_status'] = 2;
        $order['status'] = 2;
        $api = new \User\Client\Api();
        $api->execute('Scorebox', 'add_score', array('uid' => $order['uid'], 'name' => 'buy', 'num' => $order['money']));
        
        $api->execute('CashCoupon', 'make_lottery_coupon', array('uid' => $order['uid'], 'order_sn' => $order_sn));
        if($goods_id){
            if($order['type'] != 'shop'){
                D('Addons://Goods/GoodsStore')->num_notice($goods_id, $order['store_id']);
            }
        }
        // 店铺用户的商品发通知
        $order['type'] == 'shop' && $api->execute('Message', 'add_notice', array('act_uid' => $order['uid'], 'act_id' => $order_sn, 'type' => 'seller_order', 'uid' => $order['store_id'], 'param' => $push_data, 'hid' => 'wait_delivery'));
        
        // 减批次库存
        $order['type'] == 'store' && $this->sell_goods(array('order_sn' => $order_sn));
        
        // 支付完触发回调
        $this->pay_callback(array('order_sn' => $order_sn));        
        
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
        $field = 'uid,pay_money,money,cash_code,cash_money,user_discount_money,pay_app,type,store_id';
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
                
                
                // 获取门店优惠拆折扣值
                if($order['type'] == 'store'){
                    $store_discount = $this->getStoreDiscount($order['store_id']);
                } else {
                    $store_discount = 0;
                }

                //xydebug($store_discount, 'descount.txt');
                
                // 有门店优惠
                if ($store_discount > 0) {
                    $user_sale = (100 - $store_discount)/100;   
                } else {
                    // 获取会员优惠折扣值
                    $scorebox = \User\Client\Api::execute('Scorebox', 'info', array('uid' => $uid));
                    $user_sale = (100 - $scorebox['level_sale'])/100;                
                }                
                
                
                // 计算折扣金额
                if($pay_money > 0 && $user_sale > 0){
                    $n_money = round($pay_money*$user_sale, 2);
                    $user_discount_money = round($pay_money - $n_money, 2);
                    $pay_money = $n_money;
                }                
                
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
    
    /**
     * 返回优惠值
     * 0:不优惠
     * 15:优惠15%
     */
    private function getStoreDiscount($store_id)
    {
        
        if (empty($store_id)) {
            return 0;
        }
        
        
        $store_info = M('store')->where(array(
            'id' => $store_id,
        ))->find();
        
        if (empty($store_info) || empty($store_info['is_rate']) || empty($store_info['rate_val'])) {
            return 0;
        }
        
        
        if ($store_info['is_rate'] != 1 || $store_info['rate_val'] < 0) {
            return 0;
        }
        
        if ($store_info['rate_val'] > 50) {
            $discount = 50;
        } else {
            $discount = $store_info['rate_val'];
        }
        
        return $discount;
    }  
    
    
    
    public function use_cash($order_sn, $uid, $code, $info = array()) {
        !$info && $info = $this->where(array('order_sn' => $order_sn, 'uid' => $uid))->field('order_sn, cash_code, money')->find();
        if ($info['cash_code']) {
            return false;
        }
        $req = \User\Client\Api::execute('CashCoupon', 'to_use_cash', array('code' => $code, 'uid' => $uid, 'money' => $info['money']));
        if($req['status'] != 1){
            return false;
        }
        $cash = $req['data'];
        
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
            $req = \User\Client\Api::execute('CashCoupon', 'coupon_info', array('code' => $cash_code, 'uid' => $uid, 'money' => $pay_money));
            if($req['status'] == 1){
                $cash_info = $req['data'];
                if($cash_info && $cash_info['status'] == 1){
                    $cash_money = $cash_info['cash_money'];
                }
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
                D('Addons://Order/OrderDetail')->create($detail);
                D('Addons://Order/OrderDetail')->add();
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
    public function add_order($type, $store_id , $uid, $goods, $receipt_data, $cash_code, $pay_app = '', $remark = ''){
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
        $_goods_data = array();
        switch($type){
            case 'online':
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
                foreach($goods_lists as $v){
                    $v['price'] <= 0 && $v['price'] = $v['sell_price'];
                    unset($v['sell_price']);
                    $_goods_data[$v['id']] = $v;
                }
                break;
            case 'shop':
                if($store_id == $uid){
                    $this->error = '不能购买自己的商品';
                    return false;
                }
                $goods_lists = M('ShopGoods')->where(array('id' => array('in', $goods_ids), 'uid' => $store_id, 'status' => 1, 'is_shelf' => 1))->field('id,title,pic,price,num,express_money')->select();
                foreach($goods_lists as $v){
                    $v['cover_id'] = $v['pic'];
                    $express_money += $v['express_money'];
                    $_goods_data[$v['id']] = $v;
                }
                break;
            default:
                $this->error = '添加订单失败';
                return false;
        }
        $num = 0;
        $goods_id = array();
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
        $type == 'online' && $express_money = $this->get_express_money($pay_money, array('sheng' => $receipt_data['sheng'], 'goods_id' => $goods_id));
        if(!$goods){
            $this->error = '未选择商品';
            return false;
        }
        $order_sn = $this->get_sn($uid);
        // 快递费用
        $pay_money += $express_money;
        // 判断优惠券是否可用
        $cash_money = 0;
        if($cash_code){
            $Api = new \User\Client\Api();
            $req = $Api->execute('CashCoupon', 'coupon_info', array('code' => $cash_code, 'uid' => $uid, 'money' => $pay_money));
            if($req['status'] == 1){
                $cash_info = $req['data'];
                if($cash_info && $cash_info['status'] == 1){
                    $cash_money = $cash_info['cash_money'];
                }
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
            'type' => $type,
            'remark' => $remark,
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
            $push_data = array();
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
                D('Addons://Order/OrderDetail')->create($detail);
                D('Addons://Order/OrderDetail')->add();
                $goods_ids[] = $v['id'];
                $item = array(
                    'goods_id' => $v['id'],
                    'title' => $v['info']['title'],
                    'num' => $v['num'],
                    'price' => $v['info']['price'],
                    'pic_url' => get_cover_url($v['info']['cover_id']),
                );
                $goods_data[] = $item;
                !$push_data && $push_data = array('title' => $item['title'], 'pic_url' => $item['pic_url']);
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
            if($type == 'shop'){
                $this->add_notice(1, 'wait_pay', array('order_sn' => $order_sn, 'store_id' => $store_id, 'uid' => $uid), $push_data);
            }
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
        if($this->where($where)->save(array('status' => 5, 'end_time' => NOW_TIME, 'update_time' => NOW_TIME))){
            $info = $this->get_info($order_sn, array(), 0, 'order_sn, type, store_id, pay_money');
            if($info['type'] == 'shop'){
                // 为买家钱包添加金额
                $Api = new \User\Client\Api();
                $Api->execute('Wallet', 'inc_money', array('uid' => $info['store_id'], 'money' => $info['pay_money'], 'sn' => $order_sn, 'action' => 'order_deal'));
                $this->add_notice(1, 'confirm_receipt', $order_sn);
            }
            return true;
        }else{
            return false;
        }
    }
    /**
     * 设置已评价
     * @param type $order_sn
     * @return boolean
     */
    public function set_assess($order_sn){
        if(!$order_sn) return false;
        if($this->where(array('order_sn' => $order_sn))->save(array('is_assess' => 1))){
            $this->add_notice(1, 'assess', $order_sn);
            return true;
        }else{
            return false;
        }
    }
    /**
     * 修改订单金额
     * @param type $order_sn
     * @param type $money
     * @param type $uid
     * @return boolean
     */
    public function edit_pay_money($order_sn, $money, $uid){
        if(!trim($order_sn)){
            return false;
        }
        $money = round($money, 2);
        $uid = intval($uid);
        if(!($uid > 0)){
            return false;
        }
        $where = array('order_sn' => $order_sn, 'type' => 'shop', 'store_id' => $uid);
        if($this->where($where)->save(array('pay_money' => $money, 'update_time' => NOW_TIME))){
            $data = array(
                'order_sn' => $order_sn,
                'uid' => $uid,
                'money' => $money,
                'ip' => get_client_ip(),
                'create_time' => NOW_TIME,
            );
            M('OrderMoneyChange')->add($data);
            return true;
        }else{
            return false;
        }
    }
    /**
     * 卖家发货
     * @param type $order_sn
     * @param type $uid
     * @param type $express_name
     * @param type $no
     * @return boolean
     */
    public function delivery_by_shop($order_sn, $uid, $express_name, $no){
        return $this->delivery($order_sn, $uid, $express_name, $no, 'shop');
    }
    /**
     * 发货
     * @param type $order_sn
     * @param type $uid
     * @param type $express_name
     * @param type $no
     * @return boolean
     */
    public function delivery($order_sn, $uid, $express_name, $no, $type = ''){
        $express_data = D('Addons://Order/OrderExpress')->get_express($express_name);
        if(!$express_data){
            $this->error = '快递公司不存在';
            return false;
        }
        $express_info = array(
            'name' => $express_data['name'],
            'company' => $express_data['company'],
            'no' => $no,
        );
        $data = array(
            'status' => 4,
            'express_info' => json_encode($express_info),
            'express_time' => NOW_TIME,
            'rd_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        $where = array('order_sn' => $order_sn, 'store_id' => $uid, 'pay_status' => 2, 'status' => 2);
        $type && $where['type'] = $type;
        $order_info = $this->get_info($order_sn, array(), 0, 'id,order_sn,uid', true);
        if(!$order_info){
            return false;
        }
        if($this->where($where)->save($data)){
            $this->send_delivery_notify($order_sn, $express_data['name'], $no);
            $this->add_notice(2, 'delivery', $order_info);
            return true;
        }else{
            return false;
        }
    }
    public function add_notice($type, $hid, $order, $push_data = array(), $t = ''){
        $get_detail = false;
        !$push_data && $get_detail = true;
        if(!is_array($order) || (empty($order['detail']) && !$push_data)){
            $order = $this->get_info($order, array(), 'order_sn,store_id,uid', $get_detail);
        }
        if($get_detail){
            $item = $order['detail'][0];
            $push_data = array(
                'pic_url' => $item['pic_url'],
                'title' => $item['title']
            );
        }
        if($type == 1){
            $type = 'seller_order';
            $uid = $order['store_id'];
            $act_uid = $order['uid'];
        }else{
            $type = 'order';
            $uid = $order['uid'];
            $act_uid = $order['store_id'];
        }
        $api = new \User\Client\Api();
        $api->execute('Message', 'add_notice', array('act_uid' => $act_uid, 'act_id' => $order['order_sn'], 'type' => $type, 'uid' => $uid, 'param' => $push_data, 'hid' => $hid, 't' => $t));
    }
    /**
     * 修改发货信息
     * @param type $order_sn
     * @param type $express_name
     * @param type $no
     * @return boolean
     */
    public function change_delivery($order_sn, $express_name, $no){
        $express_data = D('Addons://Order/OrderExpress')->get_express($express_name);
        if(!$express_data){
            $this->error = '快递公司不存在';
            return false;
        }
        $express_info = array(
            'name' => $express_data['name'],
            'company' => $express_data['company'],
            'no' => $no,
        );
        $data = array(
            'order_sn' => $order_sn,
            'express_info' => json_encode($express_info),
            'express_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        $where = array('order_sn' => $order_sn, 'pay_status' => 2, 'status' => 4);
        if($this->where($where)->save($data)){
            $this->send_delivery_notify($order_sn, $express_data['name'], $no, 'update');
            return true;
        }else{
            return false;
        }
    }
    /**
     * 发送物流通知
     * @param type $order_sn
     * @param type $company_name
     * @param type $no
     */
    public function send_delivery_notify($order_sn, $company_name, $no, $type = 'new'){
        $data = array(
            'order_sn' => $order_sn,
            'no' => $no,
            'company_name' => $company_name,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME
        );
        if($type == 'update'){
            $result = M('OrderExpressLog')->where(array('order_sn' => $order_sn))->save($data);
        }else{
            $result =M('OrderExpressLog')->add($data);
        }
        if($result){
            $exress_data = D('Addons://Order/OrderExpress')->get_express($company_name);
            if(!$exress_data || empty($exress_data['search_no'])){
                return false;
            }
            $api = new \Addons\Order\Lib\Express\KdApi();
            $api->traces_sub($exress_data['search_no'], $no, $order_sn);
        }
    }
    
    /**
     * 获取物流信息
     * @param type $order_sn
     * @param type $type        查询类型：order_sn：订单号  no：快递单号
     * @return type
     */
    public function get_express_log($order_sn, $type = 'order_sn'){
        $where = array();
        if($type == 'no'){
            $where['no'] = $order_sn;
        }else{
            $where['order_sn'] = $order_sn;
        }
        $log_data = M('OrderExpressLog')->where($where)->find();
        if(!$log_data || empty($log_data['data'])){
            return array();
        }
        $express_data = json_decode($log_data['data'], true);
        return $express_data;
    }
    /**
     * 退款申请
     * @param type $order_sn
     * @param type $uid
     * @param type $money
     * @param type $reason
     * @param type $pics
     * @return boolean
     */
    public function refund($order_sn, $uid, $money, $reason, $pics){
        if(strlen($reason) > 150){
            $this->error = '申请理由太长了，最多150个字符';
            return false;
        }
        $info = $this->get_info($order_sn, array('uid' => $uid));
        if(!$info){
            $this->error = '订单不存在';
            return false;
        }
        if($info['type'] != 'shop' || $info['pay_status'] != 2 || !in_array($info['refund_status'], array(0,3)) || !in_array($info['status'], array(2,4))){
            $this->error = '该订单不能发起退款申请';
            return false;
        }
        if($info['refund_status'] == 1){
            $this->error = '退款申请正在等待处理';
            return false;
        }elseif($info['refund_status'] == 2){
            $this->error = '正在退款中';
            return false;
        }
        if($info['refund_status'] == 4){
            $this->error = '退款已完成';
            return false;
        }
        $max_times = 3;
        if($info['refund_times'] >= $max_times){
            $this->error = '已发起过3次申请，无法再次发起';
            return false;
        }
        if($money > $info['pay_money']){
            $this->error = '申请金额不能大于支付金额';
            return false;
        }
        $Model = M('OrderRefund');
        if($Model->where(array('order_sn' => $order_sn, 'uid' => $uid, 'status' => 1))->find()){
            $this->error = '退款申请正在等待处理，请耐心等待';
            return false;
        }
        if($Model->where(array('order_sn' => $order_sn, 'uid' => $uid, 'status' => 2))->find()){
            $this->error = '该订单已退款';
            return false;
        }
        $pics = $pics ? explode(',', $pics) : array();
        foreach($pics as $k => $v){
            $v = intval($v);
            if(!($v > 0)){
                unset($pics[$k]);
            }
        }
        $pics = implode(',', $pics);
        $data = array(
            'order_sn' => $order_sn,
            'uid' => $uid,
            'money' => $money,
            'reason' => $reason,
            'pics' => $pics,
            'status' => 1,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        if(!$Model->create($data)){
            $this->error = '发起失败';
            return false;
        }
        if($Model->add()){
            $this->where(array('order_sn' => $order_sn))->save(array('refund_status' => 1, 'refund_times' => array('exp', 'refund_times+1'), 'rd_time' => NOW_TIME, 'update_time' => NOW_TIME));
            $this->add_notice(1, 'wait_refund', $order_sn, array(), $info['refund_times']+1);
            return true;
        }else{
            return false;
        }
    }
    /**
     * 取消退款
     * @param type $order_sn
     * @param type $uid
     * @return boolean
     */
    public function cancel_refund($order_sn, $uid){
        $Model  = M('OrderRefund');
        if($Model->where(array('order_sn' => $order_sn, 'uid' => $uid, 'status' => 1))->save(array('status' => 4))){
            $this->where(array('order_sn' => $order_sn))->save(array('refund_status' => 0, 'rd_time' => NOW_TIME, 'update_time' => NOW_TIME));
            return true;
        }else{
            return false;
        }
        
    }
    /**
     * 拒绝退款
     * @param type $order_sn
     * @param type $shop_uid
     * @param type $reason
     * @return boolean
     */
    public function denied_refund($order_sn, $shop_uid, $reason){
        $shop_uid = intval($shop_uid);
        $info = $this->get_info($order_sn, array('type' => 'shop', 'store_id' => $shop_uid));
        if(!$info){
            $this->error = '该订单无法操作';
            return false;
        }
        $Model = M('OrderRefund');
        $where = array('order_sn' => $order_sn, 'status' => 1);
        if(!$Model->where($where)->find()){
            $this->error = '没有需要处理的退款申请';
            return false;
        }
        $data = array(
            'status' => 3,
            'sell_reason' => $reason,
            'update_time' => NOW_TIME,
        );
        if($Model->where($where)->save($data)){
            $this->where(array('order_sn' => $order_sn))->save(array('refund_status' => 3, 'rd_time' => NOW_TIME, 'update_time' => NOW_TIME));
            $this->add_notice(2, 'refund_false', $order_sn, array(), $info['refund_times']);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 同意退款
     * @param type $order_sn
     * @param type $shop_uid
     * @return boolean
     */
    public function agree_refund($order_sn, $shop_uid){
        
        //if ($order_sn == 'i67170809113810360') {

        //}

        
        
        $shop_uid = intval($shop_uid);
        $info = $this->get_info($order_sn, array('type' => 'shop', 'store_id' => $shop_uid, 'refund_status' => 1));
        if(!$info){
            $this->error = '该订单无法操作';
            return false;
        }
        $Model = M('OrderRefund');
        $where = array('order_sn' => $order_sn, 'status' => 1);
        $re_data = $Model->where($where)->find();
        
        
        if(!$re_data){
            $this->error = '没有需要处理的退款申请';
            return false;
        }
        $data = array(
            'status' => 2,
            'update_time' => NOW_TIME,
        );
        if($Model->where($where)->save($data)){          
            
            $refund_info = $Model->where(array('order_sn' => $order_sn, 'status' => 2))->find();
            
            xydebug(array(
                'xx' => $refund_info,
                'info' => $info,
            ));             
            
            if($this->do_refund($refund_info['money'], $order_sn, $info)){
                $this->add_notice(2, 'refund_true', $order_sn, array(), $info['refund_times']);
            }else{
                
                // 失败还原状态
                $Model->where(array('order_sn' => $order_sn, 'status' => 2))->save(array('status' => 1));
            }
            return true;
        }else{
            return false;
        }
    }
    /**
     * 获取订单自动退款的剩余时间
     * @param type $c_time  订单倒计时时间
     * @return second 秒数      time 时间戳
     */
    public function get_last_rd_time($c_time, $type){
        switch($type){
            case 'refund':
                $days = C('ORDER_REFUND_TIME');
                break;
            case 'receipt':
                $days = C('ORDER_RECEIPT_TIMES');
                break;
        }
        // 秒数
        $rd_second = $days*3600*24;
        $last_time = $c_time+$rd_second-NOW_TIME;
        $second = $last_time > 0 ? $last_time : 0;
        // 时间戳
        $time = $c_time+$rd_second;
        return array('second' => $second, 'time' => $time);
    }
    /**
     * 订单退款操作
     * @param type $order_sn
     * @return type
     */
    public function do_refund($refund_money, $order_sn, $info = array()){
        !$info && $info = $this->get_info($order_sn);
        $result = $this->where(array('order_sn' => $order_sn, 'status' => array('in', '2,4')))->save(array('refund_status' => 2, 'refund_money' => $refund_money, 'update_time' => NOW_TIME));
        if($result){
            switch($info['pay_type']){
                case 1:
                    A('Addons://WechatPay/WechatPayclass')->set_config('app');
                    $do_result = A('Addons://WechatPay/WechatPayclass')->refund(array('sn' => $order_sn, 'times' => $info['refund_times'], 'refund_fee' => $refund_money*100));
                    

                    xydebug(array(
                        'do_result' => $do_result,
                    ));

                    
                    break;
                case 2:
                    $do_result = A('Addons://Alipay/F2fpayclass')->refund(array('sn' => $order_sn, 'times' => $info['refund_times'], 'refund_amount' => $refund_money));
                    
                    xydebug(array(
                        'do_result' => $do_result,
                    ));
                    
                    break;
            }
            if(isset($do_result['status']) && $do_result['status'] == 1){
                // 成功
                $this->where(array('order_sn' => $order_sn, 'status' => array('in', '2,4')))->save(array('status' => 6, 'refund_status' => 4, 'end_time' => NOW_TIME));
                return true;
            }else{
                // 失败，可重新发起退款
                $this->where(array('order_sn' => $order_sn, 'status' => array('in', '2,4')))->save(array('refund_status' => 1));
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 假性删除订单
     * @param type $order_sn
     * @param type $uid
     * @return type
     */
    public function hide_order($order_sn, $uid){
        return $this->where(array('order_sn' => $order_sn, 'uid' => $uid, 'status' => array('in', array(3, 5, 6))))->save(array('is_del' => 1)) ? 1 : 0;
    }
    
    
    public function xytest($order_sn, $uid)
    {
        xydebug(array(
            'vvv' => 'test正常',
        ));
        return true;
    }
    
    public function test_xy($data, $filename = 'test.txt')
    {
        $datetime = date('Y-m-d H:i:s');
        $data = print_r($data, true);
        file_put_contents('./' . $filename, $datetime . "\r\n" . $data . "\r\n\r\n", FILE_APPEND);
    }   


    private function pay_callback($data = array())
    {
        
        $isTest = $this->isTest();
        
        if ($isTest) {
            $domain = 'http://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'http://v.imzhaike.com/Apiv2';
        }
        
        $url = '/SmBack/payCallback';
        $url = $domain . $url;
        
        
        
        // xydebug($url, 'wxtpl.txt');
        
        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
        
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /*
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        */
        $result = curl_exec($ch);
        
        $result = json_decode($result, true);
        return $result;
    }     
    
    private function sell_goods($data = array())
    {
        
        $isTest = $this->isTest();
        
        if ($isTest) {
            $domain = 'http://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'http://v.imzhaike.com/Apiv2';
        }
        
        $url = '/SmJump/sell_goods';
        $url = $domain . $url;
        
        
        
        
        
        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
        
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /*
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        */
        $result = curl_exec($ch);
        
        $result = json_decode($result, true);
        return $result;
    }     
    
    
    private function isTest()
    {
        //echo $_SERVER["HTTP_HOST"];
        if ($_SERVER["HTTP_HOST"] != 'v.imzhaike.com') {
            return true;
        } else {
            return false;
        }        
    }    
        
    
}