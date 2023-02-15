<?php
namespace Addons\Scorebox\Controller;

use Admin\Controller\AddonsController;

class ScoreboxConfigAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Scorebox/ScoreboxConfig'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '蜜糖规则管理';
        $this->display(T('Addons://Scorebox@Admin/ScoreboxConfig/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Scorebox/ScoreboxConfig');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑蜜糖规则' : '添加蜜糖规则';
        $this->display(T('Addons://Scorebox@Admin/ScoreboxConfig/save'));
    }
    
    public function update(){
        $Model = D('Addons://Scorebox/ScoreboxConfig');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
