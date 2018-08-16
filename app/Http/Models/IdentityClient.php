<?php
/**
 * Created by PhpStorm.
 * User: tuantruong
 * Date: 6/19/18
 * Time: 5:11 PM
 */

namespace App\Http\Models;


use Illuminate\Database\Eloquent\Model;

class IdentityClient extends Model
{
    protected $table = 'cloud_identity_client';
    protected $primaryKey = 'client_id';

    public function identity($data) {
        $select = IdentityClient::select()
            ->where([
                ['client_id', $data['client_id']],
                ['client_secret', $data['client_secret']],
                ['client_ipaddress', $data['ip_address']],
                ['client_status', true]
            ]);

        return $select->first();
    }


    public function genRnd($length = 32) {
        $stringToKen = '';
        $data = 'abcdefghijklmnopqrstuvxyzABCDEFGHIJKLMNOPQRSTUVXYZ0123456789';

        for ($i = 0; $i < $length; $i++) {
            $stringToKen .= $data[rand(0, strlen($data) - 1)];
        }
        return $stringToKen;
    }
}