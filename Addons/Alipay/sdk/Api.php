<?php

class Api {

    public $sdk_path;
    protected $c, $config;


    public function __construct() {
        $this->sdk_path = dirname(__FILE__).'/';
    }
    
    protected function init(){
        require_once $this->sdk_path."aop/AopClient.php";
        require_once $this->sdk_path.'AopSdk.php';
        require_once $this->sdk_path.'function.inc.php';
        require $this->sdk_path.'config.php';
        $this->config = $config;
        $this->c = new \AopClient();
        $this->c->gatewayUrl = $this->config['gatewayUrl'];
        $this->c->appId = '2016052901456954';
        //$this->c->appId = $config['app_id'];
        $this->c->rsaPrivateKeyFilePath = $this->config['merchant_private_key_file'];
        $this->c->alipayPublicKey=$this->config['alipay_public_key_file2'];
    }
}