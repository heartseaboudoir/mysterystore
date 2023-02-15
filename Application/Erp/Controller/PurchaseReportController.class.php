<?php

namespace Erp\Controller;
use Think\Controller;


class PurchaseReportController extends AdminController {

    public function __construct() {
        parent::__construct();
    }

    
    public function index()
    {
        $data = $this->gv();
        $where1 = "";
        //查询条件：供应商参数
        //勾选的供应商
        $supply_select = $data['supply_select'];
        if($supply_select != "" && $supply_select != 0){
            if( !is_array($supply_select) ){
                $supply_select = explode(",", $supply_select);
            }
            $where1 .= " And A.supply_id in (" .implode(',',$supply_select) . ")";
        }
        //筛选条件
        $shequ = implode(',',$_SESSION['can_shequs_cg']);
        $Model1 = M('Shequ');
        $datashequ = $Model1->field('id as shequ_id,title as shequ_name')->where('id in (' . $shequ . ')')->select();
        for($i=0;$i<count($datashequ);$i++){
            $Model2 = M('Supply');
            $datasupply = $Model2->field('s_id,s_name')->where("shequ_id=" .$datashequ[$i]['shequ_id'])->select();
            if(is_array($datasupply) && count($datasupply)>0){
                $datashequ[$i]['supply'] = $datasupply;
            }else{
                $datashequ[$i]['supply'] = array();
            }
        }
        $dataout['supply_list'] = $datashequ;

        $where0 = " And (";
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ('Supply','A.supply_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Store','A.store_id');
        $where0 .= " )";
        //第几页
        $p = $data['page'];
        if($p == "" || $p == 0){
            $p = 1;
        }
        //第几页
        $p1 = $data['p'];
        if($p1 == "" || $p1 == 0){
            $p1 = 1;
        }
        if($p1 != 1){
            $p = $p1;
        }
        //时间范围默认30天
        $s_date = $data['s_date'];
        $e_date = $data['e_date'];
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $e_date = mktime(0,0,0,date("m"),date("d"),date("Y"));
        }else{
            if ($s_date != ""){
                $s_date = strtotime($s_date);
            }
            if ($e_date != ""){
                $e_date = strtotime($e_date);
            }
        }
        $s_date = date('Y-m-d',$s_date);
        $e_date = date('Y-m-d',$e_date);

        $dataout['s_date'] = $s_date;
        $dataout['e_date'] = $e_date;

        $title = $s_date. '>>>' .$e_date. '采购报表';

