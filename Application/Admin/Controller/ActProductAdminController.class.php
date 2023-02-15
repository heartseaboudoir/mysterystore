<?php
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

/**
 * 功能：内容管理
 * 
 */
class ActProductAdminController extends AdminController {


    public function __construct() {
        parent::__construct();
    }
    
    
    
    /**
     * 查看活动配置
     */
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        

        $data = M('act_product_config')->find();
        
        $this->assign('data', $data);
        
        
        $products = empty($data['products']) ? '' : trim($data['products']);
        
        
        $product_arr = explode(',', $products);
        
        if (empty($product_arr)) {
            $select_product = array(
                'count' => 0,
                'list' => array(),
            );
        } else {
            
            $products = implode(',', $product_arr);
            $where = ' status = 1 and id in (' . $products . ')';
            
            $sql = "select id,title,cover_id from hii_goods where {$where} order by id desc limit 10;";
            
            
            $list = M()->query($sql);
            
            if (empty($list)) {
                $list = array();
            }  


            foreach($list as $k => $v){
                $v['pic_url'] = get_cover($v['cover_id'], 'path');
                $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
                $list[$k] = $v;
            }
            
            $select_product = array(
                'count' => count($list),
                'list' => $list,
            );             
            
        }
        
        
        if (empty($select_product['list'])) {
            $select_product['list'] = (object)array();
        }
        
