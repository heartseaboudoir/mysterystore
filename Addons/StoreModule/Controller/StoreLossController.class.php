<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-15
 * Time: 15:04
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreLossController extends AddonsController
{

    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    //退货单
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreLoss/index'));
    }

    //被退货单
    public function index2()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreLoss/index2'));
    }

    public function view()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreLoss/view'));
    }

    public function view2()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreLoss/view2'));
    }

}