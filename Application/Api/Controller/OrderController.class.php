<?php
// +----------------------------------------------------------------------
// | Title: 订单
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 门店端
// +----------------------------------------------------------------------
namespace Api\Controller;
use Api\Controller\ApiController;
use Apiv2\Extend\JjPay;


class OrderController extends ApiController {
    
    /**
     * @name   add_order
     * @title  提交订单
     * @param  json  $goods  商品json数组,每个数组中的参数为：<br>[id] => 商品ID，[num] => 商品数量<br>示例:<br>[{"id":1,"num":2},{"id":3,"num":2}]
     * @return [order_sn] => 订单号<br>
                [pay_money] => 总价<br>
                [num] => 订单商品总数<br>
                [pay_status] => 支付状态： 1 未支付 2 已支付<br>
                [pay_status_text] => 支付状态文本<br>
                [create_time] => 交易时间<br>
                [status] => 状态 1 新订单 2 待确认 3 已取消 4 已完成<br>
                [status_text] => 状态文本<br>
                [pay_qrcode_url] => 支付扫码地址<br>
                <br>[goods_data] => 订单内容<br><br>
                -- goods_data 数组字段 --<br>
                [goods_id] => 商品ID<br>
                [title] => 标题<br>
                [pic_url] => 图片<br>
                [num] => 数量<br>
                [month_num] => 本月销量<br>
                [hot_val] => 热度值<br>
                [price] => 单价<br>
     * @remark 测试过程中，在支付时，订单的金额将为1分。
     */
    public function add_order(){   
        $this->_check_token();
        // 计算总价        
        $pay_money = 0; //快递费用
        $goods = I('goods', '');
        if(!$goods){
            $this->return_data(0, array(), '提交订单失败：订单商品为空');
        }
        $goods = json_decode($goods, true);
        if(!is_array($goods)){
            $this->return_data(0, array(), '提交订单失败：订单商品数据错误');
        }
        foreach($goods as $k => $v){
            if(empty($v['id']) || empty($v['num'])){
                unset($goods[$k]);
            }
            $v['id'] = intval($v['id']);
            $v['num'] = intval($v['num']);
            if($v['id'] < 0 || $v['num'] < 0){
                unset($goods[$k]);
            }
        }
        if(!$goods){
            $this->return_data(0, array(), '提交订单失败：购物车为空');
        }
        $num = 0;
        $pre = C('DB_PREFIX');
        foreach($goods as $k => $v){
            $info = M('Goods')->where(array('_string' => "{$pre}goods_store.status = 1 and {$pre}goods.id = {$v['id']} and {$pre}goods_store.store_id = ".$this->_store_id))->join('__GOODS_STORE__ ON __GOODS__.id = __GOODS_STORE__.goods_id')->field("title, cover_id, cate_id, sell_price, {$pre}goods_store.price, {$pre}goods_store.shequ_price, {$pre}goods_store.num, {$pre}goods_store.sell_num")->find();
            if(!$info){
                $this->return_data(0, array(), '订单中有商品不存在或已下架');
            }elseif($v['num']>$info['num']){
                $this->return_data(0, array(), '《'.$info['title'].'》库存不足');
            }
            $info['price'] <= 0 && $info['price'] = $info['shequ_price'];
            
            //$info['price'] <= 0 && $info['price'] = $this->getShequPrice($v['id'], $this->_store_id);
            
            $info['price'] <= 0 && $info['price'] = $info['sell_price'];
            $pay_money += $info['price']*$v['num'];
            $goods[$k]['info'] = $info;
            $num += $v['num'];
        }
        $order_sn = $this->get_sn($this->_uid);
        // 添加订单
        $data = array(
            'order_sn' => $order_sn,
            'pay_money' => $pay_money,
            'money' => $pay_money,
            'status' => 1,
            'pay_status' => 1,
            'uid' => 0,
            'store_id' => $this->_store_id,
            'pos_id' => $this->_pos_id,
        );
        
        D('Addons://Order/Order')->create($data);
        
        $result = D('Addons://Order/Order')->add();
        if($result){
            $goods_ids = array();
            $goods_data = array();
            foreach($goods as $v){
                $detail = array(
                    'order_sn' => $order_sn,
                    'title' => $v['info']['title'],
                    'type' => 'goods',
                    'd_id' => $v['id'],
                    'num' => $v['num'],
                    'price' => $v['info']['price'],
                    'cover_id' => $v['info']['cover_id'],
                    'setting' => '',
                    'goods_log' => json_encode($v['info'])
                );
                D('Addons://Order/OrderDetail')->create($detail);
                D('Addons://Order/OrderDetail')->add();
                $goods_ids[] = $v['id'];
                $goods_data[] = array(
                    'goods_id' => $v['id'],
                    'title' => $v['info']['title'],
                    'num' => $v['num'],
                    'month_num'  => 0,
                    'hot_val'  => 0,
                    'price' => $v['info']['price'],
                    'pic_url' => get_cover_url($v['info']['cover_id']),
                );
            }
            
            $log_model = M('GoodsSellLog'.$this->_store_id);
            $date = array(
                date('Y-m-d', strtotime('-1 day')), // 昨天
                date('Y-m-d', strtotime('-2 day')), // 前天
            );
            $log_data = $log_model->where(array('goods_id' => array('in', $goods_ids), 'date' => array('in', $date)))->select();
            foreach($log_data as $v){
                if($v['date'] == $date[0]){
                    $log_num1[$v['goods_id']] = $v['num'];
                }elseif($v['date'] == $date[1]){
                    $log_num2[$v['goods_id']] = $v['num'];
                }
            }
            foreach($goods_data as $k => $v){
                $num1 = isset($log_num1[$v['goods_id']]) ? $log_num1[$v['goods_id']] : 0;
                $num2 = isset($log_num2[$v['goods_id']]) ? $log_num2[$v['goods_id']] : 0;
                // 公式 前一天的销量 / （前一天的销量+前两天的销量） * 10  取一位小数
                $v['hot_val'] = round($num1/($num1+$num2)*10, 1);
                $goods_data[$k] = $v;
            }
            
            // 神秘商店支付二维码
            $sm_qrcode_url = U('Relay/order', array('order_sn' => $order_sn));
            
            // 是否加盟商
            $is_jm = $this->isJm($this->_store_id);
            
            $r_data = array(
                'order_sn' => $order_sn,
                'pay_money' => $pay_money,
                'num' => $num,
                'status' => 1,
                'status_text' => '新订单',
                'pay_status' => 1,
                'pay_status_text' => '未支付',
                'create_time' => time(),
                'goods_data' => $goods_data,
                'pay_qrcode_url' => $sm_qrcode_url,
                //'pay_qrcode_url' => '',
                'is_jj' => 0, 
                'is_jm' => empty($is_jm) ? 0 : 1, 
                'jj_qrcode_urls' => array(
                    'wxpay' => '',
                    'alipay' => '',
                    'wxpay1' => '',
                    'alipay1' => '',
                ),
            );
            
            
            $is_jj = $this->isJj($this->_store_id);
            // $is_jj = false;
            if ($is_jj) {
                xydebug('start~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~', 'jj_test.txt');
                // 锦江支付二维码
                $jj_qrcode_urls = $this->getCodes(array(
                    'money' => $pay_money * 100, 
                    'order_sn' => $order_sn,
                    //'type' => 'wxpay',//alipay,wxpay
                    //'notify_url' => 'http://test.imzhaike.com/Apiv2/Jjback/notify',
                    'notify_url' => U('Apiv2/Jjback/notify'),
                ));                  
                
                $jj_qrcode_urls['wxpay1'] =  $jj_qrcode_urls['wxpay'] . '&sm=' . mt_rand(10000, 99999);
                $jj_qrcode_urls['alipay1'] =  $jj_qrcode_urls['alipay'] . '&sm=' . mt_rand(10000, 99999);
                

                //$jj_qrcode_urls['wxpay'] =  'http://v.imzhaike.com/abc';
                //$jj_qrcode_urls['alipay'] =  'http://v.imzhaike.com/x123';
                
                $r_data['is_jj'] = 1;
                $r_data['jj_qrcode_urls'] = $jj_qrcode_urls;
                
                
                $json_data = json_encode($r_data);
                xydebug($json_data, 'jj_test.txt');
                xydebug('end~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~', 'jj_test.txt');
                
            }            
            

            
            
            $this->return_data(1, array($r_data), '');
        }else{
            $this->return_data(0, array(), '提交订单失败：'.$result->getError());
        }
    }
    
