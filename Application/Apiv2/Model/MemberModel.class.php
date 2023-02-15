<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Apiv2\Model;
use Think\Model;
use User\Api\UserApi;

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
            $UcApi = new \User\Client\Api();
            $req = $UcApi->execute('User', 'info', array('uid' => $uid));
            if($req['status'] != 1){
                $this->error = '用户不存在！';
                return false;
            }
            $info = $req['data'];
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
            $res_data && $user = array_merge($user, $res_data);
            if(!$this->create($user)){
                $this->error = '操作失败！';
                return false;
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
    }

}
