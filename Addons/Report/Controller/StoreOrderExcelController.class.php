<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class StoreOrderExcelController extends AddonsController{

    public function __construct() {
        parent::__construct();
        $this->check_store();
    }

    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        //daytarget每日目标
        $storewhere3 = "";
        $storewhere = "";
        //查询条件：门店参数，日期范围，目标值
        //勾选的门店
        $store_select = I('store');
        if($store_select == ""){
            $store_select = $_POST['store'];
        }
        if($store_select != ""){
            if( !is_array($store_select) ){
                $store_select = explode(",", $store_select);
            }
            $storewhere3 .= " and store_id in (" .implode(',',$store_select) . ")";
            $storewhere .= " and S.id in (" .implode(',',$store_select) . ")";
        }
        $this->assign('store_select', $store_select);

        //时间范围默认当天+昨天，上个月当天+昨天
        //结束日期
        $e_date = I('e_date');
        if($e_date == ""){
            $e_date = $_POST['e_date'];
        }
        if ($e_date == "") {
            $e_date_ymd = date('Y-m-d', time());//今天
        }else{
            $e_date_ymd = $e_date;
        }
        $e_date = strtotime($e_date_ymd);
        //开始日期
        $s_date = I('s_date');
        if($s_date == ""){
            $s_date = $_POST['s_date'];
        }
        if ($s_date == "") {
            $s_date_ymd = $e_date_ymd;//今天;
        }else{
            $s_date_ymd = $s_date;
        }
        $s_date = $s_date_ymd;
        $days = $this->diffBetweenTwoDays($s_date_ymd,$e_date_ymd);
        if($days == 0){$days = 1;}
        //上一批开始日期~结束日期
        $s_date_ymd1 = date('Y-m-d', strtotime("$s_date_ymd -" .$days. " days"));//上一批开始日期;
        $e_date_ymd1 = date('Y-m-d', strtotime("$e_date_ymd -" .$days. " days"));//上一批结束日期;

        //上一期时间段【上一年，上一个月】
        $pre = I('pre');
        if($pre == ""){
            $pre = $_POST['s_date'];
        }
        if ($pre == "") {
            $pre = "m";
        }
        $this->assign('pre', $pre);

        if ($pre == "m") {//月
            $pre_s_date_ymd = date('Y-m-d', strtotime("$s_date_ymd -1 month"));//上一期开始日期
            $pre_e_date_ymd = date('Y-m-d', strtotime("$e_date_ymd -1 month"));//上一期结束日期
            $pre_s_date_ymd1 = date('Y-m-d', strtotime("$s_date_ymd1 -1 month"));//上一期上一批开始日期
            $pre_e_date_ymd1 = date('Y-m-d', strtotime("$e_date_ymd1 -1 month"));//上一期上一批结束日期
        }elseif ($pre == "y"){//年
            $pre_s_date_ymd = date('Y-m-d', strtotime("$s_date_ymd -1 year"));//上一期开始日期
            $pre_e_date_ymd = date('Y-m-d', strtotime("$e_date_ymd -1 year"));//上一期结束日期
            $pre_s_date_ymd1 = date('Y-m-d', strtotime("$s_date_ymd1 -1 year"));//上一期上一批开始日期
            $pre_e_date_ymd1 = date('Y-m-d', strtotime("$e_date_ymd1 -1 year"));//上一期上一批结束日期
        }elseif ($pre == "w"){//周
            $pre_s_date_ymd = date('Y-m-d', strtotime("$s_date_ymd "));//上一期开始日期
            $pre_e_date_ymd = date('Y-m-d', strtotime("$e_date_ymd -1 week"));//上一期结束日期
            $pre_s_date_ymd1 = date('Y-m-d', strtotime("$s_date_ymd1 -1 week"));//上一期上一批开始日期
            $pre_e_date_ymd1 = date('Y-m-d', strtotime("$e_date_ymd1 -1 week"));//上一期上一批结束日期
        }elseif ($pre == "t"){//季度
            $pre_s_date_ymd = date('Y-m-d', strtotime("$s_date_ymd -3 month"));//上一期开始日期
            $pre_e_date_ymd = date('Y-m-d', strtotime("$e_date_ymd -3 month"));//上一期结束日期
            $pre_s_date_ymd1 = date('Y-m-d', strtotime("$s_date_ymd1 -3 month"));//上一期上一批开始日期
            $pre_e_date_ymd1 = date('Y-m-d', strtotime("$e_date_ymd1 -3 month"));//上一期上一批结束日期
        }else{
            $this->error("错误的时间段参数");
        }

        $s_date = $s_date_ymd;
        $e_date = $e_date_ymd;
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);

        $this->assign('s_date_ymd', $s_date_ymd);
        $this->assign('e_date_ymd', $e_date_ymd);
        $this->assign('s_date_ymd1', $s_date_ymd1);
        $this->assign('e_date_ymd1', $e_date_ymd1);
        $this->assign('pre_s_date_ymd', $pre_s_date_ymd);
        $this->assign('pre_e_date_ymd', $pre_e_date_ymd);
        $this->assign('pre_s_date_ymd1', $pre_s_date_ymd1);
        $this->assign('pre_e_date_ymd1', $pre_e_date_ymd1);

        //目标增加订单数
        $add_order = I('add_order');
        if($add_order == ""){
            $add_order = $_POST['add_order'];
        }
        if($add_order == ""){
            $add_order = 0;
        }
        //目标增加订单金额
        $add_money = I('add_money');
        if($add_money == ""){
            $add_money = $_POST['add_money'];
        }
        if($add_money == ""){
            $add_money = 0;
        }
        $this->assign('add_order', $add_order);
        $this->assign('add_money', $add_money);

        //查询条件：社区门店
        $store_id = "";
        $stores = null;
        if(!IS_ROOT && !in_array(1, $this->group_id)){
            // && !in_array(9, $this->group_id)
            $my_shequ = M('MemberStore')->where(array('uid' => UID, 'type' => 2))->select();
            $my_store = array();
            if($my_shequ){
                $shequ_ids = array();
                foreach($my_shequ as $v){
                    $shequ_ids[] = $v['store_id'];
                    $group_shequ[$v['store_id']][] = $v['group_id'];
                }
                $store_data = M('Store')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
                if($store_data){
                    foreach($store_data as $v){
                        $my_store[$v['id']] = array(
                            'group_id' => $group_shequ[$v['shequ_id']],
                            'store_id' => $v['id'],
                        );
                    }
                }
            }
            $_my_store = M('MemberStore')->where(array('uid' => UID, 'type' => 1))->field('group_id,store_id')->select();

            foreach($_my_store as $v){
                if(isset($my_store[$v['store_id']])){
                    !in_array($v['group_id'], $my_store[$v['store_id']]['group_id']) &&  $my_store[$v['store_id']]['group_id'][] = $v['group_id'];
                }else{
                    $my_store[$v['store_id']] = array(
                        'group_id' => array($v['group_id']),
                        'store_id' => $v['store_id'],
                    );
                }
            }
            if(!$my_store){
                $this->error('未授权任何门店管理');
            }
            $my_store_access = array();
            $my_group = array();
            foreach($my_store as $v){
                $my_store_access[] = $v['store_id'];
                $my_group[$v['store_id']] = $v['group_id'];
            }
            if(empty($my_store_access)){
                $this->error('未授权任何门店管理');
            }
            $stores = $my_store_access;
        }
        $shequ = M('Shequ')->field('id, title')->select();
        /*$map = array();
        $map['id'] = array('in',$stores);
        $_my_store1 = M('Store')->where($map)->select();*/
        !$shequ && $shequ = array();
        $where = array();
        $stores && $where['id'] = array('in', $stores);
        $where['status'] = array('eq', 1);
        $store = M('Store')->where($where)->field('id, shequ_id, title, sell_type')->select();
        !$store && $store = array();
        $_store = array();
        foreach($store as $v){
            $_store[$v['shequ_id']][] = $v;
        }
        $shequid = array_column($shequ,'id');
        $this->assign('shequ', $shequ);
        $this->assign('store', $_store);
        $this->assign('store_count', count($store));
        $this->assign('this_group', implode(',',$shequid));
        $_storeary = array_column($store,'id');
        $storewhere3 .= " and A.store_id in (" .implode(',',$_storeary) . ")";
        $storewhere3 .= " and S.status = 1";
        $storewhere .= " and S.id in (" .implode(',',$_storeary) . ")";
        /*
         $s_date_ymd as sday,$e_date_ymd as eday,
        $s_date_ymd1 as sday1,$e_date_ymd1 as eday1,
        $pre_s_date_ymd as sday2,$pre_e_date_ymd as eday2,
        $pre_s_date_ymd1 as sday3,$pre_e_date_ymd1 as eday3
         */
        //A表：今天，B表1：昨天，C表2：上个月今天，D表3：上个月昨天
        $sql = "
        select S.id,S.title," .$add_order ." as s1," .$add_money ." as m1,
        A.store_id as store_id,A.store_name as store_name,ifnull(A.sn_num,0) as sn_num,ifnull(A.money_amount,0) as money_amount,
        B.store_id as store_id1,B.store_name as store_name1,ifnull(B.sn_num,0) as sn_num1,ifnull(B.money_amount,0) as money_amount1,
        C.store_id as store_id2,C.store_name as store_name2,ifnull(C.sn_num,0) as sn_num2,ifnull(C.money_amount,0) as money_amount2,
        D.store_id as store_id3,D.store_name as store_name3,ifnull(D.sn_num,0) as sn_num3,ifnull(D.money_amount,0) as money_amount3
      from hii_store S left join
        (
            select A.store_id,S.title as store_name,count(DISTINCT A.order_sn) as sn_num,sum(B.num*price) as money_amount
             from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn left join hii_store S on A.store_id=S.id
             where FROM_UNIXTIME(A.create_time,'%Y-%m-%d')  between '" .$s_date_ymd. "' and '" .$e_date_ymd. "' $storewhere3
             and A.`status`=5
             group by A.store_id order by A.store_id
         ) A on S.id=A.store_id left join (
            select A.store_id,S.title as store_name,count(DISTINCT A.order_sn) as sn_num,sum(B.num*price) as money_amount
             from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn left join hii_store S on A.store_id=S.id
             where FROM_UNIXTIME(A.create_time,'%Y-%m-%d')  between '" .$s_date_ymd1. "' and '" .$e_date_ymd1. "' $storewhere3
             and A.`status`=5
             group by A.store_id order by A.store_id
         ) B on S.id=B.store_id left join (
            select A.store_id,S.title as store_name,count(DISTINCT A.order_sn) as sn_num,sum(B.num*price) as money_amount
             from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn left join hii_store S on A.store_id=S.id
             where FROM_UNIXTIME(A.create_time,'%Y-%m-%d')  between '" .$pre_s_date_ymd. "' and '" .$pre_e_date_ymd. "' $storewhere3
             and A.`status`=5
             group by A.store_id order by A.store_id
         ) C on S.id=C.store_id left join (
            select A.store_id,S.title as store_name,count(DISTINCT A.order_sn) as sn_num,sum(B.num*price) as money_amount
             from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn left join hii_store S on A.store_id=S.id
             where FROM_UNIXTIME(A.create_time,'%Y-%m-%d')  between '" .$pre_s_date_ymd1. "' and '" .$pre_e_date_ymd1. "' $storewhere3
             and A.`status`=5
             group by A.store_id order by A.store_id
         ) D on S.id=D.store_id
         where 1=1 $storewhere
        ";
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if(IS_POST || $isprint == 1 || $store_select != "" || $add_order != "" || $add_money != "") {
            $data = M('Order')->query($sql);
            for($i = 0;$i < count($data);$i++){
                $sql="
                select A.*,B.nickname from hii_member_store A left join hii_member B on A.uid = B.uid where A.store_id = " .$data[$i]['id'] ." and group_id = 2 and `type`=1
                ";
                $store_memeber = M('MemberStore')->query($sql);
                $data[$i]['member'] = array_column($store_memeber,'nickname');
            }
        }else{
            $data = array();
        }
        //分页
        $pcount=50;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $Page->parameter['s_date']   		=   $s_date;
        $Page->parameter['e_date']   		=   $e_date;
        $Page->parameter['store']       =   implode(',',$store_select);
        $Page->parameter['pre']   		=   $pre;
        $Page->parameter['add_order']   	=   $add_order;
        $Page->parameter['add_money']   	=   $add_money;
        $show= $Page->show();// 分页显示输出﻿



        if($isprint == 1) {
            //导出数据Excel【PHPExcel】" and store_id in (" .implode(',',$store_select) . ")"
            $storelist = M('Store')->where("id in (" .implode(',',$store_select) . ")")->field('GROUP_CONCAT(title) as store_name')->select();
            $storenamelist = $storelist[0]['store_name'];
            $title = '每日目标';
            $fname = 'StoreOrderExcel_'.time().'.xlsx';
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushStoreOrderExcel($data,$title,$fname,$s_date_ymd,$e_date_ymd,$s_date_ymd1,$e_date_ymd1,$pre_s_date_ymd,$pre_e_date_ymd,$pre_s_date_ymd1,$pre_e_date_ymd1,$storenamelist,$pre,$add_order,$add_money);
            //$printfile = $this->OutStoreSaleList1Function($list,$title,$fname);
            echo($printfile);die;
        }
        $this->meta_title = '每日目标';
        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Report@StoreOrderExcel/index'));
        exit;
    }

    function diffBetweenTwoDays ($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }
}
