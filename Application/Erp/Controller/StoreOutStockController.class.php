<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-01
 * Time: 15:00
 * 出库单相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreOutStockController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /****************
     * 出库单接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     * 日期：2017-12-01
     * 注意：
     */
    public function index()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreOutStockModel = M("StoreOutStock");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id1 in (" . implode(",", $can_store_id_array) . ") or A.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " A.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " A.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = "select A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id,A.c_id, FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname as admin_nickname, S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "W.w_name as warehouse_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_out_stock A  ";
        $sql .= "left join hii_store_stock_detail A1 on A1.s_out_s_id=A.s_out_s_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id  ";
        $sql .= "left join hii_store_out SO on SO.s_out_id=A.s_out_id  ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id  ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1  ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2  ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id  ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "where A.store_id2={$this->_store_id} {$shequ_where} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "group by A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id,A.ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname, S1.title,S2.title, ";
        $sql .= "W.w_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums ";
        $sql .= "order by A.s_out_s_id desc ";

        $data = $StoreOutStockModel->query($sql);
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            //`s_out_s_status` int(1) DEFAULT '0' COMMENT '状态:0.新增,1.已审核转出库,2.已拒绝,3.部分拒绝',
            //`s_out_s_type` int(1) DEFAULT '0' COMMENT '来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它',
            $StoreToStoreModel = M("StoreToStore");
            $ConsignOutModel = M("ConsignmentOut");
            switch ($val["s_out_s_status"]) {
                case 0: {
                    $datamain[$key]["s_out_s_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$datamain[$key]["s_out_s_status_name"] = "已审核转出库";
                    $datamain[$key]["s_out_s_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $datamain[$key]["s_out_s_status_name"] = "已拒绝";
                };
                    break;
                case 3: {
                    $datamain[$key]["s_out_s_status_name"] = "部分拒绝";
                };
                    break;
            }
            switch ($val["s_out_s_type"]) {
                case 0: {
                    $datamain[$key]["s_out_s_type_name"] = "仓库调拨";
                };
                    break;
                case 1: {
                    $datamain[$key]["s_out_s_type_name"] = "门店申请";
                    $tmp = $StoreToStoreModel->query("select s_t_s_sn from hii_store_to_store where s_t_s_id in ({$val["s_r_id"]}) ");
                    $str = "";
                    foreach ($tmp as $k => $v) {
                        $str .= $v["s_t_s_sn"] . ",";
                    }
                    $datamain[$key]["rel_orders"] = !empty($str) ? substr($str, 0, strlen($str) - 1) : $str;
                };
                    break;
                case 3: {
                    $datamain[$key]["s_out_s_type_name"] = "盘亏出库";
                };
                    break;
                case 4: {
                    $datamain[$key]["s_out_s_type_name"] = "其它";
                };
                    break;
                case 5: {
                    $datamain[$key]["s_out_s_type_name"] = "寄售";
                    $tmp = $ConsignOutModel->query(" select c_out_sn from hii_consignment_out where c_out_id={$val["c_id"]} limit 1 ");
                    if ($this->isArrayNull($tmp) != null) {
                        $datamain[$key]["rel_orders"] = $tmp[0]["c_out_sn"];
                    }
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;

        $result["data"] = $this->isArrayNull($datamain);
        $this->response(self::CODE_OK, $result);
    }

    /***************
     * 查看接口
     * 请求方式：GET
     * 请求参数：s_out_s_id  出库单ID  必须
     * 日期：2017-12-04
     */
    public function view()
    {
        $s_out_s_id = I("get.s_out_s_id");
        if (is_null($s_out_s_id) || empty($s_out_s_id)) {
            $this->response(0, "请选择要查看的出库单");
        }
        $result = $this->getSingleStoreOutStockDetail($s_out_s_id);
        $this->response(self::CODE_OK, $result);
    }


    /**************
     * 导出列表Excel文档接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * 日期：2017-12-04
     */
    public function exportStoreOutStockListExcel()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreOutStockModel = M("StoreOutStock");
        $sql = "select A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id,A.c_id, FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname as admin_nickname, S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "W.w_name as warehouse_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_out_stock A  ";
        $sql .= "left join hii_store_stock_detail A1 on A1.s_out_s_id=A.s_out_s_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id  ";
        $sql .= "left join hii_store_out SO on SO.s_out_id=A.s_out_id  ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id  ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1  ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2  ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id  ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$this->_store_id} and GS.goods_id=A1.goods_id ";
        $sql .= "where A.store_id2={$this->_store_id} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "group by A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id,A.ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname, S1.title,S2.title, ";
        $sql .= "W.w_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums ";
        $data = $StoreOutStockModel->query($sql);
        foreach ($data as $key => $val) {
            //`s_out_s_status` int(1) DEFAULT '0' COMMENT '状态:0.新增,1.已审核转出库,2.已拒绝,3.部分拒绝',
            //`s_out_s_type` int(1) DEFAULT '0' COMMENT '来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它',
            $StoreToStoreModel = M("StoreToStore");
            $ConsignOutModel = M("ConsignmentOut");
            switch ($val["s_out_s_status"]) {
                case 0: {
                    $data[$key]["s_out_s_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$data[$key]["s_out_s_status_name"] = "已审核转出库";
                    $data[$key]["s_out_s_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_out_s_status_name"] = "已拒绝";
                };
                    break;
                case 3: {
                    $data[$key]["s_out_s_status_name"] = "部分拒绝";
                };
                    break;
            }
            switch ($val["s_out_s_type"]) {
                case 0: {
                    $data[$key]["s_out_s_type_name"] = "仓库调拨";
                };
                    break;
                case 1: {
                    $data[$key]["s_out_s_type_name"] = "门店申请";
                    $tmp = $StoreToStoreModel->query("select s_t_s_sn from hii_store_to_store where s_t_s_id in ({$val["s_r_id"]}) ");
                    $str = "";
                    foreach ($tmp as $k => $v) {
                        $str .= $v["s_t_s_sn"] . ",";
                    }
                    $data[$key]["rel_orders"] = !empty($str) ? substr($str, 0, strlen($str) - 1) : $str;
                };
                    break;
                case 3: {
                    $data[$key]["s_out_s_type_name"] = "盘亏出库";
                };
                    break;
                case 4: {
                    $data[$key]["s_out_s_type_name"] = "其它";
                };
                    break;
                case 5: {
                    $datamain[$key]["s_out_s_type_name"] = "寄售";
                    $tmp = $ConsignOutModel->query(" select c_out_sn from hii_consignment_out where c_out_id={$val["c_id"]} limit 1 ");
                    if ($this->isArrayNull($tmp) != null) {
                        $datamain[$key]["rel_orders"] = $tmp[0]["c_out_sn"];
                    }
                };
                    break;
            }
        }

        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店出库单';
        $fname = './Public/Excel/StoreOutStock_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOutStockModel();
        $printfile = $printmodel->createStoreOutStockListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }


    /*******************
     * 导出单个出库单明细Excel
     * 请求方式：GET
     * 请求参数：s_out_s_id  出库单ID  必须
     */
    public function exportStoreOutStockViewExcel()
    {
        $s_out_s_id = I("get.s_out_s_id");
        if (is_null($s_out_s_id) || empty($s_out_s_id)) {
            $this->response(0, "请选择要查看的出库单");
        }
        $result = $this->getSingleStoreOutStockDetail($s_out_s_id);
        ob_clean;
        $title = '出库单查看';
        $fname = './Public/Excel/StoreOutStockView_' . $result["maindata"]["s_out_s_sn"] . '_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOutStockModel();
        $printfile = $printmodel->createStoreOutStockViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }


    /********************
     * 出库单审核接口
     * 请求方式：POST
     * 请求参数：s_out_s_id  出库单ID  必须
     * 日期：2017-12-05
     * 注意：
     */
    public function check()
    {
        $s_out_s_id = I("post.s_out_s_id");
        if (is_null($s_out_s_id) || empty($s_out_s_id)) {
            $this->response(0, "请选择要审核的出库单");
        }
        $padmin_id = UID;
        $store_id = $this->_store_id;
        $StoreOutStockRepository = D("StoreOutStock");
        $result = $StoreOutStockRepository->check($s_out_s_id, $padmin_id, $store_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, $result["msg"]);
        } else {
            $this->response(0, $result["msg"]);
        }
    }


    /***************
     * 获取当前页
     ***************/
    private
    function getPageIndex()
    {
        $p = I("get.p");
        return is_null($p) || empty($p) ? 1 : $p;
    }

    /************************
     * 获取搜索日期
     * s_date：开始日期
     * e_date：结束日期
     *****************************/
    private
    function getDates()
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
    private
    function getPageSize()
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

    /*****************************
     * 获取单个出库单明细信息
     * @param $s_out_s_id 出库单号
     */
    private function getSingleStoreOutStockDetail($s_out_s_id)
    {
        $StoreOutStockModel = M("StoreOutStock");
        $StoreStockDetailModel = M("StockStockDetail");

        $sql = "select A.s_out_s_id,A.s_out_s_sn,A.s_out_s_status,A.s_out_s_type,A.s_out_id,A.si_id, FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,A.store_id1,A.store_id2,A.warehouse_id,M.nickname as admin_nickname, S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "W.w_name as warehouse_name,SO.s_out_sn,SO.s_r_id,SO.w_r_id,SO.s_o_out_id, A.remark,A.g_type,A.g_nums  ";
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

        //sys_price 系统售价  shequ_price 区域价  store_price 门店售价
        $sql = "select A1.goods_id,A1.g_num,A1.g_price,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,ifnull(GS.num,0) as stock_num,GC.title as cate_name, ";
        $sql .= "ifnull(A1.g_num,0) as  sod_g_num,ifnull(SOD.in_num,0) as sod_in_num,ifnull(SOD.out_num,0) as sod_out_num,A1.remark, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_stock_detail A1 ";
        $sql .= "left join hii_store_out_detail SOD on SOD.s_out_d_id=A1.s_out_d_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id  ";
        $sql .= "where A1.s_out_s_id={$s_out_s_id} ";
        //echo $sql;exit;
        $sql .= "order by A1.goods_id asc ";
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
        $result = array();
        $result["maindata"] = $data;
        $result["list"] = $list;

        return $result;
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