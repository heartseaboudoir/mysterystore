<?php
namespace Apiv2\Controller;
use Think\Controller;

class RelayController extends Controller {
    
    public function order(){
        $order_sn = I('order_sn');
        $url = U('Wap/Order/detail', array('order_sn' => $order_sn));
        $url = str_replace('http://', 'https://', $url);
        redirect($url);
    }
}
