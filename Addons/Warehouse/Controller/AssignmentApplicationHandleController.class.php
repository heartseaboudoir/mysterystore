<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-10-30
 * Time: 14:06
 * 调拨申请处理
 */

namespace Addons\Warehouse\Controller;


use Admin\Controller\AddonsController;

class AssignmentApplicationHandleController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();//检测是否已选择仓库
    }

    /*************************
     * 调拨申请处理列表
     *******************************/
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list_type = I("list_type");
        $isprint = I("isprint");
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
        $this->assign("s_date", $s_date);
        $this->assign("e_date", $e_date);
        if (is_null($list_type) || empty($list_type) || $list_type == 1) {
            $this->assign("list_type", 1);
            $excelData = $this->autoSortByOrder($s_date, $e_date);
        } else {
            $this->assign("list_type", 2);
            $excelData = $this->allOrder($s_date, $e_date);
        }
        if (!is_null($isprint) && $isprint == 1) {
            if ($list_type == 1) {
                $this->exportAutoSortByOrder($s_date, $e_date, $excelData);
            } elseif ($list_type == 2) {
                $this->exportAllOrder($s_date, $e_date, $excelData);
            }
        }
        $this->display(T('Addons://Warehouse@AssignmentApplicationHandle/index'));
    }


    /**************
     * 导出自动分单Excel
     * @param $s_date 开始日期
     * @param $e_date 结束日期
     * @param $excelData 导出数据
     */
    private function exportAutoSortByOrder($s_date, $e_date, $excelData)
    {
        ob_clean;
        $title = $s_date . "__" . $e_date . "调拨申请列表自动分单";
        $fname = $title;
        $printmodel = new \Addons\Report\Model\WarehouseRequestReportModel();
        $printfile = $printmodel->exportWarehouseRequestAutoSortByOrderListExcel($excelData, $title, $fname);
        echo($printfile);
        die;
    }

    /*************
     * 导出所有申请Excel
     * @param $s_date 开始日期
     * @param $e_date 结束日期
     * @param $excelData 导出数据
     */
    private function exportAllOrder($s_date, $e_date, $excelData)
    {
        ob_clean;
        $title = $s_date . "__" . $e_date . "调拨申请列表全部单";
        $fname = $title;
        $printmodel = new \Addons\Report\Model\WarehouseRequestReportModel();
        $printfile = $printmodel->exportWarehouseRequestAllOrderListExcel($excelData, $title, $fname);
        echo($printfile);
        die;
    }

    /******************
     * 自动分单
     ******************/
    private function autoSortByOrder($s_date, $e_date)
    {
        //查找所有还未所有处理的调拨申请单
        $WarehouseRequestModel = M("WarehouseRequest");

        //hii_warehouse_request 的 w_r_status 状态:0.新增,1.已审核申请,2.部分通过申请,3.全部拒绝,4.已作废',

        /*
        $sql = " select A.w_r_sn,A.w_r_id,A.remark,m.nickname,w.w_name,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime ";
        $sql .= " from hii_warehouse_request A ";
        $sql .= " left join hii_member m on A.admin_id=m.uid ";
        $sql .= " left join hii_warehouse w on A.warehouse_id1=w.w_id ";
        $sql .= " where  ( A.w_r_status<>3 and A.w_r_status<>4 and A.w_r_status<>5 ) and A.warehouse_id2={$this->_warehouse_id} and FROM_UNIXTIME(ctime,'%Y-%m-%d')  between '$s_date' and '$e_date' ";
        $sql .= " order by A.w_r_id desc ";*/

        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= " A.warehouse_id1 in (" . implode(",", $can_warehouse_id_array) . ") or A.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = " select A.warehouse_id1 as warehouse_id ";
        $sql .= " from `hii_warehouse_request` A ";
        $sql .= " left join (SELECT * from hii_warehouse_request_detail where is_pass=0 and w_r_d_id not in (SELECT w_r_d_id from hii_warehouse_out_detail ) ) A1 on A1.w_r_id=A.w_r_id   ";
        $sql .= " and A.warehouse_id2={$this->_warehouse_id} {$shequ_where} and FROM_UNIXTIME(ctime,'%Y-%m-%d')  between '$s_date' and '$e_date'   ";
        $sql .= " group by A.warehouse_id1 ";

        //echo $sql;exit;

        $list = $WarehouseRequestModel->query($sql);
        //分页
        $pcount = 10;
        $count = count($list);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = array_slice($list, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        //查找每个调拨申请还未处理的明细
        if (!is_null($list)) {
            $WarehouseRequestDetailModel = M("WarehouseRequestDetail");
            foreach ($list as $key => $val) {
                $warehouse_id = $val["warehouse_id"];
                $sql = " select A1.goods_id,A1.g_num,g.title as goods_name,ifnull(AV.bar_code,g.bar_code)bar_code,A1.w_r_d_id,A.w_r_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
                $sql .= " m.nickname,A1.remark,w.w_name,B.g_price,B.s_name,ifnull(WS.num,0) as stock_num,AV.value_id,AV.value_name ";
                $sql .= " from hii_warehouse_request_detail A1 ";
                $sql .= " left join hii_warehouse_request A on A.w_r_id = A1.w_r_id ";
                $sql .= " left join hii_goods g on A1.goods_id=g.id ";
                $sql .= " left join hii_member m on A.admin_id=m.uid ";
                $sql .= " left join hii_warehouse w on A.warehouse_id1=w.w_id ";
                $sql .= " left join hii_goods_last_purchase_price_view B on A1.goods_id=B.goods_id ";
                $sql .= " left join hii_warehouse_stock WS on WS.w_id=A.warehouse_id2 and WS.value_id=A1.value_id ";
                $sql .= " left join hii_attr_value AV on AV.value_id=A1.value_id ";
                $sql .= " where A.warehouse_id1={$warehouse_id} and A.warehouse_id2={$this->_warehouse_id} and A1.is_pass=0 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '$s_date' and '$e_date'  ";
                $sql .= " and A1.w_r_d_id not in (SELECT w_r_d_id from hii_warehouse_out_detail ) ";
                $sql .= " order by A1.w_r_d_id desc";
                //echo $sql;echo ";<br/>";

                $WarehouseRequestDetailList = $WarehouseRequestDetailModel->query($sql);
                if (!is_null($WarehouseRequestDetailList) && !empty($WarehouseRequestDetailList) && count($WarehouseRequestDetailList) > 0) {
                    $list[$key]["detail"] = $WarehouseRequestDetailList;
                } else {
                    unset($list[$key]);
                }
            }
        }

        //exit;

        $this->assign("list", $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', count($list));

        $this->meta_title = "调拨申请列表自动分单";
        return $list;
    }

    /********************************
     * 全部申请
     *******************************/
    private function allOrder($s_date, $e_date)
    {
        $WarehouseRequestModel = M("WarehouseRequest");

        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= " A.warehouse_id1 in (" . implode(",", $can_warehouse_id_array) . ") or A.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = " select A.w_r_sn,A1.g_num,g.title as goods_name,ifnull(AV.bar_code,g.bar_code)bar_code,A1.g_num,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,";
        $sql .= " w.w_name,m.nickname,A1.remark,B.g_price,B.s_name,0 as is_select,A1.goods_id,A1.w_r_d_id,ifnull(WS.num,0) as stock_num,AV.value_id,AV.value_name ";
        $sql .= " from `hii_warehouse_request` A ";
        $sql .= " left join `hii_warehouse_request_detail` A1 on A1.w_r_id = A.w_r_id ";
        $sql .= " left join hii_warehouse w on A.warehouse_id1=w.w_id ";
        $sql .= " left join hii_member m on A.admin_id=m.uid ";
        $sql .= " left join hii_goods g on A1.goods_id=g.id ";
        $sql .= " left join hii_goods_last_purchase_price_view B on A1.goods_id=B.goods_id ";
        $sql .= " left join hii_warehouse_stock WS on WS.w_id=A.warehouse_id2 and WS.value_id=A1.value_id ";
        $sql .= " left join hii_attr_value AV on AV.value_id=A1.value_id ";
        $sql .= " where  A.warehouse_id2={$this->_warehouse_id} {$shequ_where} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '$s_date' and '$e_date' and A1.is_pass=0 ";
        $sql .= " and A1.w_r_d_id not in (SELECT w_r_d_id from hii_warehouse_out_detail ) ";
        $sql .= " order by A1.w_r_d_id desc ";

        $list = $WarehouseRequestModel->query($sql);

        //print_r($list);

        //分页
        $pcount = 10;
        $count = count($list);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = array_slice($list, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿


        $this->assign("list", $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);
        $this->meta_title = "调拨申请列表全部申请";

        return $list;
    }

    /*****************************
     * 拒绝申请
     ****************************/
    public function reject()
    {
        $w_r_d_id = I("id");
        /***********检测是否具备操作该明细的条件********************/
        $WarehouseRequestDetailModel = M("WarehouseRequestDetail");
        $data = $WarehouseRequestDetailModel
            ->where(" w_r_d_id={$w_r_d_id} and is_pass=0 ")
            ->order(" w_r_d_id desc ")
            ->limit(1)->select();
        if (is_null($data) || empty($data)) {
            $this->error("找不到要拒绝的数据");
        }
        $WarehouseRequestDetailEntity = $data[0];
        $w_r_id = $WarehouseRequestDetailEntity["w_r_id"];

        /************查找主表信息，判断请求调拨仓库是否当前仓库**************/
        $WarehouseRequestModel = M("WarehouseRequest");
        $WarehouseRequestEntityData = $WarehouseRequestModel
            ->where(" w_r_id={$w_r_id} and warehouse_id2={$this->_warehouse_id} ")
            ->order(" w_r_id desc ")
            ->limit(1)
            ->select();
        if (is_null($WarehouseRequestEntityData) || empty($WarehouseRequestEntityData)) {
            $this->error("找不到要拒绝的数据");
        }

        /*********************同时更新明细表和主表状态**********************************/
        $WarehouseRequestRepository = D('Addons://Warehouse/WarehouseRequest');
        $res = $WarehouseRequestRepository->rejectWarehouseRequestAssgin($WarehouseRequestEntityData[0], $w_r_d_id,false);
        if ($res > 0) {
            $this->success('拒绝成功');
        } else {
            $this->error($WarehouseRequestRepository->err['msg']);
        }
    }

    /**********************
     * 生成出库抓货单
     * 提示：只能来自同一申请单
     *********************/
    public function generateWarehouseOutOrder()
    {
        $w_r_d_id_array = I("post.selectprdid");//需要生成的hii_warehouse_request_detail的ID
        $remark = I("post.remark");
        if (is_null($w_r_d_id_array) || empty($w_r_d_id_array) || count($w_r_d_id_array) == 0) {
            $this->error("请选择要生成出库验货单的商品");
        }
        $WarehouseRequestRepository = D("Addons://Warehouse/WarehouseRequest");
        $admin_id = UID;
        $warehouse_id = $this->_warehouse_id;
        $res = $WarehouseRequestRepository->generateWarehouseRequestOutOrder($warehouse_id, $admin_id, $w_r_d_id_array, $remark);
        if ($res["status"] == "200") {
            $this->success("操作成功");
        } else {
            $this->error($res["msg"]);
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