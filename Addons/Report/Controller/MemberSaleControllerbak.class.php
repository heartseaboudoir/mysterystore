<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class MemberSaleController extends AddonsController{
    //http://localhost/Admin/Addons/ex_Report/_addons/Report/_controller/StoreJieCunAdmin/_action/index.html
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }

    public function m_sale()
    {
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

        $where = array(
            'create_time' => array('between', array($starttime, $endtime)),
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
        $data = $model->alias('A')->join('left join hii_order_detail B on A.order_sn=B.order_sn')->where($where)->field($field)->order($order)->group($group)->select();
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
            $dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(showdata,0) as showdata from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from  hii_order limit " .$Days. ") B";
            $sql .= " left join (select c_time as ctime,count(distinct uid) as showdata from (select uid,FROM_UNIXTIME(create_time,'%Y-%m-%d') as c_time from hii_order";
            $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5) as tmp WHERE c_time  between '" .$s_date. "' and '" .$e_date. "' group by c_time) A on A.ctime=B.ctime order by ctime";
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
                    $sql .= "select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from hii_order";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }else{
                    $sql .= " union all select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from hii_order";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
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
                    $sql .= "select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from hii_order";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }else{
                    $sql .= " union all select '" .$arr[$i]. "' as ctime,count(distinct uid) as showdata from hii_order";
                    $sql .= " WHERE store_id = " .$this->_store_id ." and status = 5 and FROM_UNIXTIME(create_time,'%Y-%m-%d') between '" .$t1. "' and '" .$t2. "'";
                }
            }
            $data = M('order')->query($sql);
        }
        $this->meta_title = session('user_store.title') ."用户分析~活跃用户";
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
                FROM `hii_order` A
                left join `hii_order_detail` B on A.order_sn=B.order_sn
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and store_id = " .$this->_store_id ." and A.status = 5
                group by A.uid
                order by buymoney desc
            ) A left JOIN
            (
                select uid,d_id,goods_name,goods_id from
                  (
                      select a.* from
                      (
                      SELECT A.uid,B.d_id,count(B.d_id) as goods_id,C.title as goods_name
                        FROM `hii_order` A
                        left join `hii_order_detail` B on A.order_sn=B.order_sn
                        left join hii_goods C on B.d_id=C.id
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and store_id = " .$this->_store_id ." and A.status = 5
                        group by A.uid,B.d_id,C.title
                      ) as a
                      where not exists
                          (
                          select * from
                            (SELECT A.uid,B.d_id,count(B.d_id) as goods_id,C.title as goods_name
                                FROM `hii_order` A
                                left join `hii_order_detail` B on A.order_sn=B.order_sn
                                left join hii_goods C on B.d_id=C.id
                                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and store_id = " .$this->_store_id ." and A.status = 5
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
            $dataBegin = M()->execute('set @mycnt = 0;');
            $sql = "select B.ctime,ifnull(counttime,0) as counttime,ifnull(buymoney,0) as buymoney from (select date_add('" .$s_date_1. "',interval @mycnt :=@mycnt + 1 day) as ctime from  hii_order limit " .$Days. ") B left join (";
            $sql .= "
                SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as ctime,count(distinct A.order_sn) as counttime,sum(B.num*B.price) as buymoney
                FROM `hii_order` A
                left join `hii_order_detail` B on A.order_sn=B.order_sn
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "'
                 and store_id = " .$this->_store_id ." and A.status = 5 " .$where. "
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
                        FROM `hii_order` A
                        left join `hii_order_detail` B on A.order_sn=B.order_sn
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $t1 . "' and '" . $t2 . "'
                         and store_id = " . $this->_store_id . " and A.status = 5 " . $where . "
                    ";
                } else {
                    $sql .= "
                        union all
                        SELECT '" . $arr[$i] . "' as ctime,count(distinct A.order_sn) as counttime,sum(B.num*B.price) as buymoney
                        FROM `hii_order` A
                        left join `hii_order_detail` B on A.order_sn=B.order_sn
                        WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $t1 . "' and '" . $t2 . "'
                         and store_id = " . $this->_store_id . " and A.status = 5 " . $where . "
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
                FROM `hii_order` A
                left join `hii_order_detail` B on A.order_sn=B.order_sn
                left join `hii_goods` C on B.d_id=C.id
                left join `hii_goods_cate` D on C.cate_id=D.id
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and A.store_id = " .$this->_store_id ." and A.status = 5 " . $where . "
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
                FROM `hii_order` A
                left join `hii_order_detail` B on A.order_sn=B.order_sn
                left join `hii_goods` C on B.d_id=C.id
                left join `hii_goods_cate` D on C.cate_id=D.id
                WHERE FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" .$s_date. "' and '" .$e_date. "' and A.store_id = " .$this->_store_id ." and A.status = 5 " . $where . "
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
}
