<?php
namespace Addons\Store\Controller;

use Admin\Controller\AddonsController;

class ShequAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Store/Shequ'), $where, 'create_time asc');
        $this->assign('list', $list);
        $this->meta_title = '区域管理';
        $this->display(T('Addons://Store@Admin/Shequ/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Store/Shequ');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $member = M('MemberStore')->where(array('store_id' => $id, 'type' => 2))->select();
        !$member && $member = array();
        $_member = array();
        foreach($member as $v){
            $_member[$v['group_id']][] = $v;
        }
        $this->assign('member_ls', $_member);
        $this->meta_title = $id ? '编辑区域' : '添加区域';
        $this->display(T('Addons://Store@Admin/Shequ/save'));
    }
    
    public function update(){
        $Model = D('Addons://Store/Shequ');
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
            $Model = D('Addons://Store/Shequ');
            if(M('Store')->where(array('shequ_id' => $id))->field('id')->find()){
                $this->error('该区域已存在门店，无法删除');
            }
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
        $Shequ = D('Addons://Store/Shequ');
        $data = array(
            'id' => $id,
            'listorder' => $listorder,
        );
        $res = $Shequ->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }
}
