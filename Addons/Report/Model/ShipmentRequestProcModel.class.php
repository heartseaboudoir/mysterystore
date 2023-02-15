<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-20
 * Time: 17:07
 */

namespace Addons\Report\Model;

use Think\Model;

class ShipmentRequestProcModel extends Model
{

    /*******************
     * 创建仓库出库验货单列表Excel文件
     * @param $data 数据
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createWarehouseOutListExcel($data, $title, $fname)
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
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '序号');
        $objActSheet->setCellValue('B' . $rowNum, '申请单号');
        $objActSheet->setCellValue('C' . $rowNum, '申请日期');
        $objActSheet->setCellValue('D' . $rowNum, '申请种类');
        $objActSheet->setCellValue('E' . $rowNum, '申请数量');
        $objActSheet->setCellValue('F' . $rowNum, '申请人');
        $objActSheet->setCellValue('G' . $rowNum, '申请来源');
        $objActSheet->setCellValue('H' . $rowNum, '发货仓库');
        $objActSheet->setCellValue('I' . $rowNum, '售价金额');
        $objActSheet->setCellValue('J' . $rowNum, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $app_source = is_null($val["store_id"]) || empty($val["store_id"]) ? $val["warehouse_name1"] : $val["store_name"];
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['w_out_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['admin_nickname']);
            $objActSheet->setCellValue('G' . $rowNum, $app_source);
            $objActSheet->setCellValue('H' . $rowNum, $val['warehouse_name2']);
            $objActSheet->setCellValue('I' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('J' . $rowNum, $val["remark"]);

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:K' . (string)(3))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="' . $fname . '"');  //日期为文件名后缀
        //header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }

    /*******************
     * 创建单个仓库出库验货单Excel文件
     * @param $data 数据
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createSingleWarehouseOutDetailInfoExcel($data, $title, $fname)
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
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        //第一行>>>报表标题
        $maindata = $data["maindata"];
        $app_source = "";//申请来源
        if (is_null($maindata["store_name"]) || empty($maindata["store_name"])) {
            $app_source = $maindata["warehouse_name"];
        } else {
            $app_source = $maindata["store_name"];
        }
        $objActSheet->setCellValue('A2', '申请单号');
        $objActSheet->setCellValue('B2', $maindata['w_out_sn']);
        $objActSheet->setCellValue('D2', '创建日期');
        $objActSheet->setCellValue('E2', $maindata['ctime']);
        $objActSheet->setCellValue('A3', '商品种类');
        $objActSheet->setCellValue('B3', $maindata['g_type']);
        $objActSheet->setCellValue('D3', '商品数量');
        $objActSheet->setCellValue('E3', $maindata['g_nums']);
        $objActSheet->setCellValue('G3', '售价金额');
        $objActSheet->setCellValue('H3', $maindata['g_amount']);
        $objActSheet->setCellValue('A4', '申请人');
        $objActSheet->setCellValue('B4', $maindata['admin_nickname']);
        $objActSheet->setCellValue('D4', '申请来源');
        $objActSheet->setCellValue('E4', $app_source);
        $objActSheet->setCellValue('G4', '发货仓库');
        $objActSheet->setCellValue('H4', $maindata['warehouse_name2']);
        $objActSheet->setCellValue('A5', '处理人');
        $objActSheet->setCellValue('B5', $maindata['padmin_nickname']);
        $objActSheet->setCellValue('D5', '处理时间');
        $objActSheet->setCellValue('E5', $maindata['ptime']);
        $objActSheet->setCellValue('A6', '备注');
        $objActSheet->setCellValue('B6', $maindata['remark']);
        date_default_timezone_set("PRC");

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num = 8;
        $objActSheet->setCellValue('A' . $num, '商品ID');
        $objActSheet->setCellValue('B' . $num, '商品名称');
        $objActSheet->setCellValue('C' . $num, '商品类别');
        $objActSheet->setCellValue('D' . $num, '商品条码');
        $objActSheet->setCellValue('E' . $num, '零售价');
        $objActSheet->setCellValue('F' . $num, '申请数量');
        $objActSheet->setCellValue('G' . $num, '验收数量');
        $objActSheet->setCellValue('H' . $num, '退货数量');

        $rowNum = 9;//当前行
        foreach ($data["list"] as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val['goods_id']);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['bar_code']);
            $objActSheet->setCellValue('E' . $rowNum, $val['sell_price']);
            $objActSheet->setCellValue('F' . $rowNum, $val['g_num']);
            $objActSheet->setCellValue('G' . $rowNum, $val['in_num']);
            $objActSheet->setCellValue('H' . $rowNum, is_null($val['out_name']) || empty($val['out_name']) ? 0 : $val["out_num"]);

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
        $objPHPExcel->getActiveSheet()->getStyle('A2:H' . (string)(6))->applyFromArray($styleArray);//应用边框

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