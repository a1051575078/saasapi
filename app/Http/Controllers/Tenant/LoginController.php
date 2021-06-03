<?php

namespace App\Http\Controllers\Tenant;

use App\Cache\AdvancedRateLimiter;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Black;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Log;
use App\Models\Tenant\Online;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Record;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Models\Tenant\Whitelist;
use GatewayClient\Gateway;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Zhuzhichao\IpLocationZh\Ip;

class LoginController extends Controller {
    public function __construct(){
        $this->middleware('auth:api',['except'=>['login','logout','logout1']]);
    }
    protected function limiter(){
        return app(AdvancedRateLimiter::class);
    }
    public function username(){
        return 'name';
    }
    protected $maxAttempts = 3;
    protected $decayMinutes = [1,3,5];
    protected function throttleKey(Request $request){
        return Str::lower($request->input($this->username())).'|'.$this->getHttpIp();
    }
    public function maxAttempts(){
        return property_exists($this, 'maxAttempts') ? $this->maxAttempts : 5;
    }
    protected function hasTooManyLoginAttempts(Request $request){
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), $this->maxAttempts()
        );
    }
    protected function sendLockoutResponse(Request $request){
        return $this->limiter()->availableIn(
            $this->throttleKey($request)
        );
        /*throw ValidationException::withMessages([

            $this->username() => [Lang::get('auth.throttle', [
                'seconds'=>$seconds,
                'minutes'=>ceil($seconds/60),
            ])],
        ])->status(Response::HTTP_PAYMENT_REQUIRED);*/
    }
    public function decayMinutes(){
        return property_exists($this, 'decayMinutes') ? $this->decayMinutes : 1;
    }
    protected function incrementLoginAttempts(Request $request){
        $this->limiter()->hit(
            $this->throttleKey($request), array_map(function ($decayMinute) {
                return (int) ($decayMinute * 60);
            }, (array) $this->decayMinutes())
        );
    }
    //用户登录
    public function login(Request $request){
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
        if(!$token=auth()->attempt(['name'=>request('username'),'password'=>request('password')])) {
            //密码错误就加次数
            //$this->incrementLoginAttempts($request);
            return $this->jsonRertun(400,'账号或密码错误','账号或密码错误');
        }
        $this->canlogin($token,$ip,$address);
        $data['token']="Bearer ".$token;
        return $this->jsonRertun(200,'登录成功',$data);
        /*$whitelist=Whitelist::where('ip',$ip)->first();
        if(!empty($whitelist)){
            if(!$token=auth()->attempt(['name'=>request('username'),'password'=>request('password')])) {
                return $this->jsonRertun(400,'账号或密码错误','账号或密码错误');
            }
            $this->canlogin($token,$ip,$address);
            $data['token']="Bearer ".$token;
            return $this->jsonRertun(200,'登录成功',$data);
        }
        //判断用户是否登录过多
        if($this->hasTooManyLoginAttempts($request)){
            $time=$this->sendLockoutResponse($request);
            if($time>6){
                //登录次数过多，锁定账户
                Black::create([
                    'ip'=>$ip,
                    'address'=>$address[1].$address[2].$address[3],
                ]);
                Log::create([
                    'user_id'=>1,
                    'type'=>'尝试爆破',
                    'content'=>'IP:'.$ip.'密码输错3次,强制封禁IP',
                    'ip'=>$ip,
                    'address'=>$address[1].$address[2].$address[3]
                ]);
                return $this->jsonRertun(400,'IP已被永久封禁,请更换IP池重新尝试爆破','IP已被永久封禁,请更换IP池重新尝试爆破');
            }
            //登录次数过多，锁定账户
            return $this->jsonRertun(400,'请在'.$time.'秒后重试','爆破的应该');
        }else{
            if(!$token=auth()->attempt(['name'=>request('username'),'password'=>request('password')])) {
                //密码错误就加次数
                $this->incrementLoginAttempts($request);
                return $this->jsonRertun(400,'账号或密码错误','账号或密码错误');
            }
            $this->canlogin($token,$ip,$address);
            $data['token']="Bearer ".$token;
            return $this->jsonRertun(200,'登录成功',$data);
        }*/
    }
    //封装登录方法
    public function canlogin($token,$ip,$address){
        $user=auth()->user();
        //管理员登录,登录上去就会把自定义设置天数的聊天记录进行删除。
        if($user->hasRole('管理员')){
            $hostname=Hostname::where('fqdn',$_SERVER['SERVER_NAME'])->first();
            if(!empty($hostname->deleteday)&&$hostname->deleteday<30){
                $website=Website::where('id',$hostname->website_id)->first();
                app(Environment::class)->tenant($website);
                $day=Carbon::parse(-$hostname->deleteday.' days')->toDateTimeString();
                while (true){
                    $record=Record::where('created_at','<=',$day)->get();
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
                        })->where('created_at','<=',$day)->count();
                        Contact::where('ip',$ip)->decrement('recordnumber',$num);
                        Record::where(function ($query) use($visitors){
                            $query->where('fromid',$visitors)
                                ->orWhere('toid',$visitors);
                        })->where('created_at','<=',$day)->delete();
                        break;
                    }
                }
                Contact::where('created_at','<=',$day)->orWhere('recordnumber','<=',0)->delete();
            }
        }
        $apiGuard = auth('api');
        //如果是新号第一次登录,为空就把现在的token存到数据库里
        if(!empty($user->token)){
            $apiGuard->setToken($user->token);
        }
        // 检查旧 Token 是否有效, 被其他人更新token,那么这个token就失效
        if ($apiGuard->check()&&!empty($user->token)) {
            // 加入黑名单
            $apiGuard->invalidate();
        }
        Log::create([
            'user_id'=>$user->id,
            'type'=>'客服登录',
            'content'=>'',
            'ip'=>$ip,
            'address'=>$address[1].$address[2].$address[3],
        ]);
        User::where('id',$user->id)->update([
            'is_server'=>1,
            'token'=>$token
        ]);
    }
    //用户退出
    public function logout(){
        auth()->logout();
        if(!empty(request('client_id'))){
            Gateway::closeClient(request('client_id'));
        }
        return $this->jsonRertun(200,'退出成功','退出成功');
    }
    public function logout1(){
        if(!empty(request('client_id'))){
            Gateway::closeClient(request('client_id'));
        }
        return $this->jsonRertun(402,'您的账号已在其他地方登录,请注意修改密码','退出成功');
    }
    public function getUserInfo(){
        $user=auth()->user();
        $user->hasAllRoles(Role::all());
        $data['id']=$user->id;
        $data['name']=$user->name;
        $data['is_server']=$user->is_server;
        $data['roles']=$user->roles[0]->name;
        $data['avatar']=$user->avatar;
        $data['content']=$user->content;
        $data['music']=$user->music;
        if(!empty($user->roles)){
            $permission_id=[];
            foreach ($user->roles as $role){
                foreach ($role->permissions()->pluck('id') as $v){
                    $permission_id[]=$v;
                }
            }
        }
        $permission=array_unique($permission_id);
        // 第一层可以理解为从数组中键为0开始循环到最后一个
        for ($i = 0;$i < count($permission) ; $i++) {
            // 第二层为从$i+1的地方循环到数组最后
            for ($a=$i+1; $a < count($permission); $a++) {
                // 比较数组中两个相邻值的大小
                if ($permission[$i] > $permission[$a]) {
                    $tem = $permission[$i]; // 这里临时变量，存贮$i的值
                    $permission[$i] = $permission[$a]; // 第一次更换位置
                    $permission[$a] = $tem; // 完成位置互换
                }
            }
        }
        $arrays=[];
        foreach ($permission as $v){
            //1 2 6 7 8 9
            $arrays[]= Permission::where('id', '=', $v)->first()->toArray();
        }
        $data['menus']=$this->make_tree($this->getRoutes($arrays));
        $hostname=Hostname::where('fqdn',$_SERVER['SERVER_NAME'])->first();
        $data['jumplink']=$hostname->jumplink;
        $data['title']=$hostname->title;
        $data['deleteday']=$hostname->deleteday;
        return $this->jsonRertun(200,'获取信息成功',$data);
    }
    public function refresh(){
        return $this->respondWithToken(auth()->refresh());
    }
    protected function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL()*60
        ]);
    }
    public function getRoutes($permission=[]){
        foreach ($permission as $k=>$v){
            $array=[];
            foreach ($v as $j=>$val){
                if($val!==null){
                    if($j==='title'||$j==='icon'||$j==='noCache'||$j==='activeMenu'||$j==='affix'){
                        if($j==='affix'&&$v[$j]===1){
                            $v[$j]=true;
                        }
                        $array['meta'][$j]=$v[$j];
                    }else{
                        $array[$j]=$v[$j];
                    }
                }
            }
            $permission[$k]=$array;
        }
        return $permission;
    }
}
