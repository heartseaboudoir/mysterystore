<?php
/*
 * 此文件用于验证语音服务API接口，供开发时参考
 * 执行验证前请确保文件为utf-8编码，并替换相应参数为您自己的信息，并取消相关调用的注释
 * 建议验证前先执行Test.php验证PHP环境
 *
 * 2017/11/30
 */

namespace Aliyun\DySDKLite\Vms\Demo;

require_once "../SignatureHelper.php";

use Aliyun\DySDKLite\SignatureHelper;

// todo 接口定义，请先替换相应参数为您自己的信息

/**
 * 交互式语音应答
 */
function ivrCall() {

    $params = array();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = "your access key id";
    $accessKeySecret = "your access key secret";

    // fixme 必填: 被叫显号
    $params["CalledShowNumber"] = "4001112222";

    // fixme 必填: 被叫显号
    $params["CalledNumber"] = "13700000000";

    // fixme 必填: 呼叫开始时播放的提示音-语音文件Code名称或者Tts模板Code
    $params["StartCode"] = "TTS_10001";

    // fixme 可选: Tts模板中的变量替换JSON,假如Tts模板中存在变量，则此处必填
    $params["StartTtsParams"] = array("AckNum" => "123456");

    // fixme 必填: 按键与语音文件ID或tts模板的映射关系
    $menuKeyMaps = array (
        array ( // 按下1键, 播放语音
            "Key" => "1",
            "Code" => "9a9d7222-670f-40b0-a3af.wav"
        ),
        array ( // 按下2键, 播放语音
            "Key" => "2",
            "Code" => "44e3e577-3d3a-418f-932c.wav"
        ),
        array ( // 按下3键, 播放TTS语音
            "Key" => "3",
            "Code" => "TTS_71390000",
            "TtsParams" => array("product"=>"aliyun", "code"=>"123")
        ),
    );

    // fixme 可选: 重复播放次数
    $params["PlayTimes"] = 3;

    // fixme 可选: 等待用户按键超时时间，单位毫秒
    $params["Timeout"] = 3000;

    // fixme 可选: 播放结束时播放的结束提示音,支持语音文件和Tts模板2种方式,但是类型需要与StartCode一致，即前者为Tts类型的，后者也需要是Tts类型的
    $params["ByeCode"] = "TTS_71400007";

    // fixme 可选: Tts模板变量替换JSON,当ByeCode为Tts时且Tts模板中带变量的情况下此参数必填
    $params["ByeTtsParams"] = array("product" => "aliyun", "code" => "123");

    // fixme 可选: 预留给调用方使用的ID, 最终会通过在回执消息中将此ID带回给调用方
    $params["OutId"] = "yourOutId";

    // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***

    if(!empty($params["StartTtsParams"]) && is_array($params["StartTtsParams"])) {
        $params["StartTtsParams"] = json_encode($params["StartTtsParams"], JSON_UNESCAPED_UNICODE);
    }

    if(!empty($params["ByeTtsParams"]) && is_array($params["ByeTtsParams"])) {
        $params["ByeTtsParams"] = json_encode($params["ByeTtsParams"], JSON_UNESCAPED_UNICODE);
    }

    $i = 0;
    foreach($menuKeyMaps as $menuKeyMap) {
        ++$i;
        $params["MenuKeyMap." . $i . ".Key"] = $menuKeyMap["Key"];
        $params["MenuKeyMap." . $i . ".Code"] = $menuKeyMap["Code"];
        if(!empty($menuKeyMap["TtsParams"]) && is_array($menuKeyMap["TtsParams"])) {
            $params["MenuKeyMap." . $i . ".TtsParams"] = json_encode($menuKeyMap["TtsParams"], JSON_UNESCAPED_UNICODE);
        }
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
            "Action" => "IvrCall",
            "Version" => "2017-05-25",
        ))
        // fixme 选填: 启用https
        // ,true
    );

    return $content;
}

ini_set("display_errors", "on"); // 显示错误提示，仅用于测试时排查问题
// error_reporting(E_ALL); // 显示所有错误提示，仅用于测试时排查问题
set_time_limit(0); // 防止脚本超时，仅用于测试使用，生产环境请按实际情况设置
header("Content-Type: text/plain; charset=utf-8"); // 输出为utf-8的文本格式，仅用于测试

// 验证交互式语音应答(IvrCall)接口
print_r(ivrCall());