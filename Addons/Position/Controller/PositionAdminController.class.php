<?php
namespace Addons\Position\Controller;

use Admin\Controller\AddonsController;

class PositionAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Position/Position'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '推荐位列表';
        $this->display(T('Addons://Position@Admin/Position/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Position/Position');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑推荐位' : '添加推荐位';
        $this->display(T('Addons://Position@Admin/Position/save'));
    }
    
    public function update(){
        $Model = D('Addons://Position/Position');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
