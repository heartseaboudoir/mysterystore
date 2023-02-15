<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-21
 * Time: 17:41
 * 寄售模块
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreConsignController extends AddonsController{

    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    public function temp(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/temp'));
    }

    public function index(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/index'));
    }

    public function view(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/view'));
    }

    public function outtemp(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/outtemp'));
    }

    public function outindex(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/outindex'));
    }

    public function outview(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/outview'));
    }

    public function report(){
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreConsign/report'));
    }

}