<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-15
 * Time: 16:38
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreOtherOutModel extends Model
{
    /***************
     * 生成退货单列表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createIndexListExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "K";
        date_default_timezone_set("PRC");
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
        $objActSheet->setCellValue('B' . $rowNum, '退货单号');
        $objActSheet->setCellValue('C' . $rowNum, '创建日期');
        $objActSheet->setCellValue('D' . $rowNum, '商品种类');
        $objActSheet->setCellValue('E' . $rowNum, '商品数量');
        $objActSheet->setCellValue('F' . $rowNum, '售价金额');
        $objActSheet->setCellValue('G' . $rowNum, '创建人');
        $objActSheet->setCellValue('H' . $rowNum, '发货仓库/门店');
        $objActSheet->setCellValue("I" . $rowNum, '退货仓库');
        $objActSheet->setCellValue("J" . $rowNum, '状态');
        $objActSheet->setCellValue("K" . $rowNum, '备注');

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $warehouse_or_store = "";
            if (is_null($val["warehouse_name"]) || empty($val["warehouse_name"])) {
                $warehouse_or_store = $val["store_name1"];
            } else {
                $warehouse_or_store = $val["warehouse_name"];
            }
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['s_o_out_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('G' . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue('H' . $rowNum, $warehouse_or_store);
            $objActSheet->setCellValue('I' . $rowNum, $val['store_name2']);
            $objActSheet->setCellValue('J' . $rowNum, $val["s_o_out_status_name"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["remark"]);
            $index++;
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

    /******************
     * 生成退货单详细信息Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createViewExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "K";
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        $maindata = $data["maindata"];
        $list = $data["list"];

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue("A" . $rowNum, "退货单号");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["s_o_out_sn"]);
        $objActSheet->setCellValue("D" . $rowNum, "创建日期");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["ctime"]);
        $objActSheet->setCellValue("G" . $rowNum, "商品种类");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["g_type"]);
        $objActSheet->setCellValue("J" . $rowNum, "商品数量");
        $objActSheet->setCellValue("K" . $rowNum, $maindata["g_nums"]);

        $rowNum = 3;
        $objActSheet->setCellValue("A" . $rowNum, "退货金额");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["out_amounts"]);
        $objActSheet->setCellValue("D" . $rowNum, "申请人");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["admin_nickname"]);
        $objActSheet->setCellValue("G" . $rowNum, "退货门店");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["store_name2"]);
        if (!is_null($maindata["warehouse_name"]) && !empty($maindata["warehouse_name"])) {
            $objActSheet->setCellValue("J" . $rowNum, "发货仓库");
            $objActSheet->setCellValue("K" . $rowNum, $maindata["warehouse_name"]);
        } else {
            $objActSheet->setCellValue("J" . $rowNum, "发货门店");
            $objActSheet->setCellValue("K" . $rowNum, $maindata["store_name1"]);
        }

        $rowNum = 4;
        $objActSheet->setCellValue("A" . $rowNum, "备注");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["remark"]);

        $rowNum = 5;//当前行
        $objActSheet->setCellValue("A" . $rowNum, "商品ID");
        $objActSheet->setCellValue("B" . $rowNum, "商品名称");
        $objActSheet->setCellValue("C" . $rowNum, "商品类别");
        $objActSheet->setCellValue("D" . $rowNum, "商品属性");
        $objActSheet->setCellValue("E" . $rowNum, "商品条码");
        $objActSheet->setCellValue("F" . $rowNum, "零售价");
        $objActSheet->setCellValue("G" . $rowNum, "退货价");
        $objActSheet->setCellValue("H" . $rowNum, "退货数量");
        $objActSheet->setCellValue("I" . $rowNum, "备注");

        $rowNum = 6;
        foreach ($list as $key => $val) {
            $objActSheet->setCellValue("A" . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue("B" . $rowNum, $val["goods_name"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["cate_name"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["value_name"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["bar_code"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["sell_price"]);
            $objActSheet->setCellValue("G" . $rowNum, $val["g_price"]);
            $objActSheet->setCellValue("H" . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue("I" . $rowNum, $val["remark"]);
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
     * 生成被退货单列表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createIndex2ListExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "I";
        date_default_timezone_set("PRC");
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
        $objActSheet->setCellValue('B' . $rowNum, '退货单号');
        $objActSheet->setCellValue('C' . $rowNum, '创建日期');
        $objActSheet->setCellValue('D' . $rowNum, '商品种类');
        $objActSheet->setCellValue('E' . $rowNum, '商品数量');
        $objActSheet->setCellValue('F' . $rowNum, '售价金额');
        $objActSheet->setCellValue('G' . $rowNum, '创建人');
        $objActSheet->setCellValue('H' . $rowNum, '退货门店/仓库');
        $objActSheet->setCellValue("I" . $rowNum, '备注');

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['s_o_out_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('G' . $rowNum, $val["admin_nickname"]);
            $objActSheet->setCellValue('H' . $rowNum, $val["store_name2"] .'/' .$val["warehouse_name"]);
            $objActSheet->setCellValue('I' . $rowNum, $val['remark']);
            $index++;
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

    /******************
     * 生成被退货单详细信息Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createView2Excel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "K";
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        $maindata = $data["maindata"];
        $list = $data["list"];

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue("A" . $rowNum, "退货单号");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["s_o_out_sn"]);
        $objActSheet->setCellValue("D" . $rowNum, "创建日期");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["ctime"]);
        $objActSheet->setCellValue("G" . $rowNum, "商品种类");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["g_type"]);
        $objActSheet->setCellValue("J" . $rowNum, "商品数量");
        $objActSheet->setCellValue("K" . $rowNum, $maindata["g_nums"]);

        $rowNum = 3;
        $objActSheet->setCellValue("A" . $rowNum, "售价金额");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["g_amounts"]);
        $objActSheet->setCellValue("D" . $rowNum, "申请人");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["admin_nickname"]);
        $objActSheet->setCellValue("G" . $rowNum, "退货门店/仓库");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["store_name2"] .'/' .$maindata["warehouse_name"]);

        $rowNum = 4;
        $objActSheet->setCellValue("A" . $rowNum, "备注");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["remark"]);

        $rowNum = 5;//当前行
        $objActSheet->setCellValue("A" . $rowNum, "商品ID");
        $objActSheet->setCellValue("B" . $rowNum, "商品名称");
        $objActSheet->setCellValue("C" . $rowNum, "商品类别");
        $objActSheet->setCellValue("D" . $rowNum, "商品属性");
        $objActSheet->setCellValue("E" . $rowNum, "商品条码");
        $objActSheet->setCellValue("F" . $rowNum, "零售价");
        //$objActSheet->setCellValue("F" . $rowNum, "退货价");
        $objActSheet->setCellValue("G" . $rowNum, "退货数量");
        $objActSheet->setCellValue("H" . $rowNum, "备注");

        $rowNum = 6;
        foreach ($list as $key => $val) {
            $objActSheet->setCellValue("A" . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue("B" . $rowNum, $val["goods_name"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["cate_name"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["value_name"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["bar_code"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["sell_price"]);
            //$objActSheet->setCellValue("F" . $rowNum, $val["g_price"]);
            $objActSheet->setCellValue("G" . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue("H" . $rowNum, $val["remark"]);
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