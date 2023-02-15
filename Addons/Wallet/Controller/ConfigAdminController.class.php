<?php
namespace Addons\Wallet\Controller;

use Admin\Controller\AddonsController;

class ConfigAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $Model = D('Addons://Wallet/WalletConfig');
        $list = $Model->where(array('is_show' => 1))->select();
        if(IS_POST){
            $data = I('data');
            if(!is_array($data)){
                $this->error('操作失败');
            }
            foreach($data as $k => $v){
                $Model->where(array('name' => $k))->save(array('data' => $v, 'update_time' => NOW_TIME));
            }
            $Model->u_wallet_config();
            $this->success('操作成功');
        }else{
            $this->assign('list', $list);
            $this->meta_title = '钱包配置';
            $this->display(T('Addons://Wallet@Admin/Config/index'));
        }
    }
    
    // 充值优惠列表
    public function wallet_recharge_list()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list   = $this->lists(M('recharge_list'), $where, 'id asc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);        
        
        
        
        $this->meta_title = '充值优惠列表';
        $this->display(T('Addons://Wallet@Admin/Config/wallet_recharge_list'));
    }
    
    
    // 保证优惠选项
    public function save()
    {

    
        if(IS_POST){
            $money = I('money', 0, 'floatval');
            $give = I('give', 0, 'floatval');
            $info = I('info');
            $id = I('id', 0, 'intval');
            
            $money = round($money, 2);
            $give = round($give, 2);
            
            if ($money <= 0) {
                $this->error('充值金额需大于0');
            }
            
            if ($give < 0) {
                $this->error('赠送金额不得小于0');
            }
            
            $time = time();
            
            $data = array(
                'money' => $money,
                'give' => $give,
                'info' => $info,
                'id' => $id,
                'update_time' => $time,
            );
            

            
            // 有ID更新，没则新增
            if (empty($id)) {
                $data['create_time'] = $time;
                $res = M('recharge_list')->add($data);
                
            } else {
                $res = M('recharge_list')->where(array(
                    'id' => $id,
                ))->save($data);
            }
            
            if (empty($res)) {
                $this->error('操作失败');
            } else {
                $this->success('操作成功', addons_url('Wallet://ConfigAdmin:/wallet_recharge_list'));
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
            
            
        }else{
            
            $id = I('id', 0, 'intval');
            
            
            $data = array(
                'id' => 0,
                'money' => 0,
                'give' => 0,
                'info' => '',
            );
            
            // 有ID则取数据，没则不处理
            if (!empty($id)) {
                $info = M('recharge_list')->where(array(
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
            
            $this->meta_title = '充值优惠列表';
            $this->display(T('Addons://Wallet@Admin/Config/save'));   
        }        
     
    }
    
    
    // 删除 
    public function del()
    {
        if (IS_GET) {
            $id = I('id', 0, 'intval');
            
            if (empty($id)) {
                $this->error('非法操作');
            } else {
                $res = M('recharge_list')->where(array(
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
    
    
    
    
    /**
     * 活动获奖日志
     */
    public function logs() {

        
        
        

        
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list   = $this->lists(M('act_log'), $where, 'create_time desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);        
        
        
        
        $this->meta_title = '活动优惠券列表';
        $this->display(T('Addons://CashCoupon@Admin/ActCoupon/lists'));
    }    
}
