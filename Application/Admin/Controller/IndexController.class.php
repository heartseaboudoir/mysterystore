<?php
namespace Admin\Controller;

/**
 * 后台首页控制器

 */
class IndexController extends AdminController {
    /**
     * 后台首页
    
     */
    public function index(){
        if(UID){
            /*if(array_diff($this->group_id, array(2,3,10))){
                $this->meta_title = '管理首页';
                A('Addons://Store/StoreAdmin')->my_index();
            }else{
                $this->meta_title = '管理首页';
                $this->display();
            }*/
			$this->meta_title = '管理首页';
			A('Addons://Store/StoreAdmin')->my_index();
        } else {
            $this->redirect('Public/login');
        }
    }

}
