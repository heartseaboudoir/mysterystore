<?php
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

/**
 * 功能：内容管理
 *
 */
class ActPrizeAdminController extends AdminController {


    public function __construct() {
        parent::__construct();
    }



    /**
     * 每日中奖排行
     */
    public function prize_number()
    {

        // 获取活动ID参数
        $id = empty($_GET['id']) ? 0 : $_GET['id'];
        $id = intval($id);

        // 活动ID参数不合法
        if (empty($id)) {
            $this->error('参数id: 错误');
        }

        // 是否已经统计当前的数据
        $day = M('prize_day')->where(array(
            'id' => $id,
        ))->find();

        if (empty($day)) {
            $this->error('不存在的记录');
        }

        // 设置排名信息
        if (empty($day['status'])) {
            $this->setNum50($id);
        }


        Cookie('__forward__',$_SERVER['REQUEST_URI']);





        $sql = "select d.id as did, n.*, m.nickname,um.mobile,s.title as store,q.title as shequ
from hii_prize_day d
inner join hii_prize_number n on d.id = n.lid
left join hii_member m on n.uid = m.uid
left join hii_ucenter_member um on n.uid = um.id
left join hii_store s on n.sid = s.id
left join hii_shequ q on s.shequ_id = q.id
where d.id = {$id}
order by n.give desc;";


        // echo $sql;exit;

        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $this->assign('list', $list);


        $this->meta_title = '每日充值中奖排行榜';
        $this->display('prize_number');
    }



    /**
     * 每日中奖记录
     */
    public function prize_day()
    {
        // 设置门店信息
        //$this->setNoStore();

        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $day = date('Y-m-d');
        $time = strtotime($day);

        $Model = M('prize_day');
        $count = $Model->where("act_time < {$time}")->count();
        $pcount = 15;
        $Page = new \Think\Page($count, $pcount);
        /*
        $sql = "select l.*
        from hii_act2_log l
        order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        */

        $sql = "select * from hii_prize_day where act_time < {$time} order by act_time desc limit {$Page->firstRow}, {$Page->listRows}";


        // echo $sql;exit;

        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);


