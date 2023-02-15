<?php
// +----------------------------------------------------------------------
// | Title: 订单
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | Type: 客户端
// +----------------------------------------------------------------------
namespace Apiv2\Controller;

class OrderController extends ApiController {



    public function wallet_info()
    {
        $this->check_account_token();
        $money = $this->wallet_money();
        $this->return_data(1, $money);

    }


    // 可用余额
    private function wallet_money()
    {
        $this->check_account_token();
        $data = $this->uc_api('Wallet', 'info', array('uid' => $this->_uid));

        if (empty($data['money']) && empty($data['recharge_money'])) {
            $money = '0.00';
        } else {
            $money = $data['money'] + $data['recharge_money'];
            $money = number_format($money, 2, '.', '');
        }
        return $money;
    }


    // 用户可用余额
    private function wallet_balance()
    {
        $this->check_account_token();
        $data = $this->uc_api('Wallet', 'info', array('uid' => $this->_uid));

        if (empty($data['money']) && empty($data['recharge_money'])) {
            $result = array(
                'all_money' => 0,
                'money' => 0,
                'recharge_money' => 0,
            );
        } else {
            $all_money = $data['money'] + $data['recharge_money'];
            $all_money = number_format($all_money, 2, '.', '');

            $result = array(
                'all_money' => $all_money,
                'money' => $data['money'],
                'recharge_money' => $data['recharge_money'],
            );
        }
        return $result;
    }


    // 余额支付
    private function balancePay($uid, $order_sn, $pay_money)
    {
        // 查询余额
        $data = $this->wallet_balance();

        // 余额不够保用
        if ($data['all_money'] < $pay_money) {
            return array(false, '余额不足以支付');
        }


        $set_data = array();

        // 充值金额不足以支付 
        if ($data['recharge_money'] < $pay_money) {
            $recharge_money = $data['recharge_money'];
            $money = $pay_money - $data['recharge_money'];

            $set_data['money'] = array('exp', 'money-'.$money);
            $set_data['all_money'] = array('exp', 'all_money-'.$money);
            $set_data['recharge_money'] = array('exp', 'recharge_money-'.$recharge_money);
        } else {
            $recharge_money = $pay_money;
            $money = 0;

            $set_data['recharge_money'] = array('exp', 'recharge_money-'.$recharge_money);
        }




        // 调整余额值
        $res = M('wallet')->where(array(
            'uid' => $uid
        ))->save($set_data);



        // 将记录加到余额列表
        if ($res) {
            $time = time();
            $action = 'orderpay';
            $data = array(
                'uid' => $uid,
                'money' => $pay_money,
                'give' => 0,
                'type' => 4,
                'action' => $action,
                'action_sn' => $order_sn,
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            );
            M('wallet_log')->add($data);

            return array(true, '余额支付成功');
        } else {
            return array(false, '余额支付失败');
        }

    }






    /**
     * @name  add_order
     * @title  添加订单
     * @param  int     $shop_uid    卖家ID（若是购买网上门店的商品，则不传值）
     * @param  string  $goods       商品信息json数据，支持多个（id:商品ID, num:数量 格式：[{"id": 1,"num":5}]）
     * @param  int     $rid         收货地址ID
     * @param  string  $pay_type    支付方式(wechat:微信 alipay:支付宝)
     * @param  string  $cash_code   优惠券号（可为空）
     * @param  string  $remark      备注信息
     * @param  string  $token
     * @return 返回参数见接口 Order/pay_order
     * @remark
     */
    public function add_order(){
        $this->check_account_token();
        $this->_check_param(array('goods', 'rid', 'pay_type'));
        $goods = I('goods', '', 'trim');
        $rid = I('rid', 0, 'intval');
        $cash_code = I('cash_code', '', 'trim');
        $pay_type = I('pay_type', '', 'trim');
        $shop_uid = I('shop_uid', 0, 'intval');
        $remark = I('remark', 0, 'trim');
        $goods = json_decode($goods, true);
        if(!$goods){
            $this->return_data(0, '', '未选择商品');
        }
        if(!in_array($pay_type, array('wechat', 'alipay', 'balance'))){
            $this->return_data(0, '', '请选择支付方式');
        }
        if(!is_array($goods)){
            $this->return_data(0, array(), '提交订单失败：订单商品数据错误');
        }
        if(!$rid){
            $this->return_data(0, '', '请选择收货地址');
        }
        if($shop_uid > 0){
            $type = 'shop';
            if($shop_uid == $this->_uid){
                $this->return_data(0, '', '不能购买自己的商品');
            }
        }else{
            $type = 'online';
        }
        $receipt_data = D('Common/UserReceipt')->get_info($rid, $this->_uid);
        if(!$receipt_data){
            $this->return_data(0, '', '收货地址无效');
        }


        // 检查商品库存
        //$this->numCheck($goods, $shop_uid);


        $cash_code = '';
        $data = D('Addons://Order/Order')->add_order($type, $shop_uid, $this->_uid, $goods, $receipt_data, $cash_code, 'account_app', $remark);
        if(!$data){
            $error = D('Addons://Order/Order')->getError();
            !$error && $error = '提交订单失败';
            $this->return_data(0, array(), $error);
        }
        // 删除购物车商品
        $gids = array();
        foreach($goods as $v){
            $gids[] = array('id' => $v['id']);
        }
        $api = new \Addons\Order\Lib\CartLib($this->_uid);
        $api->del_cart_by_data($gids);


        // 减少库存
        $this->numDec($data['order_sn']);

        // 设置订单号
        $_REQUEST['order_sn'] = $data['order_sn'];
        if(IS_GET){
            $_GET['order_sn'] = $data['order_sn'];
        }else{
            $_POST['order_sn'] = $data['order_sn'];
        }
        $this->pay_order();
    }


    // 检测库存
    /*
    private function numCheck($goods, $store_id)
    {
        $goods_ids = array();
        foreach($goods as $k => $v){
            if(empty($v['id']) || empty($v['num'])){
                unset($goods[$k]);
            }
            $v['id'] = intval($v['id']);
            $v['num'] = intval($v['num']);
            if($v['id'] <= 0 || $v['num'] <= 0){
                unset($goods[$k]);
            }else{
                $goods_ids[] = $v['id'];
            }
        }
        if(empty($goods_ids)){
            $this->return_data(0, '', '选择商品无效');
        }        
        
        $goods_info = M('ShopGoods')->where(array('id' => array('in', $goods_ids), 'uid' => $store_id, 'status' => 1, 'is_shelf' => 1))->select();
        $goods_info = reset_data($goods_info, 'id');

        foreach ($goods as $v) {
            if(!isset($goods_info[$v['d_id']]) || $goods_info[$v['d_id']]['num'] < $v['num']){
                $this->return_data(0, '', '商品库存不足');
            }
        }

        return true;        
    }
    */

    // 增加库存
    private function numInc($order_sn)
    {
        if (empty($order_sn)) {
            return false;
        }

        $products = M('OrderDetail')->where(array(
            'order_sn' => $order_sn,
        ))->select();

        foreach ($products as $key => $val) {
            M('ShopGoods')->where(array(
                'id' => $val['d_id'],
            ))->SetInc('num', $val['num']);
        }


    }

    // 减少库存
    private function numDec($order_sn)
    {
        if (empty($order_sn)) {
            return false;
        }

        $products = M('OrderDetail')->where(array(
            'order_sn' => $order_sn,
        ))->select();

        foreach ($products as $key => $val) {
            M('ShopGoods')->where(array(
                'id' => $val['d_id'],
            ))->SetDec('num', $val['num']);
        }
    }


