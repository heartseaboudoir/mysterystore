<?php
namespace Addons\Alisms\Lib;



use Aliyun\DySDKLite\SignatureHelper;

class SmsLibNew{
    
    public function __construct() {
        /*
        $this->accessKey = 'LTAIbUHzw10brUhy';
        $this->accessSecret = 'lWRcFbaroWWgSRquAsk7rJrd1gI13m';
        $this->sign = '神秘商店';
        */
        
        $this->accessKeyId = "LTAIilEI79fU3v95";
        $this->accessKeySecret = "LfFNS4oroh15uHY9oLP7R7eDzr5VkK";  
        $this->SignName = '神秘商店';        
        
    }
    
    public function send($mobile, $param, $tpl, $sign = '', $db = ''){
        empty($sign) && $sign = $this->SignName;
        $now_time = time();
        
        
        //echo $param;exit;

        // 发送短信
        $response = $this->sendSms($mobile, $param, $tpl, $sign);
        xydebug($response, 'check_code.txt');
        /*
        $param_arr = json_decode($param, true);
        if (!empty($param_arr['code'])) {
            $response2 = $this->singleCallByTts($mobile, array(
                'product' => '神秘商店',
                'code' => $param_arr['code'],
            ), 'TTS_138525039');
            xydebug($response, 'check_code.txt');
            xydebug($response2, 'check_code.txt');
        }
        */
        
        /*
        print_r($response);
        
        echo '------------';
        exit;
        */
        if($response->Code == 'OK'){
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
            'resp_status' => $response->Code,
            'resp' => json_encode((array)$response),
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
    
    
    
        
        
    /**
     * 发送短信"SMS_39370204"
     */
    function sendSms($mobile, $param, $tpl) {
        include_once dirname(__FILE__).'/aliyun-dysms-php-sdk-lite/SignatureHelper.php';
        
        
        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $mobile;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $this->SignName;

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $tpl;

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = $param;

        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = date('YmdHis') . mt_rand(100000, 999999);

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        //$params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            //$params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
            $params["TemplateParam"] = json_encode($params["TemplateParam"]);
        }
        
        
        
        //$params["TemplateParam"] = '{"code":"123456","product":"阿里通信"}';

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );

        return $content;
    }




    public function send_voice($mobile, $param, $tpl, $sign = '', $db = ''){
        empty($sign) && $sign = $this->SignName;
        $now_time = time();
        
        
        //echo $param;exit;

        // 发送短信

        $response = $this->singleCallByTts($mobile, $param, 'TTS_138525039');
        xydebug($response, 'check_code.txt');
        // xydebug($response2, 'check_code.txt');

        
        /*
        print_r($response);
        
        echo '------------';
        exit;
        */
        if($response->Code == 'OK'){
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
            'resp_status' => $response->Code,
            'resp' => json_encode((array)$response),
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
        
        
        
    
    
        
    /**
     * 文本转语音外呼
     */
    function singleCallByTts($mobile, $param, $tpl) {
        
        //include_once dirname(__FILE__).'/aliyun-dyvms-php-sdk-lite/SignatureHelper.php';
        include_once dirname(__FILE__).'/aliyun-dysms-php-sdk-lite/SignatureHelper.php';

        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息        
        $accessKeyId = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;        
        

        // fixme 必填: 被叫显号
        $params["CalledShowNumber"] = "076023701606";

        // fixme 必填: 被叫显号
        $params["CalledNumber"] = $mobile;

        // fixme 必填: Tts模板Code
        $params["TtsCode"] = $tpl;

        // fixme 选填: Tts模板中的变量替换JSON,假如Tts模板中存在变量，则此处必填
        $params["TtsParam"] = $param;

        // fixme 选填: 音量
        $params["Volume"] = 100;

        // fixme 选填: 播放次数
        $params["PlayTimes"] = 3;

        // fixme 选填: 音量, 取值范围 0~200
        $params["Volume"] = 100;

        // fixme 选填: 预留给调用方使用的ID, 最终会通过在回执消息中将此ID带回给调用方
        $params["OutId"] = $mobile . '_' . date('YmdHis');

        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***

        if(!empty($params["TtsParam"]) && is_array($params["TtsParam"])) {
            //$params["TtsParam"] = json_encode($params["TtsParam"], JSON_UNESCAPED_UNICODE);
            $params["TtsParam"] = json_encode($params["TtsParam"]);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dyvmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SingleCallByTts",
                "Version" => "2017-05-25",
            ))
            // fixme 选填: 启用https
            // ,true
        );

        return $content;
    }    
        
    
    
    
    
}