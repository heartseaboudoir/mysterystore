<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsInventoryAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->check_store();
        $this->assign('last_date', $this->get_last_date());
        $this->assign('check_do_invent', $this->check_do_invent());
    }
    
    private function get_last_date(){
        return date('Y-m-d', strtotime(date('Y-m-01')." +1 month -2 day"));
    }
    
    private function check_do_invent(){
        return $this->get_last_date() == date('Y-m-d') ? 1 : 0;
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(D('Addons://Goods/GoodsInventoryLs'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '月末盘点管理';
        $this->display(T('Addons://Goods@Admin/GoodsInventory/index'));
    }
    
    public function log(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $year = I('year', date('Y'));
        $month = I('month', date('m'));
        if($year > date('Y') || ($year == date('Y') && $month > date('m'))){
            redirect(addons_url('Goods://GoodsInventoryAdmin:/index'));
        }
        $where = array('store_id' => $this->_store_id, 'year' => $year, 'month' => $month);
        $list = $this->lists(D('Addons://Goods/GoodsInventory'), $where);
        if($list){
            $goods_ids = array();
            foreach($list as $v){
                $goods_ids[] = $v['goods_id'];
            }
            $goods_data = M('Goods')->where(array('id' => array('in', $goods_ids)))->field('id,title')->select();
            foreach($goods_data as $v){
                $_goods_data[$v['id']] = $v;
            }
            foreach($list as $k => $v){
                $v['goods'] = isset($_goods_data[$v['goods_id']]) ? $_goods_data[$v['goods_id']] : array();
                $list[$k] = $v;
            }
        }
        $this->meta_title = $year. '.'.$month. ' 月末盘点详细';
        $this->assign('list', $list);
        $this->display(T('Addons://Goods@Admin/GoodsInventory/log'));
    }
    
    /**
     * 导出盘点记录
     */
    public function download_log(){
        $year = I('year', date('Y'));
        $month = I('month', date('m'));
        if($year > date('Y') || ($year == date('Y') && $month > date('m'))){
            exit;
        }
        
        $where = array('store_id' => $this->_store_id, 'year' => $year, 'month' => $month);
        $list = D('Addons://Goods/GoodsInventory')->where($where)->select();
        if($list){
            foreach($list as $v){
                $goods_ids[] = $v['goods_id'];
            }
            $goods_data = M('Goods')->where(array('id' =>  array('in', $goods_ids)))->field('id,title,cate_id')->select();
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
        
        $meta_title = "{$year}.{$month}月末盘点记录";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename='.$meta_title.'.xls');
        header('Pragma: no-cache');
        header('Expires: 0');
        $l = '<style>'
                . 'body{font-size:16px;}'
                . 'img{max-height:150px; max-width:200px;}'
                . 'tr{height:50px;}'
                . '.tr0{background:#eee;}'
                . 'td{border:1px solid #ccc; text-align:center;}'
                . '</style>';
        $l .= '<table>';
        
        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="4">'.$meta_title.' </td></tr>';
        $l .= '<tr><td width="150">商品ID</td><td width="150">商品分类</td><td width="300">商品名</td><td width="100">盘点库存</td></tr>';
        
        foreach ($list as $key => $val) {
            $l .= '<tr class="tr'.($key%2).'">';
            $value = array(
                $val['goods_id'], $_goods_data[$val['goods_id']]['cate_title'], $_goods_data[$val['goods_id']]['title'], $val['num']
            );
            foreach($value as $k => $v){
                $l .= '<td class="td'.$k.'">'.$v.'</td>';
            }
            $l .= '</tr>';
        }
        $l .= '</table>';
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }
    
    public function del(){
        $year = I('year', date('Y'));
        $month = I('month', date('m'));
        if($year > date('Y') || ($year == date('Y') && $month > date('m'))){
            $this->error('记录不存在');
        }
        $where = array('store_id' => $this->_store_id, 'year' => $year, 'month' => $month);
        D('Addons://Goods/GoodsInventoryLs')->where($where)->delete();
        D('Addons://Goods/GoodsInventoryData')->where($where)->delete();
        D('Addons://Goods/GoodsInventory')->where($where)->delete();
        $this->success('删除成功');
    }
}
