<?php
namespace Addons\CashCoupon\Controller;

use Admin\Controller\AddonsController;

class CashCouponMakeAdminController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    public function config(){
        $Model = D('Addons://CashCoupon/CashCouponConfig');
        $UserApi = new \User\Client\Api();
        if(IS_POST){
            $data = $_POST;
            if(isset($data['name'])){
                unset($data['name']);
            }
            if(!$Model->create($data)){
                $this->error('操作失败');
            }
            if($Model->where(array('name' => 'pay_share'))->save($data)){
                $UserApi->execute('CashCoupon', 'config_info', array('name'  => 'pay_share', 'get_new' => true));
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }else{
            $req = $UserApi->execute('CashCoupon', 'config_info', array('name'  => 'pay_share'));
            if($req['status'] != 1){
                $this->error('获取失败');
            }
            $info = $req['data'];
            $this->assign('data', $info);
            $this->meta_title = '红包生成配置管理';
            $this->display(T('Addons://CashCoupon@Admin/CashCouponMake/config'));
        }
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list   = $this->lists(D('Addons://CashCoupon/CashCouponMake'), $where, 'create_time desc');
        foreach($list as $k => $v){
            $v['show_url'] = U('Wap/CashCoupon/lottery_coupon_lists', array('cash_code' => $v['code']));
            $list[$k] = $v;
        }
        $this->assign('list', $list);
        $this->meta_title = '购物红包列表';
        $this->display(T('Addons://CashCoupon@Admin/CashCouponMake/index'));
    }
    
}
