<?php
namespace Addons\Tongji\Controller;

use Admin\Controller\AddonsController;

class TongjiAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }
    
    public function day_lists(){
        $date = I('date', date('Y-m-d'));
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['date'] = $date;
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(M('GoodsSellLog'.$this->_store_id), $where,'num desc',array('status' => array('egt', 0)),true,50);
        foreach($list as $v){
            $goods_id[] = $v['goods_id'];
        }
        if($goods_id){
            $goods = M('Goods')->where(array('id' => array('in', $goods_id)))->field('id, title')->select();
            if($goods){
                $goods_title = array();
                foreach($goods as $g){
                    $goods_title[$g['id']] = $g['title'];
                }
                foreach($list as &$v){
                    $v['goods_title'] = isset($goods_title[$v['goods_id']]) ? $goods_title[$v['goods_id']] : '<span style="color:red;">商品不存在</span>';
                }
            }
        }
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】 '.($date == date('Y-m-d') ? '今' : ('【'.$date.'】')). '日销售情况';
        $this->display(T('Addons://Tongji@Admin/Tongji/day_lists'));
    }
    
    public function days_lists(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $s_date = I('s_date', date('Y-m-d'));
        $e_date = I('e_date', date('Y-m-d'));
        if(!$s_date || !strtotime($s_date)){
            $this->error('请选择开始日期');
        }
        if(!$e_date || !strtotime($e_date)){
            $this->error('请选择结束日期');
        }
        if(strtotime($s_date) > strtotime($e_date)){
            $this->error('结束日期不得小于开始日期');
        }
        $where = array(
            'date' => array('between', array($s_date, $e_date)),
            'store_id' => $this->_store_id,
        );
        $data = M('GoodsSellLog'.$this->_store_id)->where($where)->select();
        $_data = array();
        if($data){
            foreach($data as $v){
                $_data[$v['date']]['num'] = isset($_data[$v['date']]['num']) ? ($_data[$v['date']]['num']+$v['num']) : $v['num'];
                $_data[$v['date']]['money'] = isset($_data[$v['date']]['money']) ? ($_data[$v['date']]['money']+$v['money']) : $v['money'];
            }
        }
        //获取该门店的 房间数量以及入住率
        $getStoreInfo = M('Store')->where(array('id' => $this->_store_id))->find();
		//获取该门店运营管理员
		$member = M('MemberStore')->alias('ms')
		->join('hii_member AS m on ms.uid = m.uid' , 'LEFT')
		->where(array('ms.store_id' => $this->_store_id,'ms.group_id' => 2,'ms.type' => 1))
		->select();
        $this->assign('list', $_data);
        $this->meta_title = '【'.session('user_store.title').'】 销售统计';
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->assign('storeInfo',$getStoreInfo);
		$this->assign('member_gs',$member);
        $this->display(T('Addons://Tongji@Admin/Tongji/days_lists'));
    }
    
    public function day_detail(){
        $goods_id = I('goods_id', 0, 'intval');
        $date = I('date', date('Y-m-d'), 'trim');
        $goods = M('Goods')->find($goods_id);
        if(!$goods){
            $this->error('商品信息不存在');
        }
        $where = array();
        $where['goods_id'] = $goods_id;
        $where['date'] = $date;
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(M('GoodsSellLog'.$this->_store_id), $where);
        foreach($list as $v){
            $store_id[] = $v['store_id'];
        }
        if($store_id){
            $store = M('Store')->where(array('id' => array('in', $store_id)))->field('id, title')->select();
            if($store){
                foreach($store as $s){
                    $store_title[$s['id']] = $s['title'];
                }
                foreach($list as &$v){
                    $v['store_title'] = isset($store_title[$v['store_id']]) ? $store_title[$v['store_id']] : '<span style="color:red;">门店不存在</span>';
                }
            }
        }
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品 ['. $goods['title']. '] '.$date.' 日销售详细';
        $this->display(T('Addons://Tongji@Admin/Tongji/day_detail'));
    }
    
    public function store_lists(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(M('GoodsStore'), $where, 'sell_num desc',array('status' => array('egt', 0)),true,50 );
        foreach($list as $v){
            $goods_id[] = $v['goods_id'];
        }
        if($goods_id){
            $goods = M('Goods')->where(array('id' => array('in', $goods_id)))->field('id, title')->select();
            if($goods){
                $goods_title = array();
                foreach($goods as $g){
                    $goods_title[$g['id']] = $g['title'];
                }
                foreach($list as &$v){
                    $v['goods_title'] = isset($goods_title[$v['goods_id']]) ? $goods_title[$v['goods_id']] : '<span style="color:red;">商品不存在</span>';
                }
            }
        }
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】'.' 总销售排行';
        $this->display(T('Addons://Tongji@Admin/Tongji/store_lists'));
    }
    
    
    public function store_detail(){
        $goods_id = I('goods_id', 0, 'intval');
        $goods = M('Goods')->find($goods_id);
        if(!$goods){
            $this->error('商品信息不存在');
        }
        $where = array();
        $where['goods_id'] = $goods_id;
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(M('GoodsSellLog'.$this->_store_id), $where, 'date desc');
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品 ['. $goods['title']. '] '.' 总销售历史详细';
        $this->display(T('Addons://Tongji@Admin/Tongji/store_detail'));
    }
    
    public function day_download(){
            $s_date = I('s_date', date('Y-m-d'));
            $e_date = I('e_date', date('Y-m-d'));
            if(!$s_date || !strtotime($s_date)){
                $this->error('请选择开始日期');
            }
            if(!$e_date || !strtotime($e_date)){
                $this->error('请选择结束日期');
            }
            if(strtotime($s_date) > strtotime($e_date)){
                $this->error('结束日期不得小于开始日期');
            }
            $where = array(
                'date' => array('between', array($s_date, $e_date)),
                'store_id' => $this->_store_id,
            );
            $title = session('user_store.title'). $s_date.'至'.$e_date.'的销售记录报表';
            $data = M('GoodsSellLog'.$this->_store_id)->where($where)->select();
            if(!$data){
                $this->error('当前选择的日期区间无销售记录');
            }
            $_data = array();
            foreach($data as $v){
                $_data[$v['goods_id']]['num'] = isset($_data[$v['goods_id']]['num']) ? ($_data[$v['goods_id']]['num']+$v['num']) : $v['num'];
                $_data[$v['goods_id']]['money'] = isset($_data[$v['goods_id']]['money']) ? ($_data[$v['goods_id']]['money']+$v['money']) : $v['money'];
            }
            $log_data = $_data;
            $goods = M('Goods')->field('id,title,cate_id')->select();
            !$goods && $goods = array();
            $cate = M('GoodsCate')->select();
            !$cate && $cate = array();
            $cate_title = array();
            foreach($cate as $v){
                $cate_title[$v['id']] = $v['title'];
            }
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename='.$title.'.xls');
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
            $l .= '    <tr style="height:60px;"><td style="text-align:left;font-size:18px; " colspan="4"><strong>门店：</strong>'.session('user_store.title').' <strong>销售记录日期：</strong>'.$s_date.'至'.$e_date.'</td></tr>';
            $l .= '    <tr style="font-weight:bold;"><td width="200">商品ID</td><td width="200">商品分类</td><td width="400">商品名</td><td width="150">销售总数量</td><td width="150">销售总金额</td></tr>';
                
            $data = array();
            foreach($goods as $k => $v){
                $data[] = array(
                    $v['id'], $cate_title[$v['cate_id']],$v['title'], isset($log_data[$v['id']]['num'])?$log_data[$v['id']]['num']:0,  isset($log_data[$v['id']]['money']) ? $log_data[$v['id']]['money'] : 0
                );
            }
            foreach ($data as $key => $value) {
                $l .= '<tr class="tr'.($key%2).'">';
                foreach($value as $k => $v){
                    $l .= '<td class="td'.$k.'">'.$v.'</td>';
                }
                $l .= '</tr>';
            }
            $l .= '</table>';
            echo iconv('utf-8', 'gbk', $l);
            exit;
    }

    //简单更新门店信息
    public function updateStore(){
        $room_amount = I('room_amount', 0, 'intval');
        $occupancy_rate = I('occupancy_rate', 0, '');
        if($occupancy_rate > 100){
            $data_json = array('code' => -200 , 'content' => '入住率不可大于100');
            echo json_encode($data_json);
            exit;
        }
        if($occupancy_rate < 0){
            $data_json = array('code' => -200 , 'content' => '入住率不可小于0');
            echo json_encode($data_json);
            exit;
        }
        $Store = D('Addons://Store/Store');
        $data = array(
            'room_amount' => $room_amount,
            'occupancy_rate' => $occupancy_rate,
        );
        $res = $Store->where(array('id' => $this->_store_id))->save($data);
        $data_json = array('code' => 200 , 'content' => '更新成功!');
        echo json_encode($data_json);
    }
}
