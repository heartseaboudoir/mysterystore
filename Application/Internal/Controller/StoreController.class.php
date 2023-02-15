<?php
namespace Internal\Controller;

use Apiv2\Extend\JgPush;

class StoreController extends ApiController {

    public function _initialize()
    {
        // 是否验证token
        $action = ACTION_NAME;
        $actions = array();
        $check = false; // true为指定的验证，false为指定的不验证 
        if (in_array($action, $actions)) {
            $this->ctoken = $check;
        } else {
            $this->ctoken = !$check;
        }
        
        
        parent::_initialize();
        //echo 2222;
    }
    
    
    public function test()
    {
        $this->response(self::RESPONSE_SUCCES, 'test...');
    }
    
    

    
  
    // 同步商品
    public function push_update(){
        
        
        // 处理类型
        $code = I('code', '', 'trim');
        
        if (empty($code)) {
            $code = 'all';
        }
        
        if (!in_array($code, array('all', 'goods_by_cid'))) {
            $this->response(10010, '参数非法: code');
        }
        
        // 处理商品分类ID集合
        $ids = I('ids', 0);
        
        if (empty($ids)) {
            $ids = array();
        }
        
        if ($code == 'goods_by_cid' && empty($ids)) {
            $this->response(10020, '参数非法: ids');
        }
        
        $store_id = I('store_id', 0);
        
        if (empty($store_id)) {
            $this->response(10030, '参数非法: store_id');
        }
        
        
        $date = date('H:i:s') . ' ~rand: ' . mt_rand(100000, 999999);
        $result = JgPush::pushToApp('store' . '_' . $store_id, 1, '门店更新', '说明：门店更新; (TIME)' . $date, '{"name": "value"}');
        $this->response(self::RESPONSE_SUCCES, array('msg' => '请求成功'));

        
        // D('Addons://Goods/GoodsStore')->push_update('all', $ids, $store_id, true);
        
        // $this->return_data(1, array('msg' => '请求成功'));

    }
    
    
    
    // 同步商品
    public function push_update_admin(){
        
        // 处理类型
        $code = I('code', '', 'trim');
        
        if (empty($code)) {
            $code = 'all';
        }

        if (!in_array($code, array('all', 'goods_by_cid'))) {
            $this->response(10010, '参数非法: code');
        }
        
        // 处理商品分类ID集合
        $ids = I('ids', 0);
        
        if (empty($ids)) {
            $ids = array();
        }
        
        if ($code == 'goods_by_cid' && empty($ids)) {
            $this->response(10020, '参数非法: ids');
        }
        
        $store_id = I('store_id', 0);
        
        
        $id = empty($store_id) ? 0 : ('store' . '_' . $store_id);

        $date = date('H:i:s') . ' ~rand: ' . mt_rand(100000, 999999);
        $result = JgPush::pushToApp($id, 1, '门店更新', '说明：门店更新; (TIME)' . $date, '{"name": "value"}');
        $this->return_data(1, array('msg' => '请求成功'));

        
        // D('Addons://Goods/GoodsStore')->push_update('all', $ids, $store_id, true);
        
        // $this->return_data(1, array('msg' => '请求成功'));

    }     
    
}
