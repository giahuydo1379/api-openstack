<?php
/**
 * Created by PhpStorm.
 * User: tuantruong
 * Date: 6/19/18
 * Time: 5:05 PM
 */

namespace App\Http\Controllers;


use App\Http\Models\AccessToken;
use App\Http\Models\IdentityClient;

class IdentityController extends Controller
{
    public $data = array();
    public $params = array();
    public $model;
    public function __construct()
    {
        $this->params = \Request::all();
        $this->params['ip_address'] = \Request::server('SERVER_ADDR');
        $this->model = new IdentityClient();
    }

    public  function getAccessToken() {
        $rules = array(
            'client_id' => 'required',
            'client_secret' => 'required'
        );
        $validator = \Validator::make($this->params, $rules);
        if ($validator->fails()) {
            $this->data['state'] = false;
            $this->data['error'] = $validator->errors()->first();
            return response()->json($this->data);
        }
        $data = $this->model->identity($this->params);

        if ($data == null) {
            $this->data['state'] = false;
            $this->data['error'] = 'Not find account information';
        }else {
            $modelToken = new AccessToken();
            $this->params['token'] = $this->model->genRnd(128);
            $data = $modelToken->add($this->params);

            if ($data == null) {
                $this->data['state'] = false;
                $this->data['error'] = 'Server đang bận vui lòng thử lại trong ít phút.';
            } else {
                $this->data['state'] = true;
                $this->data['param'] = $data;
            }
        }
        return response()->json($this->data);
    }


}