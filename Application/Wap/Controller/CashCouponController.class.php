<?php

namespace Wap\Controller;

class CashCouponController extends BaseController {
    
    public function lists(){
        $this->check_login();
        $type = I('type', 0, 'intval');
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $order_sn = I('get.callback', '');
        $order = array();
        if($order_sn && ($type == 1 || ($page == 1 && !IS_AJAX))){
            $order = D('Addons://Order/Order')->get_info($order_sn, array('status' => 1), $this->uid, '*', false);
        }else{
            unset($_GET['order_sn']);
        }
        if(IS_AJAX){
            $req = \User\Client\Api::execute('CashCoupon', 'user_coupon_list', array('uid' => $this->uid, 'type' => $type, 'page' => $page, 'row' => $row, 'order_money' => ($type == 1 && $order) ? $order['money'] : 0));
            if($req['status'] == 1){
                $data = isset($req['data']) ? $req['data'] : array();
                $data['lists'] = $this->set_str($data['lists']);
                $this->ajaxReturn(array('status' => 1, 'data' => $data['lists']));
            }else{
                $this->ajaxReturn(array('status' => 0, 'data' => array()));
            }
            exit;
        }else{
            $req = \User\Client\Api::execute('CashCoupon', 'user_coupon_list', array('uid' => $this->uid, 'type' => 1, 'page' => $page, 'row' => $row, 'order_money' => $order ? $order['money'] : 0));
            if($req['status'] == 1){
                $data = isset($req['data']) ? $req['data'] : array();
                $data['lists'] = $this->set_str($data['lists']);
                $this->assign('lists1', $data);
            }
            $req = \User\Client\Api::execute('CashCoupon', 'user_coupon_list', array('uid' => $this->uid, 'type' => 2, 'page' => $page, 'row' => $row));
            if($req['status'] == 1){
                $data = isset($req['data']) ? $req['data'] : array();
                $data['lists'] = $this->set_str($data['lists']);
                $this->assign('lists2', $data);
            }
            $req = \User\Client\Api::execute('CashCoupon', 'user_coupon_list', array('uid' => $this->uid, 'type' => 3, 'page' => $page, 'row' => $row));
            if($req['status'] == 1){
                $data = isset($req['data']) ? $req['data'] : array();
                $data['lists'] = $this->set_str($data['lists']);
                $this->assign('lists3', $data);
            }
            $this->display();
        }
    }
    
