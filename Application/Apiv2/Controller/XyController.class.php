<?php
// +----------------------------------------------------------------------
// | Title: <strong>内部APP</strong>
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 内部端
// +----------------------------------------------------------------------

namespace Apiv2\Controller;

use Apiv2\Extend\JgPush;

class XyController extends ApiController {
    
    private $uid = 0;
    private $group_id = 0;
    
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * @name  login
     * @title 登录接口
     * @param string $username 用户名
     * @param string $password 密码 (用md5加密)
     * @param string $stime    当前时间戳（精确到秒） 必传
     * @param string $ckey     用户名登录成功后获得
     * @return [uid] => 用户ID<br>
                [username] => 用户登录名<br>
                [nickname] => 用户姓名<br>
                [group_id] => 管理员类型（1：超级管理员 2 门店管理员）<br>
                [group_text] => 管理员类型文本<br>
                [ckey] => 访问内部APP其他接口时所需要的值
     * @remark <br><br> 只允许超级管理员或门店管理员登录 <br><br>  ckey每次登录后会重新生成
     */
    public function login() {
        $this->_check_param();
        $status = 0;
        $msg = "";
        $data = array();
        $username = I('username');
        $password = I('password');
        $ckey = I('ckey');
        $stime = I('stime');
        
        if($ckey){
            $users_ckey = S('InternalUser');
            if(isset($users_ckey['token'][$ckey])){
                $user_ckey_info = $users_ckey['token'][$ckey];
                if($users_ckey['access'][$user_ckey_info['uid']] != $ckey){
                    $this->return_data(0, '', '登录失败');
                }
                $username = $user_ckey_info['username'];
                $password = $user_ckey_info['password'];
            }else{
                $this->return_data(0, '', '登录失败');
            }
        }elseif(empty($username) || empty($password) ) { 
            $this->return_data(0, '', "用户名和密码不能为空");
        }else{
            // 
            $password = $this->_set_password($password);    
        }
        
        /* 调用UC登录接口登录 */
        $req = \User\Client\Api::execute('User', 'login', array('username' => $username, 'password' => $password, 'type' => 1));
        if($req['status'] != 1){
            $this->return_data(0);
        }else{
            $uid = $req['data'];
        }
        if (0 < $uid) { //UC登录成功
            // 验证是否超级管理员或门店管理员
            $prv = M('AuthGroupAccess')->where(array('uid' => $uid, 'group_id' => array('in', '1,2')))->order('group_id asc')->find();
            if(!$prv){
                $this->return_data(0, '', '访问权限不足');
            }
            /* 登录用户 */
            $Member = D('Member');
            $info = $Member->where(array('uid' => $uid))->find();
            if ($Member->login($uid)) { //登录用户
                $status = 1;
                $msg = "登录成功！";

                $group_text = '';
                switch($prv['group_id']){
                    case 1:
                        $group_text = '超级管理员';
                        break;
                    case 2:
                        $group_text = '门店管理员';
                        break;
                }
                $ckey = $this->get_token($uid, $username, $password, $prv['group_id'], $stime, $ckey);
                $data = array(
                    'uid' => $uid,
                    'username' => $username,
                    'nickname' => $info['nickname'],
                    'group' => $prv['group_id'],
                    'group_text' => $group_text,
                    'ckey' => $ckey,
                );
                $this->return_data($status, $data, $msg);
            } else {
                $msg = $Member->getError();
            }
        } else { //登录失败
            switch ($uid) {
                case -1: $error = '管理员不存在或被禁用！';
                    break; //系统级别禁用
                case -2: $error = '密码错误！';
                    break;
                default: $error = '未知错误！';
                    break; // 0-接口参数错误
            }
            $msg = $error;
            $this->return_data(0, '', $msg);
        }
    }
    /**
     * @name  get_store_lists
     * @title 获取当前管理员可管理的门店
     * @param int       $page       分页数（默认为1）
     * @param string    $ckey       登录后返回的ckey
     * @param string    $ctime      当前时间戳
     * @return 
                [id]=> 门店ID<br>
                [title]=> 门店名<br>
                [address]=> 门店地址<br>
                [logo]=> logo地址 <br>
     * @remark 接口附加返回参数<br><br>
                [row] => 每次获取的条数<br>
                [page] => 当前页码<br>
                [count] => 当前返回的数据条数<br>
                [total] => 总的数据条数<br><br>
     */
    public function get_store_lists(){
        $this->check_token();
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        // 获取当前可管理的门店， 超级管理员为全部门店
        $where = array();
        if($this->group_id == 2){
            $my_store_access = M('MemberStore')->where(array('uid' => $this->uid, 'group_id' => 2))->field('store_id')->select();
            foreach($my_store_access as $v){
                $stores[] = $v['store_id'];
            }
            $where['id'] = array('in', $stores);
        }
        $where['status'] = 1;
        $my_store = M('Store')->where($where)->field('id,title,address')->page($page, $row)->select();
        !$my_store && $my_store = array();
        foreach($my_store as $k => $v){
            $v['logo'] = '';
            $my_store[$k] = $v;
        }
        $total = M('Store')->where($where)->count();
        
        $version = C('IOS_IN_VERSION_NO');
        if (empty($version)) {
            $version = '';
        }
        $this->return_data(1, $my_store, '', array('row' => (int)$row, 'page' => (int)$page, 'count' => count($my_store), 'total' => (int)$total, 'version' => $version));
    }
    
