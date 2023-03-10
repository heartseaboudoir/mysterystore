<?php
require_once dirname(dirname(__FILE__)).'/AopSdk.php';
require_once dirname(dirname(__FILE__)).'/function.inc.php';
require dirname(dirname(__FILE__)).'/config.php';

class F2fpay {
	
	
	public function barpay($out_trade_no, $auth_code, $total_amount, $subject) {
		date_default_timezone_set('Asia/Shanghai');
		
		$time_expire = date('Y-m-d H:i:s', time()+60*60); 
		
	
		
		$biz_content="{\"out_trade_no\":\"" . $out_trade_no . "\",";
		$biz_content.="\"scene\":\"bar_code\",";
		$biz_content.="\"auth_code\":\"" . $auth_code . "\",";
		$biz_content.="\"total_amount\":\"" . $total_amount
		. "\",\"discountable_amount\":\"0.00\",";
		$biz_content.="\"subject\":\"" . $subject . "\",\"body\":\"test\",";
		$biz_content.="\"goods_detail\":[{\"goods_id\":\"apple-01\",\"goods_name\":\"ipad\",\"goods_category\":\"7788230\",\"price\":\"88.00\",\"quantity\":\"1\"},{\"goods_id\":\"apple-02\",\"goods_name\":\"iphone\",\"goods_category\":\"7788231\",\"price\":\"88.00\",\"quantity\":\"1\"}],";
		$biz_content.="\"operator_id\":\"op001\",\"store_id\":\"pudong001\",\"terminal_id\":\"t_001\",";
		$biz_content.="\"time_expire\":\"" . $time_expire . "\"}";
		
		echo $biz_content;
		
		$request = new AlipayTradePayRequest();
		$request->setBizContent ( $biz_content );
		$response = aopclient_request_execute ( $request );
		
		
		
		return $response;
	}
	
	
	public function qrpay($out_trade_no,  $total_amount, $subject) {
		date_default_timezone_set('Asia/Shanghai');
	
		$time_expire = date('Y-m-d H:i:s', time()+60*60);
	
	
	
		$biz_content="{\"out_trade_no\":\"" . $out_trade_no . "\",";
		$biz_content.="\"total_amount\":\"" . $total_amount
		. "\",\"discountable_amount\":\"0.00\",";
		$biz_content.="\"subject\":\"" . $subject . "\",\"body\":\"test\",";
		$biz_content.="\"goods_detail\":[{\"goods_id\":\"apple-01\",\"goods_name\":\"ipad\",\"goods_category\":\"7788230\",\"price\":\"88.00\",\"quantity\":\"1\"},{\"goods_id\":\"apple-02\",\"goods_name\":\"iphone\",\"goods_category\":\"7788231\",\"price\":\"88.00\",\"quantity\":\"1\"}],";
		$biz_content.="\"operator_id\":\"op001\",\"store_id\":\"pudong001\",\"terminal_id\":\"t_001\",";
		$biz_content.="\"time_expire\":\"" . $time_expire . "\"}";
	
		$request = new AlipayTradePrecreateRequest();
		$request->setBizContent ( $biz_content );
		$response = aopclient_request_execute ( $request );
	
	
	
		return $response;
	}
	
	
	public function query($out_trade_no) {	
		$biz_content="{\"out_trade_no\":\"" . $out_trade_no . "\"}";
		$request = new AlipayTradeQueryRequest();
		$request->setBizContent ( $biz_content );
		$response = aopclient_request_execute ( $request );
		return $response;
	}
	
	
	public function cancel($out_trade_no) {
		$biz_content="{\"out_trade_no\":\"" . $out_trade_no . "\"}";
		$request = new AlipayTradeCancelRequest();
		$request->setBizContent ( $biz_content );
		$response = aopclient_request_execute ( $request );
		return $response;
	}
	
	public function refund($trade_no,
			$refund_amount, $out_request_no) {
		$biz_content = "{\"trade_no\":\"". $trade_no . "\",\"refund_amount\":\""
						. $refund_amount
						. "\",\"out_request_no\":\""
								. $out_request_no
								. "\",\"refund_reason\":\"reason\",\"store_id\":\"store001\",\"terminal_id\":\"terminal001\"}";
		
		$request = new AlipayTradeRefundRequest();
		$request->setBizContent ( $biz_content );
		$response = aopclient_request_execute ( $request );
		return $response;
	}
}