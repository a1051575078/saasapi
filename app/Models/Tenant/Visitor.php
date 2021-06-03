<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    //
    use UsesTenantConnection;
    protected $table='visitors';
    protected $fillable = [
        'ip', 'frequency', 'address','isphone','origin'
    ];
}