    private function get_token( $uid, $username, $password, $group){
        $data = S('InternalUser');
        $key = md5(md5($username.'Internal'.$password).NOW_TIME);
        
        $old = isset($data['access'][$uid]) ? $data['access'][$uid] : '';
        $data['access'][$uid] = $key;
        $data['token'][$key] = array(
            'uid' => $uid,
            'username' => $username,
            'password' => $password,
            'group_id' => $group,
            'time' => NOW_TIME
        );
        if($old && isset($data['token'][$old])) unset($data['token'][$old]);
        
        S('InternalUser', $data);
        return $key;
    }
    private function check_token(){
        $_ckey = I('ckey');
        $ctime = I('ctime');
        if(!$_ckey){
            $this->return_data(0, '', 'ckey值不存在');
        }
        
        $data = S('InternalUser');
        
        if(isset($data['token'][$_ckey])){
            $uinfo = $data['token'][$_ckey];
            // 双重验证，防止多端登录
            if($data['access'][$uinfo['uid']] != $_ckey){
                $this->return_data(0, '', 'ckey与用户不匹配');
            }
            $this->uid = $uinfo['uid'];
            $this->group_id = $uinfo['group_id'];
        }else{
            $this->return_data(0, '', 'ckey非法');
        }
    }
    private function _set_password($password){
        if(strlen($password) != 32){
            $this->return_data(0, '', '密码非法');
        }
        return '^md5'.$password.'md5$';
    }
    /**
     * @name  goods_detail
     * @title 获取商品详细
     * @param int       $id       商品条形码ID
     * @param int       $store_id 门店ID
     * @param string    $ckey      登录后返回的ckey
     * @param string    $ctime      当前时间戳
     * @return [id] => 商品ID<br>
                [title] => 商品标题<br>
                [pic_url] => 图片地址<br>
                [price] => 商品售价<br>
                [num] => 当前库存<br>
                [cate_title] => 商品分类名<br>
                [bar_code] => 条码形（数组）<br>
     * @remark 
     */
    public function goods_detail(){
        $this->check_store_pri();
        $id = I('id', '');
        $store_id = I('store_id', 0, 'intval');
        
        if(!$id){
            $this->return_data(0, '', '请选择商品');
        }
        if($store_id < 1){
            $this->return_data(0, '', '请选择门店');
        }
        // 通过条形码获取商品
        $bar = D('GoodsBarCode')->where(array('bar_code' => $id))->find();
        if(!$bar){
            $this->return_data(0, '', '商品不存在');
        }
        $id = $bar['goods_id'];
        
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $info = D('Addons://Goods/Goods')->where($where)->field('id,title,cate_id,cover_id,sell_price')->find();
        if(!$info){
            $this->return_data(0, '', '商品不存在');
        }
        $data = M('GoodsStore')->where(array('goods_id' => $id, 'store_id' => $store_id))->field('num,price')->find();
        
        $info['pic_url'] = $info['cover_id'] ? get_cover_url($info['cover_id']) : '';
        unset($info['cover_id']);
        
        $cate = M('GoodsCate')->field('title')->find($info['cate_id']);
        $info['cate_title'] = empty($cate['title']) ? '未知分类' : $cate['title'];
        unset($info['cate_id']);
        if($data){
            $info['num'] = $data['num'];
            $info['price'] = (!empty($data['price']) &&  $data['price'] != 0) ?  $data['price'] : $info['sell_price'];
        }else{
            $info['num'] = 0;
            $info['price'] = $info['sell_price'];
        }
        unset($info['sell_price']);
        $info['bar_code'] = array();
        $barcode_data = M('GoodsBarCode')->where(array('goods_id' => $id))->select();
        foreach($barcode_data as $v){
            $info['bar_code'][] = $v['bar_code'];
        }
        $this->return_data(1, $info);
    }
    
