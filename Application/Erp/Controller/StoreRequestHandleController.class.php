<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-07
 * Time: 16:46
 * 门店发货申请处理接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreRequestHandleController extends AdminController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_warehouse();
    }

    /*******************
     * 申请处理列表接口
     * 请求方式：GET
     * 请求参数：list_type 显示方式  非必须  1：自动分单 2：全部申请  默认1
     *           p         当前页    非必需  默认1
     *           s_date    开始日期  非必需  默认30天前
     *           e_date    结束日期  非必需  默认当天
     */
    public function index()
    {
        $warehouse_id = $this->_warehouse_id;
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择仓库");
        }
        //时间范围默认30天
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $list_type = I("get.list_type");
        $list_order = I("get.list_order",'');
        if(is_null($list_order) || $list_order==''){
            $list_order = array();
        }else{
            $list_order = explode(",",$list_order);
        }
        if (is_null($list_type) || empty($list_type) || $list_type == 1) {
            $result = $this->getListDataByStoreRequest($warehouse_id, $s_date, $e_date,true,$list_order);
        } else {
            $result = $this->getListDataByAll($warehouse_id, $s_date, $e_date,true,$list_order);
        }
        $this->response(self::CODE_OK, $result);
    }

    /****************
     * 导出审核列表Excel
     * 请求方式：GET
     * 请求参数：list_type 显示方式  非必须  1：自动分单 2：全部申请  默认1
     *           s_date    开始日期  非必需  默认30天前
     *           e_date    结束日期  非必需  默认当天
     */
    public function exportCheckListExcel()
    {
        $warehouse_id = $this->_warehouse_id;
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择仓库");
        }
        //时间范围默认30天
        $s_date = I('get.s_date');
        $e_date = I('get.e_date');
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
        $list_type = I("get.list_type");
        $list_order = I("get.list_order",'');
        if(is_null($list_order) || $list_order==''){
            $list_order = array();
        }else{
            $list_order = explode(",",$list_order);
        }

        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店发货申请单';
        $fname = './Public/Excel/StoreRequestCheckList_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreRequestReportModel();
        if (is_null($list_type) || empty($list_type) || $list_type == 1) {
            $data = $this->getListDataByStoreRequest($warehouse_id, $s_date, $e_date, false,$list_order);
            $printfile = $printmodel->createCheckListExcel($data, $title, $fname, 1);
        } else {
            $data = $this->getListDataByAll($warehouse_id, $s_date, $e_date, false,$list_order);
            $printfile = $printmodel->createCheckListExcel($data, $title, $fname, 2);
        }
        $this->response(self::CODE_OK, $printfile);
    }


    /*************
     * 单个申请拒绝接口
     * 请求方式：POST
     * 请求参数：s_r_d_id 门店发货申请明细表ID 必须
     */
    public function reject()
    {
        $s_r_d_id = I("post.s_r_d_id");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择仓库");
        }
        if (is_null($s_r_d_id) || empty($s_r_d_id)) {
            $this->response(0, "请选择要拒绝的申请");
        }
        $StoreRequestRepository = D('StoreRequest');
        $result = $StoreRequestRepository->reject($warehouse_id, $s_r_d_id);
        if ($result["status"] == "0") {
            $this->response(0, $result["msg"]);
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /*************
     * 批量拒绝接口
     * 请求方式：POST
     * 请求参数：s_r_d_ids 门店发货申请明细表ID   必须  格式：[{"s_r_d_id":""},{"s_r_d_id":""}]
     *           remark    备注                   非必须
     */
    public function batchReject()
    {
        $s_r_d_id_array = I("post.s_r_d_ids");
        $remark = I("post.remark");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择仓库");
        }
        if (is_null($s_r_d_id_array) || !is_array($s_r_d_id_array) || count($s_r_d_id_array) == 0) {
            $this->response(0, "请选择要拒绝的申请");
        }
        $StoreRequestRepository = D('StoreRequest');
        $result = $StoreRequestRepository->batchReject(UID, $warehouse_id, $s_r_d_id_array, $remark);
        $this->response($result["status"], $result["msg"]);
    }


    /***************
     * 生成出库
     * 请求方式：POST
     * 请求参数：s_r_d_id_str 门店发货申请明细表s_r_d_id字符串 必须 格式：101,102,103
     *                  remark 备注 非必需
     * 注意：不存在g_price的请求不能提交
     */
    public function generateWarehouseOutOrder()
    {
        $remark = I("post.remark");
        $s_r_d_id_str = I("post.s_r_d_id_str");
        if (is_null($s_r_d_id_str) || empty($s_r_d_id_str)) {
            $this->response(0, "请提交要生成的订单");
        }
        $s_r_d_id_array = explode(",", $s_r_d_id_str);
        if (count($s_r_d_id_array) == 0) {
            $this->response(0, "请提交要生成的订单");
        }
        $admin_id = UID;
        $warehouse_id = $this->_warehouse_id;
        $StoreRequestHandleRepository = D("StoreRequestHandle");
        $res = $StoreRequestHandleRepository->generateWarehouseOutOrder($warehouse_id, $admin_id, $s_r_d_id_array, $remark);
        //\Think\Log::record($res["msg"]);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, $res["msg"]);
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /***************
     * 转门店采购
     * 请求方式：POST
     * 请求参数：info_json  提交信息，格式：[{"s_r_d_id":""},{"s_r_d_id":""}]  必须
     * 日期：2018-01-03
     */
    public function toStorePurchase()
    {
        $info_json = I("post.info_json");
        $remark = I("post.remark");
        if ($this->isArrayNull($info_json) == null) {
            $this->response(0, "请选择需要转门店采购的申请单");
        }
        $StoreRequestHandleRepository = D("StoreRequestHandle");
        $warehouse_id = $this->_warehouse_id;
        $result = $StoreRequestHandleRepository->toStorePurchase(UID, $warehouse_id, $info_json, $remark);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /****************
     * 转仓库采购
     * 请求方式：POST
     * 请求参数：info_json  提交信息，格式：[{"s_r_d_id":""},{"s_r_d_id":""}]  必须
     * 日期：2018-01-03
     */
    public function toWarehousePurchase()
    {
        $info_json = I("post.info_json");
        $remark = I("post.remark");
        if ($this->isArrayNull($info_json) == null) {
            $this->response(0, "请选择需要转仓库采购的申请单");
        }
        $StoreRequestHandleRepository = D("StoreRequestHandle");
        $warehouse_id = $this->_warehouse_id;
        $result = $StoreRequestHandleRepository->toWarehousePurchase(UID, $warehouse_id, $info_json, $remark);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }


    /****************
     * 自动分单列表内容
     * @param $warehouse_id 仓库ID
     * @param $s_date 开始日期
     * @param $e_date 结束日期
     * @param $usePager 是否启用分页
     * @param $list_order 是否排序
     */
    private function getListDataByStoreRequest($warehouse_id, $s_date, $e_date, $usePager = true,$list_order=array())
    {
        $StoreRequestModel = M("StoreRequest");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " A.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = "select A.store_id ";
        $sql .= "from hii_store_request A ";
        $sql .= "left join hii_store_request_detail A1 on A1.s_r_id=A.s_r_id ";
        $sql .= "WHERE A1.is_pass=0 and A.warehouse_id={$this->_warehouse_id} {$shequ_where} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "GROUP BY A.store_id ";
        $data = $StoreRequestModel->query($sql);
        // and s_r_d_id not in (SELECT s_r_d_id from hii_warehouse_out_detail )
        //echo $sql;exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount,array('p'=>$this->getPageIndex(),'pageSize'=>$this->getPageSize(),"list_type"=>I("get.list_type")));// 实例化分页类 传入总记录数和每页显示的记录数
            $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        } else {
            $count = count($data);//得到数组元素个数
            $datamain = $data;
            $show = "";
        }

        $list = array();
        $index = 0;

        foreach ($datamain as $key => $val) {
            $store_id = $val["store_id"];

            //判断是否排序
            if(empty($list_order)){
                $order = "A1.s_r_d_id desc";
            }else{
                if(in_array($store_id,$list_order)){
                    $order = "A1.goods_id";
                }else{
                    $order = "A1.s_r_d_id desc";
                }
            }

            $sql = "select A1.s_r_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,S.title as store_name,S.id as store_id, ";
            $sql .= "B.nickname as nickname,A1.remark as remark,floor(ifnull(WS.num,0)) as stock_num,A1.g_num,A.s_r_sn,AV.value_id,AV.value_name ";
            $sql .= "from hii_store_request A ";
            $sql .= "left join hii_store_request_detail A1 on A1.s_r_id = A.s_r_id ";
            $sql .= "left join hii_goods G on G.id = A1.goods_id ";
            $sql .= "left join hii_store S on S.id = A.store_id ";
            $sql .= "left join hii_member B on A.admin_id=B.uid ";
            $sql .= "left join hii_warehouse_stock WS on WS.w_id=A.warehouse_id and WS.value_id=A1.value_id ";
            $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
            $sql .= "where A.warehouse_id={$warehouse_id} and A.store_id={$store_id} and A1.is_pass=0 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
            $sql .= "and A1.is_pass = 0 ";
            //$sql .= "and A1.s_r_d_id not in (SELECT s_r_d_id from hii_warehouse_out_detail) ";
            $sql .= "order by {$order} ; ";
            //echo $sql ."<br/><br/>";
            $result = $StoreRequestModel->query($sql);
            if (is_null($result) || empty($result) || count($result) == 0) {

            } else {
                $list[$index] = $result;
                $index++;
            }
        }

        //exit;

        $result = array();
        $result["s_date"] = $s_date;
        $result["list_order"] = $list_order;
        $result["e_date"] = $e_date;
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $list;

        //dump($result["data"]);
        //exit();

        return $result;
    }


    /****************
     * 所有申请内容
     * @param $warehouse_id 仓库ID
     * @param $s_date 开始日期
     * @param $e_date 结束日期
     * @param $usePager 是否启用分页
     * @param $list_order 是否排序
     */
    private function getListDataByAll($warehouse_id, $s_date, $e_date, $usePager = true,$list_order=array())
    {
        $StoreRequestModel = M("StoreRequest");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " A.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }
        if(empty($list_order)){
            $order = "A1.s_r_d_id desc";
        }else{
            if(in_array('0',$list_order)){
                $order = "A1.goods_id";
            }else{
                $order = "A1.s_r_d_id desc";
            }
        }
        $sql = " select A1.s_r_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,S.title as store_name,0 as store_id, ";
        $sql .= "B.nickname as nickname,A1.remark as remark,floor(ifnull(WS.num,0)) as stock_num,A1.g_num,A.s_r_sn,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_request A ";
        $sql .= "left join hii_store_request_detail A1 on A1.s_r_id = A.s_r_id ";
        $sql .= "left join hii_goods G on G.id = A1.goods_id ";
        $sql .= "left join hii_store S on S.id = A.store_id ";
        $sql .= "left join hii_member B on A.admin_id=B.uid ";
        $sql .= "left join hii_warehouse_stock WS on WS.w_id=A.warehouse_id and WS.value_id=A1.value_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
        $sql .= "where A.warehouse_id={$warehouse_id} {$shequ_where} and A1.is_pass=0 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "and A1.s_r_d_id not in (SELECT s_r_d_id from hii_warehouse_out_detail) ";
        $sql .= "order by {$order} ";
        //echo $sql;die;
        //echo $sql;exit;
        $data = $StoreRequestModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount,array('p'=>$this->getPageIndex(),'pageSize'=>$this->getPageSize(),"list_type"=>I("get.list_type")));// 实例化分页类 传入总记录数和每页显示的记录数
            $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        } else {
            $datamain = $data;
            $show = "";
            $count = count($datamain);
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["list_order"] = $list_order;
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($datamain);

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