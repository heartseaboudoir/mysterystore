<?php
// +----------------------------------------------------------------------
// | Title: 钱包
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 客户端 
// +----------------------------------------------------------------------
namespace Apiv2\Controller;

class WalletController extends ApiController {
    /**
     * @name info
     * @title 钱包
     * @return [money] => 可用余额 <br>[lock_money] => 锁定金额（用户不可操作的部分） <br>[all_money] => 账号总余额（ 可用余额+锁定金额 ）<br>
                [is_bind]   => 是否绑定支付宝（1 是 0 否）<br>[nick_name]=>昵称<br>[avatar] => 头像
     * @remark  当is_bind 为 1时，将返回用户的绑定信息<br>注：<br>1.返回的信息会添加*号密文<br>2.mobile_text 和 email_text 因支付宝机制问题，不一定都有值，需要自行做逻辑判断。
     */
    public function info(){
        $this->check_account_token();
        $result = $this->uc_api('Wallet', 'info', array('uid' => $this->_uid));

        //返回版本号，前端用版本号判断是否使用钱包
        $IOS_BE_PUBLISH_NO = '';
        $model = M('config');
        $strwhere = "`name`='IOS_BE_PUBLISH_NO'" ;
        $wallet_on_off = $model->where($strwhere)->find();
        if(is_array($wallet_on_off) && count($wallet_on_off)>0){
            $IOS_BE_PUBLISH_NO = $wallet_on_off['value'];
        }

        $config = M('wallet_config')->where(array(
            'name' => 'withdraw_fee'
        ))->find();

        if (empty($config) || empty($config['data'])) {
            $withdraw_fee = 0;
        } else {
            $withdraw_fee = $config['data'];
        }
        
        
        $rorder = M('recharge_order')->where(array(
            'uid' => $this->_uid,
            'status' => 1,
        ))->order('id desc')->find();
        
        if (!empty($rorder) && !empty($rorder['money'])) {
            $recharge_last_money = $rorder['money'];
            $recharge_last_give = $rorder['give'];
        } else {
            $recharge_last_money = '0.00';
            $recharge_last_give = '0.00';       
        }

        $data = array(
            'money' => $result['money'],
            'lock_money' => $result['lock_money'],
            'all_money' => $result['all_money'],
            'recharge_money' => $result['recharge_money'],
            'is_bind' => $result['is_bind'],
            'nick_name' => isset($result['nick_name']) ? $result['nick_name'] : '',
            'avatar' => isset($result['avatar']) ? $result['avatar'] : '',
            'email_text' => $result['email_text'],
            'withdraw_fee' => $withdraw_fee,
            'IOS_BE_PUBLISH_NO' => $IOS_BE_PUBLISH_NO,
            'recharge_last_money' => $recharge_last_money,
            'recharge_last_give' => $recharge_last_give,
        );
        $this->return_data(1, $data);
    }
    /**
     * @name bind_alipay
     * @title 绑定支付宝账号
     * @param string $code 支付宝登录后返回的授权码
     * @return  [nick_name] => 昵称 <br>[avatar] => 头像地址
     * @remark  返回信息为支付宝返回，为支付宝用户主动设置，若未设置，则对应字段将返回空字符。
     */
    public function bind_alipay(){
        $this->check_account_token();
        $this->_check_param(array('code'));
        $code = I('code');
        $api = new \Addons\Alipay\Lib\User\Api();
        $result = $api->oauth_token($code);
        if($result['status'] != 1){
            $this->return_data(0, '', '授权失败');
        }
        $token = $result['data']['access_token'];
        $result = $api->userinfo($token);
        if($result['status'] != 1){
            $this->return_data(0, '', '获取授权信息失败');
        }
        $alipay_data = $result['data'];
        if(!empty($alipay_data['is_balance_frozen']) && $alipay_data['is_balance_frozen'] == 'T'){
            $this->return_data(0, '', '支付宝账号被冻结，授权失败');
        }
        $result = $this->uc_api('Wallet', 'bind_alipay', array('uid' => $this->_uid, 'bind_id' => $alipay_data['alipay_user_id'], 'bind_data' => $alipay_data));
        if($result == 1){
            $data = array(
                'nick_name' => !empty($alipay_data['nick_name']) ? $alipay_data['nick_name'] : '',
                'real_name' => !empty($alipay_data['real_name']) ? $alipay_data['real_name'] : '',
                'avatar' => !empty($alipay_data['avatar']) ? $alipay_data['avatar'] : '',
            );
            $this->return_data(1, $data, '授权成功');
        }elseif($result == -1){
            $this->return_data(0, '', '账号已设置了支付宝账号');
        }else{
            $this->return_data(0, '', '设置失败');
        }
    }
    /**
     * @name withdraw_apply
     * @title 发起提现
     * @param int $money   金额，最多保留两位小数
     * @param string $code 手机验证码
     * @return  
     * @remark  
     */
    public function withdraw_apply(){
        $this->check_account_token();
        $this->_check_param(array('money', 'code'));
        $money = I('money');
        $money = round($money, 2);
        if($money <= 0){
            $this->return_data(0, '', '请填写金额');
        }
        $code = I('code', '', 'trim');
        $result = check_code('app_withdraw_code', $this->_uinfo['mobile'], $code);
        if($result['status'] != 1){
            $this->return_data(0, '', $result['msg']);
        }
        $result = $this->uc_api('Wallet', 'withdraw_apply', array('uid' => $this->_uid, 'money' => $money));
        if($result['status'] == 1){
            unset_code('app_withdraw_code', $this->_uinfo['mobile']);
            $this->return_data(1, '', '提现成功');
        }else{
            $this->return_data(0, '', $result['msg']);
        }
    }
    /**
     * @name  get_withdraw_code
     * @title 获取提现申请验证码
     * @return
     * @remark 验证码有效时长为600秒，验证码发送间隔为90秒
     */
    public function get_withdraw_code(){
        $this->check_account_token();
        $code = make_code('app_withdraw_code', $this->_uinfo['mobile']);
        $result = send_sms($this->_uinfo['mobile'], 'SMS_39370204', array('code' => $code, 'product' => '神秘商店'));
        if($result['status'] == 1){
            $this->return_data(1, '', '发送成功');
        }else{
            $this->return_data(0, '', $result['msg']);
        }
    }
    /**
     * @name  log_lists
     * @title 钱包明细
     * @param int     $type     收支类型： 1 收入 2 支出 0 全部（默认0）
     * @param string  $act      操作类型：(withdraw:提现) 默认（空）
     * @param int     $page     页码
     * @return [id] => 记录ID<br>[type] => 收支（1收入 2 支出）<br>[action] => 类型<br>[action_title] => 操作类型标题<br>[money]=>操作金额<br>
                [is_lock]=>是否被锁定（0 否 1 是）<br>[unlock_time] => 解锁时间<br>[create_time] => 创建时间
     */
    public function log_lists(){
        $this->check_account_token();
        $type = I('type', 0, 'intval');
        $act = I('act', '', 'trim');
        $page = I('page', 1, 'intval');
        if($act == 'withdraw'){
            $act = array('withdraw', 'withdraw_return');
        }
        $result = $this->uc_api('Wallet', 'log', array('uid' => $this->_uid, 'type' => $type, 'act' => $act, 'page' => $page));
        $this->return_lists(1, $result['data'], $result['page'], $result['row'], count($result['data']), $result['total']);
    }
    
    
    /**
     * 是否已经设置了支付密码
     */
    public function have_paypwd()
    {
        $this->check_account_token();
        $uid = $this->_uid;        
        
        if (empty($uid)) {
            $this->return_data(0, '', '获取用户信息异常');
        }        
        
        $one = M('wallet')->where(array(
            'uid' => $uid
        ))->find();   
        
        if (empty($one)) {
            $this->return_data(0, '', '获取用户信息异常err-002');
        }
        
        if (empty($one['pay_pwd'])) {
            $this->return_data(1, array(
                'have' => '0',
            ));
        } else {
            $this->return_data(1, array(
                'have' => '1',
            ));
        }

        
    }    
    
    
    /**
     * 设置密码
     */
    public function set_paypwd()
    {
        $this->check_account_token();
        $uid = $this->_uid;

        $paypwd = I('paypwd', '', 'trim');

        if (empty($paypwd)){
            $this->return_data(0, '', '支付密码不能为空');
        }        
        
        if (!preg_match('/^\d{6}$/', $paypwd)){
            $this->return_data(0, '', '支付密码设置不合法');
        }
        
        if (empty($uid)) {
            $this->return_data(0, '', '获取用户信息异常');
        }
        
        
        
        $one = M('wallet')->where(array(
            'uid' => $uid
        ))->find();
        
        if (empty($one)) {
            $this->return_data(0, '', '获取用户信息异常err-002');
        }
        
        if (!empty($one['pay_pwd'])) {
            $this->return_data(0, '', '已经设置过密码');
        }      
        
        
        
        $pay_pwd = md5($paypwd);
        
        $result = M('wallet')->where(array(
            'uid' => $uid
        ))->save(array(
            'pay_pwd' => $pay_pwd,
        ));
        


        $this->return_data(1, '支付密码设置成功');
    }
    

    
    /**
     * 修改密码
     */
    public function modify_paypwd()
    {
        $this->check_account_token();
        $uid = $this->_uid;

        $paypwd_old = I('paypwd_old', '', 'trim');
        
        $paypwd = I('paypwd', '', 'trim');

        if (empty($paypwd_old)){
            $this->return_data(0, '', '原支付密码不能为空');
        }        
        
        if (!preg_match('/^\d{6}$/', $paypwd_old)){
            $this->return_data(0, '', '原支付密码不合法');
        }
        
        if (empty($paypwd)){
            $this->return_data(0, '', '支付密码不能为空');
        }        
        
        if (!preg_match('/^\d{6}$/', $paypwd)){
            $this->return_data(0, '', '支付密码设置不合法');
        }        
        
        
        if (empty($uid)) {
            $this->return_data(0, '', '获取用户信息异常');
        }
        
        $pay_pwd_old = md5($paypwd_old);
        
        $pay_pwd = md5($paypwd); 

        
        $one = M('wallet')->where(array(
            'uid' => $uid
        ))->find();
        
        if (empty($one)) {
            $this->return_data(0, '', '获取用户信息异常err-002');
        }
        
        if ($one['pay_pwd'] != $pay_pwd_old) {
            $this->return_data(0, '', '原支付密码输入错误');
        }          
        
        $result = M('wallet')->where(array(
            'uid' => $uid
        ))->save(array(
            'pay_pwd' => $pay_pwd,
        ));
        
        $this->return_data(1, '支付密码设置成功');
    }    
    