    /**
     * @name pay_order
     * @title 获取支付信息
     * @param string $order_sn  订单号
     * @param string $pay_type  支付方式(wechat => 微信 alipay => 支付宝)
     * @param string $cash_code 可用的优惠券号(默认为空，第一次结算时传递有效)
     * @param  string  $token
     * @return [wechat_data] => (<br>
    &nbsp;&nbsp;[appid] => 应用ID<br>
    &nbsp;&nbsp;[partnerid] => 商户号<br>
    &nbsp;&nbsp;[prepayid] => 预支付交易会话ID<br>
    &nbsp;&nbsp;[timestamp] => 时间戳<br>
    &nbsp;&nbsp;[wx_package] =>  扩展字段<br>
    &nbsp;&nbsp;[noncestr] => 随机字符串<br>
    &nbsp;&nbsp;[sign] => 签名）<br>
    [alipay_data] => (<br>
    &nbsp;&nbsp;[server] => 接口名称<br>
    &nbsp;&nbsp;[partner] => 合作者身份ID <br>
    &nbsp;&nbsp;[_input_charset] => 参数编码字符集<br>
    &nbsp;&nbsp;[sign_type] => 签名方式<br>
    &nbsp;&nbsp;[sign] => 签名<br>
    &nbsp;&nbsp;[notify_url] => 服务器异步通知页面路径 <br>
    &nbsp;&nbsp;[app_id] => 客户端号<br>
    &nbsp;&nbsp;[appenv] => 客户端来源<br>
    &nbsp;&nbsp;[out_trade_no] => 商户网站唯一订单号<br>
    &nbsp;&nbsp;[payment_type] => 支付类型<br>
    &nbsp;&nbsp;[subject] => 商品名称<br>
    &nbsp;&nbsp;[seller_id] => 卖家支付宝账号<br>
    &nbsp;&nbsp;[total_fee] => 总金额<br>
    &nbsp;&nbsp;[body] => 商品详情<br>
    &nbsp;&nbsp;[goods_type] => 商品类型<br>
    &nbsp;&nbsp;[rn_check] => 是否发起实名校验<br>
    &nbsp;&nbsp;[it_b_pay] => 未付款交易的超时时间<br>
    &nbsp;&nbsp;[extern_token] => 授权令牌<br><br>
    [order_sn] => 订单号<br>
     * @remark  当返回的status = 2时， 表示当前的订单自动支付成功，无需调用支付sdk。 <br>
    当返回的status = 3时， 表示当前的订单已被取消。 <br>
     */
    public function pay_order(){
        $this->check_account_token();
        $this->_check_param(array('order_sn', 'pay_type'));
        $order_sn = I('order_sn', '', 'trim');
        $pay_type = I('pay_type', '', 'trim');
        $paypwd = I('paypwd', '', 'trim');
        $cash_code = I('cash_code', '', 'trim');
        if(!$order_sn){
            $this->return_data(0, '', '未知订单');
        }
        if(!$pay_type){
            $this->return_data(0, '', '未知的支付方式');
        }
        $data = D('Addons://Order/Order')->get_info($order_sn, array(), $this->_uid, 'order_sn, uid,create_time,pay_status, pay_money, store_id, type');
        if(!$data){
            $this->return_data(0, '', '订单不存在');
        }elseif($data['uid'] != $this->_uid){
            $this->return_data(0, '', '订单已被其他用户绑定');
        }elseif($data['status'] == 2){
            $this->return_data(0, '', '订单已支付');
        }elseif($data['status'] == 3){
            $this->return_data(3, '', '订单已支付失败');
        }

        if ($data['type'] != 'store') {
            $cash_code = '';
        }
        $pay_money = D('Addons://Order/Order')->get_pay_money($order_sn, $this->_uid, $cash_code, 'account_app');
        if($pay_money == -1){
            $this->return_data(0, '', '订单信息有误，请重新操作');
        }
        /*
        if(D('Addons://Order/Order')->check_goods($order_sn, $data['store_id'], $data['detail'], $data['type']) == false){
            $this->return_data(3, '', '商品库存不足，订单已取消');
        }
        */
        if($pay_money == 0){
            $result = D('Addons://Order/Order')->set_pay($order_sn, $pay_type, $order_sn, '');
            if(!$result){
                $this->return_data(0, '', '订单信息有误，请重新操作');
            }
            $this->return_data(2, array(
                'order_sn' => $order_sn,
            ), '支付成功');
        }
        $result = array(
            'order_sn' => $order_sn,
            'pay_money' => $pay_money
        );

        // 非店铺不许余额支付
        /*
        if ($data['type'] != 'store' && $pay_type == 'balance') {
            $this->return_data(0, '', '不合法的支付方式');
        }
        */


        // 余额支付
        if ($pay_type == 'balance') {

            // 验证支付密码
            if (empty($paypwd)){
                $this->return_data(0, '', '支付密码不能为空');
            }

            if (!preg_match('/^\d{6}$/', $paypwd)){
                $this->return_data(0, '', '支付密码设置不合法');
            }

            $uid = $this->_uid;

            if (empty($uid)) {
                $this->return_data(0, '', '获取用户信息异常');
            }


            $pay_pwd = md5($paypwd);


            $one = M('wallet')->where(array(
                'uid' => $uid
            ))->find();

            if (empty($one)) {
                $this->return_data(0, '', '获取用户信息异常err-002');
            }

            if ($one['pay_pwd'] != $pay_pwd) {
                $this->return_data(0, '', '支付密码错误');
            }


            $presult = $this->balancePay($this->_uid, $order_sn, $pay_money);

            if (empty($presult[0])) {
                $this->return_data(0, '', empty($presult[1]) ? '余额支付失败' : $presult[1]);
            } else {
                $result = D('Addons://Order/Order')->set_pay($order_sn, 5, $order_sn, '');
                if(!$result){
                    $this->return_data(0, '', '订单信息有误，请重新操作');
                }
                $this->return_data(2, array(
                    'order_sn' => $order_sn,
                ), '余额支付成功');
            }

        }

        switch($pay_type){
            case 'wechat':
                $wx_data = array(
                    'body' => '神秘商店订单',
                    'attach' => json_encode(array('order_sn' => $order_sn, 'store_id' => $data['store_id'], 'pos_id' => $data['pos_id'])),
                    'fee' => $pay_money*100,
                    //'fee' => 1,
                    'sn' => $order_sn,
                    'trade_type' => 'APP',
                );
                A('Addons://WechatPay/WechatPayclass')->set_config('app');
                $wx_pay_data = A('Addons://WechatPay/WechatPayclass')->unifiedorder($wx_data, U('Api/Public/wx_pay_notify'));

                $result['wechat_data'] = array();
                if($wx_pay_data['status'] == 1){
                    $wx_signdata = array(
                        'appid' => $wx_pay_data['data']['appid'],
                        'partnerid' => $wx_pay_data['data']['mch_id'],
                        'prepayid' => $wx_pay_data['data']['prepay_id'],
                        'timestamp' => time(),
                        'package' => 'Sign=WXPay',
                        'noncestr' => $wx_pay_data['data']['nonce_str'],
                    );
                    $wx_sign = A('Addons://WechatPay/WechatPayclass')->MakeSign($wx_signdata);
                    $wx_signdata['sign'] = $wx_sign;
                    $wx_signdata['wx_package'] = $wx_signdata['package'];
                    unset($wx_signdata['package']);
                    $result['wechat_data'] = $wx_signdata;
                }
                $this->return_data(1, $result);
                break;
            case 'alipay':
                $ali_data = array(
                    'subject' => '神秘商店订单',
                    'body' => json_encode(array('order_sn' => $order_sn, 'store_id' => $data['store_id'], 'pos_id' => $data['pos_id'])),
                    'total_fee' => $pay_money,
                    //'total_fee' => 0.01,
                    'goods_detail' => array(),
                    'sn' => $order_sn,
                    'it_b_pay' => '15d',
                );
                //A('Addons://Alipay/F2fpayclass')->set_config();
                $alipay_data = A('Addons://Alipay/F2fpayclass')->app_order($ali_data, U('Api/Public/ali_pay_notify'));
                $result['alipay_data'] = $alipay_data;
                $this->return_data(1, $result);
                break;
            default:
                $this->return_data(0, '', '请选择支付方式');
        }
    }
    /**
     * @name get_goods_order
     * @title 获取商品在确认购买时的信息
     * @param  int    $goods_id   商品ID
     * @param  int    $num        数量
     * @param  string $token   用户token
     * @return [store_title] => 门店名<br>[goods_data] => 商品信息(<br>[id] => 商品id<br>[title] => 商品标题<br>[pic_url] => 图片地址<br>[price] => 价格<br>[num] => 购买数量 <br>[goods_money] => 商品总金额 <br>)<br>
    [receipt_info] =>收货信息,没有时为空<br>(<br>[id] => 地址ID<br>[name] => 收货人<br>[mobile] => 联系方式<br>[sheng_title] => 省份文本<br>[shi_title] => 城市文本<br>[qu_title] => 地区文本<br>[address] => 地址<br>)<br>
    [express_money] => 运费<br>[pay_money] => 总支付金额<br>[user_discount_money] => 会员优惠<br>[user_sale] => 会员折扣<br>[type] => 要确认的订单类型（store:线下 online：线上）

     * @remark 用于在APP确认购买后显示的商品订单信息，只适用于系统门店
     */
    public function get_goods_order(){
        $this->check_account_token();
        $this->_check_param(array('goods_id', 'num'));
        $goods_id = I('goods_id', 0, 'intval');
        $num = I('num', 1, 'intval');
        if(!$goods_id){
            $this->return_data(0, '', '商品不存在');
        }

        $receipt_data = $this->uc_api('Receipt', 'get_default', array('uid' => $this->_uid));

        $store_id = C('STORE_ONLINE');
        $goods_data = M('Goods')->alias('a')->join('__GOODS_STORE__ as b ON a.id = b.goods_id')->where(array('a.status' => 1, 'b.store_id' => $store_id, 'a.id' => $goods_id))->field('a.id,a.title,a.cover_id,a.sell_price,b.num,b.price')->find();
        if(!$goods_data){
            $this->return_data(0, '', '商品不存在');
        }
        if($goods_data['num'] < $num){
            $this->return_data(0, '', '商品库存不足');
        }
        $goods_data['pic_url'] = get_cover_url($goods_data['cover_id']);
        $goods_data['price'] = empty($goods_data['price']) ? $goods_data['sell_price'] : $goods_data['price'];
        unset($goods_data['sell_price'],$goods_data['cover_id']);
        $goods_data['num'] = $num;
        $pay_money = $goods_data['goods_money'] = $goods_data['price']*$num;

        // 快递费用
        //$express_money = D('Addons://Order/Order')->get_express_money($pay_money, array('sheng' => isset($receipt_data['sheng']) ? $receipt_data['sheng'] : 0, 'goods_id' => $goods_id));
        $express_money = 0;
        $pay_money += $express_money;

        $level = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
        $user_sale = (100-$level['level_sale'])/100;
        $n_money = round($pay_money*$user_sale, 2);
        $user_discount_money = round($pay_money - $n_money, 2);
        $pay_money = $n_money;

        $store = M('Store')->where(array('id' => $store_id))->find();

        $result = array(
            'store_title' => isset($store['title']) ? $store['title'] : '',
            'goods_data' => $goods_data,
            'receipt_info' => $receipt_data ? array(
                'id' => isset($receipt_data['id']) ? $receipt_data['id'] : 0,
                'name' => $receipt_data['name'],
                'mobile' => $receipt_data['mobile'],
                'zip_code' => $receipt_data['zip_code'],
                'sheng' => $receipt_data['sheng'],
                'shi' => $receipt_data['shi'],
                'qu' => $receipt_data['qu'],
                'address' => $receipt_data['address'],
                'sheng_title' => $receipt_data['sheng_title'],
                'shi_title' => $receipt_data['shi_title'],
                'qu_title' => $receipt_data['qu_title'],
            ) : (object)array(),
            'express_money' => $express_money,
            'pay_money' => $pay_money,
            'user_discount_money' => $user_discount_money,
            'user_sale' => $user_sale,
            'type' => 'online',
        );
        $this->return_data(1, $result);
    }
    /**
     * @name get_shop_goods_order
     * @title 商品确认订单信息（仅支持店铺商品）
     * @param  string  $goods       商品信息json数据，支持多个同店铺的商品（goods_id:商品ID, num:数量 格式：[{"goods_id": 1,"num":5}]）
     * @param  int    $shop_uid     店铺用户ID
     * @param  string $token   用户token
     * @return [shop_data] => 店铺信息（<br>[uid] => 店铺用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>）<br>[goods_data] => 商品信息数组(<br>[goods_id] => 商品id<br>[goods_title] => 商品标题<br>[goods_pic] => 图片地址<br>[price] => 价格<br>[num] => 购买数量 <br>[goods_money] => 商品金额 <br>)<br>
    [receipt_info] =>收货信息,没有时为空<br>(<br>[id] => 地址ID<br>[name] => 收货人<br>[mobile] => 联系方式<br>[sheng_title] => 省份文本<br>[shi_title] => 城市文本<br>[qu_title] => 地区文本<br>[address] => 地址<br>)<br>
    [express_money] => 运费<br>[pay_money] => 总支付金额<br><br>[goods_money] => 商品总金额[user_discount_money] => 会员优惠<br>[user_sale] => 会员折扣<br>[goods_num] => 商品总数量<br>

     * @remark
     */
    public function get_shop_goods_order(){
        $this->check_account_token();
        $this->_check_param(array('goods', 'shop_uid'));
        $goods = I('goods', '', 'trim');
        $goods = json_decode($goods, true);
        $shop_uid = I('shop_uid', 0, 'intval');
        if(!$goods){
            $this->return_data(0, '', '未选择商品');
        }
        if(!($shop_uid > 0)){
            $this->return_data(0, '', '请选择店铺');
        }
        $shop_data = M('Shop')->where(array('uid' => $shop_uid))->field('uid')->find();
        if(!$shop_data){
            $this->return_data(0, '', '店铺不存在');
        }
        $shop_data['nickname'] = get_nickname($shop_uid);
        $shop_data['header_pic'] = get_header_pic($shop_uid);
        $goods_ids = array();
        $_goods = reset_data($goods, 'goods_id');
        foreach($goods as $v){
            !empty($v['goods_id']) && $goods_ids[] = $v['goods_id'];
        }
        if(!$goods_ids){
            $this->return_data(0, '', '请选择商品');
        }
        $goods_data = M('ShopGoods')->where(array('id' => array('in', $goods_ids), 'uid' => $shop_uid, 'status' => 1, 'is_shelf' => 1))->field('id,pic,title,num,express_money,price')->select();
        if(!$goods_data){
            $this->return_data(0, '', '选择的商品无效');
        }
        // 快递费用
        $express_money = 0;
        $pay_money = 0;
        $goods_money = 0;
        $goods_num = 0;
        foreach($goods_data as $k => $v){
            $item = $_goods[$v['id']];
            if($v['num'] < $item['num']){
                $this->return_data(0, '', '商品'.$v['title'].'库存不足');
            }
            $v['pic_url'] = get_cover_url($v['pic']);
            $express_money += $v['express_money'];
            $v['goods_money'] = $v['price'] * $item['num'];
            $goods_money += $v['goods_money'];
            $goods_num += $item['num'];
            $_item = array(
                'goods_id' => $v['id'],
                'goods_title' => $v['title'],
                'goods_pic' => $v['pic_url'],
                'price' => $v['price'],
                'num' => $item['num'],
                'goods_money' => $v['goods_money'],
            );
            $goods_data[$k] = $_item;
        }
        $pay_money += $goods_money;
        $pay_money += $express_money;

        $receipt_data = $this->uc_api('Receipt', 'get_default', array('uid' => $this->_uid));

        $level = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
        $user_sale = (100-$level['level_sale'])/100;
        $n_money = round($pay_money*$user_sale, 2);
        $user_discount_money = round($pay_money - $n_money, 2);
        $pay_money = $n_money;



        // 用户可用余额
        $balance = $this->wallet_money();


        $result = array(
            'shop_data' => $shop_data,
            'goods_data' => $goods_data,
            'goods_num' => $goods_num,
            'receipt_info' => $receipt_data ? array(
                'id' => isset($receipt_data['id']) ? $receipt_data['id'] : 0,
                'name' => $receipt_data['name'],
                'mobile' => $receipt_data['mobile'],
                'zip_code' => $receipt_data['zip_code'],
                'sheng' => $receipt_data['sheng'],
                'shi' => $receipt_data['shi'],
                'qu' => $receipt_data['qu'],
                'address' => $receipt_data['address'],
                'sheng_title' => $receipt_data['sheng_title'],
                'shi_title' => $receipt_data['shi_title'],
                'qu_title' => $receipt_data['qu_title'],
            ) : (object)array(),
            'express_money' => $express_money,
            'pay_money' => $pay_money,
            'goods_money' => $goods_money,
            'user_discount_money' => $user_discount_money,
            'user_sale' => $user_sale,
            'balance' => $balance,
        );
        $this->return_data(1, $result);
    }
    /**
     * @name get_express_money
     * @title 获取运费
     * @param  int  $goods_id   商品ID
     * @param  int  $sheng      省份ID
     * @return  [express_money] => 运费
     * @remark
     */
    public function get_express_money(){
        $this->_check_param(array('goods_id', 'sheng'));
        $goods_id = I('goods_id', 0);
        $sheng = I('sheng', 0);
        $express_money = D('Addons://Order/Order')->get_express_money(0, array('sheng' => $sheng, 'goods_id' => $goods_id));
        $this->return_data(1, array('express_money' => $express_money));
    }
    /**
     * @name  order_lists
     * @title  我的订单列表
     * @param int $page   页码（默认为1）
     * @param int $utype 对象：1 买家 2 卖家（默认为1）
     * @param int $type   类型（默认为0 0：全部 1：待付款 2：待发货 3：待收货  4：已完成 5：待退款）
     * @param  string  $token
     * @return [order_sn] => 订单号 <br> [create_time] =>创建时间<br> [create_time_text] =>创建时间文本 <br>[money] => 总价<br>
    [pic_url] => 图片地址<br>[store_title] => 门店名/卖家昵称<br>[store_pic] => 门店图标（目前为空）/卖家头像<br>[pay_status]支付状态：1 未支付 2 已支付 <br>[status] => 订单状态 1 未支付 2 已支付 3 已取消 4 已发货 5 已完成 6 已退款<br>
    [title] => 商品名<br>[pay_money] => 实际支付<br>[cash_money] => 优惠金额<br>[user_discount_money] => 会员优惠金额<br>[express_money] => 运费<br>[num] => 数量<br>[last_pay_second] => 剩余支付秒数(未支付时有效)<br>[last_pay_time] => 最后支付的时间戳<br>
    [type] => 订单类型：（store:门店订单[线下] online:线上 shop:用户店铺订单）<br>[is_assess] => 是否已评价 0 否 1 是[goods_total_num] => 商品总数量<br>[goods_total_money] => 商品总价<br>
    [detail] => 商品信息数组（<br>[title] => 商品名<br>[pic_url] => 商品图片<br>[price] => 价格<br>[num] => 数量<br>[d_id] => 商品ID<br>[goods_num] => 当前库存<br>）
    [refund_status] => 退款状态（0 未申请 1 已申请 2 已通过退款中 3 已拒绝 4 退款成功）<br>[refund_money] => 退款金额<br>[uid] => 买家用户ID<br>[nickname] => 买家昵称<br>[header_pic] => 买家头像<br>
    [cashcoupon_title] => 已绑定的优惠券标题<br>[user_sale] => 当前会员优惠折扣（0~1）
     * @remark
     */
    public function order_lists(){
        $this->check_account_token();
        $page = I('page', 0, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $type = I('type', 0, 'intval');
        $utype = I('utype', 1, 'intval');
        $where = $utype == 2 ? array('store_id' => $this->_uid, 'type' => 'shop') : array('uid' => $this->_uid, 'is_del' => 0);
        switch($type){
            case 1:
                $where['pay_status'] = 1;
                $where['status'] = 1;
                break;
            case 2:
                $where['refund_status'] = array('in', '0,3');
                $where['pay_status'] = 2;
                $where['status'] = 2;
                break;
            case 3:
                $where['status'] = 4;
                break;
            case 4:
                $where['status'] = array('in', array(5,6));
                break;
            case 5:
                $where['refund_status'] = 1;
                $where['pay_status'] = 2;
                $where['status'] = 2;
                break;
            default:
                $where['status'] = array('neq', 3);
                break;
        }
        $lists = D('Addons://Order/Order')->lists($where, 'order_sn,uid,create_time,money,pay_money,express_money,user_discount_money,cash_money,pay_status,status,store_id,type,is_assess,refund_status,refund_money,cash_code', $page, $row, true);
        if(!empty($lists['lists'])){
            $store_id = array();
            $goods_ids = array();
            $refund_sn = array();
            $cash_code = array();
            foreach($lists['lists'] as $v){
                if($v['type'] != 'shop'){
                    $store_id[] = $v['store_id'];
                }else{
                    if($utype == 2){
                        foreach($v['detail'] as $dv){
                            $goods_ids[] = $dv['d_id'];
                        }
                    }
                }
                if($v['refund_status'] == 1){
                    $refund_sn[] = $v['order_sn'];
                }
                $cash_code[] = $v['cash_code'];
            }
            if($store_id){
                $store = M('Store')->where(array('id' => array('in', $store_id)))->field('id,title')->select();
                foreach($store as $v){
                    $_store[$v['id']] = $v;
                }
            }
            if($goods_ids){
                $shop_data = reset_data_field(M('ShopGoods')->where(array('id' => array('in', $goods_ids), 'uid' => $this->_uid))->field('id,num')->select(), 'id', 'num');
            }
            if($refund_sn){
                $refund_data = reset_data_field(M('OrderRefund')->where(array('order_sn' => array('in', $refund_sn), 'status' => 1))->field('order_sn,money')->select(), 'order_sn', 'money');
            }
            $cash_data = $cash_code ? reset_data_field($this->uc_api('CashCoupon', 'user_coupon_by_code', array('code' => $cash_code, 'field' => 'code,title')), 'code', 'title') : array();
            $scorebox = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
            $user_sale = (100-$scorebox['level_sale'])/100;
            foreach($lists['lists'] as $k => $v){
                if($v['type'] == 'shop'){
                    $v['store_title'] = get_nickname($v['store_id']);
                    $v['store_pic'] = get_header_pic($v['store_id']);
                }else{
                    $v['store_title'] = isset($_store[$v['store_id']]['title']) ? $_store[$v['store_id']]['title'] : '';
                    $v['store_pic'] = get_store_header($v['store_id']);
                }
                $v['username'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $v['title'] = isset($v['detail'][0]['title']) ? $v['detail'][0]['title'] : 0;
                $v['pic_url'] = isset($v['detail'][0]['pic_url']) ? $v['detail'][0]['pic_url'] : '';
                $v['num'] = isset($v['detail'][0]['num']) ? $v['detail'][0]['num'] : 0;
                $goods_num = 0;
                $goods_total_money = 0;
                foreach($v['detail'] as $dk => $dv){
                    $goods_num += $dv['num'];
                    $goods_total_money += $dv['price']*$dv['num'];
                    $dv['goods_num'] = isset($shop_data[$dv['d_id']]) ? $shop_data[$dv['d_id']] : 0;
                    $v['detail'][$dk] = $dv;
                }
                $v['goods_total_num'] = $goods_num;
                $v['goods_total_money'] = sprintf("%.2f", round($goods_total_money, 2));
                $v['create_time_text'] = date('Y-m-d H:i', $v['create_time']);
                !empty($refund_data[$v['order_sn']]) && $v['refund_money'] = $refund_data[$v['order_sn']];
                $v['cashcoupon_title'] = ($v['cash_code'] && isset($cash_data[$v['cash_code']])) ? $cash_data[$v['cash_code']] : '';

                // 是否加盟商订单
                $isJm = $this->isJm($v['store_id']);
                $v['is_jm'] = empty($isJm) ? 0 : 1;

                // 计算每个订单的优惠折扣

                // 获取门店优惠拆折扣值
                if($v['type'] == 'store'){
                    $store_discount = $this->getStoreDiscount($v['store_id']);
                } else {
                    $store_discount = 0;
                }

                // 有门店优惠
                if ($store_discount > 0) {
                    $v['user_sale'] = (100 - $store_discount)/100;

                    // 无门店优惠
                } else {
                    $v['user_sale'] = $user_sale;
                }

                // 之前使用过折扣，还原之后再算
                $cash_money = $v['cash_money'] ? $v['cash_money'] : 0;
                $pay_money = round($v['money'] - $cash_money, 2);


                // 计算折扣金额
                if($pay_money > 0 && $v['user_sale'] > 0 && $v['status'] == 1){


                    $n_money = round($pay_money * $v['user_sale'], 2);
                    $user_discount_money = round($pay_money - $n_money, 2);
                    $pay_money = $n_money;

                    $v['user_discount_money'] = $user_discount_money;
                    $v['pay_money'] = $pay_money;


                }








                $lists['lists'][$k] = $v;
            }
        }
        $data = isset($lists['lists']) ? $lists['lists'] : array();
        $count = isset($lists['lists']) ? count($lists['lists']) : 0;
        $total = isset($lists['count']) ? $lists['count'] : 0;


        // 用户可用余额
        $balance = $this->wallet_money();

        // 活动是否开启
        $actOpen = $this->getActOpen();


        foreach ($data as $dkey => $dval) {
            $data[$dkey]['act_open'] = $actOpen;
            $data[$dkey]['back_time'] = $this->getBacktime();
            $data[$dkey]['act_can'] = $this->getOrderShareStatus($dval['order_sn']);
            $data[$dkey]['balance'] = $balance;

            // 自动选择合适的优惠券
            $data[$dkey]['fit_coupon'] = $this->getFitCoupon($this->_uid, $data[$dkey]['money'], $data[$dkey]['type']);
        }





        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => $count, 'total' => (int)$total));
    }
    /**
     * @name  order_info
     * @title  订单详情
     * @param string  $order_sn 订单号
     * @param  string  $token
     * @return [store_id] => 门店ID/卖家ID<br>[store_title] => 门店名/卖家昵称<br>[store_pic] => 门店图标（目前为空）/卖家头像<br>
    [order_sn] => 订单号<br>[pay_money] => 支付金额<br>[money] => 订单总金额<br>[cash_title] => 绑定优惠券名<br>[cash_money] => 绑定优惠券金额（折扣券时，则为折扣的金额）<br>
    [cash_type] => 绑定优惠券类型（1 满减券 2 折扣券）<br>[cash_discount] => 绑定优惠券折扣额度（折扣券时调用）<br>[create_time] => 创建时间<br>
    [detail] => 商品详细<br>
    ([title] => 商品名<br>[pic_url] => 图片地址<br>[price] => 售价<br>[num] => 数量<br> ) <br>
    [pay_type] => 支付方式(wechat:微信 alipay:支付宝)<br>
    [status] => 订单状态 1 未支付 2 已支付 3 已取消 4 已发货 5 已完成 6 已退款<br>
    [redbag_title] => 支付后获取的红包分享标题<br>
    [redbag_desc] => 支付后获取的红包分享描述<br>
    [redbag_pic] => 支付后获取的红包分享图片<br>
    [redbag_url] => 支付后获取的红包分享url<br>
    [discount_money] => 总优惠金额<br>
    [user_discount_money] => 会员优惠金额<br>
    [user_sale] => 会员当前折扣值（0到1之间）<br>
    [last_pay_second] => 剩余支付时间<br>[last_pay_time] => 最后支付的时间戳<br>
    [receipt_info] =>收货信息(<br>[name] => 收货人<br>[mobile] => 联系方式<br>[sheng_title] => 省份文本<br>[shi_title] => 城市文本<br>[qu_title] => 地区文本<br>[address] => 地址<br>)<br>
    [express_money] => 运费<br>
    [express_info] => 发货信息<br>(<br>[no] => 物流号<br>[company] => 快递公司<br>)<br>
    [type] => 订单类型：（store:门店订单[线下]   online:线上）<br>
    [express_data] => 物流信息数组<br>(<br>[time] => 时间<br>[文本内容]<br>)<br>
    [goods_total_num] => 商品总数<br>
    [goods_total_money] => 商品总价<br>
    [pay_time] => 支付时间<br>
    [express_time] => 发货时间<br>
    [is_assess] => 是否已评价<br>
    [refund_status] => 退款状态（0 未申请 1 已申请 2 已通过退款中 3 已拒绝 4 退款成功）<br>
    [refund_times] => 已发起申请次数<br>
    [last_refund_second] => 剩余自动退款时间<br>[last_refund_time] => 自动退款的时间戳<br>[refund_apply_time] => 退款申请时间<br>
    [last_receipt_second] => 剩余自动收货时间<br>[last_receipt_time] => 自动收货的时间戳<br>
    [refund_data] => 退款信息（<br>[reason] => 理由<br>[pics] => 图片数组<br>[money] => 金额<br>[seller_reason] => 卖家拒绝理由<br>）<br>
    [end_time] => 交易完成时间<br>[remark] => 备注<br>[stoer_im_userid] => 卖家的用户的im ID（只有类型为shop的才会有）
     * @remark
    通过门店端扫码调用接口时，order_sn值为扫码得到的链接，截取出order_sn，如：<br>
    扫码得到的：http://chaoshipos.k.hiiyee.com/Api/Relay/order/order_sn/160529165445139921.html<br>
    则截取从 order_sn/ 开始到 .html 中间的字符串，得160529165445139921<br><br>
    cash_title 和 cash_money 在第一次结算时做绑定，绑定后，无法再修改优惠券<br>
    参数返回为时间戳时，将会额外添加一个时间格式的参数，如 end_time 对应增加 end_time_text<br>
     */
    public function order_info(){
        $this->check_account_token();
        $this->_check_param(array('order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        if(!$order_sn){
            $this->return_data(0, '', '订单不存在');
        }
        $Model =  D('Addons://Order/Order');

        $data = $Model->get_info($order_sn, array(), $this->_uid);
        if(!$data){
            $this->return_data(0, '', '订单不存在');
        }elseif($data == -1){
            $this->return_data(0, '', '订单已被其他用户绑定');
        }
        $goods_total_money = 0;
        $goods_num = 0;
        foreach($data['detail'] as $v){
            $goods_num += $v['num'];
            $goods_total_money += $v['price']*$v['num'];
        }
        $cash = $data['cash_code'] ? $this->uc_api('CashCoupon','coupon_info', array('code' => $data['cash_code'], 'uid' => $this->_uid)) : array();

        switch($data['pay_type']){
            case 1:
                $data['pay_type'] = 'wechat';
                break;
            case 2:
                $data['pay_type'] = 'alipay';
                break;
            default:
                $data['pay_type'] = '';
        }
        $cash_money = $data['cash_money'] ? $data['cash_money'] : 0;
        $user_sale = 1;
        // 当订单未支付时，重新计算会员折扣
        if($data['status'] == 1){
            // 待支付金额需根据优惠金额处理，优惠金额可能调整（在第一次未选择时）
            $pay_money = round($data['money']-$cash_money, 2);


            // 获取门店优惠拆折扣值
            if($data['type'] == 'store'){
                $store_discount = $this->getStoreDiscount($data['store_id']);
            } else {
                $store_discount = 0;
            }

            // 有门店优惠
            if ($store_discount > 0) {
                $user_sale = (100 - $store_discount)/100;
            } else {
                // 获取会员优惠折扣值
                $scorebox = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
                $user_sale = (100 - $scorebox['level_sale'])/100;
            }




            // 计算折扣金额
            if($pay_money > 0 && $user_sale > 0){
                $n_money = round($pay_money*$user_sale, 2);
                $user_discount_money = round($pay_money - $n_money, 2);
                $pay_money = $n_money;
            }


            // 已支付时，相关值已确认
        }else{
            $pay_money = $data['pay_money'];
            $user_discount_money = $data['user_discount_money'];
        }
        $pay_money < 0 && $pay_money = 0;
        if($data['status'] >= 2){
            $lottery = $this->uc_api('CashCoupon','get_lottery', array('order_sn' => $order_sn, 'uid' => $this->_uid));
            if($lottery){
                $shareConfig = share_config('pay_share');
                $shareConfig['url'] = U('Wap/CashCoupon/lottery_coupon', array('cash_code' => $lottery['code']));
                $shareConfig['url'] = $shareConfig['url'];
            }
        }
        // ios审核的版本不做显示
        if($this->device_type == 2 && $this->version_no == C('IOS_BE_PUBLISH_NO')){
            $shareConfig = array();
        }

        $discount_money = round($data['money'] - $pay_money, 2);

        if($data['refund_status'] > 0){
            $refund_data = M('OrderRefund')->where(array('order_sn' => $order_sn))->order('id desc')->field('reason, pics, money, seller_reason, create_time')->find();
            if($refund_data){
                $refund_data['pics'] = !empty($refund_data['pics']) ?  explode(',', $refund_data['pics']) : array();
                foreach($refund_data['pics'] as $k => $v){
                    $v = intval($v);
                    $pic = '';
                    if($v > 0){
                        $pic = get_cover_url($v);
                    }
                    if($pic){
                        $refund_data['pics'][$k] = $pic;
                    }else{
                        unset($refund_data['pics'][$k]);
                    }
                }
                $refund_data['pics'] = array_values($refund_data['pics']);
            }else{
                $refund_data = (object)array();
            }
        }else{
            $refund_data = (object)array();
        }
        $express_data = $Model->get_express_log($order_sn);
        if($data['type'] == 'shop'){
            $im_info = D('Common/Member')->get_im($data['store_id']);
            $stoer_im_userid = $im_info['userid'];
        }else{
            $stoer_im_userid = '';
        }

        // 是否可以参加活动
        $actCan = $this->getOrderShareStatus($order_sn);

        // 倒计时
        $backTime = $this->getBacktime();


        // 活动相关信息
        $actInfo = $this->getShareInfo($this->_uid);

        // 活动是否开启
        $actOpen = $this->getActOpen();

        // 活动详情ID
        $bindId = $this->getBindId();

        // 用户可用余额
        $balance = $this->wallet_money();


        // 是否加盟商订单
        $isJm = $this->isJm($data['store_id']);
        $isJm = empty($isJm) ? 0 : 1;


        // 自动选择合适的优惠券
        $fitCoupon = $this->getFitCoupon($this->_uid, $data['money'], $data['type']);

        $result = array(
            'store_id' => $data['store_id'],
            'store_title' => $data['store_title'],
            'store_pic' => $data['store_pic'],
            'order_sn' => $data['order_sn'],
            'cash_title' => $cash ? $cash['title'] : '',
            'cash_type' => isset($cash['type']) ? $cash['type'] : 0,
            'cash_discount' => isset($cash['discount']) ? $cash['discount'] : 0,
            'create_time' => $data['create_time'],
            'create_time_text' => $data['create_time_text'],
            'detail' => $data['detail'],
            'pay_type' => $data['pay_type'],
            'redbag_url' => empty($shareConfig['url']) ? '' : $shareConfig['url'],
            'redbag_desc' => empty($shareConfig['desc']) ? '' : $shareConfig['desc'],
            'redbag_title' => empty($shareConfig['title']) ? '': $shareConfig['title'] ,
            'redbag_pic' => empty($shareConfig['cover']) ? '' : $shareConfig['cover'],
            'status' => $data['status'],
            'user_sale' => $user_sale,
            'pay_money' => $pay_money,
            'cash_money' => $cash_money,
            'user_discount_money' => $user_discount_money,
            'discount_money' => $discount_money,
            'money' => $data['money'],
            'last_pay_second' => $data['last_pay_second'],
            'last_pay_time' => $data['last_pay_time'],
            'last_pay_time_text' => $data['last_pay_time'] ? time_format($data['last_pay_time'], 'Y-m-d H:i:s') : '',
            'receipt_info' => $data['receipt_info'] ? $data['receipt_info'] : (object)array(),
            'express_money' => $data['express_money'],
            'express_info' => $data['express_info'] ? $data['express_info'] : (object)array(),
            'express_data' => $express_data,
            'type' => $data['type'] ? $data['type'] : 'store',
            'goods_total_num' => $goods_num,
            'goods_total_money' => sprintf("%.2f", round($goods_total_money, 2)),
            'is_assess' => $data['is_assess'],
            'refund_status' => $data['refund_status'],
            'refund_times' => $data['refund_times'],
            'refund_data' => $refund_data,
            'last_refund_second' => $data['last_refund_second'],
            'last_refund_time' => $data['last_refund_time'],
            'last_refund_time_text' => $data['last_refund_time'] ? time_format($data['last_refund_time'], 'Y-m-d H:i:s') : '',
            'refund_apply_time' => (is_array($refund_data) && isset($refund_data['create_time'])) ? $refund_data['create_time'] : '0',
            'refund_apply_time_text' => (is_array($refund_data) && !empty($refund_data['create_time'])) ? time_format($refund_data['create_time'], 'Y-m-d H:i:s') : '',
            'last_receipt_second' => $data['last_receipt_second'],
            'last_receipt_time' => $data['last_receipt_time'],
            'last_receipt_time_text' => $data['last_receipt_time'] ? time_format($data['last_receipt_time'], 'Y-m-d H:i:s') : '',
            'pay_time' => $data['pay_time'],
            'pay_time_text' => $data['pay_time'] ? time_format($data['pay_time'], 'Y-m-d H:i:s') : '',
            'end_time' => $data['end_time'],
            'end_time_text' => $data['end_time'] ? time_format($data['end_time'], 'Y-m-d H:i:s') : '',
            'remark' => $data['remark'],
            'stoer_im_userid' => $stoer_im_userid,
            'act_open' => $actOpen,
            'back_time' => $backTime,
            'act_can' => $actCan,
            'act_info' => $actInfo,
            'bind_id' => $bindId,
            'balance' => $balance,
            'is_jm' => $isJm,
            'fit_coupon' => $fitCoupon,
        );
        $this->return_data(1, $result, '');
    }

    /**
     * 获取最适合使用的优惠券
     */
    private function getFitCoupon($uid, $order_money, $type)
    {


        if (empty($uid) || empty($order_money) || $type != 'store') {
            return array(
                'title' => '',
                'code' => '',
                'money' => 0,
                'description' => '',
                'type' => 1,
            );
        }

        $sql = "select * from  hii_cash_coupon_user where status = 1 and (last_time = 0 or last_time > unix_timestamp()) and type = 1 and uid = {$uid} and min_use_money <= {$order_money} order by money desc,last_time asc limit 1;";

        $data = M()->query($sql);

        if (empty($data[0]) || empty($data[0]['code']) || empty($data[0]['money'])) {
            return array(
                'title' => '',
                'code' => '',
                'money' => 0,
                'description' => '',
                'type' => 1,
            );
        } else {
            $coupon = $data[0];
            return array(
                'title' => $coupon['title'],
                'code' => $coupon['code'],
                'money' => $coupon['money'],
                'description' => $coupon['description'],
                'type' => 1,
            );
        }

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


    /**
     * 分享红包
     */
    public function share_redbag()
    {
        // 可以参与分享红包的条件
        // 1.订单完成；2.订单是当天的；订单还未参与分享

        // 活动开始
        $config = $this->getActConfig();

        $time = time();
        if (empty($config['is_open']) || $time < $config['stime'] || $time > ($config['etime'] + 3600 * 24)) {
            $this->return_data(0, '', '活动不存在或还没有开始');
        }

        // params: order_sn 订单号


        $this->check_account_token();


        $order_sn = I('order_sn', '', 'trim');
        if(empty($order_sn)){
            $this->return_data(0, '', '订单不存在');
        }

        // 当天的下单时间
        $daytime = strtotime(date('Y-m-d'));

        $order_info = M('order')->where(array('order_sn' => $order_sn))->find();
        if (empty($order_info)) {
            $this->return_data(0, '', '订单不存在');
        } elseif ($order_info['uid'] != $this->_uid) {
            $this->return_data(0, '', '订单已被其他用户绑定');
        } elseif ($order_info['status'] != 5 || $order_info['pay_status'] != 2) {
            $this->return_data(0, '', '订单状态未完成');
        } elseif ($order_info['create_time'] < $daytime) {
            $this->return_data(0, '', '不是当天的订单');
        } else {
            $have = M('act_prize_share')->where(array(
                'order_sn' => $order_sn
            ))->find();

            if (!empty($have)) {


                $shareInfo = $this->getShareInfo($this->_uid);

                $this->return_data(1, $shareInfo, '该订单已经分享');
            }
        }


        $result = M('act_prize_share')->add(array(
            'uid' => $order_info['uid'],
            'order_sn' => $order_info['order_sn'],
            'pay_money' => $order_info['pay_money'],
            'order_time' => $order_info['create_time'],
            'create_time' => time(),
        ));

        if (empty($result)) {
            $this->return_data(0, '', '分享失败');
        } else {

            $shareInfo = $this->getShareInfo($this->_uid);
            $this->return_data(1, $shareInfo, '分享成功');
        }

    }


    // 获取奖池信息
    public function share_info()
    {
        $this->check_account_token();
        $shareInfo = $this->getShareInfo($this->_uid);
        $this->return_data(1, $shareInfo, '奖池信息');
    }

    // 获取活动信息
    public function act_info()
    {
        $config = $this->getActConfig();
        $config['stime_text'] = date('Y-m-d', $config['stime']);
        $config['etime_text'] = date('Y-m-d', $config['etime']);
        $config['etime'] += 3600*24 -1 ;
        unset($config['money'], $config['parcent'], $config['prize'], $config['num'], $config['create_time'], $config['update_time']);
        $this->return_data(1, $config, '活动信息');
    }

    // 活动倒计时
    private function getBacktime()
    {
        // 活动开始
        $config = $this->getActConfig();

        $time = time();
        if (empty($config['is_open']) || $time < $config['stime'] || $time > ($config['etime'] + 3600 * 24)) {
            return 0;
        }
        $backtime = strtotime(date('Y-m-d') . '23:59:59') + 1 + 60;
        //$backtime = strtotime(date('Y-m-d') . '23:59:59') - time() + 1;  
        return $backtime;
    }


    // 获取奖池信息
    private function getShareInfo($uid = 0)
    {


        $config = $this->getActConfig();

        // 参数: 比例


        // 参与总人次，奖池金额
        $parcent = $config['parcent'];


        // 当天的下单时间
        $daytime = strtotime(date('Y-m-d'));

        $where = array();

        $where['order_time'] = array('egt', $daytime);

        // 总人数
        $count = M('act_prize_share')->where($where)->count();

        $count += $config['num'];

        // 总支付金额
        $pay_money = M('act_prize_share')->where($where)->sum('pay_money');

        if (!empty($uid)) {
            $where['uid'] = $uid;
            $pay_money_me = M('act_prize_share')->where($where)->sum('pay_money');
        } else {
            $pay_money_me = 0;
        }


        $money = $pay_money * $parcent / 100 + $config['prize'];
        $money_me = $pay_money_me * $parcent / 100;

        return array(
            'count' => $count,
            'money' => number_format($money, 2),
            'money_me' => number_format($money_me, 2),
        );


    }

    // 获取订单分享信息
    private function getOrderShareStatus($order_sn)
    {

        if (empty($order_sn)) {
            return 0;
        }



        // 活动开始
        $config = $this->getActConfig();

        $time = time();
        if (empty($config['is_open']) || $time < $config['stime'] || $time > ($config['etime'] + 3600 * 24)) {
            return 0;
        }








        $order_info = M('order')->where(array('order_sn' => $order_sn))->find();

        if (empty($order_info)) {
            $actCan = 0;
        }



        // 当天的下单时间
        $daytime = strtotime(date('Y-m-d'));

        // 是否可以参与活动
        // 未支付不可参与
        if ($order_info['status'] != 5 || $order_info['pay_status'] != 2) {
            $actCan = 0;

            // 可参与
        } else {
            $actCan = 1;
        }


        if ($actCan == 1) {

            // 是否已经分享
            $have = M('act_prize_share')->where(array(
                'order_sn' => $order_sn
            ))->find();


            // 今天的单
            if ($order_info['create_time'] > $daytime) {
                // 今天的已参与
                if (!empty($have)) {
                    $actCan = 2;
                } else {
                    $actCan = 1;
                }

            } else {
                // 往天的已参与
                if (!empty($have)) {
                    // 是否中奖
                    if (!empty($have['give'])) {
                        $actCan = 3;

                        // 已参与未中奖，可能会有时间误差
                    } else {
                        $actCan = 4;
                    }
                } else {
                    $actCan = 0;
                }

            }
        }

        return $actCan;

    }

    // 活动是否开启
    private function getActOpen()
    {
        $data = $this->getActConfig();

        return empty($data['is_open']) ? 0 : 1;
    }


    // 活动详情ID
    private function getBindId()
    {
        $data = $this->getActConfig();

        return empty($data['bind_id']) ? 0 : intval($data['bind_id']);
    }


    // 获取活动配置信息
    private function getActConfig()
    {
        $data = M('act_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => false,
                'title' => '',
                'info' => '',
                'act_url' => '',
                'money' => 1,
                'min_use_money' => 0,
                'parcent' => 0,
                'prize' => 1,
                'num' => 0,
                'max_get' => 0,
                'days' => 1,
                'remark' => '',
                'stime' => 0,
                'etime' => 0,
            );
        }

        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);


        $data['parcent'] > 20 && $data['parcent'] = 20;


        $data['prize'] > 1000 && $data['prize'] = 1;



        $data['bind_id'] = $data['act_url'];

        unset($data['act_url']);

        return $data;

    }



