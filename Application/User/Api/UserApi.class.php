<?php
namespace User\Api;
use User\Api\Api;

class UserApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        $this->model = D('UcenterMember');
    }

    /**
     * 注册一个新用户
     * @param  string $username 用户名
     * @param  string $password 用户密码
     * @param  string $email    用户邮箱
     * @param  string $mobile   用户手机号码
     * @return integer          注册成功-用户信息，注册失败-错误编号
     */
    public function register($username, $password, $email, $mobile = ''){
        return $this->model->register($username, $password, $email, $mobile);
    }

    /**
     * 用户登录认证
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function login($username, $password, $type){
        is_null($type) && $type = 1;
        return $this->model->login($username, $password, $type);
    }

    /**
     * 获取用户信息
     * @param  string  $uid         用户ID或用户名
     * @param  boolean $is_username 是否使用用户名查询
     * @return array                用户信息
     */
    public function info($uid, $is_username){
        is_null($is_username) && $is_username = false;
        return $this->model->info($uid, $is_username);
    }

    /**
     * 检测用户名
     * @param  string  $field  用户名
     * @return integer         错误编号
     */
    public function checkUsername($username){
        return $this->model->checkField($username, 1);
    }

    /**
     * 检测邮箱
     * @param  string  $email  邮箱
     * @return integer         错误编号
     */
    public function checkEmail($email){
        return $this->model->checkField($email, 2);
    }

    /**
     * 检测手机
     * @param  string  $mobile  手机
     * @return integer         错误编号
     */
    public function checkMobile($mobile){
        return $this->model->checkField($mobile, 3);
    }

    /**
     * 更新用户信息
     * @param int $uid 用户id
     * @param string $password 密码，用来验证
     * @param array $data 修改的字段数组
     * @param boolean $is_in 是否需要验证密码
     * @return true 修改成功，false 修改失败
     * @author huajie <banhuajie@163.com>
     */
    public function updateInfo($uid, $password, $data, $is_in){
        is_null($is_in) && $is_in = true;
        if($this->model->updateUserFields($uid, $password, $data, $is_in) !== false){
            $return['status'] = true;
        }else{
            $return['status'] = false;
            $return['info'] = $this->model->getError();
        }
        return $return;
    }

    public function verifyUser($uid, $password_in){
        return $this->model->verifyUser($uid, $password_in);
    }
    
    public function check_bind($type, $token){
        return D('MemberBind')->check_bind($type, $token);
    }
    
    public function bind($uid, $type, $token, $bind_data){
        is_null($bind_data) && $bind_data = array();
        return D('MemberBind')->bind($uid, $type, $token, $bind_data);
    }
    
    public function unset_bind($uid, $type){
        return D('MemberBind')->unset_bind($uid, $type);
    }
    
    public function get_bind($uid, $type){
        is_null($type) && $type = array();
        return D('MemberBind')->get_bind($uid, $type);
    }
    /**
     * 添加/取消关注
     * @param type $uid     会员ID
     * @param type $fid     要关注的会员ID
     * @return int
     */
    public function follow($uid, $fid){
        if($uid == $fid){
            return array('status' => 0);
        }
        $Model = M('MemberFollow');
        $data = array('uid' => $uid, 'fid' => $fid);
        if($Model->where($data)->find()){
            $result = $Model->where($data)->delete();
            $type = 2;
        }else{
            $result = $Model->add(array('uid' => $uid, 'fid' => $fid, 'create_time' => NOW_TIME));
            $type = 1;
            $this->inside_api('Message', 'add_notice', array('act_uid' => $uid, 'act_id' => $uid, 'type' => 'follow', 'title'=> '新的关注', 'content' => '有人关注了你', 'uid' => $fid, 'param' => array(), 'hid' => 'follow'));
        }
        return array('status' => $result ? 1 : 0, 'type' => $type);
    }
    
    public function check_follow($uid, $check_uids){
        is_null($check_uids) && $check_uids = array();
        !is_array($check_uids) && $check_uids = explode(',', $check_uids);
        $Model = M('MemberFollow');
        $where = array(
            'uid' => $uid,
            'fid' => array('in', $check_uids)
        );
        $list = $Model->where($where)->select();
        $result = array();
        foreach($list as $v){
            $result[] = $v['fid'];
        }
        return $result;
    }
    
    public function get_follow($uid){
        $Model = M('MemberFollow');
        $lists = $Model->where(array('uid' => $uid))->select();
        $result = array();
        if($lists){
            foreach($lists as $v){
                $result[] = $v['fid'];
            }
        }
        return $result;
    }
    public function get_follow_lists($type, $uid, $check_uid, $page, $row){
        is_null($page) && $page = 1;
        is_null($row) && $row = 20;
        $check_uid = intval($check_uid);
        $Model = M('MemberFollow');
        $where = array();
        if($type == 1){
            $where['uid'] = $uid;
            $field = 'fid as uid,create_time';
        }else{
            $where['fid'] = $uid;
            $field = 'uid, create_time';
        }
        $lists = $Model->where($where)->field($field)->page($page, $row)->order('id desc')->select();
        !$lists && $lists = array();
        if($lists && $check_uid > 0){
            $uids = array();
            foreach($lists as $v){
                $uids[] = $v['uid'];
            }
            $in_follow = $this->check_follow($check_uid, $uids);
            
            foreach($lists as $k => $v){
                $v['is_follow'] = ($in_follow && in_array($v['uid'], $in_follow)) ? 1 : 0;
                $lists[$k] = $v;
            }
        }
        $total = $Model->where($where)->count();
        $count = count($lists);
        return array('data' => $lists, 'page' => $page, 'row' => $row, 'total' => $total, 'count' => $count);
    }
    public function check_is_auth_by_data($uid){
        $AuthModel = M('MemberAuth');
        $data = $AuthModel->where(array('uid' => array('in', $uid)))->select();
        $result = array();
        if($data){
            foreach($data as $v){
                $result[] = $v['uid'];
            }
        }
        return $result;
    }
    public function check_is_auth($uid){
        $uid = intval($uid);
        if(!($uid > 0)){
            return 0;
        }
        $Model = M('MemberAuthApply');
        $AuthModel = M('MemberAuth');
        if($AuthModel->where(array('uid' => $uid))->find()){
            return 1;
        }
        if($Model->where(array('uid' => $uid, 'status' => 1))->find()){
            return 2;
        }
        return 0;
    }
    public function check_auth_apply($uid){
        $uid = intval($uid);
        if(!($uid > 0)){
            return array('status' => 0);
        }
        $Model = M('MemberAuthApply');
        $AuthModel = M('MemberAuth');
        if($AuthModel->where(array('uid' => $uid))->find()){
            return array('status' => 0, 'msg' => '用户已认证');
        }
        if($Model->where(array('uid' => $uid, 'status' => 1))->find()){
            return array('status' => 0, 'msg' => '已提交认证审核');
        }
        return array('status' => 1);
    }
    public function auth_apply($uid, $real_name, $cert_no, $cert_pic1, $cert_pic2, $cert_pic3, $mobile){
        $uid = intval($uid);
        if(!($uid > 0)){
            return array('status' => 0);
        }
        $Model = M('MemberAuthApply');
        $AuthModel = M('MemberAuth');
        if($AuthModel->where(array('uid' => $uid))->find()){
            return array('status' => 0, 'msg' => '用户已认证');
        }
        if($AuthModel->where(array('cert_no' => $cert_no))->find()){
            return array('status' => 0, 'msg' => '身份证号码已被认证');
        }
        if($Model->where(array('uid' => $uid, 'status' => 1))->find()){
            return array('status' => 0, 'msg' => '已提交认证审核');
        }
        $data = array(
            'uid' => $uid,
            'real_name' => $real_name,
            'cert_no' => $cert_no,
            'cert_pic1' => $cert_pic1,
            'cert_pic2' => $cert_pic2,
            'cert_pic3' => $cert_pic3,
            'mobile' => $mobile,
            'status' => 1,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME
        );
        if($Model->create($data)){
            if($Model->add()){
                return array('status' => 1);
            }
        }
        return array('status' => 0, 'msg' => '认证申请提交失败');
    }
}