    /**
     * 找回密码
     */
    public function find_paypwd()
    {
        $this->check_account_token();
        $uid = $this->_uid;
        
        if (empty($uid)) {
            $this->return_data(0, '', '获取用户信息异常');
        }        

        $mobile = get_mobile($uid, false);
        
        if (empty($mobile)) {
            $this->return_data(0, '', '获取用户信息异常err-002');
        }  

        
        $paypwd = I('paypwd', '', 'trim');        

        if (empty($paypwd)){
            $this->return_data(0, '', '支付密码不能为空');
        }        
        
        if (!preg_match('/^\d{6}$/', $paypwd)){
            $this->return_data(0, '', '支付密码设置不合法');
        }        
        
        $code = I('code', '', 'trim');
        
        if (empty($code)){
            $this->return_data(0, '', '验证码不能为空');
        }          
        
        // 判断验证码
        $result = check_code('find_paypwd_code', $mobile, $code);
        if($result['status'] != 1){
            $this->return_data(0, '', $result['msg']);
        }        
        
        
        $pay_pwd = md5($paypwd);
        
        $result = M('wallet')->where(array(
            'uid' => $uid
        ))->save(array(
            'pay_pwd' => $pay_pwd,
        ));
        
        unset_code('find_paypwd_code', $mobile);

        $this->return_data(1, '支付密码设置成功');        
        
        
        

    } 


