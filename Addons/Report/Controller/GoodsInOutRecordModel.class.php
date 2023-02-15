<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-07
 * Time: 16:09
 */

namespace Addons\Report\Model;

use Think\Model;

class GoodsInOutRecordModel extends Model
{
    /***************
     * 生成入库单列表Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function createStockConditionExcel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "I";

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '商品ID');
        $objActSheet->setCellValue('B' . $rowNum, '商品名称');
        $objActSheet->setCellValue('C' . $rowNum, '门店库存');
        $objActSheet->setCellValue('D' . $rowNum, '仓库库存');
        $objActSheet->setCellValue('E' . $rowNum, '门店在途数量');
        $objActSheet->setCellValue('F' . $rowNum, '仓库在途数量');
        $objActSheet->setCellValue('G' . $rowNum, '批次数量');
        $objActSheet->setCellValue('H' . $rowNum, '相差数量');
        $objActSheet->setCellValue("I" . $rowNum, '入库平均价');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            $source = is_null($val["warehouse_name"]) || empty($val["warehouse_name"]) ? $val["store_name1"] : $val["warehouse_name"];
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['store_num']);
            $objActSheet->setCellValue('D' . $rowNum, $val['warehouse_num']);
            $objActSheet->setCellValue('E' . $rowNum, $val['store_zt_num']);
            $objActSheet->setCellValue('F' . $rowNum, $val['warehouse_zt_num']);
            $objActSheet->setCellValue('G' . $rowNum, $val["inout_num"]);
            $objActSheet->setCellValue('H' . $rowNum, $val["b_num"]);
            $objActSheet->setCellValue('I' . $rowNum, $val['ginprice']);
            $rowNum++;
        }

        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);


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