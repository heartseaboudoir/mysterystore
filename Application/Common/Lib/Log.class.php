<?php
namespace Common\Lib;

/**
 * 日志类
 * @author 小马哥 Robbie
 */
class Log {
    
    public function add_log($name, $dir, $action_data = array(), $info_data = array()){
        $log_dir = RUNTIME_PATH.'_LOG_/'.$name.'/'.$dir.'/';
        $filename = $log_dir.date('Ymd').'.log';
        !is_dir($log_dir) && mkdir($log_dir, 0777, true);
        $microtime = microtime(true);
        $server = array(
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
            'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'REDIRECT_STATUS' => $_SERVER['REDIRECT_STATUS'],
            'SERVER_NAME' => $_SERVER['SERVER_NAME'],
            'SERVER_PORT' => $_SERVER['SERVER_PORT'],
            'SERVER_ADDR' => $_SERVER['SERVER_ADDR'],
            'REMOTE_PORT' => $_SERVER['REMOTE_PORT'],
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
            'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'],
            'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'],
            'REQUEST_TIME_FLOAT' => $_SERVER['REQUEST_TIME_FLOAT'],
            'HTTP_UTOKEN' => $_SERVER['HTTP_UTOKEN']
        );
        $get_str = !empty($_GET) ? $this->_log_str($_GET) : '';
        $post_str = !empty($_POST) ? $this->_log_str($_POST) : '';
        $request_str = !empty($_REQUEST) ? $this->_log_str($_REQUEST) : '';
        $files_str = !empty($_FILES) ? $this->_log_str($_FILES) : '';
        $server_str = $this->_log_str($server);
        $str = array();
        $time_mic = substr($microtime, strpos($microtime, '.')+1);
        $info = array(
            'time' => date('Y-m-d H:i:s')." ".$time_mic,
            'ip' => get_client_ip()
        );
        foreach($info_data as $k => $v){
            $info[$k] = $v;
        }
        $info_str = $this->_log_str($info);
        $str[] = "info: ".$info_str;
        $str[] = "GET: ".$get_str;
        $str[] = "POST: ".$post_str;
        $str[] = "FILES: ".$files_str;
        $str[] = "REQUEST: ".$request_str;
        $str[] = "SERVER: ". $server_str;
        foreach($action_data as $k => $v){
            $str[] = "$k: ".$v;
        }
        $str = "--------------------start--------------------\n".implode("\n--------\n", $str)."\n--------------------end--------------------\n\n";
        file_put_contents($filename, $str, FILE_APPEND);
    }
    private function _log_str($data){
        $data_str = '';
        foreach($data as $k => $v){
            $data_str .= '['.$k.':'.$v.'] ';
        }
        return $data_str;
    }
}
