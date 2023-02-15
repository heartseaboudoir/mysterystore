<?php

namespace Addons\Position\Model;
use Think\Model;

class PositionModel extends Model{
    
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
        
    );
    
    private $_bind_config = array('line' => '自定义', 'shop_article' => '店铺文章', 'member' => '会员', 'content' => '内容库');
    
    public function get_bind_config(){
        return $this->_bind_config;
    }
    
    protected function _after_update($data, $options) {
        parent::_after_update($data, $options);
        $position = S('ADDONS_POSITION');
        if(isset($data['name'])){
            foreach($position[$data['name']] as $k => $v){
                $v = $data[$k];
                $position[$data['name']][$k] = $v;
            }
            S('ADDONS_POSITION', $position);
        }
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
    
    public function get_position($name){
        $position = S('ADDONS_POSITION');
        if(empty($position[$name])){
            $position_data = $this->get_info($name, array('status' => 1));
            if(!$position_data){
                return false;
            }
            $position[$name] = $position_data;
            S('ADDONS_POSITION', $position);
        }else{
            $position_data = $position[$name];
        }
        $Model = D('Addons://Position/PositionData');
        $where = array(
            'pos_id' => $position_data['id'], 
            'status' => 1
        );
        $data = $Model->where($where)->field('id, title,description,cover_id,url,bind_type, bind_id, listorder')->order('listorder desc, create_time asc')->limit($position_data['show_num'])->select();
        !$data && $data = array();
        foreach($data as $k => $v){
            $v['pic_url'] = get_cover_url($v['cover_id']);
            unset($v['cover_id']);
            $data[$k] = $v;
        }
        return $data;
    }
}