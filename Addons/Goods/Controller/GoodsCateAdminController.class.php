<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsCateAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    function get_data($list) {
        // 取一级菜单
        foreach ($list as $k => $vo) {
            if ($vo ['pid'] != 0)
                continue;

            $one_arr [$vo ['id']] = $vo;
            unset($list [$k]);
        }

        foreach ($one_arr as $p) {
            $data [] = $p;

            $two_arr = array();
            foreach ($list as $key => $l) {
                if ($l ['pid'] != $p ['id'])
                    continue;

                $l ['title'] = '├──' . $l ['title'];
                $two_arr [] = $l;
                unset($list [$key]);
            }

            $data = array_merge($data, $two_arr);
        }

        return $data;
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = D('Addons://Goods/GoodsCate')->where($where)->order('listorder desc, create_time asc')->select();
        $list = $this->get_data($list);
        $this->assign('list', $list);
        $this->meta_title = '商品分类管理';
        $this->display(T('Addons://Goods@Admin/GoodsCate/index'));
    }
    
    public function save() {
        $id = I('id', 0, 'intval');
        $pid = I('pid', 0, 'intval');
        $Model = D('Addons://Goods/GoodsCate');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        $this->assign('data', $data);
        !empty($data['pid']) && $pid = $data['pid'];
        $where = array();
        $where['status'] = 1;
        $where['id'] = array('neq', $id);
        $where['pid'] = 0;
        $parent = $Model->where($where)->select();
        $this->assign('parent', $parent);
        $this->assign('pid', $pid);
        $this->meta_title = $id ? '编辑商品分类' : '添加商品分类';
        $this->display(T('Addons://Goods@Admin/GoodsCate/save'));
    }
    
    public function update(){
        $Model = D('Addons://Goods/GoodsCate');
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
            $Model = D('Addons://Goods/GoodsCate');
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
        $Goods = D('Addons://Goods/GoodsCate');
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
    
    
    public function get_u_cate(){
        $Model = D('Addons://Goods/GoodsCate');
        $where = array(
            'status' => 1
        );
        $result = $Model->where($where)->order('listorder asc')->select();
        is_null($result) && $result = array();
        $this->ajaxReturn($result);
    }
}
