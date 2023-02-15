<?php
/**
 * 商品属性管理类
 * User: zzy
 * Date: 2018-04-16
 * Time: 12:48
 */
namespace Addons\Goods\Controller;
use Admin\Controller\AddonsController;

class GoodsAttributeController extends AddonsController{
    public function __construct(){
        parent::__construct();
    }

   

    /**
     * 编辑/添加商品属性值方法
     */
    public function attrvalueupdate(){
    	$value_name= I('post.value_name','','trim');
    	$bar_code = I('post.bar_code','','trim');
    	$goods_id = I('post.goods_id','');
    	$value_id = I("post.value_id",'');
    	if(empty($value_name)|| empty($bar_code)){
    		$this->response(0,'值不能为空');
    	}
        $AttrValueModel = D("Addons://Goods/AttrValue");
        $res = $AttrValueModel->update();
        $msg = $value_id ? '编辑' : '添加';
        if(!$res){
        	$this->response(0,$AttrValueModel->getError()?$AttrValueModel->getError():$msg.'失败');
      
        }else{
        	$this->response(200,$msg.'属性成功');
        	
        }
    }

    /**
     * 删除属性值方法
     */
    public function attrvaluedelete(){
            $value_id = I("post.value_id",0,"intval");
            $goods_id = I("post.goods_id",0,"intval");
            $status = I('post.status',2,'intval');
            if(!$value_id || !$goods_id){
            	$this->response(0,'请选择数据！');
            }
            if($value_id){
            	 $warehouseStockModel = M('WarehouseStock');
            	if($status == 2){
            		$sel = $warehouseStockModel->alias('WS')->field('WS.num,W.w_name,W.w_id')->join("inner join hii_warehouse W on W.w_id=WS.w_id")->where(array('WS.goods_id'=>$goods_id,'WS.value_id'=>$value_id,'WS.num'=>array('gt',0)))->select();
            		if(!empty($sel)){
            			$select = array('num'=>array_sum(array_column($sel,'num')),'w_count'=>count($sel),'w_name'=>$sel[0]['w_name'],'w_id'=>$sel[0]['w_id']);
            			$this->response(2,$select);
            		}
            	} 
            	
                $AttrValueModel = D("Addons://Goods/AttrValue");
                $id = $AttrValueModel->where(array("value_id"=>$value_id))->save(array('status'=>$status));
                $id = M('GoodsBarCode')->where(array("value_id"=>$value_id))->delete();
                
                if(!$id){
    				$this->response(0,'找不到数据！');
                }else{
                	$this->response(200,'成功！');
                }
            }else{
            	$this->response(0,'请选择数据！');
            }

    }
    protected function response($code = 200, $msg = '已处理请求')
    {
    	if ($code == 200) {
    		$data = array(
    				'code' => 200,
    				'content' => $msg,
    		);
    	} else {
    		$data = array(
    				'code' => $code,
    				'content' => $msg,
    		);
    	}
    
    	//echo json_encode($data, JSON_UNESCAPED_UNICODE);
    	echo json_encode($data);
    	exit;
    }
}