<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    //
    use UsesTenantConnection;
    protected $table='evaluation';
    protected $fillable = [
        'user_id', 'good', 'nogood','content','visitors'
    ];
    //获取用户的姓名
    public function user(){
        return $this->belongsTo('App\Models\Tenant\User');
    }
}
