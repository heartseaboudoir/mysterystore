<?php
namespace User\Api;
use User\Api\Api;

class ScoreboxApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        $this->model = D('Scorebox');
    }

    public function info($uid){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        return $this->model->get_info($uid);
    }
    
    public function get_level($val = 0, $type = 'exper'){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        return $this->model->get_level($val, $type);
    }
    
    public function level_data($update){
        is_null($update) && $update = 0;
        return $this->model->get_level_data($update);
    }
    
    public function get_score($uid){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        return $this->model->get_score($uid);
    }
    
    public function add_score($uid, $name, $num){
        is_null($num) && $num = 1;
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        return $this->model->add_score($uid, $name, $num);
    }
    public function dec_score($uid, $score, $name){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        return $this->model->dec_score($uid, $score, $name);
    }
    
    public function score_log($uid, $type, $page, $row){
        $uid = intval($uid);
        if($uid < 1){
            return array('status' => 0);
        }
        is_null($type) && $type = 0;
        $page = intval($page);
        is_null($page) && $page = 1;
        $page < 1 && $page = 1;
        $row = intval($row);
        is_null($row) && $row = 20;
        $row < 0 && $row = 0;
        
        $where = array();
        $where['uid'] = $uid;
        $type > 0 && $where['type'] = $type;
        $Model = M('ScoreboxLog');
        $data = $Model->where($where)->page($page, $row)->order('create_time desc')->field('id, name, score, type, create_time')->select();
        if($data){
            foreach($data as $v){
                $name[] = $v['name'];
            }
            $name = M('ScoreboxConfig')->where(array('name' => array('in', $name)))->field('name,title, description')->select();
            foreach($name as $v){
                $_name[$v['name']] = $v;
            }
            foreach($data as $k => $v){
                $v['title'] = isset($_name[$v['name']]['title']) ? $_name[$v['name']]['title'] : '';
                $v['description'] = isset($_name[$v['name']]['description']) ? $_name[$v['name']]['description'] : '';
                unset($v['name'], $v['uid']);
                $v['create_time_text'] = date('Y-m-d', $v['create_time']);
                $data[$k] = $v;
            }
        }else{
            $data = array();
        }
        
        if(!$data && $page == 1){
            $total = 0;
        }else{
            $total = $Model->where($where)->count();
        }
        $count = count($data);
        return array('data' => $data, 'page' => $page, 'row' => $row, 'total' => $total, 'count' => $count);
    }
    
    public function checkin($uid){
        $uid = intval($uid);
        if($uid < 1){
            return array('status' => 0, 'msg' => '用户不存在');
        }
        $set_result = $this->_set_checkin_times($uid);
        if($set_result['status'] == 2){
            return array('status' => 0, 'msg' => '今天已签到');
        }elseif($set_result['status'] == 0){
            return array('status' => 0, 'msg' => '签到失败');
        }
        $times = $set_result['times'];
        $LogModel = M('ScoreboxLog');
        if($LogModel->where(array('uid' => $uid, 'name' => 'checkin', 'create_time' => array('between', array(strtotime(date('Ymd')), strtotime(date('Ymd'))+3600*24))))->find()){
            return array('status' => 0, 'msg' => '今天已签到');
        }
        $result = $this->model->add_score($uid, 'checkin');
        if($result){
            $score_data = $this->model->get_info($uid);
            // 今天的签到后显示
            $now = $this->_get_check_day_data($times);
            $day_data = $now['day_data'];
            $next_score = $now['next_score'];
            $data = array(
                'action_score' => $result['score'],
                'score' => !empty($score_data['score']) ? $score_data['score'] : 0,
                'exper' => !empty($score_data['exper']) ? $score_data['exper'] : 0,
                'level_title' => !empty($score_data['level_title']) ? $score_data['level_title'] : '',
                'level_icon' => !empty($score_data['level_icon']) ? get_cover_url($score_data['level_icon']) : '',
                'check_times' => $times,
                'next_score' => $next_score,
                'day_data' => $day_data
            );
            return array('status' => 1, 'data' => $data, 'msg' => '签到成功');
        }else{
            $this->_return_checkin_times($uid, $set_result['ck_data']);
            return array('status' => 0, 'msg' => '签到失败');
        }
    }
    // 设置连续签到次数
    private function _set_checkin_times($uid){
        $CModel = M('UserCheckinCount');
        $ck_data = $CModel->where(array('uid' => $uid))->find();
        if($ck_data['do_date'] == date('Y-m-d')){
            return array('status' => 2);
        }
        if($ck_data){
            $times = ($ck_data['do_date'] == date('Y-m-d', strtotime('-1 day'))) ? ($ck_data['times'] + 1) : 1;
            if(!$CModel->where(array('uid' => $uid, 'do_date' => $ck_data['do_date']))->save(array('times' => $times, 'do_date' => date('Y-m-d'), 'update_time' => NOW_TIME))){
                return array('status' => 0);
            }
        }else{
            $ck_data = array();
            $times = 1;
            $i_data = array(
                'uid' => $uid,
                'times' => $times,
                'do_date' => date('Y-m-d'),
                'create_time' => NOW_TIME,
                'update_time' => NOW_TIME
            );
            if(!$CModel->add($i_data)){
                return array('status' => 0);
            }
        }
        return array('status' => 1 ,'times' => $times, 'ck_data' => $ck_data);
    }
    // 返回连续签到次数
    private function _return_checkin_times($uid, $data){
        $CModel = M('UserCheckinCount');
        $ck_data = $CModel->where(array('uid' => $uid))->find();
        if(!$data){
            $data['do_date'] = '';
            $data['times'] = 0;
        }
        if($ck_data['times'] > $data['times']+1){
            // 如果之前有签到，且当前签到次数比之前多，则直接减次数
            $CModel->where(array('id' => $ck_data['id']))->save(array('times' => array('exp', 'times-1')));
        }else{
            // 如果之前无签到或当前签到次数跟之前一样，则减次数、退回日期
            $CModel->where(array('id' => $ck_data['id']))->save(array('times' => array('exp', 'times-1'), 'do_date' => $data['do_date']));
        }
    }
    
    public function get_before_checkin($uid){
        $uid = intval($uid);
        if($uid < 1){
            return false;
        }
        $CModel = M('UserCheckinCount');
        $ck_data = $CModel->where(array('uid' => $uid))->find();
        if($ck_data){
            if($ck_data['do_date'] == date('Y-m-d')){
                $pre_times = $ck_data['times'];
            }elseif($ck_data['do_date'] == date('Y-m-d', strtotime('-1 day'))){
                $pre_times = $ck_data['times'];
            }else{
                $pre_times = 0;
            }
        }else{
            $pre_times = 0;
        }
        // 昨天的签到显示
        $pre = $this->_get_check_day_data($pre_times, 1);
        $pre_day_data = $pre['day_data'];
        $next_score = $pre['next_score'];
        $data = array(
            'next_score' => $next_score,
            'check_times' => $pre_times,
            'day_data' => $pre_day_data
        );
        return $data;
    }
    // 生成签到天数数组
    private function _get_check_day_data($times, $day_t = 0){
        $config = M('ScoreboxConfig')->where(array('name' => 'checkin'))->find();
        $rule = explode("\r\n",$config['score']);
        foreach($rule as $v){
            $v = explode('=', $v);
            $_rule[$v[0]]['score'] = $v[1];
        }
        // 已签到3天及以上则前后取2天
        if($times >= 3){
            $pre = 2;
            $next = 2;
        }elseif($times == 0){
            $pre = 0;
            $next = 5;
        }else{
            // 少于3天则前面的天数为 已签天数减去1  后面的天数为 5减去已签天数
            $pre = $times - 1;
            $next = 5 - $times;
        }
        $day_data = array();
        $date_format = 'm.d';
        for($i = $pre; $i > 0;  $i--){
            $_times = $times - $i;
            foreach($_rule as $k => $v){
                if($_times >= $k){
                    $_score = $v['score'];
                }else{
                    break;
                }
            }
            $day_data[] = array(
                'date' => date($date_format, strtotime('-'.($i+$day_t).' day')),
                'score' => $_score,
                'is_check' => 1,
            );
        }
        if($times > 0){
            $_times = $times;
            foreach($_rule as $k => $v){
                if($_times >= $k){
                    $_score = $v['score'];
                }else{
                    break;
                }
            }
            $day_data[] = array(
                'date' => date($date_format,  strtotime('-'.($day_t).' day')),
                'score' => $_score,
                'is_check' => 1,
            );
        }
        // 下一次签到的积分
        $next_score = 0;
        for($i = 1; $i <= $next;  $i++){
            $_times = $times + $i;
            foreach($_rule as $k => $v){
                if($_times >= $k){
                    $_score = $v['score'];
                }else{
                    break;
                }
            }
            if($i == 1){
                $next_score = $_score;
            }
            $day_data[] = array(
                'date' => date($date_format, strtotime('+'.($i-$day_t).' day')),
                'score' => $_score,
                'is_check' => 0,
            );
        }
        return array('day_data' => $day_data, 'next_score' => $next_score);
    }
    
    public function exchange_info($id, $field){
        is_null($field) && $field = '*';
        $info = M('ScoreboxExchange')->where(array('id' => $id))->field($field)->find();
        return $info ? $info : array();
    }
    
    public function is_checkin($uid){
        $where = array(
            'uid' => $uid, 
            'name' => 'checkin', 
            'create_time' => array('between', array(strtotime(date('Ymd')), strtotime(date('Ymd'))+3600*24))
        );
        $result = M('ScoreboxLog')->where($where)->find();
        return $result ? 1 : 0;
    }
}
