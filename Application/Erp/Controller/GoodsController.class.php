<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-02
 * Time: 16:04
 */

namespace Erp\Controller;

use Think\Controller;

class GoodsController extends AdminController
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
    }

    /*****************
     * 获取商品区域价信息
     * 请求方式：GET
     * 请求参数：goods_id  商品ID  必须
     * 日期：2018-03-02
     */
    public function goods_shequ_price_info()
    {
        $goods_id = I("get.goods_id");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        $GoodsModel = M("Goods");
        $ShequModel = M("Shequ");
        $GoodsShequModel = M("GoodsShequ");

        $goods_datas = $GoodsModel->query("select G.title as goods_name,GC.title as cate_name from hii_goods G left join hii_goods_cate GC on GC.id=G.cate_id where G.id={$goods_id} limit 1 ");
        if ($this->isArrayNull($goods_datas) == null) {
            $this->response(0, "商品不存在");
        }
        $goods_shequ_price_datas = array();
       // $shequ = implode(',', $_SESSION['can_shequs']);
       
       //新增获取社区方法  zzy
        $shequ = $this->__member_store_shequ();

        if(empty($shequ)){
        	$result["goods_info"] = $goods_datas[0];
        	$result["shequ_price_info"] = array();
        	$this->response(self::CODE_OK, $result);
        }
        $shequ = implode(',', $shequ);
        $shequ_datas = $ShequModel->query("select id,title as name from hii_shequ where id in ({$shequ}) order by id ASC ");
        $ctime = time();
        $current_year = date('Y', $ctime);//当前年份
        $current_month = date('m', $ctime);//当前月份
        $effect_date = "生效日期为" . date("Y-m-d", strtotime("+1 months", strtotime("{$current_year}-{$current_month}-01")));
        foreach ($shequ_datas as $key => $val) {
            $tmp_datas = $GoodsShequModel->where(" `goods_id`={$goods_id} and `shequ_id`={$val["id"]} ")->limit(1)->select();
            if ($this->isArrayNull($tmp_datas) == null) {
                $goods_shequ_price_datas[] = array(
                    "goods_shequ_id" => "",
                    "shequ_id" => $val["id"],
                    "shequ_name" => $val["name"],
                    "price" => "",
                    "effect_date" => ""
                );
            } else {
                if ($tmp_datas[0]["status"] == 1) {
                    $goods_shequ_price_datas[] = array(
                        "goods_shequ_id" => $tmp_datas[0]["id"],
                        "shequ_id" => $val["id"],
                        "shequ_name" => $val["name"],
                        "price" => $tmp_datas[0]["price"],
                        "effect_date" => "已生效"
                    );
                } else {
                    $goods_shequ_price_datas[] = array(
                        "goods_shequ_id" => $tmp_datas[0]["id"],
                        "shequ_id" => $val["id"],
                        "shequ_name" => $val["name"],
                        "price" => $tmp_datas[0]["price"],
                        "effect_date" => $effect_date
                    );
                }
            }
        }
        $result["goods_info"] = $goods_datas[0];
        $result["shequ_price_info"] = $goods_shequ_price_datas;
        $this->response(self::CODE_OK, $result);
    }

    /*****************
     * @name获取商品售价信息
     * @params  int goods_id
     * @author Ard
     * 日期：2018-03-02
     */
    public function goods_selling_price_info()
    {
        $goods_id = I("get.goods_id");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请选择商品");
        }
        $GoodsModel = M("Goods");
        $GoodsShequModel = M("GoodsSelling");

        $goods_datas = $GoodsModel->query("select G.title as goods_name,GC.title as cate_name,G.sell_price from hii_goods G left join hii_goods_cate GC on GC.id=G.cate_id where G.id={$goods_id} limit 1 ");
        if ($this->isArrayNull($goods_datas) == null) {
            $this->response(0, "商品不存在");
        }
        $ctime = time();
        $current_year = date('Y', $ctime);//当前年份
        $current_month = date('m', $ctime);//当前月份
        $effect_date = "生效日期为" . date("Y-m-d", strtotime("+1 months", strtotime("{$current_year}-{$current_month}-01")));
        $tmp_datas = $GoodsShequModel->where(array('goods_id'=> $goods_id))->limit(1)->select();
        if ($this->isArrayNull($tmp_datas) == null || empty($tmp_datas)) {
            $goods_selling_price_datas = array(
                "goods_selling_id" => "",
                "price" => "",
                "effect_date" => ""
            );
        } else {
            if ($tmp_datas[0]["status"] == 1) {
                $goods_selling_price_datas = array(
                    "goods_selling_id" => $tmp_datas[0]["id"],
                    "price" => $tmp_datas[0]["price"],
                    "effect_date" => "已生效"
                );
            } else {
                $goods_selling_price_datas = array(
                    "goods_selling_id" => $tmp_datas[0]["id"],
                    "price" => $tmp_datas[0]["price"],
                    "effect_date" => $effect_date
                );
            }
        }
        $result["goods_info"] = $goods_datas[0];
        $result["selling_price_info"] = $goods_selling_price_datas;
        $this->response(self::CODE_OK, $result);
    }



    /*********************
     * 商品区域价保存
     * 请求方式：POST
     * 请求参数：goods_id    商品ID         必须
     *           price_array 区域价信息数组 必须  Array(Array("goods_shequ_id"=>"","shequ_id"=>"","price"=>""),Array("goods_shequ_id"=>"","shequ_id"=>"","price"=>""))
     * 日期：2018-03-02
     */
    public function goods_shequ_price_save()
    {
        $goods_id = I("post.goods_id");
        $price_array = I("post.price_array");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请提交要设置区域价的商品");
        }
        $ctime = time();
        $GoodsShequModel = M("GoodsShequ");
        foreach ($price_array as $key => $val) {
            $ok = true;
            if (!is_null($val["goods_shequ_id"]) && !empty($val["goods_shequ_id"])) {
                //修改
                $savedata["price"] = empty($val["price"]) || is_null($val["price"]) ? 0 : $val["price"];
                $savedata["ctime"] = $ctime;
                $savedata["admin_id"] = UID;
                $savedata["status"] = 0;
                $ok = $GoodsShequModel->where(" id={$val["goods_shequ_id"]} and goods_id={$goods_id} and shequ_id={$val["shequ_id"]} ")->save($savedata);
            } else {
                //新增
                $savedata["goods_id"] = $goods_id;
                $savedata["shequ_id"] = $val["shequ_id"];
                $savedata["price"] = $val["price"];
                $savedata["ctime"] = $ctime;
                $savedata["admin_id"] = UID;
                $savedata["status"] = 0;
                $ok = $GoodsShequModel->add($savedata);
            }
            if ($ok === false) {
                $this->response(0, "保存失败");
            }
        }
        $this->response(self::CODE_OK, "操作成功");
    }


    /**
     * @name:商品区域价保存
     * @params：goods_id    商品ID         必须
     *           price       价格         必须
     * 日期：2018-03-02
     */
    public function goods_selling_price_save()
    {
        $goods_id = I("post.goods_id");
        $price = I("post.price");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "请提交要设置售价的商品");
        }
        $ctime = time();
        $GoodsSellingModel = M("GoodsSelling");
        //查询是否存在
        $IsExist = $GoodsSellingModel->where(array('goods_id' => $goods_id))->find();
        if ($IsExist) {
            if(empty($price) || is_null($price)){
                $result = $GoodsSellingModel->where(array('goods_id'=>$goods_id))->delete();
            }else{
                //修改
                $savedata["price"] = $price;
                $savedata["ctime"] = $ctime;
                $savedata["admin_id"] = UID;
                $savedata["status"] = 0;
                $result = $GoodsSellingModel->where(array('goods_id'=>$goods_id))->save($savedata);
            }
        } else {
            //新增
            $savedata["goods_id"] = $goods_id;
            $savedata["price"] = $price;
            $savedata["ctime"] = $ctime;
            $savedata["admin_id"] = UID;
            $savedata["status"] = 0;
            $result = $GoodsSellingModel->add($savedata);
        }
        if ($result === false) {
            $this->response(0, "保存失败");
        }
        $this->response(self::CODE_OK, "操作成功");
    }

    /****************
     * 获取区域价历史记录
     * 请求方式：GET
     * 请求参数：shequ_id   区域ID   必须
     *           goods_id   商品ID   必须
     */
    public function get_shequ_price_history()
    {
        $shequ_id = I("get.shequ_id");
        $goods_id = I("get.goods_id");
        if (is_null($shequ_id) || empty($shequ_id)) {
            $this->response(0, "缺少区域ID");
        }
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "缺少商品ID");
        }

        $ShequPriceSnapshotModel = M("ShequPriceSnapshot");

        $sql = "select SPS.`shequ_id`,SPS.`goods_id`,SPS.`price`,SPS.`year`,SPS.`month`,ifnull(M.nickname,'') as admin_nickname
                from hii_shequ_price_snapshot SPS 
                left join hii_member M on M.uid=SPS.admin_id
                where shequ_id={$shequ_id} and goods_id={$goods_id} 
                order by id desc ";

        $data = $ShequPriceSnapshotModel->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;

        $result["data"] = $this->isArrayNull($data);

        $this->response(self::CODE_OK, $result);
    }

    /****************
     * @name 获取售价历史记录
     * @params：shequ_id   区域ID   必须
     *           goods_id   商品ID   必须
     * @author:Ard
     * @date;2018-05-02
     */
    public function get_selling_price_history()
    {
        $goods_id = I("get.goods_id");
        if (is_null($goods_id) || empty($goods_id)) {
            $this->response(0, "缺少商品ID");
        }

        $SellingPriceSnapshotModel = M("SellingPriceSnapshot");

        $sql = "select SPS.`goods_id`,SPS.`price`,SPS.`year`,SPS.`month`,ifnull(M.nickname,'') as admin_nickname
                from hii_selling_price_snapshot SPS
                left join hii_member M on M.uid=SPS.admin_id
                where goods_id={$goods_id}
                order by id desc ";

        $data = $SellingPriceSnapshotModel->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;

        $result["data"] = $this->isArrayNull($data);

        $this->response(self::CODE_OK, $result);
    }

    /*****************************
     * 同一商品同一区域出现多个区域价
     * 请求方式：GET
     * 请求参数：p           当前页          非必须，默认1
     *           pageSize    每页显示数量    非必须，默认15
     */
    public function goods_shequ_price_issue()
    {

        $sql = "
            select T.goods_id,T.shequ_id,G.title as goods_name,SQ.title as shequ_name,GC.title as cate_name from (
                  select GS.goods_id,S.shequ_id,GS.shequ_price
                  from hii_goods_store GS
                  left join hii_store S on S.id=GS.store_id
                  where GS.shequ_price is not NULL 
                  GROUP BY GS.goods_id,S.shequ_id,GS.shequ_price ) T
            left join hii_goods G on G.id=T.goods_id
            left join hii_shequ SQ on SQ.id=T.shequ_id
            left join hii_goods_cate GC on GC.id=G.cate_id
            GROUP BY T.goods_id,T.shequ_id,G.title,SQ.title,GC.title
            HAVING COUNT(*)>1
        ";

        $data = M()->query($sql);

        //分页
        $pcount = $this->getPageSize();
        $count = count($data);//得到数组元素个数
        $Page = new \Think\Page($count, $pcount);// 实例化分页类 传入总记录数和每页显示的记录数
        $data = array_slice($data, $Page->firstRow, $Page->listRows);
        $show = $Page->show();// 分页显示输出﻿

        $result["pageSize"] = $pcount;
        $result["recordCount"] = $count;
        $result["p"] = $this->getPageIndex();
        $result["pager"] = $show;
        $result["data"] = $this->isArrayNull($data);
        $this->response(self::CODE_OK, $result);
    }

    /**************************
     * 某个商品区域价列表
     * 请求方式：GET
     * 请求参数：shequ_id    区域ID   必须
     *           goods_id    商品ID   必须
     */
    public function goods_shequ_price_issue_detail()
    {
        $shequ_id = I("get.shequ_id");
        $goods_id = I("get.goods_id");
        if (empty($shequ_id)) {
            $this->response(0, "缺少区域ID");
        }
        if (empty($goods_id)) {
            $this->response(0, "缺少商品ID");
        }
        $sql = "
          select GS.goods_id,GS.shequ_price,S.title as store_name,G.title as goods_name,GC.title as cate_name,SQ.title as shequ_name
          from hii_goods_store GS
          left join hii_store S on S.id=GS.store_id
          left join hii_goods G on G.id=GS.goods_id
          left join hii_goods_cate GC on GC.id=G.cate_id
          left join hii_shequ SQ on SQ.id=S.shequ_id
          where SQ.id={$shequ_id} and GS.goods_id={$goods_id}
          order by S.id ASC 
        ";
        $list = M()->query($sql);
        $this->response(self::CODE_OK, $list);
    }

    /***************
     * 获取当前页
     ***************/
    private function getPageIndex()
    {
        $p = I("get.p");
        return is_null($p) || empty($p) ? 1 : $p;
    }

    /************************
     * 获取搜索日期
     * s_date：开始日期
     * e_date：结束日期
     *****************************/
    private function getDates()
    {
        //时间范围默认30天
        $s_date = I('s_date');
        $e_date = I('e_date');
        if ($s_date == "" && $e_date == "") {
            //搜索时间条件 默认30天
            $s_date = strtotime(date('Y-m-d', strtotime("30 days ago")));
            $e_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        } else {
            if ($s_date != "") {
                $s_date = strtotime($s_date);
            }
            if ($e_date != "") {
                $e_date = strtotime($e_date);
            }
        }
        $s_date = date('Y-m-d', $s_date);
        $e_date = date('Y-m-d', $e_date);
        return array(
            "s_date" => $s_date,
            "e_date" => $e_date
        );
    }

    /*********
     * 获取每页显示数量，默认15
     */
    private function getPageSize()
    {
        $pcount = I("get.pageSize");
        return is_null($pcount) || empty($pcount) ? 15 : $pcount;
    }

    /*********************
     * 检测数组是否空
     */
    private function isArrayNull($array)
    {
        if (!is_null($array) && !empty($array) && count($array) > 0) {
            return $array;
        } else {
            return null;
        }
    }

    private function getCanStoreIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_store_id_array = array();
        $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
        if ($store) {
            //$this->storewhere = " And store_id in (" . implode(',', array_column($store, 'id')) . ")";
            $can_store_id_array = array_column($store, "id");
        }
        return $can_store_id_array;
    }

    private function getCanWarehouseIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_warehouse_id_array = array();
        $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
        if ($warehouse) {
            //$this->warehousewhere = " And warehosue_id in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            $can_warehouse_id_array = array_column($warehouse, "w_id");
        }
        return $can_warehouse_id_array;
    }

    private function getCanSupplyIdArray()
    {
        $shequ = implode(',', $_SESSION['can_shequs']);
        $can_supply_id_array = array();
        $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
        if ($supply) {
            //$this->supplywhere = " And supply_id in (" . implode(',', array_column($warehouse, 's_id')) . ")";
            $can_supply_id_array = array_column($supply, "s_id");
        }
        return $can_supply_id_array;
    }

    /**
     * 根据商品id获取商品属性值
     */
    public function get_attr_value()
    {
        $goods_id = I('goods_id',0,'intval');

        $attr_value_array = M('AttrValue')->field('value_id,value_name')->where(array('goods_id'=>$goods_id,'status'=>array('neq',2)))->select();
        if(empty($attr_value_array)){
            $attr_value_array = array();
        }
        $this->response(self::CODE_OK, $attr_value_array);
    }

    /**
     * 获取用户操作社区的权限
     */
    public function __member_store_shequ(){
    	 // 1.用户有哪些区域的权限：从采购、仓库、门店方面查

        // 可使用的社区
        $sq_cans = array();


        $uid = intval(UID);

        // 获取不到用户信息无权限
        if (empty($uid)) {
            return $sq_cans;
        }


        // 超级管理员具备所有社区的权限
        if (IS_ROOT || in_array(1, $this->group_id)) {
            $rshequs = M('shequ')->select();

            if (!empty($rshequs)) {
                foreach ($rshequs as $key => $val) {
                    $sq_cans[] = $val['id'];
                }
            }

            return $sq_cans;

        }
        
        //采购  仓库  门店  9,13,15
		$group_id  = '';
		if(in_array(9, $this->group_id)){
			$group_id .= '9,';
		}
		if(in_array(13, $this->group_id)){
			$group_id .= '13,';
		}
		if(in_array(15, $this->group_id)){
			$group_id .= '15,';
		}
		if($group_id == ''){
			return array();
		}
		$group_id = rtrim($group_id,',');
        $shequs = array();
        //var_dump($this->group_id);exit;

        //var_dump(IS_ROOT);exit;
        //echo $uid;exit;

        // 门店社区(含采购)
        $sql_shequ_store = "select * from hii_member_store where type = 2 and group_id in({$group_id}) and uid = {$uid}";

        $data_shequ_store = M()->query($sql_shequ_store);

        if (!empty($data_shequ_store)) {
            foreach ($data_shequ_store as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                    $shequs[] = $val['shequ_id'];
                }

            }
        }


        // 门店(含采购)
        $sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where  type = 1 and group_id in({$group_id}) and uid = {$uid} group by s.shequ_id;";

        $data_shequ2_store = M()->query($sql_shequ2_store);

        if (!empty($data_shequ2_store)) {
            foreach ($data_shequ2_store as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }


        // 仓库社区
        $sql_shequ_warehouse = "select * from hii_member_warehouse where type = 2 and group_id in({$group_id}) and uid = {$uid}";

        $data_shequ_warehouse = M()->query($sql_shequ_warehouse);

        if (!empty($data_shequ_warehouse)) {
            foreach ($data_shequ_warehouse as $key => $val) {
                if (!in_array($val['warehouse_id'], $shequs)) {
                    $shequs[] = $val['warehouse_id'];
                }
            }
        }


        // 仓库
        $sql_shequ2_warehouse = "select mw.*,w.shequ_id  from hii_member_warehouse mw left join hii_warehouse w on w.w_id = mw.warehouse_id where type = 1 and group_id in({$group_id}) and uid = {$uid} group by w.shequ_id;";

        $data_shequ2_warehouse = M()->query($sql_shequ2_warehouse);
        /*
        if ($_GET['xy']) {
            print_r($data_shequ2_warehouse);
        }
        */
        if (!empty($data_shequ2_warehouse)) {
            foreach ($data_shequ2_warehouse as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }

        return $shequs;
    }
}