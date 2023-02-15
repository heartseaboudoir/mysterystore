<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-02-26
 * Time: 15:15
 */

namespace Erp\Controller;

use Think\Controller;

class GoodsInOutRecordController extends AdminController
{
    /********************************
     * 0:门店销售类型
     * 1-4：仓库出入库单据类型
     * 5-8：门店出入库单据类型
     ***************************/
    private $s_type_array = array(
        0 => array(
            0 => "门店销售"
        ),
        1 => array(
            0 => "采购",
            1 => "门店退货",
            2 => "仓库调拨",
            3 => "盘盈",
            4 => "门店返仓",
            5 => "其他"
        ),
        2 => array(
            0 => "仓库调拨",
            1 => "门店申请",
            3 => "盘亏",
            4 => "其他"
        ),
        3 => array(
            0 => "仓库退货",
            1 => "盘亏",
            2 => "其他",
            4 => "门店退货"
        ),
        4 => array(
            0 => "仓库退货",
            1 => "盘亏",
            2 => "其他",
            4 => "门店退货"
        ),
        5 => array(
            0 => "仓库发货",
            1 => "门店调拨",
            2 => "盘盈",
            3 => "其他",
            4 => "采购",
            5 => "寄售"
        ),
        6 => array(
            0 => "仓库调拨",
            1 => "门店调拨",
            3 => "盘亏",
            4 => "其他",
            5 => "寄售"
        ),
        7 => array(
            0 => "仓库发货",
            1 => "门店调拨",
            2 => "盘亏",
            3 => "商品过期",
            4 => "其他",
            5 => "门店返仓"
        ),
        8 => array(
             0 => "仓库发货",
            1 => "门店调拨",
            2 => "盘亏",
            3 => "商品过期",
            4 => "其他",
            5 => "门店返仓"
        ),
        9 => array(
            0 => "供应商发货",
            1 => "退货给供应商",
        ),
        10 => array(
            0 => "门店返仓",
        )
    );

