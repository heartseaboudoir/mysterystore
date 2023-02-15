<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-16
 * Time: 9:49
 */


namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreInStockController extends  AddonsController{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreInStock/index'));
    }

    public function view(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreInStock/view'));
    }

}