    public function coupon_receive(){
        $this->get_open(true);
        $cash_code = I('cash_code', '');
        if(!$cash_code){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $req = \User\Client\Api::execute('CashCoupon', 'info', array('code' => $cash_code));
        if($req['status'] != 1){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $info = $req['data'];
        // ??????????????????????????????
        if(!$info || $info['rule_type']){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        // ??????????????????
        if($info['is_max'] && $info['get_num'] >= $info['num']){
            redirect(U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }
        $mobile = $this->get_lottery_mobile();

        // ?????????????????????????????????
        $req = \User\Client\Api::execute('CashCoupon', 'info', array('code' => $cash_code));
        
        if($req['status'] != 1){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $req = \User\Client\Api::execute('CashCoupon', 'check_lottery', array('code' => $cash_code, 'type' => APP_TYPE, 'key' => $this->app_data_id, 'uid' => $mobile ? $this->get_uid($mobile): 0));
        
        
        if($req['status'] == 1 && $req['data']){
            redirect(U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }
        

        
        // ??????????????????????????????????????????
        if($mobile){
            $uid = $this->get_uid($mobile);
            $req = \User\Client\Api::execute('CashCoupon', 'get_cash_coupon', array('code' => $cash_code, 'uid' => $uid));
            
            
            if($req['status'] != 1){
                $this->error('????????????');
            }
            $result = $req['data'];
            if($result != 1){
                switch($result){
                    case 0:
                        redirect(U('receive_coupon_lists', array('cash_code' => $cash_code)));
                        break;
                    case -2:
                        $error = '????????????????????????~';
                        break;
                    case -3:
                        $error = '????????????????????????????????????~';
                        break;
                }
                $this->error($error);
            }
        }
        
        $req = \User\Client\Api::execute('CashCoupon', 'config_info', array('name' => 'pay_share'));
        if($req['status'] == 1){
            $config = $req['data'];
            $this->assign('config', $config);
        }
        
        // ????????????
        $pay_share = share_config('pay_share');
        $pay_share['url'] = U('CashCoupon/coupon_receive', array('cash_code' => $cash_code));
        $this->assign('pay_share', $pay_share);
        $this->assign('info', $info);              
        $this->display();
    }
    
    // ?????????????????????
    public function user_receive(){
        $this->get_open(true);
        $cash_code = I('cash_code');
        if(!$cash_code){
            $this->error('??????????????????');
        }
        $req = \User\Client\Api::execute('CashCoupon', 'info', array('code' => $cash_code));
        if($req['status'] != 1){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $info = $req['data'];
        // ??????????????????????????????
        if(!$info || $info['rule_type']){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $mobile = $this->get_lottery_mobile();
        if(!$mobile){
            $mobile = I('mobile', '', 'trim');
        }
        if(!$mobile){
            $this->error('?????????????????????');
        }elseif(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->error('??????????????????????????????');
        }
        $sms_code = I('sms_code', '', 'trim');
        $result = check_code('lottery_mobile_code', $mobile, $sms_code);
        if($result['status'] != 1){
            $this->error($result['msg']);
        }
        $result = $this->set_lottery_mobile($mobile);
        if($result['status'] != 1){
            $this->error($result['info']);
        }
        
        $req = \User\Client\Api::execute('CashCoupon', 'get_cash_coupon', array('code' => $cash_code, 'uid' => $this->get_uid($mobile)));
        if($req['status'] != 1){
            $this->error('????????????');
        }
        if($result != 1){
            switch($result){
                case -2:
                    $error = '????????????????????????~';
                    break;
                case -3:
                    $error = '????????????????????????????????????~';
                    break;
            }
            $this->error('?????????', U('receive_coupon_lists', array('cash_code' => $cash_code)));
        }
        $this->success('?????????', U('receive_coupon_lists', array('cash_code' => $cash_code)));
    }
    // ?????????????????????
    public function receive_coupon_lists(){
        $this->get_open(true);
        $cash_code = I('cash_code');
        if(!$cash_code){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        
        $req = \User\Client\Api::execute('CashCoupon', 'info', array('code' => $cash_code));
        if($req['status'] != 1){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $make = $req['data'];
        // ??????????????????????????????
        if(!$make || $make['rule_type']){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        
        // ??????????????????
        $mobile = $this->get_lottery_mobile();
        if(!$mobile){
            redirect(U('CashCoupon/coupon_receive', array('cash_code' => $cash_code)));
        }
        $uid = $this->get_uid($mobile);
        $info = array();
        if($uid){
            $req = \User\Client\Api::execute('CashCoupon', 'coupon_info_by_where', array('where' => array('p_code' => $cash_code, 'uid' => $uid)));
            if($req['status'] == 1){
                $info = $req['data'];
            }
        }
        if(!$info && (!$make['is_max'] || ($make['is_max'] && $make['get_num'] < $make['num']))){
            // ???????????????????????????????????????????????????
            $req = \User\Client\Api::execute('CashCoupon', 'get_cash_coupon', array('code' => $cash_code, 'uid' => $uid));
            if($req['status'] != 1){
                $this->error('????????????');
            }
            $result = $req['data'];
            switch($result){
                case 1:
                    // ??????????????????
                    $req = \User\Client\Api::execute('CashCoupon', 'coupon_info_by_where', array('where' => array('p_code' => $cash_code, 'uid' => $uid)));
                    if($req['status'] == 1){
                        $info = $req['data'];
                    }else{
                        $info = array();
                    }
                    break;
                case -2:
                    $make['status'] = 2;
                    break;
                case -3:
                    $make['status'] = 3;
                    break;
            }
        }
        
        $outtime = time();
        if (!empty($info['create_time']) && $outtime - $info['create_time'] > 5) {
            $outdate = 1;
        } else {
            $outdate = 0;
        }
        $this->assign('outdate', $outdate);        
        
        $this->assign('info', $info);
        $this->assign('mobile', $mobile);
        
        // ????????????
        $req = \User\Client\Api::execute('CashCoupon', 'coupon_list', array('p_code' => $cash_code, 'row' => $make['num']));
        if($req['status'] == 1){
            $lists = !empty($req['data']['data']) ? $req['data']['data'] : array();
        }else{
            $lists = array();
        }
        !$lists && $lists = array();
        foreach($lists as $k => $v){
            $v['receive_user'] = json_decode($v['receive_user'], true);
            if($v['receive_type'] == 'wechat'){
                $v['nickname'] = isset($v['receive_user']['nickname']) ? $v['receive_user']['nickname'] : '';
                $v['headimgurl'] = isset($v['receive_user']['headimgurl']) ? $v['receive_user']['headimgurl'] : '';
            }elseif($v['receive_type'] == 'alipay'){
                $v['nickname'] = isset($v['receive_user']['nick_name']) ? $v['receive_user']['nick_name'] : '';
                $v['headimgurl'] = isset($v['receive_user']['avatar']) ? $v['receive_user']['avatar'] : '';
            }
            !$v['nickname'] && $v['nickname'] = get_nickname($v['uid']);
            !$v['headimgurl'] && $v['headimgurl'] = get_header_pic($v['uid']);
            $lists[$k] = $v;
        }
        $this->assign('lists', $lists);
        
        // ????????????
        $pay_share = share_config('pay_share');
        $pay_share['url'] = U('CashCoupon/coupon_receive', array('cash_code' => $cash_code));
        $this->assign('pay_share', $pay_share);
        
        $this->display();
    }
    
    private function set_str($data){
        $order_sn = I('get.callback', '');
        foreach($data as $k => $v){
            if($v['last_time'] > 0){
                $v['last_time_text'] = '????????????'.date('Y-m-d', $v['last_time']);
                if($v['last_time'] <= NOW_TIME){
                    $v['last_day'] = '?????????';
                }else{
                    $last_time = $v['last_time']-NOW_TIME;
                    $day = $last_time/3600/24;

                    if($day >= 1){
                        $v['last_day'] = '??????'.ceil($day).'?????????';
                    }else{
                        $hour = $last_time/3600;
                        if($hour >= 1){
                            $v['last_day'] = '??????'.ceil($hour).'????????????';
                        }else{
                            $mi = $last_time/60;
                            $v['last_day'] = '??????'.ceil($mi).'????????????';
                        }
                    }
                }
            }else{
                $v['last_time_text'] = '?????????';
                $v['last_day'] = '?????????';
            }
            if($v['status'] == 1 && $order_sn){
                $v['back_url'] = U('Order/detail', array('order_sn' => $order_sn)).'?cash_code='.$v['code'];
            }else{
                $v['back_url'] = '';
            }
            !$v['description'] && $v['description'] = '';
            $data[$k] = $v;
        }
        return $data;
    }
    
    // ?????????????????????
    public function lottery_coupon_none(){
        $this->display();
    }
    // ??????????????????
    public function lottery_coupon(){
        $this->get_open(true);
        $cash_code = I('cash_code', '');
        if(!$cash_code){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        // ???????????????
        $req = \User\Client\Api::execute('CashCoupon', 'make_info', array('code' => $cash_code));
        
        if($req['status'] != 1 || !$req['data']){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $info = $req['data'];
        if($info['get_num'] >= $info['num']){
            redirect(U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }
        $mobile = $this->get_lottery_mobile();

        // ?????????????????????????????????
        $req = \User\Client\Api::execute('CashCoupon', 'check_lottery', array('code' => $cash_code, 'type' => APP_TYPE, 'key' => $this->app_data_id, 'uid' => $mobile ? $this->get_uid($mobile): 0));
                
        
        if($req['status'] == 1 && $req['data'] && !empty($mobile)){
            // redirect(U('User/login'));
            redirect(U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }
        
        
        
        
        // ??????????????????
        $req = \User\Client\Api::execute('CashCoupon', 'get_lottery_money', array('code' => $cash_code, 'type' => APP_TYPE, 'key' => $this->app_data_id));
        if($req['status'] == 1){
            $money = $req['data'];
            $this->assign('money', $money);
        }
        
        // ??????????????????????????????????????????
        if($mobile){
            $result = $this->get_lottery_cash_coupon($mobile, $cash_code);
            if($result['status'] == 1 || $result['status'] == 2){
                redirect(U('lottery_coupon_lists', array('cash_code' => $cash_code)));
            }
            //header('Content-Type:text/html;charset=utf-8');
            //print_r($result);exit;
        }
        
        
        
        //print_r($mobile);exit;
        
        $req = \User\Client\Api::execute('CashCoupon', 'config_info', array('name' => 'pay_share'));
        if($req['status'] == 1){
            $config = $req['data'];
            $this->assign('config', $config);
        }
        
        
        
        // ????????????
        $pay_share = share_config('pay_share');
        $pay_share['url'] = U('CashCoupon/lottery_coupon', array('cash_code' => $cash_code));
        $this->assign('pay_share', $pay_share);
        $this->display();
    }
    // ?????????????????????
    public function lottery_coupon_lists(){
        $this->get_open(true);
        $cash_code = I('cash_code');

        if(!$cash_code){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        // ??????????????????
        $req = \User\Client\Api::execute('CashCoupon', 'make_info', array('code' => $cash_code));
        if($req['status'] != 1 || !$req['data']){
            redirect(U('CashCoupon/lottery_coupon_none'));
        }
        $make = $req['data'];
        $this->assign('make', $make);
        // ??????????????????
        $mobile = $this->get_lottery_mobile();
        
        if(!$mobile){
            redirect(U('CashCoupon/lottery_coupon', array('cash_code' => $cash_code)));
        }
        $req = \User\Client\Api::execute('CashCoupon', 'check_lottery', array('code' => $cash_code, 'type' => APP_TYPE, 'key' => $this->app_data_id, 'uid' => $this->get_uid($mobile)));
        if($req['status'] == 1){
            $info = $req['data'];
        }else{
            $info = array();
        }
        if(!$info && $make['get_num'] < $make['num']){
            $this->get_lottery_cash_coupon($mobile, $cash_code);
        }
        
        /*
        echo '<pre>';
        echo $this->get_uid($mobile);
        print_r($req);
        print_r($make);
        echo '</pre>';
        exit;
        */
        
        $outtime = time();
        if (!empty($info['create_time']) && $outtime - $info['create_time'] > 5) {
            $outdate = 1;
        } else {
            $outdate = 0;
        }
        $this->assign('outdate', $outdate);
        
        $this->assign('info', $info);
        $this->assign('mobile', $mobile);
        
        // ????????????
        $req = \User\Client\Api::execute('CashCoupon', 'coupon_list', array('p_code' => $cash_code, 'row' => $make['num']));
        if($req['status'] == 1){
            $lists = !empty($req['data']['data']) ? $req['data']['data'] : array();
        }else{
            $lists = array();
        }
        !$lists && $lists = array();
        foreach($lists as $k => $v){
            $v['receive_user'] = json_decode($v['receive_user'], true);
            if($v['receive_type'] == 'wechat'){
                $v['nickname'] = isset($v['receive_user']['nickname']) ? $v['receive_user']['nickname'] : '';
                $v['headimgurl'] = isset($v['receive_user']['headimgurl']) ? $v['receive_user']['headimgurl'] : '';
            }elseif($v['receive_type'] == 'alipay'){
                $v['nickname'] = isset($v['receive_user']['nick_name']) ? $v['receive_user']['nick_name'] : '';
                $v['headimgurl'] = isset($v['receive_user']['avatar']) ? $v['receive_user']['avatar'] : '';
            }
            !$v['nickname'] && $v['nickname'] = get_nickname($v['uid']);
            !$v['headimgurl'] && $v['headimgurl'] = get_header_pic($v['uid']);
            $lists[$k] = $v;
        }
        $this->assign('lists', $lists);
        
        // ??????????????????,???????????????
        $act_config = $this->getActConfig();
        $time = time();
        $act_data = array();
        if (empty($act_config['is_open']) || $time < $act_config['stime'] || $time > ($act_config['etime'] + 3600 * 24)) {
            $is_open = false;
        } else {
            // ??????????????????????????????????????????
            $act_data = M('act_prize_share')->where(array(
                'order_sn' => $make['action_sn'],
            ))->find();
            
            if (empty($act_data) || empty($act_data['pay_money'])) {
                $is_open = false;
            } else {
                $is_open = true;  
                
                
                $money_one = $act_data['pay_money'] * $act_config['parcent'] / 100;
                $money_one = number_format(round($money_one, 2), 2, '.', '');
                
                
                
                
                
                
                // ?????????????????????
                $daytime = strtotime(date('Y-m-d'));        
                
                $where = array();
                
                $where['order_time'] = array('egt', $daytime);
                
                // ?????????
                $count = M('act_prize_share')->where($where)->count();
                $count += $act_config['num'];
                
                // ???????????????
                $pay_money = M('act_prize_share')->where($where)->sum('pay_money');
                $money = $pay_money * $act_config['parcent'] / 100 + $act_config['prize'];
                $money = number_format(round($money, 2), 2, '.', '');
                
                
                // ????????????
                if (date('Y-m-d') != date('Y-m-d', $act_data['order_time'])) {

                    
                    if ($make['uid'] == $info['uid']) {
                        $share_msg = '????????????????????????????????????????????????<span style="color:#ea284a;">' . $money_one . '???</span>??????????????????????????????<span style="color:#ea284a;">' . $money . '???</span>????????????????????????????????????????????????';
                        
                    } else {
                        
                        $nickname = get_nickname($make['uid']);
                        
                        $share_msg = $nickname . '?????????????????????????????????????????????<span style="color:#ea284a;">' . $money_one . '???</span>??????????????????????????????<span style="color:#ea284a;">' . $money . '???</span>????????????????????????????????????????????????????????????????????????';
                    }                    
                    
                    $title = '???' . date('Y-m-d', $act_data['order_time']) . '???';
                    $share_msg = $title .  $share_msg;
                } else {
                    if ($make['uid'] == $info['uid']) {
                        $share_msg = '????????????????????????????????????????????????<span style="color:#ea284a;">' . $money_one . '???</span>??????????????????????????????<span style="color:#ea284a;">' . $money . '???</span>?????????????????????????????????????????????';
                        
                    } else {
                        
                        $nickname = get_nickname($make['uid']);
                        
                        $share_msg = $nickname . '?????????????????????????????????????????????<span style="color:#ea284a;">' . $money_one . '???</span>??????????????????????????????<span style="color:#ea284a;">' . $money . '???</span>????????????????????????????????????????????????????????????????????????';
                    }                     
                }
               
                
            }
        } 
        $this->assign('is_open', $is_open);
        $this->assign('act_data', $act_data);
        $this->assign('share_msg', $share_msg);
        
        

        
        
        
        
        
        
        // ????????????
        $pay_share = share_config('pay_share');
        $pay_share['url'] = U('CashCoupon/lottery_coupon', array('cash_code' => $cash_code));
        $this->assign('pay_share', $pay_share);
        
        //$this->display();
        $this->display('lottery_coupon_lists_new');
    }
    
    
    // ????????????????????????
    private function getActConfig()
    {
        $data = M('act_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => false,
                'title' => '',
                'info' => '',
                'money' => 1,
                'min_use_money' => 0,
                'parcent' => 0,
                'prize' => 1,
                'num' => 0,
                'days' => 1,
                'remark' => '',
                'stime' => 0,
                'etime' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        
        $data['parcent'] > 20 && $data['parcent'] = 20;

        
        $data['prize'] > 1000 && $data['prize'] = 1;
        
        
        return $data;
        
    }    
    
    
    
    /**
     * @name  get_mobile_code
     * @title ?????????????????????
     * @param  string  $mobile  ????????????
     * @return
     * @remark ????????????????????????600??????????????????????????????90???
     */
    public function get_mobile_code(){
        $this->get_open();
        $mobile = I('mobile', '', 'trim');
        if(!$mobile){
            $this->error('?????????????????????');
        }
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->error('??????????????????????????????');
        }
        
        $code = make_code('lottery_mobile_code', $mobile);
            //$this->success($code);return;
        $result = send_sms($mobile, 'SMS_39370204', array('code' => $code, 'product' => '????????????'));
        if($result['status'] == 1){
            $this->success('????????????');
        }else{
            $this->error($result['msg']);
        }
    }
    
    
    public function debug_0010_mobile(){
        $this->get_open();
        $mobile_data = S('USER_LOTTERY_MOBILE');
        $type = APP_TYPE;
        $token = $this->app_data_id;
        
        if ($token == 'ohEQbxBavUfzG5Y4JKUsIyaOoNxg' || $token == 'ohEQbxGFpN7xQ3M8tcdsfv_HJJZc') {
        //if ($token == 'ohEQbxGFpN7xQ3M8tcdsfv_HJJZc') {
            if (isset($mobile_data[$type][$token])) {
                unset($mobile_data[$type][$token]);
                S('USER_LOTTERY_MOBILE', $mobile_data);
            }

            print_r(count($mobile_data[$type]));
        } else {
            echo 0;
        }
        
        exit;

    }    



    public function debug_mobile_count()
    {
        $mobile_data = S('USER_LOTTERY_MOBILE');
        $type = APP_TYPE;
        $token = $this->app_data_id;  
        $data = $mobile_data[$type];
        echo count($data);
    }

    public function debug_clear_mobile()
    {
        $mobile_data = S('USER_LOTTERY_MOBILE');
        $type = APP_TYPE;
        $token = $this->app_data_id;  
        $data = $mobile_data[$type];
        print_r($type);
        print_r($data);
        foreach ($data as $key => $val) {
            if (in_array($val, array('13013382598', '17788567561', '13751730010'))) {
                echo $key . '=>' . $val;
                echo ',';
                unset($mobile_data[$type][$key]);
            }
            
        }
        S('USER_LOTTERY_MOBILE', $mobile_data);
    }
    
    
    // ??????????????????
    public function get_lottery_mobile(){
        $this->get_open();
        $mobile_data = S('USER_LOTTERY_MOBILE');
        $type = APP_TYPE;
        $token = $this->app_data_id;
        $mobile = isset($mobile_data[$type][$token]) ? $mobile_data[$type][$token] : '';
        if(empty($mobile_data[$type][$token])){
            return '';
        }
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            unset($mobile_data[$type][$token]);
            S('USER_LOTTERY_MOBILE', $mobile_data);
            return '';
        }else{
            return $mobile;
        }
    }
    
    
    public function test_count_mobile()
    {
        $data = S('USER_LOTTERY_MOBILE');
        var_dump($data);
    }
    
    
    
    public function test_null_mobile()
    {
        $data = S('USER_LOTTERY_MOBILE', null);
        var_dump($data);
    }

    
    // ??????????????????
    private function set_lottery_mobile($mobile){
        if(!$mobile) {
            return array('status' => 0, 'info' => '?????????????????????');
        }
        if(!preg_match('/^1\d{10}$/', $mobile, $match)){
            return array('status' => 0, 'info' => '??????????????????????????????');
        }
        $type = APP_TYPE;
        $token = $this->app_data_id;
        $mobile_data = S('USER_LOTTERY_MOBILE');
        if (in_array($mobile, $mobile_data[$type])) {
            return array('status' => 0, 'info' => '???????????????????????????????????????');
        }
        $mobile_data[$type][$token] = $mobile;
        S('USER_LOTTERY_MOBILE', $mobile_data);
        return array('status' => 1);
    }
    // ??????????????????
    public function change_lottery_mobile(){
        $this->get_open();
        $mobile = I('mobile');
        if(!$mobile) {
            $this->error('?????????????????????');
        }
        $sms_code = I('sms_code', '', 'trim');
        $result = check_code('lottery_mobile_code', $mobile, $sms_code);
        if($result['status'] != 1){
            $this->error($result['msg']);
        }
        $result = $this->set_lottery_mobile($mobile);
        if($result['status'] == 1){
            unset_code('lottery_mobile_code', $mobile);
            $this->success('????????????');
        }else{
            $this->error($result['info']);
        }
    }
    // ??????????????????????????????
    public function user_lottery(){
        $this->get_open(true);
        $cash_code = I('cash_code');
        if(!$cash_code){
            $this->error('??????????????????');
        }
        $mobile = $this->get_lottery_mobile();
        if(!$mobile){
            $mobile = I('mobile', '', 'trim');
        }
        if(!$mobile){
            $this->error('?????????????????????');
        }elseif(!preg_match('/^1\d{10}$/', $mobile, $match)){
            $this->error('??????????????????????????????');
        }
        $sms_code = I('sms_code', '', 'trim');
        $result = check_code('lottery_mobile_code', $mobile, $sms_code);
        if($result['status'] != 1){
            $this->error($result['msg']);
        }
        $result = $this->set_lottery_mobile($mobile);
        if($result['status'] != 1){
            $this->error($result['info']);
        }
        $req = \User\Client\Api::execute('CashCoupon', 'check_lottery', array('code' => $cash_code, 'type' => APP_TYPE, 'key' => $this->app_data_id, 'uid' => $this->get_uid($mobile)));
        if($req['status'] == 1 && $req['data']){
            $this->error('?????????', U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }
        
        $result = $this->get_lottery_cash_coupon($mobile, $cash_code);
        if($result['status'] == 1){
            $this->success('?????????', U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }elseif($result['status'] == 2){
            $this->success($result['info'], U('lottery_coupon_lists', array('cash_code' => $cash_code)));
        }else{
            $this->error($result['info']);
        }
    }
    private function get_uid($mobile){
        $req = \User\Client\Api::execute('User', 'info', array('uid' => $mobile, 'is_username' => 3));
        if($req['status'] != 1){
            $this->error($req['msg']);
        }else{
            $user = $req['data'];
        }
        if($user == -1){
            $uid = D('Common/Member')->login($mobile);
        }else{
            $uid = $user[0];
        }
        return $uid;
    }
    // ???????????????
    private function get_lottery_cash_coupon($mobile, $cash_code){
        $uid = $this->get_uid($mobile);
        
        $req = \User\Client\Api::execute('CashCoupon', 'lottery_cash_coupon', array('code' => $cash_code, 'uid' => $uid, 'type' => APP_TYPE, 'key' => $this->app_data_id, 'user_data' => $this->app_data));
        
        //print_r($req);exit;
        //print_r($this->app_data);exit;
        if($req['status'] != 1){
            return array('status' => 0, 'info' => '????????????');
        }
        $result = $req['data'];
        if($result === -1){
            return array('status' => 2, 'info' => '???????????????????????????');
        }elseif($result === -2){
            return array('status' => 2, 'info' => '?????????????????????');
        }elseif(!$result){
            return array('status' => 0, 'info' => '????????????');
        }
        return array('status' => 1, 'info' => '????????????');
    }
}