<?php

namespace App\Http\Controllers;

use App\Http\Models\Users;
use App\Http\Models\Vms;
use Validator;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public $model;
    public $params = array();
    public $data = array();

    public function __construct()
    {
        $this->model = new Users();
        $this->params = \Request::all();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function Synchronous()
    {
        $this->data['state'] = true;
        $this->data['param'] = $this->model->edit($this->params['id'], $this->params);
        if ($this->data['param'] == null) {
            $this->data['param'] = $this->model->add($this->params);
        }
        return response()->json($this->data);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function postUserInfo($id)
    {
        $this->data['state'] = true;
        $this->data['param'] = $this->model->edit($id, $this->params);
        if ($this->data['param'] == null) {
            $this->data['state'] = false;
            $this->data['error'] = 'Không tìm thấy thống tin tài khoản.';
        }
        return response()->json($this->data);
    }

    public function List()
    {
        $ListUser = Users::select(
            "user_id",
            "user_email",
            "user_phone",
            "user_first_name",
            "user_last_name",
            "user_gender",
            "user_dob",
            "user_address",
//            "user_created_time",
//            "user_update_time",
//            "user_money",
//            "user_money_virtual",
//            "user_tenant_id",
//            "user_private_subnet_id",
//            "user_private_network_id",
            "user_trial",
//            "user_codeotp",
//            "user_codeotp_datetime",
//            "user_codeotp_used",
            "user_status",
            "user_type"
        )->paginate(10);
        return $this->resSuccess('success', $ListUser);
    }

    public function ListServer($id)
    {
        try {
            $Vms = new Vms();
            $ListVms = $Vms->listdetailbyuser($id);
            return $this->resSuccess('success', $ListVms);
        }
        catch(\Exception $e){
            return $this->resError('fail');
        }
    }

    public function Search(\Illuminate\Http\Request $request) {
        $validator = Validator::make($request->all(),
            [


                'email' => 'string|max:100',
                'phone' => 'string|max:100',
                'name'=> 'string|max:100',
                'address'=> 'string|max:100',
                'created_time_from' => 'Date',
                'created_time_to'=> 'Date',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resError($errors);
        }

        $filters = array(
            'offset'        => (int) $request->input('offset', 0),
            'limit'         => (int) $request->input('limit', 10),
            'sort'          => $request->input('sort', 'user_id'),
            'order'         => $request->input('order', 'asc'),
            'email'         => $request->input('email', null),
            'phone'         => $request->input('phone', null),
            'name'         => $request->input('name', null),
            'address'       => $request->input('address', null),
            'created_time_from'  => $request->input('created_time_from', null),
            'created_time_to'   => $request->input('created_time_to', null)
        );

//        return $this->resSuccess($filters);
        $data = $this->model->searchAll($filters);

        return $this->resSuccess('successful',$data);
    }

}
