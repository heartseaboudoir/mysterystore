<?php
namespace Addons\Scorebox\Controller;

use Admin\Controller\AddonsController;

class ScoreboxExchangeAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Scorebox/ScoreboxExchange'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '蜜糖兑换规则管理';
        $this->display(T('Addons://Scorebox@Admin/ScoreboxExchange/index'));
    }
    
    public function save() {
        $id = I('get.id', 0 ,'intval');
        if($id > 0){
            $Model = D('Addons://Scorebox/ScoreboxExchange');
            $where = array();
            $where['id'] = $id;
            $data = $Model->where($where)->find();
            $this->assign('data', $data);
        }else{
            $this->error('请选择数据');
        }
        $this->meta_title = $id ? '编辑蜜糖兑换规则' : '添加蜜糖兑换规则';
        $this->display(T('Addons://Scorebox@Admin/ScoreboxExchange/save'));
    }
    
    public function update(){
        if(empty($_POST['id'])) $this->error('请选择数据');
        $Model = D('Addons://Scorebox/ScoreboxExchange');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
}
