<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-28
 * Time: 16:59
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreOutController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    /*****************
     * 门店出库验货单
     */
    public function index()
    {
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreOut/index'));
    }

    /*****************
     * 查看
     */
    public function view()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreOut/view'));
    }

}