    /**
     * 找回支付密码验证码
     */
    public function get_find_paypwd_code(){
        $this->check_account_token();
        $uid = $this->_uid;
        
        if (empty($uid)) {
            $this->return_data(0, '', '获取用户信息异常');
        }        
        
        
        
        $mobile = get_mobile($uid, false);
        
        if (empty($mobile)) {
            $this->return_data(0, '', '获取用户信息异常err-002');
        }           
        
        
        $code = make_code('find_paypwd_code', $mobile);
        
        
        //$this->return_data(1, '', $code);exit;
        //$result = send_sms($mobile, 'SMS_39185282', array('code' => $code));
        $result = send_sms($mobile, 'SMS_39370204', array('code' => $code, 'product' => '神秘商店'));
        if($result['status'] == 1){
            $this->return_data(1, '', '发送成功');
        }else{
            $this->return_data(0, '', $result['msg']);
        }
    }    
    
    
    
    
    /**
     * 充值账单
     */
    public function recharge_bill()
    {
        $this->check_account_token();
        $uid = $this->_uid;

        $page = I('page', 1, 'intval');

        $row = I('row', 20, 'intval');

        $page < 1 && $page = 1;

        $row < 1 && $row = 1;

        $row > 100 && $row = 100;

        $start = ($page - 1) * $row;

        $sql_count = "select count(*) as c from hii_recharge_order where uid = {$uid} and status = 1";



        $data_count = M()->query($sql_count);



        $total = empty($data_count[0]['c']) ? 0 : $data_count[0]['c'];

        //$this->return_data(1, $total);

        if ($total == 0) {
            $data = array();
        } else {
            $sql = "select id,uid,order_sn,money,give,`type`,update_time from hii_recharge_order where uid = {$uid} and status = 1 order by id desc limit {$start}, {$row}";

            $data = M()->query($sql);

            empty($data) && $data = array();
        }

        
        foreach ($data as $key => $val) {
            $data[$key]['day'] = date('Y-m-d', $val['update_time']);
            $data[$key]['time'] = date('H:i:s', $val['update_time']);
        }

        // $this->return_data(1, $data);
        $this->return_lists(1, $data, $page, $row, count($data), $total);


    }

