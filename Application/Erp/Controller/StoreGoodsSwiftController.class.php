<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-29
 * Time: 11:12
 * 结款单报表
 */

namespace Erp\Controller;

use Think\Controller;

class StoreGoodsSwiftController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        //$this->check_store();
    }

    public function index()
    {
        $this->check_store();//检测是否已选择仓库
        $store_id = $this->_store_id;
        $GoodsStoreNewSwiftIndexModel = M("GoodsStoreNewSwiftIndex");
        $sql = "select `id`,`year`,`month`,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as ctime ";
        $sql .= "from hii_goods_store_new_swift_index where store_id={$store_id} order by id desc ";
        $data = $GoodsStoreNewSwiftIndexModel->query($sql);
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($data);

        $this->response(self::CODE_OK, $result);
    }

    public function ls()
    {
        $this->check_store();//检测是否已选择仓库
        $result = $this->getLs(true);
        $this->response(self::CODE_OK, $result);
    }

    public function exportLsExcel()
    {
        $this->check_store();//检测是否已选择仓库
        $result = $this->getLs(false);
        ob_clean;
        $title = $result["year"] . "." . $result["month"] . '结款单';
        $fname = './Public/Excel/GoodsStoreSwift_' . time() . '.xlsx';
        $printmodel = new \Addons\Report\Model\StoreGoodsSwiftModel();

        $printfile = $printmodel->goods_store_new_swift_index_Excel($result, $title, $fname);
        $this->response(self::CODE_OK, $printfile);
    }

    /*******************
     * 详细查看
     * 请求方式：GET
     * 请求参数：id   索引结算单ID 必须
     *           did  结算单详细表ID  必须
     * 日期：2018-01-30
     */
    public function view()
    {
        $this->check_store();//检测是否已选择仓库
        $id = I("get.id");
        $did = I("get.did");
        $store_id = $this->_store_id;
        $GoodsStoreNewSwiftIndexModel = M("GoodsStoreNewSwiftIndex");
        $data = $GoodsStoreNewSwiftIndexModel->where(" id={$id} and store_id={$store_id} ")->limit(1)->select();
        if ($this->isArrayNull($data) == null) {
            $this->response(0, "数据不存在");
        }
        $year = $data[0]["year"];
        $month = $data[0]["month"];
        $GoodsStoreNewSwiftYearModel = M("GoodsStoreNewSwift" . $year);
        $sql = "select GSNW.goods_id, G.title as goods_name , GSNW.prev_month_num,GSNW.now_month_num, ";
        $sql .= "GSNW.in_num,GSNW.out_num,GSNW.find_num,GSNW.sell_num,GSNW.price,GSNW.result_num,GSNW.result_money, ";
        $sql .= "GSNW.system_lost_num ";
        $sql .= "from hii_goods_store_new_swift_{$year} GSNW ";
        $sql .= "left join hii_goods G on G.id=GSNW.goods_id ";
        $sql .= "where GSNW.id={$did} and GSNW.store_id={$store_id} and GSNW.year={$year} and GSNW.month={$month} ";
        $data = $GoodsStoreNewSwiftYearModel->query($sql);
        if ($this->isArrayNull($data) == null) {
            $this->response(0, "数据不存在");
        }
        $data[0]["lost_rand"] = ($data[0]["result_num"] > 0 ? (round($data[0]["system_lost_num"] / $data[0]["result_num"] * 100, 2)) : 0) . "%";
        $this->response(self::CODE_OK, $data[0]);
    }

    private function getLs($usePager)
    {
        $this->check_store();//检测是否已选择仓库
        $store_id = $this->_store_id;
        $id = I("get.id");
        $GoodsStoreNewSwiftIndexModel = M("GoodsStoreNewSwiftIndex");
        $data = $GoodsStoreNewSwiftIndexModel->where("id={$id} and store_id={$store_id} ")->order(" id desc ")->limit(1)->select();
        if ($this->isArrayNull($data) == null) {
            $this->response(0, "记录不存在");
        }
        $year = $data[0]["year"];
        $month = $data[0]["month"];

        $next_date = $year . "-" . $month;
        $base = strtotime(date('Y-m', strtotime($next_date)) . '-01 00:00:01');
        $next_year = date('Y', strtotime('+1 month', $base));
        $next_month = date('n', strtotime('+1 month', $base));

        /******合计数量******/
        $total_prev_month_num = 0;
        $total_now_month_num = 0;
        $total_in_num = 0;
        $total_out_num = 0;
        $total_sell_num = 0;
        $total_sell_money = 0;
        $total_inprice_money = 0;
        $total_gross_profit = 0;
        $total_result_num = 0;
        $total_result_money = 0;
        $total_inout_num = 0;
        $total_system_lost_num = 0;
        $total_lost_money = 0;


        $GoodsStoreNewSwiftYearModel = M("GoodsStoreNewSwift{$year}");
        $sql = "select GSNS.id,GSNS.goods_id,GSNS.now_month_num,GSNS.prev_month_num,GSNS.in_num,GSNS.out_num,GSNS.find_num,GSNS.system_lost_num, ";
        $sql .= "GSNS.sell_num,GSNS.result_num,GSNS.result_money,GSNS.lost_rand,GSNS.price,GSNS.inprice,GSNS.inprice_money,GSNS.year,GSNS.month,GSNS.store_id, ";
        $sql .= "FROM_UNIXTIME(GSNS.create_time,'%Y-%m-%d %H:%i:%s') as ctime,GSNS.status,GSNS.inout_num,G.title as goods_name,GC.title as cate_name,ifnull(GSS.num,0) as stock_num,ifnull(GSS.g_price,0) as g_price ";
        $sql .= "from hii_goods_store_new_swift_{$year} GSNS ";
        $sql .= "left join hii_goods G on G.id=GSNS.goods_id ";
        $sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
        $sql .= "left join hii_goods_store_snapshot_{$next_year} GSS on GSS.`month`={$next_month} and GSS.`year`={$next_year} and GSS.store_id=GSNS.store_id and GSS.goods_id=GSNS.goods_id ";
        $sql .= "where GSNS.store_id={$store_id} and GSNS.year={$year} and GSNS.month={$month} order by GSNS.goods_id asc ";
        $data = $GoodsStoreNewSwiftYearModel->query($sql);
        if ($usePager) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿

            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        foreach ($data as $key => $val) {
            if ($val["status"] == 1) {
                $data[$key]["status_name"] = "上架";
            } elseif ($val["status"] == 2) {
                $data[$key]["status_name"] = "下架";
            } elseif ($val["status"] == -1) {
                $data[$key]["status_name"] = "已删除";
            }
            $data[$key]["sell_money"] = round(($val["sell_num"] * $val["price"]), 2);
            $data[$key]["lost_money"] = round(($val["system_lost_num"] * $val["price"]), 2);
            $data[$key]["lost_rand"] = ($val["result_money"] > 0 ? (round($val["price"] * $val["system_lost_num"] / $val["result_money"] * 100, 2)) : 0);
            $data[$key]["gross_profit"] = $data[$key]["sell_money"] - $val["inprice_money"];
            $data[$key]["gross_profit_rate"] = $data[$key]["sell_money"] > 0 ? round(($data[$key]["gross_profit"] / $data[$key]["sell_money"] * 100), 2) : 0;
            $data[$key]["stock_g_amounts"] = $val["stock_num"] * $val["g_price"];

            /************计算总数*****************/
            $total_prev_month_num += $val["prev_month_num"];
            $total_now_month_num += $val["now_month_num"];
            $total_in_num += $val["in_num"];
            $total_out_num += $val["out_num"];
            $total_sell_num += $val["sell_num"];
            $total_sell_money += $data[$key]["sell_money"];
            $total_inprice_money += $val["inprice_money"];
            $total_result_num += $val["result_num"];
            $total_result_money += $val["result_money"];
            $total_inout_num += $val["inout_num"];
            $total_system_lost_num += $val["system_lost_num"];
            $total_lost_money += $data[$key]["lost_money"];

        }
        $result["year"] = $year;
        $result["month"] = $month;
        $result["data"] = $this->isArrayNull($data);

        /*********页脚总计信息****************/
        $result["total_prev_month_num"] = $total_prev_month_num;
        $result["total_now_month_num"] = $total_now_month_num;
        $result["total_in_num"] = $total_in_num;
        $result["total_out_num"] = $total_out_num;
        $result["total_sell_num"] = $total_sell_num;
        $result["total_sell_money"] = $total_sell_money;
        $result["total_inprice_money"] = $total_inprice_money;
        $result["total_result_num"] = $total_result_num;
        $result["total_result_money"] = $total_result_money;
        $result["total_inout_num"] = $total_inout_num;
        $result["total_system_lost_num"] = $total_system_lost_num;
        $result["total_lost_money"] = $total_lost_money;

        return $result;
    }
    /**
     * 全局结款单列表
     */
    public function index_overall_situation()
    {
        $GoodsStoreNewSwiftIndexModel = M("GoodsStoreNewSwiftIndex");
        $sql = "select distinct `year`,`month`,FROM_UNIXTIME(create_time,'%Y-%m-01') as ctime ";
        $sql .= "from hii_goods_store_new_swift_index where store_id not in(select id from hii_store where shequ_id = 18) order by id desc ";
        $data = $GoodsStoreNewSwiftIndexModel->query($sql);
        
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿
        
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($data);
        
        $this->response(self::CODE_OK, $result);
    }
    /**
     * 全局单月结款单详细
     */
    public function ls_overall_situation(){
        $isprint = I('isprint',0,'intval');
        $year = I('year',0,'intval');
        $month = I('month',0,'intval');
        $GoodsStoreNewSwiftIndexModel = M("GoodsStoreNewSwiftIndex");
        $data = $GoodsStoreNewSwiftIndexModel->where("year={$year} and month={$month} ")->order(" id desc ")->limit(1)->select();
        if ($this->isArrayNull($data) == null) {
            $this->response(0, "记录不存在");
        }
        
        $next_date = $year . "-" . $month;
        $base = strtotime(date('Y-m', strtotime($next_date)) . '-01 00:00:01');
        $next_year = date('Y', strtotime('+1 month', $base));
        $next_month = date('n', strtotime('+1 month', $base));
        
        /******合计数量******/
        $total_prev_month_num = 0;
        $total_now_month_num = 0;
        $total_in_num = 0;
        $total_out_num = 0;
        $total_sell_num = 0;
        $total_sell_money = 0;
        $total_inprice_money = 0;
        $total_gross_profit = 0;
        $total_result_num = 0;
        $total_result_money = 0;
        $total_inout_num = 0;
        $total_system_lost_num = 0;
        $total_lost_money = 0;
        $total_new_month_g_inprice_money = 0;
        
        $GoodsStoreNewSwiftYearModel = M("GoodsStoreNewSwift{$year}");
        $sql = "SELECT
                	GSNS.store_id,
                	S.title,
                    SQ.title as shequ_name,
                	SUM(GSNS.result_num) result_num,
                	SUM(GSNS.result_money) result_money,
                	sum(GSNS.sell_num) sell_num,
                	sum(GSNS.sell_num * GSNS.price) sell_money,
                	sum(GSNS.inprice_money) inprice_money,
                	sum(GSNS.system_lost_num) system_lost_num,
                	sum(GSNS.system_lost_num * GSNS.price) lost_money,
                	sum(GSNS.inout_num) inout_num,
                	sum(GSNS.prev_month_num) prev_month_num,
                	sum(GSNS.now_month_num) now_month_num,
                	sum(GSNS.now_month_num * GSS.g_price) g_inprice_money,
                	sum(GSNS.in_num) in_num,
                	sum(GSNS.out_num) out_num
                FROM
                	hii_goods_store_new_swift_{$year} GSNS
                LEFT JOIN hii_store S ON GSNS.store_id = S.id
                LEFT JOIN hii_shequ SQ ON SQ.id = S.shequ_id
                LEFT JOIN hii_goods_store_snapshot_{$next_year} GSS ON GSS.`month` = {$next_month}
                AND GSS.`year` = {$next_year}
                AND GSS.store_id = GSNS.store_id
                AND GSS.goods_id = GSNS.goods_id
                WHERE
                	GSNS.`year` ={$year}
                AND GSNS.`month` = {$month}
                AND S.shequ_id != 3 AND S.shequ_id != 18
                GROUP BY
                	GSNS.store_id
                ORDER BY
                	GSNS.store_id";
        $data = $GoodsStoreNewSwiftYearModel->query($sql);
        $result = array();
        if ($isprint ==0) {
            //分页
            $pcount = $this->getPageSize();
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            
            $result["pageSize"] = $pcount;
            $result["recordCount"] = $count;
            $result["p"] = $this->getPageIndex();
            $result["pager"] = $show;
        }
        foreach ($data as $key => $val) {
 
                $data[$key]["lost_rand"] = ($val["result_num"] > 0 ? (round($val["system_lost_num"] / $val["result_num"] * 100, 2)) : 0);
                $data[$key]["gross_profit"] = round($val["sell_money"] - $val["inprice_money"],2);
                $data[$key]["gross_profit_rate"] = $data[$key]["sell_money"] > 0 ? round(($data[$key]["gross_profit"] / $data[$key]["sell_money"] * 100), 2) : 0;
            if($isprint == 1){
                /************计算总数*****************/
                $total_prev_month_num += $val["prev_month_num"];
                $total_now_month_num += $val["now_month_num"];
                $total_new_month_g_inprice_money += $val['g_inprice_money'];
                $total_in_num += $val["in_num"];
                $total_out_num += $val["out_num"];
                $total_sell_num += $val["sell_num"];
                $total_sell_money += $data[$key]["sell_money"];
                $total_inprice_money += $val["inprice_money"];
                $total_result_num += $val["result_num"];
                $total_result_money += $val["result_money"];
                $total_inout_num += $val["inout_num"];
                $total_system_lost_num += $val["system_lost_num"];
                $total_lost_money += $data[$key]["lost_money"];
            }
        }
        $result["year"] = $year;
        $result["month"] = $month;
        $result["data"] = $this->isArrayNull($data);
        if($isprint == 0){
            $this->response(self::CODE_OK, $result);
        }
        /*********页脚总计信息****************/
        $result["total_prev_month_num"] = $total_prev_month_num;
        $result["total_now_month_num"] = $total_now_month_num;
        $result["total_in_num"] = $total_in_num;
        $result["total_out_num"] = $total_out_num;
        $result["total_sell_num"] = $total_sell_num;
        $result["total_sell_money"] = $total_sell_money;
        $result["total_inprice_money"] = $total_inprice_money;
        $result["total_result_num"] = $total_result_num;
        $result["total_result_money"] = $total_result_money;
        $result["total_inout_num"] = $total_inout_num;
        $result["total_system_lost_num"] = $total_system_lost_num;
        $result["total_lost_money"] = $total_lost_money;
        $result["total_new_month_g_inprice_money"] = $total_new_month_g_inprice_money;
        if($isprint == 1){
            ob_clean;
            $title = $result["year"] . "." . $result["month"] . '全局结款单';
            $fname = './Public/Excel/GoodsStoreSwift_overall_situation_' . time() . '.xlsx';
            $printmodel = new \Addons\Report\Model\StoreGoodsSwiftModel();
            
            $printfile = $printmodel->overall_situation_goods_store_new_swift_index_Excel($result, $title, $fname);
            $this->response(self::CODE_OK, $printfile);
        }
     
        
    }

    /**
     * 全局订单首页
     * @param time_type 时间状态 1日 2周 3月
     */
    public function index_order_situation(){
        $time_type = I('time_type',2,'intval');
        $time = date('Y');
        $StoreOrderModel = M("StoreOrder_{$time}");
       $data = $StoreOrderModel->field("distinct s_time,e_time,FROM_UNIXTIME(ctime,'%Y-%m-%d')ctime,time_type")->where(array('time_type'=>$time_type,'store_id'=>array('exp',"not in(select id from hii_store where shequ_id = 18)")))->order('s_time desc')->select();
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿
        foreach ($data as $key=>$val){
            if($time_type == 2){
                $day=date('d',$val['e_time']); //今天几号
                $d = ceil($day/7); //计算是第几个星期几
                $data[$key]['remark'] = date('Y年m月',$val['e_time']).'-第'.$d.'周';
            }else{
                $data[$key]['remark'] = '';
            }

            $data[$key]['s_time'] =date('Y-m-d',$val['s_time']);
            $data[$key]['e_time'] =date('Y-m-d',$val['e_time']);
        }
        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($data);

        $this->response(self::CODE_OK, $result);
    }
    /**
     * 全局订单查询列表
     * @param time_type 时间状态 1日 2周 3月
     * @param s_time 开始时间
     * @param isprint 导出 1
     *            shequ_id     社区ID     必须
     *           goods_name   商品名称   非必须
     */
    public function ls_order_situation(){
        $time_type = I('time_type',2,'intval');
        $s_time = I('s_time','','trim');
        $isprint = I('isprint',0,'intval');
        $shequ_id = I('shequ_id',0,'intval');
        $goods_name = I('goods_name','','trim');
        $time = date('Y');
        $StoreOrderModel = M("StoreOrder_{$time}");
        $ShequModel = M("Shequ");
        $where = array();
        $result = array();
        $where['time_type'] = $time_type;
        $where['store_id'] = array('exp',"not in(select id from hii_store where shequ_id = 18)");
        if($s_time){
            $where['s_time'] = strtotime($s_time);
        }else{
            $this->response(999, '缺少时间');
        }
        if($shequ_id){
            $where['shequ_id'] = $shequ_id;
        }

        if($goods_name){
            $where['store_name'] = array('like',"%{$goods_name}%");
        }
        $data = array();
        if($shequ_id){
            $data = $StoreOrderModel->field("id,s_time,FROM_UNIXTIME(s_time,'%Y-%m-%d')s_time,FROM_UNIXTIME(e_time,'%Y-%m-%d')e_time,store_id,store_name,num,shequ_name,pay_money,money,inout_money,order_count")->where($where)->order('shequ_id asc,store_id asc')->select();

        }
        $result["s_time"] = $s_time;
        $result['remark'] = '';
        if($time_type == 1){
            $result["e_time"] = date('Y-m-d',strtotime( $s_time.'+1 day'));
        }elseif($time_type == 2){
            $sss_time = strtotime($s_time);
            $ee_time = strtotime( '+6 day',$sss_time);
            $day=date('d',$ee_time); //今天几号
            $d = ceil($day/7); //计算是第几个星期几
            $result['remark'] =  '--'.date('Y年m月',$ee_time).'-第'.$d.'周';
            $result["e_time"] = date('Y-m-d',strtotime( '+6 day',$sss_time));
        }elseif($time_type == 3){
            $result["e_time"] = date('Y-m-d',strtotime( substr($s_time,0,-3).'+ 1 month -1 day'));
        }
        if($isprint == 1){
            ob_clean;
            $title = $result["s_time"]. "至" . $result["e_time"] . '全局订单';
            $fname = './Public/Excel/GoodsStoreSwift_order_situation_' . time() . '.xlsx';
            $printmodel = new \Addons\Report\Model\StoreGoodsSwiftModel();

            $printfile = $printmodel->order_situation_store_index_Excel($data, $title, $fname);
            $this->response(self::CODE_OK, $printfile);
        }
        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿


        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($data);

        $result["shequ"] = $ShequModel->query(" select id as shequ_id,title as shequ_name from hii_shequ  order by id ASC ");
        $this->response(self::CODE_OK, $result);
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
            $group_id = '9,';
        }
        if(in_array(13, $this->group_id)){
            $group_id = '13,';
        }
        if(in_array(15, $this->group_id)){
            $group_id = '15,';
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
                $shequs[] = $val['store_id'];

            }
        }


        // 门店(含采购)
        $sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where  type = 1 and group_id in({$group_id}) and uid = {$uid} group by s.shequ_id;";

        $data_shequ2_store = M()->query($sql_shequ2_store);

        if (!empty($data_shequ2_store)) {
            foreach ($data_shequ2_store as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs)) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }


        // 仓库社区
        $sql_shequ_warehouse = "select * from hii_member_warehouse where type = 2 and group_id in({$group_id}) and uid = {$uid}";

        $data_shequ_warehouse = M()->query($sql_shequ_warehouse);

        if (!empty($data_shequ_warehouse)) {
            foreach ($data_shequ_warehouse as $key => $val) {
                if (!in_array($val['warehouse_id'], $shequs)) {
                    $shequs[] = $val['warehouse_id'];
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
                if (!in_array($val['shequ_id'], $shequs)) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }

        return $shequs;
    }
}