<?php

namespace Addons\WechatMenu\Controller;

use Admin\Controller\AddonsController;

class WechatMenuController extends AddonsController {

    public function __construct() {
        parent::__construct();
        A('Addons://Wechat/WechatAdmin')->set_ukey();
    }
    function get_data($list) {
        // 取一级菜单
        foreach ($list as $k => $vo) {
            if ($vo ['pid'] != 0)
                continue;

            $one_arr [$vo ['id']] = $vo;
            unset($list [$k]);
        }

        foreach ($one_arr as $p) {
            $data [] = $p;

            $two_arr = array();
            foreach ($list as $key => $l) {
                if ($l ['pid'] != $p ['id'])
                    continue;

                $l ['title'] = '├──' . $l ['title'];
                $two_arr [] = $l;
                unset($list [$key]);
            }

            $data = array_merge($data, $two_arr);
        }

        return $data;
    }

    function _deal_data($d, $ukey = '') {
        $res ['name'] = urlencode(str_replace('├──', '', $d ['title']));

        if (!empty($d ['keyword'])) {
            $res ['type'] = 'click';

            $res ['key'] = $d ['keyword'];
        } else {
            $res ['type'] = 'view';
            $res ['url'] = $this->_replace_url($d ['url'], $ukey);
        }
        return $res;
    }
    /**
     * 替换url参数
     * @param string $url  链接
     * @param string $ukey ukey
     * @return type
     */
    function _replace_url($url, $ukey){
        $url = str_replace('[ukey]', $ukey, $url);
        $config = S('WECHATADDONS_CONF_' . strtoupper($ukey));
        $url = str_replace('[config_shequurl]', $config['extend']['shequurl'], $url);
        return $url;
    }
    function json_encode_cn($data) {
        return urldecode(json_encode($data));
        return preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2', 'UTF-8', pack('H*', '$1'));", $data);
    }
public function test(){
    
        $data = array(
            array(
                'name' => '关于你',
                'url' => 'http://'
            ),
        );
        print_r($data);
        $data = json_encode($data);
        echo $data;
        echo preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2', 'UTF-8', pack('H*', '$1'));", $data);exit;
        
        $t =  mb_detect_encoding( '关于你',array('ASSCII','GB2312','UTF-8'));
        echo $t;
        echo '关于你';exit;
    $data = array(
        array(
            'name' => '关于你',
            'url' => 'http://'
        ),
    );
    $data = json_encode($data);
    echo preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2', '{$t}', pack('H*', '$1'));", $data);exit;
}
    public function index() {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $pid = I('get.pid', 0);
        $list = $this->get_data(D('Addons://WechatMenu/WechatMenu')->where(array('ukey' => $this->ukey))->order('sort asc')->select());
        $this->assign('list', $list);
        $this->assign('pid', $pid);
        $this->meta_title = '公众号菜单列表';
        $this->display(T('Addons://WechatMenu@WechatMenu/index'));
    }

    public function save() {
        $id = I('get.id', 0);
        $pid = I('get.pid', 0);
        $WechatMenu = D('Addons://WechatMenu/WechatMenu');
        if ($id) {
            $data = $WechatMenu->where(array('ukey' => $this->ukey, 'id' => $id))->find();
            if(!$data){
                $this->error('菜单不存在');
            }
            $pid = $data['pid'];
            $this->assign('data', $data);
        }
        $parent = $WechatMenu->where(array('pid' => 0, 'ukey' => $this->ukey, 'id' => array('neq', $id)))->select();
        $this->assign('parent', $parent);
        $this->assign('pid', $pid);
        $this->meta_title = ($id ? '编辑' : '添加'). '公众号菜单';
        $this->display(T('Addons://WechatMenu@WechatMenu/save'));
    }

    public function update() {
        if(empty($_POST['id'])){
            $_POST['ukey'] = $this->ukey;
        }
        $WechatMenu = D('Addons://WechatMenu/WechatMenu');
        $res = $WechatMenu->update();
        if (!$res) {
            $this->error($WechatMenu->getError());
        } else {
            $this->success(($res['id'] ? '更新成功' : '新增成功') . '，点击生成菜单按钮后生效', Cookie('__forward__'));
        }
    }

    public function delete() {
        $id = I('get.id', '');
        $where = array();
        $ids = false;
        if (I('post.ids', '')) {
            $ids = I('post.ids');
        } elseif ($id) {
            $ids = array($id);
        }
        
        if(!$ids) $this->error('请选择数据');
        $where['id'] = array('in', $ids);
        $where['ukey'] = $this->ukey;
        $WechatMenu = D('Addons://WechatMenu/WechatMenu');
        $res = $WechatMenu->where($where)->delete();
        if (!$res) {
            $error = $WechatMenu->getError();
            $this->error($error ? $error : '找不到要删除的数据');
        } else {
            $this->success('删除成功，点击生成菜单按钮后生效', Cookie('__forward__'));
        }
    }

    /*
     * 生成菜单（通过微信插件）
     */

    public function send_menu() {
        $data = D('Addons://WechatMenu/WechatMenu')->where(array('ukey' => $this->ukey))->order('sort asc')->select();
        $tree = $this->get_menu_data($data, $this->ukey);
        $_GET['ukey'] = $this->ukey;
        S('WECHATADDONS_MENU_' . $this->ukey, null);
        $result = A("Addons://Wechat/Wechatclass")->setMenu($tree);
        $this->success('更新菜单成功');
    }
    
    public function get_menu_data($data, $ukey){
        $i = 0;
        foreach ($data as $k => $d) {
            if($i==3) break;
            if ($d ['pid'] != 0)
                continue;
            $tree ['button'] [$d ['id']] = $this->_deal_data($d, $ukey);
            unset($data [$k]);
            $i++;
        }
        foreach ($data as $k => $d) {
            isset($tree ['button'] [$d ['pid']]) && count($tree ['button'] [$d ['pid']] ['sub_button']) < 5 && $tree ['button'] [$d ['pid']] ['sub_button'] [] = $this->_deal_data($d, $ukey);
            unset($data [$k]);
        }
        $tree2 = array();
        $tree2 ['button'] = array();
        foreach ($tree ['button'] as $k => $d) {
            $tree2 ['button'] [] = $d;
        }
        $tree = $this->json_encode_cn($tree2);
        return $tree;
    }
    
    
    public function listorder(){
        $id = I('get.id', 0);
        $listorder = I('get.listorder', 0);
        $Model = D('Addons://WechatMenu/WechatMenu');
        $data = array(
            'id' => $id,
            'sort' => $listorder,
        );
        $res = $Model->where(array('ukey' => $this->ukey, 'id' => $id))->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }

}
