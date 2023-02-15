<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class StoreSaleController extends AddonsController{

    public function __construct() {
        parent::__construct();
        $this->check_store();
    }

    public function allsale(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $storewhere3 = "";
        $storewhere8 = "";
        $goodsmainwhere = "";
        $pp = I('p',0,'intval');
        //查询条件：门店参数，类别参数，单个商品名参数，价格范围参数
        //勾选的门店
        $store_select = I('store');
        if($store_select == ""){
            $store_select = $_POST['store'];
        }
        if($store_select != ""){
            if( !is_array($store_select) ){
                $store_select = explode(",", $store_select);
            }
            $storewhere3 .= " and id in (" .implode(',',$store_select) . ")";
            $storewhere8 .= " and store_id in (" .implode(',',$store_select) . ")";
        }
        $this->assign('store_select', $store_select);
        //勾选的类别
        $goodscate_select = I('goodscate_select');
        if($goodscate_select == ""){
            $goodscate_select = $_POST['goodscate_select'];
        }
        if($goodscate_select != ""){
            if( !is_array($goodscate_select) ){
                $goodscate_select = explode(",", $goodscate_select);
            }
            $goodsmainwhere .= " and C.d_id in (select id as d_id from hii_goods where cate_id in (" .implode(',',$goodscate_select) . ") )";
        }
        $this->assign('goodscate_select', $goodscate_select);
        //按商品ID搜索
        $goods_id_search = I('goods_id');
        if($goods_id_search == ""){
            $goods_id_search = $_POST['goods_id'];
        }
        if($goods_id_search != ""){
            $goodsmainwhere .= " and C.d_id = " .$goods_id_search;
        }
        $this->assign('goods_id', $goods_id_search);
        //按商品名称搜索
        $goods_name_search = I('goods_name');
        if($goods_name_search == ""){
            $goods_name_search = $_POST['goods_name'];
        }
        if($goods_name_search != ""){
            $goodsmainwhere .= " and C.d_id in (select id as d_id from hii_goods where title like '%" .$goods_name_search . "%' )";
        }
        $this->assign('goods_name', $goods_name_search);
        //最低价
        $min_price_search = I('min_price');
        if($min_price_search == ""){
            $min_price_search = $_POST['min_price'];
        }
        if($min_price_search != ""){
            $goodsmainwhere .= " and C.price >= " .$min_price_search;
        }
        $this->assign('min_price', $min_price_search);
        //最高价
        $max_price_search = I('max_price');
        if($max_price_search == ""){
            $max_price_search = $_POST['max_price'];
        }
        if($max_price_search != ""){
            $goodsmainwhere .= " and C.price <= " .$max_price_search;
        }
        $this->assign('max_price', $max_price_search);

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
           // $new_time = strtotime('-1 day');
            $new_time = time();
            $e_date = mktime(0,0,0,date("m",$new_time),date("d",$new_time),date("Y",$new_time));
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
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
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
        /*$map = array();
        $map['id'] = array('in',$stores);
        $_my_store1 = M('Store')->where($map)->select();*/
        !$shequ && $shequ = array();
        $where = array();
        $stores && $where['id'] = array('in', $stores);
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
        $_storeary = array_column($store,'id');
        $storewhere3 .= " and id in (" .implode(',',$_storeary) . ")";
        //查询条件：类别
        $goodscate = M('GoodsCate')->field('id, title')->select();
        $this->assign('goodscate', $goodscate);


        $strorder = " order by";
        //排序条件：销量
        $num_order = I('num_order');
        if($num_order == ""){
            $num_order = $_POST['num_order'];
        }
        if($num_order == "1"){
            $strorder .= " num desc";
        }
        $this->assign('num_order', $num_order);
        //排序条件：金额
        $amount_order = I('amount_order');
        if($amount_order == ""){
            $amount_order = $_POST['amount_order'];
        }
        if($amount_order == "1"){
            if($strorder == " order by"){
                $strorder .= " amount desc";
            }else{
                $strorder .= ",amount desc";
            }
        }
        $this->assign('amount_order', $amount_order);
        //排序条件：订单数
        $sn_order = I('sn_order');
        if($sn_order == ""){
            $sn_order = $_POST['sn_order'];
        }
        if($sn_order == "1"){
            if($strorder == " order by"){
                $strorder .= " sn desc";
            }else{
                $strorder .= ",sn desc";
            }
        }
        $this->assign('sn_order', $sn_order);
        if($strorder == " order by"){
            $strorder .= " S.shequ_id,S.id";
        }
        $storewhere4 = $storewhere3;
        //$storewhere3 = ' 1=1 ' .$storewhere3;
        //$list = $model->where($storewhere3)->order('shequ_id,id')->limit($Page->firstRow.','.$Page->listRows)->select();
        $where['_string'] = '1=1';

        if($pp || IS_POST ||  $isprint == 1 || $storewhere8 != "" || $goodsmainwhere!= "" || $strorder != " order by S.shequ_id,S.id") {

            //订单表太庞大，查询SQL语句太慢，没办法，先获取门店条件查出来的数组，循环门店数组,再查询对应时间条件范围内、商品条件范围内的数据，保存到临时表
            //然后再join查询临时表
            $field = "A.id as store_id,A.title as store_name";
            $field .= ",ifnull(count(DISTINCT C.order_sn),0) as sn_qty_day,sum(ifnull(C.pay_money,0)) as pay_money_day,sum(ifnull(C.num,0)) as qty_day,sum(ifnull(C.num,0)*ifnull(C.price,0)) as amount_day";
            $group = "A.id,A.title";
            $listout = array();
            $days = $this->diffBetweenTwoDays($s_date, $e_date);
            //合计
            $Model0 = M('Store');
            /*$sql = "select S.id as store_id,S.title as store_name,ifnull(COUNT(DISTINCT A.order_sn),0) as sn,ifnull(sum(C.num),0) as num,ifnull(sum(C.num*C.price),0) as amount
          from hii_store S left join hii_order A on S.id=A.store_id left join hii_order_detail C on A.order_sn=C.order_sn
          where FROM_UNIXTIME(A.create_time,'%Y-%m-%d')  between '" . $s_date . "' and '" . $e_date . "' and S.status = 1 and A.status=5 " . $goodsmainwhere . $storewhere8 . " group by S.id,S.title" .$strorder;*/

            $sql = "
            select S.id,S.title,ifnull(A.sn,0) as sn,ifnull(A.num,0) as num,ifnull(A.amount,0) as amount,if(A.count_uid,A.count_uid,0) as count_uid 
                from hii_store S
                left join (
                  select A.store_id,COUNT(DISTINCT A.order_sn) as sn,sum(C.num) as num,sum(C.num*C.price) as amount,count(distinct A.uid)count_uid 
                  from hii_order A  left join hii_order_detail C on A.order_sn=C.order_sn
                    where FROM_UNIXTIME(A.create_time,'%Y-%m-%d') between '" . $s_date . "' and '" . $e_date . "' and A.status=5 and A.type='store'" . $goodsmainwhere . $storewhere8 . "
                    group by A.store_id ) A on S.id=A.store_id
                where 1=1 " .$storewhere4 . $strorder . "
            ";
            $data00 = $Model0->query($sql);
            //print_r($data00);die;
            //分页
            $model = M('store');
            if($isprint == ""){
                $pcount=30;
            }else{
                $pcount=3000;
            }
            $count = count($data00);
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $Page->parameter['s_date'] = $s_date;
            $Page->parameter['e_date'] = $e_date;
            $Page->parameter['store'] = implode(',', $store_select);
            $Page->parameter['goodscate_select'] = implode(',', $goodscate_select);
            $Page->parameter['goods_id'] = $goods_id_search;
            $Page->parameter['goods_name'] = $goods_name_search;
            $Page->parameter['min_price'] = $min_price_search;
            $Page->parameter['max_price'] = $max_price_search;
            $Page->parameter['num_order'] = $num_order;
            $Page->parameter['amount_order'] = $amount_order;
            $Page->parameter['sn_order'] = $sn_order;
            $list = array_slice($data00,$Page->firstRow,$Page->listRows);
            $show = $Page->show();

            $sumqty_day = 0;
            $sumamount_day = 0;
            $sumsn_qty_day = 0;
            $count_uid_qty_day = 0;
            if (is_array($data00) && count($data00) > 0) {
                $numarray = array_column($data00, 'num');
                $sumqty_day = array_sum($numarray);
                $amountarray = array_column($data00, 'amount');
                $sumamount_day = array_sum($amountarray);
                $snarray = array_column($data00, 'sn');
                $sumsn_qty_day = array_sum($snarray);
                $count_uid = array_column($data00, 'count_uid');
                $count_uid_qty_day = array_sum($count_uid);
            }

            for ($i = 0; $i < count($list); $i++) {
                $where3 = " And A.id =" . $list[$i]['id'];
                $listout_storename[] = $list[$i]['title'];
                $whereone['_string'] = $where['_string'] . $where3 . $goodsmainwhere;
                //$sql = "CREATE TEMPORARY TABLE tmp_sale_order" .$list[$i]['id']. " select store_id,sum(pay_money) as pay_money from hii_order where FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' and status=5 and store_id=" .$list[$i]['id'] ." group by store_id";
                //$data1 = M()->execute($sql);
                $sql = "CREATE TEMPORARY TABLE tmp_sale_order_detail" . $list[$i]['id'] . "
                select A.store_id,A.order_sn,A.pay_money,C.d_id,C.num,C.price from hii_order A left join hii_order_detail C on A.order_sn=C.order_sn
                where FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" . $s_date . "' and '" . $e_date . "'  and status=5 and A.type='store' and store_id=" . $list[$i]['id'] . $goodsmainwhere;
                $data1 = M()->execute($sql);
                /*$model = M('store');
                $list1 = $model->alias('A')
                    ->join("left join tmp_sale_order_detail" . $list[$i]['id'] . " C on A.id=C.store_id")
                    ->field($field)->where($whereone)->group($group)->order('A.id')
                    ->select();*/

                if ($list[$i]['num']>0) {
                    $listout_storesnqty[] = $list[$i]['sn'];//$list1[0]['sn_qty_day'];
                    $listout_storenum[] = $list[$i]['num'];//$list1[0]['qty_day'];
                    $listout_storeamount[] = $list[$i]['amount'];//$list1[0]['amount_day'];
                    $listout1[] = $list[$i];
                    if ($list[$i]['num'] > 0) {
                        //商品销售TOP10
                        $sql = "
                    SELECT A.d_id,A.goods_name,A.cate_name,A.buynum,A.buymoney,FORMAT(A.buynum/" . $days . ",2) as avgnum,FORMAT(A.buymoney/" . $days . ",2) as avgmoney FROM
                    (
                        SELECT d_id,C.title as goods_name,D.title as cate_name,sum(A.num) as buynum,sum(A.num*A.price) as buymoney
                        FROM tmp_sale_order_detail" . $list[$i]['id'] . " A
                        left join `hii_goods` C on A.d_id=C.id
                        left join `hii_goods_cate` D on C.cate_id=D.id
                        WHERE 1=1
                        group by A.d_id,C.title,D.title limit 0,10
                    ) A
                    order by A.buymoney desc limit 0,10
                 ";
                        $goodsdata1 = M()->query($sql);
                        $list[$i]['goodschild'] = $goodsdata1;
                        //商品分类销售TOP10
                        $sql = "
                    SELECT cate_id,A.cate_name,A.buynum,A.buymoney,FORMAT(A.buynum/" . $days . ",2) as avgnum,FORMAT(A.buymoney/" . $days . ",2) as avgmoney FROM
                    (
                        SELECT C.cate_id,D.title as cate_name,sum(A.num) as buynum,sum(A.num*A.price) as buymoney
                        FROM tmp_sale_order_detail" . $list[$i]['id'] . " A
                        left join `hii_goods` C on A.d_id=C.id
                        left join `hii_goods_cate` D on C.cate_id=D.id
                        WHERE 1=1
                        group by D.title
                    ) A
                    order by A.buymoney desc
                 ";
                        $catedata1 = M()->query($sql);
                        $list[$i]['catechild'] = $catedata1;
                    } else {
                        $list[$i]['goodschild'] = array();
                        $list[$i]['catechild'] = array();
                    }
                    $listout[] = $list[$i];
                } else {
                    $listout_storesnqty[] = 0;
                    $listout_storenum[] = 0;
                    $listout_storeamount[] = 0;
                    $outlist['id'] = $list[$i]['id'];
                    $outlist['title'] = $list[$i]['title'];
                    $outlist['sn'] = "0";
                    $outlist['amount'] = "0.00";
                    $outlist['num'] = "0.00";
                    $outlist['count_uid'] = "0";
                    $outlist['goodschild'] = array();
                    $outlist['catechild'] = array();
                    $listout[] = $outlist;
                }
            }
        }else{
            $listout = array();
        }

        if($isprint == 1) {
            //导出数据Excel【PHPExcel】
            $title = $s_date .'>>>' .$e_date;
            if($goodscate_select != ''){
                $title .= '>>>' .$goodscate_select;
            }
            if($goods_name_search != ''){
                $title .= '>>>' .$goods_name_search;
            }
            if($min_price_search != ''){
                $title .= '>>>' .$min_price_search;
            }
            if($max_price_search != ''){
                $title .= '>>>' .$max_price_search;
            }
            $title .= '>>>门店销售对比';
            $fname = 'StoreSale_'.time().'.xlsx';
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushStoreSaleList($listout,$title,$fname,$sumqty_day,$sumamount_day,$sumsn_qty_day,$count_uid_qty_day);
            //$printfile = $this->OutStoreSaleList1Function($list,$title,$fname);
            echo($printfile);die;
        }
        $this->assign('list', $listout);
        $this->assign('sumqty_day', $sumqty_day);
        $this->assign('sumamount_day', $sumamount_day);
        $this->assign('sumsn_qty_day', $sumsn_qty_day);
        $this->assign('count_uid_qty_day', $count_uid_qty_day);
        $this->assign('listpic', json_encode($listout1));
        $this->assign('listout_storename', json_encode($listout_storename));
        $this->assign('listout_storesn', json_encode($listout_storesnqty));
        $this->assign('listout_storenum', json_encode($listout_storenum));
        $this->assign('listout_storeamount', json_encode($listout_storeamount));
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = '门店销售对比';
        $this->display(T('Addons://Report@StoreSale/allsale'));
        exit;
    }

    public function storesale(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $pre = C('DB_PREFIX');
        $id = I('id');
        if($id == ""){
            $id = $_POST['id'];
        }
        if($id == '') {
            $this->error('错误的门店id！');
            exit;
        }
        $this->assign('id', $id);

        //门店
        $model = M('store');
        $liststore = $model->where("id=" .$id)->find();
        $s_name = $liststore['title'];
        $goodsmainwhere = '';
        $wherechild = '';
        //$storewhere3 = " 1=1 ";
        //$goodsmainwhere = " 1=1 ";
        //查询条件：类别参数，单个商品名参数，价格范围参数
        //勾选的类别
        $goodscate_select = I('goodscate_select');
        if($goodscate_select == ""){
            $goodscate_select = $_POST['goodscate_select'];
        }
        if($goodscate_select != ""){
            if( !is_array($goodscate_select) ){
                $goodscate_select = explode(",", $goodscate_select);
            }
            $goodsmainwhere .= " and C.d_id in (select id as d_id from hii_goods where cate_id in (" .implode(',',$goodscate_select) . ") )";
            $wherechild .= " and C.d_id in (select id as d_id from hii_goods where cate_id in (" .implode(',',$goodscate_select) . ") )";
        }
        $this->assign('goodscate_select', $goodscate_select);
        //按商品名称搜索
        $goods_name_search = I('goods_name');
        if($goods_name_search == ""){
            $goods_name_search = $_POST['goods_name'];
        }
        if($goods_name_search != ""){
            $goodsmainwhere .= " and C.d_id in (select id as d_id from hii_goods where title like '%" .$goods_name_search . "%' )";
            $wherechild .= " and C.d_id in (select id as d_id from hii_goods where title like '%" .$goods_name_search . "%' )";
        }
        $this->assign('goods_name', $goods_name_search);
        //最低价
        $min_price_search = I('min_price');
        if($min_price_search == ""){
            $min_price_search = $_POST['min_price'];
        }
        if($min_price_search != ""){
            $goodsmainwhere .= " and C.price >= " .$min_price_search;
            $wherechild .= " and C.price >= " .$min_price_search;
        }
        $this->assign('min_price', $min_price_search);
        //最高价
        $max_price_search = I('max_price');
        if($max_price_search == ""){
            $max_price_search = $_POST['max_price'];
        }
        if($max_price_search != ""){
            $goodsmainwhere .= " and C.price <= " .$max_price_search;
            $wherechild .= " and C.price <= " .$max_price_search;
        }
        $this->assign('max_price', $max_price_search);

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
        $store = M('Store')->field('id, shequ_id , title, sell_type')->select();
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
        $wheremain .= " and A.store_id in (" .implode(',',$_storeary) . ")";

        //查询条件：类别
        $goodscate = M('GoodsCate')->field('id, title')->select();
        $this->assign('goodscate', $goodscate);

        $days = $this -> diffBetweenTwoDays($s_date, $e_date);
        //商品销售排行
        $sql = "CREATE TEMPORARY TABLE tmp_sale_order_detail" .$id. " select A.store_id,A.order_sn,A.pay_money,C.d_id,C.num,C.price from hii_order A left join hii_order_detail C on A.order_sn=C.order_sn where FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'  and status=5 and A.store_id=" .$id .$wheremain .$wherechild;
        $data1 = M()->execute($sql);
        $sql = "
		SELECT A.d_id,A.goods_name,A.cate_name,A.buynum,A.buymoney,FORMAT(A.buynum/" .$days. ",2) as avgnum,FORMAT(A.buymoney/" .$days. ",2) as avgmoney FROM
		(
			SELECT d_id,C.title as goods_name,D.title as cate_name,sum(A.num) as buynum,sum(A.num*A.price) as buymoney
			FROM tmp_sale_order_detail" .$id. " A
			left join `hii_goods` C on A.d_id=C.id
			left join `hii_goods_cate` D on C.cate_id=D.id
			WHERE 1=1
			group by A.d_id,C.title,D.title
		) A
		order by A.buymoney desc
	 ";
        $goodsdata1 = M()->query($sql);
        //商品分类销售排行
        $sql = "
		SELECT cate_id,A.cate_name,A.buynum,A.buymoney,FORMAT(A.buynum/" .$days. ",2) as avgnum,FORMAT(A.buymoney/" .$days. ",2) as avgmoney FROM
		(
			SELECT C.cate_id,D.title as cate_name,sum(A.num) as buynum,sum(A.num*A.price) as buymoney
			FROM tmp_sale_order_detail" .$id. " A
			left join `hii_goods` C on A.d_id=C.id
			left join `hii_goods_cate` D on C.cate_id=D.id
			WHERE 1=1
			group by D.title
		) A
		order by A.buymoney desc
	 ";
        $catedata1 = M()->query($sql);
        /*
        $fieldmain = "A.id as store_id,B.id as order_id,A.title as store_name,B.uid,B.create_time,B.order_sn,B.pay_money";
        $field = "C.id,A.id as store_id,A.title as store_name,B.uid,B.create_time,B.id as order_id";
        $field .= ",B.order_sn,B.pay_money,C.num,ifnull(C.price,0) as sale_price,D.id as goods_id,D.title as goods_name,E.title as cate_name";
        $order = "B.order_sn";
        $sql="CREATE TEMPORARY TABLE tmp_sale_order select id,order_sn,store_id,uid,pay_money,create_time from hii_order where FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' and status=5" .$wheremain;
        $data1 = M()->execute($sql);
        $sql="CREATE TEMPORARY TABLE tmp_sale_order_detail_s select A.id,A.order_sn,B.d_id,B.num,B.price from hii_order A left join hii_order_detail B on A.order_sn=B.order_sn where FROM_UNIXTIME(create_time,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "' and status=5" .$wheremain .$wherechild;
        $data1 = M()->execute($sql);

        $model = M('store');
        $listmain = $model->alias('A')
            ->join('left join tmp_sale_order B on A.id=B.store_id')
            ->join('left join tmp_sale_order_detail_s C on B.order_sn=C.order_sn')
            ->join('left join hii_goods D on C.d_id=D.id')
            ->join('left join hii_goods_cate E on D.cate_id=E.id')
            ->field($fieldmain)->where('A.id = ' .$id .' and' .$goodsmainwhere)->order($order)->distinct(true)
            ->select();
        $s_name = $listmain[0]['store_name'];
        if(count($listmain) == 1 && $listmain[0]['order_id'] == ''){
            $listmain =array();
        }*/
        //分页
        $pcount=80;
        $count=count($goodsdata1);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $listmain1 = array_slice($goodsdata1,$Page->firstRow,$Page->listRows);
        if(count($listmain1) > 0){
            $order_sn_array = array_column($listmain1,'order_sn');
            if($order_sn_array){
                $wheresn = " And C.order_sn in ('" .implode('\',\'',$order_sn_array) . "')";
            }
            $where1 = $where;
            $where1['_string'] .= '1=1' .$wheresn;
        }
        $Page->parameter['id']   		=   $id;
        $Page->parameter['s_date']   		=   $s_date;
        $Page->parameter['e_date']   		=   $e_date;
        $Page->parameter['goodscate_select']   =   implode(',',$goodscate_select);
        $Page->parameter['goods_name']   =   $goods_name;
        $Page->parameter['min_price']   =   $min_price;
        $Page->parameter['max_price']   =   $max_price;
        $show= $Page->show();// 分页显示输出﻿
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            //导出数据Excel【PHPExcel】
            $title = $s_name;
            $title .= '>>>' .$s_date .'>>>' .$e_date;
            if($goodscate_select != ''){
                $title .= '>>>' .$goodscate_select;
            }
            if($goods_name_search != ''){
                $title .= '>>>' .$goods_name_search;
            }
            if($min_price_search != ''){
                $title .= '>>>' .$min_price_search;
            }
            if($max_price_search != ''){
                $title .= '>>>' .$max_price_search;
            }
            $title .= '>>>门店销售详情';
            $fname = 'StoreSaleView_'.time().'.xlsx';
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushStoreSaleView($goodsdata1,$catedata1,$title,$fname);
            //$printfile = $this->OutStoreSaleList1Function($list,$title,$fname);
            echo($printfile);die;
            //导出数据Excel【PHPExcel】
            date_default_timezone_set("PRC");
            $starttimeout = date("Y年m月d日",strtotime($s_date));
            $endtimeout = date("Y年m月d日",strtotime($e_date));
            $title =$s_name .'>>>' .$starttimeout .'>>>' .$endtimeout .'>>>门店销售详情';
            $fname = $s_name .'_门店销售详情_'.$starttimeout.'_'.$endtimeout;
            $listmain2 = $listmain;
            if(count($listmain2) > 0){
                $order_sn_array = array_column($listmain2,'order_sn');
                if($order_sn_array){
                    $wheresn = " 1=1 And C.order_sn in (" .implode(',',$order_sn_array) . ")";
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
        $this->assign('catelist', $catedata1);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);

        $starttimeout = date("Y年m月d日",strtotime($s_date));
        $endtimeout = date("Y年m月d日",strtotime($e_date));
        $title = $s_name .'>>>' . $starttimeout .'~' .$endtimeout .'>>>门店销售详情';
        $this->meta_title =$title;
        $this->display(T('Addons://Report@StoreSale/storesale'));
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
