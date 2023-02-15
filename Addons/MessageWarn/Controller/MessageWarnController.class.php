<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-21
 * Time: 16:33
 */

namespace Addons\MessageWarn\Controller;


use Admin\Controller\AddonsController;
use Erp\Model\MessageWarnModel;

class MessageWarnController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        //$this->check_warehouse();//检测是否选择仓库
        //$this->check_store();//检测是否已选择门店
    }

    public function index1(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://MessageWarn@MessageWarn/index'));
    }

    /***
     * name:列表接口
     * method：GET
     * params：m_status  状态【0：未读  1：已读   100：所有状态】 必须
     *           m_type    来源【0.商品过期提醒,1.内部消息,2.其它 , 3汇总消息 100：所有来源】 必须
     *           m_other_tpye 汇总消息类型 1.门店汇总2.仓库汇总3.采购汇总
     *           keyword  关键字  非必需
     *           s_date  开始日期  非必填
     *           e_date  结束日期  非必填
     * date：2018-04-17
     */
    public function index(){
        $m_status = I("get.m_status" , 100);
        $m_type = I("get.m_type" , 100);
        $m_other_type = I("get.m_other_type");
        $admin_id = UID;
        $keyword = I("get.keyword");

        //$dates = $this->getDates();
        $s_date = I("get.s_date");
        $e_date = I("get.e_date");

        $MessageWarnModel = D('Addons://MessageWarn/MessageWarn');
        $where['to_admin_id'] = $admin_id;
        $where['is_delete'] = 0;
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
/*        if(!empty($keyword)){
            //关键字模糊查询
            $map1['message_title'] = array('like' , '%'.$keyword.'%');
            $map1['message_content'] = array('like' , '%'.$keyword.'%');
            $map1['_logic'] = 'or';
            $mapk['_complex'] = $map1;
            //设定查看当前仓库或者门店数据
            if(!empty($this->_store_id) && !empty($this->_warehouse_id)){
                $map2['to_store_id'] =  $this->_store_id;
                $map2['to_warehouse_id'] =  $this->_warehouse_id;
                $map2['_logic'] = 'or';
                $mapx['_complex'] = $map2;
            }elseif(!empty($this->_store_id)){
                $mapx['to_store_id'] =  $this->_store_id;
            }elseif(!empty($this->_warehouse_id)){
                $mapx['to_warehouse_id'] =  $this->_warehouse_id;
            }
            $where['_complex'] = array($mapx,$mapk);
        }else{*/
            //设定查看当前仓库或者门店数据
           /* if(!empty($this->_store_id) && !empty($this->_warehouse_id)){
                $map['to_store_id'] =  $this->_store_id;
                $map['to_warehouse_id'] =  $this->_warehouse_id;
                $map['_logic'] = 'or';
                $where['_complex'] = $map;
            }elseif(!empty($this->_store_id)){
                $where['to_store_id'] =  $this->_store_id;
            }elseif(!empty($this->_warehouse_id)){
                $where['to_warehouse_id'] =  $this->_warehouse_id;
            }*/
        //}

        //$list = $this->lists($MessageWarnModel, $where, 'm_id desc');

        //查询用户消息读取设置
        $messageWarnSetModel = D('MessageWarnSet');
        $getMessageReadRuls = $messageWarnSetModel->where(array('uid' => UID))->select();
        $data = array();
        $flag = array();
        $m_type_ruls = array();
        $m_other_type_ruls = array();
        foreach($getMessageReadRuls as $key => $value){
            $m_type_ruls[] = $value['m_type'];
            if($value['m_type'] == MessageWarnModel::TYPE_STATISTICS || $value['m_type'] == MessageWarnModel::TYPE_ONE_MESSAGE){
                if(empty($value['m_other_type'])){
                    continue;
                }
                $m_other_type_ruls = array_merge($m_other_type_ruls , explode(',' , $value['m_other_type']));
                $where['m_type'] = $value['m_type'];
                $where['m_other_type'] = array('in' , $value['m_other_type']);
            }else{
                $where['m_type'] = $value['m_type'];
            }
            $getData = $MessageWarnModel->where($where)->select();
            if($getData){
                $data = array_merge($data , $getData);
            }
        }
        $i = 1;
        foreach ($data as $key => $val) {
            $flag[] = $val['ctime'];
            $data[$key]['id'] = $i;
            $data[$key]['ctime'] = date('Y-m-d H:i:s' , $val['ctime']);
            switch ($val["m_status"]) {
                case 0: {
                    $data[$key]["m_status_name"] = "未读";
                };
                    break;
                case 1: {
                    $data[$key]["m_status_name"] = "已读";
                };
                    break;
            }
            switch ($val["m_type"]) {
                case 0: {
                    $data[$key]["m_type_name"] = "商品过期";
                };
                    break;
                case 1: {
                    $data[$key]["m_type_name"] = "内部消息";
                };
                    break;
                case 2: {
                    $data[$key]["m_type_name"] = "其他";
                };
                    break;
                case 3 :{
                    //1.门店汇总2.仓库汇总3.采购汇总
                    $data[$key]['m_type_name'] = $val['m_other_type'] == 1 ? '日报汇总-门店汇总' : ($val['m_other_type'] == 2 ? '日报汇总-仓库汇总' : '日报汇总-采购汇总');
                }
                    break;
                case 4 :{
                    //4.门店入库 5.门店出库 6.仓库入库 7.仓库出库 8.采购申请
                    if($val['m_other_type'] == 4){
                        $data[$key]['m_type_name'] = "门店入库";
                    }elseif($val['m_other_type'] == 5){
                        $data[$key]['m_type_name'] = "门店出库";
                    }elseif($val['m_other_type'] == 6){
                        $data[$key]['m_type_name'] = "仓库入库";
                    }elseif($val['m_other_type'] == 7){
                        $data[$key]['m_type_name'] = "仓库出库";
                    }elseif($val['m_other_type'] == 8){
                        $data[$key]['m_type_name'] = "采购申请";
                    }
                }
                    break;
            }
            $i++;
        }
        //排序
        array_multisort($flag, SORT_DESC, $data);
        //分页
        $pcount = 15;
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('m_type_ruls', $m_type_ruls);
        $this->assign('m_other_type_ruls', $m_other_type_ruls);
        $this->assign('m_type' , $m_type);
        $this->assign('m_status' , $m_status);
        $this->assign('keyword' , $keyword);
        $this->assign('s_date' , $s_date);
        $this->assign('e_date' , $e_date);
        $this->assign('m_other_type' , $m_other_type);
        $this->meta_title = '站内消息';
        $this->display(T('Addons://MessageWarn@MessageWarn/index'));
    }

    public function view(){
        $m_id = I('get.m_id' , 0);
        if(empty($m_id)){
            $this->error('无效id');
        }
        //根据id 查询信息
        $MessageWarnModel = D('Addons://MessageWarn/MessageWarn');
        $mesageInfo = $MessageWarnModel->where(array('m_id' => $m_id))->find();
        if($mesageInfo){
            //更新信息状态为已读取
            $MessageWarnModel->where(array('m_id' => $m_id))->save(array('m_status' => 1));
            $mesageInfo['message_content'] = json_decode($mesageInfo['message_content'] , true);
            $inSource = array(
                '0' => '仓库出库',
                '1' => '门店调拨',
                '2' => '盘盈入库',
                '3' => '其它',
                '4' => '采购',
                '5' => '寄售'
            );
            $outSource = array(
                '0' => '仓库调拨',
                '1' => '门店申请',
                '3' => '盘亏出库',
                '4' => '其它',
                '5' => '寄售出库'
            );
            $this->assign('message' , $mesageInfo);
            $this->assign('inSource' , $inSource);
            $this->assign('outSource' , $outSource);
            $this->display(T('Addons://MessageWarn@MessageWarn/view'));
        }else{
            $this->error('无效id');
        }
    }

    /**
     * 导出信息列表
     */
    public function downloadMessageList(){
        $m_status = I("get.m_status" , 100);
        $m_type = I("get.m_type" , 100);
        $m_other_type = I("get.m_other_type");
        $admin_id = UID;
        $keyword = I("get.keyword");

        //$dates = $this->getDates();
        $s_date = I("get.s_date");
        $e_date = I("get.e_date");

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
        $where['message_title'] = array('like' , '%'.$keyword.'%');
       /* if(!empty($keyword)){
            //关键字模糊查询
            $map1['message_title'] = array('like' , '%'.$keyword.'%');
            $map1['message_content'] = array('like' , '%'.$keyword.'%');
            $map1['_logic'] = 'or';
            $mapk['_complex'] = $map1;
            //设定查看当前仓库或者门店数据
            if(!empty($this->_store_id) && !empty($this->_warehouse_id)){
                $map2['to_store_id'] =  $this->_store_id;
                $map2['to_warehouse_id'] =  $this->_warehouse_id;
                $map2['_logic'] = 'or';
                $mapx['_complex'] = $map2;
            }elseif(!empty($this->_store_id)){
                $mapx['to_store_id'] =  $this->_store_id;
            }elseif(!empty($this->_warehouse_id)){
                $mapx['to_warehouse_id'] =  $this->_warehouse_id;
            }
            $where['_complex'] = array($mapx,$mapk);
        }else{*/
            //设定查看当前仓库或者门店数据
            if(!empty($this->_store_id) && !empty($this->_warehouse_id)){
                $map['to_store_id'] =  $this->_store_id;
                $map['to_warehouse_id'] =  $this->_warehouse_id;
                $map['_logic'] = 'or';
                $where['_complex'] = $map;
            }elseif(!empty($this->_store_id)){
                $where['to_store_id'] =  $this->_store_id;
            }elseif(!empty($this->_warehouse_id)){
                $where['to_warehouse_id'] =  $this->_warehouse_id;
            }
        //}
        $MessageWarnModel = D('Addons://MessageWarn/MessageWarn');
        $list = $MessageWarnModel->where($where)->select();
        $meta_title = 'MessageList'.date('Y-m-d');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename='.$meta_title.'.xls');
        header('Pragma: no-cache');
        header('Expires: 0');
        $l = '<style>'
            . 'body{font-size:16px;}'
            . 'img{max-height:150px; max-width:200px;}'
            . 'tr{height:50px;}'
            . '.tr0{background:#eee;}'
            . 'td{border:1px solid #ccc; text-align:center;}'
            . '</style>';
        $l .= '<table>';
        $l .= '<tr style="height:60px;"><td style="text-align:center;font-size:20px; font-weight:bold;" colspan="5">'.$meta_title.' </td></tr>';
        $l .= '<tr><td width="200">序号</td><td width="200">日期</td><td width="200">标题</td><td width="100">来源</td><td width="100">状态</td></tr>';
        $i = 1;
        foreach ($list as $key => $val) {
            $l .= '<tr>';
            $l .= '<td>'.$i.'</td>'.'<td>'.$val['ctime'].'</td>'.'<td>'.$val['message_title'].'</td>'.'<td>'.$val['m_type_name'].'</td>'.'<td>'.$val['m_status_name'].'</td>';
            $l .= '</tr>';
            $i++;
        }
        $l .= '</table>';
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }


    /**
     * 导出信息详情
     */
    public function downloadMessageViewById(){
        $m_id = I('get.m_id' , 0);
        if(empty($m_id)){
            $this->error('无效id');
        }
        //根据id 查询信息
        $MessageWarnModel = D('Addons://MessageWarn/MessageWarn');
        $mesageInfo = $MessageWarnModel->alias('MW')
            ->join('hii_member M1 ON M1.uid = MW.from_admin_id' , 'LEFT')
            ->join('hii_member M2 ON M2.uid = MW.to_admin_id' , 'LEFT')
            ->field('MW.message_title,MW.message_content,MW.ctime,M1.nickname as from_nickname , M2.nickname as to_nickname,MW.m_type,MW.m_other_type')
            ->where(array('MW.m_id' => $m_id))->find();

        $title = 'MessageView'.$m_id;
        ob_clean;
        $printmodel = new \Addons\Report\Model\ReportModel();
        //根据信息类型决定调用方法
        if($mesageInfo['m_type'] == 0){
            //商品过期提醒
            $printfile = $printmodel->messageViewExpiredReport($mesageInfo,$title);
        }elseif($mesageInfo['m_type'] == 3){
            //汇总消息
            $printfile = $printmodel->messageViewStatisticsReport($mesageInfo , $title);
        }else{
            //普通消息
            $printfile = $printmodel->normalMessageReport($mesageInfo , $title);
        }
        echo($printfile);die;
    }

    /**
     * name:更改读取状态
     * params:ids
     * author:Ard
     * date:2018-04-20
     */
    public function changeStatus(){
        $ids = I('request.ids');
        $m_status = I('request.m_status');
        if (empty($ids)) {
            $this->error('请选择要操作的数据');
        }
        $MessageWarnModel = D('Addons://MessageWarn/MessageWarn');
        $MessageWarnModel->where(array('m_id' => array('in' , $ids) ))->save(array('m_status' => $m_status));
        $this->success('更新成功');
    }

    /**
     * name:删除消息
     * params:id,is_delete
     * author:ard
     * date:2018-04-20
     */
    public function changeIsDelete(){
        $ids = I('request.ids');
        if (empty($ids)) {
            $this->error('请选择要操作的数据');
        }
        $MessageWarnModel = D('Addons://MessageWarn/MessageWarn');
        $MessageWarnModel->where(array('m_id' => array('in' , $ids) ))->save(array('is_delete' => 1));
        $this->success('删除成功');
    }

    public function sendMessage(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://MessageWarn@MessageWarn/send_message'));
    }

    public function view2(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://MessageWarn@MessageWarn/view'));
    }

    /**
     * @name:消息读取设置
     * @params:
     * @author:Ard
     * @date:2018-05-08
     */
    public function messageWarnSet(){
        //读取用户消息配置信息
        $messageWarnSetModel = D('MessageWarnSet');
        $get_data = $messageWarnSetModel->where(array('uid' => UID))->select();
        $m_type = '';
        $m_sum_type = '';
        $m_only_type = '';
        if($get_data){
            foreach($get_data as $key => $value){
                $m_type .= $value['m_type'].',';
                if($value['m_type'] == 3){
                    $m_sum_type = $value['m_other_type'];
                }
                if($value['m_type'] == 4){
                    $m_only_type = $value['m_other_type'];
                }
            }
        }
        $this->assign('m_type' , substr($m_type,0,strlen($m_type)-1));
        $this->assign('m_sum_type' , $m_sum_type);
        $this->assign('m_only_type' , $m_only_type);
        $this->display(T('Addons://MessageWarn@MessageWarn/message_warn_set'));
    }

    /**
     * @name:更新读取设置
     * @params:other_type string
     * @author:Ard
     * @date:2018-05-08
     */
    public function updateMessageWarnSet(){
        $m_type = I('post.m_type');
        $m_sum_tpye = I('post.m_sum_tpye');//汇总消息类型
        $m_only_tpye = I('post.m_only_tpye');//单挑提醒消息类型
        //循环m_type组合
        foreach($m_type as $key => $value){
            $params[] = array(
                'uid' => UID,
                'm_type' => $value,
                'm_other_type' => ($value == 3) ? implode(',' , $m_sum_tpye) : ($value == 4 ? implode(',' , $m_only_tpye) : ''),
            );
        }
        if(count($params) > 0){
            $messageWarnSetModel = D('MessageWarnSet');
            $messageWarnSetModel->where(array('uid' => UID))->delete();
            $messageWarnSetModel->addall($params);
        }
        $this->success('更新成功');
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
     * 字符串截取，支持中文和其他编码
     * @static
     * @access public
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     */
    function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
            if (false === $slice) {
                $slice = '';
            }
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        //字数不满不添加...
        $count = mb_strlen($str, 'utf-8');
        if ($count > $length) {
            return $suffix ? $slice . '...' : $slice;
        } else {
            return $slice;
        }
    }
}