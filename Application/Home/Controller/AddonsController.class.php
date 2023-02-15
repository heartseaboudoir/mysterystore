<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Think\Controller;

/**
 * 扩展控制器
 * 用于调度各个扩展的URL访问需求
 */
class AddonsController extends Controller{

	protected $addons = null;

	public function execute($_addons = null, $_controller = null, $_action = null){
		if(C('URL_CASE_INSENSITIVE')){
			$_addons = ucfirst(parse_name($_addons, 1));
			$_controller = parse_name($_controller,1);
		}

		if(!empty($_addons) && !empty($_controller) && !empty($_action)){
			$Addons = A("Addons://{$_addons}/{$_controller}")->$_action();
		} else {
			$this->error('没有指定插件名称，控制器或操作！');
		}
	}

        protected function lists ($model,$where=array(),$order='',$base = array('status'=>array('egt',0)),$field=true){
            $options    =   array();
            $REQUEST    =   (array)I('request.');
            if(is_string($model)){
                $model  =   M($model);
            }

            $OPT        =   new \ReflectionProperty($model,'options');
            $OPT->setAccessible(true);

            $pk         =   $model->getPk();
            if($order===null){
                //order置空
            }else if ( isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']),array('desc','asc')) ) {
                $options['order'] = '`'.$REQUEST['_field'].'` '.$REQUEST['_order'];
            }elseif( $order==='' && empty($options['order']) && !empty($pk) ){
                $options['order'] = $pk.' desc';
            }elseif($order){
                $options['order'] = $order;
            }
            unset($REQUEST['_order'],$REQUEST['_field']);

            $options['where'] = array_filter(array_merge( (array)$base, /*$REQUEST,*/ (array)$where ),function($val){
                if($val===''||$val===null){
                    return false;
                }else{
                    return true;
                }
            });
            if( empty($options['where'])){
                unset($options['where']);
            }
            $options      =   array_merge( (array)$OPT->getValue($model), $options );
            $total        =   $model->where($options['where'])->count();

            if( isset($REQUEST['r']) ){
                $listRows = (int)$REQUEST['r'];
            }else{
                $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
            }
            $page = new \Think\Page($total, $listRows, $REQUEST);
            if($total>$listRows){
                $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            }
            $p =$page->show();
            $this->assign('_page', $p? $p: '');
            $this->assign('_total',$total);
            $options['limit'] = $page->firstRow.','.$page->listRows;

            $model->setProperty('options',$options);

            return $model->field($field)->select();
        }

}
