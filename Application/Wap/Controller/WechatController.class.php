<?php

namespace Wap\Controller;

use Think\Controller;

class WechatController extends Controller {

    public function execute($_controller = null, $_action = null){
        if(C('URL_CASE_INSENSITIVE')){
            $_controller    =   parse_name($_controller,1);
        }

        if(!empty($_controller) && !empty($_action)){
            A("{$_controller}")->$_action();
        } else {
            $this->error('没有指定插件名称，控制器或操作！');
        }
    }
}
