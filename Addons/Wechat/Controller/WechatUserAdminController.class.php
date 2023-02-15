<?php
namespace Addons\Wechat\Controller;

use Admin\Controller\AddonsController;

class WechatUserAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
     public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('WechatUser'), $where, '');
        $this->assign('list', $list);
        $this->meta_title = '微信用户列表';
        $this->display(T('Addons://Wechat@Admin/WechatUser/index'));
    }
    
    public function show() {
        $id = I('get.id',0);
        $Config = D('WechatUser');
        $data = $Config->find($id);
        if(!$data){
            $this->error('数据不存在');
        }
        $data['data'] = json_decode($data['data'], true);
        $this->assign('data', $data);
        $this->meta_title = '微信用户详情';
        $this->display(T('Addons://Wechat@Admin/WechatUser/show'));
    }
}
