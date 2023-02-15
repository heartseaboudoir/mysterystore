<?php
// +----------------------------------------------------------------------
// | Title: <strong>内部APP</strong>
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 内部端
// +----------------------------------------------------------------------

namespace Apiv2\Controller;

class InternalExtController extends InternalController {
    
    public function push_update(){
        
        $this->check_token();
        
        // 处理类型
        $code = I('get.type', '', 'trim');
        if (empty($code) || !in_array($code, array('all', 'goods_by_cid'))) {
            $this->return_data(0, '', '参数非法: code');
        }
        
        // 处理商品分类ID集合
        $ids = I('ids', 0);
        
        if (empty($ids)) {
            $ids = array();
        }
        
        if ($code == 'goods_by_cid' && empty($ids)) {
            $this->return_data(0, '', '参数非法: ids');
        }
        
        $store_id = I('store_id', 0);
        
        if (empty($store_id)) {
            $this->return_data(0, '', '参数非法: store_id');
        }
        
        D('Addons://Goods/GoodsStore')->push_update('all', $ids, $store_id, true);
        
        $this->return_data(1, array('msg' => '请求成功'));

    }
    

    

}