    /**
     * 充值列表
     */
    public function recharge_list()
    {
        $this->check_account_token();

        $data = M('recharge_list')->field('id,money, give')->select();

        $this->return_data(1, $data);
    }
    

    // 获取充值订单号
    private function getOrderSn()
    {
        $order_sn = 'cz' . date('YmdHis') . mt_rand(10000, 99999);

        return $order_sn;
    }
    
    /**
     * 充值
     */
    public function recharge()
    {
        $this->check_account_token();


        // 充值方式
        $pay_type = I('pay_type', '', 'trim');

        // 充值金额
        //$pay_value = I('pay_value', 0, 'floatval');

        // 充值编号
        $pay_id = I('pay_id', 0, 'intval');

        // 支付方式
        if(!in_array($pay_type, array('wechat', 'alipay'))) {
            $this->return_data(0, '', '未知的支付方式');
        }

        if ($pay_type == 'wechat') {
            $type = 1;
        } else {
            $type = 2;
        }

        // 支付金额
        /*
        if ($pay_value <= 0) {
            $this->return_data(0, '', '不合法的支付金额');
        }
        */

        if ($pay_id <= 0) {
            $this->return_data(0, '', '不合法的支付编号-01');
        }

        $pay_info = M('recharge_list')->where(array(
            'id' => $pay_id
        ))->find();

        if (empty($pay_info) || empty($pay_info['money'])) {
            $this->return_data(0, '', '不合法的支付编号-02');
        }

        $pay_value = $pay_info['money'];
        $pay_give = $pay_info['give'] > 0 ? $pay_info['give'] : 0;

        /*
        1,生成充值订单


        2,发起支付

        3,支付成功回调,调整充值订单状态

        */

        $this_time = time();
        $order_sn = $this->getOrderSn();

        $order = array(
            'uid' => $this->_uid,
            'type' => $type,
            'order_sn' => $order_sn,
            'money' => $pay_value,
            'give' => $pay_give,
            'create_time' => $this_time,
            'update_time' => $this_time,

        );

        // 创建订单
        $res_add = M('recharge_order')->add($order);



        // 发起支付
        switch($pay_type){
            case 'wechat':
                $wx_data = array(
                    'body' => '神秘商店-充值',
                    'attach' => json_encode(array('order_sn' => 'test')),
                    'fee' => $pay_value * 100,
                    'sn' => $order_sn,
                    'trade_type' => 'APP',
                );
                A('Addons://WechatPay/WechatPayclass')->set_config('app');
                $wx_pay_data = A('Addons://WechatPay/ZkPayclass')->unifiedorder($wx_data, U('Apiv2/Callback/wx_recharge_notify'));
                
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
                    'subject' => '神秘商店-充值',
                    'body' => json_encode(array('order_sn' => 'test')),
                    'total_fee' => $pay_value,
                    //'goods_detail' => array(),
                    'sn' => $order_sn,
                    'it_b_pay' => '15d',
                );
                //A('Addons://Alipay/F2fpayclass')->set_config();
                $alipay_data = A('Addons://Alipay/Zkpayclass')->app_order($ali_data, U('Apiv2/Callback/ali_recharge_notify'));
                $result['alipay_data'] = $alipay_data;
                $this->return_data(1, $result);
                break;
            default:
                $this->return_data(0, '', '请选择支付方式');
        }        
    }

    public function testinfo()
    {
        $this->check_account_token();

        $result = $this->getWallet();

        $this->return_data(1, $result);
    }
    
}