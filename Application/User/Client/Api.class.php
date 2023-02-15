<?php
namespace User\Client;
define('UC_CLIENT_PATH', dirname(dirname(__FILE__)));

//载入配置文件
require_cache(UC_CLIENT_PATH . '/Conf/config.php');

//载入函数库文件
require_cache(UC_CLIENT_PATH . '/Common/common.php');

class Api{

    public function execute($api, $action, $param = array()){
        if(!is_array($param)){
            return array('status' => 0, 'param必须为数组形式');
        }
        $param = json_encode($param);
        $url = API_SERVER;
        
        $result = http($url, array('service' => $api.'.'.$action, 'param' => $param), 'post');
        $result = json_decode($result, true);
        if(!empty($result['status'])){
            return $result;
        }else{
            return array('status' => 0, 'msg' => '接口服务器无返回');
        }
    }

}
