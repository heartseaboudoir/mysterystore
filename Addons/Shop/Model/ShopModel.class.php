<?php
namespace Addons\Shop\Model;
use Think\Model;

class ShopModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
        );
        
        protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);
        
	protected function _after_find(&$result,$options) {
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        public function info($uid){
            $info = $this->where(array('uid' => $uid))->find();
            if(!$info){
                $info = array(
                    'uid' => $uid,
                );
                $info = $this->create($info);
                if(!$info){
                    return false;
                }
                $result = $this->add();
                if(!$result){
                    return false;
                }
                $info['id'] = $result;
            }
            isset($info['star']) && $info['star'] = $this->get_t_star($info['star']);
            return $info;
        }
        public function get_t_star($star){
            return $star/10;
        }
        public function get_star($uid){
            $info  = $this->info($uid);
            if($info){
                return $info['star'];
            }else{
                return 0;
            }
        }
        /**
         * 添加评价
         * @param type $create_uid
         * @param type $shop_uid
         * @param type $content
         * @param type $goods_star
         * @param type $shop_star
         * @param type $bind_sn
         * @param type $order_end_time
         * @param type $goods_data
         * @return boolean
         */
        public function add_assess($create_uid, $shop_uid, $content, $goods_star, $shop_star, $bind_sn, $order_end_time = 0, $goods_data = array()){
            $goods_star = intval($goods_star);
            $shop_star = intval($shop_star);
            $Model = M('ShopAssess');
            $star = ($goods_star+$shop_star)/2;
            $data = array(
                'uid' => $shop_uid,
                'create_uid' => $create_uid,
                'content' => $content, 
                'goods_star' => $goods_star,
                'shop_star' => $shop_star,
                'bind_sn' => $bind_sn,
                'star' => $star,
                'bind_goods' => json_encode($goods_data),
                'order_end_time' => $order_end_time,
                'create_time' => NOW_TIME
            );
            if(!$Model->create($data)){
                $this->return_data(0, '', '评价失败');
            }
            if($Model->add()){
                $this->_set_star($shop_uid);
                return true;
            }else{
                return false;
            }
        }
        
        private function _set_star($uid){
            $star = M('ShopAssess')->where(array('uid' => $uid))->avg('star');
            if(!$this->where(array('uid' => $uid))->save(array('star' => $star))){
                // 防止店铺信息未生成
                $this->info($uid);
                $this->where(array('uid' => $uid))->save(array('star' => $star));
            }
        }
}