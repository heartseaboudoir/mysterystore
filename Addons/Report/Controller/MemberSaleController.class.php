<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class MemberSaleController extends AddonsController{
    //http://localhost/Admin/Addons/ex_Report/_addons/Report/_controller/StoreJieCunAdmin/_action/index.html
    public function __construct() {
        parent::__construct();
        //$this->check_store();
    }

    public function m_sale()
    {
        $this->check_store();//检测是否已选择仓库
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $s_date = I('s_date');
        $e_date = I('e_date');
        if ($s_date == "" && $e_date == "") {
            //默认获取30天月活用户
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
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
        $starttime = date('Y-m-d',$starttime);
        $endtime = date('Y-m-d',$endtime);
        //FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'
        //查询的订单主表和子表名字
        $db_order_name = "hii_order";
        $db_order_detail_name = "hii_order_detail";

        $start_year = date("Y", strtotime($starttime));
        $end_year = date("Y", strtotime($endtime));

        if ($start_year == $end_year) {
            //$db_order_name .= "_" . $start_year;
            //$db_order_detail_name .= "_" . $start_year;
        }
        $where = array(
            "FROM_UNIXTIME(A.create_time,'%Y-%m-%d')" => array('between', array($starttime, $endtime)),
            'store_id' => $this->_store_id,
            'status' => 5,
        );
        $whereall = array(
            'store_id' => $this->_store_id,
            'status' => 5,
        );
        $field = "A.uid,sum(B.num*B.price) as p_m";
        $order = "p_m desc";
        $group = "A.uid";
        $model = M('order');
        $sql = "
          SELECT A.uid,sum(B.num*B.price) as p_m FROM {$db_order_name} A left join {$db_order_detail_name} B on A.order_sn=B.order_sn
          WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') BETWEEN '$starttime' AND '$endtime'
           AND `store_id` = " .$this->_store_id. " AND `status` = 5
            GROUP BY A.uid ORDER BY p_m desc
        ";
        $data = $model-> query($sql);
        //$data = $model->alias('A')->join('left join hii_order_detail B on A.order_sn=B.order_sn')->where($where)->field($field)->order($order)->group($group)->select();
        $datauid = $model->where($whereall)->field('uid')->distinct(true)->select();
        $usernum = count($datauid);
        $this->assign('usernum', $usernum);
        $title = '【'.session('user_store.title').'】 活跃用户' .$s_date. '>>>' .$e_date. '统计';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = session('user_store.title') .'_活跃用户统计_'.$s_date .'>>>' .$e_date;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushMemberSaleList1($data,$title,$fname);
            //$printfile = $this->pushStoreJieCunList2($list,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=20;
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
        $viewtype = I('v');
        if($viewtype == ""){
            for($i=0;$i<count($listmain);$i++) {
                $listmain[$i]['nick_name'] = get_nickname($listmain[$i]['uid']);
            }
            $nickary = array_column($listmain,'nick_name');
            $nickary = str_replace("\\","",$nickary);
            $nickary = str_replace("/","",$nickary);
            $nickary = str_replace("'","",$nickary);
            $nay = json_encode($nickary);
            $p_mary = array_column($listmain,'p_m');
            $uid_ary = array_column($listmain,'uid');
            //print_r($nickary);
            $nickary = stripslashes($nickary);

            //print_r($nickary);die;
            $this->assign('jsondata', json_encode($listmain));
            $this->assign('nickary', $nay);
            $this->assign('p_mary', json_encode($p_mary));
            $this->assign('uid_ary', json_encode($uid_ary));
            $this->display(T('Addons://Report@MemberSale/m_sale_pic'));
        }else{
            $this->display(T('Addons://Report@MemberSale/m_sale'));
        }
    }

    public function s_sale()
    {
        $this->check_store();//检测是否已选择仓库
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $s_date = I('s_date');
        $e_date = I('e_date');
        $uid = I('uid');
        if ($s_date == "" && $e_date == "") {
            //默认获取30天月活用户
            $starttime = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $endtime = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
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

        $where = array(
            'create_time' => array('between', array($starttime, $endtime)),
            'store_id' => $this->_store_id,
            'status' => 5,
            'uid' => $uid,
        );
        $field = "id,order_sn,uid,pay_money,create_time";
        $order = "create_time";
        $model = M('order');
        /*$sql = "
          SELECT A.uid,A.order_sn,A.create_time,sum(B.num*B.price) as pay_money FROM hii_order A left join hii_order_detail B on A.order_sn=B.order_sn
          WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') BETWEEN '$starttime' AND '$endtime'
           AND `store_id` = " .$this->_store_id. " AND `status` = 5 AND uid = $uid
            GROUP BY A.order_sn ORDER BY create_time
        ";
        $data = $model-> query($sql);*/
        $data = $model->where($where)->field($field)->order($order)->select();
        $nickname = get_nickname($uid);
        $this->assign('nickname', json_encode($nickname));
        $title = '【'.session('user_store.title').'】 活跃用户【' .$nickname. '】' .$s_date. '>>>' .$e_date. '统计';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = session('user_store.title') .'_活跃用户【' .$nickname. '】统计_'.$s_date .'>>>' .$e_date;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushMemberSaleList2($data,$title,$fname);
            //$printfile = $this->pushStoreJieCunList2($list,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=20;
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
        $this->assign('uid', $uid);
        //累计消费金额
        $where1 = array(
            'store_id' => $this->_store_id,
            'status' => 5,
            'uid' => $uid,
        );
        $sum_money_data = $model->where($where1)->field('uid,sum(pay_money) as p_money')->group('uid')->select();
        $this->assign('p_money', $sum_money_data[0]['p_money']);

        $viewtype = I('v');
        if($viewtype == ""){
            for($i=0;$i<count($listmain);$i++) {
                $listmain[$i]['ctime'] = date('Y-m-d',$listmain[$i]['create_time']);
            }

            $order_sn_ary = array_column($listmain,'order_sn');
            $pay_money_ary = array_column($listmain,'pay_money');
            $create_time_ary = array_column($listmain,'ctime');

            $this->assign('jsondata', json_encode($listmain));
            $this->assign('order_sn_data', json_encode($order_sn_ary));
            $this->assign('pay_money_data', json_encode($pay_money_ary));
            $this->assign('create_time_data', json_encode($create_time_ary));
            $this->display(T('Addons://Report@MemberSale/s_sale_pic'));
        }else{
            $this->display(T('Addons://Report@MemberSale/s_sale'));
        }
    }

    public function member_date()
    {
        $this->check_store();//检测是否已选择仓库
        $s_date = I('s_date');
        $e_date = I('e_date');
        $dwm = I('dwm');
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
        $this->assign('dwm', $dwm);

        $s_date_1 = date("Y-m-d",(strtotime($s_date) - 3600*24));
        $d1 = strtotime($s_date);
        $d2 = strtotime($e_date);
        $Days = round(($d2-$d1)/3600/24) + 1;
        if($dwm == "d"){
            //按日
            $legendstr = '活跃用户趋势~每日';
            //$arr = $this->get_time_arr($s_date, $e_date, 'week');
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(showdata,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B";
            $sql .= " left join (select c_time as ctime,count(distinct uid) as showdata from (select uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as c_time from {$db_order_name}";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store') as tmp WHERE c_time  between '" .$s_date. "' and '" .$e_date. "' group by c_time) A on A.ctime=B.ctime order by ctime";
            $data = M('order')->query($sql);
        }
        if($dwm == "w"){
            //按周
            $legendstr = '活跃用户趋势~每周';
            $arr = $this->get_ld_times($s_date, $e_date, 'w');
            $sql = '';
            for($i = 0;$i < count($arr);$i++){
                $t = explode(',',$arr[$i]);
                $t1 = $t[0];
                $t2 = $t[1];
                if($i == 0){
                    $sql .= "select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from {$db_order_name}";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }else{
                    $sql .= " union all select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from {$db_order_name}";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }
            }
            $data = M('order')->query($sql);
        }
        if($dwm == "m"){
            //按月
            $legendstr = '活跃用户趋势~每月';
            $arr = $this->get_ld_times($s_date, $e_date, 'm');
            $sql = '';
            for($i = 0;$i < count($arr);$i++){
                $t = explode(',',$arr[$i]);
                $t1 = $t[0];
                $t2 = $t[1];
                if($i == 0){
                    $sql .= "select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from {$db_order_name}";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }else{
                    $sql .= " union all select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from {$db_order_name}";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and `type`='store' and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }
            }
            $data = M('order')->query($sql);
        }
        $this->meta_title = session('user_store.title') ."用户分析~活跃用户";
        //print_r($sql);die;
        //分页
        $pcount=365;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        //排序
        //$datamain1 = sort($datamain);

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

        //消费金额TOP25
        //SQL A表：获取uid+消费金额合计+消费次数合计。B表：uid+购买次数最多的商品id,商品name如果有多个购买次数最多的商品则获取第一个【group by uid】
        $sql = "
        SELECT A.*,B.d_id,B.goods_name FROM
            (
                SELECT A.uid,count(distinct A.order_sn) as counttime,sum(B.num*B.price) as buymoney
                FROM `{$db_order_name}` A
                left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store'
                group by A.uid
                order by buymoney desc
            ) A left JOIN
            (
                select uid,d_id,goods_name,goods_id from
                  (
                      select a.* from
                      (
                      SELECT A.uid,B.d_id,count(B.d_id) as goods_id,C.title as goods_name
                        FROM `{$db_order_name}` A
                        left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                        left join hii_goods C on B.d_id=C.id
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store'
                        group by A.uid,B.d_id,C.title
                      ) as a
                      where not exists
                          (
                          select * from
                            (SELECT A.uid,B.d_id,count(B.d_id) as goods_id,C.title as goods_name
                                FROM `{$db_order_name}` A
                                left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                                left join hii_goods C on B.d_id=C.id
                                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and store_id = " .$this->_store_id ." and A.status = 5 and A.`type`='store'
                                group by A.uid,B.d_id,C.title
                                ) AA
                          where AA.uid=a.uid and AA.goods_id>a.goods_id
                          )
                  ) A group by uid
            ) B on A.uid=B.uid
            order by A.buymoney desc
        ";
        $datau = M('order')->query($sql);
        $datamainu = array_slice($datau,0,25);
       
        $this->assign('listu', $datamainu);
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = session('user_store.title') .'_用户分析~活跃用户_'.$s_date .'>>>' .$e_date;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushMemberDateList($data,$datau,$this->meta_title,$legendstr,$fname);
            //$printfile = $this->pushStoreJieCunList2($list,$title,$fname);
            echo($printfile);die;
        }

        $this->display(T('Addons://Report@MemberSale/member_date'));
    }

    public function member_buy()
    {
        $this->check_store();//检测是否已选择仓库
        $s_date = I('s_date');
        $e_date = I('e_date');
        $dwm = I('dwm');
        $uid = I('uid');
        $uname = I('uname');
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
        if ($uid != ""){
            $where .= " and uid = $uid";
            $datauser = M('member')->where('uid = ' .$uid)->select();
            $this->assign('udata', $datauser);
        }
        if ($uname != ""){
            $where .= " and uid in (select uid from hii_member where nickname = '" .$uname . "')";
            $datauser = M('member')->where("nickname = '" .$uname . "'")->select();
            $this->assign('udata', $datauser);
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
        $this->assign('uid', $uid);
        $this->assign('uname', $uname);
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->assign('dwm', $dwm);

        $s_date_1 = date("Y-m-d",(strtotime($s_date) - 3600*24));
        $d1 = strtotime($s_date);
        $d2 = strtotime($e_date);
        $Days = round(($d2-$d1)/3600/24) + 1;

        if($dwm == "d"){
            //按日
            $legendstr = '用户消费趋势~每日';
            //$dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(counttime,0) as counttime,ifnull(buymoney,0) as buymoney from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from {$db_order_name} a,(select @mycnt:=0) b limit " .$Days. ") B left join (";
            $sql .= "
                SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,count(distinct A.order_sn) as counttime,sum(B.num*B.price) as buymoney
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
            $legendstr = '用户消费趋势~每周';
            $arr = $this->get_ld_times($s_date, $e_date, 'w');
        }

        if($dwm == "m"){
            //按月
            $legendstr = '用户消费趋势~每月';
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
                        SELECT '" . $arr[$i] . "' as ctime,count(distinct A.order_sn) as counttime,sum(B.num*B.price) as buymoney
                        FROM `{$db_order_name}` A
                        left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $t1 . "' and '" . $t2 . "'
                         and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    ";
                } else {
                    $sql .= "
                        union all
                        SELECT '" . $arr[$i] . "' as ctime,count(distinct A.order_sn) as counttime,sum(B.num*B.price) as buymoney
                        FROM `{$db_order_name}` A
                        left join `{$db_order_detail_name}` B on A.order_sn=B.order_sn
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $t1 . "' and '" . $t2 . "'
                         and store_id = " . $this->_store_id . " and A.status = 5 and A.`type`='store' " . $where . "
                    ";
                }
            }
        }
        $data = M('order')->query($sql);
        //print_r($datauser[0]['nickname']);die;
        if(is_array($datauser) && count($datauser) > 0){
            $nickname = $datauser[0]['nickname'];
            $this->assign('uname', $nickname);
            $this->meta_title = session('user_store.title') ."用户分析~用户【" .$nickname. "】消费趋势";
        }else{
            $this->meta_title = session('user_store.title') ."用户分析~用户消费趋势";
        }
        //分页
        $pcount=365;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        //排序
        //$datamain1 = sort($datamain);
        $lstr1 = '消费次数';
        $lstr2 = '消费金额';
        $dates = array_column($datamain,'ctime');
        $xstr = '"' .implode('","',$dates) .'"';//x轴日期数据
        $datas = array_column($datamain,'counttime');
        $datastr1 = implode(',',$datas);
        $datas = array_column($datamain,'buymoney');
        $datastr2 = implode(',',$datas);

        $this->assign('list', $datamain);
        $this->assign('xstr', $xstr);
        $this->assign('datastr1', $datastr1);
        $this->assign('datastr2', $datastr2);
        $this->assign('legendstr', $legendstr);
        $this->assign('legendstr1', $lstr1);
        $this->assign('legendstr2', $lstr2);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);

        //商品销售TOP10
        $sql = "
        SELECT A.d_id,A.goods_name,A.cate_name,A.buymoney FROM
            (
                SELECT d_id,C.title as goods_name,D.title as cate_name,sum(B.num*B.price) as buymoney
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

        $title_top10_1 = '商品销售额TOP10';
        $title_top10_1_sub = $s_date .'~' .$e_date;
        $data_top10_1 = array_column($datau,'goods_name');
        $xstr_top10_1 = implode(',',$data_top10_1);//商品TOP10饼图名称
        $xstr_top10_1_1 = "'" .implode("','",$data_top10_1) ."'";//商品TOP10饼图名称
        $datas_top10_1 = array_column($datau,'buymoney');
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
            $printfile = $printmodel->pushMemberBuyList($data,$datau,$datac,$this->meta_title,$legendstr,$fname);
            //$printfile = $this->pushStoreJieCunList2($list,$title,$fname);
            echo($printfile);die;
        }

        $this->display(T('Addons://Report@MemberSale/member_buy'));
    }
    /**
     * 全国的用户分析
     */
    public function member_whole_country(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        //查询条件：门店参数，类别参数，单个商品名参数，价格范围参数 时间范围参数  用户名参数   月周日
        $where_user_h = array(); //用户活跃的条件
        $top_where = '';
        $user_name = I('user_name','','trim');
        $this->assign('user_name', $user_name);
        if($user_name != ''){
            $uid = M('Member')->where(array('nickname'=>$user_name))->getField('uid');
                $where_user_h['O.uid'] = $uid;
                $top_where .= ' AND O.uid='.$uid;
        }

      
        //勾选的门店*******************************************
        $store_select = I('store');
        if($store_select == ""){
            $store_select = $_POST['store'];
        }
        

        //勾选的类别***********************************************
        $goodscate_select = I('goodscate_select');
        if($goodscate_select == ""){
            $goodscate_select = $_POST['goodscate_select'];
        }
        if($goodscate_select != ""){
            if( !is_array($goodscate_select) ){
                $goodscate_select = explode(",", $goodscate_select);
            }
            $where_user_h['G.cate_id'] = array('in',$goodscate_select);
            $top_where .= ' AND G.cate_id IN('.implode(',', $goodscate_select).') ';
        }
        $this->assign('goodscate_select', $goodscate_select);
        //按商品ID搜索*********************************************
        $goods_id_search = I('goods_id',0,'intval');
        if($goods_id_search == ""){
            $goods_id_search = $_POST['goods_id'];
        }
        if($goods_id_search != ""){
            $where_user_h['OD.d_id'] = $goods_id_search;
            $top_where .= ' AND OD.d_id='.$goods_id_search;
        }
        $this->assign('goods_id', $goods_id_search);
        //按商品名称搜索 ***********************************************
        $goods_name_search = I('goods_name','','trim');
        if($goods_name_search == ""){
            $goods_name_search = $_POST['goods_name'];
        }
        if($goods_name_search != ""){
            $where_user_h['G.title'] = array("like","%{$goods_name_search}%");
            $top_where .= " AND G.title like '%{$goods_name_search}%'";
        }
        $this->assign('goods_name', $goods_name_search);
        //最低价 ******************************************************
        $min_price_search = I('min_price','','trim');
        if($min_price_search == ""){
            $min_price_search = $_POST['min_price'];
        }
        //最高价 ******************************************************
        $max_price_search = I('max_price','','trim');
        if($max_price_search == ""){
            $max_price_search = $_POST['max_price'];
        }
        if($min_price_search != ""){
            if($max_price_search != ""){
                $where_user_h['OD.price'] = array(array('EGT',$min_price_search),array('ELT',$max_price_search));
            }else{
                $where_user_h['OD.price'] = array('EGT',$min_price_search);
            }
        }else{
            if($max_price_search != ""){
                $where_user_h['OD.price'] = array('ELT',$max_price_search);
            }
        }
        $this->assign('min_price', $min_price_search);
      
        $this->assign('max_price', $max_price_search);
        //月周日 ******************************************************
        $dwm = I('dwm');
        if ($dwm == ""){
            $dwm = "d";
        }
        $this->assign('dwm',$dwm);
        //时间范围默认30天
        $s_date = I('s_date');
        if($s_date == ""){
            $s_date = $_POST['s_date'];
        }
        $e_date = I('e_date');
        if($e_date == ""){
            $e_date = $_POST['e_date'];
        }
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("31 days ago")));
            $new_time = strtotime('-1 day');
            $e_date = mktime(0,0,0,date("m",$new_time),date("d",$new_time),date("Y",$new_time));
        }else{
            if ($s_date != ""){
                $s_date = strtotime($s_date);
            }
            if ($e_date != ""){
                $e_date = strtotime($e_date);
            }
        }
        $s_date_t = $s_date;
        $e_date_t = $e_date;
        //判断使用数据表
        if(date('Y',$s_date) == date('Y',$e_date)){
            /* $orderModel = 'Order_'.date('Y',$s_date);
            $orderModel_1 = 'hii_order_'.date('Y',$s_date);
            $orderDetailMode = 'hii_order_detail_'.date('Y',$s_date); */
            $orderModel = 'Order';
            $orderModel_1 = 'hii_order';
            $orderDetailMode = 'hii_order_detail';
        }else{
            $orderModel = 'Order';
            $orderModel_1 = 'hii_order';
            $orderDetailMode = 'hii_order_detail';
        }
        $s_date = date('Y-m-d',$s_date);
        $e_date = date('Y-m-d',$e_date);
        
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);
        $this->assign('dwm', $dwm);
        $isprint = I('isprint',0,'intval');
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
       // var_dump($this->group_id);die;
        if(!in_array(1, $this->group_id)){
            $where['status'] = array('eq', 1);
           // $where['shequ_id'] = array('neq', 3);
        }

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
      //重新查询门店条件 如果没有传门店 就获取所有个人权限内的门店  权限    
        if($store_select != ""){
            if( !is_array($store_select) ){
                $store_select = explode(",", $store_select);
            }
            $where_user_h['O.store_id'] = array('in',$store_select);
            $top_where .= ' AND O.store_id IN('.implode(',', $store_select).') ';
        }else{
            $where_user_h['O.store_id'] = array('in',array_column($store, 'id'));
            $top_where .= ' AND O.store_id IN('.implode(',', array_column($store, 'id')).') ';
        }
        $this->assign('store_select', $store_select);
        //查询条件：类别
        $goodscate = M('GoodsCate')->field('id, title')->select();
        $this->assign('goodscate', $goodscate);
        $user_store_data = array();
        $user_data = array(
            'time'=>  '',
            'count'=> '',
            'money_count'=>  '',
            'money_sum'=>  '',
            'num'=>  '',
        );
        $info = array();
        
        //判断第一次进入不获取数据
        $one = I('one',0,'intval');
        //判断第一次进入 不获取数据
      if($one == 1){
          //查询条件
          if(!in_array(1, $this->group_id)){
              $where_user_h['S.status'] = 1;
          }
          $where_user_h['O.status'] = 5;
          $where_user_h['O.type'] = 'store';
        if($dwm == "d"){
            //天
            $time_array = $this->get_time_day($s_date_t, $e_date_t);
            foreach($time_array as $k=>$v){

                $where_user_h['O.create_time'] = array(array('egt',strtotime($v.' 00:00:00')),array('lt',strtotime($v.' 00:00:00 +1 day')));
                $a = M($orderModel)->alias("O")->field('count(distinct O.uid)count,count(DISTINCT O.order_sn)money_count,ifnull(sum(OD.num),0)num,ifnull(sum(OD.num*OD.price),0.00)money_sum')
                ->join("inner join {$orderDetailMode} OD on O.order_sn=OD.order_sn")
                ->join('left join hii_goods G on G.id= OD.d_id')
                ->join('left join hii_store S on S.id= O.store_id')
                ->where($where_user_h)
                ->find();
                if(empty($a)){
                    $user_store_data['count'][] = 0;
                    $user_store_data['money_count'][] = 0;
                    $user_store_data['money_sum'][] = 0;
                    $user_store_data['num'][] = 0;
                }else{
                    $user_store_data['count'][] = $a['count'];
                    $user_store_data['money_count'][] = $a['money_count'];
                    $user_store_data['money_sum'][] = $a['money_sum'];
                    $user_store_data['num'][] = $a['num'];
                }
            }
           
            $user_data = array(
                'time'=>  implode("','", $time_array),
                'count'=>  implode("','", $user_store_data['count']),
                'money_count'=>  implode("','", $user_store_data['money_count']),
                'money_sum'=>  implode("','", $user_store_data['money_sum']),
                'num'=>  implode("','", $user_store_data['num']),
            );
           
        }elseif($dwm == "w"){
            //周
            $time_array = $this->get_ld_times($s_date, $e_date,'w');
            foreach($time_array as $k=>$v){
                $v = explode(',', $v);
                $where_user_h['O.create_time'] = array(array('egt',strtotime($v[0].' 00:00:00')),array('lt',strtotime($v[1].' 00:00:00 +1 day')));
                $a = M($orderModel)->alias("O")->field('count(distinct O.uid)count,count(DISTINCT O.order_sn)money_count,ifnull(sum(OD.num),0.00)num,ifnull(sum(OD.num*OD.price),0.00)money_sum')
                ->join("inner join {$orderDetailMode} OD on O.order_sn=OD.order_sn")
                ->join('left join hii_goods G on G.id= OD.d_id')
                ->join('left join hii_store S on S.id= O.store_id')
                ->where($where_user_h)
                ->find();
                if(empty($a)){
                    $user_store_data['count'][] = 0;
                    $user_store_data['money_count'][] = 0;
                    $user_store_data['money_sum'][] = 0;
                    $user_store_data['num'][] = 0;
                }else{
                    $user_store_data['count'][] = $a['count'];
                    $user_store_data['money_count'][] = $a['money_count'];
                    $user_store_data['money_sum'][] = $a['money_sum'];
                    $user_store_data['num'][] = $a['num'];
                }
            }
            
            $user_data = array(
                'time'=>  implode("','", $time_array),
                'count'=>  implode("','", $user_store_data['count']),
                'money_count'=>  implode("','", $user_store_data['money_count']),
                'money_sum'=>  implode("','", $user_store_data['money_sum']),
                'num'=>  implode("','", $user_store_data['num']),
            );
        }elseif($dwm == "m"){
            //月
            $time_array = $this->get_ld_times($s_date, $e_date,'m');
            foreach($time_array as $k=>$v){
                $v = explode(',', $v);
                $where_user_h['O.create_time'] = array(array('egt',strtotime($v[0].' 00:00:00')),array('lt',strtotime($v[1].' 00:00:00 +1 day')));
                $a = M($orderModel)->alias("O")->field('count(distinct O.uid)count,count(DISTINCT O.order_sn)money_count,ifnull(sum(OD.num),0.00)num,ifnull(sum(OD.num*OD.price),0.00)money_sum')
                ->join("inner join {$orderDetailMode} OD on O.order_sn=OD.order_sn")
                ->join('left join hii_goods G on G.id= OD.d_id')
                ->join('left join hii_store S on S.id= O.store_id')
                ->where($where_user_h)
                ->find();
                if(empty($a)){
                    $user_store_data['count'][] = 0;
                    $user_store_data['money_count'][] = 0;
                    $user_store_data['money_sum'][] = 0;
                    $user_store_data['num'][] = 0;
                }else{
                    $user_store_data['count'][] = $a['count'];
                    $user_store_data['money_count'][] = $a['money_count'];
                    $user_store_data['money_sum'][] = $a['money_sum'];
                    $user_store_data['num'][] = $a['num'];
                }
            }
            
            $user_data = array(
                'time'=>  implode("','", $time_array),
                'count'=>  implode("','", $user_store_data['count']),
                'money_count'=>  implode("','", $user_store_data['money_count']),
                'money_sum'=>  implode("','", $user_store_data['money_sum']),
                'num'=>  implode("','", $user_store_data['num']),
            );
        }
        /******************商品销售额TOP10********************/
        $where_user_h['O.create_time'] = array(array('egt',strtotime($s_date.' 00:00:00')),array('elt',strtotime($e_date.' 23:59:59'))) ;
        $goods_money_p = M($orderModel)->alias("O")->field('ifnull(sum(OD.num*OD.price),0) value,G.title as name')
        ->join("inner join {$orderDetailMode} OD on O.order_sn=OD.order_sn")
        ->join('left join hii_goods G on G.id= OD.d_id')
        ->join('left join hii_store S on S.id=O.store_id')
        ->where($where_user_h)
        ->group('OD.d_id')
        ->order('value desc')
        ->limit(10)
        ->select();
        if(empty($goods_money_p)){
            $$goods_money_p = array(); 
        }
        $this->assign('goods_money_p10',json_encode(array('money'=>$goods_money_p,'data'=>array_column($goods_money_p, 'name'))));
        /******************分类销售额TOP10********************/
        $where_user_h['O.create_time'] = array(array('egt',strtotime($s_date.' 00:00:00')),array('elt',strtotime($e_date.' 23:59:59'))) ;
        $goods_cate_money_p = M($orderModel)->alias("O")->field('ifnull(sum(OD.num*OD.price),0) value,GC.title as name')
        ->join("inner join {$orderDetailMode} OD on O.order_sn=OD.order_sn")
        ->join('left join hii_goods G on G.id= OD.d_id')
        ->join('left join hii_goods_cate GC on G.cate_id=GC.id')
        ->join('left join hii_store S on S.id=O.store_id')
        ->where($where_user_h)
        ->group('GC.id')
        ->order('value desc')
        ->limit(10)
        ->select();
        if(empty($goods_cate_money_p)){
            $goods_cate_money_p = array();
        }
        $this->assign('goods_cate_money_p10',json_encode(array('money'=>$goods_cate_money_p,'data'=>array_column($goods_cate_money_p, 'name'))));
        /******************TOP25活跃用户表格********************/
        $s_date_t = strtotime($s_date);
        $e_date_t = strtotime($e_date)+3600*24;
        
        //如果导出就查询所有
        if($isprint ==1){
            $limit = "";
        }else{
            $limit = " LIMIT 25";
        }
        //如果是超级管理员就显示 所有门店  包括(关闭的门店)
          //查询条件
          if(!in_array(1, $this->group_id)){
              $is_store= " AND S. `STATUS` = 1  ";
          }

        /*$sql = "SELECT
                		uid,
            			d_id,
            			title,
                        pay_type,
                        GROUP_CONCAT(DISTINCT a.store_name) store_name,
            			GROUP_CONCAT(DISTINCT a.order_count) order_count,
            			sum(a.order_money) order_money
                	FROM
                		(
                			SELECT
                				O.uid,
                				OD.d_id,
                                G.title,
                                O.pay_type,
                				GROUP_CONCAT(DISTINCT S.title)store_name,
                                GROUP_CONCAT(DISTINCT O.id) order_count,
                				sum(OD.num * OD.price) order_money
                			FROM
                				{$orderModel_1} O
                			INNER JOIN {$orderDetailMode} OD ON OD.order_sn = O.order_sn
                			LEFT JOIN hii_goods G ON G.id = OD.d_id
                            LEFT JOIN hii_store S ON S.id = O.store_id
                			WHERE
                				O.create_time >= {$s_date_t}
                			AND O.create_time < {$e_date_t}
                			{$top_where}
                			AND O. `STATUS` = 5 
                            AND O.type = 'store' 
                            {$is_store}
                			GROUP BY
                				O.uid,
                				OD.d_id
                			ORDER BY
                				order_money DESC
                		) a
                	GROUP BY
                		uid
                	ORDER BY
                		order_money DESC
                        {$limit}";*/

          $sql = "SELECT
                        b.uid,
                        b.d_id,
                        b.title,
                    b.pay_type,
                    sum(c.order_money)order_money,
                    sum(c.order_count)order_count,
                    GROUP_CONCAT(c.store_name order by c.order_money desc)store_name
                    FROM
                        (
                            SELECT
                                uid,
                                d_id,
                                title,
                                pay_type
                            FROM
                                (
                                    SELECT
                                        O.uid,
                                        OD.d_id,
                                        G.title,
                                        O.pay_type,
                                        sum(OD.num * OD.price) order_money
                                    FROM
                                       {$orderModel_1}  O
                                    INNER JOIN {$orderDetailMode} OD ON OD.order_sn = O.order_sn
                                    LEFT JOIN hii_goods G ON G.id = OD.d_id
                                    LEFT JOIN hii_store S ON S.id = O.store_id
                                    WHERE
                                        O.create_time >= {$s_date_t}
                                    AND O.create_time < {$e_date_t}
                                    AND O.`STATUS` = 5
                                    {$top_where}
                                    AND O.type = 'store'
                                    
                                    {$is_store}
                                    GROUP BY
                                        O.uid,
                                        OD.d_id
                                    ORDER BY
                                        order_money DESC
                                    LIMIT 99999999
                                ) a
                            GROUP BY
                                uid
                            ORDER BY
                                a.order_money DESC
                        ) b
                    RIGHT JOIN (
                        SELECT
                            O.uid,
                            sum(od.num * od.price) order_money,
                            count(DISTINCT O.id) order_count,
                            S.title AS store_name
                        FROM
                            {$orderModel_1} O
                        LEFT JOIN {$orderDetailMode} od ON O.order_sn = od.order_sn
                        LEFT JOIN hii_store S ON S.id = O.store_id
                        WHERE
                            O.create_time >= {$s_date_t}
                        AND O.create_time < {$e_date_t}
                        AND O.`STATUS` = 5
                        {$top_where}
                        AND O.type = 'store'
                        {$is_store}
                        GROUP BY
                            O.uid,
                            O.store_id
                        ORDER BY
                            order_money DESC
                    ) c ON b.uid = c.uid
	               where b.uid is not null
                    GROUP BY
                        b.uid
                    ORDER BY
	            order_money DESC {$limit}";
         $info = M()->query($sql);  
         if(empty($info)){
             $info = array();
         }
         
         if($isprint ==1 ){
             //导出
             ob_clean;
             $title = '全局用户分析';
             $title1 = '活跃用户趋势';
             $title2 = '活跃用户消费次数排行';
             $fname = '全局用户分析_'.$s_date_t.'-'.$e_date_t.'_'.time().'.xlsx';
             $printmodel = new \Addons\Report\Model\ReportModel();
             $printfile = $printmodel->pushMemberDateList_whole_country(array('data'=>array('time'=>$time_array,'data'=>$user_store_data),'info'=>$info),$title,$fname,$title1,$title2);
             //$printfile = $this->OutStoreSaleList1Function($list,$title,$fname);
             echo($printfile);die;
         }
      }
      //var_dump($one);die;
        $this->assign('user_data',$user_data);
        $this->assign('info',$info);
        $this->assign('one',1);
        $this->meta_title = '用户分析~全局';
        $this->display(T('Addons://Report@MemberSale/member_whole_country'));
    }
    /***
     * 全局商品分析
     */
    public function globalCommodityAnalysis(){
        $goods_id = I('goods_id',0,'intval');
        $goods_name = I('goods_name','','trim');
        $s_date = I('s_date','','trim');
        $e_date = I('e_date','','trim');
        $dwm = I('dwm','d');
        $one = I('one',0,'intval');  //判断第一次进入不获取数据
        $isprint = I('isprint',0,'intval');  //判断第一次进入不获取数据
        $SQL_cate = ''; //查询条件
        $sql_goods_top20_where = '';   //当前天直接查询 order订单表

        $goodsModel = M('Goods');
        $goodsCateModel = M('GoodsCate');
        //时间范围默认30天
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $new_time = strtotime('+1 day');
            $e_date = mktime(0,0,0,date("m",$new_time),date("d",$new_time),date("Y",$new_time));
        }else{
            if ($s_date != ""){
                $s_date = strtotime($s_date);
            }
            if ($e_date != ""){
                $e_date = strtotime($e_date.'+1 day');
            }
        }

        //sql field的取值范围

        //所有类别
        $goodscate = M('GoodsCate')->field('id, title')->select();
        $goodscate_key = array_combine(array_column($goodscate,'id'),array_column($goodscate,'title'));
        //勾选的类别
        //勾选上的类别返回
        $cate_return = array();
        $goodscate_select = I('goodscate_select');
        if($goodscate_select == ""){
            $goodscate_select = $_POST['goodscate_select'];
        }
        if(!empty($goodscate_select) && empty($goods_name) && empty($goods_id)){
            if( !is_array($goodscate_select) ){
                $goodscate_select = explode(",", $goodscate_select);
            }
            $cate_return = $goodscate_select;
            $field2 = array();
            $SQL_cate .= ' AND O.cate_id IN('.implode(',', $goodscate_select).') ';
            $sql_goods_top20_where .= ' AND G.cate_id IN('.implode(',', $goodscate_select).') ';
            foreach ($goodscate_select as $key=>$val){
                $field2[] = " MAX(CASE goods_cate_id WHEN {$val} THEN buymoney ELSE 0 END ) m{$val}";
            }
            $field2 = implode(',',$field2);
        }elseif(!$goods_name && !$goods_id){
            $field2 = array();
            $goodscate_select = array_column($goodscate,'id');
            foreach ($goodscate_select as $key=>$val){
                $field2[] = " MAX(CASE goods_cate_id WHEN {$val} THEN buymoney ELSE 0 END ) m{$val}";
            }
            $field2 = implode(',',$field2);
        }
        if($goods_name){  //如果查询单个商品 就重写类别
            $cate_id = $goodsModel->field('cate_id,id')->where(array('title'=>array('like',"%{$goods_name}%")))->find();
           // $SQL_cate .= ' AND O.cate_id='.$cate_id['cate_id'];
            $SQL_cate .= ' AND O.goods_id='.$cate_id['id'];
            $sql_goods_top20_where .= ' AND OD.d_id='.$cate_id['id'];
            $goodscate_select = array($cate_id['cate_id']);
            $field2 = array();
            foreach ($goodscate_select as $key=>$val){
                $field2[] = " MAX(CASE goods_cate_id WHEN {$val} THEN buymoney ELSE 0 END ) m{$val}";
            }
            $field2 = implode(',',$field2);
        }
        if($goods_id){  //如果查询单个商品 就重写类别
            $cate_id = $goodsModel->field('cate_id,id')->where(array('id'=>$goods_id))->find();
           // $SQL_cate .= ' AND O.cate_id='.$cate_id['cate_id'];
            $SQL_cate .= ' AND O.goods_id='.$cate_id['id'];
            $sql_goods_top20_where .= ' AND OD.d_id='.$cate_id['id'];
            $goodscate_select = array($cate_id['cate_id']);
            $field2 = array();
            foreach ($goodscate_select as $key=>$val){
                $field2[] = " MAX(CASE goods_cate_id WHEN {$val} THEN buymoney ELSE 0 END ) m{$val}";
            }
            $field2 = implode(',',$field2);
        }

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
        !$shequ && $shequ = array();
        $where = array();
        $stores && $where['id'] = array('in', $stores);
        // var_dump($this->group_id);die;
        if(!in_array(1, $this->group_id)){
            $where['status'] = array('eq', 1);
        }

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
        $store_select = I('store');
        if($store_select != ""){
            if( !is_array($store_select) ){
                $store_select = explode(",", $store_select);
            }
            $SQL_cate .= ' AND O.store_id IN('.implode(',', $store_select).') ';
            $sql_goods_top20_where .= ' AND O.store_id IN('.implode(',', $store_select).') ';
        }else{
            $SQL_cate .= ' AND O.store_id IN('.implode(',', array_column($store,'id')).') ';
            $sql_goods_top20_where .= ' AND O.store_id IN('.implode(',', array_column($store,'id')).') ';
        }
        $this->assign('store_select', $store_select);

        $this->assign('goods_id',!$goods_id?'':$goods_id);
        $this->assign('goods_name',$goods_name);
        $this->assign('dwm',$dwm);
        $this->assign('one',1);
        $this->assign('goodscate_select', $cate_return);
        $this->assign('goodscate', $goodscate);
        $this->assign('s_date',date('Y-m-d',$s_date));
        $this->assign('e_date',date('Y-m-d',$e_date-3600*24));

        if($one == 1){
            //判断结束时间是否时当前时间
            $new_time_date = date('Y-m-d');
            $new_time_strto = strtotime($new_time_date);
            if($dwm == 'd'){
                $time_array = $this->get_time_day($s_date, $e_date-3600*24);
               $data =  M()->query("SELECT ORD.days as ctime, {$field2}  FROM
                             ( SELECT O.cate_id AS goods_cate_id,sum(O.buymoney) buymoney, FROM_UNIXTIME(O.ctime, '%Y-%m-%d') days
                                FROM hii_global_commodity_analysis O
                                WHERE O.ctime >= {$s_date} AND O.ctime < {$e_date}  {$SQL_cate}
                                GROUP BY days,O.cate_id 
                            ) ORD  group by ORD.days order by ORD.days");
                $data = array_combine(array_column($data,'ctime'),$data);
                foreach ($time_array as $key=>$val){
                    if(empty($data[$val])){
                        $week_on_info = array();
                        foreach ($goodscate_select as $kk=>$vv){
                            $week_on_info['m'.$vv] = '0.00';
                        }
                        $week_on_info['ctime'] = $val;
                        $data[$val] = $week_on_info;
                    }
                }

                if(($e_date-3600*24) == strtotime($new_time_date)){
                    $data_e_date =  M()->query("SELECT ORD.days as ctime, {$field2}  FROM
                             ( SELECT G.cate_id AS goods_cate_id,sum(OD.num * OD.price) buymoney, FROM_UNIXTIME(O.create_time , '%Y-%m-%d') days
                                FROM hii_order O
                                LEFT JOIN hii_order_detail OD on OD.order_sn=O.order_sn
                                LEFT JOIN hii_goods G on G.id=OD.d_id
                                WHERE  O.create_time >= {$new_time_strto} AND O. STATUS = 5 AND O.`type` = 'store' {$sql_goods_top20_where} AND G.cate_id is not null
                                GROUP BY G.cate_id
                            ) ORD group by ORD.days");
                    if(!empty($data_e_date)){
                        foreach ($goodscate_select as $kk=>$vv){
                            $data[$new_time_date]['m'.$vv] = $data_e_date[0]['m'.$vv];
                        }
                    }
                }


            }elseif($dwm == 'w'){
                //周
                $time_array = $this->get_ld_times(date('Y-m-d',$s_date), date('Y-m-d',$e_date-3600*24),'w');
                $data = M()->query("SELECT ORD.weeks as ctime,{$field2}  FROM
                                     ( SELECT O.cate_id AS goods_cate_id, sum(O.buymoney) buymoney,FROM_UNIXTIME(O.ctime, '%Y-%m-%u') weeks
                                        FROM hii_global_commodity_analysis O
                                        WHERE O.ctime >= {$s_date}  AND O.ctime < {$e_date}  {$SQL_cate}
                                        GROUP BY weeks, O.cate_id 
                                    ) ORD  group by ORD.weeks order by ORD.weeks");
                    $data = array_combine(array_column($data,'ctime'),$data);

                foreach ($time_array as $key=>$val){
                    $val_ex = explode(',',$val);
                    $val_week = $this->get_weeks_num(strtotime($val_ex[0]));
                    $val_week = date('Y-m-'.$val_week,strtotime($val_ex[0]));
                    if(empty($data[$val_week])){
                        $week_on_info = array();
                        foreach ($goodscate_select as $kk=>$vv){
                            $week_on_info['m'.$vv] = '0.00';
                        }
                        $week_on_info['ctime'] = $val;
                        $data[$val_week] = $week_on_info;
                    }else{
                        $data[$val_week]['ctime'] = $val;
                    }
                }
                //判断结束时间是否时当前时间
                $new_time_date = date('Y-m-d');
                if(($e_date-3600*24) == strtotime($new_time_date)){
                    $data_e_date =  M()->query("SELECT ORD.days as ctime, {$field2}  FROM
                             ( SELECT G.cate_id AS goods_cate_id,sum(OD.num * OD.price) buymoney, FROM_UNIXTIME(O.create_time , '%Y-%m-%u') days
                                FROM hii_order O
                                LEFT JOIN hii_order_detail OD on OD.order_sn=O.order_sn
                                LEFT JOIN hii_goods G on G.id=OD.d_id
                                WHERE  O.create_time >= {$new_time_strto} AND O. STATUS = 5 AND O.`type` = 'store' {$sql_goods_top20_where} AND G.cate_id is not null
                                GROUP BY G.cate_id
                            ) ORD group by ORD.days");
                    if(!empty($data_e_date)){
                        $val_week = $this->get_weeks_num(strtotime($new_time_date));
                        $new_time_datess = date('Y-m-'.$val_week);
                        foreach ($goodscate_select as $kk=>$vv){
                            $data[$new_time_datess]['m'.$vv] = $data[$new_time_datess]['m'.$vv] + $data_e_date[0]['m'.$vv];
                        }
                    }

                }

            }elseif($dwm == 'm'){
                //月
                $time_array = $this->get_ld_times(date('Y-m-d',$s_date), date('Y-m-d',$e_date-3600*24),'m');
                $data = M()->query("SELECT  ORD.months as ctime, {$field2} FROM
                                     ( SELECT O.cate_id AS goods_cate_id, sum(O.buymoney) buymoney, FROM_UNIXTIME(O.ctime, '%Y-%m-01') months 
                                        FROM  hii_global_commodity_analysis O
                                        WHERE  O.ctime >= {$s_date}  AND  O.ctime < {$e_date}  {$SQL_cate}
                                        GROUP BY months, O.cate_id  
                                    ) ORD  group by ORD.months order by ORD.months");
                 $data = array_combine(array_column($data,'ctime'),$data);
                foreach ($time_array as $key=>$val){
                    $val_ex = explode(',',$val);
                    $val_month = date('Y-m-01',strtotime($val_ex[0]));
                    if(empty($data[$val_month])){
                        $week_on_info = array();
                        foreach ($goodscate_select as $kk=>$vv){
                            $week_on_info['m'.$vv] = '0.00';
                        }
                        $week_on_info['ctime'] = $val;
                        $data[$val_month] = $week_on_info;
                    }else{
                        $data[$val_month]['ctime'] = $val;
                    }
                }
                //判断结束时间是否时当前时间
                $new_time_date = date('Y-m-d');
                if(($e_date-3600*24) == strtotime($new_time_date)){
                    $data_e_date =  M()->query("SELECT ORD.days as ctime, {$field2}  FROM
                             ( SELECT G.cate_id AS goods_cate_id,sum(OD.num * OD.price) buymoney, FROM_UNIXTIME(O.create_time , '%Y-%m-01') days
                                FROM hii_order O
                                LEFT JOIN hii_order_detail OD on OD.order_sn=O.order_sn
                                LEFT JOIN hii_goods G on G.id=OD.d_id
                                WHERE  O.create_time >= {$new_time_strto} AND O. STATUS = 5 AND O.`type` = 'store' {$sql_goods_top20_where} AND G.cate_id is not null
                                GROUP BY G.cate_id
                            ) ORD group by ORD.days");
                    if(!empty($data_e_date)){
                        $val_month = date('Y-m-01');
                        foreach ($goodscate_select as $kk=>$vv){
                            $data[$val_month]['m'.$vv] = $data[$val_month]['m'.$vv] + $data_e_date[0]['m'.$vv];
                        }
                    }

                }

            }
            $info = array();
            foreach ($goodscate_select as $key=>$val){
                $info[] = array(
                    'name'=>$goodscate_key[$val],
                    'type'=>'line',
                    'data'=>array_column($data,'m'.$val)
                );
            }
            $first = array(
                'xAxis'=>array_column($data,'ctime'),
                'legend'=>array_column($info,'name'),
                'series'=>$info,
            );

            //表2  先取这段时间的最高的20个销售最高的20个商品
            //如果是导出 就查询所有商品
            if($isprint == 1){
                $limit ='';
            }else{
                $limit = ' limit 20';
            }

            if(($e_date-3600*24) == strtotime($new_time_date)) {
                $goods_money_20 =  M()->query(" select A.goods_id,A.goods_name,A.goods_cate_id,A.goods_cate_name,sum(buymoney) buymoney 
                          from (SELECT OD.d_id as goods_id,G.title as goods_name,G.cate_id AS goods_cate_id,GC.title as goods_cate_name,sum(OD.num) as buynum,sum(OD.num * OD.price) buymoney 
                                FROM hii_order O
                                LEFT JOIN hii_order_detail OD on OD.order_sn=O.order_sn
                                LEFT JOIN hii_goods G on G.id=OD.d_id
                                LEFT JOIN hii_goods_cate GC on  GC.id=G.cate_id
                                WHERE  O.create_time >= {$new_time_strto} AND O. STATUS = 5 AND O.`type` = 'store' {$sql_goods_top20_where} AND G.cate_id is not null
                                GROUP BY OD.d_id
                                union 
                                SELECT O.goods_id,O.goods_name,O.cate_id AS goods_cate_id,GC.title as goods_cate_name,sum(buynum) buynum,sum(buymoney) buymoney 
                                FROM hii_global_commodity_analysis O
                                LEFT JOIN hii_goods_cate GC on GC.id=O.cate_id 
                                WHERE O.ctime >= {$s_date} AND O.ctime < {$e_date}  $SQL_cate
                                GROUP BY O.goods_id)A
                                group by A.goods_id
                                order by buymoney desc {$limit}");
            }else{

                $goods_money_20 =  M()->query("
                                SELECT O.goods_id,O.goods_name,O.cate_id AS goods_cate_id,GC.title as goods_cate_name,sum(buynum) buynum,sum(buymoney) buymoney 
                                FROM hii_global_commodity_analysis O
                                LEFT JOIN hii_goods_cate GC on GC.id=O.cate_id 
                                WHERE O.ctime >= {$s_date} AND O.ctime < {$e_date}  $SQL_cate
                                GROUP BY O.goods_id
                                order by buymoney desc {$limit}
                         ");
            }

        }
        //导出
        if($isprint == 1){

            ob_clean;
            $fname = '商品全局分析'.$s_date .'>>>' .$e_date;
            $printmodel = new \Addons\Report\Model\ProductModel();
           // $printfile = $printmodel->OutProduct($top_cate, $top20_product, $cate_title, $data, $product_title,$top20_data, $fname);
            $printfile = $printmodel->OutProduct_new($first, $goods_money_20, $fname);
            //$printfile = $this->pushStoreJieCunList2($list,$title,$fname);
            echo($printfile);die;
        }
        $this->assign('first',json_encode($first));
        $this->assign('first_table2',$goods_money_20);
        $this->display(T('Addons://Report@MemberSale/global_commodity_analysis'));
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
    /**
     * 获取按天的时间段数组
     * @param unknown $s_date
     * @param unknown $e_data
     * @return array
     */
    private function get_time_day($s_date, $e_data){
        $datearr = array();
        while($s_date <= $e_data){
            $datearr[] = date('Y-m-d',$s_date);//得到dataarr的日期数组。
            $s_date=$s_date + 3600*24;
        }
        return $datearr;
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

    /**
     * 获取当前时间第几周
     * @param $time
     * @return int
     */
    private function get_weeks_num($time){
        $month = intval(date('m',$time));//当前时间的月份
        $fyear = strtotime(date('Y-01-01',$time));//今年第一天时间戳
        $fdate = intval(date('N',$fyear));//今年第一天 周几
        $sysweek = intval(date('W',$time));//系统时间的第几周
        //大于等于52 且 当前月为1时， 返回1
        if(($sysweek >= 52 && $month == 1)){
            return '01';
        }elseif($fdate == 1){
            //如果今年的第一天是周一,返回系统时间第几周
            if($sysweek < 10){
                return '0'.$sysweek;
            }
            return $sysweek;
        }else{
            if($sysweek < 9){
                return '0'.$sysweek + 1;
            }
            //返回系统周+1
            return $sysweek + 1;
        }
    }
}
