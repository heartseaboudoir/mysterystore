<?php
namespace Addons\Alipay\Lib\Openim;

class Api {
    
    public $sdk_path;
    private $c;


    public function __construct() {
        $this->sdk_path = dirname(__FILE__).'/sdk/';
    }
    
    private function init(){
        require_once $this->sdk_path."TopSdk.php";
        date_default_timezone_set('Asia/Shanghai'); 
        $this->c = new \TopClient;
        $this->c->appkey = '23533489';
        $this->c->secretKey = '415601d5f2a97b4644268c1ad8069bbd';
    }
    
    /**
     * 获取IM用户信息（站内）
     * @param type $userid
     * @return type
     */
    public function get_user_info($userid){
        $Model = M('OpenimUser');
        $info = $Model->where(array('userid' => $userid))->find();
        return $info ? $info : false;
    }
    /**
     * 添加IM用户
     * @param array $userinfos  用户二维数组（[userid] => im用户名 [password] => im密码 [nick] => 昵称 [icon_url] => 头像url [email] => 邮箱 [mobile] => 手机 [gender] => 性别(M男F女)）
     */
    public function add_user($uid = 0, $userid = '', $password = '', $nick = '', $icon_url = '', $userinfo = array(), $pre = ''){
        $this->init();
        $req = new \OpenimUsersAddRequest;
        $userinfo['nick'] = $nick;
        $userinfo['icon_url'] = $icon_url;
        if(!$userid){
            $userid = $this->get_im_userid($uid ? $uid : 0, $pre ? $pre : '');
        }
        if(!$password){
            $password = $this->get_im_password($userid);
        }
        $userinfo['userid'] = $userid;
        $userinfo['password'] = $password;
        $userinfos = array($userinfo);
        $req->setUserinfos(json_encode($userinfos));
        $resp = $this->c->execute($req);
        $resp = $this->out_format($resp);
        
        if(!empty($resp['code'])){
            $result['status'] = 0;
        }else{
            $result['status'] = 1;
            $result['data'] = array();
            if(in_array($userinfo['userid'], $resp['uid_succ']['string'])){
                $Model = M('OpenimUser');
                $userinfo['create_time'] = NOW_TIME;
                $userinfo['update_time'] = NOW_TIME;
                if($Model->create($userinfo)){
                    $userinfo['id'] = $Model->add();
                }
                $result['data'] = $userinfo;
            }
        }
        return $result;
    }
    /**
     * 更新IM用户
     * @param array [userid] => im用户名 [password] => im密码 [nick] => 昵称 [icon_url] => 头像url [email] => 邮箱 [mobile] => 手机 [gender] => 性别(M男F女)
     */
    public function update_user($userid = '', $nick = '', $icon_url = '', $userinfo = array()){
        $this->init();
        $nick && $userinfo['nick'] = $nick;
        $icon_url && $userinfo['icon_url'] = $icon_url;
        if(!$userid){
            return false;
        }
        if(!$userinfo){
            return false;
        }
        $Model = M('OpenimUser');
        $im_info = $Model->where(array('userid' => $userid))->find();
        if(!$im_info){
            return false;
        }
        $userinfo['userid'] = $userid;
        $userinfos = array($userinfo);
        $req = new \OpenimUsersUpdateRequest();
        $req->setUserinfos(json_encode($userinfos));
        $resp = $this->c->execute($req);
        $resp = $this->out_format($resp);
        
        if(!empty($resp['code'])){
            $result['status'] = 0;
        }else{
            $result['status'] = 1;
            $userinfo['id'] = $im_info['id'];
            $userinfo['update_time'] = NOW_TIME;
            if($userinfo = $Model->create($userinfo)){
                $Model->where(array('id' => $im_info['id']))->save($userinfo);
            }
            $result['data'] = $userinfo;
        }
        return $result;
    }
    /**
     * 添加多个IM用户
     * @param array $userinfos  用户二维数组（[userid] => im用户名 [password] => im密码 [nick] => 昵称 [icon_url] => 头像url [email] => 邮箱 [mobile] => 手机 [gender] => 性别(M男F女)）
     */
    public function add_user_array($userinfos = array()){
        if(!$userinfos){
            return false;
        }
        $this->init();
        
        $req = new \OpenimUsersAddRequest;
        $_userinfos = array();
        foreach($userinfos as $k => $v){
            if(empty($v['userid'])){
                $v['userid'] = $this->get_im_userid(isset($v['uid']) ? $v['uid'] : 0, isset($v['pre']) ? $v['pre'] : '');
            }
            if(empty($v['password'])){
                $v['password'] = $this->get_im_password($v['userid']);
            }
            $userinfos[$k] = $v;
            $_userinfos[$v['userid']] = $v;
        }
        
        $req->setUserinfos(json_encode($userinfos));
        
        $resp = $this->c->execute($req);
        $resp = $this->out_format($resp);
        
        if(!empty($resp['code'])){
            $result['status'] = 0;
        }else{
            $result['status'] = 1;
            $result['data'] = array();
            $Model = M('OpenimUser');
            foreach($resp['uid_succ']['string'] as $v){
                $userinfo = $_userinfos[$v['userid']];
                $userinfo['create_time'] = NOW_TIME;
                $userinfo['update_time'] = NOW_TIME;
                if($Model->create($userinfo)){
                    $userinfo['id'] = $Model->add();
                }
                $result['data'][] = $userinfo;
            }
        }
        return $result;
    }
    
    /**
     * 删除IM用户
     */
    public function get_user($userids = ''){
        if(!$userids){
            return false;
        }
        $this->init();
        is_array($userids) && $userids = implode(',', $userids);
        $req = new \OpenimUsersGetRequest();
        $req->setUserids($userids);
        
        $resp = $this->c->execute($req);
        $resp = $this->out_format($resp);
        
        if(!empty($resp['code'])){
            $result['status'] = 0;
        }else{
            $result['status'] = 1;
        }
        return $resp;
    }
    
    /**
     * 删除IM用户
     */
    public function del_user($userids = ''){
        if(!$userids){
            return false;
        }
        $this->init();
        is_array($userids) && $userids = implode(',', $userids);
        $req = new \OpenimUsersDeleteRequest;
        $req->setUserids($userids);
        
        $resp = $this->c->execute($req);
        $resp = $this->out_format($resp);
        
        if(!empty($resp['code'])){
            $result['status'] = 0;
        }else{
            $result['status'] = 1;
            M('OpenimUser')->where(array('userid' => array('in', $userids)))->delete();
        }
        return $result;
    }
    
    private function get_im_userid($uid = 0, $pre = '', $key=''){
        !$pre && $pre = 'im';
        $uid = $uid ? str_pad($uid,12,'0',STR_PAD_LEFT) : date('ymdhis');
        $bpre = md5((isset($_SERVER['HTTP_HOST']) ? substr(md5($_SERVER['HTTP_HOST']), 2, 4) : '').date('ymd'));
        $userid = $pre.$bpre.$uid.$key;
        if(M('OpenimUser')->where(array('userid' => $userid))->find()){
            $userid = $this->get_im_userid($uid, $pre, mt_rand(1000, 9999));
        }
        return $userid;
    }
    
    private function get_im_password($userid){
        return substr(md5($userid), 5, 20);
    }
    
    private function out_format($data){
        return json_decode(json_encode($data), true);
    }
}