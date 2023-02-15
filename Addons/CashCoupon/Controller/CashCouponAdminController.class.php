<?php
namespace Addons\CashCoupon\Controller;

use Admin\Controller\AddonsController;

class CashCouponAdminController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list   = $this->lists(D('Addons://CashCoupon/CashCoupon'), $where, 'create_time desc');
        foreach($list as $k => $v){
            $v['show_url'] = U('Wap/CashCoupon/receive_coupon_lists', array('cash_code' => $v['code']));
            $list[$k] = $v;
        }
        $this->assign('list', $list);
        $this->meta_title = '优惠券列表';
        $this->display(T('Addons://CashCoupon@Admin/CashCoupon/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://CashCoupon/CashCoupon');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑优惠券' : '添加优惠券';
        $this->display(T('Addons://CashCoupon@Admin/CashCoupon/save'));
    }
    
    public function update(){
        $Model = D('Addons://CashCoupon/CashCoupon');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
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
