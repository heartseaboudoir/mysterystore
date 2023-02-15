<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-29
 * Time: 11:03
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class GoodsSwiftController extends AddonsController{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@GoodsSwift/index'));
    }

    public function ls(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@GoodsSwift/ls'));
    }

    public function view(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@GoodsSwift/view'));
    }

}