<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-10-26
 * Time: 10:59
 * 调拨申请
 */

namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class AssignmentApplicationController extends AddonsController
{
    private $temp_type = 3;//调拨申请在临时申请表中的状态

    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();//检测是否已选择仓库
    }

    /*****************************
     * 调拨临时申请单列表
     ***************************/
    public function temp()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $where = array();
        $where['admin_id'] = UID;//当前登录账号的uid
        $where['temp_type'] = $this->temp_type;//临时调拨单状态
        $where['hii_request_temp.status'] = 0;
        $where["warehouse_id"] = $this->_warehouse_id;

        $warehouse_id = $this->_warehouse_id;//当前仓库ID

        $field = "hii_request_temp.id,hii_request_temp.goods_id,FROM_UNIXTIME(hii_request_temp.ctime,'%Y-%m-%d %H:%i:%s') as ctime,hii_request_temp.remark ";
        $field .= ",hii_goods.title as goods_name,ifnull(AV.bar_code,hii_goods.bar_code)bar_code,hii_goods_cate.title as cate_name,hii_goods.sell_price";
        $field .= ",'' as stock_num,floor(ifnull(hii_warehouse_stock.num,0)) as current_stock_num,hii_request_temp.g_num,AV.value_id,AV.value_name";
        $list = M('RequestTemp')
            ->join('left join hii_goods on hii_request_temp.goods_id=hii_goods.id')
            ->join('left join hii_goods_cate on hii_goods.cate_id=hii_goods_cate.id')
            ->join('left join hii_attr_value AV on AV.value_id=hii_request_temp.value_id')
            ->join('left join hii_warehouse_stock on hii_warehouse_stock.value_id=hii_request_temp.value_id and hii_warehouse_stock.w_id=' . $warehouse_id . ' ')
            ->field($field)
            ->where($where)->order('ctime asc')->select();
        //读取区域价
        $GoodsStoreModel = M("GoodsStore");
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
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

        $this->assign('list', $list);

        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $can_warehouse_sql = " `w_id`<>{$warehouse_id} and shequ_id=(select shequ_id from hii_warehouse where `w_id`={$warehouse_id} limit 1)   ";
        if (count($can_warehouse_id_array) > 0) {
            $can_warehouse_sql .= " and `w_id` in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        //获取仓库
        $warehouses = M("Warehouse")->where("{$can_warehouse_sql}")->order(" `w_id` asc ")->select();
        //echo M("Warehouse")->_sql();exit;
        $this->assign("warehouses", $warehouses);
        $this->meta_title = "临时调拨申请列表";
        $this->display(T('Addons://Warehouse@AssignmentApplication/temp'));
    }

    /***********************************
     * 调拨申请单列表
     ***********************************/
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        //时间范围默认30天
        $s_date = I('s_date');
        $e_date = I('e_date');
        $warehouse_id = $this->_warehouse_id;
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $e_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        } else {
            if ($s_date != "") {
                $s_date = strtotime($s_date);
            }
            if ($e_date != "") {
                $e_date = strtotime($e_date);
            }
        }
        $s_date = date('Y-m-d', $s_date);
        $e_date = date('Y-m-d', $e_date);
        $this->assign('s_date', $s_date);
        $this->assign('e_date', $e_date);

        $title = '【' . session('user_warehouse.w_name') . '】 ' . $s_date . '>>>' . $e_date . '调拨申请单';

        $Model = M('RequestTemp');

        $can_warehouse_id_array = $this->getCanWarehouseIdArray();
        $shequ_where = "";
        if (count($can_warehouse_id_array) > 0) {
            $shequ_where .= " A.warehouse_id1 in (" . implode(",", $can_warehouse_id_array) . ") or A.warehouse_id2 in (" . implode(",", $can_warehouse_id_array) . ") ";
        }
        if (!empty($shequ_where)) {
            $shequ_where = " and ({$shequ_where}) ";
        }

        //获取仓库所在区域
        $warehouse_data = M("Warehouse")->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];

        //sum(A1.g_num*G.sell_price) as g_amounts,
        $sql = "select A.w_r_id,A.w_r_sn,A.w_r_type,A.w_r_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,";
        $sql .= "A.admin_id,B.nickname,A.warehouse_id1,C.w_name as warehouse_name1,A.warehouse_id2,D.w_name as warehouse_name2,";
        $sql .= "A.remark,A.g_type,A.g_nums,sum(A1.is_pass) as pass_num,ifnull(WS.num,0) as stock_num, ";
        $sql .= "SUM(A1.g_num*(CASE WHEN GST.shequ_price is not null and GST.shequ_price>0 THEN GST.shequ_price ELSE G.sell_price END )) as g_amounts ";
        $sql .= "from hii_warehouse_request A ";
        $sql .= "left join hii_warehouse_request_detail A1 on A.w_r_id=A1.w_r_id ";
        $sql .= "left join hii_member B on A.admin_id=B.uid ";
        $sql .= "left join hii_warehouse C on A.warehouse_id1=C.w_id ";
        $sql .= "left join hii_warehouse D on A.warehouse_id2=D.w_id ";
        $sql .= "left join hii_goods G on A1.goods_id=G.id ";
        $sql .= "left join hii_warehouse_stock WS on WS.w_id=A.warehouse_id2 and WS.goods_id=A1.goods_id ";
        $sql .= "left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=A1.goods_id ";
        $sql .= "where warehouse_id1 = {$this->_warehouse_id} {$shequ_where} and FROM_UNIXTIME(ctime,'%Y-%m-%d') between '{$s_date}' and '{$e_date}' ";
        $sql .= "group by A.w_r_id,A.w_r_sn,A.w_r_type,A.w_r_status,A.ctime,A.admin_id,B.nickname,A.warehouse_id1,C.w_name,A.remark,A.g_type,A.g_nums order by w_r_id desc";

        $data = $Model->query($sql);

        $isprint = I('isprint');
        if ($isprint == "") {
            $isprint = $_POST['isprint'];
        }
        if ($isprint == 1) {
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\WarehouseRequestReportModel();
            $printfile = $printmodel->exportWarehouseRequestListExcel($data, $title, $fname);
            echo($printfile);
            die;
        }
        //分页
        $pcount = 15;
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $this->assign('list', $datamain);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);

        $this->meta_title = "调拨申请列表";
        $this->display(T('Addons://Warehouse@AssignmentApplication/index'));
    }

    /************
     * 通过二维码查找商品
     ***********/
    public function get_one()
    {
        $bar_code = I('bar_code', '', 'trim');
        if (!$bar_code) {
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('GoodsBarCode')->where(array('bar_code' => $bar_code))->find();
        if (!$data) {
            $this->ajaxReturn(array('status' => 0));
        }
        $data = M('Goods')->where(array('id' => $data['goods_id'], 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if (!$data) {
            $this->ajaxReturn(array('status' => 0));
        } else {
            $where['admin_id'] = UID;
            $where['temp_type'] = 3;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,g_num')->find();
            if (!$data1) {
                $this->ajaxReturn(array('status' => 1, 'data' => $data));
            } else {
                $this->ajaxReturn(array('status' => 2, 'data' => $data, 'data1' => $data1));
            }
        }
    }

    /********************
     * 通过ID查找商品
     **********************/
    public function get_one_id()
    {
        $id_code = I('id_code', 0, 'intval');
        if (empty($id_code)) {
            $this->ajaxReturn(array('status' => 0));
        }

        $data = M('Goods')->where(array('id' => $id_code, 'status' => 1))->field('id, title, sell_price, sell_online, sell_outline')->find();
        if (!$data) {
            $this->ajaxReturn(array('status' => 0));
        } else {
            $where['admin_id'] = UID;
            $where['temp_type'] = 1;
            $where['hii_request_temp.status'] = 0;
            $where['goods_id'] = $data['id'];
            $data1 = M('RequestTemp')->where($where)->field('id,g_num')->find();
            if (!$data1) {
                $this->ajaxReturn(array('status' => 1, 'data' => $data));
            } else {
                $this->ajaxReturn(array('status' => 2, 'data' => $data, 'data1' => $data1));
            }
        }
    }

    /*******************************
     * 获取商品列表
     ***************************/
    public function get_goods_lists()
    {
        $keyword = I('keyword', '', 'trim');
        $temp_type = I("temp_type","");
        $warehouse_id = $this->_warehouse_id;
        $Model = M('Goods')->alias('a');
        $where = array();
        $keyword && $where['a.title'] = array('like', '%' . $keyword . '%');
        $where['a.status'] = 1;
        $field = 'a.id,a.title,a.cover_id';
        //$join = "left join hii_goods_cate b on a.cate_id=b.id";
        $_REQUEST['r'] = 20;
        $p = I('p', '', 'trim');
        if ($p == '') {
            $p = $_POST['p'];
        }
        if ($p == '') {
            $p = 1;
        }
        if($temp_type == 7 || $temp_type == 11){
            $warehouseStockModel = M("WarehouseStock");
            $warehouse_stock_sql = $warehouseStockModel->field("distinct goods_id")->where(array("w_id"=>$warehouse_id))->select(false);
            $list = $Model->where($where)->field($field)->join("inner join {$warehouse_stock_sql} as b on b.goods_id=a.id")->order('listorder desc, create_time desc')->limit(($p - 1) * 20 . ',20')->select();
        }else{
            $list = $Model->where($where)->field($field)->order('listorder desc, create_time desc')->limit(($p - 1) * 20 . ',20')->select();
        }
        $attrValueModel = M("AttrValue");
        foreach ($list as $k => $v) {
            $attr_value = $attrValueModel->field('value_id,value_name')->where(array('goods_id'=>$v['id'],'status'=>1))->select();
            if(empty($attr_value)){
                $attr_value = array();
            }
            $v['attr_value'] = json_encode($attr_value);
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
            $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
            $list[$k] = $v;
        }
        if (IS_AJAX) {
            if (is_array($list) && count($list) > 0) {
                $this->ajaxReturn(array('status' => 1, 'data' => $list, 'msgword' => 'OK'));
            } else {
                $this->ajaxReturn(array('status' => 2, 'data' => $list, 'msgword' => '没有更多了...'));
            }
            exit;
        }
        $this->assign('list', $list);
        $this->display(T('Addons://Warehouse@AssignmentApplication/get_goods_lists'));
    }

    /************************************
     * 清空临时申请表数据
     **********************************/
    public function cleantemp()
    {
        $where = array();
        $where['admin_id'] = UID;
        $where['warehouse_id'] = $this->_warehouse_id;
        $where['temp_type'] = $this->temp_type;
        $where['hii_request_temp.status'] = 0;
        $Model = M('RequestTemp');
        $Model->where($where)->delete();
        $this->success('清空成功', Cookie('__forward__'));
    }

    /**********************************
     * 加入临时申请表
     ************************************/
    public function update()
    {
        $goods_id = I("goods_id");
        $warehouse_id = $this->_warehouse_id;
        $g_num = I("g_num");
        $value_id = I("value_id",0);
        if($value_id == 0){
        	$this->error('请选择属性');
        }
        $remark = I("request_temp_remark");
        $temp_id = I("get.temp_id",0);
        //echo $remark;exit;
        $admin_id = UID;
        if (is_null($goods_id) || empty($goods_id) || $goods_id == 0) {
            $this->error("不存在的商品ID");
        }
        $WarehouseModel = M("Warehouse");
        $RequestTempModel = M("RequestTemp");
        if(!empty($temp_id)){
        	//更新
            $saveData["goods_id"] = $goods_id;
        	$saveData["remark"] = $remark;
        	$saveData["g_num"] = $g_num;
        	$saveData["value_id"] = $value_id;
        	$result = $RequestTempModel->where(" id={$temp_id} ")->save($saveData);
        	if ($result === false) {
        		$this->error("编辑失败");
        	} else {
        		//判断是否有重复商品属性如果有删除一个
        		$RequestTempModel->where(array('id'=>array('NEQ',$temp_id),'admin_id'=>$admin_id,'warehouse_id'=>$this->_warehouse_id,'status'=>0,'goods_id'=>$goods_id,'temp_type'=>$this->temp_type,'value_id'=>$value_id))->delete();
        		 
        		 $this->success('编辑成功', Cookie('__forward__'));
        	}
        }else{
	        $where = array();
	        $where["hii_request_temp.admin_id"] = $admin_id;
	        $where["hii_request_temp.goods_id"] = $goods_id;
	        $where["hii_request_temp.temp_type"] = $this->temp_type;
	        $where["hii_request_temp.status"] = 0;
	        $where["hii_request_temp.warehouse_id"] = $this->_warehouse_id;
	        $where["hii_request_temp.value_id"] = $value_id;
	        $datas = $RequestTempModel->where($where)->limit(1)->select();
	        $g_price = 0;
	        $shequ_id = 0;
	        $warehouse_datas = $WarehouseModel->where(" `w_id`={$warehouse_id} ")->limit(1)->select();
	        if (!is_null($warehouse_datas) && !empty($warehouse_datas) && count($warehouse_datas) > 0) {
	            $shequ_id = $warehouse_datas[0]["shequ_id"];
	        }
	        $WarehouseInoutViewModel = M("WarehouseInoutView");
	        $tmp = $WarehouseInoutViewModel->field(" ifnull(stock_price,0) as g_price ")->where(" goods_id={$goods_id} and shequ_id={$shequ_id} ")->limit(1)->select();
	        if (!is_null($tmp) && !empty($tmp) && count($tmp) > 0) {
	            $g_price = $tmp[0]["g_price"];
	        }
	        if (is_null($datas) || empty($datas) || count($datas) == 0) {
	            //新增
	            $RequestTempEntity = array();
	            $RequestTempEntity["admin_id"] = $admin_id;
	            $RequestTempEntity["temp_type"] = $this->temp_type;
	            $RequestTempEntity["goods_id"] = $goods_id;
	            $RequestTempEntity["ctime"] = time();
	            $RequestTempEntity["status"] = 0;
	            $RequestTempEntity["g_num"] = $g_num;
	            $RequestTempEntity["g_price"] = $g_price;
	            $RequestTempEntity["remark"] = $remark;
	            $RequestTempEntity["warehouse_id"] = $this->_warehouse_id;
	            $RequestTempEntity["value_id"] = $value_id;
	            $ok = $RequestTempModel->add($RequestTempEntity);
	            if ($ok === false) {
	                $this->error("新增失败");
	            } else {
	                $this->success('新增成功', Cookie('__forward__'));
	            }
	        } else {
	            //更新
	            $datas[0]["g_num"] = $g_num;
	            $datas[0]["remark"] = $remark;
	            $datas[0]["g_price"] = $g_price;
	            $ok = $RequestTempModel->where(" id={$datas[0]["id"]} ")->save($datas[0]);
	            if ($ok === false) {
	                $this->error("更新失败");
	            } else {
	                $this->success('更新成功', Cookie('__forward__'));
	            }
	        }
        }
    }

    /******************************************
     * 根据ID删除临时申请表单条数据
     **********************************/
    public function delete()
    {
        $id = I('get.id', '');
        if ($id) {
            $Model = M('RequestTemp');
            $res = $Model->where(" id = $id ")->delete();
            if (!$res) {
                $error = $Model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            } else {
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }

    /************************************
     * 选择调拨仓库
     ***************************************/
    public function get_warehouse_lists()
    {
        $warehouse_id = I('warehouse_id');
        $warehouses = null;
        if (!IS_ROOT && !in_array(1, $this->group_id) && !in_array(9, $this->group_id)) {
            $my_shequ = M('MemberWarehouse')->where(array('uid' => UID, 'type' => 2))->select();
            $my_warehouse = array();
            if ($my_shequ) {
                $shequ_ids = array();
                foreach ($my_shequ as $v) {
                    $shequ_ids[] = $v['warehouse_id'];
                    $group_shequ[$v['warehouse_id']][] = $v['group_id'];
                }
                $warehouse_data = M('Warehouse')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
                if ($warehouse_data) {
                    foreach ($warehouse_data as $v) {
                        $my_warehouse[$v['id']] = array(
                            'group_id' => $group_shequ[$v['shequ_id']],
                            'warehouse_id' => $v['id'],
                        );
                    }
                }
            }
            $_my_warehouse = M('MemberWarehouse')->where(array('uid' => UID, 'type' => 1))->field('group_id,warehouse_id')->select();
            foreach ($_my_warehouse as $v) {
                if (isset($my_warehouse[$v['warehouse_id']])) {
                    !in_array($v['group_id'], $my_warehouse[$v['warehouse_id']]['group_id']) && $my_warehouse[$v['warehouse_id']]['group_id'][] = $v['group_id'];
                } else {
                    $my_warehouse[$v['warehouse_id']] = array(
                        'group_id' => array($v['group_id']),
                        'warehouse_id' => $v['warehouse_id'],
                    );
                }
            }
            if (!$my_warehouse) {
                $this->error('未授权任何仓库管理');
            }
            $my_warehouse_access = array();
            $my_group = array();
            foreach ($my_warehouse as $v) {
                $my_warehouse_access[] = $v['warehouse_id'];
                $my_group[$v['warehouse_id']] = $v['group_id'];
            }
            if (empty($my_warehouse_access)) {
                $this->error('未授权任何仓库管理');
            }
            $warehouses = $my_warehouse_access;
        }
        if ($warehouse_id) {
            if (!is_null($warehouses) && !in_array($warehouse_id, $warehouses)) {
                $this->error('该仓库未授权管理');
            }
            $warehouse = M('Warehouse')->where(array('w_id' => $warehouse_id))->field('w_id, w_name, w_type')->find();
            if (!$warehouse) {
                $this->error('仓库不存在');
            }
            // 当是授权时，才重新设置权限
            if (isset($my_group[$warehouse_id])) {
                $Auth = new \Think\Auth();
                $Auth->resetAuth(UID, array('in', '1,2'), $my_group[$warehouse_id]);
                $Auth->resetAuth(UID, 1, $my_group[$warehouse_id]);
                $Auth->resetAuth(UID, 2, $my_group[$warehouse_id]);
            }
            session('user_warehouse', $warehouse);
            redirect(Cookie('__forward__'));
            exit;
        } else {
            $cook = Cookie('__forward__');
            !$cook && Cookie('__forward__', empty($_SERVER['HTTP_REFERER']) ? U('/') : $_SERVER['HTTP_REFERER']);
        }
        $shequ = M('Shequ')->field('id, title')->select();
        !$shequ && $shequ = array();
        $where = array();
        $warehouses && $where['id'] = array('in', $warehouses);
        $warehouse = M('Warehouse')->where($where)->field('w_id, shequ_id , w_name, w_type')->select();
        !$warehouse && $warehouse = array();
        $_warehouse = array();
        foreach ($warehouse as $v) {
            $_warehouse[$v['shequ_id']][] = $v;
        }
        $this->assign('shequ', $shequ);
        $this->assign('warehouse', $_warehouse);
        $this->display(T('Addons://Warehouse@AssignmentApplication/get_warehouse_lists'));
    }

    /*********************************
     * 提交调拨临时申请单
     *******************************/
    public function send_request_temp()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $remark = I('remark', '');//备注
        $warehouse_id1 = $this->_warehouse_id;
        $warehouse_id2 = I("warehouse_id", '');//申请调拨的仓库ID
        if (empty($warehouse_id1)) {
            $this->error("请选择仓库");
        }
        if (empty($warehouse_id2)) {
            $this->error('请选择发货仓库');
        }
        //查找是否存在该仓库
        $RequestTempModel = M("Warehouse");
        $list = $RequestTempModel->where(" `w_id`={$warehouse_id2} ")->field(" `w_id` ")->order(" `w_id` asc ")->limit(1)->select();
        if (is_null($list) || count($list) == 0) {
            $this->error("请选择发货仓库");
        }
        $WarehouseRequestModel = D('Addons://Warehouse/WarehouseRequest');
        $res = $WarehouseRequestModel->saveWarehouseRequest(UID, $warehouse_id1, $warehouse_id2, $remark);

        if ($res["status"] == "200") {
            $this->success($res["msg"]);
        } else {
            $this->error($res["msg"]);
        }

    }

    /********************************
     * 查看调拨申请明细
     *******************************/
    public function view()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $w_r_id = I("id");

        $WarehouseRequestModel = M("WarehouseRequest");

        //获取仓库所在区域
        $warehouse_data = M("Warehouse")->where(" `w_id`={$this->_warehouse_id} ")->limit(1)->select();
        $shequ_id = $warehouse_data[0]["shequ_id"];

        //查找主订单信息
        $sql = " select A.w_r_id,A.w_r_sn,A.w_r_type,A.w_r_status,FROM_UNIXTIME(A.ctime,'%Y-%m-%d %H:%i:%s') as ctime,A.admin_id,A.remark,A.g_type,A.g_nums, ";
        $sql .= " sum(A1.g_num*G.sell_price) as `g_amounts`,B.nickname,W1.w_name as w_name1,W2.w_name as w_name2";
        $sql .= " from `hii_warehouse_request` A ";
        $sql .= " left join `hii_warehouse_request_detail` A1 on A.w_r_id = A1.w_r_id ";
        $sql .= " left join `hii_goods` G on A1.goods_id=G.id";
        $sql .= " left join `hii_member` B on A.admin_id=B.uid";
        $sql .= " left join `hii_warehouse` W1 on W1.w_id = A.warehouse_id1 ";
        $sql .= " left join `hii_warehouse` W2 on W2.w_id = A.warehouse_id2 ";
        $sql .= " where A.w_r_id = {$w_r_id} and A.warehouse_id1 = {$this->_warehouse_id}  order by A.ctime desc limit 1; ";
        $list = $WarehouseRequestModel->query($sql);

        if (is_null($list) || count($list) == 0) {
            $this->error("不存在单据的ID");
        }

        $WarehouseRequestDetailModel = M("WarehouseRequestDetail");
        $sql = " select A1.w_r_d_id,A1.goods_id,A1.g_num,A1.pass_num,A1.is_pass,G.title as `goods_name`, ";
        $sql .= "C.title as cate_name,ifnull(AV.bar_code,G.bar_code)bar_code,G.sell_price as sys_price,GST.shequ_price,ifnull(WS.num,0) as stock_num,A1.remark,AV.value_id,AV.value_name ";
        $sql .= " from `hii_warehouse_request` A ";
        $sql .= " left join `hii_warehouse_request_detail` A1 on A.w_r_id = A1.w_r_id ";
        $sql .= " left join `hii_goods` G on A1.goods_id = G.id ";
        $sql .= " left join hii_warehouse_stock WS on A1.value_id=WS.value_id and A.warehouse_id1=WS.w_id";
        $sql .= " left join hii_goods_cate C on G.cate_id=C.id";
        $sql .= " left join (select GS.shequ_price,GS.goods_id from hii_goods_store GS where GS.store_id in (select id from hii_store where shequ_id={$shequ_id} ) group by GS.shequ_price,GS.goods_id ) GST on GST.goods_id=A1.goods_id ";
        $sql .= " left join hii_attr_value AV on AV.value_id=A1.value_id  ";
        $sql .= " where A.w_r_id = {$w_r_id} ";
        $data = $WarehouseRequestDetailModel->query($sql);

        $g_amounts = 0;
        foreach ($data as $key => $val) {
            switch ($val["is_pass"]) {
                case 0: {
                    $data[$key]["is_pass_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["is_pass_name"] = "拒绝";
                };
                    break;
                case 2: {
                    if ($val["g_num"] > $val["pass_name"]) {
                        $data[$key]["is_pass_name"] = "部分通过";
                    } else {
                        $data[$key]["is_pass_name"] = "通过";
                    }
                };
                    break;
            }
            $price = 0;
            if (!is_null($val["shequ_price"]) && !empty($val["shequ_price"]) && $val["shequ_price"] > 0) {
                $price = $val["shequ_price"];
            } elseif (!is_null($val["sys_price"]) && !empty($val["sys_price"])) {
                $price = $val["sys_price"];
            }
            $list[$key]["sell_price"] = $price;
            $g_amounts += $val["g_num"] * $price;
        }
        $list[0]["g_amounts"] = $g_amounts;

        //dump($data);exit;

        $isprint = I('isprint');
        if ($isprint == "") {
            $isprint = $_POST['isprint'];
        }
        if ($isprint == 1) {
            header("Content-type: text/html; charset=utf-8");
            $title = '【' . session('user_warehouse.w_name') . '】 调拨申请单' . $list[0]['w_r_sn'] . '查看';
            ob_clean;
            $fname = $title;
            $printmodel = new \Addons\Report\Model\WarehouseRequestReportModel();
            $printfile = $printmodel->exportSingleWarehouseRequestExcel($list[0], $data, $title, $fname);
            echo($printfile);
            die;
        }

        $this->meta_title = "调拨申请单查看";
        $this->assign("id", $w_r_id);
        $this->assign("list", $list[0]);
        $this->assign("data", $data);
        $this->display(T('Addons://Warehouse@AssignmentApplication/view'));
    }

    /*********************************
     *再次提交申请
     *****************************/
    public function again()
    {
        $w_r_id = I("id");
        $WarehouseRequestModel = M("WarehouseRequest");

        /*********查询单据是否存在*****************/
        $list = $WarehouseRequestModel
            ->where(" `w_r_id`={$w_r_id} and `warehouse_id1`={$this->_warehouse_id} ")
            ->limit(1)
            ->order(" `w_r_id` desc ")
            ->select();
        if (is_null($list) || count($list) == 0) {
            $this->error('不存在的单据ID');
        }

        /******************
         * 再次申请就是再次根据当前调拨申请单生成一份临时申请单
         **************************/
        $WarehouseRequestDetailModel = M("WarehouseRequestDetail");
        $list = $WarehouseRequestDetailModel
            ->where(" `w_r_id`={$w_r_id} ")
            ->order(" `w_r_d_id` desc ")
            ->select();

        $RequestTempModel = M("RequestTemp");
        for ($i = 0; $i < count($list); $i++) {
            $where = array();
            $where['admin_id'] = UID;
            $where['warehouse_id'] = $this->_warehouse_id;
            $where['temp_type'] = $this->temp_type;
            $where['status'] = 0;
            $where['goods_id'] = $list[$i]['goods_id'];
            $where['value_id'] = $list[$i]['value_id'];
            $data = $RequestTempModel->where($where)->select();
            if ($data) {
                //临时申请表中已存在同样申请时候，增加申请数量
                $saveData = $where;
                $saveData['g_num'] = $data[0]["g_num"] + $list[$i]['g_num'];
                $saveData['ctime'] = time();
                $dataout = $RequestTempModel->where($where)->save($saveData);
                if (!$dataout) {
                    $this->error($RequestTempModel->getError());
                }
            } else {
                //新增
                $saveData = $where;
                $saveData['g_num'] = $list[$i]['g_num'];
                $saveData['ctime'] = time();
                $dataout = $RequestTempModel->add($saveData);
                if (!$dataout) {
                    $this->error($RequestTempModel->getError());
                }
            }
        }

        $this->success("再次申请成功");
    }

    private function getCanStoreIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_store_id_array = array();
        $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
        if ($store) {
            //$this->storewhere = " And store_id in (" . implode(',', array_column($store, 'id')) . ")";
            $can_store_id_array = array_column($store, "id");
        }
        return $can_store_id_array;
    }

    private function getCanWarehouseIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_warehouse_id_array = array();
        $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
        if ($warehouse) {
            //$this->warehousewhere = " And warehosue_id in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            $can_warehouse_id_array = array_column($warehouse, "w_id");
        }
        return $can_warehouse_id_array;
    }

    private function getCanSupplyIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_supply_id_array = array();
        $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
        if ($supply) {
            //$this->supplywhere = " And supply_id in (" . implode(',', array_column($warehouse, 's_id')) . ")";
            $can_supply_id_array = array_column($supply, "s_id");
        }
        return $can_supply_id_array;
    }

}