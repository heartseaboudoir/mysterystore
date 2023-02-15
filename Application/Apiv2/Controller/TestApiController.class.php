<?php
namespace Apiv2\Controller;
use Think\Controller;

class TestApiController extends Controller {
    
    
    public function get_token()
    {
        $weixin = A("Addons://Wechat/Wechatclass");
        $vtoken = $weixin->vtoken();
        $this->response(200, $vtoken);
    }
    
    public function get_jstick()
    {
        $weixin = A("Addons://Wechat/Wechatclass");
        $vJsApiTicket = $weixin->vJsApiTicket();
        $this->response(200, $vJsApiTicket);
        
    }    


    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'jsapi.txt');
    }
      
    
    private function response($code = 200, $msg = '已处理请求') 
    {
        if ($code == 200) {
            $data = array(
                'code' => 200,
                'content' => $msg,
            );
        } else {
            $data = array(
                'code' => $code,
                'content' => $msg,
            );
        }

        echo json_encode($data);
        exit;
    } 
    
}    

