<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-21
 * Time: 18:04
 * 寄售处理
 */

namespace Erp\Controller;

use Think\Controller;

class StoreConsignController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /******************************************* 寄售入库部分 start *************************************************************************************************************/

    /******************************
     * 临时寄售单接口
     * 请求方式：GET
     * 请求参数：无
     * 注意：
     * 日期：2017-12-21
     */
    public function temp()
    {
        $temp_type = 9;
        $datas = $this->getTempListData($temp_type);
        $this->response(self::CODE_OK, $datas);
    }

    /*******************
     * 寄售单
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function index()
    {
        $result = $this->getIndexList(true);
        $this->response(self::CODE_OK, $result);
    }

    /******************
     * 加入临时申请单接口
     * 请求方式：POST
     * 请求参数：goods_id  商品ID             必须
     *           b_n_num   箱规               必须
     *           b_num     采购箱数           必须
     *           b_price   每箱价格           必须
     *           g_num     采购数量           必须
     *           g_price   采购价(每件价格)   必须
     * 日期：2017-12-25
     */
    public function addRequestTemp()
    {
        $temp_type = 9;
        $this->addSingleRequestTemp($temp_type);
    }

    /*****************************
     * 临时申请单编辑接口
     * 请求方式：GET
     * 请求参数：id  临时申请单ID  必须
     * 日期：2017-12-26
     */
    public function edit()
    {
        $id = I("get.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择需要编辑的临时申请单");
        }
        $admin_id = UID;
        $temp_type = 9;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,G.bar_code,G.title as goods_name,RT.b_n_num,RT.b_num,RT.b_price,RT.g_num,RT.g_price,RT.remark,RT.value_id ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "where RT.id={$id} and RT.admin_id={$admin_id} and RT.temp_type={$temp_type} and  RT.status=0 order by RT.id desc limit 1 ";
        $datas = $RequestTempModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该临时申请单不存在");
        } else {
            $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datas[0]['goods_id'],'status'=>array('neq',2)))->select();
            if(empty($attr_value_array)){
                $attr_value_array = array();
            }
            $datas[0]['attr_value_array'] = $attr_value_array;
            $this->response(self::CODE_OK, $datas[0]);
        }
    }

    /*******************
     * 供应商列表接口
     * 请求方式：GET
     * 请求参数：无
     * 日期：2017-12-26
     */
    public function supplylist()
    {
        $SupplyModel = M("Supply");
        $store_id = $this->_store_id;
        $where = " shequ_id=(select shequ_id from hii_store where id={$store_id})  ";
        $can_supply_id_array = $this->getCanSupplyIdArray();
        if (count($can_supply_id_array) > 0) {
            $where .= " and s_id in (" . implode(",", $can_supply_id_array) . ") ";
        }
        $list = $SupplyModel->field(" s_id,s_name ")->where($where)->select();
        $this->response(self::CODE_OK, $list);
    }

    /**********************
     * 删除临时申请单接口
     * 请求方式：POST
     * 请求参数：id  临时申请单ID  必须
     * 日期：2017-12-26
     */
    public function deleteRequestTemp()
    {
        $id = I("post.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择需要删除的临时申请单");
        }
        $admin_id = UID;
        $temp_type = 9;
        $RequestTempModel = M("RequestTemp");
        $datas = $RequestTempModel->where(" id={$id} and admin_id={$admin_id} and temp_type={$temp_type} and  `status`=0 ")->order(" id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该临时申请单不存在");
        }
        $ok = $RequestTempModel->where(" id={$id} ")->order(" id desc ")->limit(1)->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /********************
     * 提交临时申请单
     * 请求方式：POST
     * 请求参数：supply_id  供应商ID  必须
     *           remark     备注      非必须
     * 日期：2017-12-26
     */
    public function submitRequestTemp()
    {
        $supply_id = I("post.supply_id");
        $remark = I("post.remark");
        if (is_null($supply_id) || empty($supply_id)) {
            $this->response(0, "请选择供应商");
        }
        $admin_id = UID;
        $store_id = $this->_store_id;
        $StoreConsignRepository = D("StoreConsign");
        $result = $StoreConsignRepository->submitRequestTemp($admin_id, $store_id, $supply_id, $remark);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /********************
     * 导出寄售单Excel接口
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     * 日期：2017-12-26
     */
    public function exportIndexListExcel()
    {
        $result = $this->getIndexList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 寄售入库单';
        $fname = './Public/Excel/StoreConsignment_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreConsignModel();
        $printfile = $printmodel->createIndexListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /***************************
     * 查看接口
     * 请求方式：GET
     * 请求参数：c_id  寄售单ID  必须
     * 日期：2017-12-26
     */
    public function view()
    {
        $result = $this->getViewInfo();
        $this->response(self::CODE_OK, $result);
    }

    /**********************
     * 清空临时申请接口
     * 请求方式：POST
     * 请求参数：无
     * 日期：2017-12-26
     */
    public function clearRequestTemp()
    {
        $admin_id = UID;
        $temp_type = 9;
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        $ok = $RequestTempModel->where(" `admin_id`={$admin_id} and `store_id`={$store_id} and `temp_type`={$temp_type} and `status`=0 ")->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /*******************
     * 导出查看Excel接口
     * 请求方式：GET
     * 请求参数：c_id  寄售单ID  必须
     * 日期：2017-12-26
     */
    public function exportViewExcel()
    {
        $result = $this->getViewInfo();
        ob_clean;
        $title = '寄售入库单查看';
        $fname = './Public/Excel/StoreConsignmentView_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreConsignModel();
        $printfile = $printmodel->createViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*********************
     * 作废寄售单接口
     * 请求方式：POST
     * 请求参数：c_in_id  寄售单ID  必须
     * 日期：2017-12-26
     *********************/
    public function cancelConsignment()
    {
        $c_in_id = I("post.c_in_id");
        if (is_null($c_in_id) || empty($c_in_id)) {
            $this->response(0, "请选择要作废的寄售入库单");
        }
        $ConsignmentModel = M("ConsignmentIn");
        $store_id = $this->_store_id;
        $where = array();
        $where["hii_consignment_in.c_in_id"] = $c_in_id;
        $where["hii_consignment_in.store_id"] = $store_id;
        $where["hii_consignment_in.c_in_status"] = 0;
        $datas = $ConsignmentModel->where($where)->order(" c_in_id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法作废该寄售单");
        }
        $ok = $ConsignmentModel->where(" `c_in_id`={$c_in_id} ")->order(" c_in_id desc ")->limit(1)->save(array("c_in_status" => 2));
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /**********************
     * 修改寄售单接口
     * 请求方式：POST
     * 请求参数：c_in_id       寄售单ID        必须
     *           supply_id  供应商ID        必须
     *           remark     备注            非必须
     *           info_json  明细修改json    必须     格式[{"c_in_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""},
     *                                                     "c_in_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""}]
     * 日期：2017-12-27
     */
    public function updateConsignment()
    {
        $c_in_id = I("post.c_in_id");
        $supply_id = I("post.supply_id");
        $remark = I("post.remark");
        $info_json = I("post.info_json");
        $eadmin_id = UID;
        $store_id = $this->_store_id;
        if (is_null($c_in_id) || empty($c_in_id)) {
            $this->response(0, "请选择要修改的寄售入库单");
        }
        if (is_null($supply_id) || empty($supply_id)) {
            $this->response(0, "请选择供应商");
        }
        if ($this->isArrayNull($info_json) == null) {
            $this->response(0, "请提交需要修改的子表信息");
        }
        $StoreConsignRepository = D("StoreConsign");
        $result = $StoreConsignRepository->updateConsignment($eadmin_id, $store_id, $c_in_id, $supply_id, $remark, $info_json);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }


    /*****************************
     * 审核寄售单接口
     * 请求方式：POST
     * 请求参数：c_id  寄售单ID  必须
     * 日期：2017-12-27
     */
    public function check()
    {
        $c_in_id = I("post.c_in_id");
        if (is_null($c_in_id) || empty($c_in_id)) {
            $this->response(0, "请提交需要审核的寄售入库单");
        }
        $StoreConsignRepository = D("StoreConsign");
        $padmin_id = UID;
        $store_id = $this->_store_id;
        $result = $StoreConsignRepository->check($padmin_id, $store_id, $c_in_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /***************************
     * 通过商品ID获取临时申请信息
     * 请求方式：GET
     * 请求参数：goods_id  商品ID  必须
     * 日期：2017-12-27
     */
    public function getRequestTempInfoByGoodsId()
    {
        $goods_id = I("get.goods_id");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        $admin_id = UID;
        $temp_type = 9;
        $where = array();
        $where["hii_request_temp.admin_id"] = UID;
        $where["hii_request_temp.temp_type"] = 9;
        $where["hii_request_temp.goods_id"] = $goods_id;
        $where["hii_request_temp.status"] = 0;
        $RequestTempModel = M("RequestTemp");
        $datas = $RequestTempModel->where($where)->order(" id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(self::CODE_OK, array(
                    "id" => "",
                    "b_n_num" => "",
                    "b_num" => "",
                    "b_price" => "",
                    "g_num" => "",
                    "g_price" => "",
                    "remark" => "")
            );
        } else {
            $data = $datas[0];
            $this->response(self::CODE_OK, array(
                    "id" => $data["id"],
                    "b_n_num" => $data["b_n_num"],
                    "b_num" => $data["b_num"],
                    "b_price" => $data["b_price"],
                    "g_num" => $data["g_num"],
                    "g_price" => $data["g_price"],
                    "remark" => $data["remark"])
            );
        }
    }

    private function getIndexList($usePager)
    {
        $store_id = $this->_store_id;
        $data = null;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $ConsignmentModel = M("Consignment");
        $can_store_id_array = $this->getCanStoreIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " CI.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . "  CI.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ( {$shequ_where} ) ";
        }

        $sql = "select CI.c_in_id,CI.c_in_sn,FROM_UNIXTIME(CI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,CI.g_type,CI.g_nums,CI.c_in_status,CI.remark, ";
        $sql .= "M.nickname as admin_nickname,SY.s_name as supply_name,S.title as store_name, ";
        $sql .= "SUM(CID.b_num*CID.b_price) as b_amounts, ";
        $sql .= "SUM(CID.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_consignment_in CI ";
        $sql .= "left join hii_consignment_in_detail CID on CID.c_in_id=CI.c_in_id ";
        $sql .= "left join hii_goods G on G.id=CID.goods_id ";
        $sql .= "left join hii_member M on M.uid=CI.admin_id ";
        $sql .= "left join hii_store S on S.id=CI.store_id ";
        $sql .= "left join hii_supply SY on SY.s_id=CI.supply_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=CID.goods_id and GS.store_id={$store_id} ";
        $sql .= "where CI.store_id={$store_id} {$shequ_where} and FROM_UNIXTIME(CI.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "group by CI.c_in_id,CI.c_in_sn,CI.ctime,CI.g_type,CI.g_nums,CI.c_in_status,CI.remark,M.nickname,SY.s_name,S.title ";
        $sql .= "order by CI.c_in_id desc ";

        //echo $sql;exit;

        $data = $ConsignmentModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            switch ($val["c_in_status"]) {
                case 0: {
                    $data[$key]["c_in_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["c_in_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["c_in_status_name"] = "已作废";
                };
                    break;
            }
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

    private function getViewInfo()
    {
        $c_in_id = I("get.c_in_id");
        if (is_null($c_in_id) || empty($c_in_id)) {
            $this->response(0, "请选择寄售入库单");
        }
        $store_id = $this->_store_id;
        $ConsignmentModel = M("ConsignmentIn");
        $ConsignmentDetailModel = M("ConsignmentInDetail");
        $sql = "select CI.c_in_id,CI.c_in_sn,FROM_UNIXTIME(CI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,CI.g_type,CI.g_nums,CI.c_in_status,CI.remark, ";
        $sql .= "CI.store_id,CI.supply_id,SY.s_name as supply_name, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,SUM(CID.g_price*CID.g_num) as b_amounts ";
        $sql .= "from hii_consignment_in CI ";
        $sql .= "left join hii_consignment_in_detail CID on CID.c_in_id=CI.c_in_id ";
        $sql .= "left join hii_goods G on G.id=CID.goods_id ";
        $sql .= "left join hii_member M on M.uid=CI.admin_id ";
        $sql .= "left join hii_store S on S.id=CI.store_id ";
        $sql .= "left join hii_supply SY on SY.s_id=CI.supply_id ";
        $sql .= "where CI.c_in_id={$c_in_id} and CI.store_id={$store_id} ";
        $sql .= "group by CI.c_in_id,CI.c_in_sn,CI.ctime,CI.g_type,CI.g_nums,CI.c_in_status,CI.remark,M.nickname,S.title,CI.store_id,CI.supply_id,SY.s_name ";
        $sql .= "order by CI.c_in_id desc limit 1 ";
        //echo $sql;exit;
        $datas = $ConsignmentModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该寄售单不存在");
        }
        $maindata = $datas[0];
        switch ($maindata["c_in_status"]) {
            case 0: {
                $maindata["c_in_status_name"] = "新增";
            };
                break;
            case 1: {
                //$maindata["c_in_status_name"] = "已审核转入库单";
                $maindata["c_in_status_name"] = "已审核";
            };
                break;
            case 2: {
                $maindata["c_in_status_name"] = "已作废";
            };
                break;
        }

        //sys_price 系统售价  shequ_price 区域价  store_price 门店价
        $sql = "select CID.c_in_d_id,CID.goods_id,CID.b_n_num,CID.b_num,CID.b_price,CID.g_num,CID.g_price,CID.remark, ";
        $sql .= "G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,ifnull(GS.num,0) as stock_num,GC.title as cate_name, ";
        $sql .= "ifnull(GLPPV.g_price,0) as last_price, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price,AV.value_id,AV.value_name  ";
        $sql .= "from hii_consignment_in_detail CID ";
        $sql .= "left join hii_goods G on G.id=CID.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=CID.goods_id ";
        $sql .= "left join hii_goods_last_purchase_price_view GLPPV on CID.goods_id=GLPPV.goods_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=CID.value_id ";
        $sql .= "where CID.c_in_id={$c_in_id} ";
        $list = $ConsignmentDetailModel->query($sql);

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

    private function getTempListData($temp_type)
    {
        $admin_id = UID;
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.admin_id,RT.temp_type,RT.goods_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,RT.remark, ";
        $sql .= "RT.status,RT.b_n_num,RT.b_num,RT.b_price,RT.g_num,RT.g_price,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code, ";
        $sql .= "GC.title as cate_name,ifnull(GS.num,0) as stock_num,ifnull(GLPPV.g_price,0) as last_price, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=RT.goods_id ";
        $sql .= "left join hii_goods_last_purchase_price_view GLPPV on RT.goods_id=GLPPV.goods_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id = RT.value_id ";
        $sql .= "where RT.admin_id={$admin_id} and RT.store_id={$store_id} and RT.temp_type={$temp_type} and RT.status=0 ";
        $sql .= "order by RT.id desc ";
        //echo $sql;exit;
        $list = $RequestTempModel->query($sql);
        foreach ($list as $key => $val) {
            if (!is_null($val["store_price"]) && !empty($val["store_price"]) && $val["store_price"] > 0) {
                $list[$key]["sell_price"] = $val["store_price"];
            } elseif (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $list[$key]["sell_price"] = $val["shequ_price"];
            } else {
                $list[$key]["sell_price"] = $val["sys_price"];
            }
        }
        return $list;
    }

    /************************************
     * 通过bar_code 或者 goods_id获取商品信息
     * 请求方式：GET
     * 请求参数：bar_code   商品条码   否
     *           goods_id   商品ID     否
     * 日期：2018-01-20
     */
    public function getingoods()
    {
        $bar_code = I("get.bar_code");
        $goods_id = I("get.goods_id");
        $temp_type = 9;
        $admin_id = UID;
        $GoodsModel = M("Goods");
        $RequestTempModel = M("RequestTemp");
        if (empty($bar_code) && empty($goods_id)) {
            $this->response(0, "请提供商品条码或商品ID");
        }
        if (!empty($bar_code)) {
            $datas = $GoodsModel->where(" bar_code='{$bar_code}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $goods_id = $datas[0]["id"];
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["b_n_num"] = $tmp[0]["b_n_num"];
                    $outdata["b_num"] = $tmp[0]["b_num"];
                    $outdata["b_price"] = $tmp[0]["b_price"];
                    $outdata["g_price"] = $tmp[0]["g_price"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        } elseif (!empty($goods_id)) {
            $datas = $GoodsModel->where(" id='{$goods_id}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["b_n_num"] = $tmp[0]["b_n_num"];
                    $outdata["b_num"] = $tmp[0]["b_num"];
                    $outdata["b_price"] = $tmp[0]["b_price"];
                    $outdata["g_price"] = $tmp[0]["g_price"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        }
    }

    /******************************************* 寄售入库部分 end *************************************************************************************************************/


    /******************************************* 寄售出库部分 start *************************************************************************************************************/

    /******************************
     * 临时寄售单接口
     * 请求方式：GET
     * 请求参数：无
     * 注意：
     * 日期：2018-01-15
     */
    public function outtemp()
    {
        $temp_type = 12;
        $datas = $this->getTempListData($temp_type);
        $this->response(self::CODE_OK, $datas);
    }

    /******************
     * 加入临时申请单接口
     * 请求方式：POST
     * 请求参数：goods_id  商品ID             必须
     *           g_num     采购数量           必须
     *           remark    备注               非必须
     * 日期：2018-01-15
     */
    public function addOutRequestTemp()
    {
        $temp_type = 12;
        $this->addSingleRequestTemp($temp_type);
    }


    /**********************************************
     * 提交临时申请
     * 请求方式：POST
     * 请求参数：supply_id  供应商ID  必须
     *           remark     备注      非必须
     * 日期：2018-01-15
     */
    public function submitOutRequestTemp()
    {
        $supply_id = I("post.supply_id");
        $remark = I("post.remark");
        if (is_null($supply_id) || empty($supply_id)) {
            $this->response(0, "请选择供应商");
        }
        $admin_id = UID;
        $store_id = $this->_store_id;
        $StoreConsignRepository = D("StoreConsign");
        $result = $StoreConsignRepository->submitOutRequestTemp($admin_id, $store_id, $supply_id, $remark);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }


    /**********************
     * 清空临时申请接口
     * 请求方式：POST
     * 请求参数：无
     * 日期：2018-01-15
     */
    public function clearOutRequestTemp()
    {
        $admin_id = UID;
        $temp_type = 12;
        $RequestTempModel = M("RequestTemp");
        $store_id = $this->_store_id;
        $ok = $RequestTempModel->where(" `admin_id`={$admin_id} and `store_id`={$store_id} and `temp_type`={$temp_type} and `status`=0 ")->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /**********************
     * 删除临时申请单接口
     * 请求方式：POST
     * 请求参数：id  临时申请单ID  必须
     * 日期：2017-12-26
     */
    public function deleteOutRequestTemp()
    {
        $id = I("post.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择需要删除的临时申请单");
        }
        $admin_id = UID;
        $temp_type = 12;
        $RequestTempModel = M("RequestTemp");
        $datas = $RequestTempModel->where(" id={$id} and admin_id={$admin_id} and temp_type={$temp_type} and  `status`=0 ")->order(" id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该临时申请单不存在");
        }
        $ok = $RequestTempModel->where(" id={$id} ")->order(" id desc ")->limit(1)->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /*****************************
     * 临时申请单编辑接口
     * 请求方式：GET
     * 请求参数：id  临时申请单ID  必须
     * 日期：2017-12-26
     */
    public function editOut()
    {
        $id = I("get.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择需要编辑的临时申请单");
        }
        $admin_id = UID;
        $temp_type = 12;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,G.bar_code,G.title as goods_name,RT.b_n_num,RT.b_num,RT.b_price,RT.g_num,RT.g_price,RT.remark,RT.value_id ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "where RT.id={$id} and RT.admin_id={$admin_id} and RT.temp_type={$temp_type} and  RT.status=0 order by RT.id desc limit 1 ";
        $datas = $RequestTempModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该临时申请单不存在");
        } else {
        	$attr_value_array = M('AttrValue')->where(array('goods_id'=>$datas[0]['goods_id'],'status'=>array('neq',2)))->select();
        	$datas[0]['attr_value_array'] = $attr_value_array;
            $this->response(self::CODE_OK, $datas[0]);
        }
    }

    /*******************
     * 寄售出库单
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function outindex()
    {
        $result = $this->getOutIndexList(true);
        $this->response(self::CODE_OK, $result);
    }

    /********************
     * 导出寄售单Excel接口
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     * 日期：2017-12-26
     */
    public function exportOutIndexListExcel()
    {
        $result = $this->getOutIndexList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 寄售出库单';
        $fname = './Public/Excel/StoreConsignment_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreConsignModel();
        $printfile = $printmodel->createOutIndexListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /***************************
     * 查看接口
     * 请求方式：GET
     * 请求参数：c_ou_id  寄售单ID  必须
     * 日期：2017-12-26
     */
    public function outview()
    {
        $result = $this->getOutViewInfo();
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 导出查看Excel接口
     * 请求方式：GET
     * 请求参数：c_id  寄售单ID  必须
     * 日期：2017-12-26
     */
    public function exportOutViewExcel()
    {
        $result = $this->getOutViewInfo();
        ob_clean;
        $title = '寄售出库单查看';
        $fname = './Public/Excel/StoreConsignmentView_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreConsignModel();
        $printfile = $printmodel->createOutViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*********************
     * 作废寄售单接口
     * 请求方式：POST
     * 请求参数：c_out_id  寄售单ID  必须
     * 日期：2017-12-26
     *********************/
    public function cancelOutConsignment()
    {
        $c_out_id = I("post.c_out_id");
        if (is_null($c_out_id) || empty($c_out_id)) {
            $this->response(0, "请选择要作废的寄售出库单");
        }
        $ConsignmentModel = M("ConsignmentOut");
        $store_id = $this->_store_id;
        $where = array();
        $where["hii_consignment_out.c_out_id"] = $c_out_id;
        $where["hii_consignment_out.store_id"] = $store_id;
        $where["hii_consignment_out.c_status"] = 0;
        $datas = $ConsignmentModel->where($where)->order(" c_out_id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法作废该寄售单");
        }
        $ok = $ConsignmentModel->where(" `c_out_id`={$c_out_id} ")->order(" c_out_id desc ")->limit(1)->save(array("c_status" => 2));
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /**********************
     * 修改寄售单接口
     * 请求方式：POST
     * 请求参数：c_out_id       寄售单ID        必须
     *           supply_id  供应商ID        必须
     *           remark     备注            非必须
     *           info_json  明细修改json    必须     格式[{"c_out_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""},
     *                                                     "c_out_d_id":"","b_n_num":"","b_num":"","b_price":"","g_num":"","g_price":""}]
     * 日期：2017-12-27
     */
    public function updateOutConsignment()
    {
        $c_out_id = I("post.c_out_id");
        $supply_id = I("post.supply_id");
        $remark = I("post.remark");
        $info_json = I("post.info_json");
        $eadmin_id = UID;
        $store_id = $this->_store_id;
        if (is_null($c_out_id) || empty($c_out_id)) {
            $this->response(0, "请选择要修改的寄售出库单");
        }
        if (is_null($supply_id) || empty($supply_id)) {
            $this->response(0, "请选择供应商");
        }
        if ($this->isArrayNull($info_json) == null) {
            $this->response(0, "请提交需要修改的子表信息");
        }
        $StoreConsignRepository = D("StoreConsign");
        $result = $StoreConsignRepository->updateOutConsignment($eadmin_id, $store_id, $c_out_id, $supply_id, $remark, $info_json);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /*****************************
     * 审核寄售单接口
     * 请求方式：POST
     * 请求参数：c_out_id  寄售单ID  必须
     * 日期：2017-12-27
     */
    public function checkOut()
    {
        $c_out_id = I("post.c_out_id");
        if (is_null($c_out_id) || empty($c_out_id)) {
            $this->response(0, "请提交需要审核的寄售出库单");
        }
        $StoreConsignRepository = D("StoreConsign");
        $padmin_id = UID;
        $store_id = $this->_store_id;
        $result = $StoreConsignRepository->checkOut($padmin_id, $store_id, $c_out_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    private function getOutIndexList($usePager)
    {
        $store_id = $this->_store_id;
        $data = null;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $StoreOutStockModel = M("StoreOutStock");

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_supply_id_array = $this->getCanSupplyIdArray();

        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " CO.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_supply_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . "  CO.supply_id in (" . implode(",", $can_supply_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ( {$shequ_where} ) ";
        }

        $sql = "select CO.c_out_id,CO.c_out_sn,FROM_UNIXTIME(CO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,CO.g_type,CO.g_nums,CO.c_status as c_out_status,CO.remark, ";
        $sql .= "M.nickname as admin_nickname,SY.s_name as supply_name,S.title as store_name, ";
        $sql .= "SUM(COD.b_num*COD.b_price) as b_amounts, ";
        $sql .= "SUM(COD.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_consignment_out CO ";
        $sql .= "left join hii_consignment_out_detail COD on COD.c_out_id=CO.c_out_id ";
        $sql .= "left join hii_store S on S.id=CO.store_id ";
        $sql .= "left join hii_goods G on G.id=COD.goods_id ";
        $sql .= "left join hii_member M on M.uid=CO.admin_id ";
        $sql .= "left join hii_supply SY on SY.s_id=CO.supply_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=COD.goods_id ";
        $sql .= "where CO.store_id={$store_id} {$shequ_where} and FROM_UNIXTIME(CO.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "group by CO.c_out_id,CO.c_out_sn,CO.ctime,CO.g_type,CO.g_nums,CO.c_status,CO.remark,M.nickname,SY.s_name,S.title  ";
        $sql .= "order by CO.c_out_id desc ";

        $data = $StoreOutStockModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            switch ($val["c_out_status"]) {
                case 0: {
                    $data[$key]["c_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["c_out_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["c_out_status_name"] = "已作废";
                };
                    break;
            }
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

    private function getOutViewInfo()
    {
        $c_in_id = I("get.c_out_id");
        if (is_null($c_in_id) || empty($c_in_id)) {
            $this->response(0, "请选择寄售出库单");
        }
        $store_id = $this->_store_id;
        $ConsignmentOutModel = M("ConsignmentOut");
        $ConsignmentOutDetailModel = M("ConsignmentOutDetail");
        $sql = "select CO.c_out_id,CO.c_out_sn,FROM_UNIXTIME(CO.ctime,'%Y-%m-%d %H:%i:%s') as ctime,CO.g_type,CO.g_nums,CO.c_status as c_out_status ,CO.remark, ";
        $sql .= "CO.store_id,CO.supply_id,SY.s_name as supply_name, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,SUM(COD.g_num*COD.g_price) as b_amounts ";
        $sql .= "from hii_consignment_out CO ";
        $sql .= "left join hii_consignment_out_detail COD on COD.c_out_id=CO.c_out_id ";
        $sql .= "left join hii_goods G on G.id=COD.goods_id ";
        $sql .= "left join hii_member M on M.uid=CO.admin_id ";
        $sql .= "left join hii_store S on S.id=CO.store_id ";
        $sql .= "left join hii_supply SY on SY.s_id=CO.supply_id ";
        $sql .= "where CO.c_out_id={$c_in_id} and CO.store_id={$store_id} ";
        $sql .= "group by CO.c_out_id,CO.c_out_sn,CO.ctime,CO.g_type,CO.g_nums,CO.c_status,CO.remark,M.nickname,S.title,CO.store_id,CO.supply_id,SY.s_name ";
        $sql .= "order by CO.c_out_id desc limit 1 ";

        $datas = $ConsignmentOutModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该寄售单不存在");
        }
        $maindata = $datas[0];
        switch ($maindata["c_out_status"]) {
            case 0: {
                $maindata["c_out_status_name"] = "新增";
            };
                break;
            case 1: {
                //$maindata["c_out_status_name"] = "已审核转出库单";
                $maindata["c_out_status_name"] = "已审核";
            };
                break;
            case 2: {
                $maindata["c_out_status_name"] = "已作废";
            };
                break;
        }

        $sql = "select COD.c_out_d_id,COD.goods_id,COD.b_n_num,COD.b_num,COD.b_price,COD.g_num,COD.g_price,COD.remark, ";
        $sql .= "G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,ifnull(GS.num,0) as stock_num,GC.title as cate_name, ";
        $sql .= "ifnull(GLPPV.g_price,0) as last_price,G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_consignment_out_detail COD ";
        $sql .= "left join hii_goods G on G.id=COD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=COD.goods_id ";
        $sql .= "left join hii_goods_last_purchase_price_view GLPPV on COD.goods_id=GLPPV.goods_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=COD.value_id ";
        $sql .= "where COD.c_out_id={$c_in_id} ";
        $list = $ConsignmentOutDetailModel->query($sql);
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

    /************************************
     * 通过bar_code 或者 goods_id获取商品信息
     * 请求方式：GET
     * 请求参数：bar_code   商品条码   否
     *           goods_id   商品ID     否
     * 日期：2018-01-20
     */
    public function getoutgoods()
    {
        $bar_code = I("get.bar_code");
        $goods_id = I("get.goods_id");
        $temp_type = 12;
        $admin_id = UID;
        $GoodsModel = M("Goods");
        $RequestTempModel = M("RequestTemp");
        if (empty($bar_code) && empty($goods_id)) {
            $this->response(0, "请提供商品条码或商品ID");
        }
        if (!empty($bar_code)) {
            $datas = $GoodsModel->where(" bar_code='{$bar_code}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $goods_id = $datas[0]["id"];
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["b_n_num"] = $tmp[0]["b_n_num"];
                    $outdata["b_num"] = $tmp[0]["b_num"];
                    $outdata["b_price"] = $tmp[0]["b_price"];
                    $outdata["g_price"] = $tmp[0]["g_price"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        } elseif (!empty($goods_id)) {
            $datas = $GoodsModel->where(" id='{$goods_id}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["b_n_num"] = $tmp[0]["b_n_num"];
                    $outdata["b_num"] = $tmp[0]["b_num"];
                    $outdata["b_price"] = $tmp[0]["b_price"];
                    $outdata["g_price"] = $tmp[0]["g_price"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        }
    }

    /******************************************* 寄售出库部分 end *************************************************************************************************************/


    /******************************************* 报表 start *************************************************************************************************************/
    /*******************
     * 报表
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function report()
    {
        ini_set('max_execution_time', '0');
        $result = $this->getReportList(true);
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 导出报表excel
     * 请求方式：GET
     * 请求参数：s_date    开始日期  非必填
     *           e_date    结束日期  非必填
     *           p         当前页    非必填   默认1
     *           pageSize  每页显示数量
     */
    public function exportReportExcel()
    {
        ini_set('max_execution_time', '0');
        $result = $this->getReportList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 寄售报表';
        $fname = './Public/Excel/StoreConsignmentReport_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreConsignModel();
        $printfile = $printmodel->createReportExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /******************************************* 报表 end *************************************************************************************************************/

    private function getReportList($usePager)
    {
        $cate_id = 18;//私人定制【商品种类】
        $dates = $this->getDates();
        $store_id = $this->_store_id;
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $goods_name = I("get.goods_name");
        $GoodsModel = M("Goods");

        $in_where = "";
        if (!is_null($goods_name) && !empty($goods_name)) {
            $in_where .= " and G.title like '%{$goods_name}%' ";
        }

        //寄售入库
        $sql_consignment_in = "(select CID.goods_id,SUM(CID.g_num) as in_nums from hii_consignment_in CI 
        left join hii_consignment_in_detail CID on CI.c_in_id=CID.c_in_id 
        where CI.store_id={$store_id} and CI.c_in_status=1 and FROM_UNIXTIME(CI.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' group by CID.goods_id ) ";
        //寄售出库
        $sql_consignment_out = "(select COD.goods_id,SUM(COD.g_num) as out_nums from hii_consignment_out CO 
        left join hii_consignment_out_detail COD on CO.c_out_id=COD.c_out_id 
        where CO.store_id={$store_id} and CO.c_status=1 and FROM_UNIXTIME(CO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' group by COD.goods_id ) ";
        //系统销售
        $sql_system_sold = "(select OD.d_id as goods_id,SUM(OD.num) as sold_nums from hii_order O 
        left join hii_order_detail OD on OD.order_sn=O.order_sn 
        where O.pay_status=2 and O.store_id={$store_id} and FROM_UNIXTIME(O.pay_time, '%Y-%m-%d') BETWEEN '{$s_date}' AND '{$e_date}' group by OD.d_id )  ";

        $sql = "select G.id as goods_id,G.title as goods_name,ifnull(CID.in_nums,0) as in_nums,ifnull(COD.out_nums,0) as out_nums,ifnull(OD.sold_nums,0) as sold_nums ";
        $sql .= "from hii_goods G  ";
        $sql .= "left join {$sql_consignment_in} CID on CID.goods_id=G.id ";
        $sql .= "left join {$sql_consignment_out} COD on COD.goods_id=G.id ";
        $sql .= "left join {$sql_system_sold} OD on OD.goods_id=G.id ";
        $sql .= "where 1=1 and G.cate_id={$cate_id} {$in_where} ";
        $sql .= "group by G.id,G.title ";
        $sql .= "order by in_nums desc ";

        //echo $sql;
        //exit;
        if (I("get.showsql") == true) {
            echo $sql;
            exit;
        }

        $data = $GoodsModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
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

    private function addSingleRequestTemp($temp_type)
    {
        $id = I("post.id");
        $goods_id = I("post.goods_id");
        $b_n_num = I("post.b_n_num");
        $b_num = I("post.b_num");
        $b_price = I("post.b_price");
        $g_num = I("post.g_num");
        $g_price = I("post.g_price");
        $value_id = I("post.value_id",0);
        $remark = I("post.remark");
        $temp_id = I('post.temp_id','');
        $admin_id = UID;

        if(empty($value_id)){
        	$this->response(0, "请选择商品属性");
        }
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        if (is_null($b_n_num) || empty($b_n_num)) {
            //$this->response(0, "请填写箱规");
        }
        if (is_null($b_num) || empty($b_num)) {
            //$this->response(0, "请填写采购数量");
        }
        if (is_null($b_price) || empty($b_price)) {
            //$this->response(0, "请填写每箱价格");
        }
        if (is_null($g_num) || empty($g_num)) {
            $this->response(0, "请填写采购数量");
        }
        if (is_null($g_price) || empty($g_price)) {
            $this->response(0, "请填写采购价");
        }
        $GoodsModel = M("Goods");
        $datas = $GoodsModel->query(" select id from hii_goods where id={$goods_id} order by id desc limit 1 ");
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该商品不存在");
        }
        $RequestTempModel = M("RequestTemp");
//如果$temp_id临时申请表id不未空 按id删除后重新生成
        if(!empty($temp_id)){
            //更新
            $saveData["goods_id"] = $goods_id;
            $saveData["remark"] = $remark;
            $saveData["g_num"] = $g_num;
            $saveData["g_price"] = $g_price;
            $saveData["value_id"] = $value_id;
            $result = $RequestTempModel->where(" id={$temp_id} ")->save($saveData);
            if ($result === false) {
                $this->response(0, "操作失败");
            } else {
                //判断是否有重复商品属性如果有删除一个
                $RequestTempModel->where(array('id'=>array('NEQ',$temp_id),'admin_id'=>$admin_id,'store_id'=>$this->_store_id,'goods_id'=>$goods_id,'temp_type'=>$temp_type,'value_id'=>$value_id))->delete();

                $this->response(self::CODE_OK, "操作成功");
            }

        }else {
            $where = array();
            $where["admin_id"] = $admin_id;
            $where["goods_id"] = $goods_id;
            $where["temp_type"] = $temp_type;
            $where["store_id"] = $this->_store_id;
            $where["value_id"] = $value_id;

            $datas = $RequestTempModel->where($where)->order(" id desc ")->limit(1)->select();

            if ($this->isArrayNull($datas) == null) {
                $RequestTempEntity = array();
                $RequestTempEntity["admin_id"] = $admin_id;
                $RequestTempEntity["store_id"] = $this->_store_id;
                $RequestTempEntity["temp_type"] = $temp_type;
                $RequestTempEntity["goods_id"] = $goods_id;
                $RequestTempEntity["ctime"] = time();
                $RequestTempEntity["status"] = 0;
                $RequestTempEntity["b_n_num"] = $b_n_num;
                $RequestTempEntity["b_num"] = $b_num;
                $RequestTempEntity["b_price"] = $b_price;
                $RequestTempEntity["g_num"] = $g_num;
                $RequestTempEntity["g_price"] = $g_price;
                $RequestTempEntity["remark"] = $remark;
                $RequestTempEntity["value_id"] = $value_id;
                $ok = $RequestTempModel->add($RequestTempEntity);
            } else {
                $data = $datas[0];
                $data["b_n_num"] = $b_n_num;
                $data["b_num"] = $b_num;
                $data["b_price"] = $b_price;
                $data["g_num"] = $g_num;
                $data["g_price"] = $g_price;
                $data["remark"] = $remark;
                $ok = $RequestTempModel->save($data);
            }
            if ($ok === false) {
                $this->response(0, "操作失败");
            } else {
                $this->response(self::CODE_OK, "操作成功");
            }
        }
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