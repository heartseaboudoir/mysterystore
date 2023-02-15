<?php
// OneThink常量定义
const ONETHINK_VERSION    = '1.0.131218';
const ONETHINK_ADDON_PATH = './Addons/';

/**
 * 系统公共库文件
 * 主要定义系统公共函数库
 */

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID

 */
function is_login($ukey = ''){
    $user = session('user_auth'.$ukey);
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign'.$ukey) == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

function get_warehouse_name($w_id){
    $WModel = M('Warehouse');
    $Wdata = $WModel->where('w_id=' .$w_id)->find();
    if($Wdata){
        return $Wdata['w_name'];
    }else{
        return false;
    }
}

function get_goods_name($goods_id){
    $WModel = M('Goods');
    $Wdata = $WModel->where('id=' .$goods_id)->find();
    if($Wdata){
        return $Wdata['title'];
    }else{
        return false;
    }
}

function get_store_name($store_id){
    $WModel = M('Store');
    $Wdata = $WModel->where('id=' .$store_id)->find();
    if($Wdata){
        return $Wdata['title'];
    }else{
        return false;
    }
}
/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员

 */
function is_administrator($uid = null){
    $uid = is_null($uid) ? is_login() : $uid;
    return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str  要分割的字符串
 * @param  string $glue 分割符
 * @return array

 */
function str2arr($str, $glue = ','){
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array  $arr  要连接的数组
 * @param  string $glue 分割符
 * @return string

 */
function arr2str($arr, $glue = ','){
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice.'...' : $slice;
}
//截断字符串
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length)
        return mb_substr($text, 0, $length, 'utf8').'...';
    return $text;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 单位 秒
 * @return string

 */
function think_encrypt($data, $key = '', $expire = 0) {
    $key  = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time():0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key  加密密钥
 * @return string

 */
function think_decrypt($data, $key = ''){
    $key    = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data   = str_replace(array('-','_'),array('+','/'),$data);
    $mod4   = strlen($data) % 4;
    if ($mod4) {
       $data .= substr('====', $mod4);
    }
    $data   = base64_decode($data);
    $expire = substr($data,0,10);
    $data   = substr($data,10);

    if($expire > 0 && $expire < time()) {
        return '';
    }
    $x      = 0;
    $len    = strlen($data);
    $l      = strlen($key);
    $char   = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名

 */
function data_auth_sign($data) {
    //数据类型检测
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
* 对查询结果集进行排序
* @access public
* @param array $list 查询结果
* @param string $field 排序的字段名
* @param array $sortby 排序类型
* asc正向排序 desc逆向排序 nat自然排序
* @return array
*/
function list_sort_by($list,$field, $sortby='asc') {
   if(is_array($list)){
       $refer = $resultSet = array();
       foreach ($list as $i => $data)
           $refer[$i] = &$data[$field];
       switch ($sortby) {
           case 'asc': // 正向排序
                asort($refer);
                break;
           case 'desc':// 逆向排序
                arsort($refer);
                break;
           case 'nat': // 自然排序
                natcasesort($refer);
                break;
       }
       foreach ( $refer as $key=> $val)
           $resultSet[] = &$list[$key];
       return $resultSet;
   }
   return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array

 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree  原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list  过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function tree_to_list($tree, $child = '_child', $order='id', &$list = array()){
    if(is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if(isset($reffer[$child])){
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby='asc');
    }
    return $list;
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小

 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 设置跳转页面URL
 * 使用函数再次封装，方便以后选择不同的存储方式（目前使用cookie存储）

 */
function set_redirect_url($url){
    cookie('redirect_url', $url);
}

/**
 * 获取跳转页面URL
 * @return string 跳转页URL

 */
function get_redirect_url(){
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}

/**
 * 处理插件钩子
 * @param string $hook   钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook,$params=array()){
    \Think\Hook::listen($hook,$params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name){
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 */
function get_addon_config($name){
    $class = get_addon_class($name);
    if(class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    }else {
        return array();
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数

 */
function addons_url($url, $param = array()){
    $url        = parse_url($url);
    $case       = C('URL_CASE_INSENSITIVE');
    $addons     = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action     = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if(isset($url['query'])){
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons'     => $addons,
        '_controller' => $controller,
        '_action'     => $action,
    );
    $params = array_merge($params, $param); //添加额外参数
    // 定义入口名
    $change = C('URL_ADDONS_CHANGE');
    $in_change = array_keys($change);
    if(MODULE_NAME == 'Admin'){
        $en_method = in_array($addons, $in_change) ? ('ex_'.$change[$addons]) : ('ex_'.$addons);
    }else{
        $en_method = !in_array($addons, $in_change) ? 'execute' : ('ex_'.$change[$addons]);
    }
    return U('Addons/'.$en_method, $params);
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 根据用户ID获取用户名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_username($uid = 0){
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        return session('user_auth.username');
    }

    /* 获取缓存数据 */
    if(empty($list)){
        $list = S('sys_active_user_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key])){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $req = \User\Client\Api::execute('User', 'info', array('uid' => $uid));
        if($req['status'] != 1){
            $info = array();
        }else{
            $info = $req['data'];
        }
        if($info && isset($info[1])){
            $name = $list[$key] = $info[1];
            /* 缓存用户 */
            $count = count($list);
            $max   = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_active_user_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}
/**
 * 根据用户ID获取用户手机号码
 * @param  integer $uid 用户ID
 * @return string       手机号码
 */
function get_mobile($uid = 0, $cache = true){
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        $uid = session('user_auth.uid');
    }
    /* 获取缓存数据 */
    if(empty($list) && $cache){
        $list = S('sys_active_user_mobile_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key]) && $cache){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $req = \User\Client\Api::execute('User', 'info', array('uid' => $uid));
        if($req['status'] != 1){
            $info = array();
        }else{
            $info = $req['data'];
        }
        if($info && isset($info[3])){
            $name = $list[$key] = $info[3];
            /* 缓存用户 */
            $count = count($list);
            $max   = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_active_user_mobile_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}
/**
 * 设置用户手机号码缓存值
 * @param type $uid      用户ID
 * @param type $mobile   手机号码
 */
function set_mobile($uid, $mobile){
    $list = S('sys_active_user_mobile_list');
    $key = "u{$uid}";
    $list[$key] = $mobile;
    S('sys_active_user_mobile_list', $list);
}
/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_header_pic($uid = 0, $type = 'uid'){
    $uid = intval($uid);
    if($type == 'cover_id'){
        $cover_id = $uid;
    }else{
        $info = M('Member')->field('cover_id')->find($uid);
        $cover_id = $info ? $info['cover_id'] : 0;
    }
    if($cover_id > 0){
        $name = get_cover_url($cover_id);
    } else {
        $name = '';
    }
    !$name && $name = get_domain().'/Public/res/static/default_headerimg.png';
    return $name;
}
/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_nickname($uid = 0){
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        $uid = session('user_auth.uid');
    }

    /* 获取缓存数据 */
    if(empty($list)){
        $list = S('sys_user_nickname_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key])){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $info = M('Member')->field('nickname')->find($uid);
        if($info !== false && $info['nickname'] ){
            $nickname = $info['nickname'];
            $name = $list[$key] = $nickname;
            /* 缓存用户 */
            $count = count($list);
            $max   = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_user_nickname_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}
/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_nickname_jinjiang($uid,$pay_type = 3){
    if($pay_type == 3 || $pay_type == 4){
        return '锦江';
    }
    
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        $uid = session('user_auth.uid');
    }
    
    /* 获取缓存数据 */
    if(empty($list)){
        $list = S('sys_user_nickname_list');
    }
    
    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key])){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $info = M('Member')->field('nickname')->find($uid);
        if($info !== false && $info['nickname'] ){
            $nickname = $info['nickname'];
            $name = $list[$key] = $nickname;
            /* 缓存用户 */
            $count = count($list);
            $max   = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_user_nickname_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
    
}
/**
 * 根据用户消费的order表id 去重后 计算 消费次数  (目前用户分析~全局 用  赵忠亚添加)
 * @param type $string  order表的id字符串  1,2,3,3,4
 */
function get_order_sn_count($string){
    if(empty($string)){
        return 0;
    }
    $string = count(array_unique(explode(',',$string)));
    return $string;
    
}
/**
 * 根据用户消费的order表门店 去重后 输出用户消费的门店名称  (目前用户分析~全局 用  赵忠亚添加)
 * @param type $string  order表的id字符串  1门店,2门店,3门店,3门店,门店
 */
function get_order_store_name_store($string){
    if(empty($string)){
        return '';
    }
    $string = implode(";\r\n",array_unique(explode(',',$string)));
    return $string;
    
}
/**
 * 设置用户昵称缓存值
 * @param type $uid      用户ID
 * @param type $nickname 昵称
 */
function set_nickname($uid, $nickname){
    $list = S('sys_user_nickname_list');
    $key = "u{$uid}";
    $list[$key] = $nickname;
    S('sys_user_nickname_list', $list);
}
/**
 * 获取分类信息并缓存分类
 * @param  integer $id    分类ID
 * @param  string  $field 要获取的字段名
 * @return string         分类信息
 */
function get_category($id, $field = null){
    static $list;

    /* 非法分类ID */
    if(empty($id) || !is_numeric($id)){
        return '';
    }

    /* 读取缓存数据 */
    if(empty($list)){
        $list = S('sys_category_list');
    }

    /* 获取分类名称 */
    if(!isset($list[$id])){
        $cate = M('Category')->find($id);
        if(!$cate || 1 != $cate['status']){ //不存在分类，或分类被禁用
            return '';
        }
        $list[$id] = $cate;
        S('sys_category_list', $list); //更新缓存
    }
    return is_null($field) ? $list[$id] : $list[$id][$field];
}

/* 根据ID获取分类标识 */
function get_category_name($id){
    return get_category($id, 'name');
}

/* 根据ID获取分类名称 */
function get_category_title($id){
    return get_category($id, 'title');
}

/**
 * 获取文档模型信息
 * @param  integer $id    模型ID
 * @param  string  $field 模型字段
 * @return array
 */
function get_document_model($id = null, $field = null){
    static $list;

    /* 非法分类ID */
    if(!(is_numeric($id) || is_null($id))){
        return '';
    }

    /* 读取缓存数据 */
    if(empty($list)){
        $list = S('DOCUMENT_MODEL_LIST');
    }

    /* 获取模型名称 */
    if(empty($list)){
        $map   = array('status' => 1, 'extend' => 1);
        $model = M('Model')->where($map)->field(true)->select();
        foreach ($model as $value) {
            $list[$value['id']] = $value;
        }
        S('DOCUMENT_MODEL_LIST', $list); //更新缓存
    }

    /* 根据条件返回数据 */
    if(is_null($id)){
        return $list;
    } elseif(is_null($field)){
        return $list[$id];
    } else {
        return $list[$id][$field];
    }
}

/**
 * 解析UBB数据
 * @param string $data UBB字符串
 * @return string 解析为HTML的数据

 */
function ubb($data){
    //TODO: 待完善，目前返回原始数据
    return $data;
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @return boolean
 * @author huajie <banhuajie@163.com>
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null){

    //参数检查
    if(empty($action) || empty($model) || empty($record_id)){
        return '参数不能为空';
    }
    if(empty($user_id)){
        $user_id = is_login();
    }

    //查询行为,判断是否执行
    $action_info = M('Action')->getByName($action);
    if($action_info['status'] != 1){
        return '该行为被禁用或删除';
    }

    //插入行为日志
    $data['action_id']      =   $action_info['id'];
    $data['user_id']        =   $user_id;
    $data['action_ip']      =   ip2long(get_client_ip());
    $data['model']          =   $model;
    $data['record_id']      =   $record_id;
    $data['create_time']    =   NOW_TIME;

    //解析日志规则,生成日志备注
    if(!empty($action_info['log'])){
        if(preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)){
            $log['user']    =   $user_id;
            $log['record']  =   $record_id;
            $log['model']   =   $model;
            $log['time']    =   NOW_TIME;
            $log['data']    =   array('user'=>$user_id,'model'=>$model,'record'=>$record_id,'time'=>NOW_TIME);
            foreach ($match[1] as $value){
                $param = explode('|', $value);
                if(isset($param[1])){
                    $replace[] = call_user_func($param[1],$log[$param[0]]);
                }else{
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] =   str_replace($match[0], $replace, $action_info['log']);
        }else{
            $data['remark'] =   $action_info['log'];
        }
    }else{
        //未定义日志规则，记录操作url
        $data['remark']     =   '操作url：'.$_SERVER['REQUEST_URI'];
    }

    M('ActionLog')->add($data);

    if(!empty($action_info['rule'])){
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}

/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author huajie <banhuajie@163.com>
 */
function parse_action($action = null, $self){
    if(empty($action)){
        return false;
    }

    //参数支持id或者name
    if(is_numeric($action)){
        $map = array('id'=>$action);
    }else{
        $map = array('name'=>$action);
    }

    //查询行为信息
    $info = M('Action')->where($map)->find();
    if(!$info || $info['status'] != 1){
        return false;
    }

    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key=>&$rule){
        $rule = explode('|', $rule);
        foreach ($rule as $k=>$fields){
            $field = empty($fields) ? array() : explode(':', $fields);
            if(!empty($field)){
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if(!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])){
            unset($return[$key]['cycle'],$return[$key]['max']);
        }
    }

    return $return;
}

/**
 * 执行行为
 * @param array $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 * @author huajie <banhuajie@163.com>
 */
function execute_action($rules = false, $action_id = null, $user_id = null){
    if(!$rules || empty($action_id) || empty($user_id)){
        return false;
    }

    $return = true;
    foreach ($rules as $rule){

        //检查执行周期
        $map = array('action_id'=>$action_id, 'user_id'=>$user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActionLog')->where($map)->count();
        if($exec_count > $rule['max']){
            continue;
        }

        //执行数据库操作
        $Model = M(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));

        if(!$res){
            $return = false;
        }
    }
    return $return;
}

//基于数组创建目录和文件
function create_dir_or_files($files){
    foreach ($files as $key => $value) {
        if(substr($value, -1) == '/'){
            mkdir($value);
        }else{
            @file_put_contents($value, '');
        }
    }
}

if(!function_exists('array_column')){
    function array_column(array $input, $columnKey, $indexKey = null) {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 * @author huajie <banhuajie@163.com>
 */
function get_table_name($model_id = null){
    if(empty($model_id)){
        return false;
    }
    $Model = M('Model');
    $name = '';
    $info = $Model->getById($model_id);
    if($info['extend'] != 0){
        $name = $Model->getFieldById($info['extend'], 'name').'_';
    }
    $name .= $info['name'];
    return $name;
}

/**
 * 获取属性信息并缓存
 * @param  integer $id    属性ID
 * @param  string  $field 要获取的字段名
 * @return string         属性信息
 */
function get_model_attribute($model_id, $group = true){
    static $list;

    /* 非法ID */
    if(empty($model_id) || !is_numeric($model_id)){
        return '';
    }

    /* 读取缓存数据 */
    if(empty($list)){
        $list = S('attribute_list');
    }

    /* 获取属性 */
    if(!isset($list[$model_id])){
        $map = array('model_id'=>$model_id);
        $extend = M('Model')->getFieldById($model_id,'extend');

        if($extend){
            $map = array('model_id'=> array("in", array($model_id, $extend)));
        }
        $info = M('Attribute')->where($map)->select();
        $list[$model_id] = $info;
        //S('attribute_list', $list); //更新缓存
    }

    $attr = array();
    foreach ($list[$model_id] as $value) {
        $attr[$value['id']] = $value;
    }

    if($group){
        $sort  = M('Model')->getFieldById($model_id,'field_sort');

        if(empty($sort)){	//未排序
            $group = array(1=>array_merge($attr));
        }else{
            $group = json_decode($sort, true);

            $keys  = array_keys($group);
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if(!empty($attr)){
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }
        $attr = $group;
    }
    return $attr;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string  $name 格式 [模块名]/接口名/方法名
 * @param  array|string  $vars 参数
 */
function api($name,$vars=array()){
    $array     = explode('/',$name);
    $method    = array_pop($array);
    $classname = array_pop($array);
    $module    = $array? array_pop($array) : 'Common';
    $callback  = $module.'\\Api\\'.$classname.'Api::'.$method;
    if(is_string($vars)) {
        parse_str($vars,$vars);
    }
    return call_user_func_array($callback,$vars);
}

/**
 * 根据条件字段获取指定表的数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @param string $table 需要查询的表
 * @author huajie <banhuajie@163.com>
 */
function get_table_field($value = null, $condition = 'id', $field = null, $table = null){
    if(empty($value) || empty($table)){
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M(ucfirst($table))->where($map);
    if(empty($field)){
        $info = $info->field(true)->find();
    }else{
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * 获取链接信息
 * @param int $link_id
 * @param string $field
 * @return 完整的链接信息或者某一字段
 * @author huajie <banhuajie@163.com>
 */
function get_link($link_id = null, $field = 'url'){
    $link = '';
    if(empty($link_id)){
        return $link;
    }
    $link = M('Url')->getById($link_id);
    if(empty($field)){
        return $link;
    }else{
        return $link[$field];
    }
}

/**
 * 获取文档封面图片
 * @param int $cover_id
 * @param string $field
 * @return 完整的数据  或者  指定的$field字段值
 * @author huajie <banhuajie@163.com>
 */
function get_cover($cover_id, $field = null){
    
    if ($field == 'path') {
        return get_cover_url($cover_id);
    }
    
    if(empty($cover_id)){
        return false;
    }
    $picture = M('Picture')->where(array('status'=>1))->getById($cover_id);
    return empty($field) ? $picture : $picture[$field];
}



function get_cover_url($cover_id){
    if(empty($cover_id)){
        return '';
    }
    $picture = M('Picture')->where(array('status'=>1))->getById($cover_id);
    
    if (empty($picture) || empty($picture['path'])) {
        return '';
    }
    
    $path = $picture['path'];
    
    
    
    if ($picture['sync'] == 1) {
        $domain = 'zhaike.oss-cn-shanghai.aliyuncs.com';
    } else {
        $domain = $_SERVER['HTTP_HOST'];
    }
    
    $domain = (is_ssl()?'https://':'http://') . $domain;
    
    return $domain . $path;
}


/**
 * 获取文档封面图片
 * @param int $cover_id
 * @param string $field
 * @return 完整的数据  或者  指定的$field字段值
 * @author huajie <banhuajie@163.com>
 */
function get_cover_old($cover_id, $field = null){
    if(empty($cover_id)){
        return false;
    }
    $picture = M('Picture')->where(array('status'=>1))->getById($cover_id);
    return empty($field) ? $picture : $picture[$field];
}

/**
 * 获取文档封面图片完整URL
 * @param int $cover_id
 * @author huajie <banhuajie@163.com>
 */
function get_cover_url_old($cover_id){
    if(empty($cover_id)){
        return false;
    }
    $path = get_cover($cover_id, 'path');
    if(empty($path)){
        return '';
    }else{
        return get_domain().$path;
    }
}

/**
 * 检查$pos(推荐位的值)是否包含指定推荐位$contain
 * @param number $pos 推荐位的值
 * @param number $contain 指定推荐位
 * @return boolean true 包含 ， false 不包含
 * @author huajie <banhuajie@163.com>
 */
function check_document_position($pos = 0, $contain = 0){
    if(empty($pos) || empty($contain)){
        return false;
    }

    //将两个参数进行按位与运算，不为0则表示$contain属于$pos
    $res = $pos & $contain;
    if($res !== 0){
        return true;
    }else{
        return false;
    }
}

/**
 * 获取数据的所有子孙数据的id值
 * @author 朱亚杰 <xcoolcc@gmail.com>
 */

function get_stemma($pids,Model &$model, $field='id'){
    $collection = array();

    //非空判断
    if(empty($pids)){
        return $collection;
    }

    if( is_array($pids) ){
        $pids = trim(implode(',',$pids),',');
    }
    $result     = $model->field($field)->where(array('pid'=>array('IN',(string)$pids)))->select();
    $child_ids  = array_column ((array)$result,'id');

    while( !empty($child_ids) ){
        $collection = array_merge($collection,$result);
        $result     = $model->field($field)->where( array( 'pid'=>array( 'IN', $child_ids ) ) )->select();
        $child_ids  = array_column((array)$result,'id');
    }
    return $collection;
}
/**
 * 目录或文件复制
 * @param type $src  复制的目录或文件
 * @param type $dst  目标路径
 */
function recurse_copy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    @chown($dst, '0777');
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
                @chown($src . '/' . $file,$dst . '/' . $file, '0777');
            } 
        } 
    } 
    closedir($dir); 
}

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
 }
/**
 * 执行SQL文件
 */
function execute_sql_file($sql_path, $prefix = null) {
	// 读取SQL文件
	$sql = file_get_contents ( $sql_path );
	$sql = str_replace ( "\r", "\n", $sql );
	$sql = explode ( ";\n", $sql );
	
	// 替换表前缀
	$orginal = 'tablepre_';
	is_null($prefix) && $prefix = C ( 'DB_PREFIX' );
	$sql = str_replace ( " `{$orginal}", " `{$prefix}", $sql );
	// 开始安装
	foreach ( $sql as $value ) {
		$value = trim ( $value );
		if (empty ( $value ))
			continue;
		$res = M ()->execute ( $value );
                if(M()->getDbError()){
                    return false;
                }
	}
        return true;
}
function get_domain(){
    return (is_ssl()?'https://':'http://').$_SERVER['HTTP_HOST'];
}
/**
 *  json返回
 * @param int $status
 * @param array $data
 * @param string $msg
 */
function json_return($status=1, $msg='', $data=array()){
    header('Content-Type:application/json; charset=utf-8');
    exit(
    json_encode(array(
        'status'=>$status,
        'data'=>$data,
        'msg'=>$msg,
    ))
    );
}

function i_int($field){
    return I($field, 0, 'intval') ;
}

/**
 * 以指定key的value作为多维数组的key, $is_kv:true 返回仅包含key的value数组
 */
function array_as_key($array, $key, $is_kv = false) {
    $data = array();
    foreach ($array as $v) {
        if ($is_kv)
            $data[] = $v[$key];
        else
            $data[$v[$key]] = $v;
    }
    return $data;
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params='', $method = 'GET', $header = array(), $multi = false){
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header
    );

    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            $param = is_array($params)?'?'.http_build_query($params):'';
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
//            throw new \Think\ThinkException('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
//    if($error) throw new \Think\ThinkException('请求发生错误：' . $error);
    return  $data;
}


  /**
     * 中文分词  
         * @params string $title 需要分词的语句 
         * @params int $num  分词个数，默认不用填写
     **/
function get_tags($title,$array = true, $num=null){        
    $pscws = new \Org\Util\Pscws('utf8');
    $pscws->set_charset('utf-8');
    $pscws->set_dict(LIB_PATH . 'Org/Util/dict.utf8.xdb');
    $pscws->set_rule(LIB_PATH . 'Org/Util/rules.utf8.ini');
    $pscws->set_ignore(true);
    $pscws->send_text($title);
    $words = $pscws->get_tops($num);
    $pscws->close();
    $tags = array();
    foreach ($words as $val) {
        $tags[] = $val['word'];
    }
    return $array ? $tags : implode(',', $tags);
}

function get_pinyin($str){
    $Pinyin = new \Org\Util\ChinesePinyin();
    return $Pinyin->TransformWithoutTone($str);
}

function get_letter($str){
    $Pinyin = new \Org\Util\ChinesePinyin();
    return $Pinyin->TransformUcwords($str);
}

/**
 * 生成6位验证码
 * @param type $name  缓存标识名
 * @param type $key   验证对象标识
 * @param type $time  保存时间（秒）默认为600
 * @return type
 */
function make_code($name, $key, $time = 600){
    $time = intval($time);
    $code_data = S($name);
    if(!empty($code_data[$key]['code']) && $code_data[$key]['last_time'] > NOW_TIME){
        $code = $code_data[$key]['code'];
    }else{
        $code = strval(mt_rand(100000, 999999));
    }
    $code_data[$key] = array(
        'code' => $code,
        'last_time' => NOW_TIME+$time
    );
    S($name, $code_data, 600);
    return $code;
}
/**
 * 检测6位验证码
 * @param type $name  缓存标识名
 * @param type $key   验证对象标识
 * @param type $code  验证码
 * @param type $is_remove  是否在检测成功后移除验证码
 * @return type
 */
function check_code($name, $key, $code, $is_remove = false){
    $data = S($name);
    if(empty($data[$key]['code'])){
        return array('status' => 0, 'msg' => '验证码错误');
    }elseif($data[$key]['last_time'] < NOW_TIME){
        return array('status' => -1, 'msg' => '验证码已失效');
    }elseif($code != $data[$key]['code']){
        return array('status' => 0, 'msg' => '验证码错误');
    }
    if($is_remove){
        unset($data[$key]);
        $data ? S($name, $data) : S($name, NULL);
    }
    return array('status' => 1, 'msg' => '');
}
/**
 * 销毁验证码
 * @param type $name  缓存标识名
 * @param type $key   验证对象标识
 * @param type $code  验证码
 * @param type $is_remove  是否在检测成功后移除验证码
 * @return type
 */
function unset_code($name, $key){
    $data = S($name);
    if(isset($data[$key])){
        unset($data[$key]);
        $data ? S($name, $data) : S($name, NULL);
    }
    return true;
}
/**
 * 获取分享配置
 * @param type $name  分享的标识
 * @return boolean
 */
function share_config($name){
    $info = M('ShareConfig')->where(array('name' => $name))->field('title,desc,url,cover_id')->find();
    if(!$info) return false;
    $info['cover'] = get_cover_url($info['cover_id']);
    unset($info['cover_id']);
    return $info;
}

function send_sms_old($mobile, $tpl, $param = array()){
    if(!$mobile || !preg_match('/^1\d{10}$/', $mobile, $match)){
        return array('status' => 0, 'info' => '手机号码错误');
    }
    if(!$tpl){
        return array('status' => 0, 'info' => '短信模板未知');
    }
    $param = $param ? json_encode($param) : '';
    $sign = '神秘商店';
    $Sms = new Addons\Alisms\Lib\SmsLib();
    $result = $Sms->send($mobile, $param, $tpl, $sign);
    if($result['status'] == 1){
        return array('status' => 1);
    }else{
        return array('status' => 0, 'info' => $result['msg']);
    }
}


function send_sms($mobile, $tpl, $param = array()){
    if(!$mobile || !preg_match('/^1\d{10}$/', $mobile, $match)){
        return array('status' => 0, 'info' => '手机号码错误');
    }
    if(!$tpl){
        return array('status' => 0, 'info' => '短信模板未知');
    }
    $param = $param ? json_encode($param) : '';
    $sign = '神秘商店';
    $Sms = new Addons\Alisms\Lib\SmsLibNew();
    $result = $Sms->send($mobile, $param, $tpl, $sign);
    if($result['status'] == 1){
        return array('status' => 1);
    }else{
        return array('status' => 0, 'info' => $result['msg']);
    }
}

function send_sms_voice($mobile, $tpl, $param = array()){
    if(!$mobile || !preg_match('/^1\d{10}$/', $mobile, $match)){
        return array('status' => 0, 'info' => '手机号码错误');
    }
    if(!$tpl){
        return array('status' => 0, 'info' => '短信模板未知');
    }
    $param = $param ? json_encode($param) : '';
    $sign = '神秘商店';
    $Sms = new Addons\Alisms\Lib\SmsLibNew();
    $result = $Sms->send_voice($mobile, $param, $tpl, $sign);
    if($result['status'] == 1){
        return array('status' => 1);
    }else{
        return array('status' => 0, 'info' => $result['msg']);
    }
}

/**
 * 获取以某一个值为key的数组
 * @param string   $data   多维数组
 * @param string   $key    key
 * @return type
 */
function reset_data($data, $key){
    $_data = array();
    if($data){
        foreach($data as $v){
            $_data[$v[$key]] = $v;
        }
    }
    return $_data;
}
/**
 * 获取以某一值为key，某一值为value的数组
 * @param string   $data   多维数组
 * @param string   $key    key
 * @param string   $field  对象key
 * @return type
 */
function reset_data_field($data, $key, $field){
    $_data = array();
    if($data){
        foreach($data as $v){
            $_data[$v[$key]] = $v[$field];
        }
    }
    return $_data;
}
/**
 * 检查身份证号是否正常
 * @param type $id   身份证号
 * @return boolean
 */
function check_idcard($id) {
    $id = strtoupper($id);
    $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array();
    if (!preg_match($regx, $id)) {
        return FALSE;
    }
    if (15 == strlen($id)) { //检查15位 
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

        @preg_match($regx, $id, $arr_split);
        //检查生日日期是否正确 
        $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) {
            return FALSE;
        } else {
            return TRUE;
        }
    } else {      //检查18位 
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) { //检查生日日期是否正确 
            return FALSE;
        } else {
            //检验18位身份证的校验码是否正确。 
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。 
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign = 0;
            for ($i = 0; $i < 17; $i++) {
                $b = (int) $id{$i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }
            $n = $sign % 11;
            $val_num = $arr_ch[$n];
            if ($val_num != substr($id, 17, 1)) {
                return FALSE;
            }
            else {
                return TRUE;
            }
        }
    }
}
function check_area_in($sheng, $shi = 0, $qu = 0){
    $sheng = intval($sheng);
    $shi = intval($shi);
    $qu = intval($qu);
    $area_ids = array();
    $area_ids[] = $sheng;
    $shi > 0 && $area_ids[] = $shi;
    $qu > 0 && $area_ids[] = $qu;
    $area_data = M('Area')->where(array('id' => array('in', $area_ids)))->select();
    if($area_data){
        foreach($area_data as $v){
            $_area[$v['id']] = $v;
        }
    }
    if(empty($_area[$sheng]) || $_area[$sheng]['pid'] != 0){
        return -1;
    }
    if($shi > 0 && empty($_area[$shi]) || $_area[$shi]['pid'] != $sheng){
        return -2;
    }
    if($qu > 0 && empty($_area[$qu]) || $_area[$qu]['pid'] != $shi){
        return -3;
    }
    return 1;
}

function get_store_header($store_id = 0){
    return get_domain().'/Public/res/static/store_header.jpg';
}

function xydebug($data, $filename = 'test.txt')
{
    $datetime = date('Y-m-d H:i:s');
    $data = print_r($data, true);
    file_put_contents('/data/debug/' . $filename, $datetime . "\r\n" . $data . "\r\n\r\n", FILE_APPEND);
} 