<?php

/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-22
 * Time: 11:11
 * 门店调拨相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreAssignmentApplicationController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /********************
     * 临时调拨申请列表接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     */
    public function temp()
    {
        $list = $this->getStoreAssignmentApplicationTempList();
        $this->response(self::CODE_OK, $list);
    }

    /*********************
     * 调拨申请单列表接口
     * 请求方式：GET
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     * 日期：2017-11-22
     */
    public function index()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreToStoreModel = M("StoreToStore");

        $store_in = "";
        $can_store_id_array = $this->getCanStoreIdArray();
        if (count($can_store_id_array) > 0) {
            $store_in .= " and A.store_id1 in (" . implode(",", $can_store_id_array) . ") and A.store_id2 in (" . implode(",", $can_store_id_array) . ") ";
        }

        $sql = "select A.s_t_s_id,A.s_t_s_sn,A.s_t_s_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id, ";
        $sql .= "M1.nickname as admin_nickname,A.store_id1,A.store_id2,S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "A.remark,A.g_type,A.g_nums,sum(A1.is_pass) as pass_num, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_to_store A ";
        $sql .= "left join hii_store_to_store_detail A1 on A1.s_t_s_id=A.s_t_s_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$this->_store_id} ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "where A.store_id1 = {$this->_store_id} {$store_in} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by A.s_t_s_id,A.s_t_s_sn,A.s_t_s_status,A.ctime,M1.nickname,A.store_id1,A.store_id2,S1.title,S2.title,A.remark,A.g_type,A.g_nums ";
        $sql .= "order by A.s_t_s_id desc ";
        //\Think\Log::record($sql);
        $data = $StoreToStoreModel->query($sql);
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            //状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
            switch ($val["s_t_s_status"]) {
                case 0: {
                    $datamain[$key]["s_t_s_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $datamain[$key]["s_t_s_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $datamain[$key]["s_t_s_status_name"] = "部分通过申请";
                };
                    break;
                case 3: {
                    $datamain[$key]["s_t_s_status_name"] = "全部通过申请";
                };
                    break;
                case 4: {
                    $datamain[$key]["s_t_s_status_name"] = "全部拒绝";
                };
                    break;
                case 5: {
                    $datamain[$key]["s_t_s_status_name"] = "已作废";
                };
                    break;
                default: {
                    $datamain[$key]["s_t_s_status_name"] = "新增";
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

    /*********************
     * 清空调拨临时申请表接口
     * 请求方式：POST
     * 请求参数：无
     * 日期：2017-11-22
     */
    public function clearTemp()
    {
        $temp_type = 6;//门店调拨申请种类
        $admin_id = UID;
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} ")->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        }
        $this->response(self::CODE_OK, "操作成功");
    }

    /*************
     * 获取门店列表接口
     * 请求方式：GET
     * 请求参数：无
     * 日期：2017-11-22
     */
    public function getStoreList()
    {
        $StoreModel = M("Store");
        $store_id = $this->_store_id;
        $where = " id<>{$store_id} and shequ_id=(select shequ_id from hii_store where id={$store_id} limit 1 )  ";
        $can_store_id_array = $this->getCanStoreIdArray();
        if (count($can_store_id_array) > 0) {
            $where .= " and id in (" . implode(",", $can_store_id_array) . ") ";
        }
        $list = $StoreModel->field("id,title as name")->where($where)->select();
        $this->response(self::CODE_OK, $list);
    }

    /*********************
     * 加入临时申请表接口
     * 请求方式：POST
     * 请求参数： goods_id 商品ID 必须
     *            g_num    申请数量  必须
     * 日期：2017-11-22
     * 提示：b_n_num,b_num,b_price,g_price未付值
     */
    public function addRequestTemp()
    {
        $admin_id = UID;
        $temp_type = 6;//门店调拨申请
        $ctime = time();
        $status = 0;//临时存在
        $b_n_num = 0;//箱规
        $b_num = 0;//箱数
        $b_price = 0;//每箱价格
        $g_num = null;//申请数量
        $g_price = 0;//临时采购价
        $remark = "";

        /********检测提交数据***************/
        $goods_id = I("post.goods_id");
        $g_num = I("post.g_num");
        $remark = I("post.remark");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        if (is_null($g_num) || empty($g_num)) {
            $this->response(0, "请填写申请数量");
        }

        //获取区域ID
        $store_id = $this->_store_id;
        $StoreModel = M("Store");
        $shequ_id = 0;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if ($this->isArrayNull($store_datas) != null) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }
        //获取g_price
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $warehouseinoutview_datas = $WarehouseInoutViewModel->field(" ifnull(stock_price,0) as stock_price ")
            ->where(" goods_id={$goods_id} and shequ_id={$shequ_id} ")
            ->limit(1)
            ->select();
        if ($this->isArrayNull($warehouseinoutview_datas) != null) {
            $g_price = $warehouseinoutview_datas[0]["stock_price"];
        }

        $RequestTempModel = M("RequestTemp");
            $where["admin_id"] = $admin_id;
            $where["hii_request_temp.status"] = $status;
            $where["goods_id"] = $goods_id;
            $where["temp_type"] = $temp_type;
            $where["store_id"] = $store_id;
            $data = $RequestTempModel
                ->where($where)
                ->order(" id desc ")
                ->limit(1)
                ->select();

            if ($data) {
                //更新
                $saveData["g_num"] = $g_num;
                $saveData["remark"] = $remark;
                $saveData["g_price"] = $g_price;
                $result = $RequestTempModel->where(" id={$data[0]["id"]} ")->save($saveData);
                if ($result === false) {
                    $this->response(0, $RequestTempModel->getError());
                } else {
                    $list = $this->getStoreAssignmentApplicationTempList();
                    $this->response(self::CODE_OK, $list);
                }
            } else {
                //新增
                $data["admin_id"] = $admin_id;
                $data["store_id"] = $store_id;
                $data["temp_type"] = $temp_type;
                $data["goods_id"] = $goods_id;
                $data["ctime"] = $ctime;
                $data["status"] = $status;
                $data["b_n_num"] = $b_n_num;
                $data["b_num"] = $b_num;
                $data["b_price"] = $b_price;
                $data["g_num"] = $g_num;
                $data["g_price"] = $g_price;
                $data["remark"] = $remark;
                $result = $RequestTempModel->add($data);
                if (!$result) {
                    $this->response(0, $RequestTempModel->getError());
                } else {
                    $list = $this->getStoreAssignmentApplicationTempList();
                    $this->response(self::CODE_OK, $list);
                }
            }
    }

    /***************
     * 获取门店出货单临时表数据
     * 请求方式：POST
     * 请求参数：无
     * 日期：2017-11-22
     */
    private function getStoreAssignmentApplicationTempList()
    {
        $store_id = $this->_store_id;
        $admin_id = UID;
        $temp_type = 6;
        $status = 0;
        $RequestTempModel = M("RequestTemp");
        //sys_price 系统售价 shequ_price 区域售价 store_price 门店售价
        $sql = "select A.id,A.goods_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,G.title as goods_name,A.remark, ";
        $sql .= "G.bar_code,GC.title as cate_name,G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price, ";
        $sql .= "'' as stock_num,ifnull(GS.num,0) as current_stock_num,A.g_num ";
        $sql .= "from hii_request_temp A ";
        $sql .= "left join hii_goods G on G.id=A.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A.goods_id and GS.store_id={$store_id} ";
        $sql .= "where A.admin_id={$admin_id} and A.store_id={$store_id} and A.temp_type={$temp_type} and A.status={$status} order by A.id desc ";
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
        return $this->isArrayNull($list);
    }

    /*****************
     * 提交临时调拨申请单接口
     * 请求方式：POST
     * 请求参数：remark 备注 非必需
     *           store_id 发货门店ID 必须
     * 日期：2017-11-22
     * 注意：要检测发货门店库存是否充足，只提交库存充足部分
     ***************/
    public function submitRequestTemp()
    {
        $admin_id = UID;
        $remark = I("post.remark");
        $store_id1 = $this->_store_id;
        $store_id2 = I("post.store_id");
        $StoreModel = M("Store");
        $data = $StoreModel->query("select id from hii_store where id={$store_id2} limit 1 ");
        if (is_null($data) || empty($data)) {
            $this->response(0, "不存在该门店");
        }
        $StoreAssignmentApplicationRepository = D("StoreAssignmentApplication");
        $res = $StoreAssignmentApplicationRepository->submitRequestTemp($admin_id, $store_id1, $store_id2, $remark);
        if ($res["status"] == "200") {
            //加入消息提醒
            $MessageWarnModel = D('MessageWarn');
            $MessageWarnModel->pushMessageWarn($admin_id  , 0  ,$store_id2 ,  0 , $res['data'] ,12);
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /********************
     * 删除单个临时申请接口
     * 请求方式：POST
     * 请求参数：id  临时申请ID  必须
     * 日期：2017-11-23
     */
    public function deleteRequestTemp()
    {
        $id = I("post.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要删除的信息");
        }
        $RequestTempModel = M("RequestTemp");
        $admin_id = UID;
        $temp_type = 6;
        $where = " id={$id} and temp_type={$temp_type} and status=0 and admin_id={$admin_id} ";
        $ok = $RequestTempModel->where($where)->limit(1)->delete();
        if ($ok > 0) {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, "操作失败");
        }
    }

    /****************
     * 获取单个临时申请信息接口
     * 请求方式：GET
     * 请求参数：id 临时申请ID  必须
     * 日期：2017-11-23
     */
    public function getSingleRequestTempInfo()
    {
        $id = I("get.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要查看的临时申请");
        }
        $admin_id = UID;
        $temp_type = 6;
        $RequestTempModel = M("RequestTemp");
        $sql = "select A.id,A.admin_id,A.temp_type,A.goods_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.remark, ";
        $sql .= "A.status,A.g_num,A.g_price,G.title as goods_name,GC.title as cate_name ";
        $sql .= "from hii_request_temp A ";
        $sql .= "left join hii_goods G on G.id=A.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where A.id={$id} and A.temp_type={$temp_type} and A.status=0 and A.admin_id={$admin_id}  order by A.id desc limit 1 ";

        $datas = $RequestTempModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "该信息不存在");
        }
        $this->response(self::CODE_OK, $datas[0]);
    }

    /*********************
     * 再次申请接口
     * 请求方式：POST
     * 请求参数：s_t_s_id 门店调拨ID 必须
     * 日期：2017-11-23
     */
    public function requestAgain()
    {
        $s_t_s_id = I("post.s_t_s_id");
        $admin_id = UID;
        if (is_null($s_t_s_id) || empty($s_t_s_id)) {
            $this->response(0, "请选择要再次提交的申请");
        }
        $StoreAssignmentApplicationRepository = D("StoreAssignmentApplication");
        $res = $StoreAssignmentApplicationRepository->requestAgain($admin_id, $s_t_s_id);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /********************
     * 导出调拨申请列表Excel接口
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     * 日期：2017-12-04
     */
    public function exportStoreAssignmentApplicationListExcel()
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreToStoreModel = M("StoreToStore");
        $sql = "select A.s_t_s_id,A.s_t_s_sn,A.s_t_s_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id, ";
        $sql .= "M1.nickname as admin_nickname,A.store_id1,A.store_id2,S1.title as store_name1,S2.title as store_name2, ";
        $sql .= "A.remark,A.g_type,A.g_nums,sum(A1.is_pass) as pass_num, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_to_store A ";
        $sql .= "left join hii_store_to_store_detail A1 on A1.s_t_s_id=A.s_t_s_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$this->_store_id} and GS.goods_id=A1.goods_id ";
        $sql .= "where A.store_id1 = {$this->_store_id} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by A.s_t_s_id,A.s_t_s_sn,A.s_t_s_status,A.ctime,M1.nickname,A.store_id1,A.store_id2,S1.title,S2.title,A.remark,A.g_type,A.g_nums ";
        $sql .= "order by A.s_t_s_id desc ";
        //\Think\Log::record($sql);
        $data = $StoreToStoreModel->query($sql);

        foreach ($data as $key => $val) {
            //状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
            switch ($val["s_t_s_status"]) {
                case 0: {
                    $data[$key]["s_t_s_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_t_s_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_t_s_status_name"] = "部分通过";
                };
                    break;
                case 3: {
                    $data[$key]["s_t_s_status_name"] = "全部通过";
                };
                    break;
                case 4: {
                    $data[$key]["s_t_s_status_name"] = "全部拒绝";
                };
                    break;
                case 5: {
                    $data[$key]["s_t_s_status_name"] = "已作废";
                };
                    break;
                default: {
                    $data[$key]["s_t_s_status_name"] = "新增";
                };
                    break;
            }
        }

        ob_clean;
        $title = $s_date . ">>>" . $e_date . '调拨单';
        $fname = './Public/Excel/StoreToStore_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreAssignmentApplicationModel();
        $printfile = $printmodel->createStoreAssignmentApplicationListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**********************
     * 查看接口
     * 请求参数：s_t_s_id  调拨申请单ID  必须
     * 日期：2017-12-04
     */

    public function getSingleStoreAssignmentApplicationDetailInfo()
    {
        $result = $this->getSingleStoreAssignmentApplicationWithList();
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, $result["result"]);
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /************************
     * 根据发货门店获取发货门店对应商品的库存
     * 请求方式：GET
     * 请求参数：store_id  发货门店 ID
     * 注意：
     * 日期：2017-12-22
     */
    public function getStoreStockNumByRequestTempData()
    {
        $store_id = I("get.store_id");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "请选择发货门店");
        }
        $admin_id = UID;
        $temp_type = 6;
        $status = 0;
        $current_store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,G.title as goods_name,RT.remark, ";
        $sql .= "G.bar_code,GC.title as cate_name,G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price,ifnull(GS.num,0) as stock_num,ifnull(GS2.num,0) as current_stock_num,RT.g_num ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=RT.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_goods_store GS2 on GS2.goods_id=RT.goods_id and GS2.store_id={$current_store_id} ";
        $sql .= "where RT.admin_id={$admin_id} and RT.store_id={$current_store_id} and RT.temp_type={$temp_type} and RT.status={$status} order by RT.id desc ";
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

        $this->response(self::CODE_OK, $list);
    }

    /*********************
     * 导出单个门店调拨单Excel
     * 请求参数：s_t_s_id  调拨申请单ID  必须
     * 日期：2017-12-04
     */
    public function exportSingleStoreAssignmentApplicationExcel()
    {
        $result = $this->getSingleStoreAssignmentApplicationWithList();
        if ($result["status"] == "200") {
            ob_clean;
            $result = $result["result"];
            $title = '调拨单查看';
            $fname = './Public/Excel/StoreToStoreView_' . $result["maindata"]["s_t_s_sn"] . '_' . time() . '.xlsx';
            $printmodel = new \Addons\Report\Model\StoreAssignmentApplicationModel();
            $printfile = $printmodel->createSingleStoreAssignmentApplicationDetailExcel($result, $title, $fname);
            $this->response(self::CODE_OK, $printfile);
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /************************************
     * 通过bar_code 或者 goods_id获取商品信息
     * 请求方式：GET
     * 请求参数：bar_code   商品条码   否
     *           goods_id   商品ID     否
     * 日期：2018-01-20
     */
    public function getgoods()
    {
        $bar_code = I("get.bar_code");
        $goods_id = I("get.goods_id");
        $temp_type = 6;
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
                }
                $this->response(self::CODE_OK, $outdata);
            }
        }
    }

    private function getSingleStoreAssignmentApplicationWithList()
    {
        $s_t_s_id = I("get.s_t_s_id");
        if (is_null($s_t_s_id) || empty($s_t_s_id)) {
            //$this->response(0, "请选择要查看的信息");
            return array("status" => "0", "msg" => "请选择要查看的信息");
        }
        $StoreToStoreModel = M("StoreToStore");
        $StoreToStoreDetailModel = M("StoreToStoreDetail");
        $sql = "select A.s_t_s_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.g_type,A.g_nums, ";
        $sql .= "M.nickname as admin_nickname,S1.title as store_name1,S2.title as store_name2,A.remark,A.s_t_s_type,A.s_t_s_status ";
        $sql .= "from hii_store_to_store A ";
        $sql .= "left join hii_store_to_store_detail A1 on A1.s_t_s_id=A.s_t_s_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_member M on M.uid=A.admin_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "where A.s_t_s_id={$s_t_s_id} and A.store_id1={$this->_store_id} ";
        $sql .= "group by A.s_t_s_sn,A.ctime,A.g_type,A.g_nums,M.nickname,S1.title,S2.title,A.remark,A.s_t_s_type,A.s_t_s_status ";
        //echo $sql;exit;
        $datas = $StoreToStoreModel->query($sql);
        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            //$this->response(0, "信息不存在");
            return array("status" => "0", "msg" => "信息不存在");
        }

        $maindata = $datas[0];

        switch ($maindata["s_t_s_type"]) {
            case 0: {
                $maindata["s_t_s_type_name"] = "门店调拨";
            };
                break;
            default: {
                $maindata["s_t_s_type_name"] = "其他";
            };
                break;
        }

        switch ($maindata["s_t_s_status"]) {
            // `s_t_s_status` int(1) DEFAULT '0' COMMENT '状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废',
            case 0: {
                $maindata["s_t_s_status_name"] = "新增";
            };
                break;
            case 1: {
                $maindata["s_t_s_status_name"] = "已审核";
            };
                break;
            case 2: {
                $maindata["s_t_s_status_name"] = "部分通过";
            };
                break;
            case 3: {
                $maindata["s_t_s_status_name"] = "全部通过";
            };
                break;
            case 4: {
                $maindata["s_t_s_status_name"] = "全部拒绝";
            };
                break;
            case 5: {
                $maindata["s_t_s_status_name"] = "已作废";
            };
                break;
        }

        //sys_price 系统售价 shequ_price 区域价  store_price 门店价
        $sql = "select A1.goods_id,A1.g_num,G.title as goods_name,G.bar_code,GC.title as cate_name,G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price, ";
        $sql .= "ifnull(GS.num,0) as stock_num,A1.g_num,A1.pass_num,A1.is_pass,A1.remark ";
        $sql .= "from hii_store_to_store_detail A1 ";
        $sql .= "left join hii_store_to_store A on A.s_t_s_id=A1.s_t_s_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id=A.store_id1 and GS.goods_id=A1.goods_id ";
        $sql .= "where A1.s_t_s_id={$s_t_s_id} order by A1.s_t_s_d_id desc ";

        $list = $StoreToStoreDetailModel->query($sql);

        $g_amounts = 0;
        foreach ($list as $key => $val) {
            switch ($val["is_pass"]) {
                case 0 : {
                    $list[$key]["is_pass_name"] = "新增";
                };
                    break;
                case 1 : {
                    $list[$key]["is_pass_name"] = "拒绝";
                };
                    break;
                case 2 : {
                    if ($list[$key]["g_num"] > $list[$key]["pass_num"]) {
                        $list[$key]["is_pass_name"] = "部分通过";
                    } else {
                        $list[$key]["is_pass_name"] = "通过";
                    }
                };
                    break;
            }
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

        $result = array();
        $result["maindata"] = $maindata;
        $result["list"] = $list;

        return array("status" => "200", "msg" => "", "result" => $result);
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
        \Think\Log::record("s_date " . $s_date);
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