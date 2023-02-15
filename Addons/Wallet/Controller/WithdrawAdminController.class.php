<?php
namespace Addons\Wallet\Controller;

use Admin\Controller\AddonsController;

class WithdrawAdminController extends AddonsController{
    
    public function __construct() {
        parent::__construct();
        $this->model = M('WalletWithdrawLog');
        $this->page_title = '提现申请';
        
    }
    
    public function index(){
        $where = array();
        $this->callback_fun = 'set_index';
        parent::_index($where, 'status asc, id desc');
    }
    
    protected function set_index($data){
        return $data;
    }
    
    public function save(){
        $this->meta_title = '查看提现申请';
        parent::_save();
    }
    
    public function action_apply(){
        $act = I('act', '');
        $id = I('id', 0, 'intval');
        if(!($id > 0)){
            $this->error('请选择记录');
        }
        $where = array('id' => $id,'status' => 1);
        if($act == 'y'){
            $status = 2;
        }else{
            $status = 3;
        }
        $Model = M('WalletWithdrawLog');
        $info = $Model->where($where)->find();
        if(!$info){
            $this->error('申请不存在');
        }
        if(!$Model->where($where)->save(array('status' => $status, 'udpate_time' => NOW_TIME))){
            $this->error('操作失败');
        }
        $WModel = D('Addons://Wallet/Wallet');
        if($status == 2){
            $bank_data = json_decode($info['bank_data'], true);
            $Api = new \Addons\Alipay\Lib\Trans\Api();
            $req_result = $Api->toaccount($info['money'], 1, $bank_data['bind_id'], '钱包提现', '');
            if($req_result['status'] != 1){
                $Model->where(array('id' => $info['id']))->save(array('status' => 1));
                $this->error($req_result['msg'], addons_url('Wallet://WithdrawAdmin/save', array('id' => $id)));
            }
            $WModel->clear_frozen($info['sn']);
            $this->success('提现成功',  Cookie('__forward__'));
        }else{
            $WModel->return_frozen($info['sn']);
            $this->success('拒绝成功',  Cookie('__forward__'));
        }
    }
}
