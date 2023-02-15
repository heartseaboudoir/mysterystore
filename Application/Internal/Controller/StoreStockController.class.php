<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-07
 * Time: 16:42
 * 门店库存
 */

namespace Internal\Controller;

use Think\Controller;

class StoreStockController extends ApiController
{

    public function _initialize()
    {
        // 是否验证token
        $action = ACTION_NAME;//当前请求action名称
        //$actions = array("get_store_stock_all_cates", "get_goods_cates", "get_store_stock_with_cate_id", "get_goods_in_stock_history", "get_goods_out_stock_history", "get_goods", "get_store_inventory_detail");
        $actions = array();
        $check = false; // true为指定的验证，false为指定的不验证
        if (in_array($action, $actions)) {
            $this->ctoken = $check;
        } else {
            $this->ctoken = !$check;
        }
        parent::_initialize();
        header("Content-Type: text/html;charset=utf-8");
    }

    /*************
     * 分类库存列表
     * 请求方式：POST
     * 请求参数：store_id   门店ID   必须
     * 日期：2018-03-07
     */
    public function get_store_stock_all_cates()
    {
        $store_id = I("post.store_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        $result = $this->getStoreStockAllCates($store_id);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /*********************
     * 商品分类列表
     * 请求方式：POST
     * 请求参数：无
     * 日期：2018-03-07
     */
    public function get_goods_cates()
    {
        $GoodsCateModel = M("GoodsCate");
        $list = $GoodsCateModel->query(" select id as cate_id,title as cate_name from hii_goods_cate order by id ASC ");
        $this->response(self::RESPONSE_SUCCES, $list);
    }

    /*************
     * 获取某个类别的商品库存信息
     * 请求方式：POST
     * 请求参数：store_id  门店ID       必须
     *           cate_id   商品种类ID   必须
     *           p         当前页       否，默认为1
     *           pageSize  每页显示数量 否，默认15条
     *          show_stock_zero 是否显示零库存 0：不显示 1：显示;
     */
    public function get_store_stock_with_cate_id()
    {
        $store_id = I("post.store_id");
        $cate_id = I("post.cate_id");
        $show_stock_zero = I("post.show_stock_zero",0,'intval');
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($cate_id) || empty($cate_id)) {
            $this->response(0, "缺少商品种类ID");
        }
        $result = $this->getStoreStockWithCateId($store_id, $cate_id, true,$show_stock_zero);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /************************
     * 获取商品入库记录
     * 请求方式：POST
     * 请求参数：store_id  门店ID        必须
     *           goods_id  商品ID        必须
     *           p         当前页        否，默认1
     *           pageSize  每页显示数量  否，默认15条
     */
    public function get_goods_in_stock_history()
    {
        $store_id = I("post.store_id");
        $goods_id = I("post.goods_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "缺少商品ID");
        }
        $result = $this->getGoodsInStockHistory($store_id, $goods_id, true);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /************************
     * 获取商品出库记录
     * 请求方式：POST
     * 请求参数：store_id  门店ID        必须
     *           goods_id  商品ID        必须
     *           p         当前页        否，默认1
     *           pageSize  每页显示数量  否，默认15条
     */
    public function get_goods_out_stock_history()
    {
        $store_id = I("post.store_id");
        $goods_id = I("post.goods_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "缺少商品ID");
        }
        $result = $this->getGoodsOutStockHistory($store_id, $goods_id, true);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /***********************
     * 获取商品
     * 请求方式：POST
     * 请求参数：store_id    门店ID        必须
     *           p           当前页        否，默认1
     *           pageSize    每页显示数量  否，默认15条
     *           goods_name  商品名称      否
     */
    public function get_goods()
    {
        $store_id = I("post.store_id");
        $goods_name = I("post.goods_name");
        $GoodsModel = M("Goods");
        $sql = "select G.id as goods_id,G.title as goods_name,G.sell_price,GC.title as cate_name,ifnull(GS.num,0) as stock_num ";
        $sql .= "from hii_goods G ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=G.id and GS.store_id={$store_id} ";
        $sql .= "group by G.id,G.title,G.sell_price,GC.title ";
        $sql .= "order by G.id asc ";

        $data = $GoodsModel->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["data"] = $this->isArrayNull($data);

        $this->response(self::RESPONSE_SUCCES, $result);

    }

    /***********************
     * 获取商品
     * 请求方式：POST
     * 请求参数：store_id    门店ID        必须
     *           goods_name  商品名称      否
     */
    public function get_store_all_goods()
    {
        $store_id = I("post.store_id");
        $goods_name = I("post.goods_name");
        $GoodsModel = M("Goods");
        $GoodsBarCodeModel = M("GoodsBarCode");
        $sql = "select G.cover_id,G.id as goods_id,G.title as goods_name,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END) sell_price,GC.title as cate_name,ifnull(GS.num,0) as stock_num,G.bar_code ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where GS.store_id={$store_id} ";
        if (!is_null($goods_name) && !empty($goods_name)) {
            $sql .= " and G.title like '%{$goods_name}%' ";
        }
        $sql .= "group by G.id,G.title,G.sell_price,GC.title,G.bar_code ";
        $sql .= "order by G.id asc ";

        $data = $GoodsModel->query($sql);

        if ($this->isArrayNull($data) != null) {
            foreach ($data as $key => $val) {
                $data[$key]['pic_url'] =  M('Picture')->where(array('id'=>$val['cover_id']))->getField('path');
                $bar_code_array = $GoodsBarCodeModel->field("bar_code")->where(" `goods_id`={$val["goods_id"]} ")->select();
                if ($this->isArrayNull($bar_code_array) != null) {
                    $data[$key]["bar_code"] = $bar_code_array;
                } else {
                    $data[$key]["bar_code"] = array();
                }
            }
        }

        $result["data"] = $this->isArrayNull($data) == null ? array() : $data;

        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /********************
     * 提交临时盘点数据
     * 请求方式：POST
     * 请求参数：store_id       门店ID            必须
     *           remark         主表备注          否
     *           info_json_str  子表json字符串    必须   格式：[{"goods_id":"","g_num":"","remark":""},{"goods_id":"","g_num":"","remark":""}]
     *
     */
    public function submit_temp_inventory()
    {
        $store_id = I("post.store_id");
        $remark = I("post.remark");
        $info_json_str = I("post.info_json_str");
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        if (is_null($detail_array) || count($detail_array) == 0) {
            $this->response(0, "缺少子表信息");
        }
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
		
        $uid = $this->uid;
        $StoreStockReposity = D("StoreStock");
        $result = $StoreStockReposity->add_normal_inventory($uid, $store_id, $remark, $detail_array);
        $this->response($result["status"], $result["msg"]);
    }

    /******************************
     * 新增月末盘点单
     * 请求方式：POST
     * 请求参数：store_id    门店ID    必须
     */
    public function add_month_inventory()
    {
        $store_id = I("post.store_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        $uid = $this->uid;
        $StoreStockReposity = D("StoreStock");
        $result = $StoreStockReposity->add_month_inventory($uid, $store_id);
        //$this->response($result["status"], $result["msg"]);
        if ($result["status"] == 100 || $result["status"] == 200) {
            //本月月末盘点已存在
            $si_id = $result["si_id"];
            $data = $this->getStoreInventoryDetail($store_id, $si_id);

            //分页
            $pcount = $this->getPageSize();
            $count = count($data['list']);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
            $data['list'] = array_slice($data['list'], $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿

            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["data"] = $this->isArrayNull($data);
            $this->response(self::RESPONSE_SUCCES, $result);
        } else {
            $this->response($result["status"], $result["msg"]);
        }
    }

    /********************
     * 获取盘点单列表
     * 请求方式：POST
     * 请求参数：store_id  门店ID       必须
     *           si_status 是否已审核   否，默认0   0:未审核  1：已审核
     */
    public function get_store_inventory_list()
    {
        $store_id = I("post.store_id");
        $si_status = I("post.si_status");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($si_status) || empty($si_status)) {
            $si_status = 0;
        }
        $result = $this->getStoreInventoryList($store_id, $si_status, true);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /*************
     * 获取单个盘点单详细信息
     * 请求方式：POST
     * 请求参数：store_id         门店ID     必须
     *           si_id            盘点单ID   必须
     *           status           是否验收   必须  0：未验收  1：已验收
     *           show_stock_zero  是否显示零库存  必须  0：不显示  1：显示
     *           goods_name       商品名称   非必须
     *           bar_code         商品条码   非必须
     *           cate_id          商品种类   非必须
     */
    public function get_store_inventory_detail()
    {
        $store_id = I("post.store_id");
        $si_id = I("post.si_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "缺少盘点单ID");
        }
        $result = $this->getStoreInventoryDetail($store_id, $si_id);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /*******************
     * 盘点单修改
     * 请求方式：POST
     * 请求参数：store_id          门店ID          必须
     *           si_id             盘点单ID        必须
     *           remark            备注            非必须
     *           info_json_str     盘点子表信息    必须    格式：[{"si_d_id":"1","g_num":"20","remark":""},{"si_d_id":"1","g_num":"20","remark":""}]
     */
    public function update_store_inventory()
    {
        $store_id = I("post.store_id");
        $si_id = I("post.si_id");
        $remark = I("post.remark");
        $info_json_str = I("post.info_json_str");
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        $uid = $this->uid;
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "缺少盘点单ID");
        }
        if (is_null($detail_array) || count($detail_array) == 0) {
            $this->response(0, "缺少子表信息");
        }
        $StoreStockReposity = D("StoreStock");
        $result = $StoreStockReposity->update_store_inventory($uid, $store_id, $si_id, $remark, $detail_array);
        $this->response($result["status"], $result["msg"]);
    }

    /*******************
     * 盘点单审核
     * 请求方式：POST
     * 请求参数：store_id          门店ID          必须
     *           si_id             盘点单ID        必须
     *           remark            备注            非必须
     *           info_json_str     盘点子表信息    必须    格式：[{"si_d_id":"1","g_num":"20","remark":""},{"si_d_id":"1","g_num":"20","remark":""}]
     */
    public function check()
    {
        $store_id = I("post.store_id");
        $si_id = I("post.si_id");
        $remark = I("post.remark");
        $si_type = I("post.si_type",'');
        $info_json_str = I("post.info_json_str");
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        $uid = $this->uid;
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "缺少盘点单ID");
        }
        $StoreStockReposity = D("StoreStock");

        $result = array();
        if($si_type === '1'){
            $result["status"] = 200;
        }elseif($si_type === '0'){
            $result = $StoreStockReposity->update_store_inventory($uid, $store_id, $si_id, $remark, $detail_array);
        }else{
            $this->response(0, "缺少盘点单类型");
        }

        if ($result["status"] == 200) {
            $result = $StoreStockReposity->check($uid, $store_id, $si_id);
            $this->response($result["status"], $result["msg"]);
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /*********************
     * 盘点子表数据删除
     * 请求方式：POST
     * 请求参数：store_id  门店ID          必须
     *           si_id     盘点单ID        必须
     *           si_d_id   盘点单子表ID    必须
     ********************/
    public function store_inventory_detail_delete()
    {
        $store_id = I("post.store_id");
        $si_id = I("post.si_id");
        $si_d_id = I("post.si_d_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "缺少盘点单ID");
        }
        if (is_null($si_d_id) || empty($si_d_id)) {
            $this->response(0, "缺少盘点单子表ID");
        }
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $store_inventory_data = $StoreInventoryModel->where(" `si_id`={$si_id} and `store_id`={$store_id} and `si_status`=0 ")->limit(1)->select();
        if ($this->isArrayNull($store_inventory_data) == null) {
            $this->response(0, "盘点单不存在");
        }
        $ok = $StoreInventoryDetailModel->where(" `si_d_id`={$si_d_id} and `si_id`={$si_id} ")->limit(1)->delete();
        if ($ok === false) {
            $this->response(0, "删除失败");
        } else {
            $this->response(self::RESPONSE_SUCCES, "操作成功");
        }
    }

    /********************
     * 判断当前月份月末盘点单是否已审核
     * 请求方式：POST
     * 请求参数：store_id  门店ID   必须
     */
    public function current_month_inventory_checked()
    {
        $store_id = I("post.store_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        $StoreInventoryModel = M("StoreInventory");
        $store_inventory_data = $StoreInventoryModel
            ->where(" store_id={$store_id} and si_type=1 and si_status=1 and ptime>0 and DATE_FORMAT( FROM_UNIXTIME(ctime,'%Y-%m-%d %H:%i:%s'),'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m') ")
            ->order(" si_id desc ")->limit(1)->select();

        if (!is_null($store_inventory_data) && !empty($store_inventory_data) && count($store_inventory_data) > 0) {
            $this->response(self::RESPONSE_SUCCES, "1");
        } else {
            $this->response(self::RESPONSE_SUCCES, "0");
        }

    }

    /********************************
     * 通过条码搜索商品
     * 请求方式：POST
     * 参数：store_id  门店ID    必须
     *       bar_code  商品条码  必须
     */
    public function search_goods_by_bar_code()
    {
        $bar_code = I("post.bar_code");
        $store_id = I("post.store_id");
        if (empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (empty($bar_code)) {
            $this->response(0, "缺少条码");
        }
        $sql = "
          select G.cover_id,G.id as goods_id,G.title as goods_name,G.sell_price as sys_price,GC.title as cate_name,GS.num as stock_num,
          GS.price as store_price,GS.shequ_price as shequ_price 
          from hii_goods_store GS
          left join hii_goods G on GS.goods_id=G.id 
          left join hii_goods_cate GC on GC.id=G.cate_id
          where GS.store_id={$store_id} and G.id in (select goods_id from hii_goods_bar_code where `bar_code`='{$bar_code}' group by goods_id )
        ";
        $data = M()->query($sql);
        foreach ($data as $key => $val) {
            $data[$key]['pic_url'] = get_cover($val['cover_id'],'path');
            if (!is_null($val["store_price"]) && !empty($val["store_price"]) && $val["store_price"] > 0) {
                $data[$key]["sell_price"] = $val["store_price"];
            } elseif (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $data[$key]["sell_price"] = $val["shequ_price"];
            } else {
                $data[$key]["sell_price"] = $val["sys_price"];
            }
        }
        $this->response(self::RESPONSE_SUCCES, $data);
    }

    /******************************************************* 私有方法 ***********************************************************************************************/
    private function getStoreStockAllCates($store_id)
    {
        $StoreModel = M("Store");
        $GoodsStoreModel = M("GoodsStore");
        $cate_name = I("post.cate_name");
        $StoreDatas = $StoreModel->query("select title from hii_store where id={$store_id} limit 1 ");
        if (is_null($StoreDatas) || empty($StoreDatas) || count($StoreDatas) == 0) {
            $this->response(0, "门店不存在");
        }
        $store_name = $StoreDatas[0]["title"];

        $sql = "select GC.id as cate_id,GC.title as cate_name,SUM(GS.num) as stock_num,SUM(ifnull(GS.price,G.sell_price)*GS.num) as g_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "INNER JOIN hii_goods G on G.id=GS.goods_id ";
        $sql .= "INNER JOIN hii_goods_cate GC on GC.id = G.cate_id ";
        $sql .= "where GS.store_id={$store_id} and GS.num>0 ";
        if (!empty($cate_name)) {
            $sql .= " and GC.title like '%{$cate_name}%' ";
        }
        $sql .= "GROUP BY GC.id,GC.title ";
        //echo $sql;exit;
        $list = $GoodsStoreModel->query($sql);
        foreach ($list as $key => $val) {
            $list[$key]["store_name"] = $store_name;
        }
        return $list;
    }

    private function getStoreStockWithCateId($store_id, $cate_id, $usePager,$show_stock_zero=0)
    {
        $shequ_id = 0;
        $StoreModel = M("Store");
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if ($this->isArrayNull($store_datas) != null) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }
        //默认显示有库存的
        $num = '';
        if($show_stock_zero == 0){
                $num  = " and GS.num >0 ";
        }
        $GoodsStoreModel = M("GoodsStore");
        $sql = "select GS.goods_id,G.title as goods_name,GC.title as cate_name,G.bar_code, S.title as store_name,IFNULL(GS.num,0) as stock_num,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(G.sell_price,0)>0 then G.sell_price else 0 END)sell_price,GC.id as cate_id, ";
        $sql .= "GSB.total_num,ifnull(WIV.stock_price,0) as stock_price,ifnull(GS.num*WIV.stock_price,0) as stock_amounts,G.sell_price*GS.num as g_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id  ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_store S on S.id=GS.store_id ";
        $sql .= "left join (select goods_id,SUM(num) as total_num from hii_goods_store GROUP BY goods_id ) GSB on GSB.goods_id=GS.goods_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "where GS.store_id={$store_id} and GC.id={$cate_id}  {$num} ";
        $sql .= " order by GS.goods_id asc ";
        if (I("get.showsql") == "true") {
            echo $sql;
            exit;
        }
        //echo $sql;exit;
        $data = $GoodsStoreModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿

            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
        }

        $result["data"] = $this->isArrayNull($data) == null ? array() : $data;
        return $result;
    }

    private function getGoodsInStockHistory($store_id, $goods_id, $usePager)
    {
        //查找商品主信息
        $GoodsModel = M("Goods");
        $StoreModel = M("Store");
        $StoreInStockDetailModel = M("StoreInStockDetail");

        $goods_data = $GoodsModel->where(" id={$goods_id} ")->limit(1)->select();
        if (is_null($goods_data) || !is_array($goods_data)) {
            $this->response(0, "商品不存在");
        }

        $store_data = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($goods_data) || !is_array($goods_data)) {
            $this->response(0, "门店不存在");
        }
        $store_name = $store_data[0]["title"];

        //入库记录【正常入库和退货入库】
        $sql = "SELECT G.title as goods_name,SISD.g_num,FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "G.sell_price,G.sell_price*SISD.g_num as g_amounts,SIS.s_in_s_id,SIS.s_in_s_sn, ";
        $sql .= "S.title as store_name1,W.w_name as warehouse_name,SY.s_name as supply_name, ";
        $sql .= "SIS.s_in_s_type ";
        $sql .= "from hii_store_in_stock_detail SISD ";
        $sql .= "left join hii_goods G on G.id=SISD.goods_id ";
        $sql .= "left join hii_store_in_stock SIS on SIS.s_in_s_id = SISD.s_in_s_id ";
        $sql .= "left join hii_store S on S.id=SIS.store_id1 ";
        $sql .= "left join hii_warehouse W on W.w_id=SIS.warehouse_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SIS.supply_id ";
        $sql .= "where SISD.goods_id={$goods_id} and SIS.store_id2={$store_id} and SIS.s_in_s_status=1  ";
        $sql .= "union all ";
        $sql .= "select G.title as goods_name,SOOD.g_num,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(G.sell_price,0) as sell_price,ifnull(G.sell_price,0)*SOOD.g_num as g_amounts,SOO.s_o_out_id as s_in_s_id,SOO.s_o_out_sn as s_in_s_sn, ";
        $sql .= "S.title as store_name1,'' as warehouse_name,'' as supply_name, ";
        $sql .= "20 as s_in_s_type ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_store S on S.id=SOO.store_id2 ";
        $sql .= "where SOO.store_id1={$store_id} and SOOD.goods_id={$goods_id} and SOO.s_o_out_status=1   ";

        $sql = "select * from ({$sql}) total where s_in_s_id is not null order by ptime desc  ";

        $data = $StoreInStockDetailModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
        }

        foreach ($data as $key => $val) {
            switch ($val["s_in_s_type"]) {
                //来源:0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
                case 0: {
                    $data[$key]["s_in_s_type_name"] = "仓库出库";
                    $data[$key]["source"] = $val["warehouse_name"];
                };
                    break;
                case 1: {
                    $data[$key]["s_in_s_type_name"] = "门店调拨";
                    $data[$key]["source"] = $val["store_name1"];
                };
                    break;
                case 2: {
                    $data[$key]["s_in_s_type_name"] = "盘盈入库";
                    $data[$key]["source"] = $store_name;
                };
                    break;
                case 3: {
                    $data[$key]["s_in_s_type_name"] = "其他";
                };
                    break;
                case 4: {
                    $data[$key]["s_in_s_type_name"] = "采购";
                    $data[$key]["source"] = $val["supply_name"];
                };
                    break;
                case 5: {
                    $data[$key]["s_in_s_type_name"] = "寄售";
                    $data[$key]["source"] = $val["supply_name"];
                };
                    break;
                case 20: {
                    $data[$key]["s_in_s_type_name"] = "门店退货";
                    $data[$key]["source"] = $val["store_name1"];
                };
                    break;
            }
        }

        $result["list"] = $this->isArrayNull($data) == null ? array() : $data;

        return $result;
    }

    private function getGoodsOutStockHistory($store_id, $goods_id, $usePager)
    {
        /*****************************************
         *  门店出库记录包含：hii_store_out_stock和hii_store_other_out已审核的
         *****************************************/
        //查找商品主信息
        $GoodsModel = M("Goods");
        $StoreModel = M("Store");
        $StoreOutStockDetailModel = M("StoreStockDetail");

        $goods_data = $GoodsModel->where(" id={$goods_id} ")->limit(1)->select();
        if (is_null($goods_data) || !is_array($goods_data)) {
            $this->response(0, "商品不存在");
        }

        $store_data = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if (is_null($goods_data) || !is_array($goods_data)) {
            $this->response(0, "门店不存在");
        }
        $store_name = $store_data[0]["title"];

        $sql = "select SSD.goods_id,G.title as goods_name,SOS.s_out_s_id,SOS.s_out_s_sn,FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,SSD.g_num as g_num, ";
        $sql .= "G.sell_price as sell_price,ifnull(G.sell_price,0)*SSD.g_num as g_amounts,SOS.s_out_s_type,SY.s_name as supply_name, ";
        $sql .= "S.title as store_name1,W.w_name as warehouse_name ";
        $sql .= "from hii_store_stock_detail SSD ";
        $sql .= "left join hii_store_out_stock SOS on SOS.s_out_s_id=SSD.s_out_s_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SOS.supply_id ";
        $sql .= "left join hii_store S on S.id=SOS.store_id1 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOS.warehouse_id ";
        $sql .= "left join hii_goods G on G.id=SSD.goods_id ";
        $sql .= "where SSD.goods_id={$goods_id} and SOS.store_id2={$store_id} and SOS.s_out_s_status=1  ";
        $sql .= "union all ";
        $sql .= "select SOOD.goods_id,G.title as goods_name,SOO.s_o_out_id as s_out_s_id,SOO.s_o_out_sn as s_out_s_sn,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,SOOD.g_num as g_num, ";
        $sql .= "G.sell_price as sell_price,ifnull(G.sell_price,0)*SOOD.g_num as g_amounts,20 as s_out_s_type, ";
        $sql .= "'' as supply_name,S.title as store_name1,W.w_name as warehouse_name ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=SOO.store_id1 ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id2={$store_id} and SOOD.goods_id={$goods_id}  ";

        $sql = "select * from ({$sql}) as total where s_out_s_id is not null order by ptime desc ";
        //echo $sql;exit;
        $data = $StoreOutStockDetailModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
        }

        foreach ($data as $key => $val) {
            //来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它,5.寄售出库
            switch ($val["s_out_s_type"]) {
                case 0: {
                    $data[$key]["s_out_s_type_name"] = "仓库调拨";
                };
                    break;
                case 1: {
                    $data[$key]["s_out_s_type_name"] = "门店调拨";
                    $data[$key]["source"] = $val["store_name1"];
                };
                    break;
                case 3: {
                    $data[$key]["s_out_s_type_name"] = "盘亏";
                    $data[$key]["source"] = $store_name;
                };
                    break;
                case 4: {
                    $data[$key]["s_out_s_type_name"] = "其他";
                };
                    break;
                case 5: {
                    $data[$key]["s_out_s_type_name"] = "寄售";
                };
                    break;
                case 20: {
                    $data[$key]["s_out_s_type_name"] = "退货";
                    if (!is_null($val["store_name1"]) && !empty($val["store_name1"])) {
                        $data[$key]["source"] = $val["store_name1"];
                    } else {
                        $data[$key]["source"] = $val["warehouse_name"];
                    }
                };
                    break;
            }
        }

        $result["list"] = $this->isArrayNull($data) == null ? array() : $data;
        return $result;
    }

    private function getStoreInventoryList($store_id, $si_status, $usePager)
    {
        $StoreInventoryModel = M("StoreInventory");

        $sql = "select SI.si_id,SI.si_sn,FROM_UNIXTIME(SI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SI.g_type,SI.g_nums,SI.remark, ";
        $sql .= "FROM_UNIXTIME(SI.ptime,'%Y-%m-%d %H:%i:%s') as ptime,M2.nickname as padmin_nickname, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,SUM(G.sell_price*SID.g_num) as g_amounts,SI.si_status,SUM(SID.b_num)as b_nums,SUM(GS.num)as gs_nums ";
        $sql .= "from hii_store_inventory SI ";
        $sql .= "left join hii_store_inventory_detail SID on SID.si_id=SI.si_id ";
        $sql .= "left join hii_member M on M.uid=SI.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=SI.padmin_id ";
        $sql .= "left join hii_store S on S.id=SI.store_id ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=SID.goods_id and GS.store_id={$store_id} ";
        $sql .= "where SI.store_id={$store_id} and SI.si_status={$si_status} and SI.si_type=0 ";
        $sql .= "group by SI.si_id,SI.si_sn,SI.ctime,SI.g_type,SI.g_nums,M.nickname,S.title,SI.si_status,SI.remark ";
        if ($si_status == 0) {
            $sql .= "order by SI.ctime desc ";
        } elseif ($si_status == 1) {
            $sql .= "order by SI.ptime desc ";
        }
        $data = $StoreInventoryModel->query($sql);
        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
        }

        foreach ($data as $key => $val) {
            switch ($val["si_status"]) {
                case 0: {
                    $data[$key]["si_status_name"] = "新增";
                    //如果新增显示当前的商品库存数量  如果已审核显示审核时的商品数量
                    $data[$key]['b_nums'] = $val['gb_nums'];
                };
                    break;
                case 1: {
                    $data[$key]["si_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["si_status_name"] = "已作废";
                };
                    break;
            }
        }

        $result["data"] = $this->isArrayNull($data) == null ? array() : $data;
        return $result;
    }

    private function getStoreInventoryDetail($store_id, $si_id)
    {
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $GoodsCateModel = M("GoodsCate");
        /*$all = I("post.all");
        if (is_null($all) || empty($all)) {
            $all = 1;
        }*/
        $status = I("post.status", "");//0：未验收  1：已验收 不填显示全部
        $show_stock_zero = I("post.show_stock_zero", "");//0：不显示  1：显示
        $goods_name = I("post.goods_name");//商品名称
        $bar_code = I("post.bar_code");//商品条码
        $cate_id = I("post.cate_id");

        if ($status === "") {
            $status = 100;
        }
        if ($show_stock_zero === "") {
            $status = 100;
        }

        $result = array();

        //判断盘点单是否存在
        $sql = "select SI.si_id,SI.si_sn,SI.si_status,SI.si_type,FROM_UNIXTIME(SI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SI.admin_id, ";
        $sql .= "FROM_UNIXTIME(SI.etime,'%Y-%m-%d %H:%i:%s') as etime,SI.eadmin_id, ";
        $sql .= "FROM_UNIXTIME(SI.ptime,'%Y-%m-%d %H:%i:%s') as ptime,SI.padmin_id, ";
        $sql .= "SI.store_id,SI.remark,SI.g_type,SI.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as pdmin_nickname,S.title as store_name ";
        $sql .= "from hii_store_inventory SI ";
        $sql .= "left join hii_store S on S.id = SI.store_id ";
        $sql .= "left join hii_member M1 on M1.uid=SI.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=SI.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=SI.padmin_id ";
        $sql .= "where SI.si_id={$si_id} and SI.store_id={$store_id} limit 1 ";

        $store_inventory_data = $StoreInventoryModel->query($sql);
        if ($this->isArrayNull($store_inventory_data) == null) {
            $this->response(0, "盘点单不存在");
        }
        $result["maindata"] = $store_inventory_data[0];

        //查询盘点单子表信息
        $sql = "select SID.si_d_id,SID.si_id,SID.audit_mark,SID.goods_id,ifnull(SID.g_num,0) as g_num,ifnull(SID.remark,'') as remark,ifnull(GS.num,0) as stock_num, ";
        $sql .= "G.title as goods_name,GC.title as cate_name,G.sell_price,P.path as pic_url,ifnull(SID.b_num,0) as b_num,SID.status ";
        $sql .= "from hii_store_inventory_detail SID ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=SID.goods_id ";
        $sql .= "left join hii_picture P on P.id=G.cover_id ";
        $sql .= "where SID.si_id={$si_id}  ";
        /*if ($all == 1) {
            $sql .= " and SID.goods_id in (select goods_id from hii_goods_store where store_id={$store_id} ) ";
        }*/
        if ($status == 0) {
            $sql .= " and SID.status=0 ";
        } elseif ($status == 1) {
            $sql .= " and SID.status=1 ";
        }
        if (!is_null($cate_id) && !empty($cate_id)) {
            $sql .= " and G.cate_id={$cate_id} ";
        }
        if ($show_stock_zero === '0') {
            $sql .= " and GS.num>0 ";
        }
        if (!is_null($goods_name) && !empty($goods_name)) {
            $sql .= " and G.title like '%{$goods_name}%' ";
        }
        if (!is_null($bar_code) && !empty($bar_code)) {
            $sql .= " and G.id in (select goods_id from hii_goods_bar_code where `bar_code`='{$bar_code}' group by goods_id ) ";
        }

        $sql .= " order by SID.goods_id asc ";
        if (I("post.showsql") == true) {
            echo $status . "<br/>";
            echo $sql;
            exit;
        }
        $list = $StoreInventoryDetailModel->query($sql);

        $goods_id_array = array_column($list, "goods_id");
        $goods_id_sql = implode(",", $goods_id_array);
        $bar_code_data = M("GoodsBarCode")->field(" goods_id,bar_code ")->where(" goods_id in ({$goods_id_sql}) ")->select();
        $bar_code_array = array();
        foreach ($bar_code_data as $key => $val) {
            $bar_code_array[$val["goods_id"]][] = array("bar_code" => $val["bar_code"]);
        }

        foreach ($list as $key => $val) {
            $item = $bar_code_array[$val["goods_id"]];
            if ($this->isArrayNull($item) == null) {
                $list[$key]["bar_code"] = array();
            } else {
                $list[$key]["bar_code"] = $item;
            }
        }

        $result["list"] = $list;
        $result["goods_cates"] = $GoodsCateModel->field(" id as cate_id,title as cate_name ")->order(" id asc ")->select();
        return $result;
    }


    /***************
     * 获取当前页
     ***************/
    private function getPageIndex()
    {
        $p = I("post.p");
        return is_null($p) || empty($p) ? 1 : $p;
    }

    /************************
     * 获取搜索日期
     * s_date：开始日期
     * e_date：结束日期
     *****************************/
    private function getDates()
    {
        //时间范围默认30天
        $s_date = I('s_date');
        $e_date = I('e_date');
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $e_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        } else {
            if ($s_date != "") {
                $s_date = strtotime($s_date);
            }
            if ($e_date != "") {
                $e_date = strtotime($e_date);
            }
        }
        $s_date = date('Y-m-d', $s_date);
        $e_date = date('Y-m-d', $e_date);
        return array(
            "s_date" => $s_date,
            "e_date" => $e_date
        );
    }

    /*********
     * 获取每页显示数量，默认15
     */
    private function getPageSize()
    {
        $pcount = I("post.pageSize");
        return is_null($pcount) || empty($pcount) ? 15 : $pcount;
    }

    /*********************
     * 检测数组是否空
     */
    private function isArrayNull($array)
    {
        if (!is_null($array) && !empty($array) && count($array) > 0) {
            return $array;
        } else {
            return null;
        }
    }

}