<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-07
 * Time: 17:08
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreStockController extends AddonsController{

    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreStock/index'));
    }

    public function indexgoods(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreStock/indexgoods'));
    }

    public function in_stock_history(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreStock/in_stock_history'));
    }

    public function out_stock_history(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreStock/out_stock_history'));
    }

}