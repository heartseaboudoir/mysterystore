<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-10-27
 * Time: 16:34
 * 调拨模块Excel处理
 */

namespace Addons\Report\Model;

use Think\Model;

class WarehouseRequestReportModel extends Model
{

    /**************
     * 导出申请调拨申请单列表信息
     * @param $list 原数据
     * @param $title 标题
     * @param $fname 文件名
     * @return bool
     */
    public function exportWarehouseRequestListExcel($list, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);
        $objActSheet->setCellValue('A2', '序号');
        $objActSheet->setCellValue('B2', '调拨申请单号');
        $objActSheet->setCellValue('C2', '申请日期');
        $objActSheet->setCellValue('D2', '申请种类');
        $objActSheet->setCellValue('E2', '申请数量');
        $objActSheet->setCellValue('F2', '申请人');
        $objActSheet->setCellValue('G2', '申请仓库');
        $objActSheet->setCellValue('H2', '调拨仓库');
        $objActSheet->setCellValue('I2', '售价金额');
        $objActSheet->setCellValue('J2', '申请结果');
        $objActSheet->setCellValue('K2', '备注');
        date_default_timezone_set("PRC");
        $i = 3;
        $index = 1;
        foreach ($list as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $i, $index);
            $objActSheet->setCellValue('B' . $i, $val['w_r_sn']);
            $objActSheet->setCellValue('C' . $i, $val['ctime']);
            $objActSheet->setCellValue('D' . $i, $val['g_type']);
            $objActSheet->setCellValue('E' . $i, $val['g_nums']);
            $objActSheet->setCellValue('F' . $i, $val['nickname']);
            $objActSheet->setCellValue('G' . $i, $val['warehouse_name1']);
            $objActSheet->setCellValue('H' . $i, $val['warehouse_name2']);
            $objActSheet->setCellValue('I' . $i, $val['g_amounts']);

            //审核状态  状态:0.新增,1.已审核申请,2.部分通过申请,3.全部拒绝,4.已作废
            $statusStr = "新增";
            if ($val['w_r_status'] == 0) {
                $statusStr = "新增";
            } elseif ($val['w_r_status'] == 1) {
                $statusStr = "已审核申请";
            } elseif ($val['w_r_status'] == 2) {
                $statusStr = "部分通过审核";
            } elseif ($val['w_r_status'] == 3) {
                $statusStr = "全部拒绝";
            } elseif ($val['w_r_status'] == 4) {
                $statusStr = "已作废";
            }

