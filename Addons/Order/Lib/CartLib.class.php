<?php

namespace Addons\Order\Lib;

class CartLib{
    
        private $cache_key = 'ORDER_CART';
        private $uid;
        
        public function __construct($uid, $site_id = null) {
            $site_id && $this->cache_key .= $site_id;
            $this->uid = $uid;
            $ukey = ceil($uid/50000);
            $this->cache_key .= $ukey;
        }
        
        // 获取购物车商品
        public function get_cart(){
            $cart = S($this->cache_key);
            return isset($cart[$this->uid]) ? $cart[$this->uid] : array();
        }
        
        // 更新购物车
        public function update_cart($id, $num = 1, $atta = array(), $info = array()){
            $cart = S($this->cache_key);
            empty($cart[$this->uid]) && $cart[$this->uid] = array();
            $data = array();
            $data['id'] = $id;
            $data['num'] = $num;
            $data['atta'] = $atta;
            $data['info'] = $info;
            $cid = $this->_get_cid($id, $atta);
            $data['cid'] = $cid;
            $cart[$this->uid][$cid] = $data;
            S($this->cache_key, $cart);
            return true;
        }
        /**
         * 购物车ID
         * @param type $id
         * @param type $atta
         * @return type
         */
        private function _get_cid($id, $atta = array()){
            if(is_array($atta)){
                ksort($atta);
            }
            $result = md5($id.(is_array($atta) ? json_encode($atta) : $atta));
            return $result;
        }
        // 检查是否存在于购物车
        public function is_in_cart($id, $atta = array()){
            $cart = S($this->cache_key);
            $cid = $this->_get_cid($id, $atta);
            return isset($cart[$this->uid][$cid]) ? true : false;
        }
        // 通过商品ID+属性
        public function del_cart_by_data($data){
            $cids = array();
            foreach($data as $v){
                $cids[] = $this->_get_cid($v['id'], !empty($v['atta']) ? $v['atta'] : array());
            }
            return $this->del_cart($cids);
        }
        // 删除购物车商品
        public function del_cart($cids){
            $cids = is_array($cids) ? $cids : explode(',', $cids);
            $cart = S($this->cache_key);
            foreach($cids as $id){
                if(isset($cart[$this->uid][$id])){
                    unset($cart[$this->uid][$id]);
                }
            }
            S($this->cache_key, $cart);
            return true;
        }
        // 清空购物车
        public function clear_cart(){
            $cart = S($this->cache_key);
            if(isset($cart[$this->uid])){
                unset($cart[$this->uid]);
                S($this->cache_key, $cart);
            }
            return true;
        }
}