    // 获取社区售价
    private function getShequPrice($goods_id, $store_id)
    {
        if (empty($goods_id) || empty($store_id)) {
            return 0;
        }
        
        $storeInfo = M('store')->where(array(
            'id' => $store_id
        ))->find();
        
        
        if (empty($storeInfo) || empty($storeInfo['shequ_id'])) {
            return 0;
        }
        
        $res = M('goods_shequ')->where(array(
            'goods_id' => $goods_id,
            'shequ_id' => $storeInfo['shequ_id'],
            'status' => 1,
        ))->find();
        
        if (empty($res) || empty($res['price']) || $res['price'] <= 0) {
            return 0;
        } else {
            return $res['price'];
        }  
    }
    
    private function get_sn($uid){
        $order_sn = date('ymdHis').$uid. mt_rand(1000, 9999);
        if(D('Addons://Order/Order')->where(array('order_sn' => $order_sn))->find()){
            $order_sn = $this->get_sn($uid);
        }
        return $order_sn;
    }
    /**
     * @name  get_pay_status
     * @title 查询订单支付状态
     * @param string  $order_sn  订单号
     * @return 
     * @remark 用于查询用户是否通过微信或支付宝进行支付。<br>状态：<br>-1 : 订单不存在 <br> 1 订单已支付 <br> 0 订单未支付
     */
    public function get_pay_status(){
        $order_sn = I('order_sn');
        $order = M('Order')->where(array('order_sn' => $order_sn))->field('pay_status')->find();
        if(!$order){
            $this->return_data(-1, array(),'订单不存在');
        }
        if($order['pay_status'] == 2){
            $this->return_data(1, array(), '订单已支付');
        }else{
            $this->return_data(0, array(), '订单未支付');
        }
    }
    
