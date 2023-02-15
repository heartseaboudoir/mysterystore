<?php
if(!defined(PAY_APP_ID)){
    $base_config = M('AlipayConfig')->where(array('name' => 'default'))->find();
    define('PAY_APP_ID', $base_config['app_id']);
    !defined('PAY_PARTNER') && define('PAY_PARTNER', $base_config['partner']);
}
$config = array (
    'alipay_public_key_file' => dirname ( __FILE__ ) . "/key/alipay_rsa_public_key.pem",
    'alipay_public_key_file2' => dirname ( __FILE__ ) . "/key/alipay_rsa_public_key2.pem",
    'merchant_private_key_file' => dirname ( __FILE__ ) . "/key/rsa_private_key.pem",
    'merchant_public_key_file' => dirname ( __FILE__ ) . "/key/rsa_public_key.pem",		
    'charset' => "utf-8",
    'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    'app_id' => PAY_APP_ID, 
    'partner' => (defined('PAY_PARTNER') && PAY_PARTNER) ? PAY_PARTNER : "", 
);