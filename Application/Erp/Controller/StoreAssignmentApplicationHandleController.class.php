<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-27
 * Time: 11:14
 * 门店调拨申请处理
 */

namespace Erp\Controller;

use Think\Controller;

class StoreAssignmentApplicationHandleController extends AdminController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /******************
     * 门店调拨处理列表接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     * 日期：2017-11-27
     * 注意：状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
     */
    public function index()
    {
        $list_type = I("get.list_type");
        $list_type = is_null($list_type) || empty($list_type) ? 1 : $list_type;
        if ($list_type == 1) {
            $this->autoSortByOrder();
        } elseif ($list_type == 2) {
            $this->allOrder();
        }
    }

    /**************
     * 拒绝
     * 请求方式：POST
     * 请求参数：s_t_s_d_id  申请子表ID  必须  格式：10001
     * 日期：2017-11-28
     */
    public function reject()
    {
        $s_t_s_d_id = I("post.s_t_s_d_id");
        if (is_null($s_t_s_d_id) || empty($s_t_s_d_id)) {
            $this->response(0, "请选择要拒绝的申请");
        }
        $store_id = $this->_store_id;
        $StoreAssignmentApplicationHandleRepository = D("StoreAssignmentApplicationHandle");
        $res = $StoreAssignmentApplicationHandleRepository->reject($store_id, $s_t_s_d_id);
        if ($res["status"] == 200) {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /*********************
     * 生成出库验货单
     * 请求方式：POST
     * 请求参数：s_t_s_d_ids  申请子表ID  必须  格式：10001,10002,10003
     *           remark   备注  非必须
     */
    public function generateStoreOutOrder()
    {
        $s_t_s_d_ids = I("post.s_t_s_d_ids");
        $remark = I("post.remark");
        if (is_null($s_t_s_d_ids) || empty($s_t_s_d_ids)) {
            $this->response(0, "请选择要生成出库验货单的信息");
        }
        $s_t_s_d_id_array = explode(",", $s_t_s_d_ids);
        if (empty($s_t_s_d_id_array) || count($s_t_s_d_id_array) == 0) {
            $this->response(0, "请选择要生成出库验货单的信息");
        }
        $admin_id = UID;
        $store_id = $this->_store_id;
        $StoreAssignmentApplicationHandleRepository = D("StoreAssignmentApplicationHandle");
        $res = $StoreAssignmentApplicationHandleRepository->generateStoreOutOrder($admin_id, $store_id, $s_t_s_d_id_array, $remark);
        if ($res["status"] == "200") {
            //循环加入提醒
            $MessageWarnModel = D('MessageWarn');
            foreach($res['data'] as $key => $value){
                $MessageWarnModel->pushMessageWarn($admin_id  , 0  ,$store_id ,  0 , $value ,5);
            }
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }


    /********************
     * 导出列表Excel
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * 日期：2017-12-06
     */
    public function exportListExcel()
    {
        $list_type = I("get.list_type");
        $list_type = is_null($list_type) || empty($list_type) ? 1 : $list_type;
        if ($list_type == 1) {
            $this->exprotAutoSortByOrder();
        } elseif ($list_type == 2) {
            $this->exportAllOrder();
        }
    }

    /******************
     *导出自动分单Excel
     * 日期：2017-12-05
     */
    private function exprotAutoSortByOrder()
    {
        //获取当前分页的15个申请门店
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        $sql = " select A.store_id1 as store_id ";
        $sql .= "from `hii_store_to_store` A ";
        $sql .= "left join  (SELECT * from hii_store_to_store_detail where is_pass=0 and s_t_s_d_id not in (SELECT s_r_d_id from hii_store_out_detail ) ) A1 on A1.s_t_s_id=A.s_t_s_id    ";
        $sql .= "where A.store_id2={$this->_store_id} and FROM_UNIXTIME(ctime,'%Y-%m-%d')  between '$s_date' and '$e_date'  ";
        $sql .= "group by A.store_id1 ";
        $sql .= "order by A.s_t_s_id desc ";

        $datamain = $StoreToStoreModel->query($sql);

        $list = array();
        $index = 0;
        foreach ($datamain as $key => $val) {
            $sql = "select  A.s_t_s_id,A.s_t_s_sn,A1.s_t_s_d_id,A1.g_num,G.title as goods_name,G.bar_code,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
            $sql .= "S.title as store_name1,A.remark,A1.goods_id,ifnull(GS.num,0) as stock_num,A1.is_pass,GC.title as cate_name,M.nickname as admin_nickname ";
            $sql .= "from hii_store_to_store_detail A1 ";
            $sql .= "left join hii_store_to_store A on A.s_t_s_id=A1.s_t_s_id ";
            $sql .= "left join hii_goods G on G.id=A1.goods_id ";
            $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
            $sql .= "left join hii_store S on S.id=A.store_id1 ";
            $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
            $sql .= "left join hii_member M on M.uid=A.admin_id ";
            $sql .= "where A.store_id2={$this->_store_id} and A.store_id1={$val["store_id"]} and A1.is_pass=0 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
            $sql .= "and A1.s_t_s_d_id not in (select s_r_d_id from hii_store_out_detail ) ";
            $sql .= "order by A1.s_t_s_d_id desc ";
            //echo $sql ."<br/><br/>";
            $result = $StoreToStoreDetailModel->query($sql);
            if (!is_null($result) && !empty($result) && count($result) > 0) {
                foreach ($result as $k => $v) {
                    switch ($v["is_pass"]) {
                        case 0: {
                            $result[$k]["is_pass_name"] = "新增";
                        };
                            break;
                        case 1: {
                            $result[$k]["is_pass_name"] = "未通过";
                        };
                            break;
                        case 2: {
                            $result[$k]["is_pass_name"] = "已通过";
                        };
                            break;
                    }
                }
                $list[$index] = $result;
                $index++;
            }
        }
        //exit;
        ob_clean;
        $title = $s_date . ">>>" . $e_date . '调拨审核单';
        $fname = './Public/Excel/StoreAssignmentApplicationHandle_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreAssignmentApplicationHandleModel();
        $printfile = $printmodel->createListExcelByAutoSort($list, $title, $fname);
        $this->response(self::CODE_OK, $printfile);

    }

    /**********************
     * 导出所有分单
     * 日期：2017-12-05
     */
    private function exportAllOrder()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        $sql = "select A.s_t_s_id,A.s_t_s_sn,A1.s_t_s_d_id,A1.g_num,G.title as goods_name,G.bar_code,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "S.title as store_name1,A.remark,A1.goods_id,ifnull(GS.num,0) as stock_num,A1.is_pass,GC.title as cate_name,M.nickname as admin_nickname ";
        $sql .= "from hii_store_to_store_detail A1 ";
        $sql .= "left join hii_store_to_store A on A.s_t_s_id=A1.s_t_s_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id  ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_store S on S.id=A.store_id1 ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "where A.store_id2={$this->_store_id} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' and A1.is_pass=0 ";
        $sql .= "and A1.s_t_s_d_id not in (select s_r_d_id from hii_store_out_detail ) ";
        $sql .= "order by A1.s_t_s_d_id desc ";

        $data = $StoreToStoreDetailModel->query($sql);

        foreach ($data as $key => $val) {
            switch ($val["is_pass"]) {
                case 0: {
                    $datamain[$key]["is_pass_name"] = "新增";
                };
                    break;
                case 1: {
                    $datamain[$key]["is_pass_name"] = "未通过";
                };
                    break;
                case 2: {
                    $datamain[$key]["is_pass_name"] = "已通过";
                };
                    break;
            }
        }

        ob_clean;
        $title = $s_date . ">>>" . $e_date . '调拨审核单';
        $fname = './Public/Excel/StoreAssignmentApplicationHandle_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreAssignmentApplicationHandleModel();
        $printfile = $printmodel->createListExcelByAll($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);

    }


    /*******************
     * 自动分单
     * 日期：2017-11-27
     */
    private function autoSortByOrder()
    {
        //获取当前分页的15个申请门店
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");

        $can_store_id_array = $this->getCanStoreIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id1 in (" . implode(",", $can_store_id_array) . ") or A.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = " select A.store_id1 as store_id ";
        $sql .= "from `hii_store_to_store` A ";
        $sql .= "inner join  (SELECT * from hii_store_to_store_detail where is_pass=0 and s_t_s_d_id not in (SELECT s_r_d_id from hii_store_out_detail ) ) A1 on A1.s_t_s_id=A.s_t_s_id    ";
        $sql .= "where A.store_id2={$this->_store_id} {$shequ_where} and FROM_UNIXTIME(ctime,'%Y-%m-%d')  between '$s_date' and '$e_date'  ";
        $sql .= "group by A.store_id1 ";
        $sql .= "order by A.s_t_s_id desc ";

        //echo $sql;exit;

        $list = $StoreToStoreModel->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($list);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($list, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $list = array();
        $index = 0;
        foreach ($datamain as $key => $val) {
            $sql = "select  A.s_t_s_id,A.s_t_s_sn,A1.s_t_s_d_id,G.title as goods_name,G.bar_code,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
            $sql .= "S.title as store_name1,A.remark,A1.goods_id,A1.g_num,ifnull(GS.num,0) as stock_num,A1.is_pass,GC.title as cate_name,M.nickname as admin_nickname ";
            $sql .= "from hii_store_to_store_detail A1 ";
            $sql .= "left join hii_store_to_store A on A.s_t_s_id=A1.s_t_s_id ";
            $sql .= "left join hii_goods G on G.id=A1.goods_id ";
            $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
            $sql .= "left join hii_store S on S.id=A.store_id1 ";
            $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
            $sql .= "left join hii_member M on M.uid=A.admin_id ";
            $sql .= "where A.store_id2={$this->_store_id} and A.store_id1={$val["store_id"]} and A1.is_pass=0 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
            $sql .= "and A1.s_t_s_d_id not in (select s_r_d_id from hii_store_out_detail ) ";
            $sql .= "order by A1.s_t_s_d_id desc ";
            //echo $sql ."<br/><br/>";
            $result = $StoreToStoreDetailModel->query($sql);
            if (!is_null($result) && !empty($result) && count($result) > 0) {
                foreach ($result as $k => $v) {
                    switch ($v["is_pass"]) {
                        case 0: {
                            $result[$k]["is_pass_name"] = "新增";
                        };
                            break;
                        case 1: {
                            $result[$k]["is_pass_name"] = "未通过";
                        };
                            break;
                        case 2: {
                            $result[$k]["is_pass_name"] = "已通过";
                        };
                            break;
                    }
                }
                $list[$index] = $result;
                $index++;
            }
        }

        //exit;

        $result = array();
        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $list;

        $this->response(self::CODE_OK, $result);

    }

    /******************
     * 所有单
     * 注意：is_pass 0.新增，1.未通过，2.已通过
     * 日期：2017-11-27
     */
    private
    function allOrder()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreToStoreDetailModel = M("StoreToStoreDetail");

        $can_store_id_array = $this->getCanStoreIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id1 in (" . implode(",", $can_store_id_array) . ") or A.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = "select A.s_t_s_id,A.s_t_s_sn,A1.s_t_s_d_id,A1.g_num,G.title as goods_name,G.bar_code,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime, ";
        $sql .= "S.title as store_name1,A.remark,A1.goods_id,ifnull(GS.num,0) as stock_num,A1.is_pass,GC.title as cate_name,M.nickname as admin_nickname ";
        $sql .= "from hii_store_to_store_detail A1 ";
        $sql .= "left join hii_store_to_store A on A.s_t_s_id=A1.s_t_s_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id  ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_store S on S.id=A.store_id1 ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "where A.store_id2={$this->_store_id} {$shequ_where} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' and A1.is_pass=0 ";
        $sql .= "and A1.s_t_s_d_id not in (select s_r_d_id from hii_store_out_detail ) ";
        $sql .= "order by A1.s_t_s_d_id desc ";

        //echo $sql;exit;

        $data = $StoreToStoreDetailModel->query($sql);
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            switch ($val["is_pass"]) {
                case 0: {
                    $datamain[$key]["is_pass_name"] = "新增";
                };
                    break;
                case 1: {
                    $datamain[$key]["is_pass_name"] = "未通过";
                };
                    break;
                case 2: {
                    $datamain[$key]["is_pass_name"] = "已通过";
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