<?php
namespace Addons\Alipay\Controller;

use Think\Controller;

class AlipayclassController extends Controller{
    public function __construct() {
        parent::__construct();
        !defined('SKD_PATH') && define('SKD_PATH', dirname(dirname(__FILE__)).'/sdk/');
    }
    
    public function geteway(){
        $ali_config = M('AlipayServerConfig')->find();
        if($ali_config){
            define('PAY_APP_ID', $ali_config['appid']);
        }
        require_once SKD_PATH.'function.inc.php';
        require_once SKD_PATH.'HttpRequst.php';
        require_once SKD_PATH.'config.php';
        require_once SKD_PATH.'AlipaySign.php';
        
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
        $sign_verify = $as->rsaCheckV2 ( $_REQUEST, $config['alipay_public_key_file'] );
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
            require_once SKD_PATH.'Message.php';
            $msg = new \Message ( $biz_content );
        }
    }
    
    private function verifygw($is_sign_success) {
            $biz_content = \HttpRequest::getRequest ( "biz_content" );
            $as = new \AlipaySign ();
            $xml = simplexml_load_string ( $biz_content );
            // print_r($xml);
            $EventType = ( string ) $xml->EventType;
            // echo $EventType;
            if ($EventType == "verifygw") {
                global $config;
                require SKD_PATH.'config.php';
                // print_r ( $config );
                if ($is_sign_success) {
                        $response_xml = "<success>true</success><biz_content>" . $as->getPublicKeyStr ( $config ['merchant_public_key_file'] ) . "</biz_content>";
                } else { // echo $response_xml;
                        $response_xml = "<success>false</success><error_code>VERIFY_FAILED</error_code><biz_content>" . $as->getPublicKeyStr ( $config ['merchant_public_key_file'] ) . "</biz_content>";
                }
                $return_xml = $as->sign_response ( $response_xml, $config ['charset'], $config ['merchant_private_key_file'] );
                echo $return_xml;
                exit ();
            }
    }
}