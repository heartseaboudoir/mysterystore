<?php

namespace Addons\Report\Model;
use Think\Model;

class ProductModel extends Model{
    
    public function stringFromColumnIndex($pColumnIndex = 0)  
    {   
        static $_indexCache = array();  
        if (!isset($_indexCache[$pColumnIndex])) {  
            // Determine column string  
            if ($pColumnIndex < 26) {  
                $_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);  
            } elseif ($pColumnIndex < 702) {  
                $_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);  
            } else {
                $_indexCache[$pColumnIndex] = chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);  
            }  
        }  
        return $_indexCache[$pColumnIndex];  
    }    
    
    
    // 导出商品分析
    public function OutProduct($top_cate, $top20_product, $titles, $data,$product_title,$top20_data, $fname)
    {
        /*
        print_r($product_title);
        print_r($top20_data);
        print_r($fname);
        exit;
        */
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Asia/Shanghai');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        // 报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');//合并单元格
        $objActSheet ->setCellValue('A1', $fname);


        // 分类TOP报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');//合并单元格
        $objActSheet ->setCellValue('A2', '分类Top');

        // 商品TOP报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('H2:J2');//合并单元格
        $objActSheet ->setCellValue('H2', '商品Top20');
        
        // 分类趋势统计报表标题
        $top_cate_count = count($titles);
        $top_cate_count2 = $this->stringFromColumnIndex(15+$top_cate_count);
        $objPHPExcel->getActiveSheet()->mergeCells("P2:{$top_cate_count2}2");//合并单元格
        $objActSheet ->setCellValue('P2', '分类趋势');        
        


        // 商品趋势统计报表标题
        $top_product_count = count($product_title);
        
        
        $top_product_count1 = $this->stringFromColumnIndex(15+$top_cate_count + 5);
        $top_product_count2 = $this->stringFromColumnIndex(15+$top_cate_count + 5 + $top_product_count);
        $objPHPExcel->getActiveSheet()->mergeCells("{$top_product_count1}2:{$top_product_count2}2");//合并单元格
        $objActSheet ->setCellValue("{$top_product_count1}2", '商品Top趋势'); 

        

        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题

        $objActSheet ->setCellValue('A'.$num, '排行');;
        $objActSheet ->setCellValue('B'.$num, '分类名');
        $objActSheet ->setCellValue('C'.$num, '消费金额');

        $objActSheet ->setCellValue('H'.$num, '排行');
        $objActSheet ->setCellValue('I'.$num, '商品名');
        $objActSheet ->setCellValue('J'.$num, '消费金额');
    
        // 分类趋势统计数据标题
        $count0 = 15;
        $EXCAB = $this->stringFromColumnIndex($count0);
        $objActSheet ->setCellValue($EXCAB.$num, '时间');
        $count0 = 15 + 1;
        foreach ($titles as $tkey => $tval) {
            $EXCAB = $this->stringFromColumnIndex($count0);
            $objActSheet ->setCellValue($EXCAB.$num, $tval);
            $count0++;
        }  

        // 商品趋势统计数据标题
        $count1 = 15+$top_cate_count + 5;
        $EXCAB = $this->stringFromColumnIndex($count1);
        $objActSheet ->setCellValue($EXCAB.$num, '时间');
        $count0 = $count1 + 1;
        foreach ($product_title as $tkey => $tval) {
            $EXCAB = $this->stringFromColumnIndex($count0);
            $objActSheet ->setCellValue($EXCAB.$num, $tval);
            $count0++;
        }
        
    
        $num3 = 4;
        $num4 = 4;
        $num5 = 4;
        $num6 = 4;
        
        // 分类排行
        foreach($top_cate as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num3, $key+1);
            $objActSheet ->setCellValue('B'.$num3, $val['title']);
            $objActSheet ->setCellValue('C'.$num3, $val['buymoney']);
            $num3++;
        }    
    
    
        // 商品排行
        foreach($top20_product as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('H'.$num4, $key+1);
            $objActSheet ->setCellValue('I'.$num4, $val['title']);
            $objActSheet ->setCellValue('J'.$num4, $val['buymoney']);
            $num4++;
        }     
    
        foreach($data as $key => $val){
            $count = 15;
            foreach ($val as $kval => $vval) {
                $EXCAB = $this->stringFromColumnIndex($count);
                
                //echo $EXCAB.$num5 . '--' . $vval;exit;
                $objActSheet ->setCellValue($EXCAB.$num5, $vval);
                $count++;
            }
            $num5++;
        }     


        foreach($top20_data as $key => $val){
            $count = 15 + $top_cate_count + 5;
            foreach ($val as $kval => $vval) {
                $EXCAB = $this->stringFromColumnIndex($count);
                
                //echo $count . '--' . $EXCAB.$num6 . '--' . $vval;exit;
                $objActSheet ->setCellValue($EXCAB.$num6, $vval);
                $count++;
            }
            $num6++;
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
        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(18);//标题字体大小
        $objPHPExcel->getActiveSheet()->getStyle('H2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("H2")->getFont()->setSize(18);//标题字体大小
        $objPHPExcel->getActiveSheet()->getStyle('P2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("P2")->getFont()->setSize(18);//标题字体大小     
        $objPHPExcel->getActiveSheet()->getStyle("{$top_product_count1}2")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("{$top_product_count1}2")->getFont()->setSize(18);//标题字体大小        
        
        
        
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num3-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('H2:J'.(string)($num4-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle("P2:{$top_cate_count2}".(string)($num5-1))->applyFromArray($styleArray);//应用边框        
        $objPHPExcel->getActiveSheet()->getStyle("{$top_product_count1}2:{$top_product_count2}".(string)($num6-1))->applyFromArray($styleArray);//应用边框        
        
        
        
        
        
        $objPHPExcel->setActiveSheetIndex(0);
        
        
        
        
        
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }

    /**
     * 导出商品全局分析
     * @param $cate_money_20
     * @param $goods_money_20
     * @param $first
     * @param $first_table2
     * @param $fname
     * @return bool
     */
    public function OutProduct_new( $first, $goods_money_20, $fname)
    {
        /*
        print_r($product_title);
        print_r($top20_data);
        print_r($fname);
        exit;
        */
        if( !$fname ) {
            return false;
        }
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Asia/Shanghai');
        $objPHPExcel = new \PHPExcel();
        //以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
        $num=1;
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
        // 报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');//合并单元格
        $objActSheet ->setCellValue('A1', $fname);


        // 分类TOP报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');//合并单元格
        $objActSheet ->setCellValue('A2', '分类Top');

        // 商品TOP报表标题
        $objPHPExcel->getActiveSheet()->mergeCells('F2:j2');//合并单元格
        $objActSheet ->setCellValue('F2', '商品Top20');
        
        // 分类趋势统计报表标题
        $top_cate_count = count($first['legend']);
        $top_cate_count2 = $this->stringFromColumnIndex(12+$top_cate_count);
        $objPHPExcel->getActiveSheet()->mergeCells("M2:{$top_cate_count2}2");//合并单元格
        $objActSheet ->setCellValue('M2', '分类趋势');


        

        $num=3;
        //Excel的第A列，uid是你查出数组的键值，下面以此类推
        //数据标题

        $objActSheet ->setCellValue('A'.$num, '排行');;
        $objActSheet ->setCellValue('B'.$num, '分类名');
        $objActSheet ->setCellValue('C'.$num, '消费金额');

        $objActSheet ->setCellValue('F'.$num, '排行');
        $objActSheet ->setCellValue('G'.$num, '商品id');
        $objActSheet ->setCellValue('H'.$num, '商品名');
        $objActSheet ->setCellValue('I'.$num, '分类名');
        $objActSheet ->setCellValue('J'.$num, '销售数量');
        $objActSheet ->setCellValue('K'.$num, '消费金额');

        // 分类趋势统计数据标题
        $count0 = 12;
        $EXCAB = $this->stringFromColumnIndex($count0);
        $objActSheet ->setCellValue($EXCAB.$num, '时间');
        $count0 = 12 + 1;
        foreach ($first['legend'] as $tkey => $tval) {
            $EXCAB = $this->stringFromColumnIndex($count0);
            $objActSheet ->setCellValue($EXCAB.$num, $tval);
            $count0++;
        }
        
    
        $num3 = 4;
        $num4 = 4;
        $num5 = 4;
        $num6 = 4;

        $first_cate_array = array();
        foreach ($first['series'] as $kye=>$val){
            $first_cate_array[] = array(
                'goods_cate_name' => $val['name'],
                'buymoney'=> array_sum($val['data'])
            );
        }
        // 取得列的列表
        foreach ($first_cate_array as $key => $row)
        {
            $volume[$key]  = $row['buymoney'];
        }
        array_multisort($volume, SORT_DESC, $first_cate_array);
        // 分类排行
        foreach($first_cate_array as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('A'.$num3, $key+1);
            $objActSheet ->setCellValue('B'.$num3, $val['goods_cate_name']);
            $objActSheet ->setCellValue('C'.$num3, $val['buymoney']);
            $num3++;
        }    
    
    
        // 商品排行
        foreach($goods_money_20 as $key => $val){
            //写入数据
            $objActSheet ->setCellValue('F'.$num4, $key+1);
            $objActSheet ->setCellValue('G'.$num4, $val['goods_id']);
            $objActSheet ->setCellValue('H'.$num4, $val['goods_name']);
            $objActSheet ->setCellValue('I'.$num4, $val['goods_cate_name']);
            $objActSheet ->setCellValue('J'.$num4, $val['buynum']);
            $objActSheet ->setCellValue('K'.$num4, $val['buymoney']);
            $num4++;
        }     
    //分类趋势
       foreach($first['xAxis'] as $key => $val){
            $count = 12;
           $EXCAB = $this->stringFromColumnIndex($count);
           $objActSheet ->setCellValue($EXCAB.$num5, $val);
            foreach ($first['series'] as $kval => $vval) {
                $count++;
                $EXCAB = $this->stringFromColumnIndex($count);
                
                //echo $EXCAB.$num5 . '--' . $vval;exit;
                $objActSheet ->setCellValue($EXCAB.$num5, $vval['data'][$key]);

            }
            $num5++;
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
        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(18);//标题字体大小
        $objPHPExcel->getActiveSheet()->getStyle('F2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("F2")->getFont()->setSize(18);//标题字体大小
        $objPHPExcel->getActiveSheet()->getStyle('M2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        $objPHPExcel->getActiveSheet()->getStyle("M2")->getFont()->setSize(18);//标题字体大小
        
        
        
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN//全部做边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A2:C'.(string)($num3-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle('F2:K'.(string)($num4-1))->applyFromArray($styleArray);//应用边框
        $objPHPExcel->getActiveSheet()->getStyle("M2:{$top_cate_count2}".(string)($num5-1))->applyFromArray($styleArray);//应用边框
        
        
        
        
        $objPHPExcel->setActiveSheetIndex(0);
        
        
        
        
        
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;
    }
    
    

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

    /*******************导出用户分析~用户消费**************************/
    /* 导出excel函数*/
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
        $objActSheet->getColumnDimension('A')->setWidth(18);//设置A列宽度
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(18);
        $objActSheet->getColumnDimension('D')->setWidth(24);
        $objActSheet->getColumnDimension('E')->setWidth(24);
        $objActSheet->getColumnDimension('F')->setWidth(60);
        $objActSheet->getColumnDimension('G')->setWidth(24);
        $objActSheet->getColumnDimension('H')->setWidth(24);
        $objActSheet->getColumnDimension('I')->setWidth(24);
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
}