<?php
namespace Addons\Shop\Controller;

use Admin\Controller\AddonsController;

class ShopCommentAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = M('ShopComment');
        $this->page_title = '评论';
    }
    
    public function index(){
        $aid = I('aid', 0, 'intval');
        $where = array();
        $aid > 0 && $where['aid'] = $aid;
        $this->callback_fun = 'set_index';
        parent::_index($where, 'id desc');
    }
    
    protected function set_index($data){
        $aid = reset_data_field($data, 'id', 'aid');
        $art_data = reset_data_field(M('ShopArticle')->where(array('id' => array('in', $aid)))->field('id,title')->select(), 'id', 'title');
        foreach($data as $k => $v){
            $v['art_title'] = isset($art_data[$v['aid']]) ? $art_data[$v['aid']] : '';
            $data[$k] = $v;
        }
        return $data;
    }

    public function save(){
        $id = I('id', 0, 'intval');
        if(!($id > 0)){
            $this->error('请选择数据');
        }
        $this->callback_fun = 'set_save';
        parent::_save();
    }
    
    protected function set_save($data){
        if($data){
            $art_data = M('ShopArticle')->where(array('id' => $data['aid']))->field('id,title')->find();
            $data['art_title'] = $art_data['title'];
            $pdata = $this->model->find($data['pid']);
            $data['pcontent'] = $pdata['content'];
        }
        return $data;
    }
    
    public function remove(){
        parent::_remove();
    }
}
