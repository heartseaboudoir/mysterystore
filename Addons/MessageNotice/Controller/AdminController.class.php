<?php
namespace Addons\MessageNotice\Controller;

use Admin\Controller\AddonsController;

class AdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = D('Addons://MessageNotice/MessageNotice');
        $this->page_title = '消息通知';
        $this->config = array_column(M('MessageNoticeConfig')->select(), null, 'type');
        $this->assign('config', $this->config);
    }
    
    public function index(){
        $keyword = I('keyword', '', 'trim');
        $type = I('type', '', 'trim');
        $where = array();
        if($keyword){
            $where['act_id'] = $keyword;
        }
        $type && $where['type'] = $type;
        $this->callback_fun = 'set_index';
        parent::_index($where);
    }
    
    protected function set_index($data){
        foreach($data as $k => $v){
            $v['type_title'] = isset($this->config[$v['type']]['c_title']) ? $this->config[$v['type']]['c_title'] : '';
            $v['nickname'] = $v['uid'] > 0 ? get_nickname($v['uid']) : '全体用户';
            $data[$k] = $v;
        }
        return $data;
    }

}
