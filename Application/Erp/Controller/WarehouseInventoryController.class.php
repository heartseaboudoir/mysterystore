<?php

namespace Erp\Controller;
use Think\Controller;


class WarehouseInventoryController extends AdminController {

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

        $dataout['s_date'] = $s_date;
        $dataout['e_date'] = $e_date;

        $where = " and A.warehouse_id = " .$this->_warehouse_id;//当前选择仓库
        $where .= " and A.i_type = 0";//当前普通盘点

        $w_name = session('user_warehouse.w_name');
        $title = $s_date. '>>>' .$e_date.'>>>' .$w_name. '盘点单';

        $Model = M('WarehouseInventory');

        $sql = "
            select A.i_id,i_sn,i_status,
            FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,
            FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,eadmin_id,B1.nickname as enickname,
            FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,padmin_id,B2.nickname as pnickname,
            A.warehouse_id,W.w_name,A.remark,A.g_type,A.g_nums,SUM(A1.b_num)as b_nums,ifnull(SUM(WS.num),0)as ws_nums,
            sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
             from  hii_warehouse_inventory A
              left join hii_warehouse_inventory_detail A1 on A.i_id=A1.i_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.eadmin_id=B1.uid
              left join hii_member B2 on A.padmin_id=B2.uid
              left join hii_warehouse W on A.warehouse_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_goods G on A1.goods_id=G.id
              LEFT JOIN hii_warehouse_stock WS ON A.warehouse_id=WS.w_id AND WS.`value_id` = A1.`value_id`
              where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where ."
            group by A.i_id,i_sn,i_status,
            A.ctime,A.admin_id,B.nickname,
            A.etime,A.eadmin_id,B1.nickname,
            A.ptime,A.padmin_id,B2.nickname,
            A.warehouse_id,W.w_name,A.remark,A.g_type,A.g_nums
            order by A.i_id desc
      ";
        $datalist = $Model->query($sql);
        //$dataout['sql'] = $sql;
        //print_r($sql);die;
        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/WarehouseInventory_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseInventoryList($datalist,$title,$fname);
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
        $datamain = array_slice($datalist,$Page->firstRow,$Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $dataout['pageSize'] = $pcount;
        $dataout['recordCount'] = $count;
        $dataout['p'] = $p;
        $dataout['pager'] = $show;
        $dataout['pages'] = ceil($count/$pcount);

        $dataout['list'] = $datamain;
        $dataout["show_del"] = $this->checkFunc('DelIncentory');
        $this->response(self::CODE_OK, $dataout);
    }

    public function monthlist()
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

        $dataout['s_date'] = $s_date;
        $dataout['e_date'] = $e_date;

        $where = " and A.warehouse_id = " .$this->_warehouse_id;//当前选择仓库
        $where .= " and A.i_type = 1";//当前月末盘点

        $w_name = session('user_warehouse.w_name');
        $title = $s_date. '>>>' .$e_date.'>>>' .$w_name. '月末盘点单';

        $Model = M('WarehouseInventory');

        $sql = "
             select A.i_id,i_sn,i_status,
            FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,
            FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,eadmin_id,B1.nickname as enickname,
            FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') as ptime,padmin_id,B2.nickname as pnickname,
            A.warehouse_id,W.w_name,A.remark,A.g_type,A.g_nums,SUM(A1.b_num)as b_nums,SUM(WS.num)as ws_nums,
            sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
             from  hii_warehouse_inventory A
              left join hii_warehouse_inventory_detail A1 on A.i_id=A1.i_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.eadmin_id=B1.uid
              left join hii_member B2 on A.padmin_id=B2.uid
              left join hii_warehouse W on A.warehouse_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_goods G on A1.goods_id=G.id
              LEFT JOIN hii_warehouse_stock WS ON A.warehouse_id=WS.w_id AND WS.`value_id` = A1.`value_id`
              where FROM_UNIXTIME(A.ctime,'%Y-%m-%d')  between '" .$s_date. "' and '" .$e_date. "'" .$where ."
            group by A.i_id,i_sn,i_status,
            A.ctime,A.admin_id,B.nickname,
            A.etime,A.eadmin_id,B1.nickname,
            A.ptime,A.padmin_id,B2.nickname,
            A.warehouse_id,W.w_name,A.remark,A.g_type,A.g_nums
            order by A.i_id desc
      ";
        $datalist = $Model->query($sql);
        //$dataout['sql'] = $sql;
        //print_r($sql);die;
        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/WarehouseInventory_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseInventoryList($datalist,$title,$fname);
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
        $datamain = array_slice($datalist,$Page->firstRow,$Page->listRows);
        $show = $Page->show();// 分页显示输出﻿
     /*   foreach ($datamain as $key => $val) {
            switch ($val["i_status"]) {
                case 0: {
                     //如果新增显示当前的商品库存数量  如果已审核显示审核时的商品数量
                    $goods_id = M("WarehouseInventoryDetail")->field("goods_id")->where(array('i_id'=>$val['i_id']))->select();
                    $datamain[$key]['b_nums'] = M("WarehouseStock")->where(array('w_id'=>$this->_warehouse_id,'goods_id'=>array("in",array_column($goods_id,"goods_id"))))->sum('num');
                };
                    break;
            }
        }*/
        $dataout['pageSize'] = $pcount;
        $dataout['recordCount'] = $count;
        $dataout['p'] = $p;
        $dataout['pager'] = $show;
        $dataout['pages'] = ceil($count/$pcount);

