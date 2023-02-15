<?php
/**
 * Created by PhpStorm.
 * User: Ard
 * Date: 2018-04-23
 * 消息通用模块
 */

namespace Erp\Model;

use Think\Model;

class MessageWarnModel extends Model
{
    //来源:0.商品过期提醒,1.内部消息,2.其它,3.汇总信息,4.单条信息
    const TYPE_EXPIRED = 0;
    const TYPE_INSIDE_MESSAGE = 1;
    const TYPE_OTHER = 2;
    const TYPE_STATISTICS = 3;
    const TYPE_ONE_MESSAGE = 4;
    //信息其他类型：1.门店汇总2.仓库汇总3.采购汇总4.门店入库 5.门店出库 6.仓库入库 7.仓库出库 8.采购申请  9.新增采购退货提醒 
	//10.仓库调拨 11.仓库返仓拒绝 12.门店调拨申请 13.门店退货申请 14.门店发货申请 15.门店调拨申请被拒 16.门店返仓申请 17.门店返仓申请被拒
    const STORE_STATISTICS = 1;
    const STOCK_STATISTICS = 2;
    const PURCHASE_STATISTICS = 3;
    const STORE_IN = 4;
    const STORE_OUT = 5;
    const STOCK_IN = 6;
    const STOCK_OUT = 7;
    const STORE_IN_STOCK = 8;
    const PURCHASE_RETURN = 9;
    const STOCK_ALLOT = 10;
    const STOCK_RETURN_REFUSE = 11;
    const STORE_ALLOT = 12;
    const STORE_RETURN = 13;
    const STOCK_TO_STORE = 14;
    const STORE_ALLOT_REJECT = 15;
    const STORE_RETURN_STOCK = 16;
    const STORE_RETURN_REJECT = 17;
    //读取状态  0.新增【未读】,1.已读,2.已处理,3.已过期
    const IS_NEW = 0;
    const IS_READED = 1;
    const IS_DEAL = 2;
    const IS_PAST = 3;



    /**
     * name: 消息写入
     * params: $from_admin_id int 发件人
     * params: $to_warehouse_id int 仓库id
     * params: $to_store_id int 门店id
     * params: $shequ_id int 区域id
     * params: $data array 具体内容
     * params: $type int 类型
     * params: $is_push boolean 是否推送
     * params: $type int 写入类型 4.门店入库 5.门店出库 6.仓库入库 7.仓库出库 8.采购申请
     * author: Ard
     * date: 2018-04-23
     */
    public function pushMessageWarn($from_admin_id  , $to_warehouse_id = 0  , $to_store_id = 0 , $shequ_id = 0 , $data ,$type = 1 , $is_push = false){

        if(empty($from_admin_id) || empty($data) || (empty($to_warehouse_id) && empty($to_store_id) && empty($shequ_id))){
            return array('status' => 1001, 'msg' => "参数不完整");
        }
        $MessageWarnModel = M("MessageWarn");
        /*
         * data 说明
         * 单号:sn
         * 创建日期:ctime
         * 来源:根据类型读取
         */
        //根据type 来决定标题类型

        $message_title =  array(
            '4' => '新增门店入库单提醒',
            '5' => '新增门店出库单提醒',
            '6' => '新增仓库入库单提醒',
            '7' => '新增仓库出库单提醒',
            '8' => '新增采购申请提醒',
            '9' => '新增采购退货提醒',
            '10' => '新增仓库调拨退货提醒',
            '11' => '新增返仓拒绝提醒',
            '12' => '新增门店调拨申请提醒',
            '13' => '新增门店退货申请提醒',
            '14' => '新增门店发货申请提醒',
            '15' => '新增门店调拨申请被拒提醒',
            '16' => '新增门店返仓申请提醒',
            '17' => '新增门店返仓申请被拒提醒',
        );
        //根据门店ID或者仓库ID 获取对应的管理员 写入数据
        //发往仓库
        if(!empty($to_warehouse_id)){
            $warehouseMemberModel = M('MemberWarehouse');
            $getWarehouseAdmin = $warehouseMemberModel->alias('WM')
                ->join('hii_warehouse AS W ON WM.warehouse_id = W.w_id' , 'LEFT')
                ->field('WM.uid , W.w_name')->where(array('WM.warehouse_id'=>$to_warehouse_id))->select();
            if($getWarehouseAdmin){
                foreach($getWarehouseAdmin as $key => $value){
                    $params[] = array(
                        'm_type' => self::TYPE_ONE_MESSAGE,
                        'm_other_type' => $type,
                        'from_admin_id' => $from_admin_id,
                        'to_admin_id' => $value['uid'],
                        'to_warehouse_id' => $to_warehouse_id,
                        'to_store_id' => $to_store_id,
                        'ctime' => time(),
                        'message_title' => $message_title[$type] ? $message_title[$type] : '系统消息',
                        'message_content' => json_encode($data)
                    );

                }
            }
        }
        //发往门店
        if(!empty($to_store_id)){
            $memberStoreModel = M('MemberStore');
            $getStoreAdmin = $memberStoreModel->alias('MS')
                ->join(' hii_store AS S on MS.store_id = S.id' , 'LEFT')
                ->field('MS.uid , S.title')->where(array('MS.store_id'=>$to_store_id , 'MS.type' => 1))->select();
            if($getStoreAdmin){
                foreach($getStoreAdmin as $key => $value){
                    $params[] = array(
                        'm_type' => self::TYPE_ONE_MESSAGE,
                        'm_other_type' => $type,
                        'from_admin_id' => $from_admin_id,
                        'to_admin_id' => $value['uid'],
                        'to_warehouse_id' => $to_warehouse_id,
                        'to_store_id' => $to_store_id,
                        'ctime' => time(),
                        'message_title' => $message_title[$type] ? $message_title[$type] : '系统消息',
                        'message_content' => json_encode($data)
                    );
                }
            }
        }
        //发往采购(区域)
        if(!empty($shequ_id)){
            $memberStoreModel = M('MemberStore');
            $shequAdmin = $memberStoreModel->alias('MS')
                ->join(' hii_shequ AS S on MS.store_id = S.id' , 'LEFT')
                ->field('MS.uid , S.title')->where('MS.group_id=15 and MS.type=2 and MS.store_id=' .$shequ_id)->select();
            if($shequAdmin){
                foreach($shequAdmin as $key => $value){
                    $params[] = array(
                        'm_type' => self::TYPE_ONE_MESSAGE,
                        'm_other_type' => $type,
                        'from_admin_id' => $from_admin_id,
                        'to_admin_id' => $value['uid'],
                        'to_warehouse_id' => $to_warehouse_id,
                        'to_store_id' => $to_store_id,
                        'ctime' => time(),
                        'message_title' => $message_title[$type] ? $message_title[$type] : '系统消息',
                        'message_content' => json_encode($data)
                    );
                }
            }
        }
        $result = $MessageWarnModel->addAll($params);
        if($result){
            return array('status' => 0, 'msg' => "插入成功");
        }else{
            return array('status' => -99, 'msg' => "插入失败");
        }
    }

}