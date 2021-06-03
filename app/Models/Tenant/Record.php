<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    //
    use UsesTenantConnection;
    protected $table='record';
    protected $fillable = [
        'fromid', 'toid','user_id','rand','content','withdraw','isread','type'
    ];
    //获取用户的姓名
    public function user(){
        return $this->belongsTo('App\Models\Tenant\User');
    }
}
