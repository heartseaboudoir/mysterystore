<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class DealerAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Goods/Dealer'), $where, 'listorder desc, create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '经销商管理';
        $this->display(T('Addons://Goods@Admin/Dealer/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Goods/Dealer');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑经销商' : '添加经销商';
        $this->display(T('Addons://Goods@Admin/Dealer/save'));
    }
    
    public function update(){
        $Model = D('Addons://Goods/Dealer');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
    public function delete(){
        $id = I('get.id','');
        if($id){
            $Model = D('Addons://Goods/Dealer');
            $res = $Model->where("id = $id")->delete();
            if(!$res){
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            }else{
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }
    
    public function listorder(){
        $id = I('get.id', 0);
        $listorder = I('get.listorder', 50);
        $Goods = D('Addons://Goods/Dealer');
        $data = array(
            'id' => $id,
            'listorder' => $listorder,
        );
        $res = $Goods->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }
}
