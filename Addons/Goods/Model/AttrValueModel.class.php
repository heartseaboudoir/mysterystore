<?php
/**
 * 属性值类
 * User: zzy
 * Date: 2018/4/16
 * Time: 23:09
 */
namespace Addons\Goods\Model;
use Think\Model;

class AttrValueModel extends Model{
    protected $_auto = array(
        array('ctime', NOW_TIME, self::MODEL_INSERT),
    	array('bar_code', 'set_bar_code', self::MODEL_BOTH, 'callback')
    );
    protected $_validate = array(
    		array('value_name', 'require', '请填写属性名', self::MUST_VALIDATE),
    		array('value_name', '1,40', '属性名长度不能超过60个字', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
    		array('bar_code', 'require', '请填写条形码', self::MUST_VALIDATE),
    		array('bar_code', 'check_bar_code', '有条形码已被使用，请检查后更改', self::MUST_VALIDATE, 'callback'),
 
    );
    
 	 protected function check_bar_code($param){
	     $code = explode("\n", $param);
	     if(empty($code[1])){
	     	$code = explode("\r\n", $param);
	     }
	     $value_id = I("post.value_id",'');
		     $where = array();
		     $value_id && $where['value_id'] = array('neq', $value_id);
		     foreach($code as $v){
			     $where['bar_code'] = trim($v);
			     if(M('GoodsBarCode')->where($where)->find()){
			     return false;
		     }
	     }
	     return true;
     } 
     protected function set_bar_code($param){
     $code = explode("\n", $param);
     if(empty($code[1])){
     	$code = explode("\r\n", $param);
     }
     foreach($code as $k => $v){
     $code[$k] = trim($v);
     }
     return implode("\r\n",array_unique($code));
     } 
    
    protected function _after_find(&$result,$options) {
        isset($result['ctime']) && $result['ctime'] = date('Y-m-d H:i:s', $result['ctime']);
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
 		$data = $this->where(array('value_id'=>$data['value_id']))->find();
    	$this->after_do($data, 2);
    }
    private function after_do($data, $type = 1){
    	 if(isset($data['bar_code'])){
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
    	 'goods_id' => $data['goods_id'],
    	 'bar_code' => $v,
    	 'value_id' => $data['value_id'],
    	 'create_time' => time(),
    	 );
    	 $a = $BarCodeModel->add($code_data);
    	 }
    	 if($type == 2) $BarCodeModel->where(array('value_id' => $data['value_id'], 'bar_code' => array('not in', $bar_code)))->delete();
    	 //把条形码集合存入goods表
    	$goods_bar_code_array = $BarCodeModel->field('bar_code')->where('goods_id='.$data['goods_id'])->select();
    	$goods_bar_code_array = implode("\r\n",array_column($goods_bar_code_array,'bar_code'));
    	M('Goods')->where('id='.$data['goods_id'])->save(array('bar_code'=>$goods_bar_code_array));
    	 } 
    }
    
}