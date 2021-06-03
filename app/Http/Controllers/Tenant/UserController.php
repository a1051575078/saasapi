<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Log;
use App\Models\Tenant\User;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Website\Directory;
use Illuminate\Http\Request;
use Zhuzhichao\IpLocationZh\Ip;

class UserController extends Controller{
    public function resetPwd(Request $request){
        User::where('id',$request->id)->update([
            'password'=>bcrypt(123456)
        ]);
        return $this->jsonRertun(200,'重置密码成功','重置密码成功');
    }
    public function getUser(){
        $fqdn=$_SERVER['SERVER_NAME'];
        $hostname=Hostname::where('fqdn',$fqdn)->first();
        $users=User::all();
        $data=[];
        foreach ($users as $user){
            if($user->hasRole('客服')){
                $data[]=$user;
            }
        }
        $datas['seat']=$hostname->seat;
        $datas['code']=200;
        $datas['msg']='查看用户信息成功';
        $datas['data']=$data;
        return response()->json($datas);
    }
    public function updateUser(Request $request){
        $user=User::where('id',$request->id)->first();
        //将图片存入服务器的硬盘中
        $dateinfo=date('YmdHis',time()).rand(1000,9999);
        $tenantDirectory = app(Directory::class);
        $directoryPath = $tenantDirectory->getWebsite() ? 'app/tenancy/tenants/' . $tenantDirectory->path() : null;
        if(!empty($request->file)){
            $file=$request->file('file');
            if(!$file){
                return $this->jsonRertun(402,'上传文件为空','上传文件为空');
            }
            //图片名字
            $name=$file->getClientOriginalName();
            //后缀
            $suffix=$file->getClientOriginalExtension();
            if($suffix!='png'&&$suffix!='jpg'&&$suffix!='gif'){
                return $this->jsonRertun(402,'图片类型不符合','图片类型不符合');
            }
            //获取文件大小
            $filesize=$file->getSize();
            $size=$filesize/1024/1024;
            if($size>3){
                return $this->jsonRertun(402,'文件太大','文件太大');
            }
            $logPath = storage_path($directoryPath . 'avatar/');
            $path=$file->move($logPath,$dateinfo.'.'.$suffix);
            if(!$path){
                return $this->jsonRertun(402,'存储失败','存储失败');
            }
            $path='storage/'.$tenantDirectory->path().'avatar/'.$dateinfo.'.'.$suffix;
        }else{
            $path=$user->avatar;
        }
        if(!empty($request->music)){
            $music=$request->file('music');
            if(!$music){
                return $this->jsonRertun(402,'文件为空','文件为空');
            }
            //图片名字
            $name=$music->getClientOriginalName();
            //后缀
            $suffix=$music->getClientOriginalExtension();
            if($suffix!='mp3'&&$suffix!='mp4'&&$suffix!='wav'){
                return $this->jsonRertun(402,'格式错误','格式错误');
            }
            //获取文件大小
            $filesize=$music->getSize();
            $size=$filesize/1024/1024;
            if($size>0.5){
                return $this->jsonRertun(402,'文件太大','文件太大');
            }
            $logPath = storage_path($directoryPath . 'music/');
            $music=$music->move($logPath,$dateinfo.'.'.$suffix);
            if(!$music){
                return $this->jsonRertun(402,'存储失败','存储失败');
            }
            $music='storage/'.$tenantDirectory->path().'music/'.$dateinfo.'.'.$suffix;
        }else{
            $music=$user->music;
        }
        if(!empty($request->password)){
            $password=bcrypt($request->password);
        }else{
            $password=$user->password;
        }
        if(!empty($request->input('content'))){
            $content=$request->input('content');
        }else{
            $content='';
        }
        User::where('id',$request->id)->update([
            'name'=>request('name'),
            'avatar'=>$path,
            'music'=>$music,
            'content'=>$content,
            'password'=>$password
        ]);
        if(empty($request->num)||$request->num=='undefined'||$request->num==''){
            $num=null;
        }else{
            $num=$request->num;
        }
        if(empty($request->title)||$request->title=='null'){
            $title=null;
        }else{
            $title=$request->title;
        }
        if(empty($request->jumplink)||$request->jumplink=='null'){
            $jumplink=null;
        }else{
            $jumplink=$request->jumplink;
        }
        Hostname::where('fqdn',$_SERVER['SERVER_NAME'])->update([
            'jumplink'=>$jumplink,
            'title'=>$title,
            'deleteday'=>$num
        ]);
        $data['path']=$path;
        $data['music']=$music;
        $data['num']=$num;
        return $this->jsonRertun(200,'修改信息成功',$data);
    }
    public function userDelMany(Request $request){
        $users=User::all();
        $userd=auth()->user();
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        $del=[];
        foreach($request->all() as $v){
            foreach ($users as $user){
                if($v===$user->id){
                    array_push($del,$v);
                    Log::create([
                        'user_id'=>$userd->id,
                        'type'=>'删除客服',
                        'content'=>'操作者：'.$userd->name.'删除客服->'.$user->name,
                        'ip'=>$ip,
                        'address'=>$address[1].$address[2].$address[3]
                    ]);
                    break;
                }
            }
        }
        User::destroy($del);
        Hostname::where('fqdn',$_SERVER['SERVER_NAME'])->first()->increment('seat',count($del));
        return $this->jsonRertun(200,'删除用户成功','删除用户成功');
    }
    public function index(){
        //
        return $this->jsonRertun(200,'获取当前客服信息',auth()->user());
    }
    public function create(){
        //
    }
    public function store(Request $request){
        //
        $fqdn=$_SERVER['SERVER_NAME'];
        $hostname=Hostname::where('fqdn',$fqdn)->first();
        if(empty($hostname->seat)){
            return $this->jsonRertun(402,'坐席位置不够','坐席位置不够');
        }
        $user=User::create([
            'avatar'=>'images/avatar.gif',
            'content'=>'',
            'name'=>$request->name,
            'password'=>bcrypt($request->password),
            'music'=>'images/tip.wav',
        ]);
        $user->assignRole('客服');
        Hostname::where('fqdn',$fqdn)->update([
            'seat'=>--$hostname->seat
        ]);
        $user=auth()->user();
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        Log::create([
            'user_id'=>$user->id,
            'type'=>'新增客服',
            'content'=>'操作者：'.$user->name.'新增客服->'.$request->name,
            'ip'=>$ip,
            'address'=>$address[1].$address[2].$address[3]
        ]);
        return $this->jsonRertun(200,'新增成功','新增成功');
    }
    public function show($id){
        //
    }
    public function edit($id){
        //
    }
    public function update(Request $request, $id){

    }
    public function destroy($id){
        //
    }
}
