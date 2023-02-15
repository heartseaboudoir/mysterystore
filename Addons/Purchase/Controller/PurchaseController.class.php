<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseController extends AddonsController{

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
        //显示隐藏已审核
        $showhide = I('showhide');
        if ($showhide == "" || $showhide == 0 ) {
            //显示隐藏已处理单据
            $where .= " and A.p_status = 0";
            $this->assign('thisshowhide', 0);
            $this->assign('showhide', 1);
        }else{
            $this->assign('thisshowhide', 1);
            $this->assign('showhide', 0);
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
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ_zzy('Supply','A.supply_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ_zzy('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ_zzy('Store','A.store_id');
        $where0 .= " )";
        $where0 .= $where;
        $sql = "
      select A.p_id,A.p_sn,A.p_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,
        A.supply_id,S.s_name,A.store_id,ST.title as store_name,A.remark,A.g_type,A.g_nums,
        A.w_in_id,A.s_in_id,PS.p_s_sn,A.padmin_id,B1.nickname as pnickname,
        sum(A1.g_num*(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price  else G.sell_price end)) as g_amounts,
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
          left join hii_purchase_supply PS on A.p_id=PS.p_id
          where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'".$where0 .$wheregoodsname ."
        group by A.p_id,A.p_sn,A.p_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.store_id,ST.title,
        A.remark,A.g_type,A.g_nums,A.w_in_id,PS.p_s_id,A.padmin_id,B1.nickname
        order by A.p_id desc
        ";
        //print_r($sql);die;
        $data = $Model->query($sql);
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
        //新增获取社区方法  zzy
        $shequ = $this->__member_store_shequ();
       // var_dump($_SESSION['can_shequs_cg']);die;
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
        $this->display(T('Addons://Purchase@Purchase/index'));
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

        $Model = M('PurchaseRequest');

        $sql = "
          select A.p_id,A.p_sn,A.p_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,
            A.supply_id,S.s_name,A.store_id,ST.title as store_name,A.remark,A.g_type,A.g_nums,A.p_s_id,
            A.w_in_id,WI.w_in_sn,A.s_in_id,SI.s_in_sn,PS.p_s_sn,A.padmin_id,B1.nickname as pnickname,
            sum(A1.g_num*(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price  else G.sell_price end)) as g_amounts,sum(A1.b_num*A1.b_price) as p_amounts,sum(A1.g_num*A1.g_price) as p_amounts1,
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

        $sql = "select A.p_id,A.p_sn,C.title as cate_name,(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price  else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,";
        $sql .= "A1.p_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.b_n_num,A1.b_num,A1.b_price,A1.g_num,A1.g_price,A1.remark,";
        $sql .= "WID.in_num as win_num,WID.out_num as wout_num,";
        $sql .= "SID.in_num as sin_num,SID.out_num as sout_num,";
        $sql .= "ifnull(L.supply_price,0) as last_price,AV.value_id,AV.value_name";
        $sql .= " from  hii_purchase A";
        $sql .= " left join hii_purchase_detail A1 on A.p_id=A1.p_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join hii_warehouse W on A.warehouse_id=W.w_id";
        $sql .= " left join hii_store S on S.id=A.store_id";
        $sql .= " left join hii_goods_store GS on A.store_id=GS.store_id and A1.goods_id=GS.goods_id";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id";
        $sql .= " left join hii_goods_supply L on A1.goods_id=L.goods_id and L.supply_id= A.supply_id and (L.shequ_id = S.shequ_id or L.shequ_id = W.shequ_id)";
        $sql .= " left join hii_warehouse_in_detail WID on A1.p_d_id=WID.p_d_id";
        $sql .= " left join hii_store_in_detail SID on A1.p_d_id=SID.p_d_id";
        $sql .= " left join hii_attr_value AV on AV.value_id=A1.value_id";
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
        $this->display(T('Addons://Purchase@Purchase/view'));
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
                        if(($b_n_num[$j] * $b_num[$j]) != $g_num[$j]){
                            $this->error('箱规 x 箱数 != 总数量 !!!');die;
                        }
                        if(intval($b_price[$j] / $b_n_num[$j]) != intval($g_price[$j])){
                            $this->error('箱规 x 单价 != 每箱价格 !!!');die;
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
        $sql .= " left join hii_warehouse_stock WS on A1.goods_id=WS.goods_id and A.warehouse_id=WS.w_id";
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

    public function save() {
        $id = I('get.id','');
        $Model = M('RequestTemp');
        if($id != ''){
            $this->meta_title = '编辑临时申请商品';
            $where = array();
            $where['id'] = $id;
            $data = $Model->where($where)->find();
            $this->assign('data', $data);
        }else{
            $this->meta_title =  '添加临时申请商品';
        }
        $this->display(T('Addons://Purchase@Purchase/save'));
    }


    public function temp() {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $shequ = $_SESSION['can_shequs_cg'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $where = array();
        $where['admin_id'] = UID;
        $where['temp_type'] = 5;
        $where['hii_request_temp.status'] = 0;
        if($this->_warehouse_id == ''){
            $w_id = 0;
        }else{
            $w_id = $this->_warehouse_id;
        }
        $field = "hii_request_temp.id,hii_request_temp.goods_id,FROM_UNIXTIME(hii_request_temp.ctime,'%Y-%m-%d %H:%i:%s') as ctime";
        $field .= ",hii_goods.title as goods_name,ifnull(AV.bar_code,hii_goods.bar_code)bar_code,hii_goods_cate.title as cate_name,hii_goods.sell_price,
        (case when ifnull(hii_shequ_price.shequ_price,0)>0 then concat(hii_shequ_price.shequ_name,':',hii_shequ_price.shequ_price) else hii_goods.sell_price end)
        as sell_price";
        $field .= ",hii_request_temp.b_n_num,hii_request_temp.b_num,hii_request_temp.b_price";
        $field .= ",ifnull(hii_warehouse_stock.num,0) as stock_num,hii_request_temp.g_num,hii_request_temp.g_price,hii_request_temp.remark,concat(L.supply_name,':',L.last_price) as last_price,AV.value_id,AV.value_name";
        $list = M('RequestTemp')
            ->join('left join hii_goods on hii_request_temp.goods_id=hii_goods.id')
            ->join('left join hii_goods_cate on hii_goods.cate_id=hii_goods_cate.id')
            ->join('left join (select * from hii_warehouse_stock where w_id = ' .$w_id. ') hii_warehouse_stock on hii_warehouse_stock.value_id=hii_request_temp.value_id')
            ->join('left join (select distinct rt.goods_id,group_concat(gsu.supply_price order by gsu.time desc)last_price,group_concat(supp.s_name order by gsu.time desc) supply_name from (select DISTINCT goods_id from hii_request_temp ) rt left join hii_goods_supply gsu on gsu.goods_id=rt.goods_id left join hii_supply supp on supp.s_id=gsu.supply_id group by rt.goods_id) L on hii_request_temp.goods_id=L.goods_id')
            ->join('left join hii_attr_value AV ON AV.value_id= hii_request_temp.value_id')
            ->join('left join (select goods_id,GROUP_CONCAT(shequ_id) as shequ_id,GROUP_CONCAT(B.title) as shequ_name,GROUP_CONCAT(shequ_price) as shequ_price
            from hii_shequ_price A left join hii_shequ B on A.shequ_id=B.id where ifnull(shequ_price,0)!=0
            and ' .$whereSQ .' group by goods_id) hii_shequ_price on hii_shequ_price.goods_id=hii_goods.id')
            ->field($field)
            ->where($where)->group('goods_id,value_id')->order('ctime asc')->select();
        //print_r(M('RequestTemp')->_sql());die;
        //分页
        $pcount=15;
        $count=count($list);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($list,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        $this->assign('list', $list);

        //$shequ = $_SESSION['can_shequs'];
        //$whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->where($whereSQ)->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->where($whereSQ)->select();
        $ModelStore = M('Store');
        $StoreData = $ModelStore->where($whereSQ)->select();

        $this->assign('warehouse', $WarehouseData);
        $this->assign('supply', $SupplyData);
        $this->assign('store', $StoreData);


        //查询条件：社区门店
        /*$store_id = "";
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
        $store = M('Store')->where($where)->field('id, shequ_id , title, sell_type')->select();
        !$store && $store = array();
        $_store = array();
        foreach($store as $v){
            $_store[$v['shequ_id']][] = $v;
        }
        $shequid = array_column($shequ,'id');
        $this->assign('shequ', $shequ);
        $this->assign('store', $store);
        $this->assign('store_count', count($store));
        $this->assign('this_group', implode(',',$shequid));
        $_storeary = array_column($store,'id');*/

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = '临时申请列表';
        $this->display(T('Addons://Purchase@Purchase/temp'));
    }

    public function send_request_temp() {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
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
        if ($supply_id == '' || $supply_id == '0') {
            $supply_id = $_POST['supply_id'];
        }
        if ($supply_id == '' || $supply_id == '0') {
            $this->error('没有选供应商');
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $where = array();
        $where['admin_id'] = UID;
        $where['temp_type'] = 5;
        $where['hii_request_temp.status'] = 0;
        $field = "id,goods_id,b_n_num,b_num,b_price,g_num,g_price,remark,value_id";
        $pcount = 0;
        $nums = 0;
        $DetailList = M('RequestTemp')
            ->field($field)
            ->where($where)->order('ctime asc')->select();
        if(!count($DetailList)>0){
            $this->error('没有临时采购商品');
        }
        foreach($DetailList as $k=>$v){
            $DetailList[$k]['p_d_id'] = 0;
            $pcount++;
            $nums += $DetailList[$k]['g_num'];

        }
        $p_id = 0;
        $new_no = get_new_order_no('CG','hii_purchase','p_sn');
        $data = array();
        $data['p_id'] = $p_id;
        $data['p_sn'] = $new_no;
        $data['p_status'] = 0;
        $data['p_id'] = 0;
        $data['ctime'] = time();
        $data['admin_id'] = UID;
        $data['etime'] = time();
        $data['eadmin_id'] = UID;
        $data['ptime'] = 0;
        $data['padmin_id'] = 0;
        $data['store_id'] = $store_id;
        $data['warehouse_id'] = $warehouse_id;
        $data['supply_id'] = $supply_id;
        $data['w_in_id'] = 0;
        $data['remark'] = $remark;
        $data['g_type'] = $pcount;
        $data['g_nums'] = $nums;
        $Model = D('Addons://Purchase/Purchase');
        $res = $Model->savePurchase($p_id,$data,$DetailList,false);
        if($res>0){
            $Model1 = D('Addons://Warehouse/RequestTemp');
            $DeleteList1 =$Model1->where($where)->delete();
            $this->success('提交成功');
        }else{
            $this->error($Model->err['msg']);
        }
    }

    public function get_one(){
        $bar_code = I('bar_code', '', 'trim');
        if(!$bar_code){
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
        if(!$data){
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('Goods')->where(array('id' => $data['goods_id'], 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if(!$data){
            $this->ajaxReturn(array('status' => 0));
        }else{
            $where['admin_id'] = UID;
            $where['temp_type'] = 5;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,b_n_num,b_num,b_price,g_num,g_price,remark')->find();
            if(!$data1){
                $this->ajaxReturn(array('status' => 1, 'data' => $data));
            }else{
                $this->ajaxReturn(array('status' => 2, 'data' => $data, 'data1' => $data1));
            }
        }
    }

    public function get_one_id(){
        $id_code = I('id_code', 0, 'intval');
        if(empty($id_code)){
            $this->ajaxReturn(array('status' => 0));
        }

        $data = M('Goods')->where(array('id' => $id_code, 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if(!$data){
            $this->ajaxReturn(array('status' => 0));
        }else{
            $where['admin_id'] = UID;
            $where['temp_type'] = 5;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,b_n_num,b_num,b_price,g_num,g_price,remark')->find();
            if(!$data1){
                $this->ajaxReturn(array('status' => 1, 'data' => $data));
            }else{
                $this->ajaxReturn(array('status' => 2, 'data' => $data, 'data1' => $data1));
            }
        }
    }

    public function get_goods_lists(){
        $keyword = I('keyword', '', 'trim');
        $Model = M('Goods')->alias('a');
        $where = array();
        $keyword && $where['a.title'] = array('like', '%'.$keyword.'%');
        $where['a.status'] = 1;
        $field = 'a.id,a.title,a.cover_id';
        //$join = "left join hii_goods_cate b on a.cate_id=b.id";
        $_REQUEST['r'] = 20;
        $p = I('p', '', 'trim');
        if($p == '') {
            $p = $_POST['p'];
        }
        if($p == '') {
            $p = 1;
        }
        $list = $Model->where($where)->field($field)->order('listorder desc, create_time desc')->limit(($p-1)*20 .',20')->select();
        $sql = $Model->_sql();
        $attrValueModel = M("AttrValue");
        foreach($list as $k => $v){
            $attr_value = $attrValueModel->field('value_id,value_name')->where(array('goods_id'=>$v['id']))->select();
            if(empty($attr_value)){
                $attr_value = array();
            }
            $v['attr_value'] = json_encode($attr_value);
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
            $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
            $list[$k] = $v;
        }
        if(IS_AJAX){
            if(is_array($list) && count($list) > 0){
                $this->ajaxReturn(array('status' => 1, 'data' => $list, 'msgword' => 'OK'));
            }else{
                $this->ajaxReturn(array('status' => 2, 'data' => $list, 'msgword' => '没有更多了...'));
            }
            exit;
        }
        $this->assign('list', $list);
        $this->display(T('Addons://Purchase@Purchase/get_goods_lists'));
    }

    public function update()
    {
        $g_id = I('goods_id', '');
        if ($g_id == '') {
            $g_id = $_POST['goods_id'];
        }
      
        if($g_id == '0' || $g_id == ''){
            $this->error('不存在的商品ID');die;
        }
        $value_id = I('value_id', 0);
        if($value_id == 0){
        	$this->error('请选择属性');die;
        }
        $temp_id = I('get.temp_id','');
        $b_n_num = I('b_n_num', '');
        if ($b_n_num == '') {
            $b_n_num = $_POST['b_n_num'];
        }
        if($b_n_num == '' || $b_n_num == '0'){
            $this->error('必须输入箱规');die;
        }
        $b_num = I('b_num', '');
        if ($b_num == '') {
            $b_num = $_POST['b_num'];
        }
        if($b_num == '' || $b_num == '0'){
            $this->error('必须输入箱数');die;
        }
        $g_num = I('g_num', '');
        if ($g_num == '') {
            $g_num = $_POST['g_num'];
        }
        if($g_num == '' || $g_num == '0'){
            $this->error('必须输入申请数量');die;
        }
        $b_price = I('b_price', '');
        if ($b_price == '') {
            $b_price = $_POST['b_price'];
        }
        if($b_price == '' || $b_price == '0'){
            $this->error('必须输入每箱价格');die;
        }

        $g_price = I('g_price', '');
        if ($g_price == '') {
            $g_price = $_POST['g_price'];
        }
        if($g_price == '' || $g_price == '0'){
            $this->error('必须输入采购价格');die;
        }
    if(($b_n_num * $b_num) != $g_num){
        $this->error('箱规 x 箱数 != 总数量 !!!');die;
    }
        if(intval($b_price / $b_n_num) != intval($g_price)){
            $this->error('箱规 x 单价 != 每箱价格 !!!');die;
        }

        $remark = I('remark_add', '');
        if ($remark == '') {
            $remark = $_POST['remark_add'];
        }
        /*if($b_n_num*$b_num*$b_price != $g_num*$g_price){
            $this->error('价格不等');
        }*/
        $modelGoods = M('Goods');
        $dataGoods = $modelGoods->where('id=' .$g_id)->select();
        if(floatval($g_price) > floatval($dataGoods[0]['sell_price'])){
            $this->error('采购单价不能高于系统售价');die;
        }
        $Model = M('RequestTemp');

        //如果$temp_id临时申请表id不未空 按id删除后重新生成
        if(!empty($temp_id)){
            //更新
            $saveData["remark"] = $remark;
            $saveData["g_num"] = $g_num;
            $saveData['b_num'] = $b_num;
            $saveData['b_n_num'] = $b_n_num;
            $saveData['b_price'] = $b_price;
            $saveData["g_price"] = $g_price;
            $saveData["value_id"] = $value_id;
            $saveData["goods_id"] = $g_id;
            $result = $Model->where(" id={$temp_id} ")->save($saveData);
            if ($result === false) {
                $this->error($Model->getError());
            } else {
                //判断是否有重复商品属性如果有删除一个
                $Model->where(array('id'=>array('NEQ',$temp_id),'admin_id'=>UID,'status'=>0,'goods_id'=>$g_id,'temp_type'=>5,'value_id'=>$value_id))->delete();

                $this->success($result['id'] ? '更新成功' : '修改成功');
            }

        }else {
            if ($g_id != '') {
                $where['admin_id'] = UID;
                $where['goods_id'] = $g_id;
                $where['temp_type'] = 5;
                $where['hii_request_temp.status'] = 0;
                $where['hii_request_temp.value_id'] = $value_id;

                $data = $Model->where($where)->select();
                if ($data) {
                    //已经存在,b_n_num,b_num,b_price
                    $data1['b_n_num'] = $b_n_num;
                    $data1['b_num'] = $b_num;
                    $data1['b_price'] = $b_price;
                    $data1['g_num'] = $g_num;
                    $data1['g_price'] = $g_price;
                    $data1['ctime'] = time();
                    $data1['remark'] = $remark;
                    $dataout = $Model->where($where)->save($data1);
                    if (!$dataout) {
                        $this->error($Model->getError());
                    } else {
                        $this->success($dataout['id'] ? '更新成功' : '修改成功');
                    }
                } else {
                    //新增
                    $id = 0;
                    $data1['admin_id'] = UID;
                    $data1['goods_id'] = $g_id;
                    $data1['temp_type'] = 5;
                    $data1['status'] = 0;
                    $data1['b_n_num'] = $b_n_num;
                    $data1['b_num'] = $b_num;
                    $data1['b_price'] = $b_price;
                    $data1['g_num'] = $g_num;
                    $data1['g_price'] = $g_price;
                    $data1['ctime'] = time();
                    $data1['remark'] = $remark;
                    $data1['value_id'] = $value_id;
                    $Model = D('Addons://Warehouse/RequestTemp');
                    $res = $Model->saveRequestTemp($id, $data1,false);
                    //$res = $Model->add($data1);
                    if (!$res) {
                        $this->error($Model->getError());
                    } else {
                        $this->success($res['id'] ? '更新成功' : '新增成功');
                    }
                }
            }
        }
    }

    public function delete()
    {
        $id = I('get.id', '');
        if ($id) {
            $Model = M('RequestTemp');
            $res = $Model->where("id = $id")->delete();
            if (!$res) {
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            } else {
                $this->success('删除成功');
            }
        } else {
            $this->error('请选择删除的数据！');
        }
    }

    public function cleantemp()
    {
        $where['admin_id'] = UID;
        $where['temp_type'] = 5;
        $where['hii_request_temp.status'] = 0;
        $Model = M('RequestTemp');
        $data = $Model->where($where)->delete();
        $this->success('清空成功');
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
        if ($p_id != '') {
            $where = array();
            $where['p_id'] = $p_id;
            $field = "p_d_id,p_id,p_s_d_id,goods_id,b_n_num,b_num,b_price,g_num,g_price,remark,G.expired_days,value_id";
            $DetailList = M('PurchaseDetail')
                ->join("left join hii_goods G ON hii_purchase_detail.goods_id=G.id")
                ->field($field)
                ->where($where)->order('p_d_id asc')->select();
            if(!count($DetailList)>0){
                $this->error('没有申请商品');
            }
            $pcount = 0;
            $nums = 0;
            $tmp = array();
            $DetailListPurchase = array();
            foreach($DetailList as $k=>$v){
                if($DetailList[$k]['g_price'] == 0 && $pass == '1'){
                    $this->error('没有商品采购价');
                }
                if($DetailList[$k]['g_num'] == 0 && $pass == '1'){
                    $this->error('没有商品采购数量');
                }
                $tmp['goods_id'] = $DetailList[$k]['goods_id'];
                $tmp['g_num'] = $DetailList[$k]['g_num'];
                $tmp['g_price'] = $DetailList[$k]['g_price'];
                if($DetailList[$k]['expired_days'] != '' && $DetailList[$k]['expired_days'] != 0) {
                    if( (int)$DetailList[$k]['expired_days'] > 30){
                        $end_date = date('Y-m-d', strtotime("+" .(string)((int)$DetailList[$k]['expired_days']-30). " day"));
                        $end_date = strtotime($end_date);
                    }else{
                        $end_date = date('Y-m-d', strtotime("+" .$DetailList[$k]['expired_days']. " day"));
                        $end_date = strtotime($end_date);
                    }
                }else {
                    $end_date = date('Y-m-d', strtotime("+30 day"));
                    $end_date = strtotime($end_date);
                }
                $tmp['endtime'] = $end_date;
                $tmp['remark'] = $DetailList[$k]['remark'];
                $tmp['p_d_id'] = $DetailList[$k]['p_d_id'];
                $tmp['value_id'] = $DetailList[$k]['value_id'];
                $DetailListPurchase[] = $tmp;
                $pcount++;
                $nums += $DetailList[$k]['g_num'];
                if ($pass == '2') {
                    //作废
                    if($DetailList[$k]['p_s_d_id'] != '0'){
                        //对应的询价商品
                        $ModelSupplyDetail = M('PurchaseSupplyDetail');
                        $condition['p_s_d_id'] = $DetailList[$k]['p_s_d_id'];

                        $DetailListSupply = $ModelSupplyDetail
                            ->where($condition)->order('p_s_d_id asc')->find();
                        if(is_array($DetailListSupply) && count($DetailListSupply)>0){
                            //对应的询价商品->对应拒绝采购申请
                            $ModelRequestDetail = M('PurchaseRequestDetail');
                            $data['is_pass'] = 1;
                            $condition1['p_r_d_id'] = $DetailListSupply['p_r_d_id'];
                            $res = $ModelRequestDetail->where($condition1)->save($data);
                            if (!$res) {
                                $error = $ModelRequestDetail->getError();
                                $this->error($error ? $error : '找不到要拒绝的数据！' .$res);
                            }
                        }
                    }
                }
            }
            $where['p_id'] = $p_id;
            $where['p_status'] = 0;
            $Model = M('Purchase');
            $dataPS = $Model->where($where)->find();
            if(!$dataPS){
                $this->error('单据不存在或者已经审核。');
            }
            $dataPS['ptime'] = time();
            $dataPS['padmin_id'] = UID;
            $dataPS['p_status'] = $pass;

            if ($pass == '1') {
                $supply_id = 0;
                $shequ_id = 0;
                $goods_id = 0;
                if($dataPS['warehouse_id'] != 0){
                    //审核并新增入库单
                    $w_in_id = 0;
                    $new_no = get_new_order_no('RY','hii_warehouse_in','w_in_sn');
                    $data = array();
                    $data['w_in_sn'] = $new_no;
                    $data['w_in_status'] = 0;
                    $data['w_in_type'] = 0;
                    $data['p_id'] = $p_id;
                    $data['p_out_id'] = 0;
                    $data['ctime'] = time();
                    $data['admin_id'] = UID;
                    $data['etime'] = 0;
                    $data['eadmin_id'] = 0;
                    $data['ptime'] = 0;
                    $data['padmin_id'] = 0;
                    $data['supply_id'] = $dataPS['supply_id'];
                    $data['warehouse_id'] = $dataPS['warehouse_id'];
                    $data['remark'] = $dataPS['remark'];
                    $data['g_type'] = $pcount;
                    $data['g_nums'] = $nums;

                    $Model1 = D('Addons://Warehouse/WarehouseIn');
                    $res1 = $Model1->saveWarehouseIn($w_in_id,$data,$DetailListPurchase,false);
                    if($res1>0){
                        $dataPS['w_in_id'] = $res1;
                        
                      
                        $Model = D('Addons://Purchase/Purchase');
                        $res = $Model->savePurchase($p_id,$dataPS,$DetailList,false);
                        if($res>0){
                            $ModelMsg = D('Erp/MessageWarn');
                            $msg = $ModelMsg->pushMessageWarn(UID  ,$dataPS['warehouse_id'] , $dataPS['store_id'] ,0  , $data ,6);
                            
                            //如果审核成功  判断hii_goods_supply表是否有同一供应商 同一区域 同一商品的 采购价 如果存在 就更新采购价 如果不存在 就新增一条
                            //获取供应商id  商品id  社区id
                            $supply_id = $dataPS['supply_id'];
                            $shequ_id = M('Warehouse')->where(array('w_id'=>$dataPS['warehouse_id']))->getField('shequ_id');
                            $goodsSupplyModel = M('GoodsSupply');
                            foreach($DetailList as $key=>$val){
                                $goods_id = $val['goods_id'];
                                $is_info = $goodsSupplyModel->where(array('goods_id'=>$goods_id,'shequ_id'=>$shequ_id,'supply_id'=>$supply_id))->find();
                                
                                if(empty($is_info)){
                                    $goodsSupplyModel->add(array('goods_id'=>$goods_id,'shequ_id'=>$shequ_id,'supply_id'=>$supply_id,'supply_price'=>$val['g_price'],'time'=>time()));
                                }else{
                                    $goodsSupplyModel->where(array('id'=>$is_info['id']))->save(array('supply_price'=>$val['g_price'],'time'=>time()));
                                }
                            }
                            
                            $this->success('审核成功');
                        }else{
                            $this->error($Model->err['msg']);
                        }
                    }else{
                        $this->error($Model1->err['msg']);
                    }
                  
                    
                }
                if($dataPS['store_id'] != 0){
                    //审核并新增门店入库验收单
                    $s_in_id = 0;
                    $new_no = get_new_order_no('SI','hii_store_in','s_in_sn');
                    $data = array();
                    $data['s_in_sn'] = $new_no;
                    $data['s_in_status'] = 0;
                    $data['s_in_type'] = 4;
                    $data['p_id'] = $p_id;
                    $data['ctime'] = time();
                    $data['admin_id'] = UID;
                    $data['etime'] = 0;
                    $data['eadmin_id'] = 0;
                    $data['ptime'] = 0;
                    $data['padmin_id'] = 0;
                    $data['supply_id'] = $dataPS['supply_id'];
                    $data['store_id2'] = $dataPS['store_id'];
                    $data['remark'] = $dataPS['remark'];
                    $data['g_type'] = $pcount;
                    $data['g_nums'] = $nums;

                    $Model1 = D('Addons://Store/StoreIn');
                    $res1 = $Model1->saveStoreIn($s_in_id,$data,$DetailListPurchase,false);
                    if($res1>0){
                        $dataPS['s_in_id'] = $res1;
                        $Model = D('Addons://Purchase/Purchase');
                        $res = $Model->savePurchase($p_id,$dataPS,$DetailList,false);
                        if($res>0){
                            $ModelMsg = D('Erp/MessageWarn');
                            $msg = $ModelMsg->pushMessageWarn(UID  ,$dataPS['warehouse_id'] , $dataPS['store_id'] ,0  , $data ,4);
                            //如果审核成功  判断hii_goods_supply表是否有同一供应商 同一区域 同一商品的 采购价 如果存在 就更新采购价 如果不存在 就新增一条
                            //获取供应商id  商品id  社区id
                            $supply_id = $dataPS['supply_id'];
                            $shequ_id = M('Store')->where(array('id'=>$dataPS['store_id']))->getField('shequ_id');
                            $goodsSupplyModel = M('GoodsSupply');
                            foreach($DetailList as $key=>$val){
                                $goods_id = $val['goods_id'];
                                $is_info = $goodsSupplyModel->where(array('goods_id'=>$goods_id,'shequ_id'=>$shequ_id,'supply_id'=>$supply_id))->find();
                                if(empty($is_info)){
                                    $goodsSupplyModel->add(array('goods_id'=>$goods_id,'shequ_id'=>$shequ_id,'supply_id'=>$supply_id,'supply_price'=>$val['g_price'],'time'=>time()));
                                }else{
                                    $goodsSupplyModel->where(array('id'=>$is_info['id']))->save(array('supply_price'=>$val['g_price'],'time'=>time()));
                                }
                            }
                            $this->success('审核成功');
                        }else{
                            $this->error($Model->err['msg']);
                        }
                    }else{
                        $this->error($Model1->err['msg']);
                    }
                }
           
            }else{
                if ($pass == '2') {
                    $Model = D('Addons://Purchase/Purchase');
                    $res = $Model->savePurchase($p_id,$dataPS,$DetailList,false);
                    if($res>0){
                        $this->success('作废成功');
                    }else{
                        $this->error($Model->err['msg']);
                    }
                }else{
                    $this->error('错误参数');
                }
            }
        }
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
            $group_id .= '9,';
        }
        if(in_array(13, $this->group_id)){
            $group_id .= '13,';
        }
        if(in_array(15, $this->group_id)){
            $group_id .= '15,';
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
                if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                    $shequs[] = $val['shequ_id'];
                }
                
            }
        }
        
        
        // 门店(含采购)
        $sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where  type = 1 and group_id in({$group_id}) and uid = {$uid} group by s.shequ_id;";
        
        $data_shequ2_store = M()->query($sql_shequ2_store);
        
        if (!empty($data_shequ2_store)) {
            foreach ($data_shequ2_store as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }
        
        
        // 仓库社区
        $sql_shequ_warehouse = "select * from hii_member_warehouse where type = 2 and group_id in({$group_id}) and uid = {$uid}";
        
        $data_shequ_warehouse = M()->query($sql_shequ_warehouse);
        
        if (!empty($data_shequ_warehouse)) {
            foreach ($data_shequ_warehouse as $key => $val) {
                if (!in_array($val['warehouse_id'], $shequs) && $val['shequ_id'] != null) {
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
                if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }

        return $shequs;
    }

}
