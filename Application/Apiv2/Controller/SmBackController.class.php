<?php
namespace Apiv2\Controller;
use Think\Controller;
use Apiv2\Extend\FlyCurl;

class SmBackController extends Controller {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'wxtpl.txt');
    }
    
    private function logs($data)
    {
        //print_r($data);
        xydebug($data, 'gpush_give.txt');
    }    
    
    private function response($code = 200, $msg = '已处理请求') 
    {
        if ($code == 200) {
            $data = array(
                'code' => 200,
                'content' => $msg,
            );
        } else {
            $data = array(
                'code' => $code,
                'content' => $msg,
            );
        }

        echo json_encode($data);
        exit;
    }    
    
    /**
     * 支付回调
     */
    public function payCallback()
    {
        $order_sn = I('order_sn', '', 'trim');
        
        
        if (empty($order_sn)) {
            $this->response(10010, 'order_sn is empty');
        }
        
        
        // 找出订单
        $order = M('order')->where(array('order_sn' => $order_sn))->find();
        
        // 订单不存在
        if (empty($order)) {
            $this->response(10011, 'order_sn not find');
        }
        
        
        // 订单未支付
        if ($order['status'] != 5 || $order['pay_status'] != 2) {
            $this->response(10012, 'order not over');
        }        
        
        // 发送微信模板消息
        if (!$this->isTest()) {
            $this->wxTpl($order);
        }
        
        // 推送订单至赠品机
        $this->gpush($order);
        
    }
    
    /**
     * 推送订单至赠品机
     */
    private function gpush($order)
    {
        if (empty($order) || empty($order['order_sn'])) {
            return;
        }
        
        $order_sn = $order['order_sn'];
        
        $sql = "select s.title as store_name,u.mobile,o.id,o.order_sn,o.uid,o.store_id,o.create_time,o.money,o.pay_money,
d.d_id,d.title,d.num,d.price 
from hii_order o 
left join hii_order_detail d on d.order_sn = o.order_sn 
left join hii_ucenter_member u on u.id = o.uid 
left join hii_store s on s.id = o.store_id 
where o.order_sn = '{$order_sn}' 
and o.status = 5 and o.type = 'store' and o.pay_status = 2;";

        $data = M()->query($sql);
        
        if (empty($data) || empty($data[0])) {
            return;
        }
        $one = $data[0];
        $time = time();
        
        
        $prices = 0;
        $items = array();
        foreach ($data as $key => $val) {
            $item = array(
                'goodId' => $val['d_id'],
                'goodName' => $val['title'],
                'price' => number_format($val['price'], 2, '.', ''),
                'quantity' => $val['num'],
                'subtotal' => number_format(round($val['price'] * $val['num'], 2), 2, '.', ''),
            );
            $items[] = $item;
            $prices += $val['price'] * $val['num'];
        }
        
        
        $info  = array(array(
            'id' => $one['order_sn'],
            'storeId' => $one['store_id'],
            'storeName' => $one['store_name'],
            'userPhone' => $one['mobile'],
            'items' => $items,
            'total' => number_format(round($prices, 2), 2, '.', ''),
            'soldAt' => $time * 1000,         
        ));
        //var_export($info);
        
        $json_data = json_encode($info);
        //echo $json_data;
        //exit;
        
        if ($this->isTest()) {
            $url = 'https://www.luckyseven7.com/staging/brandStoreApi/v1/zh-CN/sales';
        } else {
            $url = 'https://www.luckyseven7.com/brandStoreApi/v1/zh-CN/sales';
        }
        
        // 参数处理
        $config = array(
            'url' => $url,
            //'url' => 'https://www.luckyseven7.com/brandStoreApi/v1/zh-CN/sales',
            'method' => 'POST',
            'reqdata' => $json_data,
            'row' => array(
                'API_KEY' => '9020df4b22a2450a3ab28eddf3bbabe7',
                'Authorization' => '7b1cd66f4a8fffe097c1d9cd288ed8c9',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
        );
        

        // 请求
        $http = new FlyCurl();
        $code = $http->request($config)->getCode();
		if ($code == '200') {
            $content = $http->getDebug(true, true);
			//$content = $http->getContent();
		} else {
			$content = $http->getDebug(true, true);
		}         

        $this->logs($order_sn . ': ' . $code);
    }


    
    /**
     * 发送微信模板消息 
     */
    private function wxTpl($order)
    {
        
        
        if (empty($order['uid'])) {
            return array(10010, '订单不存在用户标示');
        }
        
        $uid = $order['uid'];
        
        if (empty($order['pay_money']) || $order['pay_money'] < 0) {
            return array(10012, '订单支付金额不足');
        }
        
        
        $user = M('ucenter_member')->where(array('id' => $uid))->find();
        
        if (empty($user)) {
            return array(10011, '用户不存在');
        }
        
        if (empty($user['openid'])) {
            return array(10020, '用户未与微信绑定');
        }
        
        $openid = $user['openid'];
        //$openid = 'ohEQbxBavUfzG5Y4JKUsIyaOoNxg';
        
        $template_id = 'vIArHlZ0RgdByDEeutWAPzp5ojoS9BBX4H0WDSH6TO8';
        

        $click_url = U('Wap/Order/detail', array('order_sn' => $order['order_sn']));
        
        // $click_url = 'https://www.baidu.com';
        
        // $ordercount = M('order_detail')->where(array('order_sn' => $order['order_sn']))->sum('num');
        $orderinfo = M('order_detail')->where(array('order_sn' => $order['order_sn']))->order('id asc')->field('id,title')->select();
        
        if (empty($orderinfo)) {
            return array(10030, '找不到订单对应商品');
        }
        
        $ordercount = count($orderinfo);
        if ($ordercount == 1) {
            $orderInfoName = $orderinfo[0]['title'];
        } else {
            $orderInfoName = $orderinfo[0]['title'] . '等' . $ordercount . '种商品';
        }
        
        $data = array(
            'first' => array(
                'value' => '欢迎光临神秘商店，您已支付完成，祝您购物愉快！',
                'color' => '#000000',				
            ),
            'orderMoneySum' => array(
                'value' => number_format($order['pay_money'], 2, '.', '') . '元',
                'color' => '#000000',
            ),
            'orderProductName' => array(
                //'value' => '神秘商店之神秘商品(点击详情查看)',
                'value' => $orderInfoName,
                'color' => '#000000',
            ),
            'Remark' => array(
                'value' => "如有问题请联系门店掌柜，神秘商店将第一时间为您服务！",
                'color' => '#000000',
            ),
        );
        
        $this->debugs($data);
        // $this->response(0, $data);
        // return;
        $weixin = A("Addons://Wechat/Wechatclass");
        $result = $weixin->tpl_msg($openid, $template_id, $data, $click_url, '#000000');        
        
        $this->response(0, $result);
        
    }
    
    
    private function isTest()
    {
        //echo $_SERVER["HTTP_HOST"];
        if ($_SERVER["HTTP_HOST"] != 'v.imzhaike.com') {
            return true;
        } else {
            return false;
        }        
    }       
    
    
    

}