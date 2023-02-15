<?php

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StockInoutOrdersController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StockInoutOrders/index'));
    }

    public function index2()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StockInoutOrders/index2'));
    }

}