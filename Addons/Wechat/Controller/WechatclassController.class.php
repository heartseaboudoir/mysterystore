<?php

namespace Addons\Wechat\Controller;

use Think\Controller;
use Addons\Wechat\Controller\MsgCryptController;

class WechatclassController extends Controller {

    /**
     * 微信推送过来的数据或响应数据
     * @var array
     */
    private $data = array();
	
    /**
     * 主动发送的数据
     * @var array
     */
    private $send = array();
    private $conf = array();
    public $ukey = null;
    private $o_ukey = null;

    /**
     * 构造方法，用于实例化微信SDK
     * @param string $token 微信开放平台设置的TOKEN
     */
    public function __construct() {
        $this->ukey = '';
        $this->o_ukey = strtoupper($this->ukey);
        $this->conf = S('WECHATADDONS_CONF_' . $this->o_ukey);
    }
    
    /**
     * 更新插件缓存
     */
    public function update_cache($config = null) {
        /* 更新插件缓存 */
        $addon_class = get_addon_class('Wechat');
        $data = new $addon_class;
        if (class_exists($addon_class)) {
            $addon['saveconfig_cache_list'] = $data->saveconfig_cache_list;
            if (is_array($addon['saveconfig_cache_list'])) {
                foreach ($addon['saveconfig_cache_list'] as $_v) {
                    S($_v . '_' . $this->o_ukey, null);
                }
            } else {
                S($addon['saveconfig_cache_list'] . '_' . $this->o_ukey, null);
            }
        }
        $config_arr = D("Addons://Wechat/WechatConfig")->find();
        S('WECHATADDONS_CONF_' . $this->o_ukey,isset($config_arr['config']) ? $config_arr['config'] : array(),0);
        return true;
    }

    /**
     * 获取用户分组
     * @param  string $type 返回类型
     * @return string/array  返回的结果；
     */
    public function getgroups() {
        $type = I('type', 0, 'intval');
        $config_file = 'WECHATADDONS_GROUPS_' . $this->o_ukey;
        S($config_file, null);
        $sgroups = S($config_file);
        if ($sgroups == false) {
            $access_token = $this->getToken();
            $url = "https://api.weixin.qq.com/cgi-bin/groups/get?access_token={$access_token}";
            $_groups = json_decode($this->http($url, $data), true);
            if (empty($_groups[errcode])) {
                $groups[0] = $_groups['groups'];
                $groups[1] = 0;
                foreach ($_groups['groups'] as $key => $value) {

                    $groups[1] += $value['count'];
                }
                S($config_file, $groups, 1000); // 放进缓存
            } else {
                S($config_file, null);
            }
        } else {
            $groups = $sgroups;
        }
        exit($this->jsencode($groups[$type]));
    }

    /**
     * 获取微信推送的数据
     * @return array 转换为数组后的数据
     */
    public function request() {
        $this->auth() || exit;
        if (IS_GET) {
            exit($_GET['echostr']);
        } else {
            
            $xmls = $xml = file_get_contents("php://input");
            // 消息加密验证
            
            if(!empty($_GET['encrypt_type']) && $_GET['encrypt_type'] == 'aes'){
                switch($this->conf['encoding_type']){
                    case 1:
                        break;
                    case 2:
                        if(empty($this->conf['encodingAESKey'])){
                            break;
                        }
                    case 3:
                        $pc = new MsgCryptController('weixin', $this->conf['encodingAESKey'], $this->conf['appid']);
                        $errCode = $pc->decryptMsg($_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $xml, $sxml);
                        if ($errCode == 0) {
                            $xml = $sxml;
                        }else{
                            exit();
                        }
                        break;
                    default:
             
                }
            }

            $xml = new \SimpleXMLElement($xml);
            $xml || exit;

            foreach ($xml as $key => $value) {
                $this->data[$key] = strval($value);
            }
        }
        if (empty($this->data['errcode'])) {
            $data = array(
                'type' => $this->data ['MsgType'],
                'content' => ($this->data ['MsgType'] == 'event') ? $this->data ['Event'] : $this->data ['Content'],
                'user' => $this->data ['FromUserName'],
                'time' => NOW_TIME,
                'msgid' => $this->data ['MsgId'],
                'ukey' => $this->conf['ukey']
            );
            D('Addons://Wechat/Wechat_message')->data($data)->add();
        }
        return $this->data;
    }

