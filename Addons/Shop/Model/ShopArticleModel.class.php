<?php
namespace Addons\Shop\Model;
use Think\Model;

class ShopArticleModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('title', 'require', '标题不能为空', self::VALUE_VALIDATE),
                array('content', 'require', '内容不能为空', self::VALUE_VALIDATE),
                array('uid', 'require', '发布人不能为空', self::VALUE_VALIDATE),
        );
        
        protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
		array('pics', set_pics, self::MODEL_BOTH, 'callback'),
	);
        
	protected function _after_find(&$result,$options) {
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        protected function set_pics($param){
            if(!is_array($param)){
                $param = $param ? explode(',', $param) : array();
            }
            foreach($param as $k => $v){
                $v = intval($v);
                if(!($v > 0)){
                    unset($param[$k]);
                }
            }
            return implode(',', $param);
        }
        
        protected function _after_update($data, $options) {
            parent::_after_update($data, $options);
            $goods = !empty($_POST['goods']) ? $_POST['goods'] : array();
            isset($_POST['status']) && $goods['status'] = $_POST['status'];
            isset($_POST['is_shelf']) && $goods['is_shelf'] = $_POST['is_shelf'];
            if($goods){
                $goods['update_time'] = NOW_TIME;
                M('ShopGoods')->where(array('aid' => $data['id']))->save($goods);
            }
        }


        public function info($id, $get_detail = false){
            $id = intval($id);
            if(!($id > 0)){
                return false;
            }
            $info = $this->where(array('id' => $id, 'status' => 1, 'is_shelf' => 1))->find();
            if(!$info){
                return false;
            }
            if($get_detail){
                $goods = M('ShopGoods')->where(array('aid' => $id))->find();
                $info['goods_data'] = $goods ? $goods : array();
            }
            return $info;
        }
        public function add_goods($data){
            $sheng = isset($data['sheng']) ? intval($data['sheng']) : 0;
            $shi = isset($data['shi']) ? intval($data['shi']) : 0;
            $qu = isset($data['qu']) ? intval($data['qu']) : 0;
            $sheng < 0 && $sheng = 0;
            $shi < 0 && $shi = 0;
            $qu < 0 && $qu = 0;
            $result = check_area_in($sheng, $shi, $qu);
            if($result != 1){
                switch($result){
                    case -1:
                        $this->error = '请选择正确的省份';
                        return false;
                        break;
                    case -2:
                        $this->error = '请选择正确的城市';
                        return false;
                        break;
                    case -3:
                        $this->error = '请选择正确的地区';
                        return false;
                        break;
                }
            }
            $a_data = array(
                'title' => $data['title'],
                'content' => $data['content'],
                'cover_id' => $data['cover_id'],
                'pics' => $data['pics'],
                'uid' => $data['uid'],
                'sheng' => $sheng,
                'shi' => $shi,
                'qu' => $qu,
                'status' => 0,
                'is_shelf' => 0,
            );
            $a_data = $this->create($a_data);
            if(!$a_data){
                return false;
            }
            $aid = $this->add();
            if(!$aid){
                return false;
            }
            $b_data = array(
                'id' => $aid,
                'aid' => $aid,
                'title' => $data['goods_title'],
                'uid' => $data['uid'],
                'pic' => $data['goods_pic'],
                'price' => $data['price'],
                'num' => $data['num'],
                'express_money' => $data['express_money'],
                'status' => 0,
                'is_shelf' => 0,
            );
            $GoodsModel = D('Addons://Shop/ShopGoods');
            $b_data = $GoodsModel->create($b_data);
            if(!$b_data){
                $this->delete($aid);
                return false;
            }
            if($GoodsModel->add()){
                $result = array(
                    'id' => $aid,
                    'title' => $a_data['title'],
                    'content' => $a_data['content'],
                    'pics' => $a_data['pics'],
                    'status' => $a_data['status'],
                    'create_time' => $a_data['create_time'],
                    'goods_id' => $b_data['goods_id'],
                    'goods_title' => $b_data['title'],
                    'goods_pic' => $b_data['pic'],
                    'sheng' => $a_data['sheng'],
                    'shi' => $a_data['shi'],
                    'qu' => $a_data['qu'],
                    'num' => $b_data['num']
                );
                $tag = !empty($data['tag']) ? explode(',', $data['tag']) : array();
                foreach($tag as $v){
                    $v = trim($v);
                    $item = array(
                        'aid' => $aid,
                        'tag' => $v,
                    );
                    M('ShopArticleTags')->add($item);
                }
                return $result;
            }else{
                $this->delete($aid);
                return false;
            }
        }
        
        public function get_cover($id, $type = 'id'){
            if($type == 'id'){
                $info = $this->field('pics')->find($id);
                if(!$info){
                    return '';
                }
                $pics = $info['pics'];
            }else{
                $pics = $id;
            }
            $pics = explode(',', $pics);
            $result = array();
            foreach($pics as $v){
                $v = intval($v);
                if($v > 0){
                    $pic_url = get_cover_url($v);
                    if($pic_url){
                        $result = $pic_url;
                        break;
                    }
                }
            }
            return $result;
        }
        
        public function to_publish($aid, $uid = 0){
            $where = array();
            $where['id'] = $aid;
            $uid > 0 && $where['uid'] = $uid;
            $where['status'] = 1;
            $where['is_shelf'] = 2;
            $Model = M('ShopGoods');
            $Model2 = $this;
            $data = array('is_shelf' => 1, 'update_time' => NOW_TIME);
            if($Model->where($where)->save($data)){
                $Model2->where(array('id' => $aid))->save($data);
                return true;
            }
            return false;
        }
        
        public function to_down($aid, $uid = 0){
            $where = array();
            $where['id'] = $aid;
            $uid > 0 && $where['uid'] = $uid;
            $where['status'] = 1;
            $where['is_shelf'] = 1;
            $Model = M('ShopGoods');
            $Model2 = $this;
            $data = array('is_shelf' => 2, 'update_time' => NOW_TIME);
            if($Model->where($where)->save($data)){
                $Model2->where(array('id' => $aid))->save($data);
                return true;
            }
            return false;
        }
        
        public function del_by_user($aid, $uid){
            $where = array();
            $where['id'] = $aid;
            $where['uid'] = $uid;
            $map['status'] = array('in', '0,2');
            $map['is_shelf'] = 2;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
            $data = array('status' => -1, 'update_time' => NOW_TIME);
            $Model = M('ShopGoods');
            $Model2 = $this;
            if($Model->where($where)->save($data)){
                $Model2->where(array('id' => $aid))->save($data);
                return true;
            }
            return false;
        }
        
        public function do_act($uid, $aid, $type){
            $info = D('Addons://Shop/ShopArticle')->info($aid);
            if(!$info){
                return false;
            }
            $cover_id = !empty($info['cover_id']) ? $info['cover_id'] : 0;
            $where = array(
                'uid' => $uid,
                'aid' => $aid,
                'type' => $type,
            );
            switch($type){
                case 1:
                    $do_type = 'zan';
                    $field = 'zan_num';
                    break;
                case 2:
                    $do_type = 'collect';
                    $field = 'collect_num';
                    break;
                default:
                    return false;
            }
            $Model = M('ShopMemberAct');
            if($Model->where($where)->find()){
                $act = 2;
                if($Model->where($where)->delete()){
                    $this->where(array('id' => $aid, $field => array('gt', 0)))->setDec($field);
                    $num = $info[$field] - 1;
                }else{
                    return false;
                }
            }else{
                $act = 1;
                $data = $where;
                $data['create_time'] = NOW_TIME;
                $data['title'] = $info['title'];
                $data['cover_id'] = $cover_id;
                $data['param'] = '';
                if($Model->add($data)){
                    $param = array(
                        'nickname' => get_nickname($uid),
                        'header_pic' => get_header_pic($uid),
                        'title' => $info['title'],
                        'pic_url' => get_cover_url($cover_id),
                    );
                    $api = new \User\Client\Api();
                    $api->execute('Message', 'add_notice', array('act_uid' => $uid, 'act_id' => $aid, 'type' => $do_type, 'uid' => $info['uid'], 'param' => $param));
                    $this->where(array('id' => $aid))->setInc($field);
                    $num = $info[$field] + 1;
                }else{
                    return false;
                }
            }
            $num < 0 && $num = 0;
            return array('act' => $act, 'num' => $num);
        }
        
        public function sell_notify($gid, $uid, $fid, $num, $order_sn){
            $GModel = M('ShopGoods');
            $info = $GModel->where(array('id' => $gid, 'uid' => $uid))->find();
            if(!$info){
                return false;
            }
            
            /*
            if(!$GModel->where(array('id' => $gid, 'uid' => $uid, 'num' => array('egt', $num)))->save(array('sell_num' => array('exp', 'sell_num+'.$num), 'num' => array('exp', 'num-'.$num)))){
                return false;
            }
            */
            if(!$GModel->where(array('id' => $gid, 'uid' => $uid, 'num' => array('egt', $num)))->save(array('sell_num' => array('exp', 'sell_num+'.$num)))){
                return false;
            }            
            $LModel = M('ShopSellLog');
            if($LModel->where(array('sn' => $order_sn, 'gid' => $gid))->find()){
                return false;
            }
            $data = array(
                'sn' => $order_sn,
                'gid' => $gid,
                'aid' => $info['aid'],
                'uid' => $uid,
                'fid' => $fid,
                'num' => $num,
                'create_time' => NOW_TIME
            );
            if(!$LModel->add($data)){
                return false;
            }
            $DModel = M('ShopSellDLog');
            $where = array(
                'aid' => $info['aid'],
                'date' => date('Y-m-d', NOW_TIME),
            );
            $data = $where;
            if($DModel->where($where)->find()){
                $data['num'] = array('exp', 'num+'.$num);
                $result = $DModel->where($where)->save($data);
            }else{
                $data['uid'] = $uid;
                $data['num'] = $num;
                $result = $DModel->add($data);
            }
            return $result ? true : false;
        }
}