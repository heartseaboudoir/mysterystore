<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2017-11-14
 * Time: 17:20
 * 门店来货
 */

namespace Addons\StoreModule\Controller;


use Admin\Controller\AddonsController;

class StoreInController extends AddonsController
{
    public function __construct()
    {
        parent::__construct();
        $this->check_store();//检测是否已选择仓库
    }

    /*******************************
     * 入库验收列表
     */
    public function index()
    {
        //ini_set('max_execution_time', '0');
        //$this->goodsStoreToWarehouseInStock();
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreIn/index'));
    }

    /**************
     * 把hii_goods_store的数据做成一张入库单
     */
    function goodsStoreToWarehouseInStock()
    {
        $GoodsStoreModel = M("GoodsStore");
        $list = $GoodsStoreModel->query("SELECT goods_id,sum(num) as num,CAST( G.sell_price * 0.6  AS  DECIMAL(10,2))  as g_price
from hii_goods_store A
LEFT JOIN hii_goods G on G.id=A.goods_id
where A.status=1 and num>0 group by A.goods_id");
        $StoreInRepository = D('Addons://StoreModule/StoreIn');
        $StoreInRepository->goodsStoreToWarehouseInStock(UID, $list);
    }

    /*******************************
     * 查看
     */
    public function view()
    {
        //Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display(T('Addons://StoreModule@StoreIn/view'));
    }

}