<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-22
 * Time: 17:24
 */

namespace Erp\Controller;

use Think\Controller;

class WarehouseAssignmentApplicationController extends AdminController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_warehouse();
    }

    /******************************************
     * 获取临时申请对应仓库库存接口
     * 请求方式：GET
     * 请求参数：warehouse_id  发货仓库ID  必须
     * 日期：2017-12-22
     */
    public function getWarehouseStockNumByRequestTempData()
    {
        $warehouse_id = I("get.warehouse_id");
        if (is_null($warehouse_id) || empty($warehouse_id)) {
            $this->response(0, "请选择发货仓库");
        }
        $RequestTempModel = M("RequestTemp");
        $admin_id = UID;

        $sql = "select RT.goods_id,round(ifnull(WS.num,0)) as stock_num ";
        $sql .= "from hii_request_temp RT ";
        $sql .= "left join hii_warehouse_stock WS on WS.value_id=RT.value_id and WS.w_id={$warehouse_id} ";
        $sql .= "where RT.admin_id={$admin_id} and RT.warehouse_id={$this->_warehouse_id} and RT.temp_type=3 and RT.status=0 ";
        $sql .= "order by RT.ctime asc ";


        $list = $RequestTempModel->query($sql);

        //echo $sql; exit;
        $this->response(self::CODE_OK, $list);
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

}