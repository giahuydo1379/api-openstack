<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class AccessToken extends Model
{
    protected $table = 'cloud_access_token';
    protected $keyType ='string';
    protected $primaryKey='token';
    public $incrementing=false;
    public $timestamps = false;

    public function searchToKen($token,$ipAddress,$status_accessToken=TRUE,$status_client=TRUE){

        return $this
            ->leftjoin('cloud_identity_client', 'cloud_identity_client.client_id', '=', 'cloud_access_token.client_id')
            ->select(
                'cloud_identity_client.client_id',
                'cloud_identity_client.client_secret',
                'expires_in',
                'created_time'
            )
            ->where(
                [
                    ['token', '=', $token],
                    ['ipaddress', '=', $ipAddress],
                    ['status', '=', $status_accessToken],
                    ['client_status', '=', $status_client],
                ]
            )
            ->get()
            ->first(); //get the first records
    }


    public function add($data) {
        $date = new \DateTime('NOW');
        $model = new AccessToken();
        $model->token = $data['token'];
        $model->client_id = $data['client_id'];
        $model->expires_in = 3600 * 24;
        $model->created_time = $date->format('Y-m-d H:i:s');
        $model->ipaddress = $data['ip_address'];
        $model->status = 1;
        if ($model->save()) {
            return array(
                'access_token_client' => $model->token,
                'create_time' => $model->create_time,
                'expires_in' => $model->expires_in
            );
        }
        return null;
    }

}