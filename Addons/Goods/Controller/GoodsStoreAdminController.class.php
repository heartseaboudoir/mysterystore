<?php
namespace Addons\Goods\Controller;

use Admin\Controller\AddonsController;

class GoodsStoreAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->check_store();
        $this->_line_type = session('user_store.sell_type');
    }
    /**
     * 检测门店是否拥有商品的销售途径
     * @param type $goods_id     商品ID
     * @param type $check_type   途径类型（1 线上 2 线下 ，0则自己获取）
     * @return boolean
     */
    public function check_line($goods_id, $check_type = null){
        $g_line_type = array();
        if(is_null($check_type)){
            $goods_data = M('Goods')->where(array('id' => $goods_id))->find();
            if($goods_data['sell_online'] == 1){
                $g_line_type[] = 2; 
            }
            if($goods_data['sell_outline'] == 1){
                $g_line_type[] = 1;
            }
        }else{
            $g_line_type[] = $check_type;
        }
        if(in_array($this->_line_type, $g_line_type)){
            return true;
        }else{
            $this->error('门店未拥有商品的销售途径');
        }
    }
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $title = I('title', '', 'trim');
        $cate_id = I('cate_id', 0, 'intval');
        $by_store = I('by_store', 0, 'intval');
        $where = array();
        if($title){
            $bar = D('GoodsBarCode')->where(array('bar_code' => $title))->find();
            if($bar){
                $where['g.id'] = $bar['goods_id'];
            }else{
                $where['g.title'] = array('like', "%{$title}%");
            }
        }
        $cate_id && $where['g.cate_id'] = $cate_id;
        $where['g.status'] = 1;
        switch($this->_line_type){
            case 1:
                $where['g.sell_outline'] = 1;
                break;
            case 2:
                $where['g.sell_online'] = 1;
                break;
            default:
                $this->error('门店未设置销售途径，请联系管理员');
        }
        $Model = D('Addons://Goods/Goods')->alias('g');
        $field = '*';
        if($by_store){
            $where['s.store_id'] = $this->_store_id;
            $Model->join('__GOODS_STORE__ as s ON s.goods_id = g.id');
            $field = 'g.id,g.title,g.sell_price,g.cover_id,g.cate_id,s.shequ_price,s.price,s.num,s.month_num,s.update_time';
        }
        
        $list = $this->lists($Model, $where, 'listorder desc, g.create_time desc', array(), $field);
        $goods_id = array();
        $cate_ids = array();
        foreach($list as $k => $v){
            !in_array($v['cate_id'], $cate_ids) && $cate_ids[] = $v['cate_id'];
            $goods_id[] = $v['id'];
        }
        $cate_title = array();
        if($cate_ids){
            $where = array();
            $where['id'] = array('in', implode(',', $cate_ids));
            $cate = D('Addons://Goods/GoodsCate')->select();
            foreach($cate as $c){
                $cate_title[$c['id']] = $c['title'];
            }
        }
        if($by_store){
            foreach($list as $k => $v){
                $v['price'] = isset($v['price']) ? $v['price'] : '未设置';
                $v['shequ_price'] = isset($v['shequ_price']) ? $v['shequ_price'] : '未设置';
                $v['num'] = isset($v['num']) ? $v['num'] : '未设置';
                $v['sell_num'] = isset($v['sell_num']) ? $v['sell_num'] : 0;
                $v['month_num'] = isset($v['month_num']) ? $v['month_num'] : 0;
                $list[$k] = $v;
            }
        }else{
            if($goods_id){
                $store_data = M('GoodsStore')->where(array('store_id' => $this->_store_id, 'goods_id' => array('in', $goods_id)))->select();
                if($store_data){
                    foreach($store_data as $v){
                        $_store_data[$v['goods_id']] = $v;
                    }
                    foreach($list as $k => $v){
                        $v['price'] = isset($_store_data[$v['id']]['price']) ? $_store_data[$v['id']]['price'] : '未设置';
                        $v['num'] = isset($_store_data[$v['id']]['num']) ? $_store_data[$v['id']]['num'] : '未设置';
                        $v['shequ_price'] = isset($_store_data[$v['id']]['shequ_price']) ? $_store_data[$v['id']]['shequ_price'] : '未设置';
                        $v['sell_num'] = isset($_store_data[$v['id']]['sell_num']) ? $_store_data[$v['id']]['sell_num'] : 0;
                        $v['month_num'] = isset($_store_data[$v['id']]['month_num']) ? $_store_data[$v['id']]['month_num'] : 0;
                        $v['store_data'] = isset($_store_data[$v['id']]) ? $_store_data[$v['id']] : array();
                        $list[$k] = $v;
                    }
                }
            }
        }
        $this->assign('cate_title', $cate_title);
        $this->assign('list', $list);

        $cate_ls = M('GoodsCate')->select();
        !$cate_ls && $cate_ls = array();
        $_cate_ls = array();
        foreach($cate_ls as $c){
            $_cate_ls[$c['id']] = $c['title'];
        }
        $this->assign('cate_ls', $_cate_ls);
        
        $dealer_ls = M('Dealer')->where(array('store_id' => $this->_store_id))->select();
        !$dealer_ls && $dealer_ls = array();
        $_dealer_ls = array();
        foreach($dealer_ls as $c){
            $_dealer_ls[$c['id']] = $c['title'];
        }
 
        $this->assign('dealer_ls', $_dealer_ls);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品设置';
        $this->display(T('Addons://Goods@Admin/GoodsStore/index'));
    }
    public function show(){
        $id = I('get.id','');
        $Model = D('Addons://Goods/Goods');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        if(!$data){
            $this->error('商品不存在');
        }
        $cate = M('GoodsCate')->where(array('id' => $data['cate_id']))->field('title')->find();
        $data['cate_title'] = $cate['title'];
        $dealer = M('Dealer')->where(array('id' => $data['dealer_id']))->field('title')->find();
        $data['dealer_title'] = $dealer['title'];
        $this->assign('data', $data);
        $store = M('GoodsStore')->where(array('goods_id' => $id, 'store_id' => $this->_store_id))->find();
        if($store['dealer_id']){
            $store['dealer'] = M('Dealer')->where(array('id' => $store['dealer_id']))->find();
        }
        $this->assign('store', $store);
        $store_log = M('GoodsStoreLog')->where(array('goods_id' => $id, 'store_id' => $this->_store_id, 'status' => 1, 'uid' => UID))->find();
        $this->assign('store_log', $store_log);
        $this->meta_title = '商品详情';
        $this->display(T('Addons://Goods@Admin/GoodsStore/show'));
    }

    public function save() {
        $id = I('get.id','');
        $Model = D('Addons://Goods/Goods');
        $where = array();
        $where['id'] = $id;
        $data = $Model->where($where)->find();
        if(!$data){
            $this->error('商品不存在');
        }
        $cate = M('GoodsCate')->where(array('id' => $data['cate_id']))->field('title')->find();
        $data['cate_title'] = $cate['title'];
        $dealer = M('Dealer')->where(array('id' => $data['dealer_id']))->field('title')->find();
        $data['dealer_title'] = $dealer['title'];
        $this->assign('data', $data);
        $store = M('GoodsStore')->where(array('goods_id' => $id, 'store_id' => $this->_store_id))->find();
        $this->assign('store', $store);
        $store_log = M('GoodsStoreLog')->where(array('goods_id' => $id, 'store_id' => $this->_store_id, 'status' => 1, 'uid' => UID))->find();
        $this->assign('store_log', $store_log);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品设置';
        
        $dealer_list = M('Dealer')->where(array('store_id' => $this->_store_id))->select();
        !$dealer_list && $dealer_list = array();
        $this->assign('dealer_list', $dealer_list);
        
        $this->display(T('Addons://Goods@Admin/GoodsStore/save'));
    }
    
    public function get_goods(){
        $cate_id = I('cate_id', 0, 'intval');
        $data = array();
        if($cate_id > 0){ 
            $data = M('Goods')->where(array('cate_id' => $cate_id,'status' => 1))->field('id,title')->select();
            !$data && $data = array();
        }
        $this->ajaxReturn($data);
    }
    
    public function update(){
        if(empty($_POST['goods_id'])){
            $this->error('请选择商品');
        }
        $info = M('Goods')->where(array('id' => $_POST['goods_id'], 'status' => 1))->find();
        if(!$info){
            $this->error('商品不存在');
        }
        $Model = D('Addons://Goods/GoodsStore');
        $_POST['store_id'] = $this->_store_id;
        if(isset($_POST['price'])){
            // 价格修改时做审核
            $info = $Model->where(array('store_id' => $this->_store_id, 'goods_id' => $_POST['goods_id']))->find();
            if($info['price'] != $_POST['price']){
                $_idata = array(
                    array(
                        'id' => $_POST['goods_id'],
                        'price' => $_POST['price']
                    ),
                );
                $idata = array(
                    'data' => json_encode($_idata),
                    'type' => 5,
                    'status' => 1,
                    'store_id' => $this->_store_id
                );
                $ApplyModel = D('Addons://Goods/GoodsStoreApply');
                $idata = $ApplyModel->create($idata);
                if(!$idata){
                    $this->error('操作失败');
                }
                $ApplyModel->add($idata);
            }
            unset($_POST['price']);
        }
        $res = $Model->update();
        if(!$res){
            $this->error($Model->getError());
        }else{
            $Model->push_num($_POST['goods_id'], $this->_store_id);
            $this->success('更新成功', Cookie('__forward__'));
        }
    }
    public function apply_index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['type'] = array('in', array(1,2,3,4));
        $list = $this->lists(M('GoodsStoreApply'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】'.' 出入库申请管理';
        $this->display(T('Addons://Goods@Admin/GoodsStore/apply_index'));
    }
    public function apply_price_index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['type'] = 5;
        $list = $this->lists(M('GoodsStoreApply'), $where, 'create_time desc');
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品价格修改申请管理';
        $this->display(T('Addons://Goods@Admin/GoodsStore/apply_price_index'));
    }
    public function add_apply(){
        if(IS_POST){
            $_POST['store_id'] = $this->_store_id;
            $goods = I('goods');
            $GModel = M('Goods');
            $data = array();
            $goods_ids = array();
            foreach($goods as $v){
                //if(empty($v['bar_code']) || empty($v['id']) || empty($v['num']) || $v['num'] < 1) continue;
                if(empty($v['id']) || empty($v['num']) || $v['num'] < 1) continue;
                $info = $GModel->where(array('status' => 1, 'id' => $v['id']))->field('id,title,cate_id')->find();
                if(!$info){
                    if (!empty($v['bar_code'])) {
                        $this->error('条形码为'.$v['bar_code'].'的商品不存在');
                    } else {
                        $this->error('ID为'.$v['id'].'的商品不存在');
                    }
                }
                
//                if(empty($v['total_cost'])){
//                    $this->error('条形码为'.$v['bar_code'].'的商品未录入入库总成本');
//                }
//                preg_match('/^\d{1,}\.?\d{0,2}$/', $v['total_cost'], $match);
//                if(!$match){
//                    $this->error('条形码为'.$v['bar_code'].'的商品入库总成本录入格式错误，应为数字且最多保留两位小数');
//                }
                $data[] = $v;
                $g_info[$v['id']] = $info;
                $goods_data[$v['id']] = array(
                    'num' => isset($goods_data[$v['id']]['num']) ? ($goods_data[$v['id']]['num']+$v['num']) : $v['num'],
                    //'total_cost' => isset($goods_data[$v['id']]['total_cost']) ? ($goods_data[$v['id']]['total_cost']+$v['total_cost']) : $v['total_cost'],
                );
                $goods_ids[] = $v['id'];
            }
            if(empty($data)){
                $this->error('未填写入库信息');
            }
            $_POST['data'] = json_encode($data);
            $_POST['type'] = 1;
            $Model = D('Addons://Goods/GoodsStoreApply');
            $idata = $Model->create();
            if(!$idata){
                $this->error('操作失败');
            }
            $idata['status'] = 2;
            if($Model->add($idata)){
                $GSModel = D('Addons://Goods/GoodsStore');
                $GSLModel =  D('Addons://Goods/GoodsStoreLog');
                $push = array();
                $store_goods_data = $GSModel->where(array('goods_ids' => array('in', $goods_ids), 'store_id' => $this->_store_id))->field('goods_id, num')->select();
                //$store_goods_data = $GSModel->where(array('goods_ids' => array('in', $goods_ids), 'store_id' => $this->_store_id))->field('goods_id, num, sy_cost')->select();
                if($store_goods_data){
                    foreach($store_goods_data as $v){
                        $store_goods[$v['goods_id']] = $v; 
                    }
                }
                foreach($goods_data as $k => $v){
                    if(isset($store_goods[$k])){
                        // (单位成本(前)*库存数(前)+本次入库总成本)/(库存数(前)+本次入库数)=单位成本(后)
                        //$sy_cost = $GSModel->get_sy_cost($store_goods[$k]['sy_cost'], $store_goods[$k]['num'], $v['total_cost'], $v['num']);
                        $item = array(
                            'num' => array('exp', 'num+'.$v['num']),
                            //'sy_cost' => $sy_cost,
                            'update_time' => NOW_TIME
                        );
                        $GSModel->where(array('goods_id' => $k, 'store_id' => $this->_store_id))->save($item);
                    }else{
                        //$sy_cost = $v['total_cost']/$v['num'];
                        $item = array(
                            'goods_id' => $k,
                            'store_id' => $this->_store_id,
                            'num' => $v['num'],
                            //'sy_cost' => $sy_cost,
                            'update_time' => NOW_TIME
                        );
                        $GSModel->add($item);
                    }
                    $GSLModel->add(array('cate_id' => $g_info[$k]['cate_id'], 'goods_id' => $k, 'store_id' => $this->_store_id, 'num' => $v['num'], 'type' => 1,'uid' => UID, 'check_uid' => UID, 'create_time' => NOW_TIME));   
                
                    $push[] = $k;
                }
                // 推送更新
                $push && D('Addons://Goods/GoodsStore')->push_num($push, $this->_store_id);
                $this->success('操作成功', Cookie('__forward__'));
            }else{
                $this->error('操作失败');
            }
            exit;
        }
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品入库';
        $this->display(T('Addons://Goods@Admin/GoodsStore/add_apply'));
    }
    
    
    public function out_apply(){
        if(IS_POST){
            $_POST['store_id'] = $this->_store_id;
            $goods = I('goods');
            $data = array();
            $GModel = M('Goods');
            foreach($goods as $v){
                if(empty($v['id']) || empty($v['num']) || $v['num'] < 1) continue;
                $info = $GModel->where(array('status' => 1, 'id' => $v['id']))->field('id,title,cate_id')->find();
                if(!$info){
                    if (!empty($v['bar_code'])) {
                        $this->error('条形码为'.$v['bar_code'].'的商品不存在');
                    } else {
                        $this->error('ID为'.$v['id'].'的商品不存在');
                    }                    
                }
                $data[] = $v;
                $g_info[$v['id']] = $info;
                $goods_num[$v['id']] = isset($goods_num[$v['id']]) ? ($goods_num[$v['id']]+$v['num']) : $v['num'];
            }
            if(empty($data)){
                $this->error('未填写出库信息');
            }
            $_POST['data'] = json_encode($data);
            $_POST['type'] = 2;
            $Model = D('Addons://Goods/GoodsStoreApply');
            $idata = $Model->create();
            if(!$idata){
                $this->error('操作失败');
            }
            $idata['status'] = 2;
            if($Model->add($idata)){
                $GSModel =  D('Addons://Goods/GoodsStore');
                $GSLModel =  D('Addons://Goods/GoodsStoreLog');
                $push = array();
                foreach($goods_num as $k => $v){
                   if($GSModel->where(array('goods_id' => $k, 'store_id' => $this->_store_id, 'num' => array('EGT', $v)))->setDec('num', $v)){
                       $GSLModel->add(array('cate_id' => $g_info[$k]['cate_id'], 'goods_id' => $k, 'store_id' => $this->_store_id, 'num' => $v, 'type' => 2,'uid' => UID, 'check_uid' => UID, 'create_time' => NOW_TIME));
                        $push[] = $k;
                   }
                }
                // 推送更新
                $push && D('Addons://Goods/GoodsStore')->push_num($push, $this->_store_id);
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
            exit;
        }
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品出库';
        $this->display(T('Addons://Goods@Admin/GoodsStore/out_apply'));
    }
    /**
     * 商品找回
     */
    public function add_find(){
        if(IS_POST){
            $_POST['store_id'] = $this->_store_id;
            $goods = I('goods');
            $GModel = M('Goods');
            $data = array();
            foreach($goods as $v){
                if(empty($v['id']) || empty($v['num']) || $v['num'] < 1) continue;
                $info = $GModel->where(array('status' => 1, 'id' => $v['id']))->field('id,title,cate_id')->find();
                if(!$info){
                    if (!empty($v['bar_code'])) {
                        $this->error('条形码为'.$v['bar_code'].'的商品不存在');
                    } else {
                        $this->error('ID为'.$v['id'].'的商品不存在');
                    }                    
                    
                }
                $data[] = $v;
                $g_info[$v['id']] = $info;
                $goods_num[$v['id']] = isset($goods_num[$v['id']]) ? ($goods_num[$v['id']]+$v['num']) : $v['num'];
                break;
            }
            if(empty($data)){
                $this->error('未填写商品信息');
            }
            $_POST['data'] = json_encode($data);
            $_POST['type'] = 3;
            $Model = D('Addons://Goods/GoodsStoreApply');
            $idata = $Model->create();
            if(!$idata){
                $this->error('操作失败');
            }
            $idata['status'] = 2;
            if($Model->add($idata)){
                $GSModel = D('Addons://Goods/GoodsStore');
                $GSLModel =  D('Addons://Goods/GoodsStoreLog');
                $push = array();
                foreach($goods_num as $k => $v){
                    if($GSModel->where(array('goods_id' => $k, 'store_id' => $this->_store_id))->find()){
                        $GSModel->where(array('goods_id' => $k, 'store_id' => $this->_store_id))->setInc('num', $v);
                    }else{
                            $GSModel->add(array('goods_id' => $k, 'store_id' => $this->_store_id, 'num' => $v, 'update_time' => NOW_TIME));
                    }
                    $GSLModel->add(array('cate_id' => $g_info[$k]['cate_id'], 'goods_id' => $k, 'store_id' => $this->_store_id, 'num' => $v, 'type' => 3,'uid' => UID, 'check_uid' => UID, 'create_time' => NOW_TIME));
                    $push[] = $k;
                }
                // 推送更新
                $push && D('Addons://Goods/GoodsStore')->push_num($push, $this->_store_id);
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
            exit;
        }
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品找回';
        $this->display(T('Addons://Goods@Admin/GoodsStore/add_find'));
    }
    /**
     * 商品丢耗
     */
    public function add_lost(){
        if(IS_POST){
            $_POST['store_id'] = $this->_store_id;
            $goods = I('goods');
            $data = array();
            $GModel = M('Goods');
            foreach($goods as $v){
                if(empty($v['id']) || empty($v['num']) || $v['num'] < 1) continue;
                $info = $GModel->where(array('status' => 1, 'id' => $v['id']))->field('id,title,cate_id')->find();
                if(!$info){
                    if (!empty($v['bar_code'])) {
                        $this->error('条形码为'.$v['bar_code'].'的商品不存在');
                    } else {
                        $this->error('ID为'.$v['id'].'的商品不存在');
                    }
                }
                $data[] = $v;
                $g_info[$v['id']] = $info;
                $goods_num[$v['id']] = isset($goods_num[$v['id']]) ? ($goods_num[$v['id']]+$v['num']) : $v['num'];
                break;
            }
            if(empty($data)){
                $this->error('未填写商品信息');
            }
            $_POST['data'] = json_encode($data);
            $_POST['type'] = 4;
            $Model = D('Addons://Goods/GoodsStoreApply');
            $idata = $Model->create();
            if(!$idata){
                $this->error('操作失败');
            }
            $idata['status'] = 2;
            if($Model->add($idata)){
                $GSModel =  D('Addons://Goods/GoodsStore');
                $GSLModel =  D('Addons://Goods/GoodsStoreLog');
                $push = array();
                foreach($goods_num as $k => $v){
                   if($GSModel->where(array('goods_id' => $k, 'store_id' => $this->_store_id, 'num' => array('EGT', $v)))->setDec('num', $v)){
                        $GSLModel->add(array('cate_id' => $g_info[$k]['cate_id'], 'goods_id' => $k, 'store_id' => $this->_store_id, 'num' => $v, 'type' => 4,'uid' => UID, 'check_uid' => UID, 'create_time' => NOW_TIME));
                        $push[] = $k;
                   }
                }
                // 推送更新
                $push && D('Addons://Goods/GoodsStore')->push_num($push,  $this->_store_id);
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
            exit;
        }
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品丢耗';
        $this->display(T('Addons://Goods@Admin/GoodsStore/add_lost'));
    }
    
    public function apply_show(){
        $sn = I('sn', '', 'trim');
        if(!$sn){
            $this->error('请选择记录');
        }
        $where = array();
        $where['sn'] = $sn;
        $Model = M('GoodsStoreApply');
        $info = $Model->where($where)->find();
        if(!$info){
            $this->error('记录不存在');
        }
        $info['data'] = json_decode($info['data'], true);
        $goods_ids = array();
        foreach($info['data'] as $v){
            $goods_ids[] = $v['id'];
        }
        if($goods_ids){
            $goods = M('Goods')->where(array('goods_id' => array('in', $goods_ids)))->field('id, title')->select();
            $_goods = array();
            foreach($goods as $v){
                $_goods[$v['id']] = $v;
            }
            $this->assign('goods', $_goods);
        }
        $this->assign('info', $info);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品操作申请管理';
        $this->display(T('Addons://Goods@Admin/GoodsStore/apply_show'));
    }
    
    public function log_index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = array();
        $where['store_id'] = $this->_store_id;
        if($start_time){
            $where['create_time'] = array('EGT', strtotime($start_time));
        }elseif($end_time){
            $where['create_time'] = array('ELT', strtotime($end_time)+3600*24);
        }
        if($start_time && $end_time){
            $where['create_time'] = array('BETWEEN', array(strtotime($start_time), strtotime($end_time)+3600*24));
        }
        
        $list = $this->lists(M('GoodsStoreLog'), $where, 'create_time desc');
        foreach($list as $v){
            $goods_id[] = $v['goods_id'];
        }
        if($goods_id){
            $goods = M('Goods')->where(array('id' => array('in', $goods_id)))->select();
            $_goods = array();
            if($goods){
                foreach($goods as $v){
                    $_goods[$v['id']] = $v;
                }
            }
            $this->assign('goods', $_goods);
            $store = M('GoodsStore')->where(array('goods_id' => array('in', $goods_id), 'store_id' => $this->_store_id))->select();
            $_store = array();
            if($store){
                foreach($store as $v){
                    $_store[$v['goods_id']] = $v;
                }
            }
            $this->assign('store_data', $_store);
        }

        $this->assign('list', $list);
        
        $cate_ls = M('GoodsCate')->field('id,title')->select();
        $cate_title = array();
        if($cate_ls){
            foreach($cate_ls as $v){
                $cate_title[$v['id']] = $v['title'];
            }
        }
        $this->assign('cate_title', $cate_title);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品库存记录';
        $this->display(T('Addons://Goods@Admin/GoodsStore/log_index'));
    }
    /**
     * 导出商品出入库记录
     */
    public function download_log(){
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = array();
        $where['store_id'] = $this->_store_id;
        $title = '商品出入库记录';
        if($start_time){
            $title = '商品出入库记录-'.$start_time.'之后';
            $where['create_time'] = array('EGT', strtotime($start_time));
        }elseif($end_time){
            $title = '商品出入库记录-'.$end_time.'之前';
            $where['create_time'] = array('ELT', strtotime($end_time)+3600*24);
        }
        if($start_time && $end_time){
            $where['create_time'] = array('BETWEEN', array(strtotime($start_time), strtotime($end_time)+3600*24));
            $title = '商品出入库记录-'.$start_time.' 至 '.$end_time;
        }
        $data = M('GoodsStoreLog')->where($where)->order('create_time desc')->select();
        if($data){
            
            $gids = array();
            foreach($data as $v){
                $gids[] = $v['goods_id'];
            }
            // 获取商品信息
            $detail = M('Goods')->where(array('id' => array('in', $gids)))->field('id,title')->select();
            foreach($detail as $v){
                $_detail[$v['id']] = $v;
            }
            $detail = $_detail;
            // 获取分类信息
            $cate = M('GoodsCate')->select();
            !$cate && $cate = array();
            $cate_title = array();
            foreach($cate as $v){
                $cate_title[$v['id']] = $v['title'];
            }
        }else{
            $data = array();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$title.'.xls"');
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
        
        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="7">'.$title.' </td></tr>';
        $l .= '<tr><td width="100">商品ID</td><td width="200">商品分类</td><td width="200">商品名</td><td width="100">操作</td><td width="80">数量</td><td width="120">申请人</td></td><td width="130">操作时间</td></tr>';
        
        foreach ($data as $key => $val) {
            $l .= '<tr class="tr'.($key%2).'">';
            switch($val['type']){
                case 1:
                    $_action = '入库';
                    break;
                case 2:
                    $_action = '出库';
                    break;
                case 3:
                    $_action = '找回';
                    break;
                case 4:
                    $_action = '丢耗';
                    break;
                default:
                    $_action = '';
            }
            $value = array(
                $val['goods_id'],$cate_title[$val['cate_id']], $detail[$val['goods_id']]['title'], $_action, $val['num'], get_nickname($val['uid']), time_format($val['create_time'], 'Y-m-d H:i:s')
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
    /**
     * 导出商品库存
     */
    public function download_store_num(){        
        $title = I('title', '', 'trim');
        $cate_id = I('cate_id', 0, 'intval');
        $by_store = I('by_store', 0, 'intval');
        $where = array();
        if($title){
            $bar = D('GoodsBarCode')->where(array('bar_code' => $title))->find();
            if($bar){
                $where['g.id'] = $bar['goods_id'];
            }else{
                $where['g.title'] = array('like', "%{$title}%");
            }
        }
        $cate_id && $where['g.cate_id'] = $cate_id;
        $where['g.status'] = 1;
        $Model = D('Addons://Goods/Goods')->alias('g');
        $field = '*';
        if($by_store){
            $where['s.store_id'] = $this->_store_id;
            $Model->join('__GOODS_STORE__ as s ON s.goods_id = g.id');
            $field = 'g.id,g.title,g.sell_price,g.cover_id,g.cate_id,s.shequ_price,s.price,s.num,s.sell_num,s.month_num,s.update_time';
        }
        $list = $Model->where($where)->order('listorder desc, g.create_time desc')->field($field)->select();
        $cate_id = array();
        foreach($list as $l){
            $cate_id[] = $l['cate_id'];
            $goods_id[] = $l['id'];
        }
        $cate_title = array();
        if($cate_id){
            $where = array();
            $where['id'] = array('in', implode(',', $cate_id));
            $cate = D('Addons://Goods/GoodsCate')->select();
            foreach($cate as $c){
                $cate_title[$c['id']] = $c['title'];
            }
        }
        if($goods_id && !$by_store){
            $store_data = M('GoodsStore')->where(array('store_id' => $this->_store_id))->select();
            if($store_data){
                foreach($store_data as $v){
                    $_store_data[$v['goods_id']] = $v;
                }
                foreach($list as $k => $v){
                    $v['price'] = isset($_store_data[$v['id']]['price']) ? $_store_data[$v['id']]['price'] : '未设置';
                    $v['shequ_price'] = isset($_store_data[$v['id']]['shequ_price']) ? $_store_data[$v['id']]['shequ_price'] : '未设置';
                    $v['num'] = isset($_store_data[$v['id']]['num']) ? $_store_data[$v['id']]['num'] : '未设置';
                    $v['sell_num'] = isset($_store_data[$v['id']]['sell_num']) ? $_store_data[$v['id']]['sell_num'] : 0;
                    $v['month_num'] = isset($_store_data[$v['id']]['month_num']) ? $_store_data[$v['id']]['month_num'] : 0;
                    $list[$k] = $v;
                }
            }
        }else{
            foreach($list as $k => $l){
                !$l['price'] && $l['price'] = '未设置';
                $list[$k] = $l;
            }
        }
        $meta_title = '商品库存';
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
        
        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="6">'.$meta_title.' </td></tr>';
        $l .= '<tr><td width="200">商品ID</td><td width="200">商品分类</td><td width="200">商品名</td><td width="100">商品库存</td><td width="100">门店售价</td><td width="100">系统售价</td><td width="100">区域售价</td></tr>';
        
        foreach ($list as $key => $val) {
            $l .= '<tr class="tr'.($key%2).'">';
            $value = array(
                $val['id'], $cate_title[$val['cate_id']], $val['title'], isset($val['num']) ? $val['num'] : '未设置', $val['price'], $val['sell_price'],$val['shequ_price']            );
            foreach($value as $k => $v){
                $l .= '<td class="td'.$k.'">'.$v.'</td>';
            }
            $l .= '</tr>';
        }
        $l .= '</table>';
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }
    public function change_log_index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['status'] = 1;
        $list = $this->lists(M('GoodsStoreChangeLog'), $where, 'create_time desc');
        foreach($list as $v){
            $goods_id[] = $v['goods_id'];
        }
        if($goods_id){
            $goods = M('Goods')->where(array('id' => array('in', $goods_id)))->select();
            $_goods = array();
            if($goods){
                foreach($goods as $v){
                    $_goods[$v['id']] = $v;
                }
            }
            $this->assign('goods', $_goods);
            $store = M('GoodsStore')->where(array('goods_id' => array('in', $goods_id), 'store_id' => $this->_store_id))->select();
            $_store = array();
            if($store){
                foreach($store as $v){
                    $_store[$v['goods_id']] = $v;
                }
            }
            $this->assign('store_data', $_store);
        }

        $this->assign('list', $list);
        $cate_ls = M('GoodsCate')->field('id,title')->select();
        $cate_title = array();
        if($cate_ls){
            foreach($cate_ls as $v){
                $cate_title[$v['id']] = $v['title'];
            }
        }
        $this->assign('cate_title', $cate_title);
        $this->meta_title = '【'.session('user_store.title').'】'.' 商品更新日志';
        $this->display(T('Addons://Goods@Admin/GoodsStore/change_log_index'));
    }
    public function set_apply(){
        $id = I('id');
        $status = I('status', 'n');
        if($status == 'y'){
            $status = 2;
        }else{
            $status = 3;
        }
        
        $Model = M('GoodsStoreApply');
        $info = $Model->where(array('id' => $id, 'store_id' => $this->_store_id, 'status' => 1))->find();
        if(!$info){
            $this->error('申请不存在');
        }
        if($status == 2){
            $data = json_decode($info['data'], true);
            $GSModel =  D('Addons://Goods/GoodsStore');
            $GSLModel =  D('Addons://Goods/GoodsStoreLog');
            $push = array();
            foreach($data as $v){
                $goods = M('Goods')->where(array('status' => 1, 'id' => $v['id']))->find();
                if(!$goods){
                    continue;
                }
                if($info['type'] == 5){
                    // 价格修改
                    $GSModel->where(array('store_id' => $this->_store_id, 'goods_id' => $v['id']))->save(array('price' => $v['price']));
                }
                $push[] = $v['id'];
            }
            // 推送更新
            $push && $GSModel->push_num($push,  $this->_store_id);
        }
        if($Model->where(array('id' => $id, 'store_id' => $this->_store_id, 'status' => 1))->save(array('status' => $status, 'update_time' => NOW_TIME))){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    /**
     * 设置已阅
     */
    public function set_apply_is_read(){
        $id = I('id', '');
        if(!$id){
            $this->error('请选择数据');
        }
        if(!is_array($id)){
            $id = explode(',', $id);
        }
        $id && M('GoodsStoreApply')->where(array('id' => array('in', $id), 'store_id' => $this->_store_id))->save(array('is_read' => 1));
        $this->success('操作成功');
    }
    /*
     * 设置商品价格生效
     */
    public function change_price(){
        $id = I('id', '');
        if(!$id){
            $this->error('请选择数据');
        }
        if(!is_array($id)){
            $id = explode(',', $id);
        }
        $type = I('get.type', 0, 'intval');
        $action_time = I('action_time', '');
        $Model = M('GoodsStoreApply');
        $GSModel =  D('Addons://Goods/GoodsStore');
        $where = array('id' => array('in', $id), 'store_id' => $this->_store_id, 'type' => 5, 'status' => 1);
        switch($type){
            case 1:
                // 即时生效
                $list = $Model->where($where)->order('id asc')->select();
                if(!$list){
                    $this->error('操作数据不存在');
                }
                $push = array();
                foreach($list as $v){
                    $data = json_decode($v['data'], true);
                    if($data){
                        foreach($data as $d){
                            if($GSModel->where(array('store_id' => $this->_store_id, 'goods_id' => $d['id']))->save(array('price' => $d['price'], 'update_time' => NOW_TIME))){
                                $push[] = $d['id'];
                                $GSModel->set_change_log(UID, $this->_store_id, $d['id'], array('price' => $d['price']));
                            }
                        }
                    }
                }
                $Model->where($where)->save(array('status' => 2, 'timer_time' => NOW_TIME, 'update_time' => NOW_TIME));
                // 推送更新
                $push && $GSModel->push_num($push,  $this->_store_id);
                
                break;
            case 2:
                // 定时生效
                if($action_time){
                    $action_time = strtotime($action_time);
                }
                if(!$action_time){
                    $this->error('请输入定时任务时间');
                }
                if($action_time < NOW_TIME){
                    $this->error('定时任务时间必须大于当前时间');
                }
                $u_data = array(
                    'timer_time' => $action_time,
                    'do_action' => 2,
                    'update_time' => NOW_TIME,
                );
                $Model->where($where)->save($u_data);
                break;
            default:
                $this->error('请选择生效方式');
        }
        $this->success('操作成功');
    }
    public function push_update(){
        $code = I('get.type', '', 'trim');
        if($code){
            $id = I('ids', 0);
            D('Addons://Goods/GoodsStore')->push_update($code, $id, $this->_store_id, true);
            $data = $this->request('/Internal/push_update_admin', array(
                'store_id' => $this->_store_id,
            ));            
            $this->success('操作成功');
        }else{
            $this->meta_title = '商品数据同步';
            $this->assign('category', D('Addons://Goods/GoodsCate')->get_cate_tree());
            $this->display(T('Addons://Goods@Admin/GoodsStore/push_update'));
        }
    }
    

    public function request($url, $data = array())
    {
        
        $domain = 'https://v.imzhaike.com/Apiv2';
        
        
        $url = $domain . $url;
        
        $device = 0;
        $version = '';
        $key = '$ZaiKe$ByApi$';
        
        $url = trim(strtolower($url));
        $utoken = md5($url . $key . date('Y-m-d'));
        
        
        
        
        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
        
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        $result = curl_exec($ch);
        
        $result = json_decode($result, true);
        return $result;
    }     
    
    
    
}
