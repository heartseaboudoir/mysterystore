<?php
namespace Addons\WechatPay\Controller;

use Think\Controller;

class WechatPayclassController extends Controller{
    public function set_config($config, $type = 'name'){
        if(!$config) return false;
        if(is_array($config)){
            define('PAY_MCHID', $config['mchid']);
            define('PAY_KEY', $config['key']);
            define('PAY_APPID', $config['appid']);
            define('PAY_APPSECRET', $config['appsecret']);
        
        }else{
            $where = array();
            if($type == 'appid'){
                $where['appid'] = $config;
            }else{
                $where['name'] = $config;
            }
            $config = M('WechatPayConfig')->where($where)->find();
            if($config){
                define('PAY_MCHID', $config['mchid']);
                define('PAY_KEY', $config['key']);
                define('PAY_APPID', $config['appid']);
                define('PAY_SSLCERT', dirname(__file__).'/cert/'.$config['name'].'/apiclient_cert.pem');
                define('PAY_SSLKEY', dirname(__file__).'/cert/'.$config['name'].'/apiclient_key.pem');
            }
        }
    }
    /**
     * 
     * @param type $data [body:标题 attach:附加数据 sn:订单号 fee:订单金额]
     * @param type $url
     * @return type
     */
    public function unifiedorder($data, $url = ''){
        require_once "lib/WxPay.Api.php";
        
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($data['body']);
        $input->SetAttach($data['attach']);
        $out_trade_no = uniqid('a').date("ymdHis").  mt_rand(10, 99);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($data['fee']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        
        $input->SetNotify_url($url ? $url : addons_url('WechatPay://WechatPayclass:/notify'));
        $input->SetProduct_id($data['sn']);
        switch($data['trade_type']){
            case 'JSAPI':
                $input->SetTrade_type("JSAPI");
                break;
            case 'APP':
                $input->SetTrade_type("APP");
                break;
            case 'NATIVE':
                $input->SetTrade_type("NATIVE");
                break;
            default:
                break;
        }
        isset($data['openid']) && $input->SetOpenid($data['openid']);
        
        $order = \WxPayApi::unifiedOrder($input);
        if($order['return_code'] == 'FAIL'){
            return array('status' => 0, 'msg' => $order['return_msg']);
        }
        $idata = array(
            'appid' => $order['appid'],
            'out_trade_no' => $out_trade_no,
            'total_fee' => $data['fee'],
            'order_sn' => $data['sn'],
            'data' => json_encode($order),
            'type' => $data['trade_type'],
            'openid' => '',
            'create_time' => time(),
            'update_time' => time(),
        );
        M('WechatPayLog')->add($idata);
        if($data['trade_type'] == 'JSAPI'){
            require_once "unit/WxPay.JsApiPay.php";
            $tools = new \JsApiPay();
            $jsApiParameters = $tools->GetJsApiParameters($order);
            return array('status' => 1, 'data' => $jsApiParameters);
        }else{
            return array('status' => 1, 'data' => $order);
        }
    }
    
    //查询订单
    public function Queryorder($transaction_id){
        require_once "lib/WxPay.Api.php";
        require_once 'lib/WxPay.Notify.php';
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"){
                return array('status' => 1, 'data' => $result);
        }
        return array('status' => 0, 'data' => $result);
    }
    public function notify_action(&$msg){
        require_once "lib/WxPay.Api.php";
        require_once 'lib/WxPay.Notify.php';
        $result = \WxPayApi::notify(array($this,'notify'), $msg);
        
        if($result){
            return array('status' => 1, 'data' => $result);
        }else{
            return array('status' => 0);
        }
    }
    //重写回调处理函数
    public function notify($data, &$msg){
        require_once "lib/WxPay.Api.php";
        require_once 'lib/WxPay.Notify.php';
        
        $notfiyOutput = array();
        if(!array_key_exists("transaction_id", $data)){
                $msg = "输入参数不正确";
                return false;
        }
        
        //查询订单，判断订单真实性
        $result = $this->Queryorder($data["transaction_id"]);
        if(!$result['status']){
                $msg = "订单查询失败";
                return false;
        }
        if($result['data']['trade_state'] == 'SUCCESS'){
            return $data;
        }else{
            return false;
        }
    }
    
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign($value){
        require_once "lib/WxPay.Config.php";
        //签名步骤一：按字典序排序参数
        ksort($value);
        $string = "";
        foreach ($value as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $string = trim($buff, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".\WxPayConfig::KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
   }
    /**
     * 退款申请
     * @param type $data [order_sn:绑定的订单号 out_trade_no:内部支付订单号 transaction_id:微信支付单号 refund_fee:退款金额, times:退款申请批次]
     * @param type $url
     * @return type
     */
    public function refund($data){
        require_once "lib/WxPay.Api.php";
        
        $input = new \WxPayRefund();
        $where = array();
        isset($data['sn']) && $where['order_sn'] = $data['sn'];
        isset($data['out_trade_no']) && $where['out_trade_no'] = $data['out_trade_no'];
        isset($data['transaction_id']) && $where['transaction_id'] = $data['transaction_id'];
        if(empty($where)){
            $this->error('查询条件不足');
        }
        $where['status'] = 2;
        $pay_log = M('WechatPayRecord')->where($where)->find();
        if(!$pay_log){
            return array('status' => 0, 'msg' => '支付订单不存在');
        }
        $total_fee = $pay_log['total_fee'];
        if(intval($data['refund_fee'] + $pay_log['refund_fee']) > intval($total_fee)){
            
            $this->test_xy(array(
                'a' => $data['refund_fee'],
                'b' => $pay_log['refund_fee'],
                'ab' => $data['refund_fee'] + $pay_log['refund_fee'],
                'c' => $data['refund_fee'] + $pay_log['refund_fee'] - $total_fee,
            ));
            
            
            return array('status' => 0, 'msg' => '退款金额不得大于支付金额');
        }
        $where['status'] = array('in', '1,2');
        $LModel = M('WechatPayRefundLog');
        if($LModel->where($where)->find()){
            return array('status' => '已申请退款，请等待');
        }
        empty($data['times']) && $data['times'] = 1;
        $out_refund_no = md5($data['sn'].$data['times']);
        
        $idata = array(
            'appid' => $pay_log['appid'],
            'out_trade_no' => $pay_log['out_trade_no'],
            'transaction_id' => $pay_log['transaction_id'],
            'out_refund_no' => $out_refund_no,
            'order_sn' => $data['sn'],
            'total_fee' => $total_fee,
            'refund_fee' => $data['refund_fee'],
            'refund_times' => $data['times'],
            'status' => 1,
            'create_time' => time(),
            'update_time' => time(),
        );
        $refund_result = $LModel->add($idata);
        if(!$refund_result){
            return array('status' => 0, 'msg' => '退款发起失败');
        }
        $input->SetOut_trade_no($pay_log['out_trade_no']);
        $input->SetTotal_fee($total_fee);
        $input->SetOut_refund_no($out_refund_no);
        $input->SetRefund_fee($data['refund_fee']);
        
        $order = \WxPayApi::refund($input);
        

        $this->test_xy(array(
            'order' => $order,
        ));

        
        if($order['return_code'] == 'FAIL'){
            $LModel->where(array('id' => $refund_result, 'status' => 1))->save(array('status' => 3, 'update_time' => NOW_TIME));
            return array('status' => 0, 'msg' => $order['return_msg']);
        }
        $LModel->where(array('id' => $refund_result, 'status' => 1))->save(array('status' => 2, 'refund_id' => $order['refund_id'], 'update_time' => NOW_TIME));
        
        return array('status' => 1);
    }
    
    public function test_xy($data, $filename = 'test.txt')
    {
        $datetime = date('Y-m-d H:i:s');
        $data = print_r($data, true);
        file_put_contents('./' . $filename, $datetime . "\r\n" . $data . "\r\n\r\n", FILE_APPEND);
    }       
    
    
    /**
     * 退款查询
     * @param type $data out_trade_no:内部支付订单号 transaction_id:微信支付单号 refund_id:微信退款单号, out_refund_no:内部退款单号
     * @param type $url
     * @return type
     */
    public function refund_query($data){
        require_once "lib/WxPay.Api.php";
        
        $input = new \WxPayRefund();
        if(empty($data['out_refund_no']) && empty($data['refund_id'])){
            return array('status' => 0);
        }
        isset($data['out_refund_no']) && $input->SetOut_refund_no($data['out_refund_no']);
        isset($data['refund_id']) && $input->SetRefund_id($data['refund_id']);
        
        $order = \WxPayApi::refundQuery($input);
        if($order['return_code'] == 'FAIL'){
            return array('status' => 0, 'msg' => $order['return_msg']);
        }
        switch($order['refund_status_0']){
            case 'SUCCESS':
                return array('status' => 1);
                break;
            case 'REFUNDCLOSE':
                return array('status' => 1);
                break;
            case 'NOTSURE':
                return array('status' => 0);
                break;
            case 'PROCESSING':
                return array('status' => 0);
                break;
            case 'CHANGE':
                return array('status' => 2);
                break;
        }
        return array('status' => 0);
    }
}