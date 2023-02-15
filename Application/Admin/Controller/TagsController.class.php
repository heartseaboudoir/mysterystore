<?php
namespace Admin\Controller;

class TagsController extends AdminController {
    protected function _initialize(){
        parent::_initialize();
        $this->page_title = '标签';
        $this->model = M('Tags');
    }
    public function index(){
        $map = array();
        parent::_index($map, 'listorder desc, id asc');
    }

    public function save(){
        if(IS_POST){
            parent::_update();
        }else{
            parent::_save();
        }
    }
    
}
