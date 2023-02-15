<?php
namespace Addons\Store\Controller;

use Admin\Controller\AddonsController;

class WarehouseListController extends AddonsController{
    public function __construct() {
        parent::__construct();
        //$this->check_warehouse();
    }
    
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $Model = D('Addons://Store/Warehouse');
        $list = $Model->join('left join hii_shequ on hii_warehouse.shequ_id=hii_shequ.id')->select();
        $this->assign('list', $list);
        $this->meta_title = '仓库管理';
        $this->display(T('Addons://Store@WarehouseList/index'));
    }
}
