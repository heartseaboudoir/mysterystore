<?php
namespace Addons\Store\Controller;

use Admin\Controller\AddonsController;

class StoreAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function change_store(){

        $store_id = I('store_id');
        if($store_id == '0' && session('user_store') != ''){
            session('user_store',array());
        }
        $stores = null;
        if(!IS_ROOT && !in_array(1, $this->group_id)){
            // && !in_array(9, $this->group_id)
            $my_shequ = M('MemberStore')->where(array('uid' => UID, 'type' => 2))->select();
            $my_store = array();
            if($my_shequ){
                $shequ_ids = array();
                $group_shequ = array();
                foreach($my_shequ as $v){
                    $shequ_ids[] = $v['store_id'];
                    $group_shequ[$v['store_id']][] = $v['group_id'];
                }
                $store_data = M('Store')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
                if($store_data){
                    foreach($store_data as $v){
                        $my_store[$v['id']] = array(
                            'group_id' => $group_shequ[$v['shequ_id']],
                            'store_id' => $v['id'],
                        );
                    }
                }
            }
            $_my_store = M('MemberStore')->where(array('uid' => UID, 'type' => 1))->field('group_id,store_id')->select();
            foreach($_my_store as $v){
                if(isset($my_store[$v['store_id']])){
                    !in_array($v['group_id'], $my_store[$v['store_id']]['group_id']) &&  $my_store[$v['store_id']]['group_id'][] = $v['group_id'];
                }else{
                    $my_store[$v['store_id']] = array(
                        'group_id' => array($v['group_id']),
                        'store_id' => $v['store_id'],
                    );
                }
            }
            if(!$my_store){
                $this->error('未授权任何门店管理');
            }
            $my_store_access = array();
            $my_group = array();
            foreach($my_store as $v){
                $my_store_access[] = $v['store_id'];
                $my_group[$v['store_id']] = $v['group_id'];
            }
            if(empty($my_store_access)){
                $this->error('未授权任何门店管理');
            }
            $stores = $my_store_access;
        }
        if($store_id){
            if(!is_null($stores) && !in_array($store_id, $stores)){
                $this->error('该门店未授权管理');
            }
            $store = M('Store')->where(array('id' => $store_id))->field('id, title, sell_type')->find();
            if(!$store){
                $this->error('门店不存在');
            }
            // 当是授权时，才重新设置权限 
            //if(!in_array(9, $this->group_id)){
            if(!IS_ROOT && !in_array(1, $this->group_id)){


                $quanju_group = array();
                if(in_array(18,$this->group_id)){
                    $quanju_group = array(18);
                }
                // 当前选择的门店权限+仓库权限
                if(isset($my_group[$store_id])){
                    
                    $now_warehouse_group = $this->getNowWarehouseGroup();
                    
                    $my_group_now = array_merge($my_group[$store_id], $now_warehouse_group);

                    $my_group_now = array_unique($my_group_now,$quanju_group);

                    $Auth       =   new \Think\Auth();
                    $Auth->resetAuth(UID,array('in','1,2'), $my_group_now);
                    $Auth->resetAuth(UID,1, $my_group_now);
                    $Auth->resetAuth(UID,2, $my_group_now);
                }
                
            }
            session('user_store', $store);
            redirect(Cookie('__forward__'));
            exit;
        }else{
            $cook = Cookie('__forward__');
            !$cook && Cookie('__forward__',empty($_SERVER['HTTP_REFERER']) ? U('/') : $_SERVER['HTTP_REFERER']);
        }
        $shequ = M('Shequ')->field('id, title')->select();
        !$shequ && $shequ = array();
        $where = array();
        $stores && $where['id'] = array('in', $stores);
        $where['status'] = array('eq', 1);
        $store = M('Store')->where($where)->field('id, shequ_id , title, sell_type')->select();
        !$store && $store = array();
        $_store = array();
        foreach($store as $v){
            $_store[$v['shequ_id']][] = $v;
        }

