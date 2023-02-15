<?php
namespace Admin\Controller;

/**
 * 后台配置控制器

 */
class ExpressMoneyAreaController extends AdminController {
    
    public function index(){
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        
        $list = M('Area')->where(array('pid' => 0))->select();
        $in_list = M('ExpressMoneyArea')->select();
        foreach($in_list as $v){
            $_in_list[$v['sheng']] = $v['money'];
        }
        foreach($list as $k => $v){
            $v['express_money'] = isset($_in_list[$v['id']]) ? $_in_list[$v['id']] : 0;
            $list[$k] = $v;
        }
        
        $this->assign('list', $list);
        $this->meta_title = '地区运费设置';
        $this->display();
    }

    public function save(){
        if(IS_POST){
            $config = I('config');
            $Model = M('ExpressMoneyArea');
            $in_list = $Model->select();
            foreach($in_list as $v){
                $_in_list[$v['sheng']] = $v['money'];
            }
            $area_list = M('Area')->where(array('pid' => 0))->select();
            $area_ids = array();
            foreach($area_list as $v){
                $area_ids[] = $v['id'];
            }
            foreach($config as $k => $v){
                if(!in_array($k, $area_ids)){
                    continue;
                }
                $v = round($v, 2);
                if(empty($_in_list[$k])){
                    $Model->add(array('sheng' => $k, 'money' => $v, 'create_time' => NOW_TIME, 'update_time' => NOW_TIME));
                }elseif($_in_list[$k] != $v){
                    $Model->where(array('sheng' => $k))->save(array('money' => $v, 'update_time' => NOW_TIME));
                }
            }
            $this->success('操作成功');
        }else{
            $this->error('非法操作');
        }
    }
}
