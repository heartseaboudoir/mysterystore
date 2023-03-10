<?php
namespace Addons\Report\Controller;

use Admin\Controller\AddonsController;

class StoreJieCunAdminController extends AddonsController{
    //http://localhost/Admin/Addons/ex_Report/_addons/Report/_controller/StoreJieCunAdmin/_action/index.html
    public function __construct() {
        parent::__construct();
        $this->check_store();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $pre = C('DB_PREFIX');
        $jiecun_db = "{$pre}store_jiecun";
        $where['_string'] = '';
        $_string = array();
        if($start_time){
            $_start_time = strtotime($start_time);
            $_string[] = "{$jiecun_db}.add_time >= {$_start_time}";
        }elseif($end_time){
            $_end_time = strtotime($end_time)+3600*24;
            $_string[] = "{$jiecun_db}.add_time <= {$_end_time}";
        }
        if($start_time && $end_time){
            $_start_time = strtotime($start_time);
            $_end_time = strtotime($end_time)+3600*24;
            $_string[] = "{$jiecun_db}.add_time >= {$_start_time} and {$jiecun_db}.add_time <= {$_end_time}";
        }
        if(is_array($_string) && count($_string)>0) {
            $where['_string'] = '1=1 and ' . implode(' and ', $_string);
        }else{
            $where['_string'] = '1=1';
        }
        //$field = '*';FROM_UNIXTIME(1500109248, '%Y-%m-%d %H:%i:%S')
        $field = "{$jiecun_db}.jc_id,{$jiecun_db}.title,FROM_UNIXTIME({$jiecun_db}.add_time,'%Y-%m-%d %H:%i:%S') as add_time,{$jiecun_db}.type,{$jiecun_db}.jc_nums,{$jiecun_db}.jc_money,{$jiecun_db}.jc_child";


        $model = M('store_jiecun');
        $count  = $model->where($where)->count();
        $page = new \Think\Page($count, 10, $REQUEST);
        $p =$page->show();
        $list = $model->field($field)->where($where)->order('jc_id desc')->select();
        $datamain = array_slice($list,$page->firstRow,$page->listRows);

        for($i = 0;$i<count($datamain);$i++){
            $date_elements = explode("-" ,$datamain[$i]['add_time']);
            $a=$date_elements[0];
            $b=$date_elements[1];
            $yearmonthstr = $a .$b;
            $model = M('store_jiecun_pro'.$yearmonthstr);
            $field = "sum(jc_num) as sum_num,sum(jc_num*sell_price) as sum_amount";
            $nummoney = $model->field($field)->where('jc_id=' .$datamain[$i]['jc_id'])->select();
            if(is_array($nummoney) && count($nummoney)>0) {
                $datamain[$i]['jc_nums'] = $nummoney[0]['sum_num'];
                $datamain[$i]['jc_money'] = $nummoney[0]['sum_amount'];
            }
        }

        $this->assign('list', $datamain);
        $this->assign('_page', $p? $p: '');
        $this->assign('_total', $count);

        $this->meta_title = '????????????????????????';
        $this->display(T('Addons://Report@StoreJieCunAdmin/index'));
    }

    public function view(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $id = I('id');
        if($id == ""){
            $id = $_POST['id'];
        }
        $this->assign('id', $id);
        $stroe_name = I('stroe_name');
        if($stroe_name == ""){
            $stroe_name = $_POST['stroe_name'];
        }
        $this->assign('stroe_name', $stroe_name);

        $pre = C('DB_PREFIX');
        $jiecun_db = "{$pre}store_jiecun";
        $fieldmain = "{$jiecun_db}.jc_id,{$jiecun_db}.title,FROM_UNIXTIME({$jiecun_db}.add_time,'%Y-%m-%d %H:%i:%S') as add_time,{$jiecun_db}.type,{$jiecun_db}.jc_nums,{$jiecun_db}.jc_money,{$jiecun_db}.jc_child";

        $model = M('store_jiecun');
        $parentlist  = $model->where('jc_id=' .$id)->field($fieldmain)->find();

        if(is_array($parentlist) && count($parentlist)>0) {
            $thistitle = $parentlist['title'] . '>>>??????????????????????????????';
            $this->meta_title = $thistitle;
            $this->assign('plist', $parentlist);
            $childtable = $parentlist['jc_child'];
        }else{
            $this->error('??????id???');
            return false;
        }

        $where['_string'] = '';
        $_string = array();
        if($id){
            $_string[] = "{$childtable}.jc_id={$id}";
        }
        if($stroe_name){
            $_string[] = "{$pre}store.title like '%{$stroe_name}%'";
        }
        if(is_array($_string) && count($_string)>0) {
            $where['_string'] = '1=1 and ' . implode(' and ', $_string);
        }else{
            $where['_string'] = '1=1';
        }
        //$field = '*';FROM_UNIXTIME(1500109248, '%Y-%m-%d %H:%i:%S')
        $field = "{$pre}store.id,{$pre}store.title,{$childtable}.jc_id,sum({$childtable}.jc_num) as sum_num,sum({$childtable}.jc_num*{$childtable}.sell_price) as sum_amount";
        $group = "{$pre}store.id";
        $order = "{$pre}store.id";

        $mchildtable = str_replace($pre,'',$childtable);
        $model = M($mchildtable);
        $list = $model
            ->join("left join {$pre}store on {$childtable}.store_id = {$pre}store.id")
            ->where($where)->field($field)->group($group)->order($order)->select();

        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            date_default_timezone_set("PRC");
            //????????????Excel???PHPExcel???
            $starttimeout = date("Y-m-d",$parentlist['add_time']);
            $fname = '??????????????????_'.$parentlist['title'];
            ob_clean;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushStoreJieCunList1($list,$thistitle,$fname);
            //$printfile = $this->pushStoreJieCunList1($list,$thistitle,$fname);
            echo($printfile);die;
        }

