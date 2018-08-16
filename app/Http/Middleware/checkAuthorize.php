<?php

namespace App\Http\Middleware;

use Closure;
use DateTime;
use App\Http\Models\AccessToken;

class checkAuthorize
{
    public $SIGNED_REQUEST_ALGORITHM = 'sha256';
    public $headersRequest = array();

    public function handle($request, Closure $next)
    {
        //get Data from Headers
        $this->headersRequest = $this->getDataHeaders();
        //validate Headers data
        $check_header=$this->checkDataHeaders();
        if ($check_header->status()!=202) return $check_header;
        //check AuthorizeTokenClientID
        $check_auth_token_client_id=$this->isAuthorizeTokenClientID();
        if ($check_auth_token_client_id->status()!=202) return $check_auth_token_client_id;
        //check AuthorizeTokenUser
//        $check_auth_token_user=$this->isAuthorizeTokenUser();
//        if ($check_auth_token_user->status()!=202) return $check_auth_token_user;
        return $next($request);
    }



//    public function isAuthorizeTokenUser() {
//        //phần này là chứng thực token bên id(a khang)
//        //header data example:  Tokenuser c05fcbbd6abbb9a522f939559fd3639a
//        //(oauth_access_token.token) (expired in ++)
//            $fsso = new FSSOClient();
//            $fsso->setAccessToken($this->headersRequest['Tokenuser']);
//            $result = $fsso->oauthRequest($fsso::USER_INFO_URL, null);
//        return response(serialize($result), 403);
//            if($result){
//                if(isset($result->data->id)){
//                    $this->userInfoFromId = $result->data;
//                    $this->modelUser = Users::model()->findByPk($result->data->id);
//                    if(!$this->modelUser){
//                        return response('ERROR_1055', 403);
//                    }
//                    return response('ok', 202);
//                }elseif(isset($result->code)){//nếu có lỗi
//
//                    if($result->code == 440){//nếu là 440 là token hết hạn (id thông báo về)
//                        return response('ERROR_1056', 403);
//                    }elseif($result->code = 1){
//                        return response('ERROR_1061', 403);
//                    }
//                }else{
//                    return response('ERROR_1052', 403);
//                }
//            }else{
//                return response('ERROR_1057', 403);
//            }
//
//    }


    public function getDataHeaders() {
        //get data request trong phần headers
        $headers=[];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function checkDataHeaders()
    {
        //get data from headers
        try{
            if(!isset($this->headersRequest['Tokenclient']))
            {
                return response('Tokenclient required in data headers', 401);
            }
            if(!isset($this->headersRequest['Signature']))
            {
                return response('Signature required in data headers', 401);
            }
            if(!isset($this->headersRequest['Tokenuser']))
            {
                return response('Tokenuser required in data headers', 401);
            }
        }
        catch (Exception $e) {
            return response('Fail to get data headers', 401);
        }
        return response('Data from Headers is validated', 202);
    }

    public function isAuthorizeTokenClientID()
    {
        //phần này là chứng thực token bên mình
        //test header data:
        //header:   Tokenclient 5npjlDKj61dRYf8s1zl3RFc7ANzBMEK0yrCgtVuSFigNHgduF4eXboECkvisH6xZnnc6Z6DjBrGBN9HpHquIS1s0r6pd0qHPBc9OS98ETtthhUxc6G9mYuEfXzTPFDey
        //          Signature   553bc6b58fc37875bcaad7afebe85cad137654d523a517e7d6db220eca45e849

        //search Token with Tokenclient

        try {
            $token = $this->headersRequest['Tokenclient'];
            $signature = (string)$this->headersRequest['Signature'];
            $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $Accesstoken = new Accesstoken();
            $resultToken = $Accesstoken->searchToKen($token, $ipAddress);
            if (!$resultToken) return response('Cannot find the Access Token', 401);
        } catch (Exception $e) {
            return response('Fail to get the Access Token', 401);
        }


        //check the Expiration time
        try {
            $date = new DateTime($resultToken['created_time']);
            date_add($date, date_interval_create_from_date_string($resultToken['expires_in'] . ' seconds'));
            $dateNow = new DateTime('NOW');
            if ($dateNow > $date) {
                //if dateNow>date -> this token has been expired
                return response($date->format('Y-m-d H:i:s') . ' Access Token have been expired', 401);
            }
        } catch (Exception $e) {
            return response('Fail to check the Expiration time', 401);
        }

        //check the Signature
        try {
            $signaturecompare = hash_hmac($this->SIGNED_REQUEST_ALGORITHM, $this->headersRequest['Tokenclient'],
                $resultToken['client_id'] . '|' . $resultToken['client_secret']);
            if ($signature != $signaturecompare) {
                return response('The Signature does not match', 401);
            }
        } catch (Exception $e) {
            return response('Fail to compare the Signature', 401);
        }

        return response('Authorize Token ClientId accepted', 202);

    }


}
