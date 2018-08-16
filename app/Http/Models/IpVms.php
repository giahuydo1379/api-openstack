<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class IpVms extends Model
{
    protected $table = 'cloud_ipvms';
    protected $keyType ='integer';
    protected $primaryKey='ipvm_id';
    public $incrementing=false;
    public $timestamps = false;

    public function findbyVmcode($ipvm_vm_code){
        return $packageoptions = $this
            ->select(
                '*'
            )
            ->where(
                [
                    ['ipvm_vm_code', '=', $ipvm_vm_code],
                ]
            )
            ->get();
    }

    public function findbyVmcodeIp($ipvm_vm_code,$ipvm_ip){
        return $packageoptions = $this
            ->select(
                '*'
            )
            ->where(
                [
                    ['ipvm_vm_code', '=', $ipvm_vm_code],
                    ['ipvm_ip', '=', $ipvm_ip],
                    ['ipvm_delete', '<>', 2],
                ]
            )
            ->first();
    }

}