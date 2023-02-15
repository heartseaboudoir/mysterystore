<?php
// +----------------------------------------------------------------------
// | Title: 系统
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 门店端
// +----------------------------------------------------------------------
namespace Api\Controller;

class SystemController extends ApiController {
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
