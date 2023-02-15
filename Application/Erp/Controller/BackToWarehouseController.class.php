<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-11
 * Time: 10:18
 * 门店返仓管理
 */

namespace Erp\Controller;

use Erp\Model\MessageWarnModel;
use Think\Controller;

class BackToWarehouseController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /********************
     * 返仓临时申请列表接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     */
    public function temp()
    {
        $result = $this->getTempList();
        $this->response(self::CODE_OK, $result);
    }

    /********************
     * 返仓申请列表接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     *           p    当前页    非必填   默认1
     */
    public function index()
    {
        $result = $this->getIndexList(true);
        $this->response(self::CODE_OK, $result);
    }

    /*********************
     * 加入临时申请表接口
     * 请求方式：POST
     * 请求参数： goods_id 商品ID 必须
     *            g_num    申请数量  必须
     * 日期：2018-01-11
     * 提示：b_n_num,b_num,b_price,g_price未付值
     */
    public function addRequestTemp()
    {
        $admin_id = UID;
        $temp_type = 10;//门店返仓申请
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
                $list = $this->getTempList();
                $this->response(self::CODE_OK, $list);
            }

        }else {
            $where["admin_id"] = $admin_id;
            $where["hii_request_temp.status"] = $status;
            $where["store_id"] = $this->_store_id;
            $where["goods_id"] = $goods_id;
            $where["temp_type"] = $temp_type;
            $where["value_id"] = $value_id;
            $data = $RequestTempModel
                ->where($where)
                ->order(" id desc ")
                ->limit(1)
                ->select();

            if ($data) {
                //更新
                $saveData["g_num"] = $g_num;
                $saveData["remark"] = $remark;
                $result = $RequestTempModel->where(" id={$data[0]["id"]} ")->save($saveData);
                if ($result === false) {
                    $this->response(0, $RequestTempModel->getError());
                } else {
                    $list = $this->getTempList();
                    $this->response(self::CODE_OK, $list);
                }
            } else {
                //新增
                $WarehouseInoutViewModel = M("WarehouseInoutView");
                $tmp = $WarehouseInoutViewModel->field(" ifnull(stock_price,0) as g_price ")->where(" goods_id={$goods_id} ")->limit(1)->select();
                if ($this->isArrayNull($tmp) != null) {
                    $g_price = $tmp[0]["g_price"];
                }

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
                if (!$result) {
                    $this->response(0, $RequestTempModel->getError());
                } else {
                    $list = $this->getTempList();
                    $this->response(self::CODE_OK, $list);
                }
            }
        }
    }

    /************************
     * 清空返仓临时申请接口
     * 请求方式：POST
     * 请求参数：无
     * 日期：2018-01-11
     */
    public function clearRequestTemp()
    {
        $temp_type = 10;//门店返仓申请
        $admin_id = UID;
        $RequestTempModel = M("RequestTemp");
        $store_id = $this->_store_id;
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} ")->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        }
        $this->response(self::CODE_OK, "操作成功");
    }

    /*********************
     * 仓库列表接口
     * 请求方式：GET
     * 请求参数：无
     * 日期：2018-01-11
     */
    public function warehouselist()
    {
        $WarehouseModel = M("Warehouse");
        $store_id = $this->_store_id;
        $where = " shequ_id=(select shequ_id from hii_store where id={$store_id} limit 1 ) ";
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        if (count($can_warehouse_id_array) > 0) {
            $where .= " and w_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        $list = $WarehouseModel->field(" w_id,w_name ")->where($where)->order(" w_id asc ")->select();
        $this->response(self::CODE_OK, $list);
    }

    /**************************
     * 删除单个临时申请接口
     * 请求方式：POST
     * 请求参数：id  临时申请ID  必须
     * 日期：2018-01-11
     */
    public function deleteRequestTemp()
    {
        $id = I("post.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要删除的信息");
        }
        $RequestTempModel = M("RequestTemp");
        $admin_id = UID;
        $temp_type = 10;
        $where = " id={$id} and temp_type={$temp_type} and status=0 and admin_id={$admin_id} ";
        $ok = $RequestTempModel->where($where)->limit(1)->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /************************
     * 编辑接口
     * 请求方式：GET
     * 请求参数：id 临时申请ID 必须
     * 日期：2018-01-11
     */
    public function edit()
    {
        $id = I("get.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择需要编辑的信息");
        }
        $temp_type = 10;
        $admin_id = UID;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,RT.g_num,G.title as goods_name,G.bar_code,RT.remark,RT.value_id ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "where RT.id={$id} and RT.admin_id={$admin_id} and RT.temp_type={$temp_type} order by RT.id desc limit 1 ";
        $datas = $RequestTempModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法编辑该申请");
        } else {
            $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datas[0]['goods_id'],'status'=>array('neq',2)))->select();
            if(empty($attr_value_array)){
                $attr_value_array = array();
            }
            $datas[0]['attr_value_array'] = $attr_value_array;
            $this->response(self::CODE_OK, $datas[0]);
        }
    }

    /**********************
     * 提交临时申请接口
     * 请求方式：POST
     * 请求参数：warehouse_id  仓库ID 必须
     *           remark        备注   非必须
     * 日期：2018-01-11
     */
    public function submitRequestTemp()
    {
        $warehouse_id = I("post.warehouse_id");
        $remark = I("post.remark");
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择要返回的仓库");
        }
        $admin_id = UID;
        $store_id = $this->_store_id;
        $BackToWarehouseRepository = D("BackToWarehouse");
        $result = $BackToWarehouseRepository->submitRequestTemp($admin_id, $store_id, $warehouse_id, $remark);
        if ($result["status"] == "0") {
            $this->response(0, $result["msg"]);
        } else {
            //加入消息提醒
            $MessageWarnModel = D('MessageWarn');
            $MessageWarnModel->pushMessageWarn($admin_id  , 0  ,$store_id ,  0 , $result['data'] ,MessageWarnModel::STORE_RETURN_STOCK);
            $this->response(self::CODE_OK, "提交成功");
        }
    }

    /*************************
     * 作废接口
     * 请求方式：POST
     * 请求参数：s_back_id  返仓申请ID  必须
     * 日期：2018-01-11
     */
    public function cancel()
    {
        $s_back_id = I("post.s_back_id");
        if (is_null($s_back_id) || empty($s_back_id)) {
            $this->response(0, "请选择要作废的返仓申请");
        }
        $store_id = $this->_store_id;
        $StoreBackModel = M("StoreBack");
        $datas = $StoreBackModel->where(" s_back_id={$s_back_id} and store_id={$store_id} and s_back_status=0 ")->order(" s_back_id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法作废该申请");
        }
        $ok = $StoreBackModel->where(" s_back_id={$s_back_id} ")->limit(1)->save(array("s_back_status" => 2));
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /*************************
     * 审核接口
     * 请求方式：POST
     * 请求参数：s_back_id  返仓申请ID  必须
     * 日期：2018-01-11
     */
    public function check()
    {
        $s_back_id = I("post.s_back_id");
        if (is_null($s_back_id) || empty($s_back_id)) {
            $this->response(0, "请选择要审核的返仓申请");
        }
        $padmin_id = UID;
        $store_id = $this->_store_id;
        $BackToWarehouseRepository = D("BackToWarehouse");
        $result = $BackToWarehouseRepository->check($padmin_id, $s_back_id, $store_id);
        if ($result["status"] == "0") {
            $this->response(0, $result["msg"]);
        } else {
            //加入消息提醒
            $MessageWarnModel = D('MessageWarn');
            $MessageWarnModel->pushMessageWarn($padmin_id  , $result['data']['warehouse_id']  ,0 ,  0 , $result['data'] ,MessageWarnModel::STOCK_IN);
            $this->response(self::CODE_OK, "审核成功");
        }
    }

    /***********************
     * 查看接口
     * 请求方式：GET
     * 请求参数：s_back_id 返仓申请ID  必须
     * 日期：2018-01-11
     */
    public function view()
    {
        $result = $this->getViewInfo();
        $this->response(self::CODE_OK, $result);
    }

    /*******************
     * 导出列表Excel文档接口
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     */
    public function exportIndexListExcel()
    {
        $result = $this->getIndexList(false);
        $s_date = $result["s_date"];
        $e_date = $result["e_date"];
        $data = $result["data"];
        ob_clean;
        $title = $s_date . '>>>' . $e_date . '返仓单';
        $fname = './Public/Excel/StoreBack_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\BackToWarehouseModel();
        $printfile = $printmodel->createIndexListExcel($data, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /****************************
     * 导出查看Excel文档接口
     * 请求方式：GET
     * 请求参数：s_back_id  返仓单ID  必须
     * 日期：2018-01-11
     */
    public function exportViewExcel()
    {
        $result = $this->getViewInfo();
        ob_clean;
        $title = '返仓单查看';
        $fname = './Public/Excel/StoreBack_' . $result["maindata"]["s_back_sn"] . '_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\BackToWarehouseModel();
        $printfile = $printmodel->createViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /******************************
     * 再次申请
     * 请求方式：POST
     * 请求参数：s_back_id  返仓ID  必须
     * 日期：2018-01-12
     */
    public function again()
    {
        $s_back_id = I("post.s_back_id");
        if (is_null($s_back_id) || empty($s_back_id)) {
            $this->response(0, "请选择再次申请的返仓信息");
        }
        $store_id = $this->_store_id;
        $admin_id = UID;
        $StoreBackModel = M("StoreBack");
        $StoreBackDetailModel = M("StoreBackDetail");
        $datas = $StoreBackModel->where(" s_back_id={$s_back_id} and store_id={$store_id} ")->order(" s_back_id desc ")->limit(1)->select();
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法再次提交");
        }
        $RequestTempModel = M("RequestTemp");
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $details = $StoreBackDetailModel->where(" s_back_id={$s_back_id} ")->select();
        //$RequestTempEntitys = array();
        $ctime = time();
        foreach ($details as $key => $val) {
            $tmp = $RequestTempModel->where(" value_id = {$val['value_id']} and goods_id={$val["goods_id"]} and temp_type=10 and store_id={$store_id} ")->limit(1)->select();
            if ($this->isArrayNull($tmp) == null) {
                $g_price = 0;
                $tmp = $WarehouseInoutViewModel->field(" ifnull(stock_price,0) as g_price ")->where(" goods_id={$val["goods_id"]} ")->limit(1)->select();
                if ($this->isArrayNull($tmp) != null) {
                    $g_price = $tmp[0]["g_price"];
                }
                $savedata = array(
                    "admin_id" => $admin_id,
                    "store_id" => $store_id,
                    "temp_type" => 10,
                    "goods_id" => $val["goods_id"],
                    "ctime" => $ctime,
                    "status" => 0,
                    "g_num" => $val["g_num"],
                    "g_price" => $g_price,
                    "value_id" => $val['value_id']
                );
                $ok = $RequestTempModel->add($savedata);
            } else {
                $savedata = array(
                    "g_num" => $tmp[0]["g_num"] + $val["g_num"]
                );
                $ok = $RequestTempModel->where(" id={$tmp[0]["id"]} ")->limit(1)->save($savedata);
            }
            if ($ok === false) {
                $this->response(0, "操作失败");
            }
        }
        //$ok = $RequestTempModel->addAll($RequestTempEntitys);
        $this->response(self::CODE_OK, "提交成功");
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
        $temp_type = 10;
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
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('attr_id'=>$datas[0]['attr_id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $goods_id = $datas[0]["id"];
                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                $outdata["attr_value_array"] = $attr_value_array;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["value_id"] = $tmp[0]["value_id"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        } elseif (!empty($goods_id)) {
            $datas = $GoodsModel->where(" id='{$goods_id}' ")->limit(1)->select();
            if ($this->isArrayNull($datas) == null) {
                $this->response(0, "商品不存在");
            } else {
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('attr_id'=>$datas[0]['attr_id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }

                $outdata = array();
                $where["goods_id"] = $goods_id;
                $where["temp_type"] = $temp_type;
                $where["admin_id"] = $admin_id;
                $tmp = $RequestTempModel->where($where)->limit(1)->select();
                $outdata["goods_name"] = $datas[0]["title"];
                $outdata["bar_code"] = $datas[0]["bar_code"];
                $outdata["goods_id"] = $goods_id;
                $outdata["attr_value_array"] = $attr_value_array;
                if ($this->isArrayNull($tmp) != null) {
                    $outdata["id"] = $tmp[0]["id"];
                    $outdata["g_num"] = $tmp[0]["g_num"];
                    $outdata["remark"] = $tmp[0]["remark"];
                    $outdata["value_id"] = $tmp[0]["value_id"];
                }
                $this->response(self::CODE_OK, $outdata);
            }
        }
    }

    private function getTempList()
    {
        $store_id = $this->_store_id;
        $admin_id = UID;
        $temp_type = 10;
        $status = 0;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,G.title as goods_name,RT.remark, ";
        $sql .= "G.bar_code,GC.title as cate_name,RT.g_num,ifnull(GS.num,0) as stock_num, ";
        $sql .= "G.sell_price as sys_price,GS.shequ_price as shequ_price,GS.price as store_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=RT.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_attr_value AV on AV.value_id=RT.value_id ";
        $sql .= "where RT.admin_id={$admin_id} and RT.store_id={$store_id} and RT.temp_type={$temp_type} and RT.status={$status} order by RT.id desc ";
        //echo $sql;exit;
        $list = $RequestTempModel->query($sql);
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
        }
        return $this->isArrayNull($list);
    }

    private function getIndexList($usePager)
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $StoreBackModel = M("StoreBack");
        $store_id = $this->_store_id;

        $can_store_id_array = $this->getCanStoreIdArray();
        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " SB.store_id in (" . implode(",", $can_store_id_array) . ") ";
        }
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= (!empty($shequ_where) ? "or" : "") . " SB.warehouse_id in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ( {$shequ_where} ) ";
        }

        $sql = "select SB.s_back_id,SB.s_back_sn,FROM_UNIXTIME(SB.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SB.g_type,SB.g_nums, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,W.w_name as warehouse_name,SB.s_back_type,SB.s_back_status,SB.remark, ";
        $sql .= "SUM(SBD.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_back SB ";
        $sql .= "left join hii_store_back_detail SBD on SBD.s_back_id=SB.s_back_id ";
        $sql .= "left join hii_goods G on G.id=SBD.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=SBD.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_member M on M.uid=SB.admin_id ";
        $sql .= "left join hii_store S on S.id=SB.store_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SB.warehouse_id ";
        $sql .= "where SB.store_id={$store_id} {$shequ_where} and FROM_UNIXTIME(SB.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by SB.s_back_id,SB.s_back_sn,SB.ctime,SB.g_type,SB.g_nums,M.nickname,S.title,W.w_name,SB.s_back_type,SB.s_back_status ";
        $sql .= "order by SB.s_back_id desc ";

        //echo $sql;exit;

        $data = $StoreBackModel->query($sql);

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            switch ($val["s_back_status"]) {
                case 0: {
                    $data[$key]["s_back_status_name"] = "新增";
                };
                    break;
                case 1: {
                    //$data[$key]["s_back_status_name"] = "已审核转仓库入库验收";
                    $data[$key]["s_back_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_back_status_name"] = "已作废";
                };
                    break;
            }
            switch ($val["s_back_type"]) {
                case 0: {
                    $data[$key]["s_back_type_name"] = "门店返仓";
                };
                    break;
                case 1: {
                    $data[$key]["s_back_type_name"] = "其他";
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
        $s_back_id = I("get.s_back_id");
        if (is_null($s_back_id) || empty($s_back_id)) {
            $this->response(0, "请选择返仓单");
        }
        $StoreBackModel = M("StoreBack");
        $StoreBackDetailModel = M("StoreBackDetail");
        $store_id = $this->_store_id;

        $sql = "select SB.s_back_id,SB.s_back_sn,FROM_UNIXTIME(SB.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SB.g_type,SB.g_nums, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,W.w_name as warehouse_name,SB.s_back_type,SB.s_back_status,SB.remark ";
        //$sql .= "SUM(SBD.g_num*G.sell_price) as g_amounts ";
        $sql .= "from hii_store_back SB ";
        $sql .= "left join hii_store_back_detail SBD on SBD.s_back_id=SB.s_back_id ";
        $sql .= "left join hii_goods G on G.id=SBD.goods_id ";
        $sql .= "left join hii_member M on M.uid=SB.admin_id ";
        $sql .= "left join hii_store S on S.id=SB.store_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SB.warehouse_id ";
        $sql .= "where SB.store_id={$store_id} and SB.s_back_id={$s_back_id} ";
        $sql .= "group by SB.s_back_id,SB.s_back_sn,SB.ctime,SB.g_type,SB.g_nums,M.nickname,S.title,W.w_name,SB.s_back_type,SB.s_back_status ";
        $sql .= "order by SB.s_back_id desc ";

        $datas = $StoreBackModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无权查看该返仓单信息");
        }

        //sys_price 系统售价 store_price 门店售价 shequ_price 区域价
        $sql = "select SBD.s_back_d_id,SBD.goods_id,SBD.g_num,SBD.g_price,ifnull(AV.bar_code,G.bar_code)bar_code,G.title as goods_name,GC.title as cate_name,SBD.remark, ";
        $sql .= "G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price,AV.value_id,AV.value_name ";
        $sql .= "from hii_store_back_detail SBD ";
        $sql .= "left join hii_goods G on G.id=SBD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=SBD.goods_id and GS.store_id={$store_id} ";
        $sql .= "left join hii_attr_value AV on AV.value_id=SBD.value_id ";
        $sql .= "where SBD.s_back_id={$s_back_id} order by SBD.s_back_d_id desc ";
        $list = $StoreBackDetailModel->query($sql);

        switch ($datas[0]["s_back_status"]) {
            case 0: {
                $datas[0]["s_back_status_name"] = "新增";
            };
                break;
            case 1: {
                //$datas[0]["s_back_status_name"] = "已审核转仓库入库验收";
                $datas[0]["s_back_status_name"] = "已审核";
            };
                break;
            case 2: {
                $datas[0]["s_back_status_name"] = "已作废";
            };
                break;
        }
        switch ($datas[0]["s_back_type"]) {
            case 0: {
                $datas[0]["s_back_type_name"] = "门店返仓";
            };
                break;
            case 1: {
                $datas[0]["s_back_type_name"] = "其他";
            };
                break;
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
        $datas[0]["g_amounts"] = $g_amounts;

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