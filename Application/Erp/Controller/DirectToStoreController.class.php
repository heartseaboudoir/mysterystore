<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-11
 * Time: 17:45
 * 仓库直接申请发货到门店
 */

namespace Erp\Controller;

use Think\Controller;

class DirectToStoreController extends AdminController
{

    private $can_store_id_array = array();
    private $can_warehouse_id_array = array();
    private $can_supply_id_array = array();

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_warehouse();

        /********************* 可以查看的门店 仓库 供应商 start ****************************************************************/
        $shequ = implode(',', $_SESSION['can_shequs']);
        $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
        if ($store) {
            //$this->storewhere = " And store_id in (" . implode(',', array_column($store, 'id')) . ")";
            $this->can_store_id_array = array_column($store, "id");
        }
        $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
        if ($warehouse) {
            //$this->warehousewhere = " And warehosue_id in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            $this->can_warehouse_id_array = array_column($warehouse, "w_id");
        }
        $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
        if ($supply) {
            //$this->supplywhere = " And supply_id in (" . implode(',', array_column($warehouse, 's_id')) . ")";
            $this->can_supply_id_array = array_column($supply, "s_id");
        }
        /********************* 可以查看的门店 仓库 供应商 end ****************************************************************/

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
        $temp_type = 11;//门店返仓申请
        $ctime = time();
        $status = 0;//临时存在
        $b_n_num = 0;//箱规
        $b_num = 0;//箱数
        $b_price = 0;//每箱价格
        $g_num = null;//申请数量
        $g_price = 0;//临时采购价
        $remark = "";//备注

        /*******检测提交数据***************/
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

