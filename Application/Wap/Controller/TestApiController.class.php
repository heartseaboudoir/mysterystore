<?php

namespace Wap\Controller;

class TestApiController extends BaseController {
    

    public function test_v()
    {
        echo '<input type="file" accept="audio/*" capture="microphone">';
    }
    
    public function showcode()
    {
		// 生成二维码
		vendor("phpqrcode.phpqrcode");
        
        
        $url = 'http://v.imzhaike.com/Wap/TestApi/hello/vc/1';
		\QRcode::png($url, false, 'L', 4, 2);        
    }
    
    
    public function qrcode()
    {
        
        
        $this->display();
    }
    

    
    // 录音及播放
    public function test(){
        $wechat = $this->get_open(true);
        
        print_r($wechat);
        exit;
        
        
        
        $this->display();
        /*
        print_r($wechat);
        echo '<input type="file" accept="audio/*" capture="microphone">';
        echo $jsapi;
        */
    }
    
    
    public function test_voice()
    {
        echo file_get_contents('/data/debug/voice/test.amr');
    }
    
    public function test_play()
    {
        $this->display();
    }
    
    public function test_download()
    {
        /*
        $data = file_put_contents('/data/www/chaoshipos/wwwroot/Public/voice/test.txt', 'aaaa');
        
        print_r($data);
        
        exit;
        */
        $weixin = A("Addons://Wechat/Wechatclass");
        $vtoken = $weixin->vtoken();
        
        $media_id = 'JOZRcoSMoRXhGBCo95OJdXZaCMuAfN8RAWL3ez8qVeAu78BfwuQi6xTfqHO1KQ5E';
        
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$vtoken}&media_id={$media_id}");//设定请求的url
        curl_setopt($ch, CURLOPT_HEADER, 0);//是否返回头部
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true); // 获取请求信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//将结果返回
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);        
        
        
        $return = curl_exec($ch);
        
        //请求存在错误
        if (curl_errno($ch)) {
            curl_close($ch);
            return array(false, curl_error($ch));
        
        //请求返回失败
        } elseif ($return === false) {
            curl_close($ch);
            return array(false, 'Curl error: curl_exec failed');
        
        //请求成功,解析响应结果
        } else {
            print_r($return);
            file_put_contents('/data/debug/voice/test.amr', $return);
            
            return array(true, $return);
        }     
        
    }
    
    // http://v.imzhaike.com/Wap/TestApi/hello/vc/1
    // 录音及播放
    public function hello(){
        $wechat = $this->get_open(true);
        
        
        
        // 从URL中识别解析出ID
        $vc = I('vc', 0, 'intval');
        
        if (empty($vc)) {
            exit('empty vc');
        }
        
        $id = $vc;
        
        $voice = M('wx_voice_record')->where(array('id' => $id))->find();

        // 找到不二维码
        if (empty($voice)) {
            exit('not find vc');
        }
        
        
        // 录制
        if (empty($voice['wx_id'])) {
            $this->display('test');
            
        // 播放
        } else {
        
            $this->assign('voice', $voice);
            
            $this->display('play');        
        }
        

        /*
        print_r($wechat);
        echo '<input type="file" accept="audio/*" capture="microphone">';
        echo $jsapi;
        */
    }    
    
    


    // 录音及播放
    public function play(){
        $wechat = $this->get_open(true);
        
        
        
        // 从URL中识别解析出ID
        $id = 1;
        $voice = M('wx_voice_record')->where(array('id' => $id))->find();

        
        if (empty($voice['wx_id'])) {
            exit('empty wx_id');
        }
        
        $this->assign('voice', $voice);
        
        $this->display();
        /*
        print_r($wechat);
        echo '<input type="file" accept="audio/*" capture="microphone">';
        echo $jsapi;
        */
    }
    
    
    // 保存微信录音
    public function save()
    {
        $wx_id = I('wx_id', '', 'trim');
        
        if (empty($wx_id)) {
            $this->response(10010, 'wx_id error');
        }
        
        $id = 1;
        
        $res = M('wx_voice_record')->where(array('id' => $id))->save(array(
            'wx_id' => $wx_id,
            'use_time' => time(),
            'is_use' => 1,
        ));
        
        if (empty($res)) {
            $this->response(10050, 'error');
        }
        
        $this->response(200, 'sucess');
        
        
    }
    
    
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