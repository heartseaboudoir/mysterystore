<?php
namespace User\Api;
use User\Api\Api;

class ReceiptApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        $this->model = D('UserReceipt');
    }

    public function add($data){
        $is_default = 0;
        if(isset($data['is_default'])){
            $data['is_default'] == 1 && $is_default = 1;
            unset($data['is_default']);
        }
        $data = $this->model->create($data);
        if(!$data){
            $error = $this->model->getError();
            !$error && $error = '添加失败';
            return array('status' => 0, 'msg' => $error);
        }
        $id = $this->model->add();
        if(!$id){
            return array('status' => 0, 'msg' => '添加失败');
        }
        $data['id'] = $id;
        if($is_default == 1){
            $this->model->set_default($data['uid'], $id);
        } 
        $data['is_default'] = $is_default;
        return array('status' => 1, 'data' => $data);
    }
    
    public function edit($id, $data){
        $is_default = 0;
        if(isset($data['is_default'])){
            $data['is_default'] == 1 && $is_default = 1;
            unset($data['is_default']);
        }
        $data['id'] = $id;
        $data = $this->model->create($data);
        if(!$data){
            $error = $this->model->getError();
            !$error && $error = '添加失败';
            return array('status' => 0, 'msg' => $error);
        }
        $result = $this->model->where(array('id' => $id))->save($data);
        if(!$result){
            return array('status' => 0, 'msg' => '添加失败');
        }
        if($is_default == 1){
            $this->model->set_default($data['uid'], $id);
        } 
        $data['is_default'] = $is_default;
        return array('status' => 1, 'data' => $data);
    }
    
    public function info($id, $uid = 0, $field = '*'){
        return $this->model->get_info($id, $uid, $field);
    }
    public function lists($uid, $page, $size, $field){
        is_null($page) && $page = 1;
        $uid = intval($uid);
        $page = intval($page);
        $page < 1 && $page = 1;
        is_null($size) && $size = 10;
        $size = intval($size);
        $size < 0 && $size = 0;
        is_null($field) && $field = '*';
        $where = array('uid' => $uid);
        $order = 'is_default desc, id asc';
        $lists = $this->model->where($where)->page($page, $size)->field($field)->order($order)->select();
        !$lists && $lists = array();
        $total = $this->model->where($where)->count();
        $count = count($lists);
        return array('data' => $lists, 'count' => $count, 'total' => $total, 'page' => $page, 'size' => $size);
    }
    public function del($id, $uid){
        $id = intval($id);
        is_null($uid) && $uid = 0;
        $uid = intval($uid);
        $where = array();
        $where['id'] = $id;
        $uid > 0 && $where['uid'] = $uid;
        if($this->model->where($where)->delete()){
            $this->model->set_default($uid);
            return 1;
        }else{
            return 0;
        }
    }
    public function get_default($uid){
        return $this->model->get_default($uid);
    }
}
