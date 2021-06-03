<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Log;
use App\Models\Tenant\Online;
use App\Models\Tenant\User;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Zhuzhichao\IpLocationZh\Ip;

class ToolController extends Controller
{
    //客服挂起或者接客
    public function toolHang(Request $request){
        //0是挂起，1是没有挂起
        $user=auth()->user();
        $request->is_server===1?User::where('id',$user->id)->update(['is_server'=>0]):User::where('id',$user->id)->update(['is_server'=>1]);
        $data['type']='hang';
        $data['fromid']=$user->id;
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        if($request->is_server){
            $is_server=0;
            Online::create([
                'user_id'=>$user->id,
                'tag'=>0
            ]);
            $type='挂起';
        }else{
            $is_server=1;
            Online::create([
                'user_id'=>$user->id,
                'tag'=>1
            ]);
            $type='取消挂起';
        }
        Log::create([
            'user_id'=>$user->id,
            'type'=>$type,
            'ip'=>$ip,
            'address'=>$address[1].$address[2].$address[3],
        ]);
        $data['is_server']=$is_server;
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
        return $this->jsonRertun(200,'功能是否挂起成功','功能是否挂起成功');
    }//
}
