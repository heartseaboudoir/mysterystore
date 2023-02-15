<?php

namespace Addons\Push\Helper;

class Push {

    function __construct() {
        
    }

    public function add($title, $code, $content, $tags, $other = array()) {
//        $content = array(
//            'code' => 'all',
//            'data' => array()
//        );
//        $Api = new \Addons\Push\Lib\Getui\Api;
//        return $Api->pushMessageToApp($title, json_encode($content), 'store_1');
        $Model = M('Push');
        $data = array(
            'title' => $title,
            'code'  => $code,
            'content' => $content,
            'tags' => $tags,
            'create_time' => NOW_TIME,
            'update_time' => NOW_TIME,
        );
        $other && $data = array_merge($data, $other);
        if(!$Model->create($data)){
            return false;
        }
        if(!$Model->add()){
            return false;
        }
        return true;
    }

}
