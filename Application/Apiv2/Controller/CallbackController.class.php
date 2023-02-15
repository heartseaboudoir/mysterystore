<?php
namespace Apiv2\Controller;
use Think\Controller;

class CallbackController extends Controller {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'pay.txt');
    }
    
    private function debug_wc($data)
    {
        //print_r($data);
        xydebug($data, 'debug_wc.txt');
    }


    private function debug_zw($data)
    {
        //print_r($data);
        xydebug($data, 'debug_zw.txt');
    }


    public function wx_recharge_notify(){
        // 获取请求
        //$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xml = file_get_contents("php://input");

        $this->debugs($xml);

        // 解析 XML
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        
        A('Addons://WechatPay/WechatPayclass')->set_config('app');
        $wx_result = A('Addons://WechatPay/WechatPayclass')->notify_action($msg);
        
        //xydebug($wx_result, 'pay_test.txt');
        
        if($wx_result['status'] == 0){
            echo 'fail001';
            exit;
        }
        $data = $wx_result['data'];
        
/*
Array
(
    [status] => 1
    [data] => Array
        (
            [appid] => wx5e8adcb515a66f3e
            [attach] => {"order_sn":"test"}
            [bank_type] => CFT
            [cash_fee] => 1
            [fee_type] => CNY
            [is_subscribe] => N
            [mch_id] => 1342558101
            [nonce_str] => pmgdbbd5ekpt55fah0kyzwdbql803qjb
            [openid] => ogz25wPeDhnoPxjx5mWytcwZL75k
            [out_trade_no] => cz2018081621571279676
            [result_code] => SUCCESS
            [return_code] => SUCCESS
            [sign] => 7FC6B58AE749A2EAD1D832EA8AC09556
            [time_end] => 20180816215718
            [total_fee] => 1
            [trade_type] => APP
            [transaction_id] => 4200000167201808166847863053
        )

)
*/       
        // 验证支付状态
        if (($data['result_code'] != 'SUCCESS') || ($data['return_code'] != 'SUCCESS')) {
            echo 'fail';
            exit;            
        }
 

        // 商户订单号
        if (empty($data) || empty($data['out_trade_no'])) {
            echo 'fail';
            exit;
        }

        $order_sn = $data['out_trade_no'];


        // 查看订单状态及用户信息
        $order = M('recharge_order')->where(array(
            'order_sn' => $order_sn
        ))->find();

        // 订单不存在
        if (empty($order)) {
            echo 'fail';
            exit;

        // 订单已经完成
        } elseif (!empty($order['status'])) {
            echo 'success';
            exit;
        }

        //$money = round($order['money'] + $order['give'], 2);
        $money = $order['money'];
        $give = $order['give'];
        
        
        $price = empty($data['total_fee']) ? 0 : $data['total_fee'] / 100;
        
        // 订单金额比对回调金额
        if ($money > $price) {
            echo 'fail';
            exit;            
        }        
        

        // 调整支付订单状态,调整余额值,将记录加到余额列表
        $this->changeOrder($order['uid'], $order['order_sn'], $money, $give, $data['transaction_id'], $data);


        echo 'success';
        exit;
    }





    public function ali_recharge_notify(){
        
        $result = A('Addons://Alipay/F2fpayclass')->verifyNotify();
        
        if(!$result){
            echo 'fail_001';
            exit;
        }
        
        


        $data = $_POST;

        $this->debugs($data);

        // 商户订单号
        if (empty($data) || empty($data['out_trade_no'])) {
            echo 'fail';
            exit;
        }
        
        // 成功的回调状态
        if ($data['trade_status'] != 'TRADE_SUCCESS') {
            echo 'fail';
            exit;            
        }

        
        // 订单状态
        $order_sn = trim($data['out_trade_no']);
        if (empty($order_sn)) {
            echo 'fail';
            exit;              
        }

        // 查看订单状态及用户信息
        $order = M('recharge_order')->where(array(
            'order_sn' => $order_sn
        ))->find();
        

        // 订单不存在
        if (empty($order)) {
            echo 'fail';
            exit;

            // 订单已经完成
        } elseif (!empty($order['status'])) {
            echo 'success';
            exit;
        }
        
        

        

        //$money = round($order['money'] + $order['give'], 2);
        $money = $order['money'];
        $give = $order['give'];
        
        $price = empty($data['total_fee']) ? 0 : $data['total_fee'];
        
        // 订单金额比对回调金额
        if ($money > $price) {
            echo 'fail';
            exit;            
        }
                
        

        // 调整支付订单状态,调整余额值,将记录加到余额列表
        $this->changeOrder($order['uid'], $order['order_sn'], $money, $give, $data['trade_no'], $data);

        echo 'success';
        exit;
    }







    // 调整支付订单状态,调整余额值
    private function changeOrder($uid, $order_sn, $money, $give, $pay_sn, $data)
    {
        $order_status = array(
            // 微信订单号
            'pay_sn' => $pay_sn,
            'status' => 1,
            'pay_data' => json_encode($data),
            'update_time' => time(),

        );

        // 调整支付订单状态
        $result = M('recharge_order')->where(array(
            'order_sn' => $order_sn
        ))->save($order_status);

        if (empty($result)) {
            return;
        }

        if ($give > 0) {
            $money_all = round($money + $give, 2);
        } else {
            $money_all = $money;
        }

        // 确保用户余额信息存在
        $this->addUserWallet(array(
            array('uid' => $uid)
        ));

        // 调整余额值
        $res = M('wallet')->where(array(
            'uid' => $uid
        ))->setInc('recharge_money', $money_all);

        // 将记录加到余额列表
        $this->insertList($uid, $order_sn, $money, $give);
        
        // 充值活动
        $this->setPrize($uid, $order_sn, $money, $give);
        
        // 充值获取世界杯投票资格
        $this->getWc($uid, $order_sn, $money);
        /*
          `uid` int(11) NOT NULL COMMENT '用户ID',
          `order_sn` varchar(255) NOT NULL COMMENT '订单号',
          `pay_sn` varchar(255) NOT NULL COMMENT '支付单号',
          `money` decimal(10,2) DEFAULT '0.00' COMMENT '订单支付总价',
          `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态，0，未支付，1，已支付',
          `type` int(11) NOT NULL DEFAULT '0' COMMENT '支付方式，1：微信，2：支付宝',
          `pay_data` text COMMENT '支付数据',
          `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
          `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
         *
         */
    }
    
    
    /**
     * 放发充值活动商奖品
     */
    private function setPrize($uid, $order_sn, $money, $give)
    {
        // 获取充值配置
        $config = $this->getPrizeConfig();

        // 没有开启活动,直接返回
        if (empty($config['is_open'])) {
            $this->debug_zw('活动未开启');
            return;
        }
        
        // 内测有效
        /*
        if (!in_array($uid, array(1313, 23666, 52, 5933, 11536, 955, 1383, 6170, 38648, 28893, 36324))) {
            $this->debug_zw('非内测人员：' . $uid);
            return;
        }
        */

        // 查找充值订单
        $order = M('recharge_order')->where(array('order_sn' => $order_sn))->find();
        if (empty($order)) {
            return;
        }

        // 充值订单ID
        $oid = $order['id'];

        // 处理时间
        $time = time();
        
        // 获取抽奖次数
        $prizeNum = $this->getPrizeNum($money);

        $this->debug_zw($prizeNum);

        // 抽取指定次数的奖品
        $prizeMoney = 0;
        $prizes = array();
        for ($i = 0; $i < $prizeNum; $i++) {
            
            // 随机抽取奖品
            $prize = $this->getRandomPrize();

            // 奖品有问题则跳过
            if (empty($prize)) {
                continue;
            }

            // 转换随机奖品金额
            $prize = $this->changeRandomPrize($prize);
            
            // 加入奖项
            $prizes[] = $prize;
            
            // 计算抽奖金额
            $prizeMoney += $prize['money'];
        }
        $this->debug_zw($prizes);
        
        
        // 写入活动订单
        $orderData = array(
            'uid' => $uid, // 用户ID
            'oid' => $oid, // 订单ID
            'status' => 1,
            'num' => $prizeNum,// 抽奖次数
            'money' => $prizeMoney, // 抽奖金额
            'create_time' => $time,
            'update_time' => $time,
        );
        $orderInfo = M('prize_order')->add($orderData);

        $this->debug_zw($orderData);
        $this->debug_zw($orderInfo);

        if ($prizeMoney <= 0) {
            return;
        }

        foreach ($prizes as $key => $val) {
            // 写入活动奖项
            M('prize_user')->add(array(
                'uid' => $uid,
                'oid' => $orderInfo,
                'pid' => $val['id'],
                'money' => $val['money'],
                'val' => $val['val'],
                'status' => 1,
                'create_time' => $time,
                'update_time' => $time,
            ));

            // 余额发送记录
            M('wallet_log')->add(array(
                'uid' => $uid,
                'money' => $val['money'],
                'type' => 5,
                'action' => 'give',
                'action_sn' => 'prize_' . $orderInfo . '_' . $val['id'] . '_' . date('YmdHis'),
                'msg' => '充值订单' . $order_sn . '随机抽奖获取',
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            ));

        }


        
        // 发放余额
        M('wallet')->where(array(
            'uid' => $uid,
        ))->setInc('recharge_money', $prizeMoney);
        

        
        // 发送通知
        $price_show = number_format($prizeMoney, 2, '.', '');
        M('message_notice')->add(array(
            'type' => 'notice',
            'title' => '系统通知',
            'act_uid' => 0,
            'act_id' => 214,
            'act_data' => '[]',
            'hid' => '',
            'is_read' => 0,
            'status' => 0,
            'create_time' => $time,
            'update_time' => $time,
            'uid' => $uid,
            'hash' => md5('充值活动' . $uid . $time . mt_rand(10000, 99999)),
            //'content' => '恭喜您，您在神秘商店充值随机抽取'. $price_show .'元的金额，现已发放到您的余额。',
            'content' => '恭喜您，您在神秘商店充值活动中参与' . $prizeNum . '次抽奖，共获得'. $price_show .'元的金额,现已发放到您的余额。',

        ));
    }
    
    
    /**
     * 是否在指定时间内的首次抽奖
     */
    private function isPrizeFirst_test()
    {
        // 设定的时间段
        $time_str = '2018-10-11 18:10:00';
        $time = strtotime($time_str);
        
        // 是否已存在此时间后的999

        $sql = "select * from hii_prize_user where create_time >= {$time} and money > 999 limit 0,1;";
        
        $one = M()->query($sql);
        
        // 如果之前没有，那就是第一条
        if (empty($one)) {
            return true;
            
        // 如果之前有过，那就不是
        } else {
            return false;
        }
    }
    
    
    /**
     * 是否在指定时间内的首次抽奖
     */
    private function isPrizeFirst()
    {
        // 设定的时间段
        $time_str = '2018-10-11 18:10:00';
        $time = strtotime($time_str);
        
        $now = time();
        if ($now < $time) {
            return false;
        }
        
        // 是否已存在此时间后的999
        $sql = "select * from hii_prize_concurrence where id = 1 and version = 0";
        
        $one = M()->query($sql);
        
        
        // 如果能取到版本号为0的数据
        if (!empty($one)) {
            
            // 操作成功即为第一次
            $res = M('prize_concurrence')->where(array(
                'id' => 1,
                'version' => 0,
            ))->setInc('version', 1);
            
            $this->debug_zw('更新版本: ');
            $this->debug_zw($res);
            
            // 操作失败
            if (empty($res)) {
                return false;
                
            // 操作成功
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 转换随机奖品金额
     */
    private function changeRandomPrize($prize)
    {
        if (empty($prize) || empty($prize['id'])) {
            return array();
        }


        if (in_array($prize['id'], array(9, 99, 999))) {

            switch ($prize['id']) {
                case 9:
                    // 9.99
                    $money = mt_rand(499, 999);

                    break;
                case 99:
                    // 99.99
                    $money = mt_rand(1000, 9999);
                    break;
                case 999:
                    // 999.99
                    $money = mt_rand(10000, 99999);
                    break;
            }

            $money = (floor($money / 10) * 10 + 9) / 100;

            $prize['money'] = $money;
        }

        return $prize;


    }


    public function testRandom()
    {
        $data = $this->getRandomPrize();

        echo json_encode($data);
        // print_r($data);
    }
    
    
    /**
     * 随机获取指定的奖品
     */
    private function getRandomPrize()
    {
        
        // 如果是第一条，则返回如下奖项    
        /*
        $first = $this->isPrizeFirst();
        if ($first) {
            $time = time();
            return array(
                'id' => 888,
                'money' => 999.99,
                'val' => 0,
                'info' => '100.09元~999.99元（随机999.99元）',
                'create_time' => $time,
                'update_time' => $time,
            );
        }
        */
        
        
        
        
        
        $where = array(
            'val' => array('gt',0),
        );

        $lists = M('prize_list')->where($where)->select();

        if (empty($lists)) {
            return array();
        }


        $vals = array();
        foreach ($lists as $key => $val) {
            $vals[$key] = $val['val'];
        }

        $num = $this->getRandInfo($vals, 1);

        if ($num === false || empty($lists[$num])) {
            return array();
        }

        return $lists[$num];
        
        
        
    }


    /**
     * 获取随机信息
     * params arr: array($key => $val, ...)
     * return $key
     */
    private function getRandInfo($arr, $xs=100)
    {

        //初始值
        $max = 0;


        //生成概率层阶
        foreach($arr as $key => $value) {
            $max = $value * $xs + $max;
            $prize[$key] = $max;
        }

        //生成随机值，并返回对应点位
        $zone = false;
        $rand = mt_rand(0, $max);
        foreach ($prize as $key => $value) {
            if ($rand > $value) {
                continue;
            } else {
                $zone = $key;
                break;
            }
        }

        return $zone;
    }


    
    
    /**
     * 获取抽奖次数
     */
    private function getPrizeNum($money)
    {

        // 获取充值配置
        $config = $this->getPrizeConfig();

        $one = $config['money'];

        if (empty($one) || $one <= 0) {
            return 0;
        }

        $num = $money / $one;
        $num = intval(floor($num));

        if ($num > 10) {
            return 10;
        }

        return $num;
    }
    


    // 为没有钱包信息的用户添加记录
    private function addUserWallet($users)
    {
        if (empty($users)) {
            return;
        }

        $time = time();

        foreach ($users as $key => $user) {

            $one = M('wallet')->where(array(
                'uid' => $user['uid'],
            ))->find();


            if (empty($one)) {
                M('wallet')->add(array(
                    'uid' => $user['uid'],
                    'money' => 0,
                    'all_money' => 0,
                    'lock_money' => 0,
                    'frozen_money' => 0,
                    'income_money' => 0,
                    'recharge_money' => 0,
                    'create_time' => $time,
                    'update_time' => $time,
                    'status' => 1,
                ));
            }
        }

    }



    // 将记录加到余额列表
    private function insertList($uid, $order_sn, $money, $give)
    {
        $time = time();
        $data01 = array(
                'uid' => $uid,
                'money' => $money,
                'give' => 0,
                'type' => 3, // 充值
                'action' => 'recharge',
                'action_sn' => $order_sn,
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            );
        M('wallet_log')->add($data01);    
        
        
        if (!empty($give) && $give > 0) {
            $data02 = array(
                'uid' => $uid,
                'money' => $give,
                'give' => 0,
                'type' => 5, // 赠送
                'action' => 'give',
                'action_sn' => $order_sn,
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            );
            M('wallet_log')->add($data02);
        }
        
        
        /*
          `uid` int(10) unsigned DEFAULT NULL,
          `money` decimal(10,2) DEFAULT NULL,
          `type` tinyint(1) DEFAULT '0' COMMENT '类型：1 收入 2 支出',
          `action` varchar(50) DEFAULT NULL,
          `action_sn` varchar(100) DEFAULT NULL,
          `is_lock` tinyint(1) DEFAULT '0' COMMENT '是否锁定 1 是 0 否',
          `unlock_time` int(11) DEFAULT NULL,
          `create_time` int(11) DEFAULT NULL,
          `update_time` int(11) DEFAULT NULL,
         */
    }


    // 充值获取世界杯投票资格
    private function getWc($uid, $order_sn, $money)
    {
        // 获取充值次数
        $config = $this->getWcConfig();
        
        
        $time = time();
        
        if ($config['is_open'] == 0) {
            $this->debug_wc('is_open off');
            return;
        }
        /*
        if ($config['stime'] > $time || $config['etime'] < $time) {
            $this->debug_wc('stime,etime off');
            return;
        }
        */
        if ($config['bstime'] > $time || $config['betime'] < $time) {
            $this->debug_wc('bstime,betime off: ' . $time);
            return;
        }        
        
        // 获取次数
        $num = $this->getWcNum($money);
        if (empty($num)) {
            $this->debug_wc('num=0 -> $money:' . $money);
            return;
        }
        
        $have = M('wc_bit_record')->where(array(
            'uid' => $uid,
        ))->find();
        
        if (empty($have) || empty($have['id'])) {
            M('wc_bit_record')->add(array(
                'uid' => $uid,
                'alls' => $num,
                'can' => $num,
                'create_time' => $time,
                'update_time' => $time,
            ));
        } else {
            M('wc_bit_record')->where(array('id' => $have['id']))->setInc('alls', $num);
            M('wc_bit_record')->where(array('id' => $have['id']))->setInc('can', $num);
        }
        
        
        
        $bit_log = array(
            'uid' => $uid,
            'order_sn' => $order_sn,
            'money' => $money,
            'num' => $num,
            'create_time' => $time,
        );
        
        $this->debug_wc($bit_log);
        
        //用户获取投注记录
        M('wc_bit_log')->add($bit_log);
    }
    
    
    
    
    // 获取活动配置
    private function getPrizeConfig()
    {
        $data = M('prize_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => 0,
                'money' => 0,
                'val' => '',
                'info' => '',
                'money' => 0,
                'stime' => 0,
                'etime' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        return $data;        
    }
    
    // 获取可投票次数
    private function getWcNum($money)
    {
        
        if ($money == 0.01) {
            return 1;
        } elseif ($money == 0.02) {
            return 2;
        } elseif ($money == 0.05) {
            return 7;
        }
        
        
        
        
        $test = $this->isTest();
        
        
        if ($test) {
            $money = $money * 100 * 100;
        }
        
        if ($money >= 100 && $money < 200) {
            return 1;
        } elseif ($money >= 200 && $money < 500) {
            return 2;
        } elseif ($money >= 500) {
            return 7;
        }
        
        
        return 0;
        
        /*
        return array(
            '100' => 1,
            '200' => 2,
            '500' => 7,
        );
        */

        
    }
    
    
    // 是否测试环境
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
