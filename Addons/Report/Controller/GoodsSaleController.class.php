<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class GoodsSaleController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }

    public function goods_buy()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $s_date = I('s_date');
        $e_date = I('e_date');
        $dwm = I('dwm');
        $gid = I('gid');
        $gname = I('gname');
        if ($dwm == ""){
            $dwm = "d";
        }
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
        $where = '';
        if ($gid != ""){
            $where .= " and d_id = " .$gid;
            $datauser = M('goods')->where('id = ' .$gid)->select();
            $this->assign('gdata', $datauser);
        }
        if ($gname != ""){
            $datauser = M('goods')->where("title like '%" .$gname . "%'")->select();
            $this->assign('gdata', $datauser);
            if(is_array($datauser) && count($datauser) > 0) {
                $goodsiddatas = array_column($datauser,'id');
                $goodsdatastr1 = implode(',',$goodsiddatas);
                $where .= " and d_id in (" .$goodsdatastr1. ") ";
            }else{
                $this->error('没有查到任何商品');
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
        $this->assign('gid', $gid);
        $this->assign('gname', $gname);
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->assign('dwm', $dwm);

        $s_date_1 = date("Y-m-d",(strtotime($s_date) - 3600*24));
        $d1 = strtotime($s_date);
        $d2 = strtotime($e_date);
        $Days = round(($d2-$d1)/3600/24) + 1;

        if($dwm == "d"){
            //按日
            $legendstr = '商品销售趋势~每日';
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(A.cid,0) as cid,ifnull(counttime,0) as counttime,ifnull(buymoney,0) as buymoney from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from  hii_order a,(select @mycnt:=0) b limit " .$Days. ") B left join (";
            $sql .= "
                SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,count(distinct A.id) as cid,sum(B.num) as counttime,sum(B.num*B.price) as buymoney
                FROM `{$db_order_name}` A
                left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'
                 and store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store' " .$where. "
                group by FROM_UNIXTIME(create_time,'%Y-%m-%d')
                order by FROM_UNIXTIME(create_time,'%Y-%m-%d')
            ";
            $sql .= ") A on A.ctime=B.ctime";
        }
        if($dwm == "w"){
            //按周
            $legendstr = '商品销售趋势~每周';
            $arr = $this->get_ld_times($s_date, $e_date, 'w');
        }

        if($dwm == "m"){
            //按月
            $legendstr = '商品销售趋势~每月';
            $arr = $this->get_ld_times($s_date, $e_date, 'm');
            $sql = '';
        }
        if($dwm == "m" || $dwm == "w"){
            $sql = '';
            for ($i = 0; $i < count($arr); $i++) {
                $t = explode(',',$arr[$i]);
                $t1 = $t[0];
                $t2 = $t[1];
                if ($i == 0) {
                    $sql .= "
                        SELECT '" . $arr[$i] . "' as ctime,count(distinct A.id) as cid,sum(B.num) as counttime,sum(B.num*B.price) as buymoney
                        FROM `{$db_order_name}` A
                        left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $t1 . "' and '" . $t2 . "'
                         and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    ";
                } else {
                    $sql .= "
                        union all
                        SELECT '" . $arr[$i] . "' as ctime,count(distinct A.id) as cid,sum(B.num) as counttime,sum(B.num*B.price) as buymoney
                        FROM `{$db_order_name}` A
                        left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $t1 . "' and '" . $t2 . "'
                         and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    ";
                }
            }
        }
        //print_r($sql);die;
        $data = M('order')->query($sql);
        if(is_array($datauser) && count($datauser) > 0){
            $nickname = $datauser[0]['title'];
            $this->assign('goods_name', $nickname);
            $this->meta_title = session('user_store.title') ."销售分析~商品【" .$gname. "】销售趋势";
        }else{
            $this->meta_title = session('user_store.title') ."销售分析~商品销售趋势";
        }
        //分页
        $pcount=365;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        //排序
        //$datamain1 = sort($datamain);
        $lstr1 = '销售数量';
        $lstr2 = '销售金额';
        $lstr3 = '订单数量';
        $dates = array_column($datamain,'ctime');
        $xstr = '"' .implode('","',$dates) .'"';//x轴日期数据
        $datas = array_column($datamain,'counttime');
        $datastr1 = implode(',',$datas);
        $datas = array_column($datamain,'buymoney');
        $datastr2 = implode(',',$datas);
        $datas = array_column($datamain,'cid');
        $datastr3 = implode(',',$datas);

        $this->assign('list', $datamain);
        $this->assign('xstr', $xstr);
        $this->assign('datastr1', $datastr1);
        $this->assign('datastr2', $datastr2);
        $this->assign('datastr3', $datastr3);
        $this->assign('legendstr', $legendstr);
        $this->assign('legendstr1', $lstr1);
        $this->assign('legendstr2', $lstr2);
        $this->assign('legendstr3', $lstr3);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);

        $days = $this -> diffBetweenTwoDays($s_date, $e_date);
        //商品销售TOP10
        $sql = "
        SELECT A.d_id,A.goods_name,A.cate_name,A.buynum,A.buymoney,FORMAT(A.buynum/" .$days. ",2) as avgnum,FORMAT(A.buymoney/" .$days. ",2) as avgmoney FROM
            (
                SELECT d_id,C.title as goods_name,D.title as cate_name,sum(B.num) as buynum,sum(B.num*B.price) as buymoney
                FROM `{$db_order_name}` A
                left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                left join `hii_goods` C on B.d_id=C.id
                left join `hii_goods_cate` D on C.cate_id=D.id
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and A.store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store' " . $where . "
                group by B.d_id,C.title,D.title
                order by buymoney desc
            ) A
            order by A.buymoney desc
        ";
        $datau = M('order')->query($sql);
        $datamainu = array_slice($datau,0,10);
        $this->assign('listu', $datamainu);
        $datamainu25 = array_slice($datau,0,25);
        $this->assign('listu25', $datamainu25);

        $title_top10_1 = '商品销售额TOP10';
        $title_top10_1_sub = $s_date .'~' .$e_date;
        $data_top10_1 = array_column($datamainu,'goods_name');
        $xstr_top10_1 = implode(',',$data_top10_1);//商品TOP10饼图名称
        $xstr_top10_1_1 = "'" .implode("','",$data_top10_1) ."'";//商品TOP10饼图名称
        $datas_top10_1 = array_column($datamainu,'buymoney');
        $datastr_top10_1 = implode(',',$datas_top10_1);//商品TOP10饼图数据
        $this->assign('xstr_top10_1', $xstr_top10_1);
        $this->assign('xstr_top10_1_1', $xstr_top10_1_1);
        $this->assign('datastr_top10_1', $datastr_top10_1);
        $this->assign('title_top10_1', $title_top10_1);
        $this->assign('title_top10_1_sub', $title_top10_1_sub);

        //商品分类销售TOP10
        $sql = "
        SELECT A.cate_name,A.buymoney FROM
            (
                SELECT D.title as cate_name,sum(B.num*B.price) as buymoney
                FROM `{$db_order_name}` A
                left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                left join `hii_goods` C on B.d_id=C.id
                left join `hii_goods_cate` D on C.cate_id=D.id
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and A.store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store' " . $where . "
                group by D.title
                order by buymoney desc
            ) A
            order by A.buymoney desc limit 0,10
        ";
        $datac = M('order')->query($sql);
        $datamainc = array_slice($datac,0,10);
        $this->assign('listc', $datamainc);

        $title_top10_2 = '分类销售额TOP10';
        $title_top10_2_sub = $s_date .'~' .$e_date;
        $data_top10_2 = array_column($datac,'cate_name');
        $xstr_top10_2 = implode(',',$data_top10_2);//商品TOP10饼图名称
        $xstr_top10_2_2 = "'" .implode("','",$data_top10_2) ."'";//商品TOP10饼图名称
        $datas_top10_2 = array_column($datac,'buymoney');
        $datastr_top10_2 = implode(',',$datas_top10_2);//商品TOP10饼图数据
        $this->assign('xstr_top10_2', $xstr_top10_2);
        $this->assign('xstr_top10_2_2', $xstr_top10_2_2);
        $this->assign('datastr_top10_2', $datastr_top10_2);
        $this->assign('title_top10_2', $title_top10_2);
        $this->assign('title_top10_2_sub', $title_top10_2_sub);

        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $this->meta_title .'_'.$s_date .'>>>' .$e_date;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushGoodsBuyList($data,$datau,$datac,$this->meta_title,$legendstr,$fname);
            echo($printfile);die;
        }

        $this->display(T('Addons://Report@GoodsSale/goods_buy'));
    }

    public function goods_sale()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $s_date = I('s_date');
        $e_date = I('e_date');
        $dwm = I('dwm');
        $gid = I('gid');
        $gname = I('gname');
        if ($dwm == ""){
            $dwm = "d";
        }
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
        $where = '';
        if ($gid != ""){
            $where .= " and d_id = " .$gid;
            $datauser = M('goods')->where('id = ' .$gid)->select();
            $this->assign('gdata', $datauser);
        }
        if ($gname != ""){
            $datauser = M('goods')->where("title like '%" .$gname . "%'")->select();
            $this->assign('gdata', $datauser);
            if(is_array($datauser) && count($datauser) > 0) {
                $goodsiddatas = array_column($datauser,'id');
                $goodsdatastr1 = implode(',',$goodsiddatas);
                $where .= " and d_id in (" .$goodsdatastr1. ") ";
            }else{
                $this->error('没有查到任何商品');
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
        $this->assign('gid', $gid);
        $this->assign('gname', $gname);
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->assign('dwm', $dwm);

        $sql = "
                    SELECT FROM_UNIXTIME(A.create_time,'%H') as ctime,DAYOFWEEK(FROM_UNIXTIME(A.create_time,'%Y-%m-%d')) as weekday,ifnull(sum(B.num*B.price),0) as buymoney
                    FROM `{$db_order_name}` A
                    left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                    WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $s_date . "' and '" . $e_date . "'
                        and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    group by FROM_UNIXTIME(A.create_time,'%H'),DAYOFWEEK(FROM_UNIXTIME(A.create_time,'%Y-%m-%d'))
                    ";
        $dataweek = M('order')->query($sql);

        if($dwm == "d"){
            //按日
            $legendstr = '销售状况~每小时';

            for($j = 0;$j < 24;$j++){
                $xdata[$j] = $j .'点';
                $work_rest_xdata[$j] = $j .'点';
                $smoney = 0.00;
                $workmoney = 0.00;
                $restmoney = 0.00;
                for($i = 0;$i < count($dataweek);$i++){
                    if((int)$dataweek[$i]['ctime'] == $j){
                        $smoney += $dataweek[$i]['buymoney'];
                        if((int)$dataweek[$i]['weekday'] == 1 || (int)$dataweek[$i]['weekday'] == 7){
                            //周六日
                            $restmoney += $dataweek[$i]['buymoney'];
                        }else{
                            //工作日
                            $workmoney += $dataweek[$i]['buymoney'];
                        }
                    }
                }
                $ydata[$j] = $smoney;
                $wdata[$j] = $workmoney;
                $rdata[$j] = $restmoney;
            }
        }
        if($dwm == "w"){
            //按周
            for($j = 0;$j < 24;$j++){
                $work_rest_xdata[$j] = $j .'点';
                $workmoney = 0.00;
                $restmoney = 0.00;
                for($i = 0;$i < count($dataweek);$i++){
                    if((int)$dataweek[$i]['ctime'] == $j){
                        if((int)$dataweek[$i]['weekday'] == 1 || (int)$dataweek[$i]['weekday'] == 7){
                            //周六日
                            $restmoney += $dataweek[$i]['buymoney'];
                        }else{
                            //工作日
                            $workmoney += $dataweek[$i]['buymoney'];
                        }
                    }
                }
                $wdata[$j] = $workmoney;
                $rdata[$j] = $restmoney;
            }
            $legendstr = '销售状况~周一到周日';
            for($j = 1;$j < 8;$j++){
                if($j == 1){
                    $weekstr = '周日';
                }
                if($j == 2){
                    $weekstr = '周一';
                }
                if($j == 3){
                    $weekstr = '周二';
                }
                if($j == 4){
                    $weekstr = '周三';
                }
                if($j == 5){
                    $weekstr = '周四';
                }
                if($j == 6){
                    $weekstr = '周五';
                }
                if($j == 7){
                    $weekstr = '周六';
                }
                $xdata[$j] = $weekstr;
                $smoney = 0.00;
                for($i = 0;$i < count($dataweek);$i++){
                    if((int)$dataweek[$i]['weekday'] == $j){
                        $smoney += $dataweek[$i]['buymoney'];
                    }
                }
                $ydata[$j] = $smoney;
            }
        }

        if(is_array($datauser) && count($datauser) > 0){
            $nickname = $datauser[0]['title'];
            $this->assign('gname', $nickname);
            $this->meta_title = session('user_store.title') ."销售分析~商品【" .$nickname. "】销售状况";
        }else{
            $this->meta_title = session('user_store.title') ."销售分析~销售状况";
        }
        //排序
        //$datamain1 = sort($datamain);
        $lstr2 = '销售金额';
        //$dates = array_column($datamain,'ctime');
        $xstr = '"' .implode('","',$xdata) .'"';//x轴日期数据
        //$datas = array_column($datamain,'buymoney');
        $datastr2 = implode(',',$ydata);//y轴金额数据

        $this->assign('list', $dataweek);
        $this->assign('xstr', $xstr);
        $this->assign('datastr2', $datastr2);
        $this->assign('legendstr', $legendstr);
        $this->assign('legendstr2', $lstr2);



        $legendstr_2 = '销售状况~工作日和周六日';
        $lstr1_2 = '工作日销售';
        $lstr2_2 = '周六日销售';

        $xstr_2 = '"' .implode('","',$work_rest_xdata) .'"';//x轴日期数据
        $datastr1_2 = implode(',',$wdata);
        $datastr2_2 = implode(',',$rdata);
        $this->assign('xstr_2', $xstr_2);
        $this->assign('datastr1_2', $datastr1_2);
        $this->assign('datastr2_2', $datastr2_2);
        $this->assign('legendstr_2', $legendstr_2);
        $this->assign('legendstr1_2', $lstr1_2);
        $this->assign('legendstr2_2', $lstr2_2);
        /*$arr = $this->get_ld_times($s_date, $e_date, 'w');
        $sql = '';
        for ($i = 0; $i < count($arr); $i++) {
            $t = explode(',',$arr[$i]);
            $t1 = $t[0];
            $t2 = $t[1];

            $d_s = strtotime($t1);
            $day = date('w',$d_s);
            $d_s2 = strtotime($t2);
            $day2 = date('w',$d_s2);
            if($day != 0 && $day != 6){
                //周一~周五
                if( $i == (count($arr) -1) ) {
                    $s1 = $t1;
                    $s2 = $t2;
                    $s3 = '1970-01-01';
                    $s4 = '1970-01-01';
                }else{
                    $s1 = $t1;
                    $s2 = date("Y-m-d", (strtotime($t2) - 3600 * 24 * 2));
                    $s3 = date("Y-m-d", (strtotime($t2) - 3600 * 24));
                    $s4 = $t2;

                }
            }else{
                //周六~周日
                $s1 = $t1;
                $s2 = $t2;
                $s3 = $t1;
                $s4 = $t2;
            }
            if ($i == 0) {
                $sql .= "
                    SELECT '" . $arr[$i] . "' as ctime,ifnull(sum(B.num*B.price),0) as buymoney,1 as weektype
                    FROM `hii_order` A
                    left join `hii_order_detail` B on A.order_sn=B.order_sn
                    WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $s1 . "' and '" . $s2 . "'
                     and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    UNION all
                    SELECT '" . $arr[$i] . "' as ctime,ifnull(sum(B.num*B.price),0) as buymoney,2 as weektype
                    FROM `hii_order` A
                    left join `hii_order_detail` B on A.order_sn=B.order_sn
                    WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $s3 . "' and '" . $s4 . "'
                     and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    ";
            } else {
                $sql .= "
                    UNION all
                    SELECT '" . $arr[$i] . "' as ctime,ifnull(sum(B.num*B.price),0) as buymoney,1 as weektype
                    FROM `hii_order` A
                    left join `hii_order_detail` B on A.order_sn=B.order_sn
                    WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $s1 . "' and '" . $s2 . "'
                     and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    UNION all
                    SELECT '" . $arr[$i] . "' as ctime,ifnull(sum(B.num*B.price),0) as buymoney,2 as weektype
                    FROM `hii_order` A
                    left join `hii_order_detail` B on A.order_sn=B.order_sn
                    WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $s3 . "' and '" . $s4 . "'
                     and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    ";
            }
        }*/

        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $this->meta_title .'_'.$s_date .'>>>' .$e_date;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushGoodsSaleList($xdata,$ydata,$work_rest_xdata,$wdata,$rdata,$this->meta_title,$legendstr,$legendstr_2,$fname);
            echo($printfile);die;
        }

        $this->display(T('Addons://Report@GoodsSale/goods_sale'));
    }

    function get_time_arr($s,$e,$m_or_w)
    {
        $arr=array();
        $start=strtotime($s." 00:00:00");
        $end=strtotime($e." 23:59:59");
        if($m_or_w=='week')
        {
            $s_w=date('w',$start);
            $f_w=7-$s_w;
        }else
        {
            $allday=date('t',$start);
            $today=date('d',$start);
            $f_w=$allday-$today+1;
        }
        if($f_w){
            $f_end=$start+86400*$f_w-1;
        }else{
            $f_end=$start+86400-1;
        }
        $new_end=$f_end;
        if($end<$new_end){
            $arr[]=array($start,$end);
            return $arr;
        }
        while ($end>$new_end){
            $arr[]=array($start,$new_end);
            $start=$new_end+1;
            if($m_or_w=='week'){
                $day=7;
            }else {
                $day=date('t',$new_end+10);
            }
            $new_end=$new_end+$day*86400;
        }
        if($m_or_w=='week'){
            $fullday=7;
        }else{
            $fullday=date('t',$new_end);}
        $arr[]=array($new_end-$fullday*86400+1,$end);
        return $arr;
    }
    function getdays($day){
        //指定天的周一和周天
        $lastday=date('Y-m-d',strtotime("$day Sunday"));
        $firstday=date('Y-m-d',strtotime("$lastday -6 days"));
        return array($firstday,$lastday);
    }
    function getmonths($day){
        //指定月的第一天和最后一天
        $firstday = date('Y-m-01',strtotime($day));
        $lastday = date('Y-m-d',strtotime("$firstday +1 month -1 day"));
        return array($firstday,$lastday);
    }
    /**
     * 输入开始时间，结束时间，粒度（周，月，季度）
     * @param 参数一：开始时间
     * @param 参数二：结束时间
     * @param 参数三：粒度（周，月，季度）
     * @return 时间段字符串数组
     */
    function get_ld_times($st,$et,$ld){
        if($ld=='w'){
            $timeArr=array();
            $t1=$st;
            $t2_1 = $this->getdays($t1);
            $t2=$t2_1['1'];
            while($t2<$et || $t1<=$et){
                //周为粒度的时间数组
                $timeArr[]=$t1.','.$t2;
                $t1=date('Y-m-d',strtotime("$t2 +1 day"));
                $t2_1 = $this->getdays($t1);
                $t2=$t2_1['1'];
                $t2=$t2>$et?$et:$t2;
            }
            return $timeArr;
        }else if($ld=='m'){
            $timeArr=array();
            $t1=$st;
            $t2_1 = $this->getmonths($t1);
            $t2=$t2_1['1'];
            while($t2<$et || $t1<=$et){
                //月为粒度的时间数组
                $timeArr[]=$t1.','.$t2;
                $t1=date('Y-m-d',strtotime("$t2 +1 day"));
                $t2_1 = $this->getmonths($t1);
                $t2=$t2_1['1'];
                $t2=$t2>$et?$et:$t2;
            }
            return $timeArr;
        }else if($ld=='m4'){
            $tStr=explode('-',$st);
            $month=$tStr['1'];
            if($month<=3){
                $t2=date("$tStr[0]-03-31");
            }else if($month<=6){
                $t2=date("$tStr[0]-06-30");
            }else if($month<=9){
                $t2=date("$tStr[0]-09-30");
            }else{
                $t2=date("$tStr[0]-12-31");
            }
            $t1=$st;
            $t2=$t2>$et?$et:$t2;
            $timeArr=array();
            while($t2<$et || $t1<=$et){
                //月为粒度的时间数组
                $timeArr[]=$t1.','.$t2;
                $t1=date('Y-m-d',strtotime("$t2 +1 day"));
                $t2=date('Y-m-d',strtotime("$t1 +3 months -1 day"));
                $t2=$t2>$et?$et:$t2;
            }
            return $timeArr;
        }else{
            return array('参数错误!');
        }
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