    private $lx = array(
        array("id" => "0", "name" => "门店销售"),
        array("id" => "1", "name" => "仓库入库"),
        array("id" => "2", "name" => "仓库出库"),
        array("id" => "3", "name" => "门店入库"),
        array("id" => "4", "name" => "门店出库"),
        array("id" => "5", "name" => "供应商入库"),
        array("id" => "6", "name" => "供应商出库")
    );

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
    }

    /*********************
     * 获取商品流水
     * 请求方式：POST
     * 请求参数：area  只获取区域相关仓库门店信息  非必须
     *
     * 日期：2018-02-26
     */
    public function index()
    {
        $area = I("get.area");
        $result = array();
        $result = $this->getAreaWithWarehouseAndStore();
        //echo "<pre>";print_r($result);echo "<pre>";exit;
        $all = I("get.all");
        if ($all == 0) {
            $dates = $this->getDates();
            $result["s_date"] = $dates["s_date"];
            $result["e_date"] = $dates["e_date"];
            $result["warehouse"] = 0;
            $result["store"] = 0;
            $result["supply"] = 0;
            $this->response(self::CODE_OK, $result);
        } else {
            $result = $this->getIndexList(true, $result);
            $this->response(self::CODE_OK, $result);
        }
    }

    /**********************
     * 库存批次对比情况
     * 请求方式：GET
     * 请求参数：shequ_id     社区ID     必须
     *           goods_name   商品名称   非必须
     */
    public function stock_condition()
    {
        $ShequModel = M("Shequ");
        $result = array();
        $shequ_id = I("get.shequ_id");
        if (!is_null($shequ_id) && !empty($shequ_id)) {
            $result = $this->getStockCondition(true);
        }
        //新增获取社区方法  zzy
        $shequ = $this->__member_store_shequ();
        $shequ = implode(',', $shequ);
        $result["shequ"] = $ShequModel->query(" select id as shequ_id,title as shequ_name from hii_shequ where id in ({$shequ}) order by id ASC ");
        $this->response(self::CODE_OK, $result);
    }

    /**
     * 批次修改
     *shequ_id 区域id
     * act   all  批量修改
     *id 商品id
     * num  批次相差数量
     *           goods_name   商品名称   非必须
     */
    public function save_stock_condition(){
        $shequ_id = I('shequ_id',0,'intval');
        $num = I('num',0,'intval');
        $goods_id = I('id',0,'intval');
        $act_all = I('act','','trim');
        if(!$shequ_id){
            $this->response(999,'没有区域id');
        }
        if($act_all){
                if($act_all != 'all'){
                    $this->response(999,'批量修改失败');
                }
            $result = $this->getStockCondition(false);
        }else{
            if(!$goods_id){
                $this->response(999,'没有商品id');
            }
            if(!$num){
                $this->response(self::CODE_OK,'成功');
            }
        }

        //批量修改
        if($act_all){
            $result = $result['data'];
        }else{
            $result = array();
            $result[0] = array('goods_id'=>$goods_id,'b_num'=>$num,);

        }
        foreach($result as $key=>$val){
            if($val['b_num'] == 0){
                continue;
            }
            $num = $val['b_num'];
            //判断 如果num 为 负数就减批次  如果为正数就加批次
            $warehouseInoutModel = M('WarehouseInout');
            if($num > 0){
                $warehouse_id = M('Warehouse')->where(array('shequ_id'=>$shequ_id,'w_type'=>0))->find();
                $warehouseInout_info = $warehouseInoutModel->where(array('shequ_id'=>$shequ_id,'goods_id'=>$val['goods_id']))->order('ctime desc')->find();
                if(empty($warehouseInout_info)){
                    $this->response(999,'没有批次入库价格');
                }
                /************新增批次***********/
                $array = array();
                $array['goods_id'] = $val['goods_id'];
                $array['innum'] = $num;
                $array['inprice'] = $warehouseInout_info['inprice'];
                $array['num'] = $num;
                $array['ctime'] = time();
                $array['ctype'] = 1;
                $array['shequ_id'] = $shequ_id;
                $array['endtime'] = $warehouseInout_info['endtime'];
                $array['warehouse_id'] = $warehouse_id['w_id'];
                if($warehouseInoutModel->add($array) == false){
                    $this->response(999,'增加批次失败');
                }
            }elseif($num < 0){
                $num = abs($num);
                /************减库存批次*********/
                //盘亏数量减掉【仓库】入库批次的批次数量 先进先出。
                $g_num_int = $num;
                $WarehouseInoutData = $warehouseInoutModel->where('goods_id = ' .$val['goods_id'] .' and num>0 and shequ_id = '.$shequ_id.' and warehouse_id !=0 ')->order('ctime asc')->select();
                foreach($WarehouseInoutData as $kinout=>$vinout){
                    if($g_num_int <= 0){
                        break;
                    }
                    if($g_num_int >= $vinout['num']){
                        $g_num_int -= $vinout['num'];
                        $vinout['enum'] += $vinout['num'];
                        $vinout['num'] = 0;
                        $vinout['outnum'] = $vinout['innum'];
                    }else{
                        $vinout['num'] -= $g_num_int;
                        $vinout['outnum'] += $g_num_int;
                        $vinout['enum'] += $g_num_int;
                        $g_num_int = 0;
                    }
                    $array = array();
                    $array['inout_id'] = $vinout['inout_id'];
                    $array['outnum'] = $vinout['outnum'];
                    $array['num'] = $vinout['num'];
                    $array['etime'] = time();
                    $array['etype'] = 2;
                    $array['enum'] = $vinout['enum'];
                    $array['e_no'] = $vinout['e_no'] + 1;
                    if($warehouseInoutModel->save($array) === false){
                        $this->response(999,'减批次失败');
                    }
                }
                //盘亏数量减掉【本社区其他门店】入库批次的批次数量 先进先出。
                if($g_num_int > 0){
                    $WarehouseInoutData = $warehouseInoutModel->where('goods_id = ' .$val['goods_id'] .' and num>0 and shequ_id = '.$shequ_id.' and store_id != 0 ')->order('ctime asc')->select();
                    foreach($WarehouseInoutData as $kinout=>$vinout){
                        if($g_num_int <= 0){
                            break;
                        }
                        if($g_num_int >= $vinout['num']){
                            $g_num_int -= $vinout['num'];
                            $vinout['enum'] += $vinout['num'];
                            $vinout['num'] = 0;
                            $vinout['outnum'] = $vinout['innum'];
                        }else{
                            $vinout['num'] -= $g_num_int;
                            $vinout['outnum'] += $g_num_int;
                            $vinout['enum'] += $g_num_int;
                            $g_num_int = 0;
                        }
                        $array = array();
                        $array['inout_id'] = $vinout['inout_id'];
                        $array['outnum'] = $vinout['outnum'];
                        $array['num'] = $vinout['num'];
                        $array['etime'] = time();
                        $array['etype'] = 2;
                        $array['enum'] = $vinout['enum'];
                        $array['e_no'] = $vinout['e_no'] + 1;
                        if($warehouseInoutModel->save($array) === false){
                            $this->response(999,'减批次失败');
                        }
                    }
                }
            }
        }

        $this->response(self::CODE_OK,'成功');
    }
    /**********************
     * 导出库存批次对比情况
     * 请求方式：GET
     * 请求参数：shequ_id     社区ID     必须
     *           goods_name   商品名称   非必须
     */
    public function export_stock_condition()
    {
        $result = $this->getStockCondition(false);
        ob_clean;
        $title = '库存批次对比单';
        $fname = './Public/Excel/Stock_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\GoodsInOutRecordModel();
        $printfile = $printmodel->createStockConditionExcel($result["data"], $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }


    private function getStockCondition($usePager)
    {
        $shequ_id = I("get.shequ_id");
        $goods_name = I("get.goods_name");
        $sql = "
            select A.id as goods_id,A.title as goods_name,ifnull(B.num,0) as store_num,floor(ifnull(B1.num,0)) as warehouse_num,
            floor(ifnull(D.store_zt_num,0)) as store_zt_num,floor(ifnull(E.warehouse_zt_num,0)) as warehouse_zt_num,
						ifnull(C.num,0) as inout_num,ifnull(C.ginprice,0) as ginprice
            from hii_goods A
            left join (
                select goods_id,sum(num) as num from hii_goods_store
                where num > 0 and store_id in (select id as store_id from hii_store where shequ_id = {$shequ_id})
                group by goods_id
            ) B on A.id = B.goods_id
            left join (
                select goods_id,sum(num) as num from hii_warehouse_stock
                where w_id in (select w_id from hii_warehouse where shequ_id = {$shequ_id})
                group by goods_id
            ) B1 on A.id = B1.goods_id
            left join (
                select goods_id,sum(num) as num,ROUND(sum(num*inprice)/sum(num),2) as ginprice from hii_warehouse_inout where shequ_id = {$shequ_id}
                group by goods_id
            ) C on A.id=C.goods_id
            left join (
                select goods_id,sum(num) as store_zt_num from hii_store_in_not_check_view where shequ_id = {$shequ_id}
                group by goods_id
            ) D on A.id=D.goods_id
            left join (
                select goods_id,sum(num) as warehouse_zt_num from hii_warehouse_in_not_check_view where shequ_id = {$shequ_id}
                group by goods_id
            ) E on A.id=E.goods_id
            where 
            ";
        if (!is_null($goods_name) && !empty($goods_name)) {
            $goods_name = trim($goods_name);
            $sql .= " A.title like '%{$goods_name}%' and ( B.num > 0 or B1.num > 0 or C.num > 0 or D.store_zt_num > 0 or E.warehouse_zt_num > 0 ) ";
        } else {
            $sql .= " B.num > 0 or B1.num > 0 or C.num > 0 or D.store_zt_num > 0 or E.warehouse_zt_num > 0 ";
        }

        if (I("get.showsql") == true) {
            echo $sql;
            exit;
        }

        $data = M()->query($sql);

        //分页
        if ($usePager) {
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            //$Page->parameter["warehouse"] = $warehouse_ids;
            //$Page->parameter["store"] = $store_ids;
            //$Page->parameter["supply"] = $supply_ids;
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿

            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }

        foreach ($data as $key => $val) {
            $data[$key]["b_num"] = ($val["store_num"] + $val["warehouse_num"] + $val["store_zt_num"] + $val["warehouse_zt_num"]) - $val["inout_num"];
        }

        $result["data"] = $this->isArrayNull($data);
        return $result;
    }


    private
    function getIndexList($usePager, $result)
    {
        $goods_name = I("get.goods_name");
        $goods_id = I("get.goods_id");
        if (empty($goods_name) && empty($goods_id)) {
            //$this->response(0, "请填写商品名称或商品ID");
        }
        if (!empty($goods_id) && !is_int($goods_id)) {
            //$this->response(0, "商品ID有误");
        }

        $dates = $this->getDates();
        $s_date = $dates["s_date"];
        $e_date = $dates["e_date"];

        $warehouse_ids = I("get.warehouse");
        $store_ids = I("get.store");
        $supply_ids = I("get.supply");
        $cates = I("get.cate_id");


        /*********************
         * 查询条件集合
         * system_sell:销售查询条件
         * purchase:采购入库
         * purchase_out:采购退货
         * store_in_stock:门店入库
         * store_out_stock:门店出库
         * store_other_out_in: 仓库拒绝返仓 / 门店调拨拒绝 / 门店发货拒绝
         * store_other_out_out:退货出库
         * warehouse_in_stock:仓库入库
         * warehouse_in_stock_by_return:返仓入库
         * warehouse_out_stock:仓库出库
         * warehouse_other_out_in:仓库被退货入库
         * warehouse_other_out_out:仓库退货出库
         ********************/
        $where["system_sell"] = " O.pay_status=2 and O.pay_time>0 and FROM_UNIXTIME(O.pay_time,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $where["purchase"] = " P.p_status=1 and FROM_UNIXTIME(P.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["purchase_out"] = " PO.p_o_status=1 and  FROM_UNIXTIME(PO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["store_in_stock"] = " SIS.s_in_s_status=1 and FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["store_out_stock"] = " SOS.s_out_s_status=1 and FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $where["store_other_out_in"] = " SOO.s_o_out_status=1 and FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["store_other_out_out"] = " SOO.s_o_out_status=1 and FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["store_out_back"] = " sb.s_back_status=1 and FROM_UNIXTIME(sb.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";

        $where["warehouse_in_stock"] = " WIS.w_in_s_status=1 and WIS.w_in_s_type<>4 and  FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $where["warehouse_in_stock_by_return"] = " WIS.w_in_s_status=1 and WIS.w_in_s_type=4 and  FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}'  ";
        $where["warehouse_out_stock"] = " WOS.w_out_s_status=1 and FROM_UNIXTIME(WOS.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["warehouse_other_out_in"] = " WOO.w_o_out_status=1 and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $where["warehouse_other_out_out"] = " WOO.w_o_out_status=1 and (WOO.w_o_out_type=0 or WOO.w_o_out_type=4) and FROM_UNIXTIME(WOO.ptime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";

        if (!empty($goods_id) && $goods_id != 0) {
            $where["system_sell"] .= " and OD.d_id={$goods_id} ";
            $where["purchase"] .= " and PD.goods_id={$goods_id} ";
            $where["purchase_out"] .= " and POD.goods_id={$goods_id} ";
            $where["store_in_stock"] .= " and SISD.goods_id={$goods_id} ";
            $where["store_out_stock"] .= " and SOSD.goods_id={$goods_id} ";
            $where["store_other_out_in"] .= " and SOOD.goods_id={$goods_id} ";
            $where["store_other_out_out"] .= " and SOOD.goods_id={$goods_id} ";
            $where["store_out_back"] .= " and sbd.goods_id={$goods_id} ";

            $where["warehouse_in_stock"] .= " and WISD.goods_id={$goods_id} ";
            $where["warehouse_in_stock_by_return"] .= " and WISD.goods_id={$goods_id} ";
            $where["warehouse_out_stock"] .= " and WOSD.goods_id={$goods_id} ";
            $where["warehouse_other_out_in"] .= " and WOOD.goods_id={$goods_id} ";
            $where["warehouse_other_out_out"] .= " and WOOD.goods_id={$goods_id} ";
        } else {
            if (!empty($goods_name)) {
                $where["system_sell"] .= " and G.title like '%{$goods_name}%' ";
                $where["purchase"] .= " and G.title like '%{$goods_name}%' ";
                $where["purchase_out"] .= " and G.title like '%{$goods_name}%' ";
                $where["store_in_stock"] .= " and G.title like '%{$goods_name}%' ";
                $where["store_out_stock"] .= " and G.title like '%{$goods_name}%' ";
                $where["store_other_out_in"] .= " and G.title like '%{$goods_name}%' ";
                $where["store_other_out_out"] .= " and G.title like '%{$goods_name}%' ";
                $where["store_out_back"] .= " and G.title like '%{$goods_name}%' ";

                $where["warehouse_in_stock"] .= " and G.title like '%{$goods_name}%' ";
                $where["warehouse_in_stock_by_return"] .= " and G.title like '%{$goods_name}%' ";
                $where["warehouse_out_stock"] .= " and G.title like '%{$goods_name}%' ";
                $where["warehouse_other_out_in"] .= " and G.title like '%{$goods_name}%' ";
                $where["warehouse_other_out_out"] .= " and G.title like '%{$goods_name}%' ";
            }
        }
        if (!empty($cates)) {
            $where["system_sell"] .= " and GC.id in ({$cates}) ";
            $where["purchase"] .= " and GC.id in ({$cates}) ";
            $where["purchase_out"] .= " and GC.id in ({$cates}) ";
            $where["store_in_stock"] .= " and GC.id in ({$cates}) ";
            $where["store_out_stock"] .= " and GC.id in ({$cates}) ";
            $where["store_out_back"] .= " and GC.id in ({$cates}) ";
            $where["store_other_out_in"] .= " and GC.id in ({$cates}) ";
            $where["store_other_out_out"] .= " and GC.id in ({$cates}) ";
            $where["warehouse_in_stock"] .= " and GC.id in ({$cates}) ";
            $where["warehouse_in_stock_by_return"] .= " and GC.id in ({$cates}) ";
            $where["warehouse_out_stock"] .= " and GC.id in ({$cates}) ";
            $where["warehouse_other_out_in"] .= " and GC.id in ({$cates}) ";
            $where["warehouse_other_out_out"] .= " and GC.id in ({$cates}) ";
        }

        if (!empty($warehouse_ids)) {
            if(!empty($store_ids)){
                $where["purchase"] .= " and P.warehouse_id in ({$warehouse_ids}) ";
                $where["purchase_out"] .= " and PO.warehouse_id in ({$warehouse_ids}) ";
                $where["store_in_stock"] .= " and (SIS.warehouse_id in ({$warehouse_ids}) ";
                $where["store_out_stock"] .= " and (SOS.warehouse_id in ({$warehouse_ids}) ";
                $where["store_other_out_in"] .= " and (SOO.warehouse_id in ({$warehouse_ids}) ";

                $where["store_out_back"] .= " and (sb.warehouse_id in ({$warehouse_ids}) ";

                $where["store_other_out_out"] .= " and (SOO.warehouse_id in ({$warehouse_ids}) ";
                $where["warehouse_in_stock"] .= " and WIS.warehouse_id in ({$warehouse_ids}) ";
                $where["warehouse_in_stock_by_return"] .= " and WIS.warehouse_id in ({$warehouse_ids}) ";

                //$where["warehouse_out_stock"] .= " and (WOS.warehouse_id1 in ({$warehouse_ids}) or WOS.warehouse_id2 in ({$warehouse_ids})) ";
                $where["warehouse_out_stock"] .= " and WOS.warehouse_id2 in ({$warehouse_ids}) ";
                //$where["warehouse_other_out_in"] .= " and (WOO.warehouse_id in ({$warehouse_ids}) or WOO.warehouse_id2 in ({$warehouse_ids})) ";
                $where["warehouse_other_out_in"] .= " and WOO.warehouse_id2 in ({$warehouse_ids}) ";
                //$where["warehouse_other_out_out"] .= " and (WOO.warehouse_id in ({$warehouse_ids}) or WOO.warehouse_id2 in ({$warehouse_ids})) ";
                $where["warehouse_other_out_out"] .= " and WOO.warehouse_id in ({$warehouse_ids}) ";
            }else{
                $where["purchase"] .= " and P.warehouse_id in ({$warehouse_ids}) ";
                $where["purchase_out"] .= " and PO.warehouse_id in ({$warehouse_ids}) ";
                $where["store_in_stock"] .= " and SIS.warehouse_id in ({$warehouse_ids}) ";
                $where["store_out_stock"] .= " and SOS.warehouse_id in ({$warehouse_ids}) ";
                $where["store_other_out_in"] .= " and SOO.warehouse_id in ({$warehouse_ids}) ";

                $where["store_out_back"] .= " and sb.warehouse_id in ({$warehouse_ids}) ";

                $where["store_other_out_out"] .= " and SOO.warehouse_id in ({$warehouse_ids}) ";
                $where["warehouse_in_stock"] .= " and WIS.warehouse_id in ({$warehouse_ids}) ";
                $where["warehouse_in_stock_by_return"] .= " and WIS.warehouse_id in ({$warehouse_ids}) ";

                //$where["warehouse_out_stock"] .= " and (WOS.warehouse_id1 in ({$warehouse_ids}) or WOS.warehouse_id2 in ({$warehouse_ids})) ";
                $where["warehouse_out_stock"] .= " and WOS.warehouse_id2 in ({$warehouse_ids}) ";
                //$where["warehouse_other_out_in"] .= " and (WOO.warehouse_id in ({$warehouse_ids}) or WOO.warehouse_id2 in ({$warehouse_ids})) ";
                $where["warehouse_other_out_in"] .= " and WOO.warehouse_id2 in ({$warehouse_ids}) ";
                //$where["warehouse_other_out_out"] .= " and (WOO.warehouse_id in ({$warehouse_ids}) or WOO.warehouse_id2 in ({$warehouse_ids})) ";
                $where["warehouse_other_out_out"] .= " and WOO.warehouse_id in ({$warehouse_ids}) ";
            }

        }
        if (!empty($store_ids)) {
            if(!empty($warehouse_ids)){
                $where["system_sell"] .= " and O.store_id in ({$store_ids}) ";
                $where["purchase"] .= " and P.store_id in ({$store_ids}) ";
                $where["purchase_out"] .= " and PO.store_id in ({$store_ids}) ";

                //$where["store_in_stock"] .= " and (SIS.store_id1 in ({$store_ids}) or SIS.store_id2 in ({$store_ids})) ";
                $where["store_in_stock"] .= " OR SIS.store_id2 in ({$store_ids}))  ";
                //$where["store_out_stock"] .= " and (SOS.store_id1 in ({$store_ids}) or SOS.store_id2 in ({$store_ids})) ";
                $where["store_out_stock"] .= " OR SOS.store_id2 in ({$store_ids}))  ";
                //$where["store_other_out_in"] .= " and (SOO.store_id1 in ($store_ids) or SOO.store_id2 in ({$store_ids})) ";
                $where["store_other_out_in"] .= " OR SOO.store_id1 in ({$store_ids})) ";
                //$where["store_other_out_out"] .= " and (SOO.store_id1 in ($store_ids) or SOO.store_id2 in ({$store_ids})) ";
                $where["store_other_out_out"] .= " OR SOO.store_id2 in ($store_ids)) ";
                $where["store_out_back"] .= " and sb.store_id in ({$store_ids})) ";

                $where["warehouse_out_stock"] .= " and WOS.store_id in ({$store_ids}) ";
                $where["warehouse_other_out_in"] .= " and WOO.store_id in ($store_ids) ";
                $where["warehouse_other_out_out"] .= " and WOO.store_id in ($store_ids) ";
            }else{
                $where["system_sell"] .= " and O.store_id in ({$store_ids}) ";
                $where["purchase"] .= " and P.store_id in ({$store_ids}) ";
                $where["purchase_out"] .= " and PO.store_id in ({$store_ids}) ";

                //$where["store_in_stock"] .= " and (SIS.store_id1 in ({$store_ids}) or SIS.store_id2 in ({$store_ids})) ";
                $where["store_in_stock"] .= " and SIS.store_id2 in ({$store_ids}) ";
                //$where["store_out_stock"] .= " and (SOS.store_id1 in ({$store_ids}) or SOS.store_id2 in ({$store_ids})) ";
                $where["store_out_stock"] .= " and SOS.store_id2 in ({$store_ids})  ";
                //$where["store_other_out_in"] .= " and (SOO.store_id1 in ($store_ids) or SOO.store_id2 in ({$store_ids})) ";
                $where["store_other_out_in"] .= " and SOO.store_id1 in ({$store_ids}) ";
                //$where["store_other_out_out"] .= " and (SOO.store_id1 in ($store_ids) or SOO.store_id2 in ({$store_ids})) ";
                $where["store_other_out_out"] .= " and SOO.store_id2 in ($store_ids) ";
                $where["store_out_back"] .= " and (sb.store_id in ({$store_ids})) ";

                $where["warehouse_out_stock"] .= " and WOS.store_id in ({$store_ids}) ";
                $where["warehouse_other_out_in"] .= " and WOO.store_id in ($store_ids) ";
                $where["warehouse_other_out_out"] .= " and WOO.store_id in ($store_ids) ";
            }

        }
        if (!empty($supply_ids)) {
            $where["purchase"] .= " and P.supply_id in ({$supply_ids}) ";
            $where["purchase_out"] .= " and PO.supply_id in ({$supply_ids}) ";
            $where["store_in_stock"] .= " and SIS.supply_id in ({$supply_ids}) ";
            $where["store_out_stock"] .= " and SOS.supply_id in ({$supply_ids}) ";
            $where["warehouse_in_stock"] .= " and WIS.supply_id in ({$supply_ids}) ";
            $where["warehouse_in_stock_by_return"] .= " and WIS.supply_id in ({$supply_ids}) ";
        }

        $lx_where = " where 1=1 and ( ";
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        if (in_array(0, $lx_array)) {
            $lx_where .= " ( total.type=0 ) or";
        }
        if (in_array(1, $lx_array)) {
            $lx_where .= " (total.type=1 or total.type=3 or total.type=10) or";
        }
        if (in_array(2, $lx_array)) {
            $lx_where .= " (total.type=2 or total.type=4) or";
        }
        if (in_array(3, $lx_array)) {
            $lx_where .= " (total.type=5 or total.type=7 ) or";
        }
        if (in_array(4, $lx_array)) {
            $lx_where .= " (total.type=6 or total.type=8 or total.type=10) or";
        }
        if (in_array(5, $lx_array)) {
            $lx_where .= " (total.type=9 and total.s_type=1) or";
        }
        if (in_array(6, $lx_array)) {
            $lx_where .= " (total.type=9 and total.s_type=0) or";
        }

        $lx_where = substr($lx_where, 0, strlen($lx_where) - 2) . ")";

        $sql = $this->searchAll2($where);

        $sql = "select * from ({$sql}) total {$lx_where} order by ptime DESC ";

        /***
         ***************************** 只搜索 仓库/门店/供应商 其中一种 ********************************************************
         * if (!empty($warehouse_ids) && empty($store_ids) && empty($supply_ids)) {
         * $sql = $this->searchWarehouse($where);
         * }
         * if (empty($warehouse_ids) && !empty($store_ids) && empty($supply_ids)) {
         * $sql = $this->searchStore($where);
         * }
         * if (empty($warehouse_ids) && empty($store_ids) && !empty($supply_ids)) {
         * $sql = $this->searchSupply($where);
         * }
         *
         * if ((empty($warehouse_ids) && empty($store_ids) && empty($supply_ids))
         * || (!empty($warehouse_ids) && !empty($store_ids) && !empty($supply_ids))
         * ) {
         * $sql = $this->searchAll($where);
         * }
         ****************************** 搜索 仓库+门店/仓库+供应商/门店+供应商 ***************************************************************************************
         * if (!empty($warehouse_ids) && !empty($store_ids) && empty($supply_ids)) {
         * $sql = $this->searchWarehouseAndStore($where);
         * }
         * if (!empty($warehouse_ids) && empty($store_ids) && !empty($supply_ids)) {
         * $sql = $this->searchWarehouseAndSupply($where);
         * }
         * if (empty($warehouse_ids) && !empty($store_ids) && !empty($supply_ids)) {
         * $sql = $this->searchStoreAndSupply($where);
         * }
         ***********************/

        /************************************** 执行SQL查询 ******************************************************************/

        if (I("get.showsql")) {
            echo $sql;
            exit;
        }

        $data = M()->query($sql);

        //dump($data);exit;

        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            //$Page->parameter["warehouse"] = $warehouse_ids;
            //$Page->parameter["store"] = $store_ids;
            //$Page->parameter["supply"] = $supply_ids;
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
        }

        foreach ($data as $key => $val) {
            $data[$key]["s_type_name"] = $this->s_type_array[$val["type"]][$val["s_type"]];
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

    /**********************
     * 获取区域相关仓库门店信息【返回页面搜索条件】
     */
    private
    function getAreaWithWarehouseAndStore()
    {
        //获取区域
        $SheQuModel = M("Shequ");
        $WarehouseModel = M("Warehouse");
        $StoreModel = M("Store");
        $SupplyModel = M("Supply");
        $GoodsCateModel = M("GoodsCate");
        $dates = $this->getDates();
        $total = array();
        $shequ_datas = $SheQuModel->query("select id,title from hii_shequ order by id asc ");
        //区域对应的仓库 门店 供应商
        foreach ($shequ_datas as $key => $val) {
            $warehouse_datas = $WarehouseModel->query("select w_id as id,w_name as name from hii_warehouse where shequ_id={$val["id"]} order by w_id asc ");
            $store_datas = $StoreModel->query("select id,title as name from hii_store where shequ_id={$val["id"]} order by id asc ");
            $supply_datas = $SupplyModel->query("select s_id as id,s_name as name from hii_supply where shequ_id={$val["id"]} order by s_id asc ");
            $total["area"][] = array(
                "shequ_id" => $val["id"],
                "shequ_name" => $val["title"],
                "warehouse" => $warehouse_datas,
                "store" => $store_datas,
                "supply" => $supply_datas
            );
        }
        //$this->response(self::CODE_OK, $total);
        $total["lx"] = $this->lx;
        $total["goods_cates"] = $GoodsCateModel->query("select id,title as name from hii_goods_cate order by id asc ");
        return $total;
    }

    /*******************************************获取搜索语句 start*********************************************************************************************/

    private
    function searchAll2($where)
    {
        $sql = "";
        /*--------------------查询销售商品流水【hii_order,hii_order_detail】---------------------*/
        $sql = "select O.id as id,OD.d_id as goods_id,G.title as goods_name,GC.title as cate_name,OD.num as num, ";
        $sql .= "0 as `type`,'门店销售' as `type_name`,0 as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(O.pay_time,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "'' as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S.title,'') as fahuo_store_name, ";
        $sql .= "'' as remark,O.order_sn as sn ";
        $sql .= "from hii_order_detail OD ";
        $sql .= "left join hii_order O on O.order_sn=OD.order_sn ";
        $sql .= "left join hii_goods G on G.id=OD.d_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_member M on M.uid=O.uid ";
        $sql .= "left join hii_store S on S.id=O.store_id ";
        $sql .= "where {$where["system_sell"]} ";


        /*--------------------查询采购商品流水【hii_purchase,hii_purchase_detail】---------------------*/
        $sql .= "union all ";
        $sql .= "select P.p_id as id,PD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,PD.g_num as num, ";
        $sql .= "9 as `type`,'采购' as `type_name`,0 as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(P.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
        $sql .= "PD.remark as remark,P.p_sn as sn ";
        $sql .= "from hii_purchase_detail PD ";
        $sql .= "left join hii_purchase P on P.p_id=PD.p_id ";
        $sql .= "left join hii_goods G on G.id=PD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_warehouse W on W.w_id=P.warehouse_id ";
        $sql .= "left join hii_store S on S.id=P.store_id ";
        $sql .= "left join hii_supply SY on SY.s_id=P.supply_id ";
        $sql .= "left join hii_member M on M.uid=P.admin_id ";
        $sql .= "where {$where["purchase"]} ";


        /*--------------------查询采购退货商品流水【hii_purchase_out,hii_purchase_out_detail】---------------------*/
        $sql .= "union all ";
        $sql .= "select PO.p_o_id as id,POD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,POD.g_num as num, ";
        $sql .= "9 as `type`,'采购' as `type_name`,1 as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(PO.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
        $sql .= "POD.remark as remark,PO.p_o_sn as sn ";
        $sql .= "from hii_purchase_out_detail POD ";
        $sql .= "left join hii_purchase_out PO on PO.p_o_id=POD.p_o_id ";
        $sql .= "left join hii_goods G on G.id=POD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_member M on M.uid=PO.admin_id ";
        $sql .= "left join hii_warehouse W on W.w_id=PO.warehouse_id ";
        $sql .= "left join hii_store S on S.id=PO.store_id ";
        $sql .= "left join hii_supply SY on SY.s_id=PO.supply_id ";
        $sql .= "where {$where["purchase_out"]} ";


        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "union all ";
        $sql .= "select SIS.s_in_s_id as id,SISD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,SISD.g_num as num, ";
        $sql .= "5 as `type`,'门店入库' as `type_name`,SIS.s_in_s_type as s_type, ";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(SIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime, ";
        $sql .= "'' as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name, ";
        $sql .= "SISD.remark as remark,SIS.s_in_s_sn as sn ";
        $sql .= "from hii_store_in_stock_detail SISD ";
        $sql .= "left join hii_store_in_stock SIS on SIS.s_in_s_id=SISD.s_in_s_id ";
        $sql .= "left join hii_store S2 on S2.id=SIS.store_id2  ";
        $sql .= "left join hii_supply SY on SY.s_id=SIS.supply_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SIS.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SIS.store_id1 ";
        $sql .= "left join hii_member M on M.uid=SIS.admin_id ";
        $sql .= "left join hii_goods G on G.id=SISD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where {$where["store_in_stock"]} ";
        //被退货入库
        $sql .= "union all ";
        $sql .= "select SOO.s_o_out_id as id,SOOD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,SOOD.g_num as num, ";
        $sql .= "7 as `type`,case SOO.s_o_out_type when 5 then '仓库拒绝返仓(入库)' when 1 then '门店调拨拒绝(入库)' when 0 then '仓库发货拒绝' end as `type_name`,SOO.s_o_out_type as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "'' as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W.w_name,'') as fahuo_warehouse_name,ifnull(S2.title,'') as fahuo_store_name, ";
        $sql .= "SOOD.remark as remark,SOO.s_o_out_sn as sn ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where {$where["store_other_out_in"]} and SOO.s_o_out_type !=0  ";

        //正常出库
        $sql .= "union all ";
        $sql .= "select SOS.s_out_s_id as id,SOSD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,SOSD.g_num as num, ";
        $sql .= "6 as `type`,'门店出库' as `type_name`,SOS.s_out_s_type as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(SOS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,  ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S1.title,'') as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2.title,'') as fahuo_store_name, ";
        $sql .= "SOSD.remark as remark,SOS.s_out_s_sn as sn ";
        $sql .= "from hii_store_stock_detail SOSD ";
        $sql .= "left join hii_store_out_stock SOS on SOS.s_out_s_id=SOSD.s_out_s_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOS.warehouse_id ";
        $sql .= "left join hii_supply SY on SY.s_id=SOS.supply_id ";
        $sql .= "left join hii_store S1 on S1.id=SOS.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOS.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOS.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOSD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where {$where["store_out_stock"]} ";
        //门店返仓出库
       $sql .= "union all ";
        $sql .= "select sb.s_back_id as id,sbd.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,sbd.g_num as num, ";
        $sql .= "10 as `type`,'门店返仓出库' as `type_name`,sb.s_back_type as s_type, ";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(sb.ptime,'%Y-%m-%d %H:%i:%s') as ptime,  ";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name, ";
        $sql .= "sbd.remark as remark,sb.s_back_sn as sn ";
        $sql .= "from hii_store_back_detail sbd  ";
        $sql .= "left join hii_store_back sb on sbd.s_back_id=sb.s_back_id ";
        $sql .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=sb.store_id ";
        $sql .= "left join hii_member M on M.uid=sb.admin_id ";
        $sql .= "left join hii_goods G on G.id=sbd.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where {$where["store_out_back"]} ";
        //退货出库
  /*      $sql .= "union all ";
        $sql .= "select SOO.s_o_out_id as id,SOOD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,SOOD.g_num as num, ";
        $sql .= "8 as `type`,'门店退货出库' as `type_name`,SOO.s_o_out_type as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(SOO.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,ifnull(S2.title,'') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name, ";
        $sql .= "SOOD.remark as remark,SOO.s_o_out_sn as sn ";
        $sql .= "from hii_store_other_out_detail SOOD ";
        $sql .= "left join hii_store_other_out SOO on SOO.s_o_out_id=SOOD.s_o_out_id ";
        $sql .= "left join hii_warehouse W on W.w_id=SOO.warehouse_id ";
        $sql .= "left join hii_store S1 on S1.id=SOO.store_id1 ";
        $sql .= "left join hii_store S2 on S2.id=SOO.store_id2 ";
        $sql .= "left join hii_member M on M.uid=SOO.admin_id ";
        $sql .= "left join hii_goods G on G.id=SOOD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where {$where["store_other_out_out"]} ";*/

        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        //正常入库
        $sql .= "union all ";
        $sql .= "select WIS.w_in_s_id as id,WISD.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,WISD.g_num as num, ";
        $sql .= "1 as `type`,'仓库入库' as `type_name`,WIS.w_in_s_type as s_type,";
        $sql .= "M.nickname as admin_nickname,FROM_UNIXTIME(WIS.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "ifnull(SY.s_name,'') as fahuo_supply_name,ifnull(W2.w_name,'') as fahuo_warehouse_name,'' as fahuo_store_name, ";
        $sql .= "WISD.remark as remark,WIS.w_in_s_sn as sn ";
        $sql .= "from hii_warehouse_in_stock_detail WISD ";
        $sql .= "left join hii_warehouse_in_stock WIS on WIS.w_in_s_id=WISD.w_in_s_id ";
        $sql .= "left join hii_warehouse W on W.w_id=WIS.warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY.s_id=WIS.supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI.w_in_id=WIS.w_in_id ";
        $sql .= "left join hii_warehouse W2 on W2.w_id=WI.warehouse_id2 ";//发货仓库
        $sql .= "left join hii_member M on M.uid=WIS.admin_id ";
        $sql .= "left join hii_goods G on G.id=WISD.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "where {$where["warehouse_in_stock"]} ";


        //返仓入库
        $sql .= "union all ";
        $sql .= "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
        $sql .= "1 as `type`,'门店返仓入库' as `type_name`,WIS . w_in_s_type as s_type,";
        $sql .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,S . title as fahuo_store_name, ";
        $sql .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
        $sql .= "from hii_warehouse_in_stock_detail WISD ";
        $sql .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
        $sql .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
        $sql .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
        $sql .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
        $sql .= "left join hii_store S on S . id = WI . store_id ";
        $sql .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
        $sql .= "left join hii_member M on M . uid = WIS . admin_id ";
        $sql .= "left join hii_goods G on G . id = WISD . goods_id ";
        $sql .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
        $sql .= "where {$where["warehouse_in_stock_by_return"]} ";
        //被退货入库
        $sql .= "union all ";
        $sql .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
        $sql .= "3 as `type`,'仓库被退货入库' as `type_name`,WOO . w_o_out_type as s_type,";
        $sql .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
        $sql .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
        $sql .= "from hii_warehouse_other_out_detail WOOD ";
        $sql .= "left join hii_warehouse_other_out WOO on WOO . w_o_out_id = WOOD . w_o_out_id ";
        $sql .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
        $sql .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
        $sql .= "left join hii_store S on S . id = WOO . store_id ";
        $sql .= "left join hii_member M on M . uid = WOO . admin_id ";
        $sql .= "left join hii_goods G on G . id = WOOD . goods_id ";
        $sql .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
        $sql .= "where {$where["warehouse_other_out_in"]} ";


        //正常出库
        $sql .= "union all ";
        $sql .= "select WOS . w_out_s_id as id,WOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOSD . g_num as num, ";
        $sql .= "2 as `type`,'仓库出库' as `type_name`,WOS . w_out_s_type as s_type,";
        $sql .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
        $sql .= "WOSD . remark as remark,WOS . w_out_s_sn as sn ";
        $sql .= "from hii_warehouse_out_stock_detail WOSD ";
        $sql .= "left join hii_warehouse_out_stock WOS on WOS . w_out_s_id = WOSD . w_out_s_id ";
        $sql .= "left join hii_warehouse W on W . w_id = WOS . warehouse_id1 ";//入库仓库
        $sql .= "left join hii_warehouse W2 on W2 . w_id = WOS . warehouse_id2 ";//发货仓库
        $sql .= "left join hii_store S on S . id = WOS . store_id ";//入库门店
        $sql .= "left join hii_member M on M . uid = WOS . admin_id ";
        $sql .= "left join hii_goods G on G . id = WOSD . goods_id ";
        $sql .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
        $sql .= "where {$where["warehouse_out_stock"]} ";
        //退货出库
       /*  $sql .= "union all ";
        $sql .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
        $sql .= "4 as `type`,'仓库退货出库' as `type_name`,WOO . w_o_out_type as s_type,";
        $sql .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
        $sql .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
        $sql .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
        $sql .= "from hii_warehouse_other_out_detail WOOD ";
        $sql .= "left join hii_warehouse_other_out WOO  on WOO . w_o_out_id = WOOD . w_o_out_id  ";
        $sql .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
        $sql .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
        $sql .= "left join hii_store S on S . id = WOO . store_id ";
        $sql .= "left join hii_member M on M . uid = WOO . admin_id ";
        $sql .= "left join hii_goods G on G . id = WOOD . goods_id ";
        $sql .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
        $sql .= "where {$where["warehouse_other_out_out"]} "; */

        return $sql;
    }


    private
    function searchAll($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();

        if (in_array(0, $lx_array)) {
            /*--------------------查询销售商品流水【hii_order,hii_order_detail】---------------------*/
            $sqls["system_sold_sql"] = "select O . id as id,OD . d_id as goods_id,G . title as goods_name,GC . title as cate_name,OD . num as num, ";
            $sqls["system_sold_sql"] .= "0 as `type`,'门店销售' as `type_name`,0 as s_type,";
            $sqls["system_sold_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(O . pay_time, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["system_sold_sql"] .= "'' as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["system_sold_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["system_sold_sql"] .= "'' as remark,O . order_sn as sn ";
            $sqls["system_sold_sql"] .= "from hii_order_detail OD ";
            $sqls["system_sold_sql"] .= "left join hii_order O on O . order_sn = OD . order_sn ";
            $sqls["system_sold_sql"] .= "left join hii_goods G on G . id = OD . d_id ";
            $sqls["system_sold_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["system_sold_sql"] .= "left join hii_member M on M . uid = O . uid ";
            $sqls["system_sold_sql"] .= "left join hii_store S on S . id = O . store_id ";
            $sqls["system_sold_sql"] .= "where {$where["system_sell"]} ";
        }
        if (in_array(6, $lx_array)) {
            /*--------------------查询采购商品流水【hii_purchase,hii_purchase_detail】---------------------*/
            $sqls["purchase_out_stock_sql"] = "select P . p_id as id,PD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,PD . g_num as num, ";
            $sqls["purchase_out_stock_sql"] .= "9 as `type`,'采购' as `type_name`,0 as s_type,";
            $sqls["purchase_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(P . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "PD . remark as remark,P . p_sn as sn ";
            $sqls["purchase_out_stock_sql"] .= "from hii_purchase_detail PD ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_purchase P on P . p_id = PD . p_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods G on G . id = PD . goods_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = P . warehouse_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_store S on S . id = P . store_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = P . supply_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_member M on M . uid = P . admin_id ";
            $sqls["purchase_out_stock_sql"] .= "where {$where["purchase"]} ";
        }

        if (in_array(5, $lx_array)) {
            /*--------------------查询采购退货商品流水【hii_purchase_out,hii_purchase_out_detail】---------------------*/
            $sqls["purchase_in_stock_sql"] = "select PO . p_o_id as id,POD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,POD . g_num as num, ";
            $sqls["purchase_in_stock_sql"] .= "9 as `type`,'采购' as `type_name`,1 as s_type,";
            $sqls["purchase_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(PO . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "POD . remark as remark,PO . p_o_sn as sn ";
            $sqls["purchase_in_stock_sql"] .= "from hii_purchase_out_detail POD ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_purchase_out PO on PO . p_o_id = POD . p_o_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods G on G . id = POD . goods_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_member M on M . uid = PO . admin_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = PO . warehouse_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_store S on S . id = PO . store_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = PO . supply_id ";
            $sqls["purchase_in_stock_sql"] .= "where {$where["purchase_out"]} ";
        }

        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        if (in_array(3, $lx_array)) {
            //正常入库
            $sqls["store_in_stock_sql"] = "select SIS . s_in_s_id as id,SISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SISD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "5 as `type`,'门店入库' as `type_name`,SIS . s_in_s_type as s_type, ";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SISD . remark as remark,SIS . s_in_s_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_in_stock_detail SISD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_in_stock SIS on SIS . s_in_s_id = SISD . s_in_s_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SIS . store_id2  ";
            $sqls["store_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = SIS . supply_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SIS . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SIS . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SIS . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SISD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_in_stock"]} ";
            //被退货入库
            $sqls["store_in_stock_sql"] .= "union all ";
            $sqls["store_in_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "8 as `type`,case SOO.s_o_out_type when 5 then '仓库拒绝返仓(入库)' when 1 then '门店调拨拒绝(入库)' when 0 then '仓库发货拒绝' end as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_other_out_in"]} and SOO.s_o_out_type !=0   ";
        }

        if (in_array(4, $lx_array)) {
            //正常出库
            $sqls["store_out_stock_sql"] = "select SOS . s_out_s_id as id,SOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOSD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "6 as `type`,'门店出库' as `type_name`,SOS . s_out_s_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,  ";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOSD . remark as remark,SOS . s_out_s_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_stock_detail SOSD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_out_stock SOS on SOS . s_out_s_id = SOSD . s_out_s_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOS . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = SOS . supply_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOS . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOS . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOS . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOSD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_stock"]} ";
            //门店返仓出库
            $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select sb.s_back_id as id,sbd . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,sbd . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "10 as `type`,'门店返仓出库' as `type_name`,sb . s_back_type as s_type, ";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(sb . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "sbd . remark as remark,sb . s_back_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_back_detail sbd ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_back sb on sbd.s_back_id=sb.s_back_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = sb . store_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = sb . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = sbd . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_back"]} ";
            //退货出库
           /* $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "8 as `type`,'门店退货出库' as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_other_out_out"]} ";*/
        }

        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        if (in_array(1, $lx_array)) {
            //正常入库
            $sqls["warehouse_in_stock_sql"] = "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'仓库入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock"]} ";
            //返仓入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'门店返仓入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,S . title as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WI . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock_by_return"]} ";
            //被退货入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "3 as `type`,'仓库被退货入库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_other_out WOO on WOO . w_o_out_id = WOOD . w_o_out_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_other_out_in"]} ";
        }

        if (in_array(2, $lx_array)) {
            //正常出库
            $sqls["warehouse_out_stock_sql"] = "select WOS . w_out_s_id as id,WOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOSD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "2 as `type`,'仓库出库' as `type_name`,WOS . w_out_s_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOSD . remark as remark,WOS . w_out_s_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_out_stock_detail WOSD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_out_stock WOS on WOS . w_out_s_id = WOSD . w_out_s_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOS . warehouse_id1 ";//入库仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOS . warehouse_id2 ";//发货仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOS . store_id ";//入库门店
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOS . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOSD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_out_stock"]} ";
            //退货出库
       /*      $sqls["warehouse_out_stock_sql"] .= "union all ";
            $sqls["warehouse_out_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "4 as `type`,'仓库退货出库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_other_out WOO  on WOO . w_o_out_id = WOOD . w_o_out_id  ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_other_out_out"]} "; */
        }


        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }
        return "select * from({$sql}) total order by ptime DESC ";
    }

    /****************
     * 只查仓库部分流水
     */
    private
    function searchWarehouse($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();
        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        //正常入库
        if (in_array(1, $lx_array)) {
            $sqls["warehouse_in_stock_sql"] = "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'仓库入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock"]} ";
            //返仓入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'门店返仓入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,S . title as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WI . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock_by_return"]} ";
            //被退货入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "3 as `type`,'仓库被退货入库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_other_out WOO on WOO . w_o_out_id = WOOD . w_o_out_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_other_out_in"]} ";
        }
        //正常出库
        if (in_array(2, $lx_array)) {
            $sqls["warehouse_out_stock_sql"] = "select WOS . w_out_s_id as id,WOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOSD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "2 as `type`,'仓库出库' as `type_name`,WOS . w_out_s_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOSD . remark as remark,WOS . w_out_s_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_out_stock_detail WOSD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_out_stock WOS on WOS . w_out_s_id = WOSD . w_out_s_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOS . warehouse_id1 ";//入库仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOS . warehouse_id2 ";//发货仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOS . store_id ";//入库门店
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOS . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOSD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_out_stock"]} ";
            //退货出库
           /*  $sqls["warehouse_out_stock_sql"] .= "union all ";
            $sqls["warehouse_out_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "4 as `type`,'仓库退货出库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_other_out WOO  on WOO . w_o_out_id = WOOD . w_o_out_id  ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_other_out_out"]} "; */
        }

        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }

        return "select * from({$sql}) total order by ptime DESC ";
    }

    /*************
     * 只查询门店部分流水
     */
    private
    function searchStore($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();
        /********************* 门店销售 **************************/
        if (in_array(0, $lx_array)) {
            /*--------------------查询销售商品流水【hii_order,hii_order_detail】---------------------*/
            $sqls["system_sold_sql"] = "select O . id as id,OD . d_id as goods_id,G . title as goods_name,GC . title as cate_name,OD . num as num, ";
            $sqls["system_sold_sql"] .= "0 as `type`,'门店销售' as `type_name`,0 as s_type,";
            $sqls["system_sold_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(O . pay_time, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["system_sold_sql"] .= "'' as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["system_sold_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["system_sold_sql"] .= "'' as remark,O . order_sn as sn ";
            $sqls["system_sold_sql"] .= "from hii_order_detail OD ";
            $sqls["system_sold_sql"] .= "left join hii_order O on O . order_sn = OD . order_sn ";
            $sqls["system_sold_sql"] .= "left join hii_goods G on G . id = OD . d_id ";
            $sqls["system_sold_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["system_sold_sql"] .= "left join hii_member M on M . uid = O . uid ";
            $sqls["system_sold_sql"] .= "left join hii_store S on S . id = O . store_id ";
            $sqls["system_sold_sql"] .= "where {$where["system_sell"]} ";
        }
        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        if (in_array(3, $lx_array)) {
            //正常入库
            $sqls["store_in_stock_sql"] = "select SIS . s_in_s_id as id,SISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SISD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "5 as `type`,'门店入库' as `type_name`,SIS . s_in_s_type as s_type, ";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SISD . remark as remark,SIS . s_in_s_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_in_stock_detail SISD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_in_stock SIS on SIS . s_in_s_id = SISD . s_in_s_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SIS . store_id2  ";
            $sqls["store_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = SIS . supply_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SIS . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SIS . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SIS . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SISD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_in_stock"]} ";
            $sqls["store_in_stock_sql"] .= "union all ";
            //被退货入库
            $sqls["store_in_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "8 as `type`,case SOO.s_o_out_type when 5 then '仓库拒绝返仓(入库)' when 1 then '门店调拨拒绝(入库)' when 0 then '仓库发货拒绝' end as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_other_out_in"]} and SOO.s_o_out_type !=0   ";
        }
        if (in_array(4, $lx_array)) {
            //正常出库
            $sqls["store_out_stock_sql"] .= "select SOS . s_out_s_id as id,SOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOSD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "6 as `type`,'门店出库' as `type_name`,SOS . s_out_s_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,  ";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOSD . remark as remark,SOS . s_out_s_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_stock_detail SOSD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_out_stock SOS on SOS . s_out_s_id = SOSD . s_out_s_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOS . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = SOS . supply_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOS . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOS . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOS . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOSD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_stock"]} ";

            //门店返仓出库
            $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select sb.s_back_id as id,sbd.goods_id as goods_id,G.title as goods_name,GC.title as cate_name,sbd.g_num as num, ";
            $sqls["store_out_stock_sql"] .= "10 as `type`,'门店返仓出库' as `type_name`,sb.s_back_type as s_type, ";
            $sqls["store_out_stock_sql"] .= "M.nickname as admin_nickname,FROM_UNIXTIME(sb.ptime,'%Y-%m-%d %H:%i:%s') as ptime,  ";
            $sqls["store_out_stock_sql"] .= "ifnull(W.w_name,'') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1.title,'') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "sbd.remark as remark,sb.s_back_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_back_detail sbd ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_back sb on sbd.s_back_id=sb.s_back_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = sb . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = sb . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = sb . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = sbd . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_back"]} ";
            //退货出库
/*            $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "8 as `type`,'门店退货出库' as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_other_out_out"]} ";*/
        }

        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }

        return "select * from({$sql}) total order by ptime DESC ";
    }

    /************
     * 搜索仓库+门店
     * @param $where
     */
    private
    function searchWarehouseAndStore($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();

        /*--------------------查询销售商品流水【hii_order,hii_order_detail】---------------------*/
        if (in_array(0, $lx_array)) {
            $sqls["system_sold_sql"] = "select O . id as id,OD . d_id as goods_id,G . title as goods_name,GC . title as cate_name,OD . num as num, ";
            $sqls["system_sold_sql"] .= "0 as `type`,'门店销售' as `type_name`,0 as s_type,";
            $sqls["system_sold_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(O . pay_time, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["system_sold_sql"] .= "'' as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["system_sold_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["system_sold_sql"] .= "'' as remark,O . order_sn as sn ";
            $sqls["system_sold_sql"] .= "from hii_order_detail OD ";
            $sqls["system_sold_sql"] .= "left join hii_order O on O . order_sn = OD . order_sn ";
            $sqls["system_sold_sql"] .= "left join hii_goods G on G . id = OD . d_id ";
            $sqls["system_sold_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["system_sold_sql"] .= "left join hii_member M on M . uid = O . uid ";
            $sqls["system_sold_sql"] .= "left join hii_store S on S . id = O . store_id ";
            $sqls["system_sold_sql"] .= "where {$where["system_sell"]} ";
        }
        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        //正常入库
        if (in_array(3, $lx_array)) {
            $sqls["store_in_stock_sql"] = "select SIS . s_in_s_id as id,SISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SISD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "5 as `type`,'门店入库' as `type_name`,SIS . s_in_s_type as s_type, ";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SISD . remark as remark,SIS . s_in_s_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_in_stock_detail SISD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_in_stock SIS on SIS . s_in_s_id = SISD . s_in_s_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SIS . store_id2  ";
            $sqls["store_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = SIS . supply_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SIS . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SIS . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SIS . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SISD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_in_stock"]} ";
            //被退货入库
            $sqls["store_in_stock_sql"] .= "union all ";
            $sqls["store_in_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "8 as `type`,case SOO.s_o_out_type when 5 then '仓库拒绝返仓(入库)' when 1 then '门店调拨拒绝(入库)' when 0 then '仓库发货拒绝' end as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_other_out_in"]} and SOO.s_o_out_type !=0   ";
        }
        if (in_array(4, $lx_array)) {
            //正常出库
            $sqls["store_out_stock_sql"] = "select SOS . s_out_s_id as id,SOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOSD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "6 as `type`,'门店出库' as `type_name`,SOS . s_out_s_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,  ";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOSD . remark as remark,SOS . s_out_s_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_stock_detail SOSD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_out_stock SOS on SOS . s_out_s_id = SOSD . s_out_s_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOS . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = SOS . supply_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOS . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOS . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOS . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOSD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_stock"]} ";

            //门店返仓出库
            $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select sb.s_back_id as id,sbd . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,sbd . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "10 as `type`,'门店返仓出库' as `type_name`,sb . s_back_type as s_type, ";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(sb . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "sbd . remark as remark,sb . s_back_sn as sn  ";
            $sqls["store_out_stock_sql"] .= "from hii_store_back_detail sbd ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_back sb on sbd.s_back_id=sb.s_back_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = sb . store_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = sb . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = sbd . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_back"]} ";
            //退货出库
           /* $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "8 as `type`,'门店退货出库' as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_other_out_out"]} ";*/
        }


        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        if (in_array(1, $lx_array)) {
            //正常入库
            $sqls["warehouse_in_stock_sql"] = "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'仓库入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock"]} ";
            //返仓入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'门店返仓入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,S . title as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WI . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock_by_return"]} ";
            //被退货入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "3 as `type`,'仓库被退货入库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_other_out WOO on WOO . w_o_out_id = WOOD . w_o_out_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_other_out_in"]} ";
        }

        if (in_array(2, $lx_array)) {
            //正常出库
            $sqls["warehouse_out_stock_sql"] = "select WOS . w_out_s_id as id,WOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOSD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "2 as `type`,'仓库出库' as `type_name`,WOS . w_out_s_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOSD . remark as remark,WOS . w_out_s_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_out_stock_detail WOSD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_out_stock WOS on WOS . w_out_s_id = WOSD . w_out_s_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOS . warehouse_id1 ";//入库仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOS . warehouse_id2 ";//发货仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOS . store_id ";//入库门店
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOS . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOSD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_out_stock"]} ";
            //退货出库
            /* $sqls["warehouse_out_stock_sql"] .= "union all ";
            $sqls["warehouse_out_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "4 as `type`,'仓库退货出库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_other_out WOO  on WOO . w_o_out_id = WOOD . w_o_out_id  ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_other_out_out"]} "; */
        }


        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }

        return "select * from({$sql}) total order by ptime DESC ";
    }

    /************
     * 搜索仓库+供应商
     * @param $where
     */
    private
    function searchWarehouseAndSupply($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();

        /*--------------------查询采购商品发货流水【hii_purchase,hii_purchase_detail】---------------------*/
        if (in_array(6, $lx_array)) {
            $sqls["purchase_out_stock_sql"] = "select P . p_id as id,PD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,PD . g_num as num, ";
            $sqls["purchase_out_stock_sql"] .= "9 as `type`,'采购' as `type_name`,0 as s_type,";
            $sqls["purchase_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(P . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "PD . remark as remark,P . p_sn as sn ";
            $sqls["purchase_out_stock_sql"] .= "from hii_purchase_detail PD ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_purchase P on P . p_id = PD . p_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods G on G . id = PD . goods_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = P . warehouse_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_store S on S . id = P . store_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = P . supply_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_member M on M . uid = P . admin_id ";
            $sqls["purchase_out_stock_sql"] .= "where {$where["purchase"]} ";
        }
        /*--------------------查询采购退货商品流水【hii_purchase_out,hii_purchase_out_detail】---------------------*/
        if (in_array(5, $lx_array)) {
            $sqls["purchase_in_stock_sql"] = "select PO . p_o_id as id,POD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,POD . g_num as num, ";
            $sqls["purchase_in_stock_sql"] .= "9 as `type`,'采购' as `type_name`,1 as s_type,";
            $sqls["purchase_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(PO . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "POD . remark as remark,PO . p_o_sn as sn ";
            $sqls["purchase_in_stock_sql"] .= "from hii_purchase_out_detail POD ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_purchase_out PO on PO . p_o_id = POD . p_o_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods G on G . id = POD . goods_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_member M on M . uid = PO . admin_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = PO . warehouse_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_store S on S . id = PO . store_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = PO . supply_id ";
            $sqls["purchase_in_stock_sql"] .= "where {$where["purchase_out"]} ";
        }
        /************************************** 仓库出入库(正常出入库【hii_warehouse_out_stock,hii_warehouse_in_stock】和退货出入库【hii_warehouse_other_out】) start ******************************************************************/
        if (in_array(1, $lx_array)) {
            //正常入库
            $sqls["warehouse_in_stock_sql"] = "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'仓库入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock"]} ";
            //返仓入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WIS . w_in_s_id as id,WISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WISD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "1 as `type`,'门店返仓入库' as `type_name`,WIS . w_in_s_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,S . title as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WISD . remark as remark,WIS . w_in_s_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_in_stock_detail WISD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in_stock WIS on WIS . w_in_s_id = WISD . w_in_s_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WIS . warehouse_id ";//入库仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = WIS . supply_id ";//发货供应商
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_in WI on WI . w_in_id = WIS . w_in_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WI . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WI . warehouse_id2 ";//发货仓库
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WIS . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WISD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_in_stock_by_return"]} ";
            //被退货入库
            $sqls["warehouse_in_stock_sql"] .= "union all ";
            $sqls["warehouse_in_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_in_stock_sql"] .= "3 as `type`,'仓库被退货入库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_in_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_in_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse_other_out WOO on WOO . w_o_out_id = WOOD . w_o_out_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_in_stock_sql"] .= "where {$where["warehouse_other_out_in"]} ";
        }

        if (in_array(2, $lx_array)) {
            //正常出库
            $sqls["warehouse_out_stock_sql"] = "select WOS . w_out_s_id as id,WOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOSD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "2 as `type`,'仓库出库' as `type_name`,WOS . w_out_s_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOSD . remark as remark,WOS . w_out_s_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_out_stock_detail WOSD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_out_stock WOS on WOS . w_out_s_id = WOSD . w_out_s_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOS . warehouse_id1 ";//入库仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOS . warehouse_id2 ";//发货仓库
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOS . store_id ";//入库门店
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOS . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOSD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_out_stock"]} ";
            //退货出库
           /*  $sqls["warehouse_out_stock_sql"] .= "union all ";
            $sqls["warehouse_out_stock_sql"] .= "select WOO . w_o_out_id as id,WOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,WOOD . g_num as num, ";
            $sqls["warehouse_out_stock_sql"] .= "4 as `type`,'仓库退货出库' as `type_name`,WOO . w_o_out_type as s_type,";
            $sqls["warehouse_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(WOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["warehouse_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W2 . w_name, '') as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["warehouse_out_stock_sql"] .= "WOOD . remark as remark,WOO . w_o_out_sn as sn ";
            $sqls["warehouse_out_stock_sql"] .= "from hii_warehouse_other_out_detail WOOD ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse_other_out WOO  on WOO . w_o_out_id = WOOD . w_o_out_id  ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = WOO . warehouse_id2 ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_warehouse W2 on W2 . w_id = WOO . warehouse_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_store S on S . id = WOO . store_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_member M on M . uid = WOO . admin_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods G on G . id = WOOD . goods_id ";
            $sqls["warehouse_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["warehouse_out_stock_sql"] .= "where {$where["warehouse_other_out_out"]} "; */
        }

        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }
        return "select * from({$sql}) total order by ptime DESC ";
    }

    /*************
     * 搜索门店+供应商
     * @param $where
     */
    private
    function searchStoreAndSupply($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();

        if (in_array(0, $lx_array)) {
            /*--------------------查询销售商品流水【hii_order,hii_order_detail】---------------------*/
            $sqls["system_sold_sql"] = "select O . id as id,OD . d_id as goods_id,G . title as goods_name,GC . title as cate_name,OD . num as num, ";
            $sqls["system_sold_sql"] .= "0 as `type`,'门店销售' as `type_name`,0 as s_type,";
            $sqls["system_sold_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(O . pay_time, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["system_sold_sql"] .= "'' as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["system_sold_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S . title, '') as fahuo_store_name, ";
            $sqls["system_sold_sql"] .= "'' as remark,O . order_sn as sn ";
            $sqls["system_sold_sql"] .= "from hii_order_detail OD ";
            $sqls["system_sold_sql"] .= "left join hii_order O on O . order_sn = OD . order_sn ";
            $sqls["system_sold_sql"] .= "left join hii_goods G on G . id = OD . d_id ";
            $sqls["system_sold_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["system_sold_sql"] .= "left join hii_member M on M . uid = O . uid ";
            $sqls["system_sold_sql"] .= "left join hii_store S on S . id = O . store_id ";
            $sqls["system_sold_sql"] .= "where {$where["system_sell"]} ";
        }
        if (in_array(6, $lx_array)) {
            /*--------------------查询采购商品流水【hii_purchase,hii_purchase_detail】---------------------*/
            $sqls["purchase_out_stock_sql"] = "select P . p_id as id,PD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,PD . g_num as num, ";
            $sqls["purchase_out_stock_sql"] .= "9 as `type`,'采购' as `type_name`,0 as s_type,";
            $sqls["purchase_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(P . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "PD . remark as remark,P . p_sn as sn ";
            $sqls["purchase_out_stock_sql"] .= "from hii_purchase_detail PD ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_purchase P on P . p_id = PD . p_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods G on G . id = PD . goods_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = P . warehouse_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_store S on S . id = P . store_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = P . supply_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_member M on M . uid = P . admin_id ";
            $sqls["purchase_out_stock_sql"] .= "where {$where["purchase"]} ";
        }
        if (in_array(5, $lx_array)) {
            /*--------------------查询采购退货商品流水【hii_purchase_out,hii_purchase_out_detail】---------------------*/
            $sqls["purchase_in_stock_sql"] = "select PO . p_o_id as id,POD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,POD . g_num as num, ";
            $sqls["purchase_in_stock_sql"] .= "9 as `type`,'采购' as `type_name`,1 as s_type,";
            $sqls["purchase_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(PO . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "POD . remark as remark,PO . p_o_sn as sn ";
            $sqls["purchase_in_stock_sql"] .= "from hii_purchase_out_detail POD ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_purchase_out PO on PO . p_o_id = POD . p_o_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods G on G . id = POD . goods_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_member M on M . uid = PO . admin_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = PO . warehouse_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_store S on S . id = PO . store_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = PO . supply_id ";
            $sqls["purchase_in_stock_sql"] .= "where {$where["purchase_out"]} ";
        }


        /************************************** 门店出入库(正常出入库【hii_store_out_stock,hii_store_in_stock】和退货出入库【hii_store_other_out】) start ******************************************************************/
        if (in_array(3, $lx_array)) {
            //正常入库
            $sqls["store_in_stock_sql"] = "select SIS . s_in_s_id as id,SISD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SISD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "5 as `type`,'门店入库' as `type_name`,SIS . s_in_s_type as s_type, ";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SIS . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SISD . remark as remark,SIS . s_in_s_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_in_stock_detail SISD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_in_stock SIS on SIS . s_in_s_id = SISD . s_in_s_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SIS . store_id2  ";
            $sqls["store_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = SIS . supply_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SIS . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SIS . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SIS . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SISD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_in_stock"]} ";
            //被退货入库
            $sqls["store_in_stock_sql"] .= "union all ";
            $sqls["store_in_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_in_stock_sql"] .= "8 as `type`,case SOO.s_o_out_type when 5 then '仓库拒绝返仓(入库)' when 1 then '门店调拨拒绝(入库)' when 0 then '仓库发货拒绝' end as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_in_stock_sql"] .= "'' as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_in_stock_sql"] .= "'' as fahuo_supply_name,ifnull(W . w_name, '') as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_in_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_in_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_in_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_in_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_in_stock_sql"] .= "where {$where["store_other_out_in"]} and SOO.s_o_out_type !=0   ";
        }

        if (in_array(4, $lx_array)) {
            //正常出库
            $sqls["store_out_stock_sql"] .= "select SOS . s_out_s_id as id,SOSD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOSD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "6 as `type`,'门店出库' as `type_name`,SOS . s_out_s_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOS . ptime, '%Y-%m-%d %H:%i:%s') as ptime,  ";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S1 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S2 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOSD . remark as remark,SOS . s_out_s_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_stock_detail SOSD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_out_stock SOS on SOS . s_out_s_id = SOSD . s_out_s_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOS . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = SOS . supply_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOS . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOS . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOS . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOSD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_stock"]} ";

            //门店返仓出库
            $sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select sb.s_back_id as id,sbd . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,sbd . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "10 as `type`,'门店返仓出库' as `type_name`,sb . s_back_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(sb . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,'' as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "sbd . remark as remark,sb . s_back_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_back_detail sbd ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_back sb on sbd.s_back_id=sb.s_back_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W.w_id=sb.warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = sb . store_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = sb . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = sbd . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_out_back"]} ";

            //退货出库
            /*$sqls["store_out_stock_sql"] .= "union all ";
            $sqls["store_out_stock_sql"] .= "select SOO . s_o_out_id as id,SOOD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,SOOD . g_num as num, ";
            $sqls["store_out_stock_sql"] .= "8 as `type`,'门店退货出库' as `type_name`,SOO . s_o_out_type as s_type,";
            $sqls["store_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(SOO . ptime, '%Y-%m-%d %H:%i:%s') as ptime,";
            $sqls["store_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S2 . title, '') as ruku_store_name, ";
            $sqls["store_out_stock_sql"] .= "'' as fahuo_supply_name,'' as fahuo_warehouse_name,ifnull(S1 . title, '') as fahuo_store_name, ";
            $sqls["store_out_stock_sql"] .= "SOOD . remark as remark,SOO . s_o_out_sn as sn ";
            $sqls["store_out_stock_sql"] .= "from hii_store_other_out_detail SOOD ";
            $sqls["store_out_stock_sql"] .= "left join hii_store_other_out SOO on SOO . s_o_out_id = SOOD . s_o_out_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = SOO . warehouse_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S1 on S1 . id = SOO . store_id1 ";
            $sqls["store_out_stock_sql"] .= "left join hii_store S2 on S2 . id = SOO . store_id2 ";
            $sqls["store_out_stock_sql"] .= "left join hii_member M on M . uid = SOO . admin_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods G on G . id = SOOD . goods_id ";
            $sqls["store_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["store_out_stock_sql"] .= "where {$where["store_other_out_out"]} ";*/
        }


        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }
        return "select * from({$sql}) total order by ptime DESC ";
    }

    /*************
     * 只查询供应商
     * @param $where
     */
    private
    function searchSupply($where)
    {
        $lx = I("get.lx");
        $lx_array = explode(",", $lx);
        $sql = "";
        $sqls = array();

        /*--------------------查询采购商品流水【hii_purchase,hii_purchase_detail】---------------------*/
        if (in_array(6, $lx_array)) {
            $sqls["purchase_out_stock_sql"] = "select P . p_id as id,PD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,PD . g_num as num, ";
            $sqls["purchase_out_stock_sql"] .= "9 as `type`,'采购' as `type_name`,0 as s_type,";
            $sqls["purchase_out_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(P . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_out_stock_sql"] .= "PD . remark as remark,P . p_sn as sn ";
            $sqls["purchase_out_stock_sql"] .= "from hii_purchase_detail PD ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_purchase P on P . p_id = PD . p_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods G on G . id = PD . goods_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_warehouse W on W . w_id = P . warehouse_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_store S on S . id = P . store_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_supply SY on SY . s_id = P . supply_id ";
            $sqls["purchase_out_stock_sql"] .= "left join hii_member M on M . uid = P . admin_id ";
            $sqls["purchase_out_stock_sql"] .= "where {$where["purchase"]} ";
        }
        if (in_array(5, $lx_array)) {
            /*--------------------查询采购退货商品流水【hii_purchase_out,hii_purchase_out_detail】---------------------*/
            $sqls["purchase_in_stock_sql"] = "select PO . p_o_id as id,POD . goods_id as goods_id,G . title as goods_name,GC . title as cate_name,POD . g_num as num, ";
            $sqls["purchase_in_stock_sql"] .= "9 as `type`,'采购' as `type_name`,1 as s_type,";
            $sqls["purchase_in_stock_sql"] .= "M . nickname as admin_nickname,FROM_UNIXTIME(PO . ptime, '%Y-%m-%d %H:%i:%s') as ptime, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(W . w_name, '') as ruku_warehouse_name,ifnull(S . title, '') as ruku_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "ifnull(SY . s_name, '') as fahuo_supply_name,'' as fahuo_warehouse_name,'' as fahuo_store_name, ";
            $sqls["purchase_in_stock_sql"] .= "POD . remark as remark,PO . p_o_sn as sn ";
            $sqls["purchase_in_stock_sql"] .= "from hii_purchase_out_detail POD ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_purchase_out PO on PO . p_o_id = POD . p_o_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods G on G . id = POD . goods_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_goods_cate GC on GC . id = G . cate_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_member M on M . uid = PO . admin_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_warehouse W on W . w_id = PO . warehouse_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_store S on S . id = PO . store_id ";
            $sqls["purchase_in_stock_sql"] .= "left join hii_supply SY on SY . s_id = PO . supply_id ";
            $sqls["purchase_in_stock_sql"] .= "where {$where["purchase_out"]} ";
        }

        foreach ($sqls as $key => $val) {
            if (!empty($sql)) {
                $sql .= "union all " . $val;
            } else {
                $sql .= $val;
            }
        }
        return "select * from({$sql}) total order by ptime DESC ";
    }


    /*******************************************获取搜索语句 end*********************************************************************************************/

    /***************
     * 获取当前页
     ***************/
    private
    function getPageIndex()
    {
        $p = I("get.p");
        return is_null($p) || empty($p) ? 1 : $p;
    }

    /************************
     * 获取搜索日期
     * s_date：开始日期
     * e_date：结束日期
     *****************************/
    private
    function getDates()
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
    private
    function getPageSize()
    {
        $pcount = I("get.pageSize");
        return is_null($pcount) || empty($pcount) ? 15 : $pcount;
    }

    /*********************
     * 检测数组是否空
     */
    private
    function isArrayNull($array)
    {
        if (!is_null($array) && !empty($array) && count($array) > 0) {
            return $array;
        } else {
            return null;
        }
    }


    /**
     * 获取用户操作社区的权限
     */
    public function __member_store_shequ(){
    	// 1.用户有哪些区域的权限：从采购、仓库、门店方面查
    
    	// 可使用的社区
    	$sq_cans = array();
    
    
    	$uid = intval(UID);
    
    	// 获取不到用户信息无权限
    	if (empty($uid)) {
    		return $sq_cans;
    	}
    
    
    	// 超级管理员具备所有社区的权限
    	if (IS_ROOT || in_array(1, $this->group_id)) {
    		$rshequs = M('shequ')->select();
    
    		if (!empty($rshequs)) {
    			foreach ($rshequs as $key => $val) {
    				$sq_cans[] = $val['id'];
    			}
    		}
    
    		return $sq_cans;
    
    	}
    
    	//采购  仓库  门店  9,13,15
    	$group_id  = '';
    	if(in_array(9, $this->group_id)){
    		$group_id .= '9,';
    	}
    	if(in_array(13, $this->group_id)){
    		$group_id .= '13,';
    	}
    	if(in_array(15, $this->group_id)){
    		$group_id .= '15,';
    	}
    	if($group_id == ''){
    		return array();
    	}
    	$group_id = rtrim($group_id,',');
    	$shequs = array();
    	//var_dump($this->group_id);exit;
    
    	//var_dump(IS_ROOT);exit;
    	//echo $uid;exit;
    
    	// 门店社区(含采购)
    	$sql_shequ_store = "select * from hii_member_store where type = 2 and group_id in({$group_id}) and uid = {$uid}";
    
    	$data_shequ_store = M()->query($sql_shequ_store);
    
    	if (!empty($data_shequ_store)) {
    		foreach ($data_shequ_store as $key => $val) {
    		    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
    		        $shequs[] = $val['shequ_id'];
    		    }
    
    		}
    	}
    
    
    	// 门店(含采购)
    	$sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where  type = 1 and group_id in({$group_id}) and uid = {$uid} group by s.shequ_id;";
    
    	$data_shequ2_store = M()->query($sql_shequ2_store);
    
    	if (!empty($data_shequ2_store)) {
    		foreach ($data_shequ2_store as $key => $val) {
    		    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
    		        $shequs[] = $val['shequ_id'];
    		    }
    		}
    	}
    
    
    	// 仓库社区
    	$sql_shequ_warehouse = "select * from hii_member_warehouse where type = 2 and group_id in({$group_id}) and uid = {$uid}";
    
    	$data_shequ_warehouse = M()->query($sql_shequ_warehouse);
    
    	if (!empty($data_shequ_warehouse)) {
    		foreach ($data_shequ_warehouse as $key => $val) {
    		    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
    		        $shequs[] = $val['shequ_id'];
    		    }
    		}
    	}
    
    
    	// 仓库
    	$sql_shequ2_warehouse = "select mw.*,w.shequ_id  from hii_member_warehouse mw left join hii_warehouse w on w.w_id = mw.warehouse_id where type = 1 and group_id in({$group_id}) and uid = {$uid} group by w.shequ_id;";
    
    	$data_shequ2_warehouse = M()->query($sql_shequ2_warehouse);
    	/*
    	 if ($_GET['xy']) {
    	 print_r($data_shequ2_warehouse);
    	 }
    	*/
    	if (!empty($data_shequ2_warehouse)) {
    		foreach ($data_shequ2_warehouse as $key => $val) {
    		    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
    		        $shequs[] = $val['shequ_id'];
    		    }
    		}
    	}
    
    	return $shequs;
    }
}