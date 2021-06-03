<?php

namespace App\Console\Commands;

use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DeleteDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:deletedisk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每月删除文件夹的内容';

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
    public function handle(){
        $websites=Website::all();
        foreach ($websites as $website){
            $chats=Storage::directories('/tenancy/tenants/'.$website->uuid.'/chat');
            if(!empty($chats)){
                unset($chats[count($chats)-1]);
                foreach($chats as $chat){
                    Storage::disk('local')->deleteDirectory('/'.$chat);
                }
            }
        }
    }
}
