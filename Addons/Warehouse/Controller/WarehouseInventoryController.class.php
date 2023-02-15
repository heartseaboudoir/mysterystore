<?php
/**
 * Created by PhpStorm.
 * User: wdm
 * Date: 2017-11-15
 */

namespace Addons\Warehouse\Controller;


use Admin\Controller\AddonsController;

class WarehouseInventoryController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();//检测是否已选择仓库
    }

    /***********
     * 盘点单
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseInventory/index'));
    }

    /***********
     * 月末盘点单
     */
    public function monthlist()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseInventory/monthlist'));
    }
    /******************
     * 盘点临时申请单
     */
    public function temp()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseInventory/temp'));
    }

    /****************
     * 查看盘点单明细
     */
    public function view()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@WarehouseInventory/view'));
    }

}