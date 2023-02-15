<?php
/**
 * 门店调相关接口
 * User: zzy
 * Date: 2018-05-03
 * Time: 16:27
 */
namespace Internal\Controller;
class StoreAssignmentApplicationController extends ApiController{
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
     * 获取门店所在区域的所有门店信息
     * @paran $store_id    门店id   必须
     * return json
     */
    public function get_store(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id = I("post.store_id",'','intval');
        $msg = array(array('name'=>$store_id,'msg'=>'缺少门店id'));
        $this->_isNull($msg);

        /****************************验证参数结束************************************/

        $storeModel = D("Store");
        $shequ_id = $storeModel->where(array("id"=>$store_id))->getField("shequ_id");
        $data = $storeModel->field("id as store_id,title as store_name,shequ_id")->where(array("shequ_id"=>$shequ_id,'status'=>1,'id'=>array('NEQ',$store_id)))->select();
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
     * @param $store_id_allocation 门店id  必须
     * return json
     */
    public function get_store_all_goods(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id  = I("post.store_id","","intval");
        $store_id_allocation  = I("post.store_id_allocation","","intval");
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$store_id_allocation,'msg'=>'缺少调拨门店id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/

        $goodsStoreModel = D("GoodsStore");
        $attrValueModel = D("AttrValue");
        $data = $goodsStoreModel->get_store_all_goods__allocation($store_id,$store_id_allocation);
        if ($this->_isArrayNull($data) != null) {
             foreach ($data as $key => $val) {
                $data[$key]['pic_url'] = get_cover($val['cover_id'],'path');
            } 
        }
        $re['data'] = $data;
        $this->response(200,$re);
    }

    /**
     * 提交门店调拨申请单
     * @param $store_id 门店id 必须
     * @param $store_id_allocation 调拨门店id 必须
     * @param $info_json_str 申请信息 必须   格式：[{"goods_id":"","g_num":"","remark":""},{"goods_id":"","g_num":"","remark":""}]
     * @param $remark  备注
     */
    public function submit_deliver_goods_request_temp(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $admin_id = $this->uid;
        $store_id = I('post.store_id','','intval');
        $store_id_allocation = I('post.store_id_allocation','','intval');
        $remark = I('post.remark','');
        $info_json_str = I('post.info_json_str','');
        $info_json_str = base64_decode($info_json_str);
        $detail_array = json_decode($info_json_str, true);
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$store_id_allocation,'msg'=>'缺少调拨门店id'),
            array('name'=>$detail_array,'msg'=>'缺少申请信息'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/

        $storeToStoreModel = D("StoreToStore");
        $data = $storeToStoreModel->submit_request_allocation($admin_id, $store_id, $store_id_allocation, $remark,$detail_array);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, 0, $store_id_allocation, 0, $data['data'], \Erp\Model\MessageWarnModel::STORE_ALLOT);
        $this->response(200,'操作成功');
    }

