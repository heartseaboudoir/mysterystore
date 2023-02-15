<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseErrorController extends AddonsController{

    public function __construct() {
        parent::__construct();
    }

    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购单管理';
        $where = '';
        //门店选择
        $select_store = I('select_store_id');
        if($select_store != '' && $select_store != 0) {
            $where .= ' And A.store_id = ' .$select_store;
            $this->assign('store_id', $select_store);
        }
        //仓库选择
        $select_warehouse = I('select_warehouse_id');
        if($select_warehouse != '' && $select_warehouse != 0) {
            $where .= ' And A.warehouse_id = ' .$select_warehouse;
            $this->assign('warehouse_id', $select_warehouse);
        }
        //供应商选择
        $select_supply = I('select_supply_id');
        if($select_supply != '' && $select_supply != 0) {
            $where .= ' And A.supply_id = ' .$select_supply;
            $this->assign('supply_id', $select_supply);
        }
        //商品搜索
        $goods_name = I('goods_name');
        if ($goods_name != "" && $goods_name != "商品名" ) {
            //显示隐藏已处理单据
            $wheregoodsname = " and A1.goods_id in (select id as goods_id from hii_goods where title like '%" .$goods_name ."%')";
            $this->assign('goods_name', $goods_name);
        }
        //单号
        $sn = I('sn');
        if($sn != ''){
            $where .= " And (PS.p_s_sn like '%" .$sn ."%' or A.p_sn like '%" .$sn ."%')";
            $this->assign('sn', $sn);
        }
        //时间范围默认30天
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
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);

        $title = $s_date. '>>>' .$e_date. '采购单';

        $Model = M('Purchase');
        $where0 = " And (";
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ('Supply','A.supply_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Store','A.store_id');
        $where0 .= " )";
        $where0 .= $where;
        $sql = "
      select A.p_id,A.p_sn,A.p_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,
        A.supply_id,S.s_name,A.store_id,ST.title as store_name,A.remark,A.g_type,A.g_nums,
        A.w_in_id,A.s_in_id,PS.p_s_sn,A.padmin_id,B1.nickname as pnickname,
        sum(A1.g_num*(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,
        sum(A1.b_num*A1.b_price) as p_amounts,sum(A1.g_num*A1.g_price) as p_amounts1
         from  hii_purchase A
          left join hii_purchase_detail A1 on A.p_id=A1.p_id
          left join hii_member B on A.admin_id=B.uid
          left join hii_member B1 on A.padmin_id=B1.uid
          left join hii_warehouse C on A.warehouse_id=C.w_id
          left join hii_supply S on A.supply_id=S.s_id
          left join hii_store ST on A.store_id=ST.id
          left join hii_goods G on A1.goods_id=G.id
          left join hii_goods_store GS on A.store_id=GS.store_id and A1.goods_id=GS.goods_id
          left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
          left join hii_purchase_supply PS on A.p_id=PS.p_id
          where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'".$where0 .$wheregoodsname ."
        group by A.p_id,A.p_sn,A.p_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.store_id,ST.title,
        A.remark,A.g_type,A.g_nums,A.w_in_id,PS.p_s_id,A.padmin_id,B1.nickname
        order by A.p_id desc
        ";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushPurchaseList($data,$title,$fname);
            echo($printfile);die;
        }
        $shequ = $_SESSION['can_shequs_cg'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->where($whereSQ)->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->where($whereSQ)->select();
        $ModelStore = M('Store');
        $StoreData = $ModelStore->where($whereSQ)->select();

        $this->assign('warehouse', $WarehouseData);
        $this->assign('supply', $SupplyData);
        $this->assign('store', $StoreData);
        //分页
        $pcount=10;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿


        /*WI.w_in_sn,WI.w_in_status,SI.s_in_sn,SI.s_in_status,
        ,sum(WID.in_num) as win_nums,sum(WID.out_num) as wout_num,
        sum(SID.in_num) as sin_nums,sum(SID.out_num) as sout_num
          left join (select w_in_id,w_in_sn,w_in_status from hii_warehouse_in) WI on A.w_in_id=WI.w_in_id
          left join (select s_in_id,s_in_sn,s_in_status from hii_store_in) SI on A.s_in_id=SI.s_in_id
          left join (select w_in_id,w_in_d_id,p_d_id,in_num,out_num from hii_warehouse_in_detail) WID on WI.w_in_id=WID.w_in_id and A1.p_d_id = WID.p_d_id
          left join (select s_in_id,s_in_d_id,p_d_id,in_num,out_num from hii_store_in_detail) SID on SI.s_in_id=SID.s_in_id and A1.p_d_id = SID.p_d_id
        WI.w_in_sn,,WI.w_in_sn,SI.s_in_sn
        */
        foreach($datamain as $v){
            $Model = M('WarehouseIn');
            $sql="
              select WI.w_in_sn,WI.w_in_status,sum(WID.in_num) as win_nums,sum(WID.out_num) as wout_num
              from hii_warehouse_in WI
              left join hii_warehouse_in_detail WID on WI.w_in_id=WID.w_in_id
              where WI.p_id = " .$v['p_id']. "
            ";
            $dataWI = $Model->query($sql);
            $v['w_in_sn'] = $dataWI[0]['w_in_sn'];
            $v['w_in_status'] = $dataWI[0]['w_in_status'];
            $v['win_nums'] = $dataWI[0]['win_nums'];
            $v['wout_num'] = $dataWI[0]['wout_num'];
            $Model = M('WarehouseIn');
            $sql="
              select SI.s_in_sn,SI.s_in_status,sum(SID.in_num) as sin_nums,sum(SID.out_num) as sout_num
              from hii_store_in SI
              left join hii_store_in_detail SID on SI.s_in_id=SID.s_in_id
              where SI.p_id = " .$v['p_id']. "
            ";
            $dataWI = $Model->query($sql);
            $v['s_in_sn'] = $dataWI[0]['s_in_sn'];
            $v['s_in_status'] = $dataWI[0]['s_in_status'];
            $v['sin_nums'] = $dataWI[0]['sin_nums'];
            $v['sout_num'] = $dataWI[0]['sout_num'];
            $dataout[] = $v;
        }
        $this->assign('list', $dataout);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Purchase@PurchaseError/index'));
    }

    public function view()
    {
        $this->meta_title = '采购单查看';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }

        if($id == ''){
            $this->error('不存在的单据ID');
        }
        $this->assign('id', $id);

        $Model = M('Purchase');
        $purchase = $Model->where('p_id=' .$id)->find();
        if(!$purchase){
            $this->error('不存在的单据');
        }

        $sql = "
          select A.p_id,A.p_sn,A.p_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,
            A.supply_id,S.s_name,A.store_id,ST.title as store_name,A.remark,A.g_type,A.g_nums,A.p_s_id,
            A.w_in_id,WI.w_in_sn,A.s_in_id,SI.s_in_sn,PS.p_s_sn,A.padmin_id,B1.nickname as pnickname,
            sum(A1.g_num*(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.b_num*A1.b_price) as p_amounts,sum(A1.g_num*A1.g_price) as p_amounts1,
            sum(WID.in_num) as win_nums,sum(WID.out_num) as wout_num,sum(WID.out_num*A1.g_price) as wout_amount,
            sum(SID.in_num) as sin_nums,sum(SID.out_num) as sout_num,sum(SID.out_num*A1.g_price) as sout_amount
         from  hii_purchase A
         left join hii_purchase_detail A1 on A.p_id=A1.p_id
         left join hii_member B on A.admin_id=B.uid
         left join hii_member B1 on A.padmin_id=B1.uid
         left join hii_warehouse C on A.warehouse_id=C.w_id
         left join hii_supply S on A.supply_id=S.s_id
         left join hii_store ST on A.store_id=ST.id
        left join hii_goods_store GS on A.store_id=GS.store_id and A1.goods_id=GS.goods_id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
         left join hii_goods G on A1.goods_id=G.id
         left join hii_purchase_supply PS on A.p_id=PS.p_id
          left join (select w_in_id,w_in_sn,w_in_status from hii_warehouse_in) WI on A.w_in_id=WI.w_in_id
          left join (select s_in_id,s_in_sn,s_in_status from hii_store_in) SI on A.s_in_id=SI.s_in_id
          left join (select w_in_id,w_in_d_id,p_d_id,in_num,out_num from hii_warehouse_in_detail) WID on WI.w_in_id=WID.w_in_id and A1.p_d_id = WID.p_d_id
          left join (select s_in_id,s_in_d_id,p_d_id,in_num,out_num from hii_store_in_detail) SID on SI.s_in_id=SID.s_in_id and A1.p_d_id = SID.p_d_id
         where A.p_id=$id
         group by A.p_id,A.p_sn,A.p_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.store_id,ST.title,
         A.remark,A.g_type,A.g_nums,A.w_in_id,WI.w_in_sn,PS.p_s_id,A.padmin_id,B1.nickname,WI.w_in_sn,SI.s_in_sn
         order by A.p_id desc
         ";
        $list = $Model->query($sql);

        $sql = "select AV.value_id,AV.value_name,A.p_id,A.p_sn,C.title as cate_name,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,";
        $sql .= "A1.p_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.b_n_num,A1.b_num,A1.b_price,A1.g_num,A1.g_price,A1.remark,";
        $sql .= "WID.in_num as win_num,WID.out_num as wout_num,";
        $sql .= "SID.in_num as sin_num,SID.out_num as sout_num,";
        $sql .= "ifnull(L.supply_price,0) as last_price";
        $sql .= " from  hii_purchase A";
        $sql .= " left join hii_purchase_detail A1 on A.p_id=A1.p_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join hii_warehouse W on A.warehouse_id=W.w_id";
        $sql .= " left join hii_store S on A.store_id=S.id";
        $sql .= " left join hii_goods_store GS on A.store_id=GS.store_id and A1.goods_id=GS.goods_id";
        $sql .= " left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id";
        $sql .= " left join hii_goods_supply L on A1.goods_id=L.goods_id and L.supply_id = A.supply_id and (L.shequ_id = S.shequ_id or L.shequ_id = W.shequ_id) ";
        $sql .= " left join hii_warehouse_in_detail WID on A1.p_d_id=WID.p_d_id";
        $sql .= " left join hii_store_in_detail SID on A1.p_d_id=SID.p_d_id";
        $sql .= " left join hii_attr_value AV on A1.value_id=AV.value_id";
        $sql .= " where A.p_id=$id";
        $sql .= " order by p_id desc";
        $data = $Model->query($sql);
        $title = '采购单' .$list[0]['p_sn'] .'查看';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushPurchaseView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        $shequ = $_SESSION['can_shequs_cg'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->where($whereSQ)->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->where($whereSQ)->select();
        $ModelStore = M('Store');
        $StoreData = $ModelStore->where($whereSQ)->select();

        $this->assign('store', $StoreData);
        $this->assign('warehouse', $WarehouseData);
        $this->assign('supply', $SupplyData);
        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Purchase@PurchaseError/view'));
    }
    public function updatepurchase()
    {
        $p_id = I('p_id', '');
        if ($p_id == '') {
            $p_id = $_POST['p_id'];
        }
        $warehouse_id = I('warehouse_id', '');
        if ($warehouse_id == '') {
            $warehouse_id = $_POST['warehouse_id'];
        }
        $store_id = I('store_id', '');
        if ($store_id == '') {
            $store_id = $_POST['store_id'];
        }
        if ($warehouse_id == '' || $warehouse_id == '0') {
            if ($store_id == '' || $store_id == '0') {
                $this->error('收货仓库或者门店必选其一');
            }
        }
        if ($warehouse_id != '' && $warehouse_id != '0' && $store_id != '' && $store_id != '0') {
            $this->error('不能同时选收货仓库、门店');
        }
        $supply_id = I('supply_id', '');
        if ($supply_id == '') {
            $supply_id = $_POST['supply_id'];
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $p_d_id = I('p_d_id', '');
        if ($p_d_id == '') {
            $p_d_id = $_POST['p_d_id'];
        }
        $b_n_num = I('b_n_num', '');
        if ($b_n_num == '') {
            $b_n_num = $_POST['b_n_num'];
        }
        $b_num = I('b_num', '');
        if ($b_num == '') {
            $b_num = $_POST['b_num'];
        }
        $b_price = I('b_price', '');
        if ($b_price == '') {
            $b_price = $_POST['g_price'];
        }
        $g_num = I('g_num', '');
        if ($g_num == '') {
            $g_num = $_POST['g_num'];
        }
        $g_price = I('g_price', '');
        if ($g_price == '') {
            $g_price = $_POST['g_price'];
        }
        if($p_id == ''){
            $this->error('不存在的单据ID');
        }
        if($p_d_id == '' || $p_d_id == '0'){
            $this->error('必须有商品数据');
        }
        $remark_detail = I('remark_detail', '');
        if ($remark_detail == '') {
            $remark_detail = $_POST['remark_detail'];
        }
        if ($p_id != '') {
            $where = array();
            $where['p_d_id'] = array('in',$p_d_id);
            $field = "p_d_id,p_id,goods_id,b_n_num,b_num,b_price,g_num,g_price,value_id";
            $DetailList = M('PurchaseDetail')
                ->field($field)
                ->where($where)->order('p_d_id asc')->select();
            if(!count($DetailList)>0){
                $this->error('没有采购商品');
            }
            $g_nums = 0;
            for($i = 0;$i < count($DetailList);$i++) {
                for($j = 0;$j < count($p_d_id);$j++){
                    if($DetailList[$i]['p_d_id'] == $p_d_id[$j]){
                        $modelGoods = M('Goods');
                        $dataGoods = $modelGoods->where('id=' .$DetailList[$i]['goods_id'])->select();
                        if(floatval($g_price[$j]) > floatval($dataGoods[0]['sell_price'])){
                            $this->error('采购单价不能高于系统售价');
                        }
                        $DetailList[$i]['g_price'] = $g_price[$j];
                        $DetailList[$i]['g_num'] = $g_num[$j];
                        $DetailList[$i]['remark'] = $remark_detail[$j];
                        $DetailList[$i]['b_n_num'] = $b_n_num[$j];
                        $DetailList[$i]['b_num'] = $b_num[$j];
                        $DetailList[$i]['b_price'] = $b_price[$j];
                        $g_nums += $g_num[$j];
                    }
                }
            }
            $where['p_id'] = $p_id;
            $Model = M('Purchase');
            $data = $Model->where($where)->find();
            if(!$data){
                $this->error('没有单据');
            }
            if ($warehouse_id != '' && $warehouse_id != '0'){
                $data['warehouse_id'] = $warehouse_id;
            }
            if ($store_id != '' && $store_id != '0'){
                $data['store_id'] = $store_id;
            }
            $data['supply_id'] = $supply_id;
            $data['remark'] = $remark;
            $data['etime'] = time();
            $data['eadmin_id'] = UID;
            $data['g_nums'] = $g_nums;

            $Model = D('Addons://Purchase/Purchase');
            $res = $Model->savePurchase($p_id,$data,$DetailList,false);
            if($res>0){
                $this->success('提交成功');
            }else{
                $this->error($Model->err['msg']);
            }
        }
    }

    public function again()
    {
        $this->meta_title = '采购单再次申请';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }

        if ($id == '') {
            $this->error('不存在的单据ID');
        }
        $Model0 = M('Purchase');
        $sql = "select A.p_id,A.p_sn,C.title as cate_name,G.sell_price,ifnull(WS.num,0) as stock_num,";
        $sql .= "A1.goods_id,G.title as goods_name,G.bar_code,A1.b_n_num,A1.b_num,A1.b_price,A1.g_num,A1.g_price,A1.value_id";
        $sql .= " from  hii_purchase A";
        $sql .= " left join hii_purchase_detail A1 on A.p_id=A1.p_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id";
        $sql .= " where A.p_id=$id";
        $sql .= " order by p_id desc";
        $data = $Model0->query($sql);
        for($i = 0;$i < count($data);$i++) {
            $data1 = array();
            $data1['admin_id'] = UID;
            $data1['goods_id'] = $data[$i]['goods_id'];
            $data1['temp_type'] = 5;
            $data1['status'] = 0;
            $data1['value_id'] = $data[$i]['value_id'];
            $Model = M('RequestTemp');
            $data2 = $Model->where($data1)->select();
            if($data2){
                //已经存在
                $where = $data1;
                $data1['b_n_num'] = $data[$i]['b_n_num'];
                $data1['b_num'] = $data[$i]['b_num'];
                $data1['b_price'] = $data[$i]['b_price'];
                $data1['g_num'] = $data[$i]['g_num'];
                $data1['g_price'] = $data[$i]['g_price'];
                $data1['ctime'] = time();
                $dataout = $Model->where($where)->save($data1);
                if(!$dataout){
                    $this->error($Model->getError());
                }
            }else{
                //新增
                $data1['b_n_num'] = $data[$i]['b_n_num'];
                $data1['b_num'] = $data[$i]['b_num'];
                $data1['b_price'] = $data[$i]['b_price'];
                $data1['g_num'] = $data[$i]['g_num'];
                $data1['g_price'] = $data[$i]['g_price'];
                $data1['value_id'] = $data[$i]['value_id'];
                $data1['ctime'] = time();
                $dataout = $Model->add($data1);
                if(!$dataout){
                    $this->error($Model->getError());
                }
            }
        }
        $this->success('再次申请成功');
    }

    public function pass()
    {
        $p_id = I('id', '');
        if ($p_id == '') {
            $p_id = $_POST['id'];
        }
        if ($p_id == '') {
            $this->error('没有单据ID');
        }
        $pass = I('pass', '');
        if ($pass == '') {
            $pass = $_POST['pass'];
        }
        if ($pass == '') {
            $this->error('没有参数');
        }
        if ($pass != '3') {
            $this->error('错误参数');
        }
        $where = array();
        $where['A.p_id'] = $p_id;
        $field = "A.p_d_id,A.p_id,A.p_s_d_id,A.goods_id,A.b_n_num,A.b_num,A.b_price,A.g_num,A.g_price,A.remark,B.p_sn,B.warehouse_id,B.store_id";
        $DetailList = M('PurchaseDetail')->alias('A')
            ->join("left join hii_purchase B ON A.p_id=B.p_id")
            ->field($field)
            ->where($where)->order('A.p_d_id asc')->select();
        if(!count($DetailList)>0){
            $this->error('没有申请商品');
        }
        $bill_log = array();
        $content = "";
        $bill_log['for_id'] = $DetailList[0]['p_id'];
        $bill_log['ctime'] = time();
        $bill_log['admin_id'] = UID;
        $bill_log['ctype'] = 1;
        echo('<a href="' .addons_url('Purchase://PurchaseError:/index'). '">返回采购单错误处理列表</a><br>');
        $bill_log['note'] = "修改采购单:" .$DetailList[0]['p_sn']."价格";
        foreach($DetailList as $k=>$v){
            echo('<h1>商品' . $DetailList[$k]['goods_id'] . '采购价更新流水开始：</h1><br>');
            $content .= $DetailList[$k]['goods_id'] ."更改采购价格\n";
            if($DetailList[$k]['p_s_d_id'] != 0){
                echo('<h2>采购询价单</h2><br>');
                //采购询价单生成的采购单
                $PSList = M('PurchaseSupplyDetail') ->where('p_s_d_id=' .$DetailList[$k]['p_s_d_id'])->find();
                if($PSList) {
                    if ($PSList['g_price'] != $DetailList[$k]['g_price']) {
                        $PSList['b_price'] = $DetailList[$k]['b_price'];
                        $PSList['g_price'] = $DetailList[$k]['g_price'];
                        //更新采购询价单
                        $PSSave = M('PurchaseSupplyDetail')->where('p_s_d_id=' . $DetailList[$k]['p_s_d_id'])->save($PSList);
                        if ($PSSave === false) {
                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店采购询价单价格更新失败</font><br>');
                        } else {
                            if ($PSSave > 0) {
                                echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的采购询价单价格更新成功</font><br>');
                            } else {
                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的采购询价单价格无改变，未更新成功</font><br>');
                            }
                        }
                    }else {
                        echo('<font color=red>【采购询价单价格没有变动，所以未变动价格，继续对比后续其它单据】</font><br>');
                        //continue;
                    }
                }
            }else{
                echo('<h2>采购询价单</h2><br>');
                echo('<font color=red>这张采购单是临时采购单直接提交的，没有相关采购询价单</font><br>');
            }
            //门店/仓库入库验收
            if($DetailList[$k]['warehouse_id'] != 0 && $DetailList[$k]['warehouse_id'] != ''){
                echo('<h2>仓库入库验收单</h2><br>');
                    //发往仓库的采购单
                    $WIList = M('WarehouseInDetail') ->where('p_d_id=' .$DetailList[$k]['p_d_id'])->find();
                    //print_r($WIList);die;
                    if($WIList) {
                        if ($WIList['g_price'] != $DetailList[$k]['g_price']) {
                            // || $SIList['g_num'] != $DetailList[$k]['g_num']
                            //$SIList['g_num'] = $DetailList[$k]['g_num'];
                            $WIList['g_price'] = $DetailList[$k]['g_price'];
                            //更新仓库入库验收单
                            $WISave = M('WarehouseInDetail')->where('p_d_id=' . $DetailList[$k]['p_d_id'])->save($WIList);
                            if ($WISave === false) {
                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的仓库入库验收单价格更新失败</font><br>');
                            } else {
                                if ($WISave > 0) {
                                    echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的仓库入库验收单价格更新成功</font><br>');
                                } else {
                                    echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的仓库入库验收单价格无改变，未更新成功</font><br>');
                                }
                            }
                        }else {
                            echo('<font color=red>【仓库入库验收单价格没有变动，所以未变动价格，继续对比后续其它单据】</font><br>');
                            //continue;
                        }
                        echo('<h2>仓库入库单</h2><br>');
                        $WISList = M('WarehouseInStockDetail')->where('w_in_d_id=' . $WIList['w_in_d_id'])->find();
                        if($WISList) {
                            if ($WIList['g_price'] != $WISList['g_price']) {
                                // || $SIList['g_num'] != $SISList['g_num']
                                //$SISList['g_num'] = $SIList['g_num'];
                                $WISList['g_price'] = $WIList['g_price'];
                                //更新仓库入库单
                                $WISSave = M('WarehouseInStockDetail')->where('w_in_s_d_id=' . $WISList['w_in_s_d_id'])->save($WISList);
                                if ($WISSave === false) {
                                    echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的仓库入库单价格更新失败</font><br>');
                                } else {
                                    if ($WISSave > 0) {
                                        echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的仓库入库单价格更新成功</font><br>');
                                    } else {
                                        echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的仓库入库单价格无改变，未更新成功</font><br>');
                                    }
                                }
                            } else {
                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库单价格更新失败【价格没有变动，所以未变动价格，继续对比后续单据】</font><br>');
                                //continue;
                            }
                            echo('<h2>入库批次</h2><br>');
                            $StockInOutList = M('WarehouseInout')->where('w_in_s_d_id=' . $WISList['w_in_s_d_id'])->find();
                            if($StockInOutList) {
                                if ($StockInOutList['inprice'] != $WISList['g_price']) {
                                    // || $StockInOutList['innum'] != $SISList['g_num']
                                    //$StockInOutList['innum'] = $SISList['g_num'];
                                    $StockInOutList['inprice'] = $WISList['g_price'];

                                    $WIOSave = M('WarehouseInout')->where('w_in_s_d_id=' . $WISList['w_in_s_d_id'])->save($StockInOutList);
                                    if ($WIOSave === false) {
                                        echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的批次数量价格更新失败</font><br>');
                                    } else {
                                        if ($WIOSave > 0) {
                                            echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的批次价格更新成功</font><br>');
                                        } else {
                                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的批次价格无改变，未更新成功</font><br>');
                                        }
                                    }
                                } else {
                                    echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的入库批次价格更新失败【价格没有变动，所以未变动价格，继续对比后续单据】</font><br>');
                                }
                                echo('<h2>销售订单成本价</h2><br>');
                                $OrderDetailList = M('OrderDetail')->where("FIND_IN_SET('" .$StockInOutList['inout_id']. "',inout_ids)" )->select();
                                if($OrderDetailList) {
                                    foreach($OrderDetailList as $j=>$v1){
                                        $inout_price_all = 0.00;
                                        $inout_num_all = 0;
                                        $inout_ids = explode(',', $OrderDetailList[$j]['inout_ids']);
                                        $inout_nums = explode(',', $OrderDetailList[$j]['inout_nums']);
                                        $inout_prices = explode(',', $OrderDetailList[$j]['inout_prices']);
                                        for($i=0;$i<count($inout_ids);$i++){
                                            if($inout_ids[$i] == $StockInOutList['inout_id']){
                                                $inout_prices[$i] = $StockInOutList['inprice'];
                                            }
                                            $inout_num_all += $inout_nums[$i];
                                            $inout_price_all += $inout_prices[$i]*$inout_nums[$i];
                                        }
                                        $OrderDetailList[$j]['inout_prices'] = implode(',', $inout_prices);
                                        $OrderDetailList[$j]['inout_price_all'] = $inout_price_all;
                                        $OrderDetailList[$j]['inout_price_one'] = round($inout_price_all/$inout_num_all,2);
                                        $OrderDetailSave = M('OrderDetail')->where('id=' . $OrderDetailList[$j]['id'])->save($OrderDetailList[$j]);
                                        if ($OrderDetailSave === false) {
                                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的销售成本价更新失败</font><br>');
                                        } else {
                                            if ($OrderDetailSave > 0) {
                                                echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的销售成本价更新成功</font><br>');
                                            } else {
                                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的销售成本价无改变，未更新成功</font><br>');
                                            }
                                        }
                                    }
                                } else {
                                    echo('<font color=red>未找到销售记录</font><br>');
                                    continue;
                                }
                            } else {
                                echo('<font color=red>未找到批次入库记录</font><br>');
                                continue;
                            }
                        }else {
                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的没有找到对应的仓库入库单入库数据</font><br>');
                            continue;
                        }
                    }else{
                        echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的没有找到对应的仓库入库验收单入库数据</font><br>');
                        continue;
                    }
            }else{
                echo('<h2>门店入库验收单</h2><br>');
                if($DetailList[$k]['store_id'] != 0 && $DetailList[$k]['store_id'] != ''){
                    //发往门店的采购单
                    $SIList = M('StoreInDetail') ->where('p_d_id=' .$DetailList[$k]['p_d_id'])->find();
                    //print_r($SIList);die;
                    if($SIList) {
                        if ($SIList['g_price'] != $DetailList[$k]['g_price']) {
                            // || $SIList['g_num'] != $DetailList[$k]['g_num']
                            //$SIList['g_num'] = $DetailList[$k]['g_num'];
                            $SIList['g_price'] = $DetailList[$k]['g_price'];
                            //更新门店入库验收单
                            $SISave = M('StoreInDetail')->where('p_d_id=' . $DetailList[$k]['p_d_id'])->save($SIList);
                            if ($SISave === false) {
                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库验收单价格更新失败</font><br>');
                            } else {
                                if ($SISave > 0) {
                                    echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库验收单价格更新成功</font><br>');
                                } else {
                                    echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库验收单价格无改变，未更新成功</font><br>');
                                }
                            }
                        }else {
                            echo('<font color=red>【门店入库验收单价格没有变动，所以未变动价格，继续对比后续其它单据】</font><br>');
                            //continue;
                        }
                        echo('<h2>门店入库单</h2><br>');
                        $SISList = M('StoreInStockDetail')->where('s_in_d_id=' . $SIList['s_in_d_id'])->find();
                        if($SISList) {
                            if ($SIList['g_price'] != $SISList['g_price']) {
                                // || $SIList['g_num'] != $SISList['g_num']
                                //$SISList['g_num'] = $SIList['g_num'];
                                $SISList['g_price'] = $SIList['g_price'];
                                //更新门店入库单
                                $SISSave = M('StoreInStockDetail')->where('s_in_s_d_id=' . $SISList['s_in_s_d_id'])->save($SISList);
                                if ($SISSave === false) {
                                    echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库单价格更新失败</font><br>');
                                } else {
                                    if ($SISSave > 0) {
                                        echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库单价格更新成功</font><br>');
                                    } else {
                                        echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库单价格无改变，未更新成功</font><br>');
                                    }
                                }
                            } else {
                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的门店入库单价格更新失败【价格没有变动，所以未变动价格，继续对比后续单据】</font><br>');
                                //continue;
                            }
                            echo('<h2>入库批次</h2><br>');
                            $StockInOutList = M('WarehouseInout')->where('s_in_s_d_id=' . $SISList['s_in_s_d_id'])->find();
                            if($StockInOutList) {
                                if ($StockInOutList['inprice'] != $SISList['g_price']) {
                                    // || $StockInOutList['innum'] != $SISList['g_num']
                                    //$StockInOutList['innum'] = $SISList['g_num'];
                                    $StockInOutList['inprice'] = $SISList['g_price'];

                                    $SIOSave = M('WarehouseInout')->where('s_in_s_d_id=' . $SISList['s_in_s_d_id'])->save($StockInOutList);
                                    if ($SIOSave === false) {
                                        echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的批次数量价格更新失败</font><br>');
                                    } else {
                                        if ($SIOSave > 0) {
                                            echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的批次价格更新成功</font><br>');
                                        } else {
                                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的批次价格无改变，未更新成功</font><br>');
                                        }
                                    }
                                } else {
                                    echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的入库批次价格更新失败【价格没有变动，所以未变动价格，继续对比后续单据】</font><br>');
                                }
                                echo('<h2>销售订单成本价</h2><br>');
                                $OrderDetailList = M('OrderDetail')->where("FIND_IN_SET('" .$StockInOutList['inout_id']. "',inout_ids)" )->select();
                                if($OrderDetailList) {
                                    foreach($OrderDetailList as $j=>$v1){
                                        $inout_price_all = 0.00;
                                        $inout_num_all = 0;
                                        $inout_ids = explode(',', $OrderDetailList[$j]['inout_ids']);
                                        $inout_nums = explode(',', $OrderDetailList[$j]['inout_nums']);
                                        $inout_prices = explode(',', $OrderDetailList[$j]['inout_prices']);
                                        for($i=0;$i<count($inout_ids);$i++){
                                            if($inout_ids[$i] == $StockInOutList['inout_id']){
                                                $inout_prices[$i] = $StockInOutList['inprice'];
                                            }
                                            $inout_num_all += $inout_nums[$i];
                                            $inout_price_all += $inout_prices[$i]*$inout_nums[$i];
                                        }
                                        $OrderDetailList[$j]['inout_prices'] = implode(',', $inout_prices);
                                        $OrderDetailList[$j]['inout_price_all'] = $inout_price_all;
                                        $OrderDetailList[$j]['inout_price_one'] = round($inout_price_all/$inout_num_all,2);
                                        $OrderDetailSave = M('OrderDetail')->where('id=' . $OrderDetailList[$j]['id'])->save($OrderDetailList[$j]);
                                        if ($OrderDetailSave === false) {
                                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的销售成本价更新失败</font><br>');
                                        } else {
                                            if ($OrderDetailSave > 0) {
                                                echo('<font color=blue>商品ID:' . $DetailList[$k]['goods_id'] . '的销售成本价更新成功</font><br>');
                                            } else {
                                                echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的销售成本价无改变，未更新成功</font><br>');
                                            }
                                        }
                                    }
                                } else {
                                    echo('<font color=red>未找到销售记录</font><br>');
                                    continue;
                                }
                            } else {
                                echo('<font color=red>未找到批次入库记录</font><br>');
                                continue;
                            }
                        }else {
                            echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的没有找到对应的门店入库单入库数据</font><br>');
                            continue;
                        }
                    }else{
                        echo('<font color=red>商品ID:' . $DetailList[$k]['goods_id'] . '的没有找到对应的门店入库验收单入库数据</font><br>');
                        continue;
                    }
                }else{
                    $this->error('错误：这张采购单没有接收门店/仓库');
                }

            }
        }
        $bill_log['content'] = $content;
        $Log = M('BillLog')->add($bill_log);
        if ($Log === false) {
            echo('<font color=red>写入日志失败</font><br>');
        } else {
            if ($Log > 0) {
                echo('<font color=blue>OK，写入日志成功</font><br>');
            } else {
                echo('<font color=red>写入日志错误</font><br>');
            }
        }
        echo('<a href="' .addons_url('Purchase://PurchaseError:/index'). '">返回采购单错误处理列表</a><br>');
        exit;

    }

}
