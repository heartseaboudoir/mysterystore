<?php

// +----------------------------------------------------------------------
// | Title: 商品
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 门店端
// +----------------------------------------------------------------------
namespace Api\Controller;
use Api\Controller\ApiController;

class GoodsController extends ApiController {
    /**
     * @name cate_info
     * @title 商品分类信息
     * @param int  id  分类ID
     * @return [id] => ID<br>[pid] => 上级ID<br>[title] => 分类名<br>
     * @remark 
     */
    public function cate_info(){
        $this->_check_token();
        $id = I('id', 0, 'intval');
        if($id < 1){
            $this->return_data(0, array());
        }
        $where = array();
        $where['status'] = 1;
        $where['id'] = $id;
        $data = D('Addons://Goods/GoodsCate')->where($where)->field('id, pid, title')->find();
        if(!$data){
            $this->return_data(0, array());
        }
        $pre = C('DB_PREFIX');
        // 筛选去除无库存分类
        $where = array();
        $where['cate_id'] = $id;
        $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods_store.store_id = {$this->_store_id} and {$pre}goods_store.num > 0";
        $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
        if(!D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id")->find()){
            $this->return_data(0, array());
        }
        $this->return_data(1, $data);
    }
    /**
     * @name cate_list
     * @title 商品分类列表
     * @return [id] => ID<br>[pid] => 上级ID<br>[title] => 分类名<br>
     * @remark 
     */
    public function cate_list(){
        $this->_check_token();
        $pid = I('pid', 0, 'intval');
        $pid < 0 && $pid = 0;
        $where = array();
        $where['status'] = 1;
        $data = D('Addons://Goods/GoodsCate')->where($where)->field('id, pid, title')->order('listorder desc, create_time asc')->select();
        !$data && $data = array();
        $pre = C('DB_PREFIX');
        $_data = array();
        foreach($data as $v){
            // 筛选去除无库存分类
            $where = array();
            $where['cate_id'] = $v['id'];
            $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods_store.store_id = {$this->_store_id} and {$pre}goods_store.num > 0";
            $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
            if(D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id")->find()){
                $_data[] = $v;
            }
        }
        $this->return_data(1, $_data);
    }
    /**
     * @name lists
     * @title 商品列表
     * @param   string   $ids  商品ID，默认为0，不使用此参数（多个商品ID间使用,格式，如:  12,3,22。）
     * @param   int $cate_id  分类ID(默认为全部)
     * @param   string $keyword  (关键词，可以是标题，首字母，拼音，默认为无)
     * @param   int $row 条数(最大值为500, 默认为100)
     * @param   int $offset  数据页数(默认为1，即为第1页)
     * @return [id] => ID<br>[title] => 商品名<br>[pinyin] => 商品拼音<br>[fir_letter] => 商品标题首字母<br>[num] => 库存<br>[month_num] => 本月销售数量<br>[price] => 售价<br>[unit] => 商品单位<br>[cate_id] => 分类ID<br>[pic_url] => 商品图片<br>[create_time] => 创建时间戳<br>[is_hot] => 是否热销(0 否 1 是)<br>[hot_val] => 热度值<br>[bar_code] => 条形码（数组）<br>[content] => 商品详情描述
     * @remark 
     */
    public function lists(){
        $this->_check_token();
        $row = I('row', 100, 'intval');
        $row > 1000 && $row = 1000;
        $row < 100 && $row = 100;
        $page = I('offset', 0, 'intval');
        $page < 1 && $page = 1;
        $cate_id = I('cate_id', 0, 'intval');
        $ids = I('ids', 0);
        $pre = C('DB_PREFIX');
        $where = array();
        if($cate_id > 0){
            $where['cate_id'] = $cate_id;
        }elseif($cate_id == -1){
            $where['is_hot'] = 1;
        }
        $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods.sell_outline = 1 and {$pre}goods_store.store_id = {$this->_store_id} and {$pre}goods_store.num > 0";
        if($ids){
            $ids = explode(',', trim($ids));
            foreach($ids as $k => $v){
                $v = intval($v);
                if($v > 0){
                    $ids[$k] = $v;
                }else{
                    unset($ids[$k]);
                }
            }
            $ids && $ids = implode(',', $ids);
            if(!$ids){
                $this->return_data(1, array(), '', array('row' => $row, 'offset' => $page, 'count' => 0, 'total' => 0));
            }
            $where['_string'] .= " and {$pre}goods.id in({$ids})"; 
        }
        $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
        // 去掉其它信息{$pre}goods.content,
        $data = D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id, title, pinyin, fir_letter, unit, is_hot, sell_price, cate_id, cover_id, {$pre}goods.goods_remark, {$pre}goods.create_time, {$pre}goods_store.num, {$pre}goods_store.month_num, {$pre}goods_store.price, {$pre}goods_store.shequ_price, {$pre}goods_store.hot_val")->order('listorder desc, create_time desc')->page($page, $row)->select();
        !$data && $data = array();
        $goods_ids = array();
        foreach($data as $k => $v){
            $v['pic_url'] = $v['cover_id'] ? get_cover_url($v['cover_id']) : '';
            unset($v['cover_id']);
            // 商品价格未设置，则使用社区价格
            (!$v['price'] || $v['price'] <= 0) && $v['price'] = $v['shequ_price'];
            
            //(!$v['price'] || $v['price'] <= 0) && $v['price'] = $this->getShequPrice($v['id'], $this->_store_id);
            
            // 商品价格还未设置，则使用全局售价
            (!$v['price'] || $v['price'] <= 0) && $v['price'] = $v['sell_price'];
            unset($v['sell_price']);
            $goods_ids[] = $v['id'];
            $data[$k] = $v;
        }
        if($goods_ids){
            $log_model = M('GoodsSellLog'.$this->_store_id);
            $date = array(
                date('Y-m-d', strtotime('-1 day')), // 昨天
                date('Y-m-d', strtotime('-2 day')), // 前天
            );
            $log_data = $log_model->where(array('goods_id' => array('in', $goods_ids), 'date' => array('in', $date)))->select();
            foreach($log_data as $v){
                if($v['date'] == $date[0]){
                    $log_num1[$v['goods_id']] = $v['num'];
                }elseif($v['date'] == $date[1]){
                    $log_num2[$v['goods_id']] = $v['num'];
                }
            }
            $barcode_data = M('GoodsBarCode')->where(array('goods_id' => array('in', $goods_ids)))->select();
            $goosd_barcode = array();
            foreach($barcode_data as $v){
                $goosd_barcode[$v['goods_id']][] = $v['bar_code'];
            }
            foreach($data as $k => $v){
                $num1 = isset($log_num1[$v['id']]) ? $log_num1[$v['id']] : 0;
                $num2 = isset($log_num2[$v['id']]) ? $log_num2[$v['id']] : 0;
                $v['hot_val'] > 10 && $v['hot_val'] = 10.0;
                $v['hot_val'] < 1 && $v['hot_val'] = 1.0;
                $v['bar_code'] = !empty($goosd_barcode[$v['id']]) ? $goosd_barcode[$v['id']] : array();
                $data[$k] = $v;
            }
        }
        $total = D('Addons://Goods/Goods')->join($join)->where($where)->count();
        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => count($data), 'total' => (int)$total));
    }
    
    // 获取社区售价
    private function getShequPrice($goods_id, $store_id)
    {
        if (empty($goods_id) || empty($store_id)) {
            return 0;
        }
        
        $storeInfo = M('store')->where(array(
            'id' => $store_id
        ))->find();
        
        
        if (empty($storeInfo) || empty($storeInfo['shequ_id'])) {
            return 0;
        }
        
        $res = M('goods_shequ')->where(array(
            'goods_id' => $goods_id,
            'shequ_id' => $storeInfo['shequ_id'],
            'status' => 1,
        ))->find();
        
        if (empty($res) || empty($res['price']) || $res['price'] <= 0) {
            return 0;
        } else {
            return $res['price'];
        }  
    }    
    
    
    
    /**
     * @name keyword_lists
     * @title 关键词列表
     * @param   string $keyword  (关键词）
     * @return 
     * @remark 
     */
    public function keyword_lists(){
        $keyword = I('keyword', '', 'trim');
        if(!$keyword){
            $this->return_data(1, array());
        }
        $where = array();
        $where['title'] = array('like', "{$keyword}%");
        $where['pinyin'] = array('like', "{$keyword}%");
        $where['fir_letter'] = array('like', "{$keyword}%");
        $where['_logic'] = 'or';
        $lists = M('GoodsTag')->where($where)->field('title')->select();
        !$lists && $lists = array();
        $result = array();
        foreach($lists as $v){
            $result[] = $v['title'];
        }
        $this->return_data(1, $result);
    }
}
