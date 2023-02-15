<?php
namespace Addons\Shop\Controller;

use Admin\Controller\AddonsController;

class ShopArticleAdminController extends AddonsController{
    
    public function _initialize(){
        parent::_initialize();
        $this->model = D('Addons://Shop/ShopArticle');
        $this->page_title = '商品';
    }
    
    public function apply_index(){
        $keyword = I('keyword', '', 'trim');
        $type = I('type', 0, 'intval');
        $where = array();
        if($type == 1){
            $where['status'] = 0;
        }elseif($type == 2){
            $where['status'] = 2;
        }else{
            $where['status'] = array('in', '0,2');
        }
        $keyword && $where['title'] = array('like', '%'.$keyword.'%');
        parent::_index($where);
    }
    
    public function index(){
        $keyword = I('keyword', '', 'trim');
        $shop_uid = I('shop_uid', 0, 'intval');
        $where = array();
        $keyword && $where['a.title|b.title'] = array('like', '%'.$keyword.'%');
        $shop_uid > 0 && $where['a.uid'] = $shop_uid;
        $where['a.status'] = array('in', '1');
        $join = "__SHOP_GOODS__ as b ON a.id = b.aid";
        $this->model->alias('a')->join($join);
        $field = 'a.id,a.title, a.create_time,a.uid,a.status,a.is_shelf,b.title as goods_title,b.num,b.sell_num';
        parent::_index($where, '', $field);
    }
    
    public function save(){
        $id = I('id', 0, 'intval');
        if(!($id > 0)){
            $this->error('请选择数据');
        }
        $this->callback_fun = 'set_save';
        $this->meta_title = '查看商品详情';
        $where = array('status' => array('in', '1'));
        parent::_save($where);
    }
    
    public function apply_save(){
        $id = I('id', 0, 'intval');
        if(!($id > 0)){
            $this->error('请选择数据');
        }
        $this->callback_fun = 'set_save';
        $this->meta_title = '查看商品申请详情';
        $where = array('status' => array('in', '0,2'));
        parent::_save($where);
    }
    
    protected function set_save($data){
        $goods = M('ShopGoods')->where(array('aid' => $data['id']))->find();
        $data['goods'] = $goods;
        $tags = reset_data_field(M('ShopArticleTags')->where(array('aid' => $data['id']))->select(), 'id', 'tag');
        $data['tags'] = $tags;
        $area_data = reset_data_field(M('Area')->where(array('id' => array('in', array($data['sheng'],$data['shi'],$data['qu']))))->select(), 'id', 'title');
        $data['weizhi'] =   (isset($area_data[$data['sheng']]) ? $area_data[$data['sheng']] : '').' '.
                            (isset($area_data[$data['shi']]) ? $area_data[$data['shi']] : '').' '.
                            (isset($area_data[$data['qu']]) ? $area_data[$data['qu']] : '');
        return $data;
    }
    
    public function apply_status(){
        if($_POST['status'] > 0){
            if($_POST['status'] == 2 && empty($_POST['remark'])){
                $this->error('请填写不通过原因');
            }else{
                $_POST['is_shelf'] = 1;
            }
            $_POST['review_time'] = NOW_TIME;
        }
        $this->callback_fun = 'set_apply_status';
        $this->model = M('ShopArticle');
        parent::_update();
    }
    
    protected function set_apply_status($data){
        if($data['status'] == 1){
            M('ShopGoods')->where(array('aid' => $data['id']))->save(array('status' => 1, 'is_shelf' => 1, 'update_time' => NOW_TIME));
        }
    }
}
