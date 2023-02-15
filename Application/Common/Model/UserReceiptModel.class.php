<?php

namespace Common\Model;

use Think\Model;

class UserReceiptModel extends Model{

	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('name', 'require', '收货人不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
                array('mobile', 'require', '联系方式不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
                array('address', 'require', '收货地址不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
                array('uid', 'require', '用户不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
                array('zip_code', '/^\d{6}$/', '邮编格式错误', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
                array('sheng', 'require', '请选择省份', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
                array('shi', 'require', '请选择市', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        );
	protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);
        
        public function update($data = NULL){
            $data = $this->create($data);
            if(!$data){
                return false;
            }
            if(empty($data['id'])){
                $id = $this->add();
                if(!$id){
                    !$this->error && $this->error = '添加出错！';
                    return false;
                }
            } else {
                $status = $this->where(array('id' => $data['id']))->save();
                if(false === $status){
                    !$this->error && $this->error = '更新出错！';
                    return false;
                }
            }
            return $data;
        }
        
        public function get_info($id, $uid = 0, $field = '*'){
            $where = array();
            $where['id'] = $id;
            $uid > 0 && $where['uid'] = $uid;
            
            $info = $this->where($where)->field($field)->find();
            $area_ids = array();
            $area_ids[] = $info['sheng'];
            $area_ids[] = $info['shi'];
            $area_ids[] = $info['qu'];
            $area_data = M('Area')->where(array('id' => array('in', $area_ids)))->select();
            foreach($area_data as $v){
                $area_title[$v['id']] = $v['title'];
            }
            $info['sheng_title'] = isset($area_title[$info['sheng']]) ? $area_title[$info['sheng']] : '';
            $info['shi_title'] = isset($area_title[$info['shi']]) ? $area_title[$info['shi']] : '';
            $info['qu_title'] = isset($area_title[$info['qu']]) ? $area_title[$info['qu']] : '';
            $info['s_mobile'] = substr($info['mobile'], 0, 3).'****'.substr($info['mobile'], 7, 4);
            return $info;
        }
        /**
         * 获取默认地址
         * @param type $uid
         * @param type $field
         * @return boolean
         */
        public function get_default($uid){
            $where = array();
            $where['uid'] = $uid;
            $where['is_default'] = 1;
            $info = $this->where($where)->find();
            if(!$info){
                $info = $this->where(array('uid' => $uid))->find();
                if(!$info){
                    return false;
                }
                $this->where(array('id' => $info['id']))->save(array('is_default' => 1));
            }
            $area_ids = array();
            $area_ids[] = $info['sheng'];
            $area_ids[] = $info['shi'];
            $area_ids[] = $info['qu'];
            $area_data = M('Area')->where(array('id' => array('in', $area_ids)))->select();
            foreach($area_data as $v){
                $area_title[$v['id']] = $v['title'];
            }
            $info['sheng_title'] = isset($area_title[$info['sheng']]) ? $area_title[$info['sheng']] : '';
            $info['shi_title'] = isset($area_title[$info['shi']]) ? $area_title[$info['shi']] : '';
            $info['qu_title'] = isset($area_title[$info['qu']]) ? $area_title[$info['qu']] : '';
            $info['s_mobile'] = substr($info['mobile'], 0, 3).'****'.substr($info['mobile'], 7, 4);
            return $info;
        }
        
        public function set_default($uid, $id){
            $this->where(array('uid' => $uid))->save(array('is_default' => 0));
            $this->where(array('uid' => $uid, 'id' => $id))->save(array('is_default' => 1));
        }
       
}
