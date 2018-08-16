<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Images extends Model
{
    protected $table = 'cloud_images';
    protected $keyType ='string';
    protected $primaryKey='im_code';
    public $incrementing=false;
    public $timestamps = false;

    public function getImageNoStatus($im_code){
        return $this
            ->leftjoin('cloud_groupos', 'cloud_images.im_gos_id', '=', 'cloud_groupos.gos_id')
            ->select(
                '*'
            )
            ->where(
                [
                    ['cloud_images.im_code', '=', $im_code],
                ]
            )
            ->get()
            ->first(); //get the first records
    }
}
