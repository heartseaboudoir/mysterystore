<?php
// +----------------------------------------------------------------------
// | Title: 门店端
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 门店端
// +----------------------------------------------------------------------
namespace Apiv2\Controller; 

class StoreController extends ApiController {
    /**
     * @name  login
     * @title 登录接口
     * @param string $username 用户名
     * @param string $password 密码 (用md5加密)
     * @param string $pos_id   登录的设备编号
     * @param string $pos_title   登录的设备名称
     * @return [uid] => 用户ID<br>
                [username] => 用户登录名<br>
                [nickname] => 用户姓名<br>
                [pay_seconds] => 支付限时秒长<br>
                [store_title] => 门店名<br>
                [store_admin] => 门店管理员<br>
                [pos_title] =>  设备名称<br>
                [pos_tag] =>  设备标签<br>
     * @remark 管理员只能同时在一个设备上登录，若上次登录未退出，下次登录只能通过原设备登录，或通过后台退出后才可登录另外的设备。
     */
    public function login() {
        $this->_check_param(array('username', 'password', 'pos_id', 'pos_title'));
        $status = 0;
        $msg = "";
        $data = array();
        $username = I('username');
        $password = I('password');
        $pos_id = I('pos_id');
        $pos_title = I('pos_title');
        if(!$pos_id){
            $this->return_data(0, array(), '设备编号未知');
        }
        if(!$pos_title){
            $this->return_data(0, array(), '设备名称未知');
        }
        if (!empty($username)) { //登录验证
            $password = $this->_set_password($password);
            /* 调用UC登录接口登录 */
            $user = new \User\Api\UserApi();
            $uid = $user->login($username, $password);
            if (0 < $uid) { //UC登录成功
                /* 登录用户 */
                $Member = D('Member');
                $info = $Member->where(array('uid' => $uid))->find();
                if($info['pos_id'] && $info['pos_id'] != $pos_id){
                    $this->return_data(0, array(), '登录失败，已在其他设备上登录');
                }
                if($info['bind_pos'] && $info['bind_pos'] != $pos_id){
                    $this->return_data(0, array(), '登录失败，管理员已绑定其他设备');
                }
                $store = M('Store')->where(array('id' => $info['store_id'], 'status' => 1))->find();
                if(!$store){
                    $this->return_data(0, array(), '门店不存在或已关闭');
                }
                if ($Member->login($uid)) { //登录用户
                    $status = 1;
                    $msg = "登录成功！";
                    $pos_tag = 'store_'.$info['store_id'];
                    M('Member')->where(array('uid' => $uid))->save(array('pos_id' => $pos_id, 'pos_title' => $pos_title));
                    M('LoginStoreLog')->where(array('uid' => $uid, 'store_id' => $info['store_id'], 'pos_id' => $pos_id, 'out' => 0))->save(array('out' => NOW_TIME, 'remark' => '上次登录未退出'));
                    M('LoginStoreLog')->add(array('uid' => $uid, 'store_id' => $info['store_id'], 'pos_id' => $pos_id, 'in' => NOW_TIME, 'pos_title' => $pos_title));
                    
                    $config = api('Config/lists');
                    C($config);
                    
                    $data = array(
                        'uid' => $uid,
                        'username' => $username,
                        'nickname' => $info['nickname'],
                        'store_title' => $store['title'],
                        'store_admin' => get_nickname($store['admin']),
                        'pos_title' => $pos_title,
                        'pay_seconds' => C('PAY_SECONDS'),
                        'pos_tag' => $pos_tag
                    );
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
                        break; // 0-接口参数错误（调试阶段使用）
                }
                $msg = $error;
            }
        } else { //显示登录表单
            $this->return_data(0, array(), "用户名和密码不能为空");
        }
        $this->return_data($status, $data, $msg);
    }
    /**
     * @name  logout
     * @title 退出门店登录
     */
    public function logout(){
        $this->_check_token();
        if(M('Member')->where(array('uid' => $this->_uid))->save(array('pos_id' => '', 'pos_title' => ''))){
            M('LoginStoreLog')->where(array('uid' => $this->_uid, 'store_id' => $this->_store_id, 'out' => 0))->save(array('out' => NOW_TIME, 'remark' => '正常退出'));
            $this->return_data(1, array(), '成功退出登录');
        }else{
            $this->return_data(0, array(), '退出登录失败');
        }
        
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0) {
        switch ($code) {
            case -1: $error = '手机号码长度必须在16个字符以内！';
                break;
            case -2: $error = '手机号码被禁止注册！';
                break;
            case -3: $error = '手机号码被占用！请更换手机号码注册或登录！';
                break;
            case -4: $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5: $error = '邮箱格式不正确！';
                break;
            case -6: $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7: $error = '邮箱被禁止注册！';
                break;
            case -8: $error = '邮箱被占用！';
                break;
            case -9: $error = '手机格式不正确！';
                break;
            case -10: $error = '手机被禁止注册！';
                break;
            case -11: $error = '手机号被占用！';
                break;
            default: $error = '未知错误';
        }
        return $error;
    }
    private function _set_password($password){
        if(strlen($password) != 32){
            $this->return_data(0, array(), '密码非法');
        }
        return '^md5'.$password.'md5$';
    }
    /**
     * @name cate_info
     * @title 商品分类信息
     * @param int  id  分类ID
     * @return [id] => ID<br>[pid] => 上级ID<br>[title] => 分类名<br>
     * @remark 
     */
    public function cate_info(){
        $this->_check_token();
        $id = I('id', 0, 'intval');
        if($id < 1){
            $this->return_data(0, array());
        }
        $where = array();
        $where['status'] = 1;
        $where['id'] = $id;
        $data = D('Addons://Goods/GoodsCate')->where($where)->field('id, pid, title')->find();
        if(!$data){
            $this->return_data(0, array());
        }
        $pre = C('DB_PREFIX');
        // 筛选去除无库存分类
        $where = array();
        $where['cate_id'] = $id;
        $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods_store.store_id = {$this->_store_id} and {$pre}goods_store.num > 0";
        $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
        if(!D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id")->find()){
            $this->return_data(0, array());
        }
        $this->return_data(1, $data);
    }
    /**
     * @name cate_list
     * @title 商品分类列表
     * @return [id] => ID<br>[pid] => 上级ID<br>[title] => 分类名<br>
     * @remark 
     */
    public function cate_list(){
        $this->_check_token();
        $pid = I('pid', 0, 'intval');
        $pid < 0 && $pid = 0;
        $where = array();
        $where['status'] = 1;
        $data = D('Addons://Goods/GoodsCate')->where($where)->field('id, pid, title')->order('listorder desc, create_time asc')->select();
        !$data && $data = array();
        $pre = C('DB_PREFIX');
        $_data = array();
        foreach($data as $v){
            // 筛选去除无库存分类
            $where = array();
            $where['cate_id'] = $v['id'];
            $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods_store.store_id = {$this->_store_id} and {$pre}goods_store.num > 0";
            $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
            if(D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id")->find()){
                $_data[] = $v;
            }
        }
        $this->return_data(1, $_data);
    }
    /**
     * @name lists
     * @title 商品列表
     * @param   string   $ids  商品ID，默认为0，不使用此参数（多个商品ID间使用,格式，如:  12,3,22。）
     * @param   int $cate_id  分类ID(默认为全部)
     * @param   string $keyword  (关键词，可以是标题，首字母，拼音，默认为无)
     * @param   int $row 条数(最大值为500, 默认为100)
     * @param   int $offset  数据页数(默认为1，即为第1页)
     * @return [id] => ID<br>[title] => 商品名<br>[pinyin] => 商品拼音<br>[fir_letter] => 商品标题首字母<br>[num] => 库存<br>[month_num] => 本月销售数量<br>[price] => 售价<br>[unit] => 商品单位<br>[cate_id] => 分类ID<br>[pic_url] => 商品图片<br>[create_time] => 创建时间戳<br>[is_hot] => 是否热销(0 否 1 是)<br>[hot_val] => 热度值<br>[bar_code] => 条形码（数组）<br>[content] => 商品详情描述
     * @remark 
     */
    public function goods_lists(){
        $this->_check_token();
        $row = I('row', 100, 'intval');
        $row > 1000 && $row = 1000;
        $row < 100 && $row = 100;
        $page = I('offset', 0, 'intval');
        $page < 1 && $page = 1;
        $cate_id = I('cate_id', 0, 'intval');
        $ids = I('ids', 0);
        $pre = C('DB_PREFIX');
        $where = array();
        if($cate_id > 0){
            $where['cate_id'] = $cate_id;
        }elseif($cate_id == -1){
            $where['is_hot'] = 1;
        }
        $where['_string'] = "{$pre}goods.status = 1 and {$pre}goods.sell_outline = 1 and {$pre}goods_store.store_id = {$this->_store_id} and {$pre}goods_store.num > 0";
        if($ids){
            $ids = explode(',', trim($ids));
            foreach($ids as $k => $v){
                $v = intval($v);
                if($v > 0){
                    $ids[$k] = $v;
                }else{
                    unset($ids[$k]);
                }
            }
            $ids && $ids = implode(',', $ids);
            if(!$ids){
                $this->return_data(1, array(), '', array('row' => $row, 'offset' => $page, 'count' => 0, 'total' => 0));
            }
            $where['_string'] .= " and {$pre}goods.id in({$ids})"; 
        }
        $join = "{$pre}goods_store ON {$pre}goods_store.goods_id = {$pre}goods.id";
        
        $data = D('Addons://Goods/Goods')->join($join)->where($where)->field("{$pre}goods.id, title, pinyin, fir_letter, unit, is_hot, sell_price, cate_id, cover_id, {$pre}goods.content, {$pre}goods.create_time, {$pre}goods_store.num, {$pre}goods_store.month_num, {$pre}goods_store.price, {$pre}goods_store.hot_val")->order('listorder desc, create_time desc')->page($page, $row)->select();
        !$data && $data = array();
        $goods_ids = array();
        foreach($data as $k => $v){
            $v['pic_url'] = $v['cover_id'] ? get_cover_url($v['cover_id']) : '';
            unset($v['cover_id']);
            (!$v['price'] || $v['price'] <= 0) && $v['price'] = $v['sell_price'];
            unset($v['sell_price']);
            $goods_ids[] = $v['id'];
            $data[$k] = $v;
        }
        if($goods_ids){
            $log_model = M('GoodsSellLog'.$this->_store_id);
            $date = array(
                date('Y-m-d', strtotime('-1 day')), // 昨天
                date('Y-m-d', strtotime('-2 day')), // 前天
            );
            $log_data = $log_model->where(array('goods_id' => array('in', $goods_ids), 'date' => array('in', $date)))->select();
            foreach($log_data as $v){
                if($v['date'] == $date[0]){
                    $log_num1[$v['goods_id']] = $v['num'];
                }elseif($v['date'] == $date[1]){
                    $log_num2[$v['goods_id']] = $v['num'];
                }
            }
            $barcode_data = M('GoodsBarCode')->where(array('goods_id' => array('in', $goods_ids)))->select();
            $goosd_barcode = array();
            foreach($barcode_data as $v){
                $goosd_barcode[$v['goods_id']][] = $v['bar_code'];
            }
            foreach($data as $k => $v){
                $num1 = isset($log_num1[$v['id']]) ? $log_num1[$v['id']] : 0;
                $num2 = isset($log_num2[$v['id']]) ? $log_num2[$v['id']] : 0;
                $v['hot_val'] > 10 && $v['hot_val'] = 10.0;
                $v['hot_val'] < 1 && $v['hot_val'] = 1.0;
                $v['bar_code'] = !empty($goosd_barcode[$v['id']]) ? $goosd_barcode[$v['id']] : array();
                $data[$k] = $v;
            }
        }
        $total = D('Addons://Goods/Goods')->join($join)->where($where)->count();
        $this->return_data(1, $data, '', array('row' => $row, 'offset' => $page, 'count' => count($data), 'total' => (int)$total));
    }
    /**
     * @name keyword_lists
     * @title 关键词列表
     * @param   string $keyword  (关键词）
     * @return 
     * @remark 
     */
    public function keyword_lists(){
        $keyword = I('keyword', '', 'trim');
        if(!$keyword){
            $this->return_data(1, array());
        }
        $where = array();
        $where['title'] = array('like', "{$keyword}%");
        $where['pinyin'] = array('like', "{$keyword}%");
        $where['fir_letter'] = array('like', "{$keyword}%");
        $where['_logic'] = 'or';
        $lists = M('GoodsTag')->where($where)->field('title')->select();
        !$lists && $lists = array();
        $result = array();
        foreach($lists as $v){
            $result[] = $v['title'];
        }
        $this->return_data(1, $result);
    }
    /**
     * @name   add_order
     * @title  提交订单
     * @param  json  $goods  商品json数组,每个数组中的参数为：<br>[id] => 商品ID，[num] => 商品数量<br>示例:<br>[{"id":1,"num":2},{"id":3,"num":2}]
     * @return [order_sn] => 订单号<br>
                [pay_money] => 总价<br>
                [num] => 订单商品总数<br>
                [pay_status] => 支付状态： 1 未支付 2 已支付<br>
                [pay_status_text] => 支付状态文本<br>
                [create_time] => 交易时间<br>
                [status] => 状态 1 新订单 2 待确认 3 已取消 4 已完成<br>
                [status_text] => 状态文本<br>
                [pay_qrcode_url] => 支付扫码地址<br>
                <br>[goods_data] => 订单内容<br><br>
                -- goods_data 数组字段 --<br>
                [goods_id] => 商品ID<br>
                [title] => 标题<br>
                [pic_url] => 图片<br>
                [num] => 数量<br>
                [month_num] => 本月销量<br>
                [hot_val] => 热度值<br>
                [price] => 单价<br>
     * @remark 测试过程中，在支付时，订单的金额将为1分。
     */
    public function add_order(){   
        $this->_check_token();
        // 计算总价        
        $pay_money = 0; //快递费用
        $goods = I('goods', '');
        if(!$goods){
            $this->return_data(0, array(), '提交订单失败：订单商品为空');
        }
        $goods = json_decode($goods, true);
        if(!is_array($goods)){
            $this->return_data(0, array(), '提交订单失败：订单商品数据错误');
        }
        foreach($goods as $k => $v){
            if(empty($v['id']) || empty($v['num'])){
                unset($goods[$k]);
            }
            $v['id'] = intval($v['id']);
            $v['num'] = intval($v['num']);
            if($v['id'] < 0 || $v['num'] < 0){
                unset($goods[$k]);
            }
        }
        if(!$goods){
            $this->return_data(0, array(), '提交订单失败：购物车为空');
        }
        $num = 0;
        $pre = C('DB_PREFIX');
        foreach($goods as $k => $v){
            $info = M('Goods')->where(array('_string' => "{$pre}goods_store.status = 1 and {$pre}goods.id = {$v['id']} and {$pre}goods_store.store_id = ".$this->_store_id))->join('__GOODS_STORE__ ON __GOODS__.id = __GOODS_STORE__.goods_id')->field("title, cover_id, cate_id, sell_price, {$pre}goods_store.price, {$pre}goods_store.num, {$pre}goods_store.sell_num")->find();
            if(!$info){
                $this->return_data(0, array(), '订单中有商品不存在或已下架');
            }elseif($v['num']>$info['num']){
                $this->return_data(0, array(), '《'.$info['title'].'》库存不足');
            }
            $info['price'] <= 0 && $info['price'] = $info['sell_price'];
            $pay_money += $info['price']*$v['num'];
            $goods[$k]['info'] = $info;
            $num += $v['num'];
        }
        $order_sn = $this->get_sn($this->_uid);
        // 添加订单
        $data = array(
            'order_sn' => $order_sn,
            'pay_money' => $pay_money,
            'money' => $pay_money,
            'status' => 1,
            'pay_status' => 1,
            'uid' => 0,
            'store_id' => $this->_store_id,
            'pos_id' => $this->_pos_id,
        );
        D('Addons://Order/Order')->create($data);
        $result = D('Addons://Order/Order')->add();
        if($result){
            $goods_ids = array();
            $goods_data = array();
            foreach($goods as $v){
                $detail = array(
                    'order_sn' => $order_sn,
                    'title' => $v['info']['title'],
                    'type' => 'goods',
                    'd_id' => $v['id'],
                    'num' => $v['num'],
                    'price' => $v['info']['price'],
                    'cover_id' => $v['info']['cover_id'],
                    'setting' => '',
                    'goods_log' => json_encode($v['info'])
                );
                D('Addons://Order/OrderDetail')->create($detail);
                D('Addons://Order/OrderDetail')->add();
                $goods_ids[] = $v['id'];
                $goods_data[] = array(
                    'goods_id' => $v['id'],
                    'title' => $v['info']['title'],
                    'num' => $v['num'],
                    'month_num'  => 0,
                    'hot_val'  => 0,
                    'price' => $v['info']['price'],
                    'pic_url' => get_cover_url($v['info']['cover_id']),
                );
            }
            
            $log_model = M('GoodsSellLog'.$this->_store_id);
            $date = array(
                date('Y-m-d', strtotime('-1 day')), // 昨天
                date('Y-m-d', strtotime('-2 day')), // 前天
            );
            $log_data = $log_model->where(array('goods_id' => array('in', $goods_ids), 'date' => array('in', $date)))->select();
            foreach($log_data as $v){
                if($v['date'] == $date[0]){
                    $log_num1[$v['goods_id']] = $v['num'];
                }elseif($v['date'] == $date[1]){
                    $log_num2[$v['goods_id']] = $v['num'];
                }
            }
            foreach($goods_data as $k => $v){
                $num1 = isset($log_num1[$v['goods_id']]) ? $log_num1[$v['goods_id']] : 0;
                $num2 = isset($log_num2[$v['goods_id']]) ? $log_num2[$v['goods_id']] : 0;
                // 公式 前一天的销量 / （前一天的销量+前两天的销量） * 10  取一位小数
                $v['hot_val'] = round($num1/($num1+$num2)*10, 1);
                $goods_data[$k] = $v;
            }
            $r_data = array(
                'order_sn' => $order_sn,
                'pay_money' => $pay_money,
                'num' => $num,
                'status' => 1,
                'status_text' => '新订单',
                'pay_status' => 1,
                'pay_status_text' => '未支付',
                'create_time' => time(),
                'goods_data' => $goods_data,
                'pay_qrcode_url' => U('Relay/order', array('order_sn' => $order_sn))
            );
            
            $this->return_data(1, array($r_data), '');
        }else{
            $this->return_data(0, array(), '提交订单失败：'.$result->getError());
        }
    }
    private function get_sn($uid){
        $order_sn = date('ymdHis').$uid. mt_rand(1000, 9999);
        if(D('Addons://Order/Order')->where(array('order_sn' => $order_sn))->find()){
            $order_sn = $this->get_sn($uid);
        }
        return $order_sn;
    }
    /**
     * @name  get_pay_status
     * @title 查询订单支付状态
     * @param string  $order_sn  订单号
     * @return 
     * @remark 用于查询用户是否通过微信或支付宝进行支付。<br>状态：<br>-1 : 订单不存在 <br> 1 订单已支付 <br> 0 订单未支付
     */
    public function get_pay_status(){
        $order_sn = I('order_sn');
        $order = M('Order')->where(array('order_sn' => $order_sn))->field('pay_status')->find();
        if(!$order){
            $this->return_data(-1, array(),'订单不存在');
        }
        if($order['pay_status'] == 2){
            $this->return_data(1, array(), '订单已支付');
        }else{
            $this->return_data(0, array(), '订单未支付');
        }
    }
    /**
     * @name app_info
     * @title app版本信息
     * @return [app_no] => 版本号<br>[app_name] => 版本名<br>[app_url] => 更新地址
     */
    public function app_info(){
        $config = api('Config/lists');
        C($config);
        $data = array(
            'app_no' => C('APP_NO'),
            'app_name' => C('APP_NAME'),
            'app_url' => C('APP_URL'),
        );
        $this->return_data(1, $data);
    }
}
