<?php
namespace Apiv2\Controller;
use Think\Controller;

class SmJumpController extends Controller {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'sell_goods.txt');
    }
    
    private function response($code = 200, $msg = '已处理请求') 
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

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }    
    
    // 调整批次入库优先顺序
    private function changeOrder($inouts, $store_id)
    {
        $old_inouts = $inouts;
        $this_inouts = array();
        $store_inouts = array();
        $other_inouts = array();
        foreach ($old_inouts as $key => $inout) {
            if ($inout['store_id'] == $store_id) {
                $this_inouts[] = $inout;
            } else if ($inout['store_id'] != 0) {
                $store_inouts[] = $inout;
            } else {
                $other_inouts[] = $inout;
            }
        }
        
        
        $new_inouts = array_merge($this_inouts, $store_inouts, $other_inouts);
        
        return $new_inouts;
        
    }
    
    /**
     * 销售后记录订单各商品的库存信息
     */
    private function orderDetailStock($order)
    {
        
        if (empty($order) || empty($order['order_sn']) || empty($order['store_id'])) {
            return;
        }
        
        $order_sn = $order['order_sn'];
        $store_id = $order['store_id'];
        
        
        $sql = 'show create table hii_order_detail';
        $sql_data = M()->query($sql);
        
        
        // 找出商品
        $detail = M('OrderDetail')->where(array('order_sn' => $order_sn))
            ->field('id,order_sn,title,d_id,num,price,inout_ids,pre_store,now_store')->select();        
        
        
        
        // 找到不到商品信息则返回
        if (empty($detail)) {
            $detail = array();
            return false;
        }
        
        // 遍历当前订单的商品
        foreach ($detail as $key =>$val) {
            
            // 已经处理过不再处理
            if (!empty($val['pre_store'])) {
                continue;
            }
            
            // 查询商品库存信息
            $goods_info = M('goods_store')->where(array(
                'goods_id' => $val['d_id'],
                'store_id' => $store_id,
            ))->find();   
            
            // 查询到商品的库存信息，且当前订单的商品库存信息没有记录
            if (!empty($goods_info) && ($goods_info['num'] >= 0)) {
                M('OrderDetail')->where(array(
                    'id' => $val['id'],
                ))->save(array(
                    'pre_store' => $goods_info['num'] + $val['num'],
                    'now_store' => $goods_info['num'],
                ));
                
            }     
        }
        
        return true;
        
        

    }
    
    
    /**
     * 1.减入库批次表
     * 2.对应批次加到订单商品详情
     */
    public function sell_goods(){
        $order_sn = I('order_sn', '', 'trim');
        
        
        if (empty($order_sn)) {
            $this->response(10010, 'order_sn is empty');
        }
        
        
        // 找出订单
        $order = M('order')->where(array('order_sn' => $order_sn))->find();
        
        // 订单不存在
        if (empty($order)) {
            $this->response(10011, 'order_sn not find');
        }
        
        
        // 订单未支付
        if ($order['status'] != 5 || $order['pay_status'] != 2) {
            $this->response(10012, 'order not over');
        }
        
        // 写入订单商品库存信息
        $this->orderDetailStock($order);
        
        
        // 找出订单所属社区
        $store_info = M('store')->where(array('id' => $order['store_id']))->find();
        
        //$this->response(100199, $store_info);
        
        
        if (empty($store_info) || empty($store_info['shequ_id'])) {
            $this->response(10013, 'store shequ_id empty');
        } else {
            $shequ_id = $store_info['shequ_id'];
        }
        
        
        // 哪些区域可以使用新版ERP
        $shequs_data = M('shequ')->where(array('newerp' => 1))->select();
        $shequs_can = array();
        if (!empty($shequs_data)) {
            foreach ($shequs_data as $key => $val) {
                $shequs_can[] = $val['id'];
            }
        }

        if (!in_array($shequ_id, $shequs_can)) {
            $this->response(10014, 'store shequ_id old_erp');
        }
        
        
        
        // 找出商品
        $detail = M('OrderDetail')->where(array('order_sn' => $order_sn))
            ->field('id,order_sn,title,d_id,num,price,inout_ids')->select();
        
        
        // 返回结果
        // -------------
        $res_goods = array();
        
        
        // 遍历处理每个商品的批次
        foreach ($detail as $key => $goods) {
            
            
            if (!empty($goods['inout_ids'])) {
                continue;
            }
            
            //echo $goods['d_id'] . '--';
            // 找出指定商品的批次
            $inouts = M('warehouse_inout')->where(array(
                'goods_id' => $goods['d_id'],
                'shequ_id' => $shequ_id,
                'num' => array('gt', 0),
            ))->order('inout_id asc')->select();
            
            
            if (empty($inouts)) {
                $inouts = array();
            }
            //$this->debugs($inouts);
            
            $inouts = $this->changeOrder($inouts, $order['store_id']);
            
            
            //$this->debugs($inouts);
            
            
            $inout_ids = array();
            $inout_price = array();
            $inout_nums = array();
            $num = $goods['num'];
            
            /*
            print_r($goods);
            echo 'x';
            var_dump($inouts);
            exit;
            */
            
            // -------------
            $res_goods_one = array(
                'num' => $num,
            );
            
            foreach ($inouts as $key2 => $inout) {
                
                // 还有要减扣的批次，且当前批次可减
                if ($num > 0 && $inout['num'] > 0) {
                    // 记录处理的批次ID
                    $inout_ids[] = $inout['inout_id'];
                    $inout_price[] = $inout['inprice'];
                    
                    // 当前批次足以减扣
                    if ($inout['num'] >= $num) {
                        // 当前批次减扣数
                        $inout_num = $num;                        
                        
                        // 还剩余的减扣数
                        $num = 0;
                        
                         // 记录处理的批次数量
                        $inout_nums[] = $inout_num;
                        
                        // 减扣批次
                        $this->outWarehouse($inout, $inout_num, $order);                        
                        
                        // 批次减扣完成
                        break;
                    // 当前批次不足以减扣
                    } else {
                        // 当前批次减扣数
                        $inout_num = $inout['num'];                        
                        
                        // 还剩余的减扣数
                        $num -= $inout['num'];
                        
                         // 记录处理的批次数量
                        $inout_nums[] = $inout_num;
                        
                        // 减扣批次
                        $this->outWarehouse($inout, $inout_num, $order);                        
                    }
                    

                    
                }
                
                
            }
            
            // -------------
            $res_goods_one['ids'] = $inout_ids;
            $res_goods[$goods['d_id']] = $res_goods_one;
            
            
            // 每个商品回写批次ID
            if (!empty($inout_ids)) {
                $inout_ids_str = implode(',', $inout_ids);
                $inout_nums_str = implode(',', $inout_nums);
                $inout_prices_str = implode(',', $inout_price);
                
                $inout_price_all = 0;
                $nums = 0;
                foreach ($inout_nums as $key => $val) {
                    $inout_price_all += $val * $inout_price[$key];
                    $nums += $val;
                }
                
                if ($nums != 0) {
                    $inout_price_one = $inout_price_all / $nums;
                } else {
                    $inout_price_one = 0;
                }
                
                
                M('OrderDetail')->where(array('id' => $goods['id']))->save(array(
                    'inout_ids' => $inout_ids_str,
                    'inout_nums' => $inout_nums_str,
                    'inout_prices' => $inout_prices_str,
                    'inout_price_all' => $inout_price_all,
                    'inout_price_one' => $inout_price_one,
                ));
                
                
            }
            
            
        }
        
        
        
        
        
        $this->debugs($res_goods);
        $this->response(200, $res_goods);
        //$this->response(200, $detail);
        
        
        // 对批次表减批次，同时将批次编号写入商品
        // 如查商品已经处理过一次，则不再处理
        
        
        
        
    }
    
    // 商品指次出库，获取批次，批次写回商品
    private function outWarehouse($inout, $num, $order)
    {
        /*
        print_r($inout);
        print_r($num);
        print_r($order);
        exit;
        */
        M('warehouse_inout')->where(array(
            'inout_id' => $inout['inout_id'],
        ))->save(array(
            // 最后操作类型
            'etype' =>0,
            
            // 最后操作时间
            'etime' => time(),
            
            // 已出数量
            'outnum' => array('exp', 'outnum+' . $num),
            
            // 现有批次库存数量
            'num' => array('exp', 'num-' . $num),
            
            // 最后操作数量
            'enum' => $num,
            
            // 最后操作单据id
            'e_id' => $order['id'],
            
            // 操作总次数
            'e_no' => array('exp', 'e_no+1'),
        ));
        
        
        
    }

}


