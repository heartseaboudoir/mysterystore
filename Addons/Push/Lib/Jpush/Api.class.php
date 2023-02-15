<?php
namespace Addons\Push\Lib\Jpush;
require 'autoload.php';

use JPush\Client as JPush;

class Api{
    
    public $app_key;
    public $master_secret;
    
    function __construct() {
        require_once 'config.php'; 
        $this->app_key = $config['app_key'];
        $this->master_secret = $config['master_secret'];
        $this->client = new JPush($this->app_key, $this->master_secret);
    }
    
    public function send_message($alias, $content){
        
        $options = array(
            'apns_production' => true,
        );         
        $result =  $this->client->push()
        ->setPlatform('all')
        ->addAlias($alias)
        ->setMessage($content)
        ->options($options)
        ->send();
        if($result['http_code'] == 200){
            return array('status' => 1, 'result' => $result['body']);
        }else{
            return array('status' => 0, 'result' => $result['body']);
        }
    }
    
    // push
    public function send_notice($content, $other = array(), $push_info = array()){
        
        
        if (empty($push_info['env'])) {
            $apns_production = false;
        } else {
            $apns_production = true;
        }
        
        if (empty($push_info['device'])) {
            $platform = 'all';
        } elseif ($push_info['device'] == 1) {
            $platform = 'ios';
        } else {
            $platform = 'android';
        }
        
        
        $options = array(
            'apns_production' => $apns_production,
        );        
        
        
        
        
        $ios_notification = $other;
        $ios_notification['sound'] = 'default';
        
        
        
        $result =  $this->client->push()
        ->setPlatform($platform)
        ->addAllAudience()
        ->androidNotification($content, $other)
        ->iosNotification($content, $ios_notification)
        ->options($options)
        ->send();
        if($result['http_code'] == 200){
            return array('status' => 1, 'result' => $result['body']);
        }else{
            return array('status' => 0, 'result' => $result['body']);
        }
    }
}