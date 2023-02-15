<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class SaleController extends AddonsController{
    //http://localhost/Admin/Addons/ex_Report/_addons/Report/_controller/StoreJieCunAdmin/_action/index.html
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }

    public function general()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $s_date = I('s_date');
        $e_date = I('e_date');
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
        //查询的订单主表和子表名字
        $db_order_name = "hii_order";
        $db_order_detail_name = "hii_order_detail";

        $start_year = date("Y", strtotime($s_date));
        $end_year = date("Y", strtotime($e_date));

        if ($start_year == $end_year) {
            //$db_order_name .= "_" . $start_year;
            //$db_order_detail_name .= "_" . $start_year;
        }
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        //30天
        $starttime30 = strtotime(date('Y-m-d', strtotime("30 days ago")));
        $endtime30 = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        //7天
        $starttime7 = strtotime(date('Y-m-d', strtotime("7 days ago")));
        $endtime7 = mktime(0,0,0,date("m"),date("d")+1,date("Y"));

        if (!$s_date) {
            $this->error('请选择开始日期');
        }
        if (!$e_date) {
            $this->error('请选择结束日期');
        }
        if ($s_date > $e_date) {
            $this->error('结束日期不得小于开始日期');
        }

        $whereusercount = array(
            'store_id' => $this->_store_id,
            'status' => 5,
        );
        $where7dayuser = array(
            'create_time' => array('between', array($starttime7, $endtime7)),
            'store_id' => $this->_store_id,
            'status' => 5,
        );
        $where30dayuser = array(
            'create_time' => array('between', array($starttime30, $endtime30)),
            'store_id' => $this->_store_id,
            'status' => 5,
        );
        $model = M('order');
        //所有累计用户
        $usercount = $model->where($whereusercount)->field('uid')->distinct(true)->select();
        $user_count = count($usercount);
        $this->assign('usercount', $user_count);
        //7天活跃用户
        $usercount7 = $model->where($where7dayuser)->field('uid')->distinct(true)->select();
        $user_count7 = count($usercount7);
        $this->assign('usercount7', $user_count7);
        //30天活跃用户
        $usercount30 = $model->where($where30dayuser)->field('uid')->distinct(true)->select();
        $user_count30 = count($usercount30);
        $this->assign('usercount30', $user_count30);
        //7天日客单价
        $avgprice7 = $model->join(' left join ' .$db_order_detail_name .' on ' .$db_order_name .'.order_sn='.$db_order_detail_name .'.order_sn')->where($where7dayuser)->field($db_order_name .'.order_sn,sum(num*price) as pay_money')->group($db_order_name .'.order_sn')->select();
        $avg_price7 = count($avgprice7);
        $pay_money = array_column($avgprice7,'pay_money');
        $sum_money = array_sum($pay_money);
        $avg_p = $sum_money/$avg_price7;
        if(!$avg_p){$avg_p = 0;}
        $avg_p = sprintf("%.2f", $avg_p);
        $this->assign('avg_p', $avg_p);
        $meta_title = '整体趋势';
        $this->meta_title = $meta_title;
        $v = I('v');
        if($v == ''){
            $v = 1;
        }
        $this->assign('v', $v);
        $s_date_1 = date("Y-m-d",(strtotime($s_date) - 3600*24));
        $d1 = strtotime($s_date);
        $d2 = strtotime($e_date);
        $Days = round(($d2-$d1)/3600/24) + 1;
        if($v == 1){
            //每天新用户
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(count(distinct uid),0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join ( select uid, min(FROM_UNIXTIME(create_time,'%Y-%m-%d')) as ctime from {$db_order_name}";
            $sql .= " where store_id = " .$this->_store_id ." and status = 5 and `type`='store' group by uid";
            $sql .= " having min(FROM_UNIXTIME(create_time,'%Y-%m-%d')) between '" .$s_date. "' and '" .$e_date. "' ) A ON A.ctime=B.ctime";
            $sql .= " group by B.ctime order by ctime desc";
            //echo($sql);die;
            $data = M('order')->query($sql);
            $legendstr = '新用户';
        }
        if($v == 2){
            //每天活跃用户
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(uid,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (select count(distinct uid) as uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' group by ctime) A ON A.ctime=B.ctime";
            $sql .= " group by B.ctime order by ctime desc";
            $data = M('order')->query($sql);
            $legendstr = '活跃用户';
        }
        if($v == 3){
            //消费次数
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(order_sn,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (select count(order_sn) as order_sn,FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' group by ctime) A on  A.ctime=B.ctime order by ctime desc";
            $data = M('order')->query($sql);
            $legendstr = '消费次数';
        }
        if($v == 4){
            //消费金额
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(showdata,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (select sum(num*price) as showdata,FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime from `{$db_order_name}` A left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' group by ctime) A on A.ctime=B.ctime order by ctime desc";
            $data = M('order')->query($sql);
            //消费金额
            /*$sql = "SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,sum(num*price) as showdata FROM `hii_order` A left join `hii_order_detail` B on A.order_sn=B.order_sn";
            $sql .= " WHERE FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'";
            $sql .= " and store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' group by FROM_UNIXTIME(create_time,'%Y-%m-%d') order by FROM_UNIXTIME(create_time,'%Y-%m-%d') desc";
            $data = M('order')->query($sql);*/
            $legendstr = '消费金额';
        }
        if($v == 5){
            //累计用户
            $sql = "select count(distinct uid) as sumuid from hii_order where store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')<'" .$s_date. "'";
            $data0 = M('order')->query($sql);
            if(is_array($data0) && count($data0)>0){
                if($data0[0]['sumuid'] == '' || $data0[0]['sumuid'] == 0){
                    $sumuser = 0;
                }else{
                    $sumuser = $data0[0]['sumuid'];
                }
            }else{
                $sumuser = 0;
            }
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(showdata,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (select ctime,count(distinct uid) as showdata from ( select uid, min(FROM_UNIXTIME(create_time,'%Y-%m-%d')) as ctime from {$db_order_name}";
            $sql .= " where store_id = " .$this->_store_id ." and status = 5 and `type`='store' group by uid";
            $sql .= " having min(FROM_UNIXTIME(create_time,'%Y-%m-%d')) between '" .$s_date. "' and '" .$e_date. "' ) A group by ctime) A on A.ctime=B.ctime order by ctime asc";
            $data = M('order')->query($sql);
            for($i = 0;$i < count($data);$i++){
                $sumuser += $data[$i]['showdata'];
                $data[$i]['showdata'] = $sumuser;
            }
            $legendstr = '累计用户';
        }
        if($v == 6){
            //日客单价
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(showdata,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,convert(sum(num*price)/count(distinct A.order_sn),decimal(10,2)) as showdata FROM `{$db_order_name}` A left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn";
            $sql .= " WHERE FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'";
            $sql .= " and store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' group by ctime) A on A.ctime=B.ctime order by ctime desc";
            $data = M('order')->query($sql);
            $legendstr = '日客单价';
        }
        if($v == 7){
            //日均消费次数
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(showdata,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (select c_time as ctime,convert(count(uid)/count(distinct uid),decimal(10,2)) as showdata from (select uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as c_time from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store') as tmp WHERE c_time  between '" .$s_date. "' and '" .$e_date. "' group by c_time) A on A.ctime=B.ctime order by ctime desc";
            $data = M('order')->query($sql);
            $legendstr = '日均消费次数';
        }
        //商品销售数量top10
        $sql = "select d_id as goods_id,B.title as goods_name,sum(num) as showdata from (select FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,B.d_id,B.num from {$db_order_name} A left join {$db_order_detail_name} B on A.order_sn=B.order_sn";
        $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "') A  left join ";
        $sql .= " hii_goods B on A.d_id=B.id group by d_id,B.title order by showdata desc limit 0,10";
        $goodstop10 = M('order')->query($sql);
        //print_r($goodstop10);die;
        $title_top10_1 = '商品销量TOP10';
        $title_top10_1_sub = $s_date .'~' .$e_date;
        $data_top10_1 = array_column($goodstop10,'goods_name');
        $xstr_top10_1 = '"' .implode('","',$data_top10_1) .'"';//商品TOP10饼图名称
        $datas_top10_1 = array_column($goodstop10,'showdata');
        $datastr_top10_1 = implode(',',$datas_top10_1);//商品TOP10饼图数据
        $this->assign('xstr_top10_1', $xstr_top10_1);
        $this->assign('datastr_top10_1', $datastr_top10_1);
        $this->assign('title_top10_1', $title_top10_1);
        $this->assign('title_top10_1_sub', $title_top10_1_sub);
        //分类销量top10
        $sql = "select C.title as cate_name,sum(num) as showdata from (select FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,B.d_id,B.num from {$db_order_name} A left join {$db_order_detail_name} B on A.order_sn=B.order_sn";
        $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "') A  left join ";
        $sql .= " hii_goods B on A.d_id=B.id left join hii_goods_cate C on B.cate_id=C.id group by C.title order by showdata desc limit 0,10";
        $catetop10 = M('order')->query($sql);
        //print_r($goodstop10);die;
        $title_top10_2 = '分类销量TOP10';
        $title_top10_2_sub = $s_date .'~' .$e_date;
        $data_top10_2 = array_column($catetop10,'cate_name');
        $xstr_top10_2 = '"' .implode('","',$data_top10_2) .'"';//商品TOP10饼图名称
        $datas_top10_2 = array_column($catetop10,'showdata');
        $datastr_top10_2 = implode(',',$datas_top10_2);//商品TOP10饼图数据
        $this->assign('xstr_top10_2', $xstr_top10_2);
        $this->assign('datastr_top10_2', $datastr_top10_2);
        $this->assign('title_top10_2', $title_top10_2);
        $this->assign('title_top10_2_sub', $title_top10_2_sub);
        //分页
        $pcount=365;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        //排序
        $datamain1 = sort($datamain);

        $dates = array_column($datamain,'ctime');
        $xstr = '"' .implode('","',$dates) .'"';//x轴日期数据
        $datas = array_column($datamain,'showdata');
        $datastr = implode(',',$datas);

        $this->assign('list', $datamain);
        $this->assign('xstr', $xstr);
        $this->assign('datastr', $datastr);
        $this->assign('legendstr', $legendstr);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Report@Sale/general'));
    }

    public function date_search(){
        //ajax废弃
        /*$s_date = I('post.s_date');
        $e_date = I('post.e_date');
        if (!$s_date) {
            $this->ajaxReturn(array("status"=>0,"msg"=>'请选择开始日期'));
        }
        if (!$e_date) {
            $this->ajaxReturn(array("status"=>0,"msg"=>'请选择结束日期'));
        }
        if (strtotime($s_date) > strtotime($e_date)) {
            $this->ajaxReturn(array("status"=>0,"msg"=>'结束日期不得小于开始日期'));
        }
        $sql = "SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,count(distinct uid) as uid_num FROM `hii_order`";
        $sql .= " WHERE FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'";
        $sql .= " and store_id = " .$this->_store_id ." group by FROM_UNIXTIME(create_time,'%Y-%m-%d')";
        $dayuser = M('order')->query($sql);
        $this->ajaxReturn($dayuser);*/
    }
    public function newuserlist()
    {
        $v = I('v');
        if($v == ''){
            $v = 1;
        }
        $this->assign('v', $v);
        $s_date = I('s_date');
        $e_date = I('e_date');
        $uid = I('uid');
        $where = '';
        if ($uid != ""){
            $where .= ' and uid = ' .$uid;
            $member = M('member')->where('1=1'.$where)->find();
            $membername = '【' .$member['nickname'] .'】';
            $this->assign('uid', $uid);
        }
        if ($s_date == "" && $e_date == "") {
            //默认获取30天新用户
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $s_date = date('Y-m-d',$starttime);
            $e_date = date('Y-m-d',$endtime);
        }else{
            if ($s_date != ""){
                $starttime = strtotime($s_date);
            }
            if ($e_date != ""){
                $endtime = strtotime($e_date);
            }
        }
        if (!$s_date) {
            $this->error('请选择开始日期');
        }
        if (!$e_date) {
            $this->error('请选择结束日期');
        }
        if ($s_date > $e_date) {
            $this->error('结束日期不得小于开始日期');
        }
        //查询的订单主表和子表名字
        $db_order_name = "hii_order";
        $db_order_detail_name = "hii_order_detail";

        $start_year = date("Y", strtotime($s_date));
        $end_year = date("Y", strtotime($e_date));

        if ($start_year == $end_year) {
            //$db_order_name .= "_" . $start_year;
            //$db_order_detail_name .= "_" . $start_year;
        }
        if($v == 1){
            $sql = "select distinct ctime,uid from ( select uid, min(FROM_UNIXTIME(create_time,'%Y-%m-%d')) as ctime from {$db_order_name}";
            $sql .= " where store_id = " .$this->_store_id ." and status = 5 and `type`='store' group by uid";
            $sql .= " having min(FROM_UNIXTIME(create_time,'%Y-%m-%d')) between '" .$s_date. "' and '" .$e_date. "' ) A order by ctime desc";
            $data = M('order')->query($sql);
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '新用户统计';
        }
        if($v == 2){
            $sql = "select distinct c_time as ctime,uid from (select uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as c_time from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5) as tmp WHERE c_time  between '" .$s_date. "' and '" .$e_date. "' order by c_time desc";
            $data = M('order')->query($sql);
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '活跃用户统计';
        }
        if($v == 3 || $v == 4){
            //消费次数，消费金额
            $sql = "select ctime,count(order_sn) as showdata1,sum(pay_money) as showdata2 from (select A.id,A.order_sn,uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,sum(num*price) as pay_money from `{$db_order_name}` A left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'".$where;
            $sql .= " group by A.id,A.order_sn,uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') order by create_time desc) A group by ctime order by ctime desc";
            $data = M('order')->query($sql);
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date .$membername. '每日消费次数/消费金额';
        }
        if($v == 5){
            $sql = "select uid,FROM_UNIXTIME(max(create_time),'%Y-%m-%d') as ctime from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' group by uid order by FROM_UNIXTIME(max(create_time),'%Y-%m-%d') desc";
            $data = M('order')->query($sql);
            $title = '【'.session('user_store.title').'】 累计用户列表';
        }
        if($v == 6){
            //日客单价
            $sql = "SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,convert(sum(num*price)/count(distinct A.order_sn),decimal(10,2)) as showdata FROM `{$db_order_name}` A left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn";
            $sql .= " WHERE FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'";
            $sql .= " and store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' group by FROM_UNIXTIME(create_time,'%Y-%m-%d') order by FROM_UNIXTIME(create_time,'%Y-%m-%d') desc";
            $data = M('order')->query($sql);
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '日客单价';
        }
        if($v == 7){
            //日均消费次数
            $sql = "select c_time as ctime,convert(count(uid)/count(distinct uid),decimal(10,2)) as showdata from (select uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as c_time from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store') as tmp WHERE c_time  between '" .$s_date. "' and '" .$e_date. "' group by c_time order by c_time desc";
            $data = M('order')->query($sql);
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '日均消费次数';
        }
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            if($v == 1 || $v == 2) {
                $printfile = $printmodel->pushSaleNewUserList($data, $title, $fname);
            }
            if($v == 3 || $v == 4) {
                $printfile = $printmodel->pushSaleOrderMoneyList($data, $title, $fname);
            }
            if($v == 5) {
                $printfile = $printmodel->pushSaleUserList($data, $title, $fname);
            }
            if($v == 6) {
                $printfile = $printmodel->pushSaleDayAvgList($data, $title, $fname);
            }
            if($v == 7) {
                $printfile = $printmodel->pushSaleNumList($data, $title, $fname);
            }
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $listmain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        $this->assign('list', $listmain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = $title;
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->display(T('Addons://Report@Sale/newuserlist'));
    }
    //商品销量排行
    public function goods_top10()
    {
        $s_date = I('s_date');
        $e_date = I('e_date');
        $od = I('od');
        if ($od == ""){
            $od = "sum_num";
        }
        if ($s_date == "" && $e_date == "") {
            //默认获取30天销量
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $s_date = date('Y-m-d',$starttime);
            $e_date = date('Y-m-d',$endtime);
        }else{
            if ($s_date != ""){
                $starttime = strtotime($s_date);
            }
            if ($e_date != ""){
                $endtime = strtotime($e_date);
            }
        }
        if (!$s_date) {
            $this->error('请选择开始日期');
        }
        if (!$e_date) {
            $this->error('请选择结束日期');
        }
        if ($s_date > $e_date) {
            $this->error('结束日期不得小于开始日期');
        }
        //查询的订单主表和子表名字
        $db_order_name = "hii_order";
        $db_order_detail_name = "hii_order_detail";

        $start_year = date("Y", strtotime($s_date));
        $end_year = date("Y", strtotime($e_date));

        if ($start_year == $end_year) {
            //$db_order_name .= "_" . $start_year;
            //$db_order_detail_name .= "_" . $start_year;
        }
        $days = $this -> diffBetweenTwoDays($s_date, $e_date);
        //商品销售数量top10
        $sql = "select d_id as goods_id,B.title as goods_name,sum(num) as sum_num,sum(num*price) as sum_amount,FORMAT(sum(num)/" .$days. ",2) as avgnum,FORMAT(sum(num*price)/" .$days. ",2) as avgmoney from (select FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,B.d_id,B.num,B.price from {$db_order_name} A left join {$db_order_detail_name} B on A.order_sn=B.order_sn";
        $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "') A  left join ";
        $sql .= " hii_goods B on A.d_id=B.id group by d_id,B.title order by " .$od. " desc";
        //print_r($sql);die;
        $data = M('order')->query($sql);
        if($od == "sum_num"){
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '商品销量排行';
        }else{
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '商品销售额排行';
        }
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushSaleGoodsTop10($data,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $listmain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        $this->assign('od', $od);
        $this->assign('list', $listmain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = $title;
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->display(T('Addons://Report@Sale/goods_top10'));
    }
    //显示商品具体销售单据数量
    public function show_goods_top10()
    {
        $gid = I('gid');
        $cid = I('cid');
        if ($gid == '' && $cid == '') {
            $this->error('没有id参数');
        }
        if ($gid != ''){
            //商品
            $goodsdata = M('goods')->where('id=' .$gid)->find();
            $strwhere = " and B.d_id = " .$gid;
        }
        if ($cid != ''){
            //分类
            $goodsdata = M('goods_cate')->where('id=' .$cid)->find();
            $strwhere = " and D.id = " .$cid;
        }
        $this->assign('gdata', $goodsdata);
        $s_date = I('s_date');
        $e_date = I('e_date');
        if ($s_date == "" && $e_date == "") {
            //默认获取30天销售数据
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $s_date = date('Y-m-d',$starttime);
            $e_date = date('Y-m-d',$endtime);
        }else{
            if ($s_date != ""){
                $starttime = strtotime($s_date);
            }
            if ($e_date != ""){
                $endtime = strtotime($e_date);
            }
        }
        if (!$s_date) {
            $this->error('请选择开始日期');
        }
        if (!$e_date) {
            $this->error('请选择结束日期');
        }
        if ($s_date > $e_date) {
            $this->error('结束日期不得小于开始日期');
        }
        //查询的订单主表和子表名字
        $db_order_name = "hii_order";
        $db_order_detail_name = "hii_order_detail";

        $start_year = date("Y", strtotime($s_date));
        $end_year = date("Y", strtotime($e_date));

        if ($start_year == $end_year) {
            //$db_order_name .= "_" . $start_year;
            //$db_order_detail_name .= "_" . $start_year;
        }
        //商品销售数量top10
        $sql = "select A.order_sn,FROM_UNIXTIME(A.create_time,'%Y-%m-%d %H:%i:%s') as create_time,B.num,B.price,C.id as goods_id,C.title as goods_name,D.id as cate_id,D.title as cate_name";
        $sql .= " from {$db_order_name} A left join {$db_order_detail_name} B on A.order_sn=B.order_sn  left join hii_goods C on B.d_id=C.id left join hii_goods_cate D on C.cate_id=D.id";
        $sql .= " WHERE A.store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store' and FROM_UNIXTIME(A.create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' " .$strwhere. " order by A.create_time desc";
        $data = M('order')->query($sql);
        $title = '【'.session('user_store.title').'】 ' .$goodsdata['title'] .'>>>' .$s_date. '>>>' .$e_date. '商品销售详情';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushSaleGoodsTop10_Detail($data,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $listmain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        $this->assign('list', $listmain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = $title;
        $this->assign('gid', $gid);
        $this->assign('cid', $cid);
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->display(T('Addons://Report@Sale/show_goods_top10'));
    }
    //分类销量排行
    public function cate_top10()
    {
        $s_date = I('s_date');
        $e_date = I('e_date');
        $od = I('od');
        if ($od == ""){
            $od = "sum_num";
        }
        if ($s_date == "" && $e_date == "") {
            //默认获取30天销量
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $s_date = date('Y-m-d',$starttime);
            $e_date = date('Y-m-d',$endtime);
        }else{
            if ($s_date != ""){
                $starttime = strtotime($s_date);
            }
            if ($e_date != ""){
                $endtime = strtotime($e_date);
            }
        }
        if (!$s_date) {
            $this->error('请选择开始日期');
        }
        if (!$e_date) {
            $this->error('请选择结束日期');
        }
        if ($s_date > $e_date) {
            $this->error('结束日期不得小于开始日期');
        }
        //查询的订单主表和子表名字
        $db_order_name = "hii_order";
        $db_order_detail_name = "hii_order_detail";

        $start_year = date("Y", strtotime($s_date));
        $end_year = date("Y", strtotime($e_date));

        if ($start_year == $end_year) {
            //$db_order_name .= "_" . $start_year;
            //$db_order_detail_name .= "_" . $start_year;
        }
        //分类销售数量top10
        $sql = "select C.id as cate_id,C.title as cate_name,sum(num) as sum_num,sum(num*price) as sum_amount from (select FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,B.d_id,B.num,B.price from {$db_order_name} A left join {$db_order_detail_name} B on A.order_sn=B.order_sn";
        $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and A.`type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "') A left join hii_goods B on A.d_id=B.id";
        $sql .= " left join hii_goods_cate C on B.cate_id=C.id group by C.id,C.title order by " .$od. " desc";
        $data = M('order')->query($sql);
        if($od == "sum_num"){
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '分类销量排行';
        }else{
            $title = '【'.session('user_store.title').'】 ' .$s_date. '>>>' .$e_date. '分类销售额排行';
        }
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushSaleCateTop10($data,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $listmain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        $this->assign('od', $od);
        $this->assign('list', $listmain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = $title;
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->display(T('Addons://Report@Sale/cate_top10'));
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
    function OutAllSale ()
    {
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        $title = "全部商品销售情况报表";
        //全部商品销售数量金额
        $sql = "
              select B.d_id as goods_id,C.title as goods_name,D.title as cate_name,sum(B.num) as sum_num,sum(B.num*B.price) as sum_amount
              from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn
                left join hii_goods C on B.d_id=C.id left join hii_goods_cate D on C.cate_id=D.id
                WHERE A.status = 5 and A.`type`='store' and IFNULL(C.title,'')<>'' group by B.d_id,C.title,D.title
                ";
        $data = M('order')->query($sql);
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushAllSale($data,$title,$fname);
            echo($printfile);die;
        }
    }
    function OutSHSale ()
    {
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        $title = "上海商品销售情况报表";
        //全部商品销售数量金额
        $sql = "
              select B.d_id as goods_id,C.title as goods_name,D.title as cate_name,sum(B.num) as sum_num,sum(B.num*B.price) as sum_amount
              from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn
                left join hii_goods C on B.d_id=C.id left join hii_goods_cate D on C.cate_id=D.id
                WHERE A.status = 5 and A.`type`='store' and IFNULL(C.title,'')<>'' and A.store_id in (select id as store_id from hii_store where shequ_id=10) group by B.d_id,C.title,D.title
                ";
        $data = M('order')->query($sql);
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushAllSale($data,$title,$fname);
            echo($printfile);die;
        }
    }
}
