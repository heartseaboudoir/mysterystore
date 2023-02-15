<?php
namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehouseInController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '入库验收单管理';
        //时间范围默认30天
        $s_date = I('s_date');
        $e_date = I('e_date');
        $showhide = I('showhide');
        $bysupply = I('bysupply');
        $supply_id = I('supply_id');
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
            $whereshowhide = " and A.w_in_status = 0";
            $this->assign('thisshowhide', 0);
            $this->assign('showhide', 1);
        }else{
            $this->assign('thisshowhide', 1);
            $this->assign('showhide', 0);
        }
        if ($supply_id != "" && $supply_id != 0 ) {
            //显示隐藏已处理单据
            $wheresupply = " and A.supply_id = " .$supply_id;
            $this->assign('supply_id', $supply_id);
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

        $where = " and A.warehouse_id = " .$this->_warehouse_id;

        $title = $s_date. '>>>' .$e_date. '入库验收单';

        $Model = M('WarehouseIn');

        $sql = "select A.w_in_id,A.w_in_sn,A.w_in_status,A.w_in_type,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "A.p_id,P.p_sn,A.p_out_id,PO.p_o_sn,WIS.w_in_s_sn,A.s_out_s_id,A.w_out_s_id,WOS.w_out_s_sn,A.o_out_id,A.padmin_id,B1.nickname as pnickname,A.s_back_id,SB.s_back_sn,";
        $sql .= "sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts,sum(A1.g_num) as p_g_num,sum(A1.in_num) as p_in_num,sum(A1.out_num) as p_out_num,ST.title as store_name,C2.w_name as w2_name";
        $sql .= " from  hii_warehouse_in A";
        $sql .= " left join hii_warehouse_in_detail A1 on A.w_in_id=A1.w_in_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_member B1 on A.padmin_id=B1.uid";
        $sql .= " left join hii_warehouse C2 on C2.w_id=A.warehouse_id2";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_supply S on A.supply_id=S.s_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " left join hii_purchase P on A.p_id=P.p_id";
        $sql .= " left join hii_store_back SB on A.s_back_id=SB.s_back_id";
        $sql .= " left join hii_purchase_out PO on A.p_out_id=PO.p_o_id";
        $sql .= " left join hii_warehouse_in_stock WIS on A.w_in_id=WIS.w_in_id";
        $sql .= " left join hii_warehouse_out_stock WOS on A.w_out_s_id=WOS.w_out_s_id";
        $sql .= " left join hii_store ST on ST.id=A.store_id";
        $sql .= " where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where .$whereshowhide .$wheresupply .$wheregoodsname;
        if(!IS_ROOT){
            //$sql .= " and A.admin_id=" .UID;
        }
        $sql .= " group by A.w_in_id,A.w_in_sn,A.w_in_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,";
        $sql .= "A.remark,A.g_type,A.g_nums,A.p_id,P.p_sn,A.padmin_id,B1.nickname,A.s_back_id,SB.s_back_sn";
        if ($bysupply != "" && $bysupply != 0 ) {
            $this->assign('bysupply', 0);
            $this->assign('thisbysupply', 1);
            //按照供应商，再按照单据创建时间排序
            $sql .= " order by A.supply_id asc,A.w_in_id desc";
        }else {
            $this->assign('bysupply', 1);
            $this->assign('thisbysupply', 0);
            $sql .= " order by A.w_in_id desc";
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
            $printfile = $printmodel->pushWarehouseInList($data,$title,$fname);
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
        $Page->parameter["bysupply"] = $bysupply;
        $Page->parameter["supply_id"] = $supply_id;
        $Page->parameter["goods_name"] = $goods_name;
        $show= $Page->show();// 分页显示输出﻿

        $shequ = $_SESSION['can_shequs'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->where($whereSQ)->select();
        $this->assign('supply', $SupplyData);

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Warehouse@WarehouseIn/index'));
    }

    public function view()
    {
        $this->meta_title = '入库验收单查看';
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

        $Model = M('WarehouseIn');

        $sql = "select A.w_in_id,A.w_in_sn,A.w_in_status,A.w_in_type,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "A.p_id,P.p_sn,A.p_out_id,PO.p_o_sn,WIS.w_in_s_sn,A.s_out_s_id,A.w_out_s_id,WOS.w_out_s_sn,A.o_out_id,A.padmin_id,B1.nickname as pnickname,A.s_back_id,SB.s_back_sn,";
        $sql .= "sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts,sum(A1.g_num) as p_g_num,sum(A1.in_num) as p_in_num,sum(A1.out_num) as p_out_num";
        $sql .= " from  hii_warehouse_in A";
        $sql .= " left join hii_warehouse_in_detail A1 on A.w_in_id=A1.w_in_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_member B1 on A.padmin_id=B1.uid";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id";
        $sql .= " left join hii_supply S on A.supply_id=S.s_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_purchase P on A.p_id=P.p_id";
        $sql .= " left join hii_store_back SB on A.s_back_id=SB.s_back_id";
        $sql .= " left join hii_purchase_out PO on A.p_out_id=PO.p_o_id";
        $sql .= " left join hii_warehouse_in_stock WIS on A.w_in_id=WIS.w_in_id";
        $sql .= " left join hii_warehouse_out_stock WOS on A.w_out_s_id=WOS.w_out_s_id";
        $sql .= " where A.w_in_id=$id" .$where;
        if(!IS_ROOT){
            //$sql .= " and A.admin_id=" .UID;
        }
        $sql .= " group by A.w_in_id,A.w_in_sn,A.w_in_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.supply_id,S.s_name,";
        $sql .= "A.remark,A.g_type,A.g_nums,A.p_id,P.p_sn,A.padmin_id,B1.nickname,A.s_back_id,SB.s_back_sn";
        $sql .= " order by A.w_in_id desc";
        
        $list = $Model->query($sql);

        $sql = "select A.w_in_id,A.w_in_sn,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,";
        $sql .= "A1.w_in_d_id,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,G.expired_days,A1.g_num,A1.in_num,A1.out_num,A1.g_price,A1.remark";
        $sql .= ",(case when G.expired_days>0 then FROM_UNIXTIME(A1.endtime-G.expired_days*86400,'%Y-%m-%d') else FROM_UNIXTIME(A1.endtime-60*86400,'%Y-%m-%d') end) as startime";
        $sql .= ",FROM_UNIXTIME(A1.endtime,'%Y-%m-%d') as endtime,ifnull(L.g_price,0) as last_price,";
        $sql .= "PD.g_num as pg_num,PD.b_n_num,PD.b_num,PD.b_price,AV.value_id,AV.value_name ";
        $sql .= " from  hii_warehouse_in A";
        $sql .= " left join hii_warehouse_in_detail A1 on A.w_in_id=A1.w_in_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id
        left join hii_warehouse C1 on A.warehouse_id=C1.w_id
        left join hii_shequ_price SP on C1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join hii_purchase_detail PD on A1.p_d_id=PD.p_d_id";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id";
        $sql .= " left join hii_goods_last_purchase_price_view L on A1.goods_id=L.goods_id";
        $sql .= " left join hii_attr_value AV on AV.value_id=A1.value_id";
        $sql .= " where A.w_in_id=$id" .$where;
        $sql .= " order by A1.goods_id asc";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $title = '入库验收单' .$list[0]['w_in_sn'] .'查看';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseInView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Warehouse@WarehouseIn/view'));
    }

    public function updatewarehouse()
    {
        $w_in_id = I('w_in_id', '');
        if ($w_in_id == '') {
            $w_in_id = $_POST['w_in_id'];
        }
        if($w_in_id == ''){
            $this->error('不存在的单据ID');die;
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $w_in_d_id = I('w_in_d_id', '');
        if ($w_in_d_id == '') {
            $w_in_d_id = $_POST['w_in_d_id'];
        }
        if($w_in_d_id == '' || $w_in_d_id == '0'){
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
        $endtime = I('endtime', '');
        if ($endtime == '') {
            $endtime = $_POST['endtime'];
        }
        if ($w_in_id != '') {
            $where = array();
            $where['w_in_d_id'] = array('in',$w_in_d_id);
            $field = "w_in_d_id,w_in_id,goods_id,g_num,in_num,out_num,g_price,remark,p_d_id,value_id";
            $where['w_in_id'] = $w_in_id;
            $Model = M('WarehouseIn');
            $data = $Model->where($where)->find();
            $DetailList = M('WarehouseInDetail')
                ->field($field)
                ->where($where)->order('w_in_d_id asc')->select();
            if(!count($DetailList) > 0){
                $this->error('没有入库验收商品');die;
            }
            for($i = 0;$i < count($DetailList);$i++) {
                for($j = 0;$j < count($w_in_d_id);$j++){
                    if($DetailList[$i]['w_in_d_id'] == $w_in_d_id[$j]){
                        if($data['w_in_type'] != 0) {
                            if (($g_num[$j] - $in_num[$j] - $out_num[$j]) != 0) {
                                $this->error('验收数量+退货数量 不等于 申请数量');die;
                            }
                        }else{
                            if($in_num[$j] > $g_num[$j]){
                                //采购供应商送了赠品，送货数量大于采购数量
                                //重新计算采购价，均摊.新单价=采购数*采购价/实际送货数
                                $newPrice = (float)$g_num[$j]*(float)$DetailList[$i]['g_price']/(float)$in_num[$j];    
                                $DetailList[$i]['g_price'] = round($newPrice,2);
                            }else{
                                if (($g_num[$j] - $in_num[$j] - $out_num[$j]) != 0) {
                                    $this->error('验收数量+退货数量 不等于 申请数量');die;
                                }
                            }
                        }
                        if($data['w_in_type'] == 0) {
                            if ($endtime[$j] == "") {
                                $this->error('没有过期时间');die;
                            }
                        }
                        $DetailList[$i]['in_num'] = $in_num[$j];
                        $DetailList[$i]['out_num'] = $out_num[$j];
                        $DetailList[$i]['endtime'] = strtotime($endtime[$j]);
                    }
                }
            }
            if(!$data){
                $this->error('没有单据');die;
            }
            $data['remark'] = $remark;
            $data['etime'] = time();
            $data['eadmin_id'] = UID;
            $Model = D('Addons://Warehouse/WarehouseIn');

            $res = $Model->saveWarehouseIn($w_in_id,$data,$DetailList,false);
            if($res>0){
                $this->success('提交成功',Cookie('__forward__'));
            }else{
                $this->error($Model->err['msg']);
            }
        }
    }

    /**
     * 入库验收审核
     * id 验收单
     * pass  1 审核入库  2  全部退货
     */
    public function pass(){
        $w_in_id = I('id',0,'intval');
        $pass = I('pass',0,'intval');
        $warehouse_id = $this->_warehouse_id;
        $admin_id = UID;
        if(!$w_in_id){
            $this->error('没有单据ID');
        }
        if($pass != 1 && $pass != 2){
            $this->error('没有参数');
        }
        $warehouseInModel = D('Addons://Warehouse/WarehouseIn');
        $info = $warehouseInModel->check($w_in_id,$admin_id, $pass, $warehouse_id);
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
        $w_in_id = I('id', '');
        if ($w_in_id == '') {
            $w_in_id = $_POST['id'];
        }
        if ($w_in_id == '') {
            $this->error('没有单据ID');
        }
        $pass = I('pass', '');
        if ($pass == '') {
            $pass = $_POST['pass'];
        }
        if ($pass == '') {
            $this->error('没有参数');
        }
        if ($w_in_id != '') {
            $where = array();
            $where['w_in_id'] = $w_in_id;
            $field = "w_in_d_id,w_in_id,goods_id,g_num,in_num,out_num,g_price,remark,endtime,p_d_id,value_id";
            $DetailList = M('WarehouseInDetail')
                ->field($field)
                ->where($where)->order('w_in_d_id asc')->select();
            if(!count($DetailList)>0){
                $this->error('没有申请商品');
            }
            $where['w_in_id'] = $w_in_id;
            $where['w_in_status'] = 0;
            $Model = M('WarehouseIn');
            $dataMain = $Model->where($where)->find();
            if(!$dataMain){
                $this->error('单据不存在或者已经审核。');
            }
            $pcount1 = 0;
            $pcount2 = 0;
            $nums1 = 0;
            $nums2 = 0;
            $nums3 = 0;
            $tmp = array();
            $DetailListWarehouseInStock = array();//入库部分数组
            $DetailListPurchaseOut = array();//退货部分数组
            $DetailListPurchaseOutAll = array();//全部退货部分数组
            foreach($DetailList as $k=>$v){
                if($DetailList[$k]['g_price'] == 0 && $pass == '1' && $dataMain['w_in_type'] == 0){
                    $this->error('没有商品入库验收价');
                }
                if($dataMain['w_in_type'] == 0) {
                    if($DetailList[$k]['endtime'] == 0 && $pass == '1'){
                        $this->error('没有商品过期时间');
                    }
                }
                if($dataMain['w_in_type'] != 0) {
                    if (($DetailList[$k]['g_num'] - $DetailList[$k]['in_num'] - $DetailList[$k]['out_num']) != 0) {
                        $this->error('验收数量+退货数量 不等于 申请数量');
                    }
                }else{
                    if ( $DetailList[$k]['g_num'] > ($DetailList[$k]['in_num'] + $DetailList[$k]['out_num']) ) {
                        $this->error('验收数量+退货数量 小于 申请数量');
                    }
                }
                //入库部分
                $tmp['goods_id'] = $DetailList[$k]['goods_id'];
                $tmp['g_num'] = $DetailList[$k]['in_num'];
                $tmp['g_price'] = $DetailList[$k]['g_price'];
                $tmp['remark'] = $DetailList[$k]['remark'];
                $tmp['endtime'] = $DetailList[$k]['endtime'];
                $tmp['w_in_d_id'] = $DetailList[$k]['w_in_d_id'];
                $tmp['value_id'] = $DetailList[$k]['value_id'];
                if($DetailList[$k]['in_num'] > 0) {
                    $DetailListWarehouseInStock[] = $tmp;
                    $pcount1++;
                }
                //退货部分
                $tmp1['goods_id'] = $DetailList[$k]['goods_id'];
                $tmp1['g_num'] = $DetailList[$k]['out_num'];
                $tmp1['g_price'] = $DetailList[$k]['g_price'];
                $tmp1['remark'] = $DetailList[$k]['remark'];
                $tmp1['w_in_d_id'] = $DetailList[$k]['w_in_d_id'];
                $tmp1['value_id'] = $DetailList[$k]['value_id'];
                if($DetailList[$k]['out_num'] > 0) {
                    $DetailListPurchaseOut[] = $tmp1;
                    $pcount2++;
                }
                //全部退货
                $tmp2['goods_id'] = $DetailList[$k]['goods_id'];
                $tmp2['g_num'] = $DetailList[$k]['g_num'];
                $tmp2['g_price'] = $DetailList[$k]['g_price'];
                $tmp['remark'] = $DetailList[$k]['remark'];
                $tmp2['w_in_d_id'] = $DetailList[$k]['w_in_d_id'];
                $tmp2['value_id'] = $DetailList[$k]['value_id'];
                $DetailListPurchaseOutAll[] = $tmp2;

                $nums1 += $DetailList[$k]['in_num'];
                $nums2 += $DetailList[$k]['out_num'];
                $nums3 += $DetailList[$k]['g_num'];
            }
            $dataMain['ptime'] = time();
            $dataMain['padmin_id'] = UID;
            $dataMain['w_in_status'] = $pass;
            $WarehouseModel = M('Warehouse');
            $w = $WarehouseModel->where('w_id=' .$this->_warehouse_id)->select();
            $shequ_id = $w[0]['shequ_id'];

            if ($pass == '1') {
                if($dataMain['w_in_type'] == 0) {
                    //采购入库验收审核，拒绝的生成采购退货单
                    if (count($DetailListWarehouseInStock) > 0) {
                        //审核并新增入库单
                        $w_in_s_id = 0;
                        $new_no = get_new_order_no('RK', 'hii_warehouse_in_stock', 'w_in_s_sn');
                        $data = array();
                        $data['w_in_s_sn'] = $new_no;
                        $data['w_in_s_status'] = 0;
                        $data['w_in_s_type'] = $dataMain['w_in_type'];
                        $data['w_in_id'] = $w_in_id;
                        $data['i_id'] = 0;
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['supply_id'] = $dataMain['supply_id'];
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount1;
                        $data['g_nums'] = $nums1;
                        $Model1 = D('Addons://Warehouse/WarehouseInStock');
                        $res1 = $Model1->saveWarehouseInStock($w_in_s_id, $data, $DetailListWarehouseInStock,false);
                    } else {
                        $this->error('入库失败，没有入库验收数量。' . $Model->err['msg']);
                    }
                    if (count($DetailListPurchaseOut) > 0) {
                        //退货并新增采购退货单
                        $p_o_id = 0;
                        $new_no1 = get_new_order_no('CT', 'hii_purchase_out', 'p_o_sn');
                        $data = array();
                        $data['p_o_sn'] = $new_no1;
                        $data['p_o_status'] = 0;
                        $data['w_in_id'] = $w_in_id;
                        $data['p_id'] = $dataMain['p_id'];
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['supply_id'] = $dataMain['supply_id'];
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount2;
                        $data['g_nums'] = $nums2;
                        $Model = D('Addons://Purchase/PurchaseOut');
                        $res2 = $Model->savePurchaseOut($p_o_id, $data, $DetailListPurchaseOut,false);
                        if ($res2 > 0) {
                            $ModelMsg = D('Erp/MessageWarn');
                            $msg = $ModelMsg->pushMessageWarn(UID  ,0 , 0 ,$shequ_id  , $data ,9);
                            $dataMain['w_in_status'] = 3;
                            $dataMain['p_out_id'] = $res2;
                            //$this->success('退货成功');
                        } else {
                            $this->error('退货失败1' . $Model->err['msg']);
                        }
                    }
                    if ($res1 > 0) {
                        $Model = D('Addons://Warehouse/WarehouseIn');
                        $res = $Model->saveWarehouseIn($w_in_id, $dataMain, $DetailList,false);
                        if ($res > 0) {
                            $this->pass1($res1,1);
                            $this->success('入库成功');
                        } else {
                            $this->error($Model->err['msg']);
                        }
                    } else {
                        $this->error($Model1->err['msg']);
                    }
                }
                if($dataMain['w_in_type'] == 1) {
                    $this->error('入库失败，店铺退货还没做。');
                }
                if($dataMain['w_in_type'] == 2) {
                    //仓库调拨入库验收审核，拒绝的生成仓库报损单
                    if (count($DetailListWarehouseInStock) > 0) {
                        //审核并新增入库单
                        $w_in_s_id = 0;
                        $new_no = get_new_order_no('RK', 'hii_warehouse_in_stock', 'w_in_s_sn');
                        $data = array();
                        $data['w_in_s_sn'] = $new_no;
                        $data['w_in_s_status'] = 0;
                        $data['w_in_s_type'] = $dataMain['w_in_type'];
                        $data['w_in_id'] = $w_in_id;
                        $data['i_id'] = 0;
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['supply_id'] = $dataMain['supply_id'];
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount1;
                        $data['g_nums'] = $nums1;
                        $Model1 = D('Addons://Warehouse/WarehouseInStock');
                        $res1 = $Model1->saveWarehouseInStock($w_in_s_id, $data, $DetailListWarehouseInStock,false);
                    } else {
                        $this->error('入库失败，没有入库验收数量。' . $Model->err['msg']);
                    }
                    if (count($DetailListPurchaseOut) > 0) {
                        //退货并新增仓库报损单
                        $w_o_out_id = 0;
                        $new_no1 = get_new_order_no('CB', 'hii_warehouse_other_out', 'w_o_out_sn');
                        $data = array();
                        $data['w_o_out_sn'] = $new_no1;
                        $data['w_o_out_status'] = 0;
                        $data['w_o_out_type'] = 0;
                        $data['w_in_id'] = $w_in_id;
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['warehouse_id2'] = $dataMain['warehouse_id2'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount2;
                        $data['g_nums'] = $nums2;
                        $Model = D('Addons://Warehouse/WarehouseOtherOut');
                        //print_r($data);die;
                        $res2 = $Model->saveWarehouseOtherOut($w_o_out_id, $data, $DetailListPurchaseOut,false);
                        $ModelMsg = D('Erp/MessageWarn');
                        $msg = $ModelMsg->pushMessageWarn(UID  ,$dataMain['warehouse_id'] , 0 ,0  , $data ,10);
                        if ($res2 > 0) {
                            $dataMain['w_in_status'] = 3;
                        } else {
                            $this->error('退货失败1' . $Model->err['msg']);
                        }
                    }
                    if ($res1 > 0) {
                        $Model = D('Addons://Warehouse/WarehouseIn');
                        $res = $Model->saveWarehouseIn($w_in_id, $dataMain, $DetailList,false);
                        if ($res > 0) {
                            $this->pass1($res1,1);
                            $this->success('入库成功');
                        } else {
                            $this->error($Model->err['msg']);
                        }
                    } else {
                        $this->error($Model1->err['msg']);
                    }
                }
                if($dataMain['w_in_type'] == 3) {
                    $this->error('入库失败，其它入库还没做。');
                }
                if($dataMain['w_in_type'] == 4) {
                    //门店返仓
                    if (count($DetailListPurchaseOut) > 0) {
                        //退货并新增门店退货单
                        $s_o_out_id = 0;
                        $new_no1 = get_new_order_no('MB', 'hii_store_other_out', 's_o_out_sn');
                        $data = array();
                        $data['s_o_out_sn'] = $new_no1;
                        $data['s_o_out_status'] = 0;
                        $data['s_o_out_type'] = 5;
                        $data['s_in_id'] = 0;
                        $data['s_id'] = 0;
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['store_id1'] = $dataMain['store_id'];
                        $data['store_id2'] = 0;
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount2;
                        $data['g_nums'] = $nums2;
                        $Model = D('Addons://Warehouse/StoreOtherOut');
                        //print_r($data);die;
                        $res2 = $Model->saveStoreOtherOut($s_o_out_id, $data, $DetailListPurchaseOut,false);
                        $ModelMsg = D('Erp/MessageWarn');
                        $msg = $ModelMsg->pushMessageWarn(UID  ,0 , $dataMain['store_id'] ,0  , $data ,11);
                        if ($res2 > 0) {
                            $dataMain['w_in_status'] = 3;
                        } else {
                            $this->error('退货失败1' . $Model->err['msg']);
                        }
                    }
                    if (count($DetailListWarehouseInStock) > 0) {
                        //审核并新增入库单
                        $w_in_s_id = 0;
                        $new_no = get_new_order_no('RK', 'hii_warehouse_in_stock', 'w_in_s_sn');
                        $data = array();
                        $data['w_in_s_sn'] = $new_no;
                        $data['w_in_s_status'] = 0;
                        $data['w_in_s_type'] = $dataMain['w_in_type'];
                        $data['w_in_id'] = $w_in_id;
                        $data['i_id'] = 0;
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['supply_id'] = $dataMain['supply_id'];
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount1;
                        $data['g_nums'] = $nums1;
                        $Model1 = D('Addons://Warehouse/WarehouseInStock');
                        $res1 = $Model1->saveWarehouseInStock($w_in_s_id, $data, $DetailListWarehouseInStock,false);
                    } else {
                        $this->error('入库失败，没有入库验收数量。' . $Model->err['msg']);
                    }
                    if ($res1 > 0) {
                        $Model = D('Addons://Warehouse/WarehouseIn');
                        $res = $Model->saveWarehouseIn($w_in_id, $dataMain, $DetailList,false);
                        if ($res > 0) {
                            $this->pass1($res1,1);
                            $this->success('入库成功');
                        } else {
                            $this->error($Model->err['msg']);
                        }
                    } else {
                        $this->error($Model1->err['msg']);
                    }
                }
            }else{
                if ($pass == '2') {
                    if ($dataMain['p_id'] != 0) {
                        if (count($DetailListWarehouseInStock) > 0) {
                            $this->error('退货失败,存在入库数量' . $Model->err['msg']);
                        }
                        //退货并新增采购退货单
                        $p_o_id = 0;
                        $new_no = get_new_order_no('CT', 'hii_purchase_out', 'p_o_sn');
                        $data = array();
                        $data['p_o_sn'] = $new_no;
                        $data['p_o_status'] = 0;
                        $data['w_in_id'] = $w_in_id;
                        $data['p_id'] = $dataMain['p_id'];
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['supply_id'] = $dataMain['supply_id'];
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount2;
                        $data['g_nums'] = $nums3;
                        $Model = D('Addons://Purchase/PurchaseOut');
                        $res1 = $Model->savePurchaseOut($p_o_id, $data, $DetailListPurchaseOutAll,false);
                        if ($res1 > 0) {
                            $dataMain['p_out_id'] = $res1;
                            $dataMain['w_in_status'] = 2;
                            $Model = D('Addons://Warehouse/WarehouseIn');
                            $res2 = $Model->saveWarehouseIn($w_in_id, $dataMain, $DetailList,false);
                            if ($res2 > 0) {
                                $ModelMsg = D('Erp/MessageWarn');
                                $msg = $ModelMsg->pushMessageWarn(UID  ,0 , 0 ,$shequ_id  , $data ,9);
                                $this->success('退货成功');
                            } else {
                                $this->error($Model->err['msg']);
                            }
                        } else {
                            $this->error('退货失败2' . $Model->err['msg']);
                        }
                    }
                    if ($dataMain['w_out_s_id'] != 0) {
                        if (count($DetailListWarehouseInStock) > 0) {
                            $this->error('退货失败,存在入库数量' . $Model->err['msg']);
                        }
                        //退货并新增仓库报损单
                        $w_o_out_id = 0;
                        $new_no1 = get_new_order_no('CB', 'hii_warehouse_other_out', 'w_o_out_sn');
                        $data = array();
                        $data['w_o_out_sn'] = $new_no1;
                        $data['w_o_out_status'] = 0;
                        $data['w_o_out_type'] = 0;
                        $data['w_in_id'] = $w_in_id;
                        $data['ctime'] = time();
                        $data['admin_id'] = UID;
                        $data['etime'] = 0;
                        $data['eadmin_id'] = 0;
                        $data['ptime'] = 0;
                        $data['padmin_id'] = 0;
                        $data['warehouse_id'] = $dataMain['warehouse_id'];
                        $data['warehouse_id2'] = $dataMain['warehouse_id2'];
                        $data['remark'] = $dataMain['remark'];
                        $data['g_type'] = $pcount2;
                        $data['g_nums'] = $nums2;
                        $Model = D('Addons://Warehouse/WarehouseOtherOut');
                        $res2 = $Model->saveWarehouseOtherOut($w_o_out_id, $data, $DetailListPurchaseOut,false);
                        if ($res2 > 0) {
                            $dataMain['w_in_status'] = 2;
                            $Model = D('Addons://Warehouse/WarehouseIn');
                            $res2 = $Model->saveWarehouseIn($w_in_id, $dataMain, $DetailList,false);
                            if ($res2 > 0) {
                                $ModelMsg = D('Erp/MessageWarn');
                                $msg = $ModelMsg->pushMessageWarn(UID  ,$dataMain['warehouse_id2'] , 0 ,0  , $data ,10);
                                $this->success('退货成功');
                            } else {
                                $this->error($Model->err['msg']);
                            }
                        } else {
                            $this->error('退货失败2' . $Model->err['msg']);
                        }
                    }
                    if($dataMain['s_back_id'] != 0){
                        //门店返仓
                        if (count($DetailListWarehouseInStock) > 0) {
                            $this->error('退货失败,存在入库数量' . $Model->err['msg']);
                        }
                        if (count($DetailListPurchaseOut) > 0) {
                            //退货并新增门店退货单
                            $s_o_out_id = 0;
                            $new_no1 = get_new_order_no('MB', 'hii_store_other_out', 's_o_out_sn');
                            $data = array();
                            $data['s_o_out_sn'] = $new_no1;
                            $data['s_o_out_status'] = 0;
                            $data['s_o_out_type'] = 5;
                            $data['s_in_id'] = 0;
                            $data['s_id'] = 0;
                            $data['ctime'] = time();
                            $data['admin_id'] = UID;
                            $data['etime'] = 0;
                            $data['eadmin_id'] = 0;
                            $data['ptime'] = 0;
                            $data['padmin_id'] = 0;
                            $data['warehouse_id'] = $dataMain['warehouse_id'];
                            $data['store_id1'] = $dataMain['store_id'];
                            $data['store_id2'] = 0;
                            $data['remark'] = $dataMain['remark'];
                            $data['g_type'] = $pcount2;
                            $data['g_nums'] = $nums2;
                            $Model = D('Addons://Warehouse/StoreOtherOut');
                            //print_r($data);die;
                            $res2 = $Model->saveStoreOtherOut($s_o_out_id, $data, $DetailListPurchaseOut,false);
                            if ($res2 > 0) {
                                $dataMain['w_in_status'] = 2;
                                $Model = D('Addons://Warehouse/WarehouseIn');
                                $res2 = $Model->saveWarehouseIn($w_in_id, $dataMain, $DetailList,false);
                                if ($res2 > 0) {
                                    $ModelMsg = D('Erp/MessageWarn');
                                    $msg = $ModelMsg->pushMessageWarn(UID  ,0 , $dataMain['store_id'] ,0  , $data ,11);
                                    $this->success('退货成功');
                                } else {
                                    $this->error($Model->err['msg']);
                                }
                            } else {
                                $this->error('退货失败1' . $Model->err['msg']);
                            }
                        }
                    }
                } else {
                    $this->error('错误参数');
                }
            }
        }
    }
    public function pass1($id,$pass)
    {
        $w_in_s_id = $id;
        if ($w_in_s_id == '') {
            $this->error('没有单据ID');
        }

        $where = array();
        $where['w_in_s_id'] = $w_in_s_id;

        $Model = M('WarehouseInStock');
        $dataMain = $Model->where($where)->find();
        if(!count($dataMain)>0){
            $this->error('没有入库单');
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
                    $DetailListWarehouseInOut['value_id'] = $DetailList[$k]['value_id'];
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
                return true;
                //$this->success('入库审核成功');
            }else{
                $this->error('入库审核失败2' .$Model->err['msg']);
            }
        }else{
            $this->error('错误参数');
        }
    }


}
