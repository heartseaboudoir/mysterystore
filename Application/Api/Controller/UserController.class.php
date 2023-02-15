<?php
// +----------------------------------------------------------------------
// | Title: 设备管理
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 门店端
// +----------------------------------------------------------------------
namespace Api\Controller;

use User\Api\UserApi;

class UserController extends ApiController {
    /**
     * @name  login
     * @title 登录接口
     * @param string $username 用户名
     * @param string $password 密码 (用md5加密)
     * @param string $pos_id   登录的设备编号
     * @param string $pos_title   登录的设备名称
     * @return [uid] => 用户ID<br>
                [username] => 用户登录名<br>
                [nickname] => 用户姓名<br>
                [pay_seconds] => 支付限时秒长<br>
                [store_title] => 门店名<br>
                [store_admin] => 门店管理员<br>
                [pos_title] =>  设备名称<br>
                [pos_tag] =>  设备标签<br>
     * @remark 管理员只能同时在一个设备上登录，若上次登录未退出，下次登录只能通过原设备登录，或通过后台退出后才可登录另外的设备。
     */
    public function login() {
        $this->_check_param(array('username', 'password', 'pos_id', 'pos_title'));
        $status = 0;
        $msg = "";
        $data = array();
        $username = I('username');
        $password = I('password');
        $pos_id = I('pos_id');
        $pos_title = I('pos_title');
        if(!$pos_id){
            $this->return_data(0, array(), '设备编号未知');
        }
        if(!$pos_title){
            $this->return_data(0, array(), '设备名称未知');
        }
        if (!empty($username)) { //登录验证
            $password = $this->_set_password($password);
            /* 调用UC登录接口登录 */
            $req = \User\Client\Api::execute('User', 'login', array('username' => $username, 'password' => $password));
            if($req['status'] != 1){
                $this->return_data(0);
            }else{
                $uid = $req['data'];
            }
            if (0 < $uid) { //UC登录成功
                /* 登录用户 */
                $Member = D('Member');
                $info = $Member->where(array('uid' => $uid))->find();
                if($info['pos_id'] && $info['pos_id'] != $pos_id){
                    $this->return_data(0, array(), '登录失败，已在其他设备上登录');
                }
                if($info['bind_pos'] && $info['bind_pos'] != $pos_id){
                    $this->return_data(0, array(), '登录失败，管理员已绑定其他设备');
                }
                $store = M('Store')->where(array('id' => $info['store_id'], 'status' => 1))->find();
                if(!$store){
                    $this->return_data(0, array(), '门店不存在或已关闭');
                }
                if ($Member->login($uid)) { //登录用户
                    $status = 1;
                    $msg = "登录成功！";
                    $pos_tag = 'store_'.$info['store_id'];
                    M('Member')->where(array('uid' => $uid))->save(array('pos_id' => $pos_id, 'pos_title' => $pos_title));
                    M('LoginStoreLog')->where(array('uid' => $uid, 'store_id' => $info['store_id'], 'pos_id' => $pos_id, 'out' => 0))->save(array('out' => NOW_TIME, 'remark' => '上次登录未退出'));
                    M('LoginStoreLog')->add(array('uid' => $uid, 'store_id' => $info['store_id'], 'pos_id' => $pos_id, 'in' => NOW_TIME, 'pos_title' => $pos_title));
                    
                    $config = api('Config/lists');
                    C($config);
                    
                    $data = array(
                        'uid' => $uid,
                        'username' => $username,
                        'nickname' => $info['nickname'],
                        'store_title' => $store['title'],
                        'store_admin' => get_nickname($store['admin']),
                        'pos_title' => $pos_title,
                        'pay_seconds' => C('PAY_SECONDS'),
                        'pos_tag' => $pos_tag
                    );
                } else {
                    $msg = $Member->getError();
                }
            } else { //登录失败
                switch ($uid) {
                    case -1: $error = '管理员不存在或被禁用！';
                        break; //系统级别禁用
                    case -2: $error = '密码错误！';
                        break;
                    default: $error = '未知错误！';
                        break; // 0-接口参数错误（调试阶段使用）
                }
                $msg = $error;
            }
        } else { //显示登录表单
            $this->return_data(0, array(), "用户名和密码不能为空");
        }
        $this->return_data($status, $data, $msg);
    }
    /**
     * @name  logout
     * @title 退出门店登录
     */
    public function logout(){
        $this->_check_token();
        if(M('Member')->where(array('uid' => $this->_uid))->save(array('pos_id' => '', 'pos_title' => ''))){
            M('LoginStoreLog')->where(array('uid' => $this->_uid, 'store_id' => $this->_store_id, 'out' => 0))->save(array('out' => NOW_TIME, 'remark' => '正常退出'));
            $this->return_data(1, array(), '成功退出登录');
        }else{
            $this->return_data(0, array(), '退出登录失败');
        }
        
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0) {
        switch ($code) {
            case -1: $error = '手机号码长度必须在16个字符以内！';
                break;
            case -2: $error = '手机号码被禁止注册！';
                break;
            case -3: $error = '手机号码被占用！请更换手机号码注册或登录！';
                break;
            case -4: $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5: $error = '邮箱格式不正确！';
                break;
            case -6: $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7: $error = '邮箱被禁止注册！';
                break;
            case -8: $error = '邮箱被占用！';
                break;
            case -9: $error = '手机格式不正确！';
                break;
            case -10: $error = '手机被禁止注册！';
                break;
            case -11: $error = '手机号被占用！';
                break;
            default: $error = '未知错误';
        }
        return $error;
    }
    private function _set_password($password){
        if(strlen($password) != 32){
            $this->return_data(0, array(), '密码非法');
        }
        return '^md5'.$password.'md5$';
    }
}
