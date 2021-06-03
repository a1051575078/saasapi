<?php
namespace App\Models\Tenant;
use Hyn\Tenancy\Database\Connection;
use Hyn\Tenancy\Traits\UsesTenantConnection;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole{
    public function getConnectionName(){
        if($_SERVER['SERVER_NAME']!==config('app.httphost')){
            return app(Connection::class)->tenantName();
        }else{
            return app(Connection::class)->systemName();
        }
    }
}