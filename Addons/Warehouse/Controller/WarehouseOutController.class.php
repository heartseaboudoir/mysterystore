<?php
namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehouseOutController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '出库验货单列表';
        //时间范围默认30天
        $s_date = I('s_date');
        $e_date = I('e_date');
        $showhide = I('showhide');
        $bystore = I('bystore');
        $store_id = I('store_id');
        $goods_name = I('goods_name');
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
        if ($showhide == "" || $showhide == 0 ) {
            //显示隐藏已处理单据
            $whereshowhide = " and A.w_out_status = 0";
            $this->assign('thisshowhide', 0);
            $this->assign('showhide', 1);
        }else{
            $this->assign('thisshowhide', 1);
            $this->assign('showhide', 0);
        }
        if ($store_id != "" && $store_id != 0 ) {
            //显示隐藏已处理单据
            $wheresupply = " and A.store_id = " .$store_id;
            $this->assign('store_id', $store_id);
        }
        if ($goods_name != "" && $goods_name != "商品名" ) {
            //显示隐藏已处理单据
            $wheregoodsname = " and A1.goods_id in (select id as goods_id from hii_goods where title like '%" .$goods_name ."%')";
            $this->assign('goods_name', $goods_name);
        }
        $s_date = date('Y-m-d',$s_date);
        $e_date = date('Y-m-d',$e_date);
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);

        $where = " and A.warehouse_id2 = " .$this->_warehouse_id;

        $title = $s_date. '>>>' .$e_date. '出库验货单';

        $Model = M('WarehouseOut');

        $sql = "
        select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.ctime,A.admin_id,A.nickname,A.pnickname,
        A.warehouse_id1,A.warehouse_id2,A.w_name1,A.w_name2,A.store_id,A.store_name,A.remark,A.g_type,A.g_nums,
        A.w_r_id,A.s_r_id,A.padmin_id,A.w_out_s_sn,A.g_amounts,A.p_amounts,GROUP_CONCAT( WR.w_r_sn ) as w_r_sn,A.s_r_id,GROUP_CONCAT( SR.s_r_sn ) as s_r_sn
       from (
            select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,B1.nickname as pnickname,
            A.warehouse_id1,A.warehouse_id2,C1.w_name as w_name1,C2.w_name as w_name2,A.store_id,S.title as store_name,A.remark,A.g_type,A.g_nums,
            A.w_r_id,A.s_r_id,A.padmin_id,WOS.w_out_s_sn,
            sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
             from  hii_warehouse_out A
              left join hii_warehouse_out_detail A1 on A.w_out_id=A1.w_out_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.padmin_id=B1.uid
              left join hii_warehouse C1 on A.warehouse_id1=C1.w_id
              left join hii_warehouse C2 on A.warehouse_id2=C2.w_id
        left join hii_shequ_price SP on C2.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_store S on A.store_id=S.id
              left join hii_goods G on A1.goods_id=G.id
              left join hii_warehouse_out_stock WOS on A.w_out_id=WOS.w_out_id
              where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where .$whereshowhide .$wheresupply .$wheregoodsname ."
            group by A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.ctime,A.admin_id,B.nickname,B1.nickname,
            A.warehouse_id1,A.warehouse_id2,C1.w_name,C2.w_name,A.store_id,S.title,A.remark,A.g_type,A.g_nums,
            A.w_r_id,A.s_r_id,A.padmin_id,WOS.w_out_s_sn
            order by A.w_out_id desc
           ) A
      left join hii_warehouse_request WR on find_in_set(A.w_r_id,WR.w_r_id)
      left join hii_store_request SR on find_in_set(A.s_r_id,SR.s_r_id)
      group by A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.ctime,A.admin_id,A.nickname,A.pnickname,
      A.warehouse_id1,A.warehouse_id2,A.w_name1,A.w_name2,A.store_id,A.store_name,A.remark,A.g_type,A.g_nums,
      A.w_r_id,A.s_r_id,A.padmin_id,A.w_out_s_sn,A.g_amounts,A.p_amounts

      ";
        if ($bystore != "" && $bystore != 0 ) {
            $this->assign('bystore', 0);
            $this->assign('thisbystore', 1);
            //按照供应商，再按照单据创建时间排序
            $sql .= " order by A.store_id asc,A.w_out_id desc";
        }else {
            $this->assign('bystore', 1);
            $this->assign('thisbystore', 0);
            $sql .= " order by A.w_out_id desc";
        }
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
            $printfile = $printmodel->pushWarehouseOutList($data,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $Page->parameter["s_date"] = $s_date;
        $Page->parameter["e_date"] = $e_date;
        $Page->parameter["showhide"] = $showhide;
        $Page->parameter["bystore"] = $bystore;
        $Page->parameter["store_id"] = $store_id;
        $Page->parameter["goods_name"] = $goods_name;
        $show= $Page->show();// 分页显示输出﻿

        $shequ = $_SESSION['can_shequs'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelStore = M('Store');
        $StoreData = $ModelStore->where($whereSQ)->select();
        $this->assign('store', $StoreData);

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Warehouse@WarehouseOut/index'));
    }

    public function view()
    {
        $this->meta_title = '出库验货单查看';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }

        if($id == ''){
            $this->error('不存在的单据ID');
        }
        $this->assign('id', $id);

        $where = " and A.warehouse_id2 = " .$this->_warehouse_id;

        $Model = M('WarehouseOut');

        $sql = "
        select A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,B1.nickname as pnickname,
        A.warehouse_id1,A.warehouse_id2,C1.w_name as w_name1,C2.w_name as w_name2,A.store_id,S.title as store_name,ifnull(C1.w_address,S.address) as address,A.remark,A.g_type,A.g_nums,
        A.w_r_id,WR.w_r_sn,A.s_r_id,SR.s_r_sn,A.padmin_id,WOS.w_out_s_sn,
        sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
         from  hii_warehouse_out A
          left join hii_warehouse_out_detail A1 on A.w_out_id=A1.w_out_id
          left join hii_member B on A.admin_id=B.uid
          left join hii_member B1 on A.padmin_id=B1.uid
          left join hii_warehouse C1 on A.warehouse_id1=C1.w_id
          left join hii_warehouse C2 on A.warehouse_id2=C2.w_id
        left join (select shequ_id,shequ_price,goods_id from hii_shequ_price group by goods_id,shequ_id) SP on C2.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
          left join hii_store S on A.store_id=S.id
          left join hii_goods G on A1.goods_id=G.id
          left join hii_warehouse_request WR on A.w_r_id=WR.w_r_id
          left join hii_store_request SR on A.s_r_id=SR.s_r_id
          left join hii_warehouse_out_stock WOS on A.w_out_id=WOS.w_out_id
          where A.w_out_id = $id" .$where ."
        group by A.w_out_id,A.w_out_sn,A.w_out_status,A.w_out_type,A.ctime,A.admin_id,B.nickname,B1.nickname,
        A.warehouse_id1,A.warehouse_id2,C1.w_name,C2.w_name,A.store_id,S.title,S.address,A.remark,A.g_type,A.g_nums,
        A.w_r_id,WR.w_r_sn,A.s_r_id,SR.s_r_sn,A.padmin_id,WOS.w_out_s_sn
        order by A.w_out_id desc";
        $list = $Model->query($sql);
        //print_r($list);die;
        $sql = "
        select A.w_out_id,A.w_out_sn,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,
        A1.w_out_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,A1.in_num,A1.out_num,A1.g_price,A1.remark,ifnull(L.g_price,0) as last_price,
        WRD.g_num as wrg_num,AV.value_id,AV.value_name 
         from  hii_warehouse_out A
         left join hii_warehouse_out_detail A1 on A.w_out_id=A1.w_out_id
         left join hii_goods G on A1.goods_id=G.id
         left join hii_goods_cate C on G.cate_id=C.id
        left join hii_warehouse C1 on A.warehouse_id2=C1.w_id
        left join (select shequ_id,shequ_price,goods_id from hii_shequ_price group by goods_id,shequ_id) SP on C1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
         left join hii_warehouse_request_detail WRD on A1.w_r_d_id=WRD.w_r_d_id
         left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id2=WS.w_id
         left join hii_goods_last_purchase_price_view L on A1.goods_id=L.goods_id
         left join hii_attr_value AV on A1.value_id=AV.value_id
         where A.w_out_id=$id" .$where ." order by C.id asc,A1.goods_id asc
         ";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $title = '出库验货单' .$list[0]['w_out_sn'] .'查看';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseOutView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Warehouse@WarehouseOut/view'));
    }

    public function updatewarehouse()
    {
        $w_out_id = I('w_out_id', '');
        if ($w_out_id == '') {
            $w_out_id = $_POST['w_out_id'];
        }
        if($w_out_id == ''){
            $this->error('不存在的单据ID');
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $w_out_d_id = I('w_out_d_id', '');
        if ($w_out_d_id == '') {
            $w_out_d_id = $_POST['w_out_d_id'];
        }
        if($w_out_d_id == '' || $w_out_d_id == '0'){
            $this->error('必须有商品数据');die;
        }
        $g_num = I('g_num', '');
        if ($g_num == '') {
            $g_num = $_POST['g_num'];
        }
        $in_num = I('in_num', '');
        if ($in_num == '') {
            $in_num = $_POST['in_num'];
        }
        $out_num = I('out_num', '');
        if ($out_num == '') {
            $out_num = $_POST['out_num'];
        }
        $remark_detail = I('remark_detail', '');
        if ($remark_detail == '') {
            $remark_detail = $_POST['remark_detail'];
        }
        if ($w_out_id != '') {
            $where = array();
            $where['w_out_d_id'] = array('in',$w_out_d_id);
            $field = "w_out_d_id,w_out_id,goods_id,g_num,in_num,out_num,g_price,remark,w_r_d_id,s_r_d_id,value_id";
            $DetailList = M('WarehouseOutDetail')
                ->field($field)
                ->where($where)->order('w_out_d_id desc')->select();
            if(!count($DetailList) > 0){
                $this->error('没有出库验货商品');die;
            }
            for($i = 0;$i < count($DetailList);$i++) {
                for($j = 0;$j < count($w_out_d_id);$j++){
                    if($DetailList[$i]['w_out_d_id'] == $w_out_d_id[$j]){
                        if( ($g_num[$j]-$in_num[$j]-$out_num[$j]) != 0){
                            $this->error('有货数量+缺货数量 不等于 申请数量');die;
                        }
                        $DetailList[$i]['in_num'] = $in_num[$j];
                        $DetailList[$i]['out_num'] = $out_num[$j];
                        $DetailList[$i]['remark'] = $remark_detail[$j];
                    }
                }
            }
            $where['w_out_id'] = $w_out_id;
            $Model = M('WarehouseOut');
            $data = $Model->where($where)->find();
            if(!$data){
                $this->error('没有单据');die;
            }
            $data['remark'] = $remark;
            $data['etime'] = time();
            $data['eadmin_id'] = UID;
            $Model = D('Addons://Warehouse/WarehouseOut');

            $res = $Model->saveWarehouseOut($w_out_id,$data,$DetailList,false);
            if($res>0){
                $this->success('提交成功',Cookie('__forward__'));
            }else{
                $this->error($Model->err['msg']);
            }
        }
    }

    /**
     * 仓库出库验货审核
     * @param  id 出库验货单id
     * @param pass 1 审核  2  全部拒绝
     */
    public function pass(){
        $w_out_id = I('id',0,'intval');
        $pass = I('pass',1,'intval');
        $admin_id = UID;
        $warehouse_id = $this->_warehouse_id;
        if(!$w_out_id){
            $this->error('没有单据ID');die;
        }
        if($pass != 1 && $pass != 2){
            $this->error('没有参数');die;
        }
        $warehouseOutModel = D('Addons://Warehouse/WarehouseOut');
        $info = $warehouseOutModel->check($w_out_id,$pass,$admin_id,$warehouse_id);
        if($info['status'] == 200){
            $this->success('成功');
        }else{
            $this->error($info['msg']);
        }
    }

    /**
     * 弃用
     */
    public function pass_q()
    {
        $w_out_id = I('id', '');
        if ($w_out_id == '') {
            $w_out_id = $_POST['id'];
        }
        if ($w_out_id == '') {
            $this->error('没有单据ID');die;
        }
        $pass = I('pass', '');
        if ($pass == '') {
            $pass = $_POST['pass'];
        }
        if ($pass == '') {
            $this->error('没有参数');die;
        }
        if ($w_out_id != '') {
            $where = array();
            $where['w_out_id'] = $w_out_id;
            $field = "w_out_d_id,w_out_id,goods_id,g_num,in_num,out_num,g_price,remark,w_r_d_id,s_r_d_id,value_id";
            $DetailList = M('WarehouseOutDetail')
                ->field($field)
                ->where($where)->order('w_out_d_id asc')->select();
            if(!count($DetailList)>0){
                $this->error('没有申请商品');die;
            }
            $pcount1 = 0;
            $nums1 = 0;
            $nums2 = 0;
            $nums3 = 0;
            $tmp = array();
            $where['w_out_id'] = $w_out_id;
            $where['w_out_status'] = 0;
            $Model = M('WarehouseOut');
            $dataMain = $Model->where($where)->find();
            if(!$dataMain){
                $this->error('单据不存在或者已经审核。');die;
            }
            $dataMain['ptime'] = time();
            $dataMain['padmin_id'] = UID;
            $dataMain['w_out_status'] = $pass;

            if ($pass == '1') {
                //判断库存够不够
                $field = "goods_id,max(g_price) as g_price,sum(in_num) as g_num,value_id";
                $Mdetail = M('WarehouseOutDetail');
                $DetailListSUM = $Mdetail
                    ->field($field)
                    ->where($where)
                    ->order('w_out_d_id asc')
                    ->group('goods_id,value_id')
                    ->select();

                foreach($DetailListSUM as $k=>$v) {
                    //判断库存表库存表
                    $where_stock = array();
                    $where_stock['goods_id'] = $DetailListSUM[$k]['goods_id'];
                    $where_stock['w_id'] = $this->_warehouse_id;
                    $where_stock['value_id'] = $DetailListSUM[$k]['value_id'];
                    $WarehouseStockList = M('WarehouseStock')->where($where_stock)->find();
                    if(is_array($WarehouseStockList) && count($WarehouseStockList)>0){
                        if( ($WarehouseStockList['num'] - $DetailListSUM[$k]['g_num']) < 0){
                            $this->error( '该商品ID:'.$DetailListSUM[$k]['goods_id'].'库存小于出库数量');die;
                        }
                    }else{
                        if( ($DetailListSUM[$k]['g_num']) > 0) {
                            $this->error('该商品ID:' . $DetailListSUM[$k]['goods_id'] . '没有库存');die;
                        }
                    }
                }

                $DetailListWarehouseOutStock = array();//出库部分数组
                foreach($DetailList as $k=>$v){
                    /*if($DetailList[$k]['g_price'] == 0 && $pass == '1'){
                        $this->error('没有商品出库验货价');
                    }*/
                    if( ($DetailList[$k]['g_num']-$DetailList[$k]['in_num']-$DetailList[$k]['out_num']) != 0){
                        $this->error('验收数量+退货数量 不等于 申请数量');die;
                    }
                   /*  //检查库存表有没有此商品库存,库存够不够
                    $where_stock = array();
                    $where_stock['goods_id'] = $DetailList[$k]['goods_id'];
                    $where_stock['w_id'] = $this->_warehouse_id;
                    $WarehouseStockList = M('WarehouseStock')->where($where_stock)->find();
                    if(is_array($WarehouseStockList) && count($WarehouseStockList)>0){
                        if( ($WarehouseStockList['num'] - $DetailList[$k]['in_num']) < 0){
                            $this->error( '该商品ID:'.$DetailList[$k]['goods_id'].'库存小于出库数量');die;
                        }
                    }else{
                        if($DetailList[$k]['in_num'] != '' && $DetailList[$k]['in_num'] > 0) {
                            $this->error('该商品ID:' . $DetailList[$k]['goods_id'] . '没有库存');die;
                        }
                    } */
                    //出库部分
                    $tmp['goods_id'] = $DetailList[$k]['goods_id'];
                    $tmp['g_num'] = $DetailList[$k]['in_num'];
                    $tmp['g_price'] = $DetailList[$k]['g_price'];
                    $tmp['remark'] = $DetailList[$k]['remark'];
                    $tmp['w_out_d_id'] = $DetailList[$k]['w_out_d_id'];
                    $tmp['value_id'] = $DetailList[$k]['value_id'];
                    if($DetailList[$k]['in_num'] > 0) {
                        $DetailListWarehouseOutStock[] = $tmp;
                        $pcount1++;
                    }
                    //拒绝部分
                    if($DetailList[$k]['out_num'] > 0) {
                        if ($DetailList[$k]['w_r_d_id'] > 0) {
                            //拒绝仓库调拨申请
                            $ModelRequestDetail = M('WarehouseRequestDetail');
                            $sql = "Update hii_warehouse_request_detail set is_pass=1,pass_num=" . $DetailList[$k]['g_num'] . " where w_r_d_id=" . $DetailList[$k]['w_r_d_id'];
                            $res = $ModelRequestDetail->execute($sql);
                            $error = $ModelRequestDetail->getError();
                            if ($error != '') {
                                $this->error($error ? $error : '找不到要拒绝的数据1！' . $error);die;
                            }
                        }else {
                            if ($DetailList[$k]['s_r_d_id'] > 0) {
                                //拒绝店铺调拨申请
                                $ModelRequestDetail = M('StoreRequestDetail');
                                $sql = "Update hii_store_request_detail set is_pass=1,pass_num=" . $DetailList[$k]['g_num'] . " where s_r_d_id=" . $DetailList[$k]['s_r_d_id'];
                                $res = $ModelRequestDetail->execute($sql);
                                $error = $ModelRequestDetail->getError();
                                if ($error != '') {
                                    $this->error($error ? $error : '找不到要拒绝的数据2！' . $error);die;
                                }
                            }else{
                                if ($dataMain['w_out_type'] == 3) {
                                    //直接发货
                                }else{
                                    //退货报损
                                    $this->error('暂不支持：拒绝退货报损1！');die;
                                }
                            }
                        }
                    }

                    $nums1 += $DetailList[$k]['in_num'];
                    $nums2 += $DetailList[$k]['out_num'];
                    $nums3 += $DetailList[$k]['g_num'];
                }
                if (count($DetailListWarehouseOutStock) > 0) {
                    //审核并新增出库单
                    $w_out_s_id = 0;
                    $new_no = get_new_order_no('CK','hii_warehouse_out_stock','w_out_s_sn');
                    $data = array();
                    $data['w_out_s_sn'] = $new_no;
                    $data['w_out_s_status'] = 0;
                    if($dataMain['w_out_type'] == 0){
                        //仓库调拨
                        $data['w_out_s_type'] = 0;
                    }else{
                        if($dataMain['w_out_type'] == 1){
                            //门店申请
                            $data['w_out_s_type'] = 1;
                        }else{
                            if($dataMain['w_out_type'] == 2){
                                //退货报损
                                $data['w_out_s_type'] = 4;
                            }else{
                                if($dataMain['w_out_type'] == 3){
                                    //直接发货
                                    $data['w_out_s_type'] = 5;
                                }else{
                                    //其它
                                    $data['w_out_s_type'] = 4;
                                }
                            }
                        }
                    }
                    //$data['w_out_s_type'] = $dataMain['w_out_type'];
                    $data['w_out_id'] = $w_out_id;
                    $data['i_id'] = 0;
                    $data['ctime'] = time();
                    $data['admin_id'] = UID;
                    $data['etime'] = 0;
                    $data['eadmin_id'] = 0;
                    $data['ptime'] = 0;
                    $data['padmin_id'] = 0;
                    $data['store_id'] = $dataMain['store_id'];
                    $data['warehouse_id1'] = $dataMain['warehouse_id1'];
                    $data['warehouse_id2'] = $dataMain['warehouse_id2'];
                    $data['remark'] = $dataMain['remark'];
                    $data['g_type'] = $pcount1;
                    $data['g_nums'] = $nums1;
                    $Model1 = D('Addons://Warehouse/WarehouseOutStock');
                    $res1 = $Model1->saveWarehouseOutStock($w_out_s_id, $data, $DetailListWarehouseOutStock,false);

                }else{
                    $this->error('出库验货失败，没有出库验货数量。' .$Model->err['msg']);die;
                }
                if($res1>0){
                    if($dataMain['w_out_type'] == 1){
                        $ModelStoreRequest = D('Addons://Warehouse/StoreRequest');
                        $res2 = $ModelStoreRequest->saveWarehouseOutToStoreRequest($w_out_id);
                        if($res2 > 0){
                            //$this->success('更改门店申请成功');
                        }else{
                            $this->error('更改门店申请失败1' .$ModelStoreRequest->err['msg']);die;
                        }
                    }
                    $Model = D('Addons://Warehouse/WarehouseOut');
                    $res = $Model->saveWarehouseOut($w_out_id,$dataMain,$DetailList,false);
                    if($res>0){
                        $this->pass1($res1,1);
                        $this->success('出库成功');
                    }else{
                        $this->error($Model->err['msg']);
                    }
                }else{
                    $this->error($Model1->err['msg']);
                }
            }else{
                if ($pass == '2') {
                    $DetailListWarehouseOutStock = array();//出库部分数组
                    foreach($DetailList as $k=>$v){
                        /*if( ($DetailList[$k]['g_num']-$DetailList[$k]['in_num']-$DetailList[$k]['out_num']) != 0){
                            $this->error('验收数量+退货数量 不等于 申请数量');
                        }*/
                        //出库部分
                        $tmp['goods_id'] = $DetailList[$k]['goods_id'];
                        $tmp['g_num'] = $DetailList[$k]['g_num'];
                        $tmp['remark'] = $DetailList[$k]['remark'];
                        $tmp['g_price'] = $DetailList[$k]['g_price'];
                        $tmp['w_out_d_id'] = $DetailList[$k]['w_out_d_id'];
                        $tmp['value_id'] = $DetailList[$k]['value_id'];
                        if($DetailList[$k]['in_num'] > 0) {
                            $DetailListWarehouseOutStock[] = $tmp;
                            $pcount1++;
                        }
                        //拒绝部分
                        //if($DetailList[$k]['out_num'] > 0) {
                            if ($DetailList[$k]['w_r_d_id'] > 0) {
                                //拒绝仓库调拨申请
                                $ModelRequestDetail = M('WarehouseRequestDetail');
                                $sql = "Update hii_warehouse_request_detail set is_pass=1,pass_num=" . $DetailList[$k]['g_num'] . " where w_r_d_id=" . $DetailList[$k]['w_r_d_id'];
                                $res = $ModelRequestDetail->execute($sql);
                                $error = $ModelRequestDetail->getError();
                                if ($error != '') {
                                    $this->error($error ? $error : '找不到要拒绝的数据2！' . $error);die;
                                }
                            }else {
                                if ($DetailList[$k]['s_r_d_id'] > 0) {
                                    //拒绝店铺调拨申请
                                    $ModelRequestDetail = M('StoreRequestDetail');
                                    $sql = "Update hii_store_request_detail set is_pass=1,pass_num=" . $DetailList[$k]['g_num'] . " where s_r_d_id=" . $DetailList[$k]['s_r_d_id'];
                                    $res = $ModelRequestDetail->execute($sql);
                                    $error = $ModelRequestDetail->getError();
                                    if ($error != '') {
                                        $this->error($error ? $error : '找不到要拒绝的数据2！' . $error);die;
                                    }
                                }else{
                                    if ($dataMain['w_out_type'] == 3) {
                                        //直接发货
                                    }else{
                                        $this->error('暂不支持：拒绝退货报损2！');die;
                                    }
                                }
                            }
                        //}
                        $nums1 += $DetailList[$k]['in_num'];
                        $nums2 += $DetailList[$k]['out_num'];
                        $nums3 += $DetailList[$k]['g_num'];
                    }
                    if($dataMain['w_out_type'] == 0){
                        $ModelStoreRequest = D('Addons://Warehouse/WarehouseRequest');
                        $res1 = $ModelStoreRequest->saveWarehouseOutToWarehouseRequest($w_out_id);
                        if($res1 > 0){
                            //$this->success('更改门店申请成功');
                        }else{
                            $this->error('更改仓库申请失败1' .$ModelStoreRequest->err['msg']);die;
                        }
                    }else {
                        if($dataMain['w_out_type'] == 1) {
                            $ModelStoreRequest = D('Addons://Warehouse/StoreRequest');
                            $res1 = $ModelStoreRequest->saveWarehouseOutToStoreRequest($w_out_id);
                            if ($res1 > 0) {
                                //$this->success('更改门店申请成功');
                            } else {
                                $this->error('更改门店申请失败1' . $ModelStoreRequest->err['msg']);die;
                            }
                        }else{
                            if($dataMain['w_out_type'] == 3) {
                                //拒绝直接发货
                            }else{
                                $this->error('暂时不支持拒绝退货报损');die;
                            }
                        }
                    }
                    $Model2 = D('Addons://Warehouse/WarehouseOut');
                    $res2 = $Model2->saveWarehouseOut($w_out_id,$dataMain,$DetailList,false);
                    if (!$res2) {
                        $error = $Model2->getError();
                        $this->error($error ? $error : '审核错误！' .$error);die;
                    } else {
                        $this->success('拒绝成功');
                    }
                }else{
                    $this->error('错误参数');die;
                }
            }
        }
    }

    public function pass1($id,$pass)
    {
        if ($id == "" || $id == "0"){
            $this->error("没有id");
        }

        $where = " w_out_s_id = " .$id;
        $where1 = " and w_out_s_status = 0";
        $where1 .= " and warehouse_id2 = " .$this->_warehouse_id;


        $Model = M('WarehouseOutStock');
        $dataMain1 = $Model->where($where .$where1)->select();
        if(is_array($dataMain1) && count($dataMain1)>0){
            $dataMain = $dataMain1[0];
        }else{
            $this->error("没有出库单或者出库单已处理，不能再次审核");die;
        }

        if($pass == 0 || $pass == ""){
            $this->error("没有参数");die;
        }

        $dataMain['ptime'] = time();
        $dataMain['padmin_id'] = UID;
        $dataMain['w_out_s_status'] = $pass;


        $field = "w_out_s_d_id,w_out_s_id,goods_id,g_num,g_price,remark,w_out_d_id,value_id";
        $DetailList = M('WarehouseOutStockDetail')
            ->field($field)
            ->where($where)->order('w_out_s_d_id asc')->select();
        if(!count($DetailList)>0){
            $this->error("出库单没有商品");die;
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
                    $this->error('该商品ID:'.$DetailList[$k]['goods_id'].'没有库存');die;
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
                if ($dataMain['w_out_s_type'] == 3) {
                    //出库批次:`etype`最后操作类型:0.销售出库，1.报损出库，2.盘亏出库',
                    //出库单：`w_out_s_type` int(1) DEFAULT '0' COMMENT '来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它',【只有盘亏出库才写入出库批次表】
                    $Model = M('WarehouseInout');
                    $WarehouseInoutData = $Model->where('goods_id = ' . $DetailList[$k]['goods_id'] . ' and num>0 and warehouse_id = ' . $dataMain['warehouse_id2'])->order('ctime asc')->select();
                    //修改入库批次
                    $g_num = $DetailList[$k]['g_num'];
                    $WarehouseInoutDataListTemp = array();
                    foreach ($WarehouseInoutData as $k1 => $v1) {
                        $g_num = $g_num - $WarehouseInoutData[$k1]['num'];
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
                        if ($g_num < 0) {//判断库存批次 的 现有数量  是不是 大于 盘亏出库数量，大于盘亏数量，则不继续循环，小于的话继续循环
                            $DetailListWarehouseInOut['num'] = $WarehouseInoutData[$k1]['num'] - $g_num;
                            $WarehouseInoutDataListTemp[] = $DetailListWarehouseInOut;
                            break;
                        } else {
                            $DetailListWarehouseInOut['num'] = 0;
                            $WarehouseInoutDataListTemp[] = $DetailListWarehouseInOut;
                        }
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
                            $this->error('该商品ID:'.$DetailList[$k]['goods_id'].'盘亏数量' .$DetailList[$k]['g_num']. '大于入库批次的目前库存数量总和，请检查。');die;
                            //$this->response(999, '该商品ID:'.$DetailList[$k]['goods_id'].'盘亏数量' .$DetailList[$k]['g_num']. '大于入库批次的目前库存数量总和，请检查。');die;
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
                        $this->error( '该商品ID:'.$DetailListSUM[$k]['goods_id'].'库存小于出库数量');die;
                    }else{
                        $WarehouseStockList['num'] = $WarehouseStockList['num'] - $DetailListSUM[$k]['g_num'];
                        $WarehouseStockList1[] = $WarehouseStockList;
                    }
                }else{
                    $this->error('该商品ID:'.$DetailListSUM[$k]['goods_id'].'没有库存');die;
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
                                $ModelMsg = D('Erp/MessageWarn');
                                $msg = $ModelMsg->pushMessageWarn(UID  ,$dataWI['warehouse_id'] , 0 ,0  , $dataWI ,6);
                                //$this->success('库存出库成功');
                            } else {
                                $this->error('该商品ID:' . $DetailList[$k]['goods_id'] . ',更改库存失败');die;
                            }
                        }
                        return true;
                    }else{
                        $this->error('出库审核失败:'.$Model2->err['msg']);die;
                    }
                }else{
                    $this->error( '新增入库验收单失败:'.$Model1->err['msg']);die;
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
                            $res111 = $ModelStoreRequest->saveWarehouseOutToStoreRequest($dataMain['w_out_id'],false);
                            if($res111 > 0){
                                //更改库存
                                foreach($WarehouseStockList1 as $k=>$v) {
                                    $ModelStock = D('Addons://Warehouse/WarehouseStock');
                                    $resStock = $ModelStock->saveWarehouseStock($WarehouseStockList1[$k]['id'], $WarehouseStockList1[$k],false);
                                    if ($resStock > 0) {
                                        $ModelMsg = D('Erp/MessageWarn');
                                        $msg = $ModelMsg->pushMessageWarn(UID  ,0 , $dataWI['store_id2'] ,0  , $dataWI ,4);
                                        //$this->success('库存出库成功');
                                    } else {
                                        $this->error('该商品ID:' . $DetailList[$k]['goods_id'] . ',更改库存失败');die;
                                    }
                                }
                                return true;
                            }else{
                                $this->error('更改门店申请失败1' .$ModelStoreRequest->err['msg']);die;
                            }
                        }else{
                            $this->error('出库审核失败:'.$Model2->err['msg']);die;
                        }
                    }else{
                        $this->error('出库审核失败:'.$Model1->err['msg']);die;
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
                                            //$this->success('库存出库成功');
                                        } else {
                                            $this->error( '该商品ID:' . $DetailList[$k]['goods_id'] . ',更改库存失败');die;
                                        }
                                    }
                                    return true;
                                }else{
                                    $this->error('出库审核失败:'.$Model2->err['msg']);die;
                                }
                            } else {
                                $this->error('修改入库批次失败1:'.$Model1->err['msg']);die;
                            }
                        }else{
                            $this->error('修改入库批次失败2:');die;
                        }
                    }else{
                        $this->error('暂时不支持其它类型出库');die;
                    }
                }

            }
        }else{
            if ($pass == '2') {
                $this->error('错误参数2');die;
            }else{
                $this->error('错误参数' .$pass);die;
            }
        }
    }
}