    /**
     * * 响应微信发送的信息（自动回复）
     * @param  string $to      接收用户名
     * @param  string $from    发送者用户名
     * @param  array  $content 回复信息，文本信息为string类型
     * @param  string $type    消息类型
     * @param  string $flag    是否新标刚接受到的信息
     * @return string          XML字符串
     */
    public function response($content, $type = 'text', $flag = 0) {
        // 为空时，有默认则输出默认，无则直接因空
        if(!$content){
            $base_content = $this->conf['msgset']['default']['content'];
            if(!empty($base_content)){
                $content = $base_content;
                $type = $this->conf['msgset']['default']['msgtype'];
            }else{
                exit();
            }
        }
        
        /* 基础数据 */
        $this->data = array(
            'ToUserName' => $this->data['FromUserName'],
            'FromUserName' => $this->data['ToUserName'],
            'CreateTime' => NOW_TIME,
            'MsgType' => $type,
        );

        /* 添加类型数据 */
        $this->$type($content);

        /* 添加状态 */
        $this->data['FuncFlag'] = $flag;
        $data = array(
            'type' => $this->data['MsgType'],
            'content' => $content,
            'user' => $this->data ['ToUserName'],
            'time' => $this->data ['CreateTime'],
            'ukey' => $this->conf['ukey']
        );
        D('Addons://Wechat/Wechat_message')->data($data)->add();
        /* 转换数据为XML */
        $xml = new \SimpleXMLElement('<xml></xml>');
        $this->data2xml($xml, $this->data);
        $res_xml = $xml->asXML();
        
        // 消息加密验证
        if(!empty($_GET['encrypt_type']) && $_GET['encrypt_type'] == 'aes'){
            switch($this->conf['encoding_type']){
                case 1:
                    break;
                case 2:
                    if(empty($this->conf['encodingAESKey'])){
                        break;
                    }
                case 3:
                    $pc = new MsgCryptController('weixin', $this->conf['encodingAESKey'], $this->conf['appid']);
                    $encryptMsg = '';
                    $errCode = $pc->encryptMsg($res_xml, $_GET['timestamp'], $_GET['nonce'], $encryptMsg);
                    if ($errCode == 0) {
                        exit($encryptMsg);
                    }else{
                        exit();
                    }
                    break;
                default:
            }
        }
        exit($res_xml);
    }

    /**
     * * 主动发送消息
     *
     * @param string $content   内容
     * @param string $openid   	发送者用户名
     * @param string $type   	类型
     * @return array 返回的信息
     */
    public function sendMsg($content, $openid = '', $type = 'text') {
        /* 基础数据 */
        $this->send ['touser'] = $openid;
        $this->send ['msgtype'] = $type;

        /* 添加类型数据 */
        $sendtype = 'send' . $type;
        $this->$sendtype($content);

        /* 发送 */
        $sendjson = $this->jsencode($this->send);
        $restr = $this->send($sendjson);
        return $restr;
    }
    
    /**
     * * 主动群发送消息
     *
     * @param string $content   内容
     * @param string $openid   	发送者用户名
     * @param string $type   	类型
     * @return array 返回的信息
     */
    public function sendMsg_qun($content, $group_id = 0, $type = 'text') {
        /* 基础数据 */
        $this->send ['filter'] = array('group_id' => $group_id);
        $this->send ['msgtype'] = $type;

        /* 添加类型数据 */
        $sendtype = 'send' . $type;
        $this->$sendtype($content);

        /* 发送 */
        $sendjson = $this->jsencode($this->send);
        $restr = $this->send_qun($sendjson);
        return $restr;
    }

