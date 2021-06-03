<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    //
    use UsesTenantConnection;
    protected $table='contacts';
    protected $fillable = [
        'name', 'fromid', 'blacklist','ip','address','recordnumber'
    ];
}
