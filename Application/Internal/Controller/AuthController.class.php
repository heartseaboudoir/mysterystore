<?php
namespace Internal\Controller;

class AuthController extends ApiController {

    public function _initialize()
    {
        // 是否验证token
        $action = ACTION_NAME;
        $actions = array('login', 'tokens');
        $check = false; // true为指定的验证，false为指定的不验证 
        if (in_array($action, $actions)) {
            $this->ctoken = $check;
        } else {
            $this->ctoken = !$check;
        }
        
        
        parent::_initialize();
        //echo 2222;
    }
    
    
    public function test()
    {
        // $uid = $this->uid;
        
        $version = C('IOS_IN_VERSION_NO');
        if (empty($version)) {
            $version = '';
        }
        
        $this->response(200, $version);
    }
    
    /**
     * Internal::login
     */
    public function login()
    {
        // 用户名
        $username = I('username', '', 'trim');
        
        // 密码
        $password = I('password', '', 'trim');
        
        // 身份验证标示
        /*
        $ckey = I('ckey', '', 'trim');
        if (empty($ckey) && (empty($username) || empty($password))) {
            $this->response(10010, '身份识别异常：请输入正确的账号');
        }
        */
        
        if (empty($username) || empty($password)) {
            $this->response(10010, '身份识别异常：请输入正确的账号');
        }        
        
        
        
        $password = $this->_set_password($password);
        
        // 校验用户信息，获取账号密码
        /* 调用UC登录接口登录 */
        $req = \User\Client\Api::execute('User', 'login', array('username' => $username, 'password' => $password, 'type' => 1));        
        

        
        // 接口调用失败
        if($req['status'] != 1){
            $this->response(10011, 'error: User::login接口调用失败');
        }else{
            $uid = $req['data'];
        }
        if (0 < $uid) { //UC登录成功
            // 验证是否超级管理员或门店管理员
            $prv = M('AuthGroupAccess')->where(array('uid' => $uid, 'group_id' => array('in', '1,2')))->order('group_id asc')->find();
            
            // 获取权限数据失败
            if(empty($prv)){
                $this->response(10020, '访问权限不足');
            }
            
            
            /* 登录用户 */
            $Member = D('Member');
            $info = $Member->where(array('uid' => $uid))->find();
            if ($Member->login($uid)) { //登录用户
                $group_text = '';
                switch($prv['group_id']){
                    case 1:
                        $group_text = '超级管理员';
                        break;
                    case 2:
                        $group_text = '门店管理员';
                        break;
                }
                $token = $this->get_token($uid, $username, $password, $prv['group_id']);


                $version = C('IOS_IN_VERSION_NO');
                if (empty($version)) {
                    $version = '';
                }


                $data = array(
                    'uid' => $uid,
                    'username' => $username,
                    'nickname' => $info['nickname'],
                    'group' => $prv['group_id'],
                    'group_text' => $group_text,
                    'token' => $token,
                    'ios_in_version_no' => $version,
                );
                
                
                
                
                
                $this->response(self::RESPONSE_SUCCES, $data);
            } else {
                $msg = $Member->getError();
                $this->response(10030, $msg);
            }
        } else { //登录失败
            switch ($uid) {
                case -1: $error = '管理员不存在或被禁用！';
                    break; //系统级别禁用
                case -2: $error = '密码错误！';
                    break;
                default: $error = '未知错误！';
                    break; // 0-接口参数错误
            }
            $msg = $error;
            $this->response(10040, $msg);
        }        
        
        
       
        
    }
    
    /**
     * Internal::logout
     */    
    public function logout()
    {
        // 获取用户信息
        $userinfo = $this->uinfo();
        
        // 获取用户拥有的门店
        $uid = empty($userinfo['uid']) ? 0 : $userinfo['uid'];   
        
        if (!empty($uid)) {
            $this->clean_token($uid);
        }
        
        $this->response(self::RESPONSE_SUCCES, '退出成功');
        
    }
    
    // 用户所属角色组IDs
    protected function userGroups($uid = 0)
    {
        
        if (empty($uid)) {
            // 获取用户信息
            $userinfo = $this->uinfo();
            
            // 获取用户拥有的门店
            $uid = empty($userinfo['uid']) ? 0 : $userinfo['uid'];     
        }
        
        if (empty($uid)) {
            return array();
        } else {
            $sql = "select * from hii_auth_group_access where uid = {$uid}";
            $data = M()->query($sql);
            if (!empty($data)) {
                return array_column($data, 'group_id');
            } else {
                return array();
            }
        }
        
    }
    
    
    
