<?php

namespace App\Http\Middleware;

use Closure;
use Hyn\Tenancy\Models\Hostname;

class Expired
{
    public function handle($request, Closure $next){
        $fqdn =$_SERVER['SERVER_NAME'];
        $expired=$this->tenantExists($fqdn);
        if ( empty($expired)){
            return abort(403,'Nope.');
        }else{
            if($expired->expiry_date<date('Y-m-d H:i:s',time())){
                //return abort(402,'您的到期时间为:'.$expired->expiry_date);
                $data['code']=400;
                $data['msg']='您的到期时间为:'.$expired->expiry_date;
                return response()->json($data);
            }
        }
        return $next($request);
    }
    private function tenantExists( $fqdn ) {
        return Hostname::where( 'fqdn', $fqdn )->first();
    }
}
