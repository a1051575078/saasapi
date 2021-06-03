<?php

namespace App\Models\Admin;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;

class Black extends Model
{
    //
    use UsesSystemConnection;
    protected $table='blacklists';
    protected $fillable=[
        'ip', 'address'
    ];
}
