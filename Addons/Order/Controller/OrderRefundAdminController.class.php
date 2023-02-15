<?php
namespace Addons\Order\Controller;

use Admin\Controller\AddonsController;

class OrderRefundAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = D('Addons://Order/OrderRefund');
        $this->page_title = '退款';
    }
    
    public function index(){
        $keyword = I('keyword', '', 'trim');
        $where = array();
        $where['order_sn|uid'] = $keyword;
        parent::_index($where, 'id desc');
    }
    
    public function do_refund(){
        $order_sn = I('order_sn', '', 'trim');
        if(!$order_sn){
            $this->error('请选择订单');
        }
        $where = array('order_sn' => $order_sn, 'type' => 'shop', 'status' => array('in', array(2,4)), 'refund_status' => array('in', array(0,3)));
        $order_info = M('Order')->where($where)->find();
        if(!$order_info){
            $this->error('订单不存在或不支持退款');
        }
        if($_POST){
            $reason = I('reason');
            $money = round(I('refund_money'), 2);
            if(!$money){
                $this->error('请填写退款金额');
            }
            if($money > $order_info['pay_money']){
                $this->error('退款金额不得大于订单支付金额');
            }
            $data = array(
                'order_sn' => $order_sn,
                'uid' => UID,
                'money' => $money,
                'reason' => $reason,
                'status' => 2,
                'is_system' => 1,
                'create_time' => NOW_TIME,
                'update_time' => NOW_TIME,
            );
            if(M('OrderRefund')->add($data)){
                if(D('Addons://Order/Order')->do_refund($money, $order_sn, $order_info)){
                    $this->success('操作成功',  Cookie('__forward__'));
                }else{
                    $this->error('操作失败');
                }
            }
        }else{
            $this->assign('order_info', $order_info);
            $this->meta_title = '发起退款';
            $this->display(T('Addons://Order@Admin/OrderRefundAdmin/do_refund'));
        }
    }
}
