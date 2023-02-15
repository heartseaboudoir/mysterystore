<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-16
 * Time: 17:07
 * 门店来货入库相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreInStockController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /**********************
     * 门店入库单列表接口
     * 请求方式：GET
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     * 日期：2017-11-21
     */
    public function index()
    {
        $result = $this->getIndexList(true);
        $this->response(self::CODE_OK, $result);
    }

    /********************
     * 获取单个门店收货单详细信息
     * 请求方式：GET
     * 请求参数：s_in_s_id 门店收货单ID 必须
     * 日期：2017-11-21
     */
    public function getSingleStoreInStockInfo()
    {
        $result = $this->getSingleStoreInStockDetail();
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 导出入库单列表Excel
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * 注意：
     * 日期：2017-12-14
     */
    public function exportIndexExcel()
    {
        $result = $this->getIndexList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 入库单';
        $fname = './Public/Excel/StoreInStock_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInStockModel();
        $printfile = $printmodel->createIndexListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*******************
     * 导出入库单详细Excel
     * 请求方式：GET
     * 请求参数：s_in_s_id 入库单ID 必须
     * 注意：
     * 日期：2017-12-14
     */
    public function exportViewExcel()
    {
        $result = $this->getSingleStoreInStockDetail();
        ob_clean;
        $title = '入库单查看';
        $fname = './Public/Excel/StoreInStock_' . $result["maindata"]["s_in_s_sn"] . '_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInStockModel();
        $printfile = $printmodel->createViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /********************
     * 入库单更新接口
     * 请求方式：POST
     * 请求参数：s_in_s_id  入库单ID  必须
     *           info_json  子表修改信息 ，格式 [{"s_in_s_d_id":"","endtime":""},{"s_in_s_d_id":"","endtime":""}]
     *           remark  备注
     * 注意：当s_in_s_type为2，4，5的时候可以修改商品过期时间
     * 日期：2017-12-19
     */
    public function update()
    {
        $s_in_s_id = I("post.s_in_s_id");
        $info_json = I("post.info_json");
        $remark = I("post.remark");
        $store_id = $this->_store_id;
        if (is_null($s_in_s_id) || empty($s_in_s_id)) {
            $this->response(0, "请选择要修改的入库单");
        }
        $StoreInStockRepository = D("StoreInStock");
        $result = $StoreInStockRepository->update(UID, $store_id, $s_in_s_id, $info_json, $remark);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "修改成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /****************************
     * 审核接口
     * 请求方式：POST
     * 请求参数：s_in_s_id  入库单ID 必须
     * 注意：
     * 日期：2017-12-14
     */
    public function check()
    {
        $s_in_s_id = I("post.s_in_s_id");
        if (is_null($s_in_s_id) || empty($s_in_s_id)) {
            $this->response(0, "请选择要审核的出库单");
        }
        $StoreInStockRepository = D("StoreInStock");
        $padmin_id = UID;
        $store_id = $this->_store_id;
        $result = $StoreInStockRepository->check($padmin_id, $store_id, $s_in_s_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    private function getIndexList($usePager)
    {
        $store_id = $this->_store_id;
        $data = null;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $StoreInStockModel = M("StoreInStock");
        $StoreInventoryModel = M("StoreInventory");
        $StoreInModel = M("StoreIn");

        $sql = "select A.s_in_s_id,A.si_id,A.s_in_s_sn,A.s_in_s_status,A.s_in_s_type,A.s_in_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,A.supply_id,A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname, ";
        $sql .= "M3.nickname as padmin_nickname,W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2,SY.s_name as supply_name, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_in_stock A ";
        $sql .= "left join hii_store_in_stock_detail A1 on A1.s_in_s_id=A.s_in_s_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_supply SY on SY.s_id=A.supply_id ";
        $sql .= "where A.store_id2={$store_id} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "GROUP BY A.s_in_s_id,A.s_in_s_sn,A.s_in_s_status,A.s_in_s_type,A.s_in_id,A.ctime,A.admin_id,A.etime,A.eadmin_id,A.ptime,A.si_id,";
        $sql .= "A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,A.remark,A.g_type,A.g_nums,M1.nickname,M2.nickname,M3.nickname,W.w_name,S1.title,S2.title ";
        $sql .= " order by A.s_in_s_id desc ";

        $data = $StoreInStockModel->query($sql);

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
            $data[$key]["rel_orders"] = "";//关联单号
            switch ($val["s_in_s_status"]) {
                case 0: {
                    $data[$key]["s_in_s_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$data[$key]["s_in_s_status_name"] = "已审核转收货";
                    $data[$key]["s_in_s_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_in_s_status_name"] = "已退货报损";
                };
                    break;
                case 3: {
                    $data[$key]["s_in_s_status_name"] = "部分退货报损、部分收货";
                };
                    break;
            }
            switch ($val["s_in_s_type"]) {
                case 0: {
                    $data[$key]["s_in_s_type_name"] = "仓库出库";
                    $sql = "select w_out_s_sn from hii_store_in SI ";
                    $sql .= "left join hii_store_in_stock SIS on SIS.s_in_id=SI.s_in_id ";
                    $sql .= "left join hii_warehouse_out_stock WOS on WOS.w_out_s_id=SI.w_out_s_id ";
                    $sql .= "where SIS.s_in_s_id={$val["s_in_s_id"]} limit 1 ";
                    $rel_orders_datas = $StoreInModel->query($sql);
                    if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                        $data[$key]["rel_orders"] = $rel_orders_datas[0]["w_out_s_sn"];
                    }
                };
                    break;
                case 1: {
                    $data[$key]["s_in_s_type_name"] = "门店调拨";
                    $sql = "select s_out_s_sn from hii_store_in SI ";
                    $sql .= "left join hii_store_in_stock SIS on SIS.s_in_id=SI.s_in_id ";
                    $sql .= "left join hii_store_out_stock SOS on SOS.s_out_s_id=SI.s_out_s_id ";
                    $sql .= "where SIS.s_in_s_id={$val["s_in_s_id"]} limit 1 ";
                    $rel_orders_datas = $StoreInModel->query($sql);
                    if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                        $data[$key]["rel_orders"] = $rel_orders_datas[0]["s_out_s_sn"];
                    }
                };
                    break;
                case 2: {
                    $data[$key]["s_in_s_type_name"] = "盘盈入库";
                    $rel_orders_datas = $StoreInventoryModel->query("select si_sn from hii_store_inventory where si_id={$val["si_id"]} limit 1 ");
                    if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                        $data[$key]["rel_orders"] = $rel_orders_datas[0]["si_sn"];
                    }
                };
                    break;
                case 3: {
                    $data[$key]["s_in_s_type_name"] = "其他";
                };
                    break;
                case 4: {
                    $data[$key]["s_in_s_type_name"] = "采购";
                    $sql = "select p_sn from hii_store_in SI ";
                    $sql .= "left join hii_store_in_stock SIS on SIS.s_in_id=SI.s_in_id ";
                    $sql .= "left join hii_purchase P on P.p_id=SI.p_id ";
                    $sql .= "where SIS.s_in_s_id={$val["s_in_s_id"]} limit 1 ";
                    $rel_orders_datas = $StoreInModel->query($sql);
                    if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                        $data[$key]["rel_orders"] = $rel_orders_datas[0]["p_sn"];
                    }
                };
                    break;
                case 5: {
                    $data[$key]["s_in_s_type_name"] = "寄售";
                    $rel_orders_datas = $StoreInventoryModel->query("select c_in_sn from hii_consignment_in where c_in_id={$data["c_id"]} limit 1 ");
                    if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                        $data[$key]["rel_orders"] = $rel_orders_datas[0]["c_in_sn"];
                    }
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["data"] = $this->isArrayNull($data);

        return $result;
    }

    private function getSingleStoreInStockDetail()
    {
        $s_in_s_id = I("get.s_in_s_id");
        if (is_null($s_in_s_id) || empty($s_in_s_id)) {
            $this->response(0, "请选择要查看的门店收货单");
        }
        $store_id = $this->_store_id;

        $StoreInStockModel = M("StoreInStock");
        $StoreInventoryModel = M("StoreInventory");
        $ConsignmentInModel = M("ConsignmentIn");
        $StoreInModel = M("StoreIn");

        $sql = "select A.s_in_s_id,A.si_id,A.s_in_s_sn,A.s_in_s_status,A.s_in_s_type,A.s_in_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname, ";
        $sql .= "M3.nickname as padmin_nickname,W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2,SY.s_name as supply_name ";
        $sql .= "from hii_store_in_stock A ";
        $sql .= "left join hii_store_in_stock_detail A1 on A1.s_in_s_id=A.s_in_s_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_supply SY on SY.s_id=A.supply_id ";
        $sql .= "where A.s_in_s_id={$s_in_s_id} and A.store_id2={$store_id} ";
        $sql .= "GROUP BY A.s_in_s_id,A.s_in_s_sn,A.s_in_s_status,A.s_in_s_type,A.s_in_id,A.ctime,A.admin_id,A.etime,A.eadmin_id,A.ptime,A.si_id,";
        $sql .= "A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,A.remark,A.g_type,A.g_nums,M1.nickname,M2.nickname,M3.nickname,W.w_name,S1.title,S2.title,SY.s_name ";
        $sql .= "order by A.s_in_s_id desc limit 1";

        //echo $sql;exit;

        $datas = $StoreInStockModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "该信息不存在");
        }
        $data = $datas[0];
        $data["rel_orders"] = "";//关联单号
        switch ($data["s_in_s_status"]) {
            case 0: {
                $data["s_in_s_status_name"] = "新增";
            };
                break;
            case 1: {
                //$data["s_in_s_status_name"] = "已审核转收货";
                $data["s_in_s_status_name"] = "已审核";
            };
                break;
            case 2: {
                $data["s_in_s_status_name"] = "已退货报损";
            };
                break;
            case 3: {
                $data["s_in_s_status_name"] = "部分退货报损、部分收货";
            };
                break;
        }
        switch ($data["s_in_s_type"]) {
            case 0: {
                $data["s_in_s_type_name"] = "仓库出库";
                $sql = "select w_out_s_sn from hii_store_in SI ";
                $sql .= "left join hii_store_in_stock SIS on SIS.s_in_id=SI.s_in_id ";
                $sql .= "left join hii_warehouse_out_stock WOS on WOS.w_out_s_id=SI.w_out_s_id ";
                $sql .= "where SIS.s_in_s_id={$data["s_in_s_id"]} limit 1 ";
                $rel_orders_datas = $StoreInModel->query($sql);
                if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                    $data["rel_orders"] = $rel_orders_datas[0]["w_out_s_sn"];
                }
            };
                break;
            case 1: {
                $data["s_in_s_type_name"] = "门店调拨";
                $sql = "select s_out_s_sn from hii_store_in SI ";
                $sql .= "left join hii_store_in_stock SIS on SIS.s_in_id=SI.s_in_id ";
                $sql .= "left join hii_store_out_stock SOS on SOS.s_out_s_id=SI.s_out_s_id ";
                $sql .= "where SIS.s_in_s_id={$data["s_in_s_id"]} limit 1 ";
                $rel_orders_datas = $StoreInModel->query($sql);
                if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                    $data["rel_orders"] = $rel_orders_datas[0]["s_out_s_sn"];
                }
            };
                break;
            case 2: {
                $data["s_in_s_type_name"] = "盘盈入库";
                $rel_orders_datas = $StoreInventoryModel->query("select si_sn from hii_store_inventory where si_id={$data["si_id"]} limit 1 ");
                if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                    $data["rel_orders"] = $rel_orders_datas[0]["si_sn"];
                }
            };
                break;
            case 3: {
                $data["s_in_s_type_name"] = "其他";
            };
                break;
            case 4: {
                $data["s_in_s_type_name"] = "采购";
                $sql = "select p_sn from hii_store_in SI ";
                $sql .= "left join hii_store_in_stock SIS on SIS.s_in_id=SI.s_in_id ";
                $sql .= "left join hii_purchase P on P.p_id=SI.p_id ";
                $sql .= "where SIS.s_in_s_id={$data["s_in_s_id"]} limit 1 ";
                $rel_orders_datas = $StoreInModel->query($sql);
                if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                    $data["rel_orders"] = $rel_orders_datas[0]["p_sn"];
                }
            };
                break;
            case 5: {
                $data["s_in_s_type_name"] = "寄售";
                $rel_orders_datas = $ConsignmentInModel->query("select c_in_sn from hii_consignment_in where c_in_id={$data["c_id"]} limit 1 ");
                if (!is_null($rel_orders_datas) && !empty($rel_orders_datas) && count($rel_orders_datas) > 0) {
                    $data["rel_orders"] = $rel_orders_datas[0]["c_in_sn"];
                }
            };
                break;
        }

        $StoreInStockDetailModel = M("StoreInStockDetail");
        $sql = "select A1.s_in_s_d_id,A1.goods_id,'' as g_price,G.title as goods_name,GC.title as cate_name,A1.remark, ";
        $sql .= "ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,FROM_UNIXTIME(A1.endtime,'%Y-%m-%d %H:%i:%s') as endtime,A1.endtime as endtimestamp, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_in_stock_detail A1 ";
        $sql .= "left join hii_store_in_stock A on A.s_in_s_id=A1.s_in_s_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id  ";
        $sql .= "where A1.s_in_s_id={$s_in_s_id} ";
        $sql .= "order by A1.goods_id asc ";

        //echo $sql;exit;

        $list = $StoreInStockDetailModel->query($sql);
        $g_amounts = 0;
        foreach ($list as $key => $val) {
            $price = 0;
            if (!is_null($val["store_price"]) && !empty($val["store_price"]) && $val["store_price"] > 0) {
                $price = $val["store_price"];
            } elseif (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $price = $val["shequ_price"];
            } elseif (!is_null($val["sys_price"]) && !empty($val["sys_price"])) {
                $price = $val["sys_price"];
            }
            $list[$key]["sell_price"] = $price;
            $g_amounts += $val["g_num"] * $price;
        }

        $data["g_amounts"] = $g_amounts;
        $result["maindata"] = $data;
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

}