    /**
     * 当前用户拥有的门店列表
     */
    public function store_list()
    {
        
        // 获取用户信息
        $userinfo = $this->uinfo();
        
        
        // 获取用户拥有的门店
        $uid = $userinfo['uid'];
        $uid_admin = C('USER_ADMINISTRATOR');
        
        // 用户所属组
        $groups = $this->userGroups();
        

        // 用户所具有的门店ID
        $stores = array();
        
        // 是管理员获取所有门店，不是管理员获取指定的门店
        if($uid != $uid_admin && !in_array(1, $groups)){
            $my_shequ = M('MemberStore')->where(array('uid' => $uid, 'type' => 2))->select();
            $my_store = array();
            if($my_shequ){
                $shequ_ids = array();
                foreach($my_shequ as $v){
                    $shequ_ids[] = $v['store_id'];
                    $group_shequ[$v['store_id']][] = $v['group_id'];
                }
                $store_data = M('Store')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
                if($store_data){
                    foreach($store_data as $v){
                        $my_store[$v['id']] = array(
                            'group_id' => $group_shequ[$v['shequ_id']],
                            'store_id' => $v['id'],
                        );
                    }
                }
            }
            $_my_store = M('MemberStore')->where(array('uid' => $uid, 'type' => 1))->field('group_id,store_id')->select();
            foreach($_my_store as $v){
                if(isset($my_store[$v['store_id']])){
                    !in_array($v['group_id'], $my_store[$v['store_id']]['group_id']) &&  $my_store[$v['store_id']]['group_id'][] = $v['group_id'];
                }else{
                    $my_store[$v['store_id']] = array(
                        'group_id' => array($v['group_id']),
                        'store_id' => $v['store_id'],
                    );
                }
            }
            if(!$my_store){
                $this->error('未授权任何门店管理');
            }
            
            // 我拥有的门店的ID
            $my_store_access = array();
            
            // 我扔胡的各门店所对应的各用户组
            $my_group = array();
            foreach($my_store as $v){
                $my_store_access[] = $v['store_id'];
                $my_group[$v['store_id']] = $v['group_id'];
            }

            $stores = $my_store_access;
        }        

        
        
        
        if (empty($stores)) {
            $sql_my_stores = "select s.id, s.title, q.id as q_id, q.title as q_title  
            from hii_store s 
            left join hii_shequ q
            on s.shequ_id = q.id 
            where status = 1";            
        } else {
            $store_ids = implode(',', $stores);
            $sql_my_stores = "select s.id, s.title, q.id as q_id, q.title as q_title 
            from hii_store s 
            left join hii_shequ q
            on s.shequ_id = q.id 
            where status = 1 
            and s.id in ({$store_ids})";
        }
        
        $my_stores = M()->query($sql_my_stores);
        
        
        if (empty($my_stores)) {
            $my_stores = array();
        }
        
        
        $my_stores_class = array();
        foreach ($my_stores as $key => $val) {
            if (!isset($my_stores_class[$val['q_id']])) {
                $my_stores_class[$val['q_id']] = array(
                    'shequ' => array(
                        'id' => $val['q_id'],
                        'title' => $val['q_title'],
                    ),
                    'stores' => array(),
                );
            }
            
            $my_stores_class[$val['q_id']]['stores'][] = array(
                'id' => $val['id'],
                'title' => $val['title'],
            );
            
            
        }
        
        
        $my_stores_class = array_values($my_stores_class);
        
        $this->response(self::RESPONSE_SUCCES, $my_stores_class);        
        
        
        $this->response(self::RESPONSE_SUCCES, array(
            'uid' => $uid,
            'uid_admin' => $uid_admin,
            'userinfo' => $userinfo,
            'groups' => $groups,
            'stores' => $stores,
            'shequs' => $shequs,
            'my_stores' => $my_stores,
            'my_stores_class' => $my_stores_class,
        ));        
        
    }
    
    
    /**
     *  获取用户的访问权限列表
     */
    public function uauth()
    {
        $menus = array(
            'store' => '门店管理',
            'purchase' => '采购管理',
            'warehouse' =>  '仓库管理',
            'accounting' => '账务管理',
            'test' => '测试',
        );
        
        
        $menus_return = array(
            'store' => $menus['store'],
            //'test' =>  $menus['test'],
        );
        
        
        $menus_now = array();
        foreach ($menus_return as $key => $val) {
            $data = array(
                'type' => $key,
                'name' => $val 
            );
            $menus_now[] = $data;
            
        }
        
        
        $this->response(self::RESPONSE_SUCCES, array(
            'all' => $menus,
            'now' => $menus_now,
        ));
        
        
    }    
    
    // 过滤密码
    private function _set_password($password){
        if(strlen($password) != 32){
            $this->response(10011, '密码非法');
        }
        return '^md5'.$password.'md5$';
    }
    
    
    
    public function tokens()
    {
        $data = S('InternalUserErp');
        
        
        $this->response(200, $data);
    }
    
    
  

    
    
    
    
    
}
