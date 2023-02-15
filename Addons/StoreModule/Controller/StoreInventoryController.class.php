<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-12
 * Time: 10:09
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreInventoryController extends  AddonsController{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function temp(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreInventory/temp'));
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreInventory/index'));
    }

    public function view(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreInventory/view'));
    }

    public function monthinventory(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreInventory/monthinventory'));
    }

}