<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Shortcut extends Model
{
    //
    //
    use UsesTenantConnection;
    protected $table='shortcuts';
    protected $fillable = [
        'user_id', 'content', 'sort','title'
    ];
}
