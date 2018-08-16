<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Vms extends Model
{
    protected $table = 'cloud_vms';
    protected $keyType ='string';
    protected $primaryKey='vm_code';
    public $incrementing=false;
    public $timestamps = false;

    public function list($user_id){
        return $this
            ->leftjoin('cloud_packages', 'cloud_vms.vm_package_id', '=', 'cloud_packages.pk_id')
            ->leftjoin('cloud_flavors', 'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->select(
                'vm_code',
                'vm_os_name',
                'vm_name_show',
                'vm_os_code',
                'vm_status',
                'vm_create_date',
                'vm_expires_in',
                'vm_location_id',
                'vm_package_id',
                'flavor_ram',
                'flavor_cpu',
                'flavor_disk',
                'vm_update_status_date',
                'vm_delete'
            )
            ->where(
                [
                    ['vm_user_id', '=', $user_id],
                    ['cloud_vms.vm_delete', '=', 0],
                ]
            )
            ->get();
    }

    public function listdetailbyuser($user_id){
        return $this
            ->leftjoin('cloud_packages',
                'cloud_vms.vm_package_id', '=', 'cloud_packages.pk_id')
            ->leftjoin('cloud_flavors',
                'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->leftjoin('cloud_images',
                DB::raw("cloud_vms.vm_os_code collate utf8_general_ci "),
                DB::raw("cloud_images.im_code collate utf8_general_ci "))
            ->leftjoin('cloud_ipvms as public_ip',function ($join) {
                $join->on('public_ip.ipvm_vm_code', '=', 'cloud_vms.vm_code')
                    ->where('public_ip.ipvm_public_private', '=', 0);
            })
            ->leftjoin('cloud_ipvms as private_ip',function ($join) {
                $join->on('private_ip.ipvm_vm_code', '=', 'cloud_vms.vm_code')
                    ->where('private_ip.ipvm_public_private', '=', 1);
            })
            ->select(
                'vm_code',
                'vm_os_name',
                'vm_name_show',
                'vm_os_code',
                'vm_status',
                DB::raw(" case when (im_gos_id =1) Then 'admin' else 'root' end as vm_user"),
                'vm_pass',
                'public_ip.ipvm_ip as public_ip',
                'private_ip.ipvm_ip as private_ip',
                'vm_create_date',
                'vm_expires_in',
                'vm_location_id',
                'vm_package_id',
                'pk_name',
                'flavor_ram',
                'flavor_cpu',
                'flavor_disk',
                'vm_update_status_date',
                'vm_delete'
            )
            ->where(
                [
                    ['vm_user_id', '=', $user_id],
                    ['cloud_vms.vm_delete', '=', 0],
                ]
            )
            ->get();
    }

    public function detail($vm_code){
        return $this
            ->leftjoin('cloud_packages',
                'cloud_vms.vm_package_id', '=', 'cloud_packages.pk_id')
            ->leftjoin('cloud_users',
                'cloud_vms.vm_user_id', '=', 'cloud_users.user_id')
            ->leftjoin('cloud_flavors',
                'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->leftjoin('cloud_images',
                DB::raw("cloud_vms.vm_os_code collate utf8_general_ci "),
                DB::raw("cloud_images.im_code collate utf8_general_ci "))
            ->leftjoin('cloud_ipvms as public_ip',function ($join) {
                $join->on('public_ip.ipvm_vm_code', '=', 'cloud_vms.vm_code')
                    ->where('public_ip.ipvm_public_private', '=', 0);
            })
            ->leftjoin('cloud_ipvms as private_ip',function ($join) {
                $join->on('private_ip.ipvm_vm_code', '=', 'cloud_vms.vm_code')
                    ->where('private_ip.ipvm_public_private', '=', 1);
            })
            ->leftjoin('cloud_locations',
                'cloud_vms.vm_location_id', '=', 'cloud_locations.location_id')
            ->select(
                'vm_code',
                'vm_os_name',
                'vm_name_show',
                'vm_os_code',
                'vm_status',
                DB::raw(" case when (im_gos_id =1) Then 'admin' else 'root' end as vm_user"),
                'vm_pass',
                'public_ip.ipvm_ip as public_ip',
                'private_ip.ipvm_ip as private_ip',
                'vm_create_date',
                'vm_expires_in',
                'vm_location_id',
                'vm_package_id',
                'pk_name',
                'flavor_ram',
                'flavor_cpu',
                'flavor_disk',
                'vm_update_status_date',
                'vm_delete',
                'user_id',
                'user_email',
                'user_phone',
                'user_first_name',
                'user_address',
                'user_created_time',
                'user_status',
                'location_id',
                'location_name',
                'location_name_show'
            )
            ->where(
                [
                    ['vm_code', '=', $vm_code],
//                    ['vm_user_id', '=', $user_id],
                    ['cloud_vms.vm_delete', '=', 0],
                ]
            )
            ->first();
    }

    public function searchAll($filters = array())
    {
        $sql = self::select(
            'vm_code',
            'vm_os_name',
            'vm_name_show',
            'vm_os_code',
            'vm_status',
            DB::raw(" case when (im_gos_id =1) Then 'admin' else 'root' end as vm_user"),
            'vm_pass',
            'public_ip.ipvm_ip as public_ip',
            'private_ip.ipvm_ip as private_ip',
            'vm_create_date',
            'vm_expires_in',
            'vm_location_id',
            'vm_package_id',
            'pk_name',
            'flavor_ram',
            'flavor_cpu',
            'flavor_disk',
            'vm_update_status_date',
            'vm_delete'
        )
            ->leftjoin('cloud_packages',
                'cloud_vms.vm_package_id', '=', 'cloud_packages.pk_id')
            ->leftjoin('cloud_flavors',
                'cloud_packages.pk_flavor_id', '=', 'cloud_flavors.flavor_id')
            ->leftjoin('cloud_images',
                DB::raw("cloud_vms.vm_os_code collate utf8_general_ci "),
                DB::raw("cloud_images.im_code collate utf8_general_ci "))
            ->leftjoin('cloud_ipvms as public_ip', function ($join) {
                $join->on('public_ip.ipvm_vm_code', '=', 'cloud_vms.vm_code')
                    ->where('public_ip.ipvm_public_private', '=', 0);
            })
            ->leftjoin('cloud_ipvms as private_ip', function ($join) {
                $join->on('private_ip.ipvm_vm_code', '=', 'cloud_vms.vm_code')
                    ->where('private_ip.ipvm_public_private', '=', 1);
            });

        if (isset($filters['vm_code'])) {
            $keyword = $filters['vm_code'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('vm_code', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (isset($filters['vm_name_show'])) {
            $keyword = $filters['vm_name_show'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('vm_name_show', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (isset($filters['created_time_from'])) {
            $keyword = $filters['created_time_from'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('vm_create_date', '>=', $keyword);
            });
        }
        if (isset($filters['created_time_to'])) {
            $keyword = $filters['created_time_to'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('vm_create_date', '<=', $keyword);
            });
        }

        if (isset($filters['ip'])) {
            $keyword = $filters['ip'];
            $sql->where(function ($query) use ($keyword) {
                $query->whereRaw('(public_ip.ipvm_ip LIKE '. "'%" . $keyword . "%' OR private_ip.ipvm_ip LIKE ". "'%" . $keyword . "%' )");
            });
        }
        $total = $sql->count();

//        if (!empty($keyword = $filters['order'])) {
//            $sql->orderBy($filters['order']);
//        }
        $data = $sql->skip($filters['offset'])
            ->take($filters['limit'])
            ->orderBy($filters['sort'], $filters['order'])
            ->get();

        return ['total' => $total, 'data' => $data];
    }

}