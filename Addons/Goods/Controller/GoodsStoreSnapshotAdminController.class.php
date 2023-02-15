<?php

namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsStoreSnapshotAdminController extends AddonsController
{

    public function __construct()
    {
        parent::__construct();
        $this->check_store();
    }

    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $year = date("Y");
        $list = $this->lists(D('Addons://Goods/GoodsStoreSnapshotIndex'), $where, 'create_time desc');
        foreach ($list as $key => $val) {
            $year = $val["year"];
            $month = $val["month"];
            $store_id = $val["store_id"];
            $sql = "select SUM(ifnull(num,0)*ifnull(g_price,0)) as g_amounts 
                                from hii_goods_store_snapshot_{$year} 
                                where `year`={$year} and `month`={$month} and store_id={$store_id} ";
            $datas = M()->query($sql);
            $list[$key]["g_amounts"] = $datas[0]["g_amounts"];
            //echo $sql;echo "<br/>";
        }
        //exit;
        $this->assign('list', $list);
        $this->meta_title = '门店库存快照列表';
        $this->display(T('Addons://Goods@Admin/GoodsStoreSnapshot/index'));
    }

    public function ls()
    {
        $id = I('id', 0, 'intval');
        $index = D('Addons://Goods/GoodsStoreSnapshotIndex')->where(array('id' => $id, 'store_id' => $this->_store_id))->find();
        if (!$index) {
            redirect(addons_url('Goods://GoodsStoreSnapshotAdmin:/index'));
            exit;
        }
        $year = $index['year'];
        $month = $index['month'];
        $status = I('status', 0, 'intval');
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $param = array();
        $param['store_id'] = $this->_store_id;
        $param['year'] = $year;
        $param['month'] = $month;
        $where = $param;
        $status && $where['status'] = $status;
        $Model = D('Addons://Goods/GoodsStoreSnapshot_' . $year);
        $list = $this->lists($Model, $where, 'goods_id asc');
        if ($list) {
            $goods_ids = array();
            foreach ($list as $v) {
                $goods_ids[] = $v['goods_id'];
            }
            $goods = M('Goods')->where(array('id' => array('in', $goods_ids)))->field('id,title,cover_id')->select();
            foreach ($goods as $v) {
                $_goods[$v['id']] = $v;
            }
            foreach ($list as $k => $v) {
                $v['goods'] = isset($_goods[$v['goods_id']]) ? $_goods[$v['goods_id']] : array();
                $list[$k] = $v;
                $list[$k]["g_price"] = is_null($list[$k]["g_price"]) || empty($list[$k]["g_price"]) ? 0.00 : $list[$k]["g_price"];
                $list[$k]["g_amounts"] = $list[$k]["g_price"] * $list[$k]["num"];
            }
        }
        $this->assign('list', $list);
        $this->meta_title = '门店库存快照 【' . $year . '.' . $month . '】';
        $total_data = array();
        $total_where = $param;
        $total_data[0] = $Model->where($total_where)->count();
        $total_where['status'] = 1;
        $total_data[1] = $Model->where($total_where)->count();
        $total_where['status'] = 2;
        $total_data[2] = $Model->where($total_where)->count();
        $total_where['status'] = -1;
        $total_data[3] = $Model->where($total_where)->count();
        $this->assign('total_data', $total_data);
        $this->display(T('Addons://Goods@Admin/GoodsStoreSnapshot/ls'));
    }

    /**
     * 导出盘点记录
     */
    public function download_log()
    {
        $id = I('id', 0, 'intval');
        $index = D('Addons://Goods/GoodsStoreSnapshotIndex')->where(array('id' => $id, 'store_id' => $this->_store_id))->find();
        if (!$index) {
            $this->error('快照不存在');
        }
        $year = $index['year'];
        $month = $index['month'];
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['year'] = $year;
        $where['month'] = $month;
        $list = D('Addons://Goods/GoodsStoreSnapshot_' . $year)->where($where)->order('goods_id asc')->select();
        if ($list) {
            foreach ($list as $v) {
                $goods_ids[] = $v['goods_id'];
            }
            $goods_data = M('Goods')->where(array('id' => array('in', $goods_ids)))->field('id,title,cate_id')->select();
            $cate_id = array();
            $_goods_data = array();
            foreach ($goods_data as $v) {
                $_goods_data[$v['id']] = $v;
                $cate_id[] = $v['cate_id'];
            }
            if ($cate_id) {
                $where = array();
                $where['id'] = array('in', implode(',', $cate_id));
                $cate = M('GoodsCate')->select();
                foreach ($cate as $c) {
                    $cate_title[$c['id']] = $c['title'];
                }
            }
            foreach ($_goods_data as $k => $v) {
                $v['cate_title'] = $cate_title[$v['cate_id']];
                $_goods_data[$k] = $v;
            }
            foreach ($list as $k => $v) {
                $list[$k]["g_price"] = is_null($list[$k]["g_price"]) || empty($list[$k]["g_price"]) ? 0.01 : $list[$k]["g_price"];
                $list[$k]["g_amounts"] = $list[$k]["g_price"] * $list[$k]["num"];
            }
        }

        $meta_title = "{$year}.{$month}库存快照";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $meta_title . '.xls');
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

        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="8">' . $meta_title . ' </td></tr>';
        $l .= '<tr><td width="150">商品ID</td><td width="150">商品分类</td><td width="300">商品名</td><td width="100">售价</td><td width="100">成本价</td><td width="100">库存</td><td width="130">库存成本金额</td><td width="100">状态</td></tr>';

        foreach ($list as $key => $val) {
            $l .= '<tr class="tr' . ($key % 2) . '">';
            switch ($val['status']) {
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
                $val['goods_id'], $_goods_data[$val['goods_id']]['cate_title'], $_goods_data[$val['goods_id']]['title'], $val['price'], $val["g_price"], $val['num'], $val["g_amounts"], $status_text
            );
            foreach ($value as $k => $v) {
                $l .= '<td class="td' . $k . ' style="' . $style . '">' . $v . '</td>';
            }
            $l .= '</tr>';
        }
        $l .= '</table>';
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }
}
