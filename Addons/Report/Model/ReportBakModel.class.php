<?php

namespace Addons\Report\Model;
use Think\Model;

class ReportModel extends Model{
    /*******************导出门店销售对比**************************/
    public function OutStoreSaleList1Function($data,$title,$fname){
        if(count($data)>0){
            $time = date('Ymd',time());
            $file_name = 'storesale_'.$time.'.xlsx';
            $this->pushStoreSaleList1($data,$name='Excel',$file_name,$title,$fname);
        }
        $file_name = substr($file_name,1);
        return $file_name;
    }
    /*******************导出门店销售对比2**************************/
    public function pushStoreSaleList1($data,$name='Excel',$file_name,$title,$fname){
        if( !$file_name ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '门店');
        $objActSheet ->setCellValue('B'.$num, '销量');
        $objActSheet ->setCellValue('C'.$num, '金额');
        $objActSheet ->setCellValue('D'.$num, '实收金额');
        $num2 = 3;
        $orderType = C('SYS_ORDER_TYPE');
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['store_name']);
            $objActSheet ->setCellValue('B'.$num2, $val['qty_day']);
            $objActSheet ->setCellValue('C'.$num2, $val['amount_day']);
            $objActSheet ->setCellValue('D'.$num2, $val['pay_money_day']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getRowDimension('1')->setRowHeight(40);//设置标题行高度
        //$objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出门店销售详情**************************/
    public function pushStoreSaleList2($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '门店');
        $objActSheet ->setCellValue('B'.$num, '用户');
        $objActSheet ->setCellValue('C'.$num, '订单号');
        $objActSheet ->setCellValue('D'.$num, '订单创建日期');
        //订单子表记录
        $objActSheet ->setCellValue('E'.$num, '商品');
        $objActSheet ->setCellValue('F'.$num, '分类');
        $objActSheet ->setCellValue('G'.$num, '销量');
        $objActSheet ->setCellValue('H'.$num, '售价');
        $objActSheet ->setCellValue('I'.$num, '金额');
        //end子表记录
        $objActSheet ->setCellValue('J'.$num, '售价小计');
        $objActSheet ->setCellValue('K'.$num, '实收金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            //根据订单子表记录数合并单元格
            $countchild = count($val['child']);
            if($countchild>1){
                $objPHPExcel->getActiveSheet()->mergeCells('A' .$num2. ':A' .(string)($num2+$countchild-1));//合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('B' .$num2. ':B' .(string)($num2+$countchild-1));//合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('C' .$num2. ':C' .(string)($num2+$countchild-1));//合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('D' .$num2. ':D' .(string)($num2+$countchild-1));//合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('J' .$num2. ':J' .(string)($num2+$countchild-1));//合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('K' .$num2. ':K' .(string)($num2+$countchild-1));//合并单元格
            }
            $objActSheet ->setCellValue('A'.$num2, $val['store_name']);
            $objActSheet ->setCellValue('B'.$num2, get_nickname($val['uid']));
            $objActSheet ->setCellValue('C'.$num2, ' ' .$val['order_sn']);
            $ctime = date("Y-m-d H:i", $val['create_time']);
            $objActSheet ->setCellValue('D'.$num2, $ctime);
            $sum_sub_amount = 0;
            for($i = 0;$i<$countchild;$i++){
                $objActSheet ->setCellValue('E'.(string)($num2+$i), $val['child'][$i]['goods_name']);
                $objActSheet ->setCellValue('F'.(string)($num2+$i), $val['child'][$i]['cate_name']);
                $objActSheet ->setCellValue('G'.(string)($num2+$i), $val['child'][$i]['num']);
                $objActSheet ->setCellValue('H'.(string)($num2+$i), $val['child'][$i]['price']);
                $sub_amount = (double)$val['child'][$i]['num']*(double)$val['child'][$i]['price'];
                $objActSheet ->setCellValue('I'.(string)($num2+$i), (string)$sub_amount);
                $sum_sub_amount += $sub_amount;
            }
            $objActSheet ->setCellValue('J'.$num2, $sum_sub_amount);
            $objActSheet ->setCellValue('K'.$num2, $val['pay_money']);
            $num2 = $num2 + $countchild;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(48);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getRowDimension('1')->setRowHeight(40);//设置标题行高度
        //$objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:K'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出门店库存快照view**************************/
    public function pushStoreJieCunList1($data,$title,$fname){
        if( !$title ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '门店名称');
        $objActSheet ->setCellValue('B'.$num, '库存总数量');
        $objActSheet ->setCellValue('C'.$num, '库存总零售金额');
        $num2 = 3;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['title']);
            $objActSheet ->setCellValue('B'.$num2, $val['sum_num']);
            $objActSheet ->setCellValue('C'.$num2, $val['sum_amount']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(36);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getRowDimension('1')->setRowHeight(40);//设置标题行高度
        //$objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出门店库存快照viewchild**************************/
    public function pushStoreJieCunList2($data,$title,$fname){
        if( !$title ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:G1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '门店名称');
        $objActSheet ->setCellValue('B'.$num, '商品ID');
        $objActSheet ->setCellValue('C'.$num, '商品名');
        $objActSheet ->setCellValue('D'.$num, '商品分类');
        $objActSheet ->setCellValue('E'.$num, '库存');
        $objActSheet ->setCellValue('F'.$num, '售价');
        $objActSheet ->setCellValue('G'.$num, '金额');
        $num2 = 3;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['store_title']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('C'.$num2, $val['goods_title']);
            $objActSheet ->setCellValue('D'.$num2, $val['goods_cat']);
            $objActSheet ->setCellValue('E'.$num2, $val['jc_num']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('G'.$num2, $val['jc_amount']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(48);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getRowDimension('1')->setRowHeight(40);//设置标题行高度
        //$objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:G'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店月活用户**************************/
    public function pushMemberSaleList1($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '用户');
        $objActSheet ->setCellValue('B'.$num, '消费总金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, get_nickname($val['uid']));
            $objActSheet ->setCellValue('B'.$num2, ' ' .$val['p_m']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:B'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店月活用户详情**************************/
    public function pushMemberSaleList2($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '用户');
        $objActSheet ->setCellValue('B'.$num, '订单号');
        $objActSheet ->setCellValue('C'.$num, '实付金额');
        $objActSheet ->setCellValue('D'.$num, '创建时间');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, get_nickname($val['uid']));
            $objActSheet ->setCellValue('B'.$num2, ' ' .$val['order_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['pay_money']);
            $objActSheet ->setCellValue('D'.$num2, date('Y年m月d日 H时i分',$val['create_time']));
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店新用户列表**************************/
    public function pushSaleNewUserList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '用户昵称');
        $objActSheet ->setCellValue('C'.$num, '用户ID');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, get_nickname($val['uid']));
            $objActSheet ->setCellValue('C'.$num2, $val['uid']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店累计用户列表**************************/
    public function pushSaleUserList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '用户昵称');
        $objActSheet ->setCellValue('B'.$num, '最后消费日期');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, get_nickname($val['uid']));
            $objActSheet ->setCellValue('B'.$num2, $val['ctime']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:B'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店日客单价**************************/
    public function pushSaleDayAvgList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '日客单价');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['showdata']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:B'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店日均消费次数**************************/
    public function pushSaleNumList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '日均消费次数');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['showdata']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:B'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店商品销量排行【按商品】**************************/
    public function pushSaleGoodsTop10($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品id');
        $objActSheet ->setCellValue('B'.$num, '商品名');
        $objActSheet ->setCellValue('C'.$num, '销量');
        $objActSheet ->setCellValue('D'.$num, '销售额');
        $objActSheet ->setCellValue('E'.$num, '日均销量');
        $objActSheet ->setCellValue('F'.$num, '日均销售额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['sum_num']);
            $objActSheet ->setCellValue('D'.$num2, $val['sum_amount']);
            $objActSheet ->setCellValue('E'.$num2, $val['avgnum']);
            $objActSheet ->setCellValue('F'.$num2, $val['avgmoney']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:F'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店商品销量排行【按分类】**************************/
    public function pushSaleCateTop10($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '分类id');
        $objActSheet ->setCellValue('B'.$num, '分类名');
        $objActSheet ->setCellValue('C'.$num, '销量');
        $objActSheet ->setCellValue('D'.$num, '销售额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['cate_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['sum_num']);
            $objActSheet ->setCellValue('D'.$num2, $val['sum_amount']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店商品销量详情【按商品】**************************/
    public function pushSaleGoodsTop10_Detail($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品id');
        $objActSheet ->setCellValue('B'.$num, '商品名');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '订单单号');
        $objActSheet ->setCellValue('E'.$num, '销售日期');
        $objActSheet ->setCellValue('F'.$num, '销售数量');
        $objActSheet ->setCellValue('G'.$num, '销售单价');
        $objActSheet ->setCellValue('H'.$num, '销售金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, ' ' .$val['order_sn']);
            $objActSheet ->setCellValue('E'.$num2, $val['create_time']);
            $objActSheet ->setCellValue('F'.$num2, $val['num']);
            $objActSheet ->setCellValue('G'.$num2, $val['price']);
            $objActSheet ->setCellValue('H'.$num2, number_format($val['num']*$val['price'],2));
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:H'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店每日消费金额**************************/
    public function pushSaleOrderMoneyList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '销售日期');
        $objActSheet ->setCellValue('B'.$num, '消费次数');
        $objActSheet ->setCellValue('C'.$num, '消费金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['showdata1']);
            $objActSheet ->setCellValue('C'.$num2, $val['showdata2']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出单店商品销量详情【按订单】**************************/
    public function pushSaleOrderList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '订单单号');
        $objActSheet ->setCellValue('B'.$num, '用户');
        $objActSheet ->setCellValue('C'.$num, '销售日期');
        $objActSheet ->setCellValue('D'.$num, '实付金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, ' ' .$val['order_sn']);
            $objActSheet ->setCellValue('B'.$num2, get_nickname($val['uid']));
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['pay_money']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出用户分析~活跃用户**************************/
    public function pushMemberDateList($data,$data2,$title,$title2,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        //第一行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');//合并单元格
        $objActSheet ->setCellValue('A1', $title);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:B2');//合并单元格
        $objActSheet ->setCellValue('A2', $title2);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('D2:H2');//合并单元格
        $objActSheet ->setCellValue('D2', '活跃用户消费次数排行');
        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '活跃用户');
        $objActSheet ->setCellValue('D'.$num, '排名');
        $objActSheet ->setCellValue('E'.$num, '用户名');
        $objActSheet ->setCellValue('F'.$num, '消费金额');
        $objActSheet ->setCellValue('G'.$num, '消费次数');
        $objActSheet ->setCellValue('H'.$num, '最爱');
        $num2 = 4;
        $num3 = 4;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['showdata']);
            $num2++;
        }
        $i = 0;
        foreach($data2 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('D'.$num3, $i);
            $objActSheet ->setCellValue('E'.$num3, get_nickname($val['uid']));
            $objActSheet ->setCellValue('F'.$num3, $val['buymoney']);
            $objActSheet ->setCellValue('G'.$num3, $val['counttime']);
            $objActSheet ->setCellValue('H'.$num3, $val['goods_name']);
            $num3++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(60);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('D2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("D2")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:B'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('D2:H'.(string)($num3-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出用户分析~用户消费**************************/
    public function pushMemberBuyList($data,$data2,$data3,$title,$title2,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        //第一行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');//合并单元格
        $objActSheet ->setCellValue('A1', $title);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');//合并单元格
        $objActSheet ->setCellValue('A2', $title2);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('E2:H2');//合并单元格
        $objActSheet ->setCellValue('E2', '商品销售排行');

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('J2:L2');//合并单元格
        $objActSheet ->setCellValue('J2', '分类销售排行');
        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '消费金额');
        $objActSheet ->setCellValue('C'.$num, '消费次数');

        $objActSheet ->setCellValue('E'.$num, '排行');
        $objActSheet ->setCellValue('F'.$num, '商品名');
        $objActSheet ->setCellValue('G'.$num, '分类名');
        $objActSheet ->setCellValue('H'.$num, '消费金额');

        $objActSheet ->setCellValue('J'.$num, '排行');
        $objActSheet ->setCellValue('K'.$num, '分类名');
        $objActSheet ->setCellValue('L'.$num, '消费金额');
        $num2 = 4;
        $num3 = 4;
        $num4 = 4;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['buymoney']);
            $objActSheet ->setCellValue('C'.$num2, $val['counttime']);
            $num2++;
        }
        $i = 0;
        foreach($data2 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('E'.$num3, $i);
            $objActSheet ->setCellValue('F'.$num3, $val['goods_name']);
            $objActSheet ->setCellValue('G'.$num3, $val['cate_name']);
            $objActSheet ->setCellValue('H'.$num3, $val['buymoney']);
            $num3++;
        }
        $i = 0;
        foreach($data3 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('J'.$num4, $i);
            $objActSheet ->setCellValue('K'.$num4, $val['cate_name']);
            $objActSheet ->setCellValue('L'.$num4, $val['buymoney']);
            $num4++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(60);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('E2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("E2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('J2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("J2")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('E2:H'.(string)($num3-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('J2:L'.(string)($num4-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出销售分析~商品销售趋势**************************/
    public function pushGoodsBuyList($data,$data2,$data3,$title,$title2,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        //第一行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        $objActSheet ->setCellValue('A1', $title);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');//合并单元格
        $objActSheet ->setCellValue('A2', $title2);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('E2:K2');//合并单元格
        $objActSheet ->setCellValue('E2', '商品销售排行');

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('M2:O2');//合并单元格
        $objActSheet ->setCellValue('M2', '分类销售排行');
        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '销售金额');
        $objActSheet ->setCellValue('C'.$num, '销售数量');

        $objActSheet ->setCellValue('E'.$num, '排行');
        $objActSheet ->setCellValue('F'.$num, '商品名');
        $objActSheet ->setCellValue('G'.$num, '分类名');
        $objActSheet ->setCellValue('H'.$num, '销售数量');
        $objActSheet ->setCellValue('I'.$num, '销售金额');
        $objActSheet ->setCellValue('J'.$num, '日均销量');
        $objActSheet ->setCellValue('K'.$num, '日均销售额');

        $objActSheet ->setCellValue('M'.$num, '排行');
        $objActSheet ->setCellValue('N'.$num, '分类名');
        $objActSheet ->setCellValue('O'.$num, '销售金额');
        $num2 = 4;
        $num3 = 4;
        $num4 = 4;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['buymoney']);
            $objActSheet ->setCellValue('C'.$num2, $val['counttime']);
            $num2++;
        }
        $i = 0;
        foreach($data2 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('E'.$num3, $i);
            $objActSheet ->setCellValue('F'.$num3, $val['goods_name']);
            $objActSheet ->setCellValue('G'.$num3, $val['cate_name']);
            $objActSheet ->setCellValue('H'.$num3, $val['buynum']);
            $objActSheet ->setCellValue('I'.$num3, $val['buymoney']);
            $objActSheet ->setCellValue('J'.$num3, $val['avgnum']);
            $objActSheet ->setCellValue('K'.$num3, $val['avgmoney']);
            $num3++;
        }
        $i = 0;
        foreach($data3 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('M'.$num4, $i);
            $objActSheet ->setCellValue('N'.$num4, $val['cate_name']);
            $objActSheet ->setCellValue('O'.$num4, $val['buymoney']);
            $num4++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(60);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(12);
        $objActSheet->getColumnDimension('N')->setWidth(12);
        $objActSheet->getColumnDimension('O')->setWidth(12);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('E2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("E2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('M2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("M2")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('E2:K'.(string)($num3-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('M2:O'.(string)($num4-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出销售分析~商品销售趋势**************************/
    public function pushGoodsSaleList($data,$data1,$data2,$data3,$data4,$title,$title2,$title3,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        //第一行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');//合并单元格
        $objActSheet ->setCellValue('A1', $title);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:B2');//合并单元格
        $objActSheet ->setCellValue('A2', $title2);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('D2:F2');//合并单元格
        $objActSheet ->setCellValue('D2', $title3);

        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '时间');
        $objActSheet ->setCellValue('B'.$num, '销售金额');

        //数据标题
        $objActSheet ->setCellValue('D'.$num, '日期');
        $objActSheet ->setCellValue('E'.$num, '工作日销售金额');
        $objActSheet ->setCellValue('F'.$num, '周六日销售金额');

        $num2 = 4;
        $num3 = 4;
        $num4 = 4;
        $num5 = 4;
        $num6 = 4;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val);
            $num2++;
        }
        foreach($data1 as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('B'.$num3, $val);
            $num3++;
        }
        foreach($data2 as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('D'.$num4, $val);
            $num4++;
        }
        foreach($data3 as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('E'.$num5, $val);
            $num5++;
        }
        foreach($data4 as $key => $val){
            $objActSheet ->setCellValue('F'.$num6, $val);
            $num6++;
        }

        $objActSheet->getColumnDimension('A')->setWidth(18);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(18);
        $objActSheet->getColumnDimension('C')->setWidth(18);
        $objActSheet->getColumnDimension('D')->setWidth(18);
        $objActSheet->getColumnDimension('E')->setWidth(18);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('D2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("D2")->getFont()->setSize(18);//标题字体大小

        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:B'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('D2:F'.(string)($num4-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出全部商品销售**************************/
    public function pushAllSale($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品id');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '所属类别');
        $objActSheet ->setCellValue('D'.$num, '总销售数量');
        $objActSheet ->setCellValue('E'.$num, '总销售金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['sum_num']);
            $objActSheet ->setCellValue('E'.$num2, $val['sum_amount']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(30);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(30);
        $objActSheet->getColumnDimension('E')->setWidth(30);
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(   array(
                'font' => array (
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:E'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
}