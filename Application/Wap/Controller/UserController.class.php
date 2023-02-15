<?php

namespace Wap\Controller;


class UserController extends BaseController {
    
    public function index(){
        $this->check_login();
        // 积分相关
        $result = \User\Client\Api::execute('Scorebox', 'info', array('uid' => $this->uid));
        if($result['status'] == 1){
            $score_data = $result['data'];
        }else{
            $score_data = array();
        }
        $member = array(
            'nickname' => get_nickname($this->uid),
            'header_pic' => get_header_pic($this->uid),
            'score_data' => $score_data
        );
        $this->assign('member', $member);
        $this->display();
    }
	
    public function login(){
        is_login() && redirect(U('User/index'));
        $this->get_open(true);
        if(IS_POST){
            $mobile = I('mobile');
            $code = I('sms_code');
            $result = check_code('wechat_login_code', $mobile, $code);
            if($result['status'] != 1){
                $this->error($result['msg']);
            }
            // 检测是否有账号
            $uid = D('Common/Member')->login($mobile);
            if(!$uid){
                $this->error('登录失败，请重试');
            }
            $req = \User\Client\Api::execute('User', 'check_bind', array('type' => APP_TYPE, 'token' => $this->app_data_id));
            if($req['status'] != 1){
                $this->error($req['msg']);
            }else{
                $bind_result = $req['data'];
            }
            if($bind_result > 0 && $bind_result != $uid){
                $this->error('手机号码已绑定其他账号');
            }
            $res_data = array(
                'mobile' => $mobile,
            );
            if(APP_TYPE == 'wechat'){
                $res_data['nickname'] = $this->app_data['nickname'];
                $res_data['headimgurl'] = $this->app_data['headimgurl'];
            }elseif(APP_TYPE == 'alipay'){
                !empty($this->app_data['nick_name']) && $res_data['nickname'] = $this->app_data['nick_name'];
                !empty($this->app_data['avatar']) && $res_data['headimgurl'] = $this->app_data['avatar'];
            }
            if(D('Member')->login($uid, $res_data)){
                unset_code('wechat_login_code', $mobile);
                $url = session('_ref');
                session('_ref', null);
                
                \User\Client\Api::execute('User', 'bind', array('uid' => $uid, 'type' => APP_TYPE, 'token' => $this->app_data_id, 'bind_data' => $this->app_data));
                !$url && $url = U('User/index');
                $this->success('登录成功', $url);
                exit;
            }
            $this->error('登录失败，请重试');
        }
        $this->display();
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
            $this->error('手机号码未知');
        }
        $req = \User\Client\Api::execute('User', 'checkMobile', array('mobile' => $mobile));
        if($req['status'] != 1){
            $this->error($req['msg']);
        }else{
            $result = $req['data'];
        }
        if(in_array($result, array(-9, -10))){
            $this->error($this->showRegError($result));
        }
        
