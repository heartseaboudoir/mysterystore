<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-02-26
 * Time: 11:52
 */

namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class GoodsInOutRecordController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        //$this->check_warehouse();
        //$this->check_store();
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Report@GoodsInOutRecord/index'));
    }

    public function stock_condition(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Report@GoodsInOutRecord/stock_condition'));
    }

    public function goods_inout_statistics(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Report@GoodsInOutRecord/goods_inout_statistics'));
    }

}