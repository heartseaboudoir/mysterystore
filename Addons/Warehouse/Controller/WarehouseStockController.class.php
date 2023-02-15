<?php
namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehouseStockController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = " and A.w_id = " .$this->_warehouse_id;

        $title = session('user_warehouse.w_name'). '_仓库类别库存';
        $this->meta_title = $title;

        $cate_name = I('cate_name');
        if ($cate_name != "") {
            $where .= " and C.title like '%" .$cate_name ."%'";
            $this->assign('cate_name', $cate_name);
        }

        $seeprice = $this->checkFunc('purchase_price');
        $this->assign('seeprice', $seeprice);

        $Model = M('WarehouseStock');

        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
        $shequ_id = $warehousedata['shequ_id'];

        $sql = "
        select G.cate_id,C.title as cate_name,W.w_name,cast(sum(A.num) as decimal(10,0)) as nums,cast(sum(GSV.all_nums) as decimal(10,0)) as all_nums,
        cast(sum(WIV.stock_price*A.num) as decimal(10,2)) as stock_amount,cast(sum((case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)*A.num) as decimal(10,2)) as sell_amount
         from  hii_warehouse_stock A
         left join hii_goods G on A.goods_id=G.id
         left join hii_goods_cate C on G.cate_id=C.id
         left join hii_goods_stocknum_view GSV on A.goods_id=GSV.goods_id
         left join hii_warehouse W on A.w_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A.goods_id=SP.goods_id
         left join (select * from hii_warehouse_inout_view where shequ_id = $shequ_id) WIV on A.goods_id=WIV.goods_id
         where 1=1" .$where ." group by G.cate_id,C.title,W.w_name order by G.cate_id asc";
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
            $printfile = $printmodel->pushWarehouseStockList($data,$title,$fname,$seeprice);
            echo($printfile);die;
        }
//分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Warehouse@WarehouseStock/index'));
    }

    public function indexgoods(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = '';
        $v = I('v');
        $this->assign('v', $v);
        $goods_name = I('goods_name');
        if ($goods_name != "") {
            $where .= " and G.title like '%" .$goods_name ."%'";
            $this->assign('goods_name', $goods_name);
        }
        $goods_id = I('goods_id');
        if ($goods_id != "") {
            $where .= " and G.id=" .$goods_id;
            $this->assign('goods_id', $goods_id);
        }
        $where .= " and A.w_id = " .$this->_warehouse_id;
        if ($v != "search") {
            $title = session('user_warehouse.w_name'). '_仓库商品库存';
        }else{
            $title = '搜索>>>' .$goods_name. '>>>' .session('user_warehouse.w_name'). '_商品库存';
        }

        $this->meta_title = $title;

        $cate_id = I('cate_id');
        if ($cate_id != "") {
            $where .= " and C.id=" .$cate_id;
            $this->assign('cate_id', $cate_id);
        }

        $seeprice = $this->checkFunc('purchase_price');
        $this->assign('seeprice', $seeprice);

        $Model = M('WarehouseStock');

        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
        $shequ_id = $warehousedata['shequ_id'];

        $sql = "
        select AV.value_id,AV.value_name,A.w_id,A.goods_id,floor(A.num) as num,W.w_name,
        G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,G.cate_id,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(GSV.all_nums) as all_nums,
        WIV.stock_price,cast(WIV.stock_price*A.num as decimal(10,2)) as this_stock_amout
         from  hii_warehouse_stock A
         left join hii_goods G on A.goods_id=G.id
         left join hii_goods_cate C on G.cate_id=C.id
         left join hii_goods_stocknum_view GSV on A.goods_id=GSV.goods_id
         left join hii_warehouse W on A.w_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A.goods_id=SP.goods_id
         left join (select * from hii_warehouse_inout_view where shequ_id = $shequ_id) WIV on A.goods_id=WIV.goods_id
         left join hii_attr_value AV on AV.value_id=A.value_id
         where 1=1" .$where ." order by A.goods_id asc
         ";
        //print_r($sql);die;
        $data = $Model->query($sql);
//print_r($data);die;
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseStockGoodsList($data,$title,$fname,$seeprice);
            echo($printfile);die;
        }