        $select_pro = json_encode($select_product);
        $this->assign('select_pro', $select_pro);
        
       
        
        
        $this->meta_title = '活动优惠券配置';
        $this->display('index');
    }
    
    
    /**
     * 编辑活动配置
     */
    public function update()
    {


        
        $data = array();
        
        $title = trim($_POST['title']);
        if (mb_strlen($title) < 2 || mb_strlen($title) > 200) {
            $this->error('活动的标题不合法');
        } else {
            $data['title'] = $title;
        }
        
        $info = trim($_POST['info']);
        if (mb_strlen($info) < 2 || mb_strlen($info) > 400) {
            $this->error('活动的描述不合法');
        } else {
            $data['info'] = $info;
        }
        
        $cover = trim($_POST['cover']);
        if (empty($cover)) {
            $this->error('活动分享图标不合法');
        } else {
            $data['cover'] = $cover;
        }        
        
        $toppic = trim($_POST['toppic']);
        if (empty($toppic)) {
            $this->error('活动分享图标不合法');
        } else {
            $data['toppic'] = $toppic;
        } 

        
        $is_open = empty($_POST['is_open']) ? 0 : 1;
        $data['is_open'] = $is_open;
        
        $money = floatval($_POST['money']);
        if (empty($money) || $money < 0) {
            $this->error('活动金额设置不合法');
        } else {
            $data['money'] = $money;
        }
        

        
        $remark = trim($_POST['remark']);
        if (mb_strlen($remark) < 2 || mb_strlen($remark) > 500) {
            $this->error('规则说明不合法');
        } else {
            $data['remark'] = $remark;
        }
        
        
        $products = trim($_POST['products']);
        
        
        $product_arr = explode(',', $products);
        
        if (count($product_arr) < 1 || count($product_arr) > 10) {
            $this->error('绑定商品不合法');
        } else {
            $data['products'] = $products;
        }
              


        /*
        $stime = $_POST['stime'];
        $stime = strtotime($stime);
        if ($stime <= 0) {
            $this->error('活动时间设置不合法');
        } else {
            $data['stime'] = $stime;
        }
        
        $etime = $_POST['etime'];
        $etime = strtotime($etime);
        if ($etime < $stime) {
            $this->error('活动时间设置不合法');
        } else {
            $data['etime'] = $etime;
        }
        */

        
        $time = time();
        if (empty($_POST['id'])) {
            $data['create_time'] = $time;
            $data['update_time'] = $time;
        } else {
            $data['update_time'] = $time;
        }
        
        
        if ($_POST['id']) {
            $res = M('act_product_config')->where(array(
                'id' => $_POST['id'],
            ))->save($data);
        } else {
            $res = M('act_product_config')->add($data);            
        }
    
        if(empty($res)){
            $this->error('提交失败');
        }else{
            $this->success('提交成功', Cookie('__forward__'));
        }
    }


    // 生成某日活动的商品销售额
    private function getPmoney($plog = array())
    {
        
        if (empty($plog)) {
            $plog = M('act_product_log')->order('id desc')->find();
            
            if (empty($plog)) {
                return;
            }
        }
        
        
        
        
        if (empty($plog['id']) || empty($plog['act_time']) || empty($plog['products']) || $plog['pmoney'] != 0) {
            return;
        }
        
        
        //print_r($plog);exit;
        
        $stime = $plog['act_time'];
        $etime = $stime + 3600 *24;
        
        
        $sql = "select sum(od.price * od.num) as pmoney   
from hii_order_detail od 
left join hii_order o 
on o.order_sn = od.order_sn 
where od.d_id in ({$plog['products']})
and o.create_time >= {$stime} and o.create_time < {$etime}
and o.status = 5 and o.type = 'store' and o.pay_status = 2;";

        // print_r($sql);exit;
        $data = M()->query($sql);
        
        if (empty($data) || empty($data[0]['pmoney'])) {
            return;
        }
        
        M('act_product_log')->where(array(
            'id' => $plog['id'],
        ))->save(array(
            'pmoney' => $data[0]['pmoney'],
        ));
    }
    
    /**
     * 向消费第一的用户发放奖品
     */
    public function select_num()
    {
        // 获取活动ID参数
        $id = empty($_GET['id']) ? 0 : $_GET['id'];
        $id = intval($id);
        
        // 活动ID参数不合法
        if (empty($id)) {
            $this->error('参数id: 错误');
        }

        $act2_one = M('act2_10')->where(array(
            'id' => $id,
        ))->find();
        
        //print_r($act2_one);exit;
        
        //$this->error($act2_one);
        // 用户是否存在
        if (empty($act2_one) || empty($act2_one['lid']) || empty($act2_one['act_user'])) {
            $this->error('该用户不存在');
        }
        
        // 当次活动是否已发奖
        $one = M('act2_log')->where(array(
            'id' => $act2_one['lid'],
        ))->find();
        
        if (empty($one) || !empty($one['status'])) {
            $this->error('当次活动不存在或已发放奖品');
        }
        
        
        // 发放奖品
        $this->set_select_num($act2_one['id']);
        
        $this->success('处理成功');
        
        
        
        
    }
    
    /**
     * 获取当前活动前10名的消费信息
     */
    public function lists2_user()
    {
        
        // 获取活动ID参数
        $id = empty($_GET['id']) ? 0 : $_GET['id'];
        $id = intval($id);
        
        // 活动ID参数不合法
        if (empty($id)) {
            $this->error('参数id: 错误');
        }        
        
        
        
        // 查询当前活动排名
        $one_10 = M('act2_10')->where(array(
            'lid' => $id,
        ))->find();
        
        // 设置排名信息
        if (empty($one_10)) {
            $this->setNum10($id);
        }
        

        Cookie('__forward__',$_SERVER['REQUEST_URI']);




        
        $sql = "select a.act_user as uid,a.act_price as moneys,m.nickname,um.mobile,s.title as store,q.title as shequ,l.*, a.id as id_c, a.lid as lid_c, a.act_price as act_price_c, a.act_user as act_user_c, a.act_store as act_store_c, a.create_time as create_time_c 
from hii_act2_log l 
inner join hii_act2_10 a on l.id = a.lid 
left join hii_member m on a.act_user = m.uid 
left join hii_ucenter_member um on a.act_user = um.id 
left join hii_store s on a.act_store = s.id 
left join hii_shequ q on s.shequ_id = q.id 
where l.id = {$id} 
order by l.act_price desc;";

        
        // echo $sql;exit;
        
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $this->assign('list', $list);

        
        $this->meta_title = '每日活动: 每日消费前10排名';
        $this->display('lists2_user');        
    }
    



    
    
    /**
     * 活动获奖日志
     */
    public function logs2() 
    {
        // 设置门店信息
        $this->setNoStore();

        Cookie('__forward__',$_SERVER['REQUEST_URI']);


        $Model = M('act2_log');
        $count = $Model->count();
        $pcount = 15;
        $Page = new \Think\Page($count, $pcount);
        /*
        $sql = "select l.* 
        from hii_act2_log l 
        order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        */
        
        $sql = "select l.*, m.nickname, um.mobile,s.title as store,q.title as shequ 
        from hii_act2_log l 
        left join hii_member m on m.uid = l.act_user 
        left join hii_ucenter_member um on um.id = l.act_user 
        left join hii_store s on l.act_store = s.id 
        left join hii_shequ q on s.shequ_id = q.id 
        order by id desc limit {$Page->firstRow}, {$Page->listRows}";

        
        // echo $sql;exit;
        
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);

        
        $this->meta_title = '每日活动列表';
        $this->display('lists2');
    }
    
    
    /**
     * 发放奖品
     */
    private function set_select_num($id)
    {
        $data = $this->num10_request('/SmActNew/select_num', array(
            'id' => $id,
        ));
    }    
    
    /**
     * 生成前10
     */
    private function setNum10($id)
    {
        $data = $this->num10_request('/SmActOne/act_user_10', array(
            'id' => $id,
        ));
    }
    
    
    private function isTest()
    {
        //echo $_SERVER["HTTP_HOST"];
        if ($_SERVER["HTTP_HOST"] != 'v.imzhaike.com') {
            return true;
        } else {
            return false;
        }        
    }     
    
    
    private function num10_request($url, $data = array())
    {
        
        $isTest = $this->isTest();
        
        if ($isTest) {
            $domain = 'https://test.imzhaike.com/Apiv2';
        } else {
            $domain = 'https://v.imzhaike.com/Apiv2';
        }
        
        
        
        $url = $domain . $url;
        
        $device = 0;
        $version = '';
        $key = '$ZaiKe$ByApi$';
        
        $url2 = trim(strtolower($url));
        $utoken = md5($url2 . $key . date('Y-m-d'));
        
        
        
        
        //$data = array('content' => $content);
        //$json = json_encode($data, JSON_UNESCAPED_UNICODE);        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名          
        
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'UTOKEN: ' . $utoken,
                //'Content-Type: application/json',
                //'Content-Length: ' . strlen($json),
            )
        );
        $result = curl_exec($ch);
        //xydebug($result, 'world_cup.txt');
        $result = json_decode($result, true);
        return $result;
    }    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // 设置没有门店ID的用户
    private function setNoStore()
    {
        $sql = "select * from hii_act2_log where act_user != 0 and act_time != 0 and act_store = 0";
        
        $data = M()->query($sql);
        
        if (empty($data)) {
            return;
        }
        

        
        foreach ($data as $key => $var) {
            // print_r($val);
            $this->setUserStore($var['id'], $var['act_user'], $var['act_time']);
        }
        
       
        
        
    }
    
    
    
    /**
     * 设置用户最高消费门店
     */
    private function setUserStore($id, $uid, $stime)
    {
        // echo $id . '--' . $uid . '--' . $stime;
        
        if (empty($id) || empty($uid) || empty($stime)) {
            return 0;
        }
        
        $uid = $uid;
        $stime = $stime;
        $etime = $stime + 3600 * 24;
        
        // 查询最高消费的门店
        $sql = "select o.uid, o.store_id, sum(o.money) as moneys 
from hii_order o  
where o.uid = {$uid} 
and o.create_time > {$stime} and o.create_time < {$etime} 
and o.status = 5 and o.type = 'store' and o.pay_status = 2 
group by o.store_id order by moneys desc limit 0,1;";

        $store_infos = M()->query($sql);
        
        
        $store_info = $store_infos[0];
        
        // print_r($store_info);exit;
        
        if (empty($store_info) || empty($store_info['store_id'])) {
            return 0;
        } else {
            M('act2_log')->where(array(
                'id' => $id, 
                'act_user' => $uid,
            ))->save(array(
                'act_store' => $store_info['store_id'],
            ));
            return 1;
        }

    }
    
    
    /**
     * 活动获奖日志
     */
    public function logs() 
    {
        /**
        今天是否有活动并开启，有则将相关信息在当前页面展现出来
        
        
        */
        
        
        $this->getPmoney();
        
        
        
        // 活动开始
        $config = $this->getConfig();
        
        $dayAct = array(
            'day' => date('Y-m-d'),
            'money' => $config['money'], 
        );        
        
        
        // 活动不存在
        $time = time();
        if (empty($config['is_open'])) {
            $is_open = false;
        } else {
            $is_open = true;
        }       
        
        

        $this->assign('is_open', $is_open);
        
        $this->assign('day_act', $dayAct);
        
        
        

        
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        
        /*
        $where = array();
        $list   = $this->lists(M('act_product_log'), $where, 'create_time desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);        
        */








        $Model = M('act_product_log');
        $count = $Model->count();
        $pcount = 15;
        $Page = new \Think\Page($count, $pcount);
        $sql = "select l.*, g.title as product_name 
        from hii_act_product_log l
        left join hii_goods g 
        on l.act_product = g.id
        order by id desc limit {$Page->firstRow}, {$Page->listRows}";
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);





        
        
        $this->meta_title = '每日活动列表';
        $this->display('lists');
    }
    
    /**
     * 中奖用户
     */
    public function logs_user()
    {
        
        
        $id = I('get.id',0 ,'intval');
        
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        $plog = M('act_product_log')->where(array('id' => $id))->find();
        if (!empty($plog)) {
            $this->getPmoney($plog);
        } 
        
        

        $Model = M('act_product_user');
        $count = $Model->where(array('lid' => $id))->count();
        $pcount = 30;
        $Page = new \Think\Page($count, $pcount);
        $sql = "select u.* ,s.title,sq.title as shequ 
        from hii_act_product_user u 
        left join hii_order o 
        on o.order_sn = u.order_sn 
        left join hii_store s 
        on s.id = o.store_id 
        left join hii_shequ sq 
        on s.shequ_id = sq.id 
        where u.lid = {$id}
        order by o.id desc limit {$Page->firstRow}, {$Page->listRows}";
        $list = M()->query($sql);
        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);        
        
        
        
        
        $this->meta_title = '中奖用户列表';
        $this->display('logs_user');        
    }
    


    // 获取活动配置
    private function getConfig()
    {
        $data = M('act_product_config')->find();
        if (empty($data)) {
            return array(
                'is_open' => 0,
                'title' => '',
                'info' => '',
                'remark' => '',
                'money' => 0,
                'products' => '',
                'stime' => 0,
                'etime' => 0,
            );
        }
        
        $data['is_open'] = (empty($data['is_open']) ? 0 : 1);
        
        return $data;        
    }
  
  
    /**
     * 获取商品搜索信息
     */
    public function get_ol_lists(){
        
        // 搜索关键字
        $keyword = I('keyword', '', 'trim');
        
        // 分页
        $row = I('row', 0, 'intval');
        
        $page = I('page', 0, 'intval');
        
        
        if (empty($row) || $row >= 10 || $row <= 0) {
            $row = 10;
        }
        
        if (empty($page) || $page < 1) {
            $page = 1;
        }
        
        $start = ($page - 1) * $row;
        
        $limit = "{$start},{$row}";
        
        
        
        
        
        $where = ' status = 1';
        
        if (!empty($keyword)) {
            $where .= " and `title` like '%" . $keyword . "%'";
        }
        
        $sql_count = "select count(*) as c from hii_goods where {$where}";
        
        $sql = "select id,title,cover_id from hii_goods where {$where} order by id desc limit {$limit};";
        
        $res = M()->query($sql_count);
        
        if (empty($res) || empty($res[0]['c'])) {
            $count = 0;
        } else {
            $count = $res[0]['c'];
        }
        
        $list = M()->query($sql);
        
        if (empty($list)) {
            $list = array();
        }
        
        foreach($list as $k => $v){
            $v['pic_url'] = get_cover($v['cover_id'], 'path');
            $v['url'] = addons_url('Goods://GoodsAdmin:/save', array('id' => $v['id']));
            $list[$k] = $v;
        }
        
        $data = array(
            'count' => $count,
            'list' => $list,
        );
        
        if(IS_AJAX || IS_POST){
            $this->ajaxReturn(array('status' => 1, 'data' => $data));
            exit;
        }
        $this->assign('data', $data);
        $this->display('get_ol_lists');       
    }    
    

}
