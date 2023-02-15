<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-04
 * Time: 9:52
 */
namespace Addons\StoreModule\Controller;

use Admin\Controller\AddonsController;

class StoreOutStockController extends AddonsController
{
    public
    function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    /****************
     * 门店出库单
     */
    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreOutStock/index'));
    }

    /**********
     * 查看
     */
    public function view(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreOutStock/view'));
    }

}