<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-07
 * Time: 17:11
 */

namespace Erp\Controller;

use Think\Controller;

class StoreStockController extends AdminController
{
    private $seeprice = false;//是否有权查看入库价

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
        $seeprice = $this->checkFunc('purchase_price');
    }

    /**************************************
     * 门店存库接口【仓库库存列表】
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     * 注意：根据商品类别查询hii_goods_store
     * 日期：2017-12-08
     */
    public function index()
    {
        $list = $this->getIndexList();
        $this->response(self::CODE_OK, $list);
    }

    /**********************
     * 查看类别记录接口【查看类别记录】
     * 请求方式：GET
     *           p    当前页    非必填   默认1
     *           pageSize 每页显示数量  默认15
     *           cate_id  商品类别  必须
     *           goods_name 商品名称 否
     * 日期：2017-12-08
     */
    public function indexgoods()
    {
        $result = $this->getIndexGoodsList(true);
        $this->response(self::CODE_OK, $result);
    }

    /************************************
     * 商品入库记录接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     *           goods_id  商品ID  必须
     * 注意：查找入库批次表hii_warehouse_inout表
     * 日期：2017-12-08
     */
    public function goodsInStoreHistory()
    {
        $result = $this->getSingleGoodsInStockHistory(true);
        $this->response(self::CODE_OK, $result);

    }

    /************************************
     * 商品出库记录接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     *           goods_id  商品ID  必须
     * 注意：查找hii_store_out_stock表
     * 日期：2017-12-11
     */
    public function goodsOutStoreHistory()
    {
        $result = $this->getSingleGoodsOutStockHistory(true);
        $this->response(self::CODE_OK, $result);
    }


    /*************************
     * 查看单个入库单信息
     * 请求方式：GET
     * 请求参数：s_in_s_id  入库单ID  必须
     * 日期：2017-12-08
     */
    public function getSingleStoreInStockInfo()
    {
        $s_in_s_id = I("get.s_in_s_id");
        $store_id = $this->_store_id;
        if (is_null($s_in_s_id) || empty($s_in_s_id)) {
            $this->response(0, "请选择要查看的入库单");
        }
        $StoreInStockModel = M("StoreInStock");
        $sql = "select A.s_in_s_id,A.s_in_s_sn,A.s_in_s_status,A.s_in_s_type,A.s_in_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname, ";
        $sql .= "M3.nickname as padmin_nickname,W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2 ";
        $sql .= "form hii_store_in_stock A ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "where A.s_in_s_id={$s_in_s_id} and A.store_id2={$store_id} order by A.s_in_s_id desc limit 1 ";
        $datas = $StoreInStockModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "该信息不存在");
        }
        $result["maindata"] = $datas[0];

        $StoreInStockDetailModel = M("StoreInStockDetail");
        $sql = "select A1.s_in_s_d_id,A1.goods_id,A1.g_price,G.title as goods_name,GC.title as cate_name,G.bar_code,G.sell_price,A1.g_num ";
        $sql .= "from hii_store_in_stock_detail A1 ";
        $sql .= "left join hii_store_in A on A.s_in_id=A1.s_in_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where A1.s_in_s_id={$s_in_s_id} ";

        $list = $StoreInStockDetailModel->query($sql);
        $result["list"] = $list;

        $this->response(self::CODE_OK, $result);
    }

    /********************
     * 获取单个出库单信息
     * 请求方式：GET
     * 请求参数：s_out_s_id  出库单ID  必须
     * 日期：2017-12-11
     */
    public function getSingleStoreOutStockInfo()
    {
        $s_out_s_id = I("get.s_out_s_id");
        if (is_null($s_out_s_id) || empty($s_out_s_id)) {
            $this->response(0, "请选择要查看的出库单");
        }
        $StoreOutStockModel = M("StoreOutStock");
        $StoreStockDetailModel = M("StockStockDetail");

        $sql = "select A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id, FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname as admin_nickname, S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "W.w_name as warehouse_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums,SUM(A1.g_num*G.sell_price) as g_amounts  ";
        $sql .= "from hii_store_out_stock A  ";
        $sql .= "left join hii_store_stock_detail A1 on A1.s_out_s_id=A.s_out_s_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id  ";
        $sql .= "left join hii_store_out SO on SO.s_out_id=A.s_out_id  ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id  ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1  ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2  ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id  ";
        $sql .= "where A.store_id2={$this->_store_id} and A.s_out_s_id={$s_out_s_id}  ";
        $sql .= "group by A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id,A.ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname, S1.title,S2.title, ";
        $sql .= "W.w_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums ";


        //echo $sql;exit;
        $datas = $StoreOutStockModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "该信息不存在");
        }

        $sql = "select A1.goods_id,A1.g_num,A1.g_price,G.title as goods_name,G.bar_code,G.sell_price,ifnull(GS.num,0) as stock_num,GC.title as cate_name, ";
        $sql .= "ifnull(SOD.g_num,0) as  sod_g_num,ifnull(SOD.in_num,0) as sod_in_num,ifnull(SOD.out_num,0) as sod_out_num ";
        $sql .= "from hii_store_stock_detail A1 ";
        $sql .= "left join hii_store_out_detail SOD on SOD.s_out_d_id=A1.s_out_d_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "where A1.s_out_s_id={$s_out_s_id} order by s_out_s_d_id desc ";
        //echo $sql;exit;
        $list = $StoreStockDetailModel->query($sql);

        $data = $datas[0];
        switch ($data["s_out_s_status"]) {
            case 0: {
                $data["s_out_s_status_name"] = "新增";
            };
                break;
            case 1: {
                //$data["s_out_s_status_name"] = "已审核转出库";
                $data["s_out_s_status_name"] = "已审核";
            };
                break;
            case 2: {
                $data["s_out_s_status_name"] = "已拒绝";
            };
                break;
            case 3: {
                $data["s_out_s_status_name"] = "部分拒绝";
            };
                break;
        }
        switch ($data["s_out_s_type"]) {
            case 0: {
                $data["s_out_s_type_name"] = "仓库调拨";
            };
                break;
            case 1: {
                $StoreToStoreModel = M("StoreToStore");
                $data["s_out_s_type_name"] = "门店申请";
                $tmp = $StoreToStoreModel->query("select s_t_s_sn from hii_store_to_store where s_t_s_id in ({$data["s_r_id"]}) ");
                $str = "";
                foreach ($tmp as $k => $v) {
                    $str .= $v["s_t_s_sn"] . ",";
                }
                $data["rel_orders"] = !empty($str) ? substr($str, 0, strlen($str) - 1) : $str;
            };
                break;
            case 3: {
                $data["s_out_s_type_name"] = "盘亏出库";
            };
                break;
            case 4: {
                $data["s_out_s_type_name"] = "其它";
            };
                break;
        }
        $result = array();
        $result["maindata"] = $data;
        $result["list"] = $list;

        $this->response(self::CODE_OK, $result);
    }

    /*****************
     * 导出商品种类库存Excel
     * 请求方式：GET
     * 请求参数：cate_name  种类名称  非必填
     * 注意：
     * 日期：2017-12-12
     */
    public function exportIndexExcel()
    {
        $list = $this->getIndexList();
        ob_clean;
        $title = '门店库存';
        $fname = './Public/Excel/StoreStock_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreStockModel();
        $printfile = $printmodel->createIndexListExcel($list, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**************************
     * 导出某个商品种类库存Excel
     * 请求方式：GET
     * 请求参数：goods_name 商品名称 非必填
     * 注意：
     * 日期：2017-12-12
     */
    public function exportIndexGoodsListExcel()
    {
        $result = $this->getIndexGoodsList(false);
        $list = $result["data"];
        ob_clean;
        $title = '门店库存';
        $fname = './Public/Excel/StoreStock_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreStockModel();
        $printfile = $printmodel->createIndexGoodsListExcel($list, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /********************************
     * 导出单个商品入库记录
     * 请求方式：GET
     * 请求参数：goods_id  商品ID  必须
     * 注意：
     * 日期：2017-12-13
     */
    public function exportSingleGoodsInStockHistoryExcel()
    {
        $result = $this->getSingleGoodsInStockHistory(false);
        ob_clean;
        $title = $result["s_date"] . '>>>' . $result["e_date"] . ' ' . $result["maindata"]["goods_name"] . '入库记录';
        $fname = './Public/Excel/GoodsInStockHistory_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreStockModel();
        $printfile = $printmodel->createGoodsInStockHistoryExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /********************************
     * 导出单个商品出库记录
     * 请求方式：GET
     * 请求参数：goods_id  商品ID  必须
     * 注意：
     * 日期：2017-12-13
     */
    public function exportSingleGoodsOutStockHistoryExcel()
    {
        $result = $this->getSingleGoodsOutStockHistory(false);
        ob_clean;
        $title = $result["s_date"] . '>>>' . $result["e_date"] . ' ' . $result["maindata"]["goods_name"] . '出库记录';
        $fname = './Public/Excel/GoodsOutStockHistory_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreStockModel();
        $printfile = $printmodel->createGoodsOutStockHistoryExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /************************************
     * 通过bar_code 或者 goods_id获取商品信息
     * 请求方式：GET
     * 请求参数：bar_code   商品条码   否
     *           goods_id   商品ID     否
     * 日期：2018-01-20
     */
    public function getgoods()
    {
        $bar_code = I("get.bar_code");
        $goods_id = I("get.goods_id");
        $temp_type = 8;
        $admin_id = UID;
        $GoodsModel = M("Goods");
        $RequestTempModel = M("RequestTemp");
        if (empty($bar_code) && empty($goods_id)) {
            $this->response(0, "请提供商品条码或商品ID");
        }
        if (!empty($bar_code)) {
            $datas = $GoodsModel->where(" bar_code='{$bar_code}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $goods_id = $datas[0]["id"];
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        } elseif (!empty($goods_id)) {
            $datas = $GoodsModel->where(" id='{$goods_id}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        }
    }

    private function getIndexList()
    {
        $store_id = $this->_store_id;
        $StoreModel = M("Store");
        $GoodsStoreModel = M("GoodsStore");
        $cate_name = I("get.cate_name");
        $StoreDatas = $StoreModel->query("select title from hii_store where id={$store_id} limit 1 ");
        if (is_null($StoreDatas) || empty($StoreDatas) || count($StoreDatas) == 0) {
            $this->response(0, "请选择门店");
        }
        $store_name = $StoreDatas[0]["title"];

        $can_store_id_array = $this->getCanStoreIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " GS.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ( {$shequ_where} ) ";
        }

        //SUM(ifnull(GS.price,G.sell_price)*GS.num) as g_amounts
        $sql = "select GC.id as cate_id,GC.title as cate_name,SUM(GS.num) as stock_num, ";
        $sql .= "SUM(GS.num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "INNER JOIN hii_goods G on G.id=GS.goods_id ";
        $sql .= "INNER JOIN hii_goods_cate GC on GC.id = G.cate_id ";
        $sql .= "where GS.store_id={$store_id} {$shequ_where} and GS.num>0 ";
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

    private function getIndexGoodsList($usePager)
    {
        $cate_id = I("get.cate_id");
        $goods_name = I("get.goods_name");
        $store_id = $this->_store_id;

        $shequ_id = 0;
        $StoreModel = M("Store");
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if ($this->isArrayNull($store_datas) != null) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }

        $can_store_id_array = $this->getCanStoreIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " GS.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ( {$shequ_where} ) ";
        }
        $GoodsStoreModel = M("GoodsStore");
        $sql = "select GS.goods_id,G.title as goods_name,GC.title as cate_name,G.bar_code, S.title as store_name,IFNULL(GS.num,0) as stock_num,G.sell_price,GC.id as cate_id, ";
        $sql .= "GSB.total_num,ifnull(WIV.stock_price,0) as stock_price,ifnull(GS.num*WIV.stock_price,0) as stock_amounts, ";
        $sql .= "SUM(GS.num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id  ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_store S on S.id=GS.store_id ";
        $sql .= "left join (select goods_id,SUM(num) as total_num from hii_goods_store GROUP BY goods_id ) GSB on GSB.goods_id=GS.goods_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "where GS.store_id={$store_id} {$shequ_where} ";
        if (!is_null($cate_id) && !empty($cate_id)) {
            $sql .= " and GC.id={$cate_id} ";
        }
        if (!is_null($goods_name) && !empty($goods_name)) {
            $sql .= " and G.title like '%{$goods_name}%' ";
        }
        $sql .= "group by GS.goods_id,G.title,GC.title,G.bar_code,S.title,GS.num,G.sell_price,GC.id,GSB.total_num,WIV.stock_price ";
        $sql .= "order by GS.goods_id asc ";
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
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿

            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
            //判断超级管理员  页面的库存流水记录 只能超级管理员查看
            if (in_array(1, $this->group_id)) {
                    $chaojiroot = 1;
              }else{
                $chaojiroot = 0;
            }
            foreach ($data as $key=>$val){
                $data[$key]['chaojiroot'] = $chaojiroot;
            }
        }

        $result["data"] = $this->isArrayNull($data);
        return $result;
    }

    private function getSingleGoodsInStockHistory($usePager)
    {
        $goods_id = I("get.goods_id");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        $store_id = $this->_store_id;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        //查找商品主信息
        $GoodsModel = M("Goods");
        $StoreInStockDetailModel = M("StoreInStockDetail");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();

        //商品主要信息
        /*
        $sql = "select G.id as goods_id,G.title as goods_name,G.bar_code, GC.title as cate_name,G.sell_price,GS.num,ifnull(WS.total_num,0) as total_num, ";
        $sql .= "WIV.stock_price as stock_price,WIV.stock_price*GS.num as stock_amounts,G.sell_price*GS.num as sell_amounts ";
        $sql .= "from hii_goods_store GS ";
        //$sql .= "left join (SELECT SUM(num) as total_num,goods_id from hii_goods_store where goods_id={$goods_id} GROUP BY goods_id ) GSB on GSB.goods_id=GS.goods_id ";
        $sql .= "left join (select SUM(num) as total_num,goods_id from hii_warehouse_stock where goods_id={$goods_id} group by goods_id ) WS on WS.goods_id=GS.goods_id ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id ";
        $sql .= "where GS.goods_id={$goods_id} and GS.store_id={$store_id} ";
        $sql .= "group by G.id,G.title,G.bar_code,GC.title,G.sell_price,GS.num,WS.total_num,WIV.stock_price ";
        */
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " and GS.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }

        $sql = "select  G.id as goods_id,G.title as goods_name,G.bar_code, GC.title as cate_name,G.sell_price,ifnull(GS.num,0) as num, ";
        $sql .= "ifnull(G.sell_price,0)*ifnull(GS.num,0) as sell_amounts,SUM(SISD.g_num) as in_nums,SUM(SISD.g_num*SISD.g_price) as in_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id = GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_store_in_stock SIS on SIS.store_id2=GS.store_id and SIS.s_in_s_status=1 ";
        $sql .= "left join hii_store_in_stock_detail SISD on SISD.s_in_s_id=SIS.s_in_s_id and SISD.goods_id={$goods_id} ";
        $sql .= "where GS.store_id={$store_id} {$shequ_where} and GS.goods_id={$goods_id} ";
        $sql .= "group by G.id,G.title,G.bar_code,GC.title,G.sell_price,GS.num ";

        //echo $sql;exit;

        $datas = $GoodsModel->query($sql);
        if (is_null($datas) || !is_array($datas)) {
            $this->response(0, "不存在该商品");
        }
        $main = $datas[0];

        $main["stock_price"] = $main["in_amounts"] / $main["in_nums"];
        $main["stock_amounts"] = $main["num"] * $main["stock_price"];

        $shequ_where1 = "";
        $shequ_where2 = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where1 .= " SIS.store_id1 in (" . implode(",", $can_store_id_array) . ") or SIS.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where2 .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . "  SIS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . "  SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where1)) {
            $shequ_where1 = " and ( {$shequ_where1} ) ";
        }
        if (!empty($shequ_where2)) {
            $shequ_where2 = " and ( {$shequ_where2} ) ";
        }

        //入库记录
        $sql = "SELECT SUM(SISD.g_num)g_num,'' as g_price,FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "'' as in_amounts,G.sell_price,SUM(G.sell_price*SISD.g_num) as g_amounts,SIS.s_in_s_id,SIS.s_in_s_sn, ";
        $sql .= "S.title as store_name1,W.w_name as warehouse_name,SY.s_name as supply_name, ";
        $sql .= "SIS.s_in_s_type,1 type ";
        $sql .= "from hii_store_in_stock_detail SISD ";
        $sql .= "left join hii_goods G on G.id=SISD.goods_id ";
        $sql .= "left join hii_store_in_stock SIS on SIS.s_in_s_id = SISD.s_in_s_id ";
        $sql .= "left join hii_store S on S.id=SIS.store_id1 ";
        $sql .= "left join hii_warehouse W on W.w_id=SIS.warehouse_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SIS.supply_id ";
        $sql .= "where SISD.goods_id={$goods_id} and SIS.store_id2={$store_id} {$shequ_where1} and SIS.s_in_s_status=1 group by SIS.s_in_s_id ";
        $sql .= "union all ";
        $sql .= "select SUM(SOOD.g_num)g_num,'' as g_price,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,'' as in_amounts, ";
        $sql .= "ifnull(G.sell_price,0) as sell_price,SUM(ifnull(G.sell_price,0)*SOOD.g_num) as g_amounts,SOO.s_o_out_id as s_in_s_id,SOO.s_o_out_sn as s_in_s_sn, ";
        $sql .= "S.title as store_name1,'' as warehouse_name,'' as supply_name,case SOO.s_o_out_type when 5 then 21 when 1 then 22 when 0 then 23  else '' end as s_in_s_type,2 type ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_store S on S.id=SOO.store_id2 ";
        $sql .= "where SOO.store_id1={$store_id} {$shequ_where2} and SOOD.goods_id={$goods_id} and SOO.s_o_out_status=1 group by SOO.s_o_out_id ";
        $sql = "select * from ({$sql}) total where s_in_s_id is not null order by ptime desc  ";

        //echo $sql;exit;

        $data = $StoreInStockDetailModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }

        foreach ($data as $key => $val) {
            switch ($val["s_in_s_type"]) {
                //来源:0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
                case 0: {
                    $data[$key]["s_in_s_type_name"] = "仓库出库";
                };
                    break;
                case 1: {
                    $data[$key]["s_in_s_type_name"] = "门店调拨";
                };
                    break;
                case 2: {
                    $data[$key]["s_in_s_type_name"] = "盘盈入库";
                };
                    break;
                case 3: {
                    $data[$key]["s_in_s_type_name"] = "其他";
                };
                    break;
                case 4: {
                    $data[$key]["s_in_s_type_name"] = "采购";
                };
                    break;
                case 5: {
                    $data[$key]["s_in_s_type_name"] = "寄售";
                };
                    break;
                case 20: {
                    $data[$key]["s_in_s_type_name"] = "门店退货";
                };
                    break;
                case 21: {
                    $data[$key]["s_in_s_type_name"] = "仓库拒绝返仓";
                };
                    break;
                case 22: {
                    $data[$key]["s_in_s_type_name"] = "门店调拨拒绝";
                };
                    break;
                case 23: {
                    $data[$key]["s_in_s_type_name"] = "仓库发货拒绝";
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["maindata"] = $main;
        $result["seeprice"] = $this->seeprice;
        $result["list"] = $this->isArrayNull($data);

        return $result;
    }

    private function getSingleGoodsOutStockHistory($usePager)
    {
        /*****************************************
         *  门店出库记录包含：hii_store_out_stock和hii_store_other_out已审核的
         *****************************************/
        $goods_id = I("get.goods_id");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        $store_id = $this->_store_id;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        //查找商品主信息
        $GoodsModel = M("Goods");
        $StoreOutStockDetailModel = M("StoreStockDetail");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        /*
        $sql = "select G.id as goods_id,G.title as goods_name,G.bar_code, GC.title as cate_name,G.sell_price,GS.num,GSB.total_num, ";
        $sql .= "WIV.stock_price as stock_price,WIV.stock_price*GS.num as stock_amounts,G.sell_price*GS.num as sell_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join (SELECT SUM(num) as total_num,goods_id from hii_goods_store where goods_id={$goods_id} GROUP BY goods_id ) GSB on GSB.goods_id=GS.goods_id ";
        $sql .= "left join hii_goods G on G.id=GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=GS.goods_id ";
        $sql .= "where GS.goods_id={$goods_id} and GS.store_id={$store_id} ";
        $sql .= "group by G.id,G.title,G.bar_code,GC.title,G.sell_price,GS.num,GSB.total_num,WIV.stock_price ";
        */
        //echo $sql;exit;

        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " and GS.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }

        $sql = "select  G.id as goods_id,G.title as goods_name,G.bar_code, GC.title as cate_name,G.sell_price,ifnull(GS.num,0) as num, ";
        $sql .= "ifnull(G.sell_price,0)*ifnull(GS.num,0) as sell_amounts,SUM(SISD.g_num) as in_nums,SUM(SISD.g_num*SISD.g_price) as in_amounts ";
        $sql .= "from hii_goods_store GS ";
        $sql .= "left join hii_goods G on G.id = GS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_store_in_stock SIS on SIS.store_id2=GS.store_id and SIS.s_in_s_status=1 ";
        $sql .= "left join hii_store_in_stock_detail SISD on SISD.s_in_s_id=SIS.s_in_s_id and SISD.goods_id={$goods_id} ";
        $sql .= "where GS.store_id={$store_id} {$shequ_where} and GS.goods_id={$goods_id} ";
        $sql .= "group by G.id,G.title,G.bar_code,GC.title,G.sell_price,GS.num ";

        $datamain = $GoodsModel->query($sql);
        if (is_null($datamain) || !is_array($datamain)) {
            $this->response(0, "不存在该商品");
        }
        $main = $datamain[0];
        $main["stock_price"] = $main["in_amounts"] / $main["in_nums"];
        $main["stock_amounts"] = $main["num"] * $main["stock_price"];


        $shequ_where1 = "";
        $shequ_where2 = "";
        $shequ_where3 = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where1 .= " SOS.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOS.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where2 .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where3 .= " sb.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . "  SOS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . "  SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where3 .= (!empty($shequ_where3) ? "or" : "") . "  sb.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . "  SOS.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
        }
        if (!empty($shequ_where1)) {
            $shequ_where1 = " and ( {$shequ_where1} ) ";
        }
        if (!empty($shequ_where2)) {
            $shequ_where2 = " and ( {$shequ_where2} ) ";
        }
        if (!empty($shequ_where3)) {
            $shequ_where3 = " and ( {$shequ_where3} ) ";
        }
        $sql = "select SOS.s_out_s_id,SOS.s_out_s_sn,FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, SUM(ifnull(SSD.g_num,0)) as out_num,'' as g_price, ";
        $sql .= "'' as out_amounts, G.sell_price as sell_price,SUM(ifnull(G.sell_price,0)*SSD.g_num) as g_amounts,SOS.s_out_s_type,SY.s_name as supply_name, ";
        $sql .= "S.title as store_name1,W.w_name as warehouse_name,1 as type ";
        $sql .= "from hii_store_stock_detail SSD ";
        $sql .= "left join hii_store_out_stock SOS on SOS.s_out_s_id=SSD.s_out_s_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SOS.supply_id ";
        $sql .= "left join hii_store S on S.id=SOS.store_id1 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOS.warehouse_id ";
        $sql .= "left join hii_goods G on G.id=SSD.goods_id ";
        $sql .= "where SSD.goods_id={$goods_id} {$shequ_where1} and SOS.store_id2={$store_id} and SOS.s_out_s_status=1 group by SOS.s_out_s_id ";
        //$sql .= "order by SSD.s_out_s_d_id desc  ";   //仓库报损单没有出库
        /*$sql .= "union all ";
        $sql .= "select SOO.s_o_out_id as s_out_s_id,SOO.s_o_out_sn as s_out_s_sn,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,SOOD.g_num as out_num, ";
        $sql .= "'' as g_price,'' as out_amounts,G.sell_price as sell_price,ifnull(G.sell_price,0)*SOOD.g_num as g_amounts,20 as s_out_s_type, ";
        $sql .= "'' as supply_name,S.title as store_name1,W.w_name as warehouse_name ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=SOO.store_id1 ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id2={$store_id} {$shequ_where2} and SOOD.goods_id={$goods_id}  ";*/
        $sql .= "union all ";
        $sql .= "select sb.s_back_id as s_out_s_id,sb.s_back_sn as s_out_s_sn,FROM_UNIXTIME(sb.ptime,'%Y-%m-%d %H:%i:%s') as ptime,SUM(sbd.g_num) as out_num, ";
        $sql .= "'' as g_price,'' as out_amounts,G.sell_price as sell_price,SUM(ifnull(G.sell_price,0)*sbd.g_num) as g_amounts,21 as s_out_s_type, ";
        $sql .= "'' as supply_name,S.title as store_name1,W.w_name as warehouse_name,2 type ";
        $sql .= "from hii_store_back sb ";
        $sql .= "left join hii_store_back_detail sbd on sb.s_back_id=sbd.s_back_id ";
        $sql .= "left join hii_goods G on G.id=sbd.goods_id ";
        $sql .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
        $sql .= "left join hii_store S on S.id=sb.store_id ";
        $sql .= "where sb.s_back_status=1 and sb.store_id={$store_id} {$shequ_where3} and sbd.goods_id={$goods_id} group by sb.s_back_id  ";

        $sql = "select * from ({$sql}) as total where s_out_s_id is not null order by ptime desc ";

        //echo $sql;exit;

        $data = $StoreOutStockDetailModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿

            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
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
                };
                    break;
                case 3: {
                    $data[$key]["s_out_s_type_name"] = "盘亏";
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
                };
                    break;
                case 21: {
                    $data[$key]["s_out_s_type_name"] = "返仓";
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["maindata"] = $main;
        $result["list"] = $this->isArrayNull($data);

        return $result;
    }

    /***************
     * 获取当前页
     ***************/
    private function getPageIndex()
    {
        $p = I("get.p");
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
        $pcount = I("get.pageSize");
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


    private function getCanStoreIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_store_id_array = array();
        $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
        if ($store) {
            //$this->storewhere = " And store_id in (" . implode(',', array_column($store, 'id')) . ")";
            $can_store_id_array = array_column($store, "id");
        }
        return $can_store_id_array;
    }

    private function getCanWarehouseIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_warehouse_id_array = array();
        $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
        if ($warehouse) {
            //$this->warehousewhere = " And warehosue_id in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            $can_warehouse_id_array = array_column($warehouse, "w_id");
        }
        return $can_warehouse_id_array;
    }

    private function getCanSupplyIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_supply_id_array = array();
        $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
        if ($supply) {
            //$this->supplywhere = " And supply_id in (" . implode(',', array_column($warehouse, 's_id')) . ")";
            $can_supply_id_array = array_column($supply, "s_id");
        }
        return $can_supply_id_array;
    }
}