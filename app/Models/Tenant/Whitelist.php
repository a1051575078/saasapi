<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Whitelist extends Model
{
    //
    use UsesTenantConnection;
    protected $table='whitelists';
    protected $fillable = [
        'ip'
    ];
}
