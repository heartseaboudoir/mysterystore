<?php
namespace Addons\AlipayServer\Controller;

use Admin\Controller\AddonsController;

class AlipayUserAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
     public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('AlipayUser'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '支付宝用户列表';
        $this->display(T('Addons://AlipayServer@Admin/AlipayUser/index'));
    }
    
    public function show() {
        $id = I('get.id',0);
        $Config = D('AlipayUser');
        $data = $Config->find($id);
        if(!$data){
            $this->error('数据不存在');
        }
        $data['data'] = json_decode($data['data'], true);
        $this->assign('data', $data);
        $this->meta_title = '微信用户详情';
        $this->display(T('Addons://AlipayServer@Admin/AlipayUser/show'));
    }
}
