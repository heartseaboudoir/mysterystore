<?php


namespace Addons\Goods\Model;
use Think\Model;

class GoodsModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('title', 'require', '请填写商品名', self::MUST_VALIDATE),
                array('title', '1,40', '商品名长度不能超过60个字', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
                array('code', 'require', '请填写编号', self::MUST_VALIDATE),
                array('code', '', '编号已存在', self::MUST_VALIDATE, 'unique'),
                //array('bar_code', 'require', '请填写条形码', self::MUST_VALIDATE),
                //array('bar_code', 'check_bar_code', '有条形码已被其他商品使用，请检查后更改', self::MUST_VALIDATE, 'callback'),
                array('cate_id', '/^[1-9][0-9]*$/', '请选择分类', self::VALUE_VALIDATE),
                array('cover_id', '/^[1-9][0-9]*$/', '请上传商品展示图片', self::VALUE_VALIDATE),
                array('sell_price', 'require', '请填写售价', self::MUST_VALIDATE,'',1),
                array('sell_price', '/^[0-9][0-9]*\.{0,1}[0-9]*$/', '请正确填写售价', self::VALUE_VALIDATE,'',1),
                array('bar_code_attr', 'require', '请填写条形码', self::MUST_VALIDATE,'',1),
                array('bar_code_attr', 'check_bar_code', '有条形码已被其他商品使用，请检查后更改', self::MUST_VALIDATE,'callback',1),
        );
         protected function check_bar_code($param){
            $code = explode("\r\n", $param);
            $where = array();
            foreach($code as $v){
                $where['bar_code'] = trim($v);
                if(M('GoodsBarCode')->where($where)->find()){
                    return false;
                }
            }
            return true;
        }
       /*  protected function set_bar_code($param){
            $code = explode("\r\n", $param);
            foreach($code as $k => $v){
                $code[$k] = trim($v);
            }
            return implode("\r\n",array_unique($code));
        } */
        protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
		//array('bar_code', 'set_bar_code', self::MODEL_BOTH, 'callback'),
                array('pinyin', 'set_pinyin', self::MODEL_BOTH, 'callback'),
                array('fir_letter', 'set_letter', self::MODEL_BOTH, 'callback'),
                array('sell_online', 'set_is_empty', self::MODEL_BOTH, 'callback'),
                array('sell_outline', 'set_is_empty', self::MODEL_BOTH, 'callback'),
	);
	
	protected function set_is_empty($param = false){
		return $param ? 1 : 0;
	}
	protected function set_pinyin(){
		return get_pinyin($_POST['title']);
	}
	protected function set_letter(){
		return get_letter($_POST['title']);
	}
        
	protected function _after_find(&$result,$options) {
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
                $status = array(1 => '上架' , 2 => '下架');
                isset($result['status']) && $result['status_text'] = isset($status[$result['status']]) ? $status[$result['status']] : '';
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        protected function _after_insert($data, $options) {
            parent::_after_insert($data, $options);
            $this->after_do($data, 1);
        }
        
        protected function _after_update($data, $options) {
            parent::_after_update($data, $options);
            $this->after_do($data, 2);
            M('GoodsLog')->add(array('goods_id' => $data['id'], 'uid' => UID, 'create_time' => NOW_TIME, 'data' => json_encode($data), 'type' => 2));
        }

        private function after_do($data, $type = 1){
           /*  if(isset($data['bar_code'])){
                // 添加条形码
                $bar_code = explode("\r\n", $data['bar_code']);
                $where = array();
                $BarCodeModel = M('GoodsBarCode');
                foreach($bar_code as $v){
                    $v = trim($v);
                    if(!$v){
                        continue;
                    }
                    $where['bar_code'] = $v;
                    if($BarCodeModel->where($where)->find()){
                        continue;
                    }
                    $code_data = array(
                        'goods_id' => $data['id'],
                        'bar_code' => $v,
                        'create_time' => time(),
                    );
                    $BarCodeModel->add($code_data);
                }
                $type == 2 && $BarCodeModel->where(array('goods_id' => $data['id'], 'bar_code' => array('not in', $bar_code)))->delete();
            } */
        	if($type == 1){
        		//新增属性
        		$AttrValueModel = D("Addons://Goods/AttrValue");
        		$goods_id = $this->getLastInsID();
        		$bar_code = I('bar_code_attr','','trim');
        		$value_name = I('value_name_attr','','trim');
        		$attr_add = $AttrValueModel->update(array('value_name'=>$value_name,'goods_id'=>$goods_id,'bar_code'=>$bar_code,'ctime'=>time()));

        	}
        	
        	
            if(isset($data['title'])){
                // 添加关键字
                $tags = get_tags($data['title']);
                $model = M('GoodsTag');
                foreach($tags as $v){
                    if(!$model->where(array('title' => $v))->find()){
                        $py = get_pinyin($v);
                        $fl = get_letter($v);
                        $model->add(array('title' => $v, 'pinyin' => $py, 'fir_letter' => $fl));
                    }
                }
                // 删除原来的标题关键字并添加
                if($type == 2){
                    if($model->where(array('goods_id' => $data['id'], 'tite' => array('neq' , $data['title'])))->delete()){
                        $py = get_pinyin($data['title']);
                        $fl = get_letter($data['title']);
                        $model->add(array('title' => $data['title'], 'pinyin' => $py, 'fir_letter' => $fl, 'goods_id' => $data['id']));
                    }
                }else{
                    $py = get_pinyin($data['title']);
                    $fl = get_letter($data['title']);
                    $model->add(array('title' => $data['title'], 'pinyin' => $py, 'fir_letter' => $fl, 'goods_id' => $data['id']));
                }
            }
            
            // 添加操作记录
            M('GoodsLog')->add(array('goods_id' => $data['id'], 'uid' => UID, 'create_time' => NOW_TIME, 'data' => json_encode($data), 'type' => $type));
        }
}