<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\Vms;
use App\Http\Models\Users;
use App\Http\Models\Location;
use App\Http\Models\Package;
use App\Http\Models\IpVms;
use App\Http\Models\MailQueue;
use PHPUnit\Runner\Exception;
use Validator;
use Illuminate\Validation\Rule;

class ServerInsightController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public $Vms;

    public function __construct()
    {
        //$this->middleware('check.authorize');
        $this->Vms = new Vms();
    }

    public function Detail($vmId)
    {
        try {
            $Vms = new Vms();
            $modelVM = $Vms->detail($vmId);
            if (!isset($modelVM['vm_code'])) {
                return $this->resError('Vm id is not found');
            }
            return $this->resSuccess('successful', $modelVM);


        } catch (Exception $exc) {
            return $this->resError('Error exception', 500);
        }
    }

    public function DetailCustomer($userId)
    {
        try {
            $User = new Users();
            $modelUser = $User->detail($userId);
            if (!isset($modelUser['user_id'])) {
                return $this->resError('User id is not found');
            }
            return $this->resSuccess('successful', $modelUser);

        } catch (Exception $exc) {
            return $this->resError('Error exception', 500);
        }
    }


    //this function use to test
    public function Search(\Illuminate\Http\Request $request)
    {
        $validator = Validator::make($request->all(),
            [

                'vm_code' => 'string|max:100',
                'vm_name_show' => 'string|max:200',
                'ip' => 'string|max:50',
                'created_time_from' => 'Date',
                'created_time_to' => 'Date',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resError($errors);
        }

        $filters = array(
            'offset' => (int)$request->input('offset', 0),
            'limit' => (int)$request->input('limit', 10),
            'sort' => $request->input('sort', 'vm_code'),
            'order' => $request->input('order', 'asc'),
            'vm_code' => $request->input('vm_code', null),
            'vm_name_show' => $request->input('vm_name_show', null),
            'ip' => $request->input('ip', null),
            'created_time_from' => $request->input('created_time_from', null),
            'created_time_to' => $request->input('created_time_to', null)
        );

        $data = $this->Vms->searchAll($filters);

        return $this->resSuccess('successful', $data);

    }
}