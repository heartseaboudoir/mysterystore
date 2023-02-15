<?php

namespace Wap\Controller;

use Think\Controller;

class SmActController extends Controller {

    public function index()
    {
        
        
        
        // 设置的10个商品
        
        // 昨天的的幸运商品，幸运人数，金额，开奖时间，距开奖时间
        
        
        // 开奖规则
        
        // 往期的幸运商品
        
        
        // 历次活动中奖商品信息
        $productLogs = $this->getProductLogs();
        
        $config = $this->getConfig();
        
        $lastConfig = $this->getLastConfig();
        
        
        $data = array(
            'product_lists' => $productLogs,
            'config' => $config,
            'last_config' => $lastConfig,
        );
        
        
        
        
        
        
        
        //header('Content-Type: text/html;charset=utf-8');
        $data_json = json_encode($data, JSON_FORCE_OBJECT);
        
        //echo $data_json;exit;
        
        $this->assign('data_json', $data_json);
        $this->display();
    }
    
    
    
    
    
    
    // 历次活动中奖商品信息
    private function getProductLogs()
    {
        $sql_product_logs = "select l.act_time,g.title from 
        hii_act_product_log l 
        left join hii_goods g 
        on l.act_product = g.id
        order by l.id desc limit 10;";
        
        $data_product_logs = M()->query($sql_product_logs);
        
        if (empty($data_product_logs)) {
            $data_product_logs = array();
        }
        
        
        foreach ($data_product_logs as $key => $val) {
            $data_product_logs[$key]['act_day'] = date('Y.m.d', $val['act_time']);
        }
        
        
        
        return $data_product_logs;
    }






    
    // 获取本次活动配置
    private function getConfig()
    {
        $data = M('act_product_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => 0,
                'title' => '',
                'info' => '',
                'remark' => '',
                'money' => 0,
                'products' => '',
                'stime' => 0,
                'etime' => 0,
                'remark_arr' => array(),
                'products_info' => array(),
                'backtime' => strtotime(date('Y-m-d') . '23:59:59') + 1 + 60 - time(),
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        $remark_arr = explode("\r\n", $data['remark']);
        
        $data['remark_arr'] = $remark_arr;        
        
        // 上次配置的可选商品
        $products_info = $this->getProductLists($data['products']);
        
        $data['products_info'] = $products_info;
        
        $data['backtime'] = strtotime(date('Y-m-d') . '23:59:59') + 1 + 60 - time();
       
            
        return $data;        
    }


    
    // 获取上次活动配置
    private function getLastConfig()
    {
        $data = M('act_product_log')->order('id desc')->find();
        if (empty($data)) {
            return false;
            /*
            return array(
                'title' => '',
                'info' => '',
                'remark' => '',
                'money' => 0, // 活动金额
                'products' => '', // 活动商品
                'act_product' => 0, // 活动选中的商品
                'act_num' => 0, // 中奖人数
                'act_val' => 0, // 人均中奖金额
                'stime' => 0,
                'etime' => 0,
                'remark_arr' => array(),
                'act_product_name' => '',
            );
            */
        }
        
        // 活动说明行
        $remark_arr = explode("\r\n", $data['remark']);
        
        $data['remark_arr'] = $remark_arr;
        
        
        // 上次中奖商品名
        $select = M('goods')->where(array(
            'id' => $data['act_product'],
        ))->find();
        
        if (!empty($select['title'])) {
            $data['act_product_name'] = $select['title'];
        } else {
            $data['act_product_name'] = '';
        }
        
        
        // 上次配置的可选商品
        $products_info = $this->getProductLists($data['products']);
        
        $data['products_info'] = $products_info;
        
        
        return $data;        
    }    
    
   
    // 上次活动商品信息
    private function getProductLists($products)
    {
        $products = empty($products) ? '' : trim($products);
        $product_arr = explode(',', $products);
        
        if (empty($product_arr)) {
            $select_product = array(
                'count' => 0,
                'list' => array(),
            );
        } else {
            
            $products = implode(',', $product_arr);
            $where = ' g.status = 1 and g.id in (' . $products . ')';
            
            $sql = "select g.id,g.title,g.cover_id,g.cate_id, c.title as ctitle   
            from hii_goods g 
            left join hii_goods_cate c 
            on c.id = g.cate_id 
            where {$where} order by id desc limit 10;";
            
            
            $list = M()->query($sql);
            
            if (empty($list)) {
                $list = array();
            }  


            foreach($list as $k => $v){
                $v['pic_url'] = get_cover($v['cover_id'], 'path');
                // $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
                $list[$k] = $v;
            }
            
            $select_product = array(
                'count' => count($list),
                'list' => $list,
            );             
            
        }        
        
        
        return $select_product;
        
    }
    
    
    
    
    
    
    // 上次活动商品信息
    /*
    private function getLastProducts()
    {
        
        $config = $this->getLastConfig();
        
        $products = empty($config['products']) ? '' : trim($config['products']);
        
        $act_product = $config['act_product'];
        
        $product_arr = explode(',', $products);
        
        if (empty($product_arr)) {
            $select_product = array(
                'count' => 0,
                'list' => array(),
            );
        } else {
            
            $products = implode(',', $product_arr);
            $where = ' status = 1 and id in (' . $products . ')';
            
            $sql = "select id,title,cover_id from hii_goods where {$where} order by id desc limit 10;";
            
            
            $list = M()->query($sql);
            
            if (empty($list)) {
                $list = array();
            }  


            foreach($list as $k => $v){
                $v['pic_url'] = get_cover($v['cover_id'], 'path');
                // $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
                $list[$k] = $v;
                
                if ($v['id'] == $act_product) {
                    $list['select'] = true;
                } else {
                    $list['select'] = false;
                }
                
            }
            
            $select_product = array(
                'count' => count($list),
                'list' => $list,
            );             
            
        }        
        
        
        return $select_product;
        
    }
    */

    
    
    // 本次活动商品信息
    /*
    private function getProducts()
    {
        
        $config = $this->getConfig();
        
        $products = empty($config['products']) ? '' : trim($config['products']);
        
        
        $product_arr = explode(',', $products);
        
        if (empty($product_arr)) {
            $select_product = array(
                'count' => 0,
                'list' => array(),
            );
        } else {
            
            $products = implode(',', $product_arr);
            $where = ' status = 1 and id in (' . $products . ')';
            
            $sql = "select id,title,cover_id from hii_goods where {$where} order by id desc limit 10;";
            
            
            $list = M()->query($sql);
            
            if (empty($list)) {
                $list = array();
            }  


            foreach($list as $k => $v){
                $v['pic_url'] = get_cover($v['cover_id'], 'path');
                // $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
                $list[$k] = $v;
            }
            
            $select_product = array(
                'count' => count($list),
                'list' => $list,
            );             
            
        }        
        
        
        return $select_product;
        
    }
    */    
      
    
    
    
}
