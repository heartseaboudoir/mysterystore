<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsTagAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $title = I('title', '', 'trim');
        $where = array();
        $list = $this->lists(M('GoodsTag'), $where, 'id desc');
        $this->assign('list', $list);
        
        $this->meta_title = '商品关键词管理';
        $this->display(T('Addons://Goods@Admin/GoodsTag/index'));
    }
    
    public function save() {
        if(IS_POST){
            $content = I('content', '', 'trim');
            if(!$content){
                $this->error('请填写关键词');
            }
            $content = explode("\r\n", $content);
            $Model = M('GoodsTag');
            foreach($content as $v){
                $v = trim($v);
                if(!$v){
                    continue;
                }
                $data = array(
                    'title' => $v
                );
                if($Model->where($data)->find()){
                    continue;
                }
                $data['pinyin'] = get_pinyin($v);
                $data['fir_letter'] = get_letter($v);
                $Model->add($data);
            }
            $this->success('操作成功',Cookie('__forward__'));
            exit;
        }
        $this->display(T('Addons://Goods@Admin/GoodsTag/save'));
    }
    
    public function delete(){
        $id = I('get.id','');
        if($id){
            $Model = M('GoodsTag');
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
    
}
