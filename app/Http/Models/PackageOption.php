<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class PackageOption extends Model
{
    protected $table = 'cloud_package_option';
    protected $primaryKey='pko_id';
    public $incrementing=false;
    public $timestamps = false;

    public function getDetailPackage($packageOpId, $statusPackageOp = TRUE, $statusPackage = TRUE){

        $packageoptions = $this
            ->join('cloud_packages', 'pko_package_id', '=', 'cloud_packages.pk_id')
            ->join('cloud_flavors', 'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->select(
                'pk_id',
                'pk_name',
                'pk_description',
                'pk_create_date',
                'flavor_ram',
                'flavor_disk',
                'flavor_cpu',
                'flavor_code',
                'pko_id',
                'pko_day'
            )
            ->where(
                [
                    ['cloud_flavors.flavor_active', '=', '1'],
                    ['cloud_flavors.flavor_show', '=', '1'],
                    ['cloud_flavors.flavor_delete', '=', '0'],
                    ['pko_status', '=', $statusPackageOp],
                    ['cloud_packages.pk_status', '=', $statusPackage],
                    ['pko_id', '=', $packageOpId],
                ]
            )
            ->get()
            ->first(); //get the first records
        return $packageoptions;
    }

    public function get($cpu,$ram,$disk,$day)
    {
        return $this
            ->leftjoin('cloud_packages', 'pko_package_id', '=', 'cloud_packages.pk_id')
            ->leftjoin('cloud_flavors', 'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->select(
                'pko_id'
            )
            ->where(
                [
                    ['cloud_flavors.flavor_active', '=', 1],
                    ['cloud_flavors.flavor_show', '=', 1],
                    ['pko_status', '=', true],
                    ['cloud_flavors.flavor_cpu', '=', $cpu],
                    ['cloud_flavors.flavor_ram', '=', $ram],
                    ['cloud_flavors.flavor_disk', '=', $disk],
                    ['pko_day', '=', $day],
                ]
            )
            ->get()
            ->first();

    }

}
