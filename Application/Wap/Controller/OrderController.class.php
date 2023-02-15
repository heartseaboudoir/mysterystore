<?php
namespace Wap\Controller;

class OrderController extends BaseController{
    public function __construct() {
        parent::__construct();
        $this->check_login();
    }
    public function lists(){
        $type = I('type', 'wait');
        $page = I('page', 0, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        
        $where = array('uid' => $this->uid);
        switch($type){
            case 'wait':
                $where['status'] = 1;
                break;
            default:
                
        }
        if(IS_AJAX){
        $lists = D('Addons://Order/Order')->lists($where, 'order_sn, uid,create_time,pay_type,pay_money,store_id', $page, $row, true);
            if(!empty($lists['lists'])){
                $store_id = array();
                foreach($lists['lists'] as $v){
                    $store_id[] = $v['store_id'];
                }
                if($store_id){
                    $store = M('Store')->where(array('id' => array('in', $store_id)))->field('id,title')->select();
                    foreach($store as $v){
                        $_store[$v['id']] = $v;
                    }
                }
                foreach($lists['lists'] as $k => $v){
                    $v['store_title'] = isset($_store[$v['store_id']]['title']) ? $_store[$v['store_id']]['title'] : '';
                    $v['pic_url'] = isset($v['detail'][0]['pic_url']) ? $v['detail'][0]['pic_url'] : '';
                    unset($v['detail']);
                    $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
                    $v['url'] = U('Order/detail', array('order_sn' => $v['order_sn']));
                    $lists['lists'][$k] = $v;
                }
            }
            $this->ajaxReturn(array('status' => 1, 'data' => $lists['lists']));
            exit;
        }
        $this->display();
    }
    
    public function detail(){
        $order_sn = I('order_sn');
        if(!$order_sn){
            $this->error('订单不存在');
        }
        $data = D('Addons://Order/Order')->get_info($order_sn, array(), $this->uid);
        if(!$data){
            $this->error('订单不存在');
        }elseif($data == -1){
            $this->error('订单已被其他用户绑定');
        }
        if(in_array($data['status'], array(2, 5))){
            redirect(U('Order/pay_success', array('order_sn' => $order_sn)));
        }elseif($data['status'] == 3){
            redirect(U('Order/pay_fail', array('order_sn' => $order_sn)));
        }
        switch(APP_TYPE){
            case 'alipay':
                $tpl = 'detail_alipay';
                break;
            case 'wechat':
                $tpl = 'detail_wechat';
                break;
            default:
                exit;
                break;
        }
        if(!empty($data['cash_code'])){
            $cash_code = $data['cash_code'];
        }else{
            $cash_code = I('cash_code', '', 'trim');
        }
        $cash = array();
        if($cash_code){
            $Api = new \User\Client\Api();
            $req = $Api->execute('CashCoupon', 'coupon_info', array('code' => $cash_code, 'uid' => $this->uid));
            if($req['status'] == 1){
                $cash = $req['data'];
            }
        }
        $cash_money = $cash ? $cash['cash_money'] : 0;
        $pay_money = $data['money']-$cash_money;
        $pay_money < 0 && $pay_money = 0;
        
        $data['cash'] = $cash;
        $data['discount_money'] = $cash_money;
        $data['pay_money'] = $pay_money;
        
        $this->assign('info', $data);
        $this->display($tpl);
    }
    
    public function pay(){
        $order_sn = I('get.order_sn', '');
        $cash_code = I('cash_code', '');
        // 暂时取消使用优惠券
        $cash_code = '';
        if(!$order_sn){
            $this->error('未知的订单', U('Order/lists'));
        }
        $info = D('Addons://Order/Order')->get_info($order_sn, array(), $this->uid);
        if(!$info){
            $this->error('订单不存在', U('Order/lists'));
        }elseif(in_array($info['status'], array(2, 5))){
            $this->error('订单已支付', U('Order/pay_success', array('order_sn' => $order_sn)));
        }elseif($info['status'] == 3){
            $this->error('订单已支付失败', U('Order/pay_fail', array('order_sn' => $order_sn)));
        }
        
        $pay_money = D('Addons://Order/Order')->get_pay_money($order_sn, $this->uid, $cash_code, APP_TYPE);
        if($pay_money == -1){
            $this->error('订单信息有误，请重新操作');
        }
        if(D('Addons://Order/Order')->check_goods($order_sn, $info['store_id'], $info['detail']) == false){
            $this->error('商品库存不足，订单已取消', U('Order/pay_fail', array('order_sn' => $order_sn)));
        }
        if($pay_money == 0){
            $result = D('Addons://Order/Order')->set_pay($order_sn, APP_TYPE, $order_sn, '');
            if(!$result){
                $this->error('订单信息有误，请重新操作');
            }
            $this->ajaxReturn(array('status' => 2, 'info' => '结算成功', 'url' => U('Order/pay_success', array('order_sn' => $order_sn))));
        }
        switch(APP_TYPE){
            case 'alipay':
                $data = array(
                    'sn' => $order_sn,
                    'total_fee' => $pay_money,
                    'body' => json_encode(array('order_sn' => $order_sn, 'store_id' => $this->_store_id, 'pos_id' => $this->_pos_id)),
                    'subject' => '神秘商店订单',
                    'show_url' => ''
                );
                $url = A('Addons://Alipay/F2fpayclass')->mobile_pay($data, U('Api/Public/ali_pay_notify'));
                $this->ajaxReturn(array('status' => 1, 'url' => $url));
                break;
            case 'wechat':
                $wx_data = array(
                    'body' => '神秘商店订单',
                    'attach' => json_encode(array('order_sn' => $order_sn, 'store_id' => $this->_store_id, 'pos_id' => $this->_pos_id)),
                    'fee' => $pay_money*100,
                    //'fee' => 1,
                    'sn' => $order_sn,
                    'openid' => $this->app_data_id,
                    'trade_type' => 'JSAPI',
                );
                $config = M('Store')->where(array('id' => $info['store_id']))->find();
                $config = json_decode($config['pay'], true);
                A('Addons://WechatPay/WechatPayclass')->set_config(array('mchid' => $config['wx']['mchid'], 'key' => $config['wx']['key'], 'appid' => $config['wx']['appid'], 'appsecret' => $config['wx']['appsecret']));
                $wx_pay_data = A('Addons://WechatPay/WechatPayclass')->unifiedorder($wx_data, U('Api/Public/wx_pay_notify'));
                if($wx_pay_data['status'] != 1){
                    $this->error($wx_pay_data['msg']);
                }
                $this->assign('jsApiParameters', $wx_pay_data['data']);
                $this->assign('info', $info);
                $html = $this->fetch('wechat_pay_js');
                $this->ajaxReturn(array('status' => 1, 'info' => $html));
                break;
            default:
                break;
        }
    }
    
    public function pay_success(){
        $order_sn = I('order_sn');
        $data = D('Addons://Order/Order')->get_info($order_sn, array('uid' => $this->uid));
        if(!$data){
            $this->error('订单不存在');
        }
        if($data['status'] == 1){
            redirect(U('Order/detail', array('order_sn' => $order_sn)));
        }elseif($data['status'] == 3){
            redirect(U('Order/pay_fail', array('order_sn' => $order_sn)));
        }
        
        $Api = new \User\Client\Api();
        $cash = array();
        if($data['cash_code']){
            $req = $Api->execute('CashCoupon', 'coupon_info', array('code' => $data['cash_code'], 'uid' => $this->uid));
            if($req['status'] == 1){
                $cash = $req['data'];
            }
        }
        $data['cash'] = $cash;
        $data['pic_url'] = isset($data['detail'][0]['pic_url']) ? $data['detail'][0]['pic_url'] : '';
        $data['discount_money'] = $data['money'] - $data['pay_money'];
        
        
        // 是否加盟商订单
        $isJm = $this->isJm($data['store_id']);
        $isJm = empty($isJm) ? 0 : 1;        
        $data['is_jm'] = $isJm;
        
        
        
        $this->assign('info', $data);
        $req = $Api->execute('CashCoupon', 'get_lottery', array('order_sn' => $order_sn, 'uid' => $this->uid));
        if($req['status'] == 1){
            $lottery = $req['data'];
        }else{
            $lottery = array();
        }
        if($lottery){
            $pay_share = share_config('pay_share');
            $pay_share['url'] = U('CashCoupon/lottery_coupon', array('cash_code' => $lottery['code']));
            $this->assign('pay_share', $pay_share);
        }
        $this->display();
    }
    
    
    // 是否加盟商
    private function isJm($store_id)
    {
        if (empty($store_id)) {
            return false;
        } else {
        
            $store = M('store')->where(array('id' => $store_id))->find();
            if (empty($store) || empty($store['shequ_id'])) {//shequ_id
                return false;
            } else {
                $isTest = $this->isTest();
                if (($isTest && $store['shequ_id'] == 16) || (!$isTest && $store['shequ_id'] == 18)) {
                    return true;
                } else {
                    return false;
                }
            }
        }        
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
    
    
    
    
    public function pay_fail(){
        $order_sn = I('order_sn');
        $data = D('Addons://Order/Order')->get_info($order_sn, array('uid' => $this->uid));
        if(!$data){
            $this->error('订单不存在');
        }
        if($data['status'] == 1){
            redirect(U('Order/detail', array('order_sn' => $order_sn)));
        }elseif($data['status'] == 2){
            redirect(U('Order/pay_success', array('order_sn' => $order_sn)));
        }
        
        $cash = array();
        if($data['cash_code']){
            $Api = new \User\Client\Api();
            $req = $Api->execute('CashCoupon', 'coupon_info', array('code' => $data['cash_code'], 'uid' => $this->uid));
            if($req['status'] == 1){
                $cash = $req['data'];
            }
        }
        $data['cash'] = $cash;
        $data['pic_url'] = isset($data['detail'][0]['pic_url']) ? $data['detail'][0]['pic_url'] : '';
        $data['discount_money'] = $data['money'] - $data['pay_money'];
        $this->assign('info', $data);
        $this->display();
    }
}