        $this->meta_title = '每日充值活动';
        $this->display('prize_day');
    }




    /**
     * 活动订单日志
     */
    public function logs()
    {

        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        // 活动开始
        $config = $this->getPrizeConfig();




        // 活动不存在
        if (empty($config['is_open'])) {
            $is_open = false;
        } else {
            $is_open = true;
        }



        // 活动数据统计

        // 赠送金额
        $give_money = M('prize_order')->where(array(
            'status' => 1,
        ))->sum('money');

        if (empty($give_money)) {
            $give_money = 0;
        }

        // 充值金额
        $sql_recharge = "select sum(r.money) as rmoney from hii_prize_order p left join hii_recharge_order r on p.oid = r.id;";
        $data_recharge = M()->query($sql_recharge);
        if (empty($data_recharge[0]['rmoney'])) {
            $recharge_money = 0;
        } else {
            $recharge_money = $data_recharge[0]['rmoney'];
        }


        // 人次
        $prize_count = M('prize_order')->where(array(
            'status' => 1,
        ))->count();

        // 人数
        $user_sql = "select count(*) as c from (select * from hii_prize_order group by uid) t;";
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
        ))->sum('num');
        if (empty($all_count)) {
            $all_count = 0;
        }



        $this->assign('zinfo', array(
            'give_money' => $give_money, // 赠送金额
            'recharge_money' => $recharge_money, // 充值金额
            'prize_count' => $prize_count, // 人次
            'user_count' => $user_count, // 人数
            'work_count' => $work_count, // 员工人数
            'all_count' => $all_count, // 抽奖次数

        ));


        // 活动是否开启
        $this->assign('is_open', $is_open);


        $Model = M('prize_order');
        $count = $Model->count();
        $pcount = 30;
        $Page = new \Think\Page($count, $pcount);
        $sql = "select p.*, r.order_sn, r.pay_sn, r.money as rmoney, r.type, r.give, r.status as rstatus
        from hii_prize_order as p
        left join hii_recharge_order r
        on p.oid = r.id
        order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);



        $this->meta_title = '充值活动列表';
        $this->display('lists');
    }

    /**
     * 中奖订单
     */
    public function logs_prize()
    {


        $id = I('get.id',0 ,'intval');

        if (empty($id)) {
            $this->error('参数错误');
        }





        $Model = M('prize_user');
        $count = $Model->where(array('oid' => $id))->count();
        $pcount = 30;
        $Page = new \Think\Page($count, $pcount);
        $sql = "select * from hii_prize_user
        where oid = {$id}
        order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);




        $this->meta_title = '充值抽奖详情';
        $this->display('logs_prize');
    }



    /**
     * 查看活动配置
     */
    public function config()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);


        $data = M('prize_config')->find();


        if (empty($data)) {
            $data = array();
        }



        $this->assign('data', $data);






        $this->meta_title = '活动配置';
        $this->display('config');
    }


    /**
     * 编辑活动配置
     */
    public function update()
    {



        $data = array();



        $info = trim($_POST['info']);
        if (mb_strlen($info) < 2 || mb_strlen($info) > 400) {
            $this->error('活动的描述不合法');
        } else {
            $data['info'] = $info;
        }



        $is_open = empty($_POST['is_open']) ? 0 : 1;
        $data['is_open'] = $is_open;

        $money = floatval($_POST['money']);
        if (empty($money) || $money < 0) {
            $this->error('活动金额设置不合法');
        } else {
            $data['money'] = $money;
        }




        /*
        $stime = $_POST['stime'];
        $stime = strtotime($stime);
        if ($stime <= 0) {
            $this->error('活动时间设置不合法');
        } else {
            $data['stime'] = $stime;
        }

        $etime = $_POST['etime'];
        $etime = strtotime($etime);
        if ($etime < $stime) {
            $this->error('活动时间设置不合法');
        } else {
            $data['etime'] = $etime;
        }
        */


        $time = time();
        if (empty($_POST['id'])) {
            $data['create_time'] = $time;
            $data['update_time'] = $time;
        } else {
            $data['update_time'] = $time;
        }

        if ($_POST['id']) {
            $res = M('prize_config')->where(array(
                'id' => $_POST['id'],
            ))->save($data);
        } else {
            $res = M('prize_config')->add($data);
        }

        if(empty($res)){
            $this->error('提交失败');
        }else{
            $this->success('提交成功', Cookie('__forward__'));
        }
    }



    /**
     * 查看奖项配置
     */
    public function prize_config()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);


        // 统计实际概率情况
        $sql_group = "select *, count(pid) as c, sum(money) as moneys from hii_prize_user group by pid;";


        $data_group = M()->query($sql_group);

        if (empty($data_group)) {
            $data_group = array();
        }


        // 总抽奖次数
        $num = M('prize_user')->count();

        $groups = array();

        // 当已经产生数据时才开始统计
        if ($num > 0) {
            foreach ($data_group as $key => $val) {
                $pval = round($val['c'] / $num * 100, 2, PHP_ROUND_HALF_DOWN);
                $pval = number_format($pval, 2, '.', '') . '%';

                $moneys = number_format($val['moneys'], 2, '.', '');
                $avg = $val['moneys'] / $val['c'];
                $agv = number_format($avg, 2, '.', '');

                $moneys = number_format($val['moneys'], 2, '.', '');
                $group = array(
                    'num' => $val['c'],
                    'val' => $pval,
                    'moneys' => $moneys,
                    'avg' => $agv,
                );
                $groups[$val['pid']] = $group;
            }
        }



        // 设定的概率情况
        $data = M('prize_list')->select();


        if (empty($data)) {
            $data = array();
        }

        // 计算总概率值
        $num2 = 0;
        foreach ($data as $key => $val) {
            $num2 += $val['val'];
        }

        // 加入实际概率值及未产生的概率数据
        foreach ($data as $key2 => $val) {
            $pval2 = round($val['val'] / $num2 * 100, 2, PHP_ROUND_HALF_DOWN);
            $pval2 = number_format($pval2, 2, '.', '') . '%';

            // 设定的比例
            $data[$key2]['pval'] = $pval2;

            // 将实际值写入设定值
            if (array_key_exists($val['id'], $groups)) {
                $group = $groups[$val['id']];

                // 实际的数量
                $data[$key2]['tnum'] = $group['num'];

                // 实际的比例
                $data[$key2]['tval'] = $group['val'];

                $data[$key2]['moneys'] = $group['moneys'];

                $data[$key2]['avg'] = $group['avg'];
            } else {
                // 实际的数量
                $data[$key2]['tnum'] = 0;

                // 实际的比例
                $data[$key2]['tval'] = '0.00%';

                $data[$key2]['moneys'] = '0.00';

                $data[$key2]['avg'] = '0.00';
            }
        }



        $this->assign('groups', $groups);

        $this->assign('list', $data);






        $this->meta_title = '奖项配置';
        $this->display('prize_config');
    }


    /**
     * 添加或修改奖项
     */
    public function prize_update()
    {


        if(IS_POST){
            //$money = I('money', 0, 'floatval');
            $val = I('val', 0, 'intval');
            //$info = I('info');
            $id = I('id', 0, 'intval');


            /*
            $money = round($money, 2);


            if ($money <= 0) {
                $this->error('奖项金额需大于0');
            }
            */


            if ($val < 0) {
                $this->error('概率值不得小于0');
            }

            $time = time();

            $data = array(
                //'money' => $money,
                'val' => $val,
                //'info' => $info,
                'update_time' => $time,
            );



            // 有ID更新，没则新增
            if (empty($id)) {
                $this->error('非法操作');

                $data['create_time'] = $time;
                $res = M('prize_list')->add($data);



            } else {
                $res = M('prize_list')->where(array(
                    'id' => $id,
                ))->save($data);
            }

            if (empty($res)) {
                $this->error('操作失败');
            } else {
                $this->success('操作成功', U('ActPrizeAdmin/prize_config'));
            }

            /*
            $data = array(
                'money' => $money,
                'give' => $give,
                'info' => $info,
                'id' => $id,
            );

            echo json_encode($data);
            */
            //$this->error('操作失败');
            //$this->success('操作成功');


        } else {

            $id = I('id', 0, 'intval');


            $data = array(
                'id' => 0,
                'money' => 0,
                'val' => 0,
                'info' => '',
            );

            // 有ID则取数据，没则不处理
            if (!empty($id)) {
                $info = M('prize_list')->where(array(
                    'id' => $id,
                ))->find();

                if (!empty($info)) {
                    $this->assign('info', $info);
                } else {
                    $this->assign('info', $data);
                }
            } else {
                $this->assign('info', $data);
            }

            $this->meta_title = '充值奖项';
            $this->display('prize_update');
        }

    }




    // 删除
    public function prize_del()
    {
        if (IS_GET) {
            $id = I('id', 0, 'intval');

            if (empty($id)) {
                $this->error('非法操作');
            } else {
                $res = M('prize_list')->where(array(
                    'id' => $id,
                ))->delete();

                if (empty($res)) {
                    $this->error('操作失败');
                } else {
                    $this->success('操作成功');
                }

            }


        } else {
            $this->error('非法操作');
        }
    }


    // 获取活动配置
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






    /**
     * 创建排名
     */
    private function setNum50($id)
    {
        $data = $this->num_request('/SmActOne/prize_user_num30', array(
            'id' => $id,
        ));
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


    private function num_request($url, $data = array())
    {

        $isTest = $this->isTest();

        if ($isTest) {
            $domain = 'https://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'https://v.imzhaike.com/Apiv2';
        }



        $url = $domain . $url;

        $device = 0;
        $version = '';
        $key = '$ZaiKe$ByApi$';

        $url2 = trim(strtolower($url));
        $utoken = md5($url2 . $key . date('Y-m-d'));




        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名


        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        $result = curl_exec($ch);
        //xydebug($result, 'world_cup.txt');
        $result = json_decode($result, true);
        return $result;
    }














}
