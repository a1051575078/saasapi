<?php

namespace App\Console\Commands;

use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:backupdatabases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '备份数据库';

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
        $contents = [
            '[client]',
            "user=".env('DB_USERNAME','root'),
            "password=".env('DB_PASSWORD',''),
            "host=".env('DB_HOST','127.0.0.1'),
            "port=".env('DB_PORT',3306),
        ];
        $fileName=date('Y-m-d',time()).rand(1000,9999).'.sql';
        $database=env('DB_DATABASE','');
        $handler = opendir(public_path('/sql/'));
        $files=[];
        while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != "..") {
                $files[] = $filename ;
            }
        }
        closedir($handler);
        $tenancyCount=count($files);
        if($tenancyCount>6){
            for($i=0;$i<2;$i++){
                unlink(public_path('/sql/'.$files[$i]));
            }
        }
        $tempFileHandle=tmpfile();
        $path=public_path('/sql/'.$database.$fileName);
        fwrite($tempFileHandle,implode("\n", $contents));
        $temporaryCredentialsFile = stream_get_meta_data($tempFileHandle)['uri'];
        $command="/www/server/mysql/bin/mysqldump --defaults-extra-file='{$temporaryCredentialsFile}' --extended-insert --databases $database > $path";
        exec($command);
        $websites=Website::all();
        $tenantPath=storage_path('app/tenancy/tenants');
        foreach ($websites as $website){
            $sqls=Storage::files('/tenancy/tenants/'.$website->uuid.'/');
            $datas=[];
            foreach($sqls as $sql){
                $suffix=substr(strrchr($sql,'.'),1);
                if($suffix==='sql'){
                    $datas[]=substr(strrchr($sql,'/'),1);
                }
            }
            $count=count($datas);
            if($count>6){
                for($i=0;$i<2;$i++){
                    Storage::disk('local')->delete('/tenancy/tenants/'.$website->uuid.'/'.$datas[$i]);
                }
            }


            $tempFileHandle=tmpfile();
            fwrite($tempFileHandle,implode("\n", $contents));
            $temporaryCredentialsFile = stream_get_meta_data($tempFileHandle)['uri'];
            $command="/www/server/mysql/bin/mysqldump --defaults-extra-file='{$temporaryCredentialsFile}' --extended-insert --databases $website->uuid > $tenantPath/$website->uuid/$fileName";
            exec($command);
        }
    }
}
