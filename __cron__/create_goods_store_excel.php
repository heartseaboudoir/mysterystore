<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-31
 * Time: 13:58
 * 下载库存Excel
 */
require_once "../ThinkPHP/Library/Org/Util/PHPExcel.class.php";
header("Content-type: text/html; charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'new_swift');
date_default_timezone_set("PRC");

$time_limit = 600;
set_time_limit($time_limit);

// 连接数据库
$db_config = @include ROOT_PATH . 'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
define('DB_PRE', $db_config['DB_PREFIX']);

$shequ = "杭州001号仓库";
$shequ_array = array(14);

$sql = "select GS.goods_id,G.title as goods_name,GC.title as cate_name,SUM(GS.num) as stock_num ";
$sql .= "from hii_goods_store GS ";
$sql .= "left join hii_goods G on G.id=GS.goods_id ";
$sql .= "left join hii_goods_cate GC on GC.id=G.cate_id ";
$sql .= "left join hii_store S on S.id=GS.store_id ";
$sql .= "where S.id=138 and GS.num>0 and S.shequ_id in (" . implode(",", $shequ_array) . ") ";
$sql .= "group by GS.goods_id,G.title,GC.title ";
$sql .= "order by GS.goods_id asc ";
//echo $sql."<br/>";
$list = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
//echo "<pre>";print_r($list);echo "<pre>";exit;
if (!$list) {
    echo "数据获取失败";
    exit;
}
$title = $shequ . "期初价目表";
//import("Org.Util.PHPExcel");
//vendor("excel.PHPExcel");
//vendor("excel.IOFactory");
$objPHPExcel = new \PHPExcel();
//以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改
$EndC = "E";
$objActSheet = $objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->mergeCells('A1:' . $EndC . '1');//合并单元格
//第一行>>>报表标题
$objActSheet->setCellValue('A1', $title);
$objActSheet->setCellValue('A2', '商品ID');
$objActSheet->setCellValue('B2', '商品类别');
$objActSheet->setCellValue('C2', '商品名称');
$objActSheet->setCellValue('D2', '库存数量');
$objActSheet->setCellValue('E2', '进货价');
date_default_timezone_set("PRC");
$i = 3;
foreach ($list as $key => $val) {
    //写入数据
    $objActSheet->setCellValue('A' . $i, $val['goods_id']);
    $objActSheet->setCellValue('B' . $i, $val['cate_name']);
    $objActSheet->setCellValue('C' . $i, $val['goods_name']);
    $objActSheet->setCellValue('D' . $i, $val['stock_num']);
    $objActSheet->setCellValue('E' . $i, "");
    $i++;
}
$objActSheet->getColumnDimension('A')->setWidth(12);//设置A列宽度
$objActSheet->getColumnDimension('B')->setWidth(24);
$objActSheet->getColumnDimension('C')->setWidth(96);
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
//$objPHPExcel->getActiveSheet()->getStyle('A5:I' . (string)(6 - 1))->applyFromArray($styleArray);//应用边框

$objPHPExcel->setActiveSheetIndex(0);
// excel头参数
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');  //日期为文件名后缀
header('Cache-Control: max-age=0');

$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
$objWriter->save('php://output');//输出

exit;