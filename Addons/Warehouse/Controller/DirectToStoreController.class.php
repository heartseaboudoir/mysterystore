<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-01-11
 * Time: 17:10
 * 直接发货到门店申请
 */

namespace Addons\Warehouse\Controller;


use Admin\Controller\AddonsController;

class DirectToStoreController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_warehouse();//检测是否已选择仓库
    }

    /*****************************
     * 调拨临时申请单列表
     ***************************/
    public function temp()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Warehouse@DirectToStore/temp'));
    }

}