        $dataout['list'] = $datamain;
        $dataout["show_del"] = $this->checkFunc('DelIncentory');
        $this->response(self::CODE_OK, $dataout);
    }

    /****************
     * 新增月末盘点单
     * 请求方式：POST
     ***************/
    public function newmonth()
    {
        $start = date('Y-m-01 00:00:00');
        $end = date('Y-m-d H:i:s');
        $sql = "
        select * from hii_warehouse_inventory WHERE warehouse_id=" . $this->_warehouse_id . " and `ctime` >= unix_timestamp('".$start."') AND `ctime` <= unix_timestamp('" .$end ."') AND  i_type = 1
        ";
        $GoodsModel = M("WarehouseInventory");
        $DetailList = $GoodsModel->query($sql);
        if($DetailList){
            $this->response(999, '本月已经做过月末盘点单了，不允许再次做月末盘点');
        }
        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
        $shequ_id = $warehousedata['shequ_id'];

        $sql = "SELECT
                	C.goods_id,
                	0 AS g_num,
                	ifnull(D.stock_price, 0) AS g_price,
                	C.value_id
                FROM
                (
                	SELECT
                		AV.goods_id,
                		AV.value_id
                	FROM
                		(
                			SELECT
                				DISTINCT goods_id
                			FROM
                				hii_warehouse_stock
                			WHERE
                				w_id = {$warehouse_id}
                		) WS
                	inner JOIN hii_attr_value AV ON AV.goods_id = WS.goods_id
                ) C 
                left JOIN (
                	SELECT
                		*
                	FROM
                		hii_warehouse_inout_view
                	WHERE
                		shequ_id = {$shequ_id}
                ) D ON C.goods_id = D.goods_id
                ";
        $GoodsModel = M("Goods");
        $DetailList = $GoodsModel->query($sql);
        $remark = '月末盘点单';

        if (is_array($DetailList) && count($DetailList) > 0) {
            $pcount = 0;
            $nums = 0;
            if(!count($DetailList)>0){
                $this->response(999, '没有申请商品');
            }
          
            foreach($DetailList as $k=>$v){
                $pcount++;
                $nums += $DetailList[$k]['g_num'];
            }
            $i_id = 0;
            $new_no = get_new_order_no('PD','hii_warehouse_inventory','i_sn');
            $data = array();
            $data['i_id'] = $i_id;
            $data['i_sn'] = $new_no;
            $data['i_status'] = 0;
            $data['i_type'] = 1;
            $data['ctime'] = time();
            $data['admin_id'] = UID;
            $data['warehouse_id'] = $this->_warehouse_id;
            $data['remark'] = $remark;
            $data['g_type'] = $pcount;
            $data['g_nums'] = $nums;
            $Model = D('Addons://Warehouse/WarehouseInventory');
            $res = $Model->saveWarehouseInventory($i_id,$data,$DetailList,false);
            if($res>0){
                $this->response(self::CODE_OK, $res);
            }else{
                $this->response(999, $Model->err['msg']);
            }
        }else{
            $this->response(999, "错误：没有数据");
        }
    }

    public function view()
    {

        $data = $this->gv();
        $id = $data['id'];
        $cate_id = $data['cate_id'];
        $is_disable = $data['is_disable'];

        if ($id == "" || $id == "0"){
            $this->response(999, "没有id");
        }

        $where = "A.i_id=" .$id;
        $where .= " and A.warehouse_id = " .$this->_warehouse_id;
        $dataout['id'] = $id;

        if ($is_disable != "1"){
            $where0 = $where ." And (WS.num>0 or (A1.b_num-g_num)<>0)";
        }else{
            $where0 = $where;
        }

        $Model = M('WarehouseInventory');
        $sql = "
            select A.i_id,i_sn,i_status,A.i_type,
            FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,B.nickname,
            FROM_UNIXTIME(A.etime,'%Y-%m-%d %H:%i:%s') as etime,eadmin_id,B1.nickname as enickname,
            (case A.ptime when 0 then 0 else FROM_UNIXTIME(A.ptime,'%Y-%m-%d %H:%i:%s') end) as ptime,padmin_id,B2.nickname as pnickname,
            A.warehouse_id,W.w_name,A.remark,A.g_type,A.g_nums,A.i_type,
            sum(A1.g_num*(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end)) as g_amounts,sum(A1.g_num*A1.g_price) as p_amounts
             from  hii_warehouse_inventory A
              left join hii_warehouse_inventory_detail A1 on A.i_id=A1.i_id
              left join hii_member B on A.admin_id=B.uid
              left join hii_member B1 on A.eadmin_id=B1.uid
              left join hii_member B2 on A.padmin_id=B2.uid
              left join hii_warehouse W on A.warehouse_id=W.w_id
        left join hii_shequ_price SP on W.shequ_id=SP.shequ_id and A1.goods_id=SP.goods_id
              left join hii_goods G on A1.goods_id=G.id
              where " .$where ."
            group by A.i_id,i_sn,i_status,
            A.ctime,A.admin_id,B.nickname,
            A.etime,A.eadmin_id,B1.nickname,
            A.ptime,A.padmin_id,B2.nickname,
            A.warehouse_id,W.w_name,A.remark,A.g_type,A.g_nums
            order by A.i_id desc
      ";
        $datalist = $Model->query($sql);
        if(is_array($datalist) && count($datalist)>0){
            $title =  $datalist['i_sn'] .'盘点单查看';
        }else{
            $this->response(999,'失败');
        }

        if($cate_id != 0 && $cate_id != ''){
            $where0 .= " and A1.goods_id in (select id as goods_id from hii_goods where cate_id = $cate_id)";
        }

        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
        $shequ_id = $warehousedata['shequ_id'];

        $sql = "
        select AV.value_name,AV.value_id,A.i_id,A.i_sn,C.id as cate_id,C.title as cate_name,(case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else G.sell_price end) as sell_price,floor(ifnull(WS.num,0)) as stock_num,
        A1.i_d_id,A1.goods_id,A1.audit_mark,G.title as goods_name,ifnull(AV.bar_code,G.bar_code)bar_code,A1.b_num,A1.g_num,A1.g_price,A1.remark,(case when A.i_status = 1 then floor(A1.g_num-A1.b_num) else floor(A1.g_num-ifnull(WS.num,0)) end) as add_num,ifnull(I.stock_price,0) as stock_price
         from  hii_warehouse_inventory A
         left join hii_warehouse_inventory_detail A1 on A.i_id=A1.i_id
         left join hii_goods G on A1.goods_id=G.id
              left join hii_warehouse W on A.warehouse_id=W.w_id
        left join hii_shequ_price SP on SP.shequ_id=$shequ_id and A1.goods_id=SP.goods_id
         left join hii_goods_cate C on G.cate_id=C.id
         left join hii_warehouse_stock WS on A1.value_id=WS.value_id and WS.w_id=$warehouse_id
         left join (select * from hii_warehouse_inout_view where shequ_id = $shequ_id) I on A1.goods_id=I.goods_id
         left join hii_attr_value AV on A1.value_id=AV.value_id
         where " .$where0 ." order by G.cate_id asc,G.id asc
         ";
        $datachild = $Model->query($sql);
        //print_r($sql);die;
        $cate_ary = array_column($datachild,'cate_id');
        $catewhere = " and id in (" .implode(',',$cate_ary). ")";
        $sql = "
        select id as cate_id,title as cate_name from hii_goods_cate where 1=1 $catewhere
         ";
        $Modelcate = M('GoodsCate');
        $datacate = $Modelcate->query($sql);

        $isprint = $data['isprint'];
        if($isprint == 1) {
            ob_clean;
            $fname = './Public/Excel/WarehouseInventoryView_'.$datalist[0]['i_id'] .'_'.time().'.xlsx';
            //$fname = $title;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushWarehouseInventoryView($datalist[0],$datachild,$title,$fname);
            $dataout1['filename'] = $printfile;
            $this->response(self::CODE_OK, $dataout1);
        }
         
        $dataout['cate'] = $datacate;
        $dataout['main'] = $datalist;
        $dataout['child'] = $datachild;


        $this->response(self::CODE_OK, $dataout);
    }

    public function save()
    {
        $data = $this->gv();
        $id =  $data['id'];
        $remark =  $data['remark'];
        $goods_list =  $data['goods_list'];
        /*$back_ary = array();
        foreach($goods_list as $k=>$v){
            //查盘点单有没有库存平均价
            if(($goods_list[$k]['stock_num']-$goods_list[$k]['g_num']) != 0 && $goods_list[$k]['g_price'] == 0){
                $back_ary['goods_id'] = $goods_list[$k]['goods_id'];
                $back_ary['msg'] = '该商品ID:'.$goods_list[$k]['goods_id'].'没有盘点价';
                $this->response(999, $back_ary);
            }
        }*/

        $where = " i_id = " .$id;
        $where1 = " and i_status = 0";
        $where1 .= " and warehouse_id = " .$this->_warehouse_id;

        //主表
        $Model = M('WarehouseInventory');
        $dataMain1 = $Model->where($where .$where1)->select();
        if(is_array($dataMain1) && count($dataMain1)>0){
            $dataMain = $dataMain1[0];
            $dataMain['remark'] = $remark;
            $dataMain['etime'] = time();
            $dataMain['eadmin_id'] = UID;
        }else{
            $this->response(999, '没有盘点单或者盘点单已处理，不能再次编辑');
        }

        //子表
        $Model = M('WarehouseInventoryDetail');
        $dataChild = $Model->where($where)->select();
        $goods_list_array = array();
        foreach($goods_list as $k=>$v){
        	$goods_list_array[$v['i_d_id']] = $v;
        }
        unset($goods_list);
       // M('Error')->add(array('info'=>json_encode($goods_list_array)));
        if(is_array($dataChild) && count($dataChild)>0){
            foreach($dataChild as $k=>$v){
            	if(array_key_exists($v['i_d_id'] ,$goods_list_array)){
            		//$havethis = 1;
            		$dataChild[$k]['b_num'] = $goods_list_array[$v['i_d_id']]['stock_num'];
            		$dataChild[$k]['g_num'] = $goods_list_array[$v['i_d_id']]['g_num'];
            		//$dataChild[$k]['g_price'] = $goods_list[$k1]['g_price'];
            		$dataChild[$k]['remark'] = $goods_list_array[$v['i_d_id']]['remark_detail'];
            		$dataChild[$k]['audit_mark'] = $goods_list_array[$v['i_d_id']]['audit_mark'];
            	}
                //$havethis = 0;
            	/* if($dataChild[$k]['i_d_id'] == $goods_list[$k1]['i_d_id']){
            
            	} */
                /*if($havethis == 0){
                    $this->response(999, '该商品ID:'.$dataChild[$k]['goods_id'].'没有传递值');
                }*/
            }
        }else{
            $this->response(999, '盘点单没有商品');
        }
        $Model2 = D('Addons://Warehouse/WarehouseInventory');
        $res1 = $Model2->saveWarehouseInventory($id, $dataMain, $dataChild,false);
        if($res1 > 0){
            $this->response(self::CODE_OK, '盘点单保存成功');
        }else{
            $this->response(999, '盘点单保存失败:'.$Model2->err['msg']);
        }
    }
    /**
     * 盘点单 审核
     *  id  盘点单id
     *   pass  审核 1 审核  2 删除
     */
    public function pass(){
        $id = I('id',0,'intval');
        $pass = I('pass',1,'intval');
        $warehouse_id = $this->_warehouse_id;
        $admin_id = UID;
        if(!$id){
            $this->response(999,'没有盘点id');
        }

        $warehouseInventoryModel = D('WarehouseInventory');
        if($pass == 1){
            //审核
            $info = $warehouseInventoryModel->check($id,$warehouse_id,$admin_id);
            if($info['status'] == 200){
                $this->response(self::CODE_OK,'成功');
            }else{
                $this->response(999,$info['msg']);
            }
        }elseif($pass == 2){
            //删除
            $info = $warehouseInventoryModel->is_delete($id);
            if($info['status'] == 0){
                $this->response(999,$info['msg']);
            }else{
                $this->response(self::CODE_OK,'成功');
            }

        }

    }

    /**
     * 弃用
     */
    public function pass_q()
    {
        $data = $this->gv();
        $id = $data['id'];
        if ($id == "" || $id == "0"){
            $this->response(999, "没有id");
        }
        $dataout['id'] = $id;

        $where = " i_id = " .$id;
        $where1 = " and i_status = 0";
        $where1 .= " and warehouse_id = " .$this->_warehouse_id;


        $Model = M('WarehouseInventory');
        $dataMain1 = $Model->where($where .$where1)->select();
        if(is_array($dataMain1) && count($dataMain1)>0){
            $dataMain = $dataMain1[0];
        }else{
            $this->response(999, '没有盘点单或者盘点单已处理，不能再次审核');
        }
        $Model = M('WarehouseInventoryDetail');
        $dataChild = $Model->alias('A')->join('left join hii_warehouse_stock WS on WS.value_id=A.value_id and WS.w_id='.$this->_warehouse_id)->where($where)
            ->field('A.i_d_id,A.i_id,A.goods_id,A.g_num,ifnull(WS.num,0)b_num,A.g_price,A.remark,A.value_id')
            ->select();
        if(is_array($dataChild) && count($dataChild)>0){
            $dataChild = $dataChild;
        }else{
            $this->response(999, '盘点单没有商品');
        }

        $pass = $data['pass'];
        if($pass == 0 || $pass == ""){
            $this->response(999, '没有参数');
        }
        if($data['pass'] == 1){
            //判断盘点商品是否修改
            $audit_mark = $Model->field("goods_id,value_id")->where(array("i_id"=>$id,"audit_mark"=>0))->select();
            if(!is_null($audit_mark) || !empty($audit_mark) || count($audit_mark)!=0){
            	foreach ($audit_mark as $kk=>$vv){
            		$audit_mark_id = M("WarehouseStock")->where(array("w_id"=>$this->_warehouse_id,"num"=>array("NEQ",0),"goods_id"=>$vv['goods_id'],'value_id'=>$vv['value_id']))->find();
            		if(count($audit_mark_id)!=0)
            			$this->response(999,"盘点单商品未盘点 不能审核");
            	}
              
            }
        }
        $dataMain['ptime'] = time();
        $dataMain['padmin_id'] = UID;
        $dataMain['i_status'] = $pass;


        $sql = "
        select A1.*,(A1.g_num-ifnull(WS.num,0)) as add_num
         from  hii_warehouse_inventory A
         left join hii_warehouse_inventory_detail A1 on A.i_id=A1.i_id
         left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id=WS.w_id
         where A.i_id=$id" .$where1 ." order by A1.i_d_id asc
         ";
        $DetailList = $Model->query($sql);

        if(!count($DetailList)>0){
            $this->response(999, '盘点单没有商品');
        }

        if ($pass == '1') {
            /*foreach($DetailList as $k=>$v){
                //查盘点单有没有库存平均价
                $back_ary = array();
                if($DetailList[$k]['add_num'] != 0 && $DetailList[$k]['g_price'] == 0){
                    $back_ary['goods_id'] = $DetailList[$k]['goods_id'];
                    $back_ary['msg'] = '该商品ID:'.$DetailList[$k]['goods_id'].'没有盘点价';
                    $this->response(999, $back_ary);
                }
            }*/
            $g_nums = 0;
            $pcount1 = 0;
            $nums1 = 0;
            $pcount2 = 0;
            $nums2 = 0;
            $DetailListWI1 = array();
            $DetailListWI2 = array();
            $DetailListTmp = array();
            foreach($DetailList as $k=>$v) {
                $g_nums += $v['g_num'];
                if($DetailList[$k]['add_num'] == 0) {
                    //盘点正确，没有盘盈也没有盘亏
                }else {
                    if ($DetailList[$k]['add_num'] > 0) {
                        //盘盈写入入库单
                        $DetailListTmp['goods_id'] = $DetailList[$k]['goods_id'];
                        $DetailListTmp['g_num'] = abs($DetailList[$k]['add_num']);
                        $DetailListTmp['g_price'] = $DetailList[$k]['g_price'];
                        $DetailListTmp['remark'] = $DetailList[$k]['remark'];
                        $DetailListTmp['w_in_d_id'] = $DetailList[$k]['i_d_id'];
                        $DetailListTmp['value_id'] = $DetailList[$k]['value_id'];
                        $DetailListTmp['endtime'] = time()+30*24*60*60;
                        $DetailListWI1[] = $DetailListTmp;
                        $pcount1++;
                        $nums1 += abs($DetailList[$k]['add_num']);
                    } else {
                        //盘亏写入出库单
                        $DetailListTmp['goods_id'] = $DetailList[$k]['goods_id'];
                        $DetailListTmp['g_num'] = abs($DetailList[$k]['add_num']);
                        $DetailListTmp['g_price'] = $DetailList[$k]['g_price'];
                        $DetailListTmp['remark'] = $DetailList[$k]['remark'];
                        $DetailListTmp['w_in_d_id'] = $DetailList[$k]['i_d_id'];
                        $DetailListTmp['value_id'] = $DetailList[$k]['value_id'];
                        $DetailListWI2[] = $DetailListTmp;
                        $pcount2++;
                        $nums2 += abs($DetailList[$k]['add_num']);

                    }
                }
            }
            $dataMain['g_nums'] = $g_nums;
            $havepd = 0;
            if(is_array($DetailListWI1) && count($DetailListWI1) > 0){
                $havepd = 1;
                //审核并新增入库单
                $w_in_id = 0;
                $new_no = get_new_order_no('RK','hii_warehouse_in_stock','w_in_s_sn');
                $dataWI = array();
                $dataWI['w_in_s_sn'] = $new_no;
                $dataWI['w_in_s_status'] = 0;
                $dataWI['w_in_s_type'] = 3;
                $dataWI['i_id'] = $dataMain['i_id'];
                $dataWI['w_in_id'] = 0;
                $dataWI['ctime'] = time();
                $dataWI['admin_id'] = UID;
                $dataWI['etime'] = 0;
                $dataWI['eadmin_id'] = 0;
                $dataWI['ptime'] = 0;
                $dataWI['padmin_id'] = 0;
                $dataWI['supply_id'] = 0;
                $dataWI['warehouse_id'] = $dataMain['warehouse_id'];
                $dataWI['remark'] = $dataMain['remark'];
                $dataWI['g_type'] = $pcount1;
                $dataWI['g_nums'] = $nums1;
                $Model1 = D('Addons://Warehouse/WarehouseInStock');
                $res1 = $Model1->saveWarehouseInStock($w_in_id,$dataWI,$DetailListWI1,false);
                if($res1>0){
                    $this->passin($res1,1);
                }else{
                    $this->response(999, '新增入库单失败:'.$Model1->err['msg']);
                }
            }
            if(is_array($DetailListWI2) && count($DetailListWI2) > 0){
                $havepd = 1;
                //审核并新增出库单
                $w_out_id = 0;
                $new_no = get_new_order_no('CK','hii_warehouse_out_stock','w_out_s_sn');
                $dataWI = array();
                $dataWI['w_out_s_sn'] = $new_no;
                $dataWI['w_out_s_status'] = 0;
                $dataWI['w_out_s_type'] = 3;
                $dataWI['i_id'] = $dataMain['i_id'];
                $dataWI['w_out_id'] = 0;
                $dataWI['ctime'] = time();
                $dataWI['admin_id'] = UID;
                $dataWI['etime'] = 0;
                $dataWI['eadmin_id'] = 0;
                $dataWI['ptime'] = 0;
                $dataWI['padmin_id'] = 0;
                $dataWI['store_id'] = 0;
                $dataWI['warehouse_id1'] = 0;
                $dataWI['warehouse_id2'] = $dataMain['warehouse_id'];
                $dataWI['remark'] = $dataMain['remark'];
                $dataWI['g_type'] = $pcount2;
                $dataWI['g_nums'] = $nums2;
                $Model1 = D('Addons://Warehouse/WarehouseOutStock');
                $res1 = $Model1->saveWarehouseOutStock($w_out_id,$dataWI,$DetailListWI2,false);
                if($res1>0){
                    //审核出库单
                    $this->passout($res1,1);
                }else{
                    $this->response(999, '新增出库单失败:'.$Model1->err['msg']);
                }
            }
            if($havepd == 1) {
                $Model2 = D('Addons://Warehouse/WarehouseInventory');
                $res1 = $Model2->saveWarehouseInventory($id, $dataMain, $dataChild,false);
                if ($res1 > 0) {
                    $this->response(self::CODE_OK, '盘点审核成功');
                } else {
                    $this->response(999, '盘点审核失败:' . $Model2->err['msg']);
                }
            }else{
                $Model2 = D('Addons://Warehouse/WarehouseInventory');
                $res1 = $Model2->saveWarehouseInventory($id, $dataMain, $dataChild,false);
                if ($res1 > 0) {
                    $this->response(self::CODE_OK, '盘点审核成功');
                } else {
                    $this->response(999, '盘点审核失败:' . $Model2->err['msg']);
                }
                //$this->response(999, '盘点审核失败:没有盘盈也没有盘亏，不能审核');
            }
        }else{
            if ($pass == '2') {
                $quanxian = $this->checkFunc('DelIncentory');
                if($quanxian == true) {
                    $Model2 = D('Addons://Warehouse/WarehouseInventory');
                    $res1 = $Model2->delWarehouseInventory($id, $dataMain, $dataChild,false);
                    if ($res1 > 0) {
                        $this->response(self::CODE_OK, '盘点删除成功');
                    } else {
                        $this->response(999, '盘点删除失败:' . $Model2->err['msg']);
                    }
                }else{
                    $this->response(999, '盘点删除失败:没有权限');
                }
            }else{
                $this->response(999, '错误参数:' .$pass);
            }
        }
    }

    public function passin($w_in_s_id,$pass)
    {
        $where = array();
        $where['w_in_s_id'] = $w_in_s_id;

        $Model = M('WarehouseInStock');
        $dataMain = $Model->where($where)->find();
        if(!count($dataMain)>0){
            $this->response(999, '没有入库单');
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
            $this->response(999, '入库单没有商品');
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
                    $DetailListWarehouseInOut['etime'] = time();
                    if($dataMain['w_in_s_type'] == 0){
                        $DetailListWarehouseInOut['ctype'] = 0;
                    }else{
                        $DetailListWarehouseInOut['ctype'] = 1;
                    }
                    $DetailListWarehouseInOut['enum'] = $DetailList[$k]['g_num'];
                    $DetailListWarehouseInOut['e_id'] = $dataMain['w_in_s_id'];
                    $DetailListWarehouseInOut['e_no'] = 0;
                    if($dataMain['w_in_s_type'] == 0){
                        $DetailListWarehouseInOut['ctype'] = 0;
                    }else{
                        $DetailListWarehouseInOut['ctype'] = 1;
                    }
                    $DetailListWarehouseInOut['w_in_s_d_id'] = $DetailList[$k]['w_in_s_d_id'];
                    $DetailListWarehouseInOut['value_id'] = $DetailList[$k]['value_id'];
                    //新增入库批次
                    $inout_id = 0;
                    $res = $Model->saveWarehouseInout($inout_id, $DetailListWarehouseInOut, false);
                    if($res > 0){
                        //$this->success('入库批次成功', Cookie('__forward__'));
                    }else{
                        $this->response(999, '入库批次失败1' .$Model->err['msg']);
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
                    //$this->success('库存入库成功', Cookie('__forward__'));
                }else{
                    $this->response(999, '入库审核失败1' .$Model->err['msg']);
                }
            }
            $Model1 = D('Addons://Warehouse/WarehouseInStock');
            $res1 = $Model1->saveWarehouseInStock($w_in_s_id, $dataMain, $DetailList,false);
            if($res1 > 0){
                return $res1;
                //$this->success('入库审核成功', Cookie('__forward__'));
            }else{
                $this->response(999, '入库审核失败2' .$Model->err['msg']);
            }
        }else{
            $this->response(999, '错误参数');
        }
    }


    public function passout($id,$pass)
    {
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

        if($pass == 0 || $pass == ""){
            $this->response(999, '没有参数');
        }

        $dataMain['ptime'] = time();
        $dataMain['padmin_id'] = UID;
        $dataMain['w_out_s_status'] = $pass;


        $field = "w_out_s_d_id,w_out_s_id,goods_id,g_num,g_price,w_out_d_id,remark,value_id";
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
                    $this->response(999, '出库审核时：该商品ID:'.$DetailList[$k]['goods_id'].'没有库存');
                }
            }
            $warehouse_id2 = $dataMain['warehouse_id2'];
            $WarehouseModel = M('Warehouse');
            $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id2) -> find();
            $shequ_id = $warehousedata['shequ_id'];
            $pcount = 0;
            $nums = 0;
            $g_num = 0;
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
                        $WarehouseInoutData = $Model->where('goods_id = ' .$DetailList[$k]['goods_id'] .' and num>0 and shequ_id = ' .$shequ_id. ' and warehouse_id <> ' .$dataMain['warehouse_id2'])->order('ctime asc')->select();
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
                    }
                    if($g_num > 0){//盘亏数量减掉【本区域仓库】入库批次的批次数量，还有多？！操作：减掉本区域门店入库批次的批次数量
                        $Model = M('WarehouseInout');
                        $WarehouseInoutData = $Model->where('goods_id = ' .$DetailList[$k]['goods_id'] .' and num>0 and shequ_id = ' .$shequ_id. ' and warehouse_id = 0 ')->order('ctime asc')->select();
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
                //修改库存表
                $where_stock = array();
                $where_stock['goods_id'] = $DetailList[$k]['goods_id'];
                $where_stock['w_id'] = $this->_warehouse_id;
                $where_stock['value_id'] = $DetailList[$k]['value_id'];
                $WarehouseStockList = M('WarehouseStock')->where($where_stock)->find();
                if(is_array($WarehouseStockList) && count($WarehouseStockList)>0){
                    if( ($WarehouseStockList['num'] - $DetailList[$k]['g_num']) < 0){
                        $this->response(999, '该商品ID:'.$DetailList[$k]['goods_id'].'库存小于出库数量');
                    }else{
                        $WarehouseStockList['num'] = $WarehouseStockList['num'] - $DetailList[$k]['g_num'];
                        $WarehouseStockList1[] = $WarehouseStockList;
                    }
                }else{
                    $this->response(999, '该商品ID:'.$DetailList[$k]['goods_id'].'没有库存');
                }
                $DetailListTmp['goods_id'] = $DetailList[$k]['goods_id'];
                $DetailListTmp['g_num'] = $DetailList[$k]['g_num'];
                $DetailListTmp['g_price'] = $DetailList[$k]['g_price'];
                $DetailListTmp['value_id'] = $DetailList[$k]['value_id'];
                $DetailListWI[] = $DetailListTmp;
                $pcount++;
                $nums += $DetailList[$k]['g_num'];
            }
            if($dataMain['w_out_s_type'] == 0){
                //仓库调拨，排除
            }else{
                if($dataMain['w_out_s_type'] == 1 || $dataMain['w_out_s_type'] == 5){
                    //门店申请，直接发货，排除
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
                                    return true;
                                    //$this->response(self::CODE_OK, '出库审核成功');
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



    public function temp()
    {
        $data = $this->gv();

        $page = $data['page'];
        if($page == "" || $page == 0){
            $page = 1;
        }
        //第几页
        $p1 = $data['p'];
        if($p1 == "" || $p1 == 0){
            $p1 = 1;
        }
        if($p1 != 1){
            $page = $p1;
        }
        //每页共多少条
        $pagesize = $data['pageSize'];
        if($pagesize == "" || $pagesize == 0){
            $pagesize = 2;
        }

        $list['cate'] = $this->catelist();
        $list['data'] = $this->getTempList($page,$pagesize);

        $this->response(self::CODE_OK, $list);
    }
    /*
     * 查询单个数据
     * 参数   goods_id  商品id
     * */
    public function getgoods()
    {
        $data = $this->gv();
        $goods_id = $data['goods_id'];
        $bar_code = $data['bar_code'];
        $temp_type = $data['temp_type'];
        $value_id = $data['value_id'];
        $warehouseStockModel = M("WarehouseStock");
        $warehouse_stock_id = $this->_warehouse_id;
        if($temp_type == 0 || $temp_type == '') {
            $temp_type = 7;
        }
        if(empty($goods_id) && empty($bar_code)){
            $this->response(999, '缺少商品id、条码参数！');
        }
        if(!empty($goods_id)){
            if($temp_type == 7 || $temp_type == 11){
                $goods_store_sql = $warehouseStockModel->field("distinct goods_id")->where(array("w_id"=>$warehouse_stock_id))->select(false);
                $datafind = M('Goods')->where(array('id' => $goods_id, 'status' => 1))->join("inner join {$goods_store_sql} as b on b.goods_id=id")->find();
            }else{
                $datafind = M('Goods')->where(array('id' => $goods_id, 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
            }

            if(!$datafind){
                $this->response(999, '没有该商品id！');
            }else{
                $data0 = M('GoodsBarCode')->where(array('goods_id' => $datafind['id']))->find();
                if(!$data0){
                    $this->response(999, '该商品条码没找到！');
                }
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datafind['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata['goods_id'] = $datafind['id'];
                $outdata['goods_name'] = $datafind['title'];
                $outdata['bar_code'] = $data0['bar_code'];
                $outdata['sell_price'] = $datafind['sell_price'];
                $outdata['attr_value_array'] = $attr_value_array;
                $where['admin_id'] = UID;
                $where['temp_type'] = $temp_type;
                $where['hii_request_temp.status'] = 0;
                $where['goods_id'] = $goods_id;
                if(!empty($value_id)){
                	$where['value_id'] =$value_id;
                }
                $data1 = M('RequestTemp')->where($where)->field('id,g_num,remark,value_id')->find();
                if(!$data1){
                    //不在临时盘点单中
                    $outdata['g_num'] = 0;
                }else{
                	$outdata['temp_id'] = $data1['id'];
                    $outdata['g_num'] = $data1['g_num'];
                    $outdata['remark'] = $data1['remark'];
                    $outdata['value_id'] = $data1['value_id'];
                    
                }
            }
            $this->response(self::CODE_OK,$outdata);
        }else{
            $data0 = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
            if(!$data0){
                $this->response(999, '该商品条码没找到！');
            }
            if($temp_type == 7 || $temp_type == 11){
                $goods_store_sql = $warehouseStockModel->field("distinct goods_id")->where(array("w_id"=>$warehouse_stock_id))->select(false);
                $datafind = M('Goods')->where(array('id' => $data0['goods_id'], 'status' => 1))->join("inner join {$goods_store_sql} as b on b.goods_id=id")->find();
            }else{
                $datafind = M('Goods')->where(array('id' => $data0['goods_id'], 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
            }
            if(!$datafind){
                $this->response(999, '该商品条码没找到1！');
            }else{
                $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$datafind['id'],'status'=>array('neq',2)))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $outdata['goods_id'] = $datafind['id'];
                $outdata['goods_name'] = $datafind['title'];
                $outdata['bar_code'] = $data0['bar_code'];
                $outdata['sell_price'] = $datafind['sell_price'];
                $outdata['attr_value_array'] = $attr_value_array;
                $where['admin_id'] = UID;
                $where['temp_type'] = $temp_type;
                $where['hii_request_temp.status'] = 0;
                $where['goods_id'] = $datafind['id'];
                if(!empty($value_id)){
                	$where['value_id'] =$value_id;
                }
                $data1 = M('RequestTemp')->where($where)->field('id,g_num,remark,value_id')->find();
                if(!$data1){
                    //不在临时盘点单中
                    $outdata['g_num'] = 0;
                }else{
                	$outdata['temp_id'] = $data1['id'];
                    $outdata['g_num'] = $data1['g_num'];
                    $outdata['remark'] = $data1['remark'];
                    $outdata['value_id'] = $data1['value_id'];
                }
            }
            $this->response(self::CODE_OK,$outdata);
        }
    }
    /*
     * 提交生成盘点单
     * */
    public function send_request_temp() {
        $data = $this->gv();
        $remark = $data['remark'];

        $where = array();
        $where['admin_id'] = UID;
        $where['temp_type'] = 7;
        $where['status'] = 0;
        $where['warehouse_id'] = $this->_warehouse_id;
        $field = "A.id,A.goods_id,ifnull(B.num,0) as b_num,A.g_num,A.g_price,A.remark,A.value_id";
        $pcount = 0;
        $nums = 0;
        $DetailList = M('RequestTemp')->alias('A')
            ->join('left join hii_warehouse_stock B on A.value_id=B.value_id and B.w_id='.$this->_warehouse_id)
            ->field($field)
            ->where($where)->order('ctime asc')->select();
        if(!count($DetailList)>0){
            $this->response(999, '没有申请商品');
        }
        foreach($DetailList as $k=>$v){
            $pcount++;
            $nums += $DetailList[$k]['g_num'];
        }
        $i_id = 0;
        $new_no = get_new_order_no('PD','hii_warehouse_inventory','i_sn');
        $data = array();
        $data['i_id'] = $i_id;
        $data['i_sn'] = $new_no;
        $data['i_status'] = 0;
        $data['i_type'] = 0;
        $data['ctime'] = time();
        $data['admin_id'] = UID;
        $data['warehouse_id'] = $this->_warehouse_id;
        $data['remark'] = $remark;
        $data['g_type'] = $pcount;
        $data['g_nums'] = $nums;
        $Model = D('Addons://Warehouse/WarehouseInventory');
        $res = $Model->saveWarehouseInventory($i_id,$data,$DetailList,false);
        if($res>0){
            $Model1 = D('Addons://Warehouse/RequestTemp');
            $DeleteList1 =$Model1->where($where)->delete();
            $this->response(self::CODE_OK, $res);
        }else{
            $this->response(999, $Model->err['msg']);
        }
    }
    /*
     * 删除单个数据
     * 参数   id  临时表id
     * */
    public function delete()
    {
        $data = $this->gv();
        $id = $data['id'];
        if ($id) {
            $Model = M('RequestTemp');
            $res = $Model->where("id = $id")->delete();
            if (!$res) {
                $error = $Model->getError();
                $this->response(999, '找不到要删除的数据！' .$error);
            } else {
                $this->response(self::CODE_OK, '删除成功！');
            }
        } else {
            $this->response(999, '请选择删除的数据！');
        }
    }
    /*
     * 清空临时盘点单
     * */
    public function cleantemp()
    {
        $where['admin_id'] = UID;
        $where['temp_type'] = 7;
        $where['hii_request_temp.status'] = 0;
        $where['warehouse_id'] = $this->_warehouse_id;
        $Model = M('RequestTemp');
        $data = $Model->where($where)->delete();
        if (!$data) {
            $error = $Model->getError();
            $this->response(999, '清空错误！' .$error);
        } else {
            $this->response(self::CODE_OK, '清空成功！');
        }
    }
    /****************
     * 单个加入盘点临时申请表接口
     * 请求方式：POST
     * 需要提交参数:  goods_id   商品id     必须
     *                g_num      申请数量   必须
     ***************/
    public function addRequestTemp()
    {
    	
        $datapost = $this->gv();
        /********检测提交数据***************/
        $goods_id = $datapost['goods_id'];
        $g_num = $datapost['g_num'];
        $remark = $datapost['remark'];
        $value_id = $datapost['value_id'];
        if(empty($value_id)){
        	$this->response(999, "请选择属性");
        }
        $temp_id = $datapost['temp_id'];

        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(999, "请选择商品");
        }
        if (is_null($g_num) || empty($g_num)) {
            $g_num == 0;
        }

        
        $RequestTempModel = M("RequestTemp");
        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        //如果$temp_id临时申请表id不未空 按id删除后重新生成
       if(!empty($temp_id)){
	       	//更新
            $saveData["goods_id"] = $goods_id;
	       	$saveData["remark"] = $remark;
	       	$saveData["g_num"] = $g_num;
	       	$saveData["value_id"] = $value_id;
	       	$result = $RequestTempModel->where(" id={$temp_id} ")->save($saveData);
	       	if ($result === false) {
	       		$this->response(999, $RequestTempModel->getError());
	       	} else {
	       		//判断是否有重复商品属性如果有删除一个
	       		$RequestTempModel->where(array('id'=>array('NEQ',$temp_id),'admin_id'=>UID,'warehouse_id'=>$this->_warehouse_id,'status'=>0,'goods_id'=>$goods_id,'temp_type'=>7,'value_id'=>$value_id))->delete();
	       	
	       		$this->response(self::CODE_OK, '编辑成功');
	       	}
       }else{
	       	$where["admin_id"] = UID;
	       	$where["status"] = 0;
	       	$where['warehouse_id'] = $this->_warehouse_id;
	       	$where["goods_id"] = $goods_id;
	       	$where["temp_type"] = 7;
	       	$where["value_id"] = $value_id;
	       	$data = $RequestTempModel
	       	->where($where)
	       	->order(" id desc ")
	       	->select();
	        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
	        $shequ_id = $warehousedata['shequ_id'];
	
	        $ModelPrice = D('Addons://Warehouse/WarehouseInout');
	        $goodspricedata = $ModelPrice->getWarehouseInoutAvgPrice($goods_id,$shequ_id);
	        $price = $goodspricedata[0]['stock_price'];
	        if ($data) {
	            //更新
	            $data[0]['g_num'] = $g_num;//盘点数量
	            $data[0]['remark'] = $remark;//商品备注
	 
	        } else {
	            //新增
	            $datatemp['id'] = 0;//id
	            $datatemp['admin_id'] = UID;
	            $datatemp['temp_type'] = 7;//盘点临时申请
	            $datatemp['ctime'] = time();
	            $datatemp['warehouse_id'] = $this->_warehouse_id;//所属仓库
	            $datatemp['status'] = 0;//临时存在
	            $datatemp['b_n_num'] = 0;//箱规
	            $datatemp['b_num'] = 0;//箱数
	            $datatemp['b_price'] = 0;//每箱价格
	            $datatemp['goods_id'] = $goods_id;//商品id
	            $datatemp['g_num'] = $g_num;//盘点数量
	            $datatemp['remark'] = $remark;//盘点数量
	            $datatemp['g_price'] = $price;//商品库存平均成本价
	            $datatemp['value_id'] = $value_id;
	            $data[] = $datatemp;
	        }
	
	        $RequestTempM = D('RequestTemp');
	        $result = $RequestTempM->saveRequestTemp($data,false);
	        if ($result["status"] == "1") {
	            $this->response(self::CODE_OK, $result["msg"]);
	        } else {
	            $this->response(999, $result["msg"]);
	        }
       }
    }

    /****************
     * 多个加入盘点临时申请表接口
     * 请求方式：POST
     * 需要提交参数:  is_all   默认=1     全部商品=1，类别商品 = 2
     * 需要提交参数:  cate_id   类别id     is_all = 2 则必须
     *                g_num      库存数量   关联当前仓库库存表获得
     ***************/
    public function addCateRequestTemp()
    {
        $datapost = $this->gv();
        /********检测提交数据***************/
        $is_all = $datapost['is_all'];

        if ($is_all == 1) {
            //全部商品
        } else {
            //类别商品
            if ($is_all == 2) {
                $cate_id = $datapost['cate_id'];
                $catewhere = ' and A.cate_id=' . $cate_id;
                if (is_null($cate_id) || empty($cate_id)) {
                    $this->response(999, "请选择类别");
                }
            } else {
                $this->response(999, "错误参数");
            }
        }

        $warehouse_id = $this->_warehouse_id;
        $WarehouseModel = M('Warehouse');
        $warehousedata = $WarehouseModel -> where('w_id = ' . $warehouse_id) -> find();
        $shequ_id = $warehousedata['shequ_id'];

        $sql = "
        select ifnull(B.id,0) as id,A.id as goods_id," . UID . " as admin_id,7 as temp_type," . time() . " as ctime,
        0 as status," . $this->_warehouse_id . " as warehouse_id,0 as b_n_num,0 as b_num,0 as b_price,
        ifnull(ifnull(B.g_num,C.num),0) as g_num,ifnull(D.stock_price,0) as g_price,C.value_id 
         from hii_goods A  
          join (	SELECT
                		ifnull(WS.goods_id,AV.goods_id)goods_id,
                		ifnull(WS.num,0)num,
                		ifnull(WS.value_id,AV.value_id)value_id
                	FROM 
                		(select * from hii_warehouse_stock where w_id=".$this->_warehouse_id.") WS RIGHT JOIN 
                		hii_attr_value AV ON AV.value_id = WS.value_id
                        where AV.goods_id in(select DISTINCT goods_id from hii_warehouse_stock where w_id=".$this->_warehouse_id.")
                ) C on A.id=C.goods_id  
          join (select * from hii_warehouse_inout_view where shequ_id = $shequ_id) D on C.goods_id=D.goods_id
          left join (select * from hii_request_temp where admin_id=" . UID . " and temp_type=7 and status=0 and warehouse_id=" . $this->_warehouse_id . ") B on B.value_id=C.value_id 
         where 1=1 $catewhere
        ";
        $RequestTempModel = M("RequestTemp");
        $data = $RequestTempModel->query($sql);
        if (is_array($data) && count($data) > 0) {
            $RequestTempM = D('RequestTemp');
            $result = $RequestTempM->saveRequestTemp($data,false);
            if ($result["status"] == "1") {
                $this->response(self::CODE_OK, $result["msg"]);
            } else {
                $this->response(999, $result["msg"]);
            }
        }else{
            $this->response(999, "错误：没有数据");
        }
    }

    /***************
     * 获取盘点单临时表数据
     * 请求方式：POST
     * 请求参数：无
     */
    private function getTempList($p,$pagesize)
    {
        $where = array();
        $warehouseOne = M('Warehouse')->where('w_id = '.$this->_warehouse_id)->find();
        $where['admin_id'] = UID;//当前登录账号的uid
        $where['temp_type'] = 7;//临时盘点单
        $where['hii_request_temp.status'] = 0;
        $where['warehouse_id'] = $this->_warehouse_id;
        $warehouse_id = $this->_warehouse_id;
        $RequestTempModel = M("RequestTemp");
        $field = "AV.value_id,AV.value_name,hii_request_temp.id,hii_request_temp.goods_id,FROM_UNIXTIME(hii_request_temp.ctime,'%Y-%m-%d %H:%i:%s') as ctime";
        $field .= ",hii_goods.title as goods_name,AV.bar_code,hii_goods_cate.title as cate_name,
        (case when ifnull(SP.shequ_price,0)>0 then SP.shequ_price else hii_goods.sell_price end) as sell_price";
        $field .= ",floor(ifnull(hii_warehouse_stock.num,0)) as stock_num,hii_request_temp.g_num,hii_request_temp.remark";
        $list = $RequestTempModel
            ->join('left join hii_goods on hii_request_temp.goods_id=hii_goods.id')
            ->join('left join hii_goods_cate on hii_goods.cate_id=hii_goods_cate.id
        left join (select * from hii_shequ_price where shequ_id=' .$warehouseOne['shequ_id']. ') SP on hii_request_temp.goods_id=SP.goods_id')
            ->join('left join (select * from hii_warehouse_stock where w_id = ' . $warehouse_id . ') hii_warehouse_stock on hii_warehouse_stock.value_id=hii_request_temp.value_id')
            ->join('left join hii_attr_value AV on AV.value_id=hii_request_temp.value_id')
            ->field($field)
            ->where($where)->order('ctime desc ')->select();
        //分页
        //每页共多少条
        $pcount = $pagesize;
        $count = count($list);//得到数组元素个数
        $Page = new \Think\Page($count,$pcount,array(),$p);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($list,$Page->firstRow,$Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $dataout['pageSize'] = $pcount;
        $dataout['recordCount'] = $count;
        $dataout['p'] = $p;
        $dataout['pager'] = $show;
        $dataout['pages'] = ceil($count/$pcount);
        $dataout['list'] = $datamain;
        return $dataout;
    }
    /***************
     * 获取类别列表数据
     * 请求方式：POST
     * 请求参数：无
     */
    private function catelist()
    {
        $GoodsCateModel = M("GoodsCate");
        $field = "id as cate_id,title as cate_name";
        $list = $GoodsCateModel
            ->field($field)
            ->order('listorder desc')->select();
        $sql = $GoodsCateModel->_sql();
        return $list;
    }
    /***************
     * 获取当前页
     ***************/
    private function getPageIndex()
    {
    	$p = I("get.p");
    	return is_null($p) || empty($p) ? 1 : $p;
    }
}

