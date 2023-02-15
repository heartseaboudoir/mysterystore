<?php
namespace User\Model;
use Think\Model;

class MemberBindModel extends Model {
    /**
     * 检测是否已绑定
     * @param type $type
     * @param type $token
     */
    public function check_bind($type, $token){
        $info = $this->where(array('type' => $type, 'token' => $token))->find();
        return ($info && !empty($info)) ? $info['uid'] : 0;
    }
    /**
     * 绑定
     * @param int  $uid      用户ID
     * @param type $type     类型
     * @param type $token    唯一值
     */
    public function bind($uid, $type, $token, $bind_data = array()){
        if($this->check_bind($type, $token)){
            return false;
        }
        $data = array(
            'uid' => $uid,
            'type' => $type,
            'token' => $token,
            'create_time' => NOW_TIME
        );
        if(!$this->create($data)){
            return false;
        }
        if(!$this->add($data)){
            return false;
        }
        if($bind_data){
            switch($type){
                case 'wechat':
                    if(empty($bind_data['openid'])){
                        break;
                    }
                    $Model = M('WechatUser');
                    if(!$Model->where(array('openid' => $token))->find()){
                        $user_data = array(
                            'openid' => $bind_data['openid'],
                            'nickname' => $bind_data['nickname'],
                            'headimgurl' => $bind_data['headimgurl'],
                            'subscribe' => $bind_data['subscribe'],
                            'data' => json_encode($bind_data),
                            'create_time' => NOW_TIME,
                            'update_time' => NOW_TIME,
                        );
                        if($Model->create($user_data)){
                            $Model->add();
                        }
                    }
                    break;
                case 'alipay':
                    if(empty($bind_data['user_id'])){
                        break;
                    }
                    $Model = M('AlipayUser');
                    if(!$Model->where(array('user_id' => $token))->find()){
                        $user_data = array(
                            'user_id' => $bind_data['user_id'],
                            'nick_name' => isset($bind_data['nick_name']) ? $bind_data['nick_name'] : '',
                            'avatar' => isset($bind_data['avatar']) ? $bind_data['avatar'] : '',
                            'data' => json_encode($bind_data),
                            'create_time' => NOW_TIME,
                            'update_time' => NOW_TIME,
                        );
                        if($Model->create($user_data)){
                            $Model->add();
                        }
                    }
                    break;
            }
        }
        return true;
    }
    
    public function unset_bind($uid, $type){
        $data = array(
            'uid' => $uid,
            'type' => $type,
        );
        $this->where($data)->delete();
    }
    
    public function get_bind($uid, $type = array()){
        $where = array();
        $where['uid'] = $uid;
        $type && $where['type'] = array('in', $type);
        $bind = $this->where($where)->select();
        if($bind){
            foreach($bind as $k => $v){
                $bind_data = array();
                if($v['type'] == 'wechat'){
                    $bind_data = M('WechatUser')->where(array('openid' => $v['token']))->find();
                }elseif($v['type'] == 'alipay'){
                    $bind_data = M('AlipayUser')->where(array('user_id' => $v['token']))->find();
                }
                $v['bind_data'] = $bind_data ? $bind_data : array();
                $bind[$k] = $v;
            }
        }else{
            $bind = array();
        }
        return $bind;
    }
}
