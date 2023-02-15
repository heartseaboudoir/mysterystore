<?php
namespace Addons\Order\Controller;

use Admin\Controller\AddonsController;

class OrderExpressAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = D('Addons://Order/OrderExpress');
        $this->page_title = '快递公司';
    }
    
    public function index(){
        $keyword = I('keyword', '', 'trim');
        $where = array();
        $keyword && $where['company|name'] = array('like', "%".$keyword."%");
        parent::_index($where, 'status asc, listorder desc, id asc');
    }
    
    public function log_index(){
        $this->model = M('OrderExpressLog');
        $this->meta_title = '物流信息管理';
        $this->callback_fun = 'set_log_index';
        parent::_index();
    }
    protected function set_log_index($data){
        if($data){
            $name = reset_data_field($data, 'id', 'company_name');
            $express_data = reset_data_field(M('OrderExpress')->where(array('name' => array('in', $name)))->select(), 'name', 'company');
            foreach($data as $k => $v){
                $v['company'] = isset($express_data[$v['company_name']]) ? $express_data[$v['company_name']] : '' ;
                $data[$k] = $v;
            }
        }
        return $data;
    }
    public function log_info(){
        $order_sn = I('order_sn', '');
        $no = I('no', '');
        $where = array();
        $order_sn && $where['order_sn'] = $order_sn;
        $no && $where['no'] = $no;
        $this->model = M('OrderExpressLog');
        $this->callback_fun = 'set_log_info';
        $this->meta_title = '物流详情';
        parent::_save($where);
    }
    
    protected function set_log_info($data){
        if(!$data){
            $this->error('快递信息不存在');
        }
        $data['data'] = json_decode($data['data'], true);
        $express = M('OrderExpress')->where(array('name' => $data['company_name']))->find();
        $data['company'] = $express ? $express['company'] : '';
        return $data;
    }
    
    public function get_form_lists(){
        $type = I('type');
        $act_id = I('get.act_id','','trim');
        $keyword = I('get.keyword','','trim');
        $where = array();
        $_REQUEST['r'] = 10;
        $Model = M('OrderExpress');
        $field = 'name,company';
        $where = array();
        $where['status'] = 1;
        $keyword && $where['company'] =  array('like', '%'.$keyword.'%');
        $list = $this->lists($Model, $where, '', array(), $field);
        is_null($list) && $list = array();
        foreach($list as $k => $v){
            $v['id'] = $v['name'];
            $v['title'] = $v['company'];
            $list[$k] = $v;
        }
        is_null($list) && $list = array();
        !is_array($act_ids) && $act_ids = $act_ids ? explode(',', $act_ids) : array();
        foreach($list as $k => $v){
            $v['is_active'] = in_array($v['name'], $act_ids) ? 1 : 0;
            $list[$k] = $v;
        }
        if(IS_AJAX){
            $this->ajaxReturn(array('status' => 1, 'data' => $list));
            return;
        }
        $this->assign('list', $list);
        $this->display(T('Addons://Order@Admin/OrderExpressAdmin/get_form_lists'));
    }
}
