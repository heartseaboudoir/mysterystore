<?php
namespace Addons\Order\Controller;

use Admin\Controller\AddonsController;

class ShopOrderController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $status = I('status', 0, 'intval');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $keyword = I('keyword', '', 'trim');
        $shop_uid = I('shop_uid', 0, 'intval');
        $pre = C('DB_PREFIX');
        $order_db = "{$pre}order";
        $detail_db = "{$pre}order_detail";
        $where[$order_db.'.type'] = 'shop';
        $where['_string'] = '';
        $_string = array();
        $status && $_string[] = "status = {$status}";
        $shop_uid > 0 && $_string[] = "{$order_db}.store_id = ". $shop_uid;
        $Model = D('Addons://Order/Order');
        if($keyword){
            $_string[] = "({$order_db}.order_sn = '$keyword' or {$detail_db}.title like '%{$keyword}%')";
            $Model = D('Addons://Order/Order')->join('__ORDER_DETAIL__ ON __ORDER__.order_sn = __ORDER_DETAIL__.order_sn');
            $Model->group("{$order_db}.order_sn");
        }else{
            $Model = D('Addons://Order/Order');
        }
        if($start_time){
            $_start_time = strtotime($start_time);
            $_string[] = "{$order_db}.create_time >= {$_start_time}";
        }elseif($end_time){
            $_end_time = strtotime($end_time)+3600*24;
            $_string[] = "{$order_db}.create_time <= {$_end_time}";
        }
        if($start_time && $end_time){
            $_start_time = strtotime($start_time);
            $_end_time = strtotime($end_time)+3600*24;
            $_string[] = "{$order_db}.create_time >= {$_start_time} and {$order_db}.create_time <= {$_end_time}";
        }
        $where['_string'] = implode(' and ', $_string);
        $field = "{$order_db}.id,{$order_db}.type,{$order_db}.order_sn,{$order_db}.store_id,{$order_db}.pay_type,{$order_db}.uid,{$order_db}.pay_money,{$order_db}.status,{$order_db}.create_time,{$order_db}.update_time,{$order_db}.refund_status";
        $list = $this->lists($Model, $where, 'create_time desc', array(), $field);
        
        // ????????????????????????????????????
        if($keyword){
            $total_data = $Model->query("select count(*) as total  from(SELECT count(".$order_db.".order_sn) FROM ".$order_db." INNER JOIN ".$detail_db." ON hii_order.order_sn = ".$detail_db.".order_sn where {$where['_string']}  GROUP BY ".$order_db.".order_sn) as a");
            $total = isset($total_data[0]['total']) ? $total_data[0]['total'] : 0;
            $REQUEST    =   (array)I('request.');
            $page = new \Think\Page($total, 10, $REQUEST);
            if($total>10){
                $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            }
            $p =$page->show();
            $this->assign('_page', $p? $p: '');
            $this->assign('_total', $total);
        }
        $this->assign('list', $list);
        $this->meta_title = '??????????????????';
        $this->display(T('Addons://Order@ShopOrder/index'));
    }
    
    public function show() {
        $id = I('get.id','', 'intval');
        $order_sn = I('get.order_sn','', 'trim');
        if(!($id > 0) && !$order_sn){
            $this->error('???????????????!');
        }
        $where = array();
        $where['type'] = 'shop';
        $id > 0 && $where['id'] = $id;
        $order_sn && $where['order_sn'] = $order_sn;
        $Order = D('Addons://Order/Order');
        $data = $Order->where($where)->find();
        if($data['pay_status'] == 2){
            $log = M('OrderPayLog')->where(array('order_sn' => $data['order_sn']))->find();
            if($log['pay_type'] == 1){
                $pay_log = M('WechatPayRecord')->where(array('order_sn' => $data['order_sn']))->find();
                $pay_log['return_data'] = json_decode($pay_log['return_data'], true);
                $user = M('WechatUser')->where(array('openid' => $pay_log['return_data']['openid']))->find();
                $this->assign('wx_user', $user);
                $this->assign('wx_pay_log', $pay_log);
            }elseif($log['pay_type'] == 2){
                $pay_log = M('AlipayRecord')->where(array('order_sn' => $data['order_sn'], 'status' => 2))->find();
                $pay_log['return_data'] = json_decode($pay_log['return_data'], true);
                $this->assign('ali_pay_log', $pay_log);
            }
            $this->assign('log', $log);
        }
        $this->assign('data', $data);
        $where['order_sn'] = $data['order_sn'];
        //$detail = D('Addons://Order/OrderDetail')->where($where)->select();
        $detail = M('OrderDetail')->where(array('order_sn' => $data['order_sn']))->select();
        $this->assign('detail', $detail);
        $this->meta_title = '????????????';
        $this->display(T('Addons://Order@ShopOrder/show'));
    }
    public function update(){
        if(IS_POST){
            $id = I('id', 0, 'intval');
            $where = array();
            $where['type'] = 'shop';
            $where['id'] = $id;
            $Model = D('Addons://Order/Order');
            $info = $Model->where($where)->find();
            if(!$info){
                $this->error('???????????????');
            }
            $data = $_POST;
            // ???????????????
            if(isset($data['receipt_info'])){
                foreach($data['receipt_info'] as $v){
                    if(!$v){
                        $this->error('???????????????????????????????????????');
                    }
                }
                $data['receipt_info'] = json_encode($data['receipt_info']);
            }
            
            // ????????????
            if(in_array($info['status'], array(2,4)) && isset($data['express_info']) && isset($data['express_do']) && $data['express_do'] == 1){
                $receipt_info = $data['express_info'];
                foreach($data['express_info'] as $v){
                    if(!$v){
                        $this->error('?????????????????????????????????????????????');
                    }
                }
                $Model->change_delivery($info['order_sn'], $data['express_info']['name'], $data['express_info']['no']);
                $data['express_info'] = json_encode($data['express_info']);
            }
            if($data){
                if($Model->create($data)){
                    if($Model->save()){
                        $this->success('????????????', Cookie('__forward__'));
                    }
                }
            }
            $this->error('????????????');
        }else{
            $this->error('????????????');
        }
    }
    public function download_order_old(){
        $s_date = I('s_date');
        $e_date = I('e_date');
        if(!$s_date || !$e_date){
            $this->meta_title = '???'.session('user_store.title').'???'.' ????????????';
            $this->display(T('Addons://Order@ShopOrder/download_order'));
            exit;
        }
        $store_sell_type = session('user_store.sell_type');
        if($store_sell_type == 2){
            $where = array(
                'status' => array('egt', 2),
                'store_id' => $this->_store_id,
            );
        }else{
            $where = array(
                'status' => 2,
                'store_id' => $this->_store_id,
            );
        }
        $title = '???'.session('user_store.title').'???';
        $title2 = '<strong>?????????</strong>'.session('user_store.title'). ' ';
        if($s_date && $e_date){
            if(strtotime($s_date) > strtotime($e_date)){
                $e_date = $s_date;
            }
            $where['create_time'] = array('between', array(strtotime($s_date), strtotime($e_date)+3600*24));
            $title .= '???????????????'.$s_date.'???'.$e_date.'???';
            $title2 .= '<strong>?????????????????????</strong>'.$s_date.'???'.$e_date;
        }
        $order = D('Addons://Order/Order')->where($where)->select();
        if(!$order){
            $this->meta_title = '???'.session('user_store.title').'???'.' ????????????';
            $err_msg = '???????????????????????????????????????';
            $this->assign('err_msg', $err_msg);
            $this->display(T('Addons://Order@ShopOrder/download_order'));
            exit;
        }
        
        $order_sn = array();
        foreach($order as $v){
            $order_sn[] = $v['order_sn'];
        }
        $where = array(
            'order_sn' => array('in', implode(',', $order_sn))
        );
        $detail = D('Addons://Order/OrderDetail')->where($where)->select();
        $_goods_id = array();
        foreach($detail as $v){
            $v['goods_log'] = json_decode($v['goods_log'], true);
            $_detail[$v['order_sn']][] = $v;
        }
        $detail = $_detail;
        $cate = M('GoodsCate')->select();
        !$cate && $cate = array();
        $cate_title = array();
        foreach($cate as $v){
            $cate_title[$v['id']] = $v['title'];
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename='.$title.'.xls');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $l = '<style>'
                . 'body{font-size:16px;}'
                . 'img{max-height:150px; max-width:200px;}'
                . 'tr{height:50px;}'
                . '.tr0{background:#eee;}'
                . 'td{border:1px solid #ccc; text-align:center;}'
                . '</style>';
        $l .= '<table>';
        $td_field = array(
            array('w' => 200, 't' => '?????????'),array('w' => 100, 't' => '??????UID'),array('w' => 200, 't' => '?????????'),array('w' => 80, 't' => '????????????'),array('w' => 150, 't' => '??????????????????'),array('w' => 150, 't' => '??????????????????'),array('w' => 80, 't' => '????????????'),array('w' => 80, 't' => '????????????'),
            array('w' => 85, 't' => '??????????????????'),array('w' => 85, 't' => '??????????????????'),array('w' => 100, 't' => '???????????????'),array('w' => 80, 't' => '????????????'),array('w' => 80, 't' => '??????ID'),array('w' => 150, 't' => '?????????'),array('w' => 120, 't' => '????????????'),array('w' => 80, 't' => '????????????'),array('w' => 80, 't' => '????????????'),array('w' => 80, 't' => '?????????'),
        );
        if($store_sell_type == 2){
            $td_field[] = array(
                'w' => 300,
                't' => '????????????'
            );
            $td_field[] = array(
                'w' => 200,
                't' => '????????????'
            );
            $td_field[] = array(
                'w' => 150,
                't' => '????????????'
            );
        }
        $l .= '<tr style="height:60px;"><td style="text-align:left;font-size:20px;" colspan="'.count($td_field).'">'.$title2.'</td></tr>';
        $l .= '<tr style="background:#aaa; font-weight:bold;">';
        foreach($td_field as $v){
            $l .= '<td width="'.$v['w'].'">'.$v['t'].'</td>';
        }
        $l .= '</tr>';
        foreach($order as $key => $v){
            switch($v['pay_type']){
                case 1:
                    $pay_type = '??????';
                    break;
                case 2:
                    $pay_type = '?????????';
                    break;
                default:
                    $pay_type = '';
            }
            switch($v['pay_app']){
                case 'wechat':
                    $pay_app = '??????';
                    break;
                case 'alipay':
                    $pay_app = '?????????';
                    break;
                case 'account_app':
                    $pay_app = '???????????????';
                    break;
                default:
                    $pay_app = '';
            }
            $goods_id = array();
            $goods_title = array();
            $goods_cate = array();
            $goods_price = array();
            $goods_num = array();
            $goods_money = array();
            foreach($detail[$v['order_sn']] as $d){
                $goods_id[] = $d['d_id'];
                $goods_title[] = $d['title'];
                $goods_cate[] = $cate_title[$d['goods_log']['cate_id']];
                $goods_price[] = $d['price'];
                $goods_num[] = $d['num'];
                $goods_money[] = $d['price']*$d['num'];
            }
            
            $data1 = array(
                '&nbsp;'.$v['order_sn'], $v['uid'], '&nbsp;'.get_nickname($v['uid']), $pay_type, time_format($v['create_time'], 'Y-m-d H:i'), time_format($v['update_time'], 'Y-m-d H:i'), $v['money'], $v['pay_money'], $v['cash_money'], $v['user_discount_money'], $pay_app, $v['type'] == 'online' ? '????????????' : '????????????'
            );
            $data2 = array(
                $goods_id,
                $goods_title,
                $goods_cate,
                $goods_price,
                $goods_num,
                $goods_money,
            );
            $rowspan = count($goods_id);
            $l .= '<tr class="tr'.($key%2).'">';
            foreach ($data1 as $value) {
                    $l .= '<td rowspan="'.$rowspan.'">'.$value.'</td>';
            }
            foreach ($data2 as $value) {
                    $l .= '<td>'.$value[0].'</td>';
                    unset($value[0]);
            }
            if($store_sell_type == 2){
                $receipt_str = $v['receipt_info'] ? ('<strong>????????????</strong>'.$v['receipt_info']['name'].' <strong>???????????????</strong>'.$v['receipt_info']['mobile'].'<br><strong>?????????</strong>'.$v['receipt_info']['sheng_title'].$v['receipt_info']['shi_title'].$v['receipt_info']['qu_title'].$v['receipt_info']['address']) : '';
                
                $express_str = $v['status'] >= 4 ? ('???????????????'.$v['express_info']['company'].'<br>'.'???????????????'.$v['express_info']['no']) : '?????????';
                $data3 = array(
                    $receipt_str, $express_str, $v['status'] >= 4 ? date('Y-m-d H:i', $v['express_time']) : '-'
                );
                foreach ($data3 as $k => $value) {
                    $l .= '<td rowspan="'.$rowspan.'" style="'.($k < 2 ? ('text-align:left;') : '').'">'.$value.'</td>';
                }
            }
            $l .= '</tr>';
            
            for($i = 1; $i < $rowspan; $i++){
                $l .= '<tr class="tr'.($key%2).'">';
                foreach ($data2 as $value) {
                    $l .= '<td>'.$value[$i].'</td>';
                }
                $l .= '</tr>';
            }
        }
        $l .= '</table>';echo $l;exit;
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }
    
    
    
    public function download_order()
    {
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Asia/Shanghai');
        $objPHPExcel = new \PHPExcel();
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);        
        
        
        $keyword = I('keyword');
        $start_time = I('start_time');
        $end_time = I('end_time');
        
        
        $where = "o.type = 'shop' and o.pay_status = 2 and o.status in (2, 4, 5, 6)";
        if (!empty($keyword)) {
            $where .= " and order_sn = '{$keyword}'";
        }
        
        
        if (!empty($start_time)) {
            $stime = strtotime($start_time);
            $where .= " and o.create_time >= {$stime}";
        }
        
        if (!empty($end_time)) {
            $etime = strtotime($end_time) + 3600*24;
            $where .= " and o.create_time <= {$etime}";            
        }
        
        
        
        
        
        $sql = "select o.*,m1.nickname as nickname1,m2.nickname as nickname2 from hii_order o 
        left join hii_member m1 on m1.uid = o.uid 
        left join hii_member m2 on m2.uid = o.store_id 
        where {$where} order by o.create_time asc limit 99999";
        

        //echo $sql;exit;
        $datas = M()->query($sql);

        
        
        $num = 1;
        
        $objActSheet ->setCellValue('A' . $num, '?????????');
        $objActSheet ->setCellValue('B' . $num, '?????????');
        $objActSheet ->setCellValue('C' . $num, '????????????');        
        $objActSheet ->setCellValue('D' . $num, '????????????');        
        $objActSheet ->setCellValue('E' . $num, '??????');        
        $objActSheet ->setCellValue('F' . $num, '????????????');        
        $objActSheet ->setCellValue('G' . $num, '????????????');        
        
        $objActSheet->getColumnDimension('A')->setWidth(28);
        $objActSheet->getColumnDimension('B')->setWidth(18);
        $objActSheet->getColumnDimension('C')->setWidth(18);
        $objActSheet->getColumnDimension('D')->setWidth(18);
        $objActSheet->getColumnDimension('E')->setWidth(18);
        $objActSheet->getColumnDimension('F')->setWidth(28);
        $objActSheet->getColumnDimension('G')->setWidth(28);

        
        $num++;
        
        foreach($datas as $key => $val){
            if ($val['pay_type'] == 1) {
                $pay_type_name = '??????';
            } elseif ($val['pay_type'] == 2) {
                $pay_type_name = '?????????';
            } else {
                $pay_type_name = '';
            }
            
            
            
            
            
        if (isset($val['status'])) {
            switch($val['status']){
                case 1:
                    $val['status_text'] = '?????????';
                    break;
                case 2:
                    $val['status_text'] = '?????????';
                    break;
                case 3:
                    $val['status_text'] = '?????????';
                    break;
                case 4:
                    $val['status_text'] = '?????????';
                    break;
                case 5:
                    $val['status_text'] = '?????????';
                    break;
                case 6:
                    $val['status_text'] = '?????????';
                    break;
                default:
                $val['status_text'] = '??????????????????';
            }
            if(!empty($val['refund_status'])){
                switch($val['refund_status']){
                    case 1:
                        $val['status_text'] = '?????????(???????????????)';
                        break;
                    case 2:
                        $val['status_text'] = '?????????(?????????)';
                        break;
                    case 3:
                        $val['status_text'] = '????????????(?????????)';
                        break;
                    case 4:
                        $val['status_text'] = '?????????(????????????)';
                        break;                        
                }
            }
            

            
            
        } else {
            $val['status_text'] = '';
        }            
            
            //????????????
            $objActSheet ->setCellValue('A'.$num, $val['order_sn']);
            $objActSheet ->setCellValue('B'.$num, $val['nickname1']);
            $objActSheet ->setCellValue('C'.$num, $val['nickname2']);
            $objActSheet ->setCellValue('D'.$num, $val['pay_money']);
            $objActSheet ->setCellValue('E'.$num, $val['status_text']);
            $objActSheet ->setCellValue('F'.$num, date('Y-m-d H:i:s', $val['create_time']));
            $objActSheet ->setCellValue('G'.$num, $pay_type_name);
            $num++;
        }      
        
        $fname = '????????????';
        
        
        
        
        
        
        
        
        $objPHPExcel->setActiveSheetIndex(0);
        // excel?????????
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //????????????????????????
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5???xls?????????excel2007???xlsx??????
        $objWriter->save('php://output');//??????
        die;        
    }
    
    public function refund(){
        $order_sn = I('order_sn', '' , 'trim');
        $info = D('Addons://Order/Order')->find();
    }
}
