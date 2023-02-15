<?php

namespace User\Model;
use Think\Model;

class ScoreboxModel extends Model{
    
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
            array('score', 0, self::MODEL_INSERT),
            array('all_score', 0, self::MODEL_INSERT),
            array('exper', 0, self::MODEL_INSERT),
            array('status', 1, self::MODEL_INSERT),
    );

    public function add_score($uid, $name, $num = 1){
        $info = $this->get_info($uid);
        if(!$info){
            return false;
        }
        $config = M('ScoreboxConfig')->where(array('name' => $name, 'type' => 1))->find();
        if(!$config) return false;
        $LogModel = M('ScoreboxLog');
        
        if($config['all_score'] > 0){
            $log = $LogModel->where(array('uid' => $uid, 'name' => $name))->select();
            $all_score = 0;
            foreach($log as $v){
                $all_score += $v['score'];
            }
            if($all_score >= $config['all_score']){
                return false;
            }
        }
        if($config['day_score'] > 0){
            $log = $LogModel->where(array('uid' => $uid, 'name' => $name, 'create_time' => array('GT', strtotime(date('Y-m-d')))))->select();
            $day_score = 0;
            foreach($log as $v){
                $day_score += $v['score'];
            }
            if($day_score >= $config['day_score']){
                return false;
            }
        }
        $score = 0;
        $exper = 0;
        if($config['score'] === intval($config['score'])){
            $score = $config['score'];
        }else{
            switch($config['name']){
                case "checkin";
                    $result = $this->_checkin($config, $uid);
                    $score = $result['score'];
                    $exper = $result['exper'];
                    break;
                case "buy";
                    $result = $this->_buy($config, $uid, $num);
                    $score = $result['score'];
                    $exper = $result['exper'];
                    break;
                default:
                    exit;
            }
        }
        
        if(!$score && !$exper) return false;
        $data = array(
            'uid' => $uid,
            'name' => $name,
            'score' => $score,
            'exper' => $exper,
            'type' => 1,
            'create_time' => NOW_TIME
        );
        if($LogModel->add($data)){
            $this->where(array('uid' => $uid))->save(array('score' => array('exp', 'score+'.$score), 'all_score' => array('exp', 'all_score+'.$score), 'exper' => array('exp', 'exper+'.$exper)));
            return array('score' => $score, 'exper' => $exper);
        }else{
            return false;
        }
    }
    
    public function dec_score($uid, $score, $name){
        $info = $this->get_info($uid);
        
        if($info['score'] < $score){
            return -1;
        }
        $config = M('ScoreboxConfig')->where(array('name' => $name, 'type' => 2))->find();
        if(!$config) return false;
        if($this->where(array('uid' => $uid, 'score' => array('EGT', $score)))->save(array('score' => array('exp', 'score-'.$score)))){
            $LogModel = M('ScoreboxLog');
            $data = array(
                'uid' => $uid,
                'name' => $name,
                'score' => $score,
                'type' => 2,
                'create_time' => NOW_TIME
            );
            $LogModel->add($data);
            return true;
        }else{
            return false;
        }
    }
    
    public function get_info($uid){
        $info = $this->where(array('uid' => $uid, 'status' => 1))->find();
        if($info){
            $level = $this->get_level($info['exper']);
            $info['level_title'] = $level['title'];
            $info['level_icon'] = $level['icon'];
            $info['level_sale'] = $level['sale'];
            return $info;
        }else{
            if(!M('Member')->where(array('uid' => $uid, 'status' => 1))->find()){
                return false;
            }
            $info = array(
                'uid' => $uid,
            );
            $info = $this->create($info);
            if(!$info){
                return false;
            }
            $id = $this->add();
            if($id){
                $info['id'] = $id;
                $level = $this->get_level(0);
                $info['level_title'] = $level['title'];
                $info['level_icon'] = $level['icon'];
                $info['level_sale'] = $level['sale'];
                return $info;
            }
            return false;
        }
    }
    /**
     * 获取等级
     * @param type $val   经验值/会员ID
     * @param type $type  类型：exper:经验 uid:会员
     * @return type
     */
    public function get_level($val = 0, $type = 'exper'){
        $exper = $val;
        switch($type){
            case 'uid':
                $info = $this->get_info($val);
                $exper = empty($info['exper']) ? 0 : $info['exper'];
                break;
        }
        $level_data = $this->get_level_data();
        $data = array();
        foreach($level_data as $v){
            if($exper >= $v['exper']){
                $data = $v;
            }
        }
        return $data;
    }
    public function get_score($uid){
        $info = $this->get_info($uid);
        return $info ? $info['score'] : 0;
    }
    
    private function _checkin($config, $uid){
        $rule = explode("\r\n",$config['score']);
        foreach($rule as $v){
            $v = explode('=', $v);
            $_rule[$v[0]]['score'] = $v[1];
            $_rule[$v[0]]['exper'] = isset($v[2]) ? $v[2] : 0;
        }
        $log = M('ScoreboxLog')->where(array('uid' => $uid, 'name' => 'checkin', 'create_time' => array('between', array(strtotime(date('Ymd'))-3600*24*4, strtotime(date('Ymd'))-1))))->order('create_time desc')->limit(5)->select();
        $i = 1;
        foreach($log as $k => $v){
            $date = date('Ymd',strtotime('-'.($i).' day'));
            if(date('Ymd', $v['create_time']) != $date){
                break;
            }
            $i++;
        }
        $score = 0;
        $exper = 0;
        foreach($_rule as $k => $v){
            if($i >= $k){
                $score = $v['score'];
                $exper = $v['exper'];
            }else{
                break;
            }
        }
        return array('score' => $score, 'exper' => $exper);
    }
    
    private function _buy($config, $uid, $num = 1){
        $rule = explode("\r\n",$config['score']);
        foreach($rule as $v){
            $v = explode('=', $v);
            $_rule[$v[0]]['score'] = $v[1];
            $_rule[$v[0]]['exper'] = isset($v[2]) ? $v[2] : 0;
        }
        
        $score = 0;
        $exper = 0;
        foreach($_rule as $k => $v){
            if($num >= $k){
                $score = $v['score'];
                $exper = $v['exper'];
            }else{
                break;
            }
        }
        return array('score' => $score, 'exper' => $exper);
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