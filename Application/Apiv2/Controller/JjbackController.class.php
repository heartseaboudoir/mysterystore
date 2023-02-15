<?php
namespace Apiv2\Controller;
use Think\Controller;

class JjbackController extends Controller {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'jj.txt');
    }
    
    public function notify(){
        // 获取请求
        $jsons = file_get_contents('php://input');

        $this->debugs($jsons);
        
        // 解析请求
        $data = json_decode($jsons, true);

        // 数据不合法
        if (empty($data) || empty($data['merchantOrderNo']) || $data['notifyType'] != 1 ) {
            echo 'fail';exit;            
        }
        
        // 订单号
        $order_sn = $data['merchantOrderNo'];
        
        // 支付方式
        $pay_type = 3;
        if ($data['payChannel'] == 1 || $data['payChannel'] == 36) {
            $pay_type = 3;
        } elseif ($data['payChannel'] == 20 || $data['payChannel'] == 43) {
            $pay_type = 4;
        }
        
        M('order')->where(array('order_sn' => $order_sn))->save(array(
            'pay_app' => 'jjpay',
        ));
        

        // 调整支付状态
        D('Addons://Order/Order')->set_pay($order_sn, $pay_type, $data['platenopayFlowNo'], $jsons);
        
        echo 'success';
        exit;
    }

}


/*
微信
{"notifyType":1,"merchantNo":"00027","merchantOrderNo":"test2017120511363877530","signData":"BAAF694BDE7EFB56A1CB946911160932","platenopayFlowNo":"512444918434035315","gatewayAmount":1,"currencyDepositAmount":0,"amount":1,"payChannel":1,"ext1":"2","ext2":""}

支付宝
{"notifyType":1,"merchantNo":"00027","merchantOrderNo":"test2017120511534267576","signData":"69312E24D6BDE6ED176E70216D9CB9B3","platenopayFlowNo":"512445942195641415","gatewayAmount":1,"currencyDepositAmount":0,"amount":1,"payChannel":20,"ext1":"2","ext2":""}


{
    "notifyType": 1, //1：表示支付完成通知；2：表示退款完成通知
    "merchantNo": "00027",
    "merchantOrderNo": "test2017120511363877530",
    "signData": "BAAF694BDE7EFB56A1CB946911160932",
    "platenopayFlowNo": "512444918434035315", //示收银台交易流水号
    "gatewayAmount": 1, // 网关金额
    "currencyDepositAmount": 0, // 储值金额
    "amount": 1, //收银台金额
    "payChannel": 1, // 支付网关渠道 ID
    "ext1": "2", // 支付方式：1-支付网关+ 个人储值；2-只用支付网关支付；3-只用个人储值支付；4-企业储值
    "ext2": "" // 保留的扩展字段
}
*/