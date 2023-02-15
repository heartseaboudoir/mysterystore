<?php

namespace Admin\Model;
use Think\Model;
/**
 * 配置模型
 */

class ShareConfigModel extends Model {
    protected $_validate = array(
        array('name', 'require', '标识不能为空', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('name', '', '标识已经存在', self::VALUE_VALIDATE, 'unique', self::MODEL_BOTH),
        array('title', 'require', '分享标题不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
        array('cover_id', 'require', '分享图片不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
        array('desc', 'require', '分享描述不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
    );

}
