<?php

namespace Addons\Report\Model;
use Think\Model;

class ReportModel extends Model{
    /*******************导出每日目标**************************/
    public function pushStoreOrderExcel($data,$title,$fname,$s_date_ymd,$e_date_ymd,$s_date_ymd1,$e_date_ymd1,$pre_s_date_ymd,$pre_e_date_ymd,$pre_s_date_ymd1,$pre_e_date_ymd1,$store_select,$pre,$add_order,$add_money){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num2 = 2;
        date_default_timezone_set("PRC");
        $objActSheet ->setCellValue('A'.$num2, '搜索条件：');
        $objActSheet ->setCellValue('B'.$num2, '门店列表：');
        $objActSheet ->setCellValue('C'.$num2, $store_select);
        $num2++;
        $objActSheet ->setCellValue('A'.$num2, '时间区间：');
        $objActSheet ->setCellValue('B'.$num2, $s_date_ymd . '~' .$e_date_ymd);
        $num2++;
        $objActSheet ->setCellValue('A'.$num2, '时间段：');
        if($pre == 'm'){
            $prestr = '上个月';
        }
        if($pre == 'w'){
            $prestr = '上一周';
        }
        if($pre == 't'){
            $prestr = '上个季度';
        }
        if($pre == 'y'){
            $prestr = '上一年';
        }
        $objActSheet ->setCellValue('B'.$num2, $prestr);
        $num2++;
        $objActSheet ->setCellValue('A'.$num2, '预增目标：');
        $objActSheet ->setCellValue('B'.$num2, '预增订单数' .$add_order);
        $objActSheet ->setCellValue('C'.$num2, '预增订单金额' .$add_money);
        $i = 1;
        $num2++;
        $num = $num2 + 1;
        $objPHPExcel->getActiveSheet()->mergeCells('A'.$num2.':A' .$num);
        $objPHPExcel->getActiveSheet()->mergeCells('B'.$num2.':B' .$num);
        $objPHPExcel->getActiveSheet()->mergeCells('C'.$num2.':C' .$num);
        $objPHPExcel->getActiveSheet()->mergeCells('D'.$num2.':D' .$num);
        $objPHPExcel->getActiveSheet()->mergeCells('E'.$num2.':E' .$num);
        $objActSheet ->setCellValue('A'.$num2, '门店id');
        $objActSheet ->setCellValue('B'.$num2, '门店名称');
        $objActSheet ->setCellValue('C'.$num2, '门店管理员');
        $objActSheet ->setCellValue('D'.$num2, '预增订单数');
        $objActSheet ->setCellValue('E'.$num2, '预增订单金额');
        $objPHPExcel->getActiveSheet()->mergeCells('F'.$num2.':I' .$num2);
        $objActSheet ->setCellValue('F'.$num2, '上一期目标完成情况【' .$pre_s_date_ymd1  .'~' .$pre_e_date_ymd1 .'】-【' .$s_date_ymd1 .'~' .$e_date_ymd1 .'】');
        $objPHPExcel->getActiveSheet()->mergeCells('J'.$num2.':M' .$num2);
        $objActSheet ->setCellValue('J'.$num2, '本期目标完成情况【' .$pre_s_date_ymd .'~' .$pre_e_date_ymd .'】-【' .$s_date_ymd .'~' .$e_date_ymd .'】');
        $objActSheet ->setCellValue('F'.$num, '上期订单数');
        $objActSheet ->setCellValue('G'.$num, '当期订单数');
        $objActSheet ->setCellValue('H'.$num, '上期销售额');
        $objActSheet ->setCellValue('I'.$num, '当期销售额');
        $objActSheet ->setCellValue('J'.$num, '上期订单数');
        $objActSheet ->setCellValue('K'.$num, '本期目标订单数');
        $objActSheet ->setCellValue('L'.$num, '上期销售额');
        $objActSheet ->setCellValue('M'.$num, '本期目标销售额');
        $num2++;
        $num2++;
        foreach($data as $key => $val){
            $objActSheet ->setCellValue('A'.$num2, $val['id']);
            $objActSheet ->setCellValue('B'.$num2, $val['title']);
            $objActSheet ->setCellValue('C'.$num2, implode(chr(10),$val['member']));
            $objActSheet ->setCellValue('D'.$num2, $val['s1']);
            $objActSheet ->setCellValue('E'.$num2, $val['m1']);
            $objActSheet ->setCellValue('F'.$num2, $val['sn_num3']);
            $objActSheet ->setCellValue('G'.$num2, $val['sn_num1']);
            $objActSheet ->setCellValue('H'.$num2, $val['money_amount3']);
            $objActSheet ->setCellValue('I'.$num2, $val['money_amount1']);
            $objActSheet ->setCellValue('J'.$num2, $val['sn_num2']);
            $objActSheet ->setCellValue('K'.$num2, $val['sn_num2'] + $val['s1']);
            //$objActSheet ->setCellValue('K'.$num2, $val['sn_num']);
            $objActSheet ->setCellValue('L'.$num2, $val['money_amount2']);
            $objActSheet ->setCellValue('M'.$num2, $val['money_amount2']+$val['m1']);
            //$objActSheet ->setCellValue('M'.$num2, $val['money_amount']);
            $i++;
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(36);
        $objActSheet->getColumnDimension('C')->setWidth(18);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        $objActSheet->getColumnDimension('G')->setWidth(18);
        $objActSheet->getColumnDimension('H')->setWidth(18);
        $objActSheet->getColumnDimension('I')->setWidth(18);
        $objActSheet->getColumnDimension('J')->setWidth(18);
        $objActSheet->getColumnDimension('K')->setWidth(18);
        $objActSheet->getColumnDimension('L')->setWidth(18);
        $objActSheet->getColumnDimension('M')->setWidth(18);
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
        $objPHPExcel->getActiveSheet()->getStyle('A6:M'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        //$objWriter->save($fname);
        //return $fname;
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出门店销售对比**************************/
    public function pushStoreSaleList($data,$title,$fname,$sumqty_day,$sumamount_day,$sumsn_qty_day,$count_uid_qty_day){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num2 = 2;
        date_default_timezone_set("PRC");
        $objActSheet ->setCellValue('A'.$num2, '总计');
        $objActSheet ->setCellValue('B'.$num2, $sumqty_day);
        $objActSheet ->setCellValue('C'.$num2, $sumamount_day);
        $objActSheet ->setCellValue('D'.$num2, $sumsn_qty_day);
        $objActSheet ->setCellValue('E'.$num2, $count_uid_qty_day);
        $i = 1;
        $num2++;
        foreach($data as $key => $val){
            $objActSheet ->setCellValue('A'.$num2, '门店');
            $objActSheet ->setCellValue('B'.$num2, '销量');
            $objActSheet ->setCellValue('C'.$num2, '金额');
            $objActSheet ->setCellValue('D'.$num2, '订单数');
            $objActSheet ->setCellValue('E'.$num2, '活跃用户');
            $num2 ++ ;
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['title']);
            $objActSheet ->setCellValue('B'.$num2, $val['num']);
            $objActSheet ->setCellValue('C'.$num2, $val['amount']);
            $objActSheet ->setCellValue('D'.$num2, $val['sn']);
            $objActSheet ->setCellValue('E'.$num2, $val['count_uid']);
            $num2 ++ ;
            if(is_array($val['goodschild']) && count($val['goodschild'])>0){
                $objActSheet ->setCellValue('A'.$num2, '');
                $objActSheet ->setCellValue('B'.$num2, '商品销售排名');
                $objActSheet ->setCellValue('C'.$num2, '商品名');
                $objActSheet ->setCellValue('D'.$num2, '分类名');
                $objActSheet ->setCellValue('E'.$num2, '销售数量');
                $objActSheet ->setCellValue('F'.$num2, '销售金额');
                $objActSheet ->setCellValue('G'.$num2, '日均销量');
                $objActSheet ->setCellValue('H'.$num2, '日均销售额');
                $num2 ++ ;
                for($j=0;$j<count($val['goodschild']);$j++){
                    //写入数据
                    $objActSheet ->setCellValue('A'.$num2, '');
                    $objActSheet ->setCellValue('B'.$num2, $j+1);
                    $objActSheet ->setCellValue('C'.$num2, $val['goodschild'][$j]['goods_name']);
                    $objActSheet ->setCellValue('D'.$num2, $val['goodschild'][$j]['cate_name']);
                    $objActSheet ->setCellValue('E'.$num2, $val['goodschild'][$j]['buynum']);
                    $objActSheet ->setCellValue('F'.$num2, $val['goodschild'][$j]['buymoney']);
                    $objActSheet ->setCellValue('G'.$num2, $val['goodschild'][$j]['avgnum']);
                    $objActSheet ->setCellValue('H'.$num2, $val['goodschild'][$j]['avgmoney']);
                    $num2 ++ ;
                }
            }
            if(is_array($val['catechild']) && count($val['catechild'])>0){
                $objActSheet ->setCellValue('A'.$num2, '');
                $objActSheet ->setCellValue('B'.$num2, '类别销售排名');
                $objActSheet ->setCellValue('C'.$num2, '分类名');
                $objActSheet ->setCellValue('D'.$num2, '销售数量');
                $objActSheet ->setCellValue('E'.$num2, '销售金额');
                $objActSheet ->setCellValue('F'.$num2, '日均销量');
                $objActSheet ->setCellValue('G'.$num2, '日均销售额');
                $num2 ++ ;
                for($j=0;$j<count($val['catechild']);$j++){
                    //写入数据
                    $objActSheet ->setCellValue('A'.$num2, '');
                    $objActSheet ->setCellValue('B'.$num2, $j+1);
                    $objActSheet ->setCellValue('C'.$num2, $val['catechild'][$j]['cate_name']);
                    $objActSheet ->setCellValue('D'.$num2, $val['catechild'][$j]['buynum']);
                    $objActSheet ->setCellValue('E'.$num2, $val['catechild'][$j]['buymoney']);
                    $objActSheet ->setCellValue('F'.$num2, $val['catechild'][$j]['avgnum']);
                    $objActSheet ->setCellValue('G'.$num2, $val['catechild'][$j]['avgmoney']);
                    $num2 ++ ;
                }
            }
        }
        $objActSheet->getColumnDimension('A')->setWidth(36);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(24);
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
        //$objWriter->save($fname);
        //return $fname;
        $objWriter->save('php://output');//输出
        die;
    }
    
    /*******************导出门店销售对比**************************/
    public function pushMemberDateList_whole_country($data,$title,$fname,$title1,$title2){
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        //第一行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');//合并单元格
        $objActSheet ->setCellValue('A1', $title);
        
        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:E2');//合并单元格
        $objActSheet ->setCellValue('A2', $title1);
        
        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('G2:L2');//合并单元格
        $objActSheet ->setCellValue('G2', $title2);
        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '活跃用户');
        $objActSheet ->setCellValue('C'.$num, '消费次数');
        $objActSheet ->setCellValue('D'.$num, '销售数量');
        $objActSheet ->setCellValue('E'.$num, '消费金额');
        $objActSheet ->setCellValue('G'.$num, '排名');
        $objActSheet ->setCellValue('H'.$num, '用户名');
        $objActSheet ->setCellValue('I'.$num, '消费门店');
        $objActSheet ->setCellValue('J'.$num, '消费金额');
        $objActSheet ->setCellValue('K'.$num, '消费次数');
        $objActSheet ->setCellValue('L'.$num, '最爱');
        $num2 = 4;
        $num3 = 4;
        date_default_timezone_set("PRC");
        foreach($data['data']['time'] as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val);
            $objActSheet ->setCellValue('B'.$num2, $data['data']['data']['count'][$key]);
            $objActSheet ->setCellValue('C'.$num2, $data['data']['data']['money_count'][$key]);
            $objActSheet ->setCellValue('D'.$num2, $data['data']['data']['num'][$key]);
            $objActSheet ->setCellValue('E'.$num2, $data['data']['data']['money_sum'][$key]);
            $num2++;
        }
        $i = 0;
        foreach($data['info'] as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('G'.$num3, $i);
            $objActSheet ->setCellValue('H'.$num3, get_nickname_jinjiang($val['uid'],$val['pay_type']));
            $objActSheet ->setCellValue('I'.$num3, get_order_store_name_store($val['store_name']));
            $objActSheet ->setCellValue('J'.$num3, $val['order_money']);
            $objActSheet ->setCellValue('K'.$num3, $val['order_count']);
            $objActSheet ->setCellValue('L'.$num3, $val['title']);
            $num3++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(50);
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
        
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("G2")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:E'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('G2:L'.(string)($num3-1))->applyFromArray($styleArray);//应用边框
        
        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    
    /*******************导出门店销售对比详情**************************/
    public function pushStoreSaleView($data,$datacate,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:G1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $num2 = 2;
        date_default_timezone_set("PRC");
        $objPHPExcel->getActiveSheet()->mergeCells('A' .$num2. ':F' .$num2);//合并单元格
        $objActSheet ->setCellValue('A' .$num2, '类别销售排行');
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A' .$num2)->applyFromArray(   array(
                'font' => array (
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A' .$num2)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A' .$num2)->getFont()->setSize(16);//标题字体大小
		$num2++;
		
		$objActSheet ->setCellValue('A'.$num2, '类别销售排名');
		$objActSheet ->setCellValue('B'.$num2, '分类名');
		$objActSheet ->setCellValue('C'.$num2, '销售数量');
		$objActSheet ->setCellValue('D'.$num2, '销售金额');
		$objActSheet ->setCellValue('E'.$num2, '日均销量');
		$objActSheet ->setCellValue('F'.$num2, '日均销售额');
		$num2++;
        $i = 1;
        foreach($datacate as $key => $val){
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['buynum']);
            $objActSheet ->setCellValue('D'.$num2, $val['buymoney']);
            $objActSheet ->setCellValue('E'.$num2, $val['avgnum']);
            $objActSheet ->setCellValue('F'.$num2, $val['avgmoney']);
			$i++;
			$num2++;
		}
		$n1 = $num2;
        $objPHPExcel->getActiveSheet()->mergeCells('A' .$num2. ':G' .$num2);//合并单元格
        $objActSheet ->setCellValue('A' .$num2, '商品销售排行');
        //标题加粗，水平垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A' .$num2)->applyFromArray(   array(
                'font' => array (
                    'bold' => true//加粗
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER//水平居中
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A' .$num2)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A' .$num2)->getFont()->setSize(16);//标题字体大小
		$num2++;
		$objActSheet ->setCellValue('A'.$num2, '商品销售排名');
		$objActSheet ->setCellValue('B'.$num2, '商品名');
		$objActSheet ->setCellValue('C'.$num2, '分类名');
		$objActSheet ->setCellValue('D'.$num2, '销售数量');
		$objActSheet ->setCellValue('E'.$num2, '销售金额');
		$objActSheet ->setCellValue('F'.$num2, '日均销量');
		$objActSheet ->setCellValue('G'.$num2, '日均销售额');
		$num2++;
        $i = 1;
        foreach($data as $key => $val){
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['buynum']);
            $objActSheet ->setCellValue('E'.$num2, $val['buymoney']);
            $objActSheet ->setCellValue('F'.$num2, $val['avgnum']);
            $objActSheet ->setCellValue('G'.$num2, $val['avgmoney']);
			$i++;
			$num2++;
		}
        $styleArray = array(

            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A3:F'.(string)($n1-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('A' .(string)($n1+1). ':G'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
		
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(24);
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

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        //$objWriter->save($fname);
        //return $fname;
        $objWriter->save('php://output');//输出
        die;
    }
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        //第一行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:P1');//合并单元格
        $objActSheet ->setCellValue('A1', $title);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:D2');//合并单元格
        $objActSheet ->setCellValue('A2', $title2);

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('F2:L2');//合并单元格
        $objActSheet ->setCellValue('F2', '商品销售排行');

        //第二行>>>报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('N2:P2');//合并单元格
        $objActSheet ->setCellValue('N2', '分类销售排行');
        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '日期');
        $objActSheet ->setCellValue('B'.$num, '销售金额');
        $objActSheet ->setCellValue('C'.$num, '销售数量');
        $objActSheet ->setCellValue('D'.$num, '订单数量');

