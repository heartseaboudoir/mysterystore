<?php
/**
 * 返仓相关接口
 * User: zzy
 * Date: 2018/5/7
 * Time: 22:12
 */
namespace Internal\Controller;
class BackToWarehouseController extends ApiController{
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
     * return json
     */
    public function get_store_goods(){
        if(!IS_POST){
            $this->response(0,"非法操作");
        }
        $store_id  = I("post.store_id","","intval");
 		$goods_name  = I("post.goods_name","",'trim');
        $msg = array(array('name'=>$store_id,'msg'=>'缺少门店id'));
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/

        $goodsStoreModel = D("GoodsStore");
        $attrValueModel = D("AttrValue");
        $data = $goodsStoreModel->get_store_goods($store_id,$goods_name);
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
     * 提交门店返仓申请单
     * @param $store_id 门店id 必须
     * @param $info_json_str 申请信息 必须   格式：[{"goods_id":"","g_num":"","remark":"","value_id":""},{"goods_id":"","g_num":"","remark":"","value_id":""}]
     * @param $w_id 返仓仓库 必须
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
        $storeBackModel = D("StoreBack");
        $data = $storeBackModel->submit_store_back($admin_id, $store_id, $w_id, $remark,$detail_array);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        //加入消息提醒
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, $w_id, 0, 0, $data['data'], \Erp\Model\MessageWarnModel::STORE_RETURN_STOCK);
        $this->response(200,'操作成功');
    }

    /**
     * 获取门店返仓申请列表
     * @param $store_id  门店id
     * @param s_back_status 审核状态  1已审核 0 未审核 2 已作废
     */
    public function get_store_goods_returnticket_list(){
        if(!IS_POST){
            $this->response(0,'非法操作');
        }
        $admin_id = $this->uid;
        $store_id = I('post.store_id','','intval');
        $store_back_status = I('post.s_back_status',0,'intval');
        $msg = array(
            array('name'=>$store_id,'msg'=>'缺少门店id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/
        $storeBackModel = D('StoreBack');
        $data = $storeBackModel->get_store_back($store_id, $store_back_status);
        if($data === false){
            $this->response(0,'获取数据失败');
        }
        foreach($data as $key=>$val){
            $data[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch($val['s_back_status']){
                case 0:{
                $data[$key]['s_back_status_name'] = '未审核';
                };
                break;
                case 1:{
                    $data[$key]['s_back_status_name'] = '已审核';
                };
                 break;
                case 2: {
                    $data[$key]["s_back_status_name"] = "已作废";
                };
                    break;
            }
            switch($val['s_back_type']){
                case 0:{
                    $data[$key]['s_back_type_name'] = '门店返仓';
                };
                    break;
                case 1:{
                    $data[$key]['s_back_type_name'] = '其他';
                };
                    break;
            }
        }
        $re = array();
        $re['data'] = $data;
        $this->response(self::RESPONSE_SUCCES,$re);
    }
    /**
     * 获取门店返仓申请列表详细信息
     * @param $store_id  门店id
     * @param s_back_id 返仓申请id
     */
    public function get_store_goods_returnticket_detail_list()
    {
        if (!IS_POST) {
            $this->response(0, '非法操作');
        }
        $store_id = I('post.store_id', '', 'intval');
        $s_back_id = I('post.s_back_id', 0, 'intval');
        $msg = array(
            array('name' => $store_id, 'msg' => '缺少门店id'),
            array('name' => $s_back_id, 'msg' => '缺少返仓申请id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/
        $storeBackModel = D('StoreBack');
        $data_all = $storeBackModel->get_store_back_one($store_id, $s_back_id);
        if ($data_all === false) {
            $this->response(0, '获取主表数据失败');
        }
        foreach($data_all as $key=>$val){
            $data_all[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch($val['s_back_status']){
                case 0:{
                    $data_all[$key]['s_back_status_name'] = '未审核';
                };
                    break;
                case 1:{
                    $data_all[$key]['s_back_status_name'] = '已审核';
                };
                    break;
                case 2: {
                    $data_all[$key]["s_back_status_name"] = "已作废";
                };
                    break;
            }
            switch($val['s_back_type']){
                case 0:{
                    $data_all[$key]['s_back_type_name'] = '返仓申请';
                };
                    break;
                case 1:{
                    $data_all[$key]['s_back_type_name'] = '其他';
                };
                    break;
            }
        }

        $data = $storeBackModel->get_store_back_detail($store_id, $s_back_id);
        if($data === false){
            $this->response(0,'获取商品详情失败');
        }
        foreach($data as $key=>$val){
            $data_all[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            switch($val['s_back_status']){
                case 0:{
                    $data_all[$key]['s_back_status_name'] = '未审核';
                };
                    break;
                case 1:{
                    $data_all[$key]['s_back_status_name'] = '已审核';
                };
                    break;
                case 2: {
                    $data_all[$key]["s_back_status_name"] = "已作废";
                };
                    break;
            }
            switch($val['s_back_type']){
                case 0:{
                    $data_all[$key]['s_back_type_name'] = '返仓申请';
                };
                    break;
                case 1:{
                    $data_all[$key]['s_back_type_name'] = '其他';
                };
                    break;
            }
        }
        $re = array();
        $re['data'] = $data;
        $re['modata'] = $data_all;
        $this->response(self::RESPONSE_SUCCES, $re);
    }

    /**
     * 作废返仓申请单
     * @param $s_back_id 返仓单id
     */
    public function update_cance(){
        if (!IS_POST) {
            $this->response(0, '非法操作');
        }
        $s_back_id = I('post.s_back_id', 0, 'intval');
        $msg = array(
            array('name' => $s_back_id, 'msg' => '缺少返仓申请id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/
        $storeBackModel = D('StoreBack');
        $datas = $storeBackModel->where(" s_back_id={$s_back_id} and s_back_status=0 ")->limit(1)->select();
        if ($this->_isArrayNull($datas) == null) {
            $this->response(0, "无法作废该申请");
        }
        $ok = $storeBackModel->where(" s_back_id={$s_back_id} ")->limit(1)->save(array("s_back_status" => 2));
        if ($ok === false) {
            $this->response(0, "操作失败");
        } else {
            $this->response(self::RESPONSE_SUCCES, "操作成功");
        }
    }

    /**
     * 再次申请
     * @param $store_id  门店id
     * @param  $s_back_id 返仓单id
     * @param  $w_id   返仓仓库id
     */
    public function again_submit_deliver_goods_request_temp(){
        if (!IS_POST) {
            $this->response(0, '非法操作');
        }
        $store_id = I('post.store_id', '', 'intval');
        $s_back_id = I('post.s_back_id','', 'intval');
        $w_id = I('post.w_id','', 'intval');
        $admin_id = $this->uid;
        $msg = array(
            array('name' => $store_id, 'msg' => '缺少门店id'),
            array('name' => $s_back_id, 'msg' => '缺少返仓申请id'),
            array('name' => $w_id, 'msg' => '缺少仓库id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/
        $storeBackDetailModel = M('StoreBackDetail');
        $detail_array = $storeBackDetailModel->where(array('s_back_id'=>$s_back_id))->select();
        if(empty($detail_array)){
            $this->response(0,'再次申请失败');
        }

        $storeBackModel = D("StoreBack");
        $data = $storeBackModel->submit_store_back($admin_id, $store_id, $w_id, '再次申请',$detail_array);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        //加入消息提醒
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, $w_id, 0, 0, $data['data'], \Erp\Model\MessageWarnModel::STORE_RETURN_STOCK);
        $this->response(200,'操作成功');
    }

    /**
     * 返仓单审核接口
     * @param  $store_id 门店id
     * @param  $s_back_id  返仓单id
     */
    public function check(){
        if (!IS_POST) {
            $this->response(0, '非法操作');
        }
        $store_id = I('post.store_id', '', 'intval');
        $s_back_id = I('post.s_back_id','', 'intval');
        $admin_id = $this->uid;
        $msg = array(
            array('name' => $store_id, 'msg' => '缺少门店id'),
            array('name' => $s_back_id, 'msg' => '缺少返仓申请id'),
        );
        $this->_isNull($msg);
        /***********************接口参数验证结束**************************************/
        $storeBackModel = D('StoreBack');
        $data = $storeBackModel->StoreBackcheck($admin_id, $store_id, $s_back_id);
        if($data['code'] == 0){
            $this->response(0,$data['msg']);
        }
        //加入消息提醒
        $messageWarnModel = D('Erp/MessageWarn');
        $messageWarnModel->pushMessageWarn($admin_id, $data['data']['warehouse_id'], 0, 0, $data['data'], \Erp\Model\MessageWarnModel::STOCK_IN);
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