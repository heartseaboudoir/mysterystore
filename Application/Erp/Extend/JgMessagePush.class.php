<?php
/**
 * Created by PhpStorm.
 * User: Ard
 * Date: 2018-04-25
 * Time: 11:45
 */
namespace Erp\Extend;

use JPush\Client as JPush;

class JgMessagePush
{
    
    static $jpush;
    
    static public function test()
    {

        return static::pushToApp('123789', '神密商店消息', '一条测试消息', '消息内容。。。');
    }

    /**
     * @name 极光消息推送
     * @param $uid 用户ID(标签)
     * @param $type 消息类型
     * @param $alert 通知内容
     * @param $title 通知标题
     * @param $extras 信息内容
     * @return array|string
     * @author Ard
     * @date 2018-04-25
     */
    // 通用的消息推送方法
    static public function pushMessageToApp($uid, $title, $alert,$type, $extras) {
        $client = static::getJpush();
        try {
            $clientPush = $client->push();

            // ->addAlias('alias')
            //->addTag(array('tag1', 'tag2'))
            //->addRegistrationId($uid)
            //->addAllAudience()

            // 指定接收对象
            if (empty($uid)) {
                $clientPush = $clientPush->addAllAudience();
            } else {
                //指定的数组(推送列表)
                $clientPush = $clientPush->addAlias($uid);
            }

            // 指定发送平台
            $response = $clientPush->setPlatform(array('ios', 'android'))
                
                ->setNotificationAlert($alert)
                ->iosNotification($alert, array(
                    
                    // 通知提示声音
                    'sound' => 'sound',
                    
                    // 在原有的 badge 基础上进行增减
                    'badge' => '+1',
                    // 'content-available' => true,
                    // 'mutable-content' => true,
                    'category' => 'jiguang',
                    
                    'extras' => array(
                        'type' => $type,
                        'content' => $extras,
                    ),
                ))
                ->androidNotification($alert, array(
                    'title' => $title,
                    // 'build_id' => 2,
                    'extras' => array(
                        'type' => $type,
                        'content' => $extras,
                    ),
                ))
                
                ->message($alert, array(
                    'title' => $title,
                    'content_type' => 'text',
                    'extras' => array(
                        'type' => $type,
                        'content' => $extras,
                    ),
                ))

                ->options(array(
                    // sendno: 表示推送序号，纯粹用来作为 API 调用标识，
                    // API 返回时被原样返回，以方便 API 调用方匹配请求与返回
                    // 这里设置为 100 仅作为示例

                    // 'sendno' => 100,

                    // time_to_live: 表示离线消息保留时长(秒)，
                    // 推送当前用户不在线时，为该用户保留多长时间的离线消息，以便其上线时再次推送。
                    // 默认 86400 （1 天），最长 10 天。设置为 0 表示不保留离线消息，只有推送当前在线的用户可以收到
                    // 这里设置为 1 仅作为示例

                    // 'time_to_live' => 1,

                    // apns_production: 表示APNs是否生产环境，
                    // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境

                    'apns_production' => true,

                    // big_push_duration: 表示定速推送时长(分钟)，又名缓慢推送，把原本尽可能快的推送速度，降低下来，
                    // 给定的 n 分钟内，均匀地向这次推送的目标用户推送。最大值为1400.未设置则不是定速推送
                    // 这里设置为 1 仅作为示例

                    // 'big_push_duration' => 1
                ))
                ->send();

        } catch (\JPush\Exceptions\APIConnectionException $e) {
            // try something here
            //print $e;
            return array(false, 'connet error');
            return 'connet error';
        } catch (\JPush\Exceptions\APIRequestException $e) {
            // try something here
            //print_r($e);
            return array(false, $e->getMessage());
            return 'request error';
        }
        //print_r($response);
        return array(true, $response);
        //print_r($response);    
        
        
    }
    
    // 获取jpush
    static private function getJpush()
    {
        
        if (!empty(static::$jpush)) {
            return static::$jpush;
        }
        
        spl_autoload_register('self::classLoader', false, true); 
        
        $app_key = '9c6b5485fe2cbceb5ad095d6';
        $master_secret = '5d4784c6e4f1100dd5b6466a';

        $client = new JPush($app_key, $master_secret);        
        
        static::$jpush = $client;
        
        return $client;         
        
    }
    
    // 自动加载
    static private function classLoader($class)
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        
        $file = VENDOR_PATH . '/jpush/src/' . $path . '.php';
        //echo $file;exit;
        if (file_exists($file)) {
            require_once $file;
        }
    }










 

}
