<?php

namespace Addons\Position\Model;
use Think\Model;

class PositionDataModel extends Model{
    
    protected $_validate = array(
        array('bind_type', 'require', '请选择关联方式', self::MUST_VALIDATE),
        array('bind_id', 'check_bind_id', '请选择关联内容', self::MUST_VALIDATE, 'callback'),
        array('title', 'require', '请填写标题', self::MUST_VALIDATE),
    );
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
            array('bind_type', 'set_bind_type', self::MODEL_BOTH, 'callback'),
            array('bind_id', 'set_bind_id', self::MODEL_BOTH, 'callback'),
    );
    
    protected function check_bind_id($param){
        if($_POST['bind_type'] != 'line' && empty($param)){
            return false;
        }else{
            return true;
        }
    }
    
    protected function set_bind_type($param){
        if(empty($_POST['bind_type'])){
            return '';
        }
        $bind_config = D('Addons://Position/Position')->get_bind_config();
        if($param != 'link' && (!isset($bind_config[$param]) || empty($_POST['bind_id']))) {
            unset($_POST['bind_type']);
            return '';
        }
        return $param;
    }
    
    protected function set_bind_id($param){
        if(empty($_POST['bind_type']) || $_POST['bind_type'] == 'link'){
            return 0;
        }
        return (int)$param;
    }
}