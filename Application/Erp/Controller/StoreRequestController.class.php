<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-06
 * Time: 10:23
 * 门店发货申请接口
 */

namespace Erp\Controller;

use Erp\Model\MessageWarnModel;
use Think\Controller;

/*****************
 * 门店发货接口
 * CODE_OK 200 请求成功
 * CODE_FAIL 0 请求失败
 * Class StoreRequestController
 * @package Erp\Controller
 */
class StoreRequestController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /*************
     * 获取门店发货申请临时表信息接口
     * 请求方式：GET
     * 请求参数：无
     */
    public function temp()
    {
        $list = $this->getStoreRequestTempList();
        $this->response(self::CODE_OK, $list);
    }

    /**********
     * 获取仓库列表接口
     * 请求方式：GET
     * 请求参数：无
     **********/
    public function warehouselist()
    {
        $store_id = $this->_store_id;
        $where = " shequ_id=(select shequ_id from hii_store where id={$store_id} limit 1) ";
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        if (count($can_warehouse_id_array) > 0) {
            $where .= " and w_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        $warehouses = M("Warehouse")->field(" w_id,w_name ")->where($where)->order(" `w_id` asc ")->select();
        $this->response(self::CODE_OK, $warehouses);
    }

    /****************
     * 加入临时申请表接口
     * 请求方式：POST
     * 需要提交参数:  goods_id   商品id     必须
     *                g_num      申请数量   必须
     ***************/
    public function addRequestTemp()
    {
        $admin_id = UID;
        $temp_type = 2;//发货申请
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
        $value_id = I("post.value_id",0);
        if($value_id == 0){
        	$this->response(0, "请选择属性");
        }
        $remark = I("post.remark");
        $temp_id = I('post.temp_id','');
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
                $this->response(0, $RequestTempModel->getError());
            } else {
                //判断是否有重复商品属性如果有删除一个
                $RequestTempModel->where(array('id'=>array('NEQ',$temp_id),'admin_id'=>$admin_id,'store_id'=>$this->_store_id,'status'=>$status,'goods_id'=>$goods_id,'temp_type'=>$temp_type,'value_id'=>$value_id))->delete();

                $list = $this->getStoreRequestTempList();
                $this->response(self::CODE_OK, $list);
            }

        }else{
            $sql = " select * from hii_request_temp ";
            $sql .= "where admin_id={$admin_id} and `store_id`={$store_id} and `status`={$status} and `goods_id`={$goods_id} and temp_type={$temp_type} and value_id={$value_id} order by id desc limit 1 ";
            $data = $RequestTempModel->query($sql);
            if ($this->isArrayNull($data) != null) {
                //更新
                $saveData["remark"] = $remark;
                $saveData["g_num"] = $g_num;
                $saveData["g_price"] = $g_price;
                $result = $RequestTempModel->where(" id={$data[0]["id"]} ")->save($saveData);
                if ($result === false) {
                    $this->response(0, $RequestTempModel->getError());
                } else {
                    $list = $this->getStoreRequestTempList();
                    $this->response(self::CODE_OK, $list);
                }
            } else {
                //新增
                $data = array();
                $data["admin_id"] = $admin_id;
                $data["store_id"] = $this->_store_id;
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
                $data["value_id"] = $value_id;
                $result = $RequestTempModel->add($data);
                //$this->response(0, $RequestTempModel->_sql());
                if ($result === false) {
                    $this->response(0, $RequestTempModel->getError());
                } else {
                    $list = $this->getStoreRequestTempList();
                    $this->response(self::CODE_OK, $list);
                }
            }
        }


        $this->response(self::CODE_OK, $admin_id);
    }

    /**************
     * 发货申请单列表
     * 请求方式：GET
     * 需要提交参数：s_date  开始日期  非必填
     *               e_date  结束日期  非必填
     *               p    当前页    非必填   默认1
     **************/
    public function index()
    {
        $store_id = $this->_store_id;
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "请选择门店");
        }
        //时间范围默认30天
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $Model = M('RequestTemp');

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " A.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . "  A.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ( {$shequ_where} ) ";
        }

        $sql = "select A.s_r_id,A.s_r_sn,A.s_r_type,A.s_r_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,";
        $sql .= "A.admin_id,B.nickname,A.warehouse_id,C.w_name as warehouse_name,A.store_id,S.title as store_name, ";
        $sql .= "A.remark,A.g_type,A.g_nums,SUM(A1.pass_num) as sf_nums, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts, ";
        $sql .= "sum(A1.is_pass) as pass_num,ifnull(WS.num,0) as stock_num ";
        $sql .= "from hii_store_request A ";
        $sql .= "left join hii_store_request_detail A1 on A.s_r_id=A1.s_r_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_member B on A.admin_id=B.uid ";
        $sql .= "left join hii_warehouse C on A.warehouse_id=C.w_id ";
        $sql .= "left join hii_goods G on A1.goods_id=G.id ";
        $sql .= "left join hii_store S on S.id = A.store_id ";
        $sql .= "left join hii_warehouse_stock WS on WS.w_id=A.warehouse_id and WS.value_id=A1.value_id ";
        $sql .= "where A.store_id = {$store_id} {$shequ_where} and A.s_r_type=0 and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by A.s_r_id,A.s_r_sn,A.s_r_type,A.s_r_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.remark,A.g_type,A.g_nums ";
        $sql .= "order by s_r_id desc ";
        //echo $sql;
        //exit;
        $data = $Model->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        foreach ($datamain as $key => $val) {
            //0.新增,1.出库中,2.部分发货,3.全部发货,4.全部拒绝,5.已作废,6.仓库转采购直接发门店,7.仓库转采购发仓库,8.同时都有
            switch ($val["s_r_status"]) {
                case 0: {
                    $datamain[$key]["s_r_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $datamain[$key]["s_r_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $datamain[$key]["s_r_status_name"] = "部分通过";
                };
                    break;
                case 3: {
                    $datamain[$key]["s_r_status_name"] = "全部通过";
                };
                    break;
                case 4: {
                    $datamain[$key]["s_r_status_name"] = "全部拒绝";
                };
                    break;
                case 5: {
                    $datamain[$key]["s_r_status_name"] = "已作废";
                };
                    break;
                case 6: {
                    $datamain[$key]["s_r_status_name"] = "转门店采购";
                };
                    break;
                case 7: {
                    $datamain[$key]["s_r_status_name"] = "仓库备货中";
                };
                    break;
                case 8: {
                    $datamain[$key]["s_r_status_name"] = "部分转门店采购，部分仓库备货中";
                };
                    break;
            }
            if ($val["pass_num"] == $val["g_type"]) {
                $datamain[$key]["s_r_status_name"] = "全部拒绝";
            }
            if ($val["pass_num"] > 0 && $val["sf_nums"] > 0 && $val["sf_nums"] < $val["g_nums"] && ($val["pass_num"] != $val["g_type"])) {
                $datamain[$key]["s_r_status_name"] = "部分发货";
            }
            if ($val["sf_nums"] == $val["g_nums"]) {
                $datamain[$key]["s_r_status_name"] = "全部发货";
            }
            if ($val["pass_num"] > 0 && ($val["pass"] % 2 == 1)) {
                $datamain[$key]["s_r_status_name"] = "部分拒绝";
            }
        }

        $result["s_date"] = $s_date;
        $result["e_date"] = $e_date;
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $p = I("get.p");
        $result["p"] = is_null($p) || empty($p) ? "1" : I("p");
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($datamain);

        $this->response(self::CODE_OK, $result);
    }

    /******************************************
     * 获取临时申请对应仓库库存接口
     * 请求方式：GET
     * 请求参数：warehouse_id  发货仓库ID  必须
     * 日期：2017-12-22
     */
    public function getWarehouseStockNumByRequestTempData()
    {
        $where = array();
        $where['hii_request_temp.admin_id'] = UID;//当前登录账号的uid
        $where['hii_request_temp.store_id'] = $this->_store_id;
        $where['hii_request_temp.temp_type'] = 2;//门店发货申请
        $where['hii_request_temp.status'] = 0;
        $warehouse_id = I("get.warehouse_id");
        $store_id = $this->_store_id;
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择发货仓库");
        }
        $RequestTempModel = M("RequestTemp");
        $field = "hii_request_temp.id,hii_request_temp.goods_id,FROM_UNIXTIME(hii_request_temp.ctime,'%Y-%m-%d %H:%i:%s') as ctime,hii_request_temp.remark";
        $field .= ",hii_goods.title as goods_name,ifnull(AV.bar_code,hii_goods.bar_code)bar_code,hii_goods_cate.title as cate_name,hii_goods.sell_price as sys_price,hii_goods_store.price as store_price,hii_goods_store.shequ_price as shequ_price";
        $field .= ",ifnull(hii_goods_store.num,0) as current_stock_num,round(ifnull(hii_warehouse_stock.num,0)) as stock_num,hii_request_temp.g_num,AV.value_id,AV.value_name ";
        $list = $RequestTempModel
            ->join('left join hii_goods on hii_request_temp.goods_id=hii_goods.id')
            ->join('left join hii_goods_cate on hii_goods.cate_id=hii_goods_cate.id')
            ->join('left join hii_goods_store on hii_goods_store.store_id=' . $store_id . ' and hii_request_temp.goods_id=hii_goods_store.goods_id ')
            ->join('left join hii_warehouse_stock on hii_warehouse_stock.w_id=' . $warehouse_id . ' and hii_warehouse_stock.value_id=hii_request_temp.value_id ')
            ->join('left join hii_attr_value AV ON AV.value_id= hii_request_temp.value_id')
            ->field($field)
            ->where($where)->order(' hii_request_temp.ctime asc ')->select();
        $sql = $RequestTempModel->_sql();
        foreach ($list as $key => $val) {
            if (!is_null($val["store_price"]) && !empty($val["store_price"]) && $val["store_price"] > 0) {
                $list[$key]["sell_price"] = $val["store_price"];
            } elseif (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $list[$key]["sell_price"] = $val["shequ_price"];
            } else {
                $list[$key]["sell_price"] = $val["sys_price"];
            }
        }
        //echo $sql; exit;
        $this->response(self::CODE_OK, $list);
    }

    /***************
     * 获取门店出货单临时表数据
     * 请求方式：POST
     * 请求参数：无
     */
    private function getStoreRequestTempList()
    {
        $where = array();
        $where['hii_request_temp.admin_id'] = UID;//当前登录账号的uid
        $where['hii_request_temp.temp_type'] = 2;//门店发货申请
        $where['hii_request_temp.status'] = 0;
        $where['hii_request_temp.store_id'] = $this->_store_id;
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        //sys_price 系统售价 shequ_price 区域售价  store_price 门店售价
        $field = "hii_request_temp.id,hii_request_temp.goods_id,FROM_UNIXTIME(hii_request_temp.ctime,'%Y-%m-%d %H:%i:%s') as ctime,hii_request_temp.remark";
        $field .= ",hii_goods.title as goods_name,ifnull(hii_attr_value.bar_code,hii_goods.bar_code)bar_code,hii_goods_cate.title as cate_name, ";
        $field .= "hii_goods.sell_price as sys_price,hii_goods_store.price as store_price,hii_goods_store.shequ_price as shequ_price, ";
        $field .= "'' as stock_num,ifnull(hii_goods_store.num,0) as current_stock_num,hii_request_temp.g_num ,hii_request_temp.value_id,hii_attr_value.value_name ";
        $list = $RequestTempModel
            ->join('left join hii_goods on hii_request_temp.goods_id=hii_goods.id')
            ->join('left join hii_goods_cate on hii_goods.cate_id=hii_goods_cate.id')
            ->join('left join hii_attr_value on hii_attr_value.value_id=hii_request_temp.value_id')
            ->join('left join hii_goods_store on hii_goods_store.store_id=' . $store_id . ' and hii_request_temp.goods_id=hii_goods_store.goods_id ')
            ->field($field)
            ->where($where)->order(' hii_request_temp.ctime asc ')->select();
        $sql = $RequestTempModel->_sql();
        if (I("get.showsql") == "true") {
            echo $sql;
            exit;
        }

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

    /**************
     * 清空临时申请表数据接口
     * 请求方式：POST
     * 请求参数：无
     */
    public function clearRequestTemp()
    {
        $where = array();
        $where['admin_id'] = UID;//当前登录账号的uid
        $where['store_id'] = $this->_store_id;
        $where['temp_type'] = 2;//门店发货申请
        $where['hii_request_temp.status'] = 0;
        $RequestTempModel = M("RequestTemp");
        $result = $RequestTempModel->where($where)->delete();
        if (!$result) {
            $this->response(0, "删除失败");
        } else {
            $this->response(self::CODE_OK, "删除成功");
        }
    }

    /**************
     * 提交门店发货申请接口,把临时申请表的数据提交到hii_store_request和hii_store_request_detail中
     * 请求方式：POST
     * 请求参数：warehouse_id 申请仓库ID
     *           remark       备注
     * 注意：提交时候要检查发货仓库库存是否足够
     ***************/
    public function submitRequestTemp()
    {
        $store_id = $this->_store_id;
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "请选择请求门店");
        }
        $warehouse_id = I("post.warehouse_id");
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择请求仓库");
        }
        $remark = I("post.remark");
        $admin_id = UID;
        $StoreRequestRepository = D('StoreRequest');
        $result = $StoreRequestRepository->submitStoreRequest($admin_id, $store_id, $warehouse_id, $remark);
        //\Think\Log::record($result["msg"]);
        if ($result["status"] == "200") {
            //加入消息提醒
            $MessageWarnModel = D('MessageWarn');
            $MessageWarnModel->pushMessageWarn($admin_id  , $warehouse_id  ,0 ,  0 , $result['data'] ,MessageWarnModel::STOCK_TO_STORE);
            $this->response(self::CODE_OK, $result["msg"]);
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /*******************************
     * 发货申请单到处Excel接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *************************/
    public function exportStoreRequestListExcel()
    {
        $store_id = $this->_store_id;
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "请选择门店");
        }
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

        $Model = M('RequestTemp');
        //sum(A1.g_num*G.sell_price) as g_amounts
        $sql = "select A.s_r_id,A.s_r_sn,A.s_r_type,A.s_r_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,";
        $sql .= "A.admin_id,B.nickname,A.warehouse_id,C.w_name as warehouse_name,A.store_id,S.title as store_name, ";
        $sql .= "A.remark,A.g_type,A.g_nums,sum(A1.is_pass) as pass_num, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_request A ";
        $sql .= "left join hii_store_request_detail A1 on A.s_r_id=A1.s_r_id ";
        $sql .= "left join hii_member B on A.admin_id=B.uid ";
        $sql .= "left join hii_warehouse C on A.warehouse_id=C.w_id ";
        $sql .= "left join hii_goods G on A1.goods_id=G.id ";
        $sql .= "left join hii_store S on S.id = A.store_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "where A.store_id = {$store_id} and A.s_r_type=0 and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by A.s_r_id,A.s_r_sn,A.s_r_type,A.s_r_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.remark,A.g_type,A.g_nums order by s_r_id desc";
        //echo $sql;
        //exit();
        $data = $Model->query($sql);
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '门店发货申请单';
        $fname = './Public/Excel/StoreRequest_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreRequestReportModel();
        $printfile = $printmodel->createStoreRequestListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /**********************
     * 临时申请删除接口
     * 请求方式：POST
     * 请求参数：id  临时申请ID 必填
     */
    public function singleRequestTempDelete()
    {
        $admin_id = UID;
        if (is_null($admin_id) || empty($admin_id)) {
            $this->response(0, "请先登录");
        }
        $id = I("post.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要删除的信息");
        }
        $RequestTempModel = M("RequestTemp");

        $where["admin_id"] = $admin_id;
        $where["temp_type"] = 2;
        $where["status"] = 0;
        $where["id"] = $id;
        $result = $RequestTempModel->where($where)->delete();
        if (!$result) {
            $this->response(0, "删除失败");
        } else {
            $this->response(self::CODE_OK, "删除成功");
        }

    }

    /****************
     * 获取单个临时申请信息接口
     * 请求方式：GET
     * 请求参数：id  临时申请ID 必填
     */
    public function getSingleRequestTempInfo()
    {
        $admin_id = UID;
        if (is_null($admin_id) || empty($admin_id)) {
            $this->response(0, "请先登录");
        }
        $id = I("get.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要获取的信息ID");
        }
        $RequestTempModel = M("RequestTemp");
        $sql = " select A.id,A.goods_id,G.title as goods_name , G.bar_code,A.g_num,A.remark,A.value_id ";
        $sql .= "from hii_request_temp A ";
        $sql .= "left join hii_goods G on G.id = A.goods_id ";
        $sql .= "where A.id = {$id} order by id desc limit 1 ";
        //echo $sql;
        //exit();
        $data = $RequestTempModel->query($sql);
        if (is_null($data) || empty($data) || count($data) == 0) {
            $this->response(0, "不存在该信息");
        } else {

            $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$data[0]['goods_id'],'status'=>array('neq',2)))->select();
            if(empty($attr_value_array)){
                $attr_value_array = array();
            }
            $data[0]['attr_value_array'] = $attr_value_array;
            $this->response(self::CODE_OK, $data);
        }
    }

    /************
     * 获取单个申请详细信息
     * 请求方式：GET
     * 请求参数：s_r_id  申请单ID  必填
     ********************/
    public function getSingleStoreRequestDetailInfo()
    {
        $s_r_id = I("get.s_r_id");
        $admin_id = UID;
        if (is_null($admin_id) || empty($admin_id)) {
            $this->response(0, "请先登录");
        }
        if (is_null($s_r_id) || empty($s_r_id)) {
            $this->response(0, "请选择要查看的申请");
        }
        $result = $this->getSingleStoreRequestDatas($s_r_id, $admin_id);
        $this->response(self::CODE_OK, $result);
    }

    /*************
     * 导出单挑申请明细Excel接口
     * 请求方式：GET
     * 请求参数：s_r_id  申请单ID  必填
     */
    public function exportSingleStoreRequestDetailExcel()
    {
        $s_r_id = I("get.s_r_id");
        $admin_id = UID;
        if (is_null($admin_id) || empty($admin_id)) {
            $this->response(0, "请先登录");
        }
        if (is_null($s_r_id) || empty($s_r_id)) {
            $this->response(0, "请选择要查看的申请");
        }
        $result = $this->getSingleStoreRequestDatas($s_r_id, $admin_id);
        $title = '门店发货申请单明细';
        $fname = './Public/Excel/StoreRequestDetail_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreRequestReportModel();
        $printfile = $printmodel->createSingleStoreRequestInfoExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*************
     * 再次申请接口,把原订单信息复制然后放到临时申请表中
     * 请求方式：POST
     * 请求参数： s_r_id  原申请单ID  必须
     */
    public function submitAgain()
    {
        $s_r_id = I("post.s_r_id");
        $admin_id = UID;
        if (is_null($admin_id) || empty($admin_id)) {
            $this->response(0, "请先登录");
        }
        if (is_null($s_r_id) || empty($s_r_id)) {
            $this->response(0, "请选择要再次提交的申请");
        }
        $StoreRequestRepository = D('StoreRequest');
        $result = $StoreRequestRepository->submitAgain($s_r_id, $admin_id);
        if ($result["status"] == 200) {
            $this->response(self::CODE_OK, "提交成功");
        } else {
            $this->response(0, "提交失败");
        }
    }

    /************************************
     * 通过bar_code 或者 goods_id获取商品信息【门店部分同一调用该接口】
     * 请求方式：GET
     * 请求参数：bar_code   商品条码   否
     *           goods_id   商品ID     否
     *           temp_type  申请类型   是
     * 日期：2018-01-20
     * 输出：$result(
     *           "goods_name"=>"商品名称",
     *           "bar_code"=>"商品条码",
     *           "goods_id"=>"商品ID",
     *           "id"=>"临时申请ID",
     *           "g_num"=>"申请数量",
     *           "remark"=>"备注",
     *           "b_n_num"=>"箱规",
     *           "b_num"=>"箱数",
     *           "b_price"=>"每箱价格",
     *           "g_price"=>"单价",
     *           "temp_type"=>"申请类型"
     *       )
     */
    public function getgoods()
    {
        $bar_code = I("post.bar_code");
        $goods_id = I("post.goods_id");
        $value_id = I("post.value_id");

        $temp_type = I("post.temp_type");
        $admin_id = UID;
        $GoodsModel = M("Goods");
        $goodsStoreModel = M("GoodsStore");
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        if (empty($bar_code) && empty($goods_id)) {
            $this->response(0, "请提供商品条码或商品ID");
        }
        if (is_null($temp_type) || empty($temp_type)) {
            $this->response(0, "请提供临时申请类型");
        }
        //类型:1.采购申请,2.发货申请,3.仓库调拨申请,4.退货申请,5.临时采购单,
        //6门店调拨申请,7.盘点,8.门店盘点,9.门店寄售入库,10.门店返仓,
        //11.仓库出库验货,12.门店寄售出库
        if ($temp_type == 9 || $temp_type == 12) {
            $cate_id_where = "and cate_id = 18";
        }else{
            $cate_id_where = "and cate_id != 18";
        }
        if (!empty($bar_code)) {
            if($temp_type == 8){
                $goods_store_sql = $goodsStoreModel->field("distinct goods_id")->where(array("store_id"=>$store_id))->select(false);
                $datas = $GoodsModel->where(" bar_code like '%{$bar_code}%'  {$cate_id_where}")->join("inner join {$goods_store_sql} as b on b.goods_id=id")->limit(1)->select();
            }else{
                $datas = $GoodsModel->where(" bar_code like '%{$bar_code}%'  {$cate_id_where}")->limit(1)->select();
            }

            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            }else {
                if(!empty($value_id)){
                    $value_where = "RT.value_id={$value_id} and";
                }
                $sql = "select RT.id,G.title as goods_name,RT.goods_id,G.bar_code,RT.g_num,RT.remark,RT.b_n_num,RT.b_num,RT.b_price,RT.g_price,RT.value_id  ";
                $sql .= "from hii_request_temp RT left join hii_goods G on G.id=RT.goods_id  ";
                $sql .= "where {$value_where} RT.admin_id={$admin_id} and RT.temp_type={$temp_type} and G.id={$datas[0]['id']} limit 1  ";
                $temp_datas = $GoodsModel->query($sql);
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datas[0]['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                if ($this->isArrayNull($temp_datas) != null) {
                    $this->response(self::CODE_OK, array(
                        "goods_name" => $temp_datas[0]["goods_name"],
                        "bar_code" => $temp_datas[0]["bar_code"],
                        "goods_id" => $temp_datas[0]["goods_id"],
                        "id" => $temp_datas[0]["id"],
                        "g_num" => $temp_datas[0]["g_num"],
                        "remark" => $temp_datas[0]["remark"],
                        "b_n_num" => $temp_datas[0]["b_n_num"],
                        "b_num" => $temp_datas[0]["b_num"],
                        "b_price" => $temp_datas[0]["b_price"],
                        "g_price" => $temp_datas[0]["g_price"],
                        "temp_type" => $temp_type,
                        "value_id" => $temp_datas[0]["value_id"],
                        "attr_value_array"=>$attr_value_array
                    ));
                } else {
                    $this->response(self::CODE_OK, array(
                        "goods_name" => $datas[0]["title"],
                        "bar_code" => $datas[0]["bar_code"],
                        "goods_id" => $datas[0]["id"],
                        "id" => "",
                        "g_num" => "",
                        "remark" => "",
                        "b_n_num" => "",
                        "b_num" => "",
                        "b_price" => "",
                        "g_price" => $datas[0]["sell_price"],
                        "temp_type" => $temp_type,
                        "value_id" => $value_id,
                        "attr_value_array"=>$attr_value_array
                    ));
                }
            }
        } elseif (!empty($goods_id)) {

            if($temp_type == 8){
                $goods_store_sql = $goodsStoreModel->field("distinct goods_id")->where(array("store_id"=>$store_id))->select(false);
                $datas = $GoodsModel->where(" id='{$goods_id}' {$cate_id_where} ")->join("inner join {$goods_store_sql} as b on b.goods_id=id")->limit(1)->select();
            }else{
                $datas = $GoodsModel->where(" id='{$goods_id}' {$cate_id_where} ")->limit(1)->select();
            }
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            }else {

                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datas[0]['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                if(!empty($value_id)){
                    $where['value_id'] = $value_id;
                }
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["temp_type"] = $temp_type;
                $outdata["goods_id"] = $goods_id;
                $outdata['attr_value_array'] = $attr_value_array;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["b_n_num"] = $tmp[0]["b_n_num"];
                    $outdata["b_num"] = $tmp[0]["b_num"];
                    $outdata["b_price"] = $tmp[0]["b_price"];
                    $outdata["g_price"] = $tmp[0]["g_price"];
                    $outdata["value_id"] = $tmp[0]["value_id"];
                } else {
                    $outdata["id"] = "";
                    $outdata["g_num"] = "";
                    $outdata["remark"] = "";
                    $outdata["b_n_num"] = "";
                    $outdata["b_num"] = "";
                    $outdata["b_price"] = "";
                    $outdata["g_price"] = $datas[0]["sell_price"];
                    $outdata["value_id"] = $value_id;
                }
                $this->response(self::CODE_OK, $outdata);
            }
        }
    }

    /********************
     * 获取单个申请详细信息
     * @param $s_r_id 申请单ID
     * @param $admin_id 当前登录账号ID
     */
    private function getSingleStoreRequestDatas($s_r_id, $admin_id)
    {
        $StoreRequestModel = M("StoreRequest");

        $store_id = $this->_store_id;

        $sql = " select A.s_r_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.g_type,A.g_nums,A.remark, ";
        $sql .= " sum(A1.g_num*G.sell_price) as `g_amounts`,B.nickname,S.title as store_name,W.w_name as warehouse_name ";
        $sql .= "from hii_store_request A ";
        $sql .= "left join `hii_store_request_detail` A1 on A1.s_r_id = A.s_r_id ";
        $sql .= "left join `hii_goods` G on A1.goods_id=G.id ";
        $sql .= "left join `hii_member` B on A.admin_id=B.uid ";
        $sql .= "left join `hii_store` S on S.id = A.store_id ";
        $sql .= "left join `hii_warehouse` W on W.w_id = A.warehouse_id ";
        $sql .= "where A.s_r_id={$s_r_id} and A.store_id={$store_id} order by A.s_r_id desc limit 1 ";
        //echo $sql;exit;
        $StoreRequestEntityList = $StoreRequestModel->query($sql);

        if (is_null($StoreRequestEntityList) || empty($StoreRequestEntityList) || count($StoreRequestEntityList) == 0) {
            $this->response(0, "不存在该信息");
        }

        $maindata = array();
        $StoreRequestEntity = $StoreRequestEntityList[0];
        $maindata[0]["s_r_sn"] = $StoreRequestEntity["s_r_sn"];
        $maindata[0]["ctime"] = $StoreRequestEntity["ctime"];
        $maindata[0]["g_type"] = $StoreRequestEntity["g_type"];
        $maindata[0]["g_nums"] = $StoreRequestEntity["g_nums"];
        //$maindata[0]["g_amounts"] = $StoreRequestEntity["g_amounts"];
        $maindata[0]["nickname"] = $StoreRequestEntity["nickname"];
        $maindata[0]["store_name"] = $StoreRequestEntity["store_name"];
        $maindata[0]["warehouse_name"] = $StoreRequestEntity["warehouse_name"];
        $maindata[0]["remark"] = $StoreRequestEntity["remark"];

        //sys_price 系统售价  store_price 门店价格  shequ_price 区域价格
        $sql = " select A1.goods_id,G.title as goods_name,C.title as cate_name,AV.bar_code,G.sell_price as sys_price,A1.g_num, ";
        $sql .= "A1.pass_num,A1.is_pass,A1.is_pass as status_name,A1.remark,GS.price as store_price,GS.shequ_price as shequ_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_request_detail A1 ";
        $sql .= "left join hii_goods G on G.id = A1.goods_id ";
        $sql .= "left join hii_goods_cate C on G.cate_id=C.id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=A1.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_attr_value AV on AV.value_id=A1.value_id ";
        $sql .= "where A1.s_r_id = {$s_r_id} order by A1.goods_id asc ";

        $StoreRequestDetailModel = M("StoreRequestDetail");
        $StoreRequestEntityDetailList = $StoreRequestDetailModel->query($sql);
        $g_amounts = 0;//总售价金额
        foreach ($StoreRequestEntityDetailList as $key => $val) {
            switch ($val["is_pass"]) {
                case 0: {
                    $StoreRequestEntityDetailList[$key]["status_name"] = "新增";
                };
                    break;
                case 1: {
                    $StoreRequestEntityDetailList[$key]["status_name"] = "拒绝";
                };
                    break;
                case 2: {
                    if ($val["g_num"] > $val["pass_num"]) {
                        $StoreRequestEntityDetailList[$key]["status_name"] = "部分通过";
                    } else {
                        $StoreRequestEntityDetailList[$key]["status_name"] = "通过";
                    }
                };
                    break;
                case 3: {
                    $StoreRequestEntityDetailList[$key]["status_name"] = "已转采购";
                };
                    break;
                case 4: {
                    $StoreRequestEntityDetailList[$key]["status_name"] = "仓库备货中";
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
            $StoreRequestEntityDetailList[$key]["sell_price"] = $price;
            $g_amounts += $val["g_num"] * $price;
        }
        $maindata[0]["g_amounts"] = $g_amounts;
        $result = array(
            "maindata" => $maindata,
            "list" => $StoreRequestEntityDetailList
        );

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