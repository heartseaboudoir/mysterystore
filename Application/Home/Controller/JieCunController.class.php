<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class JieCunController extends HomeController {

	//系统首页
    public function index(){

        $lists    = D('Goods')->limit(20)->select();

        $this->assign('lists',$lists);//列表
                 
        $this->display();
    }
    //每周一早上9点，自动结存：test.imzhaike.com/JieCun/JieCun_AuthAdd/wd/setauthJieCunbyzhaike
    public function JieCun_AuthAdd($wd='')
    {
        if($wd!='setauthJieCunbyzhaike'){
            $this->error('错误！');
        }else{
            //自动结存
            $model = M("store_jiecun");
            $a=date("Y");
            $b=date("m");
            $c=date("d");
            $d=date("G");
            $e=date("i");
            $f=date("s");
            $title=$a.'年'.$b.'月'.$c.'日' .$d .'点' .$e .'分' .$f .'秒' .'自动结存';
            //是否自动结存
            $data['type'] = 0;

            $jiecunData = array(
                'title' => $title,
                'add_time' => time(),
                'type' => 0,
                'jc_nums' => 0,
                'jc_money' => 0,
                'jc_child' => "hii_store_jiecun_pro" .$a.$b
            );
            $model->startTrans();
            $jiecunAdd = $model->add($jiecunData);
            if(!$jiecunAdd) {
                $model->rollback();
                $this->error('结存失败1！');
                return false;
            }
            //每周结存全部门店库存数据，数据量太大，不能全部保存在一个子表里
            //按照年月创建子表，做分表处理,每个月1个表

            $sql = "
            CREATE TABLE IF NOT EXISTS `hii_store_jiecun_pro" .$a.$b. "` (
              `pro_id` int(12) NOT NULL COMMENT '结存自增id' AUTO_INCREMENT primary key,
              `jc_id` smallint(5) DEFAULT '0' COMMENT '结存表ID',
              `store_id` int(10) DEFAULT '0' COMMENT '门店ID',
              `goods_id` int(10) DEFAULT '0' COMMENT '商品ID',
              `jc_num` int(10) DEFAULT '0' COMMENT '结存数量',
              `sell_price` decimal(9,2) NOT NULL DEFAULT '0.00' COMMENT '结存时的零售价'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
            $dochildtable = D()->execute($sql);
            if(!$dochildtable) {
                $modelstore = M("store");
                $storelist = $modelstore->where('status = 1')->select();
                //循环结存门店
                for ($i = 0; $i < count($storelist); $i++) {
                    $store_id = $storelist[$i]['id'];
                    $sql = "select A.id as goods_id,(case when ifnull(B.price,0)>0 then B.price else A.sell_price end) as sell_price,ifnull(B.num,0) as jc_num from hii_goods A left join (select * from hii_goods_store where store_id = " . $store_id . ") B on A.id=B.goods_id order by A.id";
                    $prolist = M()->query($sql);
                    foreach ($prolist as $key => $val) {
                        $productData[$key]['jc_id'] = $jiecunAdd;
                        $productData[$key]['store_id'] = $store_id;
                        $productData[$key]['goods_id'] = $val['goods_id'];
                        $productData[$key]['jc_num'] = $val['jc_num'];
                        $productData[$key]['sell_price'] = $val['sell_price'];
                    }

                    $model2 = M("store_jiecun_pro" . $a . $b . "");
                    $model2->startTrans();
                    $data = $model2->addAll($productData);
                    if (!$data) {
                        $model2->rollback();
                        $this->error('结存失败2！');
                        return false;
                    }
                }
                $sql = "select sum(jc_num) as sum_num,sum(jc_num*sell_price) as sum_amount from hii_store_jiecun_pro" . $a . $b . " where jc_id=" .$jiecunAdd;
                $sumdata = D()->query($sql);
                if(is_array($sumdata) && count($sumdata)>0) {
                    $sql = "update hii_store_jiecun set jc_nums = " .$sumdata[0]['sum_num'] .",jc_money = " .$sumdata[0]['sum_amount'] ." where jc_id=" .$jiecunAdd;
                    $dochange = D()->execute($sql);
                }else{
                    echo("结存失败3");
                    die;
                }
            }
            echo("结存成功");
            die;
        }
    }

}