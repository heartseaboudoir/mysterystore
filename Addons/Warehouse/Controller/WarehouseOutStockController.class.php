<?php
namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class WarehouseOutStockController extends AddonsController{
    public function __construct() {
        parent::__construct();
        $this->check_warehouse();
    }

    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseOutStock/index'));
    }

    public function view()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseOutStock/view'));
    }
}
