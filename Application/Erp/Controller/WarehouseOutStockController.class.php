<?php

namespace Erp\Controller;
use Think\Controller;


class WarehouseOutStockController extends AdminController {

    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }

    
    public function index()
    {

        $data = $this->gv();

        //第几页
        $p = $data['page'];
        if($p == "" || $p == 0){
            $p = 1;
        }
        //第几页
        $p1 = $data['p'];
        if($p1 == "" || $p1 == 0){
            $p1 = 1;
        }
        if($p1 != 1){
            $p = $p1;
        }
        //时间范围默认30天
        $s_date = $data['s_date'];
        $e_date = $data['e_date'];
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
        //门店条件
        $store_id = $data['store_id'];
        if ($store_id != "" && $store_id != 0 ) {
            //显示隐藏已处理单据
            $wheresupply = " and A.store_id = " .$store_id;
            $dataout['store_id'] = $store_id;
        }

        $dataout['s_date'] = $s_date;
        $dataout['e_date'] = $e_date;

        $where = " and A.warehouse_id2 = " .$this->_warehouse_id;

        $title = $s_date. '>>>' .$e_date. '出库单';

        $Model = M('WarehouseOut');

        $sql = "
        select A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,A.ctime,A.ptime,A.admin_id,A.nickname,ifnull(A.pnickname,'') as pnickname,
            A.warehouse_id1,A.warehouse_id2,A.w_name1,A.w_name2,A.store_id,ifnull(A.store_name,'') as store_name,A.remark,A.g_type,A.g_nums,
            A.w_out_id,A.w_r_id,A.s_r_id,A.padmin_id,A.w_out_sn,A.g_amounts,A.p_amounts,ifnull(GROUP_CONCAT( WR.w_r_sn ),'') as w_r_sn,A.s_r_id,ifnull(GROUP_CONCAT( SR.s_r_sn ),'') as s_r_sn
       from (
            select A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.admin_id,B.nickname,B1.nickname as pnickname,
            A.warehouse_id1,A.warehouse_id2,C1.w_name as w_name1,C2.w_name as w_name2,A.store_id,S.title as store_name,A.remark,A.g_type,A.g_nums,
            A.w_out_id,WO.w_r_id,WO.s_r_id,A.padmin_id,WO.w_out_sn,
            sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
             from  hii_warehouse_out_stock A
              left join hii_warehouse_out_stock_detail A1 on A.w_out_s_id=A1.w_out_s_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.padmin_id=B1.uid
              left join hii_warehouse C1 on A.warehouse_id1=C1.w_id
              left join hii_warehouse C2 on A.warehouse_id2=C2.w_id
        left join hii_shequ_price SP on C2.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_store S on A.store_id=S.id
              left join hii_goods G on A1.goods_id=G.id
              left join hii_warehouse_out WO on A.w_out_id=WO.w_out_id
              where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where .$wheresupply ."
            group by A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,A.ctime,A.admin_id,B.nickname,B1.nickname,
            A.warehouse_id1,A.warehouse_id2,C1.w_name,C2.w_name,A.store_id,S.title,A.remark,A.g_type,A.g_nums,
            A.w_out_id,WO.w_r_id,WO.s_r_id,A.padmin_id,WO.w_out_sn
            order by A.w_out_id desc
           ) A
      left join hii_warehouse_request WR on find_in_set(A.w_r_id,WR.w_r_id)
      left join hii_store_request SR on find_in_set(A.s_r_id,SR.s_r_id)
      group by A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,A.ctime,A.admin_id,A.nickname,A.pnickname,
      A.warehouse_id1,A.warehouse_id2,A.w_name1,A.w_name2,A.store_id,A.store_name,A.remark,A.g_type,A.g_nums,
      A.w_out_id,A.w_r_id,A.s_r_id,A.padmin_id,A.w_out_sn,A.g_amounts,A.p_amounts
      order by A.w_out_s_id desc
      ";
        $datalist = $Model->query($sql);
        //$dataout['sql'] = $sql;

        //print_r($sql);die;
        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/WarehouseOutStock_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseOutStockList($datalist,$title,$fname);
            $dataout1['filename'] = $printfile;
            $this->response(self::CODE_OK, $dataout1);
        }
        //分页
        //每页共多少条
        $pagesize = $data['pageSize'];
        if($pagesize == "" || $pagesize == 0){
            $pagesize = 2;
        }
        $pcount = $pagesize;
        $count = count($datalist);//得到数组元素个数
        $Page = new \Think\Page($count,$pcount,array(),$p);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter['s_date']   		=   $s_date;
        $Page->parameter['e_date']   		=   $e_date;
        $Page->parameter['store_id']   		=   $store_id;
        $datamain = array_slice($datalist,$Page->firstRow,$Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $dataout['pageSize'] = $pcount;
        $dataout['recordCount'] = $count;
        $dataout['p'] = $p;
        $dataout['pager'] = $show;
        $dataout['pages'] = ceil($count/$pcount);

        $dataout['list'] = $datamain;

        $shequ = $_SESSION['can_shequs'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelStore = M('Store');
        $StoreData = $ModelStore->field('id as store_id,title as store_name')->where($whereSQ)->select();
        $dataout['store'] = $StoreData;

        $this->response(self::CODE_OK, $dataout);
    }

    public function view()
    {

        $data = $this->gv();

        $id = $data['id'];
        if ($id == "" || $id == "0"){
            $this->response(999, "没有id");
        }
        $dataout['id'] = $id;

        $where = " and A.warehouse_id2 = " .$this->_warehouse_id;


        $Model = M('WarehouseOutStock');

        $sql = "
        select A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,A.ctime,A.ptime,A.admin_id,A.nickname,ifnull(A.pnickname,'') as pnickname,
            A.warehouse_id1,A.warehouse_id2,A.w_name1,A.w_name2,A.store_id,ifnull(A.store_name,'') as store_name,A.remark,A.g_type,A.g_nums,
            A.w_out_id,A.w_r_id,A.s_r_id,A.padmin_id,A.w_out_sn,A.g_amounts,A.p_amounts,ifnull(GROUP_CONCAT( WR.w_r_sn ),'') as w_r_sn,A.s_r_id,ifnull(GROUP_CONCAT( SR.s_r_sn ),'') as s_r_sn
       from (
            select A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,
            FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,A.admin_id,B.nickname,B1.nickname as pnickname,
            A.warehouse_id1,A.warehouse_id2,C1.w_name as w_name1,C2.w_name as w_name2,A.store_id,S.title as store_name,A.remark,A.g_type,A.g_nums,
            A.w_out_id,WO.w_r_id,WO.s_r_id,A.padmin_id,WO.w_out_sn,
            sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
             from  hii_warehouse_out_stock A
              left join hii_warehouse_out_stock_detail A1 on A.w_out_s_id=A1.w_out_s_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.padmin_id=B1.uid
              left join hii_warehouse C1 on A.warehouse_id1=C1.w_id
              left join hii_warehouse C2 on A.warehouse_id2=C2.w_id
        left join hii_shequ_price SP on C2.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_store S on A.store_id=S.id
              left join hii_goods G on A1.goods_id=G.id
              left join hii_warehouse_out WO on A.w_out_id=WO.w_out_id
              where A.w_out_s_id = $id " .$where ."
            group by A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,A.ctime,A.admin_id,B.nickname,B1.nickname,
            A.warehouse_id1,A.warehouse_id2,C1.w_name,C2.w_name,A.store_id,S.title,A.remark,A.g_type,A.g_nums,
            A.w_out_id,WO.w_r_id,WO.s_r_id,A.padmin_id,WO.w_out_sn
            order by A.w_out_id desc
           ) A
      left join hii_warehouse_request WR on find_in_set(A.w_r_id,WR.w_r_id)
      left join hii_store_request SR on find_in_set(A.s_r_id,SR.s_r_id)
      group by A.w_out_s_id,A.w_out_s_sn,A.w_out_s_status,A.w_out_s_type,A.ctime,A.ptime,A.admin_id,A.nickname,A.pnickname,
      A.warehouse_id1,A.warehouse_id2,A.w_name1,A.w_name2,A.store_id,A.store_name,A.remark,A.g_type,A.g_nums,
      A.w_out_id,A.w_r_id,A.s_r_id,A.padmin_id,A.w_out_sn,A.g_amounts,A.p_amounts
      ";
        $datalist = $Model->query($sql);
        if(is_array($datalist) && count($datalist)>0){
            $title =  $datalist['w_out_s_sn'] .'出库单查看';
        }else{
            $this->response(998, "没有数据");
        }

        $sql = "
        select A.w_out_s_id,A.w_out_s_sn,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,
        A1.w_out_s_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,A2.in_num,A2.out_num,A1.g_price,A1.remark,ifnull(L.g_price,0) as last_price,AV.value_id,AV.value_name 
         from  hii_warehouse_out_stock A
         left join hii_warehouse_out_stock_detail A1 on A.w_out_s_id=A1.w_out_s_id
         left join hii_warehouse_out_detail A2 on A1.w_out_d_id=A2.w_out_d_id
         left join hii_goods G on A1.goods_id=G.id
         left join hii_goods_cate C on G.cate_id=C.id
        left join hii_warehouse C1 on A.warehouse_id2=C1.w_id
        left join hii_shequ_price SP on C1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
         left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id2=WS.w_id
         left join hii_goods_last_purchase_price_view L on A1.goods_id=L.goods_id
         left join hii_attr_value AV on AV.value_id=A1.value_id
         where A.w_out_s_id=$id" .$where ." order by A.w_out_id desc
         ";
        $datachild = $Model->query($sql);
        //print_r($sql);die;
        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/WarehouseOutStockView_'.$datalist[0]['w_out_s_sn'] .'_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseOutStockView($datalist[0],$datachild,$title,$fname);
            $dataout1['filename'] = $printfile;
            $this->response(self::CODE_OK, $dataout1);
        }
        $dataout['main'] = $datalist;
        $dataout['child'] = $datachild;



        $this->response(self::CODE_OK, $dataout);
    }

    public function pass()
    {
        $data = $this->gv();

        $id = $data['id'];
        if ($id == "" || $id == "0"){
            $this->response(999, "没有id");
        }
        $dataout['id'] = $id;

        $where = " w_out_s_id = " .$id;
        $where1 = " and w_out_s_status = 0";
        $where1 .= " and warehouse_id2 = " .$this->_warehouse_id;


        $Model = M('WarehouseOutStock');
        $dataMain1 = $Model->where($where .$where1)->select();
        if(is_array($dataMain1) && count($dataMain1)>0){
            $dataMain = $dataMain1[0];
        }else{
            $this->response(999, '没有出库单或者出库单已处理，不能再次审核');
        }

        $pass = $data['pass'];
        if($pass == 0 || $pass == ""){
            $this->response(999, '没有参数');
        }

        $dataMain['ptime'] = time();
        $dataMain['padmin_id'] = UID;
        $dataMain['w_out_s_status'] = $pass;


        $field = "w_out_s_d_id,w_out_s_id,goods_id,g_num,g_price,remark,w_out_d_id,value_id";
        $DetailList = M('WarehouseOutStockDetail')
            ->field($field)
            ->where($where)->order('w_out_s_d_id asc')->select();
        if(!count($DetailList)>0){
            $this->response(999, '出库单没有商品');
        }

        if ($pass == '1') {
            foreach($DetailList as $k=>$v){
                //查库存表有没有该商品库存数据
                $where_stock = array();
                $where_stock['goods_id'] = $DetailList[$k]['goods_id'];
                $where_stock['w_id'] = $this->_warehouse_id;
                $where_stock['value_id'] = $DetailList[$k]['value_id'];
                $WarehouseStockList = M('WarehouseStock')->where($where_stock)->find();
                if(is_array($WarehouseStockList) && count($WarehouseStockList)>0){
                    $stock_id = $WarehouseStockList['id'];
                    $WarehouseStockList['num'] = $WarehouseStockList['num'] - $DetailList[$k]['g_num'];
                }else{
                    $this->response(999, '该商品ID:'.$DetailList[$k]['goods_id'].'没有库存');
                }
            }
            $warehouse_id2 = $dataMain['warehouse_id2'];
            $WarehouseModel = M('Warehouse');
            $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id2) -> find();
            $shequ_id = $warehousedata['shequ_id'];
            $pcount = 0;
            $nums = 0;
            $DetailListWI = array();
            $DetailListTmp = array();

            foreach($DetailList as $k=>$v) {
                if($dataMain['w_out_s_type'] == 3){
                    //出库批次:`etype`最后操作类型:0.销售出库，1.报损出库，2.盘亏出库',
                    //出库单：`w_out_s_type` int(1) DEFAULT '0' COMMENT '来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它',【只有盘亏出库才写入出库批次表】
                    $Model = M('WarehouseInout');
                    $WarehouseInoutData = $Model->where('goods_id = ' .$DetailList[$k]['goods_id'] .' and num>0 and warehouse_id = ' .$dataMain['warehouse_id2'])->order('ctime asc')->select();
                    //修改入库批次
                    $g_num = $DetailList[$k]['g_num'];
                    $WarehouseInoutDataListTemp = array();
                    foreach($WarehouseInoutData as $k1=>$v1) {
                        $DetailListWarehouseInOut['inout_id'] = $WarehouseInoutData[$k1]['inout_id'];
                        $DetailListWarehouseInOut['goods_id'] = $WarehouseInoutData[$k1]['goods_id'];
                        $DetailListWarehouseInOut['innum'] = $WarehouseInoutData[$k1]['innum'];
                        $DetailListWarehouseInOut['outnum'] = $WarehouseInoutData[$k1]['outnum'];
                        $DetailListWarehouseInOut['ctime'] = $WarehouseInoutData[$k1]['ctime'];
                        $DetailListWarehouseInOut['ctype'] = $WarehouseInoutData[$k1]['ctype'];
                        $DetailListWarehouseInOut['endtime'] = $WarehouseInoutData[$k1]['endtime'];
                        $DetailListWarehouseInOut['warehouse_id'] = $WarehouseInoutData[$k1]['warehouse_id'];
                        $DetailListWarehouseInOut['store_id'] = $WarehouseInoutData[$k1]['store_id'];
                        $DetailListWarehouseInOut['w_in_s_d_id'] = $WarehouseInoutData[$k1]['w_in_s_d_id'];
                        $DetailListWarehouseInOut['s_in_s_d_id'] = $WarehouseInoutData[$k1]['s_in_s_d_id'];
                        $DetailListWarehouseInOut['etime'] = time();
                        $DetailListWarehouseInOut['etype'] = 2;
                        $DetailListWarehouseInOut['enum'] = $WarehouseInoutData[$k1]['num'];
                        $DetailListWarehouseInOut['e_id'] = $dataMain['w_out_s_id'];
                        $DetailListWarehouseInOut['e_no'] = $WarehouseInoutData[$k1]['e_no'] + 1;
                        if($g_num > $WarehouseInoutData[$k1]['num']){
                            $DetailListWarehouseInOut['num'] = 0;
                            $g_num = $g_num - $WarehouseInoutData[$k1]['num'];
                        }else {
                            $DetailListWarehouseInOut['num'] = $WarehouseInoutData[$k1]['num'] - $g_num;
                            $g_num = 0;
                        }
                        $WarehouseInoutDataListTemp[] = $DetailListWarehouseInOut;
                    }
                    if($g_num > 0){//盘亏数量减掉【本仓库】入库批次的批次数量，还有多？！操作：不修改这个仓库的这个商品入库批次。找区域内所有其它入库批次，然后先进先出。
                        $Model = M('WarehouseInout');
                        $WarehouseInoutData = $Model->where('goods_id = ' .$DetailList[$k]['goods_id'] .' and num>0 and shequ_id = ' .$shequ_id. 'and warehouse_id <> ' .$dataMain['warehouse_id2'])->order('ctime asc')->select();
                        //修改入库批次
                        foreach($WarehouseInoutData as $k1=>$v1) {
                            $DetailListWarehouseInOut['inout_id'] = $WarehouseInoutData[$k1]['inout_id'];
                            $DetailListWarehouseInOut['goods_id'] = $WarehouseInoutData[$k1]['goods_id'];
                            $DetailListWarehouseInOut['innum'] = $WarehouseInoutData[$k1]['innum'];
                            $DetailListWarehouseInOut['outnum'] = $WarehouseInoutData[$k1]['outnum'];
                            $DetailListWarehouseInOut['ctime'] = $WarehouseInoutData[$k1]['ctime'];
                            $DetailListWarehouseInOut['ctype'] = $WarehouseInoutData[$k1]['ctype'];
                            $DetailListWarehouseInOut['endtime'] = $WarehouseInoutData[$k1]['endtime'];
                            $DetailListWarehouseInOut['warehouse_id'] = $WarehouseInoutData[$k1]['warehouse_id'];
                            $DetailListWarehouseInOut['store_id'] = $WarehouseInoutData[$k1]['store_id'];
                            $DetailListWarehouseInOut['w_in_s_d_id'] = $WarehouseInoutData[$k1]['w_in_s_d_id'];
                            $DetailListWarehouseInOut['s_in_s_d_id'] = $WarehouseInoutData[$k1]['s_in_s_d_id'];
                            $DetailListWarehouseInOut['etime'] = time();
                            $DetailListWarehouseInOut['etype'] = 2;
                            $DetailListWarehouseInOut['enum'] = $WarehouseInoutData[$k1]['num'];
                            $DetailListWarehouseInOut['e_id'] = $dataMain['w_out_s_id'];
                            $DetailListWarehouseInOut['e_no'] = $WarehouseInoutData[$k1]['e_no'] + 1;
                            if($g_num > $WarehouseInoutData[$k1]['num']){
                                $DetailListWarehouseInOut['num'] = 0;
                                $g_num = $g_num - $WarehouseInoutData[$k1]['num'];
                            }else {
                                $DetailListWarehouseInOut['num'] = $WarehouseInoutData[$k1]['num'] - $g_num;
                                $g_num = 0;
                            }
                            $WarehouseInoutDataListTemp[] = $DetailListWarehouseInOut;
                        }
                        if($g_num > 0) {//盘亏数量大于入库批次数量,报错
                            $this->response(999, '该商品ID:'.$DetailList[$k]['goods_id'].'盘亏数量' .$DetailList[$k]['g_num']. '大于入库批次的目前库存数量总和，请检查。');
                        }
                    }
                    foreach($WarehouseInoutDataListTemp as $k1=>$v1) {
                        $WarehouseInoutDataList[] = $WarehouseInoutDataListTemp[$k1];
                    }
                }
            }
            //合计数据修改库存
            $field = "goods_id,max(g_price) as g_price,sum(g_num) as g_num,value_id";
            $Mdetail = M('WarehouseOutStockDetail');
            $DetailListSUM = $Mdetail
                ->field($field)
                ->where($where)
                ->order('w_out_s_d_id asc')
                ->group('goods_id,value_id')
                ->select();

            foreach($DetailListSUM as $k=>$v) {
                //修改库存表
                $where_stock = array();
                $where_stock['goods_id'] = $DetailListSUM[$k]['goods_id'];
                $where_stock['w_id'] = $this->_warehouse_id;
                $where_stock['value_id'] = $DetailListSUM[$k]['value_id'];
                $WarehouseStockList = M('WarehouseStock')->where($where_stock)->find();
                if(is_array($WarehouseStockList) && count($WarehouseStockList)>0){
                    if( ($WarehouseStockList['num'] - $DetailListSUM[$k]['g_num']) < 0){
                        $this->error( '该商品ID:'.$DetailListSUM[$k]['goods_id'].'库存小于出库数量');
                    }else{
                        $WarehouseStockList['num'] = $WarehouseStockList['num'] - $DetailListSUM[$k]['g_num'];
                        $WarehouseStockList1[] = $WarehouseStockList;
                    }
                }else{
                    $this->error('该商品ID:'.$DetailListSUM[$k]['goods_id'].'没有库存');
                }
                $DetailListTmp['goods_id'] = $DetailListSUM[$k]['goods_id'];
                $DetailListTmp['g_num'] = $DetailListSUM[$k]['g_num'];
                $DetailListTmp['g_price'] = $DetailListSUM[$k]['g_price'];
                $DetailListTmp['value_id'] = $DetailListSUM[$k]['value_id'];
                $DetailListWI[] = $DetailListTmp;
                $pcount++;
                $nums += $DetailListSUM[$k]['g_num'];
            }
            if($dataMain['w_out_s_type'] == 0){
                //审核并新增入库验收单
                $w_in_id = 0;
                $new_no = get_new_order_no('RY','hii_warehouse_in','w_in_sn');
                $dataWI = array();
                $dataWI['w_in_sn'] = $new_no;
                $dataWI['w_in_status'] = 0;
                $dataWI['w_in_type'] = 2;
                $dataWI['w_out_s_id'] = $dataMain['w_out_s_id'];
                $dataWI['ctime'] = time();
                $dataWI['admin_id'] = UID;
                $dataWI['etime'] = 0;
                $dataWI['eadmin_id'] = 0;
                $dataWI['ptime'] = 0;
                $dataWI['padmin_id'] = 0;
                $dataWI['supply_id'] = 0;
                $dataWI['warehouse_id'] = $dataMain['warehouse_id1'];
                $dataWI['warehouse_id2'] = $dataMain['warehouse_id2'];
                $dataWI['remark'] = $dataMain['remark'];
                $dataWI['g_type'] = $pcount;
                $dataWI['g_nums'] = $nums;
                $Model1 = D('Addons://Warehouse/WarehouseIn');
                $res1 = $Model1->saveWarehouseIn($w_in_id,$dataWI,$DetailListWI,false);
                if($res1>0){
                    $Model2 = D('Addons://Warehouse/WarehouseOutStock');
                    $res1 = $Model2->saveWarehouseOutStock($id, $dataMain, $DetailList,false);
                    if($res1 > 0){
                        //更改库存
                        foreach($WarehouseStockList1 as $k=>$v) {
                            $ModelStock = D('Addons://Warehouse/WarehouseStock');
                            $resStock = $ModelStock->saveWarehouseStock($WarehouseStockList1[$k]['id'], $WarehouseStockList1[$k],false);
                            if ($resStock > 0) {
                                //$this->success('库存出库成功', Cookie('__forward__'));
                            } else {
                                $this->response(999, '该商品ID:' . $DetailList[$k]['goods_id'] . ',更改库存失败');
                            }
                        }
                        $this->response(self::CODE_OK, '出库审核成功');
                    }else{
                        $this->response(999, '出库审核失败:'.$Model2->err['msg']);
                    }
                }else{
                    $this->response(999, '新增入库验收单失败:'.$Model1->err['msg']);
                }
            }else{
                if($dataMain['w_out_s_type'] == 1 || $dataMain['w_out_s_type'] == 5){
                    //审核并新增门店验收单
                    $w_in_id = 0;
                    $new_no = get_new_order_no('SI','hii_store_in','s_in_sn');
                    $dataWI = array();
                    $dataWI['s_in_sn'] = $new_no;
                    $dataWI['s_in_status'] = 0;
                    $dataWI['s_in_type'] = 0;
                    $dataWI['w_out_s_id'] = $dataMain['w_out_s_id'];
                    $dataWI['ctime'] = time();
                    $dataWI['admin_id'] = UID;
                    $dataWI['etime'] = 0;
                    $dataWI['eadmin_id'] = 0;
                    $dataWI['ptime'] = 0;
                    $dataWI['padmin_id'] = 0;
                    $dataWI['supply_id'] = 0;
                    $dataWI['warehouse_id'] = $dataMain['warehouse_id2'];
                    $dataWI['store_id2'] = $dataMain['store_id'];
                    $dataWI['remark'] = $dataMain['remark'];
                    $dataWI['g_type'] = $pcount;
                    $dataWI['g_nums'] = $nums;
                    $Model1 = D('Addons://Store/StoreIn');
                    $res1 = $Model1->saveStoreIn($w_in_id,$dataWI,$DetailListWI,false);
                    if($res1>0){
                        $Model2 = D('Addons://Warehouse/WarehouseOutStock');
                        $res1 = $Model2->saveWarehouseOutStock($id, $dataMain, $DetailList,false);
                        if($res1 > 0){
                            $ModelStoreRequest = D('Addons://Warehouse/StoreRequest');
                            $res111 = $ModelStoreRequest->saveWarehouseOutToStoreRequest($dataMain['w_out_id']);
                            if($res111 > 0){
                                //更改库存
                                foreach($WarehouseStockList1 as $k=>$v) {
                                    $ModelStock = D('Addons://Warehouse/WarehouseStock');
                                    $resStock = $ModelStock->saveWarehouseStock($WarehouseStockList1[$k]['id'], $WarehouseStockList1[$k],false);
                                    if ($resStock > 0) {
                                        //$this->success('库存出库成功', Cookie('__forward__'));
                                    } else {
                                        $this->response(999, '该商品ID:' . $DetailList[$k]['goods_id'] . ',更改库存失败');
                                    }
                                }
                                $this->response(self::CODE_OK, '出库审核成功');
                            }else{
                                $this->response(999,'更改门店申请失败1' .$ModelStoreRequest->err['msg']);
                            }
                        }else{
                            $this->response(999, '出库审核失败:'.$Model2->err['msg']);
                        }
                    }else{
                        $this->response(999, '出库审核失败:'.$Model1->err['msg']);
                    }
                }else{
                    if($dataMain['w_out_s_type'] == 3) {
                        //审核盘亏出库单
                        if(is_array($WarehouseInoutDataList) && count($WarehouseInoutDataList) > 0) {
                            $Model1 = D('Addons://Warehouse/WarehouseInout');
                            $res = $Model1->saveWarehouseInoutList($WarehouseInoutDataList);
                            if ($res > 0) {
                                $Model2 = D('Addons://Warehouse/WarehouseOutStock');
                                $res1 = $Model2->saveWarehouseOutStock($id, $dataMain, $DetailList,false);
                                if($res1 > 0){
                                    //更改库存
                                    foreach($WarehouseStockList1 as $k=>$v) {
                                        $ModelStock = D('Addons://Warehouse/WarehouseStock');
                                        $resStock = $ModelStock->saveWarehouseStock($WarehouseStockList1[$k]['id'], $WarehouseStockList1[$k],false);
                                        if ($resStock > 0) {
                                            //$this->success('库存出库成功', Cookie('__forward__'));
                                        } else {
                                            $this->response(999, '该商品ID:' . $DetailList[$k]['goods_id'] . ',更改库存失败');
                                        }
                                    }
                                    $this->response(self::CODE_OK, '出库审核成功');
                                }else{
                                    $this->response(999, '出库审核失败:'.$Model2->err['msg']);
                                }
                            } else {
                                $this->response(999, '修改入库批次失败1:'.$Model1->err['msg']);
                            }
                        }else{
                            $this->response(999, '修改入库批次失败2。');
                        }
                    }else{
                        $this->response(999, '暂时不支持其它类型出库');
                    }
                }

            }
        }else{
            if ($pass == '2') {
                $this->response(999, '错误参数2');
            }else{
                $this->response(999, '错误参数:' .$pass);
            }
        }
    }
}

