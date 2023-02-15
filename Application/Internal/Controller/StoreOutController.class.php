<?php
/**
 * 门店出库验货单相关接口 (内部app)
 * User: zzy
 * Date: 2018-04-18
 * Time: 11:05
 */
namespace Internal\Controller;

use Internal\Controller\ApiController;

class StoreOutController extends ApiController{

    public function _initialize()
    {
        // 是否验证token
        $action = ACTION_NAME;//当前请求action名称
        $actions = array('');
        $check = false; // true为指定的验证，false为指定的不验证
        if (in_array($action, $actions)) {
            $this->ctoken = $check;
        } else {
            $this->ctoken = !$check;
        }
        parent::_initialize();
        header("Content-Type: text/html;charset=utf-8");
    }
    public function test(){
        echo date('Y-m-d H:i:s');
        exit;
    }

    /**
     * 门店出库验货单列表
     * 请求方式 post
     * @param  store_id  int 门店id 必须
     * @param  s_out_status int  单据状态  0 未审核  1 审核 默认未审核
     */
    public function get_store_out_list(){

        $store_id = I("post.store_id",0,"intval");
        $s_out_status = I("post.s_out_status",0,"intval");
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeOutModel = D('StoreOut');
            $data = $storeOutModel->getStoreOutListModel($store_id,$s_out_status);
            if($data === false){
                $this->response(0,'获取出库验货单失败');
            }
            foreach($data as $k=>$v){
                $data[$k]['ctime'] = date('Y-m-d H:i:s',$v['ctime']);
                $data[$k]['ptime'] = date('Y-m-d H:i:s',$v['ptime']);
                switch($v['s_out_status']){
                    case 0:{
                        $data[$k]["s_out_status_name"] = "未审核";
                    };
                        break;
                    case 1:{
                        $data[$k]["s_out_status_name"] = "已审核转出库";
                    };
                        break;
                    case 2:{
                        $data[$k]["s_out_status_name"] = "已拒绝";
                    };
                        break;
                    case 3:{
                        $data[$k]["s_out_status_name"] = "部分拒绝";
                    };
                        break;
                }
                switch ($v['s_out_type']) {
                    case 0: {
                        $data[$k]["s_out_type_name"] = "仓库调拨";
                    };
                        break;
                    case 1: {
                        $data[$k]["s_out_type_name"] = "门店调拨";
                    };
                        break;
                    case 2: {
                        $data[$k]["s_out_type_name"] = "退货报损";
                    };
                        break;
                }
            }
            $re = array();
            $re['data'] = $data;
            $this->response(self::RESPONSE_SUCCES,$re);
    }

