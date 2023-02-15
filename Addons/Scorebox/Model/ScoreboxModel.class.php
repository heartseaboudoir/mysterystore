<?php

namespace Addons\Scorebox\Model;
use Admin\Model\UcModel;

class ScoreboxModel extends UcModel{
    
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
            array('score', 0, self::MODEL_INSERT),
            array('all_score', 0, self::MODEL_INSERT),
            array('exper', 0, self::MODEL_INSERT),
            array('status', 1, self::MODEL_INSERT),
    );
    /**
     * 获取等级
     * @param type $val   经验值/会员ID
     * @return type
     */
    public function get_level($val = 0){
        $exper = $val;
        $level_data = $this->get_level_data();
        $data = array();
        foreach($level_data as $v){
            if($exper >= $v['exper']){
                $data = $v;
            }
        }
        return $data;
    }
    public function get_level_data($update = true){
        $level_data = S('SCORE_BOX_LEVEL');
        if(!$level_data || $update){
            $level_data = M('ScoreboxLevel')->field('title,icon, sale, exper')->order('exper asc')->select();
            S('SCORE_BOX_LEVEL', $level_data);
        }
        return $level_data;
    }
}