    // 分配活动优惠券
    public function assign_act_coupon()
    {
        // 获取活动配置
        $config = $this->getActConfig();

        // 前一天的下单时间
        $stime = strtotime(date('Y-m-d'));

        $etime = $stime;

        $stime = $stime - (3600 * 24);


        if (empty($config['is_open'])) {
            $this->return_data(0, '', '活动未开启');
        }

        //array(array('egt',strtotime($_POST["dateStart"])),array('elt',strtotime($_POST["dateEnd"])));


        // 获取奖池金额
        // 参与总人次，奖池金额
        $parcent = $config['parcent'];
        $where = array();
        $where['order_time'] = array(array('gt', $stime), array('lt', $etime));
        $where['uid'] = array('neq', 0);
        $where['give'] = array('eq', 0);
        $pay_money = M('act_prize_share')->where($where)->sum('pay_money');
        $money = round($pay_money * $parcent / 100, 2) + $config['prize'];



        // 可种奖人数
        //$num =  floor($money / $config['money']);   
        $num =  ceil($money / $config['money']);




        // 最大获取人数
        $max_get = empty($config['max_get']) ? 0 : $config['max_get'];

        if ($max_get != 0 && $num > $max_get) {
            $num = $max_get;
        }

        // 奖池不足以派发优惠券
        if ($num < 1) {
            $this->return_data(0, '', '奖池不足以派发优惠券');
        }




        // 优惠券是否已派发
        $sql_have = "select id,uid from hii_act_prize_share where create_time > {$stime} and create_time < {$etime} and uid != 0 and give !=0;";
        $have = M()->query($sql_have);
        if (!empty($have)) {
            $this->return_data(0, '', '当前的优惠券已派发');
        }

        // 取出抽奖对象
        //$sql = "select id,uid,order_sn,count(uid) as ucount from hii_act_prize_share where create_time > {$stime} and create_time < {$etime} and uid != 0 and give = 0 group by uid;";
        $sql = "select id,uid,order_sn from hii_act_prize_share where create_time > {$stime} and create_time < {$etime} and uid != 0 and give = 0";
        $data = M()->query($sql);
        if (empty($data)) {
            $data = array();
        }


        $shareData = array();
        foreach ($data as $key => $val) {
            $shareData[$val['id']] = $val;
        }


        // 没有参与分享的人
        if (empty($shareData)) {
            $this->return_data(0, '', '没人参与分享');
        }



        // 总数少于抽奖数
        if (count($shareData) < $num) {
            shuffle($shareData);
            $ids = array_keys($shareData);

            // uid去重处理
            $users_b = array();
            $uids_b = array();

            //$this->return_data(1, $ids, 'test');exit;
            foreach($ids as $key => $id) {
                if (!in_array($shareData[$id]['uid'], $uids_b)) {
                    $users_b[$shareData[$id]['id']] = $shareData[$id];
                    $uids_b[] = $shareData[$id]['uid'];
                }
            }

            // 数量肯定不够，如果需要派完奖券，可以直接每单都取；然后再次抽取指定数量



            // 总数多于抽奖数
        } else {
            // 随机取出指定数目
            $ids = array_rand($shareData, $num);

            if (!is_array($ids)) {
                $ids = array($ids);
            }


            // uid去重处理
            $users_b = array();
            $uids_b = array();
            foreach($ids as $key => $id) {
                if (!in_array($shareData[$id]['uid'], $uids_b)) {
                    $users_b[$shareData[$id]['id']] = $shareData[$id];
                    $uids_b[] = $shareData[$id]['uid'];
                }
            }


            // 有用户重复的情况,取其它用户
            if (count($users_b) < $num) {
                // echo 'xxxxxxxxxxxxxxxxxxxx';
                shuffle($shareData);

                // 把指定的UID去掉再取值
                foreach ($shareData as $okey => $oval) {
                    if (!in_array($oval['uid'], $uids_b)) {
                        $users_b[$oval['id']] = $oval;
                        $uids_b[] = $oval['uid'];
                    }

                    if (count($users_b) >= $num) {
                        break;
                    }

                }

                // 如果数量还不够，把指定的ID去掉再取值,经过上面处理每个UID都有值（如果需要）

                // 总数多于抽奖数，不可能ID不够
            }
        }


        $users = $users_b;
        /*
        $this->return_data(1, array(
            'users_b' => $users_b,
            'num' => $num,
            'money' => $money,
        ), 'eee');exit;
        exit;
        */


        /*
        $users = array();
        foreach($ids as $key => $id) {
            $users[$id] = $shareData[$id];
        }
        */


        if (empty($users)) {
            $this->return_data(0, '', '没人参与分享');
        }

        // 中奖主录的 ID 集
        $ids = array_keys($users);



        // 标识获取名单
        M('act_prize_share')->where(array(
            'id' => array('in', $ids),
        ))->save(array(
            'give' => 1,
            'give_time' => time(),
        ));


        // 记录发券日志
        $data_log = array(
            'title' => $config['title'],
            'info' => $config['info'],
            'money' => $config['money'],
            'parcent' => $config['parcent'],
            'prize' => $config['prize'],
            'num' => $config['num'],
            'days' => $config['days'],
            'remark' => $config['remark'],
            'stime' => $config['stime'],
            'etime' => $config['etime'],
            'create_time' => time(),
            'act_time' => $stime, //活动时间
            'act_val' => $money, //奖池大小
            'act_num' => count($shareData), //参与人数
            'act_select' => count($users), // 中奖数量            
        );

        $coupon_id = M('act_log')->add($data_log);

        //$this->return_data(1, $ids, 'test');

        // 发放优惠券


        // 优惠券基础信息
        $time = time();
        $last_time = 0;
        if($config['days'] > 0){
            $last_time = strtotime('+'.$config['days'].' day');
        }
        $coupon = array(
            'title' => $config['title'],
            'status' => 1,
            'last_time' => $last_time,
            'money' => $config['money'],
            'create_time' =>  $time,
            'update_time' => $time,
            'description' => $config['info'],
            'type' => 1,
            'min_use_money' => $config['min_use_money'],
            'receive_ip' => get_client_ip(),
            'act' => $coupon_id,
        );

        $notice = array(
            'type' => 'notice',
            'title' => '系统通知',
            'act_uid' => 0,
            'act_id' => 126,
            //'act_id' => $coupon_id,
            'act_data' => '[]',
            'hid' => '',
            //'hash' => md5('活动优惠券' . $coupon_id . mt_rand(10000, 99999)),
            'is_read' => 0,
            'status' => 0,
            'create_time' => $time,
            'update_time' => $time,
        );

        // 系统通知
        $notices = array();

        // 优惠券用户信息
        $coupons = array();
        foreach ($users as $key => $user) {
            $mycode = $this->get_act_code();
            $coupons[] = array_merge($coupon, array(
                'uid' => $user['uid'],
                'code' => $mycode,
                'p_code' => 'act_' . $user['order_sn'],
                'order_sn' => $user['order_sn']
            ));

            $notices[] = array_merge($notice, array(
                'uid' => $user['uid'],
                'hash' => md5('活动优惠券' . $user['uid'] . mt_rand(10000, 99999)),
                'content' => '您参与活动的订单' . $user['order_sn'] . '被抽中获取一张' . $config['money'] . '元的优惠券，直接手机扫码支付便可使用哦。',
            ));

        }

        // 发放
        M('cash_coupon_user')->addAll($coupons);

        M('message_notice')->addAll($notices);


        $this->return_data(1, '', '优惠券派发完成');
    }

