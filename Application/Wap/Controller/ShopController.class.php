<?php
namespace Wap\Controller;

class ShopController extends BaseController{
    public function __construct() {
        parent::__construct();
    }
    
    public function article(){
        $id = I('id', 0, 'intval');
        $info = M('ShopArticle')->where(array('id' => $id, 'status' => 1, 'is_shelf' => 1))->find($id);
        if(!$info){
            $this->error('商品不存在');
        }
        if($info['pics']){
            $info['pics'] = explode(',', $info['pics']);
            foreach($info['pics'] as $v){
                $v = intval($v);
                if($v > 0){
                    $_pic = get_cover_url($v);
                    $_pic && $pics_data[] = $_pic;
                }
            }
        }
        $info['pics_data'] = $pics_data;
        $info['pic_url'] = get_cover_url($info['cover_id']);
        $info['goods'] = M('ShopGoods')->find($id);
        $info['goods']['pic'] = get_cover_url($info['goods']['pic']) ;
        $this->assign('info', $info);
        $this->display();
    }
    
    
    
    public function article2(){
        $id = I('id', 0, 'intval');
        $info = M('ShopArticle')->where(array('id' => $id, 'status' => 1, 'is_shelf' => 1))->find($id);
        if(!$info){
            $this->error('商品不存在');
        }
        if($info['pics']){
            $info['pics'] = explode(',', $info['pics']);
            foreach($info['pics'] as $v){
                $v = intval($v);
                if($v > 0){
                    $_pic = get_cover_url($v);
                    $_pic && $pics_data[] = $_pic;
                }
            }
        }
        $info['pics_data'] = $pics_data;
        $info['pic_url'] = get_cover_url($info['cover_id']);
        $info['goods'] = M('ShopGoods')->find($id);
        $info['goods']['pic'] = get_cover_url($info['goods']['pic']) ;
        $this->assign('info', $info);
        $this->display();
    }    
    
}
