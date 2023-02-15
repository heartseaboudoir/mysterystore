<?php
namespace Addons\AlipayServer\Controller;

use Admin\Controller\AddonsController;

class AlipayServerAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
     public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $_GET['id'] = 1;
        $this->save();
        exit;
        $where = array();
        $list = $this->lists(D('Addons://AlipayServer/AlipayServerConfig'), $where, 'update_time desc');
        $this->assign('list', $list);
        $this->meta_title = '服务窗配置';
        $this->display(T('Addons://AlipayServer@Admin/AlipayServer/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        if($id){
            $Config = D('Addons://AlipayServer/AlipayServerConfig');
            $data = $Config->find($id);
            if(!$data){
                $this->error('服务窗配置不存在');
            }
            $this->assign('data', $data);
        }
        $this->meta_title = ($id ? '编辑' : '添加'). '服务窗配置';
        $this->display(T('Addons://AlipayServer@Admin/AlipayServer/save'));
    }
    
    public function update(){
        $Config = D('Addons://AlipayServer/AlipayServerConfig');
        $res = $Config->update();
        if(!$res){
            $this->error($Config->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
