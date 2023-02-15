<?php


namespace Addons\Goods\Model;
use Think\Model;

class GoodsCateModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        
	protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT), 
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);

	protected function _after_find(&$result,$options) {
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
                $status = array(1 => '启用' , 2 => '禁用');
                isset($result['status']) && $result['status_text'] = isset($status[$result['status']]) ? $status[$result['status']] : '';
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
        
    public function get_cate_tree(){
        $where = array();
        $where['status'] = 1;
        $list = $this->where($where)->order('listorder desc, create_time asc')->select();
        return $list ? $this->get_data($list) : array();
    }
    private function get_data($list) {
        // 取一级菜单
        foreach ($list as $k => $vo) {
            if ($vo ['pid'] != 0)
                continue;

            $one_arr [$vo ['id']] = $vo;
            unset($list [$k]);
        }

        foreach ($one_arr as $p) {
            $data [] = $p;

            $two_arr = array();
            foreach ($list as $key => $l) {
                if ($l ['pid'] != $p ['id'])
                    continue;

                $l ['title'] = '├──' . $l ['title'];
                $two_arr [] = $l;
                unset($list [$key]);
            }

            $data = array_merge($data, $two_arr);
        }

        return $data;
    }

}