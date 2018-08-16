<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class GroupOS extends Model
{
    protected $table = 'cloud_groupos';
    protected $primaryKey='pk_id';
    public $incrementing=false;
    public $timestamps = false;


    public function list($gos_active =true,$im_status = true)
    {
        return $this
            ->leftjoin('cloud_images', 'gos_id', '=', 'im_gos_id')
            ->select(
                'gos_id',
                'gos_name',
                'gos_image',
                'gos_position',
                'im_name',
                'im_code'
            )
            ->where(
                [
                    ['gos_active', '=', $gos_active],
                    ['im_status', '=', $im_status],
                ]
            )
            ->get();

    }


}
