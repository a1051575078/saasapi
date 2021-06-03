<?php

namespace App\Console;

use App\Console\Commands\DeleteRecord;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    //应用中定义Artisan命令
    protected $commands = [
        //
       // DeleteRecord::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    //定义计划任务
    protected function schedule(Schedule $schedule){
        // $schedule->command('inspire')->hourly();
        /*$schedule->call(function(){
            DB::table('aaa')->increment('name');
        })->everyMinute();*/
        //$schedule->command('command:deleterecord')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
