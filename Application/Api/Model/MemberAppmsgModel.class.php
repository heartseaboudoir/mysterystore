<?php

namespace Api\Model;
use Think\Model;
use User\Api\UserApi;

/**
 * 用户app信息模型
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */

class MemberAppmsgModel extends Model {
    /**
     * 自动完成
     * @var array
     */
    protected $_auto = array(
        array('update_time', NOW_TIME, self::MODEL_BOTH),
    );
    
    protected function _before_insert(&$data, $options) {
        if(!empty($data['score'])){
            $data['exp'] = $data['score'];
        }
        $this->_set_vip($data);
    }
    
    protected function _before_update(&$data, $options) {
        $this->_set_vip($data);
    }
    
    private function _set_vip(&$data){
        if(!empty($data['exp']) && !empty($data['rec_num'])){
            foreach(F('setting_vip') as $k => $v){
                if($data['exp'] >= $v['exp'] && $data['rec_num'] >= $v['rec_num']){
                    $data['vip'] = $k;
                } else {
                    break;
                }
            }
        }
    }
    
    public function create_no_res($sn){
        $username = $password = substr(md5($sn), 0, 5) . time();
        $email = $username . '@youxibao.com';
        $User = new UserApi;
        $uid = $User->register($username, $password, $email);
        if ($uid) {
            $username = 'guest'.$uid;
            $User->updateInfo($uid, '', array('username' => $username), false);
            $user = D('Member')->create(array('nickname' => $username, 'status' => 1, 'type' => 2));
            $user['uid'] = $uid;
            if(!D('Member')->add($user)){
                $this->error = '前台用户信息注册失败，请重试！';
                return false;
            }
            $data = array(
                'uid' => $uid,
                'sn' => $sn,
                'last_sn' => $sn,
            );
            D('Admin/MemberAppmsg')->add($data);
            return $data;
        }
        return false;
    }
}
