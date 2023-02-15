<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsStoreSwiftAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(D('Addons://Goods/GoodsStoreSwiftIndex'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '门店结款单列表';
        $this->display(T('Addons://Goods@Admin/GoodsStoreSwift/index'));
    }
    
    public function ls(){
        $id = I('id', 0, 'intval');
        $index = D('Addons://Goods/GoodsStoreSwiftIndex')->where(array('id' => $id, 'store_id' => $this->_store_id))->find();
        if(!$index){
            redirect(addons_url('Goods://GoodsStoreSwiftAdmin:/index'));
            exit;
        }
        $year = $index['year'];
        $month = $index['month'];
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['year'] = $year;
        $where['month'] = $month;
        $list = $this->lists(M('GoodsStoreSwift_'.$year), $where, 'goods_id asc', array());
        if($list){
            $goods_ids = array();
            foreach($list as $v){
                $goods_ids[] = $v['goods_id'];
            }
            $goods = M('Goods')->where(array('id' => array('in', $goods_ids)))->field('id,title,cover_id,status')->select();
            foreach($goods as $v){
                $_goods[$v['id']]= $v;
            }
            foreach($list as $k => $v){
                $v['goods'] = isset($_goods[$v['goods_id']]) ? $_goods[$v['goods_id']] : array();
                $list[$k] = $v;
            }
        }
        $this->assign('list', $list);
        $this->meta_title = '门店结款单 【'.$year.'.'.$month.'】';
        $this->display(T('Addons://Goods@Admin/GoodsStoreSwift/ls'));
    }
    
    public function goods_show(){
        $id = I('id');
        $log_id = I('log_id');
        
        $id = I('id', 0, 'intval');
        $index = D('Addons://Goods/GoodsStoreSwiftIndex')->where(array('id' => $id, 'store_id' => $this->_store_id))->find();
        if(!$index){
            $this->error('结款单不存在');
        }
        $year = $index['year'];
        $month = $index['month'];
        $data = D('Addons://Goods/GoodsStoreSwift_'.$year)->find($log_id);
        if(!$data){
            $this->error('记录不存在');
        }
        $goods = M('Goods')->where(array('id' => $data['goods_id']))->field('id,title,cover_id')->find();
        $data['goods'] = $goods;
        $this->assign('data', $data);
        $this->meta_title = '商品结算详细';
        $this->display(T('Addons://Goods@Admin/GoodsStoreSwift/goods_show'));
    }
    
    /**
     * 导出结款单记录
     */
    public function download_log(){
        
        $id = I('id', 0, 'intval');
        $index = D('Addons://Goods/GoodsStoreSwiftIndex')->where(array('id' => $id, 'store_id' => $this->_store_id))->find();
        if(!$index){
            $this->error('结款单不存在');
        }
        $year = $index['year'];
        $month = $index['month'];
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['year'] = $year;
        $where['month'] = $month;
        
        $list = D('Addons://Goods/GoodsStoreSwift_'.$year)->where($where)->order( 'goods_id asc')->select();
        if($list){
            foreach($list as $v){
                $goods_ids[] = $v['goods_id'];
            }
            $goods_data = M('Goods')->where(array('id' =>  array('in', $goods_ids)))->field('id,title,cate_id,status')->select();
            $cate_id = array();
            $_goods_data = array();
            foreach($goods_data as $v){
                $_goods_data[$v['id']] = $v;
                $cate_id[] = $v['cate_id'];
            }
            if($cate_id){
                $where = array();
                $where['id'] = array('in', implode(',', $cate_id));
                $cate = M('GoodsCate')->select();
                foreach($cate as $c){
                    $cate_title[$c['id']] = $c['title'];
                }
            }
            foreach($_goods_data as $k => $v){
                $v['cate_title'] = $cate_title[$v['cate_id']];
                $_goods_data[$k] = $v;
            }
        }
        
        $meta_title = "{$year}.{$month}结款单";
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename='.$meta_title.'.xls');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: max-age=0');
        $l = '<style>'
                . 'body{font-size:16px;}'
                . 'img{max-height:150px; max-width:200px;}'
                . 'tr{height:50px;}'
                . '.tr0{background:#eee;}'
                . 'td{border:1px solid #ccc; text-align:center;}'
                . 'td.status2{color:#aaa;}'
                . 'td.status-1{color:#aaa;text-decoration:line-through;}'
                . '</style>';
        $l .= '<table>';
        
        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="14">'.$meta_title.' </td></tr>';
        $l .= '<tr><td width="150">商品ID</td><td width="150">商品分类</td><td width="300">商品名</td><td width="100">上期库存</td><td width="100">本月库存</td><td width="100">本月入库</td><td width="100">本月出库</td><td width="100">销售量</td><td width="100">销售价格</td><td width="100">应结数量</td><td width="100">应结货款</td><td width="100">丢失数量</td><td width="100">丢失率</td><td width="100">状态</td></tr>';
        
        foreach ($list as $key => $val) {
            $l .= '<tr class="tr'.($key%2).'">';
            switch($val['status']){
                case 1:
                    $status_text = '上架';
                    $style = '';
                    break;
                case 2:
                    $style = 'color:#ccc;';
                    $status_text = '下架';
                    break;
                case -1:
                    $style = 'color:#aaa;text-decoration:line-through;';
                    $status_text = '已删除';
                    break;
            }
            $value = array(
                $val['goods_id'],$_goods_data[$val['goods_id']]['cate_title'],  $_goods_data[$val['goods_id']]['title'], $val['prev_month_num'], $val['now_month_num'], $val['in_num'], $val['out_num'], $val['sell_num'], $val['price'], $val['result_num'], $val['result_money'], $val['lost_num'], $val['lost_rand'].'%', $status_text
            );
            foreach($value as $k => $v){
                $l .= '<td class="td'.$k.'" style="'.$style.'">'.$v.'</td>';
            }
            $l .= '</tr>';
        }
        $l .= '</table>';
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }
}
