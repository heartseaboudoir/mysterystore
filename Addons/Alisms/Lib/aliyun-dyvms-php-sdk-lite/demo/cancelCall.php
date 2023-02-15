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
 * 取消呼叫
 */
function cancelCall() {

    $params = array();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = "your access key id";
    $accessKeySecret = "your access key secret";

    // fixme 必填: 从上次呼叫调用的返回值中获取的CallId
    $params["CallId"] = "113853585007^100675005007";

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
            "Action" => "CancelCall",
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

// 验证取消呼叫(CancelCall)接口
print_r(cancelCall());
