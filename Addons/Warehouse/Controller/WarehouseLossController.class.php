<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-14
 * Time: 14:08
 */

namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehouseLossController extends AddonsController{

    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();//检测是否已选择仓库
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseLoss/index'));
    }

    public function view(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseLoss/view'));
    }

    public function index2(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseLoss/index2'));
    }

    public function view2(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseLoss/view2'));
    }

}