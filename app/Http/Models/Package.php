<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Package extends Model
{
    protected $table = 'cloud_packages';
    protected $primaryKey='pk_id';
    public $incrementing=false;
    public $timestamps = false;


    public function getConfig($service_status=true)
    {
        return DB::table('cloud_service')
            ->select(
                'service_name',
                'service_value'
            )
            ->where(
                [
                    ['service_status', '=', $service_status],
//                    ['service_name', 'in', " ('ram','cpu','disk') "],
                ]
            )
            ->whereRaw("service_name in ('ram','cpu','disk') ")
            ->orderBy('service_value')
            ->get();

    }

    public function getDetailPackage( $packageId ){
        return $this
            ->leftjoin('cloud_flavors', 'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->select(
                'pk_id',
                'cloud_packages.pk_name',
                'pk_create_date',
                'pk_status',
                'pk_ordering',
                'flavor_id',
                'flavor_code',
                'flavor_name',
                'flavor_ram',
                'flavor_disk',
                'flavor_cpu'
            )
            ->where(
                [
                    ['pk_id', '=', $packageId],
                ]
            )
            ->first();

    }



}
