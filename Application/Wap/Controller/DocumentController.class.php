<?php

namespace Wap\Controller;


class DocumentController extends BaseController {
    /**
     * @name show
     * @title 文档展示
     * @param string $name  标识(<br>'contact'：联系我们, <br>'about' => 关于我们,<br> 'pay_help' => 支付帮助, <br>'user_agreement' => 用户协议, <br>'legal_privacy' => 法律条款与隐私条款, <br>'integral_desc' => 蜜糖说明, <br>'join' => 加入我们, <br>'cash_coupon_desc' => 代金券说明<br>)
     * @return  显示html页面
     * @remark
     */
    public function show($name){
        $name = I('name');
        if(!$name){
            exit;
        }
        $data = D('Document')->detail($name);
        if(!$data){
            exit;
        }/*
        $data = $_SERVER;
        $data['c_time'] = date('Y-m-d H:i');
        file_put_contents(RUNTIME_PATH.'docu_test.txt',  date('Y-m-d H:i')."\n".var_export($_SERVER, true)."\n\n---------\n\n", FILE_APPEND);*/
        $placeholder = C('TMPL_PARSE_STRING.__IMG__')."/common/grey.gif"; //占位符图片
        $preg = "/<img(.+)src=([^>]+)>/isU"; //匹配图片正则
        $replaced = '<img$1src="'.$placeholder.'" data-original=$2>';
        
        $data['content'] = preg_replace($preg, $replaced, $data['content']); 
        $this->assign('data', $data);
        $this->assign('meta_title', $data['title']);
        $this->display();
    }
    
    public function test_lm(){
        $this->display();
    }
}
