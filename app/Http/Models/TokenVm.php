<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Components\UriMapper;

class TokenVm extends Model
{
    protected $table = 'cloud_vms';
    protected $keyType ='string';
    protected $primaryKey='vm_code';
    public $incrementing=false;
    public $timestamps = false;
}