<?php

namespace Admin\Controller;

use Think\Controller;
use Admin\Model\AuthRuleModel;
use Admin\Model\AuthGroupModel;

/**
 * 后台首页控制器
 */
class AdminController extends Controller
{
    protected $is_admin, $store_id, $warehouse_id, $group_id;

    /**
     * 后台控制器初始化
     */
    protected function _initialize()
    {
        // 获取当前用户ID
        !defined('UID') && define('UID', is_login());
        if (!UID) {// 还没登录 跳转到登录页面
            $this->redirect('Public/login');
        }
        /* 读取数据库中的配置 */
        $config = S('DB_CONFIG_DATA');
        if (!$config) {
            $config = api('Config/lists');
            S('DB_CONFIG_DATA', $config);
        }
        C($config); //添加配置
        // 是否是超级管理员
        !defined('IS_ROOT') && define('IS_ROOT', is_administrator());
        if (!IS_ROOT && C('ADMIN_ALLOW_IP')) {
            // 检查IP地址访问
            if (!in_array(get_client_ip(), explode(',', C('ADMIN_ALLOW_IP')))) {
                $this->error('403:禁止访问');
            }
        }
        // 检测访问权限
        $access = $this->accessControl();
        if ($access === false) {
            $this->error('403:禁止访问');
        } elseif ($access === null) {
            $dynamic = $this->checkDynamic();//检测分类栏目有关的各项动态权限
            if ($dynamic === null) {
                //检测非动态权限
                $rule = strtolower(MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME);
                if (!$this->checkRule($rule, array('in', '1,2'))) {
                    $this->error('未授权访问!');
                }
            } elseif ($dynamic === false) {
                $this->error('未授权访问!');
            }
        }
        $this->ukey = '';
        $this->is_admin = session('user_wechat.is_admin');
        $this->assign('__IS_ADMIN__', $this->is_admin);
        
        
        
        $Auth = new \Think\Auth();
        $authList = $Auth->getAuthList(UID, 1); //获取用户需要验证的所有有效规则列表
        
        if (isset($_SESSION['IS_Store']) && isset($_SESSION['IS_Warehouse'])) {
            $IS_Store = $_SESSION['IS_Store'];
            $IS_Warehouse = $_SESSION['IS_Warehouse'];            
        } else { 
            $IS_Store = $IS_Warehouse = 0;
            foreach ($authList as $k => $v) {
                if (strpos($authList[$k], 'change_store') !== false) {
                    $IS_Store = 1;
                }
                if (strpos($authList[$k], 'change_warehouse') !== false) {
                    $IS_Warehouse = 1;
                }
            }
            
            
            
            $_SESSION['IS_Store'] = $IS_Store;
            $_SESSION['IS_Warehouse'] = $IS_Warehouse;
        }
        
        $this->assign('IS_Store', $IS_Store);
        $this->assign('IS_Warehouse', $IS_Warehouse);
        $this->get_store();
        $this->get_warehouse();

        $menus = $this->getMenus();

        
        
        // 获取采购区域
        if (empty($_SESSION['can_shequs_cg'])) {
            $shequs_cg = $this->get_shequs15();
            $_SESSION['can_shequs_cg'] = $shequs_cg;
        } else {
            $shequs_cg = $_SESSION['can_shequs_cg'];
        }
        

        // 1.用户有哪些区域的权限：从采购、仓库、门店方面查

        if (empty($_SESSION['can_shequs'])) {
            $shequs = $this->get_shequs();
            $_SESSION['can_shequs'] = $shequs;
        } else {
            $shequs = $_SESSION['can_shequs'];
        }


        // 2.哪些区域可以使用新版ERP
        $shequs_data = M('shequ')->where(array('newerp' => 1))->select();
        $shequs_can = array();
        if (!empty($shequs_data)) {
            foreach ($shequs_data as $key => $val) {
                $shequs_can[] = $val['id'];
            }
        }


        $sq_cans = array_intersect($shequs_can, $shequs);


        // 3.判断是否使用新版ERP
        if (empty($sq_cans) && !empty($menus['main'])) {
            $unset_titles = array('采购', '仓库', '门店');
            foreach ($menus['main'] as $key => $val) {
                if (in_array($val['title'], $unset_titles)) {
                    unset($menus['main'][$key]);
                }            
            }
        }
        
        
        
        if (!empty($shequs_can) && !empty($this->_store_id)) {
            
            $store_info = M('store')->where(array('id' => $this->_store_id))->find();
            
            if (!empty($store_info) && !empty($store_info['shequ_id'])) {
                if (in_array($store_info['shequ_id'], $shequs_can)) {
                    $unset_product = false;
                    $unset_products = array('商品入库', '商品出库', '商品找回', '商品丢耗');
                    foreach ($menus['main'] as $key => $val) {         
                        if ($val['title'] == '商品' && !empty($val['class']) && $val['class'] == 'current') {
                            $unset_product = true;
                        }
                    }            
                    
                    
                    //print_r($menus['child']);exit;
                    
                    if ($unset_product && !empty($menus['child'])) {
                        foreach ($menus['child'] as $key => $val) {
                            foreach ($val as $key2 => $val2) {
                                if (in_array($val2['title'], $unset_products)) {
                                    unset($menus['child'][$key][$key2]);
                                }
                            }
                        }
                    }                     
                }
            }
            
           
        }

        // header('Content-Type: text/html; charset=utf8;');


        //print_r($menus);
        //exit;


        $this->assign('__MENU__', $menus);


    }

