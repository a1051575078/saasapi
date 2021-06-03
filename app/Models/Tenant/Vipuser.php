<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Vipuser extends Model
{
    //
    use UsesTenantConnection;
    protected $table='vipusers';
    protected $fillable = [
        'user','name', 'sex', 'phone','age','qq','wechat','ip','address','remarks'
    ];
}
