<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class StoreSaleController extends AddonsController{
    //http://localhost/Admin/Addons/ex_Report/_addons/Report/_controller/StoreJieCunAdmin/_action/index.html
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }

    public function allsale(){
        $pre = C('DB_PREFIX');
        $type = I('type');
        if($type == ""){
            $type = $_POST['type'];
        }
        if($type == ""){
            $type = 1;
        }
        $this->assign('type', $type);

        $store_name = I('store_name');
        if($store_name == ""){
            $store_name = $_POST['store_name'];
        }
        $this->assign('store_name', $store_name);

        $store_name1 = I('store_name1');
        if($store_name1 == ""){
            $store_name1 = $_POST['store_name1'];
        }
        $this->assign('store_name1', $store_name1);


        if($store_name){
            $str1 = "A.title like '%{$store_name}%'";
            $storewhere1 = "title like '%{$store_name}%'";
        }
        if($store_name1){
            $str2 = "A.title like '%{$store_name1}%'";
            $storewhere2 = "title like '%{$store_name1}%'";
        }
        if($str1 !='' && $str2 != ""){
            $str3 ="(" .$str1 . " or " .$str2 .")";
            $storewhere3 ="(" .$storewhere1 . " or " .$storewhere2 .")";
        }else{
            if($str1 !=''){
                $str3 = $str1;
                $storewhere3 = $storewhere1;
            }else {
                if ($str2 != '') {
                    $str3 = $str2;
                    $storewhere3 = $storewhere2;
                }
            }
        }
        if($str3 != ""){
            $_string[] = $str3;
        }
        //分页
        $model = M('store');
        $pcount=15;
        $count = $model->where($storewhere3)->count();
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        $list = $model->where($storewhere3)->order('id')->limit($Page->firstRow.','.$Page->listRows)->select();
        $store_id = array_column($list,'id');
        if($store_id){
            $where1 = " store_id in (" .implode(',',$store_id) . ")";
            $where2 = " And A.id in (" .implode(',',$store_id) . ")";
        }
        if(is_array($_string) && count($_string)>0) {
            $where['_string'] = '1=1 and ' . implode(' and ', $_string);
        }else{
            $where['_string'] = '1=1';
        }
        if($type == 1){
            //今天
            $starttime = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        }else if($type == 2){
            //本周
            $starttime = strtotime(date('Y-m-d', strtotime('this week')));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        }else if($type == 3){
            //本月
            $year = date("Y");
            $month = date("m");
            $allday = date("t");
            $starttime = strtotime($year."-".$month."-1");
            $endtime = strtotime($year."-".$month."-".$allday);

        }else if($type == 4){
            //7天
            $starttime = strtotime(date('Y-m-d', strtotime("7 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));

        }else if($type == 5){
            //30天
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        }
        $field = "A.id as store_id,A.title as store_name";
        $field .= ",ifnull(B.pay_money,0) as pay_money_day,sum(ifnull(C.num,0)) as qty_day,sum(ifnull(C.num,0)*ifnull(C.price,0)) as amount_day";
        $group = "A.id,A.title";
        $whereselect['_string'] = $where['_string'] .$where2;
        $listout = array();
        for($i=0;$i<count($list);$i++){
            $where3 = " And A.id =" .$list[$i]['id'];
            $whereone['_string'] = $where['_string'] .$where3;
            $sql="CREATE TEMPORARY TABLE tmp_sale_order" .$list[$i]['id']. " select store_id,sum(pay_money) as pay_money from hii_order where create_time>" .$starttime. " and create_time<" .$endtime. " and status=5 and store_id=" .$list[$i]['id'] ." group by store_id";
            $data1 = M()->execute($sql);
            $sql="CREATE TEMPORARY TABLE tmp_sale_order_detail" .$list[$i]['id']. " select A.store_id,A.order_sn,B.d_id,B.num,B.price from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn where create_time>" .$starttime. " and create_time<" .$endtime. " and status=5 and store_id=" .$list[$i]['id'];
            $data1 = M()->execute($sql);
            $model = M('store');
            $list1 = $model->alias('A')
                ->join("left join tmp_sale_order" .$list[$i]['id']. " B on A.id=B.store_id")
                ->join("left join tmp_sale_order_detail" .$list[$i]['id']. " C on A.id=C.store_id")
                ->field($field)->where($whereone)->group($group)->order('A.id')
                ->select();
            if(is_array($list1) && count($list1)>0){
                $listout[] = $list1[0];
            }else{
                $outlist['store_id'] = $list[$i]['id'];
                $outlist['store_name'] = $list[$i]['title'];
                $outlist['qty_day'] = "0";
                $outlist['amount_day'] = "0.00";
                $outlist['pay_money_day'] = "0.00";
                $listout[] = $outlist;
            }
        }
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            //导出数据Excel【PHPExcel】
            $starttime = $starttime+1;
            $endtime = $endtime-1;
            $starttimeout = date("Y-m-d",$starttime);
            $endtimeout = date("Y-m-d",$endtime);
            $title = $starttimeout .'>>>' .$endtimeout .'>>>门店销售对比';
            $fname = '门店销售对比_'.$starttimeout.'_'.$endtimeout;

            ob_clean;
            $model = M('store');
            $storelist = $model->select();

            $listoutp = array();
            for($i=0;$i<count($storelist);$i++){
                //获取数据
                $where3 = " And A.id =" .$storelist[$i]['id'];
                $whereone['_string'] = $where['_string'] .$where3;
                $sql="CREATE TEMPORARY TABLE tmp_p_sale_order" .$storelist[$i]['id']. " select store_id,sum(pay_money) as pay_money from hii_order where create_time>" .$starttime. " and create_time<" .$endtime. " and status=5 and store_id=" .$storelist[$i]['id'] ." group by store_id";
                $data1 = M()->execute($sql);
                $sql="CREATE TEMPORARY TABLE tmp_p_sale_order_detail" .$storelist[$i]['id']. " select A.store_id,A.order_sn,B.d_id,B.num,B.price from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn where create_time>" .$starttime. " and create_time<" .$endtime. " and status=5 and store_id=" .$storelist[$i]['id'];
                $data1 = M()->execute($sql);
                $model = M('store');
                $list1 = $model->alias('A')
                    ->join("left join tmp_p_sale_order" .$storelist[$i]['id']. " B on A.id=B.store_id")
                    ->join("left join tmp_p_sale_order_detail" .$storelist[$i]['id']. " C on A.id=C.store_id")
                    ->field($field)->where($whereone)->group($group)->order('A.id')
                    ->select();
                if(is_array($list1) && count($list1)>0){
                    $listoutp[] = $list1[0];
                }else{
                    $outlist['store_id'] = $storelist[$i]['id'];
                    $outlist['store_name'] = $storelist[$i]['title'];
                    $outlist['qty_day'] = "0";
                    $outlist['amount_day'] = "0.00";
                    $outlist['pay_money_day'] = "0.00";
                    $listoutp[] = $outlist;
                }
            }
            $list = $listoutp;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->OutStoreSaleList1Function($list,$title,$fname);
            //$printfile = $this->OutStoreSaleList1Function($list,$title,$fname);
            echo($printfile);die;
        }
        $this->assign('list', $listout);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = '门店销售对比';
        $this->display(T('Addons://Report@StoreSale/allsale'));
        exit;
    }

    public function storesale(){
        $pre = C('DB_PREFIX');
        $store_id = I('id');
        if($store_id == ""){
            $store_id = $_POST['id'];
        }
        if($store_id == '') {
            $this->error('错误的门店id！');
            exit;
        }else{
            $wheremain = " and store_id={$store_id}";
        }
        $this->assign('id', $store_id);
        $type = I('type');
        if($type == ""){
            $type = $_POST['type'];
        }
        if($type == ""){
            $type = 1;
        }
        $this->assign('type', $type);
        $goods_name = I('goods_name');
        if($goods_name == ""){
            $goods_name = $_POST['goods_name'];
        }
        $this->assign('goods_name', $goods_name);
        $cat_name = I('cat_name');
        if($cat_name == ""){
            $cat_name = $_POST['cat_name'];
        }
        $this->assign('cat_name', $cat_name);
        if($store_id){
            $_string[] = "A.id = {$store_id}";
        }
        $wherechild = "";
        if($goods_name){
            $_string[] = "D.title like '%{$goods_name}%'";
            $wherechild .= " and d_id in (select id as d_id from hii_goods where title like '%{$goods_name}%')";
        }
        if($cat_name){
            $_string[] = "E.title like '%{$cat_name}%'";
            $wherechild .= " and d_id in (select id as d_id from hii_goods where cate_id in (select id as cate_id from hii_goods_cate where title like '%{$cat_name}%'))";
        }
        if(is_array($_string) && count($_string)>0) {
            $where['_string'] = '1=1 and ' . implode(' and ', $_string);
        }else{
            $where['_string'] = '1=1';
        }
        if($type == 1){
            //今天
            $starttime = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        }else if($type == 2){
            //本周
            $starttime = strtotime(date('Y-m-d', strtotime('this week')));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        }else if($type == 3){
            //本月
            $year = date("Y");
            $month = date("m");
            $allday = date("t");
            $starttime = strtotime($year."-".$month."-1");
            $endtime = strtotime($year."-".$month."-".$allday);

        }else if($type == 4){
            //7天
            $starttime = strtotime(date('Y-m-d', strtotime("7 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));

        }else if($type == 5){
            //30天
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        }
        $fieldmain = "A.id as store_id,B.id as order_id,A.title as store_name,B.uid,B.create_time,B.order_sn,B.pay_money";
        $field = "C.id,A.id as store_id,A.title as store_name,B.uid,B.create_time,B.id as order_id";
        $field .= ",B.order_sn,B.pay_money,C.num,ifnull(C.price,0) as sale_price,D.id as goods_id,D.title as goods_name,E.title as cate_name";
        $order = "B.order_sn";
        $sql="CREATE TEMPORARY TABLE tmp_sale_order select id,order_sn,store_id,uid,pay_money,create_time from hii_order where create_time>" .$starttime. " and create_time<" .$endtime. " and status=5" .$wheremain;
        $data1 = M()->execute($sql);
        $sql="CREATE TEMPORARY TABLE tmp_sale_order_detail_s select A.id,A.order_sn,B.d_id,B.num,B.price from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn where create_time>" .$starttime. " and create_time<" .$endtime. " and status=5" .$wheremain .$wherechild;
        $data1 = M()->execute($sql);

        $model = M('store');
        $listmain = $model->alias('A')
            ->join('left join tmp_sale_order B on A.id=B.store_id')
            ->join('left join tmp_sale_order_detail_s C on B.order_sn=C.order_sn')
            ->join('left join hii_goods D on C.d_id=D.id')
            ->join('left join hii_goods_cate E on D.cate_id=E.id')
            ->field($fieldmain)->where($where)->order($order)->distinct(true)
            ->select();
        $s_name = $listmain[0]['store_name'];
        if(count($listmain) == 1 && $listmain[0]['order_id'] == ''){
            $listmain =array();
        }
        //分页
        $pcount=15;
        $count=count($listmain);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $listmain1 = array_slice($listmain,$Page->firstRow,$Page->listRows);
        if(count($listmain1) > 0){
            $order_sn_array = array_column($listmain1,'order_sn');
            if($order_sn_array){
                $wheresn = " And C.order_sn in ('" .implode('\',\'',$order_sn_array) . "')";
            }
            $where1 = $where;
            $where1['_string'] .= $wheresn;
        }
        $show= $Page->show();// 分页显示输出﻿
        $model = M('store');
        $listchild = $model->alias('A')
            ->join('left join tmp_sale_order B on A.id=B.store_id')
            ->join('left join tmp_sale_order_detail_s C on B.order_sn=C.order_sn')
            ->join('left join hii_goods D on C.d_id=D.id')
            ->join('left join hii_goods_cate E on D.cate_id=E.id')
            ->field($field)->where($where1)->order($order)
            ->select();
        for($i=0;$i<count($listmain1);$i++){
            for($j=0;$j<count($listchild);$j++){
                if($listmain1[$i]['order_sn'] == $listchild[$j]['order_sn']){
                    $tmpary = array();
                    $tmpary['goods_name'] = $listchild[$j]['goods_name'];
                    $tmpary['cate_name'] = $listchild[$j]['cate_name'];
                    $tmpary['num'] = $listchild[$j]['num'];
                    $tmpary['price'] = $listchild[$j]['sale_price'];
                    $listmain1[$i]['child'][] = $tmpary;
                }
            }
        }
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            //导出数据Excel【PHPExcel】
            date_default_timezone_set("PRC");
            $starttime = $starttime+1;
            $endtime = $endtime-1;
            $starttimeout = date("Y年m月d日",$starttime);
            $endtimeout = date("Y年m月d日",$endtime);
            $title =$s_name .'>>>' .$starttimeout .'>>>' .$endtimeout .'>>>门店销售详情';
            $fname = $s_name .'_门店销售详情_'.$starttimeout.'_'.$endtimeout;
            $listmain2 = $listmain;
            if(count($listmain2) > 0){
                $order_sn_array = array_column($listmain2,'order_sn');
                if($order_sn_array){
                    $wheresn = " And C.order_sn in (" .implode(',',$order_sn_array) . ")";
                }
                $where['_string'] .= $wheresn;
            }
            $listchild = $model->alias('A')
                ->join('left join tmp_sale_order B on A.id=B.store_id')
                ->join('left join tmp_sale_order_detail_s C on B.order_sn=C.order_sn')
                ->join('left join hii_goods D on C.d_id=D.id')
                ->join('left join hii_goods_cate E on D.cate_id=E.id')
                ->field($field)->where($where)->order($order)
                ->select();
            for($i=0;$i<count($listmain2);$i++){
                for($j=0;$j<count($listchild);$j++){
                    if($listmain2[$i]['order_sn'] == $listchild[$j]['order_sn']){
                        $tmpary = array();
                        $tmpary['goods_name'] = $listchild[$j]['goods_name'];
                        $tmpary['cate_name'] = $listchild[$j]['cate_name'];
                        $tmpary['num'] = $listchild[$j]['num'];
                        $tmpary['price'] = $listchild[$j]['sale_price'];
                        $listmain2[$i]['child'][] = $tmpary;
                    }
                }
            }
            ob_clean;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushStoreSaleList2($listmain2,$title,$fname);
            echo($printfile);die;
        }
        $this->assign('list', $listmain1);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);

        $starttime = $starttime+1;
        $endtime = $endtime-1;
        $starttimeout = date("Y年m月d日",$starttime);
        $endtimeout = date("Y年m月d日",$endtime);
        $title = $s_name .'>>>' . $starttimeout .'~' .$endtimeout .'>>>门店销售详情';
        $this->meta_title =$title;
        $this->display(T('Addons://Report@StoreSale/storesale'));
    }

}
