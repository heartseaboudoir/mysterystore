<?php

namespace Wap\Controller;
use Think\Controller;

/**
 * 扩展控制器
 * 用于调度各个扩展的URL访问需求
 */
class AddonsController extends BaseController{

	protected $addons = null;

	public function execute($_addons = null, $_controller = null, $_action = null){
		if(C('URL_CASE_INSENSITIVE')){
			$_addons = ucfirst(parse_name($_addons, 1));
			$_controller = parse_name($_controller,1);
		}
                defined ( '_ADDONS' ) or define ( '_ADDONS', $_addons );
                defined ( '_CONTROLLER' ) or define ( '_CONTROLLER', $_controller );
                defined ( '_ACTION' ) or define ( '_ACTION', $_action );
		if(!empty($_addons) && !empty($_controller) && !empty($_action)){
			$Addons = A("Addons://{$_addons}/{$_controller}")->$_action();
		} else {
			$this->error('没有指定插件名称，控制器或操作！');
		}
                //$js_api = A("Addons://Wechat/Wechatclass")->getSignPackage();
                $this->assign('js_api', $js_api);
	}
        
        protected function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
            $templateFile = $this->_getAddonTemplate($templateFile);
            $this->view->display($templateFile, $charset, $contentType, $content, $prefix);
        }

        private function _getAddonTemplate($templateFile = '') {
            if (file_exists($templateFile)) {
                return $templateFile;
            }
            
            $theme = C('DEFAULT_THEME');
            $oldFile = $templateFile;
            if (empty($templateFile)) {
                $templateFile = T('Addons://' . _ADDONS . '@'.$theme.'/' . _CONTROLLER . '/' . _ACTION);
            } elseif (stripos($templateFile, '/Addons/') === false && stripos($templateFile, THINK_PATH) === false) {
                if (stripos($templateFile, '/') === false) { // 如index
                    $templateFile = T('Addons://' . _ADDONS . '@'.$theme.'/' . _CONTROLLER . '/' . $templateFile);
                } elseif (stripos($templateFile, '@') === false) { // // 如 UserCenter/index
                    $templateFile = T('Addons://' . _ADDONS . '@'.$theme.'/' . $templateFile);
                }
            }

            if (stripos($templateFile, '/Addons/') !== false && !file_exists($templateFile)) {
                $templateFile = !empty($oldFile) ? $oldFile : _ACTION;
            }
            return $templateFile;
        }

}
