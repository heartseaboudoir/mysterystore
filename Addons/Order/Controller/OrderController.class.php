<?php

namespace Addons\Order\Controller;

use Admin\Controller\AddonsController;

class OrderController extends AddonsController
{

    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
        $this->check_store();
    }

    public function index($where = array())
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $status = I('status', 0, 'intval');
        $type = I('type');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $keyword = I('keyword', '', 'trim');
        $keys = I('keys', '', 'trim');

        
        $type = array('store');
        //$where['store_id'] = $this->_store_id;
        //$type && $where['status'] = $type;
        $pre = C('DB_PREFIX');
        $order_db = "{$pre}order";
        $detail_db = "{$pre}order_detail";
        $where[$order_db . '.type'] = array('in', $type ? $type : 'store,online');
        $where['_string'] = '';
        $_string = array();
        $_string = array();
        $status && $_string[] = "status = {$status}";
        $_string[] = "{$order_db}.store_id = " . $this->_store_id;

        $field = "{$order_db}.id,{$order_db}.type,{$order_db}.order_sn,{$order_db}.pay_sn,{$order_db}.pay_type,{$order_db}.uid,{$order_db}.pay_money,SUM({$detail_db}.inout_price_all) as inout_price_all ,{$order_db}.status,{$order_db}.create_time,{$order_db}.update_time";
        $groupby = "{$order_db}.id,{$order_db}.type,{$order_db}.order_sn,{$order_db}.pay_sn,{$order_db}.pay_type,{$order_db}.uid,{$order_db}.pay_money,{$order_db}.status,{$order_db}.create_time,{$order_db}.update_time";

        $Model = D('Addons://Order/Order');
        if ($keyword) {
            if($keys=="") {
                $_string[] = " ( hii_order.order_sn like '%{$keyword}%' or hii_order.order_sn in (select order_sn from hii_order_detail left join hii_goods on hii_goods.id=hii_order_detail.d_id where hii_goods.title like '%{$keyword}%' ) or hii_order.uid in (select id as uid from hii_ucenter_member where mobile like '%{$keyword}%' )) ";
            }elseif($keys=="order_sn") {
                $_string[] = " ( hii_order.order_sn like '%{$keyword}%') ";
            }elseif($keys=="goods_name") {
                $_string[] = " ( hii_order.order_sn in (select order_sn from hii_order_detail left join hii_goods on hii_goods.id=hii_order_detail.d_id where hii_goods.title like '%{$keyword}%' ) ) ";
            }elseif($keys=="mobile") {
                $_string[] = " ( hii_order.uid in (select id as uid from hii_ucenter_member where mobile like '%{$keyword}%' ) ) ";
            }
            //$_string[] = " ( hii_order.order_sn like '%{$keyword}%' or hii_order.order_sn in (select order_sn from hii_order_detail left join hii_goods on hii_goods.id=hii_order_detail.d_id where hii_goods.title like '%{$keyword}%' ) ) ";
            //$_string[] = "({$order_db}.order_sn = '$keyword' or {$detail_db}.title like '%{$keyword}%')";
            $Model = D('Addons://Order/Order');//->join('__ORDER_DETAIL__ ON __ORDER__.order_sn = __ORDER_DETAIL__.order_sn');
            //$p = I('p', 1, 'intval');
            //$p < 1 && $p = 1;
            //$detail = D('Addons://Order/OrderDetail')->where(array('title' => array('like', '%'.$keyword.'%'), 'store_id' => $this->_store_id))->group('order_sn')->page($p, 10)->select();
//            $order_sn[] = $keyword;
//            foreach($detail as $v){
//                $order_sn[] = $v['order_sn'];
//            }
//            $where['order_sn'] = array('in', $order_sn);
            //$Model->group("{$order_db}.order_sn")->join('__ORDER_DETAIL__ ON __ORDER__.order_sn = __ORDER_DETAIL__.order_sn');
            $Model->group($groupby)->join('__ORDER_DETAIL__ ON __ORDER__.order_sn = __ORDER_DETAIL__.order_sn');
        } else {
            $Model = D('Addons://Order/Order')->group($groupby)->join('left join __ORDER_DETAIL__ ON __ORDER__.order_sn = __ORDER_DETAIL__.order_sn');
        }
        if ($start_time) {
            $_start_time = strtotime($start_time);
            $_string[] = "{$order_db}.create_time >= {$_start_time}";
            //$where['create_time'] = array('egt', $start_time);
        } elseif ($end_time) {
            $_end_time = strtotime($end_time) + 3600 * 24;
            $_string[] = "{$order_db}.create_time <= {$_end_time}";
            //$where['create_time'] = array('elt', $end_time);
        }
        if ($start_time && $end_time) {
            $_start_time = strtotime($start_time);
            $_end_time = strtotime($end_time) + 3600 * 24;
            $_string[] = "{$order_db}.create_time >= {$_start_time} and {$order_db}.create_time <= {$_end_time}";
            //$where['create_time'] = array('between', array($start_time, $end_time));
        }
        $where['_string'] = implode(' and ', $_string);
        $where['_type'] = "";
        foreach ($type as $key => $val) {
            $where['_type'] .= "'{$val}',";
        }
        if (!empty($where['_type'])) {
            $where['_type'] = " AND ( hii_order.type IN (" . substr($where['_type'], 0, strlen($where['_type']) - 1) . "))";
        }

        // WHERE ( hii_order.type IN ('store','online') )
        // AND ( hii_order.store_id = 5 and (hii_order.order_sn = '????????????' or hii_order_detail.title like '%????????????%')
        // and hii_order.create_time >= 1517414400 and hii_order.create_time >= 1517414400 and hii_order.create_time <= 1517760000 )

        //echo $where['_string'];exit;


        $sql = "SELECT hii_order.id,hii_order.type,hii_order.order_sn,hii_order.pay_sn,
