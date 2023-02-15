<?php

namespace Apiv2\Controller;

use Think\Controller;

class ApiController extends Controller {

    public $_uid = 0, $_store_id = 0;
    protected $set_field = array();
    public function __construct() {
        parent::__construct();
        $this->_uid = I('uid');
        // 客户端类型： 1 安卓 2 IOS
        $this->device_type = isset($_SERVER['HTTP_DEVICE']) ? $_SERVER['HTTP_DEVICE'] : 0;
        $this->version_no = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $this->api = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        // 加载配置
        $config = api('Config/lists');
        C($config);
        //$no_check_mobile = I('mobile');
        //if($no_check_mobile != '18520120884') {
            // 检查api通讯密钥是否正确【苹果审核期间屏蔽，审核完毕再启用】
            //$this->_check_utoken();
        //}
    }
    /*
     * 接口调用检查合法
     */
    private function _check_utoken(){
        $key = C('UTOKEN_KEY');
        $utoken = $_SERVER['HTTP_UTOKEN'];
        $api_url = trim(strtolower((is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST']. (strpos($_SERVER['REQUEST_URI'], '?') !== false ? substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) : $_SERVER['REQUEST_URI'])), '/');
        $ck = md5($api_url.$key.date('Y-m-d'));
        if($utoken != $ck){
            $this->return_data(-100, '', '访问失败');
        }
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
            $v = $this->_set_string($v, $k);
            $data[$k] = $v;
        }
        return $data;
    }
    private function _set_field($data, $key){
        is_null($data) && $data = '';
        if(isset($this->set_field[$key])){
            switch($this->set_field[$key]){
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
        }else{
            $data = is_null($data) ? '' : strval($data);
        }
        return $data;
    }
    protected function _lists($model, $where = array(), $order = '', $page = 1, $row = 20, $field = '', $join = '', $alias = ''){
        $join && $model->join($join);
        $alias && $model->alias($alias);
        $lists = $model->where($where)->order($order)->page($page, $row)->field($field)->select();
        !$lists && $lists = array();
        $join && $model->join($join);
        $alias && $model->alias($alias);
        $total = $model->where($where)->count();
        return array('data' => $lists, 'page' => $page, 'row' => $row, 'total' => $total, 'count' => count($lists));
    }
    protected function return_lists($status, $data, $page, $row, $count, $total, $msg = '', $other = array()){
        $totalpage = ceil($total/$row);
        $other['offset'] = $page;
        $other['row'] = $row;
        $other['totalpage'] = $totalpage;
        $other['count'] = $count;
        $other['total'] = $total;
        $this->return_data($status, $data, $msg, $other);
    }
    protected function return_lists_by_arr($status, $data, $msg = '', $other = array()){
        $this->return_lists(
            $status, 
            isset($data['data']) ? $data['data'] : array(), 
            isset($data['page']) ? $data['page'] : 1, 
            isset($data['row']) ? $data['row'] : 0, 
            isset($data['count']) ? $data['count'] : 0, 
            isset($data['total']) ? $data['total'] : 0, 
            $msg, 
            $other
        );
    }
    protected function _check_param($param = array()) {
        if (!$param) {
            return true;
        }
        !is_array($param) && $param = explode(',', $param);
        foreach ($param as $v) {
            if (is_array($v)) {
                if (empty($_REQUEST[$v['key']])) {
                    $msg = !empty($v['msg']) ? $v['msg'] : ('参数未传：' . $v['key'] . ' 或参数不能为空');
                    $this->return_data(-2, '', $msg);
                }
            } else {
                $v = trim($v);
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
    // 生成token
    protected function get_account_token( $uid, $mobile, $password = ''){
        $data = S('AccountUser');
        $key = md5(md5($mobile.'Account'.$password).NOW_TIME);
        /* 同时保存两个token */
        if(isset($data['access'][$uid]) ){
            !is_array($data['access'][$uid]) && $data['access'][$uid] = array($data['access'][$uid]);
            //$old = array_pop($data['access'][$uid]);
            $del_key = $data['access'][$uid];
            $data['access'][$uid] = array();
        }else{
            $data['access'][$uid] = array();
            $del_key = array();
        }
        $data['access'][$uid][] = $key; 
        
        if($del_key){
            foreach($del_key as $v){
                if(isset($data['token'][$v]) && isset($data['token'][$v]['uid']) && $data['token'][$v]['uid'] == $uid){
                    unset($data['token'][$v]);
                }
            }
        }
        $data['token'][$key] = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'password' => $password,
            'time' => NOW_TIME
        );
        S('AccountUser', $data);
        return $key;
    }
    
    // 取消用户的登录状态
    protected function clear_account_token($uid){
        $data = S('AccountUser');
        if(isset($data['access'][$uid])){
            !is_array($data['access'][$uid]) && $data['access'][$uid] = array($data['access'][$uid]);
            $del_key = $data['access'][$uid];
            
            
            unset($data['access'][$uid]);
            
            if($del_key){
                foreach($del_key as $v){
                    if(isset($data['token'][$v]) && isset($data['token'][$v]['uid']) && $data['token'][$v]['uid'] == $uid){
                        unset($data['token'][$v]);
                    }
                }
            }
            S('AccountUser', $data);            
            
        }
    }     
    
    // 检测token是否合法
    protected function check_account_token($is_must = true, $is_return = false){
        $token = I('token');
        $ctime = I('ctime');
        if(!$token){
            if($is_return || !$is_must){
                return array();
            }
            $this->return_data(-1, '', 'token值不存在');
        }
        $data = S('AccountUser');
        if(isset($data['token'][$token])){
            $uinfo = $data['token'][$token];
            // 对应用户没有token记录时返回异常
            if(!isset($data['access'][$uinfo['uid']])){
                unset($data['token'][$token]);
                S('AccountUser', $data);
                if($is_return || !$is_must){
                    return array();
                }
                $this->return_data(-1, '', '请登录');
            }
            // 当登录的token不是最新两次登录的，则返回异常
            $token_arr = is_array($data['access'][$uinfo['uid']]) ? $data['access'][$uinfo['uid']] : array($data['access'][$uinfo['uid']]);
            if(!in_array($token, $token_arr)){
                unset($data['token'][$token]);
                S('AccountUser', $data);
                if($is_return || !$is_must){
                    return array();
                }
                $this->return_data(-1, '', '登录超时');
            }
            // 当登录的token为最新提供的token时，清理之前的token，只保留最新
            $new_token = array_pop($token_arr);
            if($token == $new_token && $token_arr){
                foreach($token_arr as $v){
                    if(isset($data['token'][$v])){
                        unset($data['token'][$v]);
                    }
                }
                $data['access'][$uinfo['uid']] = array($token);
                S('AccountUser', $data);
            }
            $this->_uid = $uinfo['uid'];
            $this->_uinfo = $uinfo;
            if($is_return){
                return $uinfo;
            }
        }else{
            if($is_return || !$is_must){
                return array();
            }
            $this->return_data(-1, '', '请登录');
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
        $UcApi = new \User\Client\Api();
        $req = $UcApi->execute($api, $action, $param);
        if($req['status'] != 1){
            $this->return_data(0, '', $req['msg']);
        }else{
            return $req['data'];
        }
    }

}
