<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-30
 * Time: 17:26
 */

namespace Addons\Order\Controller;

use Admin\Controller\AddonsController;

class StoreConsignController extends AddonsController
{

    public function __construct()
    {
        parent::__construct();
        $this->check_store();
    }

    public function report(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@StoreConsign/report'));
    }

}