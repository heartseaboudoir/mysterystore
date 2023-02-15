<?php
namespace Addons\AlipayServer\Controller;

use Api\Controller\AddonsController;

class IndexController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        define('SDK_PATH', dirname(dirname(__FILE__)).'/sdk/');
        $this->ali_config = M('AlipayServerConfig')->find();
        define('PAY_APP_ID', $this->ali_config['appid']);
    }
    
    public function index(){
        require_once SDK_PATH.'function.inc.php';
        require_once SDK_PATH.'HttpRequst.php';
        require_once SDK_PATH.'config.php';
        require_once SDK_PATH.'AlipaySign.php';
        
        header ( "Content-type: text/html; charset=gbk" );
        /**
         * 此文件未对接支付宝服务器的网关文件，将此文件的访问路径填入支付宝服务窗的开发中验证网关的页面中。
         * 次文件接收支付宝服务器发送的请求
        */

        if (get_magic_quotes_gpc ()) {
            foreach ( $_POST as $key => $value ) {
                $_POST [$key] = stripslashes ( $value );
            }
            foreach ( $_GET as $key => $value ) {
                $_GET [$key] = stripslashes ( $value );
            }
            foreach ( $_REQUEST as $key => $value ) {
                $_REQUEST [$key] = stripslashes ( $value );
            }
        }
        
        $sign = \HttpRequest::getRequest ( "sign" );
        $sign_type = \HttpRequest::getRequest ( "sign_type" );
        $biz_content = \HttpRequest::getRequest ( "biz_content" );
        $service = \HttpRequest::getRequest ( "service" );
        $charset = \HttpRequest::getRequest ( "charset" );

        if (empty ( $sign ) || empty ( $sign_type ) || empty ( $biz_content ) || empty ( $service ) || empty ( $charset )) {
            echo "some parameter is empty.";
            exit ();
        }
        
        // 收到请求，先验证签名
        $as = new \AlipaySign ();
        $sign_verify = $as->rsaCheckV2 ( array('sign' => $sign, 'sign_type' => $sign_type, 'biz_content' => $biz_content, 'service' => $service, 'charset' => $charset), $config['alipay_public_key_file'] );
        if (! $sign_verify) {
            // 如果验证网关时，请求参数签名失败，则按照标准格式返回，方便在服务窗后台查看。
            if (\HttpRequest::getRequest ( "service" ) == "alipay.service.check") {
                $this->verifygw ( false );
            } else {
                echo "sign verfiy fail.";
            }
            exit ();
        }

        // 验证网关请求
        if (\HttpRequest::getRequest ( "service" ) == "alipay.service.check") {
            $this->verifygw ( true );
        } else if (\HttpRequest::getRequest ( "service" ) == "alipay.mobile.public.message.notify") {
            // 处理收到的消息
            require_once SDK_PATH.'Message.php';
            $msg = new \Message ( $biz_content );
        }
    }
    
    private function verifygw($is_sign_success) {
            $biz_content = \HttpRequest::getRequest ( "biz_content" );
            $as = new \AlipaySign ();
            $xml = simplexml_load_string ( $biz_content );
            $EventType = ( string ) $xml->EventType;
            if ($EventType == "verifygw") {
                global $config;
                require SDK_PATH.'config.php';
                if ($is_sign_success) {
                        $response_xml = "<success>true</success><biz_content>" . $as->getPublicKeyStr ( $config ['merchant_public_key_file'] ) . "</biz_content>";
                } else {
                        $response_xml = "<success>false</success><error_code>VERIFY_FAILED</error_code><biz_content>" . $as->getPublicKeyStr ( $config ['merchant_public_key_file'] ) . "</biz_content>";
                }
                $return_xml = $as->sign_response ( $response_xml, $config ['charset'], $config ['merchant_private_key_file'] );
                echo $return_xml;
                exit ();
            }
    }
    
    public function oauth($type = 'base'){
        if($type == 'userinfo'){
            $scope = 'auth_userinfo';
        }else{
            $scope = 'auth_base';
        }
        $access_token = $this->get_auth_token($scope);
        if(!$access_token){
            cookie('ALIPAY_SERVER_OAUTH', $_SERVER['REQUEST_URI']);
            $reditect_uri = urlencode(addons_url('AlipayServer://Index/callback'));
            $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id='.PAY_APP_ID.'&scope='.$scope.'&redirect_uri='.$reditect_uri;
            redirect($url);
            exit;
        }else{
            if($scope == 'auth_base'){
                return array('user_id' => $access_token['user_id'], 'scope' => 'auth_base');
            }else{
                $info = $this->getUserInfo($access_token['access_token']);
                if(!$info) return false;
                $info['scope'] = 'auth_userinfo';
                return $info;
            }
        }
    }
    
    public function callback(){
        $auth_code = $_GET['auth_code'];
        if($this->requestToken($auth_code, 'code')){
            $url = cookie('ALIPAY_SERVER_OAUTH');
            redirect($url);
        }else{
            exit;
        }
    }
    
    public function get_auth_token(){
        $token_data = session('alipay_oauth');
        if(empty($token_data['access_token']) || empty($token_data['refresh_token'])){
            return false;
        }
        $refresh = (NOW_TIME - $token_data['create_time']) < $token_data['re_expires_in'] ? true : false;
        if($token_data && $refresh){
            return $this->requestToken($token_data['refresh_token'], 'token');
        }else{
            return false;
        }
    }
    
    public function getUserInfo($access_token) {
        $user_info = $this->requestUserInfo ( $access_token );
        return isset($user_info->alipay_user_userinfo_share_response) ? (array)$user_info->alipay_user_userinfo_share_response : false;
    }
    
    public function requestUserInfo($token) {
        require_once SDK_PATH.'AopSdk.php';
        require_once SDK_PATH.'function.inc.php';
        require_once SDK_PATH.'aop/request/AlipayUserUserinfoShareRequest.php';
        $AlipayUserUserinfoShareRequest = new \AlipayUserUserinfoShareRequest ();
        $result = aopclient_request_execute ( $AlipayUserUserinfoShareRequest, $token );

        return $result;
    }
    // 获取token
    private function requestToken($code, $type = 'code') {
        require_once SDK_PATH.'AopSdk.php';
        require_once SDK_PATH.'HttpRequst.php';
        require_once SDK_PATH.'function.inc.php';
        require_once SDK_PATH.'aop/request/AlipaySystemOauthTokenRequest.php';
        $AlipaySystemOauthTokenRequest = new \AlipaySystemOauthTokenRequest ();
        if($type == 'token'){
            $AlipaySystemOauthTokenRequest->setRefreshToken( $code );
            $AlipaySystemOauthTokenRequest->setGrantType ( "refresh_token" );
        }else{
            $AlipaySystemOauthTokenRequest->setCode( $code );
            $AlipaySystemOauthTokenRequest->setGrantType ( "authorization_code" );
        }

        $result = aopclient_request_execute ( $AlipaySystemOauthTokenRequest );
        if(isset($result->alipay_system_oauth_token_response)){
            $access_token = $result->alipay_system_oauth_token_response;
            $token_data = (array)$access_token;
            $token_data['create_time'] = NOW_TIME;
            session('alipay_oauth', $token_data);
            return $token_data;
        }else{
            return false;
        }
    }
    
    
}
