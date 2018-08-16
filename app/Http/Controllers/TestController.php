<?php

namespace App\Http\Controllers;
use App\Http\Models\Users;

//this controller only use for TEST
class TestController extends ServerController
{

    public function __construct()
    {
        //$this->middleware('check.authorize');
    }

//    public function Test()
//    {
//        $email='ltdungrs@gmail.com';
//        $MailQueueJob=new MailQueueJob($email);
//        $MailQueueJob::dispatch()->onQueue('sendmail');
//        return 'ok';
//    }


    public function TestGetAccessOpenstack($userid)
    {
//        return 'aa';
        $userModel=Users::find($userid);
        if (($userModel['user_tenant_id'] == NULL) || !isset($userModel['user_tenant_id'])) {
            $result = $this->GetAccessToKenOpenStack();
        }
        else{
            $result = $this->GetAccessToKenOpenStack($userModel['user_tenant_id']);
        }


        $this->headers = array(
            'X-Auth-Token: ' . $result['param']['token']['id'],
            'Content-Type: application/json'
        );

        //get the new tenant id
        $uri = env("OPENSTACK_TENANT") . $userModel["user_id"];
        $result = $this->sendRequest($uri, NULL, "POST", $this->headers);

        return $this->responseError($result);

    }


}