    protected function check_store()
    {
        if (!$this->_store_id) {
            Cookie('__forward__', $_SERVER['REQUEST_URI']);
            redirect(addons_url('Store://StoreAdmin:/change_store'));
        }
    }

    private function get_store()
    {
        $group = M('AuthGroupAccess')->where(array('uid' => UID))->select();
        $this->group_id = array_as_key($group, 'group_id', true);
        $this->_store_id = session('user_store.id');
    }

    protected function check_warehouse()
    {
        if (!$this->_warehouse_id) {
            Cookie('__forward__', $_SERVER['REQUEST_URI']);
            redirect(addons_url('Store://Warehouse:/change_warehouse'));
        }
    }

    private function get_warehouse()
    {
        $group = M('AuthGroupAccess')->where(array('uid' => UID))->select();
        $this->group_id = array_as_key($group, 'group_id', true);
        $this->_warehouse_id = session('user_warehouse.w_id');
    }

    protected function get_where_supply_warehouse_store_of_shequ($which, $fld = '')
    {
        $shequ = implode(',', $_SESSION['can_shequs_cg']);
        if ($which == 'Store') {
            $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
            if ($store) {
                if ($fld == '') {
                    $fld .= 'store_id';
                }
                $outwhere = $fld . " in (" . implode(',', array_column($store, 'id')) . ")";
            }
        }
        if ($which == 'Warehouse') {
            $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
            if ($warehouse) {
                if ($fld == '') {
                    $fld .= 'warehouse_id';
                }
                $outwhere = $fld . " in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            }
        }
        if ($which == 'Supply') {
            $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
            if ($supply) {
                if ($fld == '') {
                    $fld .= 'supply_id';
                }
                $outwhere = $fld . " in (" . implode(',', array_column($supply, 's_id')) . ")";
            }
        }
        return $outwhere;
    }
    protected function get_where_supply_warehouse_store_of_shequ_zzy($which, $fld = '')
    {
        //新增获取社区方法  zzy
        $shequ = $this->__member_store_shequ();
        $shequ = implode(',', $shequ);
        if ($which == 'Store') {
            $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
            if ($store) {
                if ($fld == '') {
                    $fld .= 'store_id';
                }
                $outwhere = $fld . " in (" . implode(',', array_column($store, 'id')) . ")";
            }
        }
        if ($which == 'Warehouse') {
            $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
            if ($warehouse) {
                if ($fld == '') {
                    $fld .= 'warehouse_id';
                }
                $outwhere = $fld . " in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            }
        }
        if ($which == 'Supply') {
            $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
            if ($supply) {
                if ($fld == '') {
                    $fld .= 'supply_id';
                }
                $outwhere = $fld . " in (" . implode(',', array_column($supply, 's_id')) . ")";
            }
        }
        return $outwhere;
    }
    /**
     * 获取用户当前用户的权限-门店
     */
    public function getNowStoreGroup()
    {    
        $my_shequ = M('MemberStore')->where(array('uid' => UID, 'type' => 2))->select();
        $my_store = array();
        if($my_shequ){
            $shequ_ids = array();
            foreach($my_shequ as $v){
                $shequ_ids[] = $v['store_id'];
                $group_shequ[$v['store_id']][] = $v['group_id'];
            }
            $store_data = M('Store')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
            if($store_data){
                foreach($store_data as $v){
                    $my_store[$v['id']] = array(
                        'group_id' => $group_shequ[$v['shequ_id']],
                        'store_id' => $v['id'],
                    );
                }
            }
        }
        $_my_store = M('MemberStore')->where(array('uid' => UID, 'type' => 1))->field('group_id,store_id')->select();
        foreach($_my_store as $v){
            if(isset($my_store[$v['store_id']])){
                !in_array($v['group_id'], $my_store[$v['store_id']]['group_id']) &&  $my_store[$v['store_id']]['group_id'][] = $v['group_id'];
            }else{
                $my_store[$v['store_id']] = array(
                    'group_id' => array($v['group_id']),
                    'store_id' => $v['store_id'],
                );
            }
        }
        
        
        
        // 没有门店权限，返回空数组
        if(!$my_store){
            return array();
        }
        $my_store_access = array();
        $my_group = array();
        foreach($my_store as $v){
            $my_store_access[] = $v['store_id'];
            $my_group[$v['store_id']] = $v['group_id'];
        }
        
        // 所有权限
        /*
        if(!IS_ROOT && !in_array(1, $this->group_id)){
            $group_now = array();
            foreach ($my_group as $group_key => $group_val) {
                $group_now = array_merge($group_now, $group_val);
            }
            
            return array_unique($group_now);            
            
        }
        */
        
        
        // 当前选择的门店
        $store_id_now = session('user_store.id');
        
        $group_now = array();
        if (empty($store_id_now)) {
            foreach ($my_group as $group_key => $group_val) {
                $group_now = array_merge($group_now, $group_val);
            }
            
            return array_unique($group_now);
        }
        
        
        
        if(empty($my_store_access) || empty($my_group[$store_id_now])){
            return array();
        } else {
            return $my_group[$store_id_now];
        }        
    

    }
    
    /**
     * 获取用户当前用户的权限-仓库
     */
    public function getNowWarehouseGroup()
    {
        $my_shequ = M('MemberWarehouse')->where(array('uid' => UID, 'type' => 2))->select();
        $my_warehouse = array();
        if($my_shequ){
            $shequ_ids = array();
            foreach($my_shequ as $v){
                $shequ_ids[] = $v['warehouse_id'];
                $group_shequ[$v['warehouse_id']][] = $v['group_id'];
            }
            $warehouse_data = M('Warehouse')->where(array('shequ_id' => array('in', $shequ_ids)))->field('id, shequ_id')->select();
            if($warehouse_data){
                foreach($warehouse_data as $v){
                    $my_warehouse[$v['id']] = array(
                        'group_id' => $group_shequ[$v['shequ_id']],
                        'warehouse_id' => $v['id'],
                    );
                }
            }
        }
        $_my_warehouse = M('MemberWarehouse')->where(array('uid' => UID, 'type' => 1))->field('group_id,warehouse_id')->select();
        foreach($_my_warehouse as $v){
            if(isset($my_warehouse[$v['warehouse_id']])){
                !in_array($v['group_id'], $my_warehouse[$v['warehouse_id']]['group_id']) &&  $my_warehouse[$v['warehouse_id']]['group_id'][] = $v['group_id'];
            }else{
                $my_warehouse[$v['warehouse_id']] = array(
                    'group_id' => array($v['group_id']),
                    'warehouse_id' => $v['warehouse_id'],
                );
            }
        }
        
        // 没有仓库权限，返回空数组
        if(!$my_warehouse){
            return array();
        }
        $my_warehouse_access = array();
        $my_group = array();
        foreach($my_warehouse as $v){
            $my_warehouse_access[] = $v['warehouse_id'];
            $my_group[$v['warehouse_id']] = $v['group_id'];
        }
        
        // 所有权限
        /*
        if(!IS_ROOT && !in_array(1, $this->group_id)){
            $group_now = array();
            foreach ($my_group as $group_key => $group_val) {
                $group_now = array_merge($group_now, $group_val);
            }
            
            return array_unique($group_now);            
            
        }
        */
        
        
        // 当前选择的仓库
        $warehouse_id_now = session('user_warehouse.w_id');
        
        $group_now = array();
        if (empty($warehouse_id_now)) {
            foreach ($my_group as $group_key => $group_val) {
                $group_now = array_merge($group_now, $group_val);
            }
            
            return array_unique($group_now);
        }
        
        
        
        if(empty($my_warehouse_access) || empty($my_group[$warehouse_id_now])){
            return array();
        } else {
            return $my_group[$warehouse_id_now];
        }
       
    }
    
    
    private function get_shequs15()
    {

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


        $shequs = array();
        //var_dump($this->group_id);exit;

        //var_dump(IS_ROOT);exit;
        //echo $uid;exit;

        // 采购
        $sql_shequ_store = "select * from hii_member_store where type = 2 and group_id = 15 and uid = {$uid}";

        $data_shequ_store = M()->query($sql_shequ_store);

        if (!empty($data_shequ_store)) {
            foreach ($data_shequ_store as $key => $val) {
                $shequs[] = $val['store_id'];

            }
        }


        // 门店(含采购)
        $sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where type = 1 and group_id = 15 and uid = {$uid} group by s.shequ_id;";

        $data_shequ2_store = M()->query($sql_shequ2_store);

        if (!empty($data_shequ2_store)) {
            foreach ($data_shequ2_store as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs)) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }
        
        return $shequs;
    }

    private function get_shequs()
    {

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


        $shequs = array();
        //var_dump($this->group_id);exit;

        //var_dump(IS_ROOT);exit;
        //echo $uid;exit;

        // 门店社区(含采购)
        $sql_shequ_store = "select * from hii_member_store where type = 2 and uid = {$uid}";

        $data_shequ_store = M()->query($sql_shequ_store);

        if (!empty($data_shequ_store)) {
            foreach ($data_shequ_store as $key => $val) {
                $shequs[] = $val['store_id'];

            }
        }


        // 门店(含采购)
        $sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where type = 1 and uid = {$uid} group by s.shequ_id;";

        $data_shequ2_store = M()->query($sql_shequ2_store);

        if (!empty($data_shequ2_store)) {
            foreach ($data_shequ2_store as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs)) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }


        // 仓库社区
        $sql_shequ_warehouse = "select * from hii_member_warehouse where type = 2 and uid = {$uid}";

        $data_shequ_warehouse = M()->query($sql_shequ_warehouse);

        if (!empty($data_shequ_warehouse)) {
            foreach ($data_shequ_warehouse as $key => $val) {
                if (!in_array($val['warehouse_id'], $shequs)) {
                    $shequs[] = $val['warehouse_id'];
                }
            }
        }


        // 仓库
        $sql_shequ2_warehouse = "select mw.*,w.shequ_id  from hii_member_warehouse mw left join hii_warehouse w on w.w_id = mw.warehouse_id where type = 1 and uid = {$uid} group by w.shequ_id;";

        $data_shequ2_warehouse = M()->query($sql_shequ2_warehouse);
        /*
        if ($_GET['xy']) {
            print_r($data_shequ2_warehouse);
        }
        */
        if (!empty($data_shequ2_warehouse)) {
            foreach ($data_shequ2_warehouse as $key => $val) {
                if (!in_array($val['shequ_id'], $shequs)) {
                    $shequs[] = $val['shequ_id'];
                }
            }
        }

        return $shequs;


        // 2.哪些区域可以使用新版ERP
        /*
        $shequs_data = M('shequ')->where(array('newerp' => 1))->select();
        $shequs_can = array();
        if (!empty($shequs_data)) {
            foreach ($shequs_data as $key => $val) {
                $shequs_can[] = $val['id'];
            }
        }
        

        
        
        $sq_cans = array_intersect($shequs_can, $shequs);

        
        return $sq_cans;
        */

        /*
        print_r($shequs_can);
        print_r($shequs);            
        print_r($sq_cans);
        */


    }