    /**
     * 发送文本消息
     * 
     * @param string $content
     *        	要发送的信息
     */
    private function sendtext($content) {
        $this->send ['text'] = array(
            'content' => $content
        );
    }

    /**
     * 发送图片消息
     * 
     * @param string $content
     *        	要发送的信息
     */
    private function sendimage($content) {
        $this->send ['image'] = array(
            'media_id' => $content
        );
    }

    /**
     * 发送视频消息
     * @param  string $content 要发送的信息
     */
    private function sendvideo($video) {
        list (
                $video ['media_id'],
                $video ['title'],
                $video ['description']
                ) = $video;

        $this->send ['video'] = $video;
    }

    /**
     * 发送语音消息
     * 
     * @param string $content
     *        	要发送的信息
     */
    private function sendvoice($content) {
        $this->send ['voice'] = array(
            'media_id' => $content
        );
    }

    /**
     * 发送音乐消息
     * 
     * @param string $content
     *        	要发送的信息
     */
    private function sendmusic($music) {
        list (
                $music ['title'],
                $music ['description'],
                $music ['musicurl'],
                $music ['hqmusicurl'],
                $music ['thumb_media_id']
                ) = $music;

        $this->send ['music'] = $music;
    }

    /**
     * 发送图文消息
     * @param  string $news 要回复的图文内容
     */
    private function sendnews($news) {
        $articles = array();
        foreach ($news as $key => $value) {
            list(
                    $articles[$key]['title'],
                    $articles[$key]['description'],
                    $articles[$key]['url'],
                    $articles[$key]['picurl']
                    ) = $value;
            if ($key >= 9) {
                break;
            } //最多只允许10调新闻
        }
        $this->send['articles'] = $articles;
    }

    /**
     * * 获取微信用户的基本资料
     * 
     * @param string $openid   	发送者用户名
     * @return array 用户资料
     */
    public function user($openid = '') {
        if ($openid) {
            header("Content-type: text/html; charset=utf-8");
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info';
            $params = array();
            $params ['access_token'] = $this->getToken();
            $params ['openid'] = $openid;
            $httpstr = $this->http($url, $params);
            //$this->response($httpstr, 'text');
            $harr = json_decode($httpstr, true);
            return $harr;
        } else {
            return false;
        }
    }

    /**
     * * 获取微信用户的基本资料
     * 
     * @param string $openid   	发送者用户名
     * @return array 用户资料
     */
    public function user_list($next_openid = '') {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get';
        $params = array();
        $params ['access_token'] = $this->getToken();
        $params ['next_openid'] = $next_openid;
        $httpstr = $this->http($url, $params);
        $harr = json_decode($httpstr, true);
        if(empty($harr['errcode'])){
            return $harr;
        }else{
            return false;
        }
    }

