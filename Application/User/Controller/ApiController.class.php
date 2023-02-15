<?php

namespace User\Controller;
use Think\Controller;
class ApiController extends Controller{
    public function api($service, $param = ''){
        $api_enter = explode('.', $service);
        $api = $api_enter[0];
        $action = $api_enter[1];
        $param = json_decode($param, true);
        $res = new \ReflectionMethod(A('User/'.$api, 'Api'), $action);
        $api_param = array();
        foreach($res->getParameters() as $v){
            if(isset($param[$v->name])){
                $api_param[] = $param[$v->name];
            }else{
                $api_param[] = null;
            }
        }
        $result = call_user_func_array( array(A('User/'.$api, 'Api'), $action) , $api_param);
        $this->ajaxReturn(array('status' => 1, 'data' => $result));
    }

}
