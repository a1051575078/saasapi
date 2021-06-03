<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    //
    use UsesTenantConnection;
    protected $table='logs';
    protected $fillable = [
        'user_id', 'type', 'content','ip','address'
    ];
    //获取用户的姓名
    public function user(){
        return $this->belongsTo('App\Models\Tenant\User');
    }
}
