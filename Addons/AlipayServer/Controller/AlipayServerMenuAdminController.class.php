<?php

namespace Addons\AlipayServer\Controller;

use Admin\Controller\AddonsController;

class AlipayServerMenuAdminController extends AddonsController {

    public function __construct() {
        parent::__construct();
    }
    function get_data($list) {
        // 取一级菜单
        foreach ($list as $k => $vo) {
            if ($vo['pid'] != 0)
                continue;

            $one_arr[$vo['id']] = $vo;
            unset($list[$k]);
        }

        foreach ($one_arr as $p) {
            $data[] = $p;

            $two_arr = array();
            foreach ($list as $key => $l) {
                if ($l['pid'] != $p['id'])
                    continue;

                $l['title'] = '├──' . $l['title'];
                $two_arr[] = $l;
                unset($list[$key]);
            }

            $data = array_merge($data, $two_arr);
        }

        return $data;
    }

    function _deal_data($d) {
        $res['name'] = urlencode(str_replace('├──', '', $d['title']));
        $res['actionType'] = $d['type'];
        $res['actionParam'] = $d['param'];
        
        return $res;
    }
    /**
     * 替换url参数
     * @param string $url  链接
     * @param string $ukey ukey
     * @return type
     */
    function _replace_url($url, $ukey){
        return $url;
    }
    function json_encode_cn($data) {
        $data = urldecode(json_encode($data));
        return $data;
    }

    public function index() {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $pid = I('get.pid', 0);
        $list = $this->get_data(D('Addons://AlipayServer/AlipayServerMenu')->order('sort asc')->select());
        $this->assign('list', $list);
        $this->assign('pid', $pid);
        $this->meta_title = '服务窗菜单列表';
        $this->display(T('Addons://AlipayServer@Admin/AlipayServerMenu/index'));
    }
    
    public function save() {
        $id = I('get.id', 0);
        $pid = I('get.pid', 0);
        $AlipayServerMenu = D('Addons://AlipayServer/AlipayServerMenu');
        if ($id) {
            $data = $AlipayServerMenu->where(array('ukey' => $this->ukey, 'id' => $id))->find();
            if(!$data){
                $this->error('菜单不存在');
            }
            $pid = $data['pid'];
            $this->assign('data', $data);
        }
        $parent = $AlipayServerMenu->where(array('pid' => 0, 'ukey' => $this->ukey, 'id' => array('neq', $id)))->select();
        $this->assign('parent', $parent);
        $this->assign('pid', $pid);
        $this->meta_title = ($id ? '编辑' : '添加'). '服务窗菜单';
        $this->display(T('Addons://AlipayServer@Admin/AlipayServerMenu/save'));
    }

    public function update() {
        $AlipayServerMenu = D('Addons://AlipayServer/AlipayServerMenu');
        $res = $AlipayServerMenu->update();
        if (!$res) {
            $this->error($AlipayServerMenu->getError());
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
        $AlipayServerMenu = D('Addons://AlipayServer/AlipayServerMenu');
        $res = $AlipayServerMenu->where($where)->delete();
        if (!$res) {
            $error = $AlipayServerMenu->getError();
            $this->error($error ? $error : '找不到要删除的数据');
        } else {
            $this->success('删除成功，点击生成菜单按钮后生效', Cookie('__forward__'));
        }
    }

    /*
     * 生成菜单
     */

    public function send_menu() {
        $data = D('Addons://AlipayServer/AlipayServerMenu')->order('sort asc')->select();
        $bizcontent = $this->get_menu_data($data, $this->ukey);
        $result = $this->update_menu($bizcontent);
        if($result['code'] == 200){
            $this->success('更新菜单成功');
        }elseif($result['code'] == 200){
            $this->error('更新菜单失败'. $result['msg']);
        }
    }
    
    
    public function add_menu(){
        define('SDK_PATH', dirname(dirname(__FILE__)).'/sdk/');
        require_once SDK_PATH.'config.php';
        require_once SDK_PATH.'aop/AopClient.php';
        require_once SDK_PATH.'AopSdk.php';
        
        $c = new \AopClient;
        $c->appId = $config['app_id'];
        $c->rsaPrivateKeyFilePath = $config['merchant_private_key_file'];
        $req = new \AlipayMobilePublicMenuAddRequest();
        
        $data = D('Addons://AlipayServer/AlipayServerMenu')->order('sort asc')->select();
        $bizcontent = $this->get_menu_data($data, $this->ukey);
        $req->setBizContent($bizcontent);
        $result = $c->execute($req);
        echo '<pre>';
        print_r($result);
    }
    
    public function update_menu($bizcontent){
        define('SDK_PATH', dirname(dirname(__FILE__)).'/sdk/');
        require_once SDK_PATH.'config.php';
        require_once SDK_PATH.'aop/AopClient.php';
        require_once SDK_PATH.'AopSdk.php';
        
        $c = new \AopClient;
        $c->appId = $config['app_id'];
        $c->rsaPrivateKeyFilePath = $config['merchant_private_key_file'];
        $req = new \AlipayMobilePublicMenuUpdateRequest();
        
        $req->setBizContent($bizcontent);
        $result = $c->execute($req);
        $result = json_decode(json_encode($result),true);
        return $result['alipay_mobile_public_menu_update_response'];
    }
    
    public function get_menu_data($data, $ukey){
        $i = 0;
        foreach ($data as $k => $d) {
            if($i==4) break;
            if ($d['pid'] != 0)
                continue;
            $tree['button'][$d['id']] = $this->_deal_data($d, $ukey);
            unset($data[$k]);
            $i++;
        }
        foreach ($data as $k => $d) {
            if(isset($tree['button'][$d['pid']])){ 
                count($tree['button'][$d['pid']]['subButton']) < 7 && $tree['button'][$d['pid']]['subButton'][] = $this->_deal_data($d, $ukey);
                unset($tree['button'][$d['pid']]['actionType'], $tree['button'][$d['pid']]['actionParam']);
            }
            unset($data[$k]);
        }
        $tree2 = array();
        $tree2['button'] = array();
        foreach ($tree['button'] as $k => $d) {
            $tree2['button'][] = $d;
        }
        $tree = $this->json_encode_cn($tree2);
        return $tree;
    }
    
    
    public function listorder(){
        $id = I('get.id', 0);
        $listorder = I('get.listorder', 0);
        $Model = D('Addons://AlipayServer/AlipayServerMenu');
        $data = array(
            'id' => $id,
            'sort' => $listorder,
        );
        $res = $Model->where(array('id' => $id))->save($data);
        if($res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }

}
