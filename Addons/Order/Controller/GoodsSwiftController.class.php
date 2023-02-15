<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-12-30
 * Time: 17:35
 */
namespace Addons\Order\Controller;


use Admin\Controller\AddonsController;

class GoodsSwiftController extends AddonsController{

    public function __construct()
    {
        parent::__construct();
        //$this->check_store();//检测是否已选择仓库
    }

    public function index(){
        $this->check_store();//检测是否已选择仓库
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/index'));
    }

    public function ls(){
        $this->check_store();//检测是否已选择仓库
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/ls'));
    }

    public function view(){
        $this->check_store();//检测是否已选择仓库
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/view'));
    }
    /**
     * 全局结款单首页
     */
    public function index_overall_situation(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/index_overall_situation'));
    }
    /**
     * 全局结款单详情
     */
    public function ls_overall_situation(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/ls_overall_situation'));
    }
    /**
     * 全局订单首页
     */
    public function index_order_situation(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/index_order_situation'));
    }
    /**
     * 全局订单详情
     */
    public function ls_order_situation(){
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://Order@GoodsSwift/ls_order_situation'));
    }
}