    /**
     * 查看临时调拨申请商品列表
     * @param $store_id 门店id  必须
     * @param $store_id_allocation 选择要发货的仓库 必须
     */
    public function get_deliver_goods_request_temp(){
        $store_id = I('post.store_id','','intval');
        $store_id_allocation = I('post.store_id_allocation','','intval');
        $admin_id = $this->uid;
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$store_id_allocation,'msg'=>'缺少发货门店id'),
        );
        $this->_isNull($msg);
        /*****************************接口参数验证结束***********************/
        $requestTempModel = D('RequestTemp');
        $data = $requestTempModel->get_deliver_goods_request_temp_allocation($store_id, $store_id_allocation, $admin_id);
        if($data === false){
            $this->response(0,"内部错误!请联系客服");
        }
        if ($this->_isArrayNull($data) != null) {
            foreach ($data as $key => $val) {
                $data[$key]['pic_url'] = get_cover($val['cover_id'],'path');
            }
        }
        $this->response(200,$data);
    }


    /**
     * 修改临时调拨申请商品数量
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
     * 删除临时调拨申请商品
     * @param $re_id 必须
     */
    public function del_deliver_goods_request_temp(){
        $re_id = I('post.re_id','','intval');
        $msg = array(
            array('name'=>$re_id,'msg'=>'缺少临时调拨申请表id'),
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
     * 获取门店调拨申请单列表
     * @param $store_id 门店id 必须
     * @param $s_t_s_status 审核状态  默认1 已审核 0 未审核
     */
    public function  get_store_to_store(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $store_id = I('post.store_id','','intval');
        $s_t_s_status = I('post.s_t_s_status','1','intval');
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeToStoreModel = D("StoreToStore");
        $data = $storeToStoreModel->get_store_to_store($store_id, $s_t_s_status);
        if($data === false){
            $this->response(0,'内部错误!请联系管理员');
        }
        foreach($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            //状态:0.新增,1.出库中,2.部分发货,3.全部发货,4.全部拒绝,5.已作废
            switch ($val["s_t_s_status"]) {
                case 0: {
                    $data[$key]["s_r_status_name"] = "新增";
                };
                    break;
                case 1: {
                    $data[$key]["s_r_status_name"] = "已审核";
                };
                    break;
                case 2: {
                    $data[$key]["s_r_status_name"] = "部分发货";
                };
                    break;
                case 3: {
                    $data[$key]["s_r_status_name"] = "全部发货";
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
            }
        }
        $re = array();
        $re['data'] = $data;
        $this->response(200,$re);

    }
    /**
     * 获取门店调拨申请单详情查看
     * @param $s_t_s_id 调拨申请单id
     * @param $store_id 门店id
     */
    public function  get_store_to_store_detail(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $store_id = I('post.store_id','','intval');
        $s_t_s_id = I('post.s_t_s_id','','intval');
        $msg = array(
            array('name'=>$s_t_s_id,'msg'=>'缺少调拨申请单id'),
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeToStoreModel = D("StoreToStore");
        $storeToStore_all = $storeToStoreModel->get_store_to_store_one($store_id,$s_t_s_id);
        foreach ($storeToStore_all as $key=>$val){
            $storeToStore_all[$key]["s_r_type"] = "门店调拨申请";//门店调拨申请
            $storeToStore_all[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            //状态:0.新增,1.出库中,2.部分发货,3.全部发货,4.全部拒绝,5.已作废
            switch ($val["s_t_s_status"]) {
                case 0: {
                    $storeToStore_all[$key]["s_t_s_status"] = "新增";
                };
                    break;
                case 1: {
                    $storeToStore_all[$key]["s_t_s_status"] = "已审核";
                };
                    break;
                case 2: {
                    $storeToStore_all[$key]["s_t_s_status"] = "部分发货";
                };
                    break;
                case 3: {
                    $storeToStore_all[$key]["s_t_s_status"] = "全部发货";
                };
                    break;
                case 4: {
                    $storeToStore_all[$key]["s_t_s_status"] = "全部拒绝";
                };
                    break;
                case 5: {
                    $storeToStore_all[$key]["s_t_s_status"] = "已作废";
                };
                    break;
            }
        }
        $data = $storeToStoreModel->get_store_to_store_detail($store_id,$s_t_s_id);
        if($data === false){
            $this->response(0,'内部错误!请联系管理员');
        }
        foreach($data as $key=>$val){
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
            }
        }
        $re = array();
        $re['data'] = $data;
        $re['modata'] = $storeToStore_all;
        $this->response(200,$re);

    }

    /**
     *再次申请门店调拨单
     * @param $s_t_s_id 调拨申请单id
     * @param $store_id 门店id
     * @param $store_id_allocation 调拨门店id 必须
     */
    public function again_submit_deliver_goods_request_temp (){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $admin_id = $this->uid;
        $store_id = I('post.store_id','','intval');
        $store_id_allocation = I('post.store_id_allocation','','intval');
        $s_t_s_id = I('post.s_t_s_id','','intval');
        $msg = array(
            array('name'=>$s_t_s_id,'msg'=>'缺少发货申请单id'),
            array('name'=>$store_id,'msg'=>'缺少门店id'),
            array('name'=>$store_id_allocation,'msg'=>'缺少调拨门店id'),
        );
        $this->_isNull($msg);
        /************************************接口参数验证结束***********************/
        $storeToStoreDetailModel = M('StoreToStoreDetail');
        $detail_array = $storeToStoreDetailModel->where(array('s_t_s_id'=>$s_t_s_id))->select();
        if(empty($detail_array)){
            $this->response(0,'再次申请失败');
        }

        $storeToStoreModel = D("StoreToStore");
        $data = $storeToStoreModel->submit_request_allocation($admin_id, $store_id, $store_id_allocation, '再次申请',$detail_array);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, 0, $store_id_allocation, 0, $data['data'], \Erp\Model\MessageWarnModel::STORE_ALLOT);
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