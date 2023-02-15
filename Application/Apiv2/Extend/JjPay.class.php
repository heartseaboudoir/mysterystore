<?php
/*
$jjpay = new Jjpay();



$data = $jjpay->pay(array(
    'money' => 1, 
    'order_sn' => date('YmdHis') . mt_rand(10000, 99999),
    'type' => 'alipay',//alipay,wxpay
));

print_r($data);
*/
namespace Apiv2\Extend;

class JjPay {
    
    
    // 请求配置
    
    public $appID = 'A00016';
    public $merchantNo = '00027';
    public $mebid = 11111;
    public $signData = '666666';
    public $purl = 'http://120.25.228.148/platenopay';
    
    
    public function __construct()
    {
        $this->getParams();
        
        // include(__DIR__ . '/FlyCurl.class.php');
    }
    
    private function getParams()
    {
        
        $isTest = $this->isTest();
        // test
        if ($isTest) {
            $this->appID = 'A00016';
            $this->merchantNo = '00027';
            $this->mebid = 11111;
            $this->signData = '666666';
            $this->purl = 'http://120.25.228.148/platenopay';
        // line
        } else {
            $this->appID = 'A00016';
            $this->merchantNo = '00027';
            $this->mebid = 11111;
            $this->signData = '7a2nGZ6Njo';
            $this->purl = 'https://pay.plateno.com/PayCashierDesk';            
        }
    }
    
    private function isTest()
    {
        //echo $_SERVER["HTTP_HOST"];
        if ($_SERVER["HTTP_HOST"] != 'v.imzhaike.com') {
            return true;
        } else {
            return false;
        }        
    }
    
    
    public function pay($paydata)
    {
        
        
        
        // 支付金额
        $money = (int)$paydata['money'];
        
        // 订单号
        $order_sn = $paydata['order_sn'];
        
        // 支付类型
        $type = $paydata['type'];
        
        // 回调URL
        $notify_url = $paydata['notify_url'];
        
        if ($money <= 0 || empty($order_sn) || !in_array($type, array('wxpay', 'alipay'))) {
            return array(false, 'pay type error');
        }
        
        if ($type == 'wxpay') {
            $gateId = 1000;
            $channelId = 36;
        } else {
            $gateId = 1020;
            $channelId = 43;
        }
        
        
        
    
        //$money = 1;
        //$order_sn = 'test123456789';// . date('YmdHis') . mt_rand(10000, 99999);
    
        $purl = $this->purl;
        $url = $purl . '/platenopay/pay.html';
        $data = $this->jj($url, array(
            'appID' => $this->appID,
            'merchantNo' => $this->merchantNo,
            'merchantOrderNo' => $order_sn,
            'amount' => $money,
            'currencyDepositAmount' => 0,
            'mebid' => $this->mebid,
            'paymentType' => 2,
            'gateId' => $gateId,
            'channelId' => $channelId,
            'notifyUrl' => $notify_url,
            'subject' => '神秘商店订单',
            'signData' => $this->signData,
            
        ));
        
        if (!empty($data['data']['referenceId'])) {
            $qrcode = $data['data']['referenceId'];
            return array(true, $qrcode, $data['data']);
        } else {
            return array(false, 'response error', $data);
        }
        
        
        
    }
    
    
    
    
    
    
    
    private function jj($url, $data)
    {
        
        //$purl = 'http://10.237.151.146:8086/platenopay';
        //$purl = 'http://120.25.228.148/platenopay';
        //$url = $purl . $url;
        
        //$url = 'http://yuan37.lo/test.php';
        
        //echo $url;exit;
        ksort($data);
        
        //$data_stm = $data['subject'];
        
        $data['subject'] = urlencode($data['subject']);
        $data_json =urldecode(json_encode($data));
    
    
        //print_r($data_json);exit;
    
        //$data_json = json_encode($data);
        $data_json = str_replace("\\/", "/", $data_json);        
        
        
        $signData = md5($data_json);
        $signData = strtoupper($signData);
        
        $data['signData'] = $signData;
        
        //$data['subject'] = urlencode($data['subject']);
        $data_json2 =urldecode(json_encode($data));
        
        
        //$data_json2 = json_encode($data);
        $data_json2 = str_replace("\\/", "/", $data_json2); 
        
        // 请求
        $http = new FlyCurl();
        $result = $http->request(array(
            'method' => 'POST',
            'url' => $url,
            'row' => array(
                //'UTOKEN' => $utoken,
                //'pay_apigwkey' => '7fcf5c22e8dc887eb2ffef8f81f69236',
                'Content-Type' => 'application/json',
            ),
            //'reqdata' =>  $data,
            'reqdata' => $data_json2,
            //'reqdata' => '{"amount":1,"appID":"A00016","currencyDepositAmount":0,"gateId":1000,"mebid":11111,"merchantNo":"00027","merchantOrderNo":"test2017120412381187898","paymentType":2,"returnUrl":"http://test.imzhaike.com/Admin/test.html","signData":"5329d4e59926f2d8135f92c938ad5546","subject":"test product"}',            
        ))->getContent();
        
        
        //print_r($data_json2);
        
        // 解析数据
        $data = json_decode($result, true);
    

        
        return array(
            'content' => $result,
            'data' => $data,
        );          
    }     
    
}
