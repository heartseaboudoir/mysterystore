<?php
/**
 * 退货管理相关接口
 * User: zzy
 * Date: 2018-05-08
 * Time: 15:36
 */
namespace Internal\Controller;
class StoreLossController extends ApiController{
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

    /**
     * 退货单列表接口
     * @param $store_id 门店id  store_id2是当前门店
     */
    public function get_goods_returnbill_list(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $msg = array(array('name'=>$store_id,'msg'=>'缺少门店id'));
        $this->_isNull($msg);
        /****************************验证参数结束************************************/
        $storeOtherOutModel =  D('StoreOtherOut');
        $data = $storeOtherOutModel->get_store_other_out_list($store_id);
        if($data === false){
            $this->response(0,'无数据');
        }
        foreach ($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch ($val["s_o_out_status"]) {
                case 0: {
                    $data[$key]["s_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["s_o_out_type"]) {
                case 0: {
                    $data[$key]["s_o_out_type_name"] = "仓库调拨入库退货";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_type_name"] = "门店调拨入库退货";
                };
                    break;
                case 2: {
                    $data[$key]["s_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 3: {
                    $data[$key]["s_o_out_type_name"] = "商品过期";
                };
                    break;
                case 4: {
                    $data[$key]["s_o_out_type_name"] = "其他退货";
                };
                    break;
                case 5: {
                    $data_all[$key]["s_o_out_type_name"] = "门店返仓拒收";
                };
                    break;
            }
        }
        $re = array();
        $re['data']=$data;
        $this->response(self::RESPONSE_SUCCES,$re);
    }
    /**
     * 退货单列表详情接口
     * @param $store_id 门店id store_id2是当前门店
     * @param  $s_o_out_id  退货单id
     */
    public function get_goods_returnbill_detail(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $s_o_out_id = I("post.s_o_out_id",'','intval');
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$s_o_out_id,'msg'=>'缺少退货单id'),
        );
        $this->_isNull($msg);
        /****************************验证参数结束************************************/
        $storeOtherOutModel =  D('StoreOtherOut');

        $data_all = $storeOtherOutModel->get_store_other_out_list_detail($store_id, $s_o_out_id);
        if($data_all === false){
            $this->response(0,'主表没数据');
        }
        foreach ($data_all as $key=>$val){
            $data_all[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch ($val["s_o_out_status"]) {
                case 0: {
                    $data_all[$key]["s_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data_all[$key]["s_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["s_o_out_type"]) {
                case 0: {
                    $data_all[$key]["s_o_out_type_name"] = "仓库调拨入库退货";
                };
                    break;
                case 1: {
                    $data_all[$key]["s_o_out_type_name"] = "门店调拨入库退货";
                };
                    break;
                case 2: {
                    $data_all[$key]["s_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 3: {
                    $data_all[$key]["s_o_out_type_name"] = "商品过期";
                };
                    break;
                case 4: {
                    $data_all[$key]["s_o_out_type_name"] = "其他退货";
                };
                    break;
                case 5: {
                    $data_all[$key]["s_o_out_type_name"] = "门店返仓拒收";
                };
                    break;
            }
        }
        $data = $storeOtherOutModel->get_store_other_out_detail($store_id, $s_o_out_id);
        if($data === false){
            $this->response(0,'详情没数据');
        }
        foreach ($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
        }
        $re = array();
        $re['modata']=$data_all;
        $re['data']=$data;
        $this->response(self::RESPONSE_SUCCES,$re);
    }

    /**
     * 被退货列表接口
     * @param $store_id 门店id   store_id1是当前门店
     * @param  $s_o_out_status  状态:0.新增,1.已审核
     */
    public function get_cover_goods_returnbill_list(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $s_o_out_status = I("post.s_o_out_status",'0','intval');
        $msg = array(array('name'=>$store_id,'msg'=>'缺少门店id'));
        $this->_isNull($msg);
        /****************************验证参数结束************************************/
        $storeOtherOutModel =  D('StoreOtherOut');
        $data = $storeOtherOutModel->get_cover_store_other_out_list_model($store_id, $s_o_out_status);
        if($data === false){
            $this->response(0,'无数据');
        }
        foreach ($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch ($val["s_o_out_status"]) {
                case 0: {
                    $data[$key]["s_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["s_o_out_type"]) {
                case 0: {
                    $data[$key]["s_o_out_type_name"] = "仓库调拨入库退货";
                };
                    break;
                case 1: {
                    $data[$key]["s_o_out_type_name"] = "门店调拨入库退货";
                };
                    break;
                case 2: {
                    $data[$key]["s_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 3: {
                    $data[$key]["s_o_out_type_name"] = "商品过期";
                };
                    break;
                case 4: {
                    $data[$key]["s_o_out_type_name"] = "其他退货";
                };
                    break;
                case 5: {
                    $data_all[$key]["s_o_out_type_name"] = "门店返仓拒收";
                };
                    break;
            }
        }
        $re = array();
        $re['data']=$data;
        $this->response(self::RESPONSE_SUCCES,$re);
    }
    /**
     * 被退货单列表详情接口
     * @param $store_id 门店id  store_id1是当前门店
     * @param  $s_o_out_id  被退货单id
     */
    public function get_cover_goods_returnbill_detail(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $s_o_out_id = I("post.s_o_out_id",'','intval');
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$s_o_out_id,'msg'=>'缺少被退货单id'),
        );
        $this->_isNull($msg);
        /****************************验证参数结束************************************/
        $storeOtherOutModel =  D('StoreOtherOut');

        $data_all = $storeOtherOutModel->get_cover_store_other_out_list_detail_model($store_id, $s_o_out_id);
        if($data_all === false){
            $this->response(0,'主表没数据');
        }
        foreach ($data_all as $key=>$val){
            $data_all[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch ($val["s_o_out_status"]) {
                case 0: {
                    $data_all[$key]["s_o_out_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data_all[$key]["s_o_out_status_name"] = "已审核";
                };
                    break;
            }
            switch ($val["s_o_out_type"]) {
                case 0: {
                    $data_all[$key]["s_o_out_type_name"] = "仓库调拨入库退货";
                };
                    break;
                case 1: {
                    $data_all[$key]["s_o_out_type_name"] = "门店调拨入库退货";
                };
                    break;
                case 2: {
                    $data_all[$key]["s_o_out_type_name"] = "盘亏退货";
                };
                    break;
                case 3: {
                    $data_all[$key]["s_o_out_type_name"] = "商品过期";
                };
                    break;
                case 4: {
                    $data_all[$key]["s_o_out_type_name"] = "其他退货";
                };
                    break;
                case 5: {
                    $data_all[$key]["s_o_out_type_name"] = "门店返仓拒收";
                };
                    break;
            }
        }
        $data = $storeOtherOutModel->get_cover_store_other_out_detail_model($store_id, $s_o_out_id);
        if($data === false){
            $this->response(0,'详情没数据');
        }
        foreach ($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
        }
        $re = array();
        $re['modata']=$data_all;
        $re['data']=$data;
        $this->response(self::RESPONSE_SUCCES,$re);
    }

    /**
     * 被退货单审核
     * @apram store_id  门店id 必须
     * @param $s_o_out_id  被退货单id
     */
    public function check(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $s_o_out_id = I("post.s_o_out_id",'','intval');
        $admin_id = $this->uid;
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$s_o_out_id,'msg'=>'缺少被退货单id'),
        );
        $this->_isNull($msg);
        /****************************验证参数结束************************************/
        $storeOtherOutModel =  D('StoreOtherOut');
        $data = $storeOtherOutModel->check_model($admin_id, $store_id, $s_o_out_id);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
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