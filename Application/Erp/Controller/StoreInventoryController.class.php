<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-12
 * Time: 10:13
 * 门店盘点相关接口
 */

namespace Erp\Controller;

use Think\Controller;

class StoreInventoryController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    /*******************
     * 临时盘点单列表接口
     * 请求方式：GET
     * 请求参数：    p    当前页    非必填   默认1
     * 注意：查找hii_request_temp的temp_type为7的数据
     * 日期：2017-12-12
     */
    public function temp()
    {
        $admin_id = UID;
        $store_id = $this->_store_id;
        $temp_type = 8;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,G.title as goods_name,G.bar_code,RT.remark, ";
        $sql .= "ifnull(GS.num,0) as stock_num ,RT.g_num,GC.title as cate_name,G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price ";
        $sql .= "from  hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=RT.goods_id ";
        $sql .= "where RT.admin_id={$admin_id} and RT.store_id={$store_id} and RT.temp_type={$temp_type} order by RT.id desc ";
        $data = $RequestTempModel->query($sql);
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;

        $result["cate"] = M("GoodsCate")->query("select id as cate_id,title as cate_name from hii_goods_cate order by id asc ");
        foreach ($datamain as $key => $val) {
            $price = 0;
            if (!is_null($val["store_price"]) && !empty($val["store_price"]) && $val["store_price"] > 0) {
                $price = $val["store_price"];
            } elseif (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $price = $val["shequ_price"];
            } elseif (!is_null($val["sys_price"]) && !empty($val["sys_price"])) {
                $price = $val["sys_price"];
            }
            $datamain[$key]["sell_price"] = $price;
        }
        $result["data"] = $this->isArrayNull($datamain);

        $this->response(self::CODE_OK, $result);
    }

    /*****************************
     * 盘点单列表接口
     * 请求方式：GET
     * 请求参数：p    当前页    非必填   默认1
     *           s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     * 日期：2017-12-13
     */
    public function index()
    {
        $result = $this->getIndexList(true);
        $result["show_del"] = $this->checkFunc('DelIncentory');
        $this->response(self::CODE_OK, $result);
    }

    /*********************
     * 查询单个数据
     * 参数   goods_id  商品id
     ********************/
    public function getgoods()
    {
        $data = $this->gv();
        $goods_id = $data['goods_id'];
        $bar_code = trim($data['bar_code']);
        $temp_type = $data['temp_type'];
        $cate_id = I("get.cate_id");

        if (empty($goods_id) && empty($bar_code)) {
            $this->response(999, '缺少商品id、条码参数！');
        }
        if (!empty($goods_id)) {
            $where["hii_goods.id"] = $goods_id;
            $where["hii_goods.status"] = 1;
            if (!is_null($cate_id) && !empty($cate_id)) {
                $where["hii_goods.cate_id"] = $cate_id;
            }else{
                $where["hii_goods.cate_id"] = array('neq',18);
            }
            $datafind = M('Goods')->where($where)->field('id, title, sell_price, sell_online, sell_outline')->find();
            if (!$datafind) {
                $this->response(999, '没有该商品id！');
            } else {
                $data0 = M('GoodsBarCode')->where(array('goods_id' => $datafind['id']))->find();
                if (!$data0) {
                    $this->response(999, '该商品条码没找到！');
                }
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datafind['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata['goods_id'] = $datafind['id'];
                $outdata['goods_name'] = $datafind['title'];
                $outdata['bar_code'] = $data0['bar_code'];
                $outdata['sell_price'] = $datafind['sell_price'];
                $outdata['attr_value_array'] = $attr_value_array;
                $where['admin_id'] = UID;
                $where['temp_type'] = 7;
                $where['hii_request_temp.status'] = 0;
                $where['goods_id'] = $goods_id;
                $data1 = M('RequestTemp')->where($where)->field('id,g_num,value_id')->find();
                if (!$data1) {
                    //不在临时盘点单中
                    $outdata['g_num'] = 0;
                    $outdata['value_id'] = 0;
                } else {
                    $outdata['g_num'] = $data1['g_num'];
                    $outdata['value_id'] = $data1['value_id'];
                }
            }
            $this->response(self::CODE_OK, $outdata);
        } else {
            $data0 = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
            if (!$data0) {
                $this->response(999, '该商品条码没找到！');
            }
            $where["id"] = $data0['goods_id'];
            $where["status"] = 1;
            if (!is_null($cate_id) && !empty($cate_id)) {
                $where["cate_id"] = $cate_id;
            }else{
                $where["cate_id"] = array('neq',18);
            }
            $datafind = M('Goods')->where($where)->field('id, title, sell_price, sell_online, sell_outline')->find();
            if (!$datafind) {
                $this->response(999, '该商品条码没找到！');
            } else {
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datafind['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata['goods_id'] = $datafind['id'];
                $outdata['goods_name'] = $datafind['title'];
                $outdata['bar_code'] = $data0['bar_code'];
                $outdata['sell_price'] = $datafind['sell_price'];
                $outdata['attr_value_array'] = $attr_value_array;
                $where['admin_id'] = UID;
                $where['temp_type'] = 7;
                $where['hii_request_temp.status'] = 0;
                $where['goods_id'] = $datafind['id'];
                $data1 = M('RequestTemp')->where($where)->field('id,g_num,value_id')->find();
                if (!$data1) {
                    //不在临时盘点单中
                    $outdata['g_num'] = 0;
                    $outdata['value_id'] = 0;
                } else {
                    $outdata['g_num'] = $data1['g_num'];
                    $outdata['value_id'] = $data1['value_id'];
                }
            }
            $this->response(self::CODE_OK, $outdata);
        }
    }

    /*****************************
     * 查看接口
     * 请求方式：GET
     * 请求参数：si_id 盘点单ID 必须
     * 注意：
     * 日期：2017-12-13
     */
    public function view()
    {
        $result = $this->getViewInfo();
        $this->response(self::CODE_OK, $result);
    }

    /********************
     * 新增单个临时申请
     * 请求方式：POST
     * 请求参数：goods_id 商品ID  必须
     *           g_num  盘点数量  必须
     * 注意：
     * 日期：2017-12-12
     */
    public function addSingleRequestTemp()
    {
        $goods_id = I("post.goods_id");
        $g_num = I("post.g_num");
        $remark = I("post.remark");
        $temp_type = 8;
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择要盘点的商品");
        }
        if (is_null($g_num)) {
            $this->response(0, "请填写盘点数量");
        }
        $g_price = 0;
        $RequestTempModel = M("RequestTemp");
        //查找门店所在区域ID
        $shequ_id = 0;
        $StoreModel = M("Store");
        $store_id = $this->_store_id;
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if ($this->isArrayNull($store_datas) != null) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }
        //查找商品g_price
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $datas = $WarehouseInoutViewModel->query("select ifnull(stock_price,0) as stock_price from hii_warehouse_inout_view where goods_id={$goods_id} and shequ_id={$shequ_id} limit 1  ");
        if ($this->isArrayNull($datas) != null) {
            $g_price = $datas[0]["stock_price"];
        }

        $admin_id = UID;

        $sql = "select id from hii_request_temp where admin_id={$admin_id} and store_id={$store_id} and goods_id={$goods_id} and temp_type={$temp_type} and status=0 limit 1 ";
        $datas = $RequestTempModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $RequestTempEntity = array();
            $RequestTempEntity["admin_id"] = $admin_id;
            $RequestTempEntity["store_id"] = $store_id;
            $RequestTempEntity["temp_type"] = $temp_type;
            $RequestTempEntity["goods_id"] = $goods_id;
            $RequestTempEntity["ctime"] = time();
            $RequestTempEntity["status"] = 0;
            $RequestTempEntity["g_num"] = $g_num;
            $RequestTempEntity["g_price"] = $g_price;
            $RequestTempEntity["remark"] = $remark;
            $ok = $RequestTempModel->add($RequestTempEntity);
        } else {
            $ok = $RequestTempModel->where(" id={$datas[0]["id"]} ")->limit(1)->save(array("g_num" => $g_num, "g_price" => $g_price, "remark" => $remark));
        }
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /******************
     * 清空临时申请表
     * 请求方式：POST
     * 请求参数：无
     * 日期：2017-12-12
     */
    public function clearTemp()
    {
        $temp_type = 8;
        $admin_id = UID;
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and store_id={$store_id} and temp_type={$temp_type} ")->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /**********
     * 临时盘点单添加全部商品，或者某类商品
     * 请求方式：POST
     * 请求参数：is_all  是否盘点所有商品，当is_all=1 盘点所有商品 必须
     *           cate_id 商品种类ID  非必需
     * 日期：2017-12-19
     *************/
    public function addCateRequestTemp()
    {
        $is_all = I("post.is_all");
        if ($is_all == 1) {
            $this->addRequestTempByStoreStock();
        } else {
            $this->addRequestTempByCateId();
        }
    }

    /*********************************
     * 月末盘点列表数据
     * 请求方式：GET
     * 请求参数：无
     * 日期：2018-01-02
     */
    public function monthinventory()
    {
        $datas = $this->getMonthInventoryList(true);
        $datas["show_del"] = $this->checkFunc('DelIncentory');
        $this->response(self::CODE_OK, $datas);
    }

    /******************************
     * 新增月末盘点
     * 请求方式：POST
     * 请求参数：无
     * 日期：2018-01-02
     */
    public function monthInventoryAdd()
    {
        ini_set('max_execution_time', '0');
        $store_id = $this->_store_id;
        $StoreInventoryRepository = D("StoreInventory");
        $result = $StoreInventoryRepository->monthInventoryAdd(UID, $store_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "新增成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /***********************
     * 月末盘点删除
     * 请求方式：GET
     * 请求参数：si_id 盘点单ID 必须
     * 日期：2018-01-29
     */
    private function monthInventoryDel()
    {
        $si_id = I("post.si_id");
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "请提交需要删除的盘点单ID");
        }
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        $data = $StoreInventoryModel->where(" si_id={$si_id} ")->limit(1)->select();
        if ($this->isArrayNull($data) == null) {
            $this->response(0, "不存在该盘点单");
        }
        if ($data[0]["si_status"] == 0) {
            $ok = $StoreInventoryDetailModel->where(" si_id={$si_id} ")->delete();
            if ($ok === false) {
                $this->response(0, "删除盘点子表信息失败");
            }
            $ok = $StoreInventoryModel->where(" si_id={$si_id} ")->limit(1)->delete();
            if ($ok === false) {
                $this->response(0, "删除盘点主表信息失败");
            }
            $this->response(self::CODE_OK, "删除成功");
        } else {
            $this->response(0, "无法删除该盘点单");
        }
    }


    /**********************
     * 通过商品类别添加临时盘点申请
     * 请求方式：POST
     * 请求参数：cate_id  商品类别  必须
     * 注意：
     * 日期：2017-12-12
     */
    private function addRequestTempByCateId()
    {
        $cate_id = I("post.cate_id");
        if (is_null($cate_id) || empty($cate_id)) {
            $this->response(0, "请选择要盘点的商品种类");
        }
        $store_id = $this->_store_id;
        $StoreInventoryRepository = D("StoreInventory");
        $result = $StoreInventoryRepository->addRequestTempByCateId(UID, $store_id, $cate_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, "操作失败");
        }
    }

    /**********************
     * 把所有商品添加到临时盘点申请
     * 请求方式：POST
     * 请求参数：无
     * 注意：
     * 日期：2017-12-12
     */
    private function addRequestTempByStoreStock()
    {
        $admin_id = UID;
        $store_id = $this->_store_id;
        $StoreInventoryRepository = D("StoreInventory");
        $result = $StoreInventoryRepository->addRequestTempByStoreStock($admin_id, $store_id);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, "操作失败");
        }
    }

    /*********************
     * 删除盘点临时申请接口
     * 请求方式：POST
     * 请求参数：id  临时申请ID  必须
     * 注意：
     * 日期：2017-12-12
     */
    public function delete()
    {
        $id = I("post.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要删除的临时申请单");
        }
        $admin_id = UID;
        $temp_type = 8;
        $RequestTempModel = M("RequestTemp");
        $ok = $RequestTempModel->where(" id={$id} and admin_id={$admin_id} and temp_type={$temp_type} ")->limit(1)->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::CODE_OK, "操作成功");
        }
    }

    /*******************
     * 获取临时盘点申请接口
     * 请求方式：GET
     * 请求参数：id  临时盘点申请ID  必须
     * 注意：
     * 日期：2017-12-12
     */
    public function edit()
    {
        ini_set('max_execution_time', '0');
        $id = I("get.id");
        if (is_null($id) || empty($id)) {
            $this->response(0, "请选择要编辑的临时申请单");
        }
        $admin_id = UID;
        $temp_type = 8;
        $store_id = $this->_store_id;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,G.bar_code,G.title as goods_name,G.sell_price,RT.g_num,RT.remark,GC.title as cate_name,GS.stock_num ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join (select ifnull(num,0) as stock_num,goods_id from hii_goods_store where store_id={$store_id} ) GS on GS.goods_id=RT.goods_id ";
        $sql .= "where RT.id={$id} and RT.admin_id={$admin_id} and RT.temp_type={$temp_type} limit 1 ";
        $datas = $RequestTempModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "不存在该信息");
        }
        $this->response(self::CODE_OK, $datas[0]);
    }

    /***********************
     * 审核接口
     * 请求方式：POST
     * 请求参数：si_id 盘点单ID  必须
     * 注意：
     * 日期：2017-12-14
     */
    public function check()
    {
        $pass = I("post.pass");
        if ($pass == 2) {
            $quanxian = $this->checkFunc('DelIncentory');
            if ($quanxian) {
                $this->monthInventoryDel();
            } else {
                $this->response(0, "无权删除");
            }
        } elseif ($pass == 1) {
            $si_id = I("post.si_id");
            if (is_null($si_id) || empty($si_id)) {
                $this->response(0, "请选择要审核的盘点单");
            }
            $store_id = $this->_store_id;
            $padmin_id = UID;
            $StoreInventoryRepository = D("StoreInventory");
            $result = $StoreInventoryRepository->check($padmin_id, $store_id, $si_id);
            if ($result["status"] == "200") {
                $this->response(self::CODE_OK, "操作成功");
            } else {
                $this->response(0, $result["msg"]);
            }
        }
    }

    /******************************
     * 提交临时盘点申请
     * 请求方式：POST
     * 请求参数：remark  备注  非必需
     * 注意：
     * 日期：2017-12-12
     */
    public function submitRequestTemp()
    {

        $remark = I("post.remark");
        $StoreInventoryRepository = D("StoreInventory");
        $store_id = $this->_store_id;
        $admin_id = UID;
        $result = $StoreInventoryRepository->submitRequestTemp($admin_id, $store_id, $remark);
        if($result['status'] == 1){
            $this->response(1, $result['msg']);
        }
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, "操作失败");
        }
    }

    /*****************************
     * 导出盘点单列表Excel
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     * 日期：2017-12-13
     */
    public function exportIndexListExcel()
    {
        $result = $this->getIndexList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 盘点单';
        $fname = './Public/Excel/StoreInventory_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInventoryModel();
        $printfile = $printmodel->createIndexListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /************************
     * 导出单个盘点单Excel
     * 请求方式：GET
     * 请求参数：si_id  盘点单ID  必须
     * 注意：
     * 日期：2017-12-13
     */
    public function exportViewExcel()
    {
        $result = $this->getViewInfo();
        ob_clean;
        $title = '盘点单查看';
        $fname = './Public/Excel/StoreInventoryView_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInventoryModel();
        $printfile = $printmodel->createViewExcel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /****************
     * 导出月末盘点单
     * 请求方式：GET
     * 请求参数：s_date  开始日期  非必需
     *           e_date  结束日期  非必需
     * 日期：2018-01-03
     */
    public function exportMonthInventory()
    {
        $result = $this->getMonthInventoryList(false);
        ob_clean;
        $title = $result["s_date"] . ">>>" . $result["e_date"] . ' 月末盘点单';
        $fname = './Public/Excel/StoreInventory_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreInventoryModel();
        $printfile = $printmodel->createIndexListExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*********************************
     * 修改门店盘点申请单
     * 请求方式：POST
     * 请求参数：si_id 盘点单ID  必须
     *           info_json 修改信息json 格式 [{"si_d_id":"1","g_num":"20","g_price":"2"},{si_d_id":"1","g_num":"20","g_price":"2"}] 必须
     *           remark 备注 非必须
     * 注意：
     * 日期：2017-12-13
     */
    public function updateStoreInventory()
    {
        ini_set('max_execution_time', '0');
        $si_id = I("post.si_id");
        $info_json_array = I("post.info_json");
        //print_r($info_json_array);exit;
        $remark = I("post.remark");
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "请提交盘点单号");
        }
        /*
        if ($this->isArrayNull($info_json_array) == false) {
            $this->response(0, "请提交修改信息");
        }*/
        $StoreInventoryRepository = D("StoreInventory");
        $store_id = $this->_store_id;
        $eadmin_id = UID;
        $result = $StoreInventoryRepository->updateStoreInventory($eadmin_id, $store_id, $si_id, $info_json_array, $remark);
        if ($result["status"] == "200") {
            $this->response(self::CODE_OK, "操作成功");
        } else {
            $this->response(0, $result["msg"]);
        }
    }

    /*****************
     * 获取盘点列表数据
     * @param $usePager 是否分页
     * @return mixed
     */
    private function getIndexList($usePager)
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $store_id = $this->_store_id;
        $StoreInventoryModel = M("StoreInventory");
        $can_store_id_array = $this->getCanStoreIdArray();
        $shequ_where = "";
        if (count($can_store_id_array) > 0) {
            $shequ_where .= " and ( SI.store_id in (" . implode(",", $can_store_id_array) . ") ) ";
        }

        $sql = "select SI.si_id,SI.si_sn,FROM_UNIXTIME(SI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SI.g_type,SI.g_nums,SI.remark, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,SI.si_status,SUM(SID.b_num)as b_nums,SUM(GS.num)as gs_nums, ";
        $sql .= "SUM(SID.g_num*(CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_store_inventory SI ";
        $sql .= "left join hii_store_inventory_detail SID on SID.si_id=SI.si_id ";
        $sql .= "left join hii_member M on M.uid=SI.admin_id ";
        $sql .= "left join hii_store S on S.id=SI.store_id ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id={$store_id} and GS.goods_id=SID.goods_id ";
        $sql .= "where SI.store_id={$store_id} {$shequ_where} and SI.si_type=0 and FROM_UNIXTIME(SI.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "group by SI.si_id,SI.si_sn,SI.ctime,SI.g_type,SI.g_nums,M.nickname,S.title,SI.si_status,SI.remark ";
        $sql .= "order by SI.si_id desc ";

        $data = $StoreInventoryModel->query($sql);

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
            switch ($val["si_status"]) {
                case 0: {
                    $data[$key]["si_status_name"] = "新增";
                    //如果新增显示当前的商品库存数量  如果已审核显示审核时的商品数量
                    $data[$key]['b_nums'] = $val['gs_nums'];
                };
                    break;
                case 1: {
                    $data[$key]["si_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["si_status_name"] = "已作废";
                };
                    break;
            }
        }

        $result["data"] = $this->isArrayNull($data);
        return $result;
    }

    private function getMonthInventoryList($usePager)
    {
        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];
        $store_id = $this->_store_id;
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        //判断新增盘点单如果超过24小时没有审核删除
        $time = time()-24*3600;
        $query = $StoreInventoryModel->field('si_id')->where(array('si_type'=>1,'etime'=>array('ELT',$time),'si_status'=>0,'store_id'=>$store_id))->select();
        if(!empty($query)){
            $StoreInventoryModel->startTrans();
            foreach ($query as $key=>$val){
                $delete = $StoreInventoryModel->where(array('si_id'=>$val['si_id']))->delete();
                if(empty($delete)){
                    $StoreInventoryModel->rollback();
                    $this->response(0, "删除过期月末盘点主表失败");
                }
                $delete = $StoreInventoryDetailModel->where(array('si_id'=>$val['si_id']))->delete();
                if(empty($delete)){
                    $StoreInventoryModel->rollback();
                    $this->response(0, "删除过期月末盘点子表失败");
                }
            }
            $StoreInventoryModel->commit();
        }
        $sql = "select SI.si_id,SI.si_sn,FROM_UNIXTIME(SI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SI.g_type,SI.g_nums,SI.remark, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,SI.si_status,SUM(SID.b_num)as b_nums,SUM(GS.num)as gs_nums,";
        $sql .= "SUM((CASE WHEN GS.price is not null and GS.price>0 THEN GS.price WHEN GS.shequ_price is not null and GS.shequ_price>0 THEN GS.shequ_price ELSE G.sell_price END )*SID.g_num) as g_amounts ";
        $sql .= "from hii_store_inventory SI ";
        $sql .= "left join hii_store_inventory_detail SID on SID.si_id=SI.si_id ";
        $sql .= "left join hii_member M on M.uid=SI.admin_id ";
        $sql .= "left join hii_store S on S.id=SI.store_id ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "left join hii_goods_store GS on GS.goods_id=SID.goods_id and GS.store_id={$store_id} ";
        $sql .= "where SI.store_id={$store_id} and SI.si_type=1 and FROM_UNIXTIME(SI.ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $sql .= "group by SI.si_id,SI.si_sn,SI.ctime,SI.g_type,SI.g_nums,M.nickname,S.title,SI.si_status,SI.remark ";
        $sql .= "order by SI.si_id desc ";

        $data = $StoreInventoryModel->query($sql);

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
            switch ($val["si_status"]) {
                case 0: {
                    $data[$key]["si_status_name"] = "新增";
                    $data[$key]['b_nums'] = $val['gs_nums']; //如果新增显示当前的商品库存数量  如果已审核显示审核时的商品数量
                    /*$goods_id = M("StoreInventoryDetail")->field("goods_id")->where(array('si_id'=>$val['si_id']))->select();
                    $data[$key]['b_nums'] = M("GoodsStore")->where(array('store_id'=>$val['store_id'],'goods_id'=>array("in",array_column($goods_id,"goods_id"))))->sum('num');*/
                };
                    break;
                case 1: {
                    $data[$key]["si_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["si_status_name"] = "已作废";
                };
                    break;
            }
        }

        $result["data"] = $this->isArrayNull($data);
        return $result;
    }

    /***********************
     * 获取盘点单详细信息
     */
    private function getViewInfo()
    {
        $is_disable = I("get.is_disable");//is_disable【是否显示0库存：0=默认不显示，1=显示】
        if (is_null($is_disable) || empty($is_disable)) {
            $is_disable = 0;
        }
        $cate_id = I("get.cate_id");
        $si_id = I("get.si_id");
        if (is_null($si_id) || empty($si_id)) {
            $this->response(0, "请选择要查看的盘点单");
        }
        $store_id = $this->_store_id;
        $shequ_id = 0;
        $StoreModel = M("Store");
        $store_datas = $StoreModel->where(" id={$store_id} ")->limit(1)->select();
        if ($this->isArrayNull($store_datas) != null) {
            $shequ_id = $store_datas[0]["shequ_id"];
        }
        $StoreInventoryModel = M("StoreInventory");
        $StoreInventoryDetailModel = M("StoreInventoryDetail");
        //查询主表信息
        $sql = "select SI.si_sn,FROM_UNIXTIME(SI.ctime,'%Y-%m-%d %H:%i:%s') as ctime,SI.g_type,SI.g_nums,SI.remark,SI.si_type, ";
        $sql .= "M.nickname as admin_nickname,S.title as store_name,SI.si_status ";
        $sql .= "from hii_store_inventory SI ";
        $sql .= "left join hii_store_inventory_detail SID on SID.si_id=SI.si_id ";
        $sql .= "left join hii_member M on M.uid=SI.admin_id ";
        $sql .= "left join hii_store S on S.id=SI.store_id ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "where SI.si_id={$si_id} and SI.store_id={$store_id} ";
        $sql .= "group by SI.si_sn,SI.ctime,SI.g_type,SI.g_nums,M.nickname,S.title,SI.si_status,SI.remark ";

        $datas = $StoreInventoryModel->query($sql);
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "该信息不存在");
        }
        $main = $datas[0];

        switch ($main["si_status"]) {
            case 0: {
                $main["si_status_name"] = "新增";
            };
                break;
            case 1: {
                $main["si_status_name"] = "已审核";
            };
                break;
            case 2: {
                $main["si_status_name"] = "已作废";
            };
                break;
        }

        //sys_price 系统价格 store_price 门店价格 shequ_price 区域价
        $sql = "select SID.si_d_id,SID.goods_id,G.title as goods_name,G.cate_id,ifnull(G.bar_code,(select bar_code from hii_attr_value where goods_id=SID.goods_id limit 1))bar_code,SID.g_num,SID.b_num,SID.remark, ";
        $sql .= "ifnull(GS.num,0) as stock_num,'' as stock_price,ifnull(SID.g_price,0) as g_price,SID.audit_mark, ";
        $sql .= "GC.id as cate_id,GC.title as cate_name,ifnull(SID.b_num,0) as b_num,G.sell_price as sys_price,GS.price as store_price,GS.shequ_price as shequ_price  ";
        $sql .= "from hii_store_inventory_detail SID ";
        $sql .= "left join hii_store_inventory SI on SI.si_id=SID.si_id ";
        $sql .= "left join hii_goods G on G.id=SID.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store GS on GS.store_id=SI.store_id and GS.goods_id=SID.goods_id ";
        $sql .= "left join hii_warehouse_inout_view WIV on WIV.goods_id=SID.goods_id and WIV.shequ_id={$shequ_id} ";
        $sql .= "where SID.si_id={$si_id} ";
        if ($main["si_status"] == 1) {
            //$sql .= " and SID.b_num<>SID.g_num ";
            $sql .= " and (SID.b_num>0 or SID.g_num>0 ) ";
        }
        if (!is_null($is_disable) && $is_disable == 0 && $main["si_status"] != 1) {
            $sql .= " and GS.num > 0 ";
        }
        if (!is_null($cate_id) && !empty($cate_id)) {
            $sql .= " and G.cate_id={$cate_id} ";
        }

        if (I("get.showsql") == true) {
            //echo $sql;exit;
        }

        $sql .= " order by SID.goods_id asc ";

        $list = $StoreInventoryDetailModel->query($sql);

        if ($main["si_status"] == 1) {
            foreach ($list as $key => $val) {
                $list[$key]["io_num"] = $val["g_num"] - $val["b_num"];
            }
        } else {
            foreach ($list as $key => $val) {
                foreach ($list as $key => $val) {
                    $list[$key]["io_num"] = $val["g_num"] - $val["stock_num"];
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

        $main["g_amounts"] = $g_amounts;
        $result["maindata"] = $main;
        $result["list"] = $this->isArrayNull($list);
        if ($is_disable == 1) {
            $result["cate"] = M("GoodsCate")->query("select GC.id as cate_id,GC.title as cate_name 
from hii_store_inventory SI
left join hii_store_inventory_detail SID on SID.si_id=SI.si_id
left join hii_goods G on G.id=SID.goods_id
left join hii_goods_cate GC on GC.id=G.cate_id
where SI.si_id={$si_id}
GROUP BY GC.id,GC.title");
        } else {
            $result["cate"] = M("GoodsCate")->query("select GC.id as cate_id,GC.title as cate_name 
from hii_store_inventory SI
left join hii_store_inventory_detail SID on SID.si_id=SI.si_id
left join hii_goods G on G.id=SID.goods_id
left join hii_goods_store GS on GS.goods_id=SID.goods_id and GS.store_id={$store_id}
left join hii_goods_cate GC on GC.id=G.cate_id
where SI.si_id={$si_id} and GS.num>0
GROUP BY GC.id,GC.title");
        }
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