    /**
     * @name  change_goods_stock
     * @title 更新商品库存
     * @param int $store_id 门店ID
     * @param string    $ckey      登录后返回的ckey
     * @param string    $ctime      当前时间戳
     * @param string    $barcode    扫描的条形码
     * @param int $id   商品ID
     * @param int $num  操作的数量
     * @param int $total_cost  入库总成本(当操作类型为inc时，为必填项)
     * @param string $type  操作的类型(inc:增加 dec:减少 find:找回 lost:丢耗)
     * @return [num] => 操作得的商品数量<br>
     * @remark 
     */
    public function change_goods_stock(){
        $this->check_store_pri();
        $id = I('id', 0, 'intval');
        $num = I('num', 0, 'intval');
        $store_id = I('store_id', 0, 'intval');
        $barcode = I('barcode', '', 'trim');
        $type = I('type', '', 'trim');
        switch($type){
            case 'inc':
                $action = '_goods_inc';
                break;
            case 'dec':
                $action = '_goods_dec';
                break;
            case 'find':
                $action = '_goods_find';
                break;
            case 'lost':
                $action = '_goods_lost';
                break;
            default:
                $this->return_data(0, '','非法操作方式');
                break;
        }      
        if($id < 1){
            $this->return_data(0, '', '请选择商品');
        }
        if($num < 1){
            $this->return_data(0, '', '操作的数量必须大于0');
        }
        
        $this->$action($id, $num, $this->uid, $store_id, $barcode);
    }
    /**
     * @title 增加商品库存
     * @param int $id   商品ID
     * @param int $num  增加的数量
     * @return [num] => 操作得的商品数量<br>
     * @remark 
     */
    private function _goods_inc($id, $num, $uid, $store_id, $barcode = ''){
        $total_cost = I('total_cost', 0);
//        if($total_cost <= 0){
//            $this->return_data(0, '', '请录入入库总成本');
//        }
//        preg_match('/^\d{1,}\.?\d{0,2}$/', $total_cost, $match);
//        if(!$match){
//            $this->return_data(0, '入库总成本录入格式错误，应为数字且最多保留两位小数');
//        }
        // 获取商品
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $info = D('Addons://Goods/Goods')->where($where)->field('id,title,cate_id,cover_id,sell_price')->find();
        if(!$info){
            $this->return_data(0, '', '商品不存在');
        }
        if($store_id < 1){
            $this->return_data(0, '', '请选择门店');
        }
        // 添加出入库单记录
        $idata = array(
            array(
                'bar_code' => $barcode,
                'id' => $id,
                'num' => $num,
                'total_cost' => $total_cost
            )
        );
        $data = array(
            'data' => json_encode($idata),
            'store_id' => $store_id,
            'type' => 1
        );
        $Model = D('Addons://Goods/GoodsStoreApply');
        $data = $Model->create($data);
        $data['status'] = 2;
        if(!$data || !$Model->add($data)){
            $this->return_data(0, '', '操作失败');
        }
        $GSModel = D('Addons://Goods/GoodsStore');
        $GSLModel =  D('Addons://Goods/GoodsStoreLog');
        $store_data = $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->field('num')->find();
        //$store_data = $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->field('sy_cost,num')->find();
        // 为商品增加库存
        if($store_data){
//            $sy_cost = $GSModel->get_sy_cost($store_data['sy_cost'], $store_data['num'], $total_cost, $num);
            $u_data = array(
                'num' => array('exp', 'num+'.$num),
                //'sy_cost' => $sy_cost,
                'update_time' => NOW_TIME
            );
            $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->save($u_data);
            $result = $store_data['num'] + $num;
        }else{
//            $sy_cost = $GSModel->get_sy_cost(0, 0, $total_cost, $num);
            $u_data = array(
                'goods_id' => $id,
                'store_id' => $store_id,
                'num' => $num,
                //'sy_cost' => $sy_cost,
                'update_time' => NOW_TIME
            );
            $GSModel->add($u_data);
            $result = $num;
        }
        // 添加记录
        $GSLModel->add(array('cate_id' => $info['cate_id'], 'goods_id' => $id, 'store_id' => $store_id, 'num' => $num, 'type' => 1,'uid' => $uid, 'check_uid' => $uid, 'create_time' => NOW_TIME));
        $this->return_data(1, array('num' => $result));
    }
    