    /**
     * 生成菜单
     * @param  string $data 菜单的str
     * @return string  返回的结果；
     */
    public function setMenu($data = NULL) {
        $config_file = 'WECHATADDONS_MENU_' . $this->o_ukey;
        $smenu = false;
        if ($smenu == false) {
            $access_token = $this->getToken();
            $this->delMenu($access_token);
            $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
            $menustr = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"), true);
            $_url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$access_token}";
            $_menustr = $this->http($_url, $data);
            S($config_file, json_decode($_menustr, true), 15000); // 放进缓存
        } else {
            $menustr = $smenu;
        }
        //print_r(S($config_file));
        return $menustr;
    }

    /**
     * 查询菜单
     * @return string  返回的结果；
     */
    public function getMenu() {
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$access_token}";
        $menustr = $this->http($url, $data);
        return $menustr;
    }

    /**
     * 删除菜单
     * @return string  返回的结果；
     */
    public function delMenu($token) {
        $access_token = empty($token) ? $this->getToken() : $token;
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$access_token}";
        $menustr = $this->http($url, $data);
        return $menustr;
    }

    public function upload_file($type, $file){
        if($this->conf['is_auth']){
            $where = array(
                'file' => $file,
                'ukey' => $this->ukey,
            );
            $media = D('Addons://Wechat/WechatMedia')->where($where)->field('type', 'media_id', 'created_at')->find();
            if($media && (time() - $media['created_at'] < 216000) ){
                return $media;
            }
            $access_token = $this->getToken();
            $url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token={$access_token}&type={$type}";
            $data = array(
                'media' => '@'.$file,
            );
            $result = $this->http($url, $data, 'post');
            $result = json_decode($result, true);
        }else{
            $result = A('Addons://Wechat/WechatDyclass')->upload($file); 
            $result = array(
                'type' => $result['type'],
                'media_id' => $result['fileid'],
                'errcode' => $result['errcode'],
                'created_at' => time()
            );
        }
        if(empty($result['errcode'])){
            $data = array(
                'type' => $result['type'],
                'media_id' => $result['media_id'],
                'file' => $file,
                'created_at' => $result['created_at'], 
                'ukey' => $this->ukey,
            );
            D('Addons://Wechat/WechatMedia')->data($data)->add();
        }
        return $result;
    }
    
    public function get_media($media_id){
        $access_token = $this->getToken();
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}";
        $result = $this->http($url); 
        $res = json_decode($result, true);
        if(empty($res['errcode'])){
            $res = array(
                'errcode' => 0,
                'data' => $result,
            );
        }
        return $res;
    }
    
    public function set_media(){
        $media_id = I('media_id');
        $result = $this->get_media($media_id);
        if(empty($result['errcode'])){
            $_config = C('PICTURE_UPLOAD');
            $dirname = $_config['rootPath'].date('Y-m-d').'/wx/heka/user/';
            !file_exists($dirname) && mkdir($dirname, 0777, true);
            $img_name = $dirname.date('ymdHis').rand(1000,9999);
            $img_file = $img_name.'.jpg';

            file_put_contents($img_file, $result['data']);
            $idata = array(
                'path' => substr($img_file, 1),
                'ukey' => $_GET['ukey'],
                'openid' => '',
                'create_time' => time(),
            );
            M('WechatImages')->add($idata);
            $result = array(
                'errcode' => 0,
                'data' => do_url($idata['path'])
            );
        }
        if(I('json') == 1){ 
            exit(json_encode($result));
        }else{
            return $result;
        }
    }
    public function set_voice($media_id, $time, $openid, $json = 0){
        $media_id = I('media_id', $media_id);
        $time = I('time', $time, 'intval');
        $json = I('json', $json);
        $openid = I('openid', $openid);
        $result = $this->get_media($media_id);
        if(empty($result['errcode'])){
            $_config = C('PICTURE_UPLOAD');
            $dirname = $_config['rootPath'].date('Y-m-d').'/wx/voice/';
            !file_exists($dirname) && mkdir($dirname, 0777, true);
            $img_name = $dirname.date('ymdHis').rand(1000,9999);
            $img_file = $img_name.'.mp3';

            file_put_contents($img_file, $result['data']);
            $idata = array(
                'path' => substr($img_file, 1),
                'ukey' => $this->ukey,
                'openid' => $openid,
                'create_time' => time(),
                'time' => $time,
                'media_id' => $media_id
            );
            $id = M('WechatVoices')->add($idata);
            $result = array(
                'errcode' => 0,
                'id' => $id
            );
        }
        if($json == 1){
            exit(json_encode($result));
        }else{
            return $result;
        }
    }
    function downloadWeixinFile($url) { 
        $ch = curl_init($url); 
        curl_setopt($ch, CURLOPT_HEADER, 0); 
        curl_setopt($ch, CURLOPT_NOBODY, 0); //只取body头 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch); 
        curl_close($ch); 
        return array('header' => $httpinfo, 'body' => $package) ;
    } 
    /**
     * 回复文本信息
     * @param  string $content 要回复的信息
     */
    private function text($content) {
        $this->data['Content'] = $content;
    }

    /**
     * 回复音乐信息
     * @param  string $content 要回复的音乐
     */
    private function music($music) {
        list(
                $music['Title'],
                $music['Description'],
                $music['MusicUrl'],
                $music['HQMusicUrl']
                ) = $music;
        $this->data['Music'] = $music;
    }

    /**
     * 回复图文信息
     * @param  string $news 要回复的图文内容
     */
    private function news($news) {
        $articles = array();
        foreach ($news as $key => $value) {
            list(
                    $articles[$key]['Title'],
                    $articles[$key]['Description'],
                    $articles[$key]['PicUrl'],
                    $articles[$key]['Url']
                    ) = $value;
            if ($key >= 9) {
                break;
            } //最多只允许10调新闻
        }
        $this->data['ArticleCount'] = count($articles);
        $this->data['Articles'] = $articles;
    }

    /**
     * 主动发送的信息
     * @param  string $data    json数据
     * @return string          微信返回信息
     */
    private function send($data = NULL) {
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
        $restr = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"), true);
        return $restr;
    }
    /**
     * 主动群发送的信息（根据用户分组）
     * @param  string       $type       类型
     * @param  string/array $data       发送的信息
     * @param  boolean      $is_to_all  是否发送给全部
     * @param  int          $group      用户分组ID 
     * @return string          微信返回信息
     */
    public function send_qun_group($type, $data = NULL, $is_to_all = true, $group = 0) {
        $type_data = $this->_send_qun_data($type, $data);
        if(!$type_data){
            return false;
        }
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token={$access_token}";
        $data = array();
        $data['filter'] = array(
            'is_to_all' => $is_to_all ? $is_to_all : false,
            'group' => intval($group)
        );
        $data['msgtype'] = $type;
        $data[$type] = $type_data;
        $result = $this->http($url, json_encode($data), 'POST', array("Content-type: text/html; charset=utf-8"), true);
        return json_decode($result, true);
    }
    /**
     * 主动群发送的信息
     * @param  string       $type       类型
     * @param  string/array $data       发送的信息
     * @param  string/array $openid     用户openid 多个时用数据形式
     * @return string          微信返回信息
     */
    public function send_qun_openid($type, $data = NULL, $openid = array()) {
        if(!$openid){
            return false;
        }
        $type_data = $this->_send_qun_data($type, $data);
        if(!$type_data){
            return false;
        }
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token={$access_token}";
        
        $data = array();
        $data['touser'] = is_array($openid) ? $openid : array($openid);
        $data['msgtype'] = $type;
        $data[$type] = $type_data;
        $result = $this->http($url, json_encode($data), 'POST');
        return json_decode($result, true);
    }/**
     * 预览发送的信息
     * @param  string       $openid     用户openid
     * @return string          微信返回信息
     */
    public function send_preview($type, $data = NULL, $openid = '') {
        if(!$openid){
            return false;
        }
        $type_data = $this->_send_qun_data($type, $data);
        if(!$type_data){
            return false;
        }
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token={$access_token}";
        
        $data = array();
        $data['touser'] = $openid;
        $data['msgtype'] = $type;
        $data[$type] = $type_data;
        $result = $this->http($url, json_encode($data), 'POST');
        return json_decode($result, true);
    }
    private function _send_qun_data($type, $data){
        switch($type){
            case 'mpnews':
                $type_data['media_id'] = $data;
                break;
            case 'text':
                $type_data['content'] = $data;
                break;
            case 'voice':
                $type_data['media_id'] = $data;
                break;
            case 'image':
                $type_data['media_id'] = $data;
                break;
            case 'mpvideo':
                $access_token = $this->getToken();
                $_url = "https://file.api.weixin.qq.com/cgi-bin/media/uploadvideo?access_token={$access_token}";
                if(is_array($data)){
                    $_data = array(
                        'media_id' => $data['media_id'],
                        'title' => $data['media_id'],
                        'description' => $data['description']
                    );
                }else{
                    $_data = array(
                        'media_id' => $data
                    );
                }
                $_restr = $this->http($_url, $data, 'POST');
                $_restr = json_decode($_restr, true);
                if(empty($_restr['media_id'])){
                    return false;
                }
                $type_data['media_id'] = $_restr['media_id'];
                break;
            default:
                return false;
        }
        return $type_data;
    }
    /**
     * 
     * @param string $msg_id  群发ID
     * @return type
     * @notice 请注意，只有已经发送成功的消息才能删除删除消息只是将消息的图文详情页失效，已经收到的用户，还是能在其本地看到消息卡片。 另外，删除群发消息只能删除图文消息和视频消息，其他类型的消息一经发送，无法删除。 
     */
    public function del_send_qun($msg_id){
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token={$access_token}";
        $data = array(
            'msg_id' => $msg_id
        );
        $restr = $this->http($url, $data, 'POST');
        return $restr;
    }
    /**
     * 查看群发送的状态
     * @param string $msg_id    群消息ID
     * @return type
     */
    public function get_send_qun_status($msg_id){
        $access_token = $this->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/get?access_token={$access_token}";
        $data = array(
            'msg_id' => $msg_id
        );
        $restr = $this->http($url, $data, 'POST');
        return $restr;
    }
    /**
     * 上传图文素材
     * @param type $lists
     * @return type
     */
    public function uploadnews($lists = NULL){
        if($this->conf['is_auth']){
            $access_token = $this->getToken();
            $url = "https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token={$access_token}";
            $articles = array();
            foreach ($lists as $key => $value) {
//                list(
//                        $articles[$key]['thumb_media_id'], // 多媒体图片ID
//                        $articles[$key]['author'],           // 作者
//                        $articles[$key]['title'],            // 标题
//                        $articles[$key]['content_source_url'],  // 原文地址
//                        $articles[$key]['content'],             // 内容
//                        $articles[$key]['digest'],              // 简介
//                        $articles[$key]['show_cover_pic']       // 图片是否显示在正文
//                        ) = $value;
                $value['content'] = str_replace("\"", "\\\"", $value['content']);
                foreach($value as &$v){
                    $v = urlencode($v);
                }
                $articles[$key] = array(
                        "thumb_media_id" => $value['thumb_media_id'], // 多媒体图片ID
                        "author" => empty($value['author']) ? "" : $value['author'],           // 作者
                        "title" => $value['title'],            // 标题
                        "content_source_url" => empty($value['content_source_url']) ? "" : $value['content_source_url'],  // 原文地址
                        "content" => $value['content'],             // 内容
                        "digest" => empty($value['digest']) ? "" : $value['digest'],              // 简介
                        "show_cover_pic" => empty($value['content_source_url']) ? "0" : "1",       // 图片是否显示在正文
                );
                if ($key >= 9) {
                    break;
                } //最多只允许10调新闻
            }
            $data = urldecode(json_encode(array('articles' => $articles)));
            $restr = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
            $result = json_decode($restr, true);
            return $result;
        }else{
            $result = A('Addons://Wechat/WechatDyclass')->create($file); 
        }
    }

    /**
     * 数据XML编码
     * @param  object $xml  XML对象
     * @param  mixed  $data 数据
     * @param  string $item 数字索引时的节点名称
     * @return string
     */
    private function data2xml($xml, $data, $item = 'item') {
        foreach ($data as $key => $value) {
            /* 指定默认的数字key */
            is_numeric($key) && $key = $item;

            /* 添加子元素 */
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }

    /**
     * 对数据进行签名认证，确保是微信发送的数据
     * @param  string $token 微信开放平台设置的TOKEN
     * @return boolean       true-签名正确，false-签名错误
     */
    private function auth() {
        /* 获取数据 */
        $data = array($this->conf['token'], $_GET['timestamp'], $_GET['nonce']);
        $sign = $_GET['signature'];
        /* 对数据进行字典排序 */
        sort($data, SORT_STRING);
        /* 生成签名 */
        $signature = sha1(implode($data));
        if ($signature == $sign) {
            return true;
        } else {
            return false;
        }
    }
    
    
    public function vtoken()
    {
        return $this->getToken();
    }
    
    
    public function vJsApiTicket()
    {
        return $this->getJsApiTicket();
    }

    /**
     * 获取保存的accesstoken
     */
    private function getToken() {
        $config_file = 'WECHATADDONS_TOKEN_' . $this->o_ukey;
        $stoken = S($config_file);
        if ($stoken == false) {
            $accesstoken = $this->getAcessToken(); // 去微信获取最新ACCESS_TOKEN
            S($config_file, $accesstoken, 5000); // 放进缓存
        } else {
            $accesstoken = $stoken;
        }
        return $accesstoken;
    }

    /**
     * 重新从微信获取accesstoken
     */
    private function getAcessToken() {
        $token = $this->conf['token'];
        $appid = $this->conf['appid'];
        $appsecret = $this->conf['appsecret'];
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $params = array();
        $params ['grant_type'] = 'client_credential';
        $params ['appid'] = $appid;
        $params ['secret'] = $appsecret;
        $httpstr = $this->http($url, $params);
        $harr = json_decode($httpstr, true);
        return $harr ['access_token'];
    }

    public function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        
        $config_file = 'WECHATADDONS_JSAPI_TICKET_' . $this->o_ukey;
        $data = S($config_file);
        if ($data == false || $data['expire_time'] < time()) {
            $accessToken = $this->getToken();
            $url = "http://api.weixin.qq.com/cgi-bin/ticket/getticket?type=1&access_token=$accessToken";
            $res = json_decode($this->http($url), true);
            $ticket = empty($res['ticket']) ? '' : $res['ticket'];
            if ($ticket) {
                $data['expire_time'] = time() + 7000;
                $data['jsapi_ticket'] = $ticket;
                S($config_file, $data);
            }
        }else{
            $ticket = $data['jsapi_ticket'];
        }
        
        return $ticket;
    }
    
    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();
        $url = (is_ssl() ? "https://" : "http://") ."{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->conf['appid'],
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage; 
    }
    
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
          $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
