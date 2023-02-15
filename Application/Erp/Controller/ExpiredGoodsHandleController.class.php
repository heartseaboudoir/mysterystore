<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-19
 * Time: 10:20
 * 商品过期处理
 */

namespace Erp\Controller;

use Think\Controller;

class ExpiredGoodsHandleController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
    }

    /***********************
     * 定期检测商品过期
     * 逻辑：直接查找批次表【hii_warehouse_inout】
     */
    public function checkExpiredGoodsThread()
    {
        ini_set('max_execution_time', '0');
        \Think\Log::record("检测商品过期 Start>>>");
        $t = time();//指定时间戳
        $today = date("Y-m-d", $t);
        $days = 3;//提前三天提醒
        $endtime = date('Y-m-d H:i:s', $t + $days * 24 * 60 * 60);//指定时间戳+1天 2017-01-10 21:10:16

        $WarehouseInoutModel = M("WarehouseInout");
        $MessageWarnModel = M("MessageWarn");

        $sql = "select WI.goods_id,G.title as goods_name,WI.store_id,WI.warehouse_id,WI.endtime, ";
        $sql .= "W.w_name as warehouse_name,S.title as store_name ";
        $sql .= "from hii_warehouse_inout WI ";
        $sql .= "left join hii_goods G ";
        $sql .= "left join hii_warehouse W on W.w_id=WI.warehouse_id ";
        $sql .= "left join hii_store S on S.id=WI.store_id ";
        $sql .= "where WI.num>0 and WI.endtime <= {$endtime} ";

        $ctime = time();
        $datas = $WarehouseInoutModel->query($sql);
        foreach ($datas as $key => $val) {

            $enddate = date("Y-m-d", $val["endtime"]);
            $leftDays = $this->count_days($enddate, $today);

            $MessageWarnEntity = array();
            $MessageWarnEntity["m_status"] = 0;
            $MessageWarnEntity["m_type"] = 0;
            $MessageWarnEntity["ctime"] = $ctime;
            if (is_null($val["warehouse_id"]) || empty($val["warehouse_id"])) {
                $MessageWarnEntity["to_store_id"] = $val["store_id"];
                $MessageWarnEntity["message_title"] = "商品【" . $val["goods_name"] . "】将要过期，门店：" . $val["store_name"];
            } else {
                $MessageWarnEntity["to_warehouse_id"] = $val["warehouse_id"];
                $MessageWarnEntity["message_title"] = "商品【" . $val["goods_name"] . "】将要过期，仓库：" . $val["warehouse_name"];
            }
            $MessageWarnEntity["message_content"] = "商品【" . $val["goods_name"] . "】将要过期，剩余天数：" . $leftDays . " 天";
            $MessageWarnModel->add($MessageWarnEntity);
        }
        \Think\Log::record("检测商品过期 End>>>");
    }


    /*************************
     * 计算日期差
     * @param $a 结束日期
     * @param $b 开始日期
     * @return float
     *
     * Demo: $date1 = strtotime(time());
     *       $date2 = strtotime('10/11/2008');
     *       $result = count_days($date1, $date2);
     */
    function count_days($a, $b)
    {
        $a_dt = getdate($a);
        $b_dt = getdate($b);
        $a_new = mktime(12, 0, 0, $a_dt['mon'], $a_dt['mday'], $a_dt['year']);
        $b_new = mktime(12, 0, 0, $b_dt['mon'], $b_dt['mday'], $b_dt['year']);
        return round(abs($a_new - $b_new) / 86400);
    }

}