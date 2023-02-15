<?php
namespace Apiv2\Controller;

class WorldCupController extends ApiController {
    
    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'world_cup.txt');
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
        
        $this->debugs($return);
        
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
        if ($_POST['check_str'] == 'dgsaihiorefhadhfah7') {
            return true;
        } else {
            return false;
        }
    }
    
    
    public function set_time_wc()
    {
        
        $stime = empty($_POST['stime']) ? 0 : $_POST['stime'];
        $etime = empty($_POST['etime']) ? 0 : $_POST['etime'];
        $bstime = empty($_POST['bstime']) ? 0 : $_POST['bstime'];
        $betime = empty($_POST['betime']) ? 0 : $_POST['betime'];
        
        if (empty($stime) || empty($etime) || empty($bstime) || empty($betime)) {
            $this->response('时间参数不合法', 10010);
        }
        
        
        M('wc_config')->where(array('id' => 1))->save(array(
            'stime' => $stime,
            'etime' => $etime,
            'bstime' => $bstime,
            'betime' => $betime,
        ));
        
        $this->response('处理成功');
    }
    
    public function get_time_wc()
    {
        $data = M('wc_config')->where(array('id' => 1))->find();
        
        if (empty($data)) {
            $data = array();
        }
        
        
        $this->response($data);
        
    }
    
    
    
    // 分配每日活动奖
    public function give_wc()
    {
        
        
        $checked = $this->checkRequest();
        
        
        
        if (!$checked) {
            $this->response('check error');
        }
        
        /*
        
        1.获取奖池
        
        2.获取中奖投注数,计算单注奖金
        
        
        
        3.获取每位中奖用户及中奖注数，计算出每位中奖用户应得金额
        
        4.生成日志记录
        
        5.向用户余额写入指定金额
        
        6.向用户发送中奖通知
        
        
        */

        
       
        
        
        // 获取活动配置
        $config = $this->getConfig();
        
        // 获取奖池金额
        $prices = $this->getPrices();

        
        // 获取中奖投注数
        $luckNum = $this->getLuckNum();
        
        // 获取中奖球队
        $luckTeams = $this->getLuckTeam();
        
        $luckTeam = $luckTeams[0];
        if (!in_array($luckTeam, array(1, 2))) {
            $this->response('team not found');
        }
        
        // 获取中奖球队名
        $luckTeamName = $luckTeams[1];

        // 获取单注奖金
        if ($luckNum > 0) {
            $price_one = round($prices / $luckNum, 2);
        } else {
            $price_one = 0;
        }
        

        if ($luckTeam == 1) {
            $team_str = 'a';
            $exp = "`a`*{$price_one}";
        } else {
            $team_str = 'b';
            $exp = "`b`*{$price_one}";
        }

        // 计算用户资金
        M('wc_bit_record')->where('id>0')->save(array(
            'price' => array('exp', $exp),
        ));
        
        
        
        // 找出需要发放余额的用户
        $users = M('wc_bit_record')->where(array(
            'price' => array('gt', 0),
        ))->select();
        
        
        
        
        
        $this->debugs(array(
            'config' => $config,
            'prices' => $prices,
            'luckTeams' => $luckTeams,
            'luckTeam' => $luckTeam,
            'luckTeamName' => $luckTeamName,
            'luckNum' => $luckNum,
            'price_one' => $price_one,
            'users' => $users,
        ));        
        
        
        
        
        
        
        
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

        
        $price_one_show = number_format($price_one, 2, '.', '');   
        
        
        $action_sn = 'wc_' . date('YmdHis') . mt_rand(1000, 9999);
        
        // 处理中奖结果
        foreach ($users as $key => $user) {
            
            
            $num = $user[$team_str];
            
            // 中奖金额
            $price_show = number_format($user['price'], 2, '.', '');            
            
            
            $notices[] = array_merge($notice, array(
                'uid' => $user['uid'],
                'hash' => md5('世界杯活动' . $user['uid'] . mt_rand(10000, 99999)),
                'content' => '恭喜您，您在参与的神秘商店世界杯活动中，投注的' . $luckTeamName . '（ ' . $num . '注）获得冠军，现将' . $price_show . '元（' . $price_one_show . ' * ' . $num . '）的金额作为奖品，发放到您的余额。',
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
                'msg' => '投注的' . $luckTeamName . '（ ' . $num . '注）获得冠军，，获得' . $price_show . '元（' . $price_one_show . ' * ' . $num . '）的金额',
                'is_lock' => 0,
                'unlock_time' => 0,
                'create_time' => $time,
                'update_time' => $time,
            ));
            
        }        
        

        // 发送通知
        if (!empty($notices)) {
            M('message_notice')->addAll($notices);
        }
        
        
        
        $response = array(
            'users' => count($users),
            'notices' => array(
                'count' => count($notices),
                'one' => empty($notices[0]) ? array() : $notices[0],
            ),
            'config' => $config,
        );
        
        
        $this->debugs($response);
        
        $this->response($response);        
    }
    
    // 获取活动配置
    private function getConfig()
    {
        $data = M('wc_config')->find();
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
                'bstime' => 0,
                'betime' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        return $data;        
    }
    
    
    // 获取奖池金额
    private function getPrices()
    {
        $config = $this->getConfig();
        
        //return $config;
        
        $products = $config['products'];
        
        /*
        $sql = "select sum(od.price * od.num) as prices   
        from hii_order_detail od 
        left join hii_order o 
        on o.order_sn = od.order_sn 
        where o.create_time >= UNIX_TIMESTAMP('2018-1-1') and o.create_time < UNIX_TIMESTAMP('2018-7-16 02:00:00') 
        and o.status = 5 and o.type = 'store' and o.pay_status = 2 
        and od.d_id in (2901,2900,2899,2898,2893,2892,2890,2889,2885,499);";
        */
        
        $sql = "select sum(od.price * od.num) as prices   
        from hii_order_detail od 
        left join hii_order o 
        on o.order_sn = od.order_sn 
        where o.create_time >= {$config['stime']} and o.create_time < {$config['etime']} 
        and o.status = 5 and o.type = 'store' and o.pay_status = 2 
        and od.d_id in ({$products});";
        
        // return $sql;
        
        $data = M()->query($sql);
        
        // return $data;
        
        if (empty($data[0]['prices'])) {
            $prices = 0;
        } else {
            $prices = $data[0]['prices'];
        }
        
        $prices = round($prices / 10, 2);
        
        return $prices;
    }
    
    // 获取中奖球队
    public function getLuckTeam()
    {
        $data = M('wc_team')->where(array(
            'wc_get' => 1,
        ))->find();
        
        if (empty($data['wc_id']) || !in_array($data['wc_id'], array(1, 2))) {
            return array(0, 'unknow');
        } else {
            return array($data['wc_id'], $data['wc_name']);
        }
    }
    
    
    
    // 获取中奖注数
    public function getLuckNum()
    {
        
        $num1 = M('wc_bit_record')->sum('a');
        $num2 = M('wc_bit_record')->sum('b');
        
        if (empty($num1)) {
            $num1 = 0;
        }
        
        if (empty($num2)) {
            $num2 = 0;
        }
        
        
        
        $teams = $this->getLuckTeam();
        
        if (!empty($teams[0]) && in_array($teams[0], array(1, 2))) {
            $team = $teams[0];
        } else {
            $team = 0;
        }
        
        
        if ($team == 1) {
            return $num1;
        } elseif ($team == 2) {
            return $num2;
        } else {
            return 0;
        }
        
    }
    
    
    
    
    public function test_wc()
    {
        //$data = $this->getPrices();
        
        $num = 1;
        
        M('wc_bit_record')->where(array('id' => 2))->setInc('alls', $num);
        $data = M('wc_bit_record')->where(array('id' => 2))->setInc('can', $num);        
        
        
        
        
        $this->response(array(
            'data' => $data,
        ));
        
        
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
