<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-14
 * Time: 14:15
 * 退货相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class WarehouseLossController extends AdminController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_warehouse();
    }


    /************
     * 退货列表接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     ****************/
    public function index()
    {
        $warehouse_id = $this->_warehouse_id;//当前仓库

        $hasSeeStockPriceRight = $this->checkFunc('warehouse_stock_price');//是否具有查看入货价权限

        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " A.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") or A.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $data = null;
        $WarehouseOtherOut = M("WarehouseOtherOut");

        //获取仓库所在区域
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];

        //g_amounts:销售价 p_amounts：进货价
        $sql = "select A.w_o_out_id,A.w_o_out_sn,A.w_o_out_status,A.w_o_out_type,A.w_in_id,A.i_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id, ";
        $sql .= "FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id, ";
        $sql .= "A.warehouse_id,A.warehouse_id2,A.store_id,A.g_type,A.g_nums,M1.nickname as admin_name,M2.nickname as eadmin_name,M3.nickname as padmin_name, ";
        $sql .= "W1.w_name as warehouse_name,W2.w_name as warehouse2_name,S.title as store_name,A.remark, ";
        $sql .= "sum(A1.g_num*(CASE WHEN GST.shequ_price is not null and GST.shequ_price>0 THEN GST.shequ_price ELSE G.sell_price END )) as g_amounts,'' as p_amounts,WI.w_in_sn,WIN.i_sn ";
        $sql .= "from hii_warehouse_other_out A ";
        $sql .= "LEFT JOIN hii_warehouse_other_out_detail A1 on A1.w_o_out_id=A.w_o_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=A.w_in_id ";
        $sql .= "left join hii_warehouse_inventory WIN on WIN.i_id=A.i_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id ";
        $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=A1.goods_id ";
        $sql .= "where A.warehouse_id={$warehouse_id} {$shequ_where} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by w_o_out_id,w_o_out_sn,w_o_out_status,w_o_out_type,w_in_id,i_id,ctime,admin_id,etime,eadmin_id,ptime,padmin_id,warehouse_id, ";
        $sql .= "warehouse_id2,store_id,g_type,g_nums,admin_name,eadmin_name,padmin_name,warehouse_name,warehouse2_name,store_name,remark,w_in_sn,i_sn ";
        $sql .= "order by A.w_o_out_id desc ";
        $data = $WarehouseOtherOut->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            switch ($val["w_o_out_status"]) {
                case 0: {
                    $datamain[$key]["w_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $datamain[$key]["w_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["w_o_out_type"]) {
                case 0: {
                    $datamain[$key]["w_o_out_type_name"] = "仓库退货";
                };
                    break;
                case 1: {
                    $datamain[$key]["w_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 2: {
                    $datamain[$key]["w_o_out_type_name"] = "其他退货";
                };
                    break;
                case 3: {
                    $datamain[$key]["w_o_out_type_name"] = "门店退货";
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["hasSeeStockPriceRight"] = $hasSeeStockPriceRight;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($datamain);
        $this->response(self::CODE_OK, $result);
    }

    /************
     * 获取单个退货详细申请
     * 请求方式：GET
     * 请求参数：w_o_out_id  退货单主表ID  必须
     */
    public function getSingleWarehouseOtherOutInfo()
    {
        $w_o_out_id = I("get.w_o_out_id");
        $warehouse_id = $this->_warehouse_id;
        $hasSeeStockPriceRight = $this->checkFunc('warehouse_stock_price');//是否具有查看入货价权限
        if (is_null($w_o_out_id) || empty($w_o_out_id)) {
            $this->response(0, "请选择要查看的退货单");
        }
        //sum(A1.g_num*G.sell_price) as g_amounts,
        $WarehouseOtherOutModel = M("WarehouseOtherOut");
        $sql = "select A.w_o_out_id,A.w_o_out_sn,A.w_o_out_status,A.w_o_out_type,A.w_in_id,A.i_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id, ";
        $sql .= "FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id, ";
        $sql .= "A.warehouse_id,A.warehouse_id2,A.store_id,A.g_type,A.g_nums,M1.nickname as admin_name,M2.nickname as eadmin_name,M3.nickname as padmin_name, ";
        $sql .= "W1.w_name as warehouse_name,W2.w_name as warehouse2_name,S.title as store_name,A.remark, ";
        $sql .= "'' as p_amounts,WI.w_in_sn,WIN.i_sn ";
        $sql .= "from hii_warehouse_other_out A ";
        $sql .= "LEFT JOIN hii_warehouse_other_out_detail A1 on A1.w_o_out_id=A.w_o_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=A.w_in_id ";
        $sql .= "left join hii_warehouse_inventory WIN on WIN.i_id=A.i_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id ";
        $sql .= "where A.w_o_out_id={$w_o_out_id} and A.warehouse_id={$warehouse_id} ";
        $sql .= "order by A.w_o_out_id desc ";

        $datas = $WarehouseOtherOutModel->query($sql);

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "无法查看该退货信息");
        } else {
            $result = array();
            $w_o_out_status = $datas[0]["w_o_out_status"];

            //获取仓库所在区域
            $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
            $shequ_id = $warehouse_data[0]["shequ_id"];

            $sql = "select A1.goods_id,G.title as goods_name,A1.g_num,'' as g_price,A1.w_in_d_id,WI.w_in_sn,ifnull(AV.bar_code,G.bar_code)bar_code,GC.title as cate_name,A1.remark, ";
            $sql .= "G.sell_price as sys_price,GST.shequ_price as shequ_price,AV.value_id,AV.value_name ";
            $sql .= "from hii_warehouse_other_out A ";
            $sql .= "left join hii_warehouse_other_out_detail A1 on  A1.w_o_out_id=A.w_o_out_id ";
            $sql .= "left join hii_goods G on G.id=A1.goods_id ";
            $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
            $sql .= "left join hii_warehouse_in_detail WID on WID.w_in_d_id=A1.w_in_d_id ";
            $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WID.w_in_id ";
            $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
            $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=A1.goods_id ";
            $sql .= "where A1.w_o_out_id={$w_o_out_id} ";

            //echo $sql;exit;

            $list = $WarehouseOtherOutModel->query($sql);

            $g_amounts = 0;
            foreach ($list as $key => $val) {
                if ($w_o_out_status == 0) {
                    $list[$key]["status_name"] = "新增";
                } elseif ($w_o_out_status == 1) {
                    $list[$key]["status_name"] = "已审核";
                }
                $price = 0;
                if (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                    $price = $val["shequ_price"];
                } elseif (!is_null($val["sys_price"]) && !empty($val["sys_price"])) {
                    $price = $val["sys_price"];
                }
                $list[$key]["sell_price"] = $price;
                $g_amounts += $val["g_num"] * $price;
            }

            $result["maindata"] = $datas[0];
            $result["list"] = $list;
            $this->response(self::CODE_OK, $result);
        }

    }

    /**************************
     * 导出退货列表的Excel接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     */
    public function exportWarehouseLossListExcel()
    {
        $warehouse_id = $this->_warehouse_id;//当前仓库

        $hasSeeStockPriceRight = $this->checkFunc('warehouse_stock_price');//是否具有查看入货价权限

        //时间范围默认30天
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $data = null;
        $WarehouseOtherOut = M("WarehouseOtherOut");

        //获取仓库所在区域
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];

        //g_amounts:销售价 p_amounts：进货价
        $sql = "select A.w_o_out_id,A.w_o_out_sn,A.w_o_out_status,A.w_o_out_type,A.w_in_id,A.i_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id, ";
        $sql .= "FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id, ";
        $sql .= "A.warehouse_id,A.warehouse_id2,A.store_id,A.g_type,A.g_nums,M1.nickname as admin_name,M2.nickname as eadmin_name,M3.nickname as padmin_name, ";
        $sql .= "W1.w_name as warehouse_name,W2.w_name as warehouse2_name,S.title as store_name,A.remark, ";
        $sql .= "sum(A1.g_num*(CASE WHEN GST.shequ_price is not null and GST.shequ_price>0 THEN GST.shequ_price ELSE G.sell_price END )) as g_amounts,'' as p_amounts,WI.w_in_sn,WIN.i_sn ";
        $sql .= "from hii_warehouse_other_out A ";
        $sql .= "LEFT JOIN hii_warehouse_other_out_detail A1 on A1.w_o_out_id=A.w_o_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=A.w_in_id ";
        $sql .= "left join hii_warehouse_inventory WIN on WIN.i_id=A.i_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id ";
        $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=A1.goods_id ";
        $sql .= "where A.warehouse_id={$warehouse_id} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "order by A.w_o_out_id desc ";

        $data = $WarehouseOtherOut->query($sql);

        foreach ($data as $key => $val) {
            switch ($val["w_o_out_status"]) {
                case 0: {
                    $data[$key]["w_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["w_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["w_o_out_type"]) {
                case 0: {
                    $data[$key]["w_o_out_type_name"] = "仓库退货";
                };
                    break;
                case 1: {
                    $data[$key]["w_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 2: {
                    $data[$key]["w_o_out_type_name"] = "其他退货";
                };
                    break;
                case 3: {
                    $data[$key]["w_o_out_type_name"] = "门店退货";
                };
                    break;
            }
        }

        ob_clean;
        $title = $s_date . '>>>' . $e_date . '仓库退货单';
        $fname = './Public/Excel/WarehouseOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\WarehouseLossReportModel();
        $printfile = $printmodel->createWarehouseLossListExcel($data, $title, $fname, $hasSeeStockPriceRight);
        $this->response(self::CODE_OK, $printfile);
    }

    /*******************
     * 导出单个仓库退货单详细信息Excel
     * 请求方式：GET
     * 请求参数：w_o_out_id  仓库退货主表ID  必须
     */
    public function exportSingleWarehouseLossInfoExcel()
    {
        $w_o_out_id = I("get.w_o_out_id");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($w_o_out_id) || empty($w_o_out_id)) {
            $this->response(0, "请选择要导出的退货单");
        }
        $hasSeeStockPriceRight = $this->checkFunc('warehouse_stock_price');//是否具有查看入货价权限
        $WarehouseOtherOutModel = M("WarehouseOtherOut");
        $sql = "select A.w_o_out_id,A.w_o_out_sn,A.w_o_out_status,A.w_o_out_type,A.w_in_id,A.i_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id, ";
        $sql .= "FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id, ";
        $sql .= "A.warehouse_id,A.warehouse_id2,A.store_id,A.g_type,A.g_nums,M1.nickname as admin_name,M2.nickname as eadmin_name,M3.nickname as padmin_name, ";
        $sql .= "W1.w_name as warehouse_name,W2.w_name as warehouse2_name,S.title as store_name,A.remark, ";
        $sql .= "sum(A1.g_num*G.sell_price) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts,WI.w_in_sn,WIN.i_sn ";
        $sql .= "from hii_warehouse_other_out A ";
        $sql .= "LEFT JOIN hii_warehouse_other_out_detail A1 on A1.w_o_out_id=A.w_o_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=A.w_in_id ";
        $sql .= "left join hii_warehouse_inventory WIN on WIN.i_id=A.i_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=A1.goods_id ";
        $sql .= "where A.w_o_out_id={$w_o_out_id} and A.warehouse_id={$warehouse_id} ";
        $sql .= "order by A.w_o_out_id desc ";

        $datas = $WarehouseOtherOutModel->query($sql);

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "无法导出退货信息");
        } else {
            $data = array();

            $tmp = $datas[0];
            switch ($tmp["w_o_out_status"]) {
                case 0: {
                    $tmp["w_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $tmp["w_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($tmp["w_o_out_type"]) {
                case 0: {
                    $tmp["w_o_out_type_name"] = "仓库退货";
                };
                    break;
                case 1: {
                    $tmp["w_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 2: {
                    $tmp["w_o_out_type_name"] = "其他退货";
                };
                    break;
                case 3: {
                    $tmp["w_o_out_type_name"] = "门店退货";
                };
                    break;
            }

            $data["maindata"] = $tmp;

            //获取仓库所在区域
            $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
            $shequ_id = $warehouse_data[0]["shequ_id"];

            $sql = "select A1.goods_id,G.title as goods_name,A1.g_num,A1.g_price,A1.w_in_d_id,WI.w_in_sn,ifnull(AV.bar_code,G.bar_code)bar_code,GC.title as cate_name, ";
            $sql .= "G.sell_price as sys_price,GST.shequ_price as shequ_price,AV.value_id,AV.value_name ";
            $sql .= "from hii_warehouse_other_out A ";
            $sql .= "left join hii_warehouse_other_out_detail A1 on  A1.w_o_out_id=A.w_o_out_id ";
            $sql .= "left join hii_goods G on G.id=A1.goods_id ";
            $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
            $sql .= "left join hii_warehouse_in_detail WID on WID.w_in_d_id=A1.w_in_d_id ";
            $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WID.w_in_id ";
            $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
            $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=A1.goods_id ";
            $sql .= "where A1.w_o_out_id={$w_o_out_id} ";

            //echo $sql;exit;

            $list = $WarehouseOtherOutModel->query($sql);

            $g_amounts = 0;
            foreach ($list as $key => $val) {
                $price = 0;
                if (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                    $price = $val["shequ_price"];
                } elseif (!is_null($val["sys_price"]) && !empty($val["sys_price"])) {
                    $price = $val["sys_price"];
                }
                $list[$key]["sell_price"] = $price;
                $g_amounts += $val["g_num"] * $price;
            }

            $datas["maindata"]["g_amounts"] = $g_amounts;
            $data["list"] = $list;
            ob_clean;
            $title = '仓库退货单';
            $fname = './Public/Excel/WarehouseOtherOut_' . time() . '.xlsx';
            $printmodel = new \Addons\Report\Model\WarehouseLossReportModel();
            $printfile = $printmodel->createSingleWarehouseLossInfoExcel($data, $title, $fname, $hasSeeStockPriceRight);
            $this->response(self::CODE_OK, $printfile);

        }
    }


    /****************************
     * 退货单审核接口
     * 请求方式：POST
     * 请求参数：w_o_out_id  退货单ID  必须
     * 日期：2017-12-28
     ***************************/
    public function check()
    {
        $w_o_out_id = I("post.w_o_out_id");
        if (is_null($w_o_out_id) || empty($w_o_out_id)) {
            $this->response(0, "请选择需要审核的退货单");
        }
        $warehouse_id = $this->_warehouse_id;
        $WarehouseLossRepository = D("WarehouseLoss");
        $padmin_id = UID;
        $result = $WarehouseLossRepository->pass($padmin_id, $warehouse_id, $w_o_out_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }


    /************
     * 被退货列表接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     *           pageSize 每页显示数量
     * 日期：2017-12-29
     ****************/
    public function index2()
    {
        $result = $this->getIndex2List(true);
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 导出被退货单列表Excel文档
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * 日期：2017-12-29
     */
    public function exportIndex2ListExcel()
    {
        $result = $this->getIndex2List(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 被退货单';
        $fname = './Public/Excel/WarehouseOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\WarehouseLossReportModel();
        $printfile = $printmodel->createIndex2ListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /******************************
     * 查看被退货单接口
     * 请求方式：GET
     * 请求参数：w_o_out_id  退货单ID  必须
     * 日期：2017-12-29
     */
    public function view2()
    {
        $result = $this->getView2Info();
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 导出被退货单Excel接口
     * 请求方式：GET
     * 请求参数：w_o_out_id  退货单ID  必须
     */
    public function exportView2Excel()
    {
        $result = $this->getView2Info();
        ob_clean;
        $title = '被退货单查看';
        $fname = './Public/Excel/WarehouseOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\WarehouseLossReportModel();
        $printfile = $printmodel->createView2Excel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    private function getIndex2List($usePager)
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $warehouse_id = $this->_warehouse_id;
        $WarehouseOtherOutModel = M("WarehouseOtherOut");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " WOO.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " WOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") or WOO.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        //获取仓库所在区域
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];

        //SUM(G.sell_price*WOOD.g_num) as g_amounts,
        $sql = "select WOO.w_o_out_id,WOO.w_o_out_sn,FROM_UNIXTIME(WOO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "WOO.w_o_out_type,WOO.w_o_out_status,M1.nickname as admin_nickname,M2.nickname as padmin_nickname,W.w_name as warehouse_name,S.title as store_name,WOO.remark, ";
        $sql .= "WOO.g_type,WOO.g_nums,'' as out_amounts, ";
        $sql .= "SUM(WOOD.g_num*(CASE WHEN GST.shequ_price is not null and GST.shequ_price>0 THEN GST.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_warehouse_other_out WOO ";
        $sql .= "left join hii_warehouse_other_out_detail WOOD on WOOD.w_o_out_id=WOO.w_o_out_id ";
        $sql .= "left join hii_goods G on G.id=WOOD.goods_id ";
        $sql .= "left join hii_member M1 on M1.uid=WOO.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=WOO.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=WOO.store_id ";
        $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=WOOD.goods_id  ";
        $sql .= "where WOO.warehouse_id2={$warehouse_id} {$shequ_where} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by WOO.w_o_out_id,WOO.w_o_out_sn,WOO.ctime,WOO.ptime,WOO.w_o_out_type,M1.nickname,M2.nickname,W.w_name,S.title,WOO.remark,WOO.g_type,WOO.g_nums ";
        $sql .= "order by WOO.w_o_out_id desc ";

        $data = $WarehouseOtherOutModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["s_date"] = $s_date;
            $result["e_date"] = $e_date;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }

        foreach ($data as $key => $val) {
            switch ($val["w_o_out_type"]) {
                case 0: {
                    $date[$key]["w_o_out_type_name"] = "仓库退货";
                };
                    break;
                case 4: {
                    $data[$key]["w_o_out_type_name"] = "门店退货";
                };
                    break;
            }
            switch ($val["w_o_out_status"]) {
                case 0: {
                    $data[$key]["w_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["w_o_out_status_name"] = "已审核";
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["data"] = $this->isArrayNull($data);

        return $result;

    }

    private function getView2Info()
    {
        $w_o_out_id = I("get.w_o_out_id");
        if (is_null($w_o_out_id) || empty($w_o_out_id)) {
            $this->response(0, "请选择退货单");
        }
        $WarehouseOtherOutModel = M("WarehouseOtherOut");
        $WarehouseOtherOutDetailModel = M("WarehouseOtherOutDetail");
        $warehouse_id = $this->_warehouse_id;
        $sql = "select WOO.w_o_out_id,WOO.w_o_out_sn,FROM_UNIXTIME(WOO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "WOO.w_o_out_type,M1.nickname as admin_nickname,M2.nickname as padmin_nickname,W.w_name as warehouse_name,W2.w_name as warehouse_name2 ,S.title as store_name,WOO.remark, ";
        $sql .= "WOO.g_type,WOO.g_nums,SUM(G.sell_price*WOOD.g_num) as g_amounts,'' as out_amounts ";
        $sql .= "from hii_warehouse_other_out WOO ";
        $sql .= "left join hii_warehouse_other_out_detail WOOD on WOOD.w_o_out_id=WOO.w_o_out_id ";
        $sql .= "left join hii_goods G on G.id=WOOD.goods_id ";
        $sql .= "left join hii_member M1 on M1.uid=WOO.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=WOO.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WOO.warehouse_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WOO.warehouse_id2 ";
        $sql .= "left join hii_store S on S.id=WOO.store_id ";
        $sql .= "where WOO.w_o_out_id={$w_o_out_id} and WOO.warehouse_id2={$warehouse_id} and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) ";
        $sql .= "group by WOO.w_o_out_id,WOO.w_o_out_sn,WOO.ctime,WOO.ptime,WOO.w_o_out_type,M1.nickname,M2.nickname,W.w_name,W2.w_name,S.title,WOO.remark,WOO.g_type,WOO.g_nums ";
        $sql .= "order by WOO.w_o_out_id desc limit 1 ";

        $datas = $WarehouseOtherOutModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法查看该退货单");
        }
        $maindata = $datas[0];

        switch ($maindata["w_o_out_type"]) {
            case 0: {
                $maindata["w_o_out_type_name"] = "仓库退货";
            };
                break;
            case 4: {
                $maindata["w_o_out_type_name"] = "门店退货";
            };
                break;
        }

        //获取仓库所在区域
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];

        $sql = "select WOOD.goods_id,WOOD.g_num,'' as g_price,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,G.sell_price as sys_price,GC.title as cate_name,WOOD.remark,GST.shequ_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_warehouse_other_out_detail WOOD ";
        $sql .= "left join hii_goods G on G.id=WOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.goods_id,GS.shequ_price) GST on GST.goods_id=WOOD.goods_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=WOOD.value_id ";
        $sql .= "where WOOD.w_o_out_id={$w_o_out_id} order by WOOD.goods_id asc ";
        $list = $WarehouseOtherOutDetailModel->query($sql);

        $g_amounts = 0;
        foreach ($list as $key => $val) {
            $price = 0;
            if (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $price = $val["shequ_price"];
            } elseif (!is_null($val["sys_price"]) && !empty($val["sys_price"])) {
                $price = $val["sys_price"];
            }
            $list[$key]["sell_price"] = $price;
            $g_amounts += $val["g_num"] * $price;
        }
        $maindata["g_amounts"] = $g_amounts;
        $result["maindata"] = $maindata;
        $result["list"] = $list;
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