    /**
     * @title 找回商品库存
     * @param int $id   商品ID
     * @param int $num  增加的数量
     * @return [num] => 操作得的商品数量<br>
     * @remark 
     */
    private function _goods_find($id, $num, $uid, $store_id, $barcode = ''){
        // 获取商品
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $info = D('Addons://Goods/Goods')->where($where)->field('id,title,cate_id,cover_id,sell_price')->find();
        if(!$info){
            $this->return_data(0, '', '商品不存在');
        }
        if($store_id < 1){
            $this->return_data(0, '', '请选择门店');
        }
        // 添加出入库单记录
        $idata = array(
            array(
            'bar_code' => $barcode,
            'id' => $id,
            'num' => $num
            )
        );
        $data = array(
            'data' => json_encode($idata),
            'store_id' => $store_id,
            'type' => 3
        );
        $Model = D('Addons://Goods/GoodsStoreApply');
        $data = $Model->create($data);
        $data['status'] = 2;
        if(!$data || !$Model->add($data)){
            $this->return_data(0, '', '操作失败');
        }
        $GSModel = D('Addons://Goods/GoodsStore');
        $GSLModel =  D('Addons://Goods/GoodsStoreLog');
        $store_data = $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->field('num')->find();
        // 为商品增加库存
        if($store_data){
            $result = $store_data['num'] + $num;
            $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->setInc('num', $num);
        }else{
            $result = $num;
            $GSModel->add(array('goods_id' => $id, 'store_id' => $store_id, 'num' => $num, 'update_time' => NOW_TIME));
        }
        // 添加记录
        $GSLModel->add(array('cate_id' => $info['cate_id'], 'goods_id' => $id, 'store_id' => $store_id, 'num' => $num, 'type' => 3,'uid' => $uid, 'check_uid' => $uid, 'create_time' => NOW_TIME));
        $this->return_data(1, array('num' => $result));
    }
    /**
     * @title 减少商品库存
     * @param int $id   商品ID
     * @param int $num  减少数量
     * @return [num] => 操作得的商品数量<br>
     * @remark 
     */
    private function _goods_dec($id, $num, $uid, $store_id, $barcode = ''){        
        if($id < 1){
            $this->return_data(0, '', '请选择商品');
        }
        // 获取商品
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $info = D('Addons://Goods/Goods')->where($where)->field('id,title,cate_id,cover_id,sell_price')->find();
        if(!$info){
            $this->return_data(0, '', '商品不存在');
        }
        $GSModel = D('Addons://Goods/GoodsStore');
        $GSLModel =  D('Addons://Goods/GoodsStoreLog');
        $store_data = $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->field('num')->find();
        
        if($store_data['num'] == 0){
            $this->return_data(0, '', '商品库存为0，不可操作');
        }
        if($store_data['num'] < $num){
            $this->return_data(0, '', '商品库存剩下'.$info['num'].'，请重新提交要减少的数量');
        }
        // 添加出入库单记录
        $idata = array(
            array(
            'bar_code' => $barcode,
            'id' => $id,
            'num' => $num
            )
        );
        $data = array(
            'data' => json_encode($idata),
            'store_id' => $store_id,
            'type' => 2
        );
        $Model = D('Addons://Goods/GoodsStoreApply');
        $data = $Model->create($data);
        $data['status'] = 2;
        if(!$data || !$Model->add($data)){
            $this->return_data(0, '', '操作失败');
        }
        // 为商品减少库存
        if($store_data['num'] < $num){
            $num = $store_data['num'];
        }
        $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->setDec('num', $num);
        $result = $store_data['num'] - $num;
        // 添加记录
        $GSLModel->add(array('cate_id' => $info['cate_id'], 'goods_id' => $id, 'store_id' => $store_id, 'num' => $num, 'type' => 2,'uid' => $uid, 'check_uid' => $uid, 'create_time' => NOW_TIME));
        $this->return_data(1, array('num' => $result));
    }
    /**
     * @title 丢耗商品库存
     * @param int $id   商品ID
     * @param int $num  减少数量
     * @return [num] => 操作得的商品数量<br>
     * @remark 
     */
    private function _goods_lost($id, $num, $uid, $store_id, $barcode = ''){
        // 获取商品
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $info = D('Addons://Goods/Goods')->where($where)->field('id,title,cate_id,cover_id,sell_price')->find();
        if(!$info){
            $this->return_data(0, '', '商品不存在');
        }
        
        $GSModel = D('Addons://Goods/GoodsStore');
        $GSLModel =  D('Addons://Goods/GoodsStoreLog');
        $store_data = $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->field('num')->find();
        if($store_data['num'] == 0){
            $this->return_data(0, '', '商品库存为0，不可操作');
        }
        if($store_data['num'] < $num){
            $this->return_data(0, '', '商品库存剩下'.$info['num'].'，请重新提交要丢耗的数量');
        }
        $idata = array(
            array(
            'bar_code' => $barcode,
            'id' => $id,
            'num' => $num
            )
        );
        $data = array(
            'data' => json_encode($idata),
            'store_id' => $store_id,
            'type' => 4
        );
        // 添加出入库单记录
        $Model = D('Addons://Goods/GoodsStoreApply');
        $data = $Model->create($data);
        $data['status'] = 2;
        if(!$data || !$Model->add($data)){
            $this->return_data(0, '', '操作失败');
        }
        // 为商品减少库存
        if($store_data['num'] < $num){
            $num = $store_data['num'];
        }
        $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->setDec('num', $num);
        $result = $store_data['num'] - $num;
        // 添加记录
        $GSLModel->add(array('cate_id' => $info['cate_id'], 'goods_id' => $id, 'store_id' => $store_id, 'num' => $num, 'type' => 4,'uid' => $uid, 'check_uid' => $uid, 'create_time' => NOW_TIME));
        $this->return_data(1, array('num' => $result));
    }
    /**
     * @name  goods_inventory
     * @title 单个商品盘点
     * @param int $store_id 门店ID
     * @param string    $ckey      登录后返回的ckey
     * @param string    $ctime      当前时间戳
     * @param string    $barcode    扫描的条形码
     * @param int $id   商品ID
     * @param int $num  盘点的数量，不得小于0
     * @return [num] => 操作得的商品数量<br>
     * @remark 
     */
    public function goods_inventory(){
        $this->check_store_pri();
        $id = I('id', 0, 'intval');
        $num = I('num', 0, 'intval');
        $store_id = I('store_id', 0, 'intval');
        $barcode = I('barcode', '', 'trim');
        if($num < 0){
            $this->return_data(0, '', '数量不得小于0');
        }
        // 获取商品
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $info = D('Addons://Goods/Goods')->where($where)->field('id,title,cate_id,cover_id,sell_price')->find();
        if(!$info){
            $this->return_data(0, '', '商品不存在');
        }
        if($store_id < 1){
            $this->return_data(0, '', '请选择门店');
        }
        if(!M('GoodsBarCode')->where(array('bar_code' => $barcode, 'goods_id' => $id))->find()){
            $this->return_data(0, '', '商品条形码信息错误');
        }
        $GSModel = D('Addons://Goods/GoodsStore');
        $store_data = $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->field('num')->find();
        if(!$store_data){
            $this->return_data(0, '', '商品未入库');
        }
        if($store_data['num'] == $num){
            $this->return_data(1, array('num' => $num));
        }
        if($store_data['num'] > $num){
            $do_num = $store_data['num']-$num;
            $type = 4;
        }else{
            $do_num = $num- $store_data['num'];
            $type = 3;
        }
        // 添加出入库单记录
        $idata = array(
            array(
                'bar_code' => $barcode,
                'id' => $id,
                'num' => $do_num
            )
        );
        $data = array(
            'data' => json_encode($idata),
            'store_id' => $store_id,
            'type' => $type
        );
        $Model = D('Addons://Goods/GoodsStoreApply');
        $data = $Model->create($data);
        $data['status'] = 2;
        if(!$data || !$Model->add($data)){
            $this->return_data(0, '', '操作失败');
        }
        
        // 为商品修改库存
        $GSModel->where(array('goods_id' => $id, 'store_id' => $store_id))->save(array('num' => $num));
        // 添加记录
        $GSLModel =  D('Addons://Goods/GoodsStoreLog');
        $GSLModel->add(array('cate_id' => $info['cate_id'], 'goods_id' => $id, 'store_id' => $store_id, 'num' => $do_num, 'type' => $type,'uid' => $this->uid, 'check_uid' => $this->uid, 'create_time' => NOW_TIME));
        
        $this->return_data(1, array('num' => $num));
    }
    
