<?php


namespace Addons\Warehouse\Model;
use Think\Model;
use User\Api\UserApi;

class MemberModel extends Model{
	/**
	 * 自动完成
     * http://localhost/Admin/Addons/ex_Warehouse/_addons/Warehouse/_controller/MemberAdmin/_action/save/group/2/warehouse_id/1.html
	 * @var array
	 */
        protected $_validate = array(
                array('warehouse_id', '/^[1-9]\d*$/', '请选择所属仓库', self::MUST_VALIDATE),
                array('nickname', 'require', '请填写设备管理员名', self::MUST_VALIDATE),
        );
        
        protected $_auto = array(
		array('s_add_time', NOW_TIME, self::MODEL_INSERT),
		array('s_edit_time', NOW_TIME, self::MODEL_BOTH),
                array('admin', 'mk_admin', self::MODEL_INSERT, 'callback'),
	);
        protected function mk_admin($param){
            $api = new UserApi;
            $username = uniqid('warehouse_');
            $uid = $api->register($username, 'mendian123');
            if($uid){
                $data = array(
                    'uid' => $uid,
                    'nickname' => $param,
                    'reg_time' => time(),
                    'status' => 1,
                );
                D('Member')->add($data);
                M('AuthGroupAccess')->add(array('uid' => $uid, 'group_id' => 2));
            }
            return $uid;
        }
        
        protected function mk_finance($param){
            $api = new UserApi;
            $username = uniqid('store_');
            $uid = $api->register($username, 'mendian123');
            if($uid){
                $data = array(
                    'uid' => $uid,
                    'nickname' => $param,
                    'reg_time' => time(),
                    'status' => 1,
                );
                D('Member')->add($data);
                M('AuthGroupAccess')->add(array('uid' => $uid, 'group_id' => 3));
            }
            return $uid;
        }


        protected function _after_find(&$result,$options) {
            isset($result['s_add_time']) && $result['s_add_time_text'] = date('Y-m-d H:i:s', $result['s_add_time']);
            isset($result['s_edit_time']) && $result['s_edit_time_text'] = date('Y-m-d H:i:s', $result['s_edit_time']);
            $status = array(0 => '正常' , 1 => '禁用');
            isset($result['w_type']) && $result['w_type'] = isset($status[$result['w_type']]) ? $status[$result['w_type']] : '';
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        
        protected function _before_update(&$data, $options) {
            parent::_before_update($data, $options);
            if(isset($data['admin'])){
                unset($data['admin']);
            }
            if(isset($data['finance'])){
                unset($data['finance']);
            }
        }
        
        public function get_store_member($store_id, $group_id){
            $where = array(
                'store_id' => $store_id,
                'group_id' => $group_id
            );
            $data = M('MemberStore')->where($where)->select();
            $uid = array();
            foreach($data as $v){
                $uid[] = $v['uid'];
            }
            return $uid;
        }

}