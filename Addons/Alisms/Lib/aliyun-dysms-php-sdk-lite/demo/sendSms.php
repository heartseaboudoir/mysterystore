<?php
/*
 * 此文件用于验证短信服务API接口，供开发时参考
 * 执行验证前请确保文件为utf-8编码，并替换相应参数为您自己的信息，并取消相关调用的注释
 * 建议验证前先执行Test.php验证PHP环境
 *
 * 2017/11/30
 */

namespace Aliyun\DySDKLite\Sms;

require_once "../SignatureHelper.php";

use Aliyun\DySDKLite\SignatureHelper;


function send_sms($mobile, $tpl, $param = array()){
    if(!$mobile || !preg_match('/^1\d{10}$/', $mobile, $match)){
        return array('status' => 0, 'info' => '手机号码错误');
    }
    if(!$tpl){
        return array('status' => 0, 'info' => '短信模板未知');
    }
    $param = $param ? json_encode($param) : '';
    $sign = '神秘商店';
    $Sms = new Addons\Alisms\Lib\SmsLib();
    $result = $Sms->send($mobile, $param, $tpl, $sign);
    if($result['status'] == 1){
        return array('status' => 1);
    }else{
        return array('status' => 0, 'info' => $result['msg']);
    }
}

function send_sms2($mobile, $tpl, $param = array()) {
    if(!$mobile || !preg_match('/^1\d{10}$/', $mobile, $match)){
        return array('status' => 0, 'info' => '手机号码错误');
    }
    if(!$tpl){
        return array('status' => 0, 'info' => '短信模板未知');
    }

    $params = array ();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = "LTAIilEI79fU3v95";
    $accessKeySecret = "LfFNS4oroh15uHY9oLP7R7eDzr5VkK";

    // fixme 必填: 短信接收号码
    $params["PhoneNumbers"] = "13751730010";

    // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
    $params["SignName"] = "神秘商店";

    // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
    $params["TemplateCode"] = "SMS_39370204";

    // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
    $params['TemplateParam'] = Array (
        "code" => "789546",
        "product" => "神秘商店"
    );

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

/**
 * 发送短信
 */
function sendSms() {

    $params = array ();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = "LTAIilEI79fU3v95";
    $accessKeySecret = "LfFNS4oroh15uHY9oLP7R7eDzr5VkK";

    // fixme 必填: 短信接收号码
    $params["PhoneNumbers"] = "13751730010";

    // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
    $params["SignName"] = "神秘商店";

    // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
    $params["TemplateCode"] = "SMS_39370204";

    // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
    $params['TemplateParam'] = Array (
        "code" => "789546",
        "product" => "神秘商店"
    );

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

ini_set("display_errors", "on"); // 显示错误提示，仅用于测试时排查问题
set_time_limit(0); // 防止脚本超时，仅用于测试使用，生产环境请按实际情况设置
header("Content-Type: text/plain; charset=utf-8"); // 输出为utf-8的文本格式，仅用于测试

// 验证发送短信(SendSms)接口
print_r(sendSms());