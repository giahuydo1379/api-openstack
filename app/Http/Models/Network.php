<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Network extends Model
{
    protected $table = 'cloud_network';
    protected $keyType ='string';
    protected $primaryKey='net_code';
    public $incrementing=false;
    public $timestamps = false;

    public function getListNetwork($net_status=true){
        $listNetwork=
            DB::table('cloud_network')
                ->select('net_code',
                    'net_limit_ip',

                    DB::raw(' (select count(*) from cloud_vms left join cloud_ipvms '.
                    'on cloud_vms.vm_code = cloud_ipvms.ipvm_vm_code  where cloud_vms.vm_net_code=net_code) as net_count_ip'))
            ->where('net_status', '=', $net_status)
            ->get();
        return json_decode($listNetwork,true);
    }
}