<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-23
 * Time: 15:09
 */

namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;


class StockInoutOrdersController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();
    }

    /****************
     * 单据出入库流水
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@StockInoutOrders/index'));
    }

    /*****************
     * 商品出入库流水
     */
    public function index2()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@StockInoutOrders/index2'));
    }

}