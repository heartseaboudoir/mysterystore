<?php
namespace Addons\Poster\Controller;

use Admin\Controller\AddonsController;

class PosterDataAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $pid = I('pid', 0, 'intval');
        if($pid < 1){
            $this->error('请选择广告位');
        }
        $this->poster = M('Poster')->find($pid);
        $this->assign('poster', $this->poster);
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['name'] = $this->poster['name'];
        $list = $this->lists(D('Addons://Poster/PosterData'), $where, 'listorder desc');
        $this->assign('list', $list);
        $this->meta_title = '广告内容管理';
        $this->display(T('Addons://Poster@Admin/PosterData/index'));
    }
    
    public function save() {
        $id = I('get.id',0, 'intval');
        if($id > 0){
            $Model = D('Addons://Poster/PosterData');
            $where = array();
            $where['id'] = $id;
            $where['name'] = $this->poster['name'];
            $data = $Model->where($where)->find();
            if(!$data){
                $this->error('广告不存在');
            }
            $this->assign('data', $data);
        }
        if($this->poster['is_access'] == 1){
            $shequ = M('Shequ')->select();
            $store = M('Store')->field('id,title,shequ_id')->select();
            $shequ_store = array();
            foreach($store as $v){
                $shequ_store[$v['shequ_id']][] = $v;
            }
            foreach($shequ as $k => $v){
                if(empty($shequ_store[$v['id']])){
                    unset($shequ[$k]);
                }
                $v['store'] = $shequ_store[$v['id']];
                $shequ[$k] = $v;
            }
            $this->assign('shequ', $shequ);
        }
        $this->meta_title = $id ? '编辑广告内容' : '添加广告内容';
        $this->display(T('Addons://Poster@Admin/PosterData/save'));
    }
    
    public function update(){
        $Model = D('Addons://Poster/PosterData');
        $_POST['name'] = $this->poster['name'];
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
    public function remove(){
        $id = I('get.id',0 ,'intval');
        if($id > 0){
            $Model = D('Addons://Poster/PosterData');
            $res = $Model->where(array( 'id' => $id, 'name' => $this->poster['name']))->delete();
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
        $listorder = I('get.listorder', 0);
        $Model = D('Addons://Poster/PosterData');
        $data = array(
            'id' => $id,
            'listorder' => $listorder,
        );
        $res = $Model->where(array( 'id' => $id, 'name' => $this->poster['name']))->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }
}
