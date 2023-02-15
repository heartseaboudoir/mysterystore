<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-26
 * Time: 16:57
 */

namespace Addons\Report\Model;

use Think\Model;

class StockInoutOrdersModel extends Model
{
    public function createWarehouseInoutStockOrdersExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "K";
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num = 2;
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '单号');
        $objActSheet->setCellValue('C' . $num, '日期');
        $objActSheet->setCellValue('D' . $num, '商品种类');
        $objActSheet->setCellValue('E' . $num, '商品数量');
        $objActSheet->setCellValue('F' . $num, '创建人');
        $objActSheet->setCellValue('G' . $num, '入库仓库/门店');
        $objActSheet->setCellValue('H' . $num, '发货仓库/门店/供应商');
        $objActSheet->setCellValue("I" . $num, "来源");
        $objActSheet->setCellValue("J" . $num, "类型");
        $objActSheet->setCellValue("K" . $num, "备注");

        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $fahuo_source = !empty($val["fahuo_warehouse_name"]) ? $val["fahuo_warehouse_name"] : (!empty($val["fahuo_store_name"]) ? $val["fahuo_store_name"] : $val["fahuo_supply_name"]);
            $objActSheet->setCellValue("A" . $rowNum, $index);
            $objActSheet->setCellValue("B" . $rowNum, $val["sn"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["ptime"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["g_type"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["g_nums"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue("G" . $rowNum, !empty($val["ruku_warehouse_name"]) ? $val["ruku_warehouse_name"] : $val["ruku_store_name"]);
            $objActSheet->setCellValue("H" . $rowNum, $fahuo_source);
            $objActSheet->setCellValue("I" . $rowNum, $val["s_type_name"]);
            $objActSheet->setCellValue("J" . $rowNum, $val["type_name"]);
            $objActSheet->setCellValue("K" . $rowNum, $val["remark"]);
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
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(36);

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:' . $EndC . (string)(3))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }

    public function createStoreInoutStockOrdersExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "K";
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num = 2;
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '单号');
        $objActSheet->setCellValue('C' . $num, '日期');
        $objActSheet->setCellValue('D' . $num, '商品种类');
        $objActSheet->setCellValue('E' . $num, '商品数量');
        $objActSheet->setCellValue('F' . $num, '创建人');
        $objActSheet->setCellValue('G' . $num, '入库仓库/门店');
        $objActSheet->setCellValue('H' . $num, '发货仓库/门店/供应商');
        $objActSheet->setCellValue("I" . $num, "来源");
        $objActSheet->setCellValue("J" . $num, "类型");
        $objActSheet->setCellValue("K" . $num, "备注");

        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $fahuo_source = !empty($val["fahuo_warehouse_name"]) ? $val["fahuo_warehouse_name"] : (!empty($val["fahuo_store_name"]) ? $val["fahuo_store_name"] : $val["fahuo_supply_name"]);
            $objActSheet->setCellValue("A" . $rowNum, $index);
            $objActSheet->setCellValue("B" . $rowNum, $val["sn"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["ptime"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["g_type"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["g_nums"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue("G" . $rowNum, !empty($val["ruku_warehouse_name"]) ? $val["ruku_warehouse_name"] : $val["ruku_store_name"]);
            $objActSheet->setCellValue("H" . $rowNum, $fahuo_source);
            $objActSheet->setCellValue("I" . $rowNum, $val["s_type_name"]);
            $objActSheet->setCellValue("J" . $rowNum, $val["type_name"]);
            $objActSheet->setCellValue("K" . $rowNum, $val["remark"]);
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
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(36);

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:' . $EndC . (string)(3))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }

    public function createWarehouseGoodsInoutStockOrdersExcel($data, $title, $fname){
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
        $num = 2;
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '日期');
        $objActSheet->setCellValue('C' . $num, '商品ID');
        $objActSheet->setCellValue('D' . $num, '商品种类');
        $objActSheet->setCellValue('E' . $num, '商品属性');
        $objActSheet->setCellValue('F' . $num, '商品名称');
        $objActSheet->setCellValue('G' . $num, '商品数量');
        $objActSheet->setCellValue('H' . $num, '创建人');
        $objActSheet->setCellValue('I' . $num, '入库仓库/门店');
        $objActSheet->setCellValue('J' . $num, '发货仓库/门店/供应商');
        $objActSheet->setCellValue("K" . $num, "来源");
        $objActSheet->setCellValue("L" . $num, "类型");
        $objActSheet->setCellValue("M" . $num, "备注");

        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $fahuo_source = !empty($val["fahuo_warehouse_name"]) ? $val["fahuo_warehouse_name"] : (!empty($val["fahuo_store_name"]) ? $val["fahuo_store_name"] : $val["fahuo_supply_name"]);
            $objActSheet->setCellValue("A" . $rowNum, $index);
            $objActSheet->setCellValue("B" . $rowNum, $val["ptime"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["cate_name"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["value_name"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["goods_name"]);
            $objActSheet->setCellValue("G" . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue("H" . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue("I" . $rowNum, !empty($val["ruku_warehouse_name"]) ? $val["ruku_warehouse_name"] : $val["ruku_store_name"]);
            $objActSheet->setCellValue("J" . $rowNum, $fahuo_source);
            $objActSheet->setCellValue("K" . $rowNum, $val["s_type_name"]);
            $objActSheet->setCellValue("L" . $rowNum, $val["type_name"]);
            $objActSheet->setCellValue("M" . $rowNum, $val["remark"]);
            $index++;
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(18);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(48);
        $objActSheet->getColumnDimension('G')->setWidth(24);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(24);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(36);

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:' . $EndC . (string)(3))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }

    public function createStoreGoodsInoutStockOrdersExcel($data, $title, $fname)
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
        $num = 2;
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '日期');
        $objActSheet->setCellValue('C' . $num, '商品ID');
        $objActSheet->setCellValue('D' . $num, '种类名称');
        $objActSheet->setCellValue('E' . $num, '商品名称');
        $objActSheet->setCellValue('F' . $num, '商品数量');
        $objActSheet->setCellValue('G' . $num, '创建人');
        $objActSheet->setCellValue('H' . $num, '入库仓库/门店');
        $objActSheet->setCellValue('I' . $num, '发货仓库/门店/供应商');
        $objActSheet->setCellValue("J" . $num, "来源");
        $objActSheet->setCellValue("K" . $num, "类型");
        $objActSheet->setCellValue("L" . $num, "备注");

        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $fahuo_source = !empty($val["fahuo_warehouse_name"]) ? $val["fahuo_warehouse_name"] : (!empty($val["fahuo_store_name"]) ? $val["fahuo_store_name"] : $val["fahuo_supply_name"]);
            $objActSheet->setCellValue("A" . $rowNum, $index);
            $objActSheet->setCellValue("B" . $rowNum, $val["ptime"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["cate_name"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["goods_name"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue("G" . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue("H" . $rowNum, !empty($val["ruku_warehouse_name"]) ? $val["ruku_warehouse_name"] : $val["ruku_store_name"]);
            $objActSheet->setCellValue("I" . $rowNum, $fahuo_source);
            $objActSheet->setCellValue("J" . $rowNum, $val["s_type_name"]);
            $objActSheet->setCellValue("K" . $rowNum, $val["type_name"]);
            $objActSheet->setCellValue("L" . $rowNum, $val["remark"]);
            $index++;
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(18);
        $objActSheet->getColumnDimension('E')->setWidth(48);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(24);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(36);

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:' . $EndC . (string)(3))->applyFromArray($styleArray);//应用边框

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