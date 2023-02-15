<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-15
 * Time: 15:18
 * 门店退货相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreLossController extends AdminController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /************
     * 退货列表接口
     * 请求方式：GET
     * 请求参数：p    当前页    非必填   默认1
     *           s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     * 注意：
     * 日期：2017-12-15
     */
    public function index()
    {
        $result = $this->getIndexList(true);
        $this->response(self::CODE_OK, $result);
    }

    /***********************
     * 被退货列表接口
     * 请求方式：GET
     * 请求参数：p    当前页    非必填   默认1
     *           s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     *           pageSize 每页显示数量 非必须
     * 注意：
     * 日期：2017-12-28
     */
    public function index2()
    {
        $result = $this->getIndex2List(true);
        $this->response(self::CODE_OK, $result);
    }

    /*****************
     * 导出列表Excel接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     * 注意：
     * 日期：2017-12-15
     */
    public function exportIndexListExcel()
    {
        $result = $this->getIndexList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 退货单';
        $fname = './Public/Excel/StoreOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOtherOutModel();
        $printfile = $printmodel->createIndexListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /********************
     * 导出被退货单Excel接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     * 注意：
     * 日期：2017-12-28
     */
    public function exportIndex2ListExcel()
    {
        $result = $this->getIndex2List(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 被退货单';
        $fname = './Public/Excel/StoreOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOtherOutModel();
        $printfile = $printmodel->createIndex2ListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**************************
     * 查看接口
     * 请求方式：GET
     * 请求参数：s_o_out_id  退货单ID  必须
     * 注意：
     * 日期：2017-12-15
     */
    public function view()
    {
        $result = $this->getSingleStoreOtherOutDetail();
        $this->response(self::CODE_OK, $result);
    }

    /************************************
     * 被退货单查看接口
     * 请求方式：GET
     * 请求参数：s_o_out_id  退货单ID  必须
     * 日期：2017-12-28
     */
    public function view2()
    {
        $result = $this->getView2Info();
        $this->response(self::CODE_OK, $result);
    }

    /**********************
     * 审核接口
     * 请求方式：POST
     * 请求参数：s_o_out_id  退货单ID  必须
     * 日期：2017-12-28
     *********************/
    public function check()
    {
        $s_o_out_id = I("post.s_o_out_id");
        if (is_null($s_o_out_id) || empty($s_o_out_id)) {
            $this->response(0, "请选择要审核的退货单");
        }
        $store_id = $this->_store_id;
        $padmin_id = UID;
        $StoreLossRepository = D("StoreLoss");
        $result = $StoreLossRepository->pass($padmin_id, $store_id, $s_o_out_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }


    /**********************
     * 导出查看Excel接口
     * 请求方式：GET
     * 请求参数：s_o_out_id  退货单ID  必须
     * 注意：
     * 日期：2017-12-18
     */
    public function exportViewExcel()
    {
        $result = $this->getSingleStoreOtherOutDetail();
        ob_clean;
        $title = '退货单查看';
        $fname = './Public/Excel/StoreOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOtherOutModel();
        $printfile = $printmodel->createViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**************
     * 导出被退货单excel接口
     * 请求方式：GET
     * 请求参数：s_o_out_id  退货单ID  必须
     * 日期：2017-12-28
     */
    public function exportView2Excel()
    {
        $result = $this->getView2Info();
        ob_clean;
        $title = '被退货单查看';
        $fname = './Public/Excel/StoreOtherOut_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreOtherOutModel();
        $printfile = $printmodel->createView2Excel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    private function getIndexList($usePager)
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $store_id = $this->_store_id;
        $StoreOtherOutModel = M("StoreOtherOut");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        //SUM(G.sell_price*SOOD.g_num) as g_amounts,
        $sql = "select SOO.s_o_out_id,SOO.s_o_out_sn,s_o_out_status,SOO.s_o_out_type, ";
        $sql .= "FROM_UNIXTIME(SOO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,M.nickname as admin_nickname, ";
        $sql .= "W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "SOO.remark,SOO.g_type,SOO.g_nums,'' as out_amounts, ";
        $sql .= "SUM(SOOD.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts  ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_store_other_out_detail SOOD on SOOD.s_o_out_id=SOO.s_o_out_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=SOOD.goods_id ";
        $sql .= "where SOO.store_id2={$store_id} {$shequ_where} and FROM_UNIXTIME(SOO.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by SOO.s_o_out_id,SOO.s_o_out_sn,s_o_out_status,SOO.s_o_out_type,SOO.ctime,M.nickname, ";
        $sql .= "W.w_name,S1.title,S2.title,SOO.remark,SOO.g_type,SOO.g_nums ";
        $sql .= "order by s_o_out_id desc ";

        //echo $sql;exit;

        $data = $StoreOtherOutModel->query($sql);

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
            switch ($val["s_o_out_status"]) {
                case 0: {
                    $data[$key]["s_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["s_o_out_type"]) {
                case 0: {
                    $data[$key]["s_o_out_type_name"] = "仓库调拨入库退货";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_type_name"] = "门店调拨入库退货";
                };
                    break;
                case 2: {
                    $data[$key]["s_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 3: {
                    $data[$key]["s_o_out_type_name"] = "商品过期";
                };
                    break;
                case 4: {
                    $data[$key]["s_o_out_type_name"] = "其他退货";
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["data"] = $this->isArrayNull($data);

        return $result;
    }

    private function getIndex2List($usePager)
    {
        $store_id = $this->_store_id;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreOtherOutModel = M("StoreOtherOut");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " SOO.store_id1 in (" . implode(",", $can_store_id_array) . ") or SOO.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " SOO.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        $sql = "select SOO.s_o_out_id,SOO.s_o_out_status,SOO.s_o_out_sn,FROM_UNIXTIME(SOO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "M1.nickname as admin_nickname,M2.nickname as padmin_nickname,SOO.g_type,SOO.g_nums,SOO.remark,S.title as store_name2,W.w_name as warehouse_name,  ";
        $sql .= "'' as out_amounts, ";
        $sql .= "SUM(SOOD.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts   ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_store_other_out_detail SOOD on SOOD.s_o_out_id=SOO.s_o_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=SOO.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=SOO.padmin_id ";
        $sql .= "left join hii_store S on S.id=SOO.store_id2 ";
        $sql .= "left join hii_warehouse W on SOO.warehouse_id=W.w_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=SOOD.goods_id ";
        $sql .= "where SOO.store_id1={$store_id} {$shequ_where} and SOO.s_o_out_type in (0,1,5) and FROM_UNIXTIME(SOO.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by SOO.s_o_out_id,SOO.s_o_out_sn,SOO.ctime,SOO.ptime,M1.nickname,M2.nickname,SOO.g_type,SOO.g_nums,SOO.remark,S.title ";
        $sql .= "order by SOO.s_o_out_id desc ";

        //echo $sql;exit;

        $data = $StoreOtherOutModel->query($sql);

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
            switch ($val["s_o_out_status"]) {
                case 0: {
                    $data[$key]["s_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_status_name"] = "已审核";
                };
                    break;
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["data"] = $this->isArrayNull($data);

        return $result;

    }

    private function getSingleStoreOtherOutDetail()
    {
        $s_o_out_id = I("get.s_o_out_id");
        if (is_null($s_o_out_id) || empty($s_o_out_id)) {
            $this->response(0, "请选择要查看的退货单");
        }
        $store_id = $this->_store_id;
        $warehouse_id = is_null($this->_warehouse_id) || empty($this->_warehouse_id) ? 0 : $this->_warehouse_id;
        $StoreOtherOutModel = M("StoreOtherOut");
        $StoreOtherOutDetailModel = M("StoreOtherOutDetail");

        $sql = "select SOO.s_o_out_id,SOO.s_o_out_sn,s_o_out_status,SOO.s_o_out_type, ";
        $sql .= "FROM_UNIXTIME(SOO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,M.nickname as admin_nickname, ";
        $sql .= "W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "SOO.remark,SOO.g_type,SOO.g_nums,SUM(G.sell_price*SOOD.g_num) as g_amounts,SUM(SOOD.g_num*SOOD.g_price) as out_amounts ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_store_other_out_detail SOOD on SOOD.s_o_out_id=SOO.s_o_out_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "where SOO.s_o_out_id={$s_o_out_id} and (SOO.store_id2={$store_id} or (SOO.store_id1={$store_id} or SOO.warehouse_id={$warehouse_id} and SOO.s_o_out_status=1 ) ) ";
        $sql .= "group by SOO.s_o_out_id,SOO.s_o_out_sn,s_o_out_status,SOO.s_o_out_type,SOO.ctime,M.nickname, ";
        $sql .= "W.w_name,S1.title,S2.title,SOO.remark,SOO.g_type,SOO.g_nums ";
        $sql .= "order by s_o_out_id desc limit 1 ";

        //echo $sql;exit;

        $datas = $StoreOtherOutModel->query($sql);

        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "不存在该退货信息");
        }
        $maindata = $datas[0];

        switch ($maindata["s_o_out_status"]) {
            case 0: {
                $maindata["s_o_out_status_name"] = "新增";
            };
                break;
            case 1: {
                $maindata["s_o_out_status_name"] = "已审核";
            };
                break;
        }
        switch ($maindata["s_o_out_type"]) {
            case 0: {
                $maindata["s_o_out_type_name"] = "仓库调拨入库退货";
            };
                break;
            case 1: {
                $maindata["s_o_out_type_name"] = "门店调拨入库退货";
            };
                break;
            case 2: {
                $maindata["s_o_out_type_name"] = "盘亏退货";
            };
                break;
            case 3: {
                $maindata["s_o_out_type_name"] = "商品过期";
            };
                break;
            case 4: {
                $maindata["s_o_out_type_name"] = "其他退货";
            };
                break;
        }

        //sys_price 系统售价 shequ_price 区域价  store_price 门店售价
        $sql = "select SOOD.goods_id,SOOD.g_num,SOOD.g_price,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,GC.title as cate_name,SOOD.remark, ";
        $sql .= "G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=SOOD.goods_id ";
        $sql .= "left join hii_attr_value AV on SOOD.value_id=AV.value_id ";
        $sql .= "where SOOD.s_o_out_id={$s_o_out_id} ";

        $list = $StoreOtherOutDetailModel->query($sql);

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
        $maindata["g_amounts"] = $g_amounts;
        $result["maindata"] = $maindata;
        $result["list"] = $list;
        return $result;
    }

    private function getView2Info()
    {
        $s_o_out_id = I("get.s_o_out_id");
        if (is_null($s_o_out_id) || empty($s_o_out_id)) {
            $this->response(0, "请选择被退货单");
        }
        $store_id = $this->_store_id;
        $warehouse_id = is_null($this->_warehouse_id) || empty($this->_warehouse_id) ? 0 : $this->_warehouse_id;
        $StoreOtherOutModel = M("StoreOtherOut");
        $sql = "select SOO.s_o_out_id,SOO.s_o_out_sn,FROM_UNIXTIME(SOO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "M1.nickname as admin_nickname,M2.nickname as padmin_nickname,SOO.g_type,SOO.g_nums,SOO.remark,S.title as store_name2,W.w_name as warehouse_name,  ";
        $sql .= "SUM(G.sell_price*SOOD.g_num) as g_amounts,'' as out_amounts ";
        $sql .= "from hii_store_other_out SOO ";
        $sql .= "left join hii_store_other_out_detail SOOD on SOOD.s_o_out_id=SOO.s_o_out_id ";
        $sql .= "left join hii_member M1 on M1.uid=SOO.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=SOO.padmin_id ";
        $sql .= "left join hii_store S on S.id=SOO.store_id2 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "where SOO.s_o_out_id={$s_o_out_id} and (SOO.store_id1={$store_id} or (SOO.store_id2={$store_id} or SOO.warehouse_id={$warehouse_id} or SOO.s_o_out_status=1 ) ) and SOO.s_o_out_type in (0,1,5) ";
        $sql .= "group by SOO.s_o_out_id,SOO.s_o_out_sn,SOO.ctime,SOO.ptime,M1.nickname,M2.nickname,SOO.g_type,SOO.g_nums,SOO.remark,S.title ";
        $sql .= "order by SOO.s_o_out_id desc limit 1 ";

        $datas = $StoreOtherOutModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法查看该被退货单");
        }

        $StoreOtherOutDetailModel = M("StoreOtherOutDetail");
        //sys_price 系统售价 shequ_price 区域价  store_price 门店售价
        $sql = "select SOOD.goods_id,SOOD.g_num,'' as g_price,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,GC.title as cate_name,SOOD.remark, ";
        $sql .= "G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=SOOD.goods_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=SOOD.value_id ";
        $sql .= "where SOOD.s_o_out_id={$s_o_out_id} ";
        $list = $StoreOtherOutDetailModel->query($sql);

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

        $datas[0]["g_amounts"] = $g_amounts;
        $result = array();
        $result["maindata"] = $datas[0];
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