    /**
     * @name  inventory 
     * @title 商品盘点
     * @param int $store_id 门店ID
     * @param string    $ckey           登录后返回的ckey
     * @param string    $ctime          当前时间戳
     * @param string    $goods_data     商品信息json数组，需要使用base64加密(格式：[{"id":1,"num":20},{"id":2,"num":40}])
     * @return 
     * @remark 当前接口需使用POST方式<br>每一个商品信息数组中，必须有id和num字段<br>若数组中一个商品出现多次，使用最后出现的商品数据。
     */
    public function inventory(){
        if(!IS_POST){
            $this->return_data(0, '', '非法操作');
        }
        $this->check_store_pri();
        $this->_check_param(array('goods_data'));
        $store_id = I('store_id', 0, 'intval');
        $year = date('Y');
        $month = date('m');
        $goods_data = I('goods_data', '', 'base64_decode');
        if(!$goods_data){
            $this->return_data(0, '', '商品信息格式出错');
        }
        $goods_data = json_decode($goods_data, true);
        
        // 商品数组格式判断
        if(!$goods_data || !is_array($goods_data)){
            $this->return_data(0, '', '商品信息格式出错');
        }
        $goods_ids = array();
        $_goods_data = array();
        foreach($goods_data as $v){
            if(empty($v['id']) || !isset($v['num'])){
                $this->return_data(0, '', '商品信息格式出错，有成员的字段出错');
            }
            $v['id'] = intval(trim($v['id']));
            $v['num'] = intval(trim($v['num']));
            if($v['id'] < 1){
                $this->return_data(0, '', '商品信息格式出错，有成员的商品ID非法');
            }
            if($v['num'] < 0){
                $this->return_data(0, '', '盘点失败，商品的数量不得小于0。');
            }
            $_goods_data[$v['id']] = $v;
            !in_array($v['id'], $goods_ids) && $goods_ids[] = $v['id'];
        }
        $goods_data = $_goods_data;
        if(!$goods_ids){
            $this->return_data(0, '', '盘点失败，未提交任何盘点信息');
        }
        $store_goods = reset_data(M('GoodsStore')->where(array('goods_id' => array('in', $goods_ids), 'store_id' => $store_id))->field('goods_id,num')->select(), 'goods_id');
        if(count($store_goods) != count($goods_ids)){
            $this->return_data(0, '', '盘点失败，部分商品不属于当前门店，请检查。');
        }
        
        $lsModel = M('GoodsInventoryLs');
        $ls_data = $lsModel->where(array('year' => $year, 'month' => $month, 'store_id' => $store_id))->find();
        if($ls_data){
            if($ls_data['status'] == 1){
                $this->return_data(0, '','系统正在进行盘点');
            }elseif($ls_data['status'] == 2){
                $this->return_data(0, '','本月盘点已结束');
            }else{
                $this->return_data(0, '','已盘点');
            }
        }
        $ls_data = array(
            'year' => $year,
            'month' => $month,
            'store_id' => $store_id,
            'create_time' => NOW_TIME,
            's_time' => NOW_TIME,
            'status' => 1
        );
        if(!$lsModel->add($ls_data)){
            $this->return_data(0, '','盘点操作失败，请稍后再试');
        }
        
        $goods_info = reset_data(M('Goods')->where(array('id' => array('in', $goods_ids)))->field('id,cate_id')->select(), 'id');
        $Model = D('Addons://Goods/GoodsInventory');
        $apply_data = array();
        foreach($goods_data as $v){
            $id = $v['id'];
            $num = $v['num'];
            if(!isset($store_goods[$id])){
                continue;
            }
            // 添加商品的盘点库存总数
            if(!$Model->where(array('year' => $year, 'month' => $month, 'goods_id' => $id, 'store_id' => $store_id))->find()){
                $idata = array(
                    'goods_id' => $id,
                    'num' => $num,
                    'year' => $year,
                    'month' => $month,
                    'store_id' => $store_id,
                    'uid' => $this->uid,
                    'create_time' => NOW_TIME,
                    'update_time' => NOW_TIME
                );
                $idata = $Model->create($idata);
                if($idata && !$Model->add($idata)){
                    continue;
                }
                $store_num = $store_goods[$id]['num'];
                $apply_num = 0;
                if($num > $store_num){
                    $apply_type = 3;
                    $apply_num = $num - $store_num;
                }elseif($num < $store_num){
                    $apply_type = 4;
                    $apply_num = $store_num - $num;
                }
                if($apply_num > 0){
                    // 更新库存
                    M('GoodsStore')->where(array('goods_id' => $id, 'store_id' => $store_id))->save(array('num' => $num, 'update_time' => NOW_TIME));
                    $apply_data[] = array('id' => $id,'num' => $apply_num, 'type' => $apply_type);
                    // 添加库存调整记录
                    D('Addons://Goods/GoodsStoreLog')->add(array('cate_id' => $goods_info[$id]['cate_id'], 'goods_id' => $id, 'store_id' => $store_id, 'num' => $apply_num, 'type' => $apply_type,'uid' => $this->uid , 'check_uid' => $this->uid , 'create_time' => NOW_TIME));
                }
            }
        }
        if($apply_data){
            $data = array(
                'data' => json_encode($apply_data),
                'store_id' => $store_id,
                'type' => 6
            );
            // 添加出入库单记录
            $ApplyModel = D('Addons://Goods/GoodsStoreApply');
            $data = $ApplyModel->create($data);
            $data['status'] = 2;
            $data && $ApplyModel->add($data);
        }
        $lsModel->where(array('year' => $year, 'month' => $month, 'status' => 1, 'store_id' => $store_id))->save(array('status' => 2, 'e_time' => NOW_TIME, 'update_time' => NOW_TIME));
        $this->return_data(1, '', '商品盘点成功');
    }
    /**
     * @name cate_list
     * @title 商品分类列表
     * @return [id] => ID<br>[pid] => 上级ID<br>[title] => 分类名<br>
     * @remark 
     */
    public function cate_list(){
        $this->check_store_pri();
        $pid = I('pid', 0, 'intval');
        $pid < 0 && $pid = 0;
        $where = array();
        $where['status'] = 1;
        $data = D('Addons://Goods/GoodsCate')->where($where)->field('id, pid, title')->order('listorder desc, create_time asc')->select();
        !$data && $data = array();
        $pre = C('DB_PREFIX');
        $_data = array();
        foreach($data as $v){
            // 筛选去除分类无数据的选项
            $where = array();
            $where['cate_id'] = $v['id'];
            $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods_store.store_id = {$this->store_id}";
            $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
            if(D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id")->find()){
                $_data[] = $v;
            }
        }
        $this->return_data(1, $_data);
    }
    /**
     * @name goods_list
     * @title 商品列表(全部)
     * @return [id] => 商品ID<br>
                [title] => 商品标题<br>
                [pic_url] => 图片地址<br>
                [price] => 商品售价<br>
                [num] => 当前库存<br>
                [cate_title] => 商品分类名<br>
                [bar_code] => 条码形（数组）<br>
     * @remark 
     */
    public function goods_list(){
        $this->check_store_pri();
        $pre = C('DB_PREFIX');
        $where = array();
        $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods_store.store_id = {$this->store_id}";
        $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
        
        $data = D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id, title, cate_id, sell_price, cover_id, {$pre}goods_store.num, {$pre}goods_store.price")->order('listorder desc, create_time desc')->select();
        !$data && $data = array();
        $goods_ids = array();
        foreach($data as $k => $v){
            $v['pic_url'] = $v['cover_id'] ? get_cover_url($v['cover_id']) : '';
            unset($v['cover_id']);
            (!$v['price'] || $v['price'] <= 0) && $v['price'] = $v['sell_price'];
            unset($v['sell_price']);
            $goods_ids[] = $v['id'];
            $cate_ids[] = $v['cate_id'];
            $data[$k] = $v;
        }
        if($goods_ids){
            $barcode_data = M('GoodsBarCode')->where(array('goods_id' => array('in', $goods_ids)))->select();
            $goosd_barcode = array();
            foreach($barcode_data as $v){
                $goosd_barcode[$v['goods_id']][] = $v['bar_code'];
            }
            
            $cate_list = M('GoodsCate')->field('id, title')->where(array('id' => array('in', $cate_ids)))->select();
            if($cate_list){
                foreach($cate_list as $v){
                    $_cate[$v['id']] = $v;
                }
            }
        }
        foreach($data as $k => $v){
            $v['bar_code'] = !empty($goosd_barcode[$v['id']]) ? $goosd_barcode[$v['id']] : array();
            $v['cate_title'] = empty($_cate[$v['cate_id']]['title']) ? '未知分类' : $_cate[$v['cate_id']]['title'];
            unset($v['cate_id']);
            $data[$k] = $v;
        }
        $this->return_data(1, $data);
    }
    // 检查管理员门店权限
    private function check_store_pri(){
        $this->check_token();
        $store_id = I('store_id', 0, 'intval');
        if($store_id < 1) $this->return_data(0, '', '请选择门店');
        if($this->uid < 1) $this->return_data(0, '', '请先登录');
        if($this->group_id != 1 && !M('MemberStore')->where(array('uid' => $this->uid, 'store_id' => $store_id))->find()){
            $this->return_data(0, '', '门店管理权限不足');
        }
        $this->store_id = $store_id;
        !defined('UID') && define('UID', $this->uid);
    }
    
