<?php
namespace Addons\Shop\Controller;

use Admin\Controller\AddonsController;

class ShopAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = D('Addons://Shop/Shop');
        $this->page_title = '店铺';
    }
    
    public function index(){
        $keyword = I('keyword', '', 'trim');
        $where = array();
        if($keyword){
            $uid = reset_data_field(M('Member')->where(array('nickname' => array('like', '%'.$keyword.'%')))->select(), 'uid', 'uid');
            if(is_numeric($keyword)){
                $uid[] = $keyword;
            }
            $where['uid'] = $uid ? array('in', $uid) : '-1';
        }
        $this->callback_fun = 'set_index';
        parent::_index($where);
    }
    
    protected function set_index($data){
        $Model = M('ShopArticle');
        foreach($data as $k => $v){
            $v['goods_num'] = $Model->where(array('uid' => $v['uid'], 'status' => 1, 'is_shelf' => 1))->field('id')->count();
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
            $data['goods_num'] = M('ShopArticle')->where(array('uid' => $data['uid'], 'status' => 1, 'is_shelf' => 1))->field('id')->count();
        }
        return $data;
    }
}
