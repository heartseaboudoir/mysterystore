<?php
namespace Addons\CashCoupon\Controller;

use Admin\Controller\AddonsController;

class WorldCupController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    
    

    public function win()
    {
        
        // 提交结果
        if (IS_POST) {
            // 检查参数
            $wc_id = $_POST['win_one'];
            if (empty($wc_id) || !in_array($wc_id, array(1, 2))) {
                $this->error('提交错误');
            }

            // 处理过不处理
            $have = M('wc_team')->where(array('wc_get' => 1))->find();
            if (!empty($have)) {
                $this->error('请求已处理，请勿重复提交');
            }
            
            // 处理胜负状态
            M('wc_team')->where(array(
                'wc_id' => $wc_id
            ))->save(array(
                'wc_get' => 1,
            ));

            $this->to_win();
            
            $this->success('提交成功', Cookie('__forward__'));
            
            
            
        } else {
            $data = M('wc_team')->order('id asc')->select();
            
            $data1 = array();
            $data2 = array();        
            if (!empty($data) && count($data) == 2) {
                if ($data[0]['wc_id'] == 1) {
                    $data1 = $data[0];
                }
                if ($data[1]['wc_id'] == 2) {
                    $data2 = $data[1];
                }            
                
            }
            
            $this->assign('data1', $data1);
            $this->assign('data2', $data2);
            
            $this->meta_title = '世界杯决赛胜负';
            $this->display(T('Addons://CashCoupon@Admin/WorldCup/win'));
        }
    }
    
    
    
    /**
     * 编辑球队配置
     */    
    public function team()
    {
        
        $data = M('wc_team')->order('id asc')->select();
        
        $data1 = array();
        $data2 = array();        
        if (!empty($data) && count($data) == 2) {
            if ($data[0]['wc_id'] == 1) {
                $data1 = $data[0];
            }
            if ($data[1]['wc_id'] == 2) {
                $data2 = $data[1];
            }            
            
        }
        
        $this->assign('data1', $data1);
        $this->assign('data2', $data2);
        
        $this->meta_title = '世界杯球队配置';
        $this->display(T('Addons://CashCoupon@Admin/WorldCup/team'));
    }
    
    
    
    
    /**
     * 更新球队配置
     */
    public function update_team()
    {


        
        $data1 = array();
        $data2 = array();
        
        $wc_name1 = trim($_POST['wc_name1']);
        if (mb_strlen($wc_name1) < 2 || mb_strlen($wc_name1) > 200) {
            $this->error('A球队名不合法');
        } else {
            $data1['wc_name'] = $wc_name1;
        }
        
        $wc_pic1 = trim($_POST['wc_pic1']);
        if (empty($wc_pic1)) {
            $this->error('A球队图片不合法');
        } else {
            $data1['wc_pic'] = $wc_pic1;
        }         
        
        
        $wc_name2 = trim($_POST['wc_name2']);
        if (mb_strlen($wc_name2) < 2 || mb_strlen($wc_name2) > 200) {
            $this->error('B球队名不合法');
        } else {
            $data2['wc_name'] = $wc_name2;
        }        
        
        $wc_pic2 = trim($_POST['wc_pic2']);
        if (empty($wc_pic2)) {
            $this->error('B球队图片不合法');
        } else {
            $data2['wc_pic'] = $wc_pic2;
        }        
        

        $time = time();

        $data1['update_time'] = $time;
        $data2['update_time'] = $time;

        
        
        M('wc_team')->where(array('wc_id' => 1))->save($data1);
        M('wc_team')->where(array('wc_id' => 2))->save($data2);
    

        $this->success('提交成功', Cookie('__forward__'));

    }    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * 查看活动配置
     */
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        

        $data = M('wc_config')->find();
        
        $this->assign('data', $data);
        
        
        $products = empty($data['products']) ? '' : trim($data['products']);
        
        
        $product_arr = explode(',', $products);
        
        if (empty($product_arr)) {
            $select_product = array(
                'count' => 0,
                'list' => array(),
            );
        } else {
            
            $products = implode(',', $product_arr);
            $where = ' status = 1 and id in (' . $products . ')';
            
            $sql = "select id,title,cover_id from hii_goods where {$where} order by id desc limit 100;";
            
            
            $list = M()->query($sql);
            
            if (empty($list)) {
                $list = array();
            }  


            foreach($list as $k => $v){
                $v['pic_url'] = get_cover($v['cover_id'], 'path');
                $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
                $list[$k] = $v;
            }
            
            $select_product = array(
                'count' => count($list),
                'list' => $list,
            );             
            
        }
        
        
        if (empty($select_product['list'])) {
            $select_product['list'] = (object)array();
        }
        
        $select_pro = json_encode($select_product);
        $this->assign('select_pro', $select_pro);
        
       
        
        
        $this->meta_title = '世界杯活动配置';
        $this->display(T('Addons://CashCoupon@Admin/WorldCup/index'));
    }
    
    
    /**
     * 编辑活动配置
     */
    public function update()
    {


        
        $data = array();
        
        $title = trim($_POST['title']);
        if (mb_strlen($title) < 2 || mb_strlen($title) > 200) {
            $this->error('活动的标题不合法');
        } else {
            $data['title'] = $title;
        }
        
        $info = trim($_POST['info']);
        if (mb_strlen($info) < 2 || mb_strlen($info) > 400) {
            $this->error('活动的描述不合法');
        } else {
            $data['info'] = $info;
        }
        
        $cover = trim($_POST['cover']);
        if (empty($cover)) {
            $this->error('活动分享图标不合法');
        } else {
            $data['cover'] = $cover;
        }        
        
        $toppic = trim($_POST['toppic']);
        if (empty($toppic)) {
            $this->error('活动分享图标不合法');
        } else {
            $data['toppic'] = $toppic;
        } 

        
        $is_open = empty($_POST['is_open']) ? 0 : 1;
        $data['is_open'] = $is_open;
        
        
        /*
        $money = floatval($_POST['money']);
        if (empty($money) || $money < 0) {
            $this->error('活动金额设置不合法');
        } else {
            $data['money'] = $money;
        }
        */

        
        $remark = trim($_POST['remark']);
        if (mb_strlen($remark) < 2 || mb_strlen($remark) > 500) {
            $this->error('规则说明不合法');
        } else {
            $data['remark'] = $remark;
        }
        
        
        $products = trim($_POST['products']);
        
        
        $product_arr = explode(',', $products);
        
        if (count($product_arr) < 1 || count($product_arr) > 100) {
            $this->error('绑定商品不合法');
        } else {
            $data['products'] = $products;
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
            $res = M('wc_config')->where(array(
                'id' => $_POST['id'],
            ))->save($data);
        } else {
            $res = M('wc_config')->add($data);            
        }
    
        if(empty($res)){
            $this->error('提交失败');
        }else{
            $this->success('提交成功', Cookie('__forward__'));
        }
    }


    
    /**
     * 投注日志
     */
    public function logs() 
    {
        /**
        今天是否有活动并开启，有则将相关信息在当前页面展现出来
        
        
        */
        
        
        // 活动开始
        $config = $this->getConfig();
        
        
        
        
        $dayAct = array(
            'day' => date('Y-m-d'),
            'money' => $config['money'], 
        );        
        
        
        // 活动不存在
        $time = time();
        if (empty($config['is_open'])) {
            $is_open = false;
        } else {
            $is_open = true;
        }       
        
        
        // 奖池累计起止时间
        
        // 充值起止时间
        
        
        

        $this->assign('is_open', $is_open);
                
        $this->assign('config', $config);
        
        
        // 奖池大小 
        $price = $this->getPrices();
        $this->assign('price', number_format($price, 2, '.', ''));
        
        
        // 决赛球队 
        $teams = $this->getTeams();
        $this->assign('teams', $teams);
        

        if (empty($teams)) {
           $teams_info = '?(0) VS ?(0)';
           
           $wc_team_a = 'A队';
           $wc_team_b = 'B队';
           
        } else {
           $wc_team_a = $teams[0]['wc_name'];
           $wc_team_b = $teams[1]['wc_name'];            
            
           
           if ($teams[0]['wc_get'] == 1) {
               $wc_name0 = '<span style="color:red;">' . $teams[0]['wc_name'] . '</span>';
               $wc_name1 = $teams[1]['wc_name'];
           } elseif ($teams[1]['wc_get'] == 1) {
               $wc_name0 = $teams[0]['wc_name'];
               $wc_name1 = '<span style="color:red;">' . $teams[1]['wc_name'] . '</span>';               
           } else {
               $wc_name0 = $teams[0]['wc_name'];
               $wc_name1 = $teams[1]['wc_name'];
           }
           
           $teams_info = $wc_name0 . '(' . $teams[0]['select'] . ')' . ' VS ' . $wc_name1 . '(' . $teams[1]['select'] . ')';
           
        }
        $this->assign('teams_info', $teams_info);
        $this->assign('wc_team_a', $wc_team_a);
        $this->assign('wc_team_b', $wc_team_b);
        
        
        // 投票情况
        $votes = $this->getVoteInfo();
        
        $vote_info = '总投票数：' . $votes[0] . ';&nbsp;&nbsp;剩余票数：' . $votes[1];
        
        $this->assign('vote_info', $vote_info);

        
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        
        /*
        $where = array();
        $list   = $this->lists(M('act_product_log'), $where, 'create_time desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);        
        */

        $Model = M('wc_bit_record');
        $count = $Model->count();
        $pcount = 25;
        $Page = new \Think\Page($count, $pcount);
        $sql = "select * from hii_wc_bit_record order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);

        $this->meta_title = '世界杯活动列表';
        $this->display(T('Addons://CashCoupon@Admin/WorldCup/lists'));
    }
    
    
    
    public function blog()
    {
        
        $teams = $this->getTeams();
        $team_a = $team_b = '';
        foreach ($teams as $key => $team) {
            if ($team['wc_id'] == 1) {
                $team_a = $team['wc_name'];
            } elseif ($team['wc_id'] == 2) {
                $team_b = $team['wc_name'];
            }
        }        
        
        $this->assign('team_a', $team_a);
        $this->assign('team_b', $team_b);
        
        
        $uid = I('uid', 'intval');
        
        $Model = M('wc_bit_log');
        $count = $Model->where(array(
            'uid' => $uid,
        ))->count();
        $pcount = 25;
        $Page = new \Think\Page($count, $pcount);
        $sql = "select * from hii_wc_bit_log where uid = {$uid} order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);

        $this->meta_title = '用户投票记录';
        $this->display(T('Addons://CashCoupon@Admin/WorldCup/blog'));        
    }
    
    
    // 投票情况
    private function getVoteInfo()
    {
        $alls = M('wc_bit_record')->sum('`alls`');
        $can = M('wc_bit_record')->sum('`can`');        
        
        if (empty($alls)) {
            $alls = 0;
        }
        
        
        if (empty($can)) {
            $can = 0;
        }
        
        
        return array($alls, $can);
        
        
    }
    
    
    
    // 获取决赛球队
    private function getTeams()
    {
        $data = M('wc_team')->order('id asc')->select();     
        
        if (empty($data) || count($data) != 2) {
            return array();
        }
        

        
        
        
        $num1 = M('wc_bit_record')->sum('a');
        $num2 = M('wc_bit_record')->sum('b');
        
        if (empty($num1)) {
            $num1 = 0;
        }
        
        if (empty($num2)) {
            $num2 = 0;
        }


        foreach ($data as $key => $team) {
            if ($team['wc_id'] == 1) {
                $data[$key]['select'] = $num1;
            } elseif ($team['wc_id'] == 2) {
                $data[$key]['select'] = $num2;
            }
        }

        return $data;  
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
                'backtime_price' => 0,
                'backtime_vote' => 0,                
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        
        $time = time();
        
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
    
    

  
  
    /**
     * 获取商品搜索信息
     */
    public function get_ol_lists(){
        
        // 搜索关键字
        $keyword = I('keyword', '', 'trim');
        
        // 分页
        $row = I('row', 0, 'intval');
        
        $page = I('page', 0, 'intval');
        
        
        if (empty($row) || $row >= 10 || $row <= 0) {
            $row = 10;
        }
        
        if (empty($page) || $page < 1) {
            $page = 1;
        }
        
        $start = ($page - 1) * $row;
        
        $limit = "{$start},{$row}";
        
        
        
        
        
        $where = ' status = 1';
        
        if (!empty($keyword)) {
            $where .= " and `title` like '%" . $keyword . "%'";
        }
        
        $sql_count = "select count(*) as c from hii_goods where {$where}";
        
        $sql = "select id,title,cover_id from hii_goods where {$where} order by id desc limit {$limit};";
        
        $res = M()->query($sql_count);
        
        if (empty($res) || empty($res[0]['c'])) {
            $count = 0;
        } else {
            $count = $res[0]['c'];
        }
        
        $list = M()->query($sql);
        
        if (empty($list)) {
            $list = array();
        }
        
        foreach($list as $k => $v){
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
            $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
            $list[$k] = $v;
        }
        
        $data = array(
            'count' => $count,
            'list' => $list,
        );
        
        if(IS_AJAX || IS_POST){
            $this->ajaxReturn(array('status' => 1, 'data' => $data));
            exit;
        }
        $this->assign('data', $data);
        $this->display(T('Addons://CashCoupon@Admin/WorldCup/get_ol_lists'));       
    }    
    
    /**
     * 处理世界杯活动开奖结果
     */
    private function to_win()
    {
        
        
        
        $data = $this->win_request('/WorldCup/give_wc', array(
            'check_str' => 'dgsaihiorefhadhfah7',
        ));
        
        $data = $this->win_request('/SmWctpl/send_smg', array(
            'check_str' => 'dgsaihiorefhadhfah7',
        ));        
        
        //xydebug($data, 'world_cup.txt');
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
    
    
    private function win_request($url, $data = array())
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