    // 同步商品
    public function push_update(){
        
        $this->check_token();
        
        // 处理类型
        $code = I('code', '', 'trim');
        
        if (empty($code)) {
            $code = 'all';
        }
        
        if (!in_array($code, array('all', 'goods_by_cid'))) {
            $this->return_data(0, '', '参数非法: code');
        }
        
        // 处理商品分类ID集合
        $ids = I('ids', 0);
        
        if (empty($ids)) {
            $ids = array();
        }
        
        if ($code == 'goods_by_cid' && empty($ids)) {
            $this->return_data(0, '', '参数非法: ids');
        }
        
        $store_id = I('store_id', 0);
        
        if (empty($store_id)) {
            $this->return_data(0, '', '参数非法: store_id');
        }
        
        if ($store_id == 1) {
            $result = JgPush::pushToApp(0, 1, '门店更新', '说明：门店更新', '{"name": "value"}');
            $this->return_data(1, array('msg' => '请求成功'));
        }
        
        
        D('Addons://Goods/GoodsStore')->push_update('all', $ids, $store_id, true);
        
        $this->return_data(1, array('msg' => '请求成功'));

    }


    public function tokens()
    {
        $users_ckey = S('InternalUser');
        $this->return_data(1, array('data' => C('IOS_IN_VERSION_NO')));
        //$this->return_data(1, array('data' => $users_ckey));
    }
    
    
    public function test_push()
    {
        $this->check_token();
        
        
        
        $store_id = I('store_id', 0);
        
        if (empty($store_id)) {
            $this->return_data(0, '', '参数非法: store_id');
        }
        
        
        // $result = JgPush::vvv();       
        
        $result = JgPush::pushToApp('store' . '_' . $store_id, 1, '门店更新', '说明：门店更新', '{"name": "value"}');
        
        $this->return_data(1, array('test' => $result));
    }
    
    
    public function test_tpl()
    {
        
        //oauth_user
        //ohEQbxBavUfzG5Y4JKUsIyaOoNxg
        $weixin = A("Addons://Wechat/Wechatclass");
        //$userinfo = $weixin->oauth_user('userinfo');        
        
        //print_r($userinfo);
        
        $openid = 'ohEQbxBavUfzG5Y4JKUsIyaOoNxg';
        $template_id = 'vIArHlZ0RgdByDEeutWAPzp5ojoS9BBX4H0WDSH6TO8';
        $click_url = 'https://www.baidu.com';
        
        
        $data = array(
            'first' => array(
                'value' => '欢迎光临神秘商店，您已支付完成，祝您购物愉快！',
                'color' => '#000000',				
            ),
            'orderMoneySum' => array(
                'value' => '125.00元',
                'color' => '#000000',
            ),
            'orderProductName' => array(
                'value' => '神秘商店之神秘商品',
                'color' => '#000000',
            ),
            'Remark' => array(
                'value' => "如有问题请致电xxx，神秘商店将第一时间为您服务！",
                'color' => '#000000',
            ),
        );
        
        
        
        $weixin = A("Addons://Wechat/Wechatclass");
        $result = $weixin->tpl_msg($openid, $template_id, $data, $click_url, '#000000');
        
        print_r($result);
    }
    
