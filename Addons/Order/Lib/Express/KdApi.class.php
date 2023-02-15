<?php
namespace Addons\Order\Lib\Express;
/**
 *
 * 快递鸟订阅推送2.0接口
 *
 */
//电商ID
defined('EBusinessID') or define('EBusinessID', '1287391');
//电商加密私钥，快递鸟提供，注意保管，不要泄漏
defined('AppKey') or define('AppKey', '3c6aeff0-5d3f-4bf1-9e79-c3c745aa1f34');
//测试请求url
//defined('ReqURL') or define('ReqURL', 'http://testapi.kdniao.cc:8081/api/dist');
//正式请求url
defined('ReqURL') or define('ReqURL', 'http://api.kdniao.cc/api/dist');

class KdApi {
    /**
     * 物流信息订阅
     */
    public function traces_sub($shipperCode, $LogisticCode, $OrderCode, $Sender = array(), $Receiver = array(), $other = array()){
        $requestData = $other;
        $requestData['OrderCode'] = (string)$OrderCode;
        $requestData['shipperCode'] = (string)$shipperCode;
        $requestData['LogisticCode'] = (string)$LogisticCode;
        $Sender && $requestData['Sender'] = $Sender;
        $Receiver && $requestData['Receiver'] = $Receiver;
        $requestData['RequestType'] = '107';
        $requestData = json_encode($requestData);
        
        $datas = array(
            'EBusinessID' => EBusinessID,
            'RequestType' => '1008',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData);
        $result = $this->sendPost(ReqURL, $datas);
        return $result;
    }
    /**
     *  post提交数据 
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据 
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容   
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data) {
        return urlencode(base64_encode(md5($data . AppKey)));
    }
    
    public function check_crypt($dataSign, $req_data){
        is_array($req_data) && $req_data = json_encode($req_data);
        return urlencode($dataSign) == $this->encrypt($req_data) ? 1 : 0;
    }

}