///////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    private function http($url, $params, $method = 'GET', $header = array(), $multi = false, $config = array()) {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header,
        );
        foreach($config as $k => $v){
            $opts[$k] = $v;
        }
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $param = is_array($params) ? '?' . http_build_query($params) : '';
                $opts[CURLOPT_URL] = $url . $param;
                break;
            case 'POST':
                //判断是否传输文件
                //$params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Think\ThinkException('不支持的请求方式！');
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error)
            throw new \Think\ThinkException('请求发生错误：' . $error);
        return $data;
    }

    /**
     * 不转义中文字符和\/的 json 编码方法
     * @param array $arr 待编码数组
     * @return string
     */
    public function jsencode_old($arr) {
        $str = str_replace("\\/", "/", json_encode($arr));
        $search = "#\\\u([0-9a-f]+)#ie";

        if (strpos(strtoupper(PHP_OS), 'WIN') === false) {
            $replace = "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))"; //LINUX
        } else {
            $replace = "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))"; //WINDOWS
        }

        return preg_replace($search, $replace, $str);
    }
    
    public function jsencode($arr) {
        if(version_compare(PHP_VERSION,'5.4.0','<')){
            $str = str_replace("\\/", "/", json_encode($arr));
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i",function($matchs){
                return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
            },$str);
            return $str;
        } else {
            $str = json_encode($arr, JSON_UNESCAPED_UNICODE);
            $str = str_replace("\\/", "/", $str);
            return $str;
        } 
    }    
    
    

    public function getconf($str) {
        $d = array();
        if (!empty($str)) {
            $d = explode("/", $str);

            $s = $this->conf;

            $i = 0;
            do {
                $s = $s[$d[$i]];
                $i++;
            } while (is_array($s) && $d[$i]);
            return $s;
        } else {
            return '';
        }
    }
    
    public function oauth_user($type = 'base'){
        $token_data = session('oauth_token_'.$this->ukey);
        if(empty($token_data['access_token'])){
            $token_data = false;
        }
        if($token_data && $token_data['create_time']+$token_data['expires_in']<=time()){
            $token_data = $this->refresh_token($token_data['refresh_token']);
            if(!empty($token_data['errcode'])){
                $token_data = false;
            }else{ 
                $token_data['create_time'] = time();
                session('oauth_token_'.$this->ukey, $token_data);
            }
        }
        if(!$token_data){
            $code = I('get.code', '');
            if(!$code){
                if(in_array($type, array('base', 'userinfo'))){
                    $snsapi = 'snsapi_'.$type;
                }else{
                    $snsapi = 'snsapi_base';
                }
                $this->get_sns_code($snsapi);
            }
            $token_data = $this->get_sns_token();
            $token_data['create_time'] = time();
            session('oauth_token_'.$this->ukey, $token_data);
        }
        if($type == 'base'){
            return $token_data;
        }else{
            $user = $this->get_sns_user($token_data['openid'], $token_data['access_token']);
            return $user;
        }
    }
    
    public function get_sns_user($openid, $token){
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$token}&openid={$openid}&lang=zh_CN";
        $httpstr = $this->http($url);
        return json_decode($httpstr, true);
    }
    
    private function get_sns_token(){
        $appid = $this->conf['appid'];
        $appsecret = $this->conf['appsecret'];
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $params = array();
        $params ['grant_type'] = 'authorization_code';
        $params ['appid'] = $appid;
        $params ['secret'] = $appsecret;
        $params['component_appid'] = '';
        $params['component_access_token'] = '';
        $params ['code'] = I('get.code');
        $httpstr = $this->http($url, $params);
        $harr = json_decode($httpstr, true);
        return $harr;
    }
    
    private function refresh_token($refresh_token){
        $appid = $this->conf['appid'];
        $url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$appid}&grant_type=refresh_token&refresh_token={$refresh_token}";
        $httpstr = $this->http($url);
        $harr = json_decode($httpstr, true);
        return $harr;
    }
    
    private function get_sns_code($scope = 'snsapi_base'){
        $appid = $this->conf['appid'];
        $redirect_uri = urlencode((is_ssl()?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state=STATE#wechat_redirect";
        redirect($url);
        exit;
    }
    /**
     * 发送模板消息
     * @param $openid
     * @param $template_id
     * @param $data
     * @param string $url
     * @param string $topcolor
     * @return mixed
     * @throws \Think\ThinkException
     */
    public function tpl_msg($openid, $template_id, $data, $click_url='', $topcolor="#FF0000"){
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$this->getToken();
        foreach($data as $k => $v){
            if(is_string($v)){
                $data[$k] = array(
                    'value' => $v
                );
            }
        }
        $data = array(
                "touser"=>$openid,
                "template_id"=>$template_id,
                "url"=>$click_url,
                "topcolor"=>$topcolor,
            )+array('data'=>$data);
        $sendjson = $this->jsencode($data);
        $httpstr = $this->http($url, $sendjson, 'POST');
        $harr = json_decode($httpstr, true);
        return $harr;
    }
}