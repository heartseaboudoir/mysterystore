<?php
namespace Apiv2\Controller;

class SmActNewController extends ApiController {
    
    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'day_act_new.txt');
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
    public function give666_old()
    {
        

        $stime = strtotime(date('Y-m-d'));        
        
        // 活动结束时间
        $etime = $stime;
        
        // 活动开始时间
        $stime = $stime - (3600 * 24);
        
        // 当前时间
        $time = time();
        
        

        
        // 活动是否已处理
        $sql_have = "select id from hii_act2_log where act_time >= {$stime};";
        $have = M()->query($sql_have);
        if (!empty($have)) {
            $this->response('当天抽奖已经完成', 10020);
        }        
        
        
   
        // 消费最多的用户
        $users_sql = "select o.uid, sum(o.money) moneys 
from hii_order o 
where o.create_time > {$stime} and o.create_time < {$etime} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 and o.uid != 0 
and o.uid not in (select distinct uid from hii_act2_black where uid != 0) 
group by uid 
order by moneys desc 
limit 0,1;";   


        
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
        
        
        if (count($users) == 1 && !empty($users[0])) {
            // 中奖用户数
            $user = $users[0];
            
            // 奖金额度
            $money = 666;
            
            //用户销售额
            $act_price = $user['moneys'];
            
            //用户UID
            $act_user = $user['uid'];
            
            // 中奖用户应得金额
            $price = 666;
          
            
            
        } else {
            $user = array();
            
            // 奖金额度
            $money = 666;
            
            //用户销售额
            $act_price = 0;
            
            //用户UID
            $act_user = 0;
            
            // 中奖用户应得金额
            $price = 0;            
            
            
        }
        

        
        $act_log = array(
            'info' => '每日消费最多的用户获取666奖金',
            'money' => $money, // 活动金额
            'act_price' => $act_price, //用户销售额
            'act_user' => $act_user, //用户UID
            'act_val' => $price, //中奖金额
            'act_time' => $stime, //活动时间
            'create_time' => $time,
        );
        
        
        // 每日活动记录
        $log_id = M('act2_log')->add($act_log);
        
        
        // 如果有用户中奖
        if (!empty($user)) {
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
            
            // 发送通知
            $notices = array();
            $price_show = number_format($price, 2, '.', '');
            $notices[] = array_merge($notice, array(
                'uid' => $user['uid'],
                'hash' => md5('每日活动' . $user['uid'] . mt_rand(10000, 99999)),
                'content' => '恭喜您，您于昨天(' . date('Ymd', $stime) . '期)在神秘商店消费第一，现将'. $price_show .'元的金额作为奖品，发放到您的余额。',
            ));
            if (!empty($notices)) {
                M('message_notice')->addAll($notices);
            }
            
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
                'action_sn' => 'one_' . $log_id,
                'msg' => '于' . date('Ymd', $stime) . '期在神秘商店消费第一',
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            ));            
            
        } else {
            $notices = array();
        }
        




        
        // 抽奖结束触发回调
        $this->act_callback(array('lid' => $log_id));  
        
        $response = array(
            'users' => $users,
            'notices' => array(
                'count' => count($notices),
                'one' => empty($notices[0]) ? array() : $notices[0],
            ),
            'act_log' => $act_log,
        );
        
        
        $this->debugs($response);
        
        $this->response($response);        
    }    
    
    
    // 分配每日活动奖
    public function give666()
    {
        

        $stime = strtotime(date('Y-m-d'));        
        
        // 活动结束时间
        $etime = $stime;
        
        // 活动开始时间
        $stime = $stime - (3600 * 24);
        
        // 当前时间
        $time = time();
        
        

        
        // 活动是否已处理
        $sql_have = "select id from hii_act2_log where act_time >= {$stime};";
        $have = M()->query($sql_have);
        if (!empty($have)) {
            $this->response('当天抽奖已经完成', 10020);
        }        
        
        
   
        // 消费最多的用户
        $users_sql = "select o.uid, sum(o.money) moneys 
from hii_order o 
where o.create_time > {$stime} and o.create_time < {$etime} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 and o.uid != 0 
and o.uid not in (select distinct uid from hii_act2_black where uid != 0) 
group by uid 
order by moneys desc 
limit 0,1;";   


        
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
        
        
        if (count($users) == 1 && !empty($users[0])) {
            // 中奖用户数
            $user = $users[0];
            
            // 奖金额度
            $money = 666;
            
            //用户销售额
            $act_price = $user['moneys'];
            
            //用户UID
            $act_user = $user['uid'];
            
            // 中奖用户应得金额
            $price = 666;
          
            
            
        } else {
            $user = array();
            
            // 奖金额度
            $money = 666;
            
            //用户销售额
            $act_price = 0;
            
            //用户UID
            $act_user = 0;
            
            // 中奖用户应得金额
            $price = 0;            
            
            
        }
        

        
        $act_log = array(
            'info' => '每日消费最多的用户获取666奖金',
            'money' => $money, // 活动金额
            'act_price' => $act_price, //用户销售额
            'act_user' => $act_user, //用户UID
            'act_val' => $price, //中奖金额
            'act_time' => $stime, //活动时间
            'create_time' => $time,
        );
        
        
        // 每日活动记录
        $log_id = M('act2_log')->add($act_log);
        

        
        $response = array(
            'users' => $users,
            'act_log' => $act_log,
            'log_id' => $log_id,
        );
        
        
        $this->debugs($response);
        
        $this->response($response);        
    }
    

    
    
    /**
     * 选定用户获取奖品 
     */
    public function select_num()
    {
        // 获取活动ID参数
        $id = empty($_POST['id']) ? 0 : $_POST['id'];
        $id = intval($id);
        
        // 活动ID参数不合法
        if (empty($id)) {
            $this->response('参数id: 错误', 10010);
        }

        $act2_one = M('act2_10')->where(array(
            'id' => $id,
        ))->find();
        
        //print_r($act2_one);exit;
        
        //$this->error($act2_one);
        // 用户是否存在
        if (empty($act2_one) || empty($act2_one['lid']) || empty($act2_one['act_user'])) {
            $this->response('该用户不存在', 10020);
        }
        
        // 当次活动是否已发奖
        $one = M('act2_log')->where(array(
            'id' => $act2_one['lid'],
        ))->find();
        
        if (empty($one) || !empty($one['status'])) {
            $this->response('当次活动不存在或已发放奖品', 10030);
        }


        // 确认中奖用户
        M('act2_log')->where(array(
            'id' => $act2_one['lid'],
        ))->save(array(
            'act_price' => $act2_one['act_price'],
            'act_user' => $act2_one['act_user'],
            'act_store' => $act2_one['act_store'],
            'status' => 1,
        ));
        
        
        // 用户
        $user = array(
            'uid' => $act2_one['act_user'],
        );
        
        // 金额
        $price = 666;
        
        // 活动时间
        $stime = $one['act_time'];
        
        // 活动ID
        $log_id = $one['id'];
        
        // 当前时间
        $time = time();
        
        // 生成用户余额信息
        $users = array(array($user));
        $this->addUserWallet($users);
        
        // 如果有用户中奖
        if (!empty($user)) {
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
            
            // 发送通知
            $notices = array();
            $price_show = number_format($price, 2, '.', '');
            $notices[] = array_merge($notice, array(
                'uid' => $user['uid'],
                'hash' => md5('每日活动' . $user['uid'] . mt_rand(10000, 99999)),
                'content' => '恭喜您，您于昨天(' . date('Ymd', $stime) . '期)在神秘商店消费第一，现将'. $price_show .'元的金额作为奖品，发放到您的余额。',
            ));
            if (!empty($notices)) {
                M('message_notice')->addAll($notices);
            }
            
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
                'action_sn' => 'one_' . $log_id,
                'msg' => '于' . date('Ymd', $stime) . '期在神秘商店消费第一',
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            ));            
            
        } else {
            $notices = array();
        }
        




        
        // 抽奖结束触发回调
        $this->act_callback(array('lid' => $log_id));  
        
        $response = array(
            'users' => $users,
            'notices' => array(
                'count' => count($notices),
                'one' => empty($notices[0]) ? array() : $notices[0],
            ),
            'act_log' => $one,
        );
        
        
        $this->debugs($response);
        
        $this->response($response);             
    }



    

    public function test_callback()
    {
        // 抽奖结束触发回调
        $response = $this->act_callback(array('lid' => 2));  
        
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
        
        $url = '/SmWxtpl/send_smg_act2';
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
    

    
    
    

    

}
