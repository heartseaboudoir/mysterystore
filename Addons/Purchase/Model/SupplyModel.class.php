<?php


namespace Addons\Purchase\Model;
use Think\Model;

class SupplyModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
    protected $_validate = array(
            array('s_name', 'require', '请填写供应商名', self::MUST_VALIDATE),
            array('s_address', 'require', '请填写供应商详细地址', self::MUST_VALIDATE),
            array('shequ_id', 'checkShequId', '请填写供应商所在门店区域', self::MUST_VALIDATE, 'callback'),
    );
        
    protected $_auto = array(
		array('s_add_time', NOW_TIME, self::MODEL_INSERT),
		array('s_edit_time', NOW_TIME, self::MODEL_BOTH),
		array('admin_id', UID, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
		isset($result['s_add_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['s_add_time']);
		isset($result['s_edit_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['s_edit_time']);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
    
    protected function checkShequId($id) {
        if (empty($id)) {
            return false;
        } else {
            return true;
        }
    }

    public function update($data = NULL){
        $data = $this->create($data);
        if(!$data){
            return false;
        }
        if(empty($data['s_id'])){
            $id = $this->add();
            if(!$id){
                $this->error = '添加出错！';
                return false;
            }
        } else {
            $status = $this->save();
            if(false === $status){
                $this->error = '更新出错！';
                return false;
            }
        }
        return $data;
    }

}