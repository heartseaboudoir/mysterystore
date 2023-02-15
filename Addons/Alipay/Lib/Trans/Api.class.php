<?php
namespace Addons\Alipay\Lib\Trans;
require_once dirname(dirname(dirname(__FILE__))).'/sdk/Api.php';

class Api extends \Api{
    
    public function toaccount($amount, $payee_type, $payee_account, $remark, $yee_real_name = '',  $yer_show_name = ''){
        $this->init();
        $aop = $this->c;
        $out_biz_no = date('ymdHis');
        
        switch($payee_type){
            case 1:
                $payee_type = 'ALIPAY_USERID';
                break;
            case 2:
                $payee_type = 'ALIPAY_LOGONID';
                break;
            default:
                return array('status' => 0);
        }
        $request = new \AlipayFundTransToaccountTransferRequest();
        $BizContent = array(
            'out_biz_no' => $out_biz_no,
            'payee_type' => $payee_type,
            'payee_account' => $payee_account,
            'amount' => (string)$amount,
            'payer_show_name' => $yer_show_name,
            'payee_real_name' => $yee_real_name,
            'remark' => $remark,
        );
        $Model = M('AlipayTransLog');
        $data = $BizContent;
        $data['status'] = 1;
        $data['create_time'] = NOW_TIME;
        $data['update_time'] = NOW_TIME;
        $result = $Model->add($data);
        if(!$result){
            return array('status' => 0);
        }
        $request->setBizContent(json_encode($BizContent));
        $req = $aop->execute ( $request); 
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $res_data = (array)$req->$responseNode;
        $resultCode = $res_data['code'];
        if(!empty($resultCode)&&$resultCode == 10000){
            $Model->where(array('id' => $result))->save(array('order_id' => $res_data['order_id'], 'pay_date' => $res_data['pay_date'], 'status' => 2, 'return_data' => json_encode($res_data)));
            return array('status' => 1, 'data' => array('order_id' => $res_data['order_id'], 'out_biz_no' => $out_biz_no));
        } else {
            $u_status = 3;
            switch($res_data['sub_code']){
                case 'SYSTEM_ERROR':
                    $status = 2;
                    $u_status = 4;
                    $msg = '';
                    break;
                case 'EXCEED_LIMIT_SM_AMOUNT':
                    $status = 2;
                    $msg = '';
                    break;
                case 'EXCEED_LIMIT_DM_AMOUNT':
                    $status = 2;
                    $msg = '';
                    break;
                case 'PERMIT_CHECK_PERM_LIMITED':
                    $status = 2;
                    $msg = '付款账户余额支付功能不可用';
                    break;
                case 'PAYEE_NOT_EXIST':
                    $status = 0;
                    $msg = '收款账号不存在';
                    break;
                case 'ACCOUNT_NOT_EXIST':
                case 'PAYER_DATA_INCOMPLETE':
                case 'PAYER_DATA_INCOMPLETE':
                case 'CERT_MISS_TRANS_LIMIT':
                case 'CERT_MISS_ACC_LIMIT':
                    $status = 2;
                    $msg = '根据监管部门的要求，需要付款用户补充身份信息才能继续操作';
                    break;
                case 'PERMIT_PAYER_LOWEST_FORBIDDEN':
                case 'PERMIT_PAYER_FORBIDDEN':
                    $status = 2;
                    $msg = '根据监管部门要求，付款方余额支付额度受限';
                    break;
                case 'PAYER_BALANCE_NOT_ENOUGH':
                    $status = 2;
                    $msg = '付款方余额不足';
                    break;
                case 'PERM_AML_NOT_REALNAME_REV':
                    $status = 0;
                    $msg = '根据监管部门的要求，需要收款用户补充身份信息才能继续操作';
                    break;
                case 'PAYER_STATUS_ERROR':
                    $status = 2;
                    $msg = '付款账号状态异常';
                    break;
                case 'PAYER_STATUS_ERROR':
                    $status = 2;
                    $msg = '付款方用户状态不正常';
                    break;
                case 'PAYEE_USER_INFO_ERROR':
                    $status = 0;
                    $msg = '支付宝账号和姓名不匹配，请确认姓名是否正确';
                    break;
                case 'PERMIT_NON_BANK_LIMIT_PAYEE':
                    $status = 0;
                    $msg = '未完善身份信息或未开立余额账户，无法收款';
                    break;
            }
            $u_data = array(
                'status' => $u_status,
                'return_data' => json_encode($res_data)
            );
            $Model->where(array('id' => $result))->save($u_data);
            return array('status' => $status, 'msg' => $msg, 'data' => array('out_biz_no' => $out_biz_no));
        }
    }
}