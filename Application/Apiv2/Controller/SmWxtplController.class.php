<?php
namespace Apiv2\Controller;
use Think\Controller;

class SmWxtplController extends Controller {

    private function debugs($data)
    {
        //print_r($data);
        xydebug($data, 'wxtpl_act.txt');
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
    
    /**
     * 支付回调
     */
    public function send_smg()
    {
        $lid = I('lid', 0, 'intval');
        
        if (empty($lid)) {
            $this->response('10010', 'lid不能为空');
        }
        
        
        
        
        // 发送微信模板消息
        $res = $this->wxTpl($lid);
        
        $this->response(200, $res);
        
        
        
        
    }
    
    
    /**
     * 支付回调
     */
    public function send_smg_act2()
    {
        $lid = I('lid', 0, 'intval');
        
        if (empty($lid)) {
            $this->response('10010', 'lid不能为空');
        }
        
        
        
        
        // 发送微信模板消息
        $res = $this->wxTpl2($lid);
        
        $this->response(200, $res);
        
        
        
        
    }    
    
    
    
    /**
     * 发送微信模板消息 
     */
    private function wxTpl($lid = 0)
    {
        
        if (empty($lid)) {
            return array(10010, 'lid为空');
        }
        
        
        $act = M('act_product_log')->where(array(
            'id' => $lid
        ))->find();
        
        
        
        if (empty($act) || empty($act['act_time'])) {
            return array(10011, 'act_time为空');
        }
        
        
        $datetime = date('Y年m月d日', $act['act_time']);   
        
        
        
        // 找出该次的活动用户
        $sql = "select u.id,u.uid,u.lid,u.money,m.openid 
from hii_act_product_user u 
left join hii_ucenter_member m
on m.id = u.uid
where u.lid = {$lid} and m.openid != '';";
        
        
        $users = M()->query($sql);
        
        $this->debugs($users);
        /*
        $users = array(
            array(
                'openid' => 'ohEQbxBavUfzG5Y4JKUsIyaOoNxg',
                'money' => 10.2,
            ),
            
            array(
                'openid' => 'ohEQbxJms5dEbJNvi6zzg01htiFQ',
                'money' => 10.2,
            ),
            array(
                'openid' => 'ohEQbxGyGMpZ1qVEPNMs2ZTOwIY0',
                'money' => 10.2,
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
        $click_url = U('Wap/SmAct/index');
        
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
                        'value' => '每日666抽奖活动',
                        'color' => '#000000',
                    ),
                    'keyword2' => array(
                        'value' => '可消费余额增加' . number_format($val['money'], 2, '.', '') . '元',
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
    
    
    
    
    
    /**
     * 发送微信模板消息 
     */
    private function wxTpl2($lid = 0)
    {
        
        if (empty($lid)) {
            return array(10010, 'lid为空');
        }
        
        
        $act = M('act2_log')->where(array(
            'id' => $lid
        ))->find();
        
        
        
        if (empty($act) || empty($act['act_time'])) {
            return array(10011, 'act_time为空');
        }
        
        
        $datetime = date('Y年m月d日', $act['act_time']);   
        
        if (empty($act['act_user'])) {
            return array(10012, 'act_user为空');
        }        
        
        // 找出该次的活动用户
        $user = M('ucenter_member')->where(array(
            'id' => $act['act_user'],
        ))->find();
        
        
        if (empty($user) || empty($user['openid'])) {
            $user = array();
            return array(10020, 'user为空');
        }
        

        
        $this->debugs($user);
        /*
        $users = array(
            array(
                'openid' => 'ohEQbxBavUfzG5Y4JKUsIyaOoNxg',
                'money' => 10.2,
            ),
            
            array(
                'openid' => 'ohEQbxJms5dEbJNvi6zzg01htiFQ',
                'money' => 10.2,
            ),
            array(
                'openid' => 'ohEQbxGyGMpZ1qVEPNMs2ZTOwIY0',
                'money' => 10.2,
            ), 
                    
        );
        */

        
        $weixin = A("Addons://Wechat/Wechatclass");
        
        // 模块ID
        $template_id = 'sS3qzvFG1MNJqTaNyjLlOhyt7-jWzAzpJ0wpUImTKJE';
        //$template_id = 'sS3qzvFG1MNJqTaNyjLIOhyt7-jWzAzpJ0wpUlmTKJE';
        
        // 跳转URL
        $click_url = U('Wap/SmAct/index');
        
        // $datetime = date('Y年m月d日', time() - 3600 *24);        
        

        
        // 遍历活动用户发送模板

        $openid = $user['openid'];

            
        $data = array(
            'first' => array(
                'value' => '恭喜你中奖了',
                'color' => '#000000',				
            ),
            'keyword1' => array(
                'value' => '每日666抽奖活动',
                'color' => '#000000',
            ),
            'keyword2' => array(
                'value' => '可消费余额增加666.00元',
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



