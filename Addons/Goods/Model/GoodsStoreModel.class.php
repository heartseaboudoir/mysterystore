<?php

namespace Addons\Goods\Model;
use Think\Model;

class GoodsStoreModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('goods_id', '/^[1-9]\d*$/', '未知商品', self::MUST_VALIDATE),
                array('store_id', '/^[1-9]\d*$/', '未知商品', self::MUST_VALIDATE),
                //array('shequ_price', 'require', '请填写门店售价', self::MUST_VALIDATE),
                array('price', '/^[0-9][0-9]*\.{0,1}[0-9]*$/', '请正确填写售价', self::VALUE_VALIDATE),
        );
        
        protected $_auto = array(
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
                $status = array(1 => '上架' , 2 => '下架');
                isset($result['status']) && $result['status_text'] = isset($status[$result['status']]) ? $status[$result['status']] : '';
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        
        protected function _after_update($data, $options) {
            parent::_after_update($data, $options);
            $this->set_log($data);
        }
        
        public function set_log($data = array()){
            $Model =  M('GoodsStoreChangeLog');
            $_data = $Model->create($data);
            if(isset($_data['id'])){
                unset($_data['id']);
            }
            $_data['uid'] = UID;
            $_data['create_time'] = time();
            $Model->add($_data);
        }
        
        
        public function push_update($code, $id, $store_id, $go = false){
            $data = array();
            switch($code){
                // 所有商品+分类
                case 'all':
                    break;
                // 所有分类
                case 'category':
                    break;
                // 对应分类下的商品
                case 'goods_by_cid':
                    $id = is_array($id) ? implode(',',$id) : $id;
                    if(empty($id)){
                        return;
                    }
                    $data = array('id' => $id);
                    break;
                // 对应ID的商品
                case 'goods_by_id':
                    $id = is_array($id) ? implode(',',$id) : $id;
                    if(empty($id)){
                        return;
                    }
                    $data = array('id' => $id);
                    break;
                // 对应ID的商品
                case 'del_category':
                    $id = is_array($id) ? implode(',',$id) : $id;
                    if(empty($id)){
                        return;
                    }
                    $data = array('id' => $id);
                    break;
                // 对应ID的商品
                case 'del_goods':
                    $id = is_array($id) ? implode(',',$id) : $id;
                    if(empty($id)){
                        return;
                    }
                    $data = array('id' => $id);
                    break;
                default:
                    return;
            }
            $content = array(
                'code' => $code,
                'data' => $data
            );
            $title = '推送更新';
            $store_id = intval($store_id);
            //$Umeng = new \Addons\Umeng\Helper\UmengHelper();
            $pushApi = new \Addons\Push\Helper\Push();
            $pushApi->add($title, $code, json_encode($content), '', array('store_id' => $store_id));
        }
        
        public function push_num($id, $store_id){
            $id = !is_array($id) ? explode(',',$id) : $id;
            if(empty($id)){
                return;
            }
            
            $info = $this->where(array('store_id' => $store_id, 'goods_id' => array('in', $id)))->field('goods_id, num')->select();
            
            if(!$info) return false;    
            $u_id = array();
            $d_id = array();
            foreach($info as $v){
                if($v['num'] > 0){
                    $u_id[] = $v['goods_id'];
                }else{
                    $d_id[] = $v['goods_id'];
                }
            }
            if($u_id){
                $this->push_update('goods_by_id', $u_id, $store_id);
            }
            if($d_id){
                $this->push_update('del_goods', $d_id, $store_id);
            }
        }
        
        public function num_notice($id, $store_id){
            $id = !is_array($id) ? explode(',',$id) : $id;
            if(empty($id)){
                return;
            }
            C(api('Config/lists'));
            $notice_arr = C('GOODS_NUM_WARN');
            if(!$notice_arr) return ;
            sort($notice_arr);
            $info = $this->where(array('store_id' => $store_id, 'goods_id' => array('in', $id)))->field('goods_id, num')->select();
            $idata = array();
            foreach($info as $v){
                foreach($notice_arr as $n){
                    if($v['num'] <= $n){
                        $idata[] = $v['goods_id'];
                        $num[$v['goods_id']] = $v['num'];
                        break;
                    }
                }
            }
            if($idata){
                $goods = M('Goods')->where(array('id' => array('in', $idata)))->field('id, title')->select();
                foreach($goods as $v){
                    $_goods[$v['id']] = $v['title'];
                }
                $store = M('Store')->where(array('id' => $store_id))->find();
                if(!$store) return;
                $uid_arr =  D('Addons://Store/Member')->get_store_member($store_id, 2);
                $mobile_arr = array();
                foreach($uid_arr as $u){
                    $mobile = get_mobile($u);
                    if(!$mobile) continue;
                    $mobile_arr[] = $mobile;
                }
                if(!$mobile_arr){
                    return true;
                }
                $mobile_str = implode(',', $mobile_arr);
                foreach($idata as $v){
                    if(!isset($_goods[$v])) continue;
                    $param = array(
                        'store' => $store['title'],
                        'good' => $_goods[$v],
                        'num' => $num[$v]
                    );
                    $sms_data = array(
                        'mobile' => $mobile_str,
                        'tpl' => 'SMS_10660701',
                        'param' => json_encode($param),
                        'create_time' => NOW_TIME,
                        'update_time' => NOW_TIME,
                    );
                    M('SmsPush')->add($sms_data);
                }
            }
            return true;
        }
}