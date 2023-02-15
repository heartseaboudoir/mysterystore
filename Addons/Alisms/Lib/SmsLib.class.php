<?php
namespace Addons\Alisms\Lib;

class SmsLib{
    
    public function __construct() {
        $this->accessKey = 'LTAIbUHzw10brUhy';
        $this->accessSecret = 'lWRcFbaroWWgSRquAsk7rJrd1gI13m';
        $this->sign = '神秘商店';
    }
    
    public function send($mobile, $param, $tpl, $sign = '', $db = ''){
        !$sign && $sign = $this->sign;
        $now_time = time();
        include_once dirname(__FILE__).'/aliyun-php-sdk-sms/aliyun-php-sdk-core/Config.php';
        
        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $this->accessKey, $this->accessSecret);
        $client = new \DefaultAcsClient($iClientProfile);    
        $request = new \Sms\Request\V20160927\SingleSendSmsRequest();
        $request->setSignName($sign);/*签名名称*/
        $request->setTemplateCode($tpl);/*模板code*/
        $request->setRecNum($mobile);/*目标手机号*/
        $request->setParamString($param);/*模板变量，数字一定要转换为字符串*/
       
        $response = $client->getAcsResponse($request);
        if($response['status'] == 1){
            $status_code = 1;
            $result = 1;
            $msg = '';
        }else{
            $status_code = 2;
            $result = 0;
            $msg = '发送失败';
        }
        $result_data = array(
            'mobile' => $mobile,
            'param' => $param,
            'tpl' => $tpl,
            'sign' => $sign,
            'status' => $status_code,
            'resp_status' => $response['http_status'],
            'resp' => json_encode((array)$response['resp']),
            'create_time' => $now_time,
            'update_time' => $now_time,
        );
        if(function_exists('M')){
            $LogModel = M('AlismsLog');
            $LogModel->add($result_data);
        }elseif($db){
            $keys = '`'.implode('`,`', array_keys($result_data)).'`';
            $params = "'".implode("','", $result_data)."'";
            $db->query("INSERT INTO hii_alisms_log ({$keys}) value({$params})");
        }
        return array('status' => $result, 'msg' => $msg);
    }
}