            $objActSheet->setCellValue('J' . $i, $statusStr);
            $objActSheet->setCellValue('K' . $i, $val['remark']);
            $i++;
            $index++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(30);
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
        $objPHPExcel->getActiveSheet()->getStyle('A5:I' . (string)(6 - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fname . '.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }

    /*********************
     * 导出单个调拨明细Excel
     * @param $list 主体部分
     * @param $data 列表部分
     * @param $title 标题
     * @param $fname 文件名
     * @return bool
     */
    public function exportSingleWarehouseRequestExcel($list, $data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num = 1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);
        $objActSheet->setCellValue('A2', '申请单号');
        $objActSheet->setCellValue('B2', $list['w_r_sn']);
        $objActSheet->setCellValue('D2', '创建日期');
        $objActSheet->setCellValue('E2', $list['ctime']);
        $objActSheet->setCellValue('A3', '商品种类');
        $objActSheet->setCellValue('B3', $list['g_type']);
        $objActSheet->setCellValue('D3', '商品数量');
        $objActSheet->setCellValue('E3', $list['g_nums']);
        $objActSheet->setCellValue('G3', '售价金额');
        $objActSheet->setCellValue('H3', $list['g_amounts']);
        $objActSheet->setCellValue('A4', '申请人');
        $objActSheet->setCellValue('B4', $list['nickname']);
        $objActSheet->setCellValue('D4', '申请来源');
        $objActSheet->setCellValue('E4', $list['w_name1']);
        $objActSheet->setCellValue('G4', '调拨仓库');
        $objActSheet->setCellValue('H4', $list['w_name2']);
        $objActSheet->setCellValue('J4', '备注');
        $objActSheet->setCellValue('K4', $list['remark']);
        $num = 5;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '商品ID');
        $objActSheet->setCellValue('C' . $num, '商品名称');
        $objActSheet->setCellValue('D' . $num, '商品类别');
        $objActSheet->setCellValue('E' . $num, '商品属性');
        $objActSheet->setCellValue('F' . $num, '商品条码');
        $objActSheet->setCellValue('G' . $num, '系统售价');
        $objActSheet->setCellValue('H' . $num, '库存数量');
        $objActSheet->setCellValue('I' . $num, '申请数量');
        $objActSheet->setCellValue('J' . $num, '发货数量');
        $objActSheet->setCellValue('K' . $num, '备注');
        $objActSheet->setCellValue('L' . $num, '申请结果');
        $num2 = 6;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $num2, $i);
            $objActSheet->setCellValue('B' . $num2, $val['goods_id']);
            $objActSheet->setCellValue('C' . $num2, $val['goods_name']);
            $objActSheet->setCellValue('D' . $num2, $val['cate_name']);
            $objActSheet->setCellValue('E' . $num2, $val['value_name']);
            $objActSheet->setCellValue('F' . $num2, ' ' . $val['bar_code']);
            $objActSheet->setCellValue('G' . $num2, $val['sell_price']);
            $objActSheet->setCellValue('H' . $num2, $val['stock_num']);
            $objActSheet->setCellValue('I' . $num2, $val['g_num']);
            $objActSheet->setCellValue('J' . $num2, $val['pass_num']);
            $objActSheet->setCellValue('K' . $num2, $val["remark"]);
            $objActSheet->setCellValue('L' . $num2, $val["is_pass_name"]);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A5:I' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fname . '.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }


    /*******************
     * 导出调拨申请自动分单Excel
     * @param $list 原数据
     * @param $title 标题
     * @param $fname 文件名
     */
    public function exportWarehouseRequestAutoSortByOrderListExcel($list, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);
        $objActSheet->setCellValue('A2', '商品ID');
        $objActSheet->setCellValue('B2', '商品名称');
        $objActSheet->setCellValue('C2', '商品属性');
        $objActSheet->setCellValue('D2', '商品条码');
        $objActSheet->setCellValue('E2', '申请单号');
        $objActSheet->setCellValue('F2', '申请数量');
        $objActSheet->setCellValue('G2', '申请时间');
        $objActSheet->setCellValue('H2', '申请来源');
        $objActSheet->setCellValue('I2', '申请人');
        $objActSheet->setCellValue('J2', '备注');
        $objActSheet->setCellValue('K2', '历史供应商');
        $objActSheet->setCellValue('L2', '历史采购价');
        date_default_timezone_set("PRC");
        $i = 3;//当前行号
        foreach ($list as $key => $val) {
            $detail = $val['detail'];//detail申请明细
            //写入数据
            foreach ($detail as $k => $v) {
                $objActSheet->setCellValue('A' . $i, $v["goods_id"]);
                $objActSheet->setCellValue('B' . $i, $v["goods_name"]);
                $objActSheet->setCellValue('C' . $i, $v["value_name"]);
                $objActSheet->setCellValue('D' . $i, $v["bar_code"]);
                $objActSheet->setCellValue('E' . $i, $val["w_r_sn"]);
                $objActSheet->setCellValue('F' . $i, $v["g_num"]);
                $objActSheet->setCellValue('G' . $i, $val["ctime"]);
                $objActSheet->setCellValue('H' . $i, $val["w_name"]);
                $objActSheet->setCellValue('I' . $i, $val["nickname"]);
                $objActSheet->setCellValue('J' . $i, $val["remark"]);
                $objActSheet->setCellValue('K' . $i, $v["s_name"]);
                $objActSheet->setCellValue('L' . $i, $v["g_price"]);
                $i++;
            }
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(50);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(40);
        $objActSheet->getColumnDimension('E')->setWidth(14);
        $objActSheet->getColumnDimension('F')->setWidth(20);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(40);
        $objActSheet->getColumnDimension('K')->setWidth(30);
        $objActSheet->getColumnDimension('L')->setWidth(30);
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
        $objPHPExcel->getActiveSheet()->getStyle('A5:I' . (string)(6 - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fname . '.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }


    /*******************
     * 导出调拨申请所有单Excel
     * @param $list 原数据
     * @param $title 标题
     * @param $fname 文件名
     */
    public function exportWarehouseRequestAllOrderListExcel($list, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);
        $objActSheet->setCellValue('A2', '商品ID');
        $objActSheet->setCellValue('B2', '商品名称');
        $objActSheet->setCellValue('C2', '商品属性');
        $objActSheet->setCellValue('D2', '商品条码');
        $objActSheet->setCellValue('E2', '申请单号');
        $objActSheet->setCellValue('F2', '申请数量');
        $objActSheet->setCellValue('G2', '申请时间');
        $objActSheet->setCellValue('H2', '申请来源');
        $objActSheet->setCellValue('I2', '申请人');
        $objActSheet->setCellValue('J2', '备注');
        $objActSheet->setCellValue('K2', '历史供应商');
        $objActSheet->setCellValue('L2', '历史采购价');

        date_default_timezone_set("PRC");
        $i = 3;//当前行号
        foreach ($list as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $i, $v["goods_id"]);
            $objActSheet->setCellValue('B' . $i, $v["goods_name"]);
            $objActSheet->setCellValue('C' . $i, $v["value_name"]);
            $objActSheet->setCellValue('D' . $i, $v["bar_code"]);
            $objActSheet->setCellValue('E' . $i, $val["w_r_sn"]);
            $objActSheet->setCellValue('F' . $i, $v["g_num"]);
            $objActSheet->setCellValue('G' . $i, $val["ctime"]);
            $objActSheet->setCellValue('H' . $i, $val["w_name"]);
            $objActSheet->setCellValue('I' . $i, $val["nickname"]);
            $objActSheet->setCellValue('J' . $i, $val["remark"]);
            $objActSheet->setCellValue('K' . $i, $v["s_name"]);
            $objActSheet->setCellValue('L' . $i, $v["g_price"]);
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(50);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(40);
        $objActSheet->getColumnDimension('E')->setWidth(14);
        $objActSheet->getColumnDimension('F')->setWidth(20);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(40);
        $objActSheet->getColumnDimension('K')->setWidth(30);
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
        $objPHPExcel->getActiveSheet()->getStyle('A5:I' . (string)(6 - 1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fname . '.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }


}