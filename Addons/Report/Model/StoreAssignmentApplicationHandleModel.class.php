<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-04
 * Time: 16:50
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreAssignmentApplicationHandleModel extends Model
{

    /***************
     * 生成自动分单列表Excel
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createListExcelByAutoSort($data, $title, $fname)
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
        date_default_timezone_set("PRC");

        $rowNum = 2;

        foreach ($data as $key => $val) {
            $objActSheet->setCellValue('A' . $rowNum, '序号');
            $objActSheet->setCellValue('B' . $rowNum, '商品ID');
            $objActSheet->setCellValue('C' . $rowNum, '商品名称');
            $objActSheet->setCellValue('D' . $rowNum, '商品条码');
            $objActSheet->setCellValue('E' . $rowNum, '申请单号');
            $objActSheet->setCellValue('F' . $rowNum, '申请数量');
            $objActSheet->setCellValue('G' . $rowNum, '申请时间');
            $objActSheet->setCellValue("H" . $rowNum, '申请来源');
            $objActSheet->setCellValue("I" . $rowNum, '申请人');
            $objActSheet->setCellValue("J" . $rowNum, '库存');
            $objActSheet->setCellValue("K" . $rowNum, '备注');
            $index = 1;
            $rowNum++;
            foreach ($val as $k => $v) {
                $objActSheet->setCellValue('A' . $rowNum, $index);
                $objActSheet->setCellValue('B' . $rowNum, $v["goods_id"]);
                $objActSheet->setCellValue('C' . $rowNum, $v["goods_name"]);
                $objActSheet->setCellValue('D' . $rowNum, $v["bar_code"]);
                $objActSheet->setCellValue('E' . $rowNum, $v["s_t_s_sn"]);
                $objActSheet->setCellValue('F' . $rowNum, $v["g_num"]);
                $objActSheet->setCellValue('G' . $rowNum, $v["ctime"]);
                $objActSheet->setCellValue("H" . $rowNum, $v["store_name1"]);
                $objActSheet->setCellValue("I" . $rowNum, $v["admin_nickname"]);
                $objActSheet->setCellValue("J" . $rowNum, $v["stock_num"]);
                $objActSheet->setCellValue("K" . $rowNum, $v["remark"]);
                $index++;
                $rowNum++;
            }
            $rowNum++;
        }

        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(12);
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

    /***************
     * 生成所有单列表Excel
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createListExcelByAll($data, $title, $fname)
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
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '序号');
        $objActSheet->setCellValue('B' . $rowNum, '商品ID');
        $objActSheet->setCellValue('C' . $rowNum, '商品名称');
        $objActSheet->setCellValue('D' . $rowNum, '商品条码');
        $objActSheet->setCellValue('E' . $rowNum, '申请单号');
        $objActSheet->setCellValue('F' . $rowNum, '申请数量');
        $objActSheet->setCellValue('G' . $rowNum, '申请时间');
        $objActSheet->setCellValue("H" . $rowNum, '申请来源');
        $objActSheet->setCellValue("I" . $rowNum, '申请人');
        $objActSheet->setCellValue("J" . $rowNum, '库存');
        $objActSheet->setCellValue("K" . $rowNum, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue('C' . $rowNum, $val["goods_name"]);
            $objActSheet->setCellValue('D' . $rowNum, $val["bar_code"]);
            $objActSheet->setCellValue('E' . $rowNum, $val["s_t_s_sn"]);
            $objActSheet->setCellValue('F' . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue('G' . $rowNum, $val["ctime"]);
            $objActSheet->setCellValue("H" . $rowNum, $val["store_name1"]);
            $objActSheet->setCellValue("I" . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue("J" . $rowNum, $val["stock_num"]);
            $objActSheet->setCellValue("K" . $rowNum, $val["remark"]);
            $index++;
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(12);
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

}