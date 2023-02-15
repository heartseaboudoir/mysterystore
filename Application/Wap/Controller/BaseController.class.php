<?php

namespace Wap\Controller;

use Think\Controller;

/**
 * 前台公共控制器
 */
class BaseController extends Controller {
    /* 空操作，用于输出404页面 */

    protected $uid = 0;
    protected $app_data_id = '';
    protected $app_data = array();
    public function _empty() {
        $this->redirect('Index/index');
    }

    protected function _initialize() {
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
            !defined('APP_TYPE') && define('APP_TYPE', 'wechat');
        }elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false){
            !defined('APP_TYPE') && define('APP_TYPE', 'alipay');
        }
        $this->assign('APP_TYPE', APP_TYPE);
        
        /* 读取站点配置 */
        $config = api('Config/lists');
        C($config); //添加配置

        if (!C('WEB_SITE_CLOSE')) {
            $this->error('站点已经关闭，请稍后访问');
            exit();
        }
        $this->ukey = '';
        $this->assign('ukey', $this->ukey);
        $this->uid = is_login();
        $this->assign('uid', $this->uid);
        
        if(APP_TYPE == 'wechat'){
            $js_api = A("Addons://Wechat/Wechatclass")->getSignPackage();
            $this->assign('js_api', $js_api);
        }
    }

    protected function lists($model, $where = array(), $order = '', $base = array('status' => array('egt', 0)), $field = true) {
        $options = array();
        $REQUEST = (array) I('request.');
        if (is_string($model)) {
            $model = M($model);
        }

        $OPT = new \ReflectionProperty($model, 'options');
        $OPT->setAccessible(true);

        $pk = $model->getPk();
        if ($order === null) {
            //order置空
        } else if (isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']), array('desc', 'asc'))) {
            $options['order'] = '`' . $REQUEST['_field'] . '` ' . $REQUEST['_order'];
        } elseif ($order === '' && empty($options['order']) && !empty($pk)) {
            $options['order'] = $pk . ' desc';
        } elseif ($order) {
            $options['order'] = $order;
        }
        unset($REQUEST['_order'], $REQUEST['_field']);

        $options['where'] = array_filter(array_merge((array) $base, /* $REQUEST, */ (array) $where), function($val) {
            if ($val === '' || $val === null) {
                return false;
            } else {
                return true;
            }
        });
        if (empty($options['where'])) {
            unset($options['where']);
        }
        $options = array_merge((array) $OPT->getValue($model), $options);
        $total = $model->where($options['where'])->count();

        if (isset($REQUEST['r'])) {
            $listRows = (int) $REQUEST['r'];
        } else {
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ($total > $listRows) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $options['limit'] = $page->firstRow . ',' . $page->listRows;

        $model->setProperty('options', $options);

        $result = $model->field($field)->select();
        !$result && $result = array();
        return $result;
    }

    protected function get_open($get_info = true) {
        if(APP_TYPE == 'wechat'){
            $wechat = session('user_wechat'.$this->ukey);
            if(empty($wechat['openid']) || ($get_info == true && empty($wechat['nickname']))){
                $weixin = A("Addons://Wechat/Wechatclass");
                $wechat = $weixin->oauth_user($get_info ? 'userinfo' : 'base');
                session('user_wechat'.$this->ukey, $wechat);
            }
            $this->app_data = $wechat;
            $this->app_data_id = $wechat['openid'];
            return $wechat;
        }elseif(APP_TYPE == 'alipay'){
            $alipay = session('user_alipay');
            if(empty($alipay['user_id']) || ($get_info == true && $alipay['scope'] != 'auth_userinfo')){
                $ali_server = A("Addons://AlipayServer/index");
                $alipay = $ali_server->oauth($get_info ? 'userinfo' : 'base');
                session('user_alipay', $alipay);
            }
            $this->app_data = $alipay;
            $this->app_data_id = $alipay['user_id'];
            return $alipay;
        }else{
            exit;
        }
    }

    protected function _set_msg($status, $msg = '', $data = array()) {
        echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
        exit;
    }

    protected function check_login($url = '') {
        $this->get_open(true);
        $this->uid = is_login();
        if (!$this->uid) {
            // 如果已经绑定，则直接登录
            $uid = D('Common/MemberBind')->check_bind(APP_TYPE, $this->app_data_id);
            // 检测是否已检测手机
            if($uid > 0 && get_mobile($uid) && D('Member')->login($uid)){
                $this->uid = $uid;
                return true;
            }
            !$url && $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            session('_ref', $url);
            if(IS_AJAX){
                $this->error('请登录', U('User/login'));
            }else{
                redirect(U('User/login'));
            }
        }
    }
}