    // 生成
    private function get_act_code($lv = 0){
        $lv = intval($lv);
        $code = substr(md5('act'.mt_rand(1000000, 9999999).$lv), 10, 10);
        if(M('cash_coupon_user')->where(array('code' => $code))->find()){
            $code = $this->get_act_code($lv+1);
        }
        return $code;
    }





    /**
     * @name  buyer_order_info
     * @title  卖家订单详情
     * @param string  $order_sn 订单号
     * @param  string  $token
     * @return [store_id] => 门店ID/卖家ID<br>[store_title] => 门店名/卖家昵称<br>[store_pic] => 门店图标（目前为空）/卖家头像<br>
    [order_sn] => 订单号<br>[pay_money] => 支付金额<br>[money] => 订单总金额<br>[cash_money]=>优惠券金额<br>[user_discount_money] => 会员优惠金额<br>[create_time] => 创建时间<br>
    [detail] => 商品详细<br>([title] => 商品名<br>[pic_url] => 图片地址<br>[price] => 售价<br>[num] => 数量<br> ) <br>
    [status] => 订单状态 1 未支付 2 已支付 3 已取消 4 已发货 5 已完成 6 已退款<br>
    [last_pay_second] => 剩余支付时间<br>[last_pay_time] => 最后支付的时间戳<br>
    [receipt_info] =>收货信息(<br>[name] => 收货人<br>[mobile] => 联系方式<br>[sheng_title] => 省份文本<br>[shi_title] => 城市文本<br>[qu_title] => 地区文本<br>[address] => 地址<br>)<br>
    [express_money] => 运费<br>
    [express_info] => 发货信息<br>(<br>[no] => 物流号<br>[company] => 快递公司<br>)<br>
    [type] => 订单类型：（store:门店订单[线下]   online:线上）<br>
    [express_data] => 物流信息数组<br>(<br>[time] => 时间<br>[文本内容]<br>)<br>
    [goods_total_num] => 商品总数<br>
    [goods_total_money] => 商品总价<br>
    [pay_time] => 支付时间<br>
    [express_time] => 发货时间<br>
    [is_assess] => 是否已评价<br>
    [refund_status] => 退款状态（0 未申请 1 已申请 2 已通过退款中 3 已拒绝 4 退款成功）<br>
    [refund_times] => 已发起申请次数<br>
    [last_refund_second] => 剩余自动退款时间<br>[last_refund_time] => 自动退款的时间戳<br>[refund_apply_time] => 退款申请时间<br>
    [last_receipt_second] => 剩余自动收货时间<br>[last_receipt_time] => 自动收货的时间戳<br>
    [refund_data] => 退款信息（<br>[reason] => 理由<br>[pics] => 图片数组<br>[money] => 金额<br>[seller_reason] => 卖家拒绝理由<br>）<br>
    [end_time] => 交易完成时间<br>[uid] => 买家ID<br>[nickname] => 买家昵称<br>[header_pic] => 买家头像<br>[remark] => 备注<br>[im_userid] => 订单用户的im ID
     * @remark  参数返回为时间戳时，将会额外添加一个时间格式的参数，如 end_time 对应增加 end_time_text
     */
    public function buyer_order_info(){
        $this->check_account_token();
        $this->_check_param(array('order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        if(!$order_sn){
            $this->return_data(0, '', '订单不存在');
        }
        $Model =  D('Addons://Order/Order');
        $where = array();
        $where['type'] = 'shop';
        $where['store_id'] = $this->_uid;
        $data = $Model->get_info($order_sn, $where);
        if(!$data){
            $this->return_data(0, '', '订单不存在');
        }

        $goods_total_money = 0;
        $goods_num = 0;
        foreach($data['detail'] as $v){
            $goods_num += $v['num'];
            $goods_total_money += $v['price']*$v['num'];
        }

        if($data['refund_status'] > 0){
            $refund_data = M('OrderRefund')->where(array('order_sn' => $order_sn))->order('id desc')->field('reason, pics, money, seller_reason,create_time')->find();
            if($refund_data){
                $refund_data['pics'] = !empty($refund_data['pics']) ?  explode(',', $refund_data['pics']) : array();
                foreach($refund_data['pics'] as $k => $v){
                    $v = intval($v);
                    $pic = '';
                    if($v > 0){
                        $pic = get_cover_url($v);
                    }
                    if($pic){
                        $refund_data['pics'][$k] = $pic;
                    }else{
                        unset($refund_data['pics'][$k]);
                    }
                }
                $refund_data['pics'] = array_values($refund_data['pics']);
            }else{
                $refund_data = (object)array();
            }
        }else{
            $refund_data = (object)array();
        }
        $express_data = $Model->get_express_log($order_sn);
        $im_info = D('Common/Member')->get_im($data['uid']);
        $im_userid = $im_info['userid'];
        $result = array(
            'store_id' => $data['store_id'],
            'store_title' => $data['store_title'],
            'store_pic' => $data['store_pic'],
            'order_sn' => $data['order_sn'],
            'create_time' => $data['create_time'],
            'create_time_text' => $data['create_time_text'],
            'detail' => $data['detail'],
            'status' => $data['status'],
            'pay_money' => $data['pay_money'],
            'cash_money' => $data['cash_money'],
            'user_discount_money' => $data['user_discount_money'],
            'money' => $data['money'],
            'last_pay_second' => $data['last_pay_second'],
            'last_pay_time' => $data['last_pay_time'],
            'last_pay_time_text' => $data['last_pay_time'] ? time_format($data['last_pay_time'], 'Y-m-d H:i:s') : '',
            'receipt_info' => $data['receipt_info'] ? $data['receipt_info'] : (object)array(),
            'express_money' => $data['express_money'],
            'express_info' => $data['express_info'] ? $data['express_info'] : (object)array(),
            'express_data' => $express_data,
            'type' => $data['type'],
            'goods_total_num' => $goods_num,
            'goods_total_money' => sprintf("%.2f", round($goods_total_money, 2)),
            'is_assess' => $data['is_assess'],
            'refund_status' => $data['refund_status'],
            'refund_times' => $data['refund_times'],
            'refund_data' => $refund_data,
            'last_refund_second' => $data['last_refund_second'],
            'last_refund_time' => $data['last_refund_time'],
            'last_refund_time_text' => $data['last_refund_time'] ? time_format($data['last_refund_time'], 'Y-m-d H:i:s') : '',
            'refund_apply_time' => (is_array($refund_data) && isset($refund_data['create_time'])) ? $refund_data['create_time'] : '0',
            'last_refund_time_text' => (is_array($refund_data) && !empty($refund_data['create_time'])) ? time_format($refund_data['create_time'], 'Y-m-d H:i:s') : '',
            'last_receipt_second' => $data['last_receipt_second'],
            'last_receipt_time' => $data['last_receipt_time'],
            'last_receipt_time_text' => $data['last_receipt_time'] ? time_format($data['last_receipt_time'], 'Y-m-d H:i:s') : '',
            'pay_time' => $data['pay_time'],
            'pay_time_text' => $data['pay_time'] ? time_format($data['pay_time'], 'Y-m-d H:i:s') : '',
            'end_time' => $data['end_time'],
            'end_time_text' => $data['end_time'] ? time_format($data['end_time'], 'Y-m-d H:i:s') : '',
            'uid' => $data['uid'],
            'nickname' => get_nickname($data['uid']),
            'header_pic' => get_header_pic($data['uid']),
            'remark' => $data['remark'],
            'im_userid' => $im_userid,
        );
        $this->return_data(1, $result, '');
    }
    /**
     * @name   cancel_order
     * @title  取消订单（未付款的订单可取消）
     * @param  string  $order_sn    订单号
     * @param  string  $token       用户token
     * @return
     *
     */
    public function cancel_order(){
        $this->check_account_token();
        $this->_check_param(array('order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        if(D('Addons://Order/Order')->cancel_order($order_sn, $this->_uid)){
            $this->numInc($order_sn);
            $this->return_data(1, '', '取消成功');
        }else{
            $this->return_data(0, '', '取消失败');
        }
    }
    /**
     * @name   refund_apply
     * @title  订单申请退款
     * @param  string  $order_sn    订单号
     * @param  string  $money       退款金额（保留两位小数）
     * @param  string  $reason      理由（最多150个字符，一个中文为3个字符）
     * @param  string  $pics        图片ID集合（可为空，多个图片ID之间使用,分格）
     * @param  string  $token       用户token
     * @return
     * @remark  只有已支付且未收货的订单可以发起退款申请
     */
    public function refund_apply(){
        $this->check_account_token();
        $this->_check_param(array('order_sn', 'reason', 'money'));
        $money = round(I('money', 0), 2);
        $reasont = I('reason', '', 'trim');
        $order_sn = I('order_sn', '', 'trim');
        $pics = I('pics', '', 'trim');
        $Model = D('Addons://Order/Order');
        if($Model->refund($order_sn, $this->_uid, $money, $reasont, $pics)){
            $this->return_data(1, '', '申请成功');
        }else{
            $error = $Model->getError();
            !$error && $error = '申请失败';
            $this->return_data(0, '', $error);
        }
    }
    /**
     * @name   cancel_refund_apply
     * @title  取消退款申请
     * @param   string  $order_sn  订单号
     */
    public function cancel_refund_apply(){
        $this->check_account_token();
        $this->_check_param(array('order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        if(D('Addons://Order/Order')->cancel_refund($order_sn, $this->_uid)){
            $this->return_data(1, '', '取消成功');
        }else{
            $this->return_data(0, '', '取消失败');
        }
    }
    /**
     * @name   confirm_receipt
     * @title  订单确认收货
     * @param  string  $order_sn    订单号
     * @param  string  $token       用户token
     * @return
     * @remark 只有已发货的订单可以做确认收货操作
     */
    public function confirm_receipt(){
        $this->check_account_token();
        $this->_check_param(array('order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        if(D('Addons://Order/Order')->confirm_receipt($order_sn, $this->_uid)){
            $this->return_data(1, '', '确认收货成功');
        }else{
            $this->return_data(0, '', '确认收货失败');
        }
    }

    // 自动收货
    public function auto_receipt(){

        $this->_check_param(array('order_sn'));

        // 用户订单
        $order_sn = I('order_sn', '', 'trim');

        // 用户ID
        $uid = I('uid', 0);

        if (empty($order_sn) || empty($uid)) {
            $this->return_data(0, '', 'params error');
        }

        if(D('Addons://Order/Order')->confirm_receipt($order_sn, $uid)){
            $this->return_data(1, array(
                'test' => 'ok',
            ));
        }else{
            $this->return_data(0, array(
                'test' =>'ng',
            ));
        }
    }


    /**
     * @name add_assess
     * @title 发表评价
     * @param string $order_sn      订单号
     * @param string $content       内容
     * @param int    $goods_star    商品质量星级（0-50）
     * @param int    $shop_star     商家服务星级（0-50）
     * @return
     * @remark 只对已完成的门店商品订单
     */
    public function add_assess(){
        $this->check_account_token();
        $this->_check_param('order_sn', 'content', 'goods_star', 'shop_star');
        $order_sn = I('order_sn', '', 'trim');
        $content = I('content', '', 'trim');
        $goods_star = I('goods_star', 0, 'intval');
        $shop_star = I('shop_star', 0, 'intval');
        if(!$order_sn){
            $this->return_data(0, '', '未知单号');
        }
        $where = array('order_sn' => $order_sn, 'uid' => $this->_uid, 'status' => 5);
        $info = D('Addons://Order/Order')->get_info($order_sn, $where, $this->_uid, '*', true);
        if(!$info){
            $this->return_data(0, '', '订单不存在');
        }
        if($info['type'] != 'shop'){
            $this->return_data(0, '', '该订单不能评价');
        }
        if($info['is_assess'] == 1){
            $this->return_data(1, '', '已发表评价');
        }
        $goods = array();
        if(!empty($info['detail'])){
            foreach($info['detail'] as $v){
                $goods[] = array(
                    'goods_id' => $v['d_id'],
                    'title' => $v['title'],
                    'pic' => $v['pic_url']
                );
            }
        }
        $result = D('Addons://Shop/Shop')->add_assess($this->_uid, $info['store_id'], $content, $goods_star, $shop_star, $order_sn, $info['end_time'], $goods);
        if($result){
            D('Addons://Order/Order')->set_assess($order_sn);
            $this->return_data(1);
        }else{
            $this->return_data(0);
        }
    }
    /**
     * @name express_data
     * @title 快递公司信息
     * @return  [express_name] => 快递公司标识<br>[company] => 快递公司名
     * @remark 因快递信息不会频繁变化，请在本地做数据缓存，在每次打开App时获取即可。
     */
    public function express_data(){
        $data = D('Addons://Order/OrderExpress')->express_data();
        $result = array();
        foreach($data as $v){
            $result[] = array(
                'express_name' => $v['name'],
                'company' => $v['company']
            );
        }
        $this->return_data(1, $result);
    }
    /**
     * @name order_edit_money
     * @title （卖家）修改订单金额
     * @param string $order_sn      订单号
     * @param string $money         修改金额
     * @return
     */
    public function order_edit_money(){
        $this->check_account_token();
        $this->_check_param(array('order_sn', 'money'));
        $order_sn = I('order_sn', '', 'trim');
        $money = round(I('money', 0), 2);
        if(!$order_sn){
            $this->return_data(0, '', '未知订单');
        }
        $Model = D('Addons://Order/Order');
        if($Model->edit_pay_money($order_sn, $money, $this->_uid)){
            $this->return_data(1);
        }else{
            $error = $Model->getError();
            !$error && $error = '修改失败';
            $this->return_data(0, '', $error);
        }
    }
    /**
     * @name order_delivery
     * @title （卖家）订单发货
     * @param string $order_sn      订单号
     * @param string $express_name  快递公司标识
     * @param string $no    快递单号
     * @return
     */
    public function order_delivery(){
        $this->check_account_token();
        $this->_check_param(array('order_sn', 'express_name', 'no'));
        $Model = D('Addons://Order/Order');
        if($Model->delivery_by_shop(I('order_sn'), $this->_uid, I('express_name'), I('no'))){
            $this->return_data(1);
        }else{
            $error = $Model->getError();
            !$error && $error = '发货失败';
            $this->return_data(0, '', $error);
        }
    }
    /**
     * @name denied_refund
     * @title （卖家）拒绝退款
     * @param string $order_sn     订单号
     * @param string $reason       理由
     * @return
     */
    public function denied_refund(){
        $this->check_account_token();
        $this->_check_param(array('reason', 'order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        $reason = I('reason', '', 'trim');
        if(D('Addons://Order/Order')->denied_refund($order_sn, $this->_uid, $reason)){
            $this->return_data(1);
        }else{
            $this->return_data(0);
        }
    }
    /**
     * @name agree_refund
     * @title （卖家）同意退款
     * @param string $order_sn      订单号
     * @return
     */
    public function agree_refund(){
        $this->check_account_token();
        $this->_check_param('order_sn');
        $order_sn = I('order_sn', '', 'trim');

        if(D('Addons://Order/Order')->agree_refund($order_sn, $this->_uid)){
            $this->return_data(1);
        }else{
            $this->return_data(0);
        }
    }




    /**
     * 自动处理退款，由计划任务请求触发
     */
    public function auto_refund()
    {

        $this->_check_param('order_sn', 'sid');
        $order_sn = I('order_sn', '', 'trim');
        $sid = I('sid', 0);

        if (empty($order_sn) || empty($sid)) {
            $this->return_data(0, '', 'params error');
        }

        //if(D('Addons://Order/Order')->xytest($order_sn, $sid)){
        if(D('Addons://Order/Order')->agree_refund($order_sn, $sid)){
            $this->return_data(1, array(
                'test' => 'ok',
            ));
        }else{
            $this->return_data(0, array(
                'test' =>'ng',
            ));
        }

        /*
        if(D('Addons://Order/Order')->agree_refund($order_sn, $this->_uid)){
            $this->return_data(1);
        }else{
            $this->return_data(0);
        } 
        */

    }



    /**
     * @name del_order
     * @title 删除订单
     * @param string $order_sn      订单号
     * @return
     */
    public function del_order(){
        $this->check_account_token();
        $this->_check_param('order_sn');
        $order_sn = I('order_sn', '', 'trim');
        if(D('Addons://Order/Order')->hide_order($order_sn, $this->_uid)){
            $this->return_data(1, '', '删除成功');
        }else{
            $this->return_data(0);
        }
    }
    /**
     * @name cart_data
     * @title 购物车商品
     * @return [shop] => 店铺信息(<br><div style="padding-left:20px;">[uid] => 用户ID<br>[nickname] => 昵称<br>[header_pic]=>头像<br></div>)<br><br>
    [goods] => 商品数组信息(<br><div style="padding-left:20px;">[cid] => 购物车ID<br>[goods_id] => 商品ID<br>[num] => 购物车数量<br>[goods_title] => 商品名<br>[goods_pic]=>商品图片<br>[goods_num]=>商品数量<br>[express_money]=>运费<br>[price]=>售价<br><div>)
     * @remark  当前接口会返回数组形式，每一个数组成员都会有shop 和 goods<br>每次获取时，会先检查商品是否存在，若不存在，则直接删除
     */
    public function cart_data(){
        $this->check_account_token();
        $api = new \Addons\Order\Lib\CartLib($this->_uid);
        $data = $api->get_cart();
        $ids = array();
        foreach($data as $v){
            $ids[] = $v['id'];
        }
        if(!$ids){
            $this->return_data(1, array());
        }
        $goods_data = reset_data(M('ShopGoods')->where(array('id' => array('in', $ids), 'status' => 1, 'is_shelf' => 1))->field('id,title,uid,pic,price,num,express_money')->select(), 'id');
        $cart_data = array();
        foreach($data as $v){
            if(empty($goods_data[$v['id']])){
                continue;
            }
            $item = $goods_data[$v['id']];
            $cart_data[$item['uid']][] = array(
                'cid' => $v['cid'],
                'goods_id' => $v['id'],
                'num' => $v['num'],
                'goods_title' => $item['title'],
                'goods_pic' => get_cover_url($item['pic']),
                'goods_num' => $item['num'],
                'express_money' => $item['express_money'],
                'price' => $item['price'],
            );
        }
        $shop_uid = array_keys($cart_data);
        $result = array();
        if($shop_uid){
            $shop_data = M('Shop')->where(array('uid' => array('in', $shop_uid)))->select();
            foreach($shop_data as $v){
                $item = array(
                    'uid' => $v['uid'],
                    'nickname' => get_nickname($v['uid']),
                    'header_pic' => get_header_pic($v['uid']),
                );
                $result[] = array(
                    'shop' => $item,
                    'goods' => $cart_data[$v['uid']]
                );
            }
        }
        $this->return_data(1, $result);
    }
    /**
     * @name cart_num
     * @title 购物车数量
     * @return [num] => 购物车数量
     * @remark 当前接口只做数量统计，不进行排查购物车的商品是否有效。需要排查时，调用购物车列表接口将会自动排查
     */
    public function cart_num(){
        $this->check_account_token();
        $api = new \Addons\Order\Lib\CartLib($this->_uid);



        $data = $api->get_cart();
        $ids = array();
        foreach($data as $v){
            $ids[] = $v['id'];
        }
        if(!$ids){
            $this->return_data(1, array('num' => 0));
        }
        $goods_data = reset_data(M('ShopGoods')->where(array('id' => array('in', $ids), 'status' => 1, 'is_shelf' => 1))->field('id,title,uid,pic,price,num,express_money')->select(), 'id');

        $num = 0;
        foreach($data as $v){
            if(empty($goods_data[$v['id']])){
                continue;
            }
            $num += $v['num'];

        }
        $this->return_data(1, array('num' => $num));






        /*
        $data = $api->get_cart();
        $num = 0;
        foreach($data as $v){
            $num += $v['num'];
        }
        $this->return_data(1, array('num' => $num));
        */
    }
    /**
     * @name cart_update
     * @title 更新购物车商品数量
     * @param string  $goods_id     商品ID
     * @param string  $num          数量（须大于0）
     *
     */
    public function cart_update(){
        $this->check_account_token();
        $this->_check_param(array('goods_id', 'num'));
        $goods_id = I('goods_id', 0, 'intval');
        $num = I('num', 0, 'intval');
        $goods = M('ShopGoods')->where(array('id' => $goods_id, 'status' => 1, 'is_shelf' => 1))->field('id,num')->find();
        if(!$goods){
            $this->return_data(0, '', '商品不存在');
        }
        if($goods['num'] < $num){
            $this->return_data(0, '', '商品库存不足');
        }
        $api = new \Addons\Order\Lib\CartLib($this->_uid);
        if($num > 0 && $api->update_cart($goods_id, $num)){
            $this->return_data(1);
        }
        $this->return_data(0);
    }
    /**
     * @name cart_del
     * @title 删除购物车商品
     * @param string  $cid  购物车ID，从购物车数组中获得，多个ID之间用,间隔
     */
    public function cart_del(){
        $this->check_account_token();
        $this->_check_param(array('cid'));
        $cid = I('cid', '');
        $api = new \Addons\Order\Lib\CartLib($this->_uid);
        $result = $api->del_cart($cid);
        if($result){
            $this->return_data(1);
        }else{
            $this->return_data(0);
        }
    }
}
