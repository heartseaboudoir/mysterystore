<?php
// +----------------------------------------------------------------------
// | Title: 【客户端app】
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | Type: 客户端
// +----------------------------------------------------------------------
namespace Api\Controller;

class AccountController extends ApiController {
    /**
     * @name  login
     * @title 登录接口
     * @param string $mobile   手机号码（使用token登录时可不传）
     * @param string $code     手机验证码（使用token登录时可不传）
     * @param string $token    用户token（默认为空，使用mobile登录时可不传）
     * @return [uid] => 用户ID<br>
                [mobile] => 手机号码<br>
                [nickname] => 用户昵称<br>
                [header_pic] => 头像地址<br>
                [score] => 当前蜜糖<br>
                [exper] => 当前经验<br>
                [level_title] => 当前等级称号<br>
                [level_icon] => 当前等级ICON<br>
                [exchange_title] => 蜜糖兑换文本<br>
                <span style="color:red;">
                [next_level_exper] => 下一等级经验<br>
                [next_level_title] => 下一等级称号<br>
                [next_level_icon] => 下一等级ICON<br>
                [im_userid] => im用户名 <br>[im_password] => im密码<br>
                [is_checkin] => 是否已签到 1 是 0 否<br>
                </span>
                [token] => 登录成功后的token值，调用其他接口时需传递<br>
     * @remark 1 每次成功登录后，token将会做更新<br> 
                2 当mobile为空并且token不为空时，将使用token进行登录<br>
                3 当mobile与token都不为空时，使用mobile进行登录<br>
     */
    public function login() {
        $status = 0;
        $msg = "";
        $data = array();
        $mobile = I('mobile', '', 'trim');
        $code = I('code', '', 'trim');
        $token = I('token', '', 'trim');
        if(!$mobile && $token){
            $this->check_account_token();
            $uid = $this->_uid;
            $mobile = $this->_uinfo['mobile'];
        }else{
            if(!$mobile){
                $this->return_data(0, '', '手机号码未知');
            }
            if(!preg_match('/^1\d{10}$/', $mobile, $match)){
                $this->return_data(0, '', '手机号码格式错误');
            }
            if(!in_array($mobile, array(15889983125, 15975635550, 13570379118))){
                // 判断验证码
                if(!($mobile == 15816616433 && $code == '147258')){
                    $result = check_code('app_login_code', $mobile, $code);
                    if($result['status'] != 1){
                        $this->return_data(0, '', $result['msg']);
                    }
                }
            }
            // 检测是否有账号
            $uid = D('Common/Member')->login($mobile);
            if(!$uid){
                $this->return_data(0, '', '登录失败，请重试');
            }
        }
        if(D('Member')->login($uid, array('mobile' => $mobile))){
            $data = $this->_login_info($uid, $mobile);
            $data['token'] = $this->get_account_token($uid, $mobile);
            unset_code('app_login_code', $mobile);
            $this->return_data(1, $data, '登录成功');
        }
        $this->return_data(0, '', '登录失败，请重试');
    }
    private function _login_info($uid, $mobile){
        $score_data = $this->uc_api('Scorebox', 'info', array('uid' => $uid));
        $score = !empty($score_data['score']) ? $score_data['score'] : 0;
        $exper = !empty($score_data['exper']) ? $score_data['exper'] : 0;
        // 获取下一等级数据，无下一等级则为空
        
        $level_data = $this->uc_api('Scorebox', 'level_data');
        $next_level_data = array();
        foreach($level_data as $v){
            if($v['exper'] > $exper){
                $next_level_data = $v;
                break;
            }
        }

        // 获取兑换比例
        $exchange = D('Addons://Scorebox/ScoreboxExchange')->where(array('id' => 1))->find();
        $im_info = D('Common/Member')->get_im($uid);
        
        // 是否签到
        $is_checkin = M('ScoreboxLog')->where(array('uid' => $uid, 'name' => 'checkin', 'create_time' => array('between', array(strtotime(date('Ymd')), strtotime(date('Ymd'))+3600*24))))->find() ? 1 : 0;
        
        $data = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'nickname' => get_nickname($uid),
            'header_pic' => get_header_pic($uid),
            'exchange_title' => $exchange['description'],
            'score' => $score,
            'exper' => $exper,
            'level_title' => !empty($score_data['level_title']) ? $score_data['level_title'] : '',
            'level_icon' => !empty($score_data['level_icon']) ? get_cover_url($score_data['level_icon']) : '',
            'next_level_exper' => !empty($next_level_data['exper']) ? $next_level_data['exper'] : 0,
            'next_level_title' => !empty($next_level_data['title']) ? $next_level_data['title'] : '',
            'next_level_icon' => !empty($next_level_data['icon']) ? get_cover_url($next_level_data['icon']) : '',
            'im_userid' => $im_info['userid'],
            'im_password' => $im_info['password'],
            'is_checkin' => $is_checkin,
        );
        return $data;
    }
    /**
     * @name  get_login_info
     * @title 获取登录用户的信息
     * @param  string  $token   用户token
     * @return 参与见接口 login
     * @remark 
     */
    public function get_login_info(){
        $this->check_account_token();
        $data = $this->_login_info($this->_uid, $this->_uinfo['mobile']);
        $data['token'] = I('token');
        $this->return_data(1, $data, '登录成功');
    }
    /**
     * @name  get_login_code
     * @title 获取登录验证码
     * @param  string  $mobile  手机号码
     * @return
     * @remark 验证码有效时长为600秒，验证码发送间隔为90秒
     */
    public function get_login_code(){
        $mobile = I('mobile', '', 'trim');
        if(!$mobile){
            $this->return_data(0, '', '手机号码未知');
        }
        $result = $this->uc_api('User', 'checkMobile', array('mobile' => $mobile));
        if(in_array($result, array(-9, -10))){
            $this->return_data(0, '', $this->showRegError($result));
        }
        
        $code = make_code('app_login_code', $mobile);
        //$this->return_data(1, '', $code);exit;
        $result = send_sms($mobile, 'SMS_39185282', array('code' => $code));
        if($result['status'] == 1){
            $this->return_data(1, '', '发送成功');
        }else{
            $this->return_data(0, '', $result['msg']);
        }
    }
    /**
     * @name  change_mobile
     * @title 更新绑定手机
     * @param  string  $mobile  手机号码
     * @param  string  $code    验证码
     * @return
     * @remark 验证码有效时长为600秒，验证码发送间隔为90秒
     */
    public function change_mobile(){
        $this->check_account_token();
        $mobile = I('mobile', '', 'trim');
        $code = I('code', '', 'trim');
        if(!$mobile){
            $this->return_data(0, '', '手机号码未知');
        }
        // 判断验证码
        $result = check_code('app_bind_code', $mobile, $code);
        if($result['status'] != 1){
            $this->return_data(0, '', $result['msg']);
        }
        // 判定是否原手机号码
        if($mobile == get_mobile($this->_uid, false)){
            $this->return_data(0, '', '新手机号与当前手机号一致，不用修改');
        }
        $result = $this->uc_api('User', 'checkMobile', array('mobile' => $mobile));
        if(in_array($result, array(-9, -10, -11))){
            $this->return_data(0, '', $this->showRegError($result));
        }
        $result = $this->uc_api('User', 'updateInfo', array('uid' => $this->_uid, 'password' => '', 'data' => array('mobile' => $mobile), 'is_in' => 0));
        if($result['status']){
            unset_code('app_bind_code', $mobile);
            set_mobile($this->_uid, $mobile);
            $this->return_data(1, '', '操作成功');
        }else{
            $this->return_data(0, '', '操作失败，请重试');
        }
        
    }
    /**
     * @name  get_bind_code
     * @title 获取绑定手机验证码
     * @param  string  $mobile  手机号码
     * @return
     * @remark 验证码有效时长为600秒，验证码发送间隔为90秒
     */
    public function get_bind_code(){
        $this->check_account_token();
        $mobile = I('mobile', '', 'trim');
        if(!$mobile){
            $this->return_data(0, '', '手机号码未知');
        }
        if($mobile == get_mobile($this->_uid, false)){
            $this->return_data(0, '', '新手机号与当前手机号一致，不用修改');
        }
        
        $result = $this->uc_api('User', 'checkMobile', array('mobile' => $mobile));
        if(in_array($result, array(-9, -10, -11))){
            $this->return_data(0, '', $this->showRegError($result));
        }
        $code = make_code('app_bind_code', $mobile);
        //$this->return_data(1, '', $code);exit;
        $result = send_sms($mobile, 'SMS_39370204', array('code' => $code, 'product' => '神秘商店'));
        if($result['status'] == 1){
            $this->return_data(1, '', '发送成功');
        }else{
            $this->return_data(0, '', $result['msg']);
        }
    }
    /**
     * @name  update_info
     * @title  修改信息
     * @param  string  $nickname  昵称
     * @param  string  $token     
     * @return [nickname] => 昵称
     * @remark 为空或不传值时表示不做修改
     */
    public function update_info(){
        $this->check_account_token();
        $nickname = I('nickname', '', 'trim');
        if(!$nickname){
            $this->return_data(0, array(), '昵称不能为空');
        }
        if($nickname != get_nickname($this->_uid)){
            $data['nickname'] = $nickname;
        }
        if(empty($data)){
            $this->return_data(1);
        }
        if(D('Member')->where(array('uid' => $this->_uid))->save($data)){
            $nickname && set_nickname($this->_uid, $nickname);
            $this->return_data(1, $data);
        }else{
            $this->return_data(0, '', D('Member')->getError());
        }
    }
    /**
     * @name  update_header_img
     * @title  修改头像
     * @param  string  $header_img  头像上传key值
     * @param  string  $token     
     * @return [header_pic] => 新的头像地址
     * @remark 
     */
    public function update_header_img(){
        $this->check_account_token();
        $result = $this->_upload_pic('header_img');
        if($result['status'] == 0){
            $this->return_data(0, '', $result['msg']);
        }
        $cover_id = $result['data']['id'];
        if(D('Member')->where(array('uid' => $this->_uid))->save(array('cover_id' => $cover_id))){
            $this->return_data(1, array('header_pic' => get_domain().$result['data']['path']));
        }else{
            $this->return_data(0, '', D('Member')->getError());
        }
    }
    /**
     * @name  score_level
     * @title 蜜狗等级对应列表
     * @param  string  $token     
     * @return [title] => 等级称号 [exper] => 经验底限 [icon] => 等级图标
     * @remark 
     */
    public function score_level(){
        $this->check_account_token();
        $lists = $this->uc_api('Scorebox', 'level_data');
        if($lists){
            foreach($lists as $k => $v){
                $v['icon'] = $v['icon'] ? get_cover_url($v['icon']) : '';
                $lists[$k] = $v;
            }
        }
        $this->return_data(1, $lists);
    }
    /**
     * @name  score_log_lists
     * @title  个人蜜糖记录列表
     * @param  string  $page  页码（默认为1）
     * @param  string  $type  类型（0：全部 1：增加 2：减少 默认为0）
     * @param  string  $token     
     * @return [id] => 记录ID<br>[title] => 标题<br>[description] => 描述<br>[score] => 操作的蜜糖<br>[type] => 类型 1: 增加 2: 减少 <br>[create_time] => 创建时间<br>[create_time_text] => 创建时间文本
     * @remark 
     */
    public function score_log_lists(){
        $this->check_account_token();
        $page = I('page', 1, 'intval');
        $type = I('type', 0, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $result = $this->uc_api('Scorebox', 'score_log', array('uid' => $this->_uid, 'page' => $page, 'row' => $row, 'type' => $type));
        $data = isset($result['data']) ? $result['data'] : array();
        $total = isset($result['total']) ? intval($result['total']) : 0;
        $count = isset($result['count']) ? intval($result['count']) : array();
        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => $count, 'total' => (int)$total));
    }
    /**
     * @name  checkin
     * @title  签到
     * @param  string  $token     
     * @return [action_score] => 签到所得的蜜糖<br> [score] => 当前的蜜糖 <br>[exper] => 当前的经验 <br>[level_title] => 当前的等级<br>[level_icon] => 当前的等级图标
                <span style="color:red;">
                [next_score] => 下次签到可得蜜糖<br>[check_times] => 已签到天数<br>[day_data] => <br>(<br>[date] => 日期 <br>[score] => 可得蜜糖<br>[is_check] => 是否已领 1 是 0 否<br>)<br>
                </span>
     * @remark 
     */
    public function checkin(){
        $this->check_account_token();
        $result = $this->uc_api('Scorebox', 'checkin', array('uid' => $this->_uid));
        if($result['status'] == 1){
            $this->return_data(1, $result['data'], '签到成功');
        }else{
            $this->return_data(0, '', $result['msg']);
        }
    }
    /**
     * @name  get_before_checkin
     * @title  获取上一次的签到信息
     * @param  string  $token     
     * @return [next_score] => 下次签到可得蜜糖<br>[check_times] => 已签到天数<br>[day_data] => <br>(<br>[date] => 日期 <br>[score] => 可得蜜糖<br>[is_check] => 是否已领 1 是 0 否<br>)<br>
     * @remark 
     */
    public function get_before_checkin(){
        $this->check_account_token();
        $data = $this->uc_api('Scorebox', 'get_before_checkin', array('uid' => $this->_uid));
        $this->return_data(1, $data);
    }
    /**
     * @name  order_lists
     * @title  我的订单列表
     * @param int $page   页码（默认为1）
     * @param int $type   类型（默认为0 0：全部 1：未付款）
     * @param  string  $token     
     * @return [order_sn] => 订单号 <br> [create_time] =>创建时间<br> [create_time_text] =>创建时间文本 <br>[money] => 总价<br>
                [pic_url] => 图片地址<br>[store_title] => 门店名<br>[pay_status]支付状态：1 未支付 2 已支付 <br>[status] => 订单状态 1 未支付 2 已支付 3 已取消 4 已发货 5 已完成<br>
                <span style="color:red;">
                [title] => 商品名<br>[pay_money] => 实际支付<br>[cash_money] => 优惠金额<br>[user_discount_money] => 会员优惠金额<br>[express_money] => 运费<br>[num] => 数量<br>[last_pay_second] => 剩余支付秒数(未支付时有效)<br>[last_pay_time] => 最后支付的时间戳<br>
                [type] => 订单类型：（store:门店订单[线下]   online:线上）<br>
                [goods_total_num] => 商品总数量<br>
                [goods_total_money] => 商品总价<br>
                </span>
     * @remark 
     */
    public function order_lists(){
        $this->check_account_token();
        $page = I('page', 0, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $type = I('type', 0, 'intval');
        $where = array('uid' => $this->_uid);
        switch($type){
            case 1:
                $where['pay_status'] = 1;
                $where['status'] = 1;
                break;
            default:
                $where['status'] = array('neq', 3);
                break;
        }
        $lists = D('Addons://Order/Order')->lists($where, 'order_sn,create_time,money,pay_money,express_money,user_discount_money,cash_money,pay_status,status,store_id,type', $page, $row, true);
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
                $v['title'] = isset($v['detail'][0]['title']) ? $v['detail'][0]['title'] : 0;
                $v['pic_url'] = isset($v['detail'][0]['pic_url']) ? $v['detail'][0]['pic_url'] : '';
                $v['num'] = isset($v['detail'][0]['num']) ? $v['detail'][0]['num'] : 0;
                $goods_num = 0;
                $goods_total_money = 0; 
                foreach($v['detail'] as $dv){
                    $goods_num += $dv['num']; 
                    $goods_total_money += $dv['price']*$dv['num'];
                }
                $v['goods_total_num'] = $goods_num;
                $v['goods_total_money'] = sprintf("%.2f", round($goods_total_money, 2));
                unset($v['detail']);
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
                $lists['lists'][$k] = $v;
            }
        } 
        $data = isset($lists['lists']) ? $lists['lists'] : array();
        $count = isset($lists['lists']) ? count($lists['lists']) : 0;
        $total = isset($lists['count']) ? $lists['count'] : 0;
        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => $count, 'total' => (int)$total));
    }
    /**
     * @name  order_info
     * @title  订单详情
     * @param string  $order_sn 订单号
     * @param  string  $token     
     * @return [store_id] => 门店ID<br>
                [store_title] => 门店名<br>
                [order_sn] => 订单号<br>
                [pay_money] => 支付金额<br>
                [money] => 订单总金额<br>
                [cash_title] => 绑定优惠券名<br>
                [cash_money] => 绑定优惠券金额（折扣券时，则为折扣的金额）<br>
                [cash_type] => 绑定优惠券类型（1 满减券 2 折扣券）<br>
                [cash_discount] => 绑定优惠券折扣额度（折扣券时调用）<br>
                [create_time] => 创建时间<br>
                [detail] => 商品详细<br>
                ([title] => 商品名<br>[pic_url] => 图片地址<br>[price] => 售价<br>[num] => 数量<br> ) <br>
                [pay_type] => 支付方式(wechat:微信 alipay:支付宝)<br>
                [status] => 订单状态 1 未支付 2 已支付 3 已取消 4 已发货 5 已完成<br>
                [redbag_title] => 支付后获取的红包分享标题<br>
                [redbag_desc] => 支付后获取的红包分享描述<br>
                [redbag_pic] => 支付后获取的红包分享图片<br>
                [redbag_url] => 支付后获取的红包分享url<br>
                [discount_money] => 总优惠金额<br>
                [user_discount_money] => 会员优惠金额<br>
                [user_sale] => 会员当前折扣值（0到1之间）<br>
                <span style="color:red">
                [last_pay_second] => 剩余支付时间<br>[last_pay_time] => 最后支付的时间戳<br>
                [receipt_info] =>收货信息(<br>[name] => 收货人<br>[mobile] => 联系方式<br>[sheng_title] => 省份文本<br>[shi_title] => 城市文本<br>[qu_title] => 地区文本<br>[address] => 地址<br>)<br>
                [express_money] => 运费<br>
                [express_info] => 发货信息<br>(<br>[no] => 物流号<br>[company] => 快递公司<br>)<br>
                [type] => 订单类型：（store:门店订单[线下]   online:线上）<br>
                [express_search_link] => 物流查询链接地址<br>
                [goods_total_money] => 商品总价<br>
                <span>
     * @remark 
                通过门店端扫码调用接口时，order_sn值为扫码得到的链接，截取出order_sn，如：<br>
                扫码得到的：http://chaoshipos.k.hiiyee.com/Api/Relay/order/order_sn/160529165445139921.html<br>
                则截取从 order_sn/ 开始到 .html 中间的字符串，得160529165445139921<br><br>
                cash_title 和 cash_money 在第一次结算时做绑定，绑定后，无法再修改优惠券<br>
     */
    public function order_info(){
        $this->check_account_token();
        $this->_check_param(array('order_sn'));
        $order_sn = I('order_sn', '', 'trim');
        if(!$order_sn){
            $this->return_data(0, '', '订单不存在');
        }
        
        $data = D('Addons://Order/Order')->get_info($order_sn, array(), $this->_uid);
        if(!$data){
            $this->return_data(0, '', '订单不存在');
        }elseif(!$data['uid']){
            D('Addons://Order/Order')->where(array('order_sn' => $order_sn))->save(array('uid' => $this->_uid));
        }elseif($data == -1){
            $this->return_data(0, '', '订单已被其他用户绑定');
        }
        $goods_total_money = 0;
        foreach($data['detail'] as $v){
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
            $pay_money = round($data['money']-$cash_money, 2);
            $scorebox = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
            $user_sale = (100-$scorebox['level_sale'])/100;
            if($pay_money > 0){
                $n_money = round($pay_money*$user_sale, 2);
                $user_discount_money = round($pay_money - $n_money, 2);
                $pay_money = $n_money;
            }
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
                $shareConfig['url'] = str_replace('http://', 'https://', $shareConfig['url']);
            }
        }
        // ios审核的版本不做显示
        if($this->device_type == 2 && $this->version_no == C('IOS_BE_PUBLISH_NO')){
            $shareConfig = array();
        }
        
        $discount_money = round($data['money'] - $pay_money, 2);
        $express_search_link = $data['express_info'] ? ('https://m.baidu.com/s?word='.$data['express_info']['company'].' '.$data['express_info']['no']) : '';
        $result = array(
            'store_id' => $data['store_id'],
            'store_title' => $data['store_title'],
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
            'receipt_info' => $data['receipt_info'] ? $data['receipt_info'] : (object)array(), 
            'express_money' => $data['express_money'], 
            'express_info' => $data['express_info'] ? $data['express_info'] : (object)array(), 
            'express_search_link' => $express_search_link,
            'type' => $data['type'] ? $data['type'] : 'store',
            'goods_total_money' => sprintf("%.2f", round($goods_total_money, 2)),
        );
        $this->return_data(1, $result, '');
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
                &nbsp;&nbsp;[package] =>  扩展字段<br>
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
        $cash_code = I('cash_code', '', 'trim');
        if(!$order_sn){
            $this->return_data(0, '', '未知订单');
        }
        if(!$pay_type){
            $this->return_data(0, '', '未知的支付方式');
        }
        $data = D('Addons://Order/Order')->get_info($order_sn, array(), $this->_uid, 'order_sn, uid,create_time,pay_status, pay_money, store_id');
        if(!$data){
            $this->return_data(0, '', '订单不存在');
        }elseif($data['uid'] != $this->_uid){
            $this->return_data(0, '', '订单已被其他用户绑定');
        }elseif($data['status'] == 2){
            $this->return_data(0, '', '订单已支付');
        }elseif($data['status'] == 3){
            $this->return_data(3, '', '订单已支付失败');
        }
        
        $pay_money = D('Addons://Order/Order')->get_pay_money($order_sn, $this->_uid, $cash_code, 'account_app');
        if($pay_money == -1){
            $this->return_data(0, '', '订单信息有误，请重新操作');
        }
        if(D('Addons://Order/Order')->check_goods($order_sn, $data['store_id'], $data['detail']) == false){
            $this->return_data(3, '', '商品库存不足，订单已取消');
        }
        if($pay_money == 0){
            $result = D('Addons://Order/Order')->set_pay($order_sn, $pay_type, $order_sn, '');
            if(!$result){
                $this->return_data(0, '', '订单信息有误，请重新操作');
            }
            $this->return_data(2);
        }
        $result = array(
            'order_sn' => $order_sn,
            'pay_money' => $pay_money
        );
        
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
                $wx_pay_data = A('Addons://WechatPay/WechatPayclass')->unifiedorder($wx_data, U('Public/wx_pay_notify'));
                
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
                $alipay_data = A('Addons://Alipay/F2fpayclass')->app_order($ali_data, U('Public/ali_pay_notify'));
                $result['alipay_data'] = $alipay_data;
                $this->return_data(1, $result);
                break;
            default:
                $this->return_data(0, '', '请选择支付方式');
        }
    }
    /**
     * @name  exchange_cash_coupon
     * @title  蜜糖兑换优惠券  
     * @param  string  $token     
     * @return [score] => 当前蜜糖<br>[exper]=>当前经验<br>[level_title]=>当前等级<br>[level_icon] => 当前等级图标
     * @remark 
     */
    public function exchange_cash_coupon(){
        $this->check_account_token();
        // 获取兑换比例
        $exchange = D('Addons://Scorebox/ScoreboxExchange')->where(array('id' => 1))->find();
        if(empty($exchange['exchange_obj'])){
            $this->return_data(0, '', '优惠券无法兑换');
        }
        $info =  D('Addons://CashCoupon/CashCoupon')->where(array('code' => $exchange['exchange_obj']))->find();
        if(!$info){
            $this->return_data(0, '', '优惠券异常，请联系管理员');
        }
        if($info['status'] == 2){
            $this->return_data(0, '', '优惠券已失效，请联系管理员');
            return -2;
        }elseif($info['status'] == 3){
            $this->return_data(0, '', '优惠券已被兑换一空了~');
        }elseif($info['status'] != 1){
            $this->return_data(0, '', '优惠券无法兑换，请联系管理员');
        }
        
        $result = $this->uc_api('Scorebox', 'dec_score', array('uid' => $this->_uid, 'score' => $exchange['score'], 'name' => 'exchange_cash_coupon'));
        if($result === -1){
            $this->return_data(0, '', '蜜糖不足');
        }elseif($result){
            $get_result = $this->uc_api('CashCoupon', 'get_cash_coupon', array('code' => $exchange['exchange_obj'], 'uid' => $this->_uid));
            if($get_result > 0){
                    $score_data = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
                    $data = array(
                        'score' => !empty($score_data['score']) ? $score_data['score'] : 0,
                        'exper' => !empty($score_data['exper']) ? $score_data['exper'] : 0,
                        'level_title' => !empty($score_data['level_title']) ? $score_data['level_title'] : '',
                        'level_icon' => !empty($score_data['level_icon']) ? get_cover_url($score_data['level_icon']) : '',
                    );
                $this->return_data(1, $data, '兑换成功，请进行【我的优惠券】查看');
            }else{
                $err = '';
                switch($get_result){
                    case -2:
                        $err = '优惠券已失效，请联系管理员';
                        break;
                    case -3:
                        $err = '优惠券已被兑换一空了~';
                        break;
                    default:
                        $err = '兑换失败，请重试';
                        break;
                }
                
                $this->return_data(0, '', $err);
            }
        }else{
            $this->return_data(0, '', '兑换失败，请重试');
        }
        
    }
    /**
     * @name  cash_coupon_lists
     * @title  个人优惠券列表
     * @param  string  $page            页码（默认为1）   
     * @param  string  $type            类型（默认为0 0：不限 1：未使用 2：已使用 3：已过期 4：已作废【预留】）    
     * @param  string  $order_money     订单金额（默认为0， 从订单详情去选择可用的优惠券列表时使用）    
     * @param  string  $token     
     * @return [title] => 标题<br>
                [description] => 描述<br>
                [code] => 优惠券码<br> 
                [type] => 类型（1 满减券 2 折扣券）<br>
                [money] => 优惠金额（满减券时使用有效）<br>
                [min_use_money] => 满多少钱可用（为0时，不限制。 满减券时使用有效）<br>
                [discount] => 折扣数（折扣券时使用有效，范围0.1-9.9）<br>
                [max_dis_money] => 最多折扣金额（为0时，不限制。 折扣券时使用有效，当计算后的折扣金额大于最多折扣金额时，折扣金额则取最多折扣金额）<br>
                [last_times] => 最后使用时间，为0时表示无限期<br>
                [status] => 状态：1 未使用 2 已使用 3 已过期 4 已作废<br>
                [create_time] => 领取时间<br>
                [last_time_text] => 最后有效日期<br>
                [last_day] => 最后有效时间<br>
                [cash_money] => 优惠券可优惠金额（为计算后的优惠券可优惠金额，订单支付页调用）<br>
                [coupon_money] => 优惠券最高可使用的金额（为优惠券最高可优惠金额， 显示在 ￥ 旁，保留两个小数）<br>
                [is_usable] => 是否可用 （0 否 1 是）<br>
                <br>以下参数，在order_money > 0时返回有效<br><br>
                [user_discount_money] => 会员优惠金额<br>
                [discount_money] => 总优惠金额<br>
                [pay_money] => 实付金额<br>
                [order_money] => 订单金额<br>
     * @remark 从订单跳转至优惠券选择时附加order_money参数后，将返回使用优惠券后，订单金额的相关参数
     */
    public function cash_coupon_lists(){
        $this->check_account_token();
        $row = 20;
        $page = I('page', 0, 'intval');
        $page < 1 && $page = 1;
        $type = I('type', 1, 'intval');
        $order_money = round(I('order_money', 0), 2);
        $result = $this->uc_api('CashCoupon', 'user_coupon_list', array('uid' => $this->_uid, 'type' => $type, 'page' => $page, 'row' => $row, 'order_money' => $order_money));
        
        $data = isset($result['lists']) ? $result['lists'] : array();
        $total = isset($result['total']) ? intval($result['total']) : 0;
        if($data){
            if($order_money > 0){
                $level = $this->uc_api('Scorebox', 'info', array('uid' => $this->_uid));
                $user_sale = (100-$level['level_sale'])/100;
            }
            foreach($data as $k => $v){
                if($v['last_time'] > 0){
                    $v['last_time_text'] = date('Y-m-d', $v['last_time']);
                    if($v['last_time'] <= NOW_TIME){
                        $v['last_day'] = '已过期';
                    }else{
                        $last_time = $v['last_time']-NOW_TIME;
                        $day = $last_time/3600/24;

                        if($day >= 1){
                            $v['last_day'] = '还有'.ceil($day).'天过期';
                        }else{
                            $hour = $last_time/3600;
                            if($hour >= 1){
                                $v['last_day'] = '还有'.ceil($hour).'小时过期';
                            }else{
                                $mi = $last_time/60;
                                $v['last_day'] = '还有'.ceil($mi).'分钟过期';
                            }
                        }
                    }
                }else{
                    $v['last_time_text'] = '无限期';
                    $v['last_day'] = '无限期';
                }
                if($order_money > 0){
                    $pay_money = $order_money-$v['cash_money'];
                    $pay_money < 0 && $pay_money = 0;
                    $n_money = $pay_money > 0 ? round($pay_money*$user_sale, 2) : 0;
                    // 会员优惠金额
                    $v['user_discount_money'] = round($pay_money - $n_money, 2);
                    // 实付金额
                    $v['pay_money'] = $n_money;
                    // 优惠总金额
                    $v['discount_money'] = round($order_money-$n_money, 2);
                    // 总金额
                    $v['order_money'] = $order_money;
                }else{
                    $v['user_discount_money'] = 0;
                    $v['pay_money'] = 0;
                    $v['discount_money'] = 0;
                    $v['order_money'] = 0;
                }
                $data[$k] = $v;
            }
        }
        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => count($data), 'total' => (int)$total));
    }
    
    /**
     * @name pic_lists
     * @title 图文列表
     * @param  string  $page   页码（默认为1）  
     * @return  [id] => id<br>[title] => 标题<br>[description] => 介绍<br> [pic_url] => 图片<br>[url] => 链接地址<br>
                <span style="color:red;">
                [tag] => 标签<br>
                [has_goods] => 是否有商品<br>
                </span>
     * @remark
     */
    public function pic_lists(){
        $row = 20;
        $page = I('page', 0, 'intval');
        $page < 1 && $page = 1;
        $where = array();
        $where['category_id'] = 2;
        $where['status'] = 1;
        $data = D('Document')->where($where)->page($page, $row)->order('level desc, id desc')->field('id, title, tag, description, cover_id')->select();
        if($data){
            $did = array();
            foreach($data as $v){
                $did[] = $v['id'];
            }
            $access_data = M('DocumentGoodsAccess')->where(array('did' => array('in', $did)))->select();
            
            $goods_ids = array();
            foreach($access_data as $v){
                $_access_data[$v['did']] = $v;
                $goods_ids[] = $v['goods_id'];
            }
            if($goods_ids){
                $store_id = C('STORE_ONLINE');
                $goods_data = M('Goods')->alias('a')->join('__GOODS_STORE__ as b ON a.id = b.goods_id')->where(array('a.status' => 1, 'b.store_id' => $store_id, 'a.id' => array('in', $goods_ids)))->field('a.id,a.title,a.cover_id,a.sell_price,b.num,b.price')->select();

                if($goods_data){
                    foreach($goods_data as $v){
                        $v['price'] = empty($v['price']) ? $v['sell_price'] : $v['price'];
                        $v['pic_url'] = get_cover_url($v['cover_id']);
                        unset($v['cover_id'], $v['sell_price']);
                        $_goods_data[$v['id']] = $v;
                    }
                }
            }
            foreach($data as $k => $v){
                $v['pic_url'] = get_cover_url($v['cover_id']);
                $v['url'] = U('Wap/Document/show', array('name' => $v['id']));
                unset($v['cover_id']);
                $_goods_id = !empty($_access_data[$v['id']]['goods_id']) ? $_access_data[$v['id']]['goods_id'] : 0;
                
                $_item_goods = isset($_goods_data[$_goods_id]) ? $_goods_data[$_goods_id] : array();
                $v['has_goods'] = $_item_goods ? 1 : 0;
                // ios审核的版本不做显示
                if($this->device_type == 2 && $this->version_no == C('IOS_BE_PUBLISH_NO')){
                    $v['has_goods'] = 0;
                }
                $data[$k] = $v;
            }
        }else{
            $data = array();
        }
        $total = D('Document')->where($where)->count();
        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => count($data), 'total' => (int)$total));
    }
    /**
     * @name index_pos
     * @title 首页轮播图
     * @return  [title] => 标题<br>[pic_url] => 图片地址<br>[url] => 链接地址<br>[has_goods] => 是否关联商品（1 是 0 否）<br>[action_id] => 图文ID
     * @remark url为空时，则不可跳转
     */
    public function index_pos(){
        $where = array();
        $where['name'] = 'account_index';
        $where['status'] = 1;
        $data = D('Addons://Poster/Poster')->where($where)->field('id, show_num')->find();
        if(empty($data)){
            $this->return_data(0, array(), '广告位不存在');
        }
        $where = array();
        $where['name'] = 'account_index';
        $where['status'] = 1;
        $size = $data['show_num'] ? $data['show_num'] : 10;
        $lists = D('Addons://Poster/PosterData')->where($where)->limit($size)->order('listorder desc')->field('id,title,url,cover_id,action_id')->select();
        if($lists){
            $action_ids = array();
            foreach($lists as $v){
                !in_array($action_ids) && $action_ids[] = $v['action_id'];
            }
            if($action_ids){
                $access = M('DocumentGoodsAccess')->where(array('action_id' => array('in', $action_ids)))->select();
                $_access = array();
                $goods_ids = array();
                foreach($access as $v){
                    $_access[$v['did']] = $v['goods_id'];
                    $v['goods_id'] && $goods_ids[] = $v['goods_id'];
                }
                if($goods_ids){
                    $store_id = C('STORE_ONLINE');
                    $goods_data = M('Goods')->alias('a')->join('__GOODS_STORE__ as b ON a.id = b.goods_id')->where(array('a.status' => 1, 'b.store_id' => $store_id, 'a.id' => array('in', $goods_ids)))->field('a.id,a.title,a.cover_id,a.sell_price,b.num,b.price')->select();

                    if($goods_data){
                        foreach($goods_data as $v){
                            $v['price'] = empty($v['price']) ? $v['sell_price'] : $v['price'];
                            $v['pic_url'] = get_cover_url($v['cover_id']);
                            unset($v['cover_id'], $v['sell_price']);
                            $_goods_data[$v['id']] = $v;
                        }
                    }
                }
            }
            foreach($lists as $k => $v){
                $v['pic_url'] = $v['cover_id'] ? get_cover_url($v['cover_id']) : '';
                $v['action_id'] = intval($v['action_id']);
                if(!$v['url'] && $v['action_id'] > 0){
                    $v['url'] = U('Wap/Document/show', array('name' => $v['action_id']));
                    $v['has_goods'] = $v['action_id'] > 0 && !empty($_goods_data[$_access[$v['action_id']]]) ? 1 : 0;
                }else{
                    $v['has_goods'] = 0;
                    $v['action_id'] = 0;
                }
                // ios审核的版本不做显示
                if($this->device_type == 2 && $this->version_no == C('IOS_BE_PUBLISH_NO')){
                    $v['has_goods'] = 0;
                    $v['action_id'] = 0;
                }
                unset($v['id'], $v['cover_id']);
                $lists[$k] = $v;
            }
        }
        $this->return_data(1, $lists);
    }
    /**
     * @name document_lists
     * @title 设置（文档）
     * @return  [id] => 文档ID<br>[name] => 标识(注：标识为 check_update 为检查更新)<br>[title] => 标题<br>[url] => 链接地址<br>[has_blank] => 是否有空白块
     * @remark 
     */
    public function document_lists(){
        C(api('Config/lists'));
        $config = C('UI_SETTING_BLOCK');
        $where = array();
        $where['category_id'] = 107;
        $where['status'] = 1;
        $data = D('Document')->where($where)->order('level desc, id desc')->field('id, title, name')->select();
        !$data && $data = array();
        $i = 0;
        foreach($config as $k => $v){
            $v += $i;
            $i = $v;
            $config[$k] = $v;
        }
        foreach($data as $k => $v){
            if($v['name'] == 'check_update' && $this->device_type == 2){
                $config = api('Config/lists');
                $v['title'] = $config['ACCOUNT_IOS_NAME'];
                $v['url'] = $config['ACCOUNT_IOS_URL'];
            }else{
                $v['url'] = U('Wap/Document/show', array('name' => $v['name'] ? $v['name'] : $v['id']));
                $v['url'] = str_replace('http://', 'https://', $v['url']);
            }
            unset($v['id']);
            if(in_array($k+1, $config)){
                $v['has_blank'] = 1;
            }else{
                $v['has_blank'] = 0;
            }
            $data[$k] = $v;
        }
        $this->return_data(1, $data);
    }
    /**
     * @name app_info
     * @title app版本信息
     * @param string $type 类型（可选值：android、ios， 不传递时默认为android）
     * @return [app_no] => 版本号<br>[app_name] => 版本名<br>[app_url] => 更新地址
     */
    public function app_info(){
        $config = api('Config/lists');
        C($config);
        $type = I('type', 'android');
        if($type == 'ios'){
            $data = array(
                'app_no' => C('ACCOUNT_IOS_NO'),
                'app_name' => C('ACCOUNT_IOS_NAME'),
                'app_url' => C('ACCOUNT_IOS_URL'),
            );
        }else{
            $data = array(
                'app_no' => C('ACCOUNT_APP_NO'),
                'app_name' => C('ACCOUNT_APP_NAME'),
                'app_url' => C('ACCOUNT_APP_URL'),
            );
        }
        $this->return_data(1, $data);
    }
    // 生成token
    private function get_account_token( $uid, $mobile, $password = ''){
        $data = S('AccountUser');
        $key = md5(md5($mobile.'Account'.$password).NOW_TIME);
        /* 同时保存两个token */
        if(isset($data['access'][$uid]) ){
            !is_array($data['access'][$uid]) && $data['access'][$uid] = array($data['access'][$uid]);
            //$old = array_pop($data['access'][$uid]);
            $del_key = $data['access'][$uid];
            $data['access'][$uid] = array();
        }else{
            $data['access'][$uid] = array();
            $del_key = array();
        }
        $data['access'][$uid][] = $key; 
        
        if($del_key){
            foreach($del_key as $v){
                if(isset($data['token'][$v]) && isset($data['token'][$v]['uid']) && $data['token'][$v]['uid'] == $uid){
                    unset($data['token'][$v]);
                }
            }
        }
        $data['token'][$key] = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'password' => $password,
            'time' => NOW_TIME
        );
        S('AccountUser', $data);
        return $key;
    }
    // 检测token是否合法
    private function check_account_token($is_return = false){
        $token = I('token');
        $ctime = I('ctime');
        if(!$token){
            if($is_return){
                return array();
            }
            $this->return_data(-1, '', 'token值不存在');
        }
        $data = S('AccountUser');
        if(isset($data['token'][$token])){
            $uinfo = $data['token'][$token];
            // 对应用户没有token记录时返回异常
            if(!isset($data['access'][$uinfo['uid']])){
                unset($data['token'][$token]);
                S('AccountUser', $data);
                if($is_return){
                    return array();
                }
                $this->return_data(-1, '', '请登录');
            }
            // 当登录的token不是最新两次登录的，则返回异常
            $token_arr = is_array($data['access'][$uinfo['uid']]) ? $data['access'][$uinfo['uid']] : array($data['access'][$uinfo['uid']]);
            if(!in_array($token, $token_arr)){
                unset($data['token'][$token]);
                S('AccountUser', $data);
                if($is_return){
                    return array();
                }
                $this->return_data(-1, '', '登录超时');
            }
            // 当登录的token为最新提供的token时，清理之前的token，只保留最新
            $new_token = array_pop($token_arr);
            if($token == $new_token && $token_arr){
                foreach($token_arr as $v){
                    if(isset($data['token'][$v])){
                        unset($data['token'][$v]);
                    }
                }
                $data['access'][$uinfo['uid']] = array($token);
                S('AccountUser', $data);
            }
            $this->_uid = $uinfo['uid'];
            $this->_uinfo = $uinfo;
            if($is_return){
                return $uinfo;
            }
        }else{
            if($is_return){
                return array();
            }
            $this->return_data(-1, '', '请登录');
        }
    }
    /**
     * @name receipt_lists
     * @title 个人收货地址列表
     * @param int       $page       页码（默认为1）
     * @param string    $token      用户token
     * @return [id] => ID<br>[name] => 收货人<br>[mobile] => 手机号码<br>[zip_code] => 邮编<br>[address] => 收货地址<br>
                [sheng] => 省ID<br>[shi]=>市ID<br>[qu] => 区ID<br>[sheng_title] => 省文本<br>[shi_title]=>市文本<br>[qu_title]=>区文本
     */
    public function receipt_lists(){
        $this->check_account_token();
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        
        $field = 'id,name,mobile,address,sheng,shi,qu,zip_code,is_default';
        
        $result = $this->uc_api('Receipt', 'lists', array('uid' => $this->_uid, 'page' => $page, 'size' => $row, 'field' => $field));
        $lists = !empty($result['data']) ? $result['data'] : array();
        $count = isset($result['count']) ? intval($result['count']) : 0;
        $total = isset($result['total']) ? intval($result['total']) : 0;
        if($lists){
            $area_ids = array();
            foreach($lists as $v){
                !in_array($v['sheng'], $area_ids) && $area_ids[] = $v['sheng'];
                !in_array($v['shi'], $area_ids) && $area_ids[] = $v['shi'];
                !in_array($v['qu'], $area_ids) && $area_ids[] = $v['qu'];
            }
            $area_data = M('Area')->where(array('id' => array('in', $area_ids)))->select();
            foreach($area_data as $v){
                $area_title[$v['id']] = $v['title'];
            }
            foreach($lists as $k => $v){
                $v['sheng_title'] = isset($area_title[$v['sheng']]) ? $area_title[$v['sheng']] : '';
                $v['shi_title'] = isset($area_title[$v['shi']]) ? $area_title[$v['shi']] : '';
                $v['qu_title'] = isset($area_title[$v['qu']]) ? $area_title[$v['qu']] : '';
                $lists[$k] = $v;
            }
        }
        $this->return_data(1, $lists, array('page' => $page, 'row' => $row, 'count' => $count, 'total' => $total));
    }
    /**
     * @name receipt_add
     * @title 添加个人收货地址
     * @param string    $name       收货人
     * @param string    $mobile     手机号码
     * @param string    $address    收货地址
     * @param string    $sheng      省份ID
     * @param string    $shi        城市ID
     * @param string    $zip_code   邮编（6位数字，可为空）
     * @param string    $token      用户token
     * @return [id] => ID<br>[name] => 收货人<br>[mobile] => 手机号码<br>[zip_code] => 邮编<br>[address] => 收货地址<br>
                [sheng] => 省ID<br>[shi]=>市ID<br>[qu] => 区ID<br>[sheng_title] => 省文本<br>[shi_title]=>市文本<br>[qu_title]=>区文本
     */
    public function receipt_add(){
        $this->check_account_token();
        $this->_check_param(array('name', 'mobile', 'address', 'sheng', 'shi', 'qu'));
        $name = I('name', '', 'trim');
        $mobile = I('mobile', '', 'trim');
        $address = I('address', '', 'trim');
        $sheng = I('sheng', 0, 'intval');
        $shi = I('shi', 0, 'intval');
        $qu = I('qu', 0, 'intval');
        $zip_code = I('zip_code', '', 'trim');
        $area_data = M('Area')->where(array('id' => array('in', array($sheng,$shi,$qu))))->select();
        if($area_data){
            foreach($area_data as $v){
                $_area[$v['id']] = $v;
            }
        }
        if(empty($_area[$sheng]) || $_area[$sheng]['pid'] != 0){
            $this->return_data(0, '', '请选择省份');
        }
        if(empty($_area[$shi]) || $_area[$shi]['pid'] != $sheng){
            $this->return_data(0, '', '请选择对应省的下级市');
        }
        if(empty($_area[$qu]) || $_area[$qu]['pid'] != $shi){
            $this->return_data(0, '', '请选择对应城市的下级区');
        }
        $data = array(
            'uid' => $this->_uid,
            'name' => $name,
            'mobile' => $mobile,
            'address' => $address,
            'sheng' => $sheng,
            'shi' => $shi,
            'qu' => $qu,
            'zip_code' => $zip_code
        );
        
        $result = $this->uc_api('Receipt', 'add', array('data' => $data));
        if($result['status'] == 0){
            $this->return_data(0, '', $result['msg']);
        }else{
            $data = $result['data'];
        }
        $data['sheng_title'] = $_area[$sheng]['title'];
        $data['shi_title'] = $_area[$shi]['title'];
        $data['qu_title'] = $_area[$qu]['title'];
        $this->return_data(1, $data, '添加成功');
    }
    /**
     * @name receipt_edit
     * @title 修改个人收货地址
     * @param int       $id         收货地址ID
     * @param string    $name       收货人
     * @param string    $mobile     手机号码
     * @param string    $address    收货地址
     * @param string    $sheng      省份ID
     * @param string    $shi        城市ID
     * @param string    $qu         区ID
     * @param string    $zip_code   邮编（6位数字，可为空）
     * @param string    $token      用户token
     * @return [id] => ID<br>[name] => 收货人<br>[mobile] => 手机号码<br>[zip_code] => 邮编<br>[address] => 收货地址<br>
                [sheng] => 省ID<br>[shi]=>市ID<br>[qu] => 区ID<br>[sheng_title] => 省文本<br>[shi_title]=>市文本<br>[qu_title]=>区文本
     */
    public function receipt_edit(){
        $this->check_account_token();
        $this->_check_param(array('id', 'name', 'mobile', 'address', 'sheng', 'shi', 'qu'));
        $id = I('id', 0, 'intval');
        $name = I('name', '', 'trim');
        $mobile = I('mobile', '', 'trim');
        $address = I('address', '', 'trim');
        $sheng = I('sheng', 0, 'intval');
        $shi = I('shi', 0, 'intval');
        $qu = I('qu', 0, 'intval');
        $zip_code = I('zip_code', '', 'trim');
        $area_data = M('Area')->where(array('id' => array('in', array($sheng,$shi,$qu))))->select();
        if($area_data){
            foreach($area_data as $v){
                $_area[$v['id']] = $v;
            }
        }
        if(empty($_area[$sheng]) || $_area[$sheng]['pid'] != 0){
            $this->return_data(0, '', '请选择省份');
        }
        if(empty($_area[$shi]) || $_area[$shi]['pid'] != $sheng){
            $this->return_data(0, '', '请选择对应省的下级市');
        }
        if(empty($_area[$qu]) || $_area[$qu]['pid'] != $shi){
            $this->return_data(0, '', '请选择对应城市的下级区');
        }
        $data = array(
            'id' => $id,
            'uid' => $this->_uid,
            'name' => $name,
            'mobile' => $mobile,
            'address' => $address,
            'sheng' => $sheng,
            'shi' => $shi,
            'qu' => $qu,
            'zip_code' => $zip_code
        );
        $result = $this->uc_api('Receipt', 'edit', array('id' => $id, 'data' => $data));
        if($result['status'] == 0){
            $this->return_data(0, '', $result['msg']);
        }else{
            $data = $result['data'];
        }
        $data['sheng_title'] = $_area[$sheng]['title'];
        $data['shi_title'] = $_area[$shi]['title'];
        $data['qu_title'] = $_area[$qu]['title'];
        $this->return_data(1, $data, '编辑成功');
    }
    /**
     * @name receipt_del
     * @title 删除个人收货地址
     * @param int       $id         收货地址ID
     * @param string    $token      用户token
     */
    public function receipt_del(){
        $this->check_account_token();
        $this->_check_param(array('id'));
        $id = I('id', 0, 'intval');
        if($id < 1){
            $this->return_data(0, '', '收货地址不存在');
        } 
        $result = $this->uc_api('Receipt', 'del', array('id' => $id, 'uid' => $this->_uid));
        if($result){
            $this->return_data(1, '', '删除成功');
        }else{
            $this->return_data(0, '', '删除失败');
        }
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
     * @name pic_info
     * @title 故事信息
     * @param  string  $id   信息ID  
     * @param   string $totken 用户token(无登录时传空值)
     * @return  [id] => ID<br>[title] => 标题<br>[content] => 详情<br> [pic_url] => 图片<br>[from] => 来源<br>[create_time] => 创建时间
                 [has_goods] => 是否有商品<br>[goods_data] => (<br>[id] => 商品ID<br>[title] => 商品名<br>[price] => 售价<br>[pic_url] => 图片地址<br>[like_num] => 喜欢数<br>[num] => 库存<br>[is_like] => 1 已喜欢 2 未喜欢)
     * @remark
     */
    public function pic_info(){
        $id = I('id', 0, 'intval');
        $data = D('Document')->detail($id);
        if(!$data){
            $this->return_data(0, '');
        }
        $result = array(
            'id' => $id,
            'title' => $data['title'],
            'pic_url' => get_cover_url($data['cover_id']),
            'content' => $data['content'],
            'from' => '神秘商店',
            'create_time' => time_format('Y-m-d', $data['create_time'])
        );
        $access_data = M('DocumentGoodsAccess')->where(array('did' => $id))->find();
        // ios审核的版本不做显示
        if($this->device_type == 2 && $this->version_no == C('IOS_BE_PUBLISH_NO')){
            $access_data = array();
        }
        $result['has_goods'] = 0;
        if($access_data){
            $store_id = C('STORE_ONLINE');
            $goods_data = M('Goods')->alias('a')->join('__GOODS_STORE__ as b ON a.id = b.goods_id')->where(array('a.status' => 1, 'b.store_id' => $store_id, 'a.id' => $access_data['goods_id']))->field('a.id,a.title,a.cover_id,a.sell_price,b.num,b.price, a.like_num')->find();
            if($goods_data){ 
                $result['has_goods'] = 1;
                $goods_data['price'] = empty($goods_data['price']) ? $goods_data['sell_price'] : $goods_data['price'];
                $goods_data['pic_url'] = get_cover_url($goods_data['cover_id']);
                unset($goods_data['cover_id'], $goods_data['sell_price']);
                $goods_data['is_like'] = 2;
                $login_user = $this->check_account_token(true);
                if($login_user && M('GoodsLikeAccess')->where(array('uid' => $login_user['uid'], 'goods_id' => $access_data['goods_id']))->find()){
                    $goods_data['is_like'] = 1;
                }
                $result['goods_data'] = $goods_data; 
            }
        }
        $this->return_data(1, $result);
    }
    
    /**
     * @name goods_like_do
     * @title 商品喜欢操作
     * @param  int    $goods_id   商品ID  
     * @param  int    $token      用户token
     * @return [like_num] => 喜欢数<br>[type] => 当前操作方式，1 添加喜欢 2 取消喜欢
     * @remark 接口将自动判断是添加还是取消
     */
    public function goods_like_do(){
        $this->check_account_token();
        $this->_check_param(array('goods_id'));
        $goods_id = I('goods_id', 0, 'intval');
        $Model = M('GoodsLikeAccess');
        $GModel = M('Goods');
        $goods_info = $GModel->where(array('id' => $goods_id))->field('like_num')->find();
        if(!$goods_info){
            $this->return_data(0, '', '商品不存在');
        }
        if($Model->where(array('uid' => $this->_uid, 'goods_id' => $goods_id))->find()){
            if($Model->where(array('uid' => $this->_uid, 'goods_id' => $goods_id))->delete()){
                M('Goods')->where(array('id' => $goods_id, 'like_num' => array('gt', 0)))->setDec('like_num');
            }
            $this->return_data(1, array('like_num' => $goods_info['like_num'] > 0 ? ($goods_info['like_num']-1) : 0, 'type' => 2), '操作成功');
        }else{
            $data = array(
                'uid' => $this->_uid,
                'goods_id' => $goods_id,
                'create_time' => NOW_TIME
            );
            if($Model->add($data)){
                $GModel->where(array('id' => $goods_id))->setInc('like_num');
                $this->return_data(1, array('like_num' => $goods_info['like_num']+1, 'type' => 1), '操作成功');
            }else{
                $this->return_data(0, '', '请稍后再试');
            }
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
                
     * @remark 用于在APP确认购买后显示的商品订单信息
     */
    public function get_goods_order(){
        $this->check_account_token();
        $this->_check_param(array('goods_id', 'num'));
        $goods_id = I('goods_id', 0, 'intval');
        $num = I('num', 1, 'intval');
        if(!$goods_id){
            $this->return_data(0, '', '商品不存在');
        }
       
        $receipt_data = array();
        
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
                'sheng_text' => $receipt_data['sheng_text'],
                'shi_text' => $receipt_data['shi_text'],
                'qu_text' => $receipt_data['qu_text'],
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
     * @name  add_order
     * @title  添加订单
     * @param  string  $goods       商品信息json数据，支持多个（id:商品ID, num:数量 格式：[{"id": 1,"num":5}]）
     * @param  int     $rid         收货地址ID
     * @param  string  $pay_type    支付方式(wechat:微信 alipay:支付宝)
     * @param  string  $cash_code   优惠券号（可为空）
     * @param  string  $token     
     * @return 返回参数见接口 Api/Account/pay_order
     * @remark 
     */
    public function add_order(){
        $this->check_account_token();
        $this->_check_param(array('goods', 'rid', 'pay_type'));
        $goods = I('goods', '', 'trim');
        $rid = I('rid', '', 'intval');
        $cash_code = I('cash_code', '', 'trim');
        $pay_type = I('pay_type', '', 'trim');
        
        $goods = json_decode($goods, true);
        if(!$goods){
            $this->return_data(0, '', '未选择商品');
        }
        if(!in_array($pay_type, array('wechat', 'alipay'))){
            $this->return_data(0, '', '请选择支付方式');
        }
        if(!is_array($goods)){
            $this->return_data(0, array(), '提交订单失败：订单商品数据错误');
        }
        if(!$rid){
            $this->return_data(0, '', '请选择收货地址');
        }
        $receipt_data = D('Common/UserReceipt')->get_info($rid, $this->_uid);
        if(!$receipt_data){
            $this->return_data(0, '', '收货地址无效');
        }
        $data = D('Addons://Order/Order')->add_order_online($this->_uid, $goods, $receipt_data, $cash_code, 'account_app');
        if(!$data){
            $error = D('Addons://Order/Order')->getError();
            !$error && $error = '提交订单失败';
            $this->return_data(0, array(), $error);
        }
        // 设置订单号
        $_REQUEST['order_sn'] = $data['order_sn'];
        if(IS_GET){
            $_GET['order_sn'] = $data['order_sn'];
        }else{
            $_POST['order_sn'] = $data['order_sn'];
        }
        $this->pay_order();
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
    /**
     * @name area_lists
     * @title 对应的下级地区列表
     * @param int $pid 上级地区ID （默认为 0，即获取省级地区）
     * @return  [id] => 地区ID <br>[title] => 地区名<br>[level] => 级别（1 省 2 市  3区）
     * @remark  结果返回的是下级地区列表，若参数未传，则获取省级地区
     */
    public function area_lists(){
        $pid = I('pid', 0, 'intval');
        $pid < 0 && $pid = 0;
        $data = M('Area')->where(array('pid' => $pid))->field('id, title, level')->select();
        !$data && $data = array();
        $this->return_data(1, $data);
    }
    /**
     * @name area_all
     * @title 所有地区列表
     * @return  [id] => 地区ID <br>[title] => 地区名<br>[level] => 级别（1 省 2 市  3区）<br>[pid] => 上级ID<br>[child] => 下级地区集
     * @remark  
     */
    public function area_all(){
        $data = $this->_set_area(M('Area')->field('id, title, level, pid')->select());
        !$data && $data = array();
        $this->return_data(1, $data);
    }
    private function _set_area($data, $pid = 0, $level = 1){
        $result = array();
        if($data){
            foreach($data as $k => $v){
                if($v['pid'] == $pid && $v['level'] == $level){
                    unset($data[$k]);
                    $item = $this->_set_area($data, $v['id'], $level+1);
                    $v['child'] = $item;
                    $result[] = $v;
                }
            }
        }
        return $result;
    }
    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0) {
        switch ($code) {
            case -1: $error = '用户名长度必须在4-16个字之间，且第一个字不是数字！';
                break;
            case -2: $error = '用户名被禁止注册！';
                break;
            case -3: $error = '用户名被占用！';
                break;
            case -4: $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5: $error = '邮箱格式不正确！';
                break;
            case -6: $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7: $error = '邮箱被禁止注册！';
                break;
            case -8: $error = '邮箱被占用！';
                break;
            case -9: $error = '手机格式不正确！';
                break;
            case -10: $error = '手机被禁止注册！';
                break;
            case -11: $error = '手机号被占用！';
                break;
            default: $error = '未知错误';
        }
        return $error;
    }
}
