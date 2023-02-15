<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-28
 * Time: 16:50
 * 门店出库管理
 */

namespace Erp\Controller;

use Think\Controller;

class StoreOutController extends AdminController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /****************
     * 出库验货单接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     * 日期：2017-11-28
     * 注意：
     */
    public function index()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreOutModel = M("StoreOut");
        $StoreToStoreModel = M("StoreToStore");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";

        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id1 in (" . implode(",", $can_store_id_array) . ") or A.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " A.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = "select A.s_out_id,A.s_out_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.g_type,A.g_nums,M.nickname as nickname,A.remark, ";
        $sql .= "S1.title as store_name1,S2.title as store_name2,A.s_out_status,A.s_r_id,W.w_name as warehouse_name, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_out A ";
        $sql .= "left join hii_store_out_detail A1 on A1.s_out_id=A.s_out_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id  ";
        $sql .= "where A.store_id2={$this->_store_id} {$shequ_where} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "group by A.s_out_id,A.s_out_sn,A.ctime,A.g_type,A.g_nums,M.nickname,S1.title,S2.title,A.s_out_status,A.s_r_id ";
        $sql .= "order by A.s_out_id desc ";

        $data = $StoreOutModel->query($sql);
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            $datamain[$key]["source"] = "门店调拨";//暂时只有门店调拨
            $sql = "select s_t_s_sn from hii_store_to_store where s_t_s_id in ({$val["s_r_id"]})  ";
            $s_t_s_sn_array = $StoreToStoreModel->query($sql);
            $rel_orders = "";
            foreach ($s_t_s_sn_array as $k => $v) {
                $rel_orders .= $v["s_t_s_sn"] . ",";
            }
            $datamain[$key]["rel_orders"] = !empty($rel_orders) ? substr($rel_orders, 0, strlen($rel_orders) - 1) : $rel_orders;
            //状态：0.新增,1.已审核转出库,2.已拒绝,3.部分拒绝
            switch ($val["s_out_status"]) {
                case 0: {
                    $datamain[$key]["s_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$datamain[$key]["s_out_status_name"] = "已审核转出库";
                    $datamain[$key]["s_out_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $datamain[$key]["s_out_status_name"] = "已拒绝";
                };
                    break;
                case 3: {
                    $datamain[$key]["s_out_status_name"] = "部分拒绝";
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


    /******************
     * 获取单个出库验货单信息
     * 请求方式：GET
     * 请求参数：s_out_id  出库验货单ID 必须
     * 日期：2017-11-29
     * 注意：
     */
    public function getSingleStoreOutInfo()
    {
        $s_out_id = I("get.s_out_id");
        if (is_null($s_out_id) || empty($s_out_id)) {
            $this->response(0, "请选择你要查看的出库验货单");
        }
        $result = $this->getSingleStoreOutDetail($s_out_id);
        $this->response(self::CODE_OK, $result);
    }


    /*********************
     * 修改入库单子表的有货数量，缺货数量
     * 请求方式：POST
     * 请求参数：s_out_id  出库验货单ID  必须
     *           info_json  修改信息json 必须 [{"s_out_d_id":"","in_num":"","out_num":""},{"s_out_d_id":"","in_num":"","out_num":""}]
     *           remark  备注  非必需
     * 日期：2017-11-30
     */
    public function updateStoreOutDetailInfo()
    {

        $s_out_id = I("post.s_out_id");
        $info_array = I("post.info_json");
        $remark = I("post.remark");
        if (is_null($info_array) || empty($info_array) || count($info_array) == 0) {
            $this->response(0, "请提交要更新的信息");
        }
        $StoreOutRepository = D("StoreOut");
        $store_id = $this->_store_id;
        $uid = UID;
        $res = $StoreOutRepository->updateStoreOutDetailInfo($uid, $store_id, $s_out_id, $info_array, $remark);
        if ($res["status"] == "0") {
            $this->response(0, $res["msg"]);
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /******************
     * 全部拒绝接口
     * 请求方式：POST
     * 请求参数：s_out_id  出库验货单ID  必须
     * 日期：2017-11-30
     */
    public function rejectAll()
    {
        $s_out_id = I("post.s_out_id");
        if (is_null($s_out_id) || empty($s_out_id)) {
            $this->response(0, "请选择要拒绝的出库验货单");
        }
        $store_id = $this->_store_id;
        $StoreOutRepository = D("StoreOut");
        $res = $StoreOutRepository->rejectAll($s_out_id, $store_id, UID);
        if ($res["status"] == "200") {

            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /*****************
     * 审核接口
     * 请求方式：POST
     * 请求参数：s_out_id  出库验货单ID  必须
     * 日期：2017-11-30
     */
    public function check()
    {
        $s_out_id = I("post.s_out_id");
        if (is_null($s_out_id) || empty($s_out_id)) {
            $this->response(0, "请选择要审核的出库验货单");
        }
        $store_id = $this->_store_id;
        $StoreOutRepository = D("StoreOut");
        $res = $StoreOutRepository->pass($s_out_id, $store_id, UID);
        if ($res["status"] == "200") {
            //加入申请门店的入库提醒
            $MessageWarnModel = D('MessageWarn');
            $MessageWarnModel->pushMessageWarn(UID  , 0  ,$res['data']['store_id2'] ,  0 , $res['data'],4);
            $this->response(self::CODE_OK, $res["msg"]);
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /*************
     * 导出列表Excel
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * 日期：2017-11-30
     */
    public function exportListExcel()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreOutModel = M("StoreOut");
        $StoreToStoreModel = M("StoreToStore");
        $sql = "select A.s_out_id,A.s_out_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.g_type,A.g_nums,M.nickname as nickname,A.remark, ";
        $sql .= "S1.title as store_name1,S2.title as store_name2,A.s_out_status,A.s_r_id,W.w_name as warehouse_name, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_out A ";
        $sql .= "left join hii_store_out_detail A1 on A1.s_out_id=A.s_out_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id  ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$this->_store_id} and GS.goods_id=A1.goods_id ";
        $sql .= "where A.store_id2={$this->_store_id} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "group by A.s_out_id,A.s_out_sn,A.ctime,A.g_type,A.g_nums,M.nickname,S1.title,S2.title,A.s_out_status,A.s_r_id ";
        $data = $StoreOutModel->query($sql);
        foreach ($data as $key => $val) {
            $data[$key]["source"] = "门店调拨";//暂时只有门店调拨
            $sql = "select s_t_s_sn from hii_store_to_store where s_t_s_id in ({$val["s_r_id"]})  ";
            $s_t_s_sn_array = $StoreToStoreModel->query($sql);
            $rel_orders = "";
            foreach ($s_t_s_sn_array as $k => $v) {
                $rel_orders .= $v["s_t_s_sn"] . ",";
            }
            $data[$key]["rel_orders"] = !empty($rel_orders) ? substr($rel_orders, 0, strlen($rel_orders) - 1) : $rel_orders;
            //状态：0.新增,1.已审核转出库,2.已拒绝,3.部分拒绝
            switch ($val["s_out_status"]) {
                case 0: {
                    $data[$key]["s_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$data[$key]["s_out_status_name"] = "已审核转出库";
                    $data[$key]["s_out_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_out_status_name"] = "已拒绝";
                };
                    break;
                case 3: {
                    $data[$key]["s_out_status_name"] = "部分拒绝";
                };
                    break;
            }
        }
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店发货验货单';
        $fname = './Public/Excel/StoreOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOutModel();
        $printfile = $printmodel->createStoreOutListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*******************
     * 导出查看页面Excel
     * 请求方式：GET
     * 请求参数：s_out_id  出库验货单ID  是
     * 日期：2017-12-01
     */
    public function exportViewExcel()
    {
        $s_out_id = I("get.s_out_id");
        if (is_null($s_out_id) || empty($s_out_id)) {
            $this->response(0, "请选择要导出的出库验货单");
        }
        $data = $this->getSingleStoreOutDetail($s_out_id);
        ob_clean;
        $title = '门店发货验货单明细';
        $fname = './Public/Excel/StoreOutView_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOutModel();
        $printfile = $printmodel->createStoreOutViewExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }


    /**********************
     * 获取单条出库验货单详细信息
     * @param $s_out_id 出库验货单ID
     * return Array("maindata"=>$maindata,"list"=>$list)
     */
    private function getSingleStoreOutDetail($s_out_id)
    {
        $StoreOutModel = M("StoreOut");
        $StoreOutDetailModel = M("StoreOutDetail");

        //s_out_type 来源:0.仓库调拨,1.门店申请,2.退货报损
        $sql = "select A.s_out_id,A.s_out_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.g_type, ";
        $sql .= "A.g_nums,A.s_out_type,A.s_r_id,A.w_r_id,A.s_o_out_id,M.nickname as admin_nickname,";
        $sql .= "S1.title as store_name1,S2.title as store_name2,W.w_name as warehouse_name,A.remark, ";
        $sql .= "A.s_r_id,A.w_r_id,A.s_o_out_id,A.s_out_status ";
        $sql .= "from hii_store_out A ";
        $sql .= "left join hii_store_out_detail A1 on A1.s_out_id=A.s_out_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id ";
        $sql .= "where A.s_out_id={$s_out_id}  ";
        $sql .= "group by A.s_out_id,A.s_out_sn,A.ctime,A.g_type,A.g_nums,A.s_out_type,A.s_r_id,A.w_r_id,A.s_o_out_id, ";
        $sql .= "M.nickname,S1.title,S2.title,W.w_name,A.remark,A.s_r_id,A.w_r_id,A.s_o_out_id,A.s_out_status limit 1";
        $data = $StoreOutModel->query($sql);

        if (is_null($data) || empty($data) || count($data) == 0) {
            $this->response(0, "不存在该信息");
        }

        //查询来源信息
        $s_out_type = $data[0]["s_out_type"];
        if ($s_out_type == 0) {

        } elseif ($s_out_type == 1) {
            $StoreToStoreModel = M("StoreToStore");
            $sql = "select s_t_s_sn from hii_store_to_store where s_t_s_id in ({$data[0]["s_r_id"]})  ";
            $s_t_s_sn_array = $StoreToStoreModel->query($sql);
            $rel_orders = "";
            foreach ($s_t_s_sn_array as $k => $v) {
                $rel_orders .= $v["s_t_s_sn"] . ",";
            }
            $data[0]["rel_orders"] = !empty($rel_orders) ? substr($rel_orders, 0, strlen($rel_orders) - 1) : $rel_orders;
            $data[0]["s_out_type_name"] = "门店调拨";
        } elseif ($s_out_type == 2) {
            $StoreOtherOutModel = M("StoreOtherOut");
            $tmp = $StoreOtherOutModel->where(" s_o_out_id in ({$data[0]["s_o_out_id"]}) ")->select();
            $data[0]["rel_orders"] = implode(",", $tmp);//关联单号
            $data[0]["s_out_type_name"] = "退货报损";
        }

        switch ($data[0]["s_out_status"]) {
            case 0: {
                $data[0]["s_out_status_name"] = "新增";
            };
                break;
            case 1: {
                //$data[0]["s_out_status_name"]="已审核转出库";
                $data[0]["s_out_status_name"] = "已审核";
            };
                break;
            case 2: {
                $data[0]["s_out_status_name"] = "已拒绝";
            };
                break;
            case 3: {
                $data[0]["s_out_status_name"] = "部分拒绝";
            };
                break;
        }

        //sys_price 系统售价 shequ_price 区域价  store_price 门店价
        $sql = "select A1.s_out_d_id,A1.goods_id,G.title as goods_name,GC.title as cate_name,A1.remark, ";
        $sql .= "G.bar_code,A1.g_num,ifnull(GS.num,0) as stock_num,ifnull(A1.in_num,0) as in_num,ifnull(A1.out_num,0) as out_num, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price ";
        $sql .= "from hii_store_out_detail A1 ";
        $sql .= "left join hii_store_out A on A.s_out_id=A1.s_out_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id=A.store_id2 and GS.goods_id=A1.goods_id ";
        $sql .= "where A1.s_out_id={$s_out_id} ";
        $sql .= "order by A1.goods_id asc ";

        //echo $sql;exit;

        $list = $StoreOutDetailModel->query($sql);
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
        $data[0]["g_amounts"] = $g_amounts;
        $result = array();
        $result["maindata"] = $data[0];
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