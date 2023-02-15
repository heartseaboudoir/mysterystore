<?php

namespace Addons\Alipay\Lib\User;
require dirname(dirname(dirname(__FILE__))).'/sdk/Api.php';
class Api extends \Api{
    
    public function oauth_token($token, $type = 'authorization_code'){
        $grant_type = array('authorization_code', 'refresh_token');
        if(!in_array($type, $grant_type)){
            return array('status' => 0);
        }
        $this->init();
        $aop = $this->c;
        $request = new \AlipaySystemOauthTokenRequest();
        $request->setGrantType($type);
        switch($type){
            case 'authorization_code':
                $request->setCode($token);
                break;
            case 'refresh_token':
                $request->setRefreshToken($token);
                break;
        }
        $result = $aop->execute ( $request); 

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(empty($result->error_response) && (empty($resultCode) || $resultCode == 10000)){
            $data = array(
                'user_id' => $result->$responseNode->user_id,
                'access_token' => $result->$responseNode->access_token,
                'expires_in' => $result->$responseNode->expires_in,
                'refresh_token' => $result->$responseNode->refresh_token,
                're_expires_in' => $result->$responseNode->re_expires_in,
            );
            return array('status' => 1, 'data' => $data);
        } else {
            return array('status' => -2);
        }
    }
    
    public function userinfo($auth_token){
        $this->init();
        $aop = $this->c;
        $request = new \AlipayUserUserinfoShareRequest();
        $result = $aop->execute ( $request, $auth_token); 

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(empty($result->error_response) && (empty($resultCode) || $resultCode == 10000)){
            $data = (array)$result->$responseNode;
            return array('status' => 1, 'data' => $data);
        } else {
            return array('status' => -2);
        }
    }
}