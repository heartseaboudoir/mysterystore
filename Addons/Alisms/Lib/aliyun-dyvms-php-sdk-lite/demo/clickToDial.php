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
 * 点击拨号
 */
function clickToDial() {

    $params = array();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = "your access key id";
    $accessKeySecret = "your access key secret";

    // fixme 必填: 主叫显号, 可在语音控制台中找到所购买的显号
    $params["CallerShowNumber"] = "05344757036";

    // fixme 必填: 主叫号码
    $params["CallerNumber"] = "1800000000";

    // fixme 必填: 被叫显号, 可在语音控制台中找到所购买的显号
    $params["CalledShowNumber"] = "4001112222";

    // fixme 必填: 被叫号码
    $params["CalledNumber"] = "13700000000";


    // fixme 可选: 是否录音
    $params["RecordFlag"] = true;

    // fixme 可选: 是否开启实时ASR功能
    $params["AsrFlag"] = true;

    // fixme 可选: ASR模型ID
    $params["AsrModelId"] = '2070aca1eff146f9a7bc826f1c3d4d33';

    // fixme 可选: 预留给调用方使用的ID, 最终会通过在回执消息中将此ID带回给调用方（15个字符及以内）
    $params["OutId"] = "yourOutId";

    // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***

    // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
    $helper = new SignatureHelper();

    // 此处可能会抛出异常，注意catch
    $content = $helper->request(
        $accessKeyId,
        $accessKeySecret,
        "dyvmsapi.aliyuncs.com",
        array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "ClickToDial",
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

// 验证点击拨号(ClickToDial)接口
print_r(clickToDial());
