<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

/**
 * Class GoodsInfoController
 * @package Addons\Goods\Controller
 * 显示商品的详细资料信息
 */
class GoodsInfoController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        //获取商品id
        $goodId = I('get.id', '');
        $Model = D('Addons://Goods/Goods');
        $ToreStock = M('GoodsStore');
        $WarehouseStock = M('WarehouseStock');
        $attrValueModel = M('AttrValue');

        //获取商品信息
        $data = $Model->where(array('id'=>$goodId))->find();
        $this->assign('data',$data);

        //获取门店库存数量
        $ToreStocknum = $ToreStock->where(array('goods_id'=>$goodId,'store_id'=>session('user_store.id')))->getField('num');
        $this->assign('storeinfo',array('num'=>$ToreStocknum,'name'=>session('user_store.title')));

        //获取仓库库存数
        $WarehouseStocknum = $WarehouseStock->where(array('w_id'=>session('user_warehouse.w_id'),'goods_id'=>$goodId))->getField('num');
        $this->assign('warehouseinfo',array('num'=>$WarehouseStocknum,'name'=>session('user_warehouse.w_name')));
        //获取商品分类信息
        $cate_ls = array();
        if($data){
            $cate_ls = M('GoodsCate')->field('title')->where(array('id'=>$data['cate_id']))->find();
        }
        $this->assign('cate_ls',$cate_ls);
        //获取商品属性
       $attrinfo = $attrValueModel->field('value_name')->where(array('status'=>array('NEQ',2),'goods_id'=>$goodId))->select();
       $this->assign('attrvalue',implode(',',array_column($attrinfo,'value_name')));
        $this->display(T('Addons://Goods@Admin/Goods/detail'));
    }

}