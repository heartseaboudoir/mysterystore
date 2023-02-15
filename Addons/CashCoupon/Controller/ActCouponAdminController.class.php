<?php
namespace Addons\CashCoupon\Controller;

use Admin\Controller\AddonsController;

class ActCouponAdminController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    
    
    
    /**
     * 查看优惠券配置
     */
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        

        $data = M('act_config')->find();
        
        $this->assign('data', $data);
     
        
        
        
        
        $this->meta_title = '活动优惠券配置';
        $this->display(T('Addons://CashCoupon@Admin/ActCoupon/index'));
    }
    
    
    /**
     * 编辑优惠券配置
     */
    public function update()
    {


        
        $data = array();
        
        $title = trim($_POST['title']);
        if (mb_strlen($title) < 2 || mb_strlen($title) > 30) {
            $this->error('优惠券的标题不合法');
        } else {
            $data['title'] = $title;
        }
        
        $info = trim($_POST['info']);
        if (mb_strlen($info) < 2 || mb_strlen($info) > 80) {
            $this->error('优惠券的描述不合法');
        } else {
            $data['info'] = $info;
        }
        
        $is_open = empty($_POST['is_open']) ? 0 : 1;
        $data['is_open'] = $is_open;
        
        $money = floatval($_POST['money']);
        if (empty($money) || $money < 0) {
            $this->error('优惠券金额设置不合法');
        } else {
            $data['money'] = $money;
        }
        
 
        $min_use_money = floatval($_POST['min_use_money']);
        if (empty($min_use_money)) {
            $min_use_money = 0;
        }        
        if ($min_use_money < 0) {
            $this->error('使用的最低限额设置不合法');
        } else {
            $data['min_use_money'] = $min_use_money;
        }
 
        $parcent = intval($_POST['parcent']);
        if (empty($parcent) || $parcent <= 0 || $parcent >= 100) {
            $this->error('奖池占当天支付订单的百分比设置不合法');
        } else {
            $data['parcent'] = $parcent;
        }      
        
        
        
        
        
        $prize = floatval($_POST['prize']);
        if (empty($prize)) {
            $prize = 0;
        }
        $data['prize'] = $prize;    

        
        
        $data['num'] = (empty($_POST['num']) || $_POST['num'] < 0) ? 0 : intval($_POST['num']);
        
        $data['max_get'] = (empty($_POST['max_get']) || $_POST['max_get'] < 0) ? 0 : intval($_POST['max_get']);
        
        
        $data['days'] = (empty($_POST['days']) || $_POST['days'] < 0) ? 0 : intval($_POST['days']);
        
        
        $data['act_url'] = trim($_POST['act_url']);
        
        $remark = trim($_POST['remark']);
        if (mb_strlen($remark) < 2 || mb_strlen($remark) > 500) {
            $this->error('规则说明不合法');
        } else {
            $data['remark'] = $remark;
        }


        
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
        

        
        $time = time();
        if (empty($_POST['id'])) {
            $data['create_time'] = $time;
            $data['update_time'] = $time;
        } else {
            $data['update_time'] = $time;
        }
        
        
        if ($_POST['id']) {
            $res = M('act_config')->where(array(
                'id' => $_POST['id'],
            ))->save($data);
        } else {
            $res = M('act_config')->add($data);            
        }
    
        if(empty($res)){
            $this->error('提交失败');
        }else{
            $this->success('提交成功', Cookie('__forward__'));
        }
    }


    
    /**
     * 活动获奖日志
     */
    public function logs() {
        /**
        今天是否有活动并开启，有则将相关信息在当前页面展现出来
        
        
        */
        
        
        // 活动开始
        $config = $this->getActConfig();
        
        $dayAct = array(
            'day' => date('Y-m-d'),
            'count' => 0,
            'money' => $config['money'],
            'prize' => '0.00', 
        );        
        
        
        // 活动不存在
        $time = time();
        if (empty($config['is_open']) || $time < $config['stime'] || $time > ($config['etime'] + 3600 * 24)) {
            $is_open = false;
        } else {
            $is_open = true;
            // 活动存在，查询活动相关
            $infoAct = $this->getShareInfo();
            
            $dayAct['count'] = $infoAct['count'];
            $dayAct['prize'] = $infoAct['money'];            
            
        }       
        
        

        $this->assign('is_open', $is_open);
        
        $this->assign('day_act', $dayAct);
        
        
        

        
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list   = $this->lists(M('act_log'), $where, 'create_time desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);        
        
        
        
        $this->meta_title = '活动优惠券列表';
        $this->display(T('Addons://CashCoupon@Admin/ActCoupon/lists'));
    }
    
    /**
     * 中奖用户
     */
    public function logs_user()
    {
        
        
        $id = I('get.id',0 ,'intval');
        
        if (empty($id)) {
            $this->error('参数错误');
        }
        

        $where = array('act' => $id);
        $list   = $this->lists(M('cash_coupon_user'), $where, 'create_time desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);          
        
        
        
        
        $this->meta_title = '中奖用户列表';
        $this->display(T('Addons://CashCoupon@Admin/ActCoupon/logs_user'));        
    }
    
    
    
    /**
     * 活动分享用户
     */
    public function logs_share()
    {
        $id = I('get.id',0 ,'intval');
        
        if (empty($id)) {
            
            $stime = strtotime(date('Y-m-d'));
            $etime = $stime + 3600 * 24;
            
            
        } else {
            $act_log = M('act_log')->where(array(
                'id' => $id,
            ))->find();
            
            
            if (empty($act_log['act_time'])) {
                $this->error('不存在的活动');
                exit;
            }

            
            
            //echo '-----------';
            $stime = $act_log['act_time'];
            $etime = $stime + 3600 * 24;
        }
        
        
        $where = array();
        //$where['create_time'] = array('gt', $stime);
        //$where['create_time'] = array('lt', $etime);   
        $where['create_time'] = array(array('gt', $stime), array('lt', $etime));        

        
        
        $list   = $this->lists(M('act_prize_share'), $where, 'create_time desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);          
        
        
        
        
        $this->meta_title = '活动分享列表';
        $this->display(T('Addons://CashCoupon@Admin/ActCoupon/logs_share'));          
    }
    
    
    // 获取活动配置信息
    private function getActConfig()
    {
        $data = M('act_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => false,
                'title' => '',
                'info' => '',
                'money' => 1,
                'min_use_money' => 0,
                'parcent' => 0,
                'prize' => 1,
                'num' => 0,
                'max_get' => 0,
                'days' => 1,
                'remark' => '',
                'stime' => 0,
                'etime' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        
        $data['parcent'] > 20 && $data['parcent'] = 20;

        
        $data['prize'] > 1000 && $data['prize'] = 1;
        
        
        return $data;
        
    }



    // 获取奖池信息
    private function getShareInfo($uid = 0)
    {
        
        
        $config = $this->getActConfig();
        
        // 参数: 比例
        
        
        // 参与总人次，奖池金额
        $parcent = $config['parcent'];
        
        
        // 当天的下单时间
        $daytime = strtotime(date('Y-m-d'));        
        
        $where = array();
        
        $where['order_time'] = array('egt', $daytime);
        
        // 总人数
        $count = M('act_prize_share')->where($where)->count();
        
        $count += $config['num'];
        
        // 总支付金额
        $pay_money = M('act_prize_share')->where($where)->sum('pay_money');
        
        if (!empty($uid)) {
            $where['uid'] = $uid;
            $pay_money_me = M('act_prize_share')->where($where)->sum('pay_money');
        } else {
            $pay_money_me = 0;
        }
        
        
        $money = $pay_money * $parcent / 100 + $config['prize'];
        $money_me = $pay_money_me * $parcent / 100;
        
        return array(
            'count' => $count,
            'money' => number_format($money, 2),
            'money_me' => number_format($money_me, 2),
        );
        
        
    }    
    
    
    public function remove(){
        $id = I('get.id',0 ,'intval');
        if($id > 0){
            $Model = D('Addons://CashCoupon/CashCoupon');
            $res = $Model->where(array( 'id' => $id))->delete();
            if(!$res){
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            }else{
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }
    
    
    public function user_lists(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $pid = I('pid', 0, 'intval');
        $uid = I('uid', 0, 'intval');
        $p_code = I('p_code', '');
        $where = array();
        if($pid > 0){
            $data = M('CashCoupon')->find($pid);
            $where['p_code'] = $data['code'];
        }elseif($p_code){
            $where['p_code'] = $p_code;
        }
        $uid > 0 && $where['uid'] = $uid;
        $list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '优惠券领取列表'.($uid > 0 ? ' 【用户：'.get_nickname($uid).'】' : '');
        $this->display(T('Addons://CashCoupon@Admin/CashCoupon/user_index'));
    }
}
