<?php
/**
* 	配置账号信息
*/
if(!defined('PAY_APPID') || !PAY_APPID){
    $config = M('WechatPayConfig')->where(array('name' => 'default'))->find();
    define("__config__appid__", !empty($config['appid']) ? $config['appid'] : '');
    define("__config__mchid__", !empty($config['mchid']) ? $config['mchid'] : '');
    define("__config__key__", !empty($config['key']) ? $config['key'] : '');
    define("__config__appsecret__", !empty($config['appsecret']) ? $config['appsecret'] : '');
    define("__config__sslcert__", dirname(dirname(__file__)).'/cert/default/apiclient_cert.pem');
    define("__config__sslkey__", dirname(dirname(__file__)).'/cert/default/apiclient_key.pem');
}else{
    define("__config__appid__", PAY_APPID);
    define("__config__mchid__", PAY_MCHID);
    define("__config__key__", PAY_KEY);
    define("__config__appsecret__", PAY_APPSECRET);
    define("__config__sslcert__", defined('PAY_SSLCERT') ? PAY_SSLCERT : '');
    define("__config__sslkey__", defined('PAY_SSLKEY') ? PAY_SSLKEY : '');
}
class WxPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * 
	 * 微信公众号信息配置
	 * APPID：绑定支付的APPID（必须配置）
	 * MCHID：商户号（必须配置）
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置）
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置）
	 * @var string
	 */
//	const APPID = 'wxa4d0ba3ff6d12e51';
//	const MCHID = '1230809802';
//	const KEY = 'bCquoBwtoEaskaIfKGAlPIhyCC8BMfwq';
//	const APPSECRET = '';
	const APPID = __config__appid__;
	const MCHID = __config__mchid__;
	const KEY = __config__key__;
	const APPSECRET = __config__appsecret__;
	
	//=======【证书路径设置】=====================================
	/**
	 * 
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要）
	 * @var path
	 */
	const SSLCERT_PATH = __config__sslcert__;
	const SSLKEY_PATH = __config__sslkey__;
	
	//=======【curl代理设置】===================================
	/**
	 * 
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认0.0.0.0和0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	const CURL_PROXY_HOST = "0.0.0.0";
	const CURL_PROXY_PORT = 0;
	
	//=======【上报信息配置】===================================
	/**
	 * 
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	const REPORT_LEVENL = 0;
}
