<?php    
namespace Addons\Alipay\Lib\Zhima;
require_once dirname(dirname(dirname(__FILE__))).'/sdk/Api.php';
class Api extends \Api{
    
    public function score_get($score, $no, $name = '', $type = 'IDENTITY_CARD', $get_new = false){
        $in_type = array('IDENTITY_CARD', 'PASSPORT', 'ALIPAY_USER_ID');
        if(!in_array($type, $in_type)){
            return array('status' => -1);
        }
        $score = intval($score);
        if($score > 950 || $score < 350){
            return array('status' => -1);
        }
        $score_str = sprintf('%04d', $score);
        $md5_str = md5($no + $score);
        $transaction_id = $md5_str.date('ymd').$score_str;
        $this->init();
        $aop = $this->c;
        $request = new \ZhimaCreditScoreBriefGetRequest ();
        $BizContent = array(
            'transaction_id' => $transaction_id,
            'product_code' => 'w1010100000000002733',
            'cert_type' => $type,
            'cert_no' => $no,
            'name' => $name,
            'admittance_score' => $score
        );
        $request->setBizContent(json_encode($BizContent));
        $result = $aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        
        $log_param = array();
        $log_param['cert_type'] = $BizContent['cert_type'];
        $log_param['cert_no'] = $BizContent['cert_no'];
        $log_param['name'] = $BizContent['name'];
        $log_param['admittance_score'] = $BizContent['admittance_score'];
        $log_data = array(
            'transaction_id' => $BizContent['transaction_id'],
            'product_code' => $BizContent['product_code'],
            'param' => json_encode($log_param),
            'create_time' => NOW_TIME,
            'resultCode' => $result->$responseNode->code,
            'resultData' => json_encode($result->$responseNode)
        );
        M('AlipayZhimaLog')->add($log_data);
        
        if(!empty($resultCode)&&$resultCode == 10000){
            switch($result->$responseNode->is_admittance){
                case 'Y':
                    return array('status' => 1);
                case 'N':
                    return array('status' => 0);
                case 'N/A':
                    return array('status' => 2);
            }
            return array('status' => 0);
        } else {
            return array('status' => -2);
        }
    }
    private function out_format($data){
        return json_decode(json_encode($data), true);
    }
    
    public function antifraud_verify($name, $cert_no, $param = array(), $cert_type = 'IDENTITY_CARD'){
        $in_type = array('IDENTITY_CARD');
        if(!in_array($cert_type, $in_type)){
            return array('status' => -1, 'msg' => '验证类型不存在');
        }
        if(!trim($name) || strlen($name) > 64){
            return array('status' => -1, 'msg' => '姓名长度不能超过64位');
        }
        if(!check_idcard($cert_no)){
            return array('status' => -1, 'msg' => '身份证号码格式不正确');
        }
        $md5_str = md5($cert_no.$name);
        $transaction_id = $md5_str.date('ymd');
        $this->init();
        $aop = $this->c;
        $request = new \ZhimaCreditAntifraudVerifyRequest();
        $BizContent = array(
            'transaction_id' => $transaction_id,
            'product_code' => 'w1010100000000002859',
            'cert_type' => $cert_type,
            'cert_no' => $cert_no,
            'name' => $name
        );
        $request->setBizContent(json_encode($BizContent));
        $result = $aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        
        $log_param = $param;
        $log_param['cert_type'] = $BizContent['cert_type'];
        $log_param['cert_no'] = $BizContent['cert_no'];
        $log_param['name'] = $BizContent['name'];
        $log_data = array(
            'transaction_id' => $BizContent['transaction_id'],
            'product_code' => $BizContent['product_code'],
            'param' => json_encode($log_param),
            'create_time' => NOW_TIME,
            'resultCode' => $result->$responseNode->code,
            'resultData' => json_encode($result->$responseNode)
        );
        M('AlipayZhimaLog')->add($log_data);
        if(!empty($resultCode) && $resultCode == 10000){
            $verify_code = $result->$responseNode->verify_code;
            if(in_array('V_CN_NM_MA', $verify_code)){
                return array('status' => 1, 'msg' => 'success');
            }elseif(in_array('V_CN_NA', $verify_code)){
                return array('status' => 0, 'msg' => '查询不到身份证信息');
            }elseif(in_array('V_CN_NM_UM', $verify_code)){
                return array('status' => 0, 'msg' => '姓名与身份证号不匹配');
            }else{
                return array('status' => 0, 'msg' => '查询失败');
            }
        } else {
            return array('status' => -2, 'msg' =>'查询不成功');
        }
        
    }
}