        $code = make_code('wechat_login_code', $mobile);
        //$this->success($code);exit;
        $result = send_sms($mobile, 'SMS_39185282', array('code' => $code));
        if($result['status'] == 1){
            $this->success('发送成功');
        }else{
            $this->error($result['msg']);
        }
    }
    
    public function change_mobile(){
        $this->check_login();
        if(IS_POST){
            $mobile = I('mobile', '', 'trim');
            $code = I('sms_code', '', 'trim');
            if(!$mobile){
                $this->error('请输入手机号码');
            }
            if(!preg_match('/^1\d{10}$/', $mobile, $match)){
                $this->error('手机号码格式错误');
            }
            // 判断验证码
            $result = check_code('wechat_bind_code', $mobile, $code);

            if($result['status'] != 1){
                $this->error($result['msg']);
            }
            // 判定是否原手机号码
            if($mobile == get_mobile($this->uid, false)){
                $this->error('请更换手机号码');
            }
            
            $req = \User\Client\Api::execute('User', 'checkMobile', array('mobile' => $mobile));
            if($req['status'] != 1){
                $this->error($req['msg']);
            }else{
                $result = $req['data'];
            }
            if(in_array($result, array(-9, -10, -11))){
                $this->error($this->showRegError($result));
            }
            $req = \User\Client\Api::execute('User', 'updateInfo', array('uid' => $this->uid, 'password' => '', 'data' => array('mobile' => $mobile), 'is_in' => 0));
            if($req['status'] != 1){
                $this->error($req['msg']);
            }else{
                $result = $req['data'];
            }
            if($result['status']){
                unset_code('wechat_bind_code', $mobile);
                set_mobile($this->uid, $mobile);
                $this->success('操作成功', U('User/index'));
            }else{
                $this->error('操作失败，请重试');
            }
        }else{
            $this->display();
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
        $this->check_login();
        $mobile = I('mobile', '', 'trim');
        if(!$mobile){
            $this->error('请输入手机号码');
        }
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->error('手机号码格式错误');
        }
        if($mobile == get_mobile($this->_uid, false)){
            $this->error('不能与使用的手机号码一样');
        }
        $req = \User\Client\Api::execute('User', 'checkMobile', array('mobile' => $mobile));
        if($req['status'] != 1){
            $this->error($req['msg']);
        }else{
            $result = $req['data'];
        }
        if(in_array($result, array(-9, -10, -11))){
            $this->error($this->showRegError($result));
        }
        
        $code = make_code('wechat_bind_code', $mobile);
        
        $result = send_sms($mobile, 'SMS_39370204', array('code' => $code, 'product' => '神秘商店'));
        if($result['status'] == 1){
            $this->success('发送成功');
        }else{
            $this->error($result['msg']);
        }
    }
    
    public function info(){
        $this->check_login();
        if(IS_POST){
            $nickname = I('nickname', '', 'trim');
            if($nickname){
                $data['nickname'] = $nickname;
            }
            if(D('Member')->where(array('uid' => $this->uid))->save($data)){
                $this->success('操作成功');
            }else{
                $this->error(D('Member')->getError());
            }
        }else{
            // 积分相关
            $req = \User\Client\Api::execute('Scorebox', 'info', array('uid' => $this->uid));
            if($req['status'] == 1){
                $score_data = $req['data'];
            }else{
                $score_data = array();
            }
            $member = array(
                'nickname' => get_nickname($this->uid),
                'header_pic' => get_header_pic($this->uid),
                'score_data' => $score_data
            );
            $this->assign('member', $member);
            $this->display();
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
        $this->check_login();
        $result = $this->_upload_pic('header_img');
        if($result['status'] == 0){
            $this->error($result['msg']);
            
        }
        $cover_id = $result['data']['id'];
        if(D('Member')->where(array('uid' => $this->uid))->save(array('cover_id' => $cover_id))){
            $this->success('修改成功');
        }else{
            $this->error(D('Member')->getError());
        }
    }
    
    public function update_nickname(){
        $this->check_login();
        if(IS_POST){
            $nickname = I('nickname', '', 'trim');
            if(!$nickname){
                $this->error('请填写昵称');
            }
            if($nickname){
                $data['nickname'] = $nickname;
            }
            if(D('Member')->where(array('uid' => $this->uid))->save($data)){
                set_nickname($this->uid, $nickname);
                $this->success('操作成功', U('User/info'));
            }else{
                $this->error(D('Member')->getError());
            }
        }else{
            $nickname = get_nickname($this->uid);
            $this->assign('nickname', $nickname);
            $this->display();
        }
    }
    
    /* 退出登录 */
    public function logout(){
        $uid = is_login();
        if($uid > 0){
            D('Member')->logout();
            switch(APP_TYPE){
                case 'wechat':
                    \User\Client\Api::execute('User', 'unset_bind', array('uid' => $uid, 'type' => 'wechat'));
                    break;
                case 'alipay':
                    \User\Client\Api::execute('User', 'unset_bind', array('uid' => $uid, 'type' => 'alipay'));
                    break;
                default:
                    
            }
        }
        $this->success('退出成功！', U('User/login'));
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