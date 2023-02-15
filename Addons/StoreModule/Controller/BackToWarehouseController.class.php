<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-11
 * Time: 10:14
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class BackToWarehouseController extends AddonsController{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function temp(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@BackToWarehouse/temp'));
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@BackToWarehouse/index'));
    }

    public function view(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@BackToWarehouse/view'));
    }

}