<?php
namespace Api\Controller;
use Think\Controller;

class RelayController extends Controller {
    
    public function order(){
        $order_sn = I('order_sn');
        $url = U('Wap/Order/detail', array('order_sn' => $order_sn));
        $url = str_replace('http://', 'https://', $url);
        redirect($url);
    }
    
    public function redbag(){
        $this->assign('meta_title', '红包领取');
        $this->display();
    }
}
