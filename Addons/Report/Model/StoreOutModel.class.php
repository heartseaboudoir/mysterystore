<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-30
 * Time: 16:58
 * 门店出库验货单相关Excel
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreOutModel extends Model
{
    /***************
     * 生成出库验货单列表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createStoreOutListExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '序号');
        $objActSheet->setCellValue('B' . $rowNum, '出库验货单号');
        $objActSheet->setCellValue('C' . $rowNum, '申请日期');
        $objActSheet->setCellValue('D' . $rowNum, '申请种类');
        $objActSheet->setCellValue('E' . $rowNum, '申请数量');
        $objActSheet->setCellValue('F' . $rowNum, '申请人');
        $objActSheet->setCellValue('G' . $rowNum, '申请仓库/门店');
        $objActSheet->setCellValue("H" . $rowNum, '发货门店');
        $objActSheet->setCellValue("I" . $rowNum, '售价金额');
        $objActSheet->setCellValue("J" . $rowNum, '来源');
        $objActSheet->setCellValue("K" . $rowNum, '关联单号');
        $objActSheet->setCellValue("L" . $rowNum, '状态');
        $objActSheet->setCellValue("M" . $rowNum, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $source = "";
            if (!empty($val["store_name1"])) {
                $source = $val["store_name1"];
            } else {
                $source = $val["warehouse_name"];
            }
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['s_out_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['nickname']);
            $objActSheet->setCellValue('G' . $rowNum, $source);
            $objActSheet->setCellValue('H' . $rowNum, $val['store_name2']);
            $objActSheet->setCellValue('I' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('J' . $rowNum, $val["source"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["rel_orders"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["s_out_status_name"]);
            $objActSheet->setCellValue('M' . $rowNum, $val["remark"]);
            $index++;
            $rowNum++;
        }

        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(24);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(24);
        $objActSheet->getColumnDimension('K')->setWidth(24);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(24);


        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(array(
                'font' => array(
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(

            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:M' . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }


    /*****************
     * 生成出库验货单明细Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createStoreOutViewExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $EndC = "J";

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$EndC.'1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        //第一行>>>报表标题
        $maindata = $data["maindata"];
        $G4Title = "";
        $source = "";//来源  来源:0.仓库调拨,1.门店申请,2.退货报损
        $rel_sn = "";//相关关联单号
        $D4Title = "";
        $E4Content = "";

        if ($maindata["s_out_type"] == 0) {
            $source = "仓库调拨单";
            $G4Title = "关联单号";
            $D4Title = "申请仓库";
        } elseif ($maindata["s_out_type"] == 1) {
            $source = "门店申请单";
            $G4Title = "关联单号";
            $D4Title = "申请门店";
            $StoreRequestModel = M("StoreRequest");
            $rel_sn = $maindata["rel_orders"];
            $E4Content = $maindata["store_name1"];
        } elseif ($maindata["s_out_type"] == 2) {
            $source = "退货报损单";
            $G4Title = "退货报损单号";
            $D4Title = "申请门店";
        }
        if (!empty($rel_sn)) {
            $rel_sn = substr($rel_sn, 0, strlen($rel_sn) - 1);
        }
        $objActSheet->setCellValue('A2', '验货单号');
        $objActSheet->setCellValue('B2', $maindata['s_out_sn']);
        $objActSheet->setCellValue('D2', '创建日期');
        $objActSheet->setCellValue('E2', $maindata['ctime']);
        $objActSheet->setCellValue('G2', '商品种类');
        $objActSheet->setCellValue('H2', $maindata["g_type"]);
        $objActSheet->setCellValue('A3', '商品数量');
        $objActSheet->setCellValue('B3', $maindata["g_nums"]);
        $objActSheet->setCellValue('D3', '销售金额');
        $objActSheet->setCellValue('E3', $maindata["g_amounts"]);
        $objActSheet->setCellValue('A4', '来源');
        $objActSheet->setCellValue('B4', $source);
        $objActSheet->setCellValue('D4', $D4Title);
        $objActSheet->setCellValue('E4', $E4Content);
        $objActSheet->setCellValue('G4', $G4Title);
        $objActSheet->setCellValue('H4', $rel_sn);
        $objActSheet->setCellValue('A5', '管理员');
        $objActSheet->setCellValue('B5', $maindata["admin_nickname"]);
        $objActSheet->setCellValue('D5', "验货门店");
        $objActSheet->setCellValue('E5', $maindata["store_name2"]);
        $objActSheet->setCellValue('A6', '单据状态');
        $objActSheet->setCellValue('B6', $maindata["s_out_status_name"]);
        $objActSheet->setCellValue('A7', '备注');
        $objActSheet->setCellValue('B7', $maindata["remark"]);

        date_default_timezone_set("PRC");

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 9;
        $objActSheet->setCellValue('A' . $rowNum, '商品ID');
        $objActSheet->setCellValue('B' . $rowNum, '商品名称');
        $objActSheet->setCellValue('C' . $rowNum, '商品类别');
        $objActSheet->setCellValue('D' . $rowNum, '商品条码');
        $objActSheet->setCellValue('E' . $rowNum, '零售价');
        $objActSheet->setCellValue('F' . $rowNum, '库存数量');
        $objActSheet->setCellValue('G' . $rowNum, '申请数量');
        $objActSheet->setCellValue('H' . $rowNum, '有货数量');
        $objActSheet->setCellValue('I' . $rowNum, '缺货数量');
        $objActSheet->setCellValue('J' . $rowNum, '备注');

        $rowNum = 10;//当前行
        foreach ($data["list"] as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val['goods_id']);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['bar_code']);
            $objActSheet->setCellValue('E' . $rowNum, $val['sell_price']);
            $objActSheet->setCellValue('F' . $rowNum, $val['stock_num']);
            $objActSheet->setCellValue('G' . $rowNum, $val['g_num']);
            $objActSheet->setCellValue('H' . $rowNum, $val["in_num"]);
            $objActSheet->setCellValue('I' . $rowNum, $val["out_num"]);
            $objActSheet->setCellValue('J' . $rowNum, $val["remark"]);

            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(24);

        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(array(
                'font' => array(
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(

            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:'.$EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }

}