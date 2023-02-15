<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-26
 * Time: 15:10
 * 门店寄售导出Excel相关接口
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreConsignModel extends Model
{

    /***************
     * 生成寄售单列表Excel文件
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
        $objActSheet->setCellValue('B' . $rowNum, '寄售单号');
        $objActSheet->setCellValue('C' . $rowNum, '创建日期');
        $objActSheet->setCellValue('D' . $rowNum, '商品种类');
        $objActSheet->setCellValue('E' . $rowNum, '商品数量');
        $objActSheet->setCellValue('F' . $rowNum, '创建人');
        $objActSheet->setCellValue('G' . $rowNum, '寄售门店');
        $objActSheet->setCellValue("H" . $rowNum, '供应商');
        $objActSheet->setCellValue("I" . $rowNum, '售价金额');
        $objActSheet->setCellValue("J" . $rowNum, '寄售金额');
        $objActSheet->setCellValue("K" . $rowNum, '申请结果');
        $objActSheet->setCellValue("L" . $rowNum, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['c_in_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['admin_nickname']);
            $objActSheet->setCellValue('G' . $rowNum, $val["store_name"]);
            $objActSheet->setCellValue('H' . $rowNum, $val['supply_name']);
            $objActSheet->setCellValue('I' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('J' . $rowNum, $val["b_amounts"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["c_in_status_name"]);
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

    /***************
     * 生成寄售单查看Excel文件
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
        $EndC = "N";

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
        $objActSheet->setCellValue('A' . $rowNum, '寄售入库单号');
        $objActSheet->setCellValue('B' . $rowNum, $maindata["c_in_sn"]);
        $objActSheet->setCellValue('D' . $rowNum, "创建日期");
        $objActSheet->setCellValue('E' . $rowNum, $maindata["ctime"]);
        $objActSheet->setCellValue('G' . $rowNum, "商品种类");
        $objActSheet->setCellValue('H' . $rowNum, $maindata["g_type"]);
        $objActSheet->setCellValue('J' . $rowNum, "商品数量");
        $objActSheet->setCellValue('K' . $rowNum, $maindata["g_nums"]);

        $rowNum = 3;
        $objActSheet->setCellValue('A' . $rowNum, "售价金额");
        $objActSheet->setCellValue('B' . $rowNum, $maindata["g_amounts"]);
        $objActSheet->setCellValue('D' . $rowNum, "寄售金额");
        $objActSheet->setCellValue('E' . $rowNum, $maindata["b_amounts"]);
        $objActSheet->setCellValue('G' . $rowNum, "管理员");
        $objActSheet->setCellValue('H' . $rowNum, $maindata["admin_nickname"]);
        $objActSheet->setCellValue('J' . $rowNum, "寄售门店");
        $objActSheet->setCellValue('K' . $rowNum, $maindata["store_name"]);
        $objActSheet->setCellValue('M' . $rowNum, "供应商");
        $objActSheet->setCellValue('N' . $rowNum, $maindata["supply_name"]);

        $rowNum = 4;
        $objActSheet->setCellValue('A' . $rowNum, "单据状态");
        $objActSheet->setCellValue('B' . $rowNum, $maindata["c_in_status_name"]);
        $objActSheet->setCellValue('D' . $rowNum, "备注");
        $objActSheet->setCellValue('E' . $rowNum, $maindata["remark"]);

        $rowNum = 5;
        $objActSheet->setCellValue('A' . $rowNum, "商品ID");
        $objActSheet->setCellValue('B' . $rowNum, "商品名称");
        $objActSheet->setCellValue('C' . $rowNum, "商品类别");
        $objActSheet->setCellValue('D' . $rowNum, "商品属性");
        $objActSheet->setCellValue('E' . $rowNum, "商品条码");
        $objActSheet->setCellValue('F' . $rowNum, "库存数量");
        $objActSheet->setCellValue('G' . $rowNum, "系统售价");
        $objActSheet->setCellValue('H' . $rowNum, "历史价格");
        $objActSheet->setCellValue('I' . $rowNum, "箱规");
        $objActSheet->setCellValue('J' . $rowNum, "采购箱数");
        $objActSheet->setCellValue('K' . $rowNum, "每箱价格");
        $objActSheet->setCellValue('L' . $rowNum, "采购数量");
        $objActSheet->setCellValue('M' . $rowNum, "采购价格");
        $objActSheet->setCellValue('N' . $rowNum, "备注");

        $rowNum = 6;//当前行
        foreach ($list as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['value_name']);
            $objActSheet->setCellValue('E' . $rowNum, $val['bar_code']);
            $objActSheet->setCellValue('F' . $rowNum, $val['stock_num']);
            $objActSheet->setCellValue('G' . $rowNum, $val['sell_price']);
            $objActSheet->setCellValue('H' . $rowNum, $val["last_price"]);
            $objActSheet->setCellValue('I' . $rowNum, $val['b_n_num']);
            $objActSheet->setCellValue('J' . $rowNum, $val['b_num']);
            $objActSheet->setCellValue('K' . $rowNum, $val["b_price"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue('M' . $rowNum, $val["g_price"]);
            $objActSheet->setCellValue('N' . $rowNum, $val["remark"]);
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
        $objActSheet->getColumnDimension('M')->setWidth(24);
        $objActSheet->getColumnDimension('N')->setWidth(24);


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
     * 生成寄售单列表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createOutIndexListExcel($data, $title, $fname)
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
        $objActSheet->setCellValue('B' . $rowNum, '寄售单号');
        $objActSheet->setCellValue('C' . $rowNum, '创建日期');
        $objActSheet->setCellValue('D' . $rowNum, '商品种类');
        $objActSheet->setCellValue('E' . $rowNum, '商品数量');
        $objActSheet->setCellValue('F' . $rowNum, '创建人');
        $objActSheet->setCellValue('G' . $rowNum, '寄售门店');
        $objActSheet->setCellValue("H" . $rowNum, '供应商');
        $objActSheet->setCellValue("I" . $rowNum, '售价金额');
        $objActSheet->setCellValue("J" . $rowNum, '寄售金额');
        $objActSheet->setCellValue("K" . $rowNum, '申请结果');
        $objActSheet->setCellValue("L" . $rowNum, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['c_out_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['admin_nickname']);
            $objActSheet->setCellValue('G' . $rowNum, $val["store_name"]);
            $objActSheet->setCellValue('H' . $rowNum, $val['supply_name']);
            $objActSheet->setCellValue('I' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('J' . $rowNum, $val["b_amounts"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["c_out_status_name"]);
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

    /***************
     * 生成寄售单查看Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createOutViewExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "N";

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
        $objActSheet->setCellValue('A' . $rowNum, '寄售出库单号');
        $objActSheet->setCellValue('B' . $rowNum, $maindata["c_out_sn"]);
        $objActSheet->setCellValue('D' . $rowNum, "创建日期");
        $objActSheet->setCellValue('E' . $rowNum, $maindata["ctime"]);
        $objActSheet->setCellValue('G' . $rowNum, "商品种类");
        $objActSheet->setCellValue('H' . $rowNum, $maindata["g_type"]);
        $objActSheet->setCellValue('J' . $rowNum, "商品数量");
        $objActSheet->setCellValue('K' . $rowNum, $maindata["g_nums"]);

        $rowNum = 3;
        $objActSheet->setCellValue('A' . $rowNum, "售价金额");
        $objActSheet->setCellValue('B' . $rowNum, $maindata["g_amounts"]);
        $objActSheet->setCellValue('D' . $rowNum, "寄售金额");
        $objActSheet->setCellValue('E' . $rowNum, $maindata["b_amounts"]);
        $objActSheet->setCellValue('G' . $rowNum, "管理员");
        $objActSheet->setCellValue('H' . $rowNum, $maindata["admin_nickname"]);
        $objActSheet->setCellValue('J' . $rowNum, "寄售门店");
        $objActSheet->setCellValue('K' . $rowNum, $maindata["store_name"]);
        $objActSheet->setCellValue('M' . $rowNum, "供应商");
        $objActSheet->setCellValue('N' . $rowNum, $maindata["supply_name"]);

        $rowNum = 4;
        $objActSheet->setCellValue('A' . $rowNum, "单据状态");
        $objActSheet->setCellValue('B' . $rowNum, $maindata["c_out_status_name"]);
        $objActSheet->setCellValue('D' . $rowNum, "备注");
        $objActSheet->setCellValue('E' . $rowNum, $maindata["remark"]);

        $rowNum = 5;
        $objActSheet->setCellValue('A' . $rowNum, "商品ID");
        $objActSheet->setCellValue('B' . $rowNum, "商品名称");
        $objActSheet->setCellValue('C' . $rowNum, "商品类别");
        $objActSheet->setCellValue('D' . $rowNum, "商品属性");
        $objActSheet->setCellValue('E' . $rowNum, "商品条码");
        $objActSheet->setCellValue('F' . $rowNum, "库存数量");
        $objActSheet->setCellValue('G' . $rowNum, "系统售价");
        $objActSheet->setCellValue('H' . $rowNum, "历史价格");
        $objActSheet->setCellValue('I' . $rowNum, "箱规");
        $objActSheet->setCellValue('J' . $rowNum, "采购箱数");
        $objActSheet->setCellValue('K' . $rowNum, "每箱价格");
        $objActSheet->setCellValue('L' . $rowNum, "采购数量");
        $objActSheet->setCellValue('M' . $rowNum, "采购价格");
        $objActSheet->setCellValue('N' . $rowNum, "备注");

        $rowNum = 6;//当前行
        foreach ($list as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['value_name']);
            $objActSheet->setCellValue('E' . $rowNum, $val['bar_code']);
            $objActSheet->setCellValue('F' . $rowNum, $val['stock_num']);
            $objActSheet->setCellValue('G' . $rowNum, $val['sell_price']);
            $objActSheet->setCellValue('H' . $rowNum, $val["last_price"]);
            $objActSheet->setCellValue('I' . $rowNum, $val['b_n_num']);
            $objActSheet->setCellValue('J' . $rowNum, $val['b_num']);
            $objActSheet->setCellValue('K' . $rowNum, $val["b_price"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["g_num"]);
            $objActSheet->setCellValue('M' . $rowNum, $val["g_price"]);
            $objActSheet->setCellValue('N' . $rowNum, $val["remark"]);
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
        $objActSheet->getColumnDimension('M')->setWidth(24);
        $objActSheet->getColumnDimension('N')->setWidth(24);


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
     * 生成寄售报表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createReportExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "F";

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
        $objActSheet->setCellValue('D' . $rowNum, '入库数量');
        $objActSheet->setCellValue('E' . $rowNum, '出库数量');
        $objActSheet->setCellValue('F' . $rowNum, '销售数量');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_id']);
            $objActSheet->setCellValue('C' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['in_nums']);
            $objActSheet->setCellValue('E' . $rowNum, $val['out_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['sold_nums']);
            $index++;
            $rowNum++;
        }

        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);


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