/*
CREATE TABLE `hii_warehouse_inout` (
  `inout_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) DEFAULT '0' COMMENT '商品id',
  `innum` int(10) DEFAULT '0' COMMENT '批次入库数量',
  `inprice` decimal(10,2) DEFAULT '0' COMMENT '批次入库价格',
  `outnum` int(10) DEFAULT '0' COMMENT '已出数量',
  `num` int(10) DEFAULT '0' COMMENT '现有批次库存数量',
  `ctime` int(10) DEFAULT '0' COMMENT '入库时间',
  `ctype` int(1) DEFAULT '0' COMMENT '批次入库类型:0.采购入库，1.盘盈入库,2.门店入库',
  `shequ_id` int(10) DEFAULT '0' COMMENT '区域ID',
  `endtime` int(10) DEFAULT '0' COMMENT '商品过期日期',
  `warehouse_id` int(10) DEFAULT '0' COMMENT '入库仓库ID',
  `store_id` int(10) DEFAULT '0' COMMENT '入库门店ID',
  `w_in_s_d_id` int(10) DEFAULT '0' COMMENT '仓库入库单子表id',
  `s_in_s_d_id` int(10) DEFAULT '0' COMMENT '门店入库单子表id',
  `etime` int(10) DEFAULT '0' COMMENT '最后操作时间',
  `etype` int(1) DEFAULT '0' COMMENT '最后操作类型:0.销售出库，1.报损出库，2.盘亏出库',
  `enum` int(10) DEFAULT '0' COMMENT '最后操作数量',
  `e_id` int(10) DEFAULT '0' COMMENT '最后操作单据id',
  `e_no` int(10) DEFAULT '0' COMMENT '操作总次数',
PRIMARY KEY (`inout_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='入库批次表';

*/