<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-12
 * Time: 17:02
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreStockModel extends Model
{

    /***********
     * 生成门店商品种类库存Excel文档
     * @param $data 数据
     * @param $title Excel标题
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
        $EndC = "E";
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
        $objActSheet->setCellValue('B' . $num, '商品种类');
        $objActSheet->setCellValue('C' . $num, '门店名称');
        $objActSheet->setCellValue('D' . $num, '库存数量');
        $objActSheet->setCellValue('E' . $num, '售价金额');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $objActSheet->setCellValue("A" . $rowNum, $index);
            $objActSheet->setCellValue("B" . $rowNum, $val["cate_name"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["store_name"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["stock_num"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["g_amounts"]);
            $index++;
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);

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

    /***********
     * 生成某个商品种类库存Excel文档
     * @param $data 数据
     * @param $title Excel标题
     * @param $fname 文件名
     */
    public function createIndexGoodsListExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "H";
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
        $objActSheet->setCellValue('B' . $num, '商品ID');
        $objActSheet->setCellValue('C' . $num, '商品种类');
        $objActSheet->setCellValue('D' . $num, '商品名称');
        $objActSheet->setCellValue('E' . $num, '商品条码');
        $objActSheet->setCellValue('F' . $num, "门店名称");
        $objActSheet->setCellValue("G" . $num, "库存数量");
        $objActSheet->setCellValue("H" . $num, "售价金额");

        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $objActSheet->setCellValue("A" . $rowNum, $index);
            $objActSheet->setCellValue("B" . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["cate_name"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["goods_name"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["bar_code"]);
            $objActSheet->setCellValue("F" . $rowNum, $val["store_name"]);
            $objActSheet->setCellValue("G" . $rowNum, $val["stock_num"]);
            $objActSheet->setCellValue("H" . $rowNum, $val["g_amounts"]);
            $index++;
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);

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

    /*****************
     * 生成单个商品入库记录Excel文档
     * @param $data 数据
     * @param $title Excel标题
     * @param $fname 文件名
     */
    public function createGoodsInStockHistoryExcel($data, $title, $fname)
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

        $maindata = $data["maindata"];
        $list = $data["list"];

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue("A" . $rowNum, "商品ID");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["goods_id"]);
        $objActSheet->setCellValue("D" . $rowNum, "商品种类");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["cate_name"]);
        $objActSheet->setCellValue("G" . $rowNum, "商品名称");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["goods_name"]);
        $objActSheet->setCellValue("J" . $rowNum, "商品条码");
        $objActSheet->setCellValue("K" . $rowNum, $maindata["bar_code"]);

        $rowNum = 3;
        $objActSheet->setCellValue("A" . $rowNum, "门店库存");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["num"]);
        $objActSheet->setCellValue("D" . $rowNum, "平均入库价");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["stock_price"]);
        $objActSheet->setCellValue("G" . $rowNum, "库存金额");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["stock_amounts"]);

        $rowNum = 4;
        $objActSheet->setCellValue("A" . $rowNum, "零售价");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["sell_price"]);
        $objActSheet->setCellValue("D" . $rowNum, "零售总额");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["sell_amounts"]);

        $rowNum = 5;//当前行
        $objActSheet->setCellValue("A" . $rowNum, "入库编号");
        $objActSheet->setCellValue("B" . $rowNum, "入库时间");
        $objActSheet->setCellValue("C" . $rowNum, "入库数量");
        //$objActSheet->setCellValue("D" . $rowNum, "入库价");
        //$objActSheet->setCellValue("E" . $rowNum, "入库金额");
        $objActSheet->setCellValue("D" . $rowNum, "系统售价");
        $objActSheet->setCellValue("E" . $rowNum, "售价金额");

        $rowNum = 6;
        foreach ($list as $key => $val) {
            $objActSheet->setCellValue("A" . $rowNum, $val["s_in_s_sn"]);
            $objActSheet->setCellValue("B" . $rowNum, $val["ptime"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["g_num"]);
            //$objActSheet->setCellValue("D" . $rowNum, $val["inprice"]);
            //$objActSheet->setCellValue("E" . $rowNum, $val["in_amounts"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["sell_price"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["g_amounts"]);
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(24);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(24);
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

    /*****************
     * 生成单个商品出库记录Excel文档
     * @param $data 数据
     * @param $title Excel标题
     * @param $fname 文件名
     */
    public function createGoodsOutStockHistoryExcel($data, $title, $fname)
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

        $maindata = $data["maindata"];
        $list = $data["list"];

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue("A" . $rowNum, "商品ID");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["goods_id"]);
        $objActSheet->setCellValue("D" . $rowNum, "商品种类");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["cate_name"]);
        $objActSheet->setCellValue("G" . $rowNum, "商品名称");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["goods_name"]);
        $objActSheet->setCellValue("J" . $rowNum, "商品条码");
        $objActSheet->setCellValue("K" . $rowNum, $maindata["bar_code"]);

        $rowNum = 3;
        $objActSheet->setCellValue("A" . $rowNum, "门店库存");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["num"]);
        $objActSheet->setCellValue("D" . $rowNum, "平均入库价");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["stock_price"]);
        $objActSheet->setCellValue("G" . $rowNum, "库存金额");
        $objActSheet->setCellValue("H" . $rowNum, $maindata["stock_amounts"]);

        $rowNum = 4;
        $objActSheet->setCellValue("A" . $rowNum, "零售价");
        $objActSheet->setCellValue("B" . $rowNum, $maindata["sell_price"]);
        $objActSheet->setCellValue("D" . $rowNum, "零售总额");
        $objActSheet->setCellValue("E" . $rowNum, $maindata["sell_amounts"]);

        $rowNum = 5;//当前行
        $objActSheet->setCellValue("A" . $rowNum, "出库编号");
        $objActSheet->setCellValue("B" . $rowNum, "出库时间");
        $objActSheet->setCellValue("C" . $rowNum, "出库数量");
        //$objActSheet->setCellValue("D" . $rowNum, "出库价");
        //$objActSheet->setCellValue("E" . $rowNum, "出库金额");
        $objActSheet->setCellValue("D" . $rowNum, "系统售价");
        $objActSheet->setCellValue("E" . $rowNum, "售价金额");

        $rowNum = 6;
        foreach ($list as $key => $val) {
            $objActSheet->setCellValue("A" . $rowNum, $val["s_out_s_sn"]);
            $objActSheet->setCellValue("B" . $rowNum, $val["ptime"]);
            $objActSheet->setCellValue("C" . $rowNum, $val["out_num"]);
            //$objActSheet->setCellValue("D" . $rowNum, $val["g_price"]);
            //$objActSheet->setCellValue("E" . $rowNum, $val["out_amounts"]);
            $objActSheet->setCellValue("D" . $rowNum, $val["sell_price"]);
            $objActSheet->setCellValue("E" . $rowNum, $val["g_amounts"]);
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(24);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(24);
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