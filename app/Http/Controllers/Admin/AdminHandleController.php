<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;
use Spatie\Permission\Models\Role;

class AdminHandleController extends Controller
{
    public function test(){
        $tempFileHandle=tmpfile();
        $contents = [
            '[client]',
            "user=".env('DB_USERNAME','root'),
            "password=".env('DB_PASSWORD',''),
            "host=".env('DB_HOST','127.0.0.1'),
            "port=".env('DB_PORT',3306),
        ];
        fwrite($tempFileHandle,implode("\n", $contents));
        $temporaryCredentialsFile = stream_get_meta_data($tempFileHandle)['uri'];
        $command="/www/server/mysql/bin/mysqldump --defaults-extra-file='{$temporaryCredentialsFile}' --extended-insert --databases 146696cea7db4702888df0380c213284 > /www/wwwroot/wancai/storage/app/tenancy/tenants/146696cea7db4702888df0380c213284/33.sql";
        echo $command;
        /*
         * port = 3333
socket		= /tmp/mysql.sock
host=localhost
user=root
password=TYDeCy66B4ijha3A*/
        //'mysqldump' --defaults-extra-file="/tmp/php2HE9mg" --skip-comments --extended-insert a885bd7e6b4b43e3882d802b61ecc825 > "1.sql"
        exec($command);
       /* MySql::create()
            ->setUserName('root')
            ->setPassword('TYDeCy66B4ijha3A')
            ->setDbName('a885bd7e6b4b43e3882d802b61ecc825')
            ->dumpToFile('1.sql');*/
        /*$db_user="root";//数据库账号
        $db_pwd='TYDeCy66B4ijha3A';//数据库密码
        $db_name="a885bd7e6b4b43e3882d802b61ecc825";//数据库名
        $filepath=storage_path('tenancy/tenants/a885bd7e6b4b43e3882d802b61ecc825/sql/1.sql');
        exec("/www/server/mysql/bin/mysqldump -u".$db_user." -p".$db_pwd." ".$db_name." > ".$filepath);*/

        //mysqldump -uroot -p --all-databases > /backup/mysqldump/all.db
        //'mysqldump' --defaults-extra-file="/tmp/phpoXfQef" --skip-comments --extended-insert a885bd7e6b4b43e3882d802b61ecc825 > "../storage/app/tenant/dump.sql"


        /*$filePath=public_path('/certificate.pfx');
        if(!file_exists($filePath)){
            return false;
        }
        $pkcs12 = file_get_contents($filePath);
        if (openssl_pkcs12_read($pkcs12, $certs,'8af3fd')) {
            $privateKey = $certs['pkey'];
            $publicKey = $certs['cert'];
            $signedMsg = "";
            $myfile = fopen("testfile.key", "w");
            fwrite($myfile, $privateKey);
            fclose($myfile);
            return $privateKey;
            /*if (openssl_sign('', $signedMsg, $privateKey)) {
                $signedMsg=bin2hex($signedMsg);//这个看情况。有些不需要转换成16进制，有些需要base64编码。看各个接口
                return $signedMsg;
            } else {
                return '';
            }
        } else {
            return '0';
        }*/
    }
    public function index()
    {
        //
        $admins=Admin::all();
        foreach ($admins as $admin){
            $data['name']=$admin->roles()->pluck('name')->implode(' &');
            $data['id']=$admin->roles()->pluck('id');
            $admin->roles=$data;
        }
        return $this->jsonRertun(200,'获取表格成功',$admins);
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
        // 验证 name、email 和 password 字段
        $this->validate($request, [
            'name'=>'required|max:120',
            'password'=>'required'
        ]);
        $user = Admin::create([
            'name'=>$request->name,
            'password'=>bcrypt($request->password)
        ]); //只获取 email、name、password 字段
        $roles = $request['roles']; // 获取输入的角色字段
        // 检查是否某个角色被选中
        if (isset($roles)) {
            foreach($roles as $role){
                $role_r = Role::where('id','=',$role)->firstOrFail();
                $user->assignRole($role_r);//Assigning role to user
            }
        }
        // 重定向到 users.index 视图并显示消息
        return $this->jsonRertun(200,'新增用户成功','新增用户成功');
    }

    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
        $user = Admin::findOrFail($id); // 通过id获取给定角色

        // 验证 name, email 和 password 字段
        $this->validate($request,[
            'name'=>'required|max:120',
        ]);
        if(empty($request->password)){
            Admin::where('id',$id)
                ->update([
                'name'=>$request->name,
            ]);
        }else{
            Admin::where('id',$id)
                ->update([
                'name'=>$request->name,
                'password'=>bcrypt($request->password)
            ]);
        }
        $roles = $request['roles']; // 获取所有角色
        if (isset($roles)) {
            $user->roles()->sync($roles);  // 如果有角色选中与用户关联则更新用户角色
        } else {
            //多态关联删除
            $user->roles()->detach(); // 如果没有选择任何与用户关联的角色则将之前关联角色解除
        }
        return $this->jsonRertun(200,'修改成功','修改成功');
    }

    public function destroy($id)
    {
        //
        // 通过给定id获取并删除用户
        $user = Admin::findOrFail($id);
        $user->delete();
        return $this->jsonRertun(200,'删除用户成功','删除用户成功');
    }
}
