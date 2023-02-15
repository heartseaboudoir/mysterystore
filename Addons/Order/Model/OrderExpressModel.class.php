<?php

namespace Addons\Order\Model;
use Think\Model;

class OrderExpressModel extends Model{
    /**
     * 自动完成
     * @var array
     */
    protected $_validate = array(
        array('company', 'require', '请填写快递公司名', self::MUST_VALIDATE),
        array('company', '', '快递公司名已存在', self::MUST_VALIDATE, 'unique'),
        array('name', 'require', '请填写标识', self::MUST_VALIDATE),
        array('name', '', '标识已存在', self::MUST_VALIDATE, 'unique'),
        array('search_no', 'require', '请填写第三方平台标识', self::MUST_VALIDATE),
        array('search_no', '', '第三方平台标识已存在', self::MUST_VALIDATE, 'unique'),
    );
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
    );

    protected function _after_insert($data, $options) {
        parent::_after_insert($data, $options);
        $this->express_data(true);
    }

    protected function _after_update($data, $options) {
        parent::_after_update($data, $options);
        $this->express_data(true);
    }

    public function express_data($update = false){
        $data = S('ORDER_EXPRESS_DATA');
        if(!$data || $update){
            $data = $this->field('company,name,search_no')->where(array('status' => 1))->order('listorder desc, id asc')->select();
            if(!$data){
                return array();
            }
            $data = reset_data($data, 'name', $data);
            S('ORDER_EXPRESS_DATA', $data);
        }
        return $data;
    }
    
    public function get_express($name){
        $data = $this->express_data();
        return isset($data[$name]) ? $data[$name] : false;
    }
}