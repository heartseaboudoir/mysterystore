<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-22
 * Time: 10:54
 * 临时调拨申请接口
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class AssignmentApplicationController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择门店
    }

    /************
     * 临时调拨申请单
     */
    public function temp()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@AssignmentApplication/temp'));
    }

    /**************
     * 调拨申请单
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@AssignmentApplication/index'));
    }

    public function view()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@AssignmentApplication/view'));
    }

    /************
     * 通过二维码查找商品
     ***********/
    public function get_one()
    {
        $bar_code = I('bar_code', '', 'trim');
        if (!$bar_code) {
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
        if (!$data) {
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('Goods')->where(array('id' => $data['goods_id'], 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if (!$data) {
            $this->ajaxReturn(array('status' => 0));
        } else {
            $where['admin_id'] = UID;
            $where['temp_type'] = 1;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,g_num')->find();
            if (!$data1) {
                $this->ajaxReturn(array('status' => 1, 'data' => $data));
            } else {
                $this->ajaxReturn(array('status' => 2, 'data' => $data, 'data1' => $data1));
            }
        }
    }

    /********************
     * 通过ID查找商品
     **********************/
    public function get_one_id()
    {
        $id_code = I('id_code', 0, 'intval');
        if (empty($id_code)) {
            $this->ajaxReturn(array('status' => 0));
        }

        $data = M('Goods')->where(array('id' => $id_code, 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if (!$data) {
            $this->ajaxReturn(array('status' => 0));
        } else {
            $where['admin_id'] = UID;
            $where['temp_type'] = 1;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,g_num')->find();
            if (!$data1) {
                $this->ajaxReturn(array('status' => 1, 'data' => $data));
            } else {
                $this->ajaxReturn(array('status' => 2, 'data' => $data, 'data1' => $data1));
            }
        }
    }

    /*******************************
     * 获取商品列表
     ***************************/
    public function get_goods_lists()
    {
        $keyword = I('keyword', '', 'trim');
        $cate_id = I("cate_id");
        $temp_type = I("temp_type",'');
        $stock_status = I('stock_status',0,'intval');//当前社区是否有出入库记录
        $store_id = $this->_store_id;
        $Model = M('Goods')->alias('a');
        $where = array();
        $keyword && $where['a.title'] = array('like', '%' . $keyword . '%');
        $where['a.status'] = 1;
        
        if (!is_null($cate_id) && !empty($cate_id)) {
            $where["a.cate_id"] = $cate_id;
        }else{
            $where['a.cate_id'] = array('neq',18); //去除私人定制
        }
        $field = 'a.id,a.title,a.sell_price,a.cover_id';
        //$join = "left join hii_goods_cate b on a.cate_id=b.id";
        $_REQUEST['r'] = 20;
        $p = I('p', '', 'trim');
        if ($p == '') {
            $p = $_POST['p'];
        }
        if ($p == '') {
            $p = 1;
        }
        
        //读取门店所在区域
        $store_id = $this->_store_id;
        $store_data = M("Store")->where(" id={$store_id} ")->select();
        $shequ_id = $store_data[0]["shequ_id"];
        $GoodsStoreModel = M("GoodsStore");
        $attrValueModel = M("AttrValue");
        
        //判断是否是盘点接口  如果是盘点接口要关联相应门店
        if($temp_type == 8){
            $goodsStoreModel = M("GoodsStore");
            $goods_store_sql = $goodsStoreModel->field("distinct goods_id")->where(array("store_id"=>$store_id))->select(false);
            $list = $Model->where($where)->field($field)->join("inner join {$goods_store_sql} as b on b.goods_id=a.id")->order('listorder desc, create_time desc')->limit(($p - 1) * 20 . ',20')->select();
        }else{
            if($stock_status){
                $where['a.id'] = array('exp',"in(select GS.goods_id as goods_id from hii_goods_store GS INNER JOIN hii_store S on S.id=GS.store_id and S.shequ_id = {$shequ_id} and S.status=1 union select WS.goods_id as goods_id from hii_warehouse_stock WS INNER JOIN hii_warehouse W on W.w_id=WS.w_id and W.shequ_id = {$shequ_id} and W.w_type=0)");
            }
            $list = $Model->where($where)->field($field)->order('listorder desc, create_time desc')->limit(($p - 1) * 20 . ',20')->select();
        }

        foreach ($list as $k => $v) {
            $attr_value = $attrValueModel->field('value_id,value_name')->where(array('goods_id'=>$v['id'],'status'=>1))->select();
            if(empty($attr_value)){
                $attr_value = array();
            }
            $v['attr_value'] = json_encode($attr_value);
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
            $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
            $list[$k] = $v;
            //读取价格，先读取区域价，没有区域价读取系统售价
            $goods_shequ_data = $GoodsStoreModel->where(" goods_id={$v["id"]} and store_id={$store_id} ")->limit(1)->select();
            if (!is_null($goods_shequ_data) && !empty($goods_shequ_data) && count($goods_shequ_data) > 0) {
                if (!is_null($goods_shequ_data[0]["price"]) && !empty($goods_shequ_data[0]["price"]) && $goods_shequ_data[0]["price"] > 0) {
                    $list[$k]["sell_price"] = $goods_shequ_data[0]["price"];
                } elseif (!is_null($goods_shequ_data[0]["shequ_price"]) && !empty($goods_shequ_data[0]["shequ_price"]) && $goods_shequ_data[0]["shequ_price"] > 0) {
                    $list[$k]["sell_price"] = $goods_shequ_data[0]["shequ_price"];
                }
            }
        }
        if (IS_AJAX) {
            if (is_array($list) && count($list) > 0) {
                $this->ajaxReturn(array('status' => 1, 'data' => $list, 'msgword' => 'OK'));
            } else {
                $this->ajaxReturn(array('status' => 2, 'data' => $list, 'msgword' => '没有更多了...'));
            }
            exit;
        }
        $this->assign('list', $list);
        $this->assign('temp_type', $temp_type);
        $this->assign('stock_status', $stock_status);
        $this->display(T('Addons://StoreModule@AssignmentApplication/get_goods_lists'));
    }

}