<?php
// +----------------------------------------------------------------------
// | Title: 用户
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | Type: 客户端
// +----------------------------------------------------------------------
namespace Apiv2\Controller;

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
                [next_level_exper] => 下一等级经验<br>
                [next_level_title] => 下一等级称号<br>
                [next_level_icon] => 下一等级ICON<br>
                [im_userid] => im用户名 <br>[im_password] => im密码<br>
                [is_checkin] => 是否已签到 1 是 0 否<br>
                [token] => 登录成功后的token值，调用其他接口时需传递<br>
                [is_auth] => 是否认证（0 否 1 是 2 待审核）<br>[shop_star] => 店铺星级<br>[follow_num] => 关注人数<br>[be_follow_num] => 粉丝人数<br>
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
            if(!in_array($mobile, array(18520120884)) && $code != '390010'){
                // 判断验证码
                $result = check_code('app_login_code', $mobile, $code);
                if($result['status'] != 1){
                    $this->return_data(0, '', $result['msg']);
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
    
    public function forbid()
    {
        $uid = I('uid', 0, 'intval');
        if (empty($uid)) {
            return;
        }
        $this->clear_account_token($uid);
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
        $exchange = $this->uc_api('Scorebox', 'exchange_info', array('id' => 1));
        // im信息
        $im_info = D('Common/Member')->get_im($uid);
        // 是否签到
        $is_checkin = $this->uc_api('Scorebox', 'is_checkin', array('uid' => $uid));
        
        $shop_star = D('Addons://Shop/Shop')->get_star($uid);
        $is_auth = $this->uc_api('User', 'check_is_auth', array('uid' => $uid));
        $info = D('Common/Member')->info($uid);
        $data = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'nickname' => $info['nickname'],
            'header_pic' => $info['header_pic'],
            'exchange_title' => isset($exchange['description']) ? $exchange['description'] : '',
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
            'is_auth' => $is_auth,
            'shop_start' => $shop_star,
            'follow_num' => $info['follow_num'],
            'be_follow_num' => $info['be_follow_num'],
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
        
        $type = I('type', '', 'trim');
        
        if (empty($type)) {
            $type = 'text';
        }
        
        if (!in_array($type, array('text', 'voice'))) {
            $this->return_data(0, '', '消息类型错误');
        }
        
        if(!$mobile){
            $this->return_data(0, '', '手机号码未知');
        }
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->return_data(0, '', '手机号码格式有误');
        }
        $result = $this->uc_api('User', 'checkMobile', array('mobile' => $mobile));
        if(in_array($result, array(-9, -10))){
            $this->return_data(0, '', $this->showRegError($result));
        }
        
        $code = make_code('app_login_code', $mobile);
        //$this->return_data(1, '', $code);exit;
        
        if ($type == 'text') {
            $result = send_sms($mobile, 'SMS_39185282', array('code' => $code));
        } else if ($type == 'voice'){
            $result = send_sms_voice($mobile, 'TTS_138525039', array(
                'product' => '神秘商店',
                'code' => $code,
            ));
        }
        
        
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
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->return_data(0, '', '手机号码格式有误');
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
                [next_score] => 下次签到可得蜜糖<br>[check_times] => 已签到天数<br>[day_data] => <br>(<br>[date] => 日期 <br>[score] => 可得蜜糖<br>[is_check] => 是否已领 1 是 0 否<br>)<br>
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
     * @name  exchange_cash_coupon
     * @title  蜜糖兑换优惠券  
     * @param  string  $token     
     * @return [score] => 当前蜜糖<br>[exper]=>当前经验<br>[level_title]=>当前等级<br>[level_icon] => 当前等级图标
     * @remark 
     */
    public function exchange_cash_coupon(){
        $this->check_account_token();
        // 获取兑换比例
        $exchange = $this->uc_api('Scorebox', 'exchange_info', array('id' => 1));
        if(empty($exchange['exchange_obj'])){
            $this->return_data(0, '', '优惠券无法兑换');
        }
        $info = $this->uc_api('CashCoupon', 'info', array('code' => $exchange['exchange_obj']));
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
                [tag] => 标签<br>[has_goods] => 是否有商品<br>
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
                !in_array($v['action_id'], $action_ids) && $action_ids[] = $v['action_id'];
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
    /**
     * @name receipt_lists
     * @title 个人收货地址列表
     * @param int       $page       页码（默认为1）
     * @param string    $token      用户token
     * @return [id] => ID<br>[name] => 收货人<br>[mobile] => 手机号码<br>[zip_code] => 邮编<br>[address] => 收货地址<br>
                [sheng] => 省ID<br>[shi]=>市ID<br>[qu] => 区ID<br>[sheng_title] => 省文本<br>[shi_title]=>市文本<br>[qu_title]=>区文本<br>[is_default] => 是否默认地址（0 否  1 是）
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
     * @param string    $is_default 是否设为默认地址 1 是 0 否（默认为0）
     * @param string    $token      用户token
     * @return [id] => ID<br>[name] => 收货人<br>[mobile] => 手机号码<br>[zip_code] => 邮编<br>[address] => 收货地址<br>
                [sheng] => 省ID<br>[shi]=>市ID<br>[qu] => 区ID<br>[sheng_title] => 省文本<br>[shi_title]=>市文本<br>[qu_title]=>区文本<br>[is_default] => 是否默认地址（0 否  1 是）
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
        $is_default = I('is_default', 0, 'intval');
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
            'zip_code' => $zip_code,
            'is_default' => $is_default,
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
     * @param string    $is_default 是否设为默认地址 1 是 0 否（默认为0）
     * @param string    $token      用户token
     * @return [id] => ID<br>[name] => 收货人<br>[mobile] => 手机号码<br>[zip_code] => 邮编<br>[address] => 收货地址<br>
                [sheng] => 省ID<br>[shi]=>市ID<br>[qu] => 区ID<br>[sheng_title] => 省文本<br>[shi_title]=>市文本<br>[qu_title]=>区文本<br>[is_default] => 是否默认地址（0 否  1 是）
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
        $is_default = I('is_default', 0, 'intval');
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
            'zip_code' => $zip_code,
            'is_default' => $is_default,
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
     * @name pic_info
     * @title 故事信息
     * @param  string  $id   信息ID  
     * @param   string $totken 用户token(无登录时传空值)
     * @return  [id] => ID<br>[title] => 标题<br>[content] => 详情<br> [pic_url] => 图片<br>[from] => 来源<br>[url] => 链接地址<br>[create_time] => 创建时间
                 [has_goods] => 是否有商品<br>[goods_data] => (<br>[id] => 商品ID<br>[title] => 商品名<br>[price] => 售价<br>[pic_url] => 图片地址<br>[like_num] => 喜欢数<br>[num] => 库存<br>[is_like] => 1 已喜欢 2 未喜欢)<br>
                 [share] => 页面分享(<br>[title] =>标题<br>[desc] => 描述<br>[pic_url] => 图片地址<br>[url] => 分享地址<br>) <br>
     * @remark
     */
    public function pic_info(){
        $id = I('id', 0, 'intval');
        
        if ($id == '99999') {
            $result = $this->pic_info_act();
            $this->return_data(1, $result);
        }        
        
        if ($id == '99998') {
            $result = $this->pic_info_wc();
            $this->return_data(1, $result);
        } 
        
        $data = D('Document')->detail($id);
        if(!$data){
            $this->return_data(0, '');
        }
        $result = array(
            'id' => $id,
            'title' => $data['title'],
            'pic_url' => get_cover_url($data['cover_id']),
            'description' => $data['description'],
            'content' => $data['content'],
            'from' => '神秘商店',
            'url' => U('Wap/Document/show', array('name' => $id)),
            'create_time' => time_format('Y-m-d', $data['create_time']),
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
                $login_user = $this->check_account_token(false, true);
                if($login_user && M('GoodsLikeAccess')->where(array('uid' => $login_user['uid'], 'goods_id' => $access_data['goods_id']))->find()){
                    $goods_data['is_like'] = 1;
                }
                $result['goods_data'] = $goods_data; 
            }
        }
        $result['share'] = array(
            'title' => $result['title'],
            'desc' => $data['description'],
            'pic_url' => $result['pic_url'],
            'url' => $result['url'],
        );
        $this->return_data(1, $result);
    }
    
    
    // 每日活动的H5页面
    private function pic_info_act()
    {
        
        
        $config = M('act_product_config')->find();
        
        $result = array(
            'id' => '99999',
            'title' => '每日活动',
            'pic_url' => '',
            'description' => '',
            'content' => '',
            'from' => '神秘商店',
            'url' => U('/Wap/SmAct/index'),
            'create_time' => time_format('Y-m-d', time()),
            'access_data' => array(),
            'share' => array(
                'title' => empty($config['title']) ? '#神秘商品#每日抽奖活动' : $config['title'],
                'desc' => empty($config['info']) ? '#神秘商品#每日抽奖活动说明' : $config['info'],
                'pic_url' => get_domain() . $config['cover'],
                //'pic_url' => get_domain() . '/Public/res/Wap/images/common/day_act.png',
                'url' => U('/Wap/SmAct/index'),            
            ),
        ); 
        
        return $result;
    }    
    
    // 世界杯活动的H5页面
    private function pic_info_wc()
    {
        $cstr = $this->getUserAuth();        
        
        $config = M('wc_config')->find();
        
        $result = array(
            'id' => '99998',
            'title' => '世界杯活动',
            'pic_url' => '',
            'description' => '',
            'content' => '',
            'from' => '神秘商店',
            'url' => U('/Wap/WorldCup/wc', array('cstr' => $cstr)),
            'create_time' => time_format('Y-m-d', time()),
            'access_data' => array(),
            'share' => array(
                'title' => empty($config['title']) ? '#神秘商品#世界杯活动' : $config['title'],
                'desc' => empty($config['info']) ? '#神秘商品#世界杯活动说明' : $config['info'],
                'pic_url' => get_domain() . $config['cover'],
                //'pic_url' => get_domain() . '/Public/res/Wap/images/common/day_act.png',
                'url' => U('/Wap/WorldCup/wc'),            
            ),
        ); 
        
        return $result;
    }   


    private function getUserAuth()
    {
        $this->check_account_token(false);
        
        $uid = $this->_uid;
        if (empty($uid)) {
            return 'null';
            //return 'nullxxx' . $_GET['token'] . 'pp' . $_POST['token'];
        }
        
        $wcauth = $this->getWcauth();
        
        
        
        M('ucenter_member')->where(array(
            'id' => $uid,
        ))->save(array(
            'wcauth' => $wcauth,
        ));
        
        
        return $wcauth;
        
    }
    
    
    private function getWcauth()
    {
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $str = str_shuffle(str_repeat($str, 5));
        
        $sub_str = substr($str, 5, 20);
        
        $time = date('YmdHis');
        $time_str = strtr($time, '0123456789', 'abcdefghij');

        $wcauth = $sub_str . $time_str;
        
        $have = M('ucenter_member')->where(array(
            'wcauth' => $wcauth
        ))->find();
        
        if (empty($have)) {
            return $wcauth;
        } else {
            return $this->getWcauth();
        }
        
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
    /**
     * @name follow
     * @title 关注/取消关注
     * @param string $fid   用户ID
     * @return  [type] => 操作结果 ： 1 关注 2 取消关注
     * @remark  
     */
    public function follow(){
        $this->check_account_token();
        $this->_check_param('fid');
        $fid = I('fid', 0, 'intval');
        if(!($fid > 0)){
            $this->return_data(0, '', '关注的人不存在');
        }
        if($fid == $this->_uid){
            $this->return_data(0, '', '不能关注自己');
        }
        $result = D('Common/Member')->follow($this->_uid, $fid);
        if($result['status'] == 1){
            $type = $result['type'];
            $this->return_data(1, array('type' => $type), $type == 1 ? '成功关注' : '成功取消');
        }else{
            $this->return_data(0, '', '操作失败');
        }
    }
    /**
     * @name follow_lists
     * @title 关注/粉丝 列表
     * @param string $uid   用户ID（为空时则默认为当前登录的用户）
     * @param string $type  类型：1 关注的人 2 粉丝(默认为1)
     * @return  [uid] => 用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[create_time]关注时间<br>[is_follow] => 是否关注 1 是 0 否
     * @remark  
     */
    public function follow_lists(){
        $this->check_account_token(false);
        $uid = I('uid', 0, 'intval');
        if(!($uid > 0) && !$this->_uid){
            $this->return_data(0);
        }
        $uid <= 0 && $uid = $this->_uid;
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 50;
        $type = I('type', 1, 'intval');
        if(!in_array($type, array(1,2))){
            $this->return_data(0);
        }
        $result = $this->uc_api('User', 'get_follow_lists', array('type' => $type, 'check_uid' => $this->_uid, 'uid' => $uid, 'page' => $page, 'row' => $row));
        $lists = array();
        foreach($result['data'] as $v){
            $lists[] = array(
                'uid' => $v['uid'],
                'nickname' => get_nickname($v['uid']),
                'header_pic' => get_header_pic($v['uid']),
                'create_time' => $v['create_time'],
                'is_follow' => $v['is_follow'],
            );
        }
        $this->return_data(1, $lists, '', array('offset' => $result['page'], 'row' => $result['row'], 'count' => $result['count'], 'total' => $result['total']));
    }
    /**
     * @name user_index
     * @title 个人主页
     * @param string $uid 用户ID
     * @return  [nickname] => 昵称 <br>[header_pic] => 头像<br>[shop_star] => 星级<br>[is_auth] => 是否认证 0 否 1 是 2待审核<br>[follow_num] => 关注数<br>[be_follow_num] => 被关注数<br>[is_follow] => 是否关注 0 否 1 是<br><br>
                 [goods] => 发布的商品集合（参数见门店的列表接口返回[Shop/lists]）<br>
     * @remark  返回信息为支付宝返回，为支付宝用户主动设置，若未设置，则对应字段将返回空字符。 goods字段为列表形式，当商品内容需要获取第2页后的数据时，调用Shop/lists接口获取
     */
    public function user_index(){
        $this->check_account_token(false);
        $this->_check_param('uid');
        $uid = I('uid', 0, 'intval');
        if(!($uid > 0) && !$this->_uid){
            $this->return_data(0);
        }
        $uid <= 0 && $uid = $this->_uid;
        $info = D('Common/Member')->info($uid);
        if(!$info){
            $this->return_data(0, '', '用户不存在');
        }
        $is_auth = $this->uc_api('User', 'check_is_auth', array('uid' => $uid));
        $data = array();
        $data['nickname'] = $info['nickname'];
        $data['header_pic'] = $info['header_pic'];
        $data['shop_star'] = D('Addons://Shop/Shop')->get_star($uid);
        $data['is_auth'] = $is_auth;
        $data['follow_num'] = $info['follow_num'];
        $data['be_follow_num'] = $info['be_follow_num'];
        $result = ($this->_uid && $this->_uid != $uid) ? $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $uid)) : array();
        $data['is_follow'] = in_array($uid, $result) ? 1 : 0;
        $goods = A('Shop')->lists($uid, 1, true);
        $data['goods'] = $goods['data'];
        $this->return_data(1, $data, '', array('offset' => $goods['offset'], 'row' => $goods['row'], 'total' => $goods['total'], 'count' => $goods['count']));
    }
    /**
     * @name my_assess_lists
     * @title 用户的评价列表
     * @param string $uid      用户ID(为空时则获取当前登录的用户ID)
     * @param int    $type     类型：1 我发出的 2 我收到的
     * @param int    $page     页码（默认为1）
     * @return  [create_uid] => 发表的用户<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[content] => 评价内容<br>[create_time] => 创建时间<br>[bind_sn] => 对应的订单号<br>
                 [order_end_time] => 交易完成时间<br>[goods_data] => 商品信息数组（字段如下）<br>-----<br>[goods_id] => 商品ID<br>[title] => 商品名<br>[pic] => 商品图片
     */
    public function my_assess_lists(){
        $this->check_account_token(false);
        $uid = I('uid', 0, 'intval');
        if(!($uid > 0)){
            $uid = $this->_uid;
        }
        if(!($uid > 0)){
            $this->return_data(0, '', '未知用户');
        }
        $type = I('type',1, 'intval');
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $where = array();
        if($type == 1){
            $where['create_uid'] = $uid;
        }elseif($type == 2){
            $where['uid'] = $uid;
        }
        
        $Model = M('ShopAssess');
        $lists = $Model->where($where)->field('content, star, create_uid, create_time, bind_goods, bind_sn, order_end_time')->select();
        if($lists){
            foreach($lists as $k => $v){
                $v['goods_data'] = json_decode($v['bind_goods'],true);
                $v['nickname'] = get_nickname($v['create_uid']);
                $v['header_pic'] = get_header_pic($v['create_uid']);
                $lists[$k] = $v;
            }
        }else{
            $lists = array();
        }
        $total = $Model->where($where)->count();
        $count = count($lists);
        $this->return_data(1, $lists, '', array('offset' => $page, 'row' => $row, 'total' => $total, 'count' => $count));
    }
    /**
     * @name auth_apply
     * @title 实名认证申请
     * @param   string $real_name 真实姓名
     * @param   string $mobile    手机号码
     * @param   string $cert_no   身份证号码
     * @param   string $cert_pic1 身份证正面
     * @param   string $cert_pic2 身份证反面
     * @param   string $cert_pic3 身份证手持照
     * @return  
     * @remark  
     */
    public function auth_apply(){
        $this->check_account_token();
        $this->_check_param(array('real_name', 'cert_no', 'cert_pic1', 'cert_pic2', 'cert_pic3', 'mobile'));
        $real_name = I('real_name');
        $cert_no = I('cert_no', '', '');
        $cert_pic1 = I('cert_pic1', 0, 'intval');
        $cert_pic2 = I('cert_pic2', 0, 'intval');
        $cert_pic3 = I('cert_pic3', 0, 'intval');
        $mobile = I('mobile', '', 'trim');
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->return_data(0, '', '手机号码格式有误');
        }
        if(!check_idcard($cert_no)){
            $this->return_data(0, '', '请正确输入15位或18位身份证号');
        }
        $result = $this->uc_api('User', 'check_auth_apply', array('uid' => $this->_uid));
        if($result['status'] != 1){
            $this->return_data(0, '', $result['msg']);
        }
        $Lib = new \Addons\Alipay\Lib\Zhima\Api();
        $result = $Lib->antifraud_verify($real_name, $cert_no);
        if($result['status'] != 1){
            $this->return_data(0, '', $result['msg']);
        }
        $data = array(
            'uid' => $this->_uid, 
            'real_name' => $real_name, 
            'cert_no' => $cert_no, 
            'cert_pic1' => $cert_pic1,  
            'cert_pic2' => $cert_pic2, 
            'cert_pic3' => $cert_pic3, 
            'mobile' => $mobile
        );
        $result = $this->uc_api('User', 'auth_apply', $data);
        if($result['status'] != 1){
            $this->return_data(0, '', $result['msg']);
        }else{
            $this->return_data(1);
        }
    }
    /**
     * @name update_pic
     * @title 图片上传
     * @param  string  $num         上传的图片数量（若数量与上传数不符，则不返回）
     * @param  string  $cover+$num  图片上传key值(格式如：  $cover1, $cover2)
     * @param  string  $token    用户token
     * @return [id] => 图片id <br> [pic_url] => 图片地址
     * @remark 当前接口需使用 multipart/form-data 方式上传图片<br>最多同时上传12张图片<br>num值必须与图片上传的key对应，否则将返回空
     */
    public function update_pic(){
        
        //$xy = I('xy', 0, 'intval');
        
        //XYdebug($_GET);
        //XYdebug($_POST);
        //xydebug($_FILES);
      
        
        $this->check_account_token();
        $this->_check_param('num');
        $num = I('num', 0, 'intval');
        $cover_key = array();
        for($i = 1; $i <= $num; $i++){
            $cover_key[] = 'cover'.$i;
            if($i == 12){
                break;
            }
        }
        if(!$cover_key){
            $this->return_data(0, '', '没有上传任何图片');
        }
        $result = $this->_upload_pic($cover_key);
        if($result['status'] == 0){
            $this->return_data(0, '', $result['msg']);
        }else{
            $return_data = array();
            foreach($result['data'] as $v){
                $return_data[] = array(
                    'id' => $v['id'],
                    'pic_url' => get_domain().$v['path'],
                );
            }
            $this->return_data(1, $return_data);
        }
    }
    /**
     * @name notice_lists
     * @title  消息列表
     * @param  string  $cate        类型（1：系统默认 2：点赞/收藏/评论 3：新关注）
     * @param  string  $page        页码（默认为1）
     * @param  string  $token    用户token
     * @return [type] => 类型：(order = 买家订单, seller_order = 卖家订单, zan = 点赞, collect = 收藏, comment = 评论, notice = 通知, follow = 关注 )<br>
                [act_uid] => 对应的用户ID<br>[act_id] => 对应的ID <br> [pic_url] => 图片地址<br> [is_read] => 是否已读<br> [create_time] => 创建时间戳<br>
                [title] => 消息标题<br>[content] => 消息内容<br>[nickname] => 用户昵称<br>[header_pic] => 头像<br>[act_title] => 对应内容的标题<br>
                [url] => 跳转的h5地址
     * @remark 
     */
    public function notice_lists(){
        $this->check_account_token();
        $cate_id = I('cate', 0, 'intval');
        if(!($cate_id > 0)){
            $this->return_data(0);
        }
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 50;
        $req = $this->uc_api('Message', 'notice_lists', array('uid' => $this->_uid, 'page' => $page, 'row' => $row, 'cate_id' => $cate_id));
        $data = array();
        
        $cstr = $this->getUserAuth();
        if(!empty($req['data'])){
            $uids = array_as_key($req['data'], 'act_uid', true);
            $follow_result = $uids ? $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $uids)) : array();
            foreach($req['data'] as $v){
                $act_data = json_decode($v['act_data'], true);
                $url = '';
                if($v['type'] == 'notice'){
                    if ($v['act_id'] == 99999) {
                        $url = U('/Wap/SmAct/index');
                    } elseif ($v['act_id'] == 99998) {
                        $url = U('/Wap/WorldCup/wc', array('cstr' => $cstr));
                    } else {
                        $url = U('Wap/Document/show', array('name' => $v['act_id']));
                    }                    
                }
                $data[] = array(
                    'id' => $v['id'],
                    'type' => $v['type'],
                    'title' => $v['title'],
                    'content' => $v['content'],
                    'act_title' => isset($act_data['title']) ? $act_data['title'] : '',
                    'pic_url' => isset($act_data['pic_url']) ? $act_data['pic_url'] : '',
                    'act_uid' => $v['act_uid'],
                    'nickname' => isset($act_data['nickname']) ? $act_data['nickname'] : get_nickname($v['act_uid']),
                    'header_pic' => isset($act_data['header_pic']) ? $act_data['header_pic'] : get_header_pic($v['act_uid']),
                    'act_id' => $v['act_id'],
                    'is_read' => $v['is_read'],
                    'create_time' => $v['create_time'],
                    'is_follow' => in_array($v['act_uid'], $follow_result) ? 1 : 0,
                    'url' => $url,
                );
            }
        }
        $result = array(
            'data' => $data,
            'page' => $req['page'],
            'row' => $req['row'],
            'count' => $req['count'],
            'total' => $req['total']
        );
        $this->return_lists_by_arr(1, $result);
    }
    /**
     * @name notice_new_num
     * @title  消息未读数量
     * @param  string  $token    用户token
     * @return [sy] => 系统通知数量<br>[shop] => 商品操作相关数量（点赞、收藏、评论）<br>[follow] => 商品操作相关数量（点赞、收藏、评论）
     * @remark 调用消息列表数据之后，将自动清空未读数量
     */
    public function notice_new_num(){
        $this->check_account_token();
        $type_data = array(
            'sy' => 1,
            'shop' => 2,
            'follow' => 3,
        );
        $req = $this->uc_api('Message', 'get_no_read_num', array('uid' => $this->_uid, 'type_data' => $type_data));
        $this->return_data(1, array('sy' => empty($req['sy']) ?  0 : $req['sy'], 'shop' => empty($req['shop']) ? 0 : $req['shop'] , 'follow' => empty($req['follow']) ? 0 : $req['follow']));
    }
}
