<?php

namespace Addons\Poster\Model;
use Think\Model;

class PosterModel extends Model{
    
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
        
    );
    
    protected function _after_update($data, $options) {
        parent::_after_update($data, $options);
        $poster = S('ADDONS_POSTER');
        if(isset($data['name'])){
            $_poster= $this->get_info($data['name'], array('status' => 1));
            
            if($_poster){
                $poster[$data['name']] = $_poster;
            }elseif(isset($poster[$data['name']])){
                unset($poster[$data['name']]);
            }
            S('ADDONS_POSTER', $poster);
        }
        $store_id = explode(',', $data['store_id']);
        $del_where = array('data_id' => $data['id']);
        $AccessModel = M('PosterDataAccess');
        if($store_id){
            $access_data = $AccessModel->where(array('data_id' => $data['id'], 'access_id' => array('in', $store_id)))->select();
            $in_access = reset_data_field($access_data, 'id', 'access_id');
            $add_store_id = array_diff($store_id, $in_access);
            foreach($add_store_id as $v){
                $item = array(
                    'name' => $data['name'],
                    'access_id' => $v,
                    'data_id' => $data['id']
                );
                $AccessModel->add($item);
            }
            $del_where['access_id'] = array('not in', $store_id);
        }
        $AccessModel->where($del_where)->delete();
    }
    
    public function get_info($id, $param = array()){
        $where = array();
        if(is_numeric($id)){
            $where['id'] = $id;
        }else{
            $where['name'] = $id;
        }
        $param && $where = array_merge($where, $param);
        $info = $this->where($where)->find();
        return $info;
    }
    
    public function get_poster($name, $access_id = 0){
        $poster = S('ADDONS_POSTER');
        if(empty($poster[$name])){
            $poster_data = $this->get_info($name, array('status' => 1));
            if(!$poster_data){
                return false;
            }
            $poster[$name] = $poster_data;
            S('ADDONS_POSTER', $poster);
        }else{
            $poster_data = $poster[$name];
        }
        
        $Model = M('PosterData');
        $where = array();
        $where['a.name'] = $name;
        $where['a.status'] = 1;
        $size = $poster_data['show_num'] ? $poster_data['show_num'] : 10;
        $join = '';
        if($poster_data['is_access'] == 1){
            $join = '__POSTER_DATA_ACCESS__ as b ON a.id = b.data_id';
            $where['b.access_id'] = $access_id;
        }
        $data = $Model->alias('a')->where($where)->join($join)->limit($size)->order('a.listorder desc')->field('a.id,title,cover_id,cover_type')->select();
        !$data && $data = array();
        foreach($data as $k => $v){
            $v['pic_url'] = get_cover_url($v['cover_id']);
            unset($v['cover_id']);
            $data[$k] = $v;
        }
        return $data;
    }
}