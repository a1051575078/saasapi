<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Black extends Model
{
    //
    use UsesTenantConnection;
    protected $table='blacks';
    protected $fillable = [
        'ip', 'address'
    ];
}
