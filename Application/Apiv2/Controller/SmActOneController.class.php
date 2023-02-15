<?php
namespace Apiv2\Controller;

class SmActOneController extends ApiController {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'day_act_one.txt');
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




    // 根据活动ID生成每日前10消费排行
    public function act_user_10()
    {



        // 获取活动ID参数
        $id = empty($_POST['id']) ? 0 : $_POST['id'];
        $id = intval($id);

        // 活动ID参数不合法
        if (empty($id)) {
            $this->response('id is empty', 10010);
        }

        // 查询活动数据
        $one = M('act2_log')->where(array(
            'id' => $id,
        ))->find();

        // 活动ID未生成,或不合法
        if (empty($one) || empty($one['act_time'])) {
            $this->response('act2_log is empty', 10020);
        }

        // 查询当前活动排名
        $one_10 = M('act2_10')->where(array(
            'lid' => $id,
        ))->find();

        // 当前活动排名已生成，不需再处理
        if (!empty($one_10)) {
            $this->response('act2_10 done!', 10020);
        }

        // 当前时间
        $time = time();


        // 活动开始时间
        $stime = $one['act_time'];

        // 活动结束时间
        $etime = $stime + 3600 * 24;

        // 活动ID
        $log_id = $one['id'];


        // 消费最多No.10用户
        $users_sql = "select o.uid, sum(o.money) moneys 
from hii_order o 
where o.create_time >= {$stime} and o.create_time < {$etime} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 and o.uid != 0 
and o.uid not in (select distinct uid from hii_act2_black where uid != 0) 
group by uid 
order by moneys desc 
limit 0,10;";



        $users = M()->query($users_sql);

        if (empty($users)) {
            $users = array();
        }


        // 前10排名的用户
        $user10 = array();

        foreach ($users as $key => $val) {
            // 查找最高消费的门店ID
            $store_sql = "select o.uid, o.store_id, sum(o.money) as moneys 
            from hii_order o  
            where o.uid = {$val['uid']} 
            and o.create_time >= {$stime} and o.create_time < {$etime} 
            and o.status = 5 and o.type = 'store' and o.pay_status = 2 
            group by o.store_id order by moneys desc limit 0,1";


            $store_info = M()->query($store_sql);
            $store_id = empty($store_info[0]['store_id']) ? 0 : $store_info[0]['store_id'];

            $user10[] = array(
                'lid' => $log_id,
                'act_price' => $val['moneys'],
                'act_user' => $val['uid'],
                'act_store' => $store_id,
                'create_time' => $time,
            );
        }

        // 记录前10名的用户
        if (!empty($user10)) {
            $res = M('act2_10')->addAll($user10);
        }



        $this->debugs($user10);

        $this->response($user10);
    }









    // 根据活动ID生成每日前10消费排行
    public function act_user_10_now()
    {


        $stime = strtotime(date('Y-m-d'));

        // 活动结束时间
        $etime = $stime + (3600 * 24);


        // 消费最多No.10用户
        $users_sql = "select o.uid, sum(o.money) moneys 
from hii_order o 
where o.create_time >= {$stime} and o.create_time < {$etime} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 and o.uid != 0 
and o.uid not in (select distinct uid from hii_act2_black where uid != 0) 
group by uid 
order by moneys desc 
limit 0,10;";



        $users = M()->query($users_sql);

        if (empty($users)) {
            $users = array();
        }


        // 前10排名的用户
        $user10 = array();

        foreach ($users as $key => $val) {
            // 查找最高消费的门店ID
            /*
            $store_sql = "select o.uid, o.store_id, sum(o.money) as moneys 
            from hii_order o  
            where o.uid = {$val['uid']} 
            and o.create_time >= {$stime} and o.create_time < {$etime} 
            and o.status = 5 and o.type = 'store' and o.pay_status = 2 
            group by o.store_id order by moneys desc limit 0,1";            
            */

            $store_sql = "select t.uid, m.nickname, um.mobile, t.store_id, t.moneys, s.title as store,q.title as shequ from 
(select o.uid, o.store_id, sum(o.money) as moneys 
from hii_order o  
where o.uid = {$val['uid']} 
and o.create_time > {$stime} and o.create_time < {$etime} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 
group by o.store_id order by moneys desc limit 0,1) t 
left join hii_member m on t.uid = m.uid 
left join hii_ucenter_member um on t.uid = um.id 
left join hii_store s on t.store_id = s.id 
left join hii_shequ q on s.shequ_id = q.id;";


            $store_info = M()->query($store_sql);
            if (empty($store_info[0])) {
                $store_one = array();
            } else {
                $store_one = $store_info[0];
            }

            $user10[] = $store_one;
        }


        $this->response($user10);
    }




    // 根据活动ID生成每日前10消费排行
    public function prize_user_num30()
    {



        // 获取活动ID参数
        $id = empty($_POST['id']) ? 0 : $_POST['id'];
        $id = intval($id);

        // 活动ID参数不合法
        if (empty($id)) {
            $this->response('id is empty', 10010);
        }

        // 查询活动数据
        $one = M('prize_day')->where(array(
            'id' => $id,
        ))->find();

        // 活动ID未生成,或不合法
        if (empty($one) || empty($one['act_time'])) {
            $this->response('prize_day is empty', 10020);
        }


        if (!empty($one['status'])) {
            $this->response('prize_number done(01)!', 10020);
        }

        // 查询当前活动排名
        $one_30 = M('prize_number')->where(array(
            'lid' => $id,
        ))->find();





        // 当前时间
        $time = time();


        // 活动开始时间
        $stime = $one['act_time'];

        // 活动结束时间
        $etime = $stime + 3600 * 24;



        // 当天数据统计

        // 赠送金额
        $give_money = M('prize_order')->where(array(
            'status' => 1,
            'create_time' => array(array('egt', $stime), array('lt', $etime)),
        ))->sum('money');

        if (empty($give_money)) {
            $give_money = 0;
        }

        // 充值金额
        $sql_recharge = "select sum(r.money) as rmoney
from hii_prize_order p
left join hii_recharge_order r on p.oid = r.id
where p.create_time >= {$stime} and p.create_time < {$etime};";
        $data_recharge = M()->query($sql_recharge);
        if (empty($data_recharge[0]['rmoney'])) {
            $recharge_money = 0;
        } else {
            $recharge_money = $data_recharge[0]['rmoney'];
        }


        // 人次
        $prize_count = M('prize_order')->where(array(
            'status' => 1,
            'create_time' => array(array('egt', $stime), array('lt', $etime)),
        ))->count();

        // 人数
        $user_sql = "select count(*) as c from (select * from hii_prize_order where create_time >= {$stime} and create_time < {$etime} group by uid) t;";
        $user_data = M()->query($user_sql);
        if (empty($user_data[0]['c'])) {
            $user_count = 0;
        } else {
            $user_count = $user_data[0]['c'];
        }


        // 员工人数
        $work_sql = "select count(*) as c
from (select * from hii_prize_order
where uid in (select distinct uid from hii_prize_work where uid != 0)
and create_time >= {$stime} and create_time < {$etime}
group by uid) t;";
        $work_data = M()->query($work_sql);
        if (empty($work_data[0]['c'])) {
            $work_count = 0;
        } else {
            $work_count = $work_data[0]['c'];
        }



        // 抽奖次数
        $all_count = M('prize_order')->where(array(
            'status' => 1,
            'create_time' => array(array('egt', $stime), array('lt', $etime)),
        ))->sum('num');
        if (empty($all_count)) {
            $all_count = 0;
        }



        M('prize_day')->where(array('id' => $one['id']))->save(array(
            'give' => $give_money, // 赠送金额
            'money' => $recharge_money, // 充值金额
            'recharge_num' => $prize_count, // 人次
            'all_num' => $user_count, // 人数
            'work_num' => $work_count, // 员工人数
            'prize_num' => $all_count, // 抽奖次数
            'status' => 1,
        ));


        // 当前活动排名已生成，不需再处理
        if (!empty($one_30)) {
            $this->response('prize_number done(02)!', 10020);
        }


        // 活动ID
        $log_id = $one['id'];


        // 消费最多No.10用户
        $users_sql = "select u.*, w.username,
case when w.mobile is null then 0 else 1 end as work
from hii_prize_user u
left join hii_prize_work w on w.uid = u.uid
where u.uid != 0 and u.create_time >= {$stime} and u.create_time < {$etime}
order by u.money desc, u.id asc limit 30;";


        $users = M()->query($users_sql);

        if (empty($users)) {
            $users = array();
        }


        // 前10排名的用户
        $user10 = array();

        foreach ($users as $key => $val) {
            // 查找最高消费的门店ID
            $store_sql = "select o.uid, o.store_id, sum(o.money) as moneys
            from hii_order o
            where o.uid = {$val['uid']}
            and o.status = 5 and o.type = 'store' and o.pay_status = 2
            group by o.store_id order by moneys desc limit 0,1";


            $store_info = M()->query($store_sql);
            $store_id = empty($store_info[0]['store_id']) ? 0 : $store_info[0]['store_id'];

            $user30[] = array(
                'lid' => $log_id,
                'give' => $val['money'],
                'uid' => $val['uid'],
                'sid' => $store_id,
                'work' => $val['work'],
                'pid' => $val['id'],
                'prize_time' => $val['create_time'],
                'create_time' => $time,
            );
        }

        // 记录前10名的用户
        if (!empty($user30)) {
            $res = M('prize_number')->addAll($user30);
        }



        $this->debugs($user30);

        $this->response($user30);
    }



    // 根据活动ID生成每日前10消费排行
    public function prize_user_num30_now()
    {

        if ($_POST['day'] == 'today') {
            $stime = strtotime(date('Y-m-d'));

            // 活动结束时间
            $etime = $stime + (3600 * 24);
        } else {
            $etime = strtotime(date('Y-m-d'));

            // 活动结束时间
            $stime = $etime - (3600 * 24);
        }



        // 消费最多No.10用户
        $users_sql = "select * from hii_prize_user
where create_time >= {$stime} and create_time < {$etime}
order by money desc limit 30;";


        $users = M()->query($users_sql);

        if (empty($users)) {
            $users = array();
        }


        // 前10排名的用户
        $user10 = array();

        foreach ($users as $key => $val) {
            // 查找最高消费的门店ID
            /*
            $store_sql = "select o.uid, o.store_id, sum(o.money) as moneys
            from hii_order o
            where o.uid = {$val['uid']}
            and o.create_time >= {$stime} and o.create_time < {$etime}
            and o.status = 5 and o.type = 'store' and o.pay_status = 2
            group by o.store_id order by moneys desc limit 0,1";
            */

            $store_sql = "select t.uid, m.nickname, um.mobile, t.store_id, t.moneys, s.title as store,q.title as shequ from
(select o.uid, o.store_id, sum(o.money) as moneys
from hii_order o
where o.uid = {$val['uid']}
and o.status = 5 and o.type = 'store' and o.pay_status = 2
group by o.store_id order by moneys desc limit 0,1) t
left join hii_member m on t.uid = m.uid
left join hii_ucenter_member um on t.uid = um.id
left join hii_store s on t.store_id = s.id
left join hii_shequ q on s.shequ_id = q.id;";


            $store_info = M()->query($store_sql);
            if (empty($store_info[0])) {



                $sql = "select m.uid, m.nickname, um.mobile from 
hii_member m 
left join hii_ucenter_member um on m.uid = um.id 
where m.uid = {$val['uid']};";
                $store_info = M()->query($sql);
                if (empty($store_info[0])) {
                    $store_one = array();
                }


            } else {
                $store_one = $store_info[0];
            }


            $store_one = array_merge($store_one, $val);

            $user10[] = $store_one;
        }


        $this->response($user10);
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
            $domain = 'https://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'https://v.imzhaike.com/Apiv2';
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
