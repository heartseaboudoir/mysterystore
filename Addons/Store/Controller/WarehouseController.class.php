<?php
namespace Addons\Store\Controller;

use Admin\Controller\AddonsController;

class WarehouseController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function change_warehouse(){
        $warehouse_id = I('warehouse_id');
        $warehouses = null;
        if(!IS_ROOT && !in_array(1, $this->group_id)){
            $my_shequ = M('MemberWarehouse')->where(array('uid' => UID, 'type' => 2))->select();
            $my_warehouse = array();
            if($my_shequ){
                $shequ_ids = array();
                foreach($my_shequ as $v){
                    $shequ_ids[] = $v['warehouse_id'];
                    $group_shequ[$v['warehouse_id']][] = $v['group_id'];
                }
                $warehouse_data = M('Warehouse')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
                if($warehouse_data){
                    foreach($warehouse_data as $v){
                        $my_warehouse[$v['id']] = array(
                            'group_id' => $group_shequ[$v['shequ_id']],
                            'warehouse_id' => $v['id'],
                        );
                    }
                }
            }
            $_my_warehouse = M('MemberWarehouse')->where(array('uid' => UID, 'type' => 1))->field('group_id,warehouse_id')->select();
            foreach($_my_warehouse as $v){
                if(isset($my_warehouse[$v['warehouse_id']])){
                    !in_array($v['group_id'], $my_warehouse[$v['warehouse_id']]['group_id']) &&  $my_warehouse[$v['warehouse_id']]['group_id'][] = $v['group_id'];
                }else{
                    $my_warehouse[$v['warehouse_id']] = array(
                        'group_id' => array($v['group_id']),
                        'warehouse_id' => $v['warehouse_id'],
                    );
                }
            }
            if(!$my_warehouse){
                $this->error('未授权任何仓库管理');
            }
            $my_warehouse_access = array();
            $my_group = array();
            foreach($my_warehouse as $v){
                $my_warehouse_access[] = $v['warehouse_id'];
                $my_group[$v['warehouse_id']] = $v['group_id'];
            }
            if(empty($my_warehouse_access)){
                $this->error('未授权任何仓库管理');
            }
            $warehouses = $my_warehouse_access;
        }
        if($warehouse_id){
            if(!is_null($warehouses) && !in_array($warehouse_id, $warehouses)){
                $this->error('该仓库未授权管理');
            }
            $warehouse = M('Warehouse')->where(array('w_id' => $warehouse_id))->field('w_id, w_name, w_type')->find();
            if(!$warehouse){
                $this->error('仓库不存在');
            }
            // 当是授权时，才重新设置权限 
            /*
            if(isset($my_group[$warehouse_id])){
                $Auth       =   new \Think\Auth();
                $Auth->resetAuth(UID,array('in','1,2'), $my_group[$warehouse_id]);
                $Auth->resetAuth(UID,1, $my_group[$warehouse_id]);
                $Auth->resetAuth(UID,2, $my_group[$warehouse_id]);
            }
            */
            
            // 当是授权时，才重新设置权限 
            //if(!in_array(9, $this->group_id)){
            if(!IS_ROOT && !in_array(1, $this->group_id)){
                // 当前选择的门店权限+仓库权限
                if(isset($my_group[$warehouse_id])){
                    
                    $now_stroe_group = $this->getNowStoreGroup();
                    
                    $my_group_now = array_merge($my_group[$warehouse_id], $now_stroe_group);
                    
                    $my_group_now = array_unique($my_group_now);
                    
                    $Auth       =   new \Think\Auth();
                    $Auth->resetAuth(UID,array('in','1,2'), $my_group_now);
                    $Auth->resetAuth(UID,1, $my_group_now);
                    $Auth->resetAuth(UID,2, $my_group_now);
                }
                
            }            
            
            
            
            
            
            
            
            session('user_warehouse', $warehouse);
            redirect(Cookie('__forward__'));
            exit;
        }else{
            $cook = Cookie('__forward__');
            !$cook && Cookie('__forward__',empty($_SERVER['HTTP_REFERER']) ? U('/') : $_SERVER['HTTP_REFERER']);
        }
        $shequ = M('Shequ')->field('id, title')->select();
        !$shequ && $shequ = array();
        $where = array();
        $warehouses && $where['w_id'] = array('in', $warehouses);
        $warehouse = M('Warehouse')->where($where)->field('w_id, shequ_id , w_name, w_type')->select();
        !$warehouse && $warehouse = array();
        $_warehouse = array();
        foreach($warehouse as $v){
            $_warehouse[$v['shequ_id']][] = $v;
        }
        $this->assign('shequ', $shequ);
        $this->assign('warehouse', $_warehouse);
        $this->meta_title = '仓库切换';
        $this->display(T('Addons://Store@Admin/Warehouse/change_warehouse'));
    }
    
    public function my_index(){
        if($this->_warehouse_id > 0){
            $info = D('Addons://Warehouse/Warehouse')->where(array('uid' => UID, 'id' => $this->_warehouse_id))->find();
            if($info){
                $shequ = M('Shequ')->find($info['shequ_id']);
                $info['shequ_title'] = $shequ['title'];
            }
            $this->assign('info', $info);
        }
        $this->display(T('Addons://Store@Admin/Warehouse/my_index'));
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Store/Warehouse'), $where, 's_add_time asc');
        $this->assign('list', $list);
        $shequ_ls = M('Shequ')->field('id, title')->select();
        $_sq_ls = array();
        foreach($shequ_ls as $v){
            $_sq_ls[$v['id']] = $v['title'];
        }
        $this->assign('shequ_ls', $_sq_ls);
        $this->meta_title = '仓库管理';
        $this->display(T('Addons://Store@Admin/Warehouse/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Store/Warehouse');
        if($id != ''){
            $this->meta_title = '编辑仓库';
            $where = array();
            $where['w_id'] = $id;
            $data = $Model->where($where)->find();
            $this->assign('data', $data);
            $member = M('MemberWarehouse')->where(array('warehouse_id' => $id, 'type' => 1))->select();
            !$member && $member = array();
            $_member = array();
            $uids = array();
            foreach($member as $v){
                $_member[$v['group_id']][] = $v;
                $v['group_id'] == 1 && $uids[] = $v['uid'];
            }
            if($uids){
                $u_data = M('Member')->field('uid, nickname, pos_title, pos_id, bind_pos')->where(array('uid' => array('in', $uids)))->select();
                !$u_data && $u_data = array();
                $_member[1] = $u_data;
            }
            $this->assign('member_ls', $_member);
        }else{
            $this->meta_title =  '添加仓库';

        }
        $shequ_ls = M('Shequ')->field('id, title')->select();
        $_sq_ls = array();
        foreach($shequ_ls as $v){
            $_sq_ls[$v['id']] = $v['title'];
        }
        $this->assign('shequ_ls', $_sq_ls);

        $this->display(T('Addons://Store@Admin/Warehouse/save'));
    }
    
    public function update(){
        $Model = D('Addons://Store/Warehouse');
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
            $Model = D('Addons://Store/Warehouse');
            $res = $Model->where("w_id = $id")->delete();
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