        $objActSheet ->setCellValue('F'.$num, '排行');
        $objActSheet ->setCellValue('G'.$num, '商品名');
        $objActSheet ->setCellValue('H'.$num, '分类名');
        $objActSheet ->setCellValue('I'.$num, '销售数量');
        $objActSheet ->setCellValue('J'.$num, '销售金额');
        $objActSheet ->setCellValue('K'.$num, '日均销量');
        $objActSheet ->setCellValue('L'.$num, '日均销售额');

        $objActSheet ->setCellValue('N'.$num, '排行');
        $objActSheet ->setCellValue('O'.$num, '分类名');
        $objActSheet ->setCellValue('P'.$num, '销售金额');
        $num2 = 4;
        $num3 = 4;
        $num4 = 4;
        date_default_timezone_set("PRC");
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('B'.$num2, $val['buymoney']);
            $objActSheet ->setCellValue('C'.$num2, $val['counttime']);
            $objActSheet ->setCellValue('D'.$num2, $val['cid']);
            $num2++;
        }
        $i = 0;
        foreach($data2 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('F'.$num3, $i);
            $objActSheet ->setCellValue('G'.$num3, $val['goods_name']);
            $objActSheet ->setCellValue('H'.$num3, $val['cate_name']);
            $objActSheet ->setCellValue('I'.$num3, $val['buynum']);
            $objActSheet ->setCellValue('J'.$num3, $val['buymoney']);
            $objActSheet ->setCellValue('K'.$num3, $val['avgnum']);
            $objActSheet ->setCellValue('L'.$num3, $val['avgmoney']);
            $num3++;
        }
        $i = 0;
        foreach($data3 as $key => $val){
            $i++;
            //写入数据
            $objActSheet ->setCellValue('N'.$num4, $i);
            $objActSheet ->setCellValue('O'.$num4, $val['cate_name']);
            $objActSheet ->setCellValue('P'.$num4, $val['buymoney']);
            $num4++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(60);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(12);
        $objActSheet->getColumnDimension('N')->setWidth(12);
        $objActSheet->getColumnDimension('O')->setWidth(12);
        $objActSheet->getColumnDimension('P')->setWidth(12);
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

        $objPHPExcel->getActiveSheet()->getStyle('F2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("F2")->getFont()->setSize(18);//标题字体大小

        $objPHPExcel->getActiveSheet()->getStyle('N2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("N2")->getFont()->setSize(18);//标题字体大小
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:D'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('F2:L'.(string)($num3-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('N2:P'.(string)($num4-1))->applyFromArray($styleArray);//应用边框

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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
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
    /*******************导出采购申请单【列表】**************************/
    public function pushWarehousePurchaseList($data,$title,$fname){
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '采购申请单号');
        $objActSheet ->setCellValue('C'.$num, '申请日期');
        $objActSheet ->setCellValue('D'.$num, '申请种类');
        $objActSheet ->setCellValue('E'.$num, '申请数量');
        $objActSheet ->setCellValue('F'.$num, '申请人');
        $objActSheet ->setCellValue('G'.$num, '申请仓库');
        $objActSheet ->setCellValue('H'.$num, '售价金额');
        $objActSheet ->setCellValue('I'.$num, '申请结果');
        $objActSheet ->setCellValue('J'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['p_r_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['g_amounts']);
            if($val['is_pass'] == 0){
                $passstr = '新增';
            }else{
                if($val['is_pass'] == $val['g_type']){
                    $passstr = '全部拒绝';
                }else{
                    if($val['is_pass'] == $val['g_type'] * 2){
                        $passstr = '全部通过';
                    }else{
                        $passstr = '部分处理';
                    }

                }
            }
            $objActSheet ->setCellValue('I'.$num2, $passstr);
            $objActSheet ->setCellValue('J'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(24);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:J'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出采购申请单【查看】**************************/
    public function pushWarehousePurchaseView($list,$data,$title,$fname){
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '申请单号');
        $objActSheet ->setCellValue('B2', $list['p_r_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '申请人');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '申请来源');
        $objActSheet ->setCellValue('E4', $list['w_name']);
        $objActSheet ->setCellValue('G4', '备注');
        $objActSheet ->setCellValue('H4', $list['remark']);
        $num=5;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '商品ID');
        $objActSheet ->setCellValue('C'.$num, '商品名称');
        $objActSheet ->setCellValue('D'.$num, '商品类别');
        $objActSheet ->setCellValue('E'.$num, '商品属性');
        $objActSheet ->setCellValue('F'.$num, '商品条码');
        $objActSheet ->setCellValue('G'.$num, '系统售价');
        $objActSheet ->setCellValue('H'.$num, '库存数量');
        $objActSheet ->setCellValue('I'.$num, '申请数量');
        $objActSheet ->setCellValue('J'.$num, '申请结果');
        $num2 = 6;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('C'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('E'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('F'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('G'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('H'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_num']);
            if($val['is_pass'] == 0){
                $passstr = '新增';
            }else{
                if($val['is_pass'] == 1){
                    $passstr = '拒绝';
                }else{
                    if($val['is_pass'] == 2){
                        $passstr = '通过';
                    }else{
                        $passstr = '其它';
                    }

                }
            }
            $objActSheet ->setCellValue('J'.$num2, $passstr);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A5:I'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出全部采购申请单【列表】**************************/
    public function pushAllWarehousePurchaseList($data,$title,$fname){
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品属性');
        $objActSheet ->setCellValue('D'.$num, '商品条码');
        $objActSheet ->setCellValue('E'.$num, '申请单号');
        $objActSheet ->setCellValue('F'.$num, '申请数量');
        $objActSheet ->setCellValue('G'.$num, '申请时间');
        $objActSheet ->setCellValue('H'.$num, '申请来源');
        $objActSheet ->setCellValue('I'.$num, '申请人');
        $objActSheet ->setCellValue('J'.$num, '备注');
        $objActSheet ->setCellValue('K'.$num, '历史供应商');
        $objActSheet ->setCellValue('L'.$num, '历史采购价');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key1 => $val1){
            foreach($val1['data'] as $key2 => $val) {
                //写入数据
                $objActSheet->setCellValue('A' . $num2, $val['goods_id']);
                $objActSheet->setCellValue('B' . $num2, $val['goods_name']);
                $objActSheet->setCellValue('C' . $num2, $val['value_name']);
                $objActSheet->setCellValue('D' . $num2, ' ' .$val['bar_code']);
                $objActSheet->setCellValue('E' . $num2, $val['p_r_sn']);
                $objActSheet->setCellValue('F' . $num2, $val['g_num']);
                $objActSheet->setCellValue('G' . $num2, $val['ctime']);
                $objActSheet->setCellValue('H' . $num2, $val['w_name'] .'/' .$val['store_name']);
                $objActSheet->setCellValue('I' . $num2, $val['nickname']);
                $objActSheet->setCellValue('J' . $num2, $val['remark_detail']);
                $objActSheet->setCellValue('K' . $num2, $val['s_name']);
                $objActSheet->setCellValue('L' . $num2, $val['g_price']);
                $num2++;
                $i++;
            }
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(24);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(24);
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
    /*******************导出全部采购申请单自动分单【列表】**************************/
    public function pushAllWarehousePurchaseListAuto($data,$title,$fname){
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品属性');
        $objActSheet ->setCellValue('D'.$num, '商品条码');
        $objActSheet ->setCellValue('E'.$num, '申请单号');
        $objActSheet ->setCellValue('F'.$num, '申请数量');
        $objActSheet ->setCellValue('G'.$num, '申请时间');
        $objActSheet ->setCellValue('H'.$num, '申请来源');
        $objActSheet ->setCellValue('I'.$num, '申请人');
        $objActSheet ->setCellValue('J'.$num, '备注');
        $objActSheet ->setCellValue('K'.$num, '历史供应商');
        $objActSheet ->setCellValue('L'.$num, '历史采购价');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key1 => $val1){
            $objActSheet->setCellValue('A' . $num2, '申请仓库/门店');
            $objActSheet->setCellValue('B' . $num2, $val1['w_name'] . '/' .$val1['store_name']);
            $objActSheet->setCellValue('C' . $num2, '供应商');
            $objActSheet->setCellValue('D' . $num2, $val1['s_name']);
            $num2++;
            $num3 = $num2;
            foreach($val1['data'] as $key2 => $val) {
                //写入数据
                $objActSheet->setCellValue('A' . $num2, $val['goods_id']);
                $objActSheet->setCellValue('B' . $num2, $val['goods_name']);
                $objActSheet->setCellValue('C' . $num2, $val['value_name']);
                $objActSheet->setCellValue('D' . $num2, ' ' .$val['bar_code']);
                $objActSheet->setCellValue('E' . $num2, $val['p_r_sn']);
                $objActSheet->setCellValue('F' . $num2, $val['g_num']);
                $objActSheet->setCellValue('G' . $num2, $val['ctime']);
                $objActSheet->setCellValue('H' . $num2, $val['w_name'] .'/' .$val['store_name']);
                $objActSheet->setCellValue('I' . $num2, $val['nickname']);
                $objActSheet->setCellValue('J' . $num2, $val['remark_detail']);
                $objActSheet->setCellValue('K' . $num2, $val['s_name']);
                $objActSheet->setCellValue('L' . $num2, $val['g_price']);
                $num2++;
                $i++;
            }
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                    ),
                ),
            );
            $objPHPExcel->getActiveSheet()->getStyle('A' .$num3. ':K'.(string)($num2-1))->applyFromArray($styleArray);//应用边框
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(24);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(24);
        $objActSheet->getColumnDimension('J')->setWidth(24);
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

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出采购询价单【列表】**************************/
    public function pushPurchaseInquiryList($data,$title,$fname){
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
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '采购询价单号');
        $objActSheet ->setCellValue('C'.$num, '询价日期');
        $objActSheet ->setCellValue('D'.$num, '商品种类');
        $objActSheet ->setCellValue('E'.$num, '商品数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货仓库/门店');
        $objActSheet ->setCellValue('H'.$num, '供应商');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '采购金额');
        $objActSheet ->setCellValue('K'.$num, '单据状态');
        $objActSheet ->setCellValue('L'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['p_s_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name'] .$val['store_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['s_name']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            $objActSheet ->setCellValue('J'.$num2, $val['g_s_amounts']);
            switch ($val['p_s_status'])
            {
                case 0:
                    $strStatus = "新增";
                    if($val['nickname'] != ''){
                        $strStatus .= '/' .$val['nickname'];
                    }
                    break;
                case 1:
                    $strStatus = "已审核";
                    if($val['pnickname'] != ''){
                        $strStatus .= '/' .$val['pnickname'];
                    }
                    break;
                case 2:
                    $strStatus = "已作废";
                    if($val['pnickname'] != ''){
                        $strStatus .= '/' .$val['pnickname'];
                    }
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('K'.$num2, $strStatus);
            $objActSheet ->setCellValue('L'.$num2, $val['remark']);
            $num2++;
            $i++;
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
        $objActSheet->getColumnDimension('K')->setWidth(24);
        $objActSheet->getColumnDimension('L')->setWidth(24);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:L'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出采购询价单【查看】**************************/
    public function pushPurchaseInquiryView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:Q1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '询价单号');
        $objActSheet ->setCellValue('B2', $list['p_s_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('G2', '报价金额');
        $objActSheet ->setCellValue('H2', $list['g_s_amounts']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '收货仓库');
        $objActSheet ->setCellValue('E4', $list['w_name'] .'/' .$list['store_name']);
        $objActSheet ->setCellValue('G4', '供应商');
        $objActSheet ->setCellValue('H4', $list['s_name']);

        if($list['pnickname'] != ''){
            $objActSheet ->setCellValue('A5', '审核人');
            $objActSheet ->setCellValue('B5', $list['pnickname']);
            $objActSheet ->setCellValue('D5', '审核时间');
            $objActSheet ->setCellValue('E5', $list['ptime']);
        }
        switch ($list['p_s_status'])
        {
            case 0:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '已审核');
                break;
            case 2:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '已作废');
                break;
            default:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '其它');
                break;
        }
        $objActSheet ->setCellValue('A6', '备注');
        $objActSheet ->setCellValue('B6', $list['remark']);
        $num=7;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '申请单号');
        $objActSheet ->setCellValue('G'.$num, '申请来源');
        $objActSheet ->setCellValue('H'.$num, '申请人');
        $objActSheet ->setCellValue('I'.$num, '商品备注');
        $objActSheet ->setCellValue('J'.$num, '系统售价');
        $objActSheet ->setCellValue('K'.$num, '库存数量');
        $objActSheet ->setCellValue('L'.$num, '箱规');
        $objActSheet ->setCellValue('M'.$num, '采购箱数');
        $objActSheet ->setCellValue('N'.$num, '每箱价格');
        $objActSheet ->setCellValue('O'.$num, '上次采购价');
        $objActSheet ->setCellValue('P'.$num, '申请数量');
        $objActSheet ->setCellValue('Q'.$num, '采购数量');
        $objActSheet ->setCellValue('R'.$num, '供应商报价');
        $num2 = 8;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['p_r_sn']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['lnickname']);
            $objActSheet ->setCellValue('I'.$num2, $val['remark']);
            $objActSheet ->setCellValue('J'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('K'.$num2, $val['stock_num']);

            $objActSheet ->setCellValue('L'.$num2, $val['b_n_num']);
            $objActSheet ->setCellValue('M'.$num2, $val['b_num']);
            $objActSheet ->setCellValue('N'.$num2, $val['b_price']);

            $objActSheet ->setCellValue('O'.$num2, $val['last_price']);
            $objActSheet ->setCellValue('P'.$num2, $val['s_num']);
            $objActSheet ->setCellValue('Q'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('R'.$num2, $val['g_price']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(18);
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
        $objPHPExcel->getActiveSheet()->getStyle('A7:Q'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出采购单【列表】**************************/
    public function pushPurchaseList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '采购单号');
        $objActSheet ->setCellValue('C'.$num, '采购日期');
        $objActSheet ->setCellValue('D'.$num, '商品种类');
        $objActSheet ->setCellValue('E'.$num, '商品数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货门店/仓库');
        $objActSheet ->setCellValue('H'.$num, '供应商');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '采购金额');
        $objActSheet ->setCellValue('K'.$num, '询价单');
        $objActSheet ->setCellValue('L'.$num, '采购结果');
        $objActSheet ->setCellValue('M'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['p_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            if($val['w_name']!= ''){
                $showname = $val['w_name'];
            }else{
                $showname = $val['store_name'];
            }
            $objActSheet ->setCellValue('G'.$num2, $showname);
            $objActSheet ->setCellValue('H'.$num2, $val['s_name']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            $objActSheet ->setCellValue('J'.$num2, $val['p_amounts']);
            $objActSheet ->setCellValue('K'.$num2, $val['p_s_sn']);
            switch ($val['p_status'])
            {
                case 0:
                    $strStatus = "新增";
                    if($val['nickname'] != ''){
                        $strStatus .= '/' .$val['nickname'];
                    }
                    break;
                case 1:
                    $strStatus = "已审核";
                    if($val['pnickname'] != ''){
                        $strStatus .= '/' .$val['pnickname'];
                    }
                    if($val['pnickname'] != ''){
                        $strStatus .=  '/' .$val['w_in_sn'] .$val['s_in_sn'] .'/验收:' .$val['win_nums'] .$val['sin_nums'] .'/退货:' .$val['wout_num'] .$val['sout_num'];
                    }
                    if($val['w_in_status'] == 1 || $val['s_in_status'] == 1){
                        $strStatus .= '已验收';
                    }
                    if($val['w_in_status'] == 3 || $val['s_in_status'] == 3){
                        $strStatus .= '部分验收，部分退货';
                    }
                    break;
                case 2:
                    $strStatus = "已作废";
                    if($val['pnickname'] != ''){
                        $strStatus .= '/' .$val['pnickname'];
                    }
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('L'.$num2, $strStatus);
            $objActSheet ->setCellValue('M'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(16);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(36);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(18);
        $objActSheet->getColumnDimension('L')->setWidth(60);
        $objActSheet->getColumnDimension('M')->setWidth(24);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:M'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出采购单【查看】**************************/
    public function pushPurchaseView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:P1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '采购单号');
        $objActSheet ->setCellValue('B2', $list['p_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('G2', '采购金额');
        $objActSheet ->setCellValue('H2', $list['p_amounts']);
        $objActSheet ->setCellValue('J2', '退货金额');
        $objActSheet ->setCellValue('K2', $list['wout_amount'] .$list['sout_amount']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '收货仓库/门店');
        $objActSheet ->setCellValue('E4', $list['w_name'] .'/' .$list['store_name']);
        $objActSheet ->setCellValue('G4', '供应商');
        $objActSheet ->setCellValue('H4', $list['s_name']);

        if($list['pnickname'] != ''){
        }
        switch ($list['p_status'])
        {
            case 0:
                $objActSheet ->setCellValue('A5', '单据状态');
                $objActSheet ->setCellValue('B5', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A5', '审核人');
                $objActSheet ->setCellValue('B5', $list['pnickname']);
                $objActSheet ->setCellValue('D5', '审核时间');
                $objActSheet ->setCellValue('E5', $list['ptime']);
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '已审核');
                break;
            case 2:
                $objActSheet ->setCellValue('A5', '作废人');
                $objActSheet ->setCellValue('B5', $list['pnickname']);
                $objActSheet ->setCellValue('D5', '作废时间');
                $objActSheet ->setCellValue('E5', $list['ptime']);
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '已作废');
                break;
            default:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '其它');
                break;
        }
        $objActSheet ->setCellValue('A6', '备注');
        $objActSheet ->setCellValue('B6', $list['remark']);
        $num=7;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '库存数量');
        $objActSheet ->setCellValue('G'.$num, '系统售价');
        $objActSheet ->setCellValue('H'.$num, '历史价格');
        $objActSheet ->setCellValue('I'.$num, '箱规');
        $objActSheet ->setCellValue('J'.$num, '采购箱数');
        $objActSheet ->setCellValue('K'.$num, '每箱价格');
        $objActSheet ->setCellValue('L'.$num, '采购数量');
        $objActSheet ->setCellValue('M'.$num, '采购单价');
        $objActSheet ->setCellValue('N'.$num, '采购金额');
        $objActSheet ->setCellValue('O'.$num, '验收数量');
        $objActSheet ->setCellValue('P'.$num, '退货数量');
        $objActSheet ->setCellValue('Q'.$num, '商品备注');
        $num2 = 8;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('G'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('H'.$num2, $val['last_price']);
            $objActSheet ->setCellValue('I'.$num2, $val['b_n_num']);
            $objActSheet ->setCellValue('J'.$num2, $val['b_num']);
            $objActSheet ->setCellValue('K'.$num2, $val['b_price']);
            $objActSheet ->setCellValue('L'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('M'.$num2, $val['g_price']);
            $objActSheet ->setCellValue('N'.$num2, sprintf(" %1\$.2f",$val['b_num']*$val['b_price']));
            $objActSheet ->setCellValue('O'.$num2, $val['win_num'] . $val['sin_num']);
            $objActSheet ->setCellValue('P'.$num2, $val['wout_num'] . $val['sout_num']);
            $objActSheet ->setCellValue('Q'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A7:P'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出入库验收单【列表】**************************/
    public function pushWarehouseInList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '入库验收单号');
        $objActSheet ->setCellValue('C'.$num, '入库验收日期');
        $objActSheet ->setCellValue('D'.$num, '验收种类');
        $objActSheet ->setCellValue('E'.$num, '验收数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货仓库');
        $objActSheet ->setCellValue('H'.$num, '供应商');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '来源');
        $objActSheet ->setCellValue('K'.$num, '关联单号');
        $objActSheet ->setCellValue('L'.$num, '当前状态');
        $objActSheet ->setCellValue('M'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['w_in_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['s_name']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            switch ($val['w_in_type'])
            {
                case 0:
                    $strType = "采购单";
                    $strNo = $val['p_sn'];
                    break;
                case 1:
                    $strType = "店铺退货";
                    $strNo = $val['s_out_id'];
                    break;
                case 2:
                    $strType = "仓库调拨";
                    $strNo = $val['w_out_id'];
                    break;
                case 3:
                    $strType = "其它";
                    $strNo = $val['o_out_id'];
                    break;
                default:
                    $strType = '其它来源';
                    break;
            }
            $objActSheet ->setCellValue('J'.$num2, $strType);
            $objActSheet ->setCellValue('K'.$num2, $strNo);
            switch ($val['w_in_status'])
            {
                case 0:
                    $strStatus = "新增";
                    break;
                case 1:
                    $strStatus = "入库单：";
                    if($val['w_in_s_sn'] != ''){
                        $strStatus .= $val['w_in_s_sn'];
                    }
                    break;
                case 2:
                    $strStatus = "已退货：";
                    if($val['p_o_sn'] != ''){
                        $strStatus .= $val['p_o_sn'];
                    }
                    break;
                case 3:
                    $strStatus = "部分入库、部分退货：";
                    if($val['w_in_s_sn'] != ''){
                        $strStatus .= '入库单' .$val['w_in_s_sn'];
                    }
                    if($val['p_o_sn'] != ''){
                        $strStatus .= '退货单' .$val['p_o_sn'];
                    }
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('L'.$num2, $strStatus);
            $objActSheet ->setCellValue('M'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(24);
        $objActSheet->getColumnDimension('K')->setWidth(24);
        $objActSheet->getColumnDimension('L')->setWidth(24);
        $objActSheet->getColumnDimension('M')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:M'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出入库验收单【查看】**************************/
    public function pushWarehouseInView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '验收单号');
        $objActSheet ->setCellValue('B2', $list['p_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '收货仓库');
        $objActSheet ->setCellValue('E4', $list['w_name']);
        $objActSheet ->setCellValue('G4', '供应商');
        $objActSheet ->setCellValue('H4', $list['s_name']);

        switch ($list['w_in_status'])
        {
            case 0:
                $objActSheet ->setCellValue('A5', '单据状态');
                $objActSheet ->setCellValue('B5', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A5', '审核人');
                $objActSheet ->setCellValue('B5', $list['pnickname']);
                $objActSheet ->setCellValue('D5', '审核时间');
                $objActSheet ->setCellValue('E5', $list['ptime']);
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '入库单：' .$list['w_in_s_sn']);
                break;
            case 2:
                $objActSheet ->setCellValue('A5', '退货人');
                $objActSheet ->setCellValue('B5', $list['pnickname']);
                $objActSheet ->setCellValue('D5', '退货时间');
                $objActSheet ->setCellValue('E5', $list['ptime']);
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '已退货/' .$list['p_o_sn']);
                break;
            case 3:
                $objActSheet ->setCellValue('A5', '审核');
                $objActSheet ->setCellValue('B5', $list['pnickname']);
                $objActSheet ->setCellValue('D5', '审核时间');
                $objActSheet ->setCellValue('E5', $list['ptime']);
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '部分入库、部分退货/' .$list['w_in_s_sn'] .'/' .$list['p_o_sn']);
                break;
            default:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '其它');
                break;
        }
        $objActSheet ->setCellValue('A6', '备注');
        $objActSheet ->setCellValue('B6', $list['remark']);
        $num=7;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '售价金额');
        $objActSheet ->setCellValue('G'.$num, '库存数量');
        $objActSheet ->setCellValue('H'.$num, '箱规');
        $objActSheet ->setCellValue('I'.$num, '箱数');
        $objActSheet ->setCellValue('J'.$num, '申请数量');
        $objActSheet ->setCellValue('K'.$num, '验收数量');
        $objActSheet ->setCellValue('L'.$num, '退货数量');
        $objActSheet ->setCellValue('M'.$num, '缺货数量');
        $num2 = 8;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('G'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('H'.$num2, $val['b_n_num']);
            $objActSheet ->setCellValue('I'.$num2, $val['b_num']);
            $objActSheet ->setCellValue('J'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('K'.$num2, $val['in_num']);
            $objActSheet ->setCellValue('L'.$num2, $val['out_num']);
            $objActSheet ->setCellValue('M'.$num2, ($val['g_num']-$val['in_num']-$val['out_num']));
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
        $objActSheet->getColumnDimension('L')->setWidth(12);
        $objActSheet->getColumnDimension('M')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A7:L'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出广州区域门店库存和库存批次表对比【列表】**************************/
    public function pushDuibiList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        date_default_timezone_set("PRC");
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', '库存对比');
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '商品ID');
        $objActSheet ->setCellValue('C'.$num, '商品名');
        $objActSheet ->setCellValue('D'.$num, '门店库存合计');
        $objActSheet ->setCellValue('E'.$num, '广州仓库存');
        $objActSheet ->setCellValue('F'.$num, '库存批次表数量');
        $objActSheet ->setCellValue('G'.$num, '库存批次表单价');
        $num2 = 3;
        $i = 1;
        foreach($data as $key => $val) {
            //写入数据
            $objActSheet->setCellValue('A' . $num2, $i);
            $objActSheet->setCellValue('B' . $num2, $val['goods_id']);
            $objActSheet->setCellValue('C' . $num2, $val['goods_name']);
            $objActSheet->setCellValue('D' . $num2, $val['store_num']);
            $objActSheet->setCellValue('E' . $num2, $val['warehouse_num']);
            $objActSheet->setCellValue('F' . $num2, $val['inout_num']);
            $objActSheet->setCellValue('G' . $num2, $val['ginprice']);
            $num2++;
            $i++;
        }
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
    /*******************导出采购退货单【列表】**************************/
    public function pushPurchaseOutList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        date_default_timezone_set("PRC");
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '采购退货单号');
        $objActSheet ->setCellValue('C'.$num, '退货日期');
        $objActSheet ->setCellValue('D'.$num, '退货种类');
        $objActSheet ->setCellValue('E'.$num, '退货数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货仓库');
        $objActSheet ->setCellValue('H'.$num, '供应商');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '报价金额');
        $objActSheet ->setCellValue('K'.$num, '单据状态');
        $objActSheet ->setCellValue('L'.$num, '关联采购单');
        $objActSheet ->setCellValue('M'.$num, '备注');
        $num2 = 3;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['p_o_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['s_name']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            $objActSheet ->setCellValue('J'.$num2, $val['g_s_amounts']);
            switch ($val['p_o_status'])
            {
                case 0:
                    $strStatus = "新增/" .$val['nickname'];
                    break;
                case 1:
                    $strStatus = "已审核/" .$val['pnickname'];
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('K'.$num2, $strStatus);
            $objActSheet ->setCellValue('L'.$num2, $val['p_sn']);
            $objActSheet ->setCellValue('M'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(24);
        $objActSheet->getColumnDimension('L')->setWidth(24);
        $objActSheet->getColumnDimension('M')->setWidth(24);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:M'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出采购退货单【查看】**************************/
    public function pushPurchaseOutView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '退货单号');
        $objActSheet ->setCellValue('B2', $list['p_o_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('G2', '售价金额');
        $objActSheet ->setCellValue('H2', $list['g_amounts']);
        $objActSheet ->setCellValue('A3', '退货种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '退货数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '报价金额');
        $objActSheet ->setCellValue('H3', $list['g_s_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '收货仓库');
        $objActSheet ->setCellValue('E4', $list['w_name']);
        $objActSheet ->setCellValue('G4', '供应商');
        $objActSheet ->setCellValue('H4', $list['s_name']);

        switch ($list['p_o_status'])
        {
            case 0:
                $objActSheet ->setCellValue('A5', '单据状态');
                $objActSheet ->setCellValue('B5', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A5', '审核人');
                $objActSheet ->setCellValue('B5', $list['pnickname']);
                $objActSheet ->setCellValue('D5', '审核时间');
                $objActSheet ->setCellValue('E5', $list['ptime']);
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '已审核/验收单号：' .$list['w_in_sn'] .'/采购单号：' .$list['p_sn']);
                break;
            default:
                $objActSheet ->setCellValue('G5', '单据状态');
                $objActSheet ->setCellValue('H5', '其它');
                break;
        }
        $objActSheet ->setCellValue('A6', '备注');
        $objActSheet ->setCellValue('B6', $list['remark']);
        $num=7;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '验收单号');
        $objActSheet ->setCellValue('G'.$num, '退货来源');
        $objActSheet ->setCellValue('H'.$num, '申请人');
        $objActSheet ->setCellValue('I'.$num, '系统售价');
        $objActSheet ->setCellValue('J'.$num, '上次采购价');
        $objActSheet ->setCellValue('K'.$num, '供应商报价');
        $objActSheet ->setCellValue('L'.$num, '库存数量');
        $objActSheet ->setCellValue('M'.$num, '采购数量');
        $objActSheet ->setCellValue('N'.$num, '入库数量');
        $objActSheet ->setCellValue('O'.$num, '退货数量');
        $num2 = 8;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['w_in_sn']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name'] .'/' .$val['s_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('I'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('J'.$num2, $val['last_price']);
            $objActSheet ->setCellValue('K'.$num2, $val['p_price']);
            $objActSheet ->setCellValue('L'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('M'.$num2, $val['p_num']);
            $objActSheet ->setCellValue('N'.$num2, $val['in_num']);
            $objActSheet ->setCellValue('O'.$num2, $val['out_num']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(18);
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
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A7:N'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出入库单【列表】**************************/
    public function pushWarehouseInStockList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '入库单号');
        $objActSheet ->setCellValue('C'.$num, '入库日期');
        $objActSheet ->setCellValue('D'.$num, '商品种类');
        $objActSheet ->setCellValue('E'.$num, '商品数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货仓库');
        $objActSheet ->setCellValue('H'.$num, '供应商');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '来源');
        $objActSheet ->setCellValue('K'.$num, '关联单号');
        $objActSheet ->setCellValue('L'.$num, '当前状态');
        $objActSheet ->setCellValue('M'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['w_in_s_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['s_name']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            switch ($val['w_in_s_type'])
            {
                case 0:
                    $strType = "采购单";
                    $strNo = $val['p_sn'];
                    break;
                case 1:
                    $strType = "店铺退货";
                    $strNo = $val['s_out_id'];
                    break;
                case 2:
                    $strType = "仓库调拨";
                    $strNo = $val['w_out_id'];
                    break;
                case 3:
                    $strType = "盘盈入库";
                    $strNo = '暂缺';
                    break;
                case 4:
                    $strType = "其它";
                    $strNo = $val['o_out_id'];
                    break;
                default:
                    $strType = '其它来源';
                    break;
            }
            $objActSheet ->setCellValue('J'.$num2, $strType);
            $objActSheet ->setCellValue('K'.$num2, $strNo);
            switch ($val['w_in_s_status'])
            {
                case 0:
                    $strStatus = "新增";
                    break;
                case 1:
                    $strStatus = "已入库";
                    break;
                case 2:
                    $strStatus = "部分入库、部分退货";
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('L'.$num2, $strStatus);
            $objActSheet ->setCellValue('M'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(24);
        $objActSheet->getColumnDimension('K')->setWidth(24);
        $objActSheet->getColumnDimension('L')->setWidth(24);
        $objActSheet->getColumnDimension('M')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:M'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出入库单【查看】**************************/
    public function pushWarehouseInStockView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '入库单号');
        $objActSheet ->setCellValue('B2', $list['w_in_s_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('G2', '验收单号');
        $objActSheet ->setCellValue('H2', $list['w_in_sn']);

        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);

        if($list['w_in_type'] == 0){
            $objActSheet ->setCellValue('A4', '来源');
            $objActSheet ->setCellValue('B4', '采购单');
            $objActSheet ->setCellValue('D4', '采购单号');
            $objActSheet ->setCellValue('E4', $list['p_sn']);
        }else{
            if($list['w_in_type'] == 1){
                $objActSheet ->setCellValue('A4', '来源');
                $objActSheet ->setCellValue('B4', '店铺退货');
            }else{
                if($list['w_in_type'] == 2){
                    $objActSheet ->setCellValue('A4', '来源');
                    $objActSheet ->setCellValue('B4', '仓库调拨');
                }else{
                    if($list['w_in_type'] == 3){
                        $objActSheet ->setCellValue('A4', '来源');
                        $objActSheet ->setCellValue('B4', '盘盈入库');
                    }else{
                        $objActSheet ->setCellValue('A4', '来源');
                        $objActSheet ->setCellValue('B4', '其它');
                    }
                }
            }
        }

        $objActSheet ->setCellValue('A5', '管理员');
        $objActSheet ->setCellValue('B5', $list['nickname']);
        $objActSheet ->setCellValue('D5', '收货仓库');
        $objActSheet ->setCellValue('E5', $list['w_name']);
        $objActSheet ->setCellValue('G5', '供应商');
        $objActSheet ->setCellValue('H5', $list['s_name']);

        switch ($list['w_in_s_status'])
        {
            case 0:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '已入库');
                $objActSheet ->setCellValue('D6', '审核人');
                $objActSheet ->setCellValue('E6', $list['pnickname']);
                $objActSheet ->setCellValue('G6', '审核时间');
                $objActSheet ->setCellValue('H6', $list['ptime']);
                break;
            case 2:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '已退货');
                break;
            case 3:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '部分退货');
                break;
            default:
                $objActSheet ->setCellValue('A5', '单据状态');
                $objActSheet ->setCellValue('B5', '其它');
                break;
        }

        $objActSheet ->setCellValue('A7', '备注');
        $objActSheet ->setCellValue('B7', $list['remark']);
        $num=8;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '售价金额');
        $objActSheet ->setCellValue('G'.$num, '库存数量');
        $objActSheet ->setCellValue('H'.$num, '申请数量');
        $objActSheet ->setCellValue('I'.$num, '验收数量');
        $num2 = 9;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('G'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('H'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('I'.$num2, $val['in_num']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A8:H'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出库存--类别【列表】**************************/
    public function pushWarehouseStockList($data,$title,$fname,$purchasebool){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        if($purchasebool == 1) {
            $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');//合并单元格
        }else{
            $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');//合并单元格
        }
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '类别');
        $objActSheet ->setCellValue('C'.$num, '所属仓库');
        $objActSheet ->setCellValue('D'.$num, '当前仓库库存');
        if($purchasebool == 1) {
            $objActSheet->setCellValue('E' . $num, '库存金额');
            $objActSheet->setCellValue('F' . $num, '售价金额');
        }else{
            $objActSheet->setCellValue('E' . $num, '售价金额');
        }
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['nums']);
            if($purchasebool == 1) {
                $objActSheet ->setCellValue('E'.$num2, $val['stock_amount']);
                $objActSheet ->setCellValue('F'.$num2, $val['sell_amount']);
            }else {
                $objActSheet->setCellValue('E' . $num2, $val['sell_amount']);
            }
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(24);
        $objActSheet->getColumnDimension('D')->setWidth(24);
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
        if($purchasebool == 1) {
            $objPHPExcel->getActiveSheet()->getStyle('A2:F' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框
        }else{
            $objPHPExcel->getActiveSheet()->getStyle('A2:E' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框
        }
        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出库存--商品【列表】**************************/
    public function pushWarehouseStockGoodsList($data,$title,$fname,$purchasebool){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        if($purchasebool == 1) {
            $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');//合并单元格
        }else{
            $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        }
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '商品ID');
        $objActSheet ->setCellValue('C'.$num, '商品名');
        $objActSheet ->setCellValue('D'.$num, '商品类别');
        $objActSheet ->setCellValue('E'.$num, '商品属性');
        $objActSheet ->setCellValue('F'.$num, '商品条码');
        $objActSheet ->setCellValue('G'.$num, '所属仓库');
        //$objActSheet ->setCellValue('H'.$num, '所有库存');
        $objActSheet ->setCellValue('H'.$num, '当前仓库库存');
        if($purchasebool == 1) {
            $objActSheet->setCellValue('I' . $num, '平均入库价');
            $objActSheet->setCellValue('J' . $num, '库存金额');
            $objActSheet->setCellValue('K' . $num, '系统售价');
            $objActSheet->setCellValue('L' . $num, '售价金额');
        }else{
            $objActSheet->setCellValue('I' . $num, '系统售价');
            $objActSheet->setCellValue('J' . $num, '售价金额');
        }
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('C'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('E'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('F'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            //$objActSheet ->setCellValue('H'.$num2, $val['all_nums']);
            $objActSheet ->setCellValue('H'.$num2, $val['num']);
            if($purchasebool == 1) {
                $objActSheet ->setCellValue('I'.$num2, $val['stock_price']);
                $objActSheet ->setCellValue('J'.$num2, $val['this_stock_amout']);
                $objActSheet ->setCellValue('K'.$num2, $val['sell_price']);
                $objActSheet ->setCellValue('L'.$num2, $val['sell_price']*$val['num'] );
            }else {
                $objActSheet->setCellValue('I' . $num2, $val['sell_amount']);
                $objActSheet ->setCellValue('J'.$num2, $val['sell_amount']);
            }
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(10);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(12);
        $objActSheet->getColumnDimension('C')->setWidth(36);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(18);
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
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        if($purchasebool == 1) {
            $objPHPExcel->getActiveSheet()->getStyle('A2:L' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框
        }else{
            $objPHPExcel->getActiveSheet()->getStyle('A2:J' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框
        }
        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出库存商品【入库记录】**************************/
    public function pushWarehouseStockInView($list,$data,$title,$fname,$purchasebool,$v='in'){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '商品ID');
        $objActSheet ->setCellValue('B2', $list['goods_id']);
        $objActSheet ->setCellValue('D2', '商品名称');
        $objActSheet ->setCellValue('E2', $list['goods_name']);
        $objActSheet ->setCellValue('G2', '商品类别');
        $objActSheet ->setCellValue('H2', $list['cate_name']);

        $objActSheet ->setCellValue('A3', '商品条码');
        $objActSheet ->setCellValue('B3', $list['bar_code']);
        $objActSheet ->setCellValue('D3', '所有库存');
        $objActSheet ->setCellValue('E3', $list['all_nums']);
        $objActSheet ->setCellValue('G3', '当前仓库库存');
        $objActSheet ->setCellValue('H3', $list['num']);

        $objActSheet ->setCellValue('A4', '系统售价');
        $objActSheet ->setCellValue('B4', $list['sell_price']);
        $objActSheet ->setCellValue('D4', '售价金额');
        $objActSheet ->setCellValue('E4', number_format($list['sell_price']*$list['num'],2));
        $objActSheet ->setCellValue('J2', '商品属性');
        $objActSheet ->setCellValue('K2', $list['value_id']);
        if($purchasebool == 1) {
            $objActSheet ->setCellValue('A5', '平均入库价');
            $objActSheet ->setCellValue('B5', $list['stock_price']);
            $objActSheet ->setCellValue('D5', '库存金额');
            $objActSheet ->setCellValue('E5', $list['this_stock_amout']);
            $num=6;
        }else{
            $num=5;
        }
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '入库单号');
        $objActSheet ->setCellValue('B'.$num, '入库时间');
        $objActSheet ->setCellValue('C'.$num, '类型');
        $objActSheet ->setCellValue('D'.$num, '来源');
        $objActSheet ->setCellValue('E'.$num, '入库仓库/门店');
        $objActSheet ->setCellValue('F'.$num, '入库数量');
        $objActSheet ->setCellValue('G'.$num, '系统售价');
        $objActSheet ->setCellValue('H'.$num, '售价金额');
        if($purchasebool == 1) {
            $objActSheet->setCellValue('I' . $num, '入库价格');
            $objActSheet->setCellValue('J' . $num, '入库金额');
        }
        $num2 = $num + 1;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['this_sn']);
            $objActSheet ->setCellValue('B'.$num2, $val['ptime']);
            if($v == 'in'){
                if($val['w_in_s_type'] == 0){
                    $objActSheet ->setCellValue('C'.$num2, '采购单');
                }else{
                    if($val['w_in_s_type'] == 1){
                        $objActSheet ->setCellValue('C'.$num2, '店铺退货');
                    }else{
                        if($val['w_in_s_type'] == 2){
                            $objActSheet ->setCellValue('C'.$num2, '仓库调拨');
                        }else{
                            if($val['w_in_s_type'] == 3){
                                $objActSheet ->setCellValue('C'.$num2, '盘盈入库');
                            }else{
                                if($val['w_in_s_type'] == 4){
                                    $objActSheet ->setCellValue('C'.$num2, '门店返仓');
                                }else{
                                    if($val['w_in_s_type'] == 5){
                                        $objActSheet ->setCellValue('C'.$num2, '被退货');
                                    }else{
                                        $objActSheet ->setCellValue('C'.$num2, '其它');
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif($v == 'out'){
                if($val['w_out_s_type'] == 0){
                    $objActSheet ->setCellValue('C'.$num2, '仓库调拨');
                }else{
                    if($val['w_out_s_type'] == 1){
                        $objActSheet ->setCellValue('C'.$num2, '门店申请');
                    }else{
                        if($val['w_out_s_type'] == 3){
                            $objActSheet ->setCellValue('C'.$num2, '盘亏出库');
                        }else{
                            if($val['w_out_s_type'] == 5){
                                $objActSheet ->setCellValue('C'.$num2, '直接发货');
                            }else{
                                $objActSheet ->setCellValue('C'.$num2, '其它');
                            }
                        }
                    }
                }
            }
            
            if($v == 'in'){
                $objActSheet ->setCellValue('D'.$num2, $val['this_from1']. '/' .$val['this_from2']);
            }elseif($v == 'out'){
                $objActSheet ->setCellValue('D'.$num2, $val['w_name2']);
             }
             if($v == 'in'){
                 $objActSheet ->setCellValue('E'.$num2, $val['this_to1']);
             }elseif($v == 'out'){
                 $objActSheet ->setCellValue('E'.$num2, $val['w_name1']. '/' .$val['store_name']);
              } 
            
            $objActSheet ->setCellValue('F'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('G'.$num2, ' ' .$val['sell_price']);
            $objActSheet ->setCellValue('H'.$num2, number_format($val['sell_price']*$val['g_num'],2));
            if($purchasebool == 1) {
                $objActSheet->setCellValue('I' . $num2, $val['g_price']);
                $objActSheet->setCellValue('J' . $num2,number_format($val['g_price']*$val['g_num'],2));
            }
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(18);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(24);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(12);
        $objActSheet->getColumnDimension('F')->setWidth(12);
        $objActSheet->getColumnDimension('G')->setWidth(12);
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
        if($purchasebool == 1) {
            $objPHPExcel->getActiveSheet()->getStyle('A' . $num . ':G' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框
        }else {
            $objPHPExcel->getActiveSheet()->getStyle('A' . $num . ':E' . (string)($num2 - 1))->applyFromArray($styleArray);//应用边框
        }
        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出出库验货单【列表】**************************/
    public function pushWarehouseOutList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '出库验货单号');
        $objActSheet ->setCellValue('C'.$num, '出库验货日期');
        $objActSheet ->setCellValue('D'.$num, '商品种类');
        $objActSheet ->setCellValue('E'.$num, '商品数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货仓库/门店');
        $objActSheet ->setCellValue('H'.$num, '发货仓库');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '来源');
        $objActSheet ->setCellValue('K'.$num, '关联单号');
        $objActSheet ->setCellValue('L'.$num, '当前状态');
        $objActSheet ->setCellValue('M'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['w_out_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name1'] .'/' .$val['store_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['w_name2']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            switch ($val['w_out_type'])
            {
                case 0:
                    $strType = "仓库调拨";
                    $strNo = $val['w_r_sn'];
                    break;
                case 1:
                    $strType = "门店申请";
                    $strNo = $val['s_r_sn'];
                    break;
                default:
                    $strType = '其它';
                    $strNo = $val['o_out_id'];
                    break;
            }
            $objActSheet ->setCellValue('J'.$num2, $strType);
            $objActSheet ->setCellValue('K'.$num2, $strNo);
            switch ($val['w_out_status'])
            {
                case 0:
                    $strStatus = "新增";
                    break;
                case 1:
                    $strStatus = "出库单：";
                    if($val['w_out_s_sn'] != ''){
                        $strStatus .= $val['w_out_s_sn'];
                    }
                    break;
                case 2:
                    $strStatus = "已拒绝";
                    break;
                case 3:
                    $strStatus = "部分缺货、部分出库：";
                    if($val['w_out_s_sn'] != ''){
                        $strStatus .= '出库单' .$val['w_out_s_sn'];
                    }
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('L'.$num2, $strStatus);
            $objActSheet ->setCellValue('M'.$num2, $val['remark']);
            $num2++;
            $i++;
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
        $objActSheet->getColumnDimension('L')->setWidth(36);
        $objActSheet->getColumnDimension('M')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:M'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出出库验货单【查看】**************************/
    public function pushWarehouseOutView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '验货单号');
        $objActSheet ->setCellValue('B2', $list['w_out_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '验货仓库');
        $objActSheet ->setCellValue('E4', $list['w_name2']);
        $objActSheet ->setCellValue('G4', '申请仓库/门店');
        $objActSheet ->setCellValue('H4', $list['w_name1'] .'/' .$list['store_name']);

        if($list['w_out_type'] == 0){
            $objActSheet ->setCellValue('A5', '来源：');
            $objActSheet ->setCellValue('B5', '仓库调拨单');
            $objActSheet ->setCellValue('D5', '申请仓库');
            $objActSheet ->setCellValue('E5', $list['w_name1']);
            $objActSheet ->setCellValue('G5', '调拨单号');
            $objActSheet ->setCellValue('H5', $list['w_r_sn']);
        }else{
            if($list['w_out_type'] == 1){
                $objActSheet ->setCellValue('A5', '来源：');
                $objActSheet ->setCellValue('B5', '门店申请单');
                $objActSheet ->setCellValue('D4', '申请门店');
                $objActSheet ->setCellValue('E4', $list['store_name']);
                $objActSheet ->setCellValue('G5', '申请单号');
                $objActSheet ->setCellValue('H5', $list['s_r_sn']);
            }else{
                $objActSheet ->setCellValue('A5', '来源：');
                $objActSheet ->setCellValue('B5', '其它');
            }
        }

        switch ($list['w_out_status'])
        {
            case 0:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A6', '审核人');
                $objActSheet ->setCellValue('B6', $list['pnickname']);
                $objActSheet ->setCellValue('D6', '审核时间');
                $objActSheet ->setCellValue('E6', $list['ptime']);
                $objActSheet ->setCellValue('G6', '单据状态');
                $objActSheet ->setCellValue('H6', '出库单：' .$list['w_out_s_sn']);
                break;
            case 2:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '已拒绝');
                $objActSheet ->setCellValue('D6', '拒绝人');
                $objActSheet ->setCellValue('E6', $list['pnickname']);
                $objActSheet ->setCellValue('G6', '时间');
                $objActSheet ->setCellValue('H6', $list['ptime']);
                break;
            case 3:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '部分拒绝、部分出库');
                $objActSheet ->setCellValue('D6', '出库单');
                $objActSheet ->setCellValue('E6', $list['w_out_s_sn']);
                break;
            default:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '其它');
                break;
        }
        $objActSheet ->setCellValue('A7', '备注');
        $objActSheet ->setCellValue('B7', $list['remark']);
        $objActSheet ->setCellValue('D7', '发货地址');
        $objActSheet ->setCellValue('E7', $list['address']);
        $num=8;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '零售价');
        $objActSheet ->setCellValue('G'.$num, '库存数量');
        $objActSheet ->setCellValue('H'.$num, '申请数量');
        $objActSheet ->setCellValue('I'.$num, '有货数量');
        $objActSheet ->setCellValue('J'.$num, '缺货数量');
        $objActSheet ->setCellValue('K'.$num, '备注');
        $num2 = 9;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('G'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('H'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('I'.$num2, $val['in_num']);
            $objActSheet ->setCellValue('J'.$num2, $val['out_num']);
            $objActSheet ->setCellValue('K'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A8:J'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    /*******************导出出库单【列表】**************************/
    public function pushWarehouseOutStockList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '出库单号');
        $objActSheet ->setCellValue('C'.$num, '出库日期');
        $objActSheet ->setCellValue('D'.$num, '商品种类');
        $objActSheet ->setCellValue('E'.$num, '商品数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '收货仓库/门店');
        $objActSheet ->setCellValue('H'.$num, '发货仓库');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '验货单号');
        $objActSheet ->setCellValue('K'.$num, '来源');
        $objActSheet ->setCellValue('L'.$num, '关联单号');
        $objActSheet ->setCellValue('M'.$num, '当前状态');
        $objActSheet ->setCellValue('N'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['w_out_s_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name1'] .'/' .$val['store_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['w_name2']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            $objActSheet ->setCellValue('J'.$num2, $val['w_out_sn']);
            switch ($val['w_out_s_type'])
            {
                case 0:
                    $strType = "仓库调拨";
                    $strNo = $val['w_r_sn'];
                    break;
                case 1:
                    $strType = "门店申请";
                    $strNo = $val['s_r_sn'];
                    break;
                default:
                    $strType = '其它';
                    $strNo = $val['o_out_id'];
                    break;
            }
            $objActSheet ->setCellValue('K'.$num2, $strType);
            $objActSheet ->setCellValue('L'.$num2, $strNo);
            switch ($val['w_out_s_status'])
            {
                case 0:
                    $strStatus = "新增";
                    break;
                case 1:
                    $strStatus = "已出库：";
                    break;
                case 2:
                    $strStatus = "已拒绝";
                    break;
                case 3:
                    $strStatus = "部分拒绝";
                    break;
                default:
                    $strStatus = '其它';
                    break;
            }
            $objActSheet ->setCellValue('M'.$num2, $strStatus);
            $objActSheet ->setCellValue('N'.$num2, $val['remark']);
            $num2++;
            $i++;
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
        $objActSheet->getColumnDimension('L')->setWidth(36);
        $objActSheet->getColumnDimension('M')->setWidth(12);
        $objActSheet->getColumnDimension('N')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:N'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
        //$objWriter->save('php://output');//输出
        //die;
    }
    /*******************导出出库单【查看】**************************/
    public function pushWarehouseOutStockView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '出库单号');
        $objActSheet ->setCellValue('B2', $list['w_out_s_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '出库仓库');
        $objActSheet ->setCellValue('E4', $list['w_name2']);
        $objActSheet ->setCellValue('G4', '申请仓库/门店');
        $objActSheet ->setCellValue('H4', $list['w_name1'] .'/' .$list['store_name']);

        switch ($list['w_out_s_type'])
        {
            case 0:
                $objActSheet ->setCellValue('A5', '来源：');
                $objActSheet ->setCellValue('B5', '仓库调拨单');
                $objActSheet ->setCellValue('D5', '申请仓库');
                $objActSheet ->setCellValue('E5', $list['w_name1']);
                $objActSheet ->setCellValue('G5', '关联单号');
                $objActSheet ->setCellValue('H5', $list['w_r_sn']);
                break;
            case 1:
                $objActSheet ->setCellValue('A5', '来源：');
                $objActSheet ->setCellValue('B5', '门店申请单');
                $objActSheet ->setCellValue('D5', '申请门店');
                $objActSheet ->setCellValue('E5', $list['store_name']);
                $objActSheet ->setCellValue('G5', '关联单号');
                $objActSheet ->setCellValue('H5', $list['s_r_sn']);
                break;
            default:
                $objActSheet ->setCellValue('A5', '来源：');
                $objActSheet ->setCellValue('B5', '其它');
                break;
        }

        switch ($list['w_out_s_status'])
        {
            case 0:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '已出库');
                $objActSheet ->setCellValue('D6', '审核人');
                $objActSheet ->setCellValue('E6', $list['pnickname']);
                $objActSheet ->setCellValue('G6', '审核时间');
                $objActSheet ->setCellValue('H6', $list['ptime']);
                break;
            case 2:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '已拒绝');
                $objActSheet ->setCellValue('D6', '拒绝人');
                $objActSheet ->setCellValue('E6', $list['pnickname']);
                $objActSheet ->setCellValue('G6', '时间');
                $objActSheet ->setCellValue('H6', $list['ptime']);
                break;
            case 3:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '部分拒绝、部分出库');
                $objActSheet ->setCellValue('D6', '出库单');
                $objActSheet ->setCellValue('E6', $list['w_out_s_sn']);
                break;
            default:
                $objActSheet ->setCellValue('A6', '单据状态');
                $objActSheet ->setCellValue('B6', '其它');
                break;
        }
        $objActSheet ->setCellValue('A7', '备注');
        $objActSheet ->setCellValue('B7', $list['remark']);
        $num=8;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '零售价');
        $objActSheet ->setCellValue('G'.$num, '库存数量');
        $objActSheet ->setCellValue('H'.$num, '申请数量');
        $objActSheet ->setCellValue('I'.$num, '有货数量');
        $objActSheet ->setCellValue('J'.$num, '缺货数量');


        $num2 = 9;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('G'.$num2, $val['stock_num']);
            $objActSheet ->setCellValue('H'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('I'.$num2, $val['in_num']);
            $objActSheet ->setCellValue('J'.$num2, $val['out_num']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(18);
        $objActSheet->getColumnDimension('G')->setWidth(12);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A8:I'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }
    /*******************导出出库单【列表】**************************/
    public function pushWarehouseInventoryList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '盘点单号');
        $objActSheet ->setCellValue('C'.$num, '盘点日期');
        $objActSheet ->setCellValue('D'.$num, '商品种类');
        $objActSheet ->setCellValue('E'.$num, '商品数量');
        $objActSheet ->setCellValue('F'.$num, '管理员');
        $objActSheet ->setCellValue('G'.$num, '盘点仓库');
        $objActSheet ->setCellValue('H'.$num, '商品金额');
        $objActSheet ->setCellValue('I'.$num, '售价金额');
        $objActSheet ->setCellValue('J'.$num, '状态');
        $objActSheet ->setCellValue('K'.$num, '备注');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['i_sn']);
            $objActSheet ->setCellValue('C'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('D'.$num2, $val['g_type']);
            $objActSheet ->setCellValue('E'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('F'.$num2, $val['nickname']);
            $objActSheet ->setCellValue('G'.$num2, $val['w_name']);
            $objActSheet ->setCellValue('H'.$num2, $val['p_amounts']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_amounts']);
            switch ($val['i_status'])
            {
                case 0:
                    $strType = "新增";
                    break;
                case 1:
                    $strType = "已审核";
                    break;
                case 2:
                    $strType = "已作废";
                    break;
                default:
                    $strType = '其它';
                    break;
            }
            $objActSheet ->setCellValue('J'.$num2, $strType);
            $objActSheet ->setCellValue('K'.$num2, $val['remark']);
            $num2++;
            $i++;
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
        $objWriter->save($fname);
        return $fname;
        //$objWriter->save('php://output');//输出
        //die;
    }
    /*******************导出出库单【查看】**************************/
    public function pushWarehouseInventoryView($list,$data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $objActSheet ->setCellValue('A2', '盘点单号');
        $objActSheet ->setCellValue('B2', $list['i_sn']);
        $objActSheet ->setCellValue('D2', '创建日期');
        $objActSheet ->setCellValue('E2', $list['ctime']);
        $objActSheet ->setCellValue('A3', '商品种类');
        $objActSheet ->setCellValue('B3', $list['g_type']);
        $objActSheet ->setCellValue('D3', '商品数量');
        $objActSheet ->setCellValue('E3', $list['g_nums']);
        $objActSheet ->setCellValue('G3', '售价金额');
        $objActSheet ->setCellValue('H3', $list['g_amounts']);
        $objActSheet ->setCellValue('A4', '管理员');
        $objActSheet ->setCellValue('B4', $list['nickname']);
        $objActSheet ->setCellValue('D4', '盘点仓库');
        $objActSheet ->setCellValue('E4', $list['w_name']);

        switch ($list['w_out_s_type'])
        {
            case 0:
                $objActSheet ->setCellValue('A5', '状态：');
                $objActSheet ->setCellValue('B5', '新增');
                break;
            case 1:
                $objActSheet ->setCellValue('A5', '状态：');
                $objActSheet ->setCellValue('B5', '已审核');
                break;
            case 2:
                $objActSheet ->setCellValue('A5', '状态：');
                $objActSheet ->setCellValue('B5', '已作废');
                break;
            default:
                $objActSheet ->setCellValue('A5', '状态：');
                $objActSheet ->setCellValue('B5', '其它');
                break;
        }
        $objActSheet ->setCellValue('A6', '备注');
        $objActSheet ->setCellValue('B6', $list['remark']);
        $num=7;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '商品ID');
        $objActSheet ->setCellValue('B'.$num, '商品名称');
        $objActSheet ->setCellValue('C'.$num, '商品类别');
        $objActSheet ->setCellValue('D'.$num, '商品属性');
        $objActSheet ->setCellValue('E'.$num, '商品条码');
        $objActSheet ->setCellValue('F'.$num, '零售价');
        $objActSheet ->setCellValue('G'.$num, '审核库存数量');
        //$objActSheet ->setCellValue('G'.$num, '库存均价');
        $objActSheet ->setCellValue('H'.$num, '盘点数量');
        $objActSheet ->setCellValue('I'.$num, '盘点价格');
        $objActSheet ->setCellValue('J'.$num, '盈亏数量');
        $objActSheet ->setCellValue('K'.$num, '商品备注');
        $num2 = 8;
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $val['goods_id']);
            $objActSheet ->setCellValue('B'.$num2, $val['goods_name']);
            $objActSheet ->setCellValue('C'.$num2, $val['cate_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['value_name']);
            $objActSheet ->setCellValue('E'.$num2, ' ' .$val['bar_code']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_price']);
            $objActSheet ->setCellValue('G'.$num2, $val['b_num']);
            //$objActSheet ->setCellValue('G'.$num2, $val['stock_price']);
            $objActSheet ->setCellValue('H'.$num2, $val['g_num']);
            $objActSheet ->setCellValue('I'.$num2, $val['g_price']);
            $objActSheet ->setCellValue('J'.$num2, $val['add_num']);
            $objActSheet ->setCellValue('K'.$num2, $val['remark']);
            $num2++;
            $i++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(12);
        $objActSheet->getColumnDimension('D')->setWidth(12);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(24);
        $objActSheet->getColumnDimension('G')->setWidth(18);
        $objActSheet->getColumnDimension('H')->setWidth(12);
        $objActSheet->getColumnDimension('I')->setWidth(12);
        $objActSheet->getColumnDimension('J')->setWidth(12);
        $objActSheet->getColumnDimension('K')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A7:J'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
    }
    /*******************导出采购报表【列表】**************************/
    public function pushPurchaseReportList($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:T1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '日期');
        $objActSheet ->setCellValue('C'.$num, '采购总数量');
        $objActSheet ->setCellValue('D'.$num, '总箱数');
        $objActSheet ->setCellValue('E'.$num, '总金额');
        $objActSheet ->setCellValue('F'.$num, '总售价金额');
        $objActSheet ->setCellValue('G'.$num, '仓库验收数');
        $objActSheet ->setCellValue('H'.$num, '仓库验收金额');
        $objActSheet ->setCellValue('I'.$num, '仓库拒收数');
        $objActSheet ->setCellValue('J'.$num, '仓库拒收金额');
        $objActSheet ->setCellValue('K'.$num, '仓库入库数');
        $objActSheet ->setCellValue('L'.$num, '仓库入库金额');
        $objActSheet ->setCellValue('M'.$num, '门店验收数');
        $objActSheet ->setCellValue('N'.$num, '门店验收金额');
        $objActSheet ->setCellValue('O'.$num, '门店拒收数');
        $objActSheet ->setCellValue('P'.$num, '门店拒收金额');
        $objActSheet ->setCellValue('Q'.$num, '门店入库数');
        $objActSheet ->setCellValue('R'.$num, '门店入库金额');
        $objActSheet ->setCellValue('S'.$num, '实际退货数');
        $objActSheet ->setCellValue('T'.$num, '实际退货金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('C'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('D'.$num2, $val['b_nums']);
            $objActSheet ->setCellValue('E'.$num2, $val['b_amounts']);
            $objActSheet ->setCellValue('F'.$num2, $val['sell_amounts']);
            $objActSheet ->setCellValue('G'.$num2, $val['in_nums']);
            $objActSheet ->setCellValue('H'.$num2, $val['in_amounts']);
            $objActSheet ->setCellValue('I'.$num2, $val['out_nums']);
            $objActSheet ->setCellValue('J'.$num2, $val['out_amounts']);
            $objActSheet ->setCellValue('K'.$num2, $val['in_stock_nums']);
            $objActSheet ->setCellValue('L'.$num2, $val['in_stock_amounts']);
            $objActSheet ->setCellValue('M'.$num2, $val['s_in_nums']);
            $objActSheet ->setCellValue('N'.$num2, $val['s_in_amounts']);
            $objActSheet ->setCellValue('O'.$num2, $val['s_out_nums']);
            $objActSheet ->setCellValue('P'.$num2, $val['s_out_amounts']);
            $objActSheet ->setCellValue('Q'.$num2, $val['s_in_stock_nums']);
            $objActSheet ->setCellValue('R'.$num2, $val['s_in_stock_amounts']);
            $objActSheet ->setCellValue('S'.$num2, $val['back_nums']);
            $objActSheet ->setCellValue('T'.$num2, $val['back_amounts']);
            $num2++;
            $i++;
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:T'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
        //$objWriter->save('php://output');//输出
        //die;
    }
    /*******************导出采购报表【详情】**************************/
    public function pushPurchaseReportView($data,$title,$fname){
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:AG1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $title);
        $num=2;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题
        $objActSheet ->setCellValue('A'.$num, '序号');
        $objActSheet ->setCellValue('B'.$num, '采购单日期');
        $objActSheet ->setCellValue('C'.$num, '供应商名');
        $objActSheet ->setCellValue('D'.$num, '采购单号');
        $objActSheet ->setCellValue('E'.$num, '采购单状态');
        $objActSheet ->setCellValue('F'.$num, '采购单数量');
        $objActSheet ->setCellValue('G'.$num, '采购单箱数');
        $objActSheet ->setCellValue('H'.$num, '采购单金额');
        $objActSheet ->setCellValue('I'.$num, '采购单零售金额');
        $objActSheet ->setCellValue('J'.$num, '入库验收单号');
        $objActSheet ->setCellValue('K'.$num, '入库验收单状态');
        $objActSheet ->setCellValue('L'.$num, '验收数量');
        $objActSheet ->setCellValue('M'.$num, '验收金额');
        $objActSheet ->setCellValue('N'.$num, '拒收数量');
        $objActSheet ->setCellValue('O'.$num, '拒收金额');
        $objActSheet ->setCellValue('P'.$num, '入库单号');
        $objActSheet ->setCellValue('Q'.$num, '入库单状态');
        $objActSheet ->setCellValue('R'.$num, '实际入库数量');
        $objActSheet ->setCellValue('S'.$num, '实际入库金额');
        $objActSheet ->setCellValue('T'.$num, '门店验收单号');
        $objActSheet ->setCellValue('U'.$num, '门店验收单状态');
        $objActSheet ->setCellValue('V'.$num, '验收数量');
        $objActSheet ->setCellValue('W'.$num, '验收金额');
        $objActSheet ->setCellValue('X'.$num, '拒收数量');
        $objActSheet ->setCellValue('Y'.$num, '拒收金额');
        $objActSheet ->setCellValue('Z'.$num, '门店入库单号');
        $objActSheet ->setCellValue('AA'.$num, '门店入库单状态');
        $objActSheet ->setCellValue('AB'.$num, '实际入库数量');
        $objActSheet ->setCellValue('AC'.$num, '实际入库金额');
        $objActSheet ->setCellValue('AD'.$num, '采购退货单号');
        $objActSheet ->setCellValue('AE'.$num, '采购退货单状态');
        $objActSheet ->setCellValue('AF'.$num, '采购退货数量');
        $objActSheet ->setCellValue('AG'.$num, '采购退货金额');
        $num2 = 3;
        date_default_timezone_set("PRC");
        $i = 1;
        foreach($data as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num2, $i);
            $objActSheet ->setCellValue('B'.$num2, $val['ctime']);
            $objActSheet ->setCellValue('C'.$num2, $val['s_name']);
            $objActSheet ->setCellValue('D'.$num2, $val['p_sn']);
            if($val['p_status'] == 0){
                $p_status = "未处理";
            }else{
                if($val['p_status'] == 1){
                    $p_status = "已审核";
                }else{
                    if($val['p_status'] == 2){
                        $p_status = "已作废";
                    }else{
                        $p_status = "其它";
                    }
                }
            }
            $objActSheet ->setCellValue('E'.$num2, $p_status);
            $objActSheet ->setCellValue('F'.$num2, $val['g_nums']);
            $objActSheet ->setCellValue('G'.$num2, $val['b_nums']);
            $objActSheet ->setCellValue('H'.$num2, $val['b_amounts']);
            $objActSheet ->setCellValue('I'.$num2, $val['sell_amounts']);
            $objActSheet ->setCellValue('J'.$num2, $val['w_in_sn']);
            if($val['w_in_status'] == 0){
                $w_in_status = "未处理";
            }else{
                if($val['w_in_status'] == 1){
                    $w_in_status = "已审核";
                }else{
                    if($val['w_in_status'] == 2){
                        $w_in_status = "已退货";
                    }else{
                        if($val['w_in_status'] == 3){
                            $w_in_status = "部分退货";
                        }else{
                            $w_in_status = "其它";
                        }
                    }
                }
            }
            $objActSheet ->setCellValue('K'.$num2, $w_in_status);
            $objActSheet ->setCellValue('L'.$num2, $val['in_nums']);
            $objActSheet ->setCellValue('M'.$num2, $val['in_amounts']);
            $objActSheet ->setCellValue('N'.$num2, $val['out_nums']);
            $objActSheet ->setCellValue('O'.$num2, $val['out_amounts']);
            $objActSheet ->setCellValue('P'.$num2, $val['w_in_s_sn']);
            if($val['w_in_s_status'] == 0){
                $w_in_s_status = "未处理";
            }else{
                if($val['w_in_s_status'] == 1){
                    $w_in_s_status = "已审核";
                }else{
                    if($val['w_in_s_status'] == 2){
                        $w_in_s_status = "已退货";
                    }else{
                        if($val['w_in_s_status'] == 3){
                            $w_in_s_status = "部分退货";
                        }else{
                            $w_in_s_status = "其它";
                        }
                    }
                }
            }
            $objActSheet ->setCellValue('Q'.$num2, $w_in_s_status);
            $objActSheet ->setCellValue('R'.$num2, $val['in_stock_nums']);
            $objActSheet ->setCellValue('S'.$num2, $val['in_stock_amounts']);

            $objActSheet ->setCellValue('T'.$num2, $val['s_in_sn']);
            if($val['s_in_status'] == 0){
                $s_in_status = "未处理";
            }else{
                if($val['s_in_status'] == 1){
                    $s_in_status = "已审核";
                }else{
                    if($val['s_in_status'] == 2){
                        $s_in_status = "已退货";
                    }else{
                        if($val['s_in_status'] == 3){
                            $s_in_status = "部分退货";
                        }else{
                            $s_in_status = "其它";
                        }
                    }
                }
            }
            $objActSheet ->setCellValue('U'.$num2, $s_in_status);
            $objActSheet ->setCellValue('V'.$num2, $val['s_in_nums']);
            $objActSheet ->setCellValue('W'.$num2, $val['s_in_amounts']);
            $objActSheet ->setCellValue('X'.$num2, $val['s_out_nums']);
            $objActSheet ->setCellValue('Y'.$num2, $val['s_out_amounts']);
            $objActSheet ->setCellValue('Z'.$num2, $val['s_in_s_sn']);
            if($val['s_in_s_status'] == 0){
                $s_in_s_status = "未处理";
            }else{
                if($val['s_in_s_status'] == 1){
                    $s_in_s_status = "已审核";
                }else{
                    if($val['s_in_s_status'] == 2){
                        $s_in_s_status = "已退货";
                    }else{
                        if($val['s_in_s_status'] == 3){
                            $s_in_s_status = "部分退货";
                        }else{
                            $s_in_s_status = "其它";
                        }
                    }
                }
            }
            $objActSheet ->setCellValue('AA'.$num2, $s_in_s_status);
            $objActSheet ->setCellValue('AB'.$num2, $val['s_in_stock_nums']);
            $objActSheet ->setCellValue('AC'.$num2, $val['s_in_stock_amounts']);
            $objActSheet ->setCellValue('AD'.$num2, $val['p_o_sn']);
            if($val['p_o_status'] == 0){
                $p_o_status = "未处理";
            }else{
                if($val['p_o_status'] == 1){
                    $p_o_status = "已审核";
                }else{
                    $p_o_status = "其它";
                }
            }
            $objActSheet ->setCellValue('AE'.$num2, $p_o_status);
            $objActSheet ->setCellValue('AF'.$num2, $val['back_nums']);
            $objActSheet ->setCellValue('AG'.$num2, $val['back_amounts']);
            $num2++;
            $i++;
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
        $objActSheet->getColumnDimension('V')->setWidth(12);
        $objActSheet->getColumnDimension('W')->setWidth(12);
        $objActSheet->getColumnDimension('X')->setWidth(12);
        $objActSheet->getColumnDimension('Y')->setWidth(12);
        $objActSheet->getColumnDimension('Z')->setWidth(12);
        $objActSheet->getColumnDimension('AA')->setWidth(12);
        $objActSheet->getColumnDimension('AB')->setWidth(12);
        $objActSheet->getColumnDimension('AC')->setWidth(12);
        $objActSheet->getColumnDimension('AD')->setWidth(12);
        $objActSheet->getColumnDimension('AE')->setWidth(12);
        $objActSheet->getColumnDimension('AF')->setWidth(12);
        $objActSheet->getColumnDimension('AG')->setWidth(12);
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
        $objPHPExcel->getActiveSheet()->getStyle('A2:AG'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save($fname);
        return $fname;
        //$objWriter->save('php://output');//输出
        //die;
    }

    /**
     * name : 导出商品过期消息详情
     * params:data array , title string
     * author:Ard
     * date:2018-04-19
     */
    public function messageViewExpiredReport($data,$title)
    {
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:P1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $data['message_title']);
        $objActSheet ->setCellValue('A2', '标题');
        $objActSheet ->setCellValue('A3', $data['message_title']);
        $objActSheet ->setCellValue('A4', '发件人');
        $objActSheet ->setCellValue('A5', $data['from_nickname']);
        $objActSheet ->setCellValue('A6', '日期');
        $objActSheet ->setCellValue('A7', date('Y-m-d H:i:s' ,$data['ctime']));
        $objActSheet ->setCellValue('A8', '收件人');
        $objActSheet ->setCellValue('A9', $data['to_nickname']);
        $objActSheet ->setCellValue('A10', '内容');
        //过期商品消息
        $objActSheet ->setCellValue('B11', '序号');
        $objActSheet ->setCellValue('C11', '商品名');
        $objActSheet ->setCellValue('D11', '数量');
        $objActSheet ->setCellValue('F11', '单位');
        $num2 = 12;
        $messageDetails = json_decode($data['message_content'] , true);
        foreach($messageDetails['details'] as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('B'.$num2, $key);
            $objActSheet ->setCellValue('C'.$num2, $val['title']);
            $objActSheet ->setCellValue('D'.$num2, $val['total_num']);
            $objActSheet ->setCellValue('E'.$num2, $val['unit']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(15);
        $objActSheet->getColumnDimension('C')->setWidth(50);
        $objActSheet->getColumnDimension('D')->setWidth(26);
        $objActSheet->getColumnDimension('E')->setWidth(26);

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
        $objPHPExcel->getActiveSheet()->getStyle('A11:E'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }

    /**
     * name : 导出商品汇总消息详情
     * params:
     * author:Ard
     * date:2018-04-19
     */
    public function messageViewStatisticsReport($data,$title)
    {
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:P1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $data['message_title']);
        $objActSheet ->setCellValue('A2', '标题');
        $objActSheet ->setCellValue('A3', $data['message_title']);
        $objActSheet ->setCellValue('A4', '发件人');
        $objActSheet ->setCellValue('A5', $data['from_nickname']);
        $objActSheet ->setCellValue('A6', '日期');
        $objActSheet ->setCellValue('A7', date('Y-m-d H:i:s' ,$data['ctime']));
        $objActSheet ->setCellValue('A8', '收件人');
        $objActSheet ->setCellValue('A9', $data['to_nickname']);
        $objActSheet ->setCellValue('A10', '内容');

        $messageDetails = json_decode($data['message_content'] , true);
        if($data['m_other_type'] == 1){
            $num = 11;
            $objActSheet ->setCellValue('B'.$num, '门店入库');
            $objActSheet ->setCellValue('C'.$num, $messageDetails['stock_in']);
            $objActSheet ->setCellValue('D'.$num, '总值');
            $objActSheet ->setCellValue('E'.$num, $messageDetails['stock_in_price']);
            //说明：0.仓库出库,1.门店调拨,2.盘盈入库,3.其它,4.采购,5.寄售
            $inSource = array(
                '0' => '仓库出库',
                '1' => '门店调拨',
                '2' => '盘盈入库',
                '3' => '其它',
                '4' => '采购',
                '5' => '寄售'
            );
            if(!empty($messageDetails['stock_in_details'])){
                foreach($messageDetails['stock_in_details'] as $key => $value){
                    $num++;
                    $objActSheet ->setCellValue('C'.$num, $inSource[$key].'总数');
                    $objActSheet ->setCellValue('D'.$num, $value['total_num']);
                    $objActSheet ->setCellValue('E'.$num, '总值');
                    $objActSheet ->setCellValue('F'.$num, $value['total_price']);
                }
            }
            $objActSheet ->setCellValue('B'.$num, '门店出库')->getTabColor()->setARGB('FFFF00');
            $objActSheet ->setCellValue('C'.$num, $messageDetails['stock_out']);
            $objActSheet ->setCellValue('D'.$num, '总值');
            $objActSheet ->setCellValue('E'.$num, $messageDetails['stock_out_price']);
            //来源:0.仓库调拨,1.门店申请,3.盘亏出库,4.其它,5.寄售出库
            $outSource = array(
                '0' => '仓库调拨',
                '1' => '门店申请',
                '3' => '盘亏出库',
                '4' => '其它',
                '5' => '寄售出库'
            );
            if(!empty($messageDetails['stock_out_details'])){
                foreach($messageDetails['stock_out_details'] as $key => $value){
                    $num++;
                    $objActSheet ->setCellValue('C'.$num, $outSource[$key].'总数');
                    $objActSheet ->setCellValue('D'.$num, $value['total_num']);
                    $objActSheet ->setCellValue('E'.$num, '总值');
                    $objActSheet ->setCellValue('F'.$num, $value['total_price']);
                }
            }
            //库存
            $objActSheet ->setCellValue('B'.$num, '门店库存');
            $objActSheet ->setCellValue('C'.$num, $messageDetails['stock_total']);
            $objActSheet ->setCellValue('D'.$num, '总值');
            $objActSheet ->setCellValue('E'.$num, $messageDetails['g_amounts']);
            //订单交易
            $objActSheet ->setCellValue('B'.$num, '门店订单交易总数');
            $objActSheet ->setCellValue('C'.$num, $messageDetails['order_total']);
            $objActSheet ->setCellValue('D'.$num, '售出货物总数');
            $objActSheet ->setCellValue('E'.$num, $messageDetails['order_goods_total']);
            $border_end = 'F'.$num;
        }elseif($data['m_other_type'] == 2){
            $border_end = 'E13';
            $objActSheet ->setCellValue('B11', '仓库入库');
            $objActSheet ->setCellValue('C11', $messageDetails['stock_in']['num']);
            $objActSheet ->setCellValue('D11', '总值');
            $objActSheet ->setCellValue('E11', $messageDetails['stock_in']['price']);
            $objActSheet ->setCellValue('B12', '仓库出库');
            $objActSheet ->setCellValue('C12', $messageDetails['stock_out']['num']);
            $objActSheet ->setCellValue('D12', '总值');
            $objActSheet ->setCellValue('E12', $messageDetails['stock_out']['price']);
            $objActSheet ->setCellValue('B13', '仓库库存');
            $objActSheet ->setCellValue('C13', $messageDetails['stock']['num']);
            $objActSheet ->setCellValue('D13', '总值');
            $objActSheet ->setCellValue('E13', $messageDetails['stock']['price']);
        }elseif($data['m_other_type'] == 3){
            $border_end = 'E15';
            //采购汇总
            $objActSheet ->setCellValue('B11', '未审核采购单数');
            $objActSheet ->setCellValue('C11', $messageDetails['is_new']);
            $objActSheet ->setCellValue('B12', '已审核采购单数');
            $objActSheet ->setCellValue('C12', $messageDetails['is_pass']);
            $objActSheet ->setCellValue('B13', '采购总数量');
            $objActSheet ->setCellValue('C13', $messageDetails['g_num_total']);
            $objActSheet ->setCellValue('D13', '采购总值');
            $objActSheet ->setCellValue('E13',  $messageDetails['g_price_total']);
            $objActSheet ->setCellValue('B14', '验收总数量');
            $objActSheet ->setCellValue('C14', $messageDetails['in_num_total']);
            $objActSheet ->setCellValue('D14', '验收总值');
            $objActSheet ->setCellValue('E14',  $messageDetails['in_price_total']);
            $objActSheet ->setCellValue('B15', '退货总数量');
            $objActSheet ->setCellValue('C15', $messageDetails['out_num_total']);
            $objActSheet ->setCellValue('D15', '退货总值');
            $objActSheet ->setCellValue('E15',  $messageDetails['out_price_total']);
        }
        $objActSheet->getColumnDimension('A')->setWidth(32);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(28);
        $objActSheet->getColumnDimension('C')->setWidth(20);
        $objActSheet->getColumnDimension('D')->setWidth(35);
        $objActSheet->getColumnDimension('E')->setWidth(20);

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
       $objPHPExcel->getActiveSheet()->getStyle('B11:'.$border_end)->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }

    /**
     * name : 导出普通信息详情
     * params:data array , title string
     * author:Ard
     * date:2018-04-19
     */
    public function normalMessageReport($data,$title)
    {
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set("PRC");
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:P1');//合并单元格
        //第一行>>>报表标题
        $objActSheet ->setCellValue('A1', $data['message_title']);
        $objActSheet ->setCellValue('A2', '标题');
        $objActSheet ->setCellValue('A3', $data['message_title']);
        $objActSheet ->setCellValue('A4', '发件人');
        $objActSheet ->setCellValue('A5', $data['from_nickname']);
        $objActSheet ->setCellValue('A6', '日期');
        $objActSheet ->setCellValue('A7', date('Y-m-d H:i:s' ,$data['ctime']));
        $objActSheet ->setCellValue('A8', '收件人');
        $objActSheet ->setCellValue('A9', $data['to_nickname']);
        $objActSheet ->setCellValue('A10', '内容');
        //过期商品消息
        $objActSheet ->setCellValue('B11', '序号');
        $objActSheet ->setCellValue('C11', '商品名');
        $objActSheet ->setCellValue('D11', '数量');
        $objActSheet ->setCellValue('F11', '单位');
        $num2 = 12;
        $messageDetails = json_decode($data['message_content'] , true);
        foreach($messageDetails['details'] as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('B'.$num2, $key);
            $objActSheet ->setCellValue('C'.$num2, $val['title']);
            $objActSheet ->setCellValue('D'.$num2, $val['total_num']);
            $objActSheet ->setCellValue('E'.$num2, $val['unit']);
            $num2++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(15);
        $objActSheet->getColumnDimension('C')->setWidth(50);
        $objActSheet->getColumnDimension('D')->setWidth(26);
        $objActSheet->getColumnDimension('E')->setWidth(26);

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
        $objPHPExcel->getActiveSheet()->getStyle('A11:E'.(string)($num2-1))->applyFromArray($styleArray);//应用边框

        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
}