<?php
namespace Addons\Store\Controller;

use Admin\Controller\AddonsController;

class MemberAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->type = I('type', 1, 'intval');
        $this->assign('_type', $this->type);
        $this->group_ids = array(2,3,4,10,15,17);
    }
    public function index(){
        $store_id = I('store_id', 0, 'intval');
        $group = I('group', 4, 'intval');
        $where = array();
        $store_id > 0 && $where['store_id'] = $store_id;
        $where['group_id'] = $group;
        $lists = $this->lists(M('MemberStore'), $where);
        if($lists){
            $uid = array();
            foreach($lists as $v){
                $uid[] = $v['uid'];
            }
            $data = M('Member')->where(array('uid' => array('in', $uid)))->field('uid, nickname, pos_id')->select();
            foreach($data as $d){
                $_data[$d['uid']] = $d;
            }
            foreach($lists as $k => $v){
                $v['pos_id'] = $_data[$v['uid']]['pos_id'];
                $v['nickname'] = $_data[$v['uid']]['nickname'];
                $lists[$k] = $v;
            }
        }
        $this->assign('list', $lists);
        $this->meta_title = '管理员列表';
        $this->display(T('Addons://Store@Admin/Member/index'));
    }
    public function save() {
        $id = I('get.id',''); 
        $store_id = I('store_id', 0, 'intval');
        $group = I('group', 0, 'intval');
        $type = I('type', 0, 'intval');
        $in_uid = array();
        if($type == 2){
            $store_ls = M('Shequ')->field('id, title')->select();
            $_sq_ls = array();
            foreach($store_ls as $v){
                $_sq_ls[$v['id']] = $v['title'];
            }
            $this->assign('store_ls', $_sq_ls);
            $in_data = M('MemberStore')->where(array('store_id' => $store_id, 'type' => 2, 'group_id' => array('in', array($group, 4))))->select();
            foreach($in_data as $v){
                $in_uid[] = $v['uid'];
            }
        }else{
            $store_ls = M('Store')->field('id, title')->select();
            $_sq_ls = array();
            foreach($store_ls as $v){
                $_sq_ls[$v['id']] = $v['title'];
            }
            $this->assign('store_ls', $_sq_ls);
            $where = array('store_id' => $store_id, 'type' => 1);
            $group != 4 && $where['group_id'] = array('in', array($group, 4));
            $in_data = M('MemberStore')->where($where)->select();
            foreach($in_data as $v){
                $in_uid[] = $v['uid'];
            }
        }
        $member_ls = M('Member')->where(array('status' => 1, 'uid' => array('not in', $in_uid)))->field('uid, nickname')->order('uid desc')->select();
        $this->assign('member_ls', $member_ls);
        $this->meta_title = $id ? '编辑管理员' : '添加管理员';
        $this->display(T('Addons://Store@Admin/Member/save'));
    }
    
    public function bind(){
        if(IS_POST){
            $uid = I('id', 0, 'intval');
            if($uid < 1){
                $this->error('请选择管理员');
            }
            $bind_pos = I('bind_pos', '', 'trim');
            if(M('Member')->where(array('uid' => $uid))->save(array('bind_pos' => $bind_pos))){
                $this->success('操作成功');
            }else{
                $this->success('操作失败');
            }
            exit;
        }
        $id = I('get.id',''); 
        $info = M('Member')->where(array('uid' => $id))->find();
        $this->assign('data', $info);
        $this->meta_title = '绑定设置';
        $this->display(T('Addons://Store@Admin/Member/bind'));
    }
    
    public function get_lists(){
        $store_id = I('store_id', 0, 'intval');
        $group = I('group', 0, 'intval');
        $data = array();
        if($store_id > 0 && $group > 0){
            $where = array();
            // 当为设备管理员时，门店只能关联一个用户
            if($group != 4){
                $where['store_id'] = $store_id;
                $where['group_id'] = array('in', array($group, 4));
            }
            $where['type'] = $this->type;
            $_u = M('MemberStore')->where($where)->select();
            $uids = array();
            $uids[] = 1;
            if($_u){
                foreach($_u as $v){
                    $uids[] = $v['uid'];
                }
            }
            $where = array();
            $where['status'] = 1;
            $where['is_admin'] = 1;
            $uids && $where['uid'] = array('not in', $uids);
            $data = D('Member')->where($where)->field('uid,nickname')->select();
            !$data && $data = array();
        }
        $this->ajaxReturn($data);
    }
    public function update(){
        if(!IS_POST){
            $this->error('操作失败');
        }
        $store_id = I('store_id', 0, 'intval');
        $group = I('group', 0, 'intval');
        $user = I('user');
        if($store_id < 1){
            $this->error('请选择所属门店');
        }
        if($group < 1){
            $this->error('请选择所属管理员类型');
        }
        if(!in_array($group, $this->group_ids )){
            $this->error('请正确选择管理员类型');
        }
        if(!$user || !is_array($user)){
            $this->error('请选择用户');
        }
        $i = 0;
        foreach($user as $v){
            $data = array(
                'group_id' => $group,
                'uid' => $v,
                'type' => $this->type,
                'store_id' => $store_id
            );
            if(M('MemberStore')->add($data)){
                // 当角色为设备管理员时，同时更新会员表
                if($group == 4){
                    M('Member')->where(array('uid' => $v))->save(array('store_id' => $store_id));
                }
                M('AuthGroupAccess')->add(array('uid' => $v, 'group_id' => $group));
                $i++;
            }
        }
        $this->success('操作成功，成功添加'.$i.'个管理员', Cookie('__forward__'));
    }
    
    public function delete(){
        $id = I('get.id','');
        $group = I('group',0, 'intval');
        $store_id = I('store_id',0, 'intval');
        $type = I('type',1, 'intval');
        if(!in_array($group, $this->group_ids )){
            $this->error('请正确选择管理员类型');
        }
        if($store_id < 1){
            $this->error('请选择门店');
        }
        if($id){
            $Model = D('Member');
            if($group == 4){
                $Model->where(array('uid' => $id))->save(array('store_id' => 0));
            }
            $res = M('MemberStore')->where(array('uid' => $id, 'type' => $type, 'group_id' => $group, 'store_id' => $store_id))->delete();
            if(!$res){
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            }else{
                if(!M('MemberStore')->where(array('uid' => $id, 'group_id' => $group))->find()){
                    M('AuthGroupAccess')->where(array('uid' => $id, 'group_id' => $group))->delete();
                }
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }
    
    public function log_index(){
        $uid = I('id', 0, 'intval');
        if(!$uid){
            $this->error('请选择管理员');
        }
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['uid'] = $uid;
        $list = $this->lists(D('LoginStoreLog'), $where, '`in` desc');
        $this->assign('list', $list);
        $this->meta_title = '设备管理员操作记录';
        $this->display(T('Addons://Store@Admin/Member/log_index'));
    }
}
