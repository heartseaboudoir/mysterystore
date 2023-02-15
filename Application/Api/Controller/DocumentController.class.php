<?php
// +----------------------------------------------------------------------
// | Title: 设置（文档）
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 公共 
// +----------------------------------------------------------------------
namespace Api\Controller;


class DocumentController extends ApiController {
    /**
     * @name show（接口地址已改）
     * @title 文档展示
     * @param string $name  标识(<br>'contact'：联系我们, <br>'about' => 关于我们,<br> 'pay_help' => 支付帮助, <br>'user_agreement' => 用户协议, <br>'legal_privacy' => 法律条款与隐私条款, <br>'integral_desc' => 蜜糖说明, <br>'join' => 加入我们, <br>'cash_coupon_desc' => 代金券说明<br>, <br>'user_level' => 会员等级<br>)
     * @return  显示html页面
     * @remark  <span style="color:red">调用接口改为  http://chaoshipos.k.hiiyee.com/Wap/Document/show?name=$name<br> 其中，http://chaoshipos.k.hiiyee.com/ 为当前域名</span>
     */
    public function show($name){
        $this->_check_param(array('name'));
        $name = I('name');
        if(!$name){
            $this->return_data(0, array(), '内容不存在');
        }
        $data = D('Document')->detail($name);
        if(!$data){
            $this->return_data(0, array(), '内容不存在');
        }
        $this->assign('data', $data);
        $this->display();
    }
}
