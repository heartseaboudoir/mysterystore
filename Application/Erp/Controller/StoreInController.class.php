<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-14
 * Time: 17:30
 * 门店来货相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreInController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /*********************
     * 入库验货单接口
     * 请求方式：GET
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     */
    public function index()
    {
        $result = $this->getListInfo(true);
        $this->response(self::CODE_OK, $result);
    }

    /******************
     * 获取单条门店验货单详细信息
     * 请求方式：GET
     * 请求参数：s_in_id  门店验货单ID  必须
     */
    public function getSingleStoreInInfo()
    {
        $result = $this->getSingleStoreInDetailInfo();
        $this->response(self::CODE_OK, $result);
    }


    /********************
     * 审核入库
     * 请求方式：POST
     * 请求参数：s_in_id  门店验货单ID  必须
     *           confirm  当confirm=yes 的时候审核
     * 注意：当明细单存在退货数量时候自动生成门店报损单,成功验收部分生成收货单【hii_store_in_stock】，同时更新主表【hii_store_in】的状态
     */
    public function checkForInStock()
    {
        $s_in_id = I("post.s_in_id");
        $store_id = $this->_store_id;
        $admin_id = UID;
        if (is_null($s_in_id) || empty($s_in_id)) {
            $this->response(0, "请选择需要审核的单");
        }

        $StoreInReposity = D("StoreIn");
        $res = $StoreInReposity->pass($admin_id, $store_id, $s_in_id);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "审核成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /**********************
     * 全部退货
     * 请求方式：POST
     * 请求参数：s_in_id 门店验货单ID  必须
     * 注意：全部退货生成门店报损单，同时更新主表【hii_store_in】的状态
     */
    public function allReject()
    {
        $s_in_id = I("post.s_in_id");
        $store_id = $this->_store_id;
        if (is_null($s_in_id) || empty($s_in_id)) {
            $this->response(0, "请选择需要审核的单");
        }
        $StoreIn = M("StoreIn");
        $StoreInDatas = $StoreIn->where(" s_in_id={$s_in_id} and store_id2={$store_id} and s_in_status=0 ")->limit(1)->select();
        if (is_null($StoreInDatas) || empty($StoreInDatas) || count($StoreInDatas) == 0) {
            $this->response(0, "信息不存在或无权操作该信息");
        }
        $StoreInReposity = D("StoreIn");
        $admin_id = UID;
        $res = $StoreInReposity->allReject($StoreInDatas[0], $admin_id);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "拒绝成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /**************
     * 修改入库验收单
     * 请求方式：POST
     * 请求参数：info_json_str 修改信息json字符串 必须 [{"s_in_d_id":"1","in_num":"20","out_num":"2"},{"s_in_d_id":"2","in_num":"15","out_num":"0"}]
     *           remark  备注  非必需
     */
    public function updateStoreInDetailInfo()
    {
        $remark = I("post.remark");
        $info_array = I("post.info_json_str");
        $store_id = $this->_store_id;
        if (is_null($info_array) || empty($info_array)) {
            $this->response(0, "请提交要修改的信息");
        }
        $StoreInModel = M("StoreIn");
        $sql = "select A.s_in_id from hii_store_in A ";
        $sql .= "left join hii_store_in_detail A1 on A1.s_in_id=A.s_in_id ";
        $sql .= "where A.s_in_status=0 and A1.s_in_d_id={$info_array[0]["s_in_d_id"]} and A.store_id2={$store_id} limit 1 ";

        $datas = $StoreInModel->query($sql);

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "提交数据有误");
        }

        $uid = UID;
        $s_in_id = $datas[0]["s_in_id"];
        $StoreInReposity = D("StoreIn");
        $res = $StoreInReposity->updateStoreInDetailInfoTrans($uid, $info_array, $s_in_id, $remark);
        if ($res["status"] == "200") {
            $this->response(self::CODE_OK, "更新成功");
        } else {
            $this->response(0, $res["msg"]);
        }
    }

    /***********************
     * 导出门店入库验货单列表Excel
     * 请求方式：GET
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     * 日期：2017-12-11
     */
    public function exportListExcel()
    {
        $result = $this->getListInfo(false);
        $s_date = $result["s_date"];
        $e_date = $result["e_date"];
        $data = $result["data"];
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店入库验货单';
        $fname = './Public/Excel/StoreIn_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInModel();
        $printfile = $printmodel->createStoreInListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**********************
     * 导出单个门店入库验货单Excel
     * 请求方式：GET
     * 请求参数：s_in_id  入库验货单ID  必须
     * 日期：2017-12-11
     */
    public function exportViewExcel()
    {
        $result = $this->getSingleStoreInDetailInfo();
        ob_clean;
        $title = '入库验货单查看';
        $fname = './Public/Excel/StoreIn_' . $result["maindata"]["s_in_sn"] . '_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInModel();
        $printfile = $printmodel->createStoreInViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**************************
     * 获取列表信息
     * @param $usePager 是否启用分页
     */
    private function getListInfo($usePager = false)
    {
        $store_id = $this->_store_id;
        $data = null;
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $StoreInModel = M("StoreIn");

        $sql = "select A.s_in_id,A.s_in_sn,A.s_in_status,A.s_in_type,A.w_out_s_id,A.s_out_s_id,A.o_out_id, ";
        $sql .= "FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime, ";
        $sql .= "A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,  ";
        $sql .= "A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname, ";
        $sql .= "W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2,SU.s_name as supply_name, ";
        $sql .= "SUM(A1.in_num) as in_nums,SUM(A1.out_num) as out_nums, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_in A ";
        $sql .= "left join hii_store_in_detail A1 on A1.s_in_id=A.s_in_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_supply SU on SU.s_id=A.supply_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "where A.store_id2={$store_id}  and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'   ";
        $sql .= "group by s_in_id,s_in_sn,s_in_status,s_in_type,w_out_s_id,s_out_s_id,o_out_id,ctime,admin_id,etime,eadmin_id, ";
        $sql .= "ptime,padmin_id,warehouse_id,store_id1,store_id2,remark,g_type,g_nums,admin_nickname,eadmin_nickname,padmin_nickname,";
        $sql .= "warehouse_name,store_name1,store_name2 ";
        $sql .= "order by A.s_in_id desc";

        $data = $StoreInModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            switch ($val["s_in_status"]) {
                case 0: {
                    $data[$key]["s_in_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$data[$key]["s_in_status_name"] = "已审核转收货";
                    $data[$key]["s_in_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_in_status_name"] = "已退货";
                };
                    break;
                case 3: {
                    $data[$key]["s_in_status_name"] = "部分退货、部分收货";
                };
                    break;

            }
            switch ($val["s_in_type"]) {
                case 0: {
                    $data[$key]["s_in_type_name"] = "仓库出库";
                };
                    break;
                case 1: {
                    $data[$key]["s_in_type_name"] = "门店调拨";
                };
                    break;
                case 2: {
                    $data[$key]["s_in_type_name"] = "盘盈入库";
                };
                    break;
                case 3: {
                    $data[$key]["s_in_type_name"] = "其他";
                };
                    break;
                case 4: {
                    $data[$key]["s_in_type_name"] = "采购";
                };
                    break;
                case 5: {
                    $data[$key]["s_in_type_name"] = "寄售";
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

    private function getSingleStoreInDetailInfo()
    {
        $store_id = $this->_store_id;
        $s_in_id = I("get.s_in_id");
        $default_expired_days = 30;//默认过期天数

        if (is_null($s_in_id) || empty($s_in_id)) {
            $this->response(0, "请选择要查看的验货单");
        }
        $StoreInModel = M("StoreIn");
        $GoodsModel = M("Goods");

        $sql = "select A.s_in_id,A.s_in_sn,A.s_in_status,A.s_in_type,A.w_out_s_id,A.s_out_s_id,A.o_out_id,A.p_id, ";
        $sql .= "FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime, ";
        $sql .= "A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,  ";
        $sql .= "A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname, ";
        $sql .= "W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2,'' as s_in_status_name,'' as s_in_type_name,SU.s_name as supply_name ";
        $sql .= "from hii_store_in A ";
        $sql .= "left join hii_store_in_detail A1 on A1.s_in_id=A.s_in_id ";
        $sql .= "left join hii_member M1 on M1.uid=A.admin_id ";
        $sql .= "left join hii_member M2 on M2.uid=A.eadmin_id ";
        $sql .= "left join hii_member M3 on M3.uid=A.padmin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=A.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=A.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=A.store_id2 ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_supply SU on SU.s_id=A.supply_id ";
        $sql .= "where A.store_id2={$store_id} and A.s_in_id={$s_in_id} order by s_in_id desc limit 1 ";
        //echo $sql;exit;
        $datas = $StoreInModel->query($sql);

        if (is_null($datas) || empty($datas) || count($datas) == 0) {
            $this->response(0, "该信息不存在");
        }

        $data = $datas[0];

        switch ($data["s_in_status"]) {
            case 0: {
                $data["s_in_status_name"] = "新增";
            };
                break;
            case 1: {
                //$data["s_in_status_name"] = "已审核转收货";
                $data["s_in_status_name"] = "已审核";
            };
                break;
            case 2: {
                $data["s_in_status_name"] = "已退货报损";
            };
                break;
            case 3: {
                $data["s_in_status_name"] = "部分退货报损、部分收货";
            };
                break;

        }
        switch ($data["s_in_type"]) {
            case 0: {
                $data["s_in_type_name"] = "仓库出库";
                $data['s_in_type_numbers'] = M("WarehouseOutStock")->where(array("w_out_s_id"=>$data['w_out_s_id']))->getField('w_out_s_sn');
            };
                break;
            case 1: {
                $data["s_in_type_name"] = "门店调拨";
                $data['s_in_type_numbers'] = M("StoreOutStock")->where(array("s_out_s_id"=>$data['s_out_s_id']))->getField("s_out_s_sn");
            };
                break;
            case 2: {
                $data["s_in_type_name"] = "盘盈入库";
                $data['s_in_type_numbers'] = '';
            };
                break;
            case 3: {
                $data["s_in_type_name"] = "其他";
                $data['s_in_type_numbers'] = '';
            };
                break;
            case 4: {
                $data["s_in_type_name"] = "采购";
                $data['s_in_type_numbers'] = M("Purchase")->where(array("p_id"=>$data['p_id']))->getField("p_sn");
            };
                break;
            case 5: {
                $data["s_in_type_name"] = "寄售";
                $data['s_in_type_numbers'] = '';
            };
                break;
        }

        //sys_price 系统售价  shequ_price 区域价  store_price 门店价
        $sql = "select A1.s_in_d_id,A1.goods_id,G.title as goods_name,GC.title as cate_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,A1.in_num,A1.out_num,A1.remark, ";
        $sql .= "FROM_UNIXTIME(A1.endtime,'%Y-%m-%d') as endtime,FROM_UNIXTIME(A1.endtime,'%Y-%m-%d') as enddate,A1.endtime as endtimestamp, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_in_detail A1 ";
        $sql .= "left join hii_store_in A on A.s_in_id=A1.s_in_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
        $sql .= "where A1.s_in_id={$s_in_id} ";
        $sql .= "order by A1.goods_id asc ";

        //echo $sql;exit;

        $list = $StoreInModel->query($sql);

        if ($data["s_in_type"] == 4) {
            //获取过期日期
            $expired_days_array = array();//商品过期时间数组
            $goods_id_array = array_column($list, "goods_id");
            $goods_id_where = implode(",", $goods_id_array);
            $goods_expired_days_array = $GoodsModel->field('expired_days,id')->where(" id in ({$goods_id_where}) ")->select();
            foreach ($goods_expired_days_array as $k => $v) {
            		if (!is_null($v["expired_days"]) && !empty($v["expired_days"]) && $v["expired_days"] > 0) {
            			$expired_days_array[$v['id']] = $v["expired_days"];
            		}
            }
            foreach ($list as $key => $val) {
                if(array_key_exists($val['goods_id'],$expired_days_array)){
                	$list[$key]["expired_days"] = $expired_days_array[$val['goods_id']];//保质期
                	$list[$key]["prod_date"] = date("Y-m-d", strtotime("-{$expired_days_array[$val['goods_id']]} day", $val["endtimestamp"]));//生产日期
                }else{
                	$list[$key]["prod_date"] = date("Y-m-d", strtotime("-30 day", $val["endtimestamp"]));//生产日期
                	$list[$key]["expired_days"] = 30;//保质期
                }
             
            }
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