<?php
namespace Addons\Push\Controller;

use Admin\Controller\AddonsController;

class AdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
    }
    
    public function send_push(){
        $bind_config = array(
            1 => '官方文章',
            2 => '商品',
            3 => '系统通知',
        );
        
        
        if(IS_POST){
            
            $env = I('env');
            if (empty($env)) {
                $env = 0;
            }
            $device = I('device');
            if (empty($device)) {
                $device = 0;
            }
            
            $push_info = array(
                'env' => $env,
                'device' => $device,
            );
            
            //print_r($push_info);exit;

            $title = I('title', '', 'trim');
            if($title == ''){
                $this->error('请填写推送标题');
            }
            $action_type = I('bind_type', '', 'trim');
            $bind_id = I('bind_id', '');
            if($action_type == ''){
                $this->error('请选择关联内容');
            }
            $api = new \Addons\Push\Lib\Jpush\Api();
            $content = array(
                'extras' => 
                array(
                    'action_type' => $action_type,
                    'bind_id' => $bind_id,
                )
            );
            $respon = $api->send_notice($title, $content, $push_info);
            if($respon['status'] == 1){
                $this->success('内容推送成功');
            }else{
                $this->error('内容推送失败，错误信息：'.$respon['result']['error']['message']);
            }
        }else{
            $this->assign('bind_config', $bind_config);
            $this->meta_title = '内容推送';
            $this->display(T('Addons://Push@Admin/Admin/send_push'));
        }
    }
    
    public function get_form_lists(){
        $type = I('type');
        $act_id = I('get.act_id','','trim');
        $keyword = I('get.keyword','','trim');
        $where = array();
        $_REQUEST['r'] = 10;
        switch($type){
            case '1':
                $Model = M('Document');
                $field = 'id,title';
                $where = array();
                $keyword && $where['title'] =  array('like', '%'.$keyword.'%');
                $where['status'] = 1;
                $list = $this->lists($Model, $where, '', array(), $field);
                is_null($list) && $list = array();
                foreach($list as $k => $v){
                    $v['url'] = U('Article/edit', array('id' => $v['id']));
                    $list[$k] = $v;
                }
                break;
            case '2':
                $Model = M('ShopArticle');
                $field = 'id,title';
                $where = array();
                $keyword && $where['title'] =  array('like', '%'.$keyword.'%');
                $where['status'] = 1;
                $where['is_shelf']  =  1;
                $list = $this->lists($Model, $where, '', array(), $field);
                is_null($list) && $list = array();
                foreach($list as $k => $v){
                    $v['url'] = addons_url('Shop://ShopArticleAdmin:/save', array('id' => $v['id']));
                    $list[$k] = $v;
                }
                break;
            case '3':
                $Model = M('Document');
                $field = 'id,title';
                $where = array();
                $where['category_id'] = 108;
                $keyword && $where['title'] =  array('like', '%'.$keyword.'%');
                $where['status'] = 1;
                $list = $this->lists($Model, $where, '', array(), $field);
                is_null($list) && $list = array();
                foreach($list as $k => $v){
                    $v['url'] = U('Article/edit', array('id' => $v['id']));
                    $list[$k] = $v;
                }
                break;
            default:
                $this->error('类型不存在');
        }
        is_null($list) && $list = array();
        !is_array($act_ids) && $act_ids = $act_ids ? explode(',', $act_ids) : array();
        foreach($list as $k => $v){
            isset($v['cover_id']) && $v['pic_url'] = get_cover_url($v['cover_id']);
            $v['is_active'] = in_array($v['id'], $act_ids) ? 1 : 0;
            $list[$k] = $v;
        }
        if(IS_AJAX){
            $this->ajaxReturn(array('status' => 1, 'data' => $list));
            return;
        }
        $this->assign('list', $list);
        $this->display(T('Addons://Position@Admin/PositionData/get_form_lists'));
    }
}
