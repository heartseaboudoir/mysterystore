<?php
namespace Addons\Purchase\Controller;

use Admin\Controller\AddonsController;

class PurchaseReportController extends AddonsController{
    public function __construct() {
        parent::__construct();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购报表';
        $this->display(T('Addons://Purchase@PurchaseReport/index'));
    }

    public function supplyindex()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购报表每日详情';
        $this->display(T('Addons://Purchase@PurchaseReport/supplyindex'));
    }

    public function view(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '采购详情';
        $this->display(T('Addons://Purchase@PurchaseReport/view'));
    }    
}
