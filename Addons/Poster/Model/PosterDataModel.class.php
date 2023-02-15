<?php

namespace Addons\Poster\Model;
use Think\Model;

class PosterDataModel extends Model{
    
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
            array('store_id', 'set_store_id', self::MODEL_BOTH, 'callback'),
        
    );
    
    protected function set_store_id($param){
        return is_array($param) ? implode(',', $param) : $param;
    }

    protected function _after_insert($data, $options) {
        parent::_after_insert($data, $options);
        $this->_set_data($data);
    }
    
    protected function _after_update($data, $options) {
        parent::_after_update($data, $options);
        $this->_set_data($data);
    }


    private function _set_data($data){
        if(isset($data['store_id'])){
            $access_id = explode(',', $data['store_id']);
            $access_model = M('PosterDataAccess');
            $where = array('data_id' => $data['id']);
            $access_id && $where['access_id'] = array('not in', $access_id);
            $access_model->where($where)->delete();
            if($access_id){
                $where = array('data_id' => $data['id']);
                $where['access_id'] = array('in', $access_id);
                $access_data = $access_model->where($where)->select();
                $in_access = array();
                foreach($access_data as $v){
                    $in_access[] = $v['access_id'];
                }
                foreach($access_id as $v){
                    if(!in_array($v, $in_access)){
                        $idata = array(
                            'name' => $data['name'],
                            'data_id' => $data['id'],
                            'access_id' => $v,
                        );
                        $access_model->add($idata);
                    }
                }
            }
        }
    }
}