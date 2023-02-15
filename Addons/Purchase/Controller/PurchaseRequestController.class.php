<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseRequestController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }

    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购申请列表自动分单';
        //自动还是全部
        $is_auto = I('is_auto');
        if($is_auto == ""){
            $is_auto = $_POST['is_auto'];
        }
        if($is_auto == "1"){
            $this->meta_title = '采购申请列表全部申请';
        }
        if($is_auto == ""){
            $is_auto = 0;
        }
        $this->assign('is_auto', $is_auto);
        $where = "";
        //仓库id
        $warehouse_id = I('select_warehouse_id');
        if($warehouse_id == ""){
            $warehouse_id = $_POST['select_warehouse_id'];
        }
        if($warehouse_id != '' && $warehouse_id != '0'){
            $where .= " And A.warehouse_id = " .$warehouse_id .' And A.p_r_type = 0';
        }
        $this->assign('select_warehouse_id', $warehouse_id);
        //门店id
        $store_id = I('select_store_id');
        if($store_id == ""){
            $store_id = $_POST['select_store_id'];
        }
        if($store_id != '' && $store_id != '0'){
            $where .= " And A.store_id = " .$store_id ." And A.p_r_type = 1";
        }
        $this->assign('select_store_id', $store_id);
        //供应商id
        $supply_id = I('select_supply_id');
       /*  if($supply_id == ""){
            $supply_id = $_POST['select_supply_id'];
        }
        if($supply_id != '' && $supply_id != '0'){
            $where .= " And C.supply_id = " .$supply_id;
        } */
        $this->assign('select_supply_id', $supply_id);
        //供应商排序
        $orderbysupply = I('orderbysupply');
        //商品ID排序
        $orderbygoodsid = I('orderbygoodsid');
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

        $title = $s_date. '>>>' .$e_date. $this->meta_title;

        $Model = M('PurchaseRequest');
        $shequ = $_SESSION['can_shequs_cg'];
        $where0 = " And (";
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Store','A.store_id');
        $where0 .= " )";
        /*$where1 = "FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '$s_date' and '$e_date' and A1.is_pass=0" .$where .$where0;
        $fld = "A.p_r_id,A1.p_r_d_id,A.p_r_type,A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code) as bar_code,A.p_r_sn,A1.g_num,floor(ifnull(ws.num,0)) as stock_num,floor(ifnull(ss.num,0)) as store_num";
        $fld .= ",A.ctime,w.w_name,s.title as store_name,m.nickname,A.remark,A1.remark as remark_detail,A.store_id,A.warehouse_id";
        $fld .= ",(case when A.warehouse_id>0 then w.shequ_id when A.store_id>0 then s.shequ_id else 0 end) as shequ_id";
        $fld .= ",(case when sq.id>0 then sq.title when sq1.id>0 then sq1.title else '' end) as shequ_name";
        $fld .= ",sl.s_name as history_supply,gp.g_price as purchase_price,AV.value_id,AV.value_name";
        $data = $Model->alias('A')
            ->join('LEFT JOIN hii_purchase_request_detail A1 ON A.p_r_id = A1.p_r_id')
            ->join('LEFT JOIN hii_goods G ON A1.goods_id = G.id')
            ->join('LEFT JOIN hii_warehouse w ON A.warehouse_id = w.w_id')
            ->join('LEFT JOIN hii_store s ON A.store_id = s.id')
            ->join('LEFT JOIN hii_member m ON A.admin_id=m.uid')
            ->join('LEFT JOIN hii_warehouse_stock ws ON A.warehouse_id=ws.w_id and A1.goods_id=ws.goods_id and A1.value_id=ws.value_id')
            ->join('LEFT JOIN hii_goods_store ss ON A.store_id=ss.store_id and A1.goods_id=ss.goods_id')
            ->join('LEFT JOIN hii_shequ sq ON s.shequ_id = sq.id')
            ->join('LEFT JOIN hii_shequ sq1 ON w.shequ_id = sq1.id')
            ->join('LEFT JOIN hii_g_price_purchase_view gp ON A1.goods_id=gp.goods_id and (w.shequ_id=gp.shequ_id or s.shequ_id=gp.shequ_id)')
            ->join('LEFT JOIN hii_supply sl ON gp.supply_id = sl.s_id')
            ->join('LEFT JOIN hii_attr_value AV on AV.value_id=A1.value_id')
            ->field($fld)
            ->where($where1)->order('p_r_id asc')->select();*/
        $sql = "select p_r_id,p_r_sn,ctime,admin_id,nickname,p_r_type,remark,remark_detail,p_r_d_id,
            goods_id,goods_name,bar_code,g_num,warehouse_id,w_name,store_id,store_name,
            shequ_name,group_concat(supply_id ) as supply_id,group_concat(s_name) as s_name,group_concat(g_price) as g_price,stock_num,store_num,
            is_select,value_id,value_name from
            (
            select distinct A.p_r_id,A.p_r_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,m.nickname,A.p_r_type,A.remark,A1.remark as remark_detail,A1.p_r_d_id,
            A1.goods_id,g.title as goods_name,ifnull(AV.bar_code,g.bar_code)bar_code,A1.g_num,A.warehouse_id,w.w_name,A.store_id,s.title as store_name,
            S1.title as shequ_name,LP.supply_id as supply_id,D.s_name as s_name,LP.supply_price as g_price,floor(ifnull(ws.num,0)) as stock_num,floor(ifnull(ss.num,0)) as store_num,
            0 as is_select,AV.value_id,AV.value_name
            from  hii_purchase_request A
            left join hii_purchase_request_detail A1 on A.p_r_id=A1.p_r_id
            left join hii_warehouse w on A.warehouse_id=w.w_id
            left join hii_warehouse_stock ws on A.warehouse_id=ws.w_id and A1.value_id=ws.value_id
            left join hii_goods_store ss on A.store_id=ss.store_id and A1.goods_id=ss.goods_id
            left join hii_store s on A.store_id=s.id
            left join hii_member m on A.admin_id=m.uid
            left join hii_goods g on A1.goods_id=g.id
            left join hii_attr_value AV on AV.value_id=A1.value_id 
            left join hii_goods_supply LP on A1.goods_id=LP.goods_id and (LP.shequ_id = s.shequ_id or LP.shequ_id = w.shequ_id)
            left join hii_shequ S1 on LP.shequ_id=S1.id
            left join hii_supply D on LP.supply_id=D.s_id
            where  FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '$s_date' and '$e_date' and A1.is_pass=0 $where $where0
            order by A.warehouse_id,LP.time desc
            ) A
            group by p_r_id,p_r_sn,ctime,admin_id,nickname,p_r_type,remark,remark_detail,p_r_d_id,
            goods_id,goods_name,bar_code,g_num,warehouse_id,w_name,store_id,store_name,stock_num,store_num,is_select
        ";
        //echo $sql;die;
        $data = $Model->query($sql);
        //print_r($data);die;
        $p_r_d_id_list = array_column($data,'p_r_d_id');

        $warehouse_id_list = array_column($data,'warehouse_id');
        $warehouse_list = array_unique($warehouse_id_list);
        $warehouse_list = array_values($warehouse_list);
        $warehouse_name_list = array_column($data,'w_name');
        $warehousename_list = array_unique($warehouse_name_list);
        $warehousename_list = array_values($warehousename_list);

        $supply_id_list = array_column($data,'supply_id');
        $supply_list = array_unique($supply_id_list);
        $supply_list = array_values($supply_list);
        $supply_name_list = array_column($data,'s_name');
        $supplyname_list = array_unique($supply_name_list);
        $supplyname_list = array_values($supplyname_list);

        $store_id_list = array_column($data,'store_id');
        $store_list = array_unique($store_id_list);
        $store_list = array_values($store_list);
        $store_name_list = array_column($data,'store_name');
        $storename_list = array_unique($store_name_list);
        $storename_list = array_values($storename_list);

        for($i = 0;$i < count($store_list);$i++) {
            if($store_list[$i]['store_id'] != 0) {
                //for ($j = 0; $j < count($supply_list); $j++) {
                    $dataout = array();
                    $dataout['warehouse_id'] = 0;
                    $dataout['w_name'] = '';
                    $dataout['supply_id'] = '';//$supply_list[$j]['supply_id'];
                    $dataout['s_name'] = '';//$supplyname_list[$j];
                    $dataout['store_id'] = $store_list[$i];
                    $dataout['store_name'] = $storename_list[$i];
                    $havedata = false;
                    for ($k = 0; $k < count($data); $k++) {
                        if ($data[$k]['store_id'] == $store_list[$i] && $data[$k]['p_r_type'] == 1) {
                        	$data[$k]['ctime'] = $data[$k]['ctime'];//date('Y-m-d H:i:s',$data[$k]['ctime']);
                            $dataout['data'][] = $data[$k];
                            $havedata = true;
                        }
                    }
                    if (is_array($dataout['data']) && count($dataout['data']) > 0 && $havedata == true) {
                        $datamain[] = $dataout;
                    }
                //}
            }
        }
        for($i = 0;$i < count($warehouse_list);$i++) {
            if($warehouse_list[$i]['warehouse_id'] != 0) {
                //for ($j = 0; $j < count($supply_list); $j++) {
                    $dataout = array();
                    $dataout['warehouse_id'] = $warehouse_list[$i]['warehouse_id'];
                    $dataout['w_name'] = $warehousename_list[$i];
                    $dataout['supply_id'] = '';//$supply_list[$j]['supply_id'];
                    $dataout['s_name'] = '';//$supplyname_list[$j];
                    $dataout['store_id'] = 0;
                    $dataout['store_name'] = '';
                    $havedata = false;
                    for ($k = 0; $k < count($data); $k++) {
                        if ($data[$k]['warehouse_id'] == $warehouse_list[$i] && $data[$k]['p_r_type'] == 0) {
                        	$data[$k]['ctime'] = $data[$k]['ctime'];//date('Y-m-d H:i:s',$data[$k]['ctime']);
                            $dataout['data'][] = $data[$k];
                            $havedata = true;
                        }
                    }
                    if (is_array($dataout['data']) && count($dataout['data']) > 0 && $havedata == true) {
                        $datamain[] = $dataout;
                    }
                //}
            }
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

        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            if($is_auto == "1"){
                $printfile = $printmodel->pushAllWarehousePurchaseList($datamain,$title,$fname);
            }else{
                $printfile = $printmodel->pushAllWarehousePurchaseListAuto($datamain,$title,$fname);
            }
            echo($printfile);die;
        }
        //分页
        $pcount=10;
        $count=count($datamain);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain1 = array_slice($datamain,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        if($orderbysupply == "1"){
            for ($k = 0; $k < count($datamain1); $k++) {
                $datamain1[$k]['data'] = $this->multi_array_sort($datamain1[$k]['data'],'s_name',SORT_DESC );
                //$datamain1[$k]['data'] = array();
            }
        }
        if($orderbygoodsid == "1"){
            for ($k = 0; $k < count($datamain1); $k++) {
                $datamain1[$k]['data'] = $this->multi_array_sort($datamain1[$k]['data'],'goods_id',SORT_ASC );
                //$datamain1[$k]['data'] = array();
            }
        }
        //print_r($datamain1);die;
        $this->assign('list', $datamain1);
        $this->assign('p_r_d_id_list', $p_r_d_id_list);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Purchase@PurchaseRequest/index'));
    }

    function multi_array_sort(&$multi_array,$sort_key,$sort=SORT_DESC){
        if(is_array($multi_array)){
            foreach ($multi_array as $row_array){
                if(is_array($row_array)){
//把要排序的字段放入一个数组中，
                    $key_array[] = $row_array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
//对多个数组或多维数组进行排序
        array_multisort($key_array,$sort,$multi_array);
        return $multi_array;
    }

    public function update()
    {
        $select_id = I('selectprdid', '');
        if ($select_id == '') {
            $select_id = $_POST['selectprdid'];
        }
        if($select_id == '0' || $select_id == ''){
            $this->error('没有选择商品');
        }else{
            $prdid = implode(',',$select_id);
            $sql = "select distinct p_r_id from hii_purchase_request_detail where p_r_d_id in ($prdid)";
            $PRDModel = M('PurchaseRequestDetail')->query($sql);
            $pridary = implode( ',' , array_column($PRDModel,'p_r_id') );
        }
        $warehouse_id = I('warehouse_id', '');
        if ($warehouse_id == '') {
            $warehouse_id = $_POST['warehouse_id'];
        }
        $store_id = I('store_id', '');
        if ($store_id == '') {
            $store_id = $_POST['store_id'];
        }
        if($warehouse_id == '0' || $warehouse_id == ''){
            if($store_id == '0' || $store_id == ''){
                $this->error('仓库/门店，必选其一');
            }
        }else{
            if($store_id == '0' || $store_id == ''){
            }else{
                $this->error('仓库/门店，不能都选');
            }
        }
        $supply_id = I('supply_id', '');
        if ($supply_id == '') {
            $supply_id = $_POST['supply_id'];
        }
        if($supply_id == '0' || $supply_id == ''){
            $this->error('没有选择供应商');
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $where = array();
        $where['p_r_d_id'] = array('in',$select_id);
        $field = "GROUP_CONCAT(p_r_d_id SEPARATOR ',') as p_r_d_id,GROUP_CONCAT(p_r_id SEPARATOR ',') as p_r_id,a.goods_id,sum(a.g_num) as g_num,GROUP_CONCAT(a.remark SEPARATOR ',') as remark,ifnull(B.g_price,0) as g_price,a.value_id";
        $pcount = 0;
        $nums = 0;
        $DetailList = M('PurchaseRequestDetail')->alias('a')
            ->join('left join hii_goods_last_purchase_price_view B on a.goods_id=B.goods_id')
            ->field($field)
            ->where($where)->group('goods_id,value_id,B.g_price')->order('p_r_d_id asc')->select();
        if(!count($DetailList)>0){
            $this->error('没有申请商品');
        }

        $ModelPurchaseRequestDetail = M('PurchaseRequestDetail');
        foreach($DetailList as $k=>$v){
            $data = array();
            $data['is_pass'] = 2;
            $data['pass_num'] = $DetailList[$k]['g_num'];;
            $condition['p_r_d_id'] = array('in',$select_id);
            $result = $ModelPurchaseRequestDetail->where($condition)->save($data);
            if($result !== false){
                //echo '申请提交询价更新成功！';
            }else{
                $this->error('申请提交询价更新失败');
            }
            $pcount++;
            $nums += $DetailList[$k]['g_num'];
        }


        $p_s_id = 0;
        $new_no = get_new_order_no('XJ','hii_purchase_supply','p_s_sn');
        $data = array();
        $data['p_s_id'] = $p_s_id;
        $data['p_s_sn'] = $new_no;
        $data['p_s_status'] = 0;
        $data['p_id'] = 0;
        $data['p_r_id'] = $pridary;
        $data['ctime'] = time();
        $data['admin_id'] = UID;
        $data['ptime'] = 0;
        $data['padmin_id'] = 0;
        $data['supply_id'] = $supply_id;
        $data['store_id'] = $store_id;
        $data['warehouse_id'] = $warehouse_id;
        $data['remark'] = $remark;
        $data['g_type'] = $pcount;
        $data['g_nums'] = $nums;
        $Model = D('Addons://Purchase/PurchaseSupply');
        $res = $Model->savePurchaseSupply($p_s_id,$data,$DetailList,false);
        if($res>0){
            $this->success('提交成功');
        }else{
            $this->error($Model->err['msg']);
        }
    }

    public function sub_delete()
    {
        $select_id = I('selectprdid', '');
        if ($select_id == '') {
            $select_id = $_POST['selectprdid'];
        }
        if($select_id == '0' || $select_id == ''){
            $this->error('没有选择商品');
        }else{
            $prdid = implode(',',$select_id);
            $sql = "update hii_purchase_request_detail set is_pass = 1 where p_r_d_id in ($prdid)";
            $Model = M('PurchaseRequestDetail');
            $res = $Model->execute($sql);
            if (!$res) {
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要拒绝的数据1！');
            } else {
                $this->success('拒绝成功', Cookie('__forward__'));
            }
        }
    }

    public function delete()
    {
        $id = I('get.id', '');
        if ($id) {
            $Model = M('PurchaseRequestDetail');
            $data['is_pass'] = 1;
            $condition['p_r_d_id'] = $id;
            $res = $Model->where($condition)->save($data);
            if (!$res) {
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要拒绝的数据！');
            } else {
                $this->success('拒绝成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择拒绝的数据！', Cookie('__forward__'));
        }
    }
}
