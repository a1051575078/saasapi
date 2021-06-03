<?php

namespace App\Models\Admin;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    //
    use UsesSystemConnection;
    protected $table='logs';
    protected $fillable=[
        'ip', 'address','content'
    ];
}
