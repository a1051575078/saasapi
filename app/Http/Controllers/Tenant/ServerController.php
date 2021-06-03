<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Libraries\EncryptUtil;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Record;
use GatewayClient\Gateway;
use Hyn\Tenancy\Website\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ServerController extends Controller {
    public function infiniteScroll(){
        $fromid=request('fromid');
        $toid=request('toid');
        $tableId=request('tableId');
        if(empty($tableId)){
            //查询最后一条数据
            $id=Record::where(function ($query) use($fromid,$toid){
                $query->where('fromid',$fromid)->where('toid',$toid);
            })->orWhere(function($query)use($fromid,$toid){
                $query->where('toid',$fromid)->where('fromid',$toid);
            })->orderBy('created_at','desc')->first('id');
            if(!empty($id)){
                $tableId=$id->id;
            }else{
                $tableId=0;
            }
        }
        $datas=Record::where(function ($query) use($fromid,$toid,$tableId){
            $query->where('fromid',$fromid)->where('toid',$toid)->where('id','<',$tableId);
        })->orWhere(function($query) use($fromid,$toid,$tableId){
            $query->where('fromid',$toid)->where('toid',$fromid)->where('id','<',$tableId);
        })->orderBy('created_at','desc')->with('user')->limit(15)->get();
        foreach($datas as $v){
            $v->loading='';
        }
        return $this->jsonRertun(200,'获取数据成功',$this->sortarr($datas));
    }
    //得到上传发送的图片
    public function sendPic(Request $request){
        if(Gateway::isUidOnline(request('fromid'))){
            $file=$request->file('file');
            if(!$file){
                return $this->jsonRertun(400,'上传文件为空','上传文件为空');
            }
            //后缀
            $suffix=$file->getClientOriginalExtension();
            if($suffix!='png'&&$suffix!='jpg'){
                return $this->jsonRertun(400,'图片类型不符合','图片类型不符合');
            }
            //获取文件大小
            $filesize=$file->getSize();
            $size=$filesize/1024/1024;
            if($size>10){
                return $this->jsonRertun(400,'文件太大','文件太大');
            }
            //将图片存入服务器的硬盘中
            $dateinfo=date('YmdHis',time()).rand(1000,9999);
            $date=date('Ym',time());
            $tenantDirectory = app(Directory::class);
            //$directoryPath=$tenantDirectory->getWebsite() ? 'app/tenancy/tenants/' . $tenantDirectory->path() : null;
            $tenantPath=$tenantDirectory->path();
            $directoryPath='app/tenancy/tenants/' . $tenantPath;
            $logPath = storage_path($directoryPath . 'chat/'.$date.'/');
            $path=$file->move($logPath,$dateinfo.'.'.$suffix);
            if(!$path){
                return $this->jsonRertun(400,'存储失败','存储失败');
            }
            $path='storage/'.$tenantPath.'chat/'.$date.'/'.$dateinfo.'.'.$suffix;
            $data=$request->all();
            $data['content']=$path;
            $data['fromid']=request('fromid');
            $data['toid']=request('toid');
            $data['sendid']=request('sendid');
            $data['type']='img';
            $data['rand']=request('rand');
            $data['user']['avatar']=request('avatar');
            $data['user']['name']=request('name');
            Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
            //Gateway::sendToUid(request('toid'),json_encode($data));
            $fromid=(int)request('fromid');
            //如果是整数型的fromid，那就是客服,否则为0
            if($fromid){
                Record::create([
                    'fromid'=>request('fromid'),
                    'toid'=>request('toid'),
                    'user_id'=>request('sendid'),
                    'rand'=>request('rand'),
                    'isread'=>0,
                    'type'=>2,
                    'content'=>$path
                ]);
                $ip=explode('访客', request('toid'))[1];
            }else{
                Record::create([
                    'fromid'=>request('fromid'),
                    'toid'=>request('toid'),
                    'rand'=>request('rand'),
                    'isread'=>0,
                    'type'=>2,
                    'content'=>$path
                ]);
                $ip=explode('访客', request('fromid'))[1];
            }
            Contact::where('ip',$ip)->increment('recordnumber');
            return $this->jsonRertun(200,'发送图片成功','发送图片成功');
        }else{
            return $this->jsonRertun(402,'你的网络波动已掉线','你的网络波动已掉线');
        }
    }
    public function sendMsg(){
        if(Gateway::isUidOnline(request('fromid'))){
            //转发消息
            Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode(request()->all()));
            $fromid=(int)request('fromid');
            //如果是整数型的fromid，那就是客服,否则为0
            if($fromid){
                Record::create([
                    'fromid'=>request('fromid'),
                    'toid'=>request('toid'),
                    'user_id'=>request('sendid'),
                    'rand'=>request('rand'),
                    'isread'=>0,
                    'type'=>1,
                    'content'=>EncryptUtil::decrypt(request('content')),
                ]);
                $ip=explode('访客', request('toid'))[1];
            }else{
                Record::create([
                    'fromid'=>request('fromid'),
                    'toid'=>request('toid'),
                    'rand'=>request('rand'),
                    'isread'=>0,
                    'type'=>1,
                    'content'=>EncryptUtil::decrypt(request('content')),
                ]);
                $ip=explode('访客', request('fromid'))[1];
            }
            Contact::where('ip',$ip)->increment('recordnumber');
            return $this->jsonRertun(200,'发送消息成功','发送消息成功');
        }else{
            return $this->jsonRertun(402,'你的网络波动已掉线','你的网络波动已掉线');
        }
    }
    /**
     * 绑定uid
     * @return mixed
     */
    public function bind(){
        $client_id=request('client_id');
        $uid=request('fromid');
        //绑定操作
        Gateway::bindUid($client_id,$uid);
        Gateway::joinGroup($client_id,$_SERVER['SERVER_NAME']);
        //给UID发送消息,测试是否绑定成功
        //Gateway::sendToUid($uid,json_encode(["msg"=>"绑定成功"]));
        return $this->jsonRertun(200,'绑定Uid成功','绑定Uid成功');
    }
}
