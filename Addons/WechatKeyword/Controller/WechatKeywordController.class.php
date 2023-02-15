<?php
namespace Addons\WechatKeyword\Controller;

use Admin\Controller\AddonsController;

class WechatKeywordController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        A('Addons://Wechat/WechatAdmin')->set_ukey();
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['ukey'] = $this->ukey;
        $list = $this->lists(D('Addons://WechatKeyword/WechatKeyword'), $where);
        $this->assign('list', $list);
        $this->meta_title = '关键词管理';
        $this->display(T('Addons://WechatKeyword@WechatKeyword/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        if($id){
            $Model = D('Addons://WechatKeyword/WechatKeyword');
            $where = array();
            $where['ukey'] = $this->ukey;
            $data = $Model->where($where)->find($id);
            $this->assign('data', $data);
        }
        $this->meta_title = ($id ? '编辑' : '添加') . '关键词';
        $this->display(T('Addons://WechatKeyword@WechatKeyword/save'));
    }
    
    public function update(){
        if(empty($_POST['id'])){
            $_POST['ukey'] = $this->ukey;
        }
        $Model = D('Addons://WechatKeyword/WechatKeyword');
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
            $Model = D('Addons://WechatKeyword/WechatKeyword');
            $res = $Model->where(array('id' => $id, 'ukey' => $this->ukey))->delete();
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
        $WechatKeyword = D('Addons://WechatKeyword/WechatKeyword');
        $data = array(
            'id' => $id,
            'listorder' => $listorder,
        );
        $res = $WechatKeyword->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }
    
    public function get_art_list(){
        $where = array();
        /*
        $ukey = $this->is_admin ? I('ukey', '') : $this->ukey;
        $keyword = I('keyword', '');
        if(!$ukey){
            if(IS_POST){
                $this->ajaxReturn(array('status' => 0));
                exit;
            }else{
                exit($this->is_admin ? '请先选择公众号！' : '');
            }
        }
        $where['ukey'] = $ukey;
         * 
         */
        $keyword && $where['title'] =  array('like', '%'.$keyword.'%');
        $_GET['p'] = I('p', 0);
        $list = $this->lists(D('Document'), $where);
        is_null($list) && $list = array();
        foreach($list as &$v){
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
        }
        if(IS_POST){
            $this->ajaxReturn(array('status' => 1, 'data' => $list));
            return;
        }
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        $this->assign('ukey', $this->is_admin ? $ukey : '');
        $this->display(T('Addons://WechatKeyword@WechatKeyword/get_art_list'));
    }
}
