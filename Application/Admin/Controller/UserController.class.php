<?php
namespace Admin\Controller;

/**
 * 后台用户控制器

 */
class UserController extends AdminController {

    /**
     * 用户管理首页
    
     */
    public function index(){
        $nickname       =   I('nickname');
        $map['status']  =   array('egt',0);
        if(is_numeric($nickname)){
            $map['uid|nickname']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);
        }else{
            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');
        }
        $map['is_admin'] = 1;
        $list   = $this->lists('Member', $map);
        int_to_string($list);
        foreach($list as $v){
            $uids[] = $v['uid'];
        }
        $access = M('AuthGroupAccess')->where(array('uid' => array('in', $uids)))->select();
        if($access){
            foreach($access as $v){
                $as[$v['uid']] = $v['group_id'];
            }
        }
        $group = M('AuthGroup')->field('id,title')->select();
        $_group = array();
        foreach($group as $v){
            $_group[$v['id']] = $v['title'];
        }
        $this->assign('_list', $list);
        $this->assign('group', $group);
        $this->meta_title = '用户信息';
        $this->display();
    }

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname(){
        $nickname = M('Member')->getFieldByUid(UID, 'nickname');
        $this->assign('nickname', $nickname);
        $this->meta_title = '修改昵称';
        $this->display();
    }

    /**
     * 修改昵称提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname(){
        //获取参数
        $nickname = I('post.nickname');
        $password = I('post.password');
        empty($nickname) && $this->error('请输入昵称');
        empty($password) && $this->error('请输入密码');

        //密码验证
        $User   =   new \User\Client\Api();
        $req = $User->execute('User', 'login', array('username' => UID, 'password' => $password, 'type' => 4));
        if($req['status'] != 1){
            $this->error($req['msg']);
        }else{
            $uid = $req['data'];
        }
        ($uid == -2) && $this->error('密码不正确');

        $Member =   D('Member');
        $data   =   $Member->create(array('nickname'=>$nickname));
        if(!$data){
            $this->error($Member->getError());
        }

        $res = $Member->where(array('uid'=>$uid))->save($data);

        if($res){
            $user               =   session('user_auth');
            $user['username']   =   $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        }else{
            $this->error('修改昵称失败！');
        }
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword(){
        $this->meta_title = '修改密码';
        $this->display();
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitPassword(){
        //获取参数
        $password   =   I('post.old');
        empty($password) && $this->error('请输入原密码');
        $data['password'] = I('post.password');
        empty($data['password']) && $this->error('请输入新密码');
        $repassword = I('post.repassword');
        empty($repassword) && $this->error('请输入确认密码');

        if($data['password'] !== $repassword){
            $this->error('您输入的新密码与确认密码不一致');
        }

        $User   =   new \User\Client\Api();
        $req = $User->execute('User', 'updateInfo', array('uid' => UID, 'password' => $password, 'data' => $data));
        if($req['status'] != 1){
            $this->error($req['msg']);
        }elseif(!$req['data']['status']){
            $this->error($req['data']['info']);
        }
    }

    /**
     * 用户行为列表
     * @author huajie <banhuajie@163.com>
     */
    public function action(){
        //获取列表数据
        $Action =   M('Action')->where(array('status'=>array('gt',-1)));
        $list   =   $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     * @author huajie <banhuajie@163.com>
     */
    public function addAction(){
        $this->meta_title = '新增行为';
        $this->assign('data',null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author huajie <banhuajie@163.com>
     */
    public function editAction(){
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑行为';
        $this->display();
    }

    /**
     * 更新行为
     * @author huajie <banhuajie@163.com>
     */
    public function saveAction(){
        $res = D('Action')->update();
        if(!$res){
            $this->error(D('Action')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method=null){
        $id = array_unique((array)I('id',0));
        if( in_array(C('USER_ADMINISTRATOR'), $id)){
            $this->error("不允许对超级管理员执行该操作!");
        }
        
        $uid = empty($id[0]) ? 0 : $id[0];
        $id = is_array($id) ? implode(',',$id) : $id;
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] =   array('in',$id);
        
        switch ( strtolower($method) ){
            // 禁用用户
            case 'forbiduser':
                if (!empty($uid)) {
                    $this->user_forbid($uid);
                }            
                $this->forbid('Member', $map );
                break;
            case 'resumeuser':
                $this->resume('Member', $map );
                break;
            case 'deleteuser':
                if(M('Member')->where($map)->delete()){
                    M('UcenterMember')->where(array('id' => $map['uid']))->delete();
                    $this->success('删除成功');
                }else{
                    $this->error('删除失败');
                }
                break;
            default:
                $this->error('参数非法');
        }
    }

    public function add($username = '', $password = '', $repassword = '', $email = '', $mobile = ''){
        if(IS_POST){
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }

            /* 调用注册接口注册用户 */
            $req = \User\Client\Api::execute('User', 'register', array('username' => $username, 'password' => $password, 'email' => $email, 'mobile' => $mobile));
            if($req['status'] != 1){
                $this->error($req['msg']);
            }else{
                $uid = $req['data'];
            }
            if(0 < $uid){ //注册成功
                $user = array('uid' => $uid, 'nickname' => $username, 'status' => 1, 'is_admin' => 1);
                if(!M('Member')->add($user)){
                    $this->error('用户添加失败！');
                } else {
                    $this->success('用户添加成功！',U('index'));
                }
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }
        } else {
            $this->meta_title = '新增用户';
            
            $shequs = M('shequ')->select();        
            
            $this->assign('shequs', $shequs);                    
            
            
            $this->display();
        }
    }
public function edit(){
        $id = I('id', 0 , 'intval');
        if(IS_POST){
            $Model = D('Member');
            $_POST['uid'] = $id;
            $old = $Model->where(array('uid' => $id))->find();
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
                $req = \User\Client\Api::execute('User', 'updateInfo', array('uid' => $id, 'password' => '', 'data' => $_data, 'is_in' => 0));
                if($req['status'] != 1){
                    $this->error($req['msg']);
                }else{
                    $result = $req['data'];
                }
                if($result['status'] == false){
                    $this->error($this->showRegError($result['info']));
                }
                if($mobile){
                    set_mobile($id, $mobile);
                }
            }
            $change = false;
            foreach($data as $k => $v){
                if($v != $old[$k]){
                    $change = true;
                    break;
                }
            }
            if(!$Model->save($data) && $change){
                $this->error('用户编辑失败！'.$Model->getError());
            }
            $this->success('用户编辑成功！',U('index'));
        } else {
            $req = \User\Client\Api::execute('User', 'info', array('uid' => $id));
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
     * user_forbid
     */
    private function user_forbid($uid)
    {
        $data = $this->api_request('/Account/forbid', array(
            'uid' => $uid,
        ));
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
    
    
    private function api_request($url, $data = array())
    {
        
        $isTest = $this->isTest();
        
        if ($isTest) {
            $domain = 'https://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'https://v.imzhaike.com/Apiv2';
        }
        
        
        
        $url = $domain . $url;
        
        $device = 0;
        $version = '';
        $key = '$ZaiKe$ByApi$';
        
        $url2 = trim(strtolower($url));
        $utoken = md5($url2 . $key . date('Y-m-d'));
        
        
        
        
        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
        
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        $result = curl_exec($ch);
        // xydebug($result, 'test_user.txt');
        $result = json_decode($result, true);
        return $result;
    }    
    
    

}
