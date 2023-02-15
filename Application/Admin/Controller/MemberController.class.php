<?php
namespace Admin\Controller;

/**
 * 后台用户控制器

 */
class MemberController extends AdminController {
    public function __construct() {
        parent::__construct();
        $this->UserApi = new \User\Client\Api();
    }

    /**
     * 用户管理首页
    
     */
    public function index(){
        $nickname       =   I('nickname');
        $map['status']  =   array('egt',0);
        $map['is_admin']  =  0;
        if(preg_match('/^1\d{10}$/',$nickname, $match)){
            $req = $this->UserApi->execute('User', 'info', array('uid' => $nickname, 'is_username' => 3));
            if($req['status'] != 1){
                $this->error($req['msg']);
            }else{
                $info = $req['data'];
                if($info != -1){
                    $map['_string'] = 'uid = '.$info[0].' or nickname like "%'.$nickname.'%"';
                }
            }
        }elseif(is_numeric($nickname)){
            $map['uid|nickname']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);
        }else{
            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');
        }
        $list   = $this->lists('Member', $map);
        int_to_string($list);
        if($list){
            $uid = reset_data_field($list, 'uid', 'uid');
            $auth_uid = array();
            $req = $this->UserApi->execute('User', 'check_is_auth_by_data', array('uid' => $uid));
            
            if($req['status'] == 1){
                $auth_uid = $req['data'];
            }
            foreach($list as $k => $v){
                $v['is_auth'] = in_array($v['uid'], $auth_uid) ? 1 : 0;
                $list[$k] = $v;
            }
        }
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }
    
    public function edit(){
        $id = I('id', 0 , 'intval');
        if(IS_POST){
            $Model = D('Member');
            $_POST['uid'] = $id;
            $data = $Model->create();
            if(!$data){
                $this->error($Model->getError());
            }
            $password = I('password', '');
            $repassword = I('repassword', '');
            /* 检测密码 */
            if($repassword && ($password != $repassword)){
                $this->error('密码和重复密码不一致！');
            }
            $_data = array();
            $_data['id'] = $id;
            $email = I('email', '', 'trim');
            $mobile = I('mobile', '', 'trim');
            $email && $_data['email'] = $email;
            $password && $_data['password'] = $password;
            $mobile && $_data['mobile'] = $mobile;
            
            /* 更新用户uc信息 */
            if($_data){
                $req = $this->UserApi->execute('User', 'updateInfo', array('uid' => UID, 'password' => '', 'data' => $_data, 'is_in' => 0));
                if($req['status'] != 1){
                    $this->error($req['msg']);
                }elseif(!$req['data']['status']){
                    $this->error($req['data']['info']);
                }
            }
            $data['uid']  = $id;
            
            if(!D('Member')->save($data)){
                $this->error('用户编辑失败！'.$Model->getError());
            } else {
                $this->success('用户编辑成功！',U('index'));
            }
        } else {
            $req = $this->UserApi->execute('User', 'info', array('uid' => $id));
            if($req['status'] != 1){
                $this->error($req['msg']);
            }else{
                $user = $req['data'];
            }
            if(!$user){
                $this->error('用户不存在');
            }
            $member = D('Member')->where(array('uid' => $id))->find();
            $member['username'] = $user[1];
            $member['email'] = $user[2];
            $member['mobile'] = $user[3];
            $this->assign('info', $member);
            $this->meta_title = '编辑用户';
            $this->display();
        }
    }
    public function receipt_lists(){
        $uid = I('uid', 0, 'intval');
        if(!$uid){
            exit;
        }
        $Model = D('UserReceipt');
        $lists = $Model->where(array('uid' => $uid))->order('create_time asc')->select();
        if($lists){
            $area_ids = array();
            foreach($lists as $v){
                $area_ids[] = $v['sheng'];
                $area_ids[] = $v['shi'];
            }
            $area_data = M('Area')->where(array('id' => array('in', $area_ids)))->select();
            if($area_data){
                foreach($area_data as $v){
                    $_area_title[$v['id']] = $v['title'];
                }
            }
            $first = array();
            foreach($lists as $k => $v){
                $v['sheng_title'] = isset($_area_title[$v['sheng']]) ? $_area_title[$v['sheng']] : '';
                $v['shi_title'] = isset($_area_title[$v['shi']]) ? $_area_title[$v['shi']] : '';
                $lists[$k] = $v;
                if($v['is_default'] == 1){
                    $first = $v;
                    unset($lists[$k]);
                }
            }
            $first && array_unshift($lists, $first);
        }
        $this->assign('_list', $lists);
        $this->meta_title = '个人收货地址列表';
        $this->display();
    }
    public function show(){
        $id = I('id', 0 , 'intval');
        
        $req = $this->UserApi->execute('User', 'info', array('uid' => $id));
        if($req['status'] != 1){
            $this->error($req['msg']);
        }else{
            $user = $req['data'];
        }
        if(!$user){
            $this->error('用户不存在');
        }
        
        // 获取注册时间
        $umember = M('ucenter_member')->where(array('id' => $id))->find();
        if (empty($umember) || empty($umember['reg_time'])) {
            $umember_regtime = 0;
        } else {
            $umember_regtime = $umember['reg_time'];
        }
        
        
        $member = D('Member')->where(array('uid' => $id))->find();
        $member['username'] = $user[1];
        $member['email'] = $user[2];
        $member['mobile'] = $user[3];
        
        $member['reg_time'] = $umember_regtime;
        
        $bind = $this->UserApi->execute('User', 'get_bind', array('uid' => $id, 'type' => array('wechat', 'alipay')));
        if($bind['status'] == 1){
            $bind_data = reset_data($bind['data'], 'type');
            isset($bind_data['wechat']) && $member['wechat'] = M('WechatUser')->where(array('openid' => $bind_data['wechat']['token']))->find();
            isset($bind_data['alipay']) && $member['alipay'] = M('AlipayUser')->where(array('user_id' => $bind_data['alipay']['token']))->find();
        }
        $req = $this->UserApi->execute('Scorebox', 'info', array('uid' => $id));
        if($req['status'] == 1){
            $score_data = $req['data'];
        }
        $member['score_data'] = $score_data;
        $member['auth_data'] = M('MemberAuth')->where(array('uid' => $id))->find();
        $this->assign('info', $member);
        $this->meta_title = '用户信息';
        
        
        $wallet_info = $this->getWallet($id);
        $this->wallet_info = $wallet_info;
        
        $this->display();
    }
    
    /**
     * 获取用户钱包信息
     */
    private function getWallet($uid)
    {
        $one = M('wallet')->where(array(
            'uid' => $uid,
        ))->find();
        

        if (empty($one)) {
            
            $data = array(
                'uid' => $uid,
                'money' => 0,
                'all_money' => 0,
                'lock_money' => 0,
                'frozen_money' => 0,
                'income_money' => 0,
                'recharge_money' => 0,
                'create_time' => $time,
                'update_time' => $time,
                'status' => 1,
            );
            
            M('wallet')->add($data);
        } else {
            $data = $one;
        }
        
        return $data;
    }
    

    
    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在16个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            default:  $error = '未知错误';
        }
        return $error;
    }
    
    /**
     * 实名认证申请管理
     */
    public function apply_auth_index(){
        $type = I('type', 'all');
        switch($type){
            case 'wait':
                $map['status'] = 1;
                break;
            case 'is_pub';
                $map['status'] = 2;
                break;
            case 'no_pub':
                $map['status'] = 3;
                break;
        }
        $list   = $this->lists(D('MemberAuthApply'), $map, 'status asc, create_time desc');
        int_to_string($list);
        foreach($list as &$v){
            $v['nickname'] = get_username($v['uid']);
        }
        $this->assign('_list', $list);
        $this->meta_title = '会员实名认证申请列表';
        $this->display();
    }
    public function apply_auth_show($id = null){
        if(!$id){
            $this->error('数据不存在!');
        }
        $info = D('MemberAuthApply')->find($id);
        if(!$info){
            $this->error('数据不存在!');
        }
        $this->assign('info', $info);
        $this->meta_title = '会员实名认证申请详情';
        $this->display();
    }
    /**
     * 认证申请状态修改
     */
    public function apply_auth_changeStatus($method=null){
        $id = I('id', 0, 'intval');
        $remark = I('remark', '', 'trim');
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['id'] =   $id;
        $map['status'] = 1;
        switch ( strtolower($method) ){
            case 'agree':
                $status = 2;
                break;
            case 'return':
                $status = 3;
                break;
            default:
                $this->error('参数非法');
        }
        $Model = D('MemberAuthApply');
        $info = $Model->where($map)->find();
        if(!$info){
            $this->error('记录不存在');
        }
        if($status == 2){
            $data = array(
                'uid' => $info['uid'],
                'real_name' => $info['real_name'],
                'cert_no' => $info['cert_no'],
                'cert_pic1' => $info['cert_pic1'],
                'cert_pic2' => $info['cert_pic2'],
                'cert_pic3' => $info['cert_pic3'],
                'create_time' => NOW_TIME
            );
            $AuthModel = D('MemberAuth');
            if($AuthModel->where(array('cert_no' => $info['cert_no']))->find()){
                $this->error('该身份证号码已经被认证');
            }
            if(!$AuthModel->create($data)){
                $this->error('会员实名认证失败');
            }
            if(!$AuthModel->add($data)){
                $this->error('会员实名认证失败');
            }
            $result = $Model->where($map)->save(array('status' => 2, 'remark' => $remark, 'update_time' => NOW_TIME ));
            if($result){
                $this->success('操作成功', U('apply_auth_index'));
            }else{
                $this->error('操作失败');
            }
        }else{
            $result = $Model->where($map)->save(array('status' => 3, 'remark' => $remark, 'update_time' => NOW_TIME ));
            if($result){
                $this->success('操作成功', U('apply_auth_index'));
            }else{
                $this->error('操作失败');
            }
        }
    }
    
    public function follow_lists(){
        $type = I('type', 1, 'intval');
        $uid = I('uid', 0, 'intval');
        $where = array();
        if($type == 1){
            $where['fid'] = $uid;
            $this->meta_title = '关注列表';
        }else{
            $where['uid'] = $uid;
            $this->meta_title = '粉丝列表';
        }
        $this->model = D('MemberFollow');
        parent::_index($where, 'create_time desc');
    }
    
    
    
    
    /**
     * 查找用用订单
     */
    public function order_lists()
    {
        $uid = I('uid', 0, 'intval');
        

        $this->assign('uid', $uid);
        
        // 订单条数
        $sql_count = "select count(*) as c from hii_order o where o.uid = {$uid} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2";
        $count_data = M()->query($sql_count);
        
        if (!empty($count_data[0]['c'])) {
            $count = $count_data[0]['c'];
        } else {
            $count = 0;
        }
        
        
        
        
        $pcount = 50;
        $Page = new \Think\Page($count, $pcount);        
        
        
        
        // 订单
        $sql = "select o.uid, o.order_sn, o.store_id, o.money, o.pay_type, o.create_time, s.title as store, q.title as shequ  
from hii_order o 
left join hii_store s on o.store_id = s.id 
left join hii_shequ q on s.shequ_id = q.id 
where o.uid = {$uid} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 
order by o.id desc 
limit {$Page->firstRow}, {$Page->listRows}";
        
        
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        
       
        
        
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);

        
        $this->meta_title = '用户消费订单列表';
        $this->display('order_lists');         
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
    }
    
    
    
}
