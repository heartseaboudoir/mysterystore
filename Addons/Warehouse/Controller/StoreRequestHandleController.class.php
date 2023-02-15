<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-06
 * Time: 10:43
 */

namespace Addons\Warehouse\Controller;


use Admin\Controller\AddonsController;

class StoreRequestHandleController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();//检测是否已选择仓库
    }

    /***************
     * 门店发货申请处理页面
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@StoreRequestHandle/index'));
    }

}