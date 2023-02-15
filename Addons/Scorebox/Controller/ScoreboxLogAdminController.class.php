<?php
namespace Addons\Scorebox\Controller;

use Admin\Controller\AddonsController;

class ScoreboxLogAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $uid = I('uid', 0, 'intval');
        $where = array();
        $uid > 0 && $where['uid'] = $uid;
        $list = $this->lists(D('Addons://Scorebox/ScoreboxLog'), $where, 'create_time desc');
        if($list){
            $name = array();
            foreach($list as $v){
                $name[] = $v['name'];
            }
            if($name){
                $config = M('ScoreboxConfig')->where(array('name' => array('in', $name)))->field('name,title')->select();
                foreach($config as $v){
                    $_config[$v['name']] = $v;
                }
                foreach($list as $k => $v){
                    $v['name_title'] = isset($_config[$v['name']]['title']) ? $_config[$v['name']]['title'] : '-';
                    $list[$k] = $v;
                }
            }
            $this->assign('list', $list);
        }
        $this->meta_title = '蜜糖/经验记录'.( $uid > 0 ? ('【用户：'.get_nickname($uid).'】') : '');
        $this->display(T('Addons://Scorebox@Admin/ScoreboxLog/index'));
    }
}
