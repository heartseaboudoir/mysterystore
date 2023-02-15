<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-06
 * Time: 10:55
 * 门店入库验货相关接口
 */

namespace Internal\Controller;

use Think\Controller;

class StoreInController extends ApiController
{

    public function _initialize()
    {
        // 是否验证token
        $action = ACTION_NAME;//当前请求action名称
        //$actions = array("get_store_in_list", "get_store_in_detail", "check");
        $actions = array("test");
        $check = false; // true为指定的验证，false为指定的不验证
        if (in_array($action, $actions)) {
            $this->ctoken = $check;
        } else {
            $this->ctoken = !$check;
        }
        parent::_initialize();
        header("Content-Type: text/html;charset=utf-8");
    }

    public function test()
    {
        //echo strtotime(date('Y-m-d H:i:s',strtotime('+1 month')));
        echo date("Y-m-d H:i", 1523519428);
        exit;
    }

    /*****************
     * 获取门店入库验货
     * 请求方式：POST
     * 请求参数：store_id  门店ID  必须
     *       s_in_status 是否审核  0 未审核 1 审核 默认未审核
     * 日期：2018-03-06
     */
    public function get_store_in_list()
    {
        $store_id = I("post.store_id");
        $s_in_status = I("post.s_in_status");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($s_in_status) || empty($s_in_status)) {
            $s_in_status = 0;
        }
        $list = $this->getStoreInList(true, $store_id, $s_in_status);
        $this->response(self::RESPONSE_SUCCES, $list);
    }

    /*****************
     * 获取门店验货单信息
     * 请求方式：POST
     * 请求参数：store_id  门店ID          必须
     *           s_in_id   入库验货单ID    必须
     *           audit_mark 不填是所有  0未操作  1已操作 
     * 日期：2018-03-06
     */
    public function get_store_in_detail()
    {
        $store_id = I("post.store_id");
        $s_in_id = I("post.s_in_id");
        $audit_mark = I("post.audit_mark",'');
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($s_in_id) || empty($s_in_id)) {
            $this->response(0, "缺少验货单ID");
        }
        $result = $this->getStoreInDetail($store_id, $s_in_id,$audit_mark);
        $this->response(self::RESPONSE_SUCCES, $result);
    }

    /*************************
     * 更新门店验货单信息
     * 请求方式：POST
     * 请求参数：store_id       门店ID              必须
     *           s_in_id        入库验收单ID        必须
     *           remark         主单备注            非必须
     *           info_json_str  修改信息json字符串  必须 格式：[{"s_in_d_id":"1","in_num":"20","out_num":"2","endtime":"","remark":""},{"s_in_d_id":"2","in_num":"15","out_num":"0","endtime":"","remark":""}]
     */
    public function update_store_in()
    {
        $store_id = I("post.store_id");
        $s_in_id = I("post.s_in_id");
        $remark = I("post.remark");
        $info_json_str = I("post.info_json_str");
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($s_in_id) || empty($s_in_id)) {
            $this->response(0, "缺少验货单ID");
        }
        if (is_null($detail_array) || count($detail_array) == 0) {
            $this->response(0, "缺少子表信息");
        }
        $StoreInReposity = D("StoreIn");
        $uid = $this->uid;
        $result = $StoreInReposity->updateStoreInInfo($uid, $store_id, $s_in_id, $remark, $detail_array);
        $this->response($result["status"], $result["msg"]);
    }

    /***********************
     * 门店入库验收单审核接口
     * 请求方式：POST
     * 请求参数：store_id     门店ID          必须
     *           s_in_id      入库验收单ID    必须
     */
    public function check()
    {
        $store_id = I("post.store_id");
        $s_in_id = I("post.s_in_id");
        $remark = I("post.remark");
        $uid = $this->uid;
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "缺少门店ID");
        }
        if (is_null($s_in_id) || empty($s_in_id)) {
            $this->response(0, "缺少验货单ID");
        }
        if (is_null($uid) || empty($uid)) {
            $this->response(0, "请登陆");
        }
        $StoreInReposity = D("StoreIn");
        $result = $StoreInReposity->check($uid, $store_id, $s_in_id);
        $this->response($result["status"], $result["msg"]);
    }


    /******************************************************* 私有方法 ***********************************************************************************************/

    private function getStoreInList($usePager, $store_id, $s_in_status)
    {
        $data = null;

        $StoreInModel = M("StoreIn");

        $sql = "select A.s_in_id,A.s_in_sn,A.s_in_status,A.s_in_type,A.w_out_s_id,A.s_out_s_id,A.o_out_id, ";
        $sql .= "FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime, ";
        $sql .= "A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,A.supply_id,  ";
        $sql .= "A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname, ";
        $sql .= "W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2,SU.s_name as supply_name, ";
        $sql .= "sum(A1.g_num*G.sell_price) as g_amounts,SUM(A1.in_num) as in_nums,SUM(A1.out_num) as out_nums,'' as other_name ";
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
        $sql .= "where A.store_id2={$store_id} ";
        if ($s_in_status == 0) {
            $sql .= " and A.s_in_status=0 ";
        } else {
            $sql .= " and A.s_in_status<>0 ";
        }
        $sql .= "group by s_in_id,s_in_sn,s_in_status,s_in_type,w_out_s_id,s_out_s_id,o_out_id,ctime,admin_id,etime,eadmin_id, ";
        $sql .= "ptime,padmin_id,warehouse_id,store_id1,store_id2,remark,g_type,g_nums,admin_nickname,eadmin_nickname,padmin_nickname,";
        $sql .= "warehouse_name,store_name1,store_name2 ";
        $sql .= "order by A.s_in_id desc";

        $data = $StoreInModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount, null, $this->getPageIndex());// 实例化分页类 传入总记录数和每页显示的记录数
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
                    $data[$key]["s_in_status_name"] = "已退货报损";
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

        if ($usePager) {
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            //$result["pager"] = $show;
        }
        $result["data"] = $this->isArrayNull($data) == null ? array() : $data;
        return $result;
    }

    private function getStoreInDetail($store_id, $s_in_id,$audit_mark='')
    {
        $StoreInModel = M("StoreIn");
        $default_expired_days = 30;//默认过期天数
        $GoodsModel = M("Goods");
       
        $sql = "select A.s_in_id,A.s_in_sn,A.s_in_status,A.s_in_type,A.w_out_s_id,A.s_out_s_id,A.o_out_id, ";
        $sql .= "FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime, ";
        $sql .= "A.eadmin_id,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.padmin_id,A.warehouse_id,A.store_id1,A.store_id2,  ";
        $sql .= "A.remark,A.g_type,A.g_nums,M1.nickname as admin_nickname,M2.nickname as eadmin_nickname,M3.nickname as padmin_nickname, ";
        $sql .= "W.w_name as warehouse_name,S1.title as store_name1,S2.title as store_name2,'' as s_in_status_name,'' as s_in_type_name,SU.s_name as supply_name,'' as other_name, ";
        $sql .= "sum(A1.g_num*G.sell_price) as g_amounts ";
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
        $sql .= "where A.store_id2={$store_id} and A.s_in_id={$s_in_id}  order by s_in_id desc limit 1 ";
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
            };
                break;
            case 1: {
                $data["s_in_type_name"] = "门店调拨";
            };
                break;
            case 2: {
                $data["s_in_type_name"] = "盘盈入库";
            };
                break;
            case 3: {
                $data["s_in_type_name"] = "其他";
            };
                break;
            case 4: {
                $data["s_in_type_name"] = "采购";
            };
                break;
            case 5: {
                $data["s_in_type_name"] = "寄售";
            };
                break;
        }

        $result["maindata"] = $data;
        
        if($audit_mark !== '') {
            $audit_mark = "and A1.audit_mark = {$audit_mark} ";
        }
        
        $sql = "select A1.s_in_d_id,A1.goods_id,G.title as goods_name,GC.title as cate_name,G.bar_code,G.sell_price,A1.g_num,A1.in_num,A1.out_num,ifnull(A1.remark,'') as remark,ifnull(G.expired_days,'') expired_days, ";
        $sql .= "A1.endtime,FROM_UNIXTIME(A1.endtime,'%Y-%m-%d') as enddate,ifnull(AV.value_id,'')value_id,ifnull(AV.value_name,'')value_name,A1.audit_mark ";
        $sql .= "from hii_store_in_detail A1 ";
        $sql .= "left join hii_store_in A on A.s_in_id=A1.s_in_id ";
        $sql .= "left join hii_goods G on G.id=A1.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
        $sql .= "where A1.s_in_id={$s_in_id} {$audit_mark} ";
        $sql .= "order by A1.goods_id asc ";

        //echo $sql;exit;

        $list = $StoreInModel->query($sql);

        //获取过期日期
        $goods_id_array = array_column($list, "goods_id");
        $goods_id_where = implode(",", $goods_id_array);
        $goods_expired_days_array = $GoodsModel->where(" id in ({$goods_id_where}) ")->select();

        foreach ($list as $key => $val) {
            if (is_null($val["endtime"]) || empty($val["endtime"]) || $val["endtime"] == 0) {
                $endtime = strtotime("+{$default_expired_days} day");
                $endtime = date('Y-m-d', $endtime);
                foreach ($goods_expired_days_array as $gk => $gv) {
                    if ($gv["id"] == $val["goods_id"]) {
                        if (!is_null($gv["expired_days"]) && !empty($gv["expired_days"])) {
                            $endtime = strtotime("+{$gv["expired_days"]} day");
                            $endtime = date('Y-m-d', $endtime);
                        }
                        break;
                    }
                }
            } else {
                $endtime = date('Y-m-d', $val["endtime"]);
            }
            $list[$key]["endtime"] = $endtime;
        }

        $result["list"] = $list;
        return $result;
    }


    /***************
     * 获取当前页
     ***************/
    private function getPageIndex()
    {
        $p = I("post.p");
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
        $pcount = I("post.pageSize");
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