    // 是否加盟商
    private function isJm($store_id)
    {
        if (empty($store_id)) {
            return false;
        } else {
        
            $store = M('store')->where(array('id' => $store_id))->find();
            
            if (empty($store) || empty($store['shequ_id'])) {
                return false;
            } else {
                $isTest = $this->isTest();
                if (($isTest && $store['shequ_id'] == 16) || (!$isTest && $store['shequ_id'] == 18)) {
                    return true;
                } else {
                    return false;
                }
            }
        }        
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
    
    
    
    // 是否锦江门店
    private function isJj($store_id)
    {
        if (empty($store_id)) {
            return false;
        } else {
        
            $store = M('store')->where(array('id' => $store_id))->find();
            
            if (empty($store) || empty($store['is_jj'])) {
                return false;
            } else {
                return true;
            }
        }
    }


    public function getCodes($paydata)
    {
        xydebug('======================================', 'jj_test.txt');
        xydebug($paydata, 'jj_test.txt');
        $jjpay = new JjPay();
        
        
        // 微信二维码
        $paydata['type'] = 'wxpay';
        $data_wxpay = $jjpay->pay($paydata);
        xydebug('wxpay:', 'jj_test.txt');
        xydebug($data_wxpay, 'jj_test.txt');
        
        
        $codes = array();
        if (!empty($data_wxpay[0]) && !empty($data_wxpay[1]) ) {
            $codes['wxpay'] = $data_wxpay[1];
        } else {
            $this->return_data(0, array(), '获取锦江支付二维码失败(wxpay)');
        }
        
        
        // 支付宝二维码
        $paydata['type'] = 'alipay';
        $data_alipay = $jjpay->pay($paydata);
        xydebug('alipay:', 'jj_test.txt');
        xydebug($data_alipay, 'jj_test.txt');        
        if (!empty($data_alipay[0]) && !empty($data_alipay[1]) ) {
            $codes['alipay'] = $data_alipay[1];
        } else {
            $this->return_data(0, array(), '获取锦江支付二维码失败(alipay)');
        }        
        
        xydebug($codes, 'jj_test.txt');
        
        xydebug('======================================', 'jj_test.txt');
        return $codes;        
    }



    public function testCode()
    {
        
        $pay_money = 0.02;
        $order_sn = 'test' . mt_rand(10000, 99999);
        $pay_type = 'alipay';
        
        // 锦江支付二维码
        $data = $this->getPayCode(array(
            'money' => $pay_money * 100, 
            'order_sn' => $order_sn,
            'type' => $pay_type,//alipay,wxpay
            'notify_url' => U('Apiv2/Jjback/notify'),
        ));    
        
        print_r($data);
    }

    
    
    // 获取锦江支付二维码
    private function getPayCode($paydata)
    {
        $jjpay = new JjPay();
        $data = $jjpay->pay($paydata);
        xydebug($data, 'jj_test.txt');
        return $data;
    }    
    
}
