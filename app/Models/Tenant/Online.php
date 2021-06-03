<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Online extends Model
{
    //
    use UsesTenantConnection;
    protected $table='onlines';
    protected $fillable = [
        'user_id', 'tag'
    ];
}
