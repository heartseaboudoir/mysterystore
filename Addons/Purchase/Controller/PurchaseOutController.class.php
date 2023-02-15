<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseOutController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购退货单列表';
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

        $title = $s_date. '>>>' .$e_date. '采购退货单';

        $Model = M('PurchaseOut');

        $where0 = " And (";
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ('Supply','A.supply_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Store','A.store_id');
        $where0 .= " )";
        $sql = "select A.p_o_id,A.p_o_sn,A.p_o_status,A.p_id,P.p_sn,WI.w_in_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "A.admin_id,B.nickname,A.padmin_id,B1.nickname as pnickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.store_id,C2.title as store_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "sum(A1.g_num*(case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as g_s_amounts";
        $sql .= " from  hii_purchase_out A";
        $sql .= " left join hii_purchase_out_detail A1 on A.p_o_id=A1.p_o_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_member B1 on A.padmin_id=B1.uid";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id";
        $sql .= " left join hii_supply C1 on A.supply_id=C1.s_id";
        $sql .= " left join hii_store C2 on A.store_id=C2.id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
            left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_purchase P on A.p_id=P.p_id";
        $sql .= " left join hii_warehouse_in WI on A.w_in_id=WI.w_in_id";
        $sql .= " where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where .$where0 .$wheregoodsname. "";
        $sql .= " group by A.p_o_id,A.p_o_sn,A.p_o_status,A.ctime,";
        $sql .= "A.admin_id,B.nickname,A.padmin_id,B1.nickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.remark,A.g_type,A.g_nums";
        $sql .= " order by p_o_id desc";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            /*$sql = "
            select A.id as goods_id,A.title as goods_name,ifnull(B.num,0) as store_num
            ,floor(ifnull(B1.num,0)) as warehouse_num,ifnull(C.num,0) as inout_num,ifnull(C.ginprice,0) as ginprice
            from hii_goods A
            left join (
                select goods_id,sum(num) as num from hii_goods_store
                where num > 0 and store_id in (select id as store_id from hii_store where shequ_id = 4)
                group by goods_id
            ) B on A.id = B.goods_id
            left join (select * from hii_warehouse_stock where w_id = 1) B1 on A.id = B1.goods_id
            left join (
                select goods_id,sum(num) as num,sum(num*inprice)/sum(num) as ginprice from hii_warehouse_inout where shequ_id = 4
                group by goods_id
            ) C on A.id=C.goods_id
            where B.num > 0 or B1.num > 0 or C.num > 0
            ";
            $data = $Model->query($sql);*/
            $printmodel = new \Addons\Report\Model\ReportModel();
            //$printfile = $printmodel->pushDuibiList($data,$title,$fname);
            $printfile = $printmodel->pushPurchaseOutList($data,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        /*$ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->select();
        $this->assign('warehouse', $WarehouseData);
        $this->assign('supply', $SupplyData);*/

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

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Purchase@PurchaseOut/index'));
    }

    public function view()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购退货单查看';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }

        if($id == ''){
            $this->error('不存在的单据ID');
        }
        $this->assign('id', $id);

        $Model = M('PurchaseOut');

        $sql = "
            select A.p_o_id,A.p_o_sn,A.p_o_status,P.p_id,P.p_sn,WI.w_in_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,
            A.admin_id,B.nickname,A.padmin_id,B1.nickname as pnickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.store_id,C2.title as store_name,A.remark,A.g_type,A.g_nums,
            sum(A1.g_num*(case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as g_s_amounts
            from  hii_purchase_out A
              left join hii_purchase_out_detail A1 on A.p_o_id=A1.p_o_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.padmin_id=B1.uid
              left join hii_warehouse C on A.warehouse_id=C.w_id
              left join hii_supply C1 on A.supply_id=C1.s_id
              left join hii_store C2 on A.store_id=C2.id
              left join hii_goods G on A1.goods_id=G.id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
            left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_purchase P on A.p_id=P.p_id
              left join hii_warehouse_in WI on A.w_in_id=WI.w_in_id
              where A.p_o_id=$id
              group by A.p_o_id,A.p_o_sn,A.p_o_status,A.ctime,
            A.admin_id,B.nickname,A.padmin_id,B1.nickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.remark,A.g_type,A.g_nums
            order by p_o_id desc
        ";
        $list = $Model->query($sql);
        if(is_array($list) && count($list) > 0){

        }else{
            $this->error('不存在的单据ID');
        }
        $pid = $list[0]['p_id'];
        $sql = "
            select A1.p_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,GC.title as cate_name,B.w_in_sn,B.warehouse_id,W.w_name,S.s_name,M.nickname,
            (case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,L.supply_price as last_price,A1.g_price as p_price,floor(ifnull(WS.num,0)) as stock_num,A1.g_num as p_num,
            B1.in_num,B1.out_num,BB1.in_num as in_num1,BB1.out_num as out_num1,
            C1.remark as remark1,CC1.remark as remark2,
            C.p_o_id,C.p_o_sn,C1.p_o_d_id,C.store_id,C2.title as store_name,AV.value_id,AV.value_name
            from hii_purchase A
             left join hii_purchase_detail A1 on A.p_id=A1.p_id
             left join hii_warehouse_in B on A.p_id=B.p_id
             left join hii_warehouse_in_detail B1 on B.w_in_id=B1.w_in_id and A1.p_d_id=B1.p_d_id
             left join hii_store_in BB on A.p_id=BB.p_id
             left join hii_store_in_detail BB1 on BB.s_in_id=BB1.s_in_id and A1.p_d_id=BB1.p_d_id
             left join hii_purchase_out C on B.w_in_id=C.w_in_id
             left join hii_purchase_out_detail C1 on C.p_o_id=C1.p_o_id and B1.w_in_d_id=C1.w_in_d_id
             left join hii_purchase_out CC on BB.s_in_id=CC.s_in_id
             left join hii_purchase_out_detail CC1 on CC.p_o_id=CC1.p_o_id and BB1.s_in_d_id=CC1.s_in_d_id
              left join hii_store C2 on CC.store_id=C2.id
             left join hii_goods G on A1.goods_id=G.id
             left join hii_goods_cate GC on G.cate_id=GC.id
             left join hii_warehouse W on B.warehouse_id=W.w_id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
            left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
             left join hii_supply S on B.supply_id=S.s_id
             left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id
             left join hii_goods_supply L on A1.goods_id=L.goods_id and A.supply_id = L.supply_id and (L.shequ_id=C2.shequ_id or L.shequ_id = W1.shequ_id)
             left join hii_member M on A.admin_id=M.uid
             left join hii_attr_value AV ON AV.value_id=A1.value_id
           where A.p_id=$pid
           order by A1.goods_id desc
       ";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $title = '采购退货单' .$list[0]['p_o_sn'] .'查看';

        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushPurchaseOutView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        $ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->select();
        $this->assign('warehouse', $WarehouseData);
        $this->assign('supply', $SupplyData);
        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Purchase@PurchaseOut/view'));
    }

    public function pass()
    {
        $p_o_id = I('id', '');
        if ($p_o_id == '') {
            $p_o_id = $_POST['id'];
        }
        if ($p_o_id == '') {
            $this->error('没有单据ID');
        }
        $pass = I('pass', '');
        if ($pass == '') {
            $pass = $_POST['pass'];
        }
        if ($pass == '') {
            $this->error('没有参数');
        }
        if ($p_o_id != '') {
            $where = array();
            $where['p_o_id'] = $p_o_id;
            $field = "p_o_d_id,p_o_id,w_in_d_id,goods_id,g_num,g_price,value_id";
            $DetailList = M('PurchaseOutDetail')
                ->field($field)
                ->where($where)->order('p_o_d_id asc')->select();
            $where['p_o_status'] = 0;
            $Model = M('PurchaseOut');
            $dataPS = $Model->where($where)->find();
            if(!$dataPS){
                $this->error('单据不存在或者已经审核。');
            }
            $dataPS['ptime'] = time();
            $dataPS['padmin_id'] = UID;
            $dataPS['p_o_status'] = $pass;

            if ($pass == '1') {
                //审核采购退货单
                $Model = D('Addons://Purchase/PurchaseOut');
                $res = $Model->savePurchaseOut($p_o_id,$dataPS,$DetailList,false);
                if($res>0){
                    $this->success('审核成功');
                }else{
                    $this->error($Model->err['msg']);
                }
            }else{
                    $this->error('错误参数');
            }
        }
    }
}
