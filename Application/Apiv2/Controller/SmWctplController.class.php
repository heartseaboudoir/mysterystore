<?php
namespace Apiv2\Controller;
use Think\Controller;

class SmWctplController extends Controller {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'wctpl_act.txt');
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

        
        $this->debugs($data);
        echo json_encode($data);
        exit;
    }



    public function send_smg()
    {
        $res = $this->doing();
        
        
        $this->response(200, $res);
        
        
    }
    
    public function doing()
    {

        $datetime = date('Y年m月d日');   
        
        // 找出该次的活动用户
        $sql = "select r.id,r.uid,r.price,m.openid   
        from hii_wc_bit_record r 
        left join hii_ucenter_member m 
        on m.id = r.uid 
        where r.price > 0 and m.openid != '';";
        
        $users = M()->query($sql);
        
        $this->debugs($users);
        
        /*
        $users = array(
            array(
                'openid' => 'ohEQbxBavUfzG5Y4JKUsIyaOoNxg',
                'price' => 9.82,
            ),
            
            array(
                'openid' => 'ohEQbxJms5dEbJNvi6zzg01htiFQ',
                'price' => 10.2,
            ),
            array(
                'openid' => 'ohEQbxGyGMpZ1qVEPNMs2ZTOwIY0',
                'price' => 10.2,
            ), 
            
                    
        );
        */
        
        if (empty($users)) {
            $users = array();
            return array(10020, 'users为空');
        }
        
        $weixin = A("Addons://Wechat/Wechatclass");
        
        // 模块ID
        $template_id = 'sS3qzvFG1MNJqTaNyjLlOhyt7-jWzAzpJ0wpUImTKJE';
        //$template_id = 'sS3qzvFG1MNJqTaNyjLIOhyt7-jWzAzpJ0wpUlmTKJE';
        
        // 跳转URL
        $click_url = U('Wap/WorldCup/wc');
        
        // $datetime = date('Y年m月d日', time() - 3600 *24);        
        
        
        $this->debugs($users);
        
        // 遍历活动用户发送模板
        foreach ($users as $key => $val) {
            $openid = $val['openid'];
            if (!empty($openid)) {
                
                $data = array(
                    'first' => array(
                        'value' => '恭喜你中奖了',
                        'color' => '#000000',				
                    ),
                    'keyword1' => array(
                        'value' => '世界杯活动',
                        'color' => '#000000',
                    ),
                    'keyword2' => array(
                        'value' => '可消费余额增加' . number_format($val['price'], 2, '.', '') . '元',
                        'color' => '#000000',
                    ),
                    'keyword3' => array(
                        'value' => $datetime,
                        'color' => '#000000',
                    ),            
                    'remark' => array(
                        'value' => "中奖金额仅可在线下门店消费使用，不可提现",
                        'color' => '#000000',
                    ),
                );                
                
                $result = $weixin->tpl_msg($openid, $template_id, $data, $click_url, '#000000');
                $this->debugs($result);
            }
            
        }
        
        
        return array(0, '处理完成');

        
    }
    
    

    
    
    private function isTest()
    {
        //echo $_SERVER["HTTP_HOST"];
        if ($_SERVER["HTTP_HOST"] != 'v.imzhaike.com') {
            return true;
        } else {
            return false;
        }        
    }       

}



