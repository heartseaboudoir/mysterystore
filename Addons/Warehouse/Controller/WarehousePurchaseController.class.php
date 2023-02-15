<?php
namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehousePurchaseController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购申请单管理';
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

        $title = '【'.session('user_warehouse.w_name').'】 ' .$s_date. '>>>' .$e_date. '采购申请单';

        $Model = M('PurchaseRequest');

        $sql = "select A.p_r_id,A.p_r_sn,A.p_r_type,A.p_r_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.store_id,C1.title as store_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,
        sum(A1.is_pass) as is_pass";
        $sql .= " from  hii_purchase_request A";
        $sql .= " left join hii_purchase_request_detail A1 on A.p_r_id=A1.p_r_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id";
        $sql .= " left join hii_store C1 on A.store_id=C1.id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " where warehouse_id = " .$this->_warehouse_id;
        $sql .= " and FROM_UNIXTIME(ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'";
        $sql .= " group by A.p_r_id,A.p_r_sn,A.p_r_type,A.p_r_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.remark,A.g_type,A.g_nums";
        $sql .= " order by p_r_id desc";
        $data = $Model->query($sql);
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehousePurchaseList($data,$title,$fname);
            $this->display(T('Addons://Warehouse@WarehousePurchase/index'));
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
        $this->display(T('Addons://Warehouse@WarehousePurchase/index'));
    }

    public function view()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购申请单查看';
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

        $sql = "select A.p_r_id,A.p_r_sn,A.p_r_type,A.p_r_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.remark,A.g_type,A.g_nums,";
        $sql .= "sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,
        sum(A1.is_pass) as is_pass";
        $sql .= " from  hii_purchase_request A";
        $sql .= " left join hii_purchase_request_detail A1 on A.p_r_id=A1.p_r_id";
        $sql .= " left join hii_member B on A.admin_id=B.uid";
        $sql .= " left join hii_warehouse C on A.warehouse_id=C.w_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id
        left join hii_shequ_price SP on C.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " where warehouse_id = " .$this->_warehouse_id;
        $sql .= " and A.p_r_id=$id";
        $sql .= " group by A.p_r_id,A.p_r_sn,A.p_r_type,A.p_r_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id,C.w_name,A.remark,A.g_type,A.g_nums";
        $sql .= " order by p_r_id desc";
        $list = $Model->query($sql);

        $sql = "select A.p_r_id,A.p_r_sn,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,";
        $sql .= "A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,A1.is_pass,A1.pass_num,A1.remark,AV.value_id,AV.value_name ";
        $sql .= " from  hii_purchase_request A";
        $sql .= " left join hii_purchase_request_detail A1 on A.p_r_id=A1.p_r_id";
        $sql .= " left join hii_warehouse C1 on A.warehouse_id=C1.w_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id
        left join hii_shequ_price SP on C1.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id";
        $sql .= " left join hii_attr_value AV on AV.value_id=A1.value_id ";
        $sql .= " where A.warehouse_id = " .$this->_warehouse_id;
        $sql .= " and A.p_r_id=$id";
        $sql .= " order by p_r_id desc";
        $data = $Model->query($sql);
        //print_r($sql);die;
        $title = '【'.session('user_warehouse.w_name').'】 采购申请单' .$list[0]['p_r_sn'] .'查看';
        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehousePurchaseView($list[0],$data,$title,$fname);
            echo($printfile);die;
        }

        $this->assign('list', $list[0]);
        $this->assign('data', $data);
        $this->display(T('Addons://Warehouse@WarehousePurchase/view'));
    }

    public function again()
    {
        $this->meta_title = '采购申请单再次申请';
        //时间范围默认30天
        $id = I('id');
        if ($id == '') {
            $id = $_POST['id'];
        }
        if ($id == '') {
            $this->error('不存在的单据ID');
        }
        $Model0 = M('PurchaseRequest');
        $sql = "select A.p_r_id,A.p_r_sn,C.title as cate_name,G.sell_price,ifnull(WS.num,0) as stock_num,";
        $sql .= "A1.goods_id,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.g_num,A1.is_pass,A1.pass_num,A1.remark,A1.value_id";
        $sql .= " from  hii_purchase_request A";
        $sql .= " left join hii_purchase_request_detail A1 on A.p_r_id=A1.p_r_id";
        $sql .= " left join hii_goods G on A1.goods_id=G.id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id";
        $sql .= " where A.warehouse_id = " .$this->_warehouse_id;
        $sql .= " and A.p_r_id=$id";
        $sql .= " order by p_r_id desc";
        $data = $Model0->query($sql);
        for($i = 0;$i < count($data);$i++) {
            $data1 = array();
            $data1['admin_id'] = UID;
            $data1['warehouse_id'] = $this->_warehouse_id;
            $data1['goods_id'] = $data[$i]['goods_id'];
            $data1['temp_type'] = 1;
            $data1['status'] = 0;
            $data1['value_id'] = $data[$i]['value_id'];
            $Model = M('RequestTemp');
            $data2 = $Model->where($data1)->select();
            if($data2){
                //已经存在
                $where = $data1;
                $data1['g_num'] = $data2[0]['g_num'] + $data[$i]['g_num'];
                $data1['ctime'] = time();
                $dataout = $Model->where($where)->save($data1);
                if(!$dataout){
                    $this->error($Model->getError());
                }
            }else{
                //新增
                $data1['g_num'] = $data[$i]['g_num'];
                $data1['remark'] = $data[$i]['remark'];
                $data1['warehouse_id'] = $this->_warehouse_id;
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
        $this->display(T('Addons://Warehouse@WarehousePurchase/save'));
    }


    public function temp() {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $warehouseOne = M('Warehouse')->where('w_id = '.$this->_warehouse_id)->find();

        $where = array();
        $where['admin_id'] = UID;
        $where['warehouse_id'] = $this->_warehouse_id;
        $where['temp_type'] = 1;
        $where['hii_request_temp.status'] = 0;
        $field = "hii_request_temp.id,hii_request_temp.goods_id,FROM_UNIXTIME(hii_request_temp.ctime,'%Y-%m-%d %H:%i:%s') as ctime";
        $field .= ",hii_goods.title as goods_name,ifnull(AV.bar_code,hii_goods.bar_code)bar_code,hii_goods_cate.title as cate_name";
        $field .= ",floor(ifnull(hii_warehouse_stock.num,0)) as stock_num,hii_request_temp.g_num,hii_request_temp.g_num,hii_request_temp.remark,AV.value_id,AV.value_name";
        $list = M('RequestTemp')
            ->join('left join hii_goods on hii_request_temp.goods_id=hii_goods.id')
            ->join('left join hii_goods_cate on hii_goods.cate_id=hii_goods_cate.id')
            ->join('left join (select * from hii_warehouse_stock where w_id = ' .$this->_warehouse_id. ') hii_warehouse_stock on hii_warehouse_stock.value_id=hii_request_temp.value_id')
            ->join('left join hii_attr_value AV on AV.value_id=hii_request_temp.value_id')
            ->field($field)
            ->where($where)->order('ctime asc')->select();
        //读取区域价
        $GoodsStoreModel = M("GoodsStore");
        $warehouse_data = M("Warehouse")->where(" `w_id`={$this->_warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];
        foreach ($list as $key => $val) {
            $sql = "select GS.shequ_price ";
            $sql .= "from hii_goods_store GS ";
            $sql .= "where GS.goods_id={$val["goods_id"]} and GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) ";
            $sql .= "group by GS.shequ_price ";
            $tmp_data = $GoodsStoreModel->query($sql);
            if (!is_null($tmp_data) && !empty($tmp_data) && count($tmp_data) > 0 && $tmp_data[0]["shequ_price"] > 0) {
                $list[$key]["sell_price"] = $tmp_data[0]["shequ_price"];
            }
        }
        //分页
        $pcount=15;
        $count=count($list);//得到数组元素个数
        $Page= new \Think\Page($count,$pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($list,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// 分页显示输出﻿
        $this->assign('list', $list);

        $this->assign('list', $datamain);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);
        $this->meta_title = '临时申请列表';
        $this->display(T('Addons://Warehouse@WarehousePurchase/temp'));
    }

    public function send_request_temp() {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $remark = I('remark', '');
        if ($remark == '') {
            $remark = $_POST['remark'];
        }
        $where = array();
        $where['admin_id'] = UID;
        $where['warehouse_id'] = $this->_warehouse_id;
        $where['temp_type'] = 1;
        $where['hii_request_temp.status'] = 0;
        $field = "id,goods_id,g_num,remark,value_id";
        $WarehouseModel = M('Warehouse');
        $w = $WarehouseModel->where('w_id=' .$this->_warehouse_id)->select();
        $shequ_id = $w[0]['shequ_id'];
        $pcount = 0;
        $nums = 0;
        $DetailList = M('RequestTemp')
            ->field($field)
            ->where($where)->order('ctime asc')->select();
        if(!count($DetailList)>0){
            $this->error('没有申请商品');
        }
        foreach($DetailList as $k=>$v){
            $DetailList[$k]['is_pass'] = 0;
            $DetailList[$k]['pass_num'] = 0;
            $pcount++;
            $nums += $DetailList[$k]['g_num'];

        }
        $p_r_id = 0;
        $new_no = get_new_order_no('SQ','hii_purchase_request','p_r_sn');
        $data = array();
        $data['p_r_id'] = $p_r_id;
        $data['p_r_sn'] = $new_no;
        $data['p_r_type'] = 0;
        $data['p_r_status'] = 0;
        $data['ctime'] = time();
        $data['admin_id'] = UID;
        $data['warehouse_id'] = $this->_warehouse_id;
        $data['remark'] = $remark;
        $data['g_type'] = $pcount;
        $data['g_nums'] = $nums;
        $Model = D('Addons://Warehouse/PurchaseRequest');
        $res = $Model->savePurchaseRequest($p_r_id,$data,$DetailList,false);
        if($res>0){
            $Model1 = D('Addons://Warehouse/RequestTemp');
            $DeleteList1 =$Model1->where($where)->delete();
            $ModelMsg = D('Erp/MessageWarn');
            $msg = $ModelMsg->pushMessageWarn(UID  ,0 , 0 ,$shequ_id  , '有新的采购申请' ,8);
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
            $where['temp_type'] = 1;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,g_num,remark')->find();
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
            $where['temp_type'] = 1;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,g_num,remark')->find();
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
        $attrValueModel = M("AttrValue");
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

        foreach($list as $k => $v){
            $attr_value = $attrValueModel->field('value_id,value_name')->where(array('goods_id'=>$v['id'],'status'=>1))->select();
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
        $this->display(T('Addons://Warehouse@WarehousePurchase/get_goods_lists'));
    }

    public function update()
    {
        $g_id = I('goods_id', '');
        if ($g_id == '') {
            $g_id = $_POST['goods_id'];
        }
        $g_num = I('g_num', '');
        if ($g_num == '') {
            $g_num = $_POST['g_num'];
        }
        $remark = I('remark_add', '');
        $value_id = I('value_id', 0);
        if($value_id == 0){
        	$this->error('请选择属性');
        }
        if ($remark == '') {
            $remark = $_POST['remark_add'];
        }

        if($g_id == '0'){
            $this->error('不存在的商品ID');
        }
        if($g_num == '' || $g_num == '0'){
            $this->error('必须输入申请数量');
        }
        $temp_id = I('get.temp_id','');
        $Model = M('RequestTemp');
        if ($g_id != '') {
            //如果$temp_id临时申请表id不未空 按id删除后重新生成
            if (!empty($temp_id)) {
                //更新
                $saveData["goods_id"] = $g_id;
                $saveData["remark"] = $remark;
                $saveData["g_num"] = $g_num;
                $saveData["value_id"] = $value_id;
                $result = $Model->where(" id={$temp_id} ")->save($saveData);
                if ($result === false) {
                    $this->error($Model->getError());
                } else {
                    //判断是否有重复商品属性如果有删除一个
                    $delete_where = array();
                    $delete_where['admin_id'] = UID;
                    $delete_where['goods_id'] = $g_id;
                    $delete_where['warehouse_id'] = $this->_warehouse_id;
                    $delete_where['temp_type'] = 1;
                    $delete_where['value_id'] = $value_id;
                    $delete_where['hii_request_temp.status'] = 0;
                    $delete_where['hii_request_temp.id'] = array('NEQ',$temp_id);
                    $Model->where($delete_where)->delete();
                    $this->success($temp_id ? '更新成功' : '修改成功');
                }

            } else {
                $where['admin_id'] = UID;
                $where['goods_id'] = $g_id;
                $where['warehouse_id'] = $this->_warehouse_id;
                $where['temp_type'] = 1;
                $where['value_id'] = $value_id;
                $where['hii_request_temp.status'] = 0;

                $data = $Model->where($where)->select();
                if ($data) {
                    //已经存在
                    $data1['g_num'] = $g_num;
                    $data1['ctime'] = time();
                    $data1['remark'] = $remark;
                    $data1['warehouse_id'] = $this->_warehouse_id;
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
                    $data1['temp_type'] = 1;
                    $data1['warehouse_id'] = $this->_warehouse_id;
                    $data1['status'] = 0;
                    $data1['g_num'] = $g_num;
                    $data1['ctime'] = time();
                    $data1['remark'] = $remark;
                    $data1['value_id'] = $value_id;
                    $Model = D('Addons://Warehouse/RequestTemp');
                    $res = $Model->saveRequestTemp($id, $data1,false);
                    //$res = $Model->update();
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
        $where['temp_type'] = 1;
        $where['warehouse_id'] = $this->_warehouse_id;
        $where['hii_request_temp.status'] = 0;
        $Model = M('RequestTemp');
        $data = $Model->where($where)->delete();
        $this->success('清空成功');
    }
}
