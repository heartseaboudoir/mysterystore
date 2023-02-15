<?php
namespace Addons\Position\Controller;

use Admin\Controller\AddonsController;

class PositionDataAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        if(I('_action') != 'get_form_lists'){
            $pos_id = I('pos_id', 0, 'intval');
            if($pos_id < 1){
                $this->error('请选择推荐位');
            }
            $this->position = M('Position')->find($pos_id);
            if(!$this->position){
                $this->error('推荐位不存在');
            }
            $this->assign('position', $this->position);
        }
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['pos_id'] = $this->position['id'];
        $list = $this->lists(D('Addons://Position/PositionData'), $where, 'listorder desc');
        $this->assign('list', $list);
        $this->meta_title = '【'.$this->position['title'].'】 推荐位内容管理';
        $this->display(T('Addons://Position@Admin/PositionData/index'));
    }
    
    public function save() {
        $id = I('get.id',0, 'intval');
        $Model = D('Addons://Position/PositionData');
        if($id > 0){
            $where = array();
            $where['id'] = $id;
            $where['pos_id'] = $this->position['id'];
            $data = $Model->where($where)->find();
            if($data && $data['bind_type'] && $data['bind_id'] > 0){
                switch($data['bind_type']){
                    case 'shop_article':
                        $Bind_Model = M('ShopArticle');
                        $data['bind_data'] = $Bind_Model->where(array('id' => $data['bind_id']))->field('id,title')->find();
                        $data['bind_data']['url'] = addons_url('Shop://ShopArticleAdmin://save', array('id' => $data['bind_id']));
                        break;
                    case 'member':
                        $Bind_Model = M('author');
                        $data['bind_data'] = array();
                        $data['bind_data']['title'] = get_nickname($data['bind_id']);
                        $data['bind_data']['pic_url'] = get_header_pic($data['bind_id']);
                        $data['bind_data']['url'] = U('Member/show', array('id' => $data['bind_id']));
                        break;
                    case 'content':
                        $Bind_Model = M('Document');
                        $data['bind_data'] = $Bind_Model->where(array('id' => $data['bind_id']))->field('id,title')->find();
                        $data['bind_data']['url'] = U('Article/edit', array('id' => $data['bind_id']));
                        break;
                    default:
                }
            }
            $this->assign('data', $data);
        }
        $this->meta_title = '【'.$this->position['title'].'】 '. ($id ? '编辑推荐位内容' : '添加推荐位内容');
        $_config = D('Addons://Position/Position')->get_bind_config();
        $pos_type = explode(',', $this->position['bind_type']);
        $bind_config = array();
        foreach($pos_type as $v){
            isset($_config[$v]) && $bind_config[$v] = $_config[$v];
        }
        $this->assign('bind_config', $bind_config);
        $this->display(T('Addons://Position@Admin/PositionData/save'));
    }
    
    public function update(){
        $Model = D('Addons://Position/PositionData');
        $_POST['name'] = $this->position['name'];
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
            $Model = D('Addons://Position/PositionData');
            $res = $Model->where(array( 'id' => $id, 'pos_id' => $this->position['id']))->delete();
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
        $Model = D('Addons://Position/PositionData');
        $data = array(
            'id' => $id,
            'listorder' => $listorder,
        );
        $res = $Model->where(array( 'id' => $id, 'pos_id' => $this->position['id']))->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }
    
    public function get_form_lists(){
        $type = I('type');
        $act_id = I('get.act_id','','trim');
        $keyword = I('get.keyword','','trim');
        $where = array();
        $_REQUEST['r'] = 10;
        switch($type){
            case 'shop_article':
                $Model = M('ShopArticle');
                $field = 'id,title';
                $where = array();
                $keyword && $where['title'] =  array('like', '%'.$keyword.'%');
                $where['status'] = 1;
                $where['is_shelf'] = 1;
                $list = $this->lists($Model, $where, '', array(), $field);
                is_null($list) && $list = array();
                foreach($list as $k => $v){
                    $v['url'] = U('Book/show', array('id' => $v['id']));
                    $list[$k] = $v;
                }
                break;
            case 'member':
                $Model = M('Member');
                $field = 'uid,nickname,cover_id';
                $where = array();
                $keyword && $where['nickname'] =  array('like', '%'.$keyword.'%');
                $where['is_admin']  =  0;
                $where['status'] = 1;
                $list = $this->lists($Model, $where, '', array(), $field);
                is_null($list) && $list = array();
                foreach($list as $k => $v){
                    $v['url'] = U('Member/show', array('id' => $v['uid']));
                    $v['id'] = $v['uid'];
                    $v['title'] = $v['nickname'];
                    $list[$k] = $v;
                }
                break;
            case 'content':
                $Model = M('Document');
                $field = 'id,title';
                $where = array();
                $keyword && $where['title'] =  array('like', '%'.$keyword.'%');
                $where['status'] = 1;
                $list = $this->lists($Model, $where, '', array(), $field);
                is_null($list) && $list = array();
                foreach($list as $k => $v){
                    $v['url'] = U('Article/edit', array('id' => $v['id']));
                    $list[$k] = $v;
                }
                break;
            default:
                $this->error('类型不存在');
        }
        is_null($list) && $list = array();
        !is_array($act_ids) && $act_ids = $act_ids ? explode(',', $act_ids) : array();
        foreach($list as $k => $v){
            isset($v['cover_id']) && $v['pic_url'] = get_cover_url($v['cover_id']);
            $v['is_active'] = in_array($v['id'], $act_ids) ? 1 : 0;
            $list[$k] = $v;
        }
        if(IS_AJAX){
            $this->ajaxReturn(array('status' => 1, 'data' => $list));
            return;
        }
        $this->assign('list', $list);
        $this->display(T('Addons://Position@Admin/PositionData/get_form_lists'));
    }
}
