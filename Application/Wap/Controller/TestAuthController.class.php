<?php

namespace Wap\Controller;

class TestAuthController extends BaseController {
    

    

    
    // 用户领取优惠券
    public function auth(){
        $wechat = $this->get_open(true);
        
        

        $authjson = json_encode($wechat);
        
        $url = $_GET['authurl'];
        $data = parse_url($url);
        
        if (empty($data['query'])) {
            $url = $url . '?authjson=' . $authjson;
        } else {
            $url = $url . '&authjson=' . $authjson;
        }

        
        //echo $url;exit;
        redirect($url);

    }
}