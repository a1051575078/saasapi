<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Black;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Models\Tenant\Whitelist;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TenantController extends Controller{
    public function deleteBlack(Request $request){
        $hostname=Hostname::where('fqdn',$request->fqdn)->first();
        $website =Website::where('id',$hostname->website_id)->first();
        app(Environment::class)->tenant($website);
        Black::where('id',$request->id)->delete();
        return $this->jsonRertun(200,'删除黑名单成功','删除黑名单成功');
    }
    public function addTenancyWhitelist(Request $request){
        $hostname=Hostname::where('fqdn',$request->fqdn)->first();
        $website =Website::where('id',$hostname->website_id)->first();
        app(Environment::class)->tenant($website);
        $array=explode("\n",$request->textarea);
        Whitelist::where('id','!=',0)->delete();
        foreach ($array as $a){
            if(!empty($a)){
                Whitelist::create([
                    'ip'=>$a
                ]);
            }
        }
        return $this->jsonRertun(200,'添加白名单成功','添加白名单成功');
    }
    public function deleteAllTenant(Request $request){
        foreach($request->all() as $item){
            $hostname=Hostname::where('fqdn',$item['fqdn'])->first();
            $website=Website::where('id',$hostname->website_id)->first();
            Storage::disk('local')->deleteDirectory('/tenancy/tenants/'.$website->uuid);
            app(HostnameRepository::class)->delete($hostname,true);
            app(WebsiteRepository::class)->delete($website, true);
        }
        return $this->jsonRertun(200,'删除成功','删除成功');
    }
    public function index(){
        return $this->jsonRertun(200,'获取租户信息成功',Hostname::orderBy('updated_at','desc')->get());
    }
    public function create(){

    }
    public function checkfile($file,$format,$size){
        if(!$file){
            return $this->jsonRertun(402,'文件为空','文件为空');
        }
        //后缀
        $suffix=$file->getClientOriginalExtension();
        if($suffix!=$format){
            return $this->jsonRertun(402,'格式错误','格式错误');
        }
        //获取文件大小
        $filesize=$file->getSize();
        if($filesize/1024/1024>$size){
            return $this->jsonRertun(402,'文件太大','文件太大');
        }
    }
    public function upload($pkcs12,$password,$logPath){
        if (openssl_pkcs12_read($pkcs12, $certs,$password)) {
            $privateKey = $certs['pkey'];
            $publicKey = $certs['cert'];
            $myfile = fopen($logPath."server.key","w");
            fwrite($myfile, $privateKey);
            fclose($myfile);
            $myfile = fopen($logPath."server.crt","w");
            fwrite($myfile, $publicKey);
            fclose($myfile);
            return 200;
        }else{
            return 402;
        }
    }
    public function store(Request $request){
        $validator=Validator::make($request->all(),[
            'name'=>'required',
            'numbering'=>'required',
            'fqdn'=>'required'
        ]);
        if($validator->fails()){
            return $validator->errors()->all();
        }
        $website = new Website;
        app(WebsiteRepository::class)->create($website);
        $ssl=$request->file('ssl');
        if(!empty($ssl)&&!empty($request->password)&&$request->ishttp){
            if(empty($this->checkfile($ssl,'pfx',0.05))){
                $logPath = storage_path('app/tenancy/tenants/'.$website->uuid.'/');
                $pkcs12 = file_get_contents($ssl);
                $code=$this->upload($pkcs12,$request->password,$logPath);
                if($code===402){
                    Storage::disk('local')->deleteDirectory('/tenancy/tenants/'.$website->uuid);
                    app(WebsiteRepository::class)->delete($website,true);
                    return $this->jsonRertun(402,'证书密码错误或者无法解析','证书密码错误或者无法解析');
                }
                $ssl->move($logPath,'server.pfx');
                $ssl='storage/'.$website->uuid.'/server.pfx';
            }
        }else{
            $ssl='';
        }
        $hostname = new Hostname;
        $hostname->certificate =$ssl;
        $hostname->ishttp =$request->ishttp;
        $hostname->fqdn = 'api.'.$request->fqdn;
        $hostname->vue=$request->fqdn;
        $hostname->name=$request->name;
        $hostname->numbering=$request->numbering;
        $hostname->password=$request->password;
        $hostname->seat=$request->seat;
        $hostname->type=$request->type;
        $hostname->expiry_date=$request->expiry_date;
        $hostname=app(HostnameRepository::class)->create($hostname);
        app(HostnameRepository::class)->attach($hostname,$website);
        app( Environment::class )->hostname($hostname);
        $password=bcrypt(321789);
        $val=User::create([
            'name'=>$request->admin,
            'avatar'=>'images/avatar.gif',
            'password'=>$password,
        ]);
        $val->assignRole('管理员');
        $users=json_decode($request->user);
        if(!empty($users)){
            foreach ($users as $user){
                $v=User::create([
                    'name'=>$user->value,
                    'avatar'=>'images/avatar.gif',
                    'content'=>'',
                    'password'=>$password,
                    'music'=>'images/tip.wav'
                ]);
                $v->assignRole('客服');
            }
        }
        $_SERVER['SERVER_NAME']=config('app.httphost');
        return $this->jsonRertun(200,'添加成功','添加成功');
    }
    public function show($id){
        $hostname=Hostname::where('fqdn',$id)->first();
        $website =Website::where('id',$hostname->website_id)->first();
        app(Environment::class)->tenant($website);
        $users=User::all();
        $whitelists=Whitelist::all();
        $blacks=Black::orderBy('created_at','desc')->get();
        $ip='';
        foreach ($whitelists as $whitelist){
            $ip=$ip.$whitelist->ip."\n";
        }
        $_SERVER['SERVER_NAME']='';
        $array=[];
        foreach ($users as $user){
            $data['id']=$user->id;
            $data['name']=$user->name;
            $data['role']=$user->roles[0]->name;
            $array[]=$data;
        }
        $_SERVER['SERVER_NAME']=config('app.httphost');
        $datas['code']=200;
        $datas['msg']='查看用户信息';
        $datas['data']=$array;
        $datas['seat']=$hostname->seat;
        if(!empty($ip)){
            $ip=substr($ip, 0, -1);
        }
        $datas['whitelist']=$ip;
        $datas['blacklist']=$blacks;
        return response()->json($datas);
    }
    public function edit($id){
    }
    public function updateTenant(Request $request){
        $validator=Validator::make($request->all(),[
            'name'=>'required',
            'numbering'=>'required',
            'fqdn'=>'required'
        ]);
        if($validator->fails()){
            return $validator->errors()->all();
        }
        $hostname=Hostname::where('id',$request->id)->first();
        $website=Website::where('id',$hostname->website_id)->first();
        $ssl=$request->file('ssl');
        if(!empty($ssl)&&!empty($request->password)&&$request->ishttp){
            if(empty($this->checkfile($ssl,'pfx',0.05))){
                $logPath = storage_path('app/tenancy/tenants/'.$website->uuid.'/');
                $pkcs12 = file_get_contents($ssl);
                $code=$this->upload($pkcs12,$request->password,$logPath);
                if($code===402){
                    return $this->jsonRertun(402,'证书密码错误或者无法解析','证书密码错误或者无法解析');
                }
                $ssl->move($logPath,'server.pfx');
                $ssl='storage/'.$website->uuid.'/server.pfx';
            }
        }else{
            $ssl=$hostname->certificate;
        }
        if(empty($request->password)||$request->password==='null'){
            $password='';
        }else{
            $password=$request->password;
        }
        $hostname->certificate=$ssl;
        $hostname->ishttp=$request->ishttp;
        $hostname->fqdn= 'api.'.$request->fqdn;
        $hostname->vue=$request->fqdn;
        $hostname->name=$request->name;
        $hostname->numbering=$request->numbering;
        $hostname->password =$password;
        $hostname->seat = $request->seat;
        $hostname->type = $request->type;
        $hostname->expiry_date=$request->expiry_date;
        $hostname->updated_at=date('Y-m-d H:i:s',time());
        $hostname=app(HostnameRepository::class)->update($hostname);
        app(HostnameRepository::class)->attach($hostname, $website);
        return $this->jsonRertun(200,'修改成功','修改成功');
    }
    public function update(Request $request,$id){
        $validator=Validator::make($request->all(),[
            'name'=>'required',
            'numbering'=>'required',
            'fqdn'=>'required'
        ]);
        if($validator->fails()){
            return $validator->errors()->all();
        }
        $hostname=Hostname::where('id',$id)->first();
        $website=Website::where('id',$hostname->website_id)->first();
        $hostname->certificate=$request->certificate;
        $hostname->force_https=$request->force_https;
        $hostname->fqdn= 'api.'.$request->fqdn;
        $hostname->vue=$request->fqdn;
        $hostname->name=$request->name;
        $hostname->numbering=$request->numbering;
        $hostname->password =$request->password;
        $hostname->seat = $request->seat;
        $hostname->type = $request->type;
        $hostname->expiry_date=$request->expiry_date;
        $hostname->updated_at=date('Y-m-d H:i:s',time());
        $hostname=app(HostnameRepository::class)->update($hostname);
        app(HostnameRepository::class)->attach($hostname, $website);
        return $this->jsonRertun(200,'修改成功','修改成功');
    }
    public function destroy($fqdn){
        $hostname=Hostname::where('fqdn',$fqdn)->first();
        $website=Website::where('id',$hostname->website_id)->first();
        Storage::disk('local')->deleteDirectory('/tenancy/tenants/'.$website->uuid);
        app(HostnameRepository::class)->delete($hostname,true);
        app(WebsiteRepository::class)->delete($website, true);
        return $this->jsonRertun(200,'删除成功','删除成功');
    }
    public function storeUser(Request $request){
        $validator=Validator::make($request->all(),[
            'fqdn'=>'required'
        ]);
        if($validator->fails()){
            return $validator->errors()->all();
        }
        $hostname=Hostname::where('fqdn',$request->fqdn)->first();
        $hostname->seat=$request->seat;
        $website =Website::where('id',$hostname->website_id)->first();
        app(Environment::class)->tenant($website);
        $_SERVER['SERVER_NAME']='';
        $password=bcrypt(321789);
        foreach ($request->user as $user){
            $v=User::create([
                'name'=>$user['value'],
                'password'=>$password,
                'content'=>'',
                'avatar'=>'images/avatar.gif',
                'music'=>'images/tip.wav'
            ]);
            $v->assignRole('客服');
        }
        $users=User::all();
        $array=[];
        foreach ($users as $user){
            $data['id']=$user->id;
            $data['name']=$user->name;
            $data['role']=$user->roles[0]->name;
            $array[]=$data;
        }
        $_SERVER['SERVER_NAME']=config('app.httphost');
        app(HostnameRepository::class)->update($hostname);
        return $this->jsonRertun(200,'添加成功',$array);
    }
    public function delUser(Request $request){
        $validator=Validator::make($request->all(),[
            'fqdn'=>'required'
        ]);
        if($validator->fails()){
            return $validator->errors()->all();
        }
        $hostname=Hostname::where('fqdn',$request->fqdn)->first();
        $website =Website::where('id',$hostname->website_id)->first();
        app(Environment::class)->tenant($website);
        $_SERVER['SERVER_NAME']='';
        $user=User::where('id',$request->id)->first();
        if($user->hasRole('客服')){
            $hostname->seat=$hostname->seat+1;
        }
        User::where('id',$request->id)->delete();
        $_SERVER['SERVER_NAME']=config('app.httphost');
        app(HostnameRepository::class)->update($hostname);
        return $this->jsonRertun(200,'删除当前用户成功','删除当前用户成功');
    }
    public function editUser(Request $request){
        $hostname=Hostname::where('fqdn',$request->fqdn)->first();
        $website =Website::where('id',$hostname->website_id)->first();
        app(Environment::class)->tenant($website);
        if(empty($request->password)){
            User::where('id',$request->id)->update([
                'name'=>$request->name
            ]);
        }else{
            User::where('id',$request->id)->update([
                'name'=>$request->name,
                'password'=>bcrypt($request->password)
            ]);
        }
        return $this->jsonRertun(200,'修改用户信息成功','修改用户信息成功');
    }
}