hii_order.pay_type,hii_order.uid,hii_order.pay_money,SUM(hii_order_detail.inout_price_all) as inout_price_all,
hii_order.status,hii_order.pay_status,hii_order.create_time,hii_order.update_time 
FROM `hii_order` 
left join hii_order_detail ON hii_order.order_sn = hii_order_detail.order_sn 
WHERE 1=1 {$where['_type']} AND {$where['_string']}
GROUP BY hii_order.id,hii_order.type,hii_order.order_sn,hii_order.pay_sn,hii_order.pay_type,hii_order.uid,hii_order.pay_money,hii_order.status,hii_order.create_time,hii_order.update_time 
ORDER BY create_time DESC ";

        if (I("get.showsql") == "true") {
            echo $sql;
            exit;
        }

        $Model = D('Addons://Order/Order');
        $data = $Model->query($sql);

        //??????
        $pcount = 15;
        $count = count($data);//????????????????????????
        $Page = new \Think\Page($count, $pcount);// ?????????????????? ?????????????????????????????????????????????
        $datamain = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// ?????????????????????
		//????????????????????????????????????
        $seeprice = $this->checkFunc('OrderPriceView');
        foreach ($datamain as $key => $val) {
			if(!$seeprice){
                $datamain[$key]["inout_price_all"] = "*";
            }
              switch ($val["pay_status"]){
                  case 1:{$datamain[$key]["status_text"]="?????????";};break;
                  case 2:{$datamain[$key]["status_text"]="?????????";};break;
              }
        }

        $this->assign('list', $datamain);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);

        $this->meta_title = '???' . session('user_store.title') . '???' . ' ????????????';
        $this->display(T('Addons://Order@Order/index'));
        exit;


        //$field = '*';
        //$list = $this->lists2($Model, $where, 'create_time desc', array(), $field,"left join hii_order_detail on hii_order_detail.order_sn=hii_order.order_sn ", $groupby);
        $list = $this->lists($Model, $where, 'create_time desc', array(), $field);

        if (I("get.showsql") == "true") {
            echo $Model->_sql();
            exit;
        }
        // ????????????????????????????????????
        if ($keyword) {
            $total_data = $Model->query("select count(*) as total  from(SELECT count(hii_order.order_sn) FROM `hii_order` INNER JOIN hii_order_detail ON hii_order.order_sn = hii_order_detail.order_sn where {$where['_string']}  GROUP BY hii_order.order_sn) as a");
            $total = isset($total_data[0]['total']) ? $total_data[0]['total'] : 0;
            $REQUEST = (array)I('request.');
            $page = new \Think\Page($total, 10, $REQUEST);
            if ($total > 10) {
                $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            }
            $p = $page->show();
            $this->assign('_page', $p ? $p : '');
            $this->assign('_total', $total);
        }
        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->meta_title = '???' . session('user_store.title') . '???' . ' ????????????';
        $this->display(T('Addons://Order@Order/index'));
    }

    public function indexOld()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $status = I('status', 0, 'intval');
        $type = I('type');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $keyword = I('keyword', '', 'trim');

        //$where['store_id'] = $this->_store_id;
        //$type && $where['status'] = $type;
        $pre = C('DB_PREFIX');
        $order_db = "{$pre}order";
        $detail_db = "{$pre}order_detail";
        $where[$order_db . '.type'] = array('in', $type ? $type : 'store,online');
        $where['_string'] = '';
        $_string = array();
        $_string = array();
        $status && $_string[] = "status = {$status}";
        $_string[] = "{$order_db}.store_id = " . $this->_store_id;
        $Model = D('Addons://Order/Order');
        if ($keyword) {
            $_string[] = "({$order_db}.order_sn = '$keyword' or {$detail_db}.title like '%{$keyword}%')";
            $Model = D('Addons://Order/Order')->join('__ORDER_DETAIL__ ON __ORDER__.order_sn = __ORDER_DETAIL__.order_sn');
            //$p = I('p', 1, 'intval');
            //$p < 1 && $p = 1;
            //$detail = D('Addons://Order/OrderDetail')->where(array('title' => array('like', '%'.$keyword.'%'), 'store_id' => $this->_store_id))->group('order_sn')->page($p, 10)->select();
//            $order_sn[] = $keyword;
//            foreach($detail as $v){
//                $order_sn[] = $v['order_sn'];
//            }
//            $where['order_sn'] = array('in', $order_sn);
            $Model->group("{$order_db}.order_sn");
        } else {
            $Model = D('Addons://Order/Order');
        }
        if ($start_time) {
            $_start_time = strtotime($start_time);
            $_string[] = "{$order_db}.create_time >= {$_start_time}";
            //$where['create_time'] = array('egt', $start_time);
        } elseif ($end_time) {
            $_end_time = strtotime($end_time) + 3600 * 24;
            $_string[] = "{$order_db}.create_time <= {$_end_time}";
            //$where['create_time'] = array('elt', $end_time);
        }
        if ($start_time && $end_time) {
            $_start_time = strtotime($start_time);
            $_end_time = strtotime($end_time) + 3600 * 24;
            $_string[] = "{$order_db}.create_time >= {$_start_time} and {$order_db}.create_time <= {$_end_time}";
            //$where['create_time'] = array('between', array($start_time, $end_time));
        }
        $where['_string'] = implode(' and ', $_string);
        //$field = '*';
        $field = "{$order_db}.id,{$order_db}.type,{$order_db}.order_sn,{$order_db}.pay_sn,{$order_db}.pay_type,{$order_db}.uid,{$order_db}.pay_money,SUM({$detail_db}.inout_price_all) as inout_price_all ,{$order_db}.status,{$order_db}.create_time,{$order_db}.update_time";
        $groupby = "{$order_db}.id,{$order_db}.type,{$order_db}.order_sn,{$order_db}.pay_sn,{$order_db}.pay_type,{$order_db}.uid,{$order_db}.pay_money,{$order_db}.status,{$order_db}.create_time,{$order_db}.update_time";
        $list = $this->lists2($Model, $where, 'create_time desc', array(), $field, "left join hii_order_detail on hii_order_detail.order_sn=hii_order.order_sn ", $groupby);
        // ????????????????????????????????????
        if ($keyword) {
            $total_data = $Model->query("select count(*) as total  from(SELECT count(hii_order.order_sn) FROM `hii_order` INNER JOIN hii_order_detail ON hii_order.order_sn = hii_order_detail.order_sn where {$where['_string']}  GROUP BY hii_order.order_sn) as a");
            $total = isset($total_data[0]['total']) ? $total_data[0]['total'] : 0;
            $REQUEST = (array)I('request.');
            $page = new \Think\Page($total, 10, $REQUEST);
            if ($total > 10) {
                $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            }
            $p = $page->show();
            $this->assign('_page', $p ? $p : '');
            $this->assign('_total', $total);
        }
        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->meta_title = '???' . session('user_store.title') . '???' . ' ????????????';
        $this->display(T('Addons://Order@Order/index'));
    }

    public function show()
    {
        $id = I('get.id', '', 'intval');
        $order_sn = I('get.order_sn', '', 'trim');
        if (!($id > 0) && !$order_sn) {
            $this->error('???????????????!');
        }
        $where = array();
        $where['store_id'] = $this->_store_id;
        $where['type'] = array('in', 'store,online');
        $id > 0 && $where['id'] = $id;
        $order_sn && $where['order_sn'] = $order_sn;
        $Order = D('Addons://Order/Order');
        $data = $Order->where($where)->find();
        if ($data['pay_status'] == 2) {
            $log = M('OrderPayLog')->where(array('order_sn' => $data['order_sn']))->find();
            if ($log['pay_type'] == 1) {
                $pay_log = M('WechatPayRecord')->where(array('order_sn' => $data['order_sn']))->find();
                $pay_log['return_data'] = json_decode($pay_log['return_data'], true);
                $user = M('WechatUser')->where(array('openid' => $pay_log['return_data']['openid']))->find();
                $this->assign('wx_user', $user);
                $this->assign('wx_pay_log', $pay_log);
            } elseif ($log['pay_type'] == 2) {
                $pay_log = M('AlipayRecord')->where(array('order_sn' => $data['order_sn'], 'status' => 2))->find();
                $pay_log['return_data'] = json_decode($pay_log['return_data'], true);
                $this->assign('ali_pay_log', $pay_log);
            }
            $this->assign('log', $log);
        }
        $this->assign('data', $data);
        $where = array();
        $where['order_sn'] = $data['order_sn'];
        $detail = D('Addons://Order/OrderDetail')->where($where)->select();
		//???????????????????????????????????????
        $seeprice = $this->checkFunc('OrderPriceView');
        if(!$seeprice){
            foreach($detail as $key=>$val){
                $detail[$key]["inout_price_one"] = "*";
            }
        }
        $this->assign('detail', $detail);
        $this->meta_title = '????????????';
        $this->display(T('Addons://Order@Order/show'));
    }

    public function update()
    {
        if (IS_POST) {
            $id = I('id', 0, 'intval');
            $where = array();
            $where['store_id'] = $this->_store_id;
            $where['type'] = array('in', 'store,online');
            $where['id'] = $id;
            $Model = D('Addons://Order/Order');
            $info = $Model->where($where)->find();
            if (!$info) {
                $this->error('???????????????');
            }
            $data = $_POST;
            // ???????????????
            if (isset($data['receipt_info'])) {
                foreach ($data['receipt_info'] as $v) {
                    if (!$v) {
                        $this->error('???????????????????????????????????????');
                    }
                }
                $data['receipt_info'] = json_encode($data['receipt_info']);
            }

            // ????????????
            if (in_array($info['status'], array(2, 4)) && isset($data['express_info']) && isset($data['express_do']) && $data['express_do'] == 1) {
                $receipt_info = $data['express_info'];
                foreach ($data['express_info'] as $v) {
                    if (!$v) {
                        $this->error('?????????????????????????????????????????????');
                    }
                }
                if ($info['status'] == 2) {
                    $data['status'] = 4;
                    $data['express_time'] = NOW_TIME;
                    $Model->send_delivery_notify($info['order_sn'], $data['express_info']['name'], $data['express_info']['no']);
                }
                $data['express_info'] = json_encode($data['express_info']);
            }
            if ($data) {
                if ($Model->create($data)) {
                    if ($Model->save()) {
                        $this->success('????????????', Cookie('__forward__'));
                    }
                }
            }
            $this->error('????????????');
        } else {
            $this->error('????????????');
        }
    }

    public function download_order()
    {
        $s_date = I('s_date');
        $e_date = I('e_date');
        if (!$s_date || !$e_date) {
            $this->meta_title = '???' . session('user_store.title') . '???' . ' ????????????';
            $this->display(T('Addons://Order@Order/download_order'));
            exit;
        }
        $store_sell_type = session('user_store.sell_type');
        if ($store_sell_type == 2) {
            $where = array(
                'status' => array('egt', 2),
                'store_id' => $this->_store_id,
            );
        } else {
            $where = array(
                'status' => 5,
                'store_id' => $this->_store_id,
            );
        }
        $title = '???' . session('user_store.title') . '???';
        $title2 = '<strong>?????????</strong>' . session('user_store.title') . ' ';
        if ($s_date && $e_date) {
            if (strtotime($s_date) > strtotime($e_date)) {
                $e_date = $s_date;
            }
            $where['create_time'] = array('between', array(strtotime($s_date), strtotime($e_date) + 3600 * 24));
            $title .= '???????????????' . $s_date . '???' . $e_date . '???';
            $title2 .= '<strong>?????????????????????</strong>' . $s_date . '???' . $e_date;
        }
        $order = D('Addons://Order/Order')->where($where)->select();
        if (!$order) {
            $this->meta_title = '???' . session('user_store.title') . '???' . ' ????????????';
            $err_msg = '???????????????????????????????????????';
            $this->assign('err_msg', $err_msg);
            $this->display(T('Addons://Order@Order/download_order'));
            exit;
        }

        $order_sn = array();
        foreach ($order as $v) {
            $order_sn[] = $v['order_sn'];
        }
        $where = array(
            'order_sn' => array('in', implode(',', $order_sn))
        );
        $detail = D('Addons://Order/OrderDetail')->where($where)->select();
        $_goods_id = array();
        foreach ($detail as $v) {
            $v['goods_log'] = json_decode($v['goods_log'], true);
            $_detail[$v['order_sn']][] = $v;
        }
        $detail = $_detail;
        $cate = M('GoodsCate')->select();
        !$cate && $cate = array();
        $cate_title = array();
        foreach ($cate as $v) {
            $cate_title[$v['id']] = $v['title'];
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $title . '.xls');
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
            array('w' => 200, 't' => '?????????'), array('w' => 200, 't' => '???????????????'), array('w' => 100, 't' => '??????UID'), array('w' => 200, 't' => '?????????'), array('w' => 80, 't' => '????????????'), array('w' => 150, 't' => '??????????????????'), array('w' => 150, 't' => '??????????????????'), array('w' => 80, 't' => '????????????'), array('w' => 80, 't' => '????????????'),
            array('w' => 85, 't' => '??????????????????'), array('w' => 85, 't' => '??????????????????'), array('w' => 100, 't' => '???????????????'), array('w' => 80, 't' => '????????????'), array('w' => 80, 't' => '??????ID'), array('w' => 150, 't' => '?????????'), array('w' => 120, 't' => '????????????'), array('w' => 80, 't' => '????????????'), array('w' => 80, 't' => '????????????'), array('w' => 80, 't' => '?????????'),
        );
        if ($store_sell_type == 2) {
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
        $l .= '<tr style="height:60px;"><td style="text-align:left;font-size:20px;" colspan="' . count($td_field) . '">' . $title2 . '</td></tr>';
        $l .= '<tr style="background:#aaa; font-weight:bold;">';
        foreach ($td_field as $v) {
            $l .= '<td width="' . $v['w'] . '">' . $v['t'] . '</td>';
        }
        $l .= '</tr>';
        foreach ($order as $key => $v) {
            switch ($v['pay_type']) {
                case 1:
                    $pay_type = '??????';
                    break;
                case 2:
                    $pay_type = '?????????';
                    break;
                case 3:
                    $pay_type = '??????-??????';
                    break;
                case 4:
                    $pay_type = '??????-?????????';
                    break;
                case 5:
                    $pay_type = '??????';
                    break;                   
                default:
                    $pay_type = '';
            }
            switch ($v['pay_app']) {
                case 'wechat':
                    $pay_app = '??????';
                    break;
                case 'alipay':
                    $pay_app = '?????????';
                    break;
                case 'account_app':
                    $pay_app = '?????????????????????';
                    break;
                case 'jjpay':
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
            foreach ($detail[$v['order_sn']] as $d) {
                $goods_id[] = $d['d_id'];
                $goods_title[] = $d['title'];
                $goods_cate[] = $cate_title[$d['goods_log']['cate_id']];
                $goods_price[] = $d['price'];
                $goods_num[] = $d['num'];
                $goods_money[] = $d['price'] * $d['num'];
            }

            // ?????????
            if (!empty($v['uid'])) {
                $nickname_x = get_nickname($v['uid']);
            } else if (in_array($v['pay_type'], array(3, 4))) {
                $nickname_x = '??????';
            } else {
                $nickname_x = '?????????';
            }

            $data1 = array(
                '&nbsp;' . $v['order_sn'], '&nbsp;' . $v['pay_sn'], $v['uid'], '&nbsp;' . $nickname_x, $pay_type, time_format($v['create_time'], 'Y-m-d H:i'), time_format($v['update_time'], 'Y-m-d H:i'), $v['money'], $v['pay_money'], $v['cash_money'], $v['user_discount_money'], $pay_app, $v['type'] == 'online' ? '????????????' : '????????????'
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
            $l .= '<tr class="tr' . ($key % 2) . '">';
            foreach ($data1 as $value) {
                $l .= '<td rowspan="' . $rowspan . '">' . $value . '</td>';
            }
            foreach ($data2 as $value) {
                $l .= '<td>' . $value[0] . '</td>';
                unset($value[0]);
            }
            if ($store_sell_type == 2) {
                $receipt_str = $v['receipt_info'] ? ('<strong>????????????</strong>' . $v['receipt_info']['name'] . ' <strong>???????????????</strong>' . $v['receipt_info']['mobile'] . '<br><strong>?????????</strong>' . $v['receipt_info']['sheng_title'] . $v['receipt_info']['shi_title'] . $v['receipt_info']['qu_title'] . $v['receipt_info']['address']) : '';

                $express_str = $v['status'] >= 4 ? ('???????????????' . $v['express_info']['company'] . '<br>' . '???????????????' . $v['express_info']['no']) : '?????????';
                $data3 = array(
                    $receipt_str, $express_str, $v['status'] >= 4 ? date('Y-m-d H:i', $v['express_time']) : '-'
                );
                foreach ($data3 as $k => $value) {
                    $l .= '<td rowspan="' . $rowspan . '" style="' . ($k < 2 ? ('text-align:left;') : '') . '">' . $value . '</td>';
                }
            }
            $l .= '</tr>';

            for ($i = 1; $i < $rowspan; $i++) {
                $l .= '<tr class="tr' . ($key % 2) . '">';
                foreach ($data2 as $value) {
                    $l .= '<td>' . $value[$i] . '</td>';
                }
                $l .= '</tr>';
            }
        }
        $l .= '</table>';
        echo $l;
        exit;
        echo iconv('utf-8', 'gbk', $l);
        exit;
    }

    /************************
     * ??????????????????
     * s_date???????????????
     * e_date???????????????
     *****************************/
    private function getDates()
    {
        //??????????????????30???
        $s_date = I('s_date');
        $e_date = I('e_date');
        if ($s_date == "" && $e_date == "") {
            //?????????????????? ??????30???
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $e_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        } else {
            if ($s_date != "") {
                $s_date = strtotime($s_date);
            }
            if ($e_date != "") {
                $e_date = strtotime($e_date);
            }
        }
        $s_date = date('Y-m-d', $s_date);
        $e_date = date('Y-m-d', $e_date);
        \Think\Log::record("s_date " . $s_date);
        return array(
            "s_date" => $s_date,
            "e_date" => $e_date
        );
    }

}
