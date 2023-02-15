<?php

namespace Wap\Controller;

// use Think\Controller;

class WorldCupController extends BaseController {


    public $uinfo = array();

    
    
    // 用户登录界面 
    public function login()
    {
        // 是否已经登录成功
        $uinfo = $this->checkLogin();
        
        // 登录成功跳至首页
        if (!empty($uinfo)) {
            $this->redirect('Wap/WorldCup/wc');
        }
        
        // 获取授权信息, 微信或支付宝登录的情况
        if (in_array(APP_TYPE, array('wechat', 'alipay'))) {
            $data = $this->get_open();
            
            
            if (APP_TYPE == 'wechat') {
                $type = 1;
                $code = $data['openid'];
            } elseif (APP_TYPE == 'alipay') {
                $type = 2;
                $code = $data['user_id'];
            }
            
            
            // 自动登录
            $result = $this->auto_login($code, $type);             
            
            // 登录成功跳至首页
            $uinfo = $this->checkLogin();
            if (!empty($uinfo)) {
                $this->redirect('Wap/WorldCup/wc');
            }
            
            $this->assign('type', $type);
            $this->assign('ids', $code);
            
        // 直接H5登录，不需要绑定
        } else {
            $this->assign('type', 0);
            $this->assign('ids', '');
        }
        $this->display(); 
    }
    
