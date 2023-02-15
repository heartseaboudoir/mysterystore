<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-11
 * Time: 11:01
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreInModel extends Model
{
    /***************
     * 生成入库验货单列表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createStoreInListExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "L";

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '序号');
        $objActSheet->setCellValue('B' . $rowNum, '验收单号');
        $objActSheet->setCellValue('C' . $rowNum, '创建日期');
        $objActSheet->setCellValue('D' . $rowNum, '商品种类');
        $objActSheet->setCellValue('E' . $rowNum, '商品数量');
        $objActSheet->setCellValue('F' . $rowNum, '创建人');
        $objActSheet->setCellValue('G' . $rowNum, '发货仓库/门店/供应商');
        $objActSheet->setCellValue("H" . $rowNum, '收货门店');
        $objActSheet->setCellValue("I" . $rowNum, '售价金额');
        $objActSheet->setCellValue("J" . $rowNum, '来源');
        $objActSheet->setCellValue("K" . $rowNum, '当前状态');
        $objActSheet->setCellValue("L" . $rowNum, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $source = "";
            if (!is_null($val["warehouse_name"]) && !empty($val["warehouse_name"])) {
                $source = $val["warehouse_name"];
            }
            if (!is_null($val["store_name1"]) && !empty($val["store_name1"])) {
                $source = $val["store_name1"];
            }
            if (!is_null($val["supply_name"]) && !empty($val["supply_name"])) {
                $source = $val["supply_name"];
            }
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['s_in_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['admin_nickname']);
            $objActSheet->setCellValue('G' . $rowNum, $source);
            $objActSheet->setCellValue('H' . $rowNum, $val['store_name2']);
            $objActSheet->setCellValue('I' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('J' . $rowNum, $val["s_in_type_name"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["s_in_status_name"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["remark"]);
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
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(24);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(24);


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
        $objPHPExcel->getActiveSheet()->getStyle('A2:' . $EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }

    /************************
     * 生成单个出库验货单Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createStoreInViewExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $maindata = $data["maindata"];
        $list = $data["list"];
        if ($maindata["s_in_type"] == 4) {
            $EndC = "J";
        } else {
            $EndC = "I";
        }

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        //第一行>>>报表标题
        date_default_timezone_set("PRC");

        $rowNum = 2;
        $objActSheet->setCellValue("A" . $rowNum, "验收单号");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["s_in_sn"]);
        $objActSheet->setCellValue("D" . $rowNum, "创建日期");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["ctime"]);

        $rowNum = 3;
        $objActSheet->setCellValue("A" . $rowNum, "商品种类");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["g_type"]);
        $objActSheet->setCellValue("D" . $rowNum, "商品数量");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["g_nums"]);
        $objActSheet->setCellValue("G" . $rowNum, "售价金额");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["g_amounts"]);

        $rowNum = 4;
        if (!is_null($maindata["warehouse_name"]) && !empty($maindata["warehouse_name"])) {
            $D4Title = "发货仓库";
            $E4Content = $maindata["warehouse_name"];
        }
        if (!is_null($maindata["store_name1"]) && !empty($maindata["store_name1"])) {
            $D4Title = "发货门店";
            $E4Content = $maindata["store_name1"];
        }
        if (!is_null($maindata["supply_name"]) && !empty($maindata["supply_name"])) {
            $D4Title = "供应商";
            $E4Content = $maindata["supply_name"];
        }
        $objActSheet->setCellValue("A" . $rowNum, "创建人");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["admin_nickname"]);
        $objActSheet->setCellValue("D" . $rowNum, $D4Title);
        $objActSheet->setCellValue("E" . $rowNum, $E4Content);
        $objActSheet->setCellValue("G" . $rowNum, "收货门店");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["store_name2"]);

        $rowNum = 5;
        $objActSheet->setCellValue("A" . $rowNum, "单据状态");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["s_in_status_name"]);

        $rowNum = 6;
        $objActSheet->setCellValue("A" . $rowNum, "备注");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["remark"]);

        $rowNum = 7;
        $objActSheet->setCellValue('A' . $rowNum, '商品ID');
        $objActSheet->setCellValue('B' . $rowNum, '商品名称');
        $objActSheet->setCellValue('C' . $rowNum, '商品类别');
        $objActSheet->setCellValue('D' . $rowNum, '商品属性');
        $objActSheet->setCellValue('E' . $rowNum, '商品条码');
        $objActSheet->setCellValue('F' . $rowNum, '零售价');
        $objActSheet->setCellValue('G' . $rowNum, '库存数量');
        $objActSheet->setCellValue('H' . $rowNum, '申请数量');
        $objActSheet->setCellValue('I' . $rowNum, '验货数量');
        $objActSheet->setCellValue('J' . $rowNum, '退货数量');
        if ($maindata["s_in_type"] == 4) {
            $objActSheet->setCellValue("K" . $rowNum, "过期日期");
            $objActSheet->setCellValue("L" . $rowNum, "备注");
        } else {
            $objActSheet->setCellValue("K" . $rowNum, "备注");
        }

        $rowNum = 8;//当前行
        foreach ($data["list"] as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val['goods_id']);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['value_name']);
            $objActSheet->setCellValue('E' . $rowNum, $val['bar_code']);
            $objActSheet->setCellValue('F' . $rowNum, $val['sell_price']);
            $objActSheet->setCellValue('G' . $rowNum, $val['stock_num']);
            $objActSheet->setCellValue('H' . $rowNum, $val['g_num']);
            $objActSheet->setCellValue('I' . $rowNum, $val["in_num"]);
            $objActSheet->setCellValue('J' . $rowNum, $val["out_num"]);
            if ($maindata["s_in_type"] == 4) {
                $objActSheet->setCellValue("K" . $rowNum, $val["enddate"]);
                $objActSheet->setCellValue("L" . $rowNum, $val["remark"]);
            } else {
                $objActSheet->setCellValue("K" . $rowNum, $val["remark"]);
            }
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        if ($maindata["s_in_type"] == 4) {
            $objActSheet->getColumnDimension('K')->setWidth(12);
            $objActSheet->getColumnDimension('L')->setWidth(24);
        } else {
            $objActSheet->getColumnDimension('K')->setWidth(24);
        }

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:' . $EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

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