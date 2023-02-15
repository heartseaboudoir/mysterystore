<?php

namespace Addons\AlipayServer\Model;

use Think\Model;

/**
 * AlipayServerMenu模型
 */
class AlipayServerMenuModel extends Model {
    /**
     * 自动完成
     * @var array
     */
    protected $_validate = array(
            array('title', 'require', '请填写菜单名', self::MUST_VALIDATE),
            array('title', 'check_top_title', '一级菜单的标题不超过4个汉字', self::MUST_VALIDATE, 'callback'),
            array('title', 'check_title', '二级菜单的标题不超过12个汉字', self::MUST_VALIDATE, 'callback'),
            array('type', 'check_type', '请选择类型', self::MUST_VALIDATE, 'callback'),
            array('param', 'check_content_empty', '参数内容不能为空', self::MUST_VALIDATE, 'callback'),
    );
    protected function check_top_title($param){
        $pid = I('post.pid', 0, 'intval');
        if($pid == 0 && strlen($param) > 12){
            return false;
        }
        return true;
    }
    protected function check_title($param){
        $pid = I('post.pid', 0, 'intval');
        if($pid && strlen($param) > 36){
            return false;
        }
        return true;
    }
    protected function check_type($param){
        $pid = I('post.pid', 0, 'intval');
        if($pid > 0 && !$param){
            return false;
        }
        return true;
    }
    protected function check_content_empty($param){
        $type = I('post.type', '', 'intval');
        if($type && !$param){
            return false;
        }
        return true;
    }
    protected $_auto = array(
        
    );
    public function update($data = NULL) {
        $data = $this->create($data);
        if(!$data){
            return false;
        }
        if (empty($data['id'])) {
            $id = $this->add();
            if (!$id) {
                !$this->error && $this->error = '添加出错！';
                return false;
            }
        } else {
            $status = $this->save();
            if (false === $status) {
                !$this->error && $this->error = '更新出错！';
                return false;
            }
        }
        return $data;
    }

}
