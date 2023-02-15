<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseSupplyController extends AddonsController{
    public function __construct() {
        parent::__construct();
        //$this->check_store();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '供应商管理';
        
        //M('supply')->
        
        $where = array();
                
        if (empty($_SESSION['can_shequs_cg'])) {
            $where['shequ_id'] = -1;
        } else {
            $where['shequ_id'] = array('in', $_SESSION['can_shequs_cg']);
        }
        
        

        
        $list = $this->lists(D('Addons://Purchase/Supply'), $where, 's_sort desc, s_add_time desc');
        $this->assign('list', $list);        
        
        $this->display(T('Addons://Purchase@PurchaseSupply/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Purchase/Supply');
        $where = array();
        $where['s_id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        
                
        
        if (empty($_SESSION['can_shequs'])) {
            $shequs = array();
        } else {
            $shequs = M('shequ')->where(array(
                'id' => array('in', $_SESSION['can_shequs'])
            ))->select();
        }
        
        /*
        if ($_GET['sq']) {
            
            print_r($_SESSION['can_shequs']);
            
            print_r($shequs);
            
            exit;
        }
        */
        
        $this->assign('shequs', $shequs);        
        
        
        $this->meta_title = $id ? '编辑供应商' : '添加供应商';
        $this->display(T('Addons://Purchase@PurchaseSupply/save'));
    }
    
    
    public function update(){
        $Model = D('Addons://Purchase/Supply');
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
            $Model = D('Addons://Purchase/Supply');
            $res = $Model->where("s_id = $id")->delete();
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
        $s_sort = I('get.s_sort', 50);
        $Model = D('Addons://Purchase/Supply');
        $data = array(
            's_id' => $id,
            's_sort' => $s_sort,
        );
        $res = $Model->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }    
}
