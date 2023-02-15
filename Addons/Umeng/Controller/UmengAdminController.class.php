<?php
namespace Addons\Umeng\Controller;

use Admin\Controller\AddonsController;
class UmengAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list = $this->lists(D('Addons://Umeng/Umeng'), $where, 'create_time asc');
        $this->assign('list', $list);
        $this->meta_title = '推送消息列表';
        $this->display(T('Addons://Umeng@Admin/Umeng/index'));
    }
    
    public function send_by_alias() {
        set_time_limit(60);
        $title = I('title', '', 'trim');
        $content = I('content', '', 'trim');
        $alias = I('alias', '', 'trim');
        $alias_type = I('alias_type', '', 'trim');
        $ticker = I('ticker', '', 'trim');
        $type = 'customizedcast';
        
        $key = C('UMENG_KEY');
        $secret = C('UMENG_SECRET');
        if(!$key || !$secret){
            $this->error('友盟推送还未配置');
        }
        $info = M('UmengSend')->where(array('type' => $type, 'alias' => $alias, 'alias_type' => $alias_type))->find();
        if($info && $info['create_time'] + 600 < time()){
            $this->success('发送成功');
        }
        
        require_once(dirname(dirname(__FILE__)).'/Lib/UmengApi.php');
        $api = new \Addons\Umeng\Lib\UmengApi($key, $secret);
        $result = $api->sendAndroidCustomizedcast($alias, $alias_type, $title, $content, $ticker);
        if($result['status'] == 1){
            M('UmengSend')->add(array('title' => $title, 'content' => $content, 'type' => $type, 'alias' => $alias, 'alias_type' => $alias_type, 'create_time' => time()));
            $this->success('发送成功');
        }else{
            $this->error('错误代码为：'.$result['code'].' '.$result['msg']);
        }
    }
    
}