    // 绑定第三方和神秘商店
    public function build()
    {
        // 验证码验证
        
        // 手机号
        $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']);
        
        // 验证码
        $code = empty($_POST['code']) ? '' : trim($_POST['code']);
        
        // 身份识别标识
        $ids = empty($_POST['ids']) ? '' : trim($_POST['ids']);
        
        // 身份识别类型
        $type = empty($_POST['type']) ? 0 : trim($_POST['type']);
        
        // type,0:无绑定登录；1：微信登录；2：支付宝登录
        if (!in_array($type, array(0, 1, 2))) {
            $this->response('type无效', 10013);
        }
        
        // 身份识别标识验证
        if ($type != 0 && $ids == '') {
            $this->response('身份识别标识错误');
        }
        

        
        if (empty($code)){
            $this->response('验证码不能为空', 10015);
        }           
        

        if (empty($mobile)){
            $this->response('手机号不得为空', 10020);
        }   
        
        // 判断验证码
        $result = check_code('login_build_code', $mobile, $code);
        if($result['status'] != 1){
            $this->response('验证码错误', 10030);
        }
        
        // 用户是否已注册
        $user = M('ucenter_member')->where(array(
            'mobile' => $mobile,
        ))->find(); 

        
        $time = time();
        
        // 用户未注册,加一条用户信息
        if (empty($user) || empty($user['id'])) {
            // 注册账号
            $username = $this->get_rand_username();
            $password = md5('chaoshipos'.$username);  

            $mdata = array(
                'username' => $username,
                'password' => $password,
                'mobile' => $mobile,
                'reg_ip' => get_client_ip(),
                'reg_time' => $time,
                'last_login_ip' => get_client_ip(),
                'last_login_time' => $time,
                'update_time' => $time,
                'status' => 1,
            );

            if ($type == 1) {
                $mdata['openid'] = $ids;             
            } elseif ($type == 2) {
                $mdata['alyid'] = $ids;          
            }            
        
            $uid = M('ucenter_member')->add($mdata);
        
      
        
            M('member')->add(array(
                'uid' => $uid,
                'nickname' => substr_replace($mobile, '****', 5, 4),
                'status' => 1,
                'reg_ip' => get_client_ip(),
                'reg_time' => $time,
                'last_login_ip' => get_client_ip(),
                'last_login_time' => $time, 
            ));        
        
            $nickname = substr_replace($mobile, '****', 5, 4);
        
        // 用户已注册
        } else {
            
            
            if ($type == 1) {
                $data_ids = array('openid' => $ids);
                M('ucenter_member')->where(array(
                    'id' => $user['id'],
                ))->save($data_ids);                
            } elseif ($type == 2) {
                $data_ids = array('alyid' => $ids);
                M('ucenter_member')->where(array(
                    'id' => $user['id'],
                ))->save($data_ids);                
            }
            
            $muser = M('member')->where(array(
                'uid' => $user['id']
            ))->find();
            
            if (empty($muser['nickname'])) {
                $nickname = substr_replace($mobile, '****', 5, 4);
            } else {
                $nickname = $muser['nickname'];
            }
                
            // 用户ID
            $uid = $user['id'];            
        }
        

        

        // 登录成功
        $_SESSION['sm_uinfo'] = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'nickname' => $nickname,
        );
        
        
        $this->response($_SESSION['sm_uinfo']);
        
    }
    
 
    
    
    
    // 获取绑定验证码,输入手机号
    public function build_code()
    {
        
        $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']);
        
        if (empty($mobile)) {
            $this->response('手机号不得为空', 10010);
        }         

        if (!preg_match('/^1\d{10}$/', $mobile)) {
            $this->response('手机号不合法', 10012);
        }
        
        
        
        $code = make_code('login_build_code', $mobile);
        
        
        //$this->return_data(1, '', $code);exit;
        //$result = send_sms($mobile, 'SMS_39185282', array('code' => $code));
        $result = send_sms($mobile, 'SMS_39370204', array('code' => $code, 'product' => '神秘商店'));
        if($result['status'] == 1) {
            $this->response('发送成功');
        } else {
            $this->response($result['msg'], 10020);
        }
    }  




    public function checkLogin()
    {
        $sm_uinfo = $_SESSION['sm_uinfo'];
        if (!empty($sm_uinfo)) {
            return $sm_uinfo;
        } else {
            return array();
        }
        
    }
    
    
    private function sm_auto_login($cstr = '')
    {
        $cstr = trim($cstr);
        if (empty($cstr) || $cstr == 'null') {
            $_SESSION['sm_uinfo'] = array();
            $this->redirect('Wap/WorldCup/wc');
        }
        
        
        $user = M('ucenter_member')->where(array(
            'wcauth' => $cstr,
        ))->find();
        
        
        if (empty($user) || empty($user['mobile']) || empty($user['id'])) {
            
            $_SESSION['sm_uinfo'] = array();
            
            
            $this->redirect('Wap/WorldCup/wc');
        }
        
        
        $muser = M('member')->where(array(
            'uid' => $user['id']
        ))->find();
        
        if (empty($muser['nickname'])) {
            $nickname = substr_replace($mobile, '****', 5, 4);
        } else {
            $nickname = $muser['nickname'];
        }
            
        // 用户ID
        $uid = $user['id'];  

        $mobile = $user['mobile'];
        
        
        // 登录成功
        $_SESSION['sm_uinfo'] = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'nickname' => $nickname,
        ); 
        
        $this->redirect('Wap/WorldCup/wc');
        
        
    }
    

    
    // 测试首页
    public function test()
    {
        
        // $uinfo = $this->checkLogin();
        
        $data = $this->get_open();
        
        $openid = $data['openid'];
        
        //$result = $this->auto_login($openid, 1);
        
        
        $this->assign('data', $data);
        
        //$this->assign('result', $result);

        $wc_share = array(
            'shareTitle' => 'title',
            'shareDesc' => "desc",
            'shareImg' => "http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJJscBficiaUXI9SmibruW3haVExFIqzgP909CN2AfmOsDDDg3XIcpUD9NorUF6nbnr365PyJOkTThPA/132",
            'shareLink' => U('Wap/WorldCup/wc'),
        );

        
        $this->assign('wc_share', $wc_share);
        
        $this->display();
              
    }
    
    
    // 第三方登录信息
    private function auto_login($code, $type = 1)
    {
        // 是否已登录,已登录跳到活动界面
        
        // 未登录获取第三方登录信息
        // Array ( [openid] => ohEQbxBavUfzG5Y4JKUsIyaOoNxg [nickname] => xuyuan [sex] => 1 [language] => zh_CN [city] => 广州 [province] => 广东 [country] => 中国 [headimgurl] => http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJJscBficiaUXI9SmibruW3haVExFIqzgP909CN2AfmOsDDDg3XIcpUD9NorUF6nbnr365PyJOkTThPA/132 [privilege] => Array ( ) [unionid] => o3lw6s5GNORFKZWF91kng8f0Qiqc )        
        
        
        // 没有code信息时无法登录
        if (empty($code) || !in_array($type, array(1, 2, 3))) {
            return array();
        }
        
        
        
        $time = time();
        
        
        
        // 第三方信息和系统信息比对
        
        // 微信
        if ($type == 1) {
            $user = M('ucenter_member')->where(array(
                'openid' => $code,
            ))->find();
            
        // 支付宝 
        } else if ($type == 2){
            $user = M('ucenter_member')->where(array(
                'alyid' => $code,
            ))->find();            
            
        // id
        } else {
            $user = M('ucenter_member')->where(array(
                'id' => $code,
            ))->find();              
            
        }
        
        //  用户不存在,待绑定
        if (empty($user) || empty($user['mobile']) || empty($user['id'])) {
            return array();
        }
        
        // 用户存在，待返回用户信息
        $member = M('member')->where(array(
            'uid' => $user['id'],
        ))->find();
        
        // 用户信息不存在即写入
        if (empty($member)) {
            M('member')->add(array(
                'uid' => $user['id'],
                'nickname' => substr_replace($user['mobile'], '****', 5, 4),
                'status' => 1,
                'reg_ip' => get_client_ip(),
                'reg_time' => $time,
                'last_login_ip' => get_client_ip(),
                'last_login_time' => $time, 
            ));
            
            $nickname = substr_replace($user['mobile'], '****', 5, 4);
        } else {
            $nickname = $member['nickname'];
        }
        
        // 登录成功
        $_SESSION['sm_uinfo'] = array(
            'uid' => $user['id'],
            'mobile' => $user['mobile'],
            'nickname' => $nickname,
        );
        
        
        return $_SESSION['sm_uinfo'];
    }
    
    

    


    
    /**
     * 活动主界面
     */
    public function wc()
    {
        
        
        $cstr = empty($_GET['cstr']) ? '' : trim($_GET['cstr']);
        
        /*
        if (!empty($cstr)) {
            echo $cstr;exit;
        }        
        */
        
        
        if (!empty($cstr)) {
            $this->sm_auto_login($cstr);
        }
        
        
        
        // 获取授权信息, 微信或支付宝登录的情况
        $uinfo = $this->checkLogin();
        if (empty($uinfo) && in_array(APP_TYPE, array('wechat', 'alipay'))) {
            $data = $this->get_open();
            
            
            if (APP_TYPE == 'wechat') {
                $type = 1;
                $code = $data['openid'];
            } elseif (APP_TYPE == 'alipay') {
                $type = 2;
                $code = $data['user_id'];
            }
            
            
            // 自动登录
            $result = $this->auto_login($code, $type);             
            
            // 登录成功跳至首页
            $uinfo = $this->checkLogin();
            if (!empty($uinfo)) {
                $this->redirect('Wap/WorldCup/wc');
            }
            
            $this->assign('type', $type);
            $this->assign('ids', $code);
            
        // 直接H5登录，不需要绑定
        }        
        
        
        
        
        
        
        
        
        // 获取活动配置（10个商品的信息）
        $config = $this->getConfig();
        
        // 获取用户信息
        $uinfo = $this->getUinfo();
        
        // 获取可选球队,+球队图片
        $teams = $this->getTeams();
        
        // 获取用户的投票情况
        $record = $this->getRrecord();
        
        // 获取奖池金额
        $prices = $this->getPrices();
        
        // 获取获胜球队
        $win = $this->getWin();
        
        if (empty($win)) {
            $win = false;
        }
        
        
        $data = array(
            // 奖池相关商品
            // 'products' => $config['products_info']['list'],
            
            
            // 当前奖池
            'prices' => $prices,
            
            // 奖池累计所剩时间
            'backtime_price' => $config['backtime_price'],
            
            // 投标所剩时间
            'backtime_vote' => $config['backtime_vote'],
            
            'backtime_recharge' => $config['backtime_recharge'],
            
            // 比赛球队
            'teams' => $teams,
            
            // 用户投票信息
            'record' => $record,
            
            // 获胜球队
            'win' => $win,
            
        
        );
        
        
        
        // print_r($data);
        
        
        $data_json = json_encode($data, JSON_FORCE_OBJECT);
        
        $this->assign('data_json', $data_json);
        
        // 1.投注选项
        
        // 2.当前用户信息
        // 总共可投注数，已投注数，未投注数，中奖注数
        
        
        // 3.当前奖池信息
        // 指定商品10%的金额
        // 总投注人次，A,B分别人次
        // 中奖金额
        $isTest = $this->isTest();
        
        if ($isTest) {
            $domain = 'https://test.imzhaike.com';
        } else {
            $domain = 'https://v.imzhaike.com';
        }         
        
        $wc_share = array(
            'shareTitle' => $config['title'],
            'shareDesc' => $config['info'],
            'shareImg' => $domain . $config['cover'],
            'shareLink' => U('Wap/WorldCup/wc'),
        );

        
        $this->assign('wc_share', $wc_share);        
        
        
        
        
        $this->display();
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
    
    

    
    
    /**
     * 用户投票
     */
    public function select()
    {
        
        // 投票数目
        $num = intval($_POST['num']);
        if (empty($num) || $num < 1) {
            $this->response('投票数目参数不合法', 10002);
        }
        
        // 投票球队
        $team = intval($_POST['team']);
        if (!in_array($team, array(1, 2))) {
            $this->response('投票球队参数不合法', 10003);
        }
        
        
        $time = time();
        
        $config = $this->getConfig();
        
        
        if (empty($config['id'])) {
            $this->response('活动未开启', 10010);
        }
        
        if ($time < $config['bstime'] || $time > ($config['betime'])) {
            $this->response('不在投票时间范围内', 10020);
        }
             
        
        // 获取用户的投票情况
        $record = $this->getRrecord();        
        
        
        if (empty($record['uinfo']) || $record['uinfo'] <= 0) {
            $this->response('获取用户信息失败', 10030);
        }
        
        $udata = $record['data'];
        if (empty($udata) || empty($udata['can']) || $udata['can'] <= 0) {
            $this->response('当前没有可投票数', 10031);
        }
        
        if ($udata['can'] < $num) {
            $this->response('当前可投票数不足', 10032);
        }
        
        
        // 投票
        M('wc_bit_record')->where(array('uid' => $record['uinfo']))->setDec('can', $num);
        
        if ($team == 1) {
            M('wc_bit_record')->where(array('uid' => $record['uinfo']))->setInc('a', $num);
            
            // 记录操作
            M('wc_bit_log')->add(array(
                'uid' => $record['uinfo'],
                'num' => $num,
                'team' => 1,
                'create_time' => $time,
            ));
            
        } else {
            M('wc_bit_record')->where(array('uid' => $record['uinfo']))->setInc('b', $num);
            
            // 记录操作
            M('wc_bit_log')->add(array(
                'uid' => $record['uinfo'],
                'num' => $num,
                'team' => 2,
                'create_time' => $time,
            ));            
            
        }
        

        $this->response('投票成功');
        
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
    
    
    
    
    
    
    
    
    /**
     * 获取用户信息
     */
    private function getUinfo()
    {
        
        $uinfo = $this->checkLogin();
        if (empty($uinfo['uid'])) {
            return array();
        } else {
            return $uinfo;
        }
        
        
        /*
        
        $uinfo = M('member')->where(array(
            'uid' => 1313
        ))->find();
        
        
        if (empty($uinfo)) {
            $uinfo = array();
        }
        
        
        $this->uinfo = $uinfo;
        
        return $uinfo;
        */
        
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
    
    
    
    

    // 获取用户的投票情况
    private function getRrecord()
    {
        // 用户在A,B球队的投票数
        // 
        
        
        $uinfo = $this->getUinfo();
        
        
        
        // 用户信息不存在
        if (empty($uinfo) || empty($uinfo['uid'])) {
            $uid = 0;
            
            $record = array(
                'id' => 0,
                'uid' => 0,
                'alls' => 0,
                'can' => 0,
                'a' => 0,
                'b' => 0,
                'price' => 0,
                'create_time' => 0,
                'update_time' => 0,
            );            
            
        } else {
            $uid = $uinfo['uid'];
            
            // 查询用户的投票记录
            $record = M('wc_bit_record')->where(array(
                'uid' => $uid,
            ))->find();            
            
            if (empty($record)) {
                $record = array(
                    'id' => 0,
                    'uid' => 0,
                    'alls' => 0,
                    'can' => 0,
                    'a' => 0,
                    'b' => 0,
                    'price' => 0,
                    'create_time' => 0,
                    'update_time' => 0,
                );
            }            
        }
        
        // print_r($uinfo);exit;
        
        

        


        
        $res = array(
            'uinfo' => $uid,
            'data' => $record,
        );
        

        return $res;
    }

    
    
    /**
     * 获取可选球队
     */
    private function getTeams()
    {
        $teams = M('wc_team')->where(array(
            'wc_id' => array('in', array(1, 2)),
        ))->select();
        
        
        
        
        
        
        if (empty($teams) || count($teams) != 2) {
            return array();
        } else {
            
            
            $record = $this->getRrecord();
            
            if (empty($record['uinfo']) || $record['uinfo'] <= 0) {
                $teams[0]['unum'] = 0;
                $teams[1]['unum'] = 0;
            } else {
                foreach ($teams as $key => $team) {
                    if ($team['wc_id'] == 1) {
                        $teams[$key]['unum'] = $record['data']['a'];
                    } elseif ($team['wc_id'] == 2) {
                        $teams[$key]['unum'] = $record['data']['b'];
                    }
                }
            }
            
            
            
            return $teams;
        }
        
        
    }
    
    
    /**
     * 获取获胜球队
     */
    private function getWin()
    {
        $team = M('wc_team')->where(array(
            'wc_id' => array('in', array(1, 2)),
            'wc_get' => 1,
        ))->find();
        
        
        if (empty($team)) {
            return array();
        } else {
            $win_num = $this->getWinNum();
            $team['win_num'] = $win_num;
            return $team;
        }
    }
    
    
    // 中奖用户数
    private function getWinNum()
    {
        $team = M('wc_team')->where(array(
            'wc_id' => array('in', array(1, 2)),
            'wc_get' => 1,
        ))->find();
        
        
        if (empty($team) || empty($team['wc_id'])) {
            return 0;
        } else {
            if ($team['wc_id'] == 1) {
                $where = array(
                    'a' => array('gt', 0),
                );
                $sum = 'a';
            } else {
                $where = array(
                    'b' => array('gt', 0),
                );   
                $sum = 'b';
            }
            
            $num = M('wc_bit_record')->where($where)->sum($sum);
            
            if (empty($num)) {
                $num = 0;
            }
            
            return $num;
        }
    }    






    
    // 获取本次活动配置
    private function getConfig()
    {
        
        $time = time();
        $data = M('wc_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => 0,
                'title' => '',
                'info' => '',
                'toppic' => '',
                'cover' => '',
                'remark' => '',
                'money' => 0,
                'products' => '',
                'stime' => 0,
                'etime' => 0,
                'remark_arr' => array(),
                'products_info' => array(),
                'backtime_price' => 0,
                'backtime_vote' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        $remark_arr = explode("\r\n", $data['remark']);
        
        $data['remark_arr'] = $remark_arr;        
        
        // 上次配置的可选商品
        // $products_info = $this->getProductLists($data['products']);
        
        // $data['products_info'] = $products_info;
        
        // 奖池注入所剩时间
        if ($time > $data['stime']) {
            if ($time < $data['etime']) {
                $data['backtime_price'] = $data['etime'] - $time;
            
            // 已经结束
            } else {
                $data['backtime_price'] = -1;
            }
        
        // 还未开始
        } else {
            $data['backtime_price'] = 0;
        }
        

        // 充值所剩时间
        $data['bstime0'] = $data['bstime'];
        $data['betime0'] = $data['betime'];
        if ($time > $data['bstime0']) {
            if ($time < $data['betime0']) {
                $data['backtime_recharge'] = $data['betime0'] - $time;
            
            // 已经结束
            } else {
                $data['backtime_recharge'] = -1;
            }
        
        // 还未开始
        } else {
            $data['backtime_recharge'] = 0;
        }
        
        
        // 投注所剩时间
        $data['bstime'] = $data['bstime'] + 3600 * 24 * 7;
        $data['betime'] = $data['betime'] + 15 * 60;
        if ($time > $data['bstime']) {
            if ($time < $data['betime']) {
                $data['backtime_vote'] = $data['betime'] - $time;
            
            // 已经结束
            } else {
                $data['backtime_vote'] = -1;
            }
        
        // 还未开始
        } else {
            $data['backtime_vote'] = 0;
        }        
        
       
            
        return $data;        
    }

   
    
   
    // 上次活动商品信息
    private function getProductLists($products)
    {
        $products = empty($products) ? '' : trim($products);
        $product_arr = explode(',', $products);
        
        if (empty($product_arr)) {
            $select_product = array(
                'count' => 0,
                'list' => array(),
            );
        } else {
            
            $products = implode(',', $product_arr);
            $where = ' g.status = 1 and g.id in (' . $products . ')';
            
            $sql = "select g.id,g.title,g.cover_id,g.cate_id, c.title as ctitle   
            from hii_goods g 
            left join hii_goods_cate c 
            on c.id = g.cate_id 
            where {$where} order by id desc limit 10;";
            
            
            $list = M()->query($sql);
            
            if (empty($list)) {
                $list = array();
            }  


            foreach($list as $k => $v){
                $v['pic_url'] = get_cover($v['cover_id'], 'path');
                // $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
                $list[$k] = $v;
            }
            
            $select_product = array(
                'count' => count($list),
                'list' => $list,
            );             
            
        }        
        
        
        return $select_product;
        
    }
    
    
    
    // 获取随机用户名
    private function get_rand_username(){
        $a = 'abcdefghijklmnopqrstuvwxyz';
        $k = mt_rand(0, 25);
        $username = $a{$k}.date('ymdhis').mt_rand(10,99);
        
        $UcApi = new \User\Client\Api();
        $req = $UcApi->execute('User', 'checkUsername', array());
        if($req['status'] != 1){
            return false;
        }else{
            $result = $req['data'];
        }
        if($result !=1 ){
            $username = $this->get_rand_username();
        }
        return $username;
    }       
    

 
      
    
    
    
}
