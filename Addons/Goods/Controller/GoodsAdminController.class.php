<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    public function enter(){
        // 只有角色为10时，直接进入统计列表
        if(in_array(10, $this->group_id) && !$this->checkRule('Admin/Addons/ex_goods?_addons=Goods&_controller=GoodsStoreAdmin&_action=index',2,null)){
            redirect(addons_url('Tongji://TongjiAdmin/day_lists'));
        }else{
            redirect(addons_url('Goods://GoodsStoreAdmin/index'));
        }
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $title = I('title', '', 'trim');
        $cate_id = I('cate_id', 0, 'intval');
        $status = I('status', 0, 'intval');
        $where = array();
        $cate_id && $where['cate_id'] = $cate_id;
        $status && $where['status'] = $status;
        if(!empty($title)){
            $condition['title'] = array('like', '%'.$title.'%');
            $condition['bar_code'] = array('like', '%'.$title.'%');
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
        }
        $list = $this->lists(D('Addons://Goods/Goods'), $where, 'listorder desc, create_time desc');
        $this->assign('list', $list);
        
        $cate_ls = M('GoodsCate')->select();
        !$cate_ls && $cate_ls = array();
        $_cate_ls = array();
        foreach($cate_ls as $c){
            $_cate_ls[$c['id']] = $c['title'];
        }
        $this->assign('cate_ls', $_cate_ls);
        
        $this->meta_title = '商品管理';
        $this->display(T('Addons://Goods@Admin/Goods/index'));
    }

    public function index2(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $title = I('title', '', 'trim');
        $cate_id = I('cate_id', 0, 'intval');
        $status = I('status', 0, 'intval');
        $where = array();
        $title && $where['title'] = array('like', '%'.$title.'%');
        $cate_id && $where['cate_id'] = $cate_id;
        $status && $where['status'] = $status;
        $list = $this->lists(D('Addons://Goods/Goods'), $where, 'listorder desc, create_time desc');
        $shequ_array = M('Shequ')->field('id,title')->select();
        
        $this->assign('list', $list);

        $cate_ls = M('GoodsCate')->select();
        !$cate_ls && $cate_ls = array();
        $_cate_ls = array();
        foreach($cate_ls as $c){
            $_cate_ls[$c['id']] = $c['title'];
        }
        $this->assign('cate_ls', $_cate_ls);

        $this->meta_title = '商品管理';
        $this->display(T('Addons://Goods@Admin/Goods/index2'));
    }
    
    public function save() {
        $id = I('get.id','');
        $cate_list = D('Addons://Goods/GoodsCate')->order('listorder desc, create_time desc')->select();
        if(!$cate_list){
            $this->error('请先添加商品分类', addons_url('Goods://GoodsCateAdmin:/save'));
        }
       
        $this->assign('cate_list', $cate_list);
        if($id){
        	//获取商品属性
        	$attr_value = M('AttrValue')->where(array('goods_id'=>$id,'status'=>array('neq',2)))->select();
        	foreach($attr_value as $k=>$v){
        		$attr_value[$k]['num'] = substr_count($v['bar_code'],"\n") + 1;
        	}
        	$this->assign('attr_value',$attr_value);
        	
            $Model = D('Addons://Goods/Goods');
            $where = array();
            $where['id'] = $id;
            $data = $Model->where($where)->find();
            $this->assign('data', $data);
        }
        $this->meta_title = $id ? '编辑商品' : '添加商品';
        $this->assign('category_list', D('Addons://Goods/GoodsCate')->get_cate_tree());
        $this->display(T('Addons://Goods@Admin/Goods/save'));
    }
    
    public function update(){
        $Model = D('Addons://Goods/Goods');
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $res['id'] && D('Addons://Goods/GoodsStore')->push_update('goods_by_id', $res['id'], 0);
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
    public function delete(){
        $id = I('get.id','');
        if($id){
            $Model = D('Addons://Goods/Goods');
            $res = $Model->where("id = $id")->save(array('status' => -1));
            if(!$res){
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            }else{
                $model = M('GoodsTag');
                $model->where(array('goods_id' => $id))->delete();
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }
    
    public function listorder(){
        $id = I('get.id', 0);
        $listorder = I('get.listorder', 50);
        $Goods = D('Addons://Goods/Goods');
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
    
    public function log(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $keyword = I('keyword', 0, 'intval');
        $where = array();
        $keyword && $where['goods_id'] = $keyword;
        $list = $this->lists(M('GoodsLog'), $where, 'create_time desc');
        foreach($list as &$v){
            $v['data'] = json_decode($v['data'], true);
        }
        $this->assign('list', $list);
        
        $cate_ls = M('GoodsCate')->select();
        !$cate_ls && $cate_ls = array();
        $_cate_ls = array();
        foreach($cate_ls as $c){
            $_cate_ls[$c['id']] = $c['title'];
        }
        $this->assign('cate_ls', $_cate_ls);
        
        $this->meta_title = '商品更新日志';
        $this->display(T('Addons://Goods@Admin/Goods/log'));
    }
    
    public function get_one(){
        $bar_code = I('bar_code', '', 'trim');
        $get_store = I('get_store', 0, 'intval');
        if(!$bar_code){
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
        if(!$data){
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('Goods')->where(array('id' => $data['goods_id'], 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if(!$data){
            $this->ajaxReturn(array('status' => 0));
        }
        if($get_store && $this->_store_id){
            $sell_type = session('user_store.sell_type');
            switch($sell_type){
                case 1:
                    if(!$data['sell_outline']){
                        $this->ajaxReturn(array('status' => 0, 'info' => '商品不支持线下销售'));
                    }
                    break;
                case 2:
                    if(!$data['sell_online']){
                        $this->ajaxReturn(array('status' => 0, 'info' => '商品不支持线上销售'));
                    }
                    break;
                default:
                        $this->ajaxReturn(array('status' => 0));
                    
            }
            A('Addons://Goods/GoodsStoreAdmin')->check_line($data['id']);
            $store = M('GoodsStore')->where(array('goods_id' => $data['id'], 'store_id' => $this->_store_id))->find();
            !$store && $store = array();
            $data['store'] = $store;
        }
        $this->ajaxReturn(array('status' => 1, 'data' => $data));
    }

    public function get_one_id(){
        $id_code = I('id_code', 0, 'intval');
        $bar_code = I('bar_code', '', 'trim');
        $get_store = I('get_store', 0, 'intval');
        if(empty($id_code)){
            $this->ajaxReturn(array('status' => 0));
        }

        $data = M('Goods')->where(array('id' => $id_code, 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if(!$data){
            $this->ajaxReturn(array('status' => 0));
        }
        if($get_store && $this->_store_id){
            $sell_type = session('user_store.sell_type');
            switch($sell_type){
                case 1:
                    if(!$data['sell_outline']){
                        $this->ajaxReturn(array('status' => 0, 'info' => '商品不支持线下销售'));
                    }
                    break;
                case 2:
                    if(!$data['sell_online']){
                        $this->ajaxReturn(array('status' => 0, 'info' => '商品不支持线上销售'));
                    }
                    break;
                default:
                        $this->ajaxReturn(array('status' => 0));
                    
            }
            A('Addons://Goods/GoodsStoreAdmin')->check_line($data['id']);
            $store = M('GoodsStore')->where(array('goods_id' => $data['id'], 'store_id' => $this->_store_id))->find();
            !$store && $store = array();
            $data['store'] = $store;
        }
        $this->ajaxReturn(array('status' => 1, 'data' => $data));
    }

    public function goods_shequ_price_set(){
        $this->display(T('Addons://Goods@Admin/Goods/goods_shequ_price_set'));
    }

    public function goods_shequ_price_issue(){
        $this->display(T('Addons://Goods@Admin/Goods/goods_shequ_price_issue'));
    }

    public function goods_shequ_price_issue_detail(){
        $this->display(T('Addons://Goods@Admin/Goods/goods_shequ_price_issue_detail'));
    }

    public function goods_selling_price_set(){
        $this->display(T('Addons://Goods@Admin/Goods/goods_selling_price_set'));
    }

    
    public function get_ol_lists(){
        $keyword = I('keyword', '', 'trim');
        $store_id = C('STORE_ONLINE');
        $Model = D('Addons://Goods/Goods')->alias('a');
        $where = array();
        $keyword && $where['a.title'] = array('like', '%'.$keyword.'%');
        $where['a.sell_online'] = 1;
        $where['a.status'] = 1;
        $where['b.store_id'] = $store_id;
        $join = '__GOODS_STORE__ as b ON a.id = b.goods_id';
        $field = 'a.id,a.title,a.cover_id';
        $_REQUEST['r'] = 20;
        $list = $this->lists($Model->join($join), $where, 'listorder desc, create_time desc', array(), $field);
        foreach($list as $k => $v){
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
            $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
            $list[$k] = $v;
        }
        if(IS_AJAX){
            $this->ajaxReturn(array('status' => 1, 'data' => $list));
            exit;
        }
        $this->assign('list', $list);
        $this->display(T('Addons://Goods@Admin/Goods/get_ol_lists'));
    }
}