<?php
namespace Erp\Controller;
use Think\Controller;
use Admin\Model\AuthRuleModel;
use Admin\Model\AuthGroupModel;
/**
 * 后台首页控制器

 */
class BaseController extends Controller {
    
    const CODE_OK = 200;
    
    
    protected $is_admin, $store_id, $warehouse_id, $group_id;

    /**
     * 后台控制器初始化
     */
    protected function _initialize(){
        // 获取当前用户ID
        !defined('UID') && define('UID',is_login());
        

        if( !UID ){// 还没登录 跳转到登录页面
            
            $this->response(0, '请登录');
        }
        /* 读取数据库中的配置 */
        $config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =   api('Config/lists');
            S('DB_CONFIG_DATA',$config);
        }
        C($config); //添加配置
        // 是否是超级管理员
        !defined('IS_ROOT') && define('IS_ROOT',   is_administrator());
        if(!IS_ROOT && C('ADMIN_ALLOW_IP')){
            // 检查IP地址访问
            if(!in_array(get_client_ip(),explode(',',C('ADMIN_ALLOW_IP')))){
                $this->response(403, '禁止访问');
            }
        }
        // 检测访问权限
        $access =   $this->accessControl();
        if ( $access === false ) {
            $this->response(403, '未授权访问');
        }elseif( $access === null ){
            $dynamic        =   $this->checkDynamic();//检测分类栏目有关的各项动态权限
            if( $dynamic === null ){
                //检测非动态权限
                $rule  = strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME);
                //exit($rule);
                if ( !$this->checkRule($rule,array('in','1,2')) ){
                    $this->response(403, '未授权访问');
                }
            }elseif( $dynamic === false ){
                $this->response(403, '未授权访问');
            }
        }

        $this->ukey = '';
        $this->is_admin = session('user_wechat.is_admin');
        $this->assign('__IS_ADMIN__', $this->is_admin);
        //$this->assign('__MENU__', $this->getMenus());
        $this->get_store();
        $this->get_warehouse();
    }
    protected function check_store(){
        if(!$this->_store_id){
            $this->response(0, '未选择门店');
        }
    }

    private function get_store(){
        $group = M('AuthGroupAccess')->where(array('uid' => UID))->select();
        $this->group_id = array_as_key($group, 'group_id', true);
        $this->_store_id = session('user_store.id');
    }
    protected function check_warehouse(){
        if(!$this->_warehouse_id){
            $this->response(0, '未选择仓库');
        }
    }
    private function get_warehouse(){
        $group = M('AuthGroupAccess')->where(array('uid' => UID))->select();
        $this->group_id = array_as_key($group, 'group_id', true);
        $this->_warehouse_id = session('user_warehouse.w_id');
    }
    protected function get_where_supply_warehouse_store_of_shequ($which,$fld=''){
        $shequ = implode(',',$_SESSION['can_shequs']);
        if($which == 'Store') {
            $store = M('Store')->where('shequ_id in (' . $shequ . ')')->select();
            if ($store) {
                if($fld == ''){ $fld .= 'store_id';}
                $outwhere = $fld . " in (" . implode(',', array_column($store, 'id')) . ")";
            }
        }
        if($which == 'Warehouse') {
            $warehouse = M('Warehouse')->where('shequ_id in (' . $shequ . ')')->select();
            if ($warehouse) {
                if($fld == ''){ $fld .= 'warehouse_id';}
                $outwhere =  $fld . " in (" . implode(',', array_column($warehouse, 'w_id')) . ")";
            }
        }
        if($which == 'Supply') {
            $supply = M('Supply')->where('shequ_id in (' . $shequ . ')')->select();
            if ($supply) {
                if($fld == ''){ $fld .= 'supply_id';}
                $outwhere = $fld . " in (" . implode(',', array_column($supply, 's_id')) . ")";
            }
        }
        return $outwhere;
    }
    /**
     * 权限检测
     * @param string  $rule    检测的规则
     * @param string  $mode    check模式
     * @return boolean
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function checkRule($rule, $type=AuthRuleModel::RULE_URL, $mode='url'){
        if(IS_ROOT){
            return true;//管理员允许访问任何页面
        }
        //针对 getMessageList 取消判断
        if(ACTION_NAME == 'getmessagelist'){
            return true;
        }
        static $Auth    =   null;
        if (!$Auth) {
            $Auth       =   new \Think\Auth();
        }
        if(!$Auth->check($rule,UID,$type,$mode)){
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
    protected function checkDynamic(){
        if(IS_ROOT){
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
    final protected function accessControl(){
        if(IS_ROOT){
            return true;//管理员允许访问任何页面
        }
        //针对 getMessageList 取消判断
        if(ACTION_NAME == 'getmessagelist'){
            return true;
        }
		$allow = C('ALLOW_VISIT');
		$deny  = C('DENY_VISIT');
		$check = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
        if ( !empty($deny)  && in_array_case($check,$deny) ) {
            return false;//非超管禁止访问deny中的方法
        }
        if ( !empty($allow) && in_array_case($check,$allow) ) {
            return true;
        }
        return null;//需要检测节点权限
    }
   
    
    
    /**
     * 检测对特定功能的执行权限
     */
    protected function  checkFunc($name)
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


    public $datas_json = null;

    protected function gv1($name = '') 
    {
        if ($this->datas_json === null) {
            $postStr = file_get_contents("php://input");
            $data = json_decode($postStr, true);
            if (!empty($data)) {
                $this->datas_json = $data;
            } else {
                $this->datas_json = array();
            }
        }
        if (!empty($name)) {
            if (!empty($this->datas_json[$name])) {
                return $this->datas_json[$name];
            } else {
                return '';
            }
        } else {
            return $this->datas_json;
        }
    }
    
    
    public $datas = null;
    
    protected function gv2($name = '') 
    {
        if ($this->datas === null) {
            $postStr = file_get_contents("php://input");
            $data = array();
            parse_str($postStr, $data);
            if (!empty($data)) {
                $this->datas = $data;
            } else {
                $this->datas = array();
            }
        }
        if (!empty($name)) {
            if (!empty($this->datas[$name])) {
                return $this->datas[$name];
            } else {
                return '';
            }
        } else {
            return $this->datas;
        }
    }

    public function gv($name = '')
    {
        $data = $this->gv1($name);
        if (!empty($data)) {
            return $data;
        } else {
            $data = $this->gv2($name);
            return $data;
        }
    }
    

    protected function response($code = 200, $msg = '已处理请求') 
    {
        if ($code == 200) {
            $data = array(
                'code' => 200,
                'content' => $msg,
            );
        } else {
            $data = array(
                'code' => $code,
                'content' => $msg,
            );
        }

        echo json_encode($data);
        exit;
    }













    
    
}
