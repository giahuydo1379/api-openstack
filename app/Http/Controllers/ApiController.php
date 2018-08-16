<?php

namespace App\Http\Controllers;
use App\Http\Models\MailQueue;

class ApiController extends Controller
{

    public function __construct()
    {
        //$this->middleware('check.authorize');
    }

    public function sendRequest($uri, $params, $method = 'POST', $headers = null)
    {

        $dataString = json_encode($params);
        //echo $dataString;
        $ch = curl_init();
        $method = strtoupper($method);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        } else if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        } else if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);

        $result = curl_exec($ch);
        $getinfoCurl = curl_getinfo($ch);

        $responseState = array('state' => FALSE);

        //decode nếu giá trị trả về là json
        if ($this->isJson($result)) {
            $result = json_decode($result, true);
        }

        //var_dump(curl_error($ch));die;
        if (curl_error($ch) != "") {//lỗi curl
            $responseState['error'] = 'The system is error please try again later.';
        } else if ($getinfoCurl['http_code'] >= 400) {//có lỗi khi gọi api openstack
            $responseState['error'] = $result;
        } else {
            $responseState['state'] = TRUE;
            //$responseState['http_code']=$getinfoCurl['http_code'];
            $responseState['param'] = $result;
        }
        curl_close($ch);

        return $responseState;
    }

    protected function isJson($string)
    {
        return is_string($string) && is_object(json_decode($string)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    public function responseError($msg_error){
        return response()->json([
            'state' => FALSE,
            'error' => $msg_error
        ])
            ->header('Content-Type', 'application/json');
    }
    public function responseSuccess($param){
        return response()->json([
            'state' => true,
            'param' => $param
        ])
            ->header('Content-Type', 'application/json');
    }

    public function resError($msg_error, $data = null, $code = 400){
        return response()->json([
            'code' => $code,
            'msg' => $msg_error,
            'data' => $data
        ])
            ->header('Content-Type', 'application/json');
    }
    public function resSuccess($msg, $data = null, $code = 200){
        return response()->json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ])
            ->header('Content-Type', 'application/json');
    }

    function generateString($length = 10) {
        $chars = array_merge(range(0, 9), range('a', 'z'));
        shuffle($chars);
        $string = implode(array_slice($chars, 0, $length));
        //them dau '_' vao chuoi
        for($i = 4;$i < $length;$i = $i+4)
        {
            if($i==4)
                $stringOut = $string;
            $stringOut = substr_replace($stringOut,"-",$i,0);
            $i++;
        }
        return $stringOut;
    }

    public function isPrivateIp($ip)
    {

        $i = explode('.', $ip);
        if ($i[0] == 10) {
            return true;
        } else if ($i[0] == 172 && $i[1] > 15 && $i[1] < 32) {
            return true;
        } else if ($i[0] == 192 && $i[1] == 168) {
            return true;
        }
        return false;
    }

    public function SaveMail($mailaddress,$infoMail=null,$type=1)
    {
        $MailQueue = new MailQueue();
        switch ($type) {
            case 1:
                $MailQueue->subject = '[Public Cloud FPT] Thông báo về việc: Tạo máy chủ ảo thành công.';
                $MailQueue->layout = 'detailserver';
                break;
            case 2:
                $MailQueue->subject = '[Public Cloud FPT] Thông báo về việc: Tạo máy chủ ảo thành công.';
                $MailQueue->layout = 'detailserver';
                break;
            default:
                $MailQueue->subject = '[Public Cloud FPT] Unknown subject.';
                $MailQueue->layout = 'detailserver';
        }

        $MailQueue->client_email = $mailaddress;
        $MailQueue->status = 0;
        $MailQueue->message = 'param';
        $MailQueue->param = json_encode($infoMail);
        $MailQueue->created_date = date('Y-m-d H:i:s');

        if (!$MailQueue->save()) {
            return false;
        }

        return true;
    }

    public function SaveMailTest($mailaddress,$infoMail=null,$type=1)
    {
        $MailQueue = new MailQueue();
        switch ($type) {
            case 1:
                $MailQueue->subject = '[Public Cloud FPT] Thông báo về việc: Tạo máy chủ ảo thành công.';
                $MailQueue->layout = 'detailserver';
                break;
            case 2:
                $MailQueue->subject = '[Public Cloud FPT] Thông báo về việc: Tạo máy chủ ảo thành công.';
                $MailQueue->layout = 'detailserver';
                break;
            default:
                $MailQueue->subject = '[Public Cloud FPT] Unknown subject.';
                $MailQueue->layout = 'detailserver';
        }
        if (!isset($infoMail)){
            $infoMail= array(
                'userName' => 'testdata',
                'packageName' => '100kg',
                'userOs' =>  'admin',
                'password' => "khongcopassword"
            );

        }

        $MailQueue->client_email = $mailaddress;
        $MailQueue->status = 0;
        $MailQueue->message = 'param';
        $MailQueue->param = json_encode($infoMail);
        $MailQueue->created_date = date('Y-m-d H:i:s');

        if (!$MailQueue->save()) {
            return $this->resError('Error. Not save');
        }

        return $this->resSuccess('Saved.');
    }



}
