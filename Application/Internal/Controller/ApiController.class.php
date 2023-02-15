<?php

namespace Internal\Controller;

use Think\Controller;

/**
 * Erp for App base class
 */
class ApiController extends Controller {
    // 处理成功响应码
    const RESPONSE_SUCCES = 200;
    
    // 用户uid,0：未登录成功
    public $uid = 0;
    
    // 用户登录成功后的用户信息
    public $uinfo = array();

    // 是否验证用户的登录的信息
    protected $ctoken = false;
    
    // 初始化信息
    public function _initialize()
    {
        // 获取用户信息
        $uinfo = $this->uinfo();
        $this->uinfo = $uinfo;
        if (!empty($uinfo['uid'])) {
            $this->uid = $uinfo['uid'];
        }
        
        // 是否效验用户登录状态
        if ($this->ctoken && !$this->ifLogin()) {
            $this->response(10101, 'token error');
        }
        
        // 加载配置
        $config = api('Config/lists');
        C($config);        
        
        
    }

    // 检测token的有效性及权限
    protected function ifLogin()
    {
        
        $userinfo = $this->uinfo();
        if (empty($userinfo)) {
            return false;
        } else {
            return true;
        }
        
    }
    



    
    // 返回用户信息
    protected function uinfo()
    {
        $token = I('token', '', 'trim');
        
        if (empty($token)) {
            return array();
        }
        
        $data = S('InternalUserErp');
        
        if (!empty($data['token'][$token])) {
            return $data['token'][$token];
        } else {
            return array();
        }
    }    
    
    /** 
     * 用户登录成功后
     * 获取token
     * 说明：这里的用户登录状态及token信息是记录在系统缓存文件中.
     * 原因：系统用户多张表分散，前后台用户又存储在一起；系统的用户信息又通过接口隔离；
     *       也为保证与PC端登录后相关处理方式一致所以未直接操作表，这也是上一版的设计方案。
     */
    protected function get_token( $uid, $username, $password, $group){
        $data = S('InternalUserErp');
        $token = md5(md5($username.'Internal'.$password).NOW_TIME);
        
        $old = isset($data['access'][$uid]) ? $data['access'][$uid] : '';
        $data['access'][$uid] = $token;
        $data['token'][$token] = array(
            'uid' => $uid,
            'username' => $username,
            'password' => $password,
            'group_id' => $group,
            'time' => NOW_TIME
        );
        if($old && isset($data['token'][$old])) unset($data['token'][$old]);
        
        S('InternalUserErp', $data);
        return $token;
    }


    /**
     * 用户退出
     * 清除用户信息
     */
    protected function clean_token($uid)
    {
        $data = S('InternalUserErp');
        if (isset($data['access'][$uid])) {
            $token = $data['access'][$uid];
            if (!empty($token) && isset($data['token'][$token])) {
                unset($data['access'][$uid]);
                unset($data['token'][$token]);
                S('InternalUserErp', $data);
                return $token;
            }
        }
        return true;
    }

    /**
     * 响应接口请求
     */
    protected function response($code = self::RESPONSE_SUCCES, $msg = '已处理请求') 
    {
        if ($code == self::RESPONSE_SUCCES) {
            $data = array(
                'code' => self::RESPONSE_SUCCES,
                'content' => $msg,
            );
        } else {
            $data = array(
                'code' => $code,
                'content' => $msg,
            );
        }

        //echo json_encode($data, JSON_UNESCAPED_UNICODE);
        echo json_encode($data);
        exit;
    }     


}