    /**
     * 权限检测
     * @param string $rule 检测的规则
     * @param string $mode check模式
     * @return boolean
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function checkRule($rule, $type = AuthRuleModel::RULE_URL, $mode = 'url')
    {
        if (IS_ROOT) {
            return true;//管理员允许访问任何页面
        }
        //针对 getMessageList 取消判断
        if(ACTION_NAME == 'getmessagelist'){
            return true;
        }
        static $Auth = null;
        if (!$Auth) {
            $Auth = new \Think\Auth();
        }
        if (!$Auth->check($rule, UID, $type, $mode)) {
            return false;
        }
        return true;
    }

    /**
     * 检测是否是需要动态判断的权限
     * @return boolean|null
     *      返回true则表示当前访问有权限
     *      返回false则表示当前访问无权限
     *      返回null，则会进入checkRule根据节点授权判断权限
     *
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    protected function checkDynamic()
    {
        if (IS_ROOT) {
            return true;//管理员允许访问任何页面
        }
        //针对 getMessageList 取消判断
        if(ACTION_NAME == 'getmessagelist'){
            return true;
        }
        return null;//不明,需checkRule
    }


    /**
     * action访问控制,在 **登陆成功** 后执行的第一项权限检测任务
     *
     * @return boolean|null  返回值必须使用 `===` 进行判断
     *
     *   返回 **false**, 不允许任何人访问(超管除外)
     *   返回 **true**, 允许任何管理员访问,无需执行节点权限检测
     *   返回 **null**, 需要继续执行节点权限检测决定是否允许访问
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function accessControl()
    {
        if (IS_ROOT) {
            return true;//管理员允许访问任何页面
        }
        //针对 getMessageList 取消判断
        if(ACTION_NAME == 'getmessageList'){
            return true;
        }
        $allow = C('ALLOW_VISIT');
        $deny = C('DENY_VISIT');
        $check = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);
        if (!empty($deny) && in_array_case($check, $deny)) {
            return false;//非超管禁止访问deny中的方法
        }
        if (!empty($allow) && in_array_case($check, $allow)) {
            return true;
        }
        return null;//需要检测节点权限
    }

    /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称,供M函数使用的参数
     * @param array $data 修改的数据
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function editRow($model, $data, $where, $msg)
    {
        $id = array_unique((array)I('id', 0));
        $id = is_array($id) ? implode(',', $id) : $id;
        $where = array_merge(array('id' => array('in', $id)), (array)$where);
        $msg = array_merge(array('success' => '操作成功！', 'error' => '操作失败！', 'url' => '', 'ajax' => IS_AJAX), (array)$msg);
        if (M($model)->where($where)->save($data) !== false) {
            $this->success($msg['success'], $msg['url'], $msg['ajax']);
        } else {
            $this->error($msg['error'], $msg['url'], $msg['ajax']);
        }
    }

    /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的 where()方法的参数
     * @param array $msg 执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function forbid($model, $where = array(), $msg = array('success' => '状态禁用成功！', 'error' => '状态禁用失败！'))
    {
        $data = array('status' => 0);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function resume($model, $where = array(), $msg = array('success' => '状态恢复成功！', 'error' => '状态恢复失败！'))
    {
        $data = array('status' => 1);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 还原条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * @author huajie  <banhuajie@163.com>
     */
    protected function restore($model, $where = array(), $msg = array('success' => '状态还原成功！', 'error' => '状态还原失败！'))
    {
        $data = array('status' => 1);
        $where = array_merge(array('status' => -1), $where);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 条目假删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function delete($model, $where = array(), $msg = array('success' => '删除成功！', 'error' => '删除失败！'))
    {
        $data['status'] = -1;
        $data['update_time'] = NOW_TIME;
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 设置一条或者多条数据的状态
     */
    public function setStatus($Model = CONTROLLER_NAME)
    {

        $ids = I('request.ids');
        $status = I('request.status');
        if (empty($ids)) {
            $this->error('请选择要操作的数据');
        }

        $map['id'] = array('in', $ids);
        switch ($status) {
            case -1 :
                $this->delete($Model, $map, array('success' => '删除成功', 'error' => '删除失败'));
                break;
            case 0  :
                $this->forbid($Model, $map, array('success' => '禁用成功', 'error' => '禁用失败'));
                break;
            case 1  :
                $this->resume($Model, $map, array('success' => '启用成功', 'error' => '启用失败'));
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }

    /**
     * 获取控制器菜单数组,二级菜单元素位于一级菜单的'_child'元素中
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final public function getMenus($controller = CONTROLLER_NAME)
    {
        // $menus  =   session('ADMIN_MENU_LIST'.$controller);
        if (empty($menus)) {
            // 获取主菜单
            $where['pid'] = 0;
            $where['hide'] = 0;
            if (!C('DEVELOP_MODE')) { // 是否开发者模式
                $where['is_dev'] = 0;
            }
            $menus['main'] = M('Menu')->where($where)->order('sort asc')->select();


            $menus['child'] = array(); //设置子节点
            //高亮主菜单
            $current = M('Menu')->where("url like '%{$controller}/" . ACTION_NAME . "%'")->field('id')->find();

            if ($_GET['yy'] == 1) {
                print_r($menus['main']);
                
                $RuleList = $_SESSION['_AUTH_LIST_' . UID . 'in,1,2'];
                
                print_r($RuleList);

                print_r("{$controller}/" . ACTION_NAME);


                print_r($current);
                exit;
            }

            if ($current) {
                $nav = D('Menu')->getPath($current['id']);
                $nav_first_title = $nav[0]['title'];

                foreach ($menus['main'] as $key => $item) {
                    if (!is_array($item) || empty($item['title']) || empty($item['url'])) {
                        $this->error('控制器基类$menus属性元素配置有误');
                    }
                    if (stripos($item['url'], MODULE_NAME) !== 0) {
                        $item['url'] = MODULE_NAME . '/' . $item['url'];
                    }
                    // 判断主菜单权限
                    if (!IS_ROOT && !$this->checkRule($item['url'], AuthRuleModel::RULE_MAIN, null)) {
                        unset($menus['main'][$key]);
                        continue;//继续循环
                    }
                    // 获取当前主菜单的子菜单项
                    if ($item['title'] == $nav_first_title) {
                        $menus['main'][$key]['class'] = 'current';
                        //生成child树
                        $groups = M('Menu')->where("pid = {$item['id']}")->distinct(true)->field("`group`")->order('sort asc')->select();
                        if ($groups) {
                            $groups = array_column($groups, 'group');
                        } else {
                            $groups = array();
                        }

                        //获取二级分类的合法url
                        $where = array();
                        $where['pid'] = $item['id'];
                        $where['hide'] = 0;
                        if (!C('DEVELOP_MODE')) { // 是否开发者模式
                            $where['is_dev'] = 0;
                        }
                        $second_urls = M('Menu')->where($where)->getField('id,url');

                        if (!IS_ROOT) {
                            // 检测菜单权限
                            $to_check_urls = array();
                            foreach ($second_urls as $key => $to_check_url) {
                                if (stripos($to_check_url, MODULE_NAME) !== 0) {
                                    $rule = MODULE_NAME . '/' . $to_check_url;
                                } else {
                                    $rule = $to_check_url;
                                }
                                if ($this->checkRule($rule, AuthRuleModel::RULE_URL, null))
                                    $to_check_urls[] = $to_check_url;
                            }
                        }
                        // 按照分组生成子菜单树
                        foreach ($groups as $g) {
                            $map = array('group' => $g);
                            if (isset($to_check_urls)) {
                                if (empty($to_check_urls)) {
                                    // 没有任何权限
                                    continue;
                                } else {
                                    $map['url'] = array('in', $to_check_urls);
                                }
                            }
                            $map['pid'] = $item['id'];
                            $map['hide'] = 0;
                            if (!C('DEVELOP_MODE')) { // 是否开发者模式
                                $map['is_dev'] = 0;
                            }
                            $menuList = M('Menu')->where($map)->field('id,pid,title,url,tip')->order('sort asc')->select();
                            $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
                        }
                        if ($menus['child'] === array()) {
                            //$this->error('主菜单下缺少子菜单，请去系统=》后台菜单管理里添加');
                        }
                    }
                }
                //因为主菜单url有个默认值，但是如果没有该默认值的权限，
                //那么判断权限session里面是否有两个默认值url
                //如果有两个代表有该默认值权限
                //如果只有1个，代表没有该默认值权限，
                //查找菜单表里面pid=主菜单的所有数据，然后对比权限session,第一个对应url相等，则把主菜单的url改为对应有session权限的url
                $RuleList = $_SESSION['_AUTH_LIST_' . UID . 'in,1,2'];
                foreach ($menus['main'] as $key => $item) {
                    if ($item['id'] != 1 && $item['id'] != 2) {
                        $havecount = 0;
                        for ($j = 0; $j < count($RuleList); $j++) {
                            $menu_url = $item['url'];//判断是否包含url
                            $string1 = strtoupper('Admin/' . $menu_url);
                            $string2 = strtoupper($RuleList[$j]);
                            if ($string1 == $string2) {
                                $havecount++;
                            }
                        }
                        if ($havecount == 1) {
                            $childmenu = M('Menu')->where("pid = " . $item['id'])->select();
                            for ($k = 0; $k < count($childmenu); $k++) {
                                for ($j = 0; $j < count($RuleList); $j++) {
                                    $string3 = strtoupper('Admin/' . $childmenu[$k]['url']);
                                    $string4 = strtoupper($RuleList[$j]);
                                    if ($string3 == $string4 && $string3 != $string1) {
                                        $itemnow = $menus['main'][$key];
                                        $itemnow['url'] = $childmenu[$k]['url'];
                                        $menusout[] = $itemnow;
                                        break 2;
                                    }
                                }
                            }
                        } else {
                            $menusout[] = $item;
                        }
                    } else {
                        $menusout[] = $item;
                    }
                }
                $menus['main'] = $menusout;
            }
            // session('ADMIN_MENU_LIST'.$controller,$menus);
        }
        return $menus;
    }

    /**
     * 返回后台节点数据
     * @param boolean $tree 是否返回多维数组结构(生成菜单时用到),为false返回一维数组(生成权限节点时用到)
     * @retrun array
     *
     * 注意,返回的主菜单节点数组中有'controller'元素,以供区分子节点和主节点
     *
     * @author 朱亚杰 <xcoolcc@gmail.com>
     */
    final protected function returnNodes($tree = true)
    {
        static $tree_nodes = array();
        if ($tree && !empty($tree_nodes[(int)$tree])) {
            return $tree_nodes[$tree];
        }
        if ((int)$tree) {
            $list = M('Menu')->field('id,pid,title,url,tip,hide')->where(array('is_dev' => 0))->order('sort asc')->select();
            foreach ($list as $key => $value) {
                if (stripos($value['url'], MODULE_NAME) !== 0 && stripos($value['url'], 'Erp') !== 0) {
                    $list[$key]['url'] = MODULE_NAME . '/' . $value['url'];
                }
            }
            $nodes = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'operator', $root = 0);
            foreach ($nodes as $key => $value) {
                if (!empty($value['operator'])) {
                    $nodes[$key]['child'] = $value['operator'];
                    unset($nodes[$key]['operator']);
                }
            }
        } else {
            $nodes = M('Menu')->field('title,url,tip,pid')->order('sort asc')->select();
            foreach ($nodes as $key => $value) {
                if (stripos($value['url'], MODULE_NAME) !== 0 && stripos($value['url'], 'Erp') !== 0) {
                    $nodes[$key]['url'] = MODULE_NAME . '/' . $value['url'];
                }
            }
        }
        $tree_nodes[(int)$tree] = $nodes;
        return $nodes;
    }


    /**
     * 通用分页列表数据集获取方法
     *
     *  可以通过url参数传递where条件,例如:  index.html?name=asdfasdfasdfddds
     *  可以通过url空值排序字段和方式,例如: index.html?_field=id&_order=asc
     *  可以通过url参数r指定每页数据条数,例如: index.html?r=5
     *
     * @param sting|Model $model 模型名或模型实例
     * @param array $where where查询条件(优先级: $where>$_REQUEST>模型设定)
     * @param array|string $order 排序条件,传入null时使用sql默认排序或模型属性(优先级最高);
     *                              请求参数中如果指定了_order和_field则据此排序(优先级第二);
     *                              否则使用$order参数(如果$order参数,且模型也没有设定过order,则取主键降序);
     *
     * @param array $base 基本的查询条件
     * @param boolean $field 单表模型用不到该参数,要用在多表join时为field()方法指定参数
     * @author 朱亚杰 <xcoolcc@gmail.com>
     *
     * @return array|false
     * 返回数据集
     */
    protected function lists($model, $where = array(), $order = '', $base = array('status' => array('egt', 0)), $field = true, $count = 0)
    {
        $options = array();
        $REQUEST = (array)I('request.');
        if (is_string($model)) {
            $model = M($model);
        }

        $OPT = new \ReflectionProperty($model, 'options');
        $OPT->setAccessible(true);

        $pk = $model->getPk();
        if ($order === null) {
            //order置空
        } else if (isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']), array('desc', 'asc'))) {
            $options['order'] = '`' . $REQUEST['_field'] . '` ' . $REQUEST['_order'];
        } elseif ($order === '' && empty($options['order']) && !empty($pk)) {
            $options['order'] = $pk . ' desc';
        } elseif ($order) {
            $options['order'] = $order;
        }
        unset($REQUEST['_order'], $REQUEST['_field']);

        $options['where'] = array_filter(array_merge((array)$base, /*$REQUEST,*/
            (array)$where), function ($val) {
            if ($val === '' || $val === null) {
                return false;
            } else {
                return true;
            }
        });
        if (empty($options['where'])) {
            unset($options['where']);
        }
        $options = array_merge((array)$OPT->getValue($model), $options);
        $total = $model->where($options['where'])->join(isset($options['join']) ? $options['join'] : '')->alias(isset($options['alias']) ? $options['alias'] : '')->count();
        if($count == 0) {
            if (isset($REQUEST['r'])) {
                $listRows = (int)$REQUEST['r'];
            } else {
                $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
            }
        }else{
            $listRows = $count;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ($total > $listRows) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $options['limit'] = $page->firstRow . ',' . $page->listRows;

        $model->setProperty('options', $options);

        return $model->field($field)->join(isset($options['join']) ? $options['join'] : '')->alias(isset($options['alias']) ? $options['alias'] : '')->select();
    }

    protected function lists2($model, $where = array(), $order = '', $base = array('status' => array('egt', 0)), $field = true, $leftJoin = null, $group = null)
    {
        $options = array();
        $REQUEST = (array)I('request.');
        if (is_string($model)) {
            $model = M($model);
        }

        $OPT = new \ReflectionProperty($model, 'options');
        $OPT->setAccessible(true);

        $pk = $model->getPk();
        if ($order === null) {
            //order置空
        } else if (isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']), array('desc', 'asc'))) {
            $options['order'] = '`' . $REQUEST['_field'] . '` ' . $REQUEST['_order'];
        } elseif ($order === '' && empty($options['order']) && !empty($pk)) {
            $options['order'] = $pk . ' desc';
        } elseif ($order) {
            $options['order'] = $order;
        }
        unset($REQUEST['_order'], $REQUEST['_field']);

        $options['where'] = array_filter(array_merge((array)$base, /*$REQUEST,*/
            (array)$where), function ($val) {
            if ($val === '' || $val === null) {
                return false;
            } else {
                return true;
            }
        });
        if (empty($options['where'])) {
            unset($options['where']);
        }
        $options = array_merge((array)$OPT->getValue($model), $options);
        $total = $model->where($options['where'])->join(isset($options['join']) ? $options['join'] : '')->alias(isset($options['alias']) ? $options['alias'] : '')->count();

        if (isset($REQUEST['r'])) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ($total > $listRows) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $options['limit'] = $page->firstRow . ',' . $page->listRows;

        $model->setProperty('options', $options);

        $result = $model->field($field)
            ->join(isset($options['join']) ? $options['join'] : '')
            ->join($leftJoin != null ? $leftJoin : '')
            ->alias(isset($options['alias']) ? $options['alias'] : '')
            ->group($group != null ? $group : '')
            ->select();
        return $result;
    }

    protected function _index($where = array(), $order = 'id desc', $field = true)
    {
        $this->_check_base();
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        !is_array($where) && $where = array();
        $list = $this->lists($this->model, $where, $order, array(), $field);
        $this->callback_fun && $list = call_user_func_array(array($this, $this->callback_fun), array($list));
        $this->assign('list', $list);
        !$this->meta_title && $this->meta_title = $this->page_title . '管理';
        $this->display($this->_get_tpl());
    }

    protected function _save($where = array(), $field = true)
    {
        $pk = $this->model->getPk();
        $id = I('get.' . $pk, 0, 'intval');
        $data = array();
        if ($id > 0 || $where) {
            $id > 0 && $where[$pk] = $id;
            $data = $this->model->where($where)->field($field)->find();
        }
        $this->callback_fun && $data = call_user_func_array(array($this, $this->callback_fun), array($data));
        !$this->meta_title && $this->meta_title = ($id ? '查看' : '添加') . $this->page_title;
        $this->assign('data', $data);
        $this->display($this->_get_tpl());
    }

    protected function _update($data = array())
    {
        $this->_check_base();
        $res = $this->model->update($data);
        $this->callback_fun && call_user_func_array(array($this, $this->callback_fun), array($res));
        if (!$res) {
            $this->error($this->model->getError());
        } else {
            $this->success($res['id'] ? '更新成功' : '新增成功', Cookie('__forward__'));
        }
    }

    protected function _remove($where = array())
    {
        $this->_check_base();
        $pk = $this->model->getPk();
        $id = I($pk, '');
        if ($id) {
            !is_array($id) && $id = explode(',', $id);
            $where[$pk] = array('in', $id);
            $res = $this->model->where($where)->delete();
            $this->callback_fun && call_user_func_array(array($this, $this->callback_fun), array($res));
            if (!$res) {
                $error = $this->model->getError();
                $this->error($error ? $error : '找不到要删除的数据！');
            } else {
                $this->success('删除成功', Cookie('__forward__'));
            }
        } else {
            $this->error('请选择删除的数据！', Cookie('__forward__'));
        }
    }

    protected function _listorder($where = array())
    {
        $this->_check_base();
        $pk = $this->model->getPk();
        $id = I('get.' . $pk, 0, 'intval');
        $listorder = I('get.listorder', 0);
        $data = array(
            $pk => $id,
            'listorder' => $listorder,
        );
        $where[$pk] = $id;
        $res = $this->model->where($where)->save($data);
        if ($res) {
            $result['status'] = 1;
        } else {
            $result['status'] = 0;
        }
        $this->ajaxReturn($result);
    }

    private function _check_base()
    {
        if (empty($this->model)) {
            $this->error('未指定model');
        }
    }

    private function _get_tpl()
    {
        if ($this->tpl) {
            return $this->tpl;
        }
        $tpl = '';
        if (CONTROLLER_NAME == 'Addons') {
            $tpl = T('Addons://' . I('get._addons') . '@Admin/' . I('get._controller') . '/' . I('get._action'));
        }

        return $tpl;
    }

    public function __call($method, $args)
    {
        if (in_array($method, array('index', 'save', 'update', 'remove', 'listorder'))) {
            $n_method = '_' . $method;
            $this->$n_method();
        } else {
            parent::__call($method, $args);
        }
    }


    protected function checkFunc($name)
    {
        $name = trim($name);

        if (empty($name)) {
            return false;
        }

        $one = M('funcs')->where(array(
            'func' => $name
        ))->find();


        if (empty($one)) {
            return false;
        }


        $func = $one['id'];


        if (!UID) {
            return false;
        }

        $groups = M('AuthGroupAccess')->where(array('uid' => UID))->find();

        if (empty($groups)) {
            return false;
        }

        $group = $groups['group_id'];


        $auths = M('AuthGroup')->where(array('id' => $group))->find();

        if (empty($auths) || empty($auths['funcs'])) {
            return false;
        }


        $funcs = explode(',', $auths['funcs']);


        if (in_array($func, $funcs)) {
            return true;
        } else {
            return false;
        }


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
                foreach ($data_shequ_store as $key => $val) {
                    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                        $shequs[] = $val['shequ_id'];
                    }
                    
                }
                
            }
        }
        
        
        // 门店(含采购)
        $sql_shequ2_store = "select ms.*, s.shequ_id from hii_member_store ms left join hii_store s on s.id = ms.store_id where  type = 1 and group_id in({$group_id}) and uid = {$uid} group by s.shequ_id;";
        
        $data_shequ2_store = M()->query($sql_shequ2_store);
        
        if (!empty($data_shequ2_store)) {
            foreach ($data_shequ2_store as $key => $val) {
                foreach ($data_shequ_store as $key => $val) {
                    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                        $shequs[] = $val['shequ_id'];
                    }
                    
                }
            }
        }
        
        
        // 仓库社区
        $sql_shequ_warehouse = "select * from hii_member_warehouse where type = 2 and group_id in({$group_id}) and uid = {$uid}";
        
        $data_shequ_warehouse = M()->query($sql_shequ_warehouse);
        
        if (!empty($data_shequ_warehouse)) {
            foreach ($data_shequ_warehouse as $key => $val) {
                foreach ($data_shequ_store as $key => $val) {
                    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                        $shequs[] = $val['shequ_id'];
                    }
                    
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
                foreach ($data_shequ_store as $key => $val) {
                    if (!in_array($val['shequ_id'], $shequs) && $val['shequ_id'] != null) {
                        $shequs[] = $val['shequ_id'];
                    }
                    
                }
            }
        }
        
        return $shequs;
    }
}
