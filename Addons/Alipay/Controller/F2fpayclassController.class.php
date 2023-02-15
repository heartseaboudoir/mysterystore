<?php
namespace Addons\Alipay\Controller;

use Think\Controller;

class F2fpayclassController extends Controller{
    public function __construct() {
        parent::__construct();
    }
    
    public function set_config($config){
        if(is_array($config)){
            !defined('PAY_APP_ID') && define('PAY_APP_ID', $config['appid']);
        }else{
            
        }
    }
    /**
     * 预下单
     * @param array $data   sn: 订单号 total_amount：订单金额（元） subject: 标题 body:描述 goods_detail:商品详情( goods_id:ID goods_name：商品名 goods_category：分类 price：价格 quantity：数量)
     * @param type $url
     * @return type
     */
    public function qrpay($data, $url = null){
        $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
        require_once $sdk_path.'AopSdk.php';
        require_once $sdk_path.'function.inc.php';
        require $sdk_path.'config.php';

        date_default_timezone_set('Asia/Shanghai');
        
        $time_expire = date('Y-m-d H:i:s', time()+60*60);
        $out_trade_no = $data['sn'].date("ymdHis").  mt_rand(10, 99);
        $content = array(
            'out_trade_no' => $out_trade_no,
            'total_amount' => $data['total_amount'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'goods_detail' => $data['goods_detail'],
            'time_expire' => $time_expire,
        );
        $biz_content = json_encode($content);
        $request = new \AlipayTradePrecreateRequest();
        $request->setBizContent ( $biz_content );
        $response = aopclient_request_execute ( $request , null, $url);
        $response = (array)$response->alipay_trade_precreate_response;
        if($response['code'] == 10000){
            $this->add_log($data['sn'], $out_trade_no, 'AlipayTradePrecreate', $data['total_amount'], $content);
        }
        return $response;
    }
    public function mobile_pay($data, $notify_url = '', $return_url = ''){
        $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
        require_once $sdk_path.'AopSdk.php';
        require $sdk_path.'config.php';
        $c = new \AopClient();
        $c->appId = $config['app_id'];
        $c->rsaPrivateKeyFilePath = $config['merchant_private_key_file'];
        
        date_default_timezone_set('Asia/Shanghai');
        
        $out_trade_no = $data['sn'].date("ymdHis").  mt_rand(10, 99);
        $service = 'alipay.wap.create.direct.pay.by.user';
        $param = array(
            'service' => $service,
            'partner' => $config['partner'],
            '_input_charset' => 'utf-8',
            'out_trade_no' => $out_trade_no,
            'subject' => $data['subject'],
            'total_fee' => $data['total_fee'],
            'seller_id' => $config['partner'],
            'payment_type' => 1,
            'show_url' => urlencode($data['show_url']),
            'body' => $data['body'],
            'it_b_pay' => "",
            'notify_url'=> $notify_url,
            'return_url' => $return_url,
        );
        
        $sign = $c->rsaSign($param);
        
        $param['sign'] = $sign;
        $param['sign_type'] = 'RSA';
        $url = 'https://mapi.alipay.com/gateway.do?';
        foreach($param as $k => $v){
            $_url[] = $k.'='.urlencode($v);
        }
        $this->add_log($data['sn'], $out_trade_no, $service, $data['total_fee'], $param);
        return $url.implode('&', $_url);
    }
    private function add_log($order_sn, $out_trade_no, $type, $total_fee, $data){
        $idata = array(
            'out_trade_no' => $out_trade_no,
            'total_fee' => $total_fee,
            'order_sn' => $order_sn,
            'data' => json_encode($data),
            'type' => $type,
            'create_time' => time(),
            'update_time' => time(),
        );
        M('AlipayLog')->add($idata);
    }
    /**
     * @param array $data   sn: 订单号 total_amount：订单金额（元） discountable_amount:折扣（0.00） subject: 标题 body:描述 goods_detail:商品详情( goods_id:ID goods_name：商品名 goods_category：分类 price：价格 quantity：数量)
     * @param type $url
     * @return type
     */
    public function app_order($data, $notify_url = null){
        $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
        require_once $sdk_path.'aop/AopClient.php';
        require $sdk_path.'config.php';

        date_default_timezone_set('Asia/Shanghai');
        
        $out_trade_no = uniqid('a').date("ymdHis").  mt_rand(10, 99);
        $server = 'mobile.securitypay.pay';
        $biz_content = array(
            'server' => $server,
            'notify_url' => $notify_url,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'total_fee' => $data['total_fee'],
            'payment_type' => 1,
            'out_trade_no' => $out_trade_no,
            'it_b_pay' => isset($data['it_b_pay']) ? $data['it_b_pay'] : '30m',
            '_input_charset' => 'utf-8',
            'partner' => $config['partner'],
            'seller_id' => $config['partner'],
            'extern_token' => '',
            'rn_check' => 'F',
            'goods_type' => '',
            'app_id' => '',
            'appenv' => '',
        );
        $c = new \AopClient;
        $c->rsaPrivateKeyFilePath = $config['merchant_private_key_file'];
        $sign = $c->rsaSign($biz_content);
        $biz_content['sign'] = $sign;
        $biz_content['sign_type'] = 'RSA';
        
        $this->add_log($data['sn'], $out_trade_no, $server, $data['total_fee'], $biz_content);
        return $biz_content;
    }
    
    public function verifyNotify(){
        $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
        require $sdk_path.'config.php';
        require_once $sdk_path.'AlipaySign.php';
        if(empty($_POST)) {//判断POST来的数组是否为空
            return false;
        }
        else {
            //对notify_data解密
            $decrypt_post_para = $_POST;
            //生成签名结果
            $isSign = $this->getSignVeryfy($decrypt_post_para, $_POST["sign"],false);

            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if ($isSign) {
                return true;
            } else {
                return false;
            }
        }
    }
    
    function sortNotifyPara($para) {
        $para_sort['service'] = $para['service'];
        $para_sort['v'] = $para['v'];
        $para_sort['sec_id'] = $para['sec_id'];
        $para_sort['notify_data'] = $para['notify_data'];
        return $para_sort;
    }
	
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序
     * @return 签名验证结果
     */
    public function getSignVeryfy($para_temp, $sign, $isSort) {
            
		//除去待签名参数数组中的空值和签名参数
                $para = array();
                while (list ($key, $val) = each ($para_temp)) {
                    if($key == "sign" || $key == "sign_type" || $val == "")continue;
                    else $para[$key] = $para_temp[$key];
                }
		//对待签名参数数组排序
		ksort($para);
                reset($para);
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		//$prestr = createLinkstring($para);
		$prestr  = "";
                while (list ($key, $val) = each ($para)) {
                        $prestr.=$key."=".$val."&";
                }
                //去掉最后一个&字符
                $prestr = substr($prestr,0,count($prestr)-2);

                //如果存在转义字符，那么去掉转义
                if(get_magic_quotes_gpc()){$prestr = stripslashes($prestr);}
                //echo $prestr;exit;
                $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
                require $sdk_path.'config.php';
		$isSgin = false;
                
                //$isSgin = rsaVerify($prestr, trim($config['ali_public_key_path']), $sign);
                $pubKey = file_get_contents($config['alipay_public_key_file']);
                $res = openssl_get_publickey($pubKey);
                $isSgin = (bool)openssl_verify($prestr, base64_decode($sign), $res);
                openssl_free_key($res); 
		return $isSgin;
	}
        
        
    /**
     * 退款申请
     * @param array $data   sn: 订单号 refund_amount：退款金额（元）
     * @param type $url
     * @return type
     */
    public function refund($data){
        $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
        require_once $sdk_path.'AopSdk.php';
        require_once $sdk_path.'function.inc.php';
        require $sdk_path.'config.php';

        date_default_timezone_set('Asia/Shanghai');
        if(empty($data['refund_amount'])){
            return array('status' => 0, 'msg' => '退款金额未知');
        }
        $where = array();
        isset($data['sn']) && $where['order_sn'] = $data['sn'];
        isset($data['out_trade_no']) && $where['out_trade_no'] = $data['out_trade_no'];
        isset($data['trade_no']) && $where['trade_no'] = $data['trade_no'];
        if(empty($where)){
            $this->error('查询条件不足');
        }
        $record = M('AlipayRecord')->where($where)->find();
        if(!$record){
            return array('status' => 0, 'msg' => '支付记录不存在');
        }
        $out_request_no = md5($data['sn'].$data['times']);
        $where['status'] = array('in', '1,2');
        if(M('AlipayRefundLog')->where($where)->find()){
            return array('status' => 0, 'msg' => '已申请退款，请等待');
        }
        $refund_amount = isset($data['refund_amount']) ? $data['refund_amount'] : 0;
        if($refund_amount + $record['refund_fee'] > $record['total_fee']){
            return array('status' => 0, 'msg' => '退款金额总和不得大于实际支付金额');
        }
        $content = array(
            'out_trade_no' => $record['out_trade_no'],
            'out_request_no' => $out_request_no,
            'refund_amount' => $refund_amount,
        );
        $idata = array(
            'appid' => '',
            'out_trade_no' => $record['out_trade_no'],
            'trade_no' => $record['trade_no'],
            'out_request_no' => $out_request_no,
            'order_sn' => $data['sn'],
            'total_fee' => $record['total_fee'],
            'refund_amount' => $refund_amount,
            'refund_times' => $data['times'],
            'status' => 1,
            'create_time' => time(),
            'update_time' => time(),
        );
        $refund_result = M('AlipayRefundLog')->add($idata);
        if(!$refund_result){
            return array('status' => 0, 'msg' => '退款发起失败');
        }
        $biz_content = json_encode($content);
        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent ( $biz_content );
        $response = aopclient_request_execute ( $request , null);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $response = (array)$response->$responseNode;
        
        xydebug($response);
        
        if($response['code'] == 10000){
            if(M('AlipayRefundLog')->where(array('id' => $refund_result, 'status' => 1))->save(array('status' => 2, 'update_time' => NOW_TIME))){
                return array('status' => 1);
            }else{
                return array('status' => 0);
            }
        }
        M('AlipayRefundLog')->where(array('id' => $refund_result, 'status' => 1))->save(array('status' => 3, 'update_time' => NOW_TIME));
        return array('status' => 0);
    }
    /**
     * 退款申请
     * @param array $data   sn: 订单号 refund_amount：退款金额（元）
     * @param type $url
     * @return type
     */
    public function refund_query($data){
        $sdk_path = dirname(dirname(__FILE__)).'/sdk/';
        require_once $sdk_path.'AopSdk.php';
        require_once $sdk_path.'function.inc.php';
        require $sdk_path.'config.php';
        if((empty( $data['out_trade_no']) && empty( $data['trade_no'])) || empty( $data['out_request_no'])){
            return array('status' => 0);
        }
        date_default_timezone_set('Asia/Shanghai');
        $content = array(
            'out_trade_no' => isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'out_request_no' => $data['out_request_no'],
            'trade_no' => isset($data['trade_no']) ? $data['trade_no'] : '',
        );
        $biz_content = json_encode($content);
        $request = new \AlipayTradeFastpayRefundQueryRequest();
        $request->setBizContent ( $biz_content );
        $response = aopclient_request_execute ( $request , null);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $response = (array)$response->$responseNode;
        if($response['code'] == 10000){
            if(!empty($response['out_request_no']) && $response['out_request_no'] == $data['out_request_no']){
                return array('status' => 1);
            }
        }
        return array('status' => 0);
    }
}