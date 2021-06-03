<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Black;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Evaluation;
use App\Models\Tenant\Record;
use App\Models\Tenant\User;
use App\Models\Tenant\Vipuser;
use App\Models\Tenant\Visitor;
use GatewayClient\Gateway;
use Hyn\Tenancy\Models\Hostname;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Zhuzhichao\IpLocationZh\Ip;

class ClientController extends Controller {
    private $clientIp;
    public function __construct(){
        $this->clientIp='访客'.$this->getHttpIp();
    }
    public function addEvaluation(){
        if(mb_strlen(request('content'),'UTF8')>66){
            return $this->jsonRertun(200,'评价成功','评价成功');
        }
        if(!request('toid')||!(int)request('toid')){
            return $this->jsonRertun(200,'评价成功','评价成功');
        }
        if(!request('good')&&!request('noog')||request('good')&&request('noog')){
            return $this->jsonRertun(200,'评价成功','评价成功');
        }
        $ip=$this->getHttpIp();
        $evluation=Evaluation::where('visitors',$ip)->first();
        if(empty($evluation)){
            Evaluation::create([
                'user_id'=>request('toid'),
                'good'=>request('good'),
                'nogood'=>request('nogood'),
                'content'=>request('content'),
                'visitors'=>$ip
            ]);
        }else{
            $date=Carbon::parse($evluation->created)->toDateString();
            $today=Carbon::parse('today')->toDateString();
            if($date!==$today){
                Evaluation::create([
                    'user_id'=>request('toid'),
                    'good'=>request('good'),
                    'nogood'=>request('nogood'),
                    'content'=>request('content'),
                    'visitors'=>$ip
                ]);
            }
        }
        return $this->jsonRertun(200,'评价成功','评价成功');
    }
    public function infiniteScroll(){
        $fromid=request('fromid');
        $tableId=request('tableId');
        $datas=Record::where(function ($query) use($fromid,$tableId){
            $query->where('fromid',$fromid)->where('id','<',$tableId);
        })->orWhere(function($query) use($fromid,$tableId){
            $query->where('toid',$fromid)->where('id','<',$tableId);
        })->orderBy('created_at','desc')->limit(15)->get();
        return $this->jsonRertun(200,'获取数据成功',$this->sortarr($datas));
    }
    public function tell(){
        //得到客服的地址還有IP
        $ip=$this->getHttpIp();
        $add=Ip::find($ip);
        //是從前台的角度傳過去。所以fromid是前台的fromid，需要改爲toid
        $fromid=request('toid');
        $toid=request('fromid');
        //我需要得到這個用戶的ip->toid、地區、手機型號
        $data['toid']=(string)request('toid');
        $data['fromid']=request('fromid');
        $data['add']=$add[1].$add[2].$add[3];
        $data['ip']=$ip;
        $data['model']=$this->getPhoneType($_SERVER['HTTP_USER_AGENT']);
        $data['isread']=0;
        $data['vip']=request('vip');
        if(empty(request('vip'))){
            $data['vip']=new class{};
        }
        $id=Record::where(function ($query) use($fromid,$toid){
            $query->where('fromid',$fromid)->where('toid',$toid);
        })->orWhere(function($query)use($fromid,$toid){
            $query->where('toid',$fromid)->where('fromid',$toid);
        })->orderBy('created_at','desc')->first('id');
        if(!empty($id)){
            $count=$id->id;
            $datas=Record::where(function($query) use($fromid,$toid){
                $query->where('fromid',$fromid)->where('toid',$toid);
            })->orWhere(function($query)use($fromid,$toid){
                $query->where('toid',$fromid)->where('fromid',$toid);
            })->where('id','<=',$count)->orderBy('created_at','desc')->with('user')->limit(15)->get();
            $datas=$this->sortarr($datas);
        }else{
            $datas=[];
        }
        foreach ($datas as $v){
            $v->loading='';
        }
        $data['record']=$datas;
        $data['type']=request('type');
        //转发消息
        Gateway::sendToGroup($_SERVER['SERVER_NAME'],json_encode($data));
        //Gateway::sendToUid(request('toid'),json_encode($data));
        return $this->jsonRertun(200,'发送消息成功',$data);
    }
    public function getPhoneType($user_agent){
        if (stripos($user_agent, "iPhone")!==false) {
            return $brand = 'iPhone';
        } else if (stripos($user_agent, "SAMSUNG")!==false || stripos($user_agent, "Galaxy")!==false || strpos($user_agent, "GT-")!==false || strpos($user_agent, "SCH-")!==false || strpos($user_agent, "SM-")!==false) {
            return $brand = '三星';
        } else if (stripos($user_agent, "Huawei")!==false || stripos($user_agent, "Honor")!==false || stripos($user_agent, "H60-")!==false || stripos($user_agent, "H30-")!==false) {
            return $brand = '华为';
        } else if (stripos($user_agent, "Lenovo")!==false) {
            return $brand = '联想';
        } else if (strpos($user_agent, "MI-ONE")!==false || strpos($user_agent, "MI 1S")!==false || strpos($user_agent, "MI 2")!==false || strpos($user_agent, "MI 3")!==false || strpos($user_agent, "MI 4")!==false || strpos($user_agent, "MI-4")!==false) {
            return $brand = '小米';
        } else if (strpos($user_agent, "HM NOTE")!==false || strpos($user_agent, "HM201")!==false) {
            return $brand = '红米';
        } else if (stripos($user_agent, "Coolpad")!==false || strpos($user_agent, "8190Q")!==false || strpos($user_agent, "5910")!==false) {
            return $brand = '酷派';
        } else if (stripos($user_agent, "ZTE")!==false || stripos($user_agent, "X9180")!==false || stripos($user_agent, "N9180")!==false || stripos($user_agent, "U9180")!==false) {
            return $brand = '中兴';
        } else if (stripos($user_agent, "OPPO")!==false || strpos($user_agent, "X9007")!==false || strpos($user_agent, "X907")!==false || strpos($user_agent, "X909")!==false || strpos($user_agent, "R831S")!==false || strpos($user_agent, "R827T")!==false || strpos($user_agent, "R821T")!==false || strpos($user_agent, "R811")!==false || strpos($user_agent, "R2017")!==false) {
            return $brand = 'OPPO';
        } else if (strpos($user_agent, "HTC")!==false || stripos($user_agent, "Desire")!==false) {
            return $brand = 'HTC';
        } else if (stripos($user_agent, "vivo")!==false) {
            return $brand = 'vivo';
        } else if (stripos($user_agent, "K-Touch")!==false) {
            return $brand = '天语';
        } else if (stripos($user_agent, "Nubia")!==false || stripos($user_agent, "NX50")!==false || stripos($user_agent, "NX40")!==false) {
            return $brand = '努比亚';
        } else if (strpos($user_agent, "M045")!==false || strpos($user_agent, "M032")!==false || strpos($user_agent, "M355")!==false) {
            return $brand = '魅族';
        } else if (stripos($user_agent, "DOOV")!==false) {
            return $brand = '朵唯';
        } else if (stripos($user_agent, "GFIVE")!==false) {
            return $brand = '基伍';
        } else if (stripos($user_agent, "Gionee")!==false || strpos($user_agent, "GN")!==false) {
            return $brand = '金立';
        } else if (stripos($user_agent, "HS-U")!==false || stripos($user_agent, "HS-E")!==false) {
            return $brand = '海信';
        } else if (stripos($user_agent, "Nokia")!==false) {
            return $brand = '诺基亚';
        } else {
            return $brand = '电脑设备';
        }
    }
    //断掉当前连接的用户client_id
    public function closeClient(){
        Gateway::closeClient(request('client_id'));
        return $this->jsonRertun(200,'断掉成功','断掉成功');
    }
    //获取当前登录的用户fromid、title、跳转连接
    public function getCurrentInfo(){
        $hostname=Hostname::where('fqdn',$_SERVER['SERVER_NAME'])->first();
        $data['title']=$hostname->title;
        $data['jumplink']=$hostname->jumplink;
        $data['fromid']=$this->clientIp;
        $vip=Vipuser::where('ip',$this->getHttpIp())->first();
        if($vip){
            $data['vip']=$vip;
        }else{
            $data['vip']=new class{};
        }
        return $this->jsonRertun(200,'获取客户的fromid成功',$data);
    }
    //选择一个在线客服
    public function chooseOnlineCustomerService(){
        //客户端的IP,就像这样访客192.168.10.1
        //先查询用户之前聊天的最后一次的客服是否在线
        //查询是否有记录
        $isChat=Record::where('fromid',$this->clientIp)
            ->orWhere('toid',$this->clientIp)
            ->orderBy('created_at','desc')
            ->first(['id','toid','fromid']);
        $online='';
        if(!empty($isChat)){
            //如果最后发送消息的toid不是用户,那么最后一条发送消息的用户就是fromid,客服则为toid
            if($isChat->toid!==$this->clientIp){
                $toid=$isChat->toid;
            }else{
                $toid=$isChat->fromid;
            }
            $tableid=$isChat->id;
            //toid就是谁和用户聊的人
            $online=$this->getOnlineState($toid);
        }else{
            $tableid=0;
        }
        $date=date('Y-m-d H:i:s',time());
        //toid是用户。因为欢迎语是从客服的角度来发送
        //如果最后聊天的客服在线
        if(!empty($online)){
            $user=User::where('id',$toid)->first();
            $data['id']=$tableid;
            $data['name']=$user->name;
            $data['avatar']=$user->avatar;
            $data['toid']=$this->clientIp;
            $data['fromid']=$toid;
            $data['content']=$user->content;
            $data['type']=1;
            $data['created_at']=$date;
            //记录日志
            $this->access($toid,$user->name);
        }else{
            //如果最后聊天的客服不在线,则取寻找新的在线客服,并把聊天记录修改转给他
            $findOtherOnline=$this->getHosterState();
            //如果都不在线.
            if(empty($findOtherOnline)){
                $data['id']=$tableid;
                $data['content']='当前客服都不在线,请稍后再联系';
                $data['isOnline']='offline';
                $data['type']=3;//中间的字体
            }else{
                //如果在线,随机选择一个客服
                $index=array_rand($findOtherOnline,1);
                $this->updateToid($this->clientIp,$findOtherOnline[$index]['id']);
                $user=User::where('id',$findOtherOnline[$index]['id'])->first();
                $data['id']=$tableid;
                $data['name']=$user->name;
                $data['avatar']=$user->avatar;
                $data['fromid']=$findOtherOnline[$index]['id'];
                $data['toid']=$this->clientIp;
                $data['content']=$user->content;
                $data['type']=1;
                $data['created_at']=$date;
                $this->access($findOtherOnline[$index]['id'],$user->name);
            }
        }
        $this->visitors();
        return $this->jsonRertun(200,'分配客服成功',$data);
    }
    public function visitors(){
        //记录用户访问了。
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        $address=$address[1].$address[2].$address[3];
        $visitor=Visitor::where('ip',$ip)->orderBy('id','desc')->first();
        if(empty($visitor)){
            Visitor::create([
                'ip'=>$ip,
                'frequency'=>1,
                'address'=>$address,
                'isphone'=>$this->getPhoneType($_SERVER['HTTP_USER_AGENT']),
                'origin'=>$_SERVER['HTTP_REFERER']
            ]);
        }else{
            $dateTime=Carbon::parse($visitor->created_at)->toDateString();
            $todayTime=Carbon::parse('today')->toDateString();
            if($dateTime===$todayTime){
                Visitor::where('id',$visitor->id)->update([
                    'ip'=>$ip,
                    'frequency'=>++$visitor->frequency,
                    'address'=>$address,
                    'isphone'=>$this->getPhoneType($_SERVER['HTTP_USER_AGENT']),
                    'origin'=>$_SERVER['HTTP_REFERER']
                ]);
            }else{
                Visitor::create([
                    'ip'=>$ip,
                    'frequency'=>1,
                    'address'=>$address,
                    'isphone'=>$this->getPhoneType($_SERVER['HTTP_USER_AGENT']),
                    'origin'=>$_SERVER['HTTP_REFERER']
                ]);
            }
        }
    }
    public function updateAccess(){
        $this->access(request('toid'),request('name'));
        return $this->jsonRertun(200,'更新聊天记录条数','更新聊天记录条数');
    }
    //记录用户访问了
    public function access($toid,$name){
        //记录用户访问了。
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        $clientIp=$this->clientIp;
        $number=Record::where(function ($query) use($clientIp){
            $query->where('fromid',$clientIp);
        })->orWhere(function($query) use($clientIp){
            $query->where('toid',$clientIp);
        })->count();
        Contact::updateOrInsert([
            'ip'=>$ip
        ],[
            'ip'=>$ip,
            'name'=>$name,
            'fromid'=>$toid,
            'recordnumber'=>$number,
            'address'=>$address[1].$address[2].$address[3],
            'blacklist'=>0,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
    }
    //转接给其他客服修改聊天记录的对话人
    public function updateToid($fromid,$toid){
        Record::where('fromid',$fromid)->update([
            'toid'=>$toid
        ]);
        Record::where('toid',$fromid)->update([
            'fromid'=>$toid
        ]);
        /*return Record::where('fromid',$fromid)
            ->orWhere('toid',$fromid)
            ->orderBy('created_at','desc')
            ->first();*/
    }
    //获取所有客服的状态
    public function getHosterState(){
        //获取所有客服的id
        $hosterList=User::role('客服')->get(['id'])->toArray();
        $hosterStateList=array_map(function ($res){
            return [
                'id'=>$res['id'],
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
        //加上分组循环是因为如果其他租户uid一样，那么就会错误
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
}
