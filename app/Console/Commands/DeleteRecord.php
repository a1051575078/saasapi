<?php

namespace App\Console\Commands;

use App\Models\Tenant\Contact;
use App\Models\Tenant\Evaluation;
use App\Models\Tenant\Log;
use App\Models\Tenant\Online;
use App\Models\Tenant\Record;
use App\Models\Tenant\Visitor;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DeleteRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:deleterecord';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日删除租户30天以前的聊天内容';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $websites=Website::all();
        foreach($websites as $website){
            app(Environment::class)->tenant($website);
            $month=Carbon::parse('-1 months')->toDateTimeString();
            $day=Carbon::parse('-8 days')->toDateTimeString();
            while (true){
                $record=Record::where('created_at','<=',$month)->get();
                if(empty($record)||!count($record)){
                    break;
                }
                foreach ($record as $v){
                    //如果fromid是客服,那么访客就是toid
                    if((int)$v->fromid){
                        $visitors=$v->toid;
                    }else{
                        $visitors=$v->fromid;
                    }
                    $ip=explode('访客', $visitors)[1];
                    $num=Record::where(function ($query) use($visitors){
                        $query->where('fromid',$visitors)
                            ->orWhere('toid',$visitors);
                    })->where('created_at','<=',$month)->count();
                    Contact::where('ip',$ip)->decrement('recordnumber',$num);
                    Record::where(function ($query) use($visitors){
                        $query->where('fromid',$visitors)
                            ->orWhere('toid',$visitors);
                    })->where('created_at','<=',$month)->delete();
                    break;
                }
            }
            Contact::where('created_at','<=',$month)->orWhere('recordnumber','<=',0)->delete();
            Log::where('created_at','<=',$month)->delete();
            Visitor::where('created_at','<=',$month)->delete();
            Evaluation::where('created_at','<=',$month)->delete();
            Online::where('created_at','<=',$day)->delete();
        }
    }
}
