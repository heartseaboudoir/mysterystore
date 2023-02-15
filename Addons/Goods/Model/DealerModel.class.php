<?php


namespace Addons\Goods\Model;
use Think\Model;

class DealerModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('title', 'require', '请填写经销商名', self::MUST_VALIDATE),
        );
        
        protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}

        public function update($data = NULL){
            $data = $this->create($data);
            if(!$data){
                return false;
            }
            if(empty($data['id'])){
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