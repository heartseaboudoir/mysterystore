<?php

namespace Addons\WechatMenu\Model;

use Think\Model;

/**
 * WechatMenu模型
 */
class WechatMenuModel extends Model {
    /**
     * 自动完成
     * @var array
     */
    protected $_validate = array(
            array('title', 'require', '请填写菜单名', self::MUST_VALIDATE),
    );
    
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
