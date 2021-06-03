<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Black;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Log;
use App\Models\Tenant\Record;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Zhuzhichao\IpLocationZh\Ip;

class ContactController extends Controller{
    //得到客服的基本信息
    public function getCustomerInfo(){
        return $this->jsonRertun(200,'得到客服信息',User::role('客服')->get(['id','name']));
    }
    //删除或者新增黑名单
    public function delAddBlack(){
        request('blacklist')===1?Black::create(['ip'=>request('ip'),'address'=>request('address')]):Black::where('ip',request('ip'))->delete();
        Contact::where('ip',request('ip'))->update([
            'blacklist'=>request('blacklist')
        ]);
        $user=auth()->user();
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        $address=$address[1].$address[2].$address[3];
        if(request('blacklist')){
            Log::create([
                'type'=>'拉黑用户',
                'content'=>'操作者：'.$user->name.'拉黑访客'.request('ip'),
                'ip'=>$ip,
                'address'=>$address,
                'user_id'=>$user->id
            ]);
        }else{
            Log::create([
                'type'=>'删除拉黑用户',
                'content'=>'操作者：'.$user->name.'取消拉黑访客'.request('ip'),
                'ip'=>$ip,
                'address'=>$address,
                'user_id'=>$user->id
            ]);
        }
        return $this->jsonRertun(200,'操作成功','操作成功');
    }
    //客服获取列表
    public function kindex(){
        $data=Contact::orderBy('created_at','desc')->where('fromid',auth()->user()->id.'')->get();
        return $this->jsonRertun(200,'得到联系人列表',$data);
    }
    public function index(){
        $data=Contact::orderBy('created_at','desc')->get();
        return $this->jsonRertun(200,'得到联系人列表',$data);
    }
    public function create(){
        //
    }
    public function store(Request $request){
        //
    }
    public function show($id){
        $contact=Contact::where('id',$id)->first();
        $user=User::where('id',$contact->fromid)->first();
        if(empty($user)){
            $avatar='/images/avatar.gif';
        }else{
            $avatar=$user->avatar;
        }
        $fromid=$contact->fromid;
        $toid='访客'.$contact->ip;
        $data['toid']=$toid;
        $data['fromid']=$fromid;
        $data['avatar']=$avatar;
        $datas=Record::where(function($query) use($fromid,$toid){
            $query->where('fromid',$fromid)->where('toid',$toid);
        })->orWhere(function($query)use($fromid,$toid){
            $query->where('toid',$fromid)->where('fromid',$toid);
        })->orderBy('created_at','desc')->with('user')->get();
        $datas=$this->sortarr($datas);
        $data['record']=$datas;
        return $this->jsonRertun(200,'獲取上綫用戶的相關信息',$data);
    }
    public function edit($id){
        //
    }
    public function update(Request $request, $id){
        //
    }
    public function destroy($id){
        //
    }
}
