<?php
namespace App\Models\Tenant;
use Hyn\Tenancy\Database\Connection;
use Hyn\Tenancy\Traits\UsesTenantConnection;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function getConnectionName(){
        if($_SERVER['SERVER_NAME']!==config('app.httphost')){
            return app(Connection::class)->tenantName();
        }else{
            return app(Connection::class)->systemName();
        }
    }
}