//分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Warehouse@WarehouseStock/indexgoods'));
    }
    public function view()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $v = I('v');
        if ($v == '') {
            $v = $_POST['v'];
        }
        $this->assign('v', $v);
		$value_id = I('value_id',0,'intval');
		$this->assign('value_id',$value_id);
        $seeprice = $this->checkFunc('purchase_price');
        $this->assign('seeprice', $seeprice);

        $goods_id = I('goods_id');
        if ($goods_id == '') {
            $goods_id = $_POST['goods_id'];
        }

        if($goods_id == ''){
            $this->error('不存在的商品ID');
        }
        $this->assign('goods_id', $goods_id);

        $where0 = " and A.w_id = " .$this->_warehouse_id ." and A.goods_id=" .$goods_id.' and A.value_id='.$value_id;
        $where1 = " and A.warehouse_id = " .$this->_warehouse_id ." and A.w_in_s_status in (1,3) and A1.goods_id=" .$goods_id.' and A1.value_id='.$value_id;
        $where2 = " and A.warehouse_id2 = " .$this->_warehouse_id." and A.w_out_s_status in (1,3) and A1.goods_id=" .$goods_id.' and A1.value_id='.$value_id;

        $goods_name = I('goods_name');
        if ($goods_name != "") {
            $where0 .= " and G.title like '%" .$goods_name ."%'";
            $where1 .= " and G.title like '%" .$goods_name ."%'";
            $where2 .= " and G.title like '%" .$goods_name ."%'";
            $this->assign('goods_name', $goods_name);
        }

        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
        $shequ_id = $warehousedata['shequ_id'];

        $Model = M('WarehouseStock');
        $sql = "
        select ifnull(AV.value_id,0)value_id,value_name,A.w_id,A.goods_id,cast(A.num as decimal(10,0)) as num,W.w_name,
        G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,G.cate_id,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,GSV.all_nums,
        WIV.stock_price,cast(WIV.stock_price*A.num as decimal(10,2)) as this_stock_amout
         from  hii_warehouse_stock A
         left join hii_goods G on A.goods_id=G.id
         left join hii_goods_cate C on G.cate_id=C.id
         left join hii_goods_stocknum_view GSV on A.goods_id=GSV.goods_id
         left join hii_warehouse W on A.w_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A.goods_id=SP.goods_id
         left join (select * from hii_warehouse_inout_view where shequ_id = $shequ_id) WIV on A.goods_id=WIV.goods_id
         left join hii_attr_value AV on AV.value_id=A.value_id
         where 1=1" .$where0 ." order by A.goods_id asc";
        $list = $Model->query($sql);
        //print_r($sql);die;
        if(is_array($list) && count($list) > 0){
            $this->assign('list', $list[0]);
        }else{
            $this->error('没有该商品出入库记录');
        }
        if($v == 'in') {
            $sql = "
            select * from (
                select A.w_in_s_id as this_id,A.w_in_s_sn as this_sn,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,
                A.w_in_s_type,S.s_name as this_from1,W1.w_name as this_from2,W.w_name as this_to1,
                A1.goods_id,A1.g_num,A1.g_price,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price
                 from  hii_warehouse_in_stock A
                  left join hii_warehouse_in_stock_detail A1 on A.w_in_s_id=A1.w_in_s_id
                  left join hii_warehouse_in WI on A.w_in_id=WI.w_in_id
                  left join hii_goods G on A1.goods_id=G.id
                  left join hii_supply S on A.supply_id=S.s_id
                  left join hii_warehouse W on A.warehouse_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
                  left join hii_warehouse W1 on WI.warehouse_id2=W1.w_id
                  where 1=1" . $where1 . "
                union all
                select A.w_o_out_id as this_id,A.w_o_out_sn as this_sn,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,
                5 as w_in_s_type,W.w_name as this_from1,S.title as this_from2,W1.w_name as this_to1,
                A1.goods_id,A1.g_num,A1.g_price,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price
                 from  hii_warehouse_other_out A
                  left join hii_warehouse_other_out_detail A1 on A.w_o_out_id=A1.w_o_out_id
                  left join hii_goods G on A1.goods_id=G.id
                  left join hii_store S on A.store_id=S.id
                  left join hii_warehouse W on A.warehouse_id=W.w_id
                  left join hii_warehouse W1 on A.warehouse_id2=W1.w_id
        left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
                  where 1=1 and A.warehouse_id2 = " . $this->_warehouse_id . " and A.w_o_out_status =1 and A1.goods_id=" .$goods_id ." and A1.value_id=".$value_id."
              ) A
                order by A.ptime desc";
            /**/
            $data = $Model->query($sql);
            //print_r($sql);die;
            $title = '查看【' . $list[0]['goods_name'] . '】【' . session('user_warehouse.w_name') . '】入库记录';
            $this->meta_title = $title;
            $isprint = I('isprint');
            if ($isprint == "") {
                $isprint = $_POST['isprint'];
            }
            if ($isprint == 1) {
                ob_clean;
                $fname = $title;
                $printmodel = new \Addons\Report\Model\ReportModel();
                $printfile = $printmodel->pushWarehouseStockInView($list[0], $data, $title, $fname, $seeprice,$v);
                echo($printfile);
                die;
            }

//分页
            $pcount = 15;
            $count = count($data);//得到数组元素个数
            $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
            $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出﻿
            $this->assign('_page', $show ? $show : '');
            $this->assign('_total', $count);
            $this->assign('v', $v);

            $this->assign('data', $datamain);
            $this->display(T('Addons://Warehouse@WarehouseStock/view'));
        }else{
            if($v == 'out') {
                $sql = "
                select A1.goods_id,A.w_out_s_type,A.store_id,S.title as store_name,A.warehouse_id1,W.w_name as w_name1,A.warehouse_id2,W1.w_name as w_name2,
                G.title as goods_name,G.bar_code,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,
                A1.g_num,A1.g_price,A.w_out_s_id as this_id,A.w_out_s_sn as this_sn,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime
                 from  hii_warehouse_out_stock A
                  left join hii_warehouse_out_stock_detail A1 on A.w_out_s_id=A1.w_out_s_id
                  left join hii_goods G on A1.goods_id=G.id
                  left join hii_store S on A.store_id=S.id
                  left join hii_warehouse W on A.warehouse_id1=W.w_id
                  left join hii_warehouse W1 on A.warehouse_id2=W1.w_id
        left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
                  where 1=1" . $where2 . " order by A.ptime desc";
                $data = $Model->query($sql);
                //print_r($sql);die;
                $title = '查看【' . $list[0]['goods_name'] . '】【' . session('user_warehouse.w_name') . '】出库记录';
                $this->meta_title = $title;
                $isprint = I('isprint');
                if ($isprint == "") {
                    $isprint = $_POST['isprint'];
                }
                if ($isprint == 1) {
                    ob_clean;
                    $fname = $title;
                    $printmodel = new \Addons\Report\Model\ReportModel();
                    $printfile = $printmodel->pushWarehouseStockInView($list[0], $data, $title, $fname, $seeprice,$v);
                    echo($printfile);
                    die;
                }

//分页
                $pcount = 15;
                $count = count($data);//得到数组元素个数
                $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
                $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
                $show = $Page->show();// 分页显示输出﻿
                $this->assign('_page', $show ? $show : '');
                $this->assign('_total', $count);
                $this->assign('v', $v);

                $this->assign('data', $datamain);
                $this->display(T('Addons://Warehouse@WarehouseStock/view'));
            }
        }
    }
}
