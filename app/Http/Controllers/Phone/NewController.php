<?php

namespace App\Http\Controllers\Phone;

use App\Http\Controllers\Controller;
use Hyn\Tenancy\Models\Hostname;
use Illuminate\Http\Request;

class NewController extends Controller
{
    //
    public function getIsHttp(Request $request){
        $hostname=Hostname::where('vue',$request->tenant)->first();
        if($hostname){
            if($hostname->ishttp){
                return $this->jsonRertun(200,'獲取是否證書','https://');
            }else{
                return $this->jsonRertun(200,'獲取是否證書','http://');
            }
        }else{
            return $this->jsonRertun(402,'請聯係管理注冊','請聯係管理注冊');
        }
    }
}
