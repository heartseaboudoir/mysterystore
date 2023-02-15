<?php
namespace Apiv2\Controller;

class SmActController extends ApiController {
    
    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'day_act.txt');
    }
    
    
    private function response($data = 'success', $code = 0) 
    {
        
        if (!is_array($data)) {
            $data = array(
                'message' => $data,
            );
        }
        
        
        if (empty($code)) {
            $return = array(
                'code' => 0,
                'data' => $data,
            );
        } else {
            $return = array(
                'code' => $code,
                'data' => $data,
            );
        }

        echo json_encode($return);
        exit;
    }


    // 我的活动信息
    public function my_act_info()
    {
        $this->check_account_token();
        
        $uid = $this->_uid;
        
        $time = time();
        $start = strtotime(date('Y-m-d') . '00:00:00');
        $end = $start + 3600 * 24;
        
        
        // 找出中奖信息
        $act = M('act_product_log')->where(array(
            'create_time' => array(
                array('egt', $start),
                array('lt', $end)
            ),
        ))->find();
        
        // 未中奖-异常
        if (empty($act)) {
            $data = array(
                'status' => 1,
                'msg' => 'success',
                'data' => array(
                    'select' => 0,
                    'info' => (object)array(),
                ),
            );
            
            echo json_encode($data);
            exit;
        }        
        
        
        
        
        
        // 是否已中奖
        $one = M('act_product_user')->where(array(
            'uid' => $uid,
            'lid' => $act['id'],
        ))->find();
        
        
        // 未中奖
        if (empty($one)) {
            $data = array(
                'status' => 1,
                'msg' => 'success',
                'data' => array(
                    'select' => 0,
                    'info' => (object)array(),
                ),
            );
            
            echo json_encode($data);
            exit;
            //$this->return_data(0, '', '未中奖'); 
        }
        
        


        // 上次中奖商品名
        if (empty($act['act_product'])) {
            $act_product_name = '';
        } else {
            $select_p = M('goods')->where(array(
                'id' => $act['act_product'],
            ))->find();
            
            if (!empty($select_p['title'])) {
                $act_product_name = $select_p['title'];
            } else {
                $act_product_name = '';
            }
            
        }
        
        
        $act_info = array(
            'money' => $one['money'], // 中奖值
            'act_num' => $act['act_num'], // 中奖人数
            'act_val' => $act['money'], // 奖金总额
            'act_product' => $act_product_name, // 中奖商品
        );
        
        
        
        
        // 是否已请求
        $have = M('act_product_request')->where(array(
            'uid' => $uid,
            'create_time' => array(
                array('egt', $start),
                array('lt', $end)
            ),            
        ))->find();
        
        
        // 中奖已请求
        if (!empty($have)) {
            $data = array(
                'status' => 1,
                'msg' => 'success',
                'data' => array(
                    'select' => 2,
                    'info' => $act_info,
                ),
            );
            
            echo json_encode($data);
            exit;            
            
            
        // 中奖未请求
        } else {
            
            M('act_product_request')->add(array(
                'uid' => $uid,
                'lid' => $one['lid'],
                'create_time' => $time,
            ));
            
            
            
            $data = array(
                'status' => 1,
                'msg' => 'success',
                'data' => array(
                    'select' => 1,
                    'info' => $act_info,
                ),
            );
            
            echo json_encode($data);
            exit;                       
            
        }
        
        
        
        
        


        
        
        
        
        
        
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
    
    
    
    
    // 分配每日活动奖
    public function give_act_award()
    {
        
        /*
        
        1.获取一个商品
        2.获取前一天购买该商品的所有用户
        
        3.获取活动奖金额度，计算出每位中奖用户应得金额
        
        4.生成日志记录
        
        5.向用户余额写入指定金额
        
        6.向用户发送中奖通知
        
        
        */

        
       
        
        
        // 获取活动配置
        $config = $this->getConfig();
        

        $stime = strtotime(date('Y-m-d'));        
        
        // 活动结束时间
        $etime = $stime;
        
        // 活动开始时间
        $stime = $stime - (3600 * 24);
        
        // 当前时间
        $time = time();
        
        
        // 活动是否开启
        if (empty($config['is_open'])) {
            $this->response('活动未开启', 10010);
        }


        
        // 活动是否已处理
        $sql_have = "select id from hii_act_product_log where act_time >= {$stime};";
        $have = M()->query($sql_have);
        if (!empty($have)) {
            $this->response('当天抽奖已经完成', 10020);
        }        
        
        
        
        // 获取一个商品
        $pid = $this->getProduct();
        
        if (empty($pid)) {
            $this->response('获取抽奖商品异常', 10030);
        }
        
        // 哪个商品中奖
        //$this->response($pid);
        $this->debugs($pid);
        
        // 获取前一天购买该商品的所有用户
        /*
        $users_sql = "select o.id,o.order_sn,o.uid,o.create_time,o.store_id,d.d_id,count(*) as num  
        from hii_order o 
        left join hii_order_detail d
        on o.order_sn = d.order_sn 
        where d.d_id = {$pid}
        and o.create_time > 0 and o.create_time < 1524585600        
        and o.type = 'store' and o.pay_status = 2 and o.status = 5 and o.uid != 0
        group by o.uid;";
        */
        
        $users_sql = "select o.id,o.order_sn,o.uid,o.create_time,o.store_id,d.d_id,count(*) as num  
        from hii_order o 
        left join hii_order_detail d
        on o.order_sn = d.order_sn 
        where d.d_id = {$pid}
        and o.create_time > {$stime} and o.create_time < {$etime}        
        and o.type = 'store' and o.pay_status = 2 and o.status = 5 and o.uid != 0
        group by o.uid;";    
        
        
        $users = M()->query($users_sql);
        
        if (empty($users)) {
            $users = array();
        
        // 如果有中奖用户，需保证每个用户的余额信息都存在
        } else {
            $this->addUserWallet($users);
        }
        
        // 哪些用户中奖
        $this->debugs($users);
        
        //$this->response($users);        
        
        
        // 中奖用户数
        $count = count($users);
        
        // 奖金额度
        $money = $config['money'];
        
        // 每位中奖用户应得金额
        if ($count > 0) {
            $price = round($money / $count, 2);
        } else {
            $price = 0;
        }
        
        $act_log = array(
            'title' => $config['title'],
            'info' => $config['info'],
            'remark' => $config['remark'],
            'money' => $config['money'],
            'products' => $config['products'],
            'act_time' => $stime, //活动时间
            'act_product' => $pid, //中奖商品
            'act_num' => $count, //中奖数量
            'act_val' => $price, //中奖金额
            'create_time' => $time,
        );
        
        
        // 每日活动记录
        $log_id = M('act_product_log')->add($act_log);
        
        
        // 中奖用户记录
        $lucky_user = array(
            'lid' => $log_id, // 批次ID
            'money' => $price, // 中奖金额
            'create_time' =>  $time, // 中奖时间
        );
        
        // 系统通知
        $notice = array(
            'type' => 'notice',
            'title' => '系统通知',
            'act_uid' => 0,
            'act_id' => 99999,
            //'act_id' => $coupon_id,
            'act_data' => '[]',
            'hid' => '',
            //'hash' => md5('活动优惠券' . $coupon_id . mt_rand(10000, 99999)),
            'is_read' => 0,
            'status' => 0,
            'create_time' => $time,
            'update_time' => $time,
        );        
        

        $lucky_users = array();
        $notices = array();

        // 中奖商品名
        $product_name = $this->getProductName($pid);
        
        // 中奖金额
        $price_show = number_format($price, 2, '.', '');
        
        // 处理中奖结果
        foreach ($users as $key => $user) {
            $lucky_users[] = array_merge($lucky_user, array(
                'uid' => $user['uid'],
                'order_sn' => $user['order_sn'],
                'order_time' => $user['create_time'],
            )); 
            
            $notices[] = array_merge($notice, array(
                'uid' => $user['uid'],
                'hash' => md5('每日活动' . $user['uid'] . mt_rand(10000, 99999)),
                'content' => '恭喜您，您于昨天(' . date('Ymd', $stime) . '期)购买的' . $product_name . '被抽中为幸运商品，现将'. $price_show .'元的金额作为奖品，发放到您的余额。',
            ));

            
            // 发放余额
            M('wallet')->where(array(
                'uid' => $user['uid'],
            ))->setInc('recharge_money', $price);
            
            // 余额发送记录
            M('wallet_log')->add(array(
                'uid' => $user['uid'],
                'money' => $price,
                'type' => 5,
                'action' => 'give',
                'action_sn' => $log_id,
                'msg' => '于' . date('Ymd', $stime) . '期购买的' . $product_name . '被抽中为幸运商品',
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            ));
            
        }        
        
        // 记录幸运用户
        if (!empty($lucky_users)) {
            $res = M('act_product_user')->addAll($lucky_users);   
        }

        // 发送通知
        if (!empty($notices)) {
            M('message_notice')->addAll($notices);
        }
        
        // 抽奖结束触发回调
        $this->act_callback(array('lid' => $log_id));  
        
        $response = array(
            'lucky_users' => $lucky_users,
            'notices' => array(
                'count' => count($notices),
                'one' => empty($notices[0]) ? array() : $notices[0],
            ),
            'config' => $config,
            'act_log' => $act_log,
        );
        
        
        $this->debugs($response);
        
        $this->response($response);        
    }
    
    // 获取活动配置
    private function getConfig()
    {
        $data = M('act_product_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => 0,
                'title' => '',
                'info' => '',
                'remark' => '',
                'money' => 0,
                'products' => '',
                'stime' => 0,
                'etime' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        return $data;        
    }
    
    
    // 获取一个商品
    private function getProduct()
    {
        $config = $this->getConfig();
        
        $products = $config['products'];
        
        $products = trim($products, ',');
        
        $p_arr = explode(',', $products);
        
        
        if (empty($p_arr)) {
            return 0;
        } else {
            $key = array_rand($p_arr, 1);
            
            if (!empty($p_arr[$key])) {
                return $p_arr[$key];
            } else {
                return 0;
            }
            
        }
        
    }
    

    private function getProduct2()
    {
        $config = $this->getConfig();
        
        $products = $config['products'];
        
        $products = trim($products, ',');
        
        $p_arr = explode(',', $products);
        
        
        if (empty($p_arr)) {
            return 0;
        } else {
            rsort($p_arr);
            
            $n = date('N');
            $n = intval($n);
            
            $d = (($n * $n) + 9) % 10;

            if (!empty($p_arr[$d])) {
                return $p_arr[$d];
            } else {
                $key = array_rand($p_arr, 1);
                
                if (!empty($p_arr[$key])) {
                    return $p_arr[$key];
                } else {
                    return 0;
                }
            }
            
        }
        
    }

    
    // 返回指定商品的名字
    private function getProductName($pid)
    {
        
        if (empty($pid)) {
            return '';
        }
        
        $product = M('goods')->where(array(
            'id' => $pid,
        ))->find();
        
        if (empty($product) || empty($product['title'])) {
            return '神秘商品';
        } else {
            return $product['title'];
        }
        
        
    }
    

    public function test_callback()
    {
        // 抽奖结束触发回调
        $response = $this->act_callback(array('lid' => 69));  
        
        $this->response($response);
    }


    private function act_callback($data = array())
    {
        
        $isTest = $this->isTest();
        
        if ($isTest) {
            $domain = 'http://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'http://v.imzhaike.com/Apiv2';
        }
        
        $url = '/SmWxtpl/send_smg';
        $url = $domain . $url;
        
        
        
        // xydebug($url, 'wxtpl.txt');
        
        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
        
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /*
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        */
        $result = curl_exec($ch);
        
        $result = json_decode($result, true);
        return $result;
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
    
    // 获取前一天购买该商品的所有用户
    private function getUsers($pid)
    {
        /*
        select * 
        from hii_order o 
        left join hii_order_detail d
        on o.order_sn = d.order_sn 
        where d.d_id = {$pid} 
        and o.create_time > {$stime} and o.create_time < {$etime} and o.uid != 0
        

        select o.id,o.order_sn,o.uid,o.create_time,o.store_id,d.d_id,count(*) as num  
        from hii_order o 
        left join hii_order_detail d
        on o.order_sn = d.order_sn 
        where d.d_id = {$pid}
        and o.create_time > {$stime} and o.create_time < {$etime}        
        and o.type = 'store' and o.pay_status = 2 and o.status = 5 and o.uid != 0
        group by o.uid;


        select o.id,o.order_sn,o.uid,o.create_time,o.store_id,d.d_id,count(*) as num  
        from hii_order o 
        left join hii_order_detail d
        on o.order_sn = d.order_sn 
        where d.d_id = 499 
        and o.type = 'store' and o.pay_status = 2 and o.status = 5 and o.uid != 0
        group by o.uid;
        //and o.create_time > 1522711400 and o.create_time < 1523289600 and o.uid != 0;
        */


        
    }
    
    
    

    

}
