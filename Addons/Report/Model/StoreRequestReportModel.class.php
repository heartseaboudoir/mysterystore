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

class StoreRequestReportModel extends Model
{
    /***********
     * 生成门店发货申请数据Excel
     * @param $data 数据
     * @param $title Excel标题
     * @param $fname 文件名
     */
    public function createStoreRequestListExcel($data, $title, $fname)
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
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num = 2;
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '申请单号');
        $objActSheet->setCellValue('C' . $num, '申请日期');
        $objActSheet->setCellValue('D' . $num, '申请种类');
        $objActSheet->setCellValue('E' . $num, '申请数量');
        $objActSheet->setCellValue('F' . $num, '申请人');
        $objActSheet->setCellValue('G' . $num, '申请门店');
        $objActSheet->setCellValue('H' . $num, '发货仓库');
        $objActSheet->setCellValue('I' . $num, '售价金额');
        $objActSheet->setCellValue('J' . $num, '申请结果');
        $objActSheet->setCellValue('K' . $num, '备注');
        date_default_timezone_set("PRC");

        $index = 1;//序号
        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //状态:0.新增,1.已审核申请,2.部分通过申请,3.全部通过申请,4.全部拒绝,5.已作废
            switch ($val["s_r_status"]) {
                case 0: {
                    $checkStatus = "新增";
                };
                    break;
                case 1: {
                    $checkStatus = "已审核申请";
                };
                    break;
                case 2: {
                    $checkStatus = "部分通过申请";
                };
                    break;
                case 3: {
                    $checkStatus = "全部通过申请";
                };
                    break;
                case 4: {
                    $checkStatus = "全部拒绝";
                };
                    break;
                case 5: {
                    $checkStatus = "已作废";
                };
                    break;
            }
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['s_r_sn']);
            $objActSheet->setCellValue('C' . $rowNum, $val['ctime']);
            $objActSheet->setCellValue('D' . $rowNum, $val['g_type']);
            $objActSheet->setCellValue('E' . $rowNum, $val['g_nums']);
            $objActSheet->setCellValue('F' . $rowNum, $val['nickname']);
            $objActSheet->setCellValue('G' . $rowNum, $val['store_name']);
            $objActSheet->setCellValue('H' . $rowNum, $val['warehouse_name']);
            $objActSheet->setCellValue('I' . $rowNum, $val['g_amounts']);
            $objActSheet->setCellValue('J' . $rowNum, $checkStatus);
            $objActSheet->setCellValue('K' . $rowNum, $val['remark']);

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


    /**********
     * 生成单个申请的Excel
     * @param $data 数据
     * @param $title Excel标题
     * @param $fname 文件名
     */
    public function createSingleStoreRequestInfoExcel($data, $title, $fname)
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
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        //第一行>>>报表标题
        $maindata = $data["maindata"][0];
        $objActSheet->setCellValue('A2', '申请单号');
        $objActSheet->setCellValue('B2', $maindata['s_r_sn']);
        $objActSheet->setCellValue('D2', '创建日期');
        $objActSheet->setCellValue('E2', $maindata['ctime']);
        $objActSheet->setCellValue('A3', '商品种类');
        $objActSheet->setCellValue('B3', $maindata['g_type']);
        $objActSheet->setCellValue('D3', '商品数量');
        $objActSheet->setCellValue('E3', $maindata['g_nums']);
        $objActSheet->setCellValue('G3', '售价金额');
        $objActSheet->setCellValue('H3', $maindata['g_amounts']);
        $objActSheet->setCellValue('A4', '申请人');
        $objActSheet->setCellValue('B4', $maindata['nickname']);
        $objActSheet->setCellValue('D4', '申请来源');
        $objActSheet->setCellValue('E4', $maindata['store_name']);
        $objActSheet->setCellValue('G4', '发货仓库');
        $objActSheet->setCellValue('H4', $maindata['warehouse_name']);
        $objActSheet->setCellValue('J4', '备注');
        $objActSheet->setCellValue('K4', $maindata['remark']);
        date_default_timezone_set("PRC");

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num = 6;
        $objActSheet->setCellValue('A' . $num, '序号');
        $objActSheet->setCellValue('B' . $num, '商品ID');
        $objActSheet->setCellValue('C' . $num, '商品名称');
        $objActSheet->setCellValue('D' . $num, '商品类别');
        $objActSheet->setCellValue('E' . $num, '商品属性');
        $objActSheet->setCellValue('F' . $num, '商品条码');
        $objActSheet->setCellValue('G' . $num, '系统售价');
        $objActSheet->setCellValue('H' . $num, '申请数量');
        $objActSheet->setCellValue('I' . $num, '发货数量');
        $objActSheet->setCellValue('J' . $num, '备注');
        $objActSheet->setCellValue('K' . $num, '申请结果');

        $index = 1;//序号
        $rowNum = 7;//当前行
        foreach ($data["list"] as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $index);
            $objActSheet->setCellValue('B' . $rowNum, $val['goods_id']);
            $objActSheet->setCellValue('C' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('E' . $rowNum, $val['value_name']);
            $objActSheet->setCellValue('F' . $rowNum, $val['bar_code']);
            $objActSheet->setCellValue('G' . $rowNum, $val['sell_price']);
            $objActSheet->setCellValue('H' . $rowNum, $val['g_num']);
            $objActSheet->setCellValue('I' . $rowNum, $val['pass_num']);
            $objActSheet->setCellValue('J' . $rowNum, $val['remark']);
            $objActSheet->setCellValue('K' . $rowNum, $val['status_name']);

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

    /**********************
     * 生成审核列表Excel
     * @param $data 数据
     * @param $title 标题
     * @param $fname 文件名
     * @param $type 类型  1：自动分单 2：全部申请
     */
    public function createCheckListExcel($data, $title, $fname, $type)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //数据标题
        $num = 2;
        if ($type == 1) {
            //自动分单
            $list = $data["data"];
            foreach ($list as $key => $val) {
                //Excel的第A列，uid是你查出数组的键值，下面以此类推
                $objActSheet->setCellValue('A' . $num, '商品ID');
                $objActSheet->setCellValue('B' . $num, '商品名称');
                $objActSheet->setCellValue('C' . $num, '商品条码');
                $objActSheet->setCellValue('D' . $num, '申请单号');
                $objActSheet->setCellValue('E' . $num, '申请数量');
                $objActSheet->setCellValue('F' . $num, '申请时间');
                $objActSheet->setCellValue('G' . $num, '申请来源');
                $objActSheet->setCellValue('H' . $num, '申请人');
                $objActSheet->setCellValue('I' . $num, '备注');
                $objActSheet->setCellValue('J' . $num, '库存');
                $num++;
                foreach ($val as $k => $v) {
                    //写入数据
                    $objActSheet->setCellValue('A' . $num, $v["goods_id"]);
                    $objActSheet->setCellValue('B' . $num, $v['goods_name']);
                    $objActSheet->setCellValue('C' . $num, $v['bar_code']);
                    $objActSheet->setCellValue('D' . $num, $v['s_r_sn']);
                    $objActSheet->setCellValue('E' . $num, $v['g_num']);
                    $objActSheet->setCellValue('F' . $num, $v['ctime']);
                    $objActSheet->setCellValue('G' . $num, $v['store_name']);
                    $objActSheet->setCellValue('H' . $num, $v['nickname']);
                    $objActSheet->setCellValue('I' . $num, $v['remark']);
                    $objActSheet->setCellValue('J' . $num, $v['stock_num']);
                    $num++;
                }
                $num = $num + 2;
            }
        } else {
            //所有申请

            //Excel的第A列，uid是你查出数组的键值，下面以此类推
            $objActSheet->setCellValue('A' . $num, '商品ID');
            $objActSheet->setCellValue('B' . $num, '商品名称');
            $objActSheet->setCellValue('C' . $num, '商品条码');
            $objActSheet->setCellValue('D' . $num, '申请单号');
            $objActSheet->setCellValue('E' . $num, '申请数量');
            $objActSheet->setCellValue('F' . $num, '申请时间');
            $objActSheet->setCellValue('G' . $num, '申请来源');
            $objActSheet->setCellValue('H' . $num, '申请人');
            $objActSheet->setCellValue('I' . $num, '备注');
            $objActSheet->setCellValue('J' . $num, '库存');

            $rowNum = 3;//当前行
            foreach ($data["data"] as $key => $val) {
                //写入数据
                $objActSheet->setCellValue('A' . $rowNum, $val["goods_id"]);
                $objActSheet->setCellValue('B' . $rowNum, $val['goods_name']);
                $objActSheet->setCellValue('C' . $rowNum, $val['bar_code']);
                $objActSheet->setCellValue('D' . $rowNum, $val['s_r_sn']);
                $objActSheet->setCellValue('E' . $rowNum, $val['g_num']);
                $objActSheet->setCellValue('F' . $rowNum, $val['ctime']);
                $objActSheet->setCellValue('G' . $rowNum, $val['store_name']);
                $objActSheet->setCellValue('H' . $rowNum, $val['nickname']);
                $objActSheet->setCellValue('I' . $rowNum, $val['remark']);
                $objActSheet->setCellValue('J' . $rowNum, $val['stock_num']);
                $rowNum++;
            }
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
        $objActSheet->getColumnDimension('J')->setWidth(12);

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


}