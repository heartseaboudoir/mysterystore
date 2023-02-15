<?php

namespace Api\Controller;

use Think\Controller;

class ApiController extends Controller {

    public $_uid = 0, $_store_id = 0;
    
    public function __construct() {
        parent::__construct();
        $this->_uid = I('uid');
        // 客户端类型： 1 安卓 2 IOS
        $this->device_type = I('device_type', 1);
        $this->version_no = I('version_no', '');
        $this->api = 'Api/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        // 加载配置
        $config = api('Config/lists');
        C($config);
    }
    protected function return_data($status, $data = '', $msg = '', $other = array()) {
        if (!$msg) {
            if ($status == 1) {
                $msg = 'success';
            } elseif (!$status) {
                $msg = 'fail';
            }
        }
        !$data && !is_array($data) && $data = (object)array();
        $result = array(
            'status' => intval($status),
            'msg' => $msg,
            'data' => $this->_set_string($data)
        );
        if($status == -1){
            unset($result['data']);
        }
        $other && $result = array_merge($result, $other);
        $this->ajaxReturn($result, 'JSON');
    }

    private function _set_string($data, $pre = '') {
        if (is_array($data)) {
            $data = $data ? $this->_set_array($data) : array();
        } elseif($data && is_object($data)){
            $data = (array)$data;
            $data = $this->_set_array($data);
            $data = (object)$data;
        }else {
            $data = $this->_set_field($data, $pre);
        }
        return $data;
    }
    private function _set_array($data){
        foreach ($data as $k => $v) {
            preg_match('/^([SIFO]{1,2})\_(.+)/', $k, $match);
            $_pre = '';
            $_k = '';
            if($match){
                $_pre = $match[1];
                $_k = $k;
                $k = $match[2];
            }
            $v = $this->_set_string($v, $_pre);
            $data[$k] = $v;
            if($_k){
                unset($data[$_k]);
            }
        }
        return $data;
    }
    private function _set_field($data, $pre){
        is_null($data) && $data = '';
        switch($pre){
            case 'S':
                $data = strval($data);
                break;
            case 'I':
                $data = intval($data);
                break;
            case 'F':
                $data = floatval($data);
                break;
            case 'O':
                $data = (object)($data);
                break;
            default:
                $data = is_null($data) ? '' : strval($data);
        }
        return $data;
    }

    protected function _check_param($param = array()) {
        if (!$param) {
            return true;
        }
        !is_array($param) && $param = array();
        foreach ($param as $v) {
            if (is_array($v)) {
                if (empty($_REQUEST[$v['key']])) {
                    $msg = !empty($v['msg']) ? $v['msg'] : ('参数未传：' . $v['key'] . ' 或参数不能为空');
                    $this->return_data(-2, '', $msg);
                }
            } else {
                if (empty($_REQUEST[$v])) {
                    $this->return_data(-2, '', '参数未知：' . $v . ' 或参数不能为空');
                }
            }
        }
    }

    /**
     * @name    _check_token
     * @title   验证token
     * @param   int     $uid    用户ID
     * @param   string     $pos_id    设备标识
     * @param   string  $token  token值(20位,  md5($uid+$key+$randcode+timestr) ， 第9个开始取20个字符)
     */
    protected function _check_token() {
        $this->_check_param(array('uid', 'token', 'pos_id'));
        $uid = $this->_uid = I('uid', 0);
        $token = I('token');
        $key = '!chaoshipos@';
        $pos_id = I('pos_id');
        if ($token != $key) {
            $this->return_data(0, array(), 'token验证失败');
        }
        $info = M('Member')->where(array('uid' => $uid))->find();
        if (!$info) {
            $this->return_data(0, array(), '用户不存在');
        }
        $this->_store_id = $info['store_id'];
        $this->_pos_id = $info['pos_id'];
        if (!$this->_pos_id) {
            $this->return_data(0, array(), '请先登录门店设备');
        }
        if ($pos_id != $this->_pos_id) {
            $this->return_data(0, array(), '设备信息不匹配');
        }
    }

    /**
     * @name    _check_token_app
     * @title   验证token
     * @param   int        $uid        用户ID
     * @param   string     $pos_id    设备标识
     * @param   string     $token  token值(20位,  md5($uid+$key+$randcode+timestr) ， 第9个开始取20个字符)
     */
    protected function _check_token_app() {
        $this->_check_param(array('uid', 'token'));
        $uid = $this->_uid = I('uid', 0);
        $token = I('token');
        $key = '!chaoshipos@';
        if ($token != $key) {
            $this->return_data(0, array(), 'token验证失败');
        }
        $info = M('Member')->where(array('uid' => $uid))->field('uid')->find();
        if (!$info) {
            $this->return_data(0, array(), '用户不存在');
        }
    }
    /**
     * @name _upload_pic
     * @title 图片上传
     * @param string $name  上传数组的KEY
     * @return 图片数组
     */
    protected function _upload_pic($name = null) {
        if (!$name) {
            return array('status' => 0, 'data' => array(), 'msg' => '上传key不存在');
        }
        $name_arr = is_array($name) ? $name : array($name);
        $return_arr = is_array($name) ? 1 : 0;
        foreach ($name_arr as $v) {
            if (empty($_FILES[$v])) {
                return array('status' => 0, 'data' => array(), 'msg' => '上传的name值[ ' . $v . ' ]不存在');
            }
        }
        /* 调用文件上传组件上传文件 */
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $info = $Picture->upload(
                $_FILES, C('PICTURE_UPLOAD'), C('PICTURE_UPLOAD_DRIVER'), C("UPLOAD_{$pic_driver}_CONFIG")
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if (!$info) {
            return array('status' => 0, 'data' => array(), 'msg' => $Picture->getError());
        }
        $data = array();
        if ($return_arr) {
            foreach ($name_arr as $v) {
                $data[$v] = $info[$v];
            }
        } else {
            $data = $info[$name];
        }
        return array('status' => 1, 'data' => $data, 'msg' => '');
    }

    protected function uc_api($api, $action, $param = array()){
        $req = \User\Client\Api::execute($api, $action, $param);
        if($req['status'] != 1){
            $this->return_data(0, $req['msg']);
        }else{
            return $req['data'];
        }
    }
}