        //??????
        $pcount=15;
        $count=count($list);//????????????????????????
        $Page= new \Think\Page($count,$pcount);// ?????????????????? ?????????????????????????????????????????????
        $dataout = array_slice($list,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// ?????????????????????

        $this->assign('list', $dataout);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);

        $this->display(T('Addons://Report@StoreJieCunAdmin/view'));
    }


    public function viewchild(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $id = I('id');//??????id
        if($id == ""){
            $id = $_POST['id'];
        }
        $this->assign('id', $id);
        $jc_id = I('jc_id');
        if($jc_id == ""){
            $jc_id = $_POST['jc_id'];
        }
        $this->assign('jc_id', $jc_id);
        $goods_name = I('goods_name');
        if($goods_name == ""){
            $goods_name = $_POST['goods_name'];
        }
        $this->assign('goods_name', $goods_name);
        $cat_name = I('cat_name');
        if($cat_name == ""){
            $cat_name = $_POST['cat_name'];
        }
        $this->assign('cat_name', $cat_name);

        $pre = C('DB_PREFIX');
        $jiecun_db = "{$pre}store_jiecun";
        $fieldmain = "{$jiecun_db}.jc_id,{$jiecun_db}.title,FROM_UNIXTIME({$jiecun_db}.add_time,'%Y-%m-%d %H:%i:%S') as add_time,{$jiecun_db}.type,{$jiecun_db}.jc_nums,{$jiecun_db}.jc_money,{$jiecun_db}.jc_child";

        $model = M('store_jiecun');
        $parentlist  = $model->where('jc_id=' .$jc_id)->field($fieldmain)->find();

        if(is_array($parentlist) && count($parentlist)>0) {
            $this->assign('plist', $parentlist);
            $childtable = $parentlist['jc_child'];
        }else{
            $this->error('????????????id???');
            return false;
        }

        $fieldmain1 = "*";
        $model1 = M('store');
        $parentlist1  = $model1->where('id=' .$id)->field($fieldmain1)->find();

        if(is_array($parentlist1) && count($parentlist1)>0) {
            $this->meta_title = $parentlist1['title']. '>>>' .$parentlist['title']. '>>>??????????????????';
            $this->assign('plist1', $parentlist1);
        }else{
            $this->error('????????????id???');
            return false;
        }

        $where['_string'] = '';
        $_string = array();
        $_string[] = "{$childtable}.store_id={$id}";
        $_string[] = "{$childtable}.jc_id={$jc_id}";
        if($goods_name){
            $_string[] = "{$pre}goods.title like '%{$goods_name}%'";
        }
        if($cat_name){
            $_string[] = "{$pre}goods_cate.title like '%{$cat_name}%'";
        }
        if(is_array($_string) && count($_string)>0) {
            $where['_string'] = '1=1 and ' . implode(' and ', $_string);
        }else{
            $where['_string'] = '1=1';
        }
        //$field = '*';FROM_UNIXTIME(1500109248, '%Y-%m-%d %H:%i:%S')
        $field = "{$pre}store.title as store_title,{$pre}goods.id as goods_id,{$pre}goods.title as goods_title,{$pre}goods_cate.title as goods_cat";
        $field .= ",{$childtable}.jc_num,{$childtable}.sell_price,({$childtable}.jc_num*{$childtable}.sell_price) as jc_amount";
        $order = "{$pre}goods.id";

        $mchildtable = str_replace($pre,'',$childtable);
        $model = M($mchildtable);
        $list = $model
            ->join("left join {$pre}store on {$childtable}.store_id = {$pre}store.id")
            ->join("left join {$pre}goods on {$childtable}.goods_id = {$pre}goods.id")
            ->join("left join {$pre}goods_cate on {$pre}goods.cate_id = {$pre}goods_cate.id")
            ->where($where)->field($field)->order($order)->select();

        $isprint = I('isprint');
        if($isprint == ""){
            $isprint = $_POST['isprint'];
        }
        if($isprint == 1) {
            date_default_timezone_set("PRC");
            //????????????Excel???PHPExcel???
            $title = $parentlist1['title']. '>>>' .$parentlist['title'];
            if($goods_name) {
                $title .= '>>>' .$goods_name;
            }
            if($cat_name) {
                $title .= '>>>' .$cat_name;
            }
            $title .= '>>>??????????????????';
            $fname = $parentlist1['title'] .'_??????????????????????????????_'.$parentlist['title'];
            ob_clean;
            $printmodel = new \Addons\Report\Model\ReportModel();
            $printfile = $printmodel->pushStoreJieCunList2($list,$title,$fname);
            //$printfile = $this->pushStoreJieCunList2($list,$title,$fname);
            echo($printfile);die;
        }
        //??????
        $pcount=15;
        $count=count($list);//????????????????????????
        $Page= new \Think\Page($count,$pcount);// ?????????????????? ?????????????????????????????????????????????
        $dataout = array_slice($list,$Page->firstRow,$Page->listRows);
        $show= $Page->show();// ?????????????????????

        $this->assign('list', $dataout);
        $this->assign('_page', $show? $show: '');
        $this->assign('_total', $count);

        $this->display(T('Addons://Report@StoreJieCunAdmin/viewchild'));
    }
    //????????????
    public function dosh(){
        if(IS_POST) {
            $id = $_POST['id'];
            $userid = $_SESSION['hiithink_admin']['user_auth']['uid'];
            $shname = $_POST['shname'];
            $setting_date = $_POST['province'];
            $setting_num = $_POST['city'];
            $setting_time = $_POST['setting_time'];
            $url = $_POST['url'];
            if($shname == '' || $setting_date == '' || $setting_num == '' || $setting_time == '' || $url == ''){
                $this->error('?????????????????????');
                return false;
            }
            if($id != '') {
                //????????????????????????
                $data['shname'] = $shname;
                $data['setting_date'] = $setting_date;
                $data['setting_num'] = $setting_num;
                $data['setting_time'] = $setting_time;
                $data['url'] = $url;
                $data['uid'] = $userid;
                $data['settime'] = time();
                $model = M('dosh');
                $update_it = $model->where('id=' .$id)->save($data);
            }else{
                //????????????????????????
                $data['shname'] = $shname;
                $data['setting_date'] = $setting_date;
                $data['setting_num'] = $setting_num;
                $data['setting_time'] = $setting_time;
                $data['url'] = $url;
                $data['uid'] = $userid;
                $data['settime'] = time();
                $model = M('dosh');
                $add_it = $model->add($data);
            }
            //$command = 'sh /var/spool/cron/www';
            $command = 'sh /data/www/chaoshipos/wwwroot/Public/sql_crontab.sh';
            //exec('sh var/spool/cron/www',$return);
            system($command,$return);
            if($return == 0) {
                $this->success('OK???????????????');
            }else{
                $this->success('???????????????????????????????????????'.$return);
            }
        }
        $model = M('dosh');
        $data = $model->where("shname='jiecun'")->find();
        $this->assign('data', $data);
        $this->meta_title ='????????????????????????';
        $this->display(T('Addons://Report@StoreJieCunAdmin/dosh'));
    }
    //????????????
    public function del()
    {
        $id = I('id');//??????id
        if ($id == "") {
            $id = $_POST['id'];
        }
        if ($id == "") {
            $this->error('??????ID???');
            return false;
        }
        $pre = C('DB_PREFIX');
        $jiecun_db = "{$pre}store_jiecun";
        $fieldmain = "{$jiecun_db}.jc_id,{$jiecun_db}.title,FROM_UNIXTIME({$jiecun_db}.add_time,'%Y-%m-%d %H:%i:%S') as add_time,{$jiecun_db}.type,{$jiecun_db}.jc_nums,{$jiecun_db}.jc_money,{$jiecun_db}.jc_child";

        $model = M('store_jiecun');
        $parentlist = $model->where('jc_id=' . $id)->field($fieldmain)->find();

        if (is_array($parentlist) && count($parentlist) > 0) {
            $this->assign('plist', $parentlist);
            $childtable = $parentlist['jc_child'];
        } else {
            $this->error('????????????id???');
            return false;
        }
        //????????????
        $mchildtable = str_replace($pre, '', $childtable);
        $model = M($mchildtable);
        $finddata = $model->where('jc_id=' . $id)->select();
        if (is_array($finddata) && count($finddata) > 0) {
            $model->startTrans();
            $do = $model->where('jc_id=' . $id)->delete();
            if (!$do) {
                $model->rollback();
                $this->error('????????????1???');
                return false;
            }
        }
        $model = M('store_jiecun');
        $model->startTrans();
        $do  = $model->where('jc_id=' .$id)->delete();
        if(!$do) {
            $model->rollback();
            $this->error('????????????2???');
            return false;
        }
        $this->success('???????????????');
    }
    //http://localhost/Admin/Addons/ex_Report/_addons/Report/_controller/StoreJieCunAdmin/_action/add.html
    //???????????????9?????????????????????test.imzhaike.com/JieCun/JieCun_AuthAdd/wd/setauthJieCunbyheiyd
    public function add()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        //????????????
        $model = M("store_jiecun");
        $a=date("Y");
        $b=date("m");
        $c=date("d");
        $d=date("G");
        $e=date("i");
        $f=date("s");
        $title=$a.'???'.$b.'???'.$c.'???' .$d .'???' .$e .'???' .$f .'???' .'????????????';
        //??????????????????
        $data['type'] = 0;

        $jiecunData = array(
            'title' => $title,
            'add_time' => time(),
            'type' => 1,
            'jc_nums' => 0,
            'jc_money' => 0,
            'jc_child' => "hii_store_jiecun_pro" .$a.$b
        );
        $model->startTrans();
        $jiecunAdd = $model->add($jiecunData);
        if(!$jiecunAdd) {
            $model->rollback();
            $this->error('????????????1???');
            return false;
        }
        //?????????????????????????????????????????????????????????????????????????????????????????????
        //??????????????????????????????????????????,?????????1??????

        $sql = "
            CREATE TABLE IF NOT EXISTS `hii_store_jiecun_pro" .$a.$b. "` (
              `pro_id` int(12) NOT NULL COMMENT '????????????id' AUTO_INCREMENT primary key,
              `jc_id` smallint(5) DEFAULT '0' COMMENT '?????????ID',
              `store_id` int(10) DEFAULT '0' COMMENT '??????ID',
              `goods_id` int(10) DEFAULT '0' COMMENT '??????ID',
              `jc_num` int(10) DEFAULT '0' COMMENT '????????????',
              `sell_price` decimal(9,2) NOT NULL DEFAULT '0.00' COMMENT '?????????????????????'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
        $dochildtable = D()->execute($sql);
        if(!$dochildtable) {
            $modelstore = M("store");
            $storelist = $modelstore->where('status = 1')->select();
            //??????????????????
            for ($i = 0; $i < count($storelist); $i++) {
                $store_id = $storelist[$i]['id'];
                $sql = "select A.id as goods_id,(case when ifnull(B.price,0)>0 then B.price when ifnull(B.shequ_price,0)>0 then B.shequ_price else A.sell_price end) as sell_price,ifnull(B.num,0) as jc_num from hii_goods A left join (select * from hii_goods_store where store_id = " . $store_id . ") B on A.id=B.goods_id order by A.id";
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
                    $this->error('????????????2???');
                    return false;
                }
            }
            $sql = "select sum(jc_num) as sum_num,sum(jc_num*sell_price) as sum_amount from hii_store_jiecun_pro" . $a . $b . " where jc_id=" .$jiecunAdd;
            $sumdata = D()->query($sql);
            if(is_array($sumdata) && count($sumdata)>0) {
                $sql = "update hii_store_jiecun set jc_nums = " .$sumdata[0]['sum_num'] .",jc_money = " .$sumdata[0]['sum_amount'] ." where jc_id=" .$jiecunAdd;
                $dochange = D()->execute($sql);
            }else{
                $this->error('????????????3???');
                return false;
            }
        }
        $this->success('???????????????');
    }
}
