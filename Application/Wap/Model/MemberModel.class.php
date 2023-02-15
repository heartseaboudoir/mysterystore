<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Wap\Model;
use Think\Model;

/**
 * 文档基础模型
 */
class MemberModel extends Model{

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('login', 0, self::MODEL_INSERT),
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('reg_time', NOW_TIME, self::MODEL_INSERT),
        array('last_login_ip', 0, self::MODEL_INSERT),
        array('last_login_time', 0, self::MODEL_INSERT),
        array('update_time', NOW_TIME),
        array('status', 1, self::MODEL_INSERT),
    );

    /**
     * 登录指定用户
     * @param  integer $uid 用户ID
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid, $res_data = array()){
        /* 检测是否在当前应用注册 */
        $user = $this->field(true)->where(array('uid' => $uid))->find();
        if(!$user){
            $req = \User\Client\Api::execute('User', 'info', array('uid' => $uid));
            if($req['status'] != 1){
                $this->error = $req['msg'];
                return false;
            }else{
                $info = $req['data'];
            }
            if($info == -1){
                $this->error = '用户不存在！';
                return false;
            }
            $user = array(
                'uid' => $uid,
                'nickname' => $info[1],
                'status' => 1
            );
            if(isset($res_data['nickname'])){
                $user['nickname'] = $res_data['nickname'];
            }elseif(isset($res_data['mobile'])){
                $user['nickname'] = substr($res_data['mobile'], 0, 5).'****'.substr($res_data['mobile'], 9, 2);
            }
            if(!empty($res_data['headimgurl'])){
                $user['cover_id'] = $this->_upload_pic($res_data['headimgurl']);
            }
            $res_data && $user = array_merge($user, $res_data);
            if(!$this->create($user)){
                $this->error = '操作失败！';
            }
            if(!$this->add()){
                $this->error = '操作失败！';
                return false;
            }
        } elseif(1 != $user['status']) {
            //应用级别禁用
            $this->error = '用户已经被禁用'; 
            return false;
        }

        /* 登录用户 */
        $this->autoLogin($user);

        //记录行为
        action_log('user_login', 'member', $uid, $uid);

        return $user;
    }
    protected function _after_update($data, $options) {
        parent::_after_update($data, $options);
        $icon_url = '';
        $nickname = '';
        if(!empty($data['nickname'])){
            $nickname = $data['nickname'];
        }
        if(!empty($data['cover_id'])){
            $icon_url = get_cover_url($data['cover_id']);
        }
        if($icon_url || $nickname){
            D('Common/Member')->update_im($data['uid'], array('header_pic' => $icon_url, 'nickname' => $nickname));
        }
    }
    private function _upload_pic($url){
        if(!$url) return 0;
        $data = http($url);
        if(!$data) return 0;
        $config = C('PICTURE_UPLOAD');
        $dir = $config['rootPath'];
        $subname = '';
        if(in_array('date',$config['subName'])){
            $v1 = $config['subName'][0];
            $v2 = $config['subName'][1];
            $subname = $v1($v2);
        }
        $filename = uniqid().'.jpg';
        $dirname = $dir.$subname.'/';
        if(!is_dir($dirname)){
            mkdir($dirname, 0777, true);
        }
        $filename = $dirname.$filename;
        file_put_contents($filename, $data);
        if(file_exists($filename)){
            $pdata = array(
                'path' => substr($filename, 1),
                'md5' => md5($data),
                'sha1' => sha1($data),
                'status' => 1,
                'create_time' => NOW_TIME
            );
            $result = M('Picture')->add($pdata);
            if($result){
                return $type == 'path' ? $pdata['path'] : $result;
            }
        }
        return false;
    }
    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($user){
        /* 更新登录信息 */
        $data = array(
            'uid'             => $user['uid'],
            'login'           => array('exp', '`login`+1'),
            'last_login_time' => NOW_TIME,
            'last_login_ip'   => get_client_ip(1),
        );
        $this->save($data);

        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid'             => $user['uid'],
            'username'        => $user['nickname'],
            'last_login_time' => $user['last_login_time'],
        );

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        session('user_auth', null);
        session('user_auth_sign', null);
    }
}
