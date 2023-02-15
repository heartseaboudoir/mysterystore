<?php
/**
 * 内部app发货申请相关接口
 * User: zzy
 * Date: 2018-04-27
 * Time: 16:29
 */
namespace Internal\Controller;

use Erp\Model\MessageWarnModel;

class StoreRequestController extends ApiController{
    public function _initialize()
    {
        // 是否验证token
        $action = ACTION_NAME;//当前请求action名称
        $actions = array();
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
     * 获取门店所在区域的所有仓库信息
     * @paran $store_id    门店id   必须
     * return json
     */
    public function get_warehouse(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $msg = array(array('name'=>$store_id,'msg'=>'缺少门店id'));
        $this->_isNull($msg);

        /****************************验证参数结束************************************/

        $storeModel = D("Store");
        $warehouseModel = D("Warehouse");
        $data = $warehouseModel->field("w_id,w_name,shequ_id")->where(array("shequ_id"=>$storeModel->where(array("id"=>$store_id))->getField("shequ_id"),'w_type'=>0))->select();
        if($data === false){
            $this->response(0,"内部错误!请联系客服");
        }
        $re = array();
        $re['data'] = $data;
        $this->response(200,$re);
    }

    /**
     *获取商品列表
     * @param $store_id 门店id  必须
     * @param $goods_name 商品名  必须
     * return json
     */
    public function get_store_all_goods(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id  = I("post.store_id","","intval");
        $goods_name  = I("post.goods_name","",'trim');
        $stock_status  = I("post.stock_status",0,'intval');
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/
        $goodsStoreModel = D("GoodsStore");
        $attrValueModel = D("AttrValue");
        $data = $goodsStoreModel->get_store_all_goods($store_id,$goods_name,$stock_status);
        if ($this->_isArrayNull($data) != null) {
            foreach ($data as $key => $val) {
                $data[$key]['pic_url'] = get_cover($val['cover_id'],'path');
                 $attr_value_array = $attrValueModel->field('value_id,value_name')->where(array('goods_id'=>$val['goods_id'],'status'=>1))->select();
                if(empty($attr_value_array)){
                    $attr_value_array = array();
                }
                $data[$key]['attr_value_array'] = $attr_value_array; 
            }
        }
        $re['data'] = $data;
         $this->response(200,$re);
    }

    /**
     * 提交门店发货申请单
     * @param $store_id 门店id 必须
     * @param $info_json_str 申请信息 必须   格式：[{"goods_id":"","g_num":"","value_id":"","remark":""},{"goods_id":"","g_num":"","value_id","remark":""}]
     * @param $w_id 发货仓库 必须
     * @param $remark  备注
     */
    public function submit_deliver_goods_request_temp(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $admin_id = $this->uid;
        $store_id = I('post.store_id','','intval');
        $w_id = I('post.w_id','','intval');
        $remark = I('post.remark','');
        $info_json_str = I('post.info_json_str','');
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$detail_array,'msg'=>'缺少申请信息'),
            array('name'=>$w_id,'msg'=>'缺少发货仓库id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/

        $storeRequestModel = D("StoreRequest");
        $data = $storeRequestModel->submit_request($admin_id, $store_id, $w_id, $remark,$detail_array);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, $w_id, 0, 0, $data['data'], \Erp\Model\MessageWarnModel::STOCK_TO_STORE);
        $this->response(200,'操作成功');
    }

