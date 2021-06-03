<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable,UsesTenantConnection,HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guard_name ='api';
    protected $fillable = [
        'name','avatar','token','music','content','is_server','email', 'password','updated_at','created_at','session'
    ];
    public function record()
    {
        return $this->hasOne('App\Models\Tenant\Record');
    }
    public function log()
    {
        return $this->hasOne('App\Models\Tenant\Log');
    }
    public function evaluation()
    {
        return $this->hasOne('App\Models\Tenant\Evaluation');
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return ['role'=>'user'];
    }
}
