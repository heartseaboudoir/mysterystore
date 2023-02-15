<?php
namespace Addons\Scorebox\Controller;

use Admin\Controller\AddonsController;

class ScoreboxLevelAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Scorebox/ScoreboxLevel'), $where, 'exper asc');
        $this->assign('list', $list);
        $this->meta_title = '等级管理';
        $this->display(T('Addons://Scorebox@Admin/ScoreboxLevel/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Scorebox/ScoreboxLevel');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑等级' : '添加等级';
        $this->display(T('Addons://Scorebox@Admin/ScoreboxLevel/save'));
    }
    
    public function update(){
        $Model = D('Addons://Scorebox/ScoreboxLevel');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            \User\Client\Api::execute('Scorebox', 'level_data', array('update' => 1));
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
