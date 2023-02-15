<?php
namespace Addons\Wechat\Controller;

use Admin\Controller\AddonsController;

class WechatAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
     public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $_GET['id'] = 1;
        $this->save();
        exit;
        $where = array();
        $list = $this->lists(D('Addons://Wechat/WechatConfig'), $where, 'update_time desc');
        $this->assign('list', $list);
        $this->meta_title = '公众号配置';
        $this->display(T('Addons://Wechat@Admin/Wechat/index'));
    }
    
    public function save() {
        $id = I('get.id','');
        if($id){
            $Config = D('Addons://Wechat/WechatConfig');
            $data = $Config->find($id);
            if(!$data){
                $this->error('公众号配置不存在');
            }
            if(!empty($data['config']['zhifu']['SSLCERT'])){
                $finfo = json_decode(think_decrypt($data['config']['zhifu']['SSLCERT']), true);
                $data['config']['zhifu']['SSLCERT_name'] = $finfo['name'];
            }
            if(!empty($data['config']['zhifu']['SSLKEY'])){
                $finfo = json_decode(think_decrypt($data['config']['zhifu']['SSLKEY']), true);
                $data['config']['zhifu']['SSLKEY_name'] = $finfo['name'];
            }
            $_GET['ukey'] = $data['ukey'];
            $_POST['_do_action'] = 1;
            $user_lists = A('Addons://Wechat/Wechatclass')->user_list();
            if($user_lists){
                $Config->where(array('id' => $id))->save(array('fensi' => $user_lists['total']));
            }
            $this->assign('data', $data);
        }
        $this->meta_title = ($id ? '编辑' : '添加'). '公众号配置';
        $this->display(T('Addons://Wechat@Admin/Wechat/save'));
    }
    
    public function update(){
        $Config = D('Addons://Wechat/WechatConfig');
        $res = $Config->update();
        if(!$res){
            $this->error($Config->getError());
        }else{
            $_POST['_do_action'] = 1;
            A('Addons://Wechat/Wechatclass')->update_cache();
            $this->success($res['id']?'更新成功':'新增成功', Cookie('__forward__'));
        }
    }
    
    public function delete(){
        $id = I('get.id','');
        if($id){
            $Config = D('Addons://Wechat/WechatConfig');
            $data = $Config->find($id);
            if(!$data){
                $this->error('找不到要删除的数据！');
            }
            $res = $Config->where("id = $id")->delete();
            if(!$res){
                $this->error('找不到要删除的数据！');
            }else{
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }
    
    public function check_config(){
        $list = $this->lists(D('Addons://Wechat/WechatConfig'), array(), 'update_time desc');
        $this->assign( 'list',     $list );
        $this->display(T('Addons://Wechat@WechatConfig/check_config'));
    }
    
    public function set_ukey(){
        $info = D('Addons://Wechat/WechatConfig')->find();
        if(!$info){
            redirect(addons_url('Wechat:/WechatAdmin:/index'));
        }
        $this->ukey = $info['ukey'];
        function get_ukey(){
            return $this->ukey;
        }
        return;
        $ukey = I('ukey');
        if(!session('user_wechat.ukey') && !$ukey){
            redirect(addons_url('Wechat://WechatConfig:/check_config'));
        }
        if(I('change') && $ukey){
            $uid = session('user_auth.uid');
            $user_wechat = array(
                'ukey' => $ukey,
                'is_admin' => C('USER_ADMINISTRATOR') == $uid ? 1 : 0,
            );
            session('user_wechat', $user_wechat);
            $_url = Cookie('__forward__') ? Cookie('__forward__') : $_SERVER['HTTP_REFERER'];
            redirect($_url);
        }
    }
}