        //获取仓库所在区域
        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M("Warehouse");
        $shequ_id = 0;
        $warehouse_datas = $WarehouseModel->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        if ($this->isArrayNull($warehouse_datas) != null) {
            $shequ_id = $warehouse_datas[0]["shequ_id"];
        }
        //获取g_price
        $WarehouseInoutViewModel = M("WarehouseInoutView");
        $tmp = $WarehouseInoutViewModel->field(" ifnull(stock_price,0) as g_price ")->where(" goods_id={$goods_id} and shequ_id={$shequ_id} ")->limit(1)->select();
        if ($this->isArrayNull($tmp) != null) {
            $g_price = $tmp[0]["g_price"];
        }
        $warehouse_gnum = M('WarehouseStock')->where(array('w_id'=>$warehouse_id,'goods_id'=>$goods_id,'value_id'=>$value_id))->getField('num');
        if($warehouse_gnum == 0){
            $this->response(0, "商品{$goods_id}库存为0 不允许发货");
        }
        $RequestTempModel = M("RequestTemp");
        //如果$temp_id临时申请表id不未空 按id删除后重新生成
        if(!empty($temp_id)){
            //更新
            $saveData["goods_id"] = $goods_id;
            $saveData["remark"] = $remark;
            $saveData["g_price"] = $g_price;
            $saveData["g_num"] = $g_num;
            $saveData["value_id"] = $value_id;
            $result = $RequestTempModel->where(" id={$temp_id} ")->save($saveData);
            if ($result === false) {
                $this->response(0, $RequestTempModel->getError());
            } else {
                //判断是否有重复商品属性如果有删除一个
                $RequestTempModel->where(array('id'=>array('NEQ',$temp_id),'admin_id'=>$admin_id,'warehouse_id'=>$warehouse_id,'status'=>$status,'goods_id'=>$goods_id,'temp_type'=>$temp_type,'value_id'=>$value_id))->delete();
                $list = $this->getTempList();
                $this->response(self::CODE_OK, $list);
            }

        }else {

            $where = array();
            $where["admin_id"] = $admin_id;
            $where["hii_request_temp.status"] = $status;
            $where["goods_id"] = $goods_id;
            $where["temp_type"] = $temp_type;
            $where["value_id"] = $value_id;
            $where["warehouse_id"] = $warehouse_id;
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
                    $list = $this->getTempList();
                    $this->response(self::CODE_OK, $list);
                }
            } else {
                //新增
                $data["admin_id"] = $admin_id;
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
                $data["warehouse_id"] = $warehouse_id;
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
        $temp_type = 11;//门店返仓申请
        $admin_id = UID;
        $RequestTempModel = M("RequestTemp");
        $ok = $RequestTempModel->where(" admin_id={$admin_id} and temp_type={$temp_type} ")->delete();
        if ($ok === false) {
            $this->response(0, "操作失败");
        }
        $this->response(self::CODE_OK, "操作成功");
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
        $temp_type = 11;
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
        $temp_type = 11;
        $admin_id = UID;
        $RequestTempModel = M("RequestTemp");
        $sql = "select RT.id,RT.goods_id,RT.g_num,G.title as goods_name,G.bar_code,RT.remark,RT.value_id ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "where RT.id={$id} and RT.admin_id={$admin_id} and RT.temp_type={$temp_type} order by RT.id desc limit 1 ";
        $datas = $RequestTempModel->query($sql);
        $attrValueModel = M('AttrValue');
        if ($this->isArrayNull($datas) == null) {
            $this->response(0, "无法编辑该申请");
        } else {
            $attr_value_array = $attrValueModel->where(array('goods_id'=>$datas[0]['goods_id'],'status'=>array('neq',2)))->select();
            if(empty($attr_value_array)){
                $attr_value_array = array();
            }
            $datas[0]['attr_value_array'] = $attr_value_array;
            $this->response(self::CODE_OK, $datas[0]);
        }
    }

    /*************
     *提交申请接口
     * 请求方式：POST
     * 请求参数：无
     * 日期：2018-01-11
     ************************/
    public function submitRequestTemp()
    {
        $DirectToStoreRepository = D("DirectToStore");
        $store_id = I("post.store_id");
        $remark = I("post.remark");
        if (is_null($store_id) || empty($store_id)) {
            $this->response(0, "请选择门店");
        }
        $admin_id = UID;
        $warehouse_id = $this->_warehouse_id;
        $result = $DirectToStoreRepository->submitRequestTemp($admin_id, $store_id, $warehouse_id, $remark);
        if ($result["status"] == "0") {
            $this->response(0, $result["msg"]);
        } else {
            $this->response(self::CODE_OK, "提交成功");
        }
    }


    /***************
     * 获取门店列表接口
     * 请求方式：GET
     */
    public function storelist()
    {
        $StoreModel = M("Store");
        $warehouse_id = $this->_warehouse_id;
        $where = " shequ_id=(select shequ_id from hii_warehouse where `w_id`={$warehouse_id} limit 1 ) ";
        if (count($this->can_store_id_array) > 0) {
            $where .= " and id in (" . implode(",", $this->can_store_id_array) . ") ";
        }
        $list = $StoreModel->field(" id,title as name ")->where($where)->order(" id asc ")->select();
        $this->response(self::CODE_OK, $list);
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
        $temp_type = 11;
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


    private function getTempList()
    {
        $admin_id = UID;
        $temp_type = 11;
        $status = 0;
        $warehouse_id = $this->_warehouse_id;
        $RequestTempModel = M("RequestTemp");
        $GoodsStoreModel = M("GoodsStore");
        $sql = "select RT.id,RT.goods_id,FROM_UNIXTIME(RT.ctime,'%Y-%m-%d %H:%i:%s') as ctime,G.title as goods_name,RT.remark, ";
        $sql .= "ifnull(AV.bar_code,G.bar_code)bar_code,GC.title as cate_name,G.sell_price,RT.g_num,WS.num as stock_num,AV.value_id,AV.value_name ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_goods G on G.id=RT.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_warehouse_stock WS on RT.value_id=WS.value_id ";
        $sql .= "left join hii_attr_value AV on AV.value_id=RT.value_id ";
        $sql .= "where RT.admin_id={$admin_id} and RT.temp_type={$temp_type} and RT.status={$status} and WS.w_id={$warehouse_id} order by RT.id desc ";
        //echo $sql;exit;
        $list = $RequestTempModel->query($sql);
        //读取区域价
        $GoodsStoreModel = M("GoodsStore");
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];
        foreach ($list as $key => $val) {
            $sql = "select GS.shequ_price ";
            $sql .= "from hii_goods_store GS ";
            $sql .= "where GS.goods_id={$val["goods_id"]} and GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) ";
            $sql .= "group by GS.shequ_price ";
            $tmp_data = $GoodsStoreModel->query($sql);
            if (!is_null($tmp_data) && !empty($tmp_data) && count($tmp_data) > 0 && $tmp_data[0]["shequ_price"] > 0) {
                $list[$key]["sell_price"] = $tmp_data[0]["shequ_price"];
            }
        }

        return $this->isArrayNull($list);
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

}
