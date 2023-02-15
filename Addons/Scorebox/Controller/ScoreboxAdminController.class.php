<?php
namespace Addons\Scorebox\Controller;

use Admin\Controller\AddonsController;

class ScoreboxAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Scorebox/Scorebox'), $where, 'create_time desc');
        foreach($list as $k => $v){
            $level = D('Addons://Scorebox/Scorebox')->get_level($v['exper']);
            $v['level'] = $level;
            $list[$k] = $v;
        }
        $this->assign('list', $list);
        $this->meta_title = '用户信息列表';
        $this->display(T('Addons://Scorebox@Admin/Scorebox/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Scorebox/Scorebox');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑用户信息' : '添加用户信息';
        $this->display(T('Addons://Scorebox@Admin/Scorebox/save'));
    }
    
    public function update(){
        $Model = D('Addons://Scorebox/Scorebox');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
