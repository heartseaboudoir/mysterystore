<?php
namespace Api\Controller;
use Think\Controller;

class PublicController extends Controller {
    
    public function wx_pay_notify(){
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $_arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        // 设置微信支付配置
        if($_arr['trade_type'] == 'APP'){
            A('Addons://WechatPay/WechatPayclass')->set_config('app');
        }
        $result = A('Addons://WechatPay/WechatPayclass')->notify_action($msg);
        
        if($result['status'] == 0){
            exit;
        }
        $data = $result['data'];
        
        $attach = json_decode($data['attach'], true);
        if(!$attach['order_sn']){
            exit;
        }
        $order_sn = $attach['order_sn'];
        $info = M('WechatPayLog')->where(array('out_trade_no' => $data['out_trade_no'], 'order_sn' => $order_sn))->find();
        if(!$info || $info['status'] != 1){
            exit;
        }
        
        $idata = array(
            'transaction_id' => $data['transaction_id'],
            'return_data' => json_encode($data),
            'update_time' => time(),
            'status' => 2,
        );
        $result = M('WechatPayLog')->where(array('out_trade_no' => $data['out_trade_no'], 'status' => 1))->save($idata);
        if(!$result){
            exit;
        }
        
        $new_data = array_merge($info, $idata);
        unset($new_data['id']);
        if(M('WechatPayRecord')->add($new_data)){
            $order = D('Addons://Order/Order')->set_pay($order_sn, 1, $data['transaction_id'], $data['attach']);
            $WechatUser = M('WechatUser');
            $user_data = $WechatUser->where(array('appid' => $data['appid'], 'openid' => $data['openid']))->find();
            if(!$user_data || !$user_data['nickname']){
                $wechat = A('Addons://Wechat/Wechatclass');
                ($order['type'] == 'store' || $order['type'] == 'online') && $_GET['ukey'] = $order['store_id'];
                $user = $wechat->user($data['openid']);
                if($user){
                    $wu_data = array(
                        'appid' => $data['appid'], 
                        'openid' => $data['openid'],
                        'nickname' => $user['nickname'],
                        'headimgurl' => $user['headimgurl'],
                        'data' => json_encode($user)
                    );
                    if($user_data){
                        $WechatUser->where(array('appid' => $data['appid'], 'openid' => $data['openid']))->save($wu_data);
                    }else{
                        $WechatUser->add($wu_data);
                    }
                }
            }
        }
        echo 'success';
        exit;
    }
    
    public function ali_pay_notify(){
        $result = A('Addons://Alipay/F2fpayclass')->verifyNotify();
        if(!$result){
            exit;
        }
        $data = $_POST;
        if($data['trade_status'] == 'TRADE_SUCCESS'){
            $body = json_decode($data['body'], true);
            if(!$body['order_sn']){
                exit;
            }
            $order_sn = $body['order_sn'];
            $info = M('AlipayLog')->where(array('out_trade_no' => $data['out_trade_no'], 'order_sn' => $order_sn))->find();
            if(!$info || $info['status'] != 1){
                exit;
            }
            
            
            
            // 查看订单状态及用户信息
            $order = M('order')->where(array(
                'order_sn' => $order_sn
            ))->find();
            

            // 订单不存在
            if (empty($order)) {
                echo 'fail';
                exit;

                // 订单已经完成
            } elseif ($order['pay_status'] == 2) {
                echo 'success';
                exit;
            }
            
            $money = $order['pay_money'];

            
            $price = empty($data['total_fee']) ? 0 : $data['total_fee'];
            
            // 订单金额比对回调金额
            if ($money > $price) {
                echo 'fail';
                exit;            
            }            
            
            
            
            
            
            
            $idata = array(
                'trade_no' => $data['trade_no'],
                'return_data' => json_encode($data),
                'update_time' => time(),
                'status' => 2,
            );
            $result = M('AlipayLog')->where(array('out_trade_no' => $data['out_trade_no'], 'status' => 1))->save($idata);
            if(!$result){
                exit;
            }
            $new_data = array_merge($info, $idata);
            unset($new_data['id']);
            if(M('AlipayRecord')->add($new_data)){
                D('Addons://Order/Order')->set_pay($order_sn, 2, $data['trade_no'], $data['body']);
            }
        }
        echo 'success';
        exit;
    }
    
    public function kd_notify(){
        if(IS_POST){
            $req_data = $_POST;
            $Api = new \Addons\Order\Lib\Express\KdApi();
            $request_data = json_decode($req_data['RequestData'], true);
            if(!$Api->check_crypt($req_data['DataSign'], $req_data['RequestData'])){
                exit;
            }
            if($req_data['RequestType'] == '101'){
                foreach($request_data['Data'] as $v){
                    $item = array();
                    foreach($v['Traces'] as $t){
                        $item[] = array(
                            'time' => $t['AcceptTime'],
                            'text' => $t['AcceptStation'],
                        );
                    }
                    M('OrderExpressLog')->where(array('no' => $v['LogisticCode']))->save(array('data' => json_encode($item), 'update_time' => NOW_TIME));
                }
                $datas = array(
                    'EBusinessID' => EBusinessID,
                    'UpdateTime' => date('Y-m-d H:i:s', NOW_TIME),
                    'Success' => true,
                    'Reason' => '',
                );
                echo json_encode($datas);
            }
        }
    }
}