        $Model = M('Purchase');
        $sql = "
            select FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,
            sum(A1.g_num) as g_nums,sum(A1.b_num) as b_nums,sum(A1.b_num*A1.b_price) as b_amounts,sum(A1.g_num*A1.g_price) as g_amounts,
            sum(A1.g_num*(case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price else G.sell_price end)) as sell_amounts,
            sum(B1.in_num) as in_nums,sum(B1.in_num*B1.g_price) as in_amounts,
            sum(B1.out_num) as out_nums,sum(B1.out_num*B1.g_price) as out_amounts,
            sum(C1.g_num) as in_stock_nums,sum(C1.g_num*C1.g_price) as in_stock_amounts,
            sum(BB1.in_num) as s_in_nums,sum(BB1.in_num*BB1.g_price) as s_in_amounts,
            sum(BB1.out_num) as s_out_nums,sum(BB1.out_num*BB1.g_price) as s_out_amounts,
            sum(CC1.g_num) as s_in_stock_nums,sum(CC1.g_num*CC1.g_price) as s_in_stock_amounts,
            ifnull(sum(D1.g_num),0) as back_nums,ifnull(sum(D1.g_num*D1.g_price),0) as back_amounts
             from  hii_purchase A
              left join hii_purchase_detail A1 on A.p_id=A1.p_id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
              left join hii_goods G on A1.goods_id=G.id
              left join hii_warehouse_in B on A.p_id=B.p_id
              left join hii_warehouse_in_detail B1 on B.w_in_id=B1.w_in_id and A1.goods_id=B1.goods_id
              left join hii_warehouse_in_stock C on B.w_in_id=C.w_in_id
              left join hii_warehouse_in_stock_detail C1 on C.w_in_s_id=C1.w_in_s_id and B1.goods_id=C1.goods_id
              left join hii_store_in BB on A.p_id=BB.p_id
              left join hii_store_in_detail BB1 on BB.s_in_id=BB1.s_in_id and A1.goods_id=BB1.goods_id
              left join hii_store_in_stock CC on BB.s_in_id=CC.s_in_id
              left join hii_store_in_stock_detail CC1 on CC.s_in_s_id=CC1.s_in_s_id and BB1.goods_id=CC1.goods_id
              left join hii_purchase_out D on A.p_id=D.p_id
              left join hii_purchase_out_detail D1 on D.p_o_id=D1.p_o_id and A1.goods_id=D1.goods_id
              where A.p_status = 1 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where1 ." $where0
            group by FROM_UNIXTIME(A.ctime,'%Y-%m-%d')
            order by A.ctime desc";//A.p_id,A.p_sn,
        $datalist = $Model->query($sql);
        //print_r($sql);die;
        //$dataout['sql'] = $sql;

        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/PurchaseReport_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushPurchaseReportList($datalist,$title,$fname);
            $dataout1['filename'] = $printfile;
            $this->response(self::CODE_OK, $dataout1);
        }
        //分页
        //每页共多少条
        $pagesize = $data['pageSize'];
        if($pagesize == "" || $pagesize == 0){
            $pagesize = 2;
        }
        $pcount = $pagesize;
        $count = count($datalist);//得到数组元素个数
        $Page = new \Think\Page($count,$pcount,array(),$p);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter['s_date']   		=   $s_date;
        $Page->parameter['e_date']   		=   $e_date;
        $Page->parameter['supply_select']   =   implode(',',$supply_select);
        $datamain = array_slice($datalist,$Page->firstRow,$Page->listRows);
        $show = $Page->show();// 分页显示输出﻿
        if(is_array($datamain) && count($datamain) > 0){
            for($i=0;$i<count($datamain);$i++){
/*                 $sql = "
                    select FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,A.supply_id,S.s_name,
                    sum(A1.g_num) as g_nums,sum(A1.b_num) as b_nums,sum(A1.b_num*A1.b_price) as b_amounts,sum(A1.g_num*A1.g_price) as g_amounts,
            sum(A1.g_num*(case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as sell_amounts,
                    sum(B1.in_num) as in_nums,sum(B1.in_num*B1.g_price) as in_amounts,
                    sum(B1.out_num) as out_nums,sum(B1.out_num*B1.g_price) as out_amounts,
                    sum(C1.g_num) as in_stock_nums,sum(C1.g_num*C1.g_price) as in_stock_amounts,
                    sum(BB1.in_num) as s_in_nums,sum(BB1.in_num*BB1.g_price) as s_in_amounts,
                    sum(BB1.out_num) as s_out_nums,sum(BB1.out_num*BB1.g_price) as s_out_amounts,
                    sum(CC1.g_num) as s_in_stock_nums,sum(CC1.g_num*CC1.g_price) as s_in_stock_amounts,
                    ifnull(sum(D1.g_num),0) as back_nums,ifnull(sum(D1.g_num*D1.g_price),0) as back_amounts
                     from  hii_purchase A
                      left join hii_purchase_detail A1 on A.p_id=A1.p_id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
            left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
                      left join hii_goods G on A1.goods_id=G.id
                      left join hii_warehouse_in B on A.p_id=B.p_id
                      left join hii_warehouse_in_detail B1 on B.w_in_id=B1.w_in_id and A1.goods_id=B1.goods_id
                      left join hii_warehouse_in_stock C on B.w_in_id=C.w_in_id
                      left join hii_warehouse_in_stock_detail C1 on C.w_in_s_id=C1.w_in_s_id and B1.goods_id=C1.goods_id
                      left join hii_store_in BB on A.p_id=BB.p_id
                      left join hii_store_in_detail BB1 on BB.s_in_id=BB1.s_in_id and A1.goods_id=BB1.goods_id
                      left join hii_store_in_stock CC on BB.s_in_id=CC.s_in_id
                      left join hii_store_in_stock_detail CC1 on CC.s_in_s_id=CC1.s_in_s_id and BB1.goods_id=CC1.goods_id
                      left join hii_purchase_out D on A.p_id=D.p_id
                      left join hii_purchase_out_detail D1 on D.p_o_id=D1.p_o_id and A1.goods_id=D1.goods_id
                      left join hii_supply S on A.supply_id=S.s_id
                      where A.p_status = 1 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d') = '" .$datamain[$i]['ctime']. "'" .$where1 ." $where0
                    group by FROM_UNIXTIME(A.ctime,'%Y-%m-%d'),A.supply_id,S.s_name
                    order by FROM_UNIXTIME(A.ctime,'%Y-%m-%d') desc
              ";
                $datalistchild = $Model->query($sql); */
                $datamain[$i]['child'] = array();
            }
        }
        $dataout['pageSize'] = $pcount;
        $dataout['recordCount'] = $count;
        $dataout['p'] = $p;
        $dataout['pager'] = $show;
        $dataout['pages'] = ceil($count/$pcount);

        $dataout['list'] = $datamain;

        $this->response(self::CODE_OK, $dataout);
    }

    public function view()
    {
        //查看日期详情:该日期采购单，采购退货单
        $data = $this->gv();

        $where1 = "";
        //查询条件：供应商参数
        //勾选的供应商
        $supply_select = $data['supply_select'];
        if($supply_select != "" && $supply_select != 0){
            if( !is_array($supply_select) ){
                $supply_select = explode(",", $supply_select);
            }
            $where1 .= " And A.supply_id in (" .implode(',',$supply_select) . ")";
        }
        $dataout['supply_select'] = $supply_select;
        //筛选条件
        $shequ = implode(',',$_SESSION['can_shequs']);
        $Model1 = M('Shequ');
        $datashequ = $Model1->field('id as shequ_id,title as shequ_name')->where('id in (' . $shequ . ')')->select();
        for($i=0;$i<count($datashequ);$i++){
            $Model2 = M('Supply');
            $datasupply = $Model2->field('s_id,s_name')->where("shequ_id=" .$datashequ[$i]['shequ_id'])->select();
            if(is_array($datasupply) && count($datasupply)>0){
                $datashequ[$i]['supply'] = $datasupply;
            }else{
                $datashequ[$i]['supply'] = array();
            }
        }
        $dataout['supply_list'] = $datashequ;

        //第几页
        $p = $data['page'];
        if($p == "" || $p == 0){
            $p = 1;
        }
        //第几页
        $p1 = $data['p'];
        if($p1 == "" || $p1 == 0){
            $p1 = 1;
        }
        if($p1 != 1){
            $p = $p1;
        }
        //时间范围默认30天
        $s_date = $data['s_date'];
        $e_date = $data['e_date'];
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $e_date = mktime(0,0,0,date("m"),date("d"),date("Y"));
        }else{
            if ($s_date != ""){
                $s_date = strtotime($s_date);
            }
            if ($e_date != ""){
                $e_date = strtotime($e_date);
            }
        }
        $s_date = date('Y-m-d',$s_date);
        $e_date = date('Y-m-d',$e_date);

        $dataout['s_date'] = $s_date;
        $dataout['e_date'] = $e_date;

        $title = $s_date. '>>>' .$e_date. '采购报表详情';

        $where0 = " And (";
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ('Supply','A.supply_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Store','A.store_id');
        $where0 .= " )";
        $Model = M('Purchase');
        $sql="
                    select FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,A.supply_id,S.s_name,
                    A.p_id,A.p_sn,concat('./Admin/Addons/ex_Purchase/_addons/Purchase/_controller/Purchase/_action/view/id/',A.p_id,'.html') as p_sn_link,A.p_status,
                    sum(A1.g_num) as g_nums,sum(A1.b_num) as b_nums,sum(A1.b_num*A1.b_price) as b_amounts,sum(A1.g_num*A1.g_price) as g_amounts,
            sum(A1.g_num*(case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price  else G.sell_price end)) as sell_amounts,
                    B.w_in_id,B.w_in_sn,concat('./Admin/Addons/ex_Warehouse/_addons/Warehouse/_controller/WarehouseIn/_action/view/id/',B.w_in_id,'.html') as w_in_sn_link,B.w_in_status,
                    C.w_in_s_id,C.w_in_s_sn,concat('./Admin/Addons/ex_Warehouse/_addons/Warehouse/_controller/WarehouseInStock/_action/view/id/',C.w_in_s_id,'.html') as w_in_s_sn_link,C.w_in_s_status,
                    sum(B1.in_num) as in_nums,sum(B1.in_num*B1.g_price) as in_amounts,
                    sum(B1.out_num) as out_nums,sum(B1.out_num*B1.g_price) as out_amounts,
                    sum(C1.g_num) as in_stock_nums,sum(C1.g_num*C1.g_price) as in_stock_amounts,
                    BB.s_in_id,BB.s_in_sn,concat('./Admin/Addons/ex_StoreModule/_addons/StoreModule/_controller/StoreIn/_action/view/id/',BB.s_in_id,'.html') as s_in_sn_link,BB.s_in_status,
                    CC.s_in_s_id,CC.s_in_s_sn,concat('./Admin/Addons/ex_StoreModule/_addons/StoreModule/_controller/StoreInStock/_action/view/id/',CC.s_in_s_id,'.html') as s_in_s_sn_link,CC.s_in_s_status,
                    sum(BB1.in_num) as s_in_nums,sum(BB1.in_num*BB1.g_price) as s_in_amounts,
                    sum(BB1.out_num) as s_out_nums,sum(BB1.out_num*BB1.g_price) as s_out_amounts,
                    sum(CC1.g_num) as s_in_stock_nums,sum(CC1.g_num*CC1.g_price) as s_in_stock_amounts,
                    D.p_o_id,D.p_o_sn,concat('./Admin/Addons/ex_Purchase/_addons/Purchase/_controller/PurchaseOut/_action/view/id/',D.p_o_id,'.html') as p_o_sn_link,D.p_o_status,
                    ifnull(sum(D1.g_num),0) as back_nums,ifnull(sum(D1.g_num*D1.g_price),0) as back_amounts
                     from  hii_purchase A
                      left join hii_purchase_detail A1 on A.p_id=A1.p_id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
                      left join hii_goods G on A1.goods_id=G.id
                      left join hii_warehouse_in B on A.p_id=B.p_id
                      left join hii_warehouse_in_detail B1 on B.w_in_id=B1.w_in_id and A1.goods_id=B1.goods_id
                      left join hii_warehouse_in_stock C on B.w_in_id=C.w_in_id
                      left join hii_warehouse_in_stock_detail C1 on C.w_in_s_id=C1.w_in_s_id and B1.goods_id=C1.goods_id
                      left join hii_store_in BB on A.p_id=BB.p_id
                      left join hii_store_in_detail BB1 on BB.s_in_id=BB1.s_in_id and A1.goods_id=BB1.goods_id
                      left join hii_store_in_stock CC on BB.s_in_id=CC.s_in_id
                      left join hii_store_in_stock_detail CC1 on CC.s_in_s_id=CC1.s_in_s_id and BB1.goods_id=CC1.goods_id
                      left join hii_purchase_out D on A.p_id=D.p_id
                      left join hii_purchase_out_detail D1 on D.p_o_id=D1.p_o_id and A1.goods_id=D1.goods_id
                      left join hii_supply S on A.supply_id=S.s_id
                    where A.p_status = 1 and FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where1 ." $where0
                    group by FROM_UNIXTIME(A.ctime,'%Y-%m-%d'),A.supply_id,S.s_name,A.p_id,A.p_sn,A.p_status,
                    B.w_in_id,B.w_in_sn,B.w_in_status,C.w_in_s_id,C.w_in_s_sn,C.w_in_s_status,
                    BB.s_in_id,BB.s_in_sn,BB.s_in_status,CC.s_in_s_id,CC.s_in_s_sn,CC.s_in_s_status
                    order by A.supply_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') desc
        ";
        $datalist = $Model->query($sql);
        //print_r($datalist);die;
        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/PurchaseReportView_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushPurchaseReportView($datalist,$title,$fname);
            $dataout1['filename'] = $printfile;
            $this->response(self::CODE_OK, $dataout1);
        }
        //分页
        //每页共多少条
        $pagesize = $data['pageSize'];
        if($pagesize == "" || $pagesize == 0){
            $pagesize = 2;
        }
        $pcount = $pagesize;
        $count = count($datalist);//得到数组元素个数
        $Page = new \Think\Page($count,$pcount,array(),$p);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter['s_date']   		=   $s_date;
        $Page->parameter['e_date']   		=   $e_date;
        $Page->parameter['supply_select']   		=   implode(',',$supply_select);
        $datamain = array_slice($datalist,$Page->firstRow,$Page->listRows);
        $show = $Page->show();// 分页显示输出﻿
        $dataout['pageSize'] = $pcount;
        $dataout['recordCount'] = $count;
        $dataout['p'] = $p;
        $dataout['pager'] = $show;
        $dataout['pages'] = ceil($count/$pcount);

        $dataout['list'] = $datamain;

        $this->response(self::CODE_OK, $dataout);
    }
    /*
     * 查询单个数据
     * 参数   goods_id  商品id
     * */
    public function getgoods()
    {
        $data = $this->gv();
        $goods_id = $data['goods_id'];
        $bar_code = trim($data['bar_code']);
        $temp_type = $data['temp_type'];
        if($temp_type == 0 || $temp_type == '') {
            $temp_type = 1;
        }
        if(empty($goods_id) && empty($bar_code)){
            $this->response(999, '缺少商品id、条码参数！');
        }
        if(!empty($goods_id)){
            $datafind = M('Goods')->where(array('id' => $goods_id, 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
            if(!$datafind){
                $this->response(999, '没有该商品id！');
            }else{
                $data0 = M('GoodsBarCode')->where(array('goods_id' => $datafind['id']))->find();
                if(!$data0){
                    $this->response(999, '该商品条码没找到！');
                }
                $outdata['goods_id'] = $datafind['id'];
                $outdata['goods_name'] = $datafind['title'];
                $outdata['bar_code'] = $data0['bar_code'];
                $outdata['sell_price'] = $datafind['sell_price'];
                $where['admin_id'] = UID;
                $where['temp_type'] = $temp_type;
                $where['hii_request_temp.status'] = 0;
                $where['goods_id'] = $goods_id;
                $data1 = M('RequestTemp')->where($where)->field('id,b_n_num,b_num,b_price,g_num,remark,value_id')->find();
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datafind['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata['attr_value_array'] =$attr_value_array;
                if(!$data1){
                    //不在临时申请单中
                    $outdata['b_n_num'] = 0;
                    $outdata['b_num'] = 0;
                    $outdata['b_price'] = 0;
                    $outdata['g_num'] = 0;
                    $outdata['value_id'] = 0;
                }else{
                    $outdata['b_n_num'] = $data1['b_n_num'];
                    $outdata['b_num'] = $data1['b_num'];
                    $outdata['b_price'] = $data1['b_price'];
                    $outdata['g_num'] = $data1['g_num'];
                    $outdata['remark'] = $data1['remark'];
                    $outdata['value_id'] = $data1['value_id'];
                }
            }
            $this->response(self::CODE_OK,$outdata);
        }else{
            $data0 = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
            if(!$data0){
                $this->response(999, '该商品条码没找到！');
            }
            $datafind = M('Goods')->where(array('id' => $data0['goods_id'], 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
            if(!$datafind){
                $this->response(999, '该商品条码没找到1！');
            }else{
                $outdata['goods_id'] = $datafind['id'];
                $outdata['goods_name'] = $datafind['title'];
                $outdata['bar_code'] = $data0['bar_code'];
                $outdata['sell_price'] = $datafind['sell_price'];
                $where['admin_id'] = UID;
                $where['temp_type'] = $temp_type;
                $where['hii_request_temp.status'] = 0;
                $where['goods_id'] = $datafind['id'];
                $data1 = M('RequestTemp')->where($where)->field('id,b_n_num,b_num,b_price,g_num,remark,value_id')->find();
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datafind['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata['attr_value_array'] =$attr_value_array;
                if(!$data1){
                    //不在临时申请单中
                    $outdata['b_n_num'] = 0;
                    $outdata['b_num'] = 0;
                    $outdata['b_price'] = 0;
                    $outdata['g_num'] = 0;
                    $outdata['value_id'] = 0;
                }else{
                    $outdata['b_n_num'] = $data1['b_n_num'];
                    $outdata['b_num'] = $data1['b_num'];
                    $outdata['b_price'] = $data1['b_price'];
                    $outdata['g_num'] = $data1['g_num'];
                    $outdata['remark'] = $data1['remark'];
                    $outdata['value_id'] = $data1['value_id'];
                }
            }
            $this->response(self::CODE_OK,$outdata);
        }
    }
    /****************
     * 采购获取单个临时申请信息接口
     * 请求方式：GET
     * 请求参数：id  临时申请ID 必填
     */
    public function getSingleRequestTempInfo_purchase()
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

