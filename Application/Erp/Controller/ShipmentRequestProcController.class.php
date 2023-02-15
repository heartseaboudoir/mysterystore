<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-17
 * Time: 11:50
 * 发货申请相关接口【无效】
 */

namespace Erp\Controller;

use Think\Controller;

class ShipmentRequestProcController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_warehouse();
    }

    /************************
     * 获取发货申请列表接口
     * 请求方式：GET
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     */
    public function index()
    {
        $warehouse_id = $this->_warehouse_id;

        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.w_r_id,A.s_r_id,A.o_out_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.store_id,A.warehouse_id1,A.warehouse_id2,A.remark,A.g_type,A.g_nums, ";
        $sql .= "M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname,S.title as store_name, ";
        $sql .= "W1.w_name as warehouse_name1,W2.w_name as warehouse_name2,sum(A1.g_num*G.sell_price) as g_amount  ";
        $sql .= "from hii_warehouse_out A ";
        $sql .= "left join hii_warehouse_out_detail A1 on A1.w_out_id=A.w_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id1 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "where A.warehouse_id2={$warehouse_id} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "GROUP BY w_out_id,w_out_sn,w_out_status,w_out_type,w_r_id,A.s_r_id,o_out_id,A.ctime, 
admin_id,etime,eadmin_id,ptime, 
padmin_id,store_id,warehouse_id1,warehouse_id2,remark,g_type,g_nums,admin_nickname,
eadmin_nickname,padmin_nickname,store_name,warehouse_name1,
warehouse_name2 ";
        $sql .= "order by A.ctime desc ";
        //echo $sql;exit;
        $data = $WarehouseOutModel->query($sql);

        //分页
        $pcount = 15;
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            switch ($val["w_out_type"]) {
                case 0: {
                    $datamain[$key]["w_out_type_name"] = "仓库调拨";
                };
                    break;
                case 1: {
                    $datamain[$key]["w_out_type_name"] = "门店申请";
                };
                    break;
                case 2: {
                    $datamain[$key]["w_out_type_name"] = "退货报损";
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
        $result["data"] = (is_null($datamain) || empty($datamain) || count($datamain) == 0 || $datamain[0]["w_out_id"] == null) ? null : $datamain;

        $this->response(self::CODE_OK, $result);

    }


    /***********************
     * 获取单个出库验货单
     * 请求方式：GET
     * 请求参数：w_out_id  出库验货单ID  必须
     */
    public function getSingleWarehouseOutInfo()
    {
        $w_out_id = I("get.w_out_id");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($w_out_id) || empty($w_out_id)) {
            $this->response(0, "请选择要查看的出库验货单");
        }
        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.w_r_id,A.s_r_id,A.o_out_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.store_id,A.warehouse_id1,A.warehouse_id2,A.remark,A.g_type,A.g_nums, ";
        $sql .= "M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname,S.title as store_name, ";
        $sql .= "W1.w_name as warehouse_name1,W2.w_name as warehouse_name2,sum(A1.g_num*G.sell_price) as g_amount  ";
        $sql .= "from hii_warehouse_out A ";
        $sql .= "left join hii_warehouse_out_detail A1 on A1.w_out_id=A.w_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id1 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "where A.w_out_id={$w_out_id} and A.warehouse_id2={$warehouse_id}  order by A.ctime desc limit 1 ";
        //echo $sql;exit;
        $data = $WarehouseOutModel->query($sql);
        if (is_null($data) || empty($data) || count($data) == 0) {
            $this->response(0, "不存在该申请");
        }

        $WarehouseOutDetail = M("WarehouseOutDetail");
        $sql = "select A.w_out_d_id,A.w_out_id,A.goods_id,A.g_num,A.in_num,A.out_num,A.g_price,A.w_r_d_id,A.s_r_d_id, ";
        $sql .= "G.title as goods_name,G.bar_code,G.sell_price,GC.title as cate_name ";
        $sql .= "from hii_warehouse_out_detail A ";
        $sql .= "left join hii_goods G on G.id=A.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where A.w_out_id={$w_out_id} ";
        $list = $WarehouseOutDetail->query($sql);

        $result = array();
        $result["maindata"] = $data[0];
        $result["list"] = $list;
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 修改出库验货单
     * 请求方式：POST
     * 请求参数：info_json_str 修改信息json字符串 必须 [{"w_out_d_id":"1","in_num":"20","out_num":"2"},{"w_out_d_id":"2","in_num":"15","out_num":"0"}]
     *           remark  备注  非必需
     * 日期：2017-11-20
     */
    public function updateWarehouseOutDetailInfo()
    {
        $info_array = I("post.info_json_str");
        $remark = I("post.remark");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($info_array) || empty($info_array) || count($info_array) == 0) {
            $this->response(0, "请提交要修改的信息");
        }
        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id ";
        $sql .= "from hii_warehouse_out A ";
        $sql .= "left join hii_warehouse_out_detail A1 on A1.w_out_id=A.w_out_id ";
        $sql .= "where A.w_out_status=0 and A1.w_out_d_id={$info_array[0]["w_out_d_id"]} and A.warehouse_id2={$warehouse_id} limit 1 ";
        $datas = $WarehouseOutModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "提交数据有误");
        }
        $w_out_id = $datas[0]["w_out_id"];
        $ShipmentRequestProcRepository = D("ShipmentRequestProc");
        $res = $ShipmentRequestProcRepository->updateWarehouseOutDetailInfo($w_out_id, $remark, $info_array);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /********************
     * 审核出库接口
     * 请求方式：POST
     * 请求参数：w_out_id 出库验货单主表ID 必须
     * 日期：2017-11-20
     */
    public function checkForWarehouseOut()
    {
        $w_out_id = I("post.w_out_id");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($w_out_id) || empty($w_out_id)) {
            $this->response(0, "请选择要审核的出库验货单");
        }
        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id from hii_warehouse_out A where A.w_out_status=0 and A.warehouse_id2={$warehouse_id} order by A.w_out_id desc limit 1 ";
        $datas = $WarehouseOutModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "提交数据有误");
        }
        $ShipmentRequestProcRepository = D("ShipmentRequestProc");
        $admin_id = UID;
        $res = $ShipmentRequestProcRepository->checkForWarehouseOut($w_out_id, $admin_id);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "提交成功");
        } else {
            $this->response(0, "审核失败");
        }
    }

    /**************
     * 全部缺货
     * 请求方式：POST
     * 请求参数：w_out_id 出库验货单主表ID 必须
     * 日期：2017-11-20
     */
    public function allOutOfStock()
    {
        $w_out_id = I("post.w_out_id");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($w_out_id) || empty($w_out_id)) {
            $this->response(0, "请选择要审核的出库验货单");
        }
        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id from hii_warehouse_out A where A.w_out_status=0 and A.warehouse_id2={$warehouse_id} order by A.w_out_id desc limit 1 ";
        $datas = $WarehouseOutModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "提交数据有误");
        }
        $ShipmentRequestProcRepository = D("ShipmentRequestProc");
        $admin_id = UID;
        $res = $ShipmentRequestProcRepository->checkForAllOutOfStock($w_out_id, $admin_id);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "提交成功");
        } else {
            $this->response(0, "审核失败");
        }
    }

    /********************
     * 导出出库验货单列表Excel
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * 日期：2017-11-20
     */
    public function exportWarehouseOutListExcel()
    {
        $warehouse_id = $this->_warehouse_id;

        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.w_r_id,A.s_r_id,A.o_out_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.store_id,A.warehouse_id1,A.warehouse_id2,A.remark,A.g_type,A.g_nums, ";
        $sql .= "M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname,S.title as store_name, ";
        $sql .= "W1.w_name as warehouse_name1,W2.w_name as warehouse_name2,sum(A1.g_num*G.sell_price) as g_amount  ";
        $sql .= "from hii_warehouse_out A ";
        $sql .= "left join hii_warehouse_out_detail A1 on A1.w_out_id=A.w_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id1 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "where A.w_out_status=0 and A.warehouse_id2={$warehouse_id} and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  order by A.ctime desc ";
        //echo $sql;exit;
        $data = $WarehouseOutModel->query($sql);

        ob_clean;
        $title = $s_date . '>>>' . $e_date . '出库验货单';
        $fname = './Public/Excel/WarehouseOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\ShipmentRequestProcModel();
        $printfile = $printmodel->createWarehouseOutListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);

    }

    /**********************
     * 导出单个出库验货单Excel
     * 请求方式：GET
     * 请求参数：$w_out_id 出库验货单ID 必须
     * 日期：2017-11-21
     */
    public function exportSingleWarehouseOutDetailInfoExcel()
    {
        $w_out_id = I("get.w_out_id");
        $warehouse_id = $this->_warehouse_id;
        if (is_null($w_out_id) || empty($w_out_id)) {
            $this->response(0, "请选择要到处的出库验货单");
        }
        $WarehouseOutModel = M("WarehouseOut");
        $sql = "select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.w_r_id,A.s_r_id,A.o_out_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "A.padmin_id,A.store_id,A.warehouse_id1,A.warehouse_id2,A.remark,A.g_type,A.g_nums, ";
        $sql .= "M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname,S.title as store_name, ";
        $sql .= "W1.w_name as warehouse_name1,W2.w_name as warehouse_name2,sum(A1.g_num*G.sell_price) as g_amount  ";
        $sql .= "from hii_warehouse_out A ";
        $sql .= "left join hii_warehouse_out_detail A1 on A1.w_out_id=A.w_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_store S on S.id=A.store_id ";
        $sql .= "left join hii_warehouse W1 on W1.w_id=A.warehouse_id1 ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=A.warehouse_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "where A.w_out_id={$w_out_id} and A.warehouse_id2={$warehouse_id}  order by A.ctime desc limit 1 ";
        //echo $sql;exit;
        $datas = $WarehouseOutModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "不存在该申请");
        }

        $WarehouseOutDetail = M("WarehouseOutDetail");
        $sql = "select A.w_out_d_id,A.w_out_id,A.goods_id,A.g_num,A.in_num,A.out_num,A.g_price,A.w_r_d_id,A.s_r_d_id, ";
        $sql .= "G.title as goods_name,G.bar_code,G.sell_price,GC.title as cate_name ";
        $sql .= "from hii_warehouse_out_detail A ";
        $sql .= "left join hii_goods G on G.id=A.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where A.w_out_id={$w_out_id} ";
        $list = $WarehouseOutDetail->query($sql);

        $data = array();
        $data["maindata"] = $datas[0];
        $data["list"] = $list;

        ob_clean;
        $title = '仓库出库验货单';
        $fname = './Public/Excel/WarehouseOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\ShipmentRequestProcModel();
        $printfile = $printmodel->createSingleWarehouseOutDetailInfoExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
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

}