    /**
     * 门店出库验收单查看详情
     * @param $store_id   门店id 必须
     * @parame $s_out_id 出库单id 必须
     * @parame $audit_mark 不填是已审核    0 未操作 1 已操作
     * return json
     */
    public function get_store_out_detail()
    {
        $store_id = I("post.store_id", 0, "intval");
        $s_out_id = I("post.s_out_id", 0, "intval");
        $audit_mark = I("post.audit_mark",'');
        $msg = array(
            array('name' => $store_id, 'msg' => '缺少门店id'),
            array('name' => $s_out_id, 'msg' => '缺少出库验收单id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeOutModel = D('StoreOut');
        $storeout_all = $storeOutModel->getStoreOutList_one($store_id, $s_out_id);
        if ($storeout_all === false) {
            $this->response(0, '获取本单数据失败');
        }
        foreach ($storeout_all as $key => $val) {
            $storeout_all[$key]['ctime'] = date('Y-m-d H:i:s', $val['ctime']);
            switch ($val['s_out_status']) {
                case 0: {
                    $storeout_all[$key]["s_out_status_name"] = "未审核";
                };
                    break;
                case 1: {
                    $storeout_all[$key]["s_out_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $storeout_all[$key]["s_out_status_name"] = "已拒绝";
                };
                    break;
                case 3: {
                    $storeout_all[$key]["s_out_status_name"] = "部分发货";
                };
                    break;
            }
            switch ($val['s_out_type']) {
                case 0: {
                    $storeout_all[$key]["s_out_type_name"] = "仓库调拨";
                };
                    break;
                case 1: {
                    $storeout_all[$key]["s_out_type_name"] = "门店调拨";
                    $rematk_st = M('StoreToStore')->where(array('s_t_s_id'=>array('in',$val['s_r_id'])))->getField('remark');
                    if(empty($rematk_st)){
                        $storeout_all[$key]["remark_to"] = "";
                    }else{
                        $storeout_all[$key]["remark_to"] = $rematk_st;
                    }
                };
                    break;
                case 2: {
                    $storeout_all[$key]["s_out_type_name"] = "退货报损";
                };
                    break;
            }
        }
        $data = $storeOutModel->get_store_out_detail($store_id, $s_out_id,$audit_mark);
        if ($data === false) {
            $this->response(0, '获取出库验收单详细信息失败');
        }
        foreach ($data as $key => $val) {
            $data[$key]["remark_to"] = '';
            switch ($val['s_out_type']) {
                case 1: {
                    $rematk_st = M('StoreToStoreDetail')->where(array('s_t_s_d_id'=>$val['s_r_d_id']))->getField('remark');
                    if(empty($rematk_st)){
                        $data[$key]["remark_to"] = "";
                    }else{
                        $data[$key]["remark_to"] = $rematk_st;
                    }
                };
                    break;
            }

            switch ($val['audit_mark']) {
                case 0: {
                    $data[$key]["s_out_status_name"] = "未操作";
                };
                    break;
                case 1: {
                    if ($val['out_num'] != 0) {
                        if ($val['g_num'] == $val['out_num']) {
                            $data[$key]["s_out_status_name"] = "全部缺货";
                        } else {
                            $data[$key]["s_out_status_name"] = "部分发货";
                        }
                    } else {
                        $data[$key]["s_out_status_name"] = "全部发货";
                    }
                }
                    break;
            }
        }
        $re = array();
        $re['data'] = $data;
        $re['modata'] = $storeout_all;
        $this->response(200, $re);
    }

    /**
     * 修改出库验收的 验收数量
     * @param $store_id 门店id
     * @param $s_out_id 出库验收单id
     * @param $all 全部有货  全部缺货 默认不填  0 缺货 1 有货
     * @param $info_json_str 申请信息    格式：[{"s_out_d_id":"","goods_id":"","in_num":"","out_num":"","remark":"","audit_mark":""},{"s_out_d_id":"","goods_id":"","in_num":"","out_num":"","remark":"","audit_mark":""}]
     */
    public function save_store_num(){
        $s_out_id = I("post.s_out_id", '', "intval");
        $store_id = I("post.store_id", '', "intval");
        $all = I("post.all",'');
        $info_json_str = I('post.info_json_str','');
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        $admin_id = $this->uid;
        $msg = array(
            array('name' => $s_out_id, 'msg' => '缺少出库验收单id'),
            array('name' => $store_id, 'msg' => '缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        //修改出库验收单
        $storeOutModel = D('StoreOut');
        if($all === ''){
            $msg = array(
                array('name' => $detail_array, 'msg' => '缺少要修改的商品'),
            );
            $this->_isNull($msg);
            $data_save = $storeOutModel->save_store_out($admin_id,$s_out_id,$detail_array);
        }else{
            $data_save = $storeOutModel->save_store_out_all($s_out_id,$all);
        }

        if($data_save['code']==0){
            $this->response(0,$data_save['msg']);
        }
        $this->response(self::RESPONSE_SUCCES,'验收成功');
    }
    /**
     * 拒绝接口
     * @param $s_out_id 出库验收单id
     */
    public function refuse_store_out(){
        $s_out_id = I("post.s_out_id", '', "intval");
        $store_id = I("post.store_id", '', "intval");
        $admin_id = $this->uid;
        $msg = array(
            array('name' => $s_out_id, 'msg' => '缺少出库验收单id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeOutModel = D('StoreOut');
        $res = $storeOutModel->rejectAll($store_id,$s_out_id, $admin_id);
        if($res['code'] == 0){
            $this->response(0,$res['msg']);
        }
        $this->response(self::RESPONSE_SUCCES,'操作成功');

    }

    /**
     * 审核接口
     * @param $store_id 门店id
     * @param $s_out_id 出库验收单id
     * @param $remark 备注
     * * 逻辑：1.先判断子表是否还有未设置in_num和out_num的，还存在提示
     *       2.根据出库验货单子表获取那些可以出库，那些完全拒绝
     *         3.1当存在可出库情况，生成出库单主表，子表信息
     *         3.2当存在拒绝出库情况，修改对应门店调拨申请信息
     *       4.修改出库验货单主表信息,如审核人，审核时间，审核状态
     */
    public function check(){
        $s_out_id = I("post.s_out_id", '', "intval");
        $store_id = I("post.store_id", '', "intval");
        $remark = I("post.remark", '');
        $admin_id = $this->uid;
        $msg = array(
            array('name' => $s_out_id, 'msg' => '缺少出库验收单id'),
            array('name' => $store_id, 'msg' => '缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        //修改出库验收单
        $storeOutModel = D('StoreOut');
        //审核
        $data_save = $storeOutModel->pass($s_out_id,$store_id,$admin_id);
        if($data_save['code']==0){
            $this->response(0,$data_save['msg']);
        }
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, 0, $data_save['data']['store_id2'], 0, $data_save['data'], \Erp\Model\MessageWarnModel::STORE_IN);
        $this->response(self::RESPONSE_SUCCES,'审核成功');

    }
    /**********************************私有方法*********************************************************/
    /**
     * 判断接口必传参数是否为空
     * @param array $array  array(0=>array('name'=>'','msg'=>''),1=>array('name'=>'','msg'=>''))
     */
    private function _isNull($array=array()){
        if(!is_array($array)){
            $this->response(0,'内部错误');
        }
        foreach($array as $k=>$v){
            if(empty($v['name'])){
                $this->response(0,$v['msg']);
            }
        }
    }

    /**
     * 获取分页
     * @return int|mixed
     */
    private function _p(){
        $p = I('post.p','','intval');
        if(empty($p)){
            return 1;
        }else{
            return $p;
        }
    }

    /**
     * 获取每页条数
     * @return int|mixed
     */
    private function _pageSize(){
        $pageSize = I('post.pageSize','','intval');
        if(empty($pageSize)){
            return 15;
        }else{
            return $pageSize;
        }
    }
    /**
     * 检测数组是否空
     */
    private function _isArrayNull($array)
    {
        if (!is_null($array) && !empty($array) && count($array) > 0) {
            return $array;
        } else {
            return null;
        }
    }
}