<?php

namespace App\Http\Middleware;

use Closure;

class AdminBlack
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip=$_SERVER['REMOTE_ADDR'];
        }else{
            $str=$_SERVER['HTTP_X_FORWARDED_FOR'];
            $Regex='#([^,]+)#is';
            preg_match($Regex,$str,$result);
            $ip=$result[1];
        }
        $black=\App\Models\Admin\Black::where('ip',$ip)->first();
        if(empty($black)){
            return $next($request);
        }else{
            $data['code']=444;
            $data['msg']='您已经被封禁';
            return response()->json($data);
        }
    }
}
