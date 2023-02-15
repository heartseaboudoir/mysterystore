<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-06
 * Time: 10:43
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreRequestController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    /***********
     * 门店发货申请单
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreRequest/index'));
    }

    /******************
     * 门店发货临时申请单
     */
    public function temp()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreRequest/temp'));
    }

    /****************
     * 查看申请明细
     */
    public function view()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreRequest/view'));
    }

}