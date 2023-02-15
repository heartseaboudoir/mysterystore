<?php

// +----------------------------------------------------------------------
// | Title: 广告
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 公共 
// +----------------------------------------------------------------------
namespace Apiv2\Controller;

class PosterController extends ApiController {
    
    /**
     * @name lists_by_shop
     * @title *广告内容列表(门店端调用)
     * @param   string $poster_name  广告位标识
     * @return [title] => 广告标题 <br> [pic_url] => 广告图 <br> [url] => 广告跳转地址
     * @remark 广告位标识对应： <br> shop_app = 门店端广告
     */
    public function lists_by_shop(){
        $this->_check_token();
        $name = I('poster_name', '', 'trim');
        if(!$name){
            $this->return_data(0, array(), '广告位未知');
        }
        $lists = D('Addons://Poster/Poster')->get_poster($name, $this->_store_id);
        foreach($lists as $k => $v){
            $v['url'] = U('Poster/go_to', array('act' => $v['id']));
            $lists[$k] = $v;
        }
        $this->return_data(1, $lists);
    }
    /**
     * @name lists
     * @title 广告内容列表
     * @param   string $poster_name  广告位标识
     * @param   string $store_id     门店ID
     * @return [title] => 广告标题 <br> [pic_url] => 广告图 <br> [url] => 广告跳转地址
     * @remark 广告位标识对应： <br> shop_app = 门店端广告
     */
    public function lists(){
        $name = I('poster_name', '', 'trim');
        if(!$name){
            $this->return_data(0, array(), '广告位未知');
        }
        $store_id = I('store_id', 0, 'intval');
        if($store_id < 1){
            $this->return_data(0, array(), '未知门店');
        }
        $lists = D('Addons://Poster/Poster')->get_poster($name, $store_id);
        foreach($lists as $k => $v){
            $v['url'] = U('Poster/go_to', array('act' => $v['id']));
            $lists[$k] = $v;
        }
        $this->return_data(1, $lists);
    }
    
    public function go_to(){
        $id = I('act', 0, 'intval');
        if($id < 1){
            exit;
        }
        $where = array('id' => $id, 'status' => 1);
        $info = M('PosterData')->where($where)->find();
        if(!$info || empty($info['url'])){
            exit;
        }
        M('PosterData')->where($where)->setInc('click');
        redirect($info['url']);
    }
}
