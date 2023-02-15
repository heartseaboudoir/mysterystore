<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseInquiryController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购询价单列表';
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
        //单号
        $sn = I('sn');
        if($sn != ''){
            $where .= " And (A.p_s_sn like '%" .$sn ."%' or P.p_sn like '%" .$sn ."%')";
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

        $title = $s_date. '>>>' .$e_date. '采购询价单';

        $Model = M('PurchaseSupply');

        $where0 = " And (";
        $where0 .= $this->get_where_supply_warehouse_store_of_shequ('Supply','A.supply_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Warehouse','A.warehouse_id');
        $where0 .= " or " .$this->get_where_supply_warehouse_store_of_shequ('Store','A.store_id');
        $where0 .= " )";
        $where0 .= $where;
        $sql = "select A.p_s_id,A.p_s_sn,A.p_s_status,A.p_id,P.p_sn,FROM_UNIXTIME(A.ctime,'%Y-%m-%d') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "A.admin_id,B.nickname,A.padmin_id,B1.nickname as pnickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.store_id,C2.title as store_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "sum(A1.g_num*(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.b_num*A1.b_price) as g_s_amounts";
        $sql .= " from  hii_purchase_supply A";
        $sql .= " left join hii_purchase_supply_detail A1 on A.p_s_id=A1.p_s_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_member B1 on A.padmin_id=B1.uid";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id";
        $sql .= " left join hii_supply C1 on A.supply_id=C1.s_id";
        $sql .= " left join hii_store C2 on A.store_id=C2.id";
        $sql .= " left join hii_goods_store GS on A.store_id=GS.store_id and A1.goods_id=GS.goods_id";
        $sql .= " left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " left join hii_purchase P on A.p_id=P.p_id";
        $sql .= " where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where0;
        $sql .= " group by A.p_s_id,A.p_s_sn,A.p_s_status,A.ctime,";
        $sql .= "A.admin_id,B.nickname,A.padmin_id,B1.nickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.remark,A.g_type,A.g_nums";
        $sql .= " order by p_s_id desc";
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
            $printfile = $printmodel->pushPurchaseInquiryList($data,$title,$fname);
            echo($printfile);die;
        }
        //分页
        $pcount=15;
        $count=count($data);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿

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

        /*$ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->select();
        $this->assign('warehouse', $WarehouseData);
        $this->assign('supply', $SupplyData);*/
        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->display(T('Addons://Purchase@PurchaseInquiry/index'));
    }

    public function view()
    {
        $this->meta_title = '采购询价单查看';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }

        if($id == ''){
            $this->error('不存在的单据ID');
        }
        $this->assign('id', $id);

        $Model = M('PurchaseSupply');

        $sql = "select A.p_s_id,A.p_s_sn,A.p_s_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,";
        $sql .= "A.admin_id,B.nickname,A.padmin_id,B1.nickname as pnickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.store_id,C2.title as store_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "sum(A1.g_num*(case when ifnull(GS.price,0)>0 then GS.price when ifnull(GS.shequ_price,0)>0 then GS.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.b_num*A1.b_price) as g_s_amounts";
        $sql .= " from  hii_purchase_supply A";
        $sql .= " left join hii_purchase_supply_detail A1 on A.p_s_id=A1.p_s_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_member B1 on A.padmin_id=B1.uid";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id";
        $sql .= " left join hii_supply C1 on A.supply_id=C1.s_id";
        $sql .= " left join hii_store C2 on A.store_id=C2.id";
        $sql .= " left join hii_goods_store GS on A.store_id=GS.store_id and A1.goods_id=GS.goods_id";
        $sql .= " left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " where A.p_s_id=$id";
        $sql .= " group by A.p_s_id,A.p_s_sn,A.p_s_status,A.ctime,";
        $sql .= "A.admin_id,B.nickname,A.padmin_id,B1.nickname,A.warehouse_id,A.supply_id,C.w_name,C1.s_name,A.remark,A.g_type,A.g_nums";
        $sql .= " order by p_s_id desc";
        $list = $Model->query($sql);
        if($list[0]['store_id']!= "" && $list[0]['store_id']!= 0){
            $Model = M('Store');
            $listshequ = $Model->where('id=' .$list[0]['store_id'])->select();
            $shequwhere = " and shequ_id = " .$listshequ[0]['shequ_id'];
        }
        if($list[0]['warehouse_id']!= "" && $list[0]['warehouse_id']!= 0){
            $Model = M('Warehouse');
            $listshequ = $Model->where('w_id=' .$list[0]['warehouse_id'])->select();
            $shequwhere = " and shequ_id = " .$listshequ[0]['shequ_id'];
        }
        $Model = M('PurchaseSupplyDetail');
        $listPS = $Model->field("GROUP_CONCAT(goods_id SEPARATOR ',') as goods_id")->where('p_s_id=' .$id)->select();
        $p_s_idwhere = " and goods_id in (" .$listPS[0]['goods_id'] .")";

        $Model = M('PurchaseSupply');
        $sql = "select A.p_s_id,A.p_s_sn,C.title as cate_name,(case when ifnull(GS1.price,0)>0 then GS1.price when ifnull(GS1.shequ_price,0)>0 then GS1.shequ_price when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,GS.num)) as stock_num,
        A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.p_s_d_id,sum(B1.g_num) as s_num,A1.b_n_num,A1.b_num,A1.b_price,A1.g_num,A1.g_price,A1.remark,
        gsup.supply_price as last_price,
        GROUP_CONCAT(B.p_r_sn SEPARATOR ',') as p_r_sn,GROUP_CONCAT(B.admin_id SEPARATOR ',') as ladmin_id,GROUP_CONCAT(M.nickname SEPARATOR ',') as lnickname,
        GROUP_CONCAT(B.warehouse_id SEPARATOR ',') as warehouse_id,GROUP_CONCAT(W.w_name SEPARATOR ',') as w_name,
        GROUP_CONCAT(B.store_id SEPARATOR ',') as store_id,GROUP_CONCAT(S.title SEPARATOR ',') as store_name,AV.value_id,AV.value_name
         from  hii_purchase_supply A
          left join hii_purchase_supply_detail A1 on A.p_s_id=A1.p_s_id
           left join hii_purchase_request_detail B1 on FIND_IN_SET(B1.p_r_d_id,A1.p_r_d_id)
            left join hii_purchase_request B on B1.p_r_id=B.p_r_id
             left join hii_member M on B.admin_id=M.uid
              left join hii_warehouse W on B.warehouse_id=W.w_id
               left join hii_store S on B.store_id=S.id
            left join hii_warehouse W1 on A.warehouse_id=W1.w_id
            left join hii_goods_store GS1 on A.store_id=GS1.store_id and A1.goods_id=GS1.goods_id
            left join hii_shequ_price SP on W1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
                left join hii_goods G on A1.goods_id=G.id
                 left join hii_goods_cate C on G.cate_id=C.id
                  left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id
         left join hii_goods_store GS on A1.goods_id=GS.goods_id and A.store_id=GS.store_id
           left join hii_goods_supply gsup on gsup.supply_id = A.supply_id and gsup.goods_id = A1.goods_id and (gsup.shequ_id = S.shequ_id or gsup.shequ_id = W.shequ_id)
             left join hii_attr_value AV  on AV.value_id=A1.value_id
        where A.p_s_id=$id
         group by A.p_s_id,A.p_s_sn,C.title,G.sell_price,floor(ifnull(WS.num,0)),
        A1.goods_id,G.title,G.bar_code,A1.p_s_d_id,A1.b_n_num,A1.b_num,A1.b_price,A1.g_num,A1.g_price,A1.remark
        order by A1.goods_id desc";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $title = '采购询价单' .$list[0]['p_s_sn'] .'查看';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushPurchaseInquiryView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        /*$ModelWarehouse = M('Warehouse');
        $WarehouseData = $ModelWarehouse->select();
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->select();
        $this->assign('supply', $SupplyData);
        $ModelStore = M('Store');
        $StoreData = $ModelStore->select();
        $this->assign('warehouse', $WarehouseData);
        $this->assign('store', $StoreData);*/
        $shequ = $_SESSION['can_shequs_cg'];
        $whereSQ = "shequ_id in (" .implode(',',$shequ). ")";
        $ModelSupply = M('Supply');
        $SupplyData = $ModelSupply->where($whereSQ)->select();

        $this->assign('supply', $SupplyData);
        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Purchase@PurchaseInquiry/view'));
    }

    public function update()
    {
        $p_s_id = I('p_s_id', '');
        if ($p_s_id == '') {
            $p_s_id = $_POST['p_s_id'];
        }
        $warehouse_id = I('warehouse_id', '');
        if ($warehouse_id == '') {
            $warehouse_id = $_POST['warehouse_id'];
        }
        $supply_id = I('supply_id', '');
        if ($supply_id == '') {
            $supply_id = $_POST['supply_id'];
        }
        $store_id = I('store_id', '');
        if ($store_id == '') {
            $store_id = $_POST['store_id'];
        }
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $p_s_d_id = I('p_s_d_id', '');
        if ($p_s_d_id == '') {
            $p_s_d_id = $_POST['p_s_d_id'];
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
            $b_price = $_POST['b_price'];
        }
        $g_num = I('g_num', '');
        if ($g_num == '') {
            $g_num = $_POST['g_num'];
        }
        $g_price = I('g_price', '');
        if ($g_price == '') {
            $g_price = $_POST['g_price'];
        }
        if($p_s_id == ''){
            $this->error('不存在的单据ID');die;
        }
        if($p_s_d_id == '' || $p_s_d_id == '0'){
            $this->error('必须有商品数据');die;
        }
        if ($p_s_id != '') {
            $where = array();
            $where['p_s_d_id'] = array('in',$p_s_d_id);
            $field = "p_s_d_id,p_s_id,p_r_d_id,goods_id,g_num,g_price,remark,value_id";
            $DetailList = M('PurchaseSupplyDetail')
                ->field($field)
                ->where($where)->order('p_s_d_id desc')->select();
            if(!count($DetailList)>0){
                $this->error('没有申请商品');die;
            }
            $ptype = 0;
            $pnum = 0;
            for($i = 0;$i < count($DetailList);$i++) {
                for($j = 0;$j < count($p_s_d_id);$j++){
                    if($DetailList[$i]['p_s_d_id'] == $p_s_d_id[$j]){
                        $modelGoods = M('Goods');
                        $dataGoods = $modelGoods->where('id=' .$DetailList[$i]['goods_id'])->select();
                        if(floatval($g_price[$j]) > floatval($dataGoods[0]['sell_price'])){
                            $this->error('采购单价不能高于系统售价');die;
                        }
                        $DetailList[$i]['b_n_num'] = $b_n_num[$j];
                        $DetailList[$i]['b_num'] = $b_num[$j];
                        $DetailList[$i]['b_price'] = $b_price[$j];
                        $DetailList[$i]['g_num'] = $g_num[$j];
                        $DetailList[$i]['g_price'] = $g_price[$j];
                        $ptype++;
                        $pnum +=  $DetailList[$i]['g_num'];
                    }
                }
            }
            $where['p_s_id'] = $p_s_id;
            $Model = M('PurchaseSupply');
            $data = $Model->where($where)->find();
            if(!$data){
                $this->error('没有单据');die;
            }
            //$data['warehouse_id'] = $warehouse_id;
            //$data['store_id'] = $store_id;
            $data['supply_id'] = $supply_id;
            $data['remark'] = $remark;
            $data['g_type'] = $ptype;
            $data['g_nums'] = $pnum;
            $data['etime'] = time();
            $data['eadmin_id'] = UID;

            $Model = D('Addons://Purchase/PurchaseSupply');
            $res = $Model->savePurchaseSupply($p_s_id,$data,$DetailList,false);
            if($res>0){
                $this->success('提交成功');die;
            }else{
                $this->error($Model->err['msg']);
            }
        }
    }

    public function pass()
    {
        $p_s_id = I('id', '');
        if ($p_s_id == '') {
            $p_s_id = $_POST['id'];
        }
        if ($p_s_id == '') {
            $this->error('没有单据ID');
        }
        $pass = I('pass', '');
        if ($pass == '') {
            $pass = $_POST['pass'];
        }
        if ($pass == '') {
            $this->error('没有参数');
        }
        if ($p_s_id != '') {
            /*备注
            采购询价单->存在相同商品需要合并->采购单
            */
            /*$sql = "select GROUP_CONCAT(p_s_d_id SEPARATOR ',') as p_s_d_id,p_s_id,GROUP_CONCAT(p_r_d_id SEPARATOR ',') as p_r_d_id,
            goods_id,max(b_n_num) as b_n_num,sum(b_num) as b_num,max(b_price) as b_price,sum(g_num) as g_num,max(g_price) as g_price,GROUP_CONCAT(remark SEPARATOR ',') as remark
            from hii_purchase_supply_detail where p_s_id = $p_s_id group by p_s_id,goods_id
            ";
            $DetailList = M('PurchaseSupplyDetail')->query($sql);*/
            $where = array();
            $where['p_s_id'] = $p_s_id;
            $field = "p_s_d_id,p_s_id,p_r_d_id,goods_id,b_n_num,b_num,b_price,g_num,g_price,remark,value_id";
            $DetailList = M('PurchaseSupplyDetail')
                ->field($field)
                ->where($where)->order('p_s_d_id desc')->select();
            if(!count($DetailList)>0){
                $this->error('没有申请商品');
            }
            $pcount = 0;
            $nums = 0;
            $tmp = array();
            $DetailListPurchase = array();
            foreach($DetailList as $k=>$v){
                if($DetailList[$k]['g_price'] == 0 && $pass == '1'){
                    $this->error($v['goods_id'].'没有商品采购价');
                }
                if($DetailList[$k]['g_num'] == 0 && $pass == '1'){
                    $this->error($v['goods_id'].'没有商品采购数量');
                }
                if($DetailList[$k]['b_n_num'] == 0 && $pass == '1'){
                	$this->error($v['goods_id'].'没有商品箱规');
                }
                if($DetailList[$k]['b_num'] == 0 && $pass == '1'){
                	$this->error($v['goods_id'].'没有商品箱数');
                }
                $tmp['goods_id'] = $DetailList[$k]['goods_id'];
                $tmp['b_n_num'] = $DetailList[$k]['b_n_num'];
                $tmp['b_num'] = $DetailList[$k]['b_num'];
                $tmp['b_price'] = $DetailList[$k]['b_price'];
                $tmp['g_num'] = $DetailList[$k]['g_num'];
                $tmp['g_price'] = $DetailList[$k]['g_price'];
                $tmp['remark'] = $DetailList[$k]['remark'];
                $tmp['p_s_d_id'] = $DetailList[$k]['p_s_d_id'];
                $tmp['value_id'] = $DetailList[$k]['value_id'];
                $DetailListPurchase[] = $tmp;
                $pcount++;
                $nums += $DetailList[$k]['g_num'];
                if ($pass == '2') {
                    $ModelRequestDetail = M('PurchaseRequestDetail');
                    $data['is_pass'] = 1;
                    $condition1['p_r_d_id'] = $DetailList[$k]['p_r_d_id'];
                    $condition['p_r_d_id'] = $DetailList[$k]['p_r_d_id'];
                    $condition1['is_pass'] = array('neq', 1);
                    $dataRD = $ModelRequestDetail->where($condition1)->select();
                    if (is_array($dataRD) && count($dataRD) > 0) {
                        $res = $ModelRequestDetail->where($condition)->save($data);
                        if (!$res) {
                            $error = $ModelRequestDetail->getError();
                            print_r($ModelRequestDetail->_sql());
                            print_r($error);
                            die;
                            $this->error($error ? $error : '找不到要拒绝的数据！' . $res);
                        } else {
                            //$this->success('拒绝成功');
                        }
                    }
                }
            }
            $where['p_s_id'] = $p_s_id;
            $where['p_s_status'] = 0;
            $Model = M('PurchaseSupply');
            $dataPS = $Model->where($where)->find();
            if(!$dataPS){
                $this->error('单据不存在或者已经审核。');
            }
            $dataPS['ptime'] = time();
            $dataPS['padmin_id'] = UID;
            $dataPS['p_s_status'] = $pass;

            if ($pass == '1') {
                //审核并新增采购单
                $p_id = 0;
                $new_no = get_new_order_no('CG','hii_purchase','p_sn');
                $data = array();
                $data['p_id'] = $p_id;
                $data['p_sn'] = $new_no;
                $data['p_status'] = 0;
                $data['p_s_id'] = $p_s_id;
                $data['ctime'] = time();
                $data['admin_id'] = UID;
                $data['etime'] = 0;
                $data['eadmin_id'] = 0;
                $data['ptime'] = 0;
                $data['padmin_id'] = 0;
                $data['supply_id'] = $dataPS['supply_id'];
                $data['warehouse_id'] = $dataPS['warehouse_id'];
                $data['store_id'] = $dataPS['store_id'];
                $data['w_in_id'] = 0;
                $data['remark'] = $dataPS['remark'];
                $data['g_type'] = $pcount;
                $data['g_nums'] = $nums;

                $Model1 = D('Addons://Purchase/Purchase');
                $res1 = $Model1->savePurchase($p_id,$data,$DetailListPurchase,false);
                if($res1>0){
                    $dataPS['p_id'] = $res1;
                    $Model = D('Addons://Purchase/PurchaseSupply');
                    $res = $Model->savePurchaseSupply($p_s_id,$dataPS,$DetailList,false);
                    if($res>0){
                        $this->pass1($res1,1);
                        $this->success('审核成功');
                    }else{
                        $this->error($Model->err['msg']);
                    }
                }else{
                    $this->error($Model1->err['msg']);
                }
            }else{
                if ($pass == '2') {
                    foreach($DetailList as $k=>$v){
                        $PRDModel = M("PurchaseRequestDetail");
                        $PRDModel -> startTrans(); //开启事务
                        $sql = "update hii_purchase_request_detail set is_pass = 0 where p_r_d_id in (".$DetailList[$k]['p_r_d_id'] .")";
                        $ok = $PRDModel->execute($sql);
                        if(!$ok) {
                            $PRDModel->rollback();
                            $this->error('打回采购申请单失败');
                        }
                        $PRDModel->commit(); //提交事物
                    }
                    $Model = D('Addons://Purchase/PurchaseSupply');
                    $res = $Model->savePurchaseSupply($p_s_id,$dataPS,$DetailList,false);
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
    public function pass1($p_id,$pass)
    {
        if ($p_id == '') {
            $this->error('没有单据ID');
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
                $tmp['remark'] = $DetailList[$k]['remark'];
                $tmp['value_id'] = $DetailList[$k]['value_id'];
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
                $tmp['p_d_id'] = $DetailList[$k]['p_d_id'];
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
            $Model = M('Purchase');
            $dataPS = $Model->where($where)->find();
            if(!$dataPS){
                $this->error('没有单据');
            }
            $dataPS['ptime'] = time();
            $dataPS['padmin_id'] = UID;
            $dataPS['p_status'] = $pass;

            if ($pass == '1') {
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
                            return $res1;
                            //$this->success('审核成功');
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
                            return $res1;
                            //$this->success('审核成功');
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

}
