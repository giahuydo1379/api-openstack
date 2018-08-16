<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Users extends Model
{
    protected $table = 'cloud_users';
    protected $keyType = 'integer';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    public function edit($id, $data)
    {
        $object = Users::select()
            ->where('user_id', $id)
            ->first();
        if ($object == null) return null;
        $data = $this->formatData($data);
        $this->filterColumns($data, $object);
        $object->user_update_time = date('Y-m-d H:i:s');
        if ($object->save()) {
            return $object;
        }
    }

    public function getUser($id)
    {
        $select = Users::select()
            ->where('user_id', $id);
        return $select->first();
    }

    public function detail($id)
    {
        return $this
            ->leftjoin('cloud_vms',
                'cloud_vms.vm_user_id', '=', 'cloud_users.user_id')
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
            })
            ->leftjoin('cloud_locations',
                'cloud_vms.vm_location_id', '=', 'cloud_locations.location_id')
            ->select(
                'user_id',
                'user_email',
                'user_phone',
                'user_first_name',
                'user_last_name',
                'user_gender',
                'user_address',

                'user_created_time',
                'user_update_time',
                'user_status',


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
                'location_name_show',
                'vm_code',
                'vm_os_name',
                'vm_name_show',
                'vm_os_code',
                'vm_status'
            )
            ->where(
                [
                    ['user_id', '=', $id],
//                    ['vm_user_id', '=', $user_id],

                ]
            )
            ->first();
    }


    public function add($data)
    {
        $object = new Users();
        $data = $this->formatData($data);
        $this->filterColumns($data, $object);
        $object->user_created_time = date('Y-m-d H:i:s');
        $object->user_update_time = date('Y-m-d H:i:s');
        $object->user_money = 0;
        $object->user_status = 1;
        if ($object->save()) {
            $id = $object->{$this->primaryKey};
            return Users::find($id);
        }
        return null;
    }

    public function formatData($data)
    {
        $dataFormat = array();
        if (isset($data['id'])) {
            $dataFormat['user_id'] = $data['id'];
        }
        if (isset($data['email'])) {
            $dataFormat['user_email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $dataFormat['user_phone'] = $data['phone'];
        }
        if (isset($data['first_name'])) {
            $dataFormat['user_first_name'] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $dataFormat['user_last_name'] = $data['last_name'];
        }
        if (isset($data['gender'])) {
            $dataFormat['user_gender'] = $data['gender'];
        }
        if (isset($data['dob'])) {
            $dataFormat['user_dob'] = $data['dob'];
        }
        if (isset($data['address'])) {
            $dataFormat['user_address'] = $data['address'];
        }
        $dataFormat['user_update_time'] = date('Y-m-d H:i:s');
        return $dataFormat;
    }

    public function getColumnsInTable()
    {
        $columns = array();
        $columnObjects = DB::select("DESCRIBE {$this->table}");
        foreach ($columnObjects as $columnObject) {
            $columns[] = $columnObject->Field;
        }
        return $columns;
    }

    public function filterColumns($data, &$object)
    {
        $dataFormat = array();

        $columns = $this->getColumnsInTable();

        foreach ($data as $column => $value) {
            if (in_array($column, $columns)) {
                $object->$column = $value;
                $dataFormat[$column] = $value;
            }
        }
        return $dataFormat;
    }

    public function searchAll($filters = array())
    {
        $sql = self::select(
            "user_id",
            "user_email",
            "user_phone",
            "user_first_name",
//            "user_last_name",
//            "user_gender",
//            "user_dob",
            "user_address",
            "user_created_time",
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
        );

        if (isset($filters['email'])) {
            $keyword = $filters['email'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('user_email', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (isset($filters['name'])) {
            $keyword = $filters['name'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('user_first_name', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (isset($filters['phone'])) {
            $keyword = $filters['phone'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('user_phone', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (isset($filters['address'])) {
            $keyword = $filters['address'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('user_address', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (isset($filters['created_time_from'])) {
            $keyword = $filters['created_time_from'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('user_created_time', '>=', $keyword);
            });
        }
        if (isset($filters['created_time_to'])) {
            $keyword = $filters['created_time_to'];
            $sql->where(function ($query) use ($keyword) {
                $query->where('user_created_time', '<=', $keyword);
            });
        }

        $total = $sql->count();

        $data = $sql->skip($filters['offset'])
            ->take($filters['limit'])
            ->orderBy($filters['sort'], $filters['order'])
            ->get();

        return ['total' => $total, 'data' => $data];
    }

}