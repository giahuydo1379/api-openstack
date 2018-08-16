<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class MailQueue extends Model
{
    protected $table = 'cloud_mail_queue';
    protected $keyType ='integer';
    protected $primaryKey='id';
    public $incrementing=false;
    public $timestamps = false;
}