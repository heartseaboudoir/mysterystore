<?php
namespace Apiv2\Controller;

class GiveWalletController extends ApiController {
    
    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'give_wallet.txt');
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
    
    private function checkRequest()
    {
        if ($_POST['check_str'] == 'jgfdgfhsghsdfgdgsd') {
            return true;
        } else {
            return false;
        }
    }
    
    

    
    
    
    // 中秋发放余额
    public function give_wallet()
    {
        $this->response('test...');
        
        $checked = $this->checkRequest();
        
        
        
        if (!$checked) {
            $this->response('check error');
        }
        
        /*
        
        1.找出用户，加上余额    

        update hii_wallet w,hii_give_user u
        set w.wallet = w.wallet + 50 
        where w.uid = u.uid;        

        4.生成日志记录
                
        6.向用户发送中奖通知
        
        
        */

        
        // 获取中奖用户
        $users = $this->getUsers();
        

        
        // $this->response($users);
        
        
        if (empty($users)) {
            $users = array();
        
        // 如果有中奖用户，需保证每个用户的余额信息都存在
        } else {
            $this->addUserWallet($users);
        }
        
        // 哪些用户中奖
        $this->debugs($users);
        
        //$this->response($users);        
        
        
        $time = time();
        
        // 系统通知
        $notice = array(
            'type' => 'notice',
            'title' => '系统通知',
            'act_uid' => 0,
            'act_id' => 99998,
            //'act_id' => $coupon_id,
            'act_data' => '[]',
            'hid' => '',
            //'hash' => md5('活动优惠券' . $coupon_id . mt_rand(10000, 99999)),
            'is_read' => 0,
            'status' => 0,
            'create_time' => $time,
            'update_time' => $time,
        );        

        $notices = array();


        
        
        $action_sn = 'zq_' . date('YmdHis') . mt_rand(1000, 9999);
        
        // 处理中奖结果
        foreach ($users as $key => $user) {
            
            // 中奖金额
            $user['price'] = 200;
            
            // 中奖金额-显示
            $price_show = number_format($user['price'], 2, '.', '');            
            
            
            $notices[] = array_merge($notice, array(
                'uid' => $user['uid'],
                'hash' => md5('中秋活动' . $user['uid'] . mt_rand(10000, 99999)),
                'content' => '中秋节快乐，现将' . $price_show . '元发放到您的余额。',
            ));

            
            // 发放余额
            M('wallet')->where(array(
                'uid' => $user['uid'],
            ))->setInc('recharge_money', $user['price']);
            
            // 余额发送记录
            M('wallet_log')->add(array(
                'uid' => $user['uid'],
                'money' => $user['price'],
                'type' => 5,
                'action' => 'give',
                'action_sn' => $action_sn,
                'msg' => '中秋节，赠送金额：' . $price_show . '元',
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            ));
            
        }        
        

        // 发送通知
        /*
        if (!empty($notices)) {
            M('message_notice')->addAll($notices);
        }
        */
        
        
        $response = array(
            'users' => count($users),
            'notices' => array(
                'count' => count($notices),
                'one' => empty($notices[0]) ? array() : $notices[0],
            ),
        );
        
        
        $this->debugs($response);
        
        $this->response($response);        
    }
    

    /**
     * 获取中奖用户
     */
    public function getUsers()
    {
          
        $sql = "select * from hii_zwallet where uid != 0 limit 200";
        
        
        $data = M()->query($sql);
        
        
        if (empty($data)) {
            $data = array();
        }
        
        return $data;
        
        
        /*
        return array(
            array(
                'uid' => 1313,
            ),
            array(
                'uid' => 6170,
            ),
        
        );
        */
        
    }

}
