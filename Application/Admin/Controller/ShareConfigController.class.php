<?php
namespace Admin\Controller;

/**
 * 后台配置控制器

 */
class ShareConfigController extends AdminController {

    public function index(){
        $map = array();
        $list = $this->lists('ShareConfig', $map);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        
        $this->assign('list', $list);
        $this->meta_title = '分享设置管理';
        $this->display();
    }

    public function add(){
        if(IS_POST){
            $Config = D('ShareConfig');
            $data = $Config->create();
            if($data){
                if($Config->add()){
                    $this->success('新增成功', U('index'));
                } else {
                    $this->error('新增失败');
                }
            } else {
                $this->error($Config->getError());
            }
        } else {
            $this->meta_title = '新增分享配置';
            $this->assign('info',null);
            $this->display('edit');
        }
    }

    public function edit($id = 0){
        if(IS_POST){
            $Config = D('ShareConfig');
            $data = $Config->create();
            if($data){
                if($Config->save()){
                    $this->success('更新成功', Cookie('__forward__'));
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Config->getError());
            }
        } else {
            $name = I('name');
            $info = array();
            $where = array();
            if($id){
                $where['id'] = $id;
            }elseif($name){
                $where['name'] = $name;
            }else{
                $this->error('获取信息错误');
            }
            /* 获取数据 */
            $info = M('ShareConfig')->where($where)->field(true)->find();

            if(false === $info){
                $this->error('获取信息错误');
            }
            $this->assign('data', $info);
            $this->meta_title = '编辑分享配置';
            $this->display();
        }
    }

}
