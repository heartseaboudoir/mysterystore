<?php
/**
 * Created by PhpStorm.
 * User: Ard
 * Date: 2018-04-17
 * Time: 10:57
 */

namespace Erp\Controller;

use Erp\Model\MessageWarnModel;
use Think\Controller;
use Erp\Extend\JgMessagePush;

class MessageWarnController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
    }

    /**
     * name:列表接口
     * method:post
     * params:
     *          admin_id 用户id 必填
     *          warehouse_id 仓库id 必填
     *          store_id 门店id 必填
     *          m_status 读取状态【0为未读 1 未已读】 非必填
     *          m_type    来源【0.商品过期提醒,1.内部消息,2.其它 , 3汇总消息 100：所有来源】 非必填
     *          m_other_type 汇总消息类型 1.门店汇总2.仓库汇总3.采购汇总 非必填
     *          page        当前页    非必填   默认1
     *          keyword  关键字  非必需
     *          s_date  开始日期  非必填
     *          e_date  结束日期  非必填
     *          pageSize  每页显示数量  默认15
     * date:2018-04-17
     */
    public function getMessageList(){
        $m_status = I("get.m_status");
        $m_type = I("get.m_type" , 100);
        $m_other_type = I("get.m_other_type");
        $admin_id = UID;
        $keyword = I("get.keyword");

        //$dates = $this->getDates();
        $s_date = I("get.s_date");
        $e_date = I("get.e_date");
        $page = I("post.page" , 1);
        $pageSize = I("post.pageSize" , 15);
        $str_len = I('post.str_len',18);

        $twoWeekTime = date('Y-m-d', strtotime('-14 days'));
        //设定查询时间
        $where['ctime'] =  array(array('ELT', time()), array('EGT', strtotime($twoWeekTime)));
        $where['to_admin_id'] = $admin_id;
        if($s_date && $e_date){
            $where['ctime'] = array('BETWEEN', array(strtotime($s_date), strtotime($e_date)+3600*24));
        }
        elseif($s_date){
            $where['ctime'] = array('EGT', strtotime($s_date));
        }elseif($e_date){
            $where['ctime'] = array('ELT', strtotime($e_date)+3600*24);
        }

        if ($m_status != 100 && !empty($m_status)) {
            $where['m_status'] = $m_status;
        }
        if($m_type != 100){
            $where['m_type'] = $m_type;
        }
        if($m_type == 3 && $m_other_type !=100){
            $where['m_other_tpye'] = $m_other_type;
        }
        if(!empty($keyword)){
            $where['message_title'] = array('like' , '%'.$keyword.'%');
        }

        $MessageWarnModel = M("MessageWarn");
        //$getMessageInfo = $MessageWarnModel->where($where)->order('m_status asc,ctime desc')->limit($page , $pageSize)->select();
        //查询用户消息读取设置
        $messageWarnSetModel = D('MessageWarnSet');
        $getMessageReadRuls = $messageWarnSetModel->where(array('uid' => UID))->select();
        $getMessageInfo = array();
        $transfers = $pageSize;
        //先获取数据
        $getData = $MessageWarnModel->where($where)->order('m_status asc , ctime desc')->select();
        $m_type_arr = array();
        $m_other_type_arr = array();
        $m_other_type_str = '';
        //循环消息读取设置 更改结构便于处理数据
        foreach($getMessageReadRuls as $key => $value){
            if($value['m_type'] == MessageWarnModel::TYPE_STATISTICS || $value['m_type'] == MessageWarnModel::TYPE_ONE_MESSAGE){
                if(empty($value['m_other_type'])){
                    continue;
                }
                $m_type_arr[] = $value['m_type'];
                $m_other_type_str = $m_other_type_str.$value['m_other_type'];
            }else{
                $m_type_arr[] = $value['m_type'];
            }
        }
        $m_other_type_arr = explode(',',$m_other_type_str);
        //筛选数据

        foreach($getData as $key => $value){
            if(in_array($value['m_type'] , $m_type_arr)){
                $getMessageInfo[] = $value;
            }
            if(in_array($value['m_other_type'] , $m_other_type_arr)){
                $getMessageInfo[] = $value;
            }
            if(count($getMessageInfo) >= 15){
                break;
            }
        }
        //提取未读
/*        foreach($getMessageReadRuls as $key => $value){
            $where['m_status'] = MessageWarnModel::IS_NEW;
            if($value['m_type'] == MessageWarnModel::TYPE_STATISTICS || $value['m_type'] == MessageWarnModel::TYPE_ONE_MESSAGE){
                if(empty($value['m_other_type'])){
                    continue;
                }
                $where['m_type'] = $value['m_type'];
                $where['m_other_type'] = array('in' , $value['m_other_type']);
            }else{
                $where['m_type'] = $value['m_type'];
            }
            $getData = $MessageWarnModel->where($where)->limit($page , $transfers)->select();
            $transfers = $transfers - count($getData);
            if($getData){
                $getMessageInfo = array_merge($getMessageInfo , $getData);
            }
            if(count($getMessageInfo) >= $pageSize){
                break;
            }
        }
        $sort = array(
            'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
            'field'     => 'ctime',       //排序字段
        );
        $getMessageInfo = self::sortData($getMessageInfo, $sort);
        //如若需要优化 则需要全部查询出来 然后根据数据的插入时间来截取数据?????????
        if(count($getMessageInfo) < $pageSize){
            //提取已读
            foreach($getMessageReadRuls as $key => $value){
                $where['m_status'] = MessageWarnModel::IS_READED;
                if($value['m_type'] == MessageWarnModel::TYPE_STATISTICS || $value['m_type'] == MessageWarnModel::TYPE_ONE_MESSAGE){
                    if(empty($value['m_other_type'])){
                        continue;
                    }
                    $where['m_type'] = $value['m_type'];
                    $where['m_other_type'] = array('in' , $value['m_other_type']);
                }else{
                    $where['m_type'] = $value['m_type'];
                }
                $getData = $MessageWarnModel->where($where)->limit($page , $transfers)->select();
                $transfers = $transfers - count($getData);
                if($getData){
                    $getMessageInfo = array_merge($getMessageInfo , $getData);
                }
                if(count($getMessageInfo) >= $pageSize){
                    break;
                }
            }
        }*/


        $i = 1;
        foreach ($getMessageInfo as $key => $val) {
            $getMessageInfo[$key]['id'] = $i;
            $getMessageInfo[$key]['ctime'] = date('Y-m-d H:i:s' , $val['ctime']);
            //$getMessageInfo[$key]['message_content_indent'] = msubstrs($val['message_content'] , 0 , $str_len);
            unset($getMessageInfo[$key]['message_content']);
            switch ($val["m_status"]) {
                case 0: {
                    $getMessageInfo[$key]["m_status_name"] = "未读";
                };
                    break;
                case 1: {
                    $getMessageInfo[$key]["m_status_name"] = "已读";
                };
                    break;
            }
            switch ($val["m_type"]) {
                case 0: {
                    $getMessageInfo[$key]["m_type_name"] = "商品过期";
                };
                    break;
                case 1: {
                    $getMessageInfo[$key]["m_type_name"] = "内部消息";
                };
                    break;
                case 2: {
                    $getMessageInfo[$key]["m_type_name"] = "其他";
                };
                case 3 :{
                    //1.门店汇总2.仓库汇总3.采购汇总
                    $getMessageInfo[$key]['m_type_name'] = $val['m_other_type'] == 1 ? '日报汇总-门店汇总' : ($val['m_other_type'] == 2 ? '日报汇总-仓库汇总' : '日报汇总-采购汇总');
                }
                    break;
                default : {
                    $getMessageInfo[$key]["m_type_name"] = "消息提醒";
                }
                    break;
            }
            $i++;
        }
        if($getMessageInfo){
            $this->response(self::CODE_OK, $getMessageInfo);
        }else{
            $this->response(self::CODE_OK, array());
        }
    }


    /***************
     * 获取当前页
     ***************/
    private function getPageIndex()
    {
        $p = I("get.p");
        return is_null($p) || empty($p) ? 1 : $p;
    }

    /************************
     * 获取搜索日期
     * s_date：开始日期
     * e_date：结束日期
     *****************************/
    private function getDates()
    {
        //时间范围默认30天
        $s_date = I('get.s_date');
        $e_date = I('get.e_date');
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
        return array(
            "s_date" => $s_date,
            "e_date" => $e_date
        );
    }

    /*********
     * 获取每页显示数量，默认15
     */
    private function getPageSize()
    {
        $pcount = I("get.pageSize");
        return is_null($pcount) || empty($pcount) ? 15 : $pcount;
    }

    /*********************
     * 检测数组是否空
     */
    private function isArrayNull($array)
    {
        if (!is_null($array) && !empty($array) && count($array) > 0) {
            return $array;
        } else {
            return null;
        }
    }

    /**
     * 排序
     * @params $data array
     *       $sort = array(
                    'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                    'field'     => 'age',       //排序字段
                    );
     *
     */

    public function sortData($data , $sort = ""){
        if(empty($sort))return $data;

        $arrSort = array();
        foreach($data AS $uniqid => $row){
            foreach($row AS $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }
        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $data);
        }
        return $data;
    }

    //test
    public function test(){
/*        $admin_id = UID;
        $to_warehouse_id = 1;
        $to_store_id = 0;
        $message_title = '测试消息';
        $data = array(
            'sn' => 'SRK20180424',
            'ctime' => time(),
            's_type' => 2,
        );
        $type = 6;
        $MessageWarnModel = D("MessageWarn");
        $result = $MessageWarnModel->pushMessageWarn($admin_id,$to_warehouse_id,$to_store_id,$data,$type);
        var_dump($result);*/
        $uid = 2520;
        $result = JgMessagePush::pushMessageToApp($uid,'测试标题','测试单条推送',1,json_encode(array('1'=>'2222','a' => 'bbbbb')));
        var_dump($result);exit;
    }
}