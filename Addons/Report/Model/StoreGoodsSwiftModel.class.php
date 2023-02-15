<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-29
 * Time: 14:18
 * 结款单相关Excel
 */

namespace Addons\Report\Model;

use Think\Model;

class StoreGoodsSwiftModel extends Model
{
    /***************
     * 生成寄售单列表Excel文件
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
        $EndC = "R";

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '商品ID');
        $objActSheet->setCellValue('B' . $rowNum, '商品分类');
        $objActSheet->setCellValue('C' . $rowNum, '商品名');
        $objActSheet->setCellValue('D' . $rowNum, '上期库存');
        $objActSheet->setCellValue('E' . $rowNum, '本月库存');
        $objActSheet->setCellValue('F' . $rowNum, '本月入库');
        $objActSheet->setCellValue('G' . $rowNum, '本月出库');
        $objActSheet->setCellValue("H" . $rowNum, '销售量');
        $objActSheet->setCellValue("I" . $rowNum, '销售价格');
        $objActSheet->setCellValue("J" . $rowNum, '销售金额');
        $objActSheet->setCellValue("K" . $rowNum, '成本价');
        $objActSheet->setCellValue("L" . $rowNum, '成本金额');
        $objActSheet->setCellValue("M" . $rowNum, '应结数量');
        $objActSheet->setCellValue("N" . $rowNum, '应结货款');
        $objActSheet->setCellValue("O" . $rowNum, '丢耗数量');
        $objActSheet->setCellValue("P" . $rowNum, '丢耗金额');
        $objActSheet->setCellValue("Q" . $rowNum, '丢耗率');
        $objActSheet->setCellValue("R" . $rowNum, '状态');
        date_default_timezone_set("PRC");

        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['prev_month_num']);
            $objActSheet->setCellValue('E' . $rowNum, $val['now_month_num']);
            $objActSheet->setCellValue('F' . $rowNum, $val['in_num']);
            $objActSheet->setCellValue('G' . $rowNum, $val["out_num"]);
            $objActSheet->setCellValue('H' . $rowNum, $val['sell_num']);
            $objActSheet->setCellValue('I' . $rowNum, $val['price']);
            $objActSheet->setCellValue('J' . $rowNum, $val["sell_money"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["inprice"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["inprice_money"]);
            $objActSheet->setCellValue('M' . $rowNum, $val["result_num"]);
            $objActSheet->setCellValue('N' . $rowNum, $val["result_money"]);
            $objActSheet->setCellValue('O' . $rowNum, $val["system_lost_num"]);
            $objActSheet->setCellValue('P' . $rowNum, $val["lost_money"]);
            $objActSheet->setCellValue('Q' . $rowNum, $val["lost_rand"]);
            $objActSheet->setCellValue('R' . $rowNum, $val["status_name"]);
            $rowNum++;
        }

        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(36);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(12);
        $objActSheet->getColumnDimension('N')->setWidth(12);
        $objActSheet->getColumnDimension('O')->setWidth(12);
        $objActSheet->getColumnDimension('P')->setWidth(12);
        $objActSheet->getColumnDimension('Q')->setWidth(12);
        $objActSheet->getColumnDimension('R')->setWidth(12);


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
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                    'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => array('argb' => 'CCCCCCCC'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

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
     * 生成结款单Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function goods_store_new_swift_index_Excel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        $value = array(
            'A'=> '合计：',
            'B'=>  '',
            'C'=>  '',
            'D'=>  $data["total_prev_month_num"],
            'E'=>  $data['total_now_month_num'],
            'F'=>  '',
            'G'=>  $data['total_in_num'],
            'H'=>  $data['total_out_num'],
            'I'=>  $data['total_sell_num'],
            'J'=>  '',
            'K'=>  $data["total_sell_money"],
            'L'=> $data["total_inprice_money"],
            'M'=> $data["total_sell_money"] - $data["total_inprice_money"],
            'N'=> ($data["total_sell_money"] > 0 ? (($data["total_sell_money"] - $data["total_inprice_money"]) / $data["total_sell_money"]) * 100 : 0) . "%",
            'O'=>   $data['total_result_num'],
            'P'=>  $data['total_result_money'],
            'Q'=>  $data['total_inout_num'],
            'R'=>  $data['total_system_lost_num'],
            'S'=>  $data["total_lost_money"],
            'T'=> ($data["total_result_num"] > 0 ? ($data["total_system_lost_num"] / $data["total_result_num"]) * 100 : 0) . "%",
            'U'=>  ''
        );


        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "R";

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
        $objActSheet->setCellValue('B' . $rowNum, '商品分类');
        $objActSheet->setCellValue('C' . $rowNum, '商品名');
        $objActSheet->setCellValue('D' . $rowNum, '上期库存');
        $objActSheet->setCellValue('E' . $rowNum, '本月库存');
        $objActSheet->setCellValue('F' . $rowNum, '库存成本金额');
        $objActSheet->setCellValue('G' . $rowNum, '本月入库');
        $objActSheet->setCellValue('H' . $rowNum, '本月出库');
        $objActSheet->setCellValue("I" . $rowNum, '销售量');
        $objActSheet->setCellValue("J" . $rowNum, '销售价格');
        $objActSheet->setCellValue("K" . $rowNum, '销售金额');
        $objActSheet->setCellValue("L" . $rowNum, '成本金额');
        $objActSheet->setCellValue("M" . $rowNum, '毛利');
        $objActSheet->setCellValue("N" . $rowNum, '毛利率');
        $objActSheet->setCellValue("O" . $rowNum, '应结数量');
        $objActSheet->setCellValue("P" . $rowNum, '应结货款');
        $objActSheet->setCellValue("Q" . $rowNum, '盘盈盘亏');
        $objActSheet->setCellValue("R" . $rowNum, '丢耗数量');
        $objActSheet->setCellValue("S" . $rowNum, '丢耗金额');
        $objActSheet->setCellValue("T" . $rowNum, '丢耗率');
        $objActSheet->setCellValue("U" . $rowNum, '状态');
        date_default_timezone_set("PRC");

        $rowNum = 3;//当前行
        $g_amounts = 0;
        foreach ($data['data'] as $key => $val) {
            switch ($val['status']) {
                case 1:
                    $status_text = '上架';
                    break;
                case 2:
                    $status_text = '下架';
                    break;
                case -1:
                    $status_text = '已删除';
                    break;
            }

            $g_amounts += $val['now_month_num'] * $val["g_price"];
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["goods_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['cate_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['goods_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['prev_month_num']);
            $objActSheet->setCellValue('E' . $rowNum, $val['now_month_num']);
            $objActSheet->setCellValue('F' . $rowNum, $val['now_month_num'] * $val["g_price"]);
            $objActSheet->setCellValue('G' . $rowNum, $val['in_num']);
            $objActSheet->setCellValue('H' . $rowNum, $val["out_num"]);
            $objActSheet->setCellValue('I' . $rowNum, $val['sell_num']);
            $objActSheet->setCellValue('J' . $rowNum, $val['price']);
            $objActSheet->setCellValue('K' . $rowNum, $val["sell_money"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["inprice_money"]);
            $objActSheet->setCellValue('M' . $rowNum, $val["gross_profit"]);
            $objActSheet->setCellValue('N' . $rowNum, $val["gross_profit_rate"] . "%");
            $objActSheet->setCellValue('O' . $rowNum, $val["result_num"]);
            $objActSheet->setCellValue('P' . $rowNum, $val["result_money"]);
            $objActSheet->setCellValue('Q' . $rowNum, $val['inout_num']);
            $objActSheet->setCellValue('R' . $rowNum, $val["system_lost_num"]);
            $objActSheet->setCellValue('S' . $rowNum, $val["lost_money"]);
            $objActSheet->setCellValue('T' . $rowNum, $val["lost_rand"]."%");
            $objActSheet->setCellValue('U' . $rowNum, $status_text);
            $rowNum++;
        }
//写入数据
        $objActSheet->setCellValue('A' . $rowNum, $value['A']);
        $objActSheet->setCellValue('B' . $rowNum, $value['B']);
        $objActSheet->setCellValue('C' . $rowNum, $value['C']);
        $objActSheet->setCellValue('D' . $rowNum, $value['D']);
        $objActSheet->setCellValue('E' . $rowNum, $value['E']);
        $objActSheet->setCellValue('F' . $rowNum, $g_amounts);
        $objActSheet->setCellValue('G' . $rowNum, $value['G']);
        $objActSheet->setCellValue('H' . $rowNum, $value['H']);
        $objActSheet->setCellValue('I' . $rowNum, $value['I']);
        $objActSheet->setCellValue('J' . $rowNum, $value['J']);
        $objActSheet->setCellValue('K' . $rowNum, $value['K']);
        $objActSheet->setCellValue('L' . $rowNum, $value['L']);
        $objActSheet->setCellValue('M' . $rowNum, $value['M']);
        $objActSheet->setCellValue('N' . $rowNum, $value['N']);
        $objActSheet->setCellValue('O' . $rowNum, $value['O']);
        $objActSheet->setCellValue('P' . $rowNum, $value['P']);
        $objActSheet->setCellValue('Q' . $rowNum, $value['Q']);
        $objActSheet->setCellValue('R' . $rowNum, $value['R']);
        $objActSheet->setCellValue('S' . $rowNum, $value['S']);
        $objActSheet->setCellValue('T' . $rowNum, $value['T']);
        $objActSheet->setCellValue('U' . $rowNum, $value['U']);
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(36);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(12);
        $objActSheet->getColumnDimension('N')->setWidth(12);
        $objActSheet->getColumnDimension('O')->setWidth(12);
        $objActSheet->getColumnDimension('P')->setWidth(12);
        $objActSheet->getColumnDimension('Q')->setWidth(12);
        $objActSheet->getColumnDimension('R')->setWidth(12);
        $objActSheet->getColumnDimension('S')->setWidth(12);
        $objActSheet->getColumnDimension('T')->setWidth(12);
        $objActSheet->getColumnDimension('U')->setWidth(12);


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
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                    'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => array('argb' => 'CCCCCCCC'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

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
     * 生成全局结款单Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function overall_situation_goods_store_new_swift_index_Excel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        $value = array(
            'A'=> '合计：',
            'B'=>  '',
            'C'=>  '',
            'D'=>  $data["total_prev_month_num"],
            'E'=>  $data['total_now_month_num'],
            'F'=>  $data['total_new_month_g_inprice_money'],
            'G'=>  $data['total_in_num'],
            'H'=>  $data['total_out_num'],
            'I'=>  $data['total_sell_num'],
            'J'=>  $data["total_sell_money"],
            'K'=>  $data["total_inprice_money"],
            'L'=>  $data["total_sell_money"] - $data["total_inprice_money"],
            'M'=> ($data["total_sell_money"] > 0 ? (($data["total_sell_money"] - $data["total_inprice_money"]) / $data["total_sell_money"]) * 100 : 0) . "%",
            'N'=> $data['total_result_num'],
            'O'=> $data['total_result_money'],
            'P'=>   $data['total_inout_num'],
            'Q'=>  $data['total_system_lost_num'],
            'R'=>  $data["total_lost_money"],
            'S'=>  ($data["total_result_num"] > 0 ? ($data["total_system_lost_num"] / $data["total_result_num"]) * 100 : 0) . "%"
        );
        
        
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "R";
        
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);
        
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '门店ID');
        $objActSheet->setCellValue('B' . $rowNum, '门店名称');
        $objActSheet->setCellValue('C' . $rowNum, '区域名称');
        $objActSheet->setCellValue('D' . $rowNum, '上期库存');
        $objActSheet->setCellValue('E' . $rowNum, '本月库存');
        $objActSheet->setCellValue('F' . $rowNum, '库存成本金额');
        $objActSheet->setCellValue('G' . $rowNum, '本月入库');
        $objActSheet->setCellValue('H' . $rowNum, '本月出库');
        $objActSheet->setCellValue("I" . $rowNum, '销售量');
        $objActSheet->setCellValue("J" . $rowNum, '销售金额');
        $objActSheet->setCellValue("K" . $rowNum, '成本金额');
        $objActSheet->setCellValue("L" . $rowNum, '毛利');
        $objActSheet->setCellValue("M" . $rowNum, '毛利率');
        $objActSheet->setCellValue("N" . $rowNum, '应结数量');
        $objActSheet->setCellValue("O" . $rowNum, '应结货款');
        $objActSheet->setCellValue("P" . $rowNum, '盘盈盘亏');
        $objActSheet->setCellValue("Q" . $rowNum, '丢耗数量');
        $objActSheet->setCellValue("R" . $rowNum, '丢耗金额');
        $objActSheet->setCellValue("S" . $rowNum, '丢耗率');
        date_default_timezone_set("PRC");
        
        $rowNum = 3;//当前行
        foreach ($data['data'] as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["store_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['title']);
            $objActSheet->setCellValue('C' . $rowNum, $val['shequ_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['prev_month_num']);
            $objActSheet->setCellValue('E' . $rowNum, $val['now_month_num']);
            $objActSheet->setCellValue('F' . $rowNum, $val['g_inprice_money']);
            $objActSheet->setCellValue('G' . $rowNum, $val['in_num']);
            $objActSheet->setCellValue('H' . $rowNum, $val["out_num"]);
            $objActSheet->setCellValue('I' . $rowNum, $val['sell_num']);
            $objActSheet->setCellValue('J' . $rowNum, $val["sell_money"]);
            $objActSheet->setCellValue('K' . $rowNum, $val["inprice_money"]);
            $objActSheet->setCellValue('L' . $rowNum, $val["gross_profit"]);
            $objActSheet->setCellValue('M' . $rowNum, $val["gross_profit_rate"] . "%");
            $objActSheet->setCellValue('N' . $rowNum, $val["result_num"]);
            $objActSheet->setCellValue('O' . $rowNum, $val["result_money"]);
            $objActSheet->setCellValue('P' . $rowNum, $val['inout_num']);
            $objActSheet->setCellValue('Q' . $rowNum, $val["system_lost_num"]);
            $objActSheet->setCellValue('R' . $rowNum, $val["lost_money"]);
            $objActSheet->setCellValue('S' . $rowNum, $val["lost_rand"]."%");
            $rowNum++;
        }
        //写入数据
        $objActSheet->setCellValue('A' . $rowNum, $value['A']);
        $objActSheet->setCellValue('B' . $rowNum, $value['B']);
        $objActSheet->setCellValue('C' . $rowNum, $value['C']);
        $objActSheet->setCellValue('D' . $rowNum, $value['D']);
        $objActSheet->setCellValue('E' . $rowNum, $value['E']);
        $objActSheet->setCellValue('F' . $rowNum, $value['F']);
        $objActSheet->setCellValue('G' . $rowNum, $value['G']);
        $objActSheet->setCellValue('H' . $rowNum, $value['H']);
        $objActSheet->setCellValue('I' . $rowNum, $value['I']);
        $objActSheet->setCellValue('J' . $rowNum, $value['J']);
        $objActSheet->setCellValue('K' . $rowNum, $value['K']);
        $objActSheet->setCellValue('L' . $rowNum, $value['L']);
        $objActSheet->setCellValue('M' . $rowNum, $value['M']);
        $objActSheet->setCellValue('N' . $rowNum, $value['N']);
        $objActSheet->setCellValue('O' . $rowNum, $value['O']);
        $objActSheet->setCellValue('P' . $rowNum, $value['P']);
        $objActSheet->setCellValue('Q' . $rowNum, $value['Q']);
        $objActSheet->setCellValue('R' . $rowNum, $value['R']);
        $objActSheet->setCellValue('S' . $rowNum, $value['R']);
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(36);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(12);
        $objActSheet->getColumnDimension('N')->setWidth(12);
        $objActSheet->getColumnDimension('O')->setWidth(12);
        $objActSheet->getColumnDimension('P')->setWidth(12);
        $objActSheet->getColumnDimension('Q')->setWidth(12);
        $objActSheet->getColumnDimension('R')->setWidth(12);
        $objActSheet->getColumnDimension('S')->setWidth(12);
        
        
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
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                    'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => array('argb' => 'CCCCCCCC'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框
        
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
     * 生成全局订单Excel文件
     * @param $data 数据源
     * @param $title 标题
     * @param $fname 文件名
     */
    public function order_situation_store_index_Excel($data, $title, $fname)
    {
        if (!$fname) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        $EndC = "G";

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
        //第一行>>>报表标题
        $objActSheet->setCellValue('A1', $title);

        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $rowNum = 2;
        $objActSheet->setCellValue('A' . $rowNum, '门店ID');
        $objActSheet->setCellValue('B' . $rowNum, '门店名称');
        $objActSheet->setCellValue('C' . $rowNum, '区域名称');
        $objActSheet->setCellValue('D' . $rowNum, '消费次数');
        $objActSheet->setCellValue('E' . $rowNum, '销售数量');
        $objActSheet->setCellValue('F' . $rowNum, '销售金额');
        $objActSheet->setCellValue('G' . $rowNum, '实付金额');
        //$objActSheet->setCellValue('H' . $rowNum, '成本金额');
        date_default_timezone_set("PRC");

        $rowNum = 3;//当前行
        foreach ($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $rowNum, $val["store_id"]);
            $objActSheet->setCellValue('B' . $rowNum, $val['store_name']);
            $objActSheet->setCellValue('C' . $rowNum, $val['shequ_name']);
            $objActSheet->setCellValue('D' . $rowNum, $val['order_count']);
            $objActSheet->setCellValue('E' . $rowNum, $val['num']);
            $objActSheet->setCellValue('F' . $rowNum, $val['money']);
            $objActSheet->setCellValue('G' . $rowNum, $val['pay_money']);
          //  $objActSheet->setCellValue('H' . $rowNum, $val['inout_money']);
            $rowNum++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(36);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
     //   $objActSheet->getColumnDimension('H')->setWidth(12);


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
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                    'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => array('argb' => 'CCCCCCCC'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $EndC . (string)($rowNum - 1))->applyFromArray($styleArray);//应用边框

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