    /**
     * 查看临时发货申请商品列表
     * @param $store_id 门店id  必须
     * @param $w_id 选择要发货的仓库 必须
     */
    public function get_deliver_goods_request_temp(){
        $store_id = I('post.store_id','','intval');
        $w_id = I('post.w_id','','intval');
        $admin_id = $this->uid;
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$w_id,'msg'=>'缺少仓库id'),
        );
        $this->_isNull($msg);
        /*****************************接口参数验证结束***********************/
        $requestTempModel = D('RequestTemp');
        $data = $requestTempModel->get_deliver_goods_request_temp($store_id, $w_id, $admin_id);
        if($data === false){
            $this->response(0,"内部错误!请联系客服");
        }
        if ($this->_isArrayNull($data) != null) {
            foreach ($data as $key => $val) {
                $data[$key]['pic_url'] = get_cover($val['cover_id'],'path');
            }
        }
        $re = array();
        $re['data'] = $data;
        $this->response(200,$re);
    }


    /**
     * 修改临时发货申请商品数量
     * @param info_json_str 申请信息 必须   格式：[{"re_id":"","g_num":"","remark":""},{"re_id":"","g_num":"","remark":""}]
     */
    public function updata_deliver_goods_request_temp(){
        $info_json_str = I('post.info_json_str','');
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        $msg = array(
            array('name'=>$detail_array,'msg'=>'缺少修改信息'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $requestTempModel = D('RequestTemp');
        foreach ($detail_array as $k=>$v){
            $v['id'] = $v['re_id'];
            $data = $requestTempModel->save($v);
            if($data === false){
                $this->response(0,'内部错误请联系管理员!');
            }
        }
        $this->response(200,'操作成功');
    }

    /**
     * 删除临时发货申请商品
     * @param $re_id 必须
     */
    public function del_deliver_goods_request_temp(){
        $re_id = I('post.re_id','','intval');
        $msg = array(
            array('name'=>$re_id,'msg'=>'缺少临时发货申请表id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $requestTempModel = D('RequestTemp');
        $data = $requestTempModel->where(array('id'=>$re_id))->delete();
        if($data === false){
            $this->response(0,'内部错误请联系管理员!');
        }
        if($data){
            $this->response(200,'操作成功');
        }else{
            $this->response(0,'操作失败');
        }
    }

    /**
     * 获取门店发货申请单列表
     * @param $store_id 门店id 必须
     * @param $s_r_status 审核状态 必须 默认1 已审核 0 未审核
     */
    public function  get_store_request(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $store_id = I('post.store_id','','intval');
        $s_r_status = I('post.s_r_status','','intval');
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeRequestModel = D('StoreRequest');
        $data = $storeRequestModel->get_store_request($store_id,$s_r_status);
        if($data === false){
            $this->response(0,'内部错误!请联系管理员');
        }
        foreach($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
                //0.新增,1.出库中,2.部分发货,3.全部发货,4.全部拒绝,5.已作废,6.仓库转采购直接发门店,7.仓库转采购发仓库,8.同时都有
            switch ($val["s_r_status"]) {
                case 0: {
                    $data[$key]["s_r_status_name"] = "未审核";
                };
                    break;
                case 1: {
                    $data[$key]["s_r_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_r_status_name"] = "部分通过";
                };
                    break;
                case 3: {
                    $data[$key]["s_r_status_name"] = "全部通过";
                };
                    break;
                case 4: {
                    $data[$key]["s_r_status_name"] = "全部拒绝";
                };
                    break;
                case 5: {
                    $data[$key]["s_r_status_name"] = "已作废";
                };
                    break;
                case 6: {
                    $data[$key]["s_r_status_name"] = "转门店采购";
                };
                    break;
                case 7: {
                    $data[$key]["s_r_status_name"] = "仓库备货中";
                };
                    break;
                case 8: {
                    $data[$key]["s_r_status_name"] = "部分转门店采购，部分仓库备货中";
                };
                    break;
            }
            if ($val["pass_num"] == $val["g_type"]) {
                $data[$key]["s_r_status_name"] = "全部拒绝";
            }
            if ($val["pass_num"] > 0 && $val["sf_nums"] > 0 && $val["sf_nums"] < $val["g_nums"] && ($val["pass_num"] != $val["g_type"])) {
                $data[$key]["s_r_status_name"] = "部分发货";
            }
            if ($val["sf_nums"] == $val["g_nums"]) {
                $data[$key]["s_r_status_name"] = "全部发货";
            }
            if ($val["pass_num"] > 0 && ($val["pass"] % 2 == 1)) {
                $data[$key]["s_r_status_name"] = "部分拒绝";
            }
        }
        $re = array();
        $re['data'] = $data;
         $this->response(200,$re);

    }
    /**
     * 获取门店申请单详情查看
     * @param $s_r_id 发货申请单id
     * @param $store_id 门店id
     */
    public function  get_store_request_detail(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $store_id = I('post.store_id','','intval');
        $s_r_id = I('post.s_r_id','','intval');
        $msg = array(
            array('name'=>$s_r_id,'msg'=>'缺少发货申请单id'),
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeRequestModel = D('StoreRequest');
        $store_request_all = $storeRequestModel->get_store_request_one($store_id,$s_r_id);
        if($store_request_all ===false){
            $this->response(0,'获取本单数据失败');
        }
        foreach($store_request_all as $key=>$val){
            $store_request_all[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            $store_request_all[$key]["s_r_type"] = "门店申请";//目前只有门店申请
            //状态:0.新增,1.出库中,2.部分发货,3.全部发货,4.全部拒绝,5.已作废,6.仓库转采购直接发门店,7.仓库转采购发仓库,8.同时都有
            switch ($val['s_r_status']){
                case 0: {
                    $store_request_all[$key]["s_r_status"] = "未审核";
                };
                    break;
                case 1: {
                    $store_request_all[$key]["s_r_status"] = "已审核";
                };
                    break;
                case 2: {
                    $store_request_all[$key]["s_r_status"] = "部分通过";
                };
                    break;
                case 3: {
                    $store_request_all[$key]["s_r_status"] = "全部通过";
                };
                    break;
                case 4: {
                    $store_request_all[$key]["s_r_status"] = "全部拒绝";
                };
                    break;
                case 5: {
                    $store_request_all[$key]["s_r_status"] = "已作废";
                };
                    break;
                case 6: {
                    $store_request_all[$key]["s_r_status"] = "转门店采购";
                };
                    break;
                case 7: {
                    $store_request_all[$key]["s_r_status"] = "仓库备货中";
                };
                    break;
                case 8: {
                    $store_request_all[$key]["s_r_status"] = "部分转门店采购，部分仓库备货中";
                };
                    break;
            }
        }
        $data = $storeRequestModel->get_store_request_detail($store_id,$s_r_id);
        if($data === false){
            $this->response(0,'内部错误!请联系管理员');
        }
        foreach($data as $key=>$val){
            $data[$key]['warehouse_num']= rtrim(rtrim($val['warehouse_num'],'0'),'.');
            switch ($val["is_pass"]) {
                case 0: {
                    $data[$key]["s_r_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_r_status_name"] = '已拒绝';
                };
                    break;
                case 2: {
                    if ($val["g_num"] > $val["pass_num"]) {
                        $data[$key]["s_r_status_name"] = "部分通过";
                    } else {
                        $data[$key]["s_r_status_name"] = "通过";
                    }
                };
                    break;
                case 3: {
                    $data[$key]["s_r_status_name"] = "已转采购";
                };
                    break;
                case 4: {
                    $data[$key]["s_r_status_name"] = "仓库备货中";
                };
                    break;
            }
        }
        $re = array();
        $re['data'] = $data;
        $re['modata'] = $store_request_all;
        $this->response(200,$re);

    }

    /**
     *再次申请门店发货单
     * @param $s_r_id   发货申请单id
     * @param $store_id 门店id
     * @param $w_id  发货仓库
     */
    public function again_submit_deliver_goods_request_temp (){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $admin_id = $this->uid;
        $store_id = I('post.store_id','','intval');
        $w_id = I('post.w_id','','intval');
        $s_r_id = I('post.s_r_id','','intval');
        $msg = array(
            array('name'=>$s_r_id,'msg'=>'缺少发货申请单id'),
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$w_id,'msg'=>'缺少发货仓库id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeRequestDetailModel = M('StoreRequestDetail');
        $detail_array = $storeRequestDetailModel->where(array('s_r_id'=>$s_r_id))->select();
        if(empty($detail_array)){
            $this->response(0,'再次申请失败');
        }
        $storeRequestModel = D("StoreRequest");
        $data = $storeRequestModel->submit_request($admin_id, $store_id, $w_id, '再次申请',$detail_array);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, $w_id, 0, 0, $data['data'], \Erp\Model\MessageWarnModel::STOCK_TO_STORE);
        $this->response(200,'操作成功');

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