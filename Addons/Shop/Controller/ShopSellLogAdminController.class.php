<?php
namespace Addons\Shop\Controller;

use Admin\Controller\AddonsController;

class ShopSellLogAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->page_title = '销售日志';
    }
    
    public function index(){
        $uid = I('shop_uid', 0, 'intval');
        $aid = I('aid', 0, 'intval');
        $start_date = I('start_date', '');
        $end_date = I('end_date', '');
        $where = array();
        $uid > 0 && $where['uid'] = $uid;
        $aid > 0 && $where['aid'] = $aid;
        if($start_date){
            $where['create_time'] = array('egt', strtotime($start_date));
        }elseif($end_date){
            $where['create_time'] = array('elt', strtotime($end_date)+3600*24);
        }
        if($start_date && $end_date){
            $where['create_time'] = array('between', array(strtotime($start_date), strtotime($end_date)+3600*24));
        }
        $this->model = M('ShopSellLog');
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
    
    public function day_index(){
        $uid = I('shop_uid', 0, 'intval');
        $aid = I('aid', 0, 'intval');
        $start_date = I('start_date', '');
        $end_date = I('end_date', '');
        $where = array();
        $uid > 0 && $where['uid'] = $uid;
        $aid > 0 && $where['aid'] = $aid;
        if($start_date){
            $where['date'] = array('egt', $start_date);
        }elseif($end_date){
            $where['date'] = array('elt', $end_date);
        }
        if($start_date && $end_date){
            $where['date'] = array('between', array($start_date, $end_date));
        }
        $this->model = M('ShopSellDLog');
        $this->callback_fun = 'set_index';
        $this->meta_title = '销售统计（天）';
        parent::_index($where, 'id desc');
    }
}
