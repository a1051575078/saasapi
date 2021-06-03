<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Black;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Log;
use App\Models\Tenant\Online;
use App\Models\Tenant\Record;
use App\Models\Tenant\User;
use App\Models\Tenant\Vipuser;
use GatewayClient\Gateway;
use Hyn\Tenancy\Website\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Zhuzhichao\IpLocationZh\Ip;

class AdminController extends Controller {
    public function translationRecordS(Request $request){
        $datas=[];
        foreach($request->chatRecord as $chat){
            if($chat['type']===1){
                //如果他不為純數字，就去翻譯
                if(!(int)$chat['content']){
                    $text=$chat['content'];
                    $result=$this->translationBaidu($request->language,$text);
                    if(!empty($result['error_code'])&&$result['error_code']===58001){
                        $chat['content']='译文语言方向不支持';
                    }else{
                        if(!empty($result['trans_result'])){
                            $chat['content']=$result['trans_result'][0]['dst'];
                        }else{
                            $chat['content']='更换为百度翻译接口，当前语种属于谷歌，对不起。无法翻译此语种';
                        }
                    }
                    $datas[]=$chat;
                }else{
                    $datas[]=$chat;
                }
            }else{
                $datas[]=$chat;
            }
        }
        return $this->jsonRertun(200,'翻译成功',$datas);
    }
    public function translationS(Request $request){
        if(empty($request->language)){
            $to='zh';
        }else{
            $to=$request->language;
        }
        $result=$this->translationBaidu($to,$request->input('content'));
        $data=$request->all();
        if(!empty($result['trans_result'])){
            $data['content']=$result['trans_result'][0]['dst'];
        }else{
            $data['content']='更换为百度翻译接口，当前语种属于谷歌，对不起。无法翻译此语种';
        }
        return $this->jsonRertun(200,'翻译成功',$data);
    }
    //百度翻译的接口
    protected function translationBaidu($to,$text){
        $text=str_replace("\n",'',$text);
        $text=str_replace(" ",'',$text);
        $id='20210527000844350';
        $salt=time();
        $key='ytNnIz_TOkrT0x8hUzKG';
        $sign=md5($id.$text.$salt.$key);
        $url='http://api.fanyi.baidu.com/api/trans/vip/translate?q='.$text.'&from=auto&to='.$to.'&appid='.$id.'&salt='.$salt.'&sign='.$sign;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);
        $ret = json_decode($r, true);
        return $ret;
    }
    //批量删除vip用户
    public function delAllVip(Request $request){
        $user=auth()->user();
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        $address=$address[1].$address[2].$address[3];
        $array=[];
        foreach ($request->all() as $item){
            array_push($array,$item['id']);
            Log::create([
                'user_id'=>$user->id,
                'type'=>'删除用户',
                'content'=>'操作者：'.$user->name.'删除用户IP：'.$item['ip'],
                'ip'=>$ip,
                'address'=>$address
            ]);
        }
        Vipuser::destroy($array);
        return $this->jsonRertun(200,'删除会员成功','删除会员成功');
    }
    //删除vip用户
    public function delVip(Request $request){
        Vipuser::where('id',$request->id)->delete();
        $user=auth()->user();
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        $address=$address[1].$address[2].$address[3];
        Log::create([
            'user_id'=>$user->id,
            'type'=>'删除用户',
            'content'=>'操作者：'.$user->name.'删除用户IP：'.$request->ip,
            'ip'=>$ip,
            'address'=>$address
        ]);
        return $this->jsonRertun(200,'删除会员成功','删除会员成功');
    }
    //得到vip表格
    public function vip(){
        return $this->jsonRertun(200,'获取vip表格数据成功',Vipuser::orderBy('created_at','desc')->get());
    }
    //添加vip或者修改vip信息
    public function addVip(Request $request){
        if(!empty($request->ip)){
            $user=auth()->user();
            Vipuser::updateOrInsert([
                'ip'=>$request->ip
            ],[
                'ip'=>$request->ip,
                'user'=>$user->name,
                'name'=>$request->name,
                'sex'=>$request->radio,
                'phone'=>$request->phone,
                'age'=>$request->age,
                'qq'=>$request->qq,
                'wechat'=>$request->wechat,
                'address'=>$request->address,
                'remarks'=>$request->remarks,
                'created_at'=>date('Y-m-d H:i:s',time())
            ]);
            $ip=$this->getHttpIp();
            $address=Ip::find($ip);
            $address=$address[1].$address[2].$address[3];
            Log::create([
                'user_id'=>$user->id,
                'type'=>'添加用户',
                'content'=>'操作者：'.$user->name.'新增用户IP：'.$request->ip,
                'ip'=>$ip,
                'address'=>$address
            ]);
        }
        return $this->jsonRertun(200,'新增VIP用戶成功','新增VIP用戶成功');
    }
    public function translationRecord(Request $request){
        $datas=[];
        foreach($request->chatRecord as $chat){
            if($chat['type']===1){
                //如果他不為純數字，就去翻譯
                if(!(int)$chat['content']){
                    $text=$chat['content'];
                    $result=$this->translationGoogle($request->language,$text);
                    if(!empty($result)){
                        foreach($result[0] as $k){
                            $v= $k[0];
                        }
                        $chat['content']=$v;
                        $datas[]=$chat;
                    }
                }else{
                    $datas[]=$chat;
                }
            }else{
                $datas[]=$chat;
            }
        }
        return $this->jsonRertun(200,'翻译成功',$datas);
    }
    public function translationGoogle($to,$text){
        $entext = urlencode($text);
        $url = 'https://translate.google.cn/translate_a/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=auto&tl='.$to.'&q='.$entext;
        set_time_limit(0);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS,20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
    public function translation(Request $request){
        if(empty($request->language)){
            $to='zh-CN';
        }else{
            $to=$request->language;
        }
        $text=$request->input('content');
        $result=$this->translationGoogle($to,$text);
        $data=$request->all();
        if(!empty($result)){
            foreach($result[0] as $k){
                $v[] = $k[0];
            }
            $data['content']=implode(" ", $v);
        }
        return $this->jsonRertun(200,'翻译成功',$data);
    }
    public function isUidOnline(){
        $isUidOnline=Gateway::isUidOnline(request('fromid'));
        return $this->jsonRertun(200,'当前退出用户是否在线',$isUidOnline);
    }
    public function nihaoya(){
        $data['hello']="world";
        return response()->json($data);
    }
    public function imclick(Request $request){
        $data=$request->all();
        $data['type']='click';
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
        return $this->jsonRertun(200,'点击得到消息','点击得到消息');
    }
    //把当前客服的消息传送给新上来的客服
    public function sendMeClientGiveOther(Request $request){
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($request->all()));
        return $this->jsonRertun(200,'把数据给新上线的用户成功','把数据给新上线的用户成功');
    }
    //撤回消息
    public function withdraw(Request $request){
        Record::where('rand',$request->rand)->update(['withdraw'=>1]);
        $data['type']='withdraw';
        $data['toid']=$request->toid;
        $data['rand']=$request->rand;
        $data['fromid']=$request->fromid;
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
        return $this->jsonRertun(200,'撤回消息成功','撤回消息成功');
    }
    //把用户转接给其他客服
    public function transfer(Request $request){
        $data=$request->all();
        $data['type']="transfer";
        Record::where('fromid',$request->fromid)->update([
            'toid'=>$request->toid,
        ]);
        Record::where('toid',$request->fromid)->update([
            'fromid'=>$request->toid,
        ]);
        $user=User::where('id',$request->toid)->first();
        Contact::where('ip',$request->ip)->update([
            'fromid'=>$request->toid,
            'name'=>$user->name
        ]);
        Log::create([
            'user_id'=>$request->oldtoid,
            'type'=>'客服转接',
            'content'=>'用户：'.$request->fromid.'转接至：'.$user->name,
            'ip'=>$request->ip,
            'address'=>$request->add
        ]);
        $vs=[];
        foreach ($request->record as $v){
            if($request->fromid==$v['fromid']){
                $v['toid']=$request->toid;
            }else{
                $v['fromid']=$request->toid;
            }
            $vs[]=$v;
        }
        $data['record']=$vs;
        $data['name']=$user->name;
        /*Gateway::sendToUid($request->toid,json_encode($data));
        Gateway::sendToUid($request->fromid,json_encode($data));*/
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
        return $this->jsonRertun(200,'转接成功','转接成功');
    }
    //查询在线的客服信息
    public function findOnline(){
        $findOtherOnline=$this->getHosterState();
        return $this->jsonRertun(200,'当前在线的客服',$findOtherOnline);
    }
    //获取所有客服的状态
    public function getHosterState(){
        //获取所有客服的id
        /*$hosterList=DB::table('users')->get(['id','name'])->map(function ($value){
            return (array)$value;
        })->toArray();*/
        $hosterList=User::role('客服')->get(['id','name'])->toArray();
        $hosterStateList=array_map(function ($res){
            return [
                'id'=>$res['id'],
                'name'=>$res['name'],
                'onlineState'=>$this->getOnlineState($res['id']),
            ];
        },$hosterList);
        //如果客服都是离线状态
        $list=[];
        foreach ($hosterStateList as $v){
            if($v['onlineState']===1){
                $list[]=$v;
            }
        }
        return $list;
    }
    //获取客服的在线状态
    public function getOnlineState($id){
        $groups=Gateway::getClientSessionsByGroup($_SERVER['SERVER_NAME']);
        $user=User::role('客服')->where('id',$id)->first();
        //当前打开很多界面,在线,那么就返回1;如果某uid绑定的client_id有至少有一个在线，那么对该uid调用 Gateway::isUidOnline($uid)将返回1。
        foreach ($groups as $group){
            if(!empty($group['uid'])){
                if($group['uid']==$id&&$user->is_server===1){
                    return Gateway::isUidOnline($id);
                }
            }
        }
        return 0;
    }
    //加入黑名单
    public function blackEnd(Request $request){
        $user=auth()->user();
        Black::create([
            'ip'=>$request->ip,
            'address'=>$request->add
        ]);
        Contact::updateOrInsert([
            'ip'=>$request->ip
        ],[
            'ip'=>$request->ip,
            'address'=>$request->add,
            'blacklist'=>1
        ]);
        Log::create([
            'type'=>'拉黑用户',
            'content'=>'操作者：'.$user->name.'拉黑访客'.request('ip'),
            'ip'=>$request->ip,
            'address'=>$request->add,
            'user_id'=>$user->id
        ]);
        $data['type']='black';
        $data['fromid']=$request->fromid;
        $data['toid']=$request->toid;
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
        return $this->jsonRertun(200,'加入黑名单成功','加入黑名单成功');
    }
    //管理员上线通知用户他们
    public function online(){
        $user=auth()->user();
        $data['type']='online';
        $data['fromid']=request()->fromid;
        $data['name']=request()->name;
        $data['avatar']=$user->avatar;
        $data['content']=$user->content;
        $data['is_server']=$this->getOnlineState(request()->fromid);
        $online=Online::orderBy('id','desc')->where('user_id',request('fromid'))->first();
        if($online){
            if(!$online->tag){
                Online::create([
                    'user_id'=>request('fromid'),
                    'tag'=>1
                ]);
            }
        }else{
            Online::create([
                'user_id'=>request('fromid'),
                'tag'=>1
            ]);
        }
        Gateway::sendToGroup($_SERVER['SERVER_NAME'], json_encode($data));
        return $this->jsonRertun(200,'推送成功','推送成功');
    }
    //查询最后一条聊天记录的信息
    public function getLastMsgTime($fromid){
        $toid=auth()->user()->id;
        return DB::table('record')->where(function ($query) use($toid,$fromid) {
            $query->where('fromid',$fromid)->where('toid',$toid);
        })->orWhere(function ($query) use($toid,$fromid) {
            $query->where('toid',$fromid)->where('fromid',$toid);
        })->orderBy('created_at','desc')
            ->limit(1)
            ->first();
    }
}
