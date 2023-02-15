<?php
namespace Addons\Alipay\Controller;

use Admin\Controller\AddonsController;

class AlipayConfigAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Alipay/AlipayConfig'), $where);
        $this->assign('list', $list);
        $this->meta_title = '支付宝支付配置管理';
        $this->display(T('Addons://Alipay@Admin/AlipayConfig/index'));
    }
    
    public function save() {
        $id = I('id', 0, 'intval');
        $pid = I('pid', 0, 'intval');
        $Model = D('Addons://Alipay/AlipayConfig');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑支付宝支付配置' : '添加支付宝支付配置';
        $this->display(T('Addons://Alipay@Admin/AlipayConfig/save'));
    }
    
    public function update(){
        $Model = D('Addons://Alipay/AlipayConfig');
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
            $Model = D('Addons://Alipay/AlipayConfig');
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
    
}
