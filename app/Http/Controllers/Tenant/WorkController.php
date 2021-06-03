<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Black;
use App\Models\Tenant\Evaluation;
use App\Models\Tenant\Online;
use App\Models\Tenant\Record;
use App\Models\Tenant\User;
use App\Models\Tenant\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WorkController extends Controller
{
    
    private $today;
    private $yesterday;
    private $twoday;
    private $threeday;
    private $fourday;
    private $fiveday;
    private $sixday;
    private $sevenday;
    private $delBefore;
    public function __construct(){
        $this->today=Carbon::parse('today')->toDateString();
        $this->yesterday=Carbon::parse('yesterday')->toDateString();
        $this->twoday=Carbon::parse('-2 days')->toDateString();
        $this->threeday=Carbon::parse('-3 days')->toDateString();
        $this->fourday=Carbon::parse('-4 days')->toDateString();
        $this->fiveday=Carbon::parse('-5 days')->toDateString();
        $this->sixday=Carbon::parse('-6 days')->toDateString();
        $this->sevenday=Carbon::parse('-7 days')->toDateString();
        $this->delBefore=Carbon::parse('-7 days')->toDateString().' 00:00';
    }
    //获取评价表
    public function evaluation(){
        return $this->jsonRertun(200,'获取评价信息成功',Evaluation::with('user')->orderBy('id','desc')->get());
    }
    //获得黑名单信息
    public function black(){
        return $this->jsonRertun(200,'获取黑名单信息成功',Black::orderBy('id','desc')->get());
    }
    //获取访客的表格
    public function visitor(){
        $data=Visitor::orderBy('id','desc')->get();
        return $this->jsonRertun(200,'获取访客表格成功',$data);
    }
    //获取客服的详细信息
    public function detailed(){
        $date=date('Y-m-d H:i:s',time());
        $users=User::role('客服')->get(['id','name']);
        $records=Record::where('created_at','<',$date)->where('created_at','>',$this->delBefore)->get();
        $evaluations=Evaluation::where('created_at','<',$date)->where('created_at','>',$this->delBefore)->get();
        $onlines=Online::where('created_at','<',$date)->where('created_at','>',$this->delBefore)->get();
        //坚决不投机取巧循环用sql
        //循环7次,有7个用户
        foreach ($users as $user){
            $msgnum=0;
            $clientnum=[];
            $msgnumy=0;
            $clientnumy=[];
            $msgnumb=0;
            $clientnumb=[];
            $r['today']['msgnum']=0;
            $r['today']['clientnum']=0;
            $r['yesterday']['msgnum']=0;
            $r['yesterday']['clientnum']=0;
            $r['before']['msgnum']=0;
            $r['before']['clientnum']=0;
            $todayAll=[];
            $yesterdayAll=[];
            $beforeAll=[];
            foreach ($records as $v){
                $dataDate=Carbon::parse($v->created_at)->toDateString();
                //如果这条消息属于发送的用户才加
                if($v->fromid==$user->id || $v->toid==$user->id){
                    //循环今天的数据
                    if($dataDate===$this->today){
                        if($user->id==$v->fromid){
                            //发送消息的数量
                            $r['today']['msgnum']=++$msgnum;
                        }else{
                            //接待有效客户的数量
                            array_push($clientnum,$v->fromid);
                            $r['today']['clientnum']=count(array_unique($clientnum));
                        }
                        array_push($todayAll,$v);
                    }else{
                        if($dataDate===$this->yesterday){
                            if($user->id==$v->fromid){
                                //发送消息的数量
                                $r['yesterday']['msgnum']=++$msgnumy;
                            }else{
                                //接待有效客户的数量
                                array_push($clientnumy,$v->fromid);
                                $r['yesterday']['clientnum']=count(array_unique($clientnumy));
                            }
                            array_push($yesterdayAll,$v);
                        }
                        if($user->id==$v->fromid){
                            //发送消息的数量
                            $r['before']['msgnum']=++$msgnumb;
                        }else{
                            //接待有效客户的数量
                            array_push($clientnumb,$v->fromid);
                            $r['before']['clientnum']=count(array_unique($clientnumb));
                        }
                        array_push($beforeAll,$v);
                    }
                }
            }
            $firstime=0;//首次响应时间
            $averagetime=0;//平均响应时间
            $dialogue=0;//对话平均时长
            $int=0;//首次响应时间次数
            $intp=0;//平均响应时间次数
            $intc=0;//平均响应时间次数
            $r['today']['firstime']=0;
            $r['today']['averagetime']=0;
            $r['today']['dialogue']=0;
            foreach(array_unique($clientnum) as $value){
                foreach ($todayAll as $v){
                    if(!(int)$v->fromid&&$value===$v->fromid){
                        foreach ($todayAll as $item){
                            if($v->fromid===$item->toid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($v->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<180){
                                    $firstime=$toidTime-$fromidTime+$firstime;
                                    ++$int;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                foreach ($todayAll as $val){
                    if(!(int)$val->fromid&&$value===$val->fromid){
                        foreach ($todayAll as $item){
                            if($val->fromid===$item->toid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($val->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<180){
                                    $averagetime=$toidTime-$fromidTime+$averagetime;
                                    ++$intp;
                                    break;
                                }
                            }
                        }
                    }
                }
                foreach ($todayAll as $va){
                    if(!(int)$va->fromid&&$value===$va->fromid){
                        foreach (array_reverse($todayAll) as $item){
                            if($va->fromid===$item->toid||$va->fromid===$item->fromid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($va->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<600){
                                    $dialogue=$toidTime-$fromidTime+$dialogue;
                                    ++$intc;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            if($int){
                $r['today']['firstime']=round($firstime/60/$int,2);;
            }
            if($intp){
                $r['today']['averagetime'] = round($averagetime / 60 / $intp, 2);;
            }
            if($intc){
                $r['today']['dialogue'] = round($dialogue / 60 / $intc, 2);;
            }


            $firstimey=0;//首次响应时间
            $averagetimey=0;//平均响应时间
            $dialoguey=0;//对话平均时长
            $inty=0;//首次响应时间次数
            $intpy=0;//平均响应时间次数
            $intcy=0;//平均响应时间次数
            $r['yesterday']['firstime']=0;
            $r['yesterday']['averagetime']=0;
            $r['yesterday']['dialogue']=0;
            foreach(array_unique($clientnumy) as $value){
                foreach ($yesterdayAll as $v){
                    if(!(int)$v->fromid&&$value===$v->fromid){
                        foreach ($yesterdayAll as $item){
                            if($v->fromid===$item->toid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($v->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<180){
                                    $firstimey=$toidTime-$fromidTime+$firstimey;
                                    ++$inty;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                foreach ($yesterdayAll as $val){
                    if(!(int)$val->fromid&&$value===$val->fromid){
                        foreach ($yesterdayAll as $item){
                            if($val->fromid===$item->toid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($val->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<180){
                                    $averagetimey=$toidTime-$fromidTime+$averagetimey;
                                    ++$intpy;
                                    break;
                                }
                            }
                        }
                    }
                }
                foreach ($yesterdayAll as $va){
                    if(!(int)$va->fromid&&$value===$va->fromid){
                        foreach (array_reverse($yesterdayAll) as $item){
                            if($va->fromid===$item->toid||$va->fromid===$item->fromid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($va->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<600){
                                    $dialoguey=$toidTime-$fromidTime+$dialoguey;
                                    ++$intcy;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            if($inty){
                $r['yesterday']['firstime']=round($firstimey/60/$inty,2);;
            }
            if($intpy){
                $r['yesterday']['averagetime'] = round($averagetimey / 60 / $intpy, 2);;
            }
            if($intcy){
                $r['yesterday']['dialogue'] = round($dialoguey / 60 / $intcy, 2);;
            }

            $firstimeb=0;//首次响应时间
            $averagetimeb=0;//平均响应时间
            $dialogueb=0;//对话平均时长
            $intb=0;//首次响应时间次数
            $intpb=0;//平均响应时间次数
            $intcb=0;//平均响应时间次数
            $r['before']['firstime']=0;
            $r['before']['averagetime']=0;
            $r['before']['dialogue']=0;
            foreach(array_unique($clientnumb) as $value){
                foreach ($beforeAll as $v){
                    if(!(int)$v->fromid&&$value===$v->fromid){
                        foreach ($beforeAll as $item){
                            if($v->fromid===$item->toid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($v->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<180){
                                    $firstimeb=$toidTime-$fromidTime+$firstimeb;
                                    ++$intb;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                foreach ($beforeAll as $val){
                    if(!(int)$val->fromid&&$value===$val->fromid){
                        foreach ($beforeAll as $item){
                            if($val->fromid===$item->toid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($val->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<180){
                                    $averagetimeb=$toidTime-$fromidTime+$averagetimeb;
                                    ++$intpb;
                                    break;
                                }
                            }
                        }
                    }
                }
                foreach ($beforeAll as $va){
                    if(!(int)$va->fromid&&$value===$va->fromid){
                        foreach (array_reverse($beforeAll) as $item){
                            if($va->fromid===$item->toid||$va->fromid===$item->fromid){
                                $toidTime=strtotime($item->created_at);
                                $fromidTime=strtotime($va->created_at);
                                if($toidTime>$fromidTime&&$toidTime-$fromidTime<600){
                                    $dialogueb=$toidTime-$fromidTime+$dialogueb;
                                    ++$intcb;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            if($intb){
                $r['before']['firstime']=round($firstimeb/60/$intb,2);;
            }
            if($intpb){
                $r['before']['averagetime'] = round($averagetimeb / 60 / $intpb, 2);;
            }
            if($intcb){
                $r['before']['dialogue'] = round($dialogueb / 60 / $intcb, 2);;
            }

            $good=0;
            $nogood=0;
            $goody=0;
            $nogoody=0;
            $goodb=0;
            $nogoodb=0;
            $r['today']['evaluation']=0;
            $r['yesterday']['evaluation']=0;
            $r['before']['evaluation']=0;
            foreach ($evaluations as $evaluation){
                if($evaluation->user_id==$user->id){
                    $dataDate=Carbon::parse($evaluation->created_at)->toDateString();
                    if($dataDate===$this->today){
                        if($evaluation->good){
                            ++$good;
                        }
                        if($evaluation->nogood){
                            ++$nogood;
                        }
                    }else{
                        if($dataDate===$this->yesterday){
                            if($evaluation->good){
                                ++$goody;
                            }
                            if($evaluation->nogood){
                                ++$nogoody;
                            }
                        }
                        if($evaluation->good){
                            ++$goodb;
                        }
                        if($evaluation->nogood){
                            ++$nogoodb;
                        }
                    }
                }
            }
            if($good+$nogood){
                $r['today']['evaluation']=round(100/($good+$nogood)*$good,2);
            }
            if($goody+$nogoody){
                $r['yesterday']['evaluation']=round(100/($goody+$nogoody)*$goody,2);
            }
            if($goodb+$nogoodb){
                $r['before']['evaluation']=round(100/($goodb+$nogoodb)*$goodb,2);
            }
            $todayOnline=[];
            $yesterdayOnline=[];
            $beforeOnline=[];
            foreach ($onlines as $online){
                if($online->user_id==$user->id){
                    $dataDate=Carbon::parse($online->created_at)->toDateString();
                    if($dataDate===$this->today){
                        array_push($todayOnline,$online);
                    }else{
                        if($dataDate===$this->yesterday){
                            array_push($yesterdayOnline,$online);
                        }
                        array_push($beforeOnline,$online);
                    }
                }
            }
            $todayzero=strtotime($this->today);
            $yesterdayzero=strtotime($this->yesterday);
            $series=[];
            $seriesy=[];
            $n=count($todayOnline);
            $ny=count($yesterdayOnline);
            $nb=0;
            for($v=0;$v<$n;$v++){
                if($todayOnline[$v]->tag){
                    $name='下线';
                }else{
                    $name='上线';
                }
                $time=strtotime($todayOnline[$v]->created_at);
                //今天的首次直接判断
                if($v){
                    $data['name']=$name;
                    $data['data']=[round(($time-strtotime($todayOnline[$v-1]->created_at))/60,2)];
                }else{
                    $data['name']=$name;
                    $data['data']=[round(($time-$todayzero)/60,2)];
                }
                $data['type']='bar';
                $data['stack']='时间';
                $data['barWidth']=20;//柱子宽度
                $data['label']['show']=false;
                $data['label']['position']='insideRight';
                array_push($series,$data);
            }
            for($v=0;$v<$ny;$v++){
                if($yesterdayOnline[$v]->tag){
                    $name='下线';
                }else{
                    $name='上线';
                }
                $time=strtotime($yesterdayOnline[$v]->created_at);
                if($v){
                    $data['name']=$name;
                    $data['data']=[round(($time-strtotime($yesterdayOnline[$v-1]->created_at))/60,2)];
                }else{
                    $data['name']=$name;
                    $data['data']=[round(($time-$yesterdayzero)/60,2)];
                }
                $data['type']='bar';
                $data['stack']='时间';
                $data['barWidth']=20;//柱子宽度
                $data['label']['show']=false;
                $data['label']['position']='insideRight';
                array_push($seriesy,$data);
            }
            $seriesb=[];
            foreach ($beforeOnline as $before){
                //他开始上线
                if($before->tag){
                    foreach($beforeOnline as $b){
                        if(strtotime($before->created_at)<strtotime($b->created_at)&&!$b->tag){
                            $nb=$nb+strtotime($b->created_at)-strtotime($before->created_at);
                            break;
                        }
                    }
                }
            }
            if($nb){
                $name='上线';
                $data['data']=[round($nb/60,2)];
            }else{
                $name='下线';
                $data['data']=[1440];
            }
            $data['name']=$name;
            $data['type']='bar';
            $data['stack']='时间';
            $data['barWidth']=20;//柱子宽度
            $data['label']['show']=false;
            $data['label']['position']='insideRight';
            array_push($seriesb,$data);
            //今天有值的情况下
            if($n){
                $lastime=(strtotime($date)-strtotime($todayOnline[$n-1]->created_at))/60;
                if($todayOnline[$n-1]->tag){
                    $name='上线';
                }else{
                    $name='下线';
                }
                $data['name']=$name;
                $data['type']='bar';
                $data['stack']='时间';
                $data['barWidth']=20;//柱子宽度
                $data['label']['show']=false;
                $data['label']['position']='insideRight';
                $data['data']=[round($lastime,2)];
            }else{
                $isOnline=Online::where('user_id',$user->id)->orderBy('id','desc')->first();
                if($isOnline){
                    if($isOnline->tag){
                        $name='上线';
                    }else{
                        $name='下线';
                    }
                    $data['name']=$name;
                    $data['type']='bar';
                    $data['stack']='时间';
                    $data['barWidth']=20;//柱子宽度
                    $data['label']['show']=false;
                    $data['label']['position']='insideRight';
                    $data['data']=[round((strtotime($date)-strtotime($this->today))/60,2)];
                }else{
                    $data['name']='下线';
                    $data['type']='bar';
                    $data['stack']='时间';
                    $data['barWidth']=20;//柱子宽度
                    $data['label']['show']=false;
                    $data['label']['position']='insideRight';
                    $data['data']=[round((strtotime($date)-strtotime($this->today.' 00:00:00'))/60,2)];
                }
            }
            array_push($series,$data);
            //昨天有值的情况下
            if($ny){
                $lastime=(strtotime($this->yesterday.' 23:59:59')-strtotime($yesterdayOnline[$ny-1]->created_at))/60;
                if($yesterdayOnline[$ny-1]->tag){
                    $name='上线';
                }else{
                    $name='下线';
                }
                $data['name']=$name;
                $data['type']='bar';
                $data['stack']='时间';
                $data['barWidth']=20;//柱子宽度
                $data['label']['show']=false;
                $data['label']['position']='insideRight';
                $data['data']=[round($lastime,2)];
            }else{
                $isOnline=Online::where('user_id',$user->id)->where('created_at','<',$this->yesterday.' 00:00:00')->orderBy('id','desc')->first();
                if($isOnline){
                    if($isOnline->tag){
                        $name='上线';
                    }else{
                        $name='下线';
                    }
                    $data['name']=$name;
                    $data['type']='bar';
                    $data['stack']='时间';
                    $data['barWidth']=20;//柱子宽度
                    $data['label']['show']=false;
                    $data['label']['position']='insideRight';
                    $data['data']=[1440];
                }else{
                    $data['name']='下线';
                    $data['type']='bar';
                    $data['stack']='时间';
                    $data['barWidth']=20;//柱子宽度
                    $data['label']['show']=false;
                    $data['label']['position']='insideRight';
                    $data['data']=[1440];
                }
            }
            array_push($seriesy,$data);
            $data['name']='今日剩余时间';
            $data['type']='bar';
            $data['stack']='时间';
            $data['barWidth']=20;//柱子宽度
            $data['label']['show']=false;
            $data['label']['position']='insideRight';
            $data['data']=[round((strtotime($this->today.' 23:59:59')-strtotime($date))/60,2)];
            array_push($series,$data);
            $r['today']['series']=$series;
            $r['yesterday']['series']=$seriesy;
            $r['before']['series']=$seriesb;
            $user->info=$r;
        }
        return  $this->jsonRertun(200,'获取客服的信息成功',$users);
    }
    //获得首页的统计
    public function index(){
        $data=[];
        $statistics=[$this->today,$this->yesterday,$this->twoday,$this->threeday,$this->fourday,$this->fiveday,$this->sixday,$this->sevenday];
        //得到图形表时间需要的数据[1,2,3,3,3,2,3,4,5]
        $user=User::pluck('id');
        $getTodayMsg=Record::where('created_at','<',$this->today.' 23:59')->where('created_at','>',$this->today.' 00:00')
            ->whereIntegerInRaw('fromid',$user)
            ->get();
        $getTodayBlack=Black::where('created_at','<',$this->today.' 23:59')->where('created_at','>',$this->today.' 00:00')->get();
        $getTodayVisitor=Visitor::where('created_at','<',$this->today.' 23:59')->where('created_at','>',$this->today.' 00:00')->get();
        $getTodayEvaluation=Evaluation::where('created_at','<',$this->today.' 23:59')->where('created_at','>',$this->today.' 00:00')->get();


        $getYesterdayMsg=Record::where('created_at','<',$this->yesterday.' 23:59')->where('created_at','>',$this->yesterday.' 00:00')
            ->whereIntegerInRaw('fromid',$user)
            ->get();
        $getYesterdayBlack=Black::where('created_at','<',$this->yesterday.' 23:59')->where('created_at','>',$this->yesterday.' 00:00')->get();
        $getYesterdayVisitor=Visitor::where('created_at','<',$this->yesterday.' 23:59')->where('created_at','>',$this->yesterday.' 00:00')->get();
        $getYesterdayEvaluation=Evaluation::where('created_at','<',$this->yesterday.' 23:59')->where('created_at','>',$this->yesterday.' 00:00')->get();



        $record=$this->recording($getTodayMsg,$this->today);
        $black=$this->recording($getTodayBlack,$this->today);
        $visitor=$this->recording($getTodayVisitor,$this->today);
        $evaluation=$this->recording($getTodayEvaluation,$this->today);

        $recordy=$this->recording($getYesterdayMsg,$this->yesterday);
        $blacky=$this->recording($getYesterdayBlack,$this->yesterday);
        $visitory=$this->recording($getYesterdayVisitor,$this->yesterday);
        $evaluationy=$this->recording($getYesterdayEvaluation,$this->yesterday);

        $arrayRecord=[];
        $arrayBlack=[];
        $arrayVisitor=[];
        $arrayEvaluation=[];
        $array=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
        $data['today']['record']=$array;
        $data['today']['black']=$array;
        $data['today']['visitor']=$array;
        $data['today']['evaluations']=$array;
        $data['yesterday']['record']=$array;
        $data['yesterday']['black']=$array;
        $data['yesterday']['visitor']=$array;
        $data['yesterday']['evaluations']=$array;
        $data['before']['record']=$array;
        $data['before']['black']=$array;
        $data['before']['visitor']=$array;
        $data['before']['evaluations']=$array;
        foreach ($statistics as $statistic){
            if($this->today===$statistic){
                $data['today']['blacks']=array_sum($black);
                $data['today']['msg']=array_sum($record);
                $data['today']['visitors']=array_sum($visitor);
                $data['today']['evaluation']=array_sum($evaluation);
                $data['today']['record']=$record;
                $data['today']['black']=$black;
                $data['today']['visitor']=$visitor;
                $data['today']['evaluations']=$evaluation;
            }else{
                if($this->yesterday===$statistic){
                    $data['yesterday']['blacks']=array_sum($blacky);
                    $data['yesterday']['msg']=array_sum($recordy);
                    $data['yesterday']['visitors']=array_sum($visitory);
                    $data['yesterday']['evaluation']=array_sum($evaluationy);
                    $data['yesterday']['record']=$recordy;
                    $data['yesterday']['black']=$blacky;
                    $data['yesterday']['visitor']=$visitory;
                    $data['yesterday']['evaluations']=$evaluationy;
                }
                $getBeforeMsg=Record::where('created_at','<',$statistic.' 23:59')->where('created_at','>',$statistic.' 00:00')
                    ->whereIntegerInRaw('fromid',$user)
                    ->get();
                $getBeforeBlack=Black::where('created_at','<',$statistic.' 23:59')->where('created_at','>',$statistic.' 00:00')->get();
                $getBeforeVisitor=Visitor::where('created_at','<',$statistic.' 23:59')->where('created_at','>',$statistic.' 00:00')->get();
                $getBeforeEvaluation=Evaluation::where('created_at','<',$statistic.' 23:59')->where('created_at','>',$statistic.' 00:00')->get();
                $arrayRecord=$this->beforeRecording($this->recording($getBeforeMsg,$statistic),$arrayRecord);
                $arrayBlack=$this->beforeRecording($this->recording($getBeforeBlack,$statistic),$arrayBlack);
                $arrayVisitor=$this->beforeRecording($this->recording($getBeforeVisitor,$statistic),$arrayVisitor);
                $arrayEvaluation=$this->beforeRecording($this->recording($getBeforeEvaluation,$statistic),$arrayEvaluation);
            }
        }
        $data['before']['blacks']=array_sum($arrayBlack);
        $data['before']['msg']=array_sum($arrayRecord);
        $data['before']['visitors']=array_sum($arrayVisitor);
        $data['before']['evaluation']=array_sum($arrayEvaluation);
        $data['before']['record']=$arrayRecord;
        $data['before']['black']=$arrayBlack;
        $data['before']['visitor']=$arrayVisitor;
        $data['before']['evaluations']=$arrayEvaluation;

        return $this->jsonRertun(200,'获取首页的统计信息成功',$data);
    }
    //记录总数
    public function recording($recording,$day){
        $one=0;
        $two=0;
        $three=0;
        $four=0;
        $five=0;
        $six=0;
        $seven=0;
        $eight=0;
        $nine=0;
        $ten=0;
        $eleven=0;
        $twelve=0;
        $thirteen=0;
        $fourteen=0;
        $fifteen=0;
        $sixteen=0;
        $seventeen=0;
        $eighteen=0;
        $nineteen=0;
        $twenty=0;
        $twentyOne=0;
        $twentyTwo=0;
        $twentyThree=0;
        $twentyFour=0;
        foreach ($recording as $value){
            if($value->created_at<$day.' 01:00'){
                $one=++$one;
            }elseif($value->created_at<$day.' 02:00'){
                $two=++$two;
            }elseif($value->created_at<$day.' 03:00'){
                $three=++$three;
            }elseif($value->created_at<$day.' 04:00'){
                $four=++$four;
            }elseif($value->created_at<$day.' 05:00'){
                $five=++$five;
            }elseif($value->created_at<$day.' 06:00'){
                $six=++$six;
            }elseif($value->created_at<$day.' 07:00'){
                $seven=++$seven;
            }elseif($value->created_at<$day.' 08:00'){
                $eight=++$eight;
            }elseif($value->created_at<$day.' 09:00'){
                $nine=++$nine;
            }elseif($value->created_at<$day.' 10:00'){
                $ten=++$ten;
            }elseif($value->created_at<$day.' 11:00'){
                $eleven=++$eleven;
            }elseif($value->created_at<$day.' 12:00'){
                $twelve=++$twelve;
            }elseif($value->created_at<$day.' 13:00'){
                $thirteen=++$thirteen;
            }elseif($value->created_at<$day.' 14:00'){
                $fourteen=++$fourteen;
            }elseif($value->created_at<$day.' 15:00'){
                $fifteen=++$fifteen;
            }elseif($value->created_at<$day.' 16:00'){
                $sixteen=++$sixteen;
            }elseif($value->created_at<$day.' 17:00'){
                $seventeen=++$seventeen;
            }elseif($value->created_at<$day.' 18:00'){
                $eighteen=++$eighteen;
            }elseif($value->created_at<$day.' 19:00'){
                $nineteen=++$nineteen;
            }elseif($value->created_at<$day.' 20:00'){
                $twenty=++$twenty;
            }elseif($value->created_at<$day.' 21:00'){
                $twentyOne=++$twentyOne;
            }elseif($value->created_at<$day.' 22:00'){
                $twentyTwo=++$twentyTwo;
            }elseif($value->created_at<$day.' 23:00'){
                $twentyThree=++$twentyThree;
            }else{
                $twentyFour=++$twentyFour;
            }
        }
        return [$one,$two,$three,$four,$five,$six,$seven,$eight,$nine,$ten,$eleven,$twelve,$thirteen,$fourteen,$fifteen,$sixteen,$seventeen,$eighteen,$nineteen,$twenty,$twentyOne,$twentyTwo,$twentyThree,$twentyFour];
    }
    //循环以前的每小时时间段的数据
    public function beforeRecording($data,$array){
        if(empty($array)||!count($array)){
            $array=$data;
        }else{
            for($i=0;$i<count($data);$i++){
                $array[$i]=$data[$i]+$array[$i];
            }
        }
        return $array;
    }
}