    public function clear_wallet_config()
    {
        $key = 'WALLET_CONFIG';
        $data = S($key, null);
        var_dump($data);
    }    
    
    // 发送模板消息
    private function push_tpl_msg()
    {
        
    }
    
    public function test_msg()
    {
        $result = send_sms_test('13751730010', 'SMS_39370204', array('code' => '123465', 'product' => '神秘商店'));
        
        print_r($result);
    }



    // 微信信息
    public function wechat_info()
    {
        
        $data = S('USER_LOTTERY_MOBILE');
        
        $i = 0;
        // 遍历微信数据
        foreach ($data['wechat'] as $key => $val) {
            echo $i . 'wechat=> ' . $key;echo "\r\n";
            if (strlen($key) < 3 || strlen($val) < 3) {
                continue;
            }
            
            // 查找是否有数据
            $one = M('uinfo')->where(array(
                'mobile' => $val,
            ))->find();
            
            
            
            // 没有数据
            if (empty($one)) {
                M('uinfo')->add(array(
                    'mobile' => $val,
                    'wechat' => $key,
                ));
            
            // 有数据，但微信数据为空
            } else if(emptY($one['wechat'])) {
                M('uinfo')->where(array(
                    'mobile' => $val,
                ))->save(array(
                    'wechat' => $key,
                ));
            } 
            $i++;
        }
        
        
        $j = 0;
        // 遍历支付宝数据
        foreach ($data['alipay'] as $key => $val) {
            echo $j . 'alipay=> ' . $key;echo "\r\n";
            if (strlen($key) < 3 || strlen($val) < 3) {
                continue;
            }
            
            // 查找是否有数据
            $one = M('uinfo')->where(array(
                'mobile' => $val,
            ))->find();
            
            
            
            // 没有数据
            if (empty($one)) {
                M('uinfo')->add(array(
                    'mobile' => $val,
                    'alipay' => $key,
                ));
            
            // 有数据，但微信数据为空
            } else if(emptY($one['alipay'])) {
                M('uinfo')->where(array(
                    'mobile' => $val,
                ))->save(array(
                    'alipay' => $key,
                ));
            } 
            $j++;
        }        
        //echo json_encode($mobile_data);
        
        
    }
    
    
    // 支付宝信息
    public function alipay_info()
    {
        $alipay = session('user_alipay');
        
        print_r($alipay);
        
        
    }    
    
    

    
}
