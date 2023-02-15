<?php
namespace Addons\Shop\Controller;

use Admin\Controller\AddonsController;

class ShopAssessAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = D('Addons://Shop/ShopAssess');
        $this->page_title = '评价';
    }
    
    public function index(){
        $uid = I('uid', 0, 'intval');
        $where = array();
        $uid > 0 && $where['uid'] = $uid;
        $this->callback_fun = 'set_index';
        parent::_index($where, 'id desc');
    }
    
    protected function set_index($data){
        foreach($data as $k => $v){
            $v['goods_star'] = D('Addons://Shop/Shop')->get_t_star($v['goods_star']);
            $v['shop_star'] = D('Addons://Shop/Shop')->get_t_star($v['shop_star']);
            $v['star'] = D('Addons://Shop/Shop')->get_t_star($v['star']);
            $data[$k] = $v;
        }
        return $data;
    }


    public function save(){
        $this->callback_fun = 'set_save';
        parent::_save();
    }
    
    protected function set_save($data){
        if($data){
            $data['goods_star'] = D('Addons://Shop/Shop')->get_t_star($data['goods_star']);
            $data['shop_star'] = D('Addons://Shop/Shop')->get_t_star($data['shop_star']);
            $data['star'] = D('Addons://Shop/Shop')->get_t_star($data['star']);
        }
        return $data;
    }
    
    public function remove(){
        parent::_remove();
    }
}
