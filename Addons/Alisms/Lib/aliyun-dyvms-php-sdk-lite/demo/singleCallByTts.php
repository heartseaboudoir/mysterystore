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
 * 文本转语音外呼
 */
function singleCallByTts() {

    $params = array ();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = "your access key id";
    $accessKeySecret = "your access key secret";

    // fixme 必填: 被叫显号
    $params["CalledShowNumber"] = "4001112222";

    // fixme 必填: 被叫显号
    $params["CalledNumber"] = "13700000000";

    // fixme 必填: Tts模板Code
    $params["TtsCode"] = "TTS_10001";

    // fixme 选填: Tts模板中的变量替换JSON,假如Tts模板中存在变量，则此处必填
    $params["TtsParam"] = array("AckNum" => "123456");

    // fixme 选填: 音量
    $params["Volume"] = 100;

    // fixme 选填: 播放次数
    $params["PlayTimes"] = 3;

    // fixme 选填: 音量, 取值范围 0~200
    $params["Volume"] = 100;

    // fixme 选填: 预留给调用方使用的ID, 最终会通过在回执消息中将此ID带回给调用方
    $params["OutId"] = "yourOutId";

    // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***

    if(!empty($params["TtsParam"]) && is_array($params["TtsParam"])) {
        $params["TtsParam"] = json_encode($params["TtsParam"], JSON_UNESCAPED_UNICODE);
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

ini_set("display_errors", "on"); // 显示错误提示，仅用于测试时排查问题
// error_reporting(E_ALL); // 显示所有错误提示，仅用于测试时排查问题
set_time_limit(0); // 防止脚本超时，仅用于测试使用，生产环境请按实际情况设置
header("Content-Type: text/plain; charset=utf-8"); // 输出为utf-8的文本格式，仅用于测试

// 验证文本转语音外呼(SingleCallByTts)接口
print_r(singleCallByTts());