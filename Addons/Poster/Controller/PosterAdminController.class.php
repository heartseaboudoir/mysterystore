<?php
namespace Addons\Poster\Controller;

use Admin\Controller\AddonsController;

class PosterAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Poster/Poster'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '广告位列表';
        $this->display(T('Addons://Poster@Admin/Poster/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Poster/Poster');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑广告位' : '添加广告位';
        $this->display(T('Addons://Poster@Admin/Poster/save'));
    }
    
    public function update(){
        $Model = D('Addons://Poster/Poster');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
