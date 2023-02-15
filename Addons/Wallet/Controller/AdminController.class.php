<?php
namespace Addons\Wallet\Controller;

use Admin\Controller\AddonsController;

class AdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        // $where = array();
        // $list = $this->lists(D('Addons://Wallet/Wallet'), $where, 'id desc');
        
        
        
        $uid = I('uid', 0, 'intval');
        
        
        if (preg_match('/^1\d{10}$/', $uid)) {
            $one = M('ucenter_member')->where(array('mobile' => $uid))->find();
            
            // 查找用户ID
            if (!empty($one['id'])) {
                $uid = $one['id']; 
            }
        }
        
        $Model = M('wallet');
        
        if (!empty($uid)) {
            $count = $Model->where(array(
                'uid' => $uid,
            ))->count();
            
            
            $pcount = 25;
            $Page = new \Think\Page($count, $pcount);                   
            $sql = "select * from hii_wallet where uid = {$uid} order by id desc limit {$Page->firstRow}, {$Page->listRows}";
            $list = M()->query($sql);            
        } else {
            $count = $Model->count(); 
            
            $pcount = 25;
            $Page = new \Think\Page($count, $pcount);       
            $sql = "select * from hii_wallet order by id desc limit {$Page->firstRow}, {$Page->listRows}";
            $list = M()->query($sql);            
        }
        

        if (empty($list)) {
            $list = array();
        }
        $show = $Page->show();
        $this->assign('list', $list);
        $this->assign('_page', $show ? $show : '');
        $this->assign('_total', $count);        
        
        
        
        
        
        $this->assign('list', $list);
        $this->meta_title = '帐户列表';
        $this->display(T('Addons://Wallet@Admin/Wallet/index'));
    }
    
    public function log(){
        $uid = I('uid', 0, 'intval');
        $type = I('type', '');
        $start_time = I('start_time');
        $end_time = I('end_time');        
        
     
        
        
        
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $Model = D('Addons://Wallet/WalletLog');
        $where = array();
        $uid > 0 && $where['uid'] = $uid;
        $type && $where['type'] = $type;
        

        
        
        if (!empty($start_time)) {
            $stime = strtotime($start_time);
            $where['create_time'] = array('egt', $stime);
        }
        
        if (!empty($end_time)) {
            $etime = strtotime($end_time) + 3600*24;
            $where['create_time'] = array('elt', $etime);           
        }           
        
        
        
        $list = $this->lists($Model, $where, 'create_time desc');
        $this->assign('list', $list);
        $_type = D('Addons://Wallet/WalletConfig')->get_type_data();
        $this->assign('type', $_type);
        $nickname = $uid ? get_nickname($uid) : '';
        $this->meta_title = '用户账户记录列表'. ($nickname ? (' 【'.$nickname.'】') : '');
        $this->display(T('Addons://Wallet@Admin/Wallet/log'));
    }
    
    
    public function download_log()
    {
        import("Org.Util.PHPExcel");
        vendor("excel.PHPExcel");
        vendor("excel.IOFactory");
        date_default_timezone_set('Asia/Shanghai');
        $objPHPExcel = new \PHPExcel();
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);        
        
        
        $uid = I('uid');
        $start_time = I('start_time');
        $end_time = I('end_time');
        
        
        $where = "1=1";
        if (!empty($uid)) {
            $where .= " and uid = '{$uid}'";
        }
        
        
        if (!empty($start_time)) {
            $stime = strtotime($start_time);
            $where .= " and wl.create_time >= {$stime}";
        }
        
        if (!empty($end_time)) {
            $etime = strtotime($end_time) + 3600*24;
            $where .= " and wl.create_time <= {$etime}";            
        }
        
        
        
        
        
        $sql = "select wl.*,m.nickname,o.pay_type, r.type as rpay from hii_wallet_log wl 
        left join hii_member m on m.uid = wl.uid 
        left join hii_order o on o.order_sn = wl.action_sn 
        left join hii_recharge_order r on r.order_sn = wl.action_sn 
        where {$where} order by wl.create_time asc limit 99999";
        

        $types = D('Addons://Wallet/WalletConfig')->get_type_data();

        $datas = M()->query($sql);


        
        
        foreach ($datas as $key => $val) {
            if (in_array($val['action'], $types)) {
                $datas[$key]['action_type'] = $types[$val['action']];
            } else {
                if ($val['type'] == 1) {
                    $datas[$key]['action_type'] = '订单收益';
                } elseif ($val['type'] == 2) {
                    $datas[$key]['action_type'] = '提现';
                } elseif ($val['type'] == 3) {
                    $datas[$key]['action_type'] = '充值';
                } elseif ($val['type'] == 4) {
                    $datas[$key]['action_type'] = '消费';
                } elseif ($val['type'] == 5) {
                    $datas[$key]['action_type'] = '赠送';
                }
            }
        }        
        
        
        
        $num = 1;
        
        $objActSheet ->setCellValue('A' . $num, '用户');
        $objActSheet ->setCellValue('B' . $num, '用户ID');
        $objActSheet ->setCellValue('C' . $num, '类型');
        $objActSheet ->setCellValue('D' . $num, '金额');        
        $objActSheet ->setCellValue('E' . $num, '方式');        
        $objActSheet ->setCellValue('F' . $num, '订单号');        
        $objActSheet ->setCellValue('G' . $num, '操作时间');        
        $objActSheet ->setCellValue('H' . $num, '订单支付方式');        
        
        $objActSheet->getColumnDimension('A')->setWidth(28);
        $objActSheet->getColumnDimension('B')->setWidth(18);
        $objActSheet->getColumnDimension('C')->setWidth(18);
        $objActSheet->getColumnDimension('D')->setWidth(18);
        $objActSheet->getColumnDimension('E')->setWidth(18);
        $objActSheet->getColumnDimension('F')->setWidth(28);
        $objActSheet->getColumnDimension('G')->setWidth(28);
        $objActSheet->getColumnDimension('H')->setWidth(28);

        
        $num++;
        
        foreach($datas as $key => $val){
            if ($val['action'] == 'order_deal') {
                $pay_type_name = $val['pay_type'] == 1 ? '微信' : '支付宝';
            } else if ($val['action'] == 'recharge') {
                $pay_type_name = $val['rpay'] == 1 ? '微信' : '支付宝';
            } else {
                $pay_type_name = '';
            }
            
            
            //写入数据
            $objActSheet ->setCellValue('A'.$num, $val['nickname']);
            $objActSheet ->setCellValue('B'.$num, $val['uid']);
            $objActSheet ->setCellValue('C'.$num, in_array($val['type'], array(1, 3, 5)) ? '增加' : '减少');
            $objActSheet ->setCellValue('D'.$num, $val['money']);
            $objActSheet ->setCellValue('E'.$num, $val['action_type']);
            $objActSheet ->setCellValue('F'.$num, $val['action'] == 'order_deal' ? $val['action_sn'] : '');
            $objActSheet ->setCellValue('G'.$num, date('Y-m-d H:i:s', $val['create_time']));
            $objActSheet ->setCellValue('H'.$num, $pay_type_name);
            $num++;
        }      
        
        $fname = '钱包记录';
        
        
        
        
        
        
        
        
        $objPHPExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fname.'.xlsx"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');//输出
        die;          
    }
    
    public function save(){
        $this->model = M('Wallet');
        $this->tpl = T('Addons://Wallet@Admin/Wallet/save');
        $this->callback_fun = 'set_save';
        parent::_save();
    }
    
    protected function set_save($data){
        $bind = M('WalletBind')->where(array('uid' => $data['uid']))->find();
        $bind && $bind['bind_data'] = json_decode($bind['bind_data'], true);
        $this->assign('bind', $bind);
        $bind_log = M('WalletBindLog')->where(array('uid' => $data['uid']))->order('create_time desc')->select();
        foreach($bind_log as $k => $v){
            $v['bind_data'] = json_decode($v['bind_data'], true);
            $bind_log[$k] = $v;
        }
        $this->assign('bind_log', $bind_log);
        return $data;
    }
    
    public function unbind_alipay(){
        $uid = I('uid', 0, 'intval');
        if(M('WalletBind')->where(array('uid' => $uid))->delete()){
            M('WalletBindLog')->where(array('uid' => $uid, 'act' => 1))->save(array('act' => 2, 'update_time' => NOW_TIME));
            $this->success('解绑成功');
        }else{
            $this->error('解绑失败');
        }
    }
}