        $this->assign('shequ', $shequ);
        $this->assign('store', $_store);
        $this->assign('store_count', count($store));
        $this->meta_title = '门店管理切换';
        $this->display(T('Addons://Store@Admin/Store/change_store'));
    }
    
    public function my_index(){
        if($this->_store_id > 0){
            $info = D('Addons://Store/Store')->where(array('uid' => UID, 'id' => $this->_store_id))->find();
            if($info){
                $shequ = M('Shequ')->find($info['shequ_id']);
                $info['shequ_title'] = $shequ['title'];
            }
            $this->assign('info', $info);
        }
        $this->display(T('Addons://Store@Admin/Store/my_index'));
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        
        $sid  = I('get.sid', 0, 'intval');
        $keyword  = I('get.keyword', '', 'trim');
        
        $where = array();
        $_string = "1=1";        
        if (!empty($keyword)) {
            $_string .= " and title like '%{$keyword}%'";
        }
        
        if (!empty($sid)) {
            $_string .= " and shequ_id = {$sid}";
        }
        
        $where['_string'] = $_string;
        $list = $this->lists(D('Addons://Store/Store'), $where, 'create_time asc');
        $this->assign('list', $list);
        $shequ_ls = M('Shequ')->field('id, title')->select();
        $_sq_ls = array();
        foreach($shequ_ls as $v){
            $_sq_ls[$v['id']] = $v['title'];
        }
        $this->assign('shequ_ls', $_sq_ls);
        $this->meta_title = '门店管理';
        $this->display(T('Addons://Store@Admin/Store/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Store/Store');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        $this->meta_title = $id ? '编辑门店' : '添加门店';
        $shequ_ls = M('Shequ')->field('id, title')->select();
        $_sq_ls = array();
        foreach($shequ_ls as $v){
            $_sq_ls[$v['id']] = $v['title'];
        }
        $this->assign('shequ_ls', $_sq_ls);
        $member = M('MemberStore')->where(array('store_id' => $id, 'type' => 1))->select();
        !$member && $member = array();
        $_member = array();
        $uids = array();
        foreach($member as $v){
            $_member[$v['group_id']][] = $v;
            $v['group_id'] == 4 && $uids[] = $v['uid'];
        }
        if($uids){
            $u_data = M('Member')->field('uid, nickname, pos_title, pos_id, bind_pos')->where(array('uid' => array('in', $uids)))->select();
            !$u_data && $u_data = array();
            $_member[4] = $u_data;
        }
        $this->assign('member_ls', $_member);
        $this->display(T('Addons://Store@Admin/Store/save'));
    }
    
    public function update(){
        $Model = D('Addons://Store/Store');
        //验证数值
        $rate_val = I('post.rate_val',0);
        $occupancy_rate = I('post.occupancy_rate',0);
        if($rate_val > 100){
            $this->error('折扣比例不可大于100');
            exit;
        }
        if($rate_val < 0){
            $this->error('折扣比例不可小于0');
            exit;
        }
        if($occupancy_rate > 100){
            $this->error('入住率不可大于100');
            exit;
        }
        if($occupancy_rate < 0){
            $this->error('入住率不可小于0');
            exit;
        }
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $wechat = A('Addons://Wechat/Wechatclass');
            $_GET['ukey'] = $res['id'];
            $config = json_decode($res['pay'],true);
            $wechat->update_cache($config['wx']);
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
    
    // 获取默认支付参数
    public function get_pay_params()
    {
        //{"wx":{"appid":"wxaae571b71aee75d0","appsecret":"be75c3f741ccccc3c4ab0a6b9cda7213","mchid":"1298759801","key":"fE2D7Cb6Zcfe1c18c1f9b978AbeT612N"},"ali":{"appid":"2015121600984156"}}
        
        
        $payParams = array(
            'wx' => array(
                'appid' => 'wxaae571b71aee75d0',
                'appsecret' => 'be75c3f741ccccc3c4ab0a6b9cda7213',
                'mchid' => '1298759801',
                'key' => 'fE2D7Cb6Zcfe1c18c1f9b978AbeT612N',
            ),
            'ali' => array(
                'appid' => '2015121600984156',
            ),
            
        );
        
        $data = json_encode(array('data' => $payParams));
        
        echo $data;
    }
    
    public function delete(){
        $id = I('get.id','');
        if($id){
            $Model = D('Addons://Store/Store');
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
        $Store = D('Addons://Store/Store');
        $data = array(
            'id' => $id,
            'listorder' => $listorder,
        );
        $res = $Store->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }
}
