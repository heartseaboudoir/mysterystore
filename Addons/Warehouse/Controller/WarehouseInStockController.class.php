<?php
namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehouseInStockController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '入库单管理';
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

        $where = " and A.warehouse_id = " .$this->_warehouse_id;

        $title = $s_date. '>>>' .$e_date. '入库单';

        $Model = M('WarehouseInStock');

        $sql = "
        select A.w_in_s_id,A.w_in_s_sn,A.w_in_s_status,A.w_in_s_type,A.w_in_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,
        A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.remark,A.g_type,A.g_nums,
        WI.w_in_sn,WI.p_id,P.p_sn,WI.p_out_id,PO.p_o_sn,WI.s_out_s_id,WI.w_out_s_id,WI.o_out_id,W.i_sn,A.padmin_id,B1.nickname as pnickname,SB.s_back_sn,
        sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
         from  hii_warehouse_in_stock A
         left join hii_warehouse_in_stock_detail A1 on A.w_in_s_id=A1.w_in_s_id
         left join hii_member B on A.admin_id=B.uid
         left join hii_member B1 on A.padmin_id=B1.uid
         left join hii_warehouse C on A.warehouse_id=C.w_id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
         left join hii_supply S on A.supply_id=S.s_id
         left join hii_goods G on A1.goods_id=G.id
         left join hii_warehouse_in WI on A.w_in_id=WI.w_in_id
         left join hii_warehouse_inventory W on A.i_id=W.i_id
         left join hii_purchase P on WI.p_id=P.p_id
         left join hii_store_back SB on WI.s_back_id=SB.s_back_id
         left join hii_purchase_out PO on WI.p_out_id=PO.p_o_id
         where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where;
        $sql .= " group by A.w_in_s_id,A.w_in_s_sn,A.w_in_s_status,A.w_in_s_type,A.w_in_id,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,
        A.remark,A.g_type,A.g_nums,WI.p_id,P.p_sn,A.padmin_id,B1.nickname,SB.s_back_sn
         order by A.w_in_s_id desc";
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
            $printfile = $printmodel->pushWarehouseInStockList($data,$title,$fname);
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
        $this->display(T('Addons://Warehouse@WarehouseInStock/index'));
    }

    public function view()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '入库单查看';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }

        if($id == ''){
            $this->error('不存在的单据ID');
        }
        $this->assign('id', $id);

        $where = " and A.warehouse_id = " .$this->_warehouse_id;

        $Model = M('WarehouseInStock');

        $sql = "
        select A.w_in_s_id,A.w_in_s_sn,A.w_in_s_status,A.w_in_s_type,A.w_in_id,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,
        A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.remark,A.g_type,A.g_nums,
        WI.w_in_sn,WI.p_id,P.p_sn,WI.p_out_id,PO.p_o_sn,WI.s_out_s_id,WI.w_out_s_id,WI.o_out_id,WI1.i_id,A.padmin_id,B1.nickname as pnickname,SB.s_back_sn,
        sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
         from  hii_warehouse_in_stock A
         left join hii_warehouse_in_stock_detail A1 on A.w_in_s_id=A1.w_in_s_id
         left join hii_member B on A.admin_id=B.uid
         left join hii_member B1 on A.padmin_id=B1.uid
         left join hii_warehouse C on A.warehouse_id=C.w_id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
         left join hii_supply S on A.supply_id=S.s_id
         left join hii_goods G on A1.goods_id=G.id
         left join hii_warehouse_in WI on A.w_in_id=WI.w_in_id
         left join hii_purchase P on WI.p_id=P.p_id
         left join hii_purchase_out PO on WI.p_out_id=PO.p_o_id
         left join hii_warehouse_inventory WI1 on A.i_id=WI1.i_id
         left join hii_store_back SB on WI.s_back_id=SB.s_back_id
         where A.w_in_s_id=$id" .$where;
        $sql .= " group by A.w_in_s_id,A.w_in_s_sn,A.w_in_s_status,A.w_in_s_type,A.w_in_id,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,
        A.remark,A.g_type,A.g_nums,WI.p_id,P.p_sn,A.padmin_id,B1.nickname,SB.s_back_sn
         order by A.w_in_s_id desc";
        $list = $Model->query($sql);
        $sql = "
            select A.w_in_s_id,A.w_in_s_sn,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,
            A1.w_in_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,WID.g_num as y_num,WID.in_num,WID.out_num,WID1.g_num as p_num,A1.g_price,A1.g_num,A1.g_price,A1.remark,
            FROM_UNIXTIME(A1.endtime,'%Y-%m-%d') as endtime,ifnull(L.g_price,0) as last_price,AV.value_id,AV.value_name
            from  hii_warehouse_in_stock A
            left join hii_warehouse_in_stock_detail A1 on A.w_in_s_id=A1.w_in_s_id
            left join hii_goods G on A1.goods_id=G.id
            left join hii_goods_cate C on G.cate_id=C.id
        left join hii_warehouse C1 on A.warehouse_id=C1.w_id
        left join hii_shequ_price SP on C1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
            left join hii_warehouse_in_detail WID on A1.w_in_d_id=WID.w_in_d_id
            left join hii_warehouse_inventory_detail WID1 on A1.w_in_d_id=WID1.i_d_id
            left join hii_goods_last_purchase_price_view L on A1.goods_id=L.goods_id
            left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id
            left join hii_attr_value AV on AV.value_id=A1.value_id 
            where A.w_in_s_id=$id" .$where;
        $sql .= " order by A.w_in_s_id desc";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $title = '入库单' .$list[0]['w_in_s_sn'] .'查看';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseInStockView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Warehouse@WarehouseInStock/view'));
    }

    public function updatewarehouse()
    {
        $w_in_s_id = I('w_in_s_id', '');
        if ($w_in_s_id == '') {
            $w_in_s_id = $_POST['w_in_s_id'];
        }
        if($w_in_s_id == ''){
            $this->error('不存在的单据ID');
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        if ($w_in_s_id != '') {
            $where = array();
            $where['w_in_s_id'] = array('in',$w_in_s_id);
            $field = "w_in_s_d_id,w_in_s_id,goods_id,g_num,g_price,remark,w_in_d_id,value_id";
            $DetailList = M('WarehouseInStockDetail')
                ->field($field)
                ->where($where)->order('w_in_s_d_id asc')->select();
            $Model = M('WarehouseInStock');
            $data = $Model->where($where)->find();
            if(!$data){
                $this->error('没有单据');
            }
            $data['remark'] = $remark;
            $data['etime'] = time();
            $data['eadmin_id'] = UID;
            $Model = D('Addons://Warehouse/WarehouseInStock');

            $res = $Model->saveWarehouseInStock($w_in_s_id,$data,$DetailList,false);
            if($res>0){
                $this->success('提交成功');
            }else{
                $this->error($Model->err['msg']);
            }
        }
    }

    public function pass()
    {
        $w_in_s_id = I('id', '');
        if ($w_in_s_id == '') {
            $w_in_s_id = $_POST['id'];
        }
        if ($w_in_s_id == '') {
            $this->error('没有单据ID');
        }
        $pass = I('pass', '');
        if ($pass == '') {
            $pass = $_POST['pass'];
        }
        if ($pass == '') {
            $this->error('没有参数');
        }

        $where = array();
        $where['w_in_s_id'] = $w_in_s_id;
        $where['w_in_s_status'] = 0;

        $Model = M('WarehouseInStock');
        $dataMain = $Model->where($where)->find();
        if(!$dataMain){
            $this->error('单据不存在或者已经审核。');
        }
        $dataMain['ptime'] = time();
        $dataMain['padmin_id'] = UID;
        $dataMain['w_in_s_status'] = $pass;
        $ModelWarehouse = M('Warehouse');
        $dataWarehouse = $ModelWarehouse->where('w_id = ' .$dataMain['warehouse_id'])->find();

        $field = "w_in_s_d_id,w_in_s_id,goods_id,g_num,g_price,remark,endtime,w_in_d_id,value_id";
        $DetailList = M('WarehouseInStockDetail')
            ->field($field)
            ->where($where)->order('w_in_s_d_id asc')->select();
        if(!count($DetailList)>0){
            $this->error('入库单没有商品');
        }

        $DetailListWarehouseInOut = array();
        if ($pass == '1') {
            $Model = D('Addons://Warehouse/WarehouseInout');
            foreach($DetailList as $k=>$v){
                if($dataMain['w_in_s_type'] == 0 || $dataMain['w_in_s_type'] == 3){
                    //入库批次
                    //入库单：`w_in_s_type` int(1) DEFAULT '0' COMMENT '来源:0.采购单,1.店铺退货,2.仓库调拨,3.盘盈入库,4.其它',【只有采购入库和盘盈入库才加入入库批次】
                    //入库批次：`ctype` int(1) DEFAULT '0' COMMENT '批次入库类型:0.采购入库，1.盘盈入库',
                    $DetailListWarehouseInOut['goods_id'] = $DetailList[$k]['goods_id'];
                    $DetailListWarehouseInOut['shequ_id'] = $dataWarehouse['shequ_id'];
                    $DetailListWarehouseInOut['warehouse_id'] = $dataWarehouse['w_id'];
                    $DetailListWarehouseInOut['innum'] = $DetailList[$k]['g_num'];
                    $DetailListWarehouseInOut['inprice'] = $DetailList[$k]['g_price'];
                    $DetailListWarehouseInOut['num'] = $DetailList[$k]['g_num'];
                    $DetailListWarehouseInOut['endtime'] = $DetailList[$k]['endtime'];
                    $DetailListWarehouseInOut['ctime'] = time();
                    if($dataMain['w_in_s_type'] == 0){
                        $DetailListWarehouseInOut['ctype'] = 0;
                    }else{
                        $DetailListWarehouseInOut['ctype'] = 1;
                    }
                    $DetailListWarehouseInOut['w_in_s_d_id'] = $DetailList[$k]['w_in_s_d_id'];
                    //新增入库批次
                    $inout_id = 0;
                    $res = $Model->saveWarehouseInout($inout_id, $DetailListWarehouseInOut,false);
                    if($res > 0){
                        //$this->success('入库批次成功');
                    }else{
                        $this->error('入库批次失败1' .$Model->err['msg']);
                    }
                }
                //写入库存表
                $where_stock = array();
                $where_stock['goods_id'] = $DetailList[$k]['goods_id'];
                $where_stock['w_id'] = $this->_warehouse_id;
                $where_stock['value_id'] = $DetailList[$k]['value_id'];
                $WarehouseStockList = M('WarehouseStock')->where($where_stock)->find();
                if(is_array($WarehouseStockList) && count($WarehouseStockList)>0){
                    $stock_id = $WarehouseStockList['id'];
                    $WarehouseStockList['num'] += $DetailList[$k]['g_num'];
                }else{
                    $stock_id = 0;
                    $WarehouseStockList['w_id'] = $dataMain['warehouse_id'];
                    $WarehouseStockList['goods_id'] = $DetailList[$k]['goods_id'];
                    $WarehouseStockList['num'] = $DetailList[$k]['g_num'];
                    $WarehouseStockList['value_id'] = $DetailList[$k]['value_id'];
                }
                $ModelStock = D('Addons://Warehouse/WarehouseStock');
                $resStock = $ModelStock->saveWarehouseStock($stock_id, $WarehouseStockList,false);
                if($resStock > 0){
                    //$this->success('库存入库成功');
                }else{
                    $this->error('入库审核失败1' .$Model->err['msg']);
                }
            }
            $Model1 = D('Addons://Warehouse/WarehouseInStock');
            $res1 = $Model1->saveWarehouseInStock($w_in_s_id, $dataMain, $DetailList,false);
            if($res1 > 0){
                $this->success('入库审核成功');
            }else{
                $this->error('入库审核失败2' .$Model->err['msg']);
            }
        }else{
            $this->error('错误参数');
        }
    }

}
