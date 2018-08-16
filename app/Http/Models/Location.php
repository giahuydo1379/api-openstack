<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Location extends Model
{
    protected $table = 'cloud_locations';
    protected $keyType ='integer';
    protected $primaryKey='location_id';
    public $incrementing=false;
    public $timestamps = false;

}