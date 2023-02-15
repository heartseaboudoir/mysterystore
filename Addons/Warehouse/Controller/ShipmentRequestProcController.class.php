<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-17
 * Time: 11:40 发货申请处理
 */

namespace Addons\Warehouse\Controller;

use Admin\Controller\AddonsController;

class ShipmentRequestProcController extends AddonsController
{

    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();
    }

    /**********************
     * 发货申请审核处理页面
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@ShipmentRequestProc/index'));
    }

    /**********************
     * 发货申请查看
     */
    public function view()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@ShipmentRequestProc/view'));
    }

}