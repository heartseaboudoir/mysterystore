<?php

date_default_timezone_set('Asia/Shanghai');

$data = request('/Order/assign_act_coupon');

print_r($data);

function request($url, $data = array())
{
    
    $domain = 'https://v.imzhaike.com/Apiv2';
    
    
    $url = $domain . $url;
    
    $device = 0;
    $version = '';
    $key = '$ZaiKe$ByApi$';
    
    $url = trim(strtolower($url));
    $utoken = md5($url . $key . date('Y-m-d'));
    
    
    
    
    //$data = array('content' => $content);
    //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
    
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'UTOKEN: ' . $utoken,
            //'Content-Type: application/json',
            //'Content-Length: ' . strlen($json),
        )
    );
    $result = curl_exec($ch);
    
    $result = json_decode($result, true);
    return $result;
} 