<?php
namespace User\Api;
use User\Api\Api;

class MessageApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        
    }
    
    public function notice_lists($uid, $type, $cate_id, $page, $row){
        $page = intval($page);
        $uid = intval($uid);
        $page < 1 && $page = 1;
        $row = intval($row);
        $Model = M('MessageNotice');
        $where = array();
        $where['uid'] = array('in', array($uid, 0));
        if($cate_id){
            $cate = M('MessageNoticeCate')->where(array('id' => $cate_id))->find();
            if(!$cate){
                return array('data' => array(), 'page' => $page, 'row' => $row, 'count' => 0, 'total' => 0);
            }
            $type = $cate['type_data'];
        }
        $type && $where['type'] = array('in', $type);
        $data = $Model->where($where)->page($page, $row)->order('id desc')->select();
        !$data && $data = array();
        $total = $Model->where($where)->count();
        $count = count($data);
        // 设置为已读
        $Model->where($where)->save(array('is_read' => 1));
        return array('data' => $data, 'page' => $page, 'row' => $row, 'count' => $count, 'total' => $total);
    }
    
    public function add_notice($uid, $type, $act_uid, $act_id, $title, $content, $hid, $param, $t){
        $uid = intval($uid);
        is_null($t) && $t = '';
        $Model = M('MessageNotice');
        $hash_data = array(
            'uid' => $uid,
            'act_uid' => $act_uid,
            'type' => $type,
            'act_id' => $act_id,
        );
        
        $hid != '' && $hash_data['hid'] = $hid;
        $t != '' && $hash_data['t'] = $t;
        $c_where = array('type' => $type);
        $hid && $c_where['act'] = $hid;
        $config = M('MessageNoticeConfig')->where($c_where)->find();
        if($config){
            is_null($title) && $title = $config['c_title'];
            is_null($content) && $content = $config['c_content'];
        }
        $hash = md5(json_encode($hash_data));
        if($Model->where(array('hash' => $hash))->find()){
            return 0;
        }
        !is_array($param) && $param = json_decode($param, true);
        $param = json_encode($param);
        $data = array(
            'uid' => $uid,
            'type' => $type,
            'act_uid' => $act_uid,
            'act_id' => $act_id,
            'act_data' => $param,
            'title' => $title ? $title : '',
            'content' => $content ? $content : '',
            'hid' => $hid,
            'hash' => $hash,
            't' => $t,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        if($Model->create($data)){
            if($Model->add()){
                return 1;
            }
        }
        return 0;
    }
    
    public function remove_notice($type, $act_id, $uid, $hid, $t){
        $uid = intval($uid);
        if(!$type || !$act_id){
            return false;
        }
        $where = array(
            'type' => $type,
            'act_id' => $act_id,
        );
        $uid > 0 && $where['uid'] = $uid;
        $hid && $where['hid'] = $hid;
        $t && $where['t'] = $t;
        return M('MessageNotice')->where($where)->delete() ? 1 : 0;
    }
    
    public function get_no_read_num($uid, $type_data){
        $uid = intval($uid);
        $cate = M('MessageNoticeCate')->where(array('id' => array('in', $type_data)))->select();
        foreach($cate as $v){
            $cate_t[$v['id']] = $v['type_data'];
        }
        foreach($type_data as $k => $v){
            if(empty($cate_t[$v])){
                continue;
            }
            $result[$k] = M('MessageNotice')->where(array('uid' => $uid, 'type' => array('in', $cate_t[$v]), 'is_read' => 0))->field('id')->count();
        }
        return $result;
    }
}
