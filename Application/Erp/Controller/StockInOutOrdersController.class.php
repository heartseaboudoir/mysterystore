<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-18
 * Time: 16:25
 * 出入库单据流水
 */

namespace Erp\Controller;

use Think\Controller;

class StockInOutOrdersController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
    }

    /*************************
     * 出入库单据流水
     * 请求方式：GET
     * 请求参数：
     */
    public function index()
    {
        exit();
        $result = $this->getIndexList(true);
        $this->response(self::CODE_OK, $result);
    }

    /*********************
     *仓库出入库单据流水
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function warehouseinoutstockorders()
    {
        $result = $this->getWarehouseInoutStockOrders(true);
        $this->response(self::CODE_OK, $result);
    }

    /*********************
     *门店出入库单据流水
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function storeinoutstockorders()
    {
        $result = $this->getStoreInoutStockOrders(true);
        $this->response(self::CODE_OK, $result);
    }

    /******************************
     * 仓库商品出入库流水记录
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function warehousegoodsinoutstockorders()
    {
        $result = $this->getWarehouseGoodsInoutStockOrders(true);
        $this->response(self::CODE_OK, $result);
    }

    /******************************
     * 仓库商品出入库流水记录
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function storegoodsinoutstockorders()
    {
        $result = $this->getStoreGoodsInoutStockOrders(true);
        $this->response(self::CODE_OK, $result);
    }

    public function exportWarehouseInoutStockOrders()
    {
        $result = $this->getWarehouseInoutStockOrders(false);
        $s_date = $result["s_date"];
        $e_date = $result["e_date"];
        $data = $result["data"];
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '仓库出入库流水';
        $fname = './Public/Excel/StockInoutOrders_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StockInoutOrdersModel();
        $printfile = $printmodel->createWarehouseInoutStockOrdersExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    public function exportStoreInoutStockOrders()
    {
        $result = $this->getStoreInoutStockOrders(false);
        $s_date = $result["s_date"];
        $e_date = $result["e_date"];
        $data = $result["data"];
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店出入库流水';
        $fname = './Public/Excel/StockInoutOrders_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StockInoutOrdersModel();
        $printfile = $printmodel->createStoreInoutStockOrdersExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    public function exportWarehouseGoodsInoutStockOrders()
    {
        $result = $this->getWarehouseGoodsInoutStockOrders(false);
        $s_date = $result["s_date"];
        $e_date = $result["e_date"];
        $data = $result["data"];
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '仓库商品出入库流水';
        $fname = './Public/Excel/StockInoutOrders_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StockInoutOrdersModel();
        $printfile = $printmodel->createWarehouseGoodsInoutStockOrdersExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    public function exportStoreGoodsInoutStockOrders()
    {
        $result = $this->getStoreGoodsInoutStockOrders(false);
        $s_date = $result["s_date"];
        $e_date = $result["e_date"];
        $data = $result["data"];
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店商品出入库流水';
        $fname = './Public/Excel/StockInoutOrders_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StockInoutOrdersModel();
        $printfile = $printmodel->createStoreGoodsInoutStockOrdersExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    private function getIndexList($usePager)
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $warehouse_id = $this->_warehouse_id;
        $store_id = $this->_store_id;

        $s_type_array = array(
            1 => array(
                0 => "采购",
                1 => "门店退货",
                2 => "仓库调拨",
                3 => "盘盈",
                4 => "门店返仓",
                5 => "其他"
            ),
            2 => array(
                0 => "仓库调拨",
                1 => "门店申请",
                3 => "盘亏",
                4 => "其他"
            ),
            3 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            4 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            5 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘盈",
                3 => "其他",
                4 => "采购",
                5 => "寄售"
            ),
            6 => array(
                0 => "仓库调拨",
                1 => "门店调拨",
                3 => "盘亏",
                4 => "其他",
                5 => "寄售"
            ),
            7 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            ),
            8 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            )
        );

        $sql = "";
        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "select WIS.w_in_s_id as id,WIS.w_in_s_sn as sn,1 as `type`,'仓库入库' as `type_name`,WIS.w_in_s_type as s_type,WIS.g_type,WIS.g_nums,FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "W.w_name as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "SY.s_name as fahuo_supply_name,W2.w_name as fahuo_warehouse_name,'' as fahuo_store_name ";
        $sql .= "from hii_warehouse_in_stock WIS ";
        $sql .= "left join hii_warehouse W on W.w_id=WIS.warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY.s_id=WIS.supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WIS.w_in_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WI.warehouse_id2 ";//发货仓库
        $sql .= "where WIS.w_in_s_status=1 and WIS.warehouse_id={$warehouse_id} and FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //正常出库
        $sql .= "select WOS.w_out_s_id as id,WOS.w_out_s_sn as sn,2 as `type`,'仓库出库' as `type_name`,WOS.w_out_s_type as s_type,WOS.g_type,WOS.g_nums,FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "W.w_name as ruku_warehouse_name,S.title as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,W2.w_name as fahuo_warehouse_name,'' as fahuo_store_name ";
        $sql .= "from hii_warehouse_out_stock WOS ";
        $sql .= "left join hii_warehouse W on W.w_id=WOS.warehouse_id1 ";//入库仓库
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOS.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_store S on S.id=WOS.store_id ";//入库门店
        $sql .= "where WOS.w_out_s_status=1 and WOS.warehouse_id2={$warehouse_id} and FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //被退货入库
        $sql .= "select WOO.w_o_out_id as id,WOO.w_o_out_sn as sn,3 as `type`,'仓库被退货入库' as `type_name`,WOO.w_o_out_type as s_type,WOO.g_type,WOO.g_nums,FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "W.w_name as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,W2.w_name as fahuo_warehouse_name,S.title as fahuo_store_name ";
        $sql .= "from hii_warehouse_other_out WOO ";
        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id2 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=WOO.store_id ";
        $sql .= "where WOO.w_o_out_status=1 and WOO.warehouse_id2={$warehouse_id} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "union all ";
        //退货出库
        $sql .= "select WOO.w_o_out_id as id,WOO.w_o_out_sn as sn,4 as `type`,'仓库退货出库' as `type_name`,WOO.w_o_out_type as s_type,WOO.g_type,WOO.g_nums,FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "W.w_name as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,W2.w_name as fahuo_warehouse_name,S.title as fahuo_store_name ";
        $sql .= "from hii_warehouse_other_out WOO ";
        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id2 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=WOO.store_id ";
        $sql .= "where WOO.w_o_out_status=1 and WOO.warehouse_id={$warehouse_id} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "union all ";
        /************************************** 仓库出入库 end ******************************************************************/

        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "select SIS.s_in_s_id as id,SIS.s_in_s_sn as sn,5 as `type`,'门店入库' as `type_name`,SIS.s_in_s_type as s_type,SIS.g_type,SIS.g_nums,FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "'' as ruku_warehouse_name,S2.title as ruku_store_name, ";
        $sql .= "SY.s_name as fahuo_supply_name,W.w_name as fahuo_warehouse_name,S1.title as fahuo_store_name ";
        $sql .= "from hii_store_in_stock SIS ";
        $sql .= "left join hii_store S2 on S2.id=SIS.store_id2  ";
        $sql .= "left join hii_supply SY on SY.s_id=SIS.supply_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SIS.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SIS.store_id1 ";
        $sql .= "where SIS.s_in_s_status=1 and SIS.store_id2={$store_id} and FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "union all ";
        //正常出库
        $sql .= "select SOS.s_out_s_id as id,SOS.s_out_s_sn as sn,6 as `type`,'门店出库' as `type_name`,SOS.s_out_s_type as s_type,SOS.g_type,SOS.g_nums,FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "W.w_name as ruku_warehouse_name,S1.title as ruku_store_name, ";
        $sql .= "SY.s_name as fahuo_supply_name,'' as fahuo_warehouse_name,S2.title as fahuo_store_name ";
        $sql .= "from hii_store_out_stock SOS ";
        $sql .= "left join hii_warehouse W on W.w_id=SOS.warehouse_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SOS.supply_id ";
        $sql .= "left join hii_store S1 on S1.id=SOS.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOS.store_id2 ";
        $sql .= "where SOS.s_out_s_status=1 and SOS.store_id2={$store_id} and FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "union all ";
        //被退货入库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,7 as `type`,'门店被退货入库' as `type_name`,SOO.s_o_out_type as s_type,SOO.g_type,SOO.g_nums,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,  ";
        $sql .= "'' as ruku_warehouse_name,S2.title as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,W.w_name as fahuo_warehouse_name,S1.title as fahuo_store_name ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id2={$store_id} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "union all ";
        //退货出库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,8 as `type`,'门店退货出库' as `type_name`,SOO.s_o_out_type as s_type,SOO.g_type,SOO.g_nums,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "W.w_name as ruku_warehouse_name,S2.title as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,S1.title as fahuo_store_name ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id1={$store_id} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";


        /************************************** 门店出入库 end ******************************************************************/

        //echo $sql;
        //exit;

        $sql = "select * from ({$sql}) total order by ptime DESC ";

        $data = M()->query($sql);

        //dump($data);exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            $data[$key]["s_type_name"] = $s_type_array[$val["type"]][$val["s_type"]];
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        if ($usePager) {
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        $result["data"] = $this->isArrayNull($data);
        return $result;

    }

    private function getWarehouseInoutStockOrders($usePager)
    {
        $this->check_warehouse();
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $warehouse_id = $this->_warehouse_id;

        $s_type_array = array(
            1 => array(
                0 => "采购",
                1 => "门店退货",
                2 => "仓库调拨",
                3 => "盘盈",
                4 => "门店返仓",
                5 => "其他"
            ),
            2 => array(
                0 => "仓库调拨",
                1 => "门店申请",
                3 => "盘亏",
                4 => "其他",
                5 => "直接发货",
            ),
            3 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            4 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            5 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘盈",
                3 => "其他",
                4 => "采购",
                5 => "寄售"
            ),
            6 => array(
                0 => "仓库调拨",
                1 => "门店调拨",
                3 => "盘亏",
                4 => "其他",
                5 => "寄售"
            ),
            7 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            ),
            8 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            )
        );

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where1 = "";
        $shequ_where2 = "";
        $shequ_where3 = "";
        $shequ_where4 = "";

        if (count($can_store_id_array) > 0) {
            $shequ_where1 .= "";
            $shequ_where2 .= " WOS.store_id in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where3 .= " WOO.store_id in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where4 .= " WOO.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where1 .= " WIS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . " WOS.warehouse_id1 in (" . implode(",", $can_warehouse_id_array) . ") or WOS.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where3 .= (!empty($shequ_where3) ? "or" : "") . " WOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") or WOO.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where4 .= (!empty($shequ_where4) ? "or" : "") . " WOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") or WOO.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . "  WIS.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
            $shequ_where2 .= "";
            $shequ_where3 .= "";
            $shequ_where4 .= "";
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
        if (!empty($shequ_where4)) {
            $shequ_where4 = " and ( {$shequ_where4} ) ";
        }

        $sql = "";
        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "select WIS.w_in_s_id as id,WIS.w_in_s_sn as sn,1 as `type`,'仓库入库' as `type_name`,WIS.w_in_s_type as s_type,WIS.g_type,WIS.g_nums, ";
        $sql .= "FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,'' as fahuo_store_name,WIS.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_warehouse_in_stock WIS ";
        $sql .= "left join hii_warehouse W on W.w_id=WIS.warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY.s_id=WIS.supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WIS.w_in_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WI.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_member M on M.uid=WIS.admin_id ";
        $sql .= "where WIS.w_in_s_status=1 {$shequ_where1} and WIS.w_in_s_type<>4 and WIS.warehouse_id={$warehouse_id} and FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //门店返仓入库
        $sql .= "select WIS.w_in_s_id as id,WIS.w_in_s_sn as sn,1 as `type`,'门店返仓入库' as `type_name`,WIS.w_in_s_type as s_type,WIS.g_type,WIS.g_nums, ";
        $sql .= "FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,S.title as fahuo_store_name,WIS.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_warehouse_in_stock WIS ";
        $sql .= "left join hii_warehouse W on W.w_id=WIS.warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY.s_id=WIS.supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WIS.w_in_id ";
        $sql .= "left join hii_store S on S.id=WI.store_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WI.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_member M on M.uid=WIS.admin_id ";
        $sql .= "where WIS.w_in_s_status=1 {$shequ_where1} and WIS.w_in_s_type=4 and WIS.warehouse_id={$warehouse_id} and FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //正常出库
        $sql .= "select WOS.w_out_s_id as id,WOS.w_out_s_sn as sn,2 as `type`,'仓库出库' as `type_name`,WOS.w_out_s_type as s_type,WOS.g_type,WOS.g_nums, ";
        $sql .= "FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,'' as fahuo_store_name,WOS.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_warehouse_out_stock WOS ";
        $sql .= "left join hii_warehouse W on W.w_id=WOS.warehouse_id1 ";//入库仓库
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOS.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_store S on S.id=WOS.store_id ";//入库门店
        $sql .= "left join hii_member M on M.uid=WOS.admin_id ";
        $sql .= "where WOS.w_out_s_status=1 and WOS.warehouse_id2={$warehouse_id} {$shequ_where2} and FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //被退货入库
        $sql .= "select WOO.w_o_out_id as id,WOO.w_o_out_sn as sn,3 as `type`,'仓库被退货入库' as `type_name`,WOO.w_o_out_type as s_type,WOO.g_type,WOO.g_nums, ";
        $sql .= "FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,ifnull(S.title,'') as fahuo_store_name,WOO.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_warehouse_other_out WOO ";
        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id2 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=WOO.store_id ";
        $sql .= "left join hii_member M on M.uid=WOO.admin_id ";
        $sql .= "where WOO.w_o_out_status=1 and WOO.warehouse_id2={$warehouse_id} {$shequ_where3} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
//        $sql .= "union all ";
//        //退货出库
//        $sql .= "select WOO.w_o_out_id as id,WOO.w_o_out_sn as sn,4 as `type`,'仓库退货出库' as `type_name`,WOO.w_o_out_type as s_type,WOO.g_type,WOO.g_nums, ";
//        $sql .= "FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
//        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
//        $sql .= "'' as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,ifnull(S.title,'') as fahuo_store_name,WOO.remark,M.nickname as admin_nickname ";
//        $sql .= "from hii_warehouse_other_out WOO ";
//        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id2 ";
//        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id ";
//        $sql .= "left join hii_store S on S.id=WOO.store_id ";
//        $sql .= "left join hii_member M on M.uid=WOO.admin_id ";
//        $sql .= "where WOO.w_o_out_status=1 and WOO.warehouse_id={$warehouse_id} {$shequ_where4} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        /************************************** 仓库出入库 end ******************************************************************/

        $sql = "select * from ({$sql}) total order by ptime DESC ";

        //echo $sql;exit;

        $data = M()->query($sql);

        //dump($data);exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            $data[$key]["s_type_name"] = $s_type_array[$val["type"]][$val["s_type"]];
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        if ($usePager) {
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        $result["data"] = $this->isArrayNull($data);
        return $result;
    }

    private function getWarehouseGoodsInoutStockOrders($usePager)
    {
        $this->check_warehouse();
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $goods_name = I("get.goods_name");
        $warehouse_id = $this->_warehouse_id;

        $s_type_array = array(
            1 => array(
                0 => "采购",
                1 => "门店退货",
                2 => "仓库调拨",
                3 => "盘盈",
                4 => "门店返仓",
                5 => "其他"
            ),
            2 => array(
                0 => "仓库调拨",
                1 => "门店申请",
                3 => "盘亏",
                4 => "其他",
                5 => "直接发货"
            ),
            3 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            4 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            5 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘盈",
                3 => "其他",
                4 => "采购",
                5 => "寄售"
            ),
            6 => array(
                0 => "仓库调拨",
                1 => "门店调拨",
                3 => "盘亏",
                4 => "其他",
                5 => "寄售"
            ),
            7 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            ),
            8 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            )
        );

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where1 = "";
        $shequ_where2 = "";
        $shequ_where3 = "";
        $shequ_where4 = "";

        if (count($can_store_id_array) > 0) {
            $shequ_where1 .= "";
            $shequ_where2 .= " WOS.store_id in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where3 .= " WOO.store_id in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where4 .= " WOO.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where1 .= " WIS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . " WOS.warehouse_id1 in (" . implode(",", $can_warehouse_id_array) . ") or WOS.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where3 .= (!empty($shequ_where3) ? "or" : "") . " WOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") or WOO.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where4 .= (!empty($shequ_where4) ? "or" : "") . " WOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") or WOO.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . "  WIS.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
            $shequ_where2 .= "";
            $shequ_where3 .= "";
            $shequ_where4 .= "";
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
        if (!empty($shequ_where4)) {
            $shequ_where4 = " and ( {$shequ_where4} ) ";
        }

        $goods_name_where_sql = "";
        if (!empty($goods_name)) {
            $goods_name_where_sql = " and G.title like '%{$goods_name}%' ";
        }

        $sql = "";
        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "select AV.value_name,WIS.w_in_s_id as id,WIS.w_in_s_sn as sn,1 as `type`,'仓库入库' as `type_name`,WIS.w_in_s_type as s_type, ";
        $sql .= "FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,'' as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "WISD.g_num as g_num,WISD.remark as remark,G.title as goods_name,GC.title as cate_name,G.sell_price as sell_price ,WISD.goods_id as goods_id ";
        $sql .= "from hii_warehouse_in_stock_detail WISD ";
        $sql .= "left join hii_warehouse_in_stock WIS on WIS.w_in_s_id=WISD.w_in_s_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WIS.warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY.s_id=WIS.supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WIS.w_in_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WI.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_member M on M.uid=WIS.admin_id ";
        $sql .= "left join hii_goods G on G.id=WISD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=WISD.value_id ";
        $sql .= "where WIS.w_in_s_status=1 {$shequ_where1} and WIS.w_in_s_type<>4 and WIS.warehouse_id={$warehouse_id} {$goods_name_where_sql} and FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        //echo $sql;exit;
        $sql .= "union all ";
        //返仓入库
        $sql .= "select AV.value_name,WIS.w_in_s_id as id,WIS.w_in_s_sn as sn,1 as `type`,'门店返仓入库' as `type_name`,WIS.w_in_s_type as s_type, ";
        $sql .= "FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,S.title as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "WISD.g_num as g_num,WISD.remark as remark,G.title as goods_name,GC.title as cate_name,G.sell_price as sell_price ,WISD.goods_id as goods_id ";
        $sql .= "from hii_warehouse_in_stock_detail WISD ";
        $sql .= "left join hii_warehouse_in_stock WIS on WIS.w_in_s_id=WISD.w_in_s_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WIS.warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY.s_id=WIS.supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WIS.w_in_id ";
        $sql .= "left join hii_store S on S.id=WI.store_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WI.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_member M on M.uid=WIS.admin_id ";
        $sql .= "left join hii_goods G on G.id=WISD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=WISD.value_id ";
        $sql .= "where WIS.w_in_s_status=1 {$shequ_where1} and WIS.w_in_s_type=4 and WIS.warehouse_id={$warehouse_id} {$goods_name_where_sql} and FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //正常出库
        $sql .= "select AV.value_name,WOS.w_out_s_id as id,WOS.w_out_s_sn as sn,2 as `type`,'仓库出库' as `type_name`,WOS.w_out_s_type as s_type, ";
        $sql .= "FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,'' as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "WOSD.g_num as g_num ,WOSD.remark as remark,G.title as goods_name,GC.title as cate_name,G.sell_price as sell_price,WOSD.goods_id as goods_id ";
        $sql .= "from hii_warehouse_out_stock_detail WOSD ";
        $sql .= "left join hii_warehouse_out_stock WOS on WOS.w_out_s_id=WOSD.w_out_s_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WOS.warehouse_id1 ";//入库仓库
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOS.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_store S on S.id=WOS.store_id ";//入库门店
        $sql .= "left join hii_member M on M.uid=WOS.admin_id ";
        $sql .= "left join hii_goods G on G.id=WOSD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=WOSD.value_id ";
        $sql .= "where WOS.w_out_s_status=1 and WOS.warehouse_id2={$warehouse_id} {$shequ_where2} {$goods_name_where_sql} and FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "union all ";
        //被退货入库
        $sql .= "select AV.value_name,WOO.w_o_out_id as id,WOO.w_o_out_sn as sn,3 as `type`,'仓库被退货入库' as `type_name`,WOO.w_o_out_type as s_type, ";
        $sql .= "FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,ifnull(S.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "WOOD.g_num as g_num,WOOD.remark as remark,G.title as goods_name,GC.title as cate_name,G.sell_price as sell_price,WOOD.goods_id as goods_id ";
        $sql .= "from hii_warehouse_other_out_detail WOOD ";
        $sql .= "left join hii_warehouse_other_out WOO on WOO.w_o_out_id=WOOD.w_o_out_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id2 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=WOO.store_id ";
        $sql .= "left join hii_member M on M.uid=WOO.admin_id ";
        $sql .= "left join hii_goods G on G.id=WOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=WOOD.value_id ";
        $sql .= "where WOO.w_o_out_status=1 and WOO.warehouse_id2={$warehouse_id} {$shequ_where3} {$goods_name_where_sql} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
       // $sql .= "union all ";
        //退货出库
//        $sql .= "select AV.value_name,WOO.w_o_out_id as id,WOO.w_o_out_sn as sn,4 as `type`,'仓库退货出库' as `type_name`,WOO.w_o_out_type as s_type, ";
//        $sql .= "FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
//        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
//        $sql .= "'' as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,ifnull(S.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
//        $sql .= "WOOD.g_num as g_num,WOOD.remark as remark,G.title as goods_name,GC.title as cate_name,G.sell_price as sell_price,WOOD.goods_id as goods_id ";
//        $sql .= "from hii_warehouse_other_out_detail WOOD ";
//        $sql .= "left join hii_warehouse_other_out WOO  on WOO.w_o_out_id=WOOD.w_o_out_id  ";
//        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id2 ";
//        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id ";
//        $sql .= "left join hii_store S on S.id=WOO.store_id ";
//        $sql .= "left join hii_member M on M.uid=WOO.admin_id ";
//        $sql .= "left join hii_goods G on G.id=WOOD.goods_id ";
//        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
//        $sql .= "left join hii_attr_value AV on AV.value_id=WOOD.value_id ";
//        $sql .= "where WOO.w_o_out_status=1 and WOO.warehouse_id={$warehouse_id} {$shequ_where4} {$goods_name_where_sql} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        //$sql .= "union all ";
        //echo $sql;exit;
        /************************************** 仓库出入库 end ******************************************************************/

        $sql = "select * from ({$sql}) total order by ptime DESC ";

        //echo $sql;exit;

        $data = M()->query($sql);

        //dump($data);exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            $data[$key]["s_type_name"] = $s_type_array[$val["type"]][$val["s_type"]];
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        if ($usePager) {
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        $result["data"] = $this->isArrayNull($data);
        $result["searchWords"] = $goods_name;
        return $result;
    }

    /**********************
     * 门店出入库流水
     * @param $usePager
     * @return mixed
     * 包含情况：
     * 1.正常入库【hii_store_in_stock】
     * 2.正常出库【hii_store_out_stock】
     * 3.被退货入库【hii_store_other_out】
     * 4.退货出库【hii_store_other_out】
     */
    private function getStoreInoutStockOrders($usePager)
    {
        $this->check_store();
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $warehouse_id = $this->_warehouse_id;
        $store_id = $this->_store_id;

        $s_type_array = array(
            1 => array(
                0 => "采购",
                1 => "门店退货",
                2 => "仓库调拨",
                3 => "盘盈",
                4 => "门店返仓",
                5 => "其他"
            ),
            2 => array(
                0 => "仓库调拨",
                1 => "门店申请",
                3 => "盘亏",
                4 => "其他"
            ),
            3 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            4 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            5 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘盈",
                3 => "其他",
                4 => "采购",
                5 => "寄售"
            ),
            6 => array(
                0 => "仓库调拨",
                1 => "门店调拨",
                3 => "盘亏",
                4 => "其他",
                5 => "寄售"
            ),
            7 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他",
                5=> "门店返仓"
            ),
            8 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            ),
            9 => array(
                0 => "门店返仓",
            ),
            10 => array(
                0 => "库存结存"
            )
        );

        $sql = "";

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where1 = "";
        $shequ_where2 = "";
        $shequ_where3 = "";
        $shequ_where4 = "";
        $shequ_where5 = "";

        if (count($can_store_id_array) > 0) {
            $shequ_where1 .= " SIS.store_id1 in (" . implode(",", $can_store_id_array) . ") or SIS.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where2 .= " SOS.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOS.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where3 .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where4 .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where5 .= " sb.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . " SIS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . " SOS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where3 .= (!empty($shequ_where3) ? "or" : "") . " SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where4 .= (!empty($shequ_where4) ? "or" : "") . " SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where5 .= (!empty($shequ_where5) ? "or" : "") . " sb.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where1 .= "";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . " SOS.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
            $shequ_where3 .= "";
            $shequ_where4 .= "";
            $shequ_where5 .= "";
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
        if (!empty($shequ_where4)) {
            $shequ_where4 = " and ( {$shequ_where4} ) ";
        }
        if (!empty($shequ_where5)) {
            $shequ_where5 = " and ( {$shequ_where5} ) ";
        }

        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "select SIS.s_in_s_id as id,SIS.s_in_s_sn as sn,5 as `type`,'门店入库' as `type_name`,SIS.s_in_s_type as s_type,SIS.g_type,SIS.g_nums,FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "'' as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,SIS.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_store_in_stock SIS ";
        $sql .= "left join hii_store S2 on S2.id=SIS.store_id2  ";
        $sql .= "left join hii_supply SY on SY.s_id=SIS.supply_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SIS.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SIS.store_id1 ";
        $sql .= "left join hii_member M on M.uid=SIS.admin_id ";
        $sql .= "where SIS.s_in_s_status=1 and SIS.store_id2={$store_id} {$shequ_where1} and FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "union all ";
        //正常出库
        $sql .= "select SOS.s_out_s_id as id,SOS.s_out_s_sn as sn,6 as `type`,'门店出库' as `type_name`,SOS.s_out_s_type as s_type,SOS.g_type,SOS.g_nums,FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2.title,'') as fahuo_store_name,SOS.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_store_out_stock SOS ";
        $sql .= "left join hii_warehouse W on W.w_id=SOS.warehouse_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SOS.supply_id ";
        $sql .= "left join hii_store S1 on S1.id=SOS.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOS.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOS.admin_id ";
        $sql .= "where SOS.s_out_s_status=1 and SOS.store_id2={$store_id} {$shequ_where2} and FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "union all ";
/*        //被退货入库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,7 as `type`,'门店被退货入库' as `type_name`,SOO.s_o_out_type as s_type,SOO.g_type,SOO.g_nums,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,  ";
        $sql .= "'' as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,SOO.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id2={$store_id} {$shequ_where3} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "union all ";
        //退货出库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,8 as `type`,'门店退货出库' as `type_name`,SOO.s_o_out_type as s_type,SOO.g_type,SOO.g_nums,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,SOO.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id1={$store_id} {$shequ_where4} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
*/
        //门店报损单入库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,7 as `type`, case SOO.s_o_out_type when 5 then '仓库拒绝返仓' when 1 then '门店调拨拒绝' when 0 then '仓库发货拒绝'  else '' end as `type_name`,SOO.s_o_out_type as s_type,SOO.g_type,SOO.g_nums,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S2.title,'') as fahuo_store_name,SOO.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_store_other_out SOO  ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2  ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id  ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id1={$store_id} {$shequ_where3} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        //门店返仓出库
        $sql .= "union all ";
        $sql .= "select sb.s_back_id as id,sb.s_back_sn as sn,9 as `type`, case sb.s_back_type when 0 then '门店返仓出库' else '' end as `type_name`,sb.s_back_type as s_type,sb.g_type,sb.g_nums,FROM_UNIXTIME(sb.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,sb.remark,M.nickname as admin_nickname ";
        $sql .= "from hii_store_back sb ";
        $sql .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=sb.store_id ";
        $sql .= "left join hii_member M on M.uid=sb.admin_id ";
        $sql .= "where sb.s_back_status=1 and sb.store_id={$store_id} {$shequ_where5}  and  FROM_UNIXTIME(sb.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";

        /************************************** 门店出入库 end ******************************************************************/
        /************************************** 门店周库存结存 开始 ******************************************************************/
        if(!empty($goods_name_where_sql)){
            $storeJiecunModel = M('StoreJiecun');
            $info = $storeJiecunModel->field('jc_id,jc_child,add_time')->where(array('add_time'=>array('between',array(strtotime($s_date),strtotime($e_date)))))->select();
            foreach($info as $key=>$val){
                //周库存结存
                $sql .= "union all  ";
                $sql .= "select sb.jc_id as id,sb.jc_id as sn,10 as `type`, '周库存结存' as `type_name`,0 as s_type,FROM_UNIXTIME({$val['add_time']},'%Y-%m-%d %H:%i:%s') as ptime, ";
                $sql .= "'' as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
                $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,''  as fahuo_store_name,'' as admin_nickname, ";
                $sql .= "sb.jc_num as g_num, '' as remark,G.title as goods_name,sb.sell_price as sell_price,GC.title as cate_name,sb.goods_id as goods_id ";
                $sql .= "from {$val['jc_child']} sb ";
                $sql .= "left join hii_store S1 on S1.id=sb.store_id ";
                $sql .= "left join hii_goods G on G.id=sb.goods_id ";
                $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
                $sql .= "where sb.jc_id={$val['jc_id']} and sb.store_id={$store_id} {$goods_name_where_sql}  ";

            }
        }
        /************************************** 门店周库存结存 结束 ******************************************************************/

        //echo $sql;
        //exit;

        $sql = "select * from ({$sql}) total order by ptime DESC ";

        $data = M()->query($sql);

        //dump($data);exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            $data[$key]["s_type_name"] = $s_type_array[$val["type"]][$val["s_type"]];
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        if ($usePager) {
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        $result["data"] = $this->isArrayNull($data);
        return $result;
    }

    private function getStoreGoodsInoutStockOrders($usePager)
    {
        $this->check_store();
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $goods_name = I("get.goods_name");

        $goods_id = I("get.goods_id");

        $warehouse_id = $this->_warehouse_id;
        $store_id = $this->_store_id;

        $s_type_array = array(
            1 => array(
                0 => "采购",
                1 => "门店退货",
                2 => "仓库调拨",
                3 => "盘盈",
                4 => "门店返仓",
                5 => "其他"
            ),
            2 => array(
                0 => "仓库调拨",
                1 => "门店申请",
                3 => "盘亏",
                4 => "其他"
            ),
            3 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            4 => array(
                0 => "仓库退货",
                1 => "盘亏",
                2 => "其他",
                4 => "门店退货"
            ),
            5 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘盈",
                3 => "其他",
                4 => "采购",
                5 => "寄售"
            ),
            6 => array(
                0 => "仓库调拨",
                1 => "门店调拨",
                3 => "盘亏",
                4 => "其他",
                5 => "寄售"
            ),
            7 => array(
                0 => "仓库发货",
                1 => "门店调拨",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他",
                5 => "门店返仓"
            ),
            8 => array(
                0 => "仓库退货",
                1 => "门店退货",
                2 => "盘亏",
                3 => "商品过期",
                4 => "其他"
            ),
            9 => array(
                0 => "门店返仓",
            ),
            10 => array(
                0 => "库存结存"
            )
        );

        $sql = "";

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where1 = "";
        $shequ_where2 = "";
        $shequ_where3 = "";
        $shequ_where4 = "";
        $shequ_where5 = "";

        if (count($can_store_id_array) > 0) {
            $shequ_where1 .= " SIS.store_id1 in (" . implode(",", $can_store_id_array) . ") or SIS.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where2 .= " SOS.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOS.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where3 .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where4 .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
            $shequ_where5 .= " sb.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where1 .= (!empty($shequ_where1) ? "or" : "") . " SIS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . " SOS.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where3 .= (!empty($shequ_where3) ? "or" : "") . " SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where4 .= (!empty($shequ_where4) ? "or" : "") . " SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
            $shequ_where5 .= (!empty($shequ_where5) ? "or" : "") . " sb.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where1 .= "";
            $shequ_where2 .= (!empty($shequ_where2) ? "or" : "") . " SOS.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
            $shequ_where3 .= "";
            $shequ_where4 .= "";
            $shequ_where5 .= "";
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
        if (!empty($shequ_where4)) {
            $shequ_where4 = " and ( {$shequ_where4} ) ";
        }
        if (!empty($shequ_where5)) {
            $shequ_where5 = " and ( {$shequ_where5} ) ";
        }

        $goods_name_where_sql = "";
        if (!empty($goods_name)) {
            $goods_name_where_sql = " and G.title like '%{$goods_name}%' ";
        }
        if (!empty($goods_id)) {
            $goods_name_where_sql = " and G.id={$goods_id} ";
        }

        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "select SIS.s_in_s_id as id,SIS.s_in_s_sn as sn,5 as `type`,'门店入库' as `type_name`,SIS.s_in_s_type as s_type,FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "'' as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "SISD.g_num as g_num,SISD.remark as remark,G.title as goods_name,G.sell_price as sell_price,GC.title as cate_name,SISD.goods_id as goods_id ";
        $sql .= "from hii_store_in_stock_detail SISD ";
        $sql .= "left join hii_store_in_stock SIS on SIS.s_in_s_id=SISD.s_in_s_id ";
        $sql .= "left join hii_store S2 on S2.id=SIS.store_id2  ";
        $sql .= "left join hii_supply SY on SY.s_id=SIS.supply_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SIS.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SIS.store_id1 ";
        $sql .= "left join hii_member M on M.uid=SIS.admin_id ";
        $sql .= "left join hii_goods G on G.id=SISD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where SIS.s_in_s_status=1 and SIS.store_id2={$store_id} {$shequ_where1} {$goods_name_where_sql} and FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        //echo $sql;exit;
        $sql .= "union all ";
        //正常出库
        $sql .= "select SOS.s_out_s_id as id,SOS.s_out_s_sn as sn,6 as `type`,'门店出库' as `type_name`,SOS.s_out_s_type as s_type,FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "SOSD.g_num as g_num,SOSD.remark as remark,G.title as goods_name,G.sell_price as sell_price,GC.title as cate_name,SOSD.goods_id as goods_id ";
        $sql .= "from hii_store_stock_detail SOSD ";
        $sql .= "left join hii_store_out_stock SOS on SOS.s_out_s_id=SOSD.s_out_s_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOS.warehouse_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SOS.supply_id ";
        $sql .= "left join hii_store S1 on S1.id=SOS.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOS.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOS.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOSD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where SOS.s_out_s_status=1 and SOS.store_id2={$store_id} {$shequ_where2} {$goods_name_where_sql} and FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "union all ";
      /*  //被退货入库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,7 as `type`,'门店被退货入库' as `type_name`,SOO.s_o_out_type as s_type,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,  ";
        $sql .= "'' as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "SOOD.g_num as g_num ,SOOD.remark as remark ,G.title as goods_name,G.sell_price as sell_price,GC.title as cate_name,SOOD.goods_id as goods_id ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id2={$store_id} and SOO.warehouse_id=0 {$shequ_where3} {$goods_name_where_sql} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "union all ";*/
        //echo $sql;exit;
/*        //退货出库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,8 as `type`,'门店退货出库' as `type_name`,SOO.s_o_out_type as s_type,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "SOOD.g_num as g_num,SOOD.remark as remark,G.title as goods_name,G.sell_price as sell_price,GC.title as cate_name,SOOD.goods_id as goods_id ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id1={$store_id} {$shequ_where4} {$goods_name_where_sql} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";*/

        //门店报损单入库
        $sql .= "select SOO.s_o_out_id as id,SOO.s_o_out_sn as sn,7 as `type`, case SOO.s_o_out_type when 5 then '仓库拒绝返仓' when 1 then '门店调拨拒绝' when 0 then '仓库发货拒绝' end as `type_name`,SOO.s_o_out_type as s_type,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S2.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "SOOD.g_num as g_num,SOOD.remark as remark,G.title as goods_name,G.sell_price as sell_price,GC.title as cate_name,SOOD.goods_id as goods_id ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where SOO.s_o_out_status=1 and SOO.store_id1={$store_id} {$shequ_where4} {$goods_name_where_sql} and  FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        //门店返仓出库
        $sql .= "union all ";
        $sql .= "select sb.s_back_id as id,sb.s_back_sn as sn,9 as `type`, case sb.s_back_type when 0 then '门店返仓出库' end as `type_name`,sb.s_back_type as s_type,FROM_UNIXTIME(sb.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name,M.nickname as admin_nickname, ";
        $sql .= "sbd.g_num as g_num,sbd.remark as remark,G.title as goods_name,G.sell_price as sell_price,GC.title as cate_name,sbd.goods_id as goods_id ";
        $sql .= "from hii_store_back sb ";
        $sql .= "left join hii_store_back_detail sbd on sbd.s_back_id=sb.s_back_id ";
        $sql .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=sb.store_id ";
        $sql .= "left join hii_member M on M.uid=sb.admin_id ";
        $sql .= "left join hii_goods G on G.id=sbd.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where sb.s_back_status=1 and sb.store_id={$store_id} {$shequ_where5} {$goods_name_where_sql} and  FROM_UNIXTIME(sb.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";

        //echo $sql;
        //exit;

        /************************************** 门店出入库 end ******************************************************************/
        /************************************** 门店周库存结存 开始 ******************************************************************/
        //查询条件
        if (in_array(1, $this->group_id)) {
            if (!empty($goods_id_where_sql) || !empty($goods_name_where_sql)) {
                $storeJiecunModel = M('StoreJiecun');
                $info = $storeJiecunModel->field('jc_id,jc_child,add_time')->where(array('add_time' => array('between', array(strtotime($s_date), strtotime($e_date)))))->select();
                foreach ($info as $key => $val) {
                    //周库存结存
                    $sql .= "union all  ";
                    $sql .= "select sb.jc_id as id,sb.jc_id as sn,10 as `type`, '周库存结存' as `type_name`,0 as s_type,FROM_UNIXTIME({$val['add_time']},'%Y-%m-%d %H:%i:%s') as ptime, ";
                    $sql .= "'' as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
                    $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,''  as fahuo_store_name,'' as admin_nickname, ";
                    $sql .= "sb.jc_num as g_num, '' as remark,G.title as goods_name,sb.sell_price as sell_price,GC.title as cate_name,sb.goods_id as goods_id ";
                    $sql .= "from {$val['jc_child']} sb ";
                    $sql .= "left join hii_store S1 on S1.id=sb.store_id ";
                    $sql .= "left join hii_goods G on G.id=sb.goods_id ";
                    $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
                    $sql .= "where sb.jc_id={$val['jc_id']} and sb.store_id={$store_id} {$goods_name_where_sql}  ";
                }
            }
         }
        /************************************** 门店周库存结存 结束 ******************************************************************/

        $sql = "select * from ({$sql}) total order by ptime DESC ";

        $data = M()->query($sql);

        //dump($data);exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            $data[$key]["s_type_name"] = $s_type_array[$val["type"]][$val["s_type"]];
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        if ($usePager) {
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        $result["searchWords"] = $goods_name;
        $result["data"] = $this->isArrayNull($data);
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
        \Think\Log::record("s_date " . $s_date);
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