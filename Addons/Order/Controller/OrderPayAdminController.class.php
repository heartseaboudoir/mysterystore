<?php
namespace Addons\Order\Controller;

use Admin\Controller\AddonsController;

class OrderPayAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $where['store_id'] = $this->_store_id;
        $list = $this->lists(D('OrderPayLog'), $where);
        
        $this->assign('list', $list);
        $this->meta_title = '【'.session('user_store.title').'】'.' 收入记录';
        $this->display(T('Addons://Order@Admin/OrderPay/index'));
    }
}
