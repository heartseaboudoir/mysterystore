<?php
namespace Common\Model;
use Think\Model;

class MemberModel extends Model {
    
    public function login($mobile){
        $UcApi = new \User\Client\Api();
        // 检测是否有账号
        $req = $UcApi->execute('User', 'info', array('uid' => $mobile, 'is_username' => 3));
	if(I('test') == 1){
            $Log = new \Common\Lib\Log();
            $Log->add_log(MODULE_NAME, 'text', array('data' => var_export($req, true)));
        }
        if($req['status'] != 1){
            return false;
        }else{
            $result = $req['data'];
        }
        //$res_data = array();
        if($result == -1){
            // 注册账号
            $username = $this->get_rand_username();
            $password = md5('chaoshipos'.$username);
            $req = $UcApi->execute('User', 'register', array('username' => $username, 'password' => $password, 'email' => '', 'mobile' => $mobile));
            if($req['status'] != 1){
                return false;
            }else{
                $uid = $req['data'];
            }
            if($uid <= 0 ){
                return false;
            }
        }else{
            // 获取已注册账号
            $uid = $result[0];
            $password = md5('chaoshipos'. $result[1]);
        }
        $req = $UcApi->execute('User', 'login', array('username' => $uid, 'password' => $password, 'type' => 4));
        if($req['status'] != 1 || !$req['data']){
            return false;
        }
        return $uid;
    }

    // 获取随机用户名
    private function get_rand_username(){
        $a = 'abcdefghijklmnopqrstuvwxyz';
        $k = mt_rand(0, 25);
        $username = $a{$k}.date('ymdhis').mt_rand(10,99);
        
        $UcApi = new \User\Client\Api();
        $req = $UcApi->execute('User', 'checkUsername', array());
        if($req['status'] != 1){
            return false;
        }else{
            $result = $req['data'];
        }
        if($result !=1 ){
            $username = $this->get_rand_username();
        }
        return $username;
    }
    
    public function get_im($uid, $data = array()){
        $uid = intval($uid);
        if(!($uid > 0)){
            return false;
        }
        $Model = M('MemberIm');
        $info = $Model->where(array('uid' => $uid))->find();
        $ImApi = new \Addons\Alipay\Lib\Openim\Api();
        $result['userid'] = '';
        $result['password'] = '';
        if(!$info){
            $nickname = !empty($data['nickname']) ? $data['nickname'] : get_nickname($uid);
            $header_pic = !empty($data['header_pic']) ? $data['header_pic'] : get_header_pic($uid);
            $pre = 'im'.(isset($_SERVER['HTTP_HOST']) ? substr(md5($_SERVER['HTTP_HOST']), 2, 2) : '');
            $im_data = $ImApi->add_user($uid, '', '', $nickname, $header_pic, array(), $pre);
            if($im_data['status'] == 1 && !empty($im_data['data'])){
                $ImModel = M('MemberIm');
                if($ImModel->create(array('uid' => $uid, 'userid' => $im_data['data']['userid'],'create_time' => NOW_TIME)) && $ImModel->add()){
                    $result['userid'] = $im_data['data']['userid'];
                    $result['password'] = $im_data['data']['password'];
                }
            }
        }else{
            $im_data = $ImApi->get_user_info($info['userid']);
            $result['userid'] = $im_data['userid'];
            $result['password'] = $im_data['password'];
        }
        return $result;
    }
    
    public function update_im($uid, $data = array()){
        $im_data = $this->get_im($uid, $data);
        if(!empty($im_data['userid'])){
            $ImApi = new \Addons\Alipay\Lib\Openim\Api();
            $ImApi->update_user($im_data['userid'], !empty($data['nickname']) ? $data['nickname'] : '', !empty($data['header_pic']) ? $data['header_pic'] : '');
        }
    }
    
    public function follow($uid, $fid){
        $UcApi = new \User\Client\Api();
        // 检测是否有账号
        $req = $UcApi->execute('User', 'follow', array('uid' => $uid, 'fid' => $fid));
        if($req['status'] != 1){
            return false;
        }
        $result = $req['data'];
        if($result['status'] == 1){
            $type = $result['type'];
            if($type == 1){
                $this->where(array('uid' => $uid))->setInc('follow_num');
                $this->where(array('uid' => $fid))->setInc('be_follow_num');
            }else{
                $this->where(array('uid' => $uid))->setDec('follow_num');
                $this->where(array('uid' => $fid))->setDec('be_follow_num');
            }
            return array('status' => 1, 'type' => $type);
        }else{
            return array('status' => 0);
        }
    }
    
    public function info($uid, $is_get_base = false){
        $info = M('Member')->where(array('uid' => $uid, 'status' => 1))->field('uid, nickname, sex, cover_id,follow_num,be_follow_num')->find();
        if(!$info){
            return array();
        }
        $info['header_pic'] = get_header_pic($info['cover_id'], 'cover_id');
        unset($info['cover_id']);
        if($is_get_base){
            $UcApi = new \User\Client\Api();
            // 检测是否有账号
            $req = $UcApi->execute('User', 'info', array('uid' => $uid));
            if($req['status'] != 1){
                return array();
            }
            $info['username'] = $req['data'][1];
            $info['email'] = $req['data'][2];
            $info['mobile'] = $req['data'][3];
        }
        return $info;
    }
    
    public function check_full_info($uid){
        
    }
}
