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
        if($month == 12){
            $year1 = $year+1;
            $month1 = 1;
        }else{
            $year1 = $year;
            $month1 = $month+1;
        }
        $sql = "
        Select A.*,B.`price` as kzprice,C.title as goods_name from hii_goods_store_swift_".$year." A
        left join (
            select id,goods_id,cate_id,num,price,$year as `year`,
            $month as `month`,store_id,status,create_time from hii_goods_store_snapshot_" . $year1 . "
            where `year` = ".$year1." and `month`=".$month1." and store_id=".$this->_store_id."
         )B ON A.store_id=B.store_id and A.goods_id=B.goods_id and A.`month`=B.`month` and A.`year`=B.`year`
        left join hii_goods C on A.goods_id=C.id
        where A.`year` = ".$year." and A.`month`=".$month." and A.store_id=".$this->_store_id."
        order by A.goods_id asc
        ";
        //$list = $this->lists(M('GoodsStoreSwift_'.$year), $where, 'goods_id asc', array());
        $list = M('hii_goods_store_swift_'.$year)->query($sql);
        //分页
        $pcount=10;
        $count=count($list);//得到数组元素个数
        $REQUEST    =   (array)I('request.');
        $Page= new \Think\Page($count,$pcount,$REQUEST);// 实例化分页类 传入总记录数和每页显示的记录数
        if($count>$pcount){
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $datamain = array_slice($list,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        /*if($list){
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
        }*/
        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
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

        //$list = D('Addons://Goods/GoodsStoreSwift_'.$year)->where($where)->order( 'goods_id asc')->select();
        if($month == 12){
            $year1 = $year+1;
            $month1 = 1;
        }else{
            $year1 = $year;
            $month1 = $month+1;
        }
        $sql = "
        Select A.*,B.`price` as kzprice,C.title as goods_name,D.title as cate_name from hii_goods_store_swift_".$year." A
        left join (
            select id,goods_id,cate_id,num,price,$year as `year`,
            $month as `month`,store_id,status,create_time from hii_goods_store_snapshot_" . $year1 . "
            where `year` = ".$year1." and `month`=".$month1." and store_id=".$this->_store_id."
         )B ON A.store_id=B.store_id and A.goods_id=B.goods_id and A.`month`=B.`month` and A.`year`=B.`year`
        left join hii_goods C on A.goods_id=C.id
        left join hii_goods_cate D on C.cate_id=D.id
        where A.`year` = ".$year." and A.`month`=".$month." and A.store_id=".$this->_store_id."
        order by A.goods_id asc
        ";
        $list = M('hii_goods_store_swift_'.$year)->query($sql);
        /*if($list){
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
        }*/

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

        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="16">'.$meta_title.' </td></tr>';
        $l .= '<tr><td width="150">商品ID</td><td width="150">商品分类</td><td width="300">商品名</td><td width="100">上期库存</td><td width="100">本月库存</td><td width="100">本月入库</td><td width="100">本月出库</td><td width="100">销售量</td><td width="100">销售价格</td><td width="100">销售金额</td><td width="100">应结数量</td><td width="100">应结货款</td><td width="100">丢耗数量</td><td width="100">丢耗金额</td><td width="100">丢耗率</td><td width="100">状态</td></tr>';

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
                $val['goods_id'],$val['cate_name'],  $val['goods_name'], $val['prev_month_num'], $val['now_month_num'], $val['in_num'], $val['out_num'], $val['sell_num'], $val['kzprice'],(float)$val['sell_num']* $val['kzprice'], $val['result_num'], $val['result_money'], $val['lost_num'],round((float)$val['kzprice']*(float)$val['lost_num'],2),round((float)$val['kzprice']*(float)$val['lost_num']/(float)$val['result_money']*100,2) .'%', $status_text
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
