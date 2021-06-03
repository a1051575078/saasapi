<?php

namespace App\Http\Controllers\Admin;

use App\Cache\AdvancedRateLimiter;
use App\Http\Controllers\Controller;
use App\Models\Admin\Black;
use App\Models\Admin\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Zhuzhichao\IpLocationZh\Ip;

class LoginController extends Controller{
    public function __construct(){
        $this->middleware('auth:admin',['except'=>['login']]);
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
    public function login(Request $request){
        $ip=$this->getHttpIp();
        $address=Ip::find($ip);
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
                    'ip'=>$ip,
                    'address'=>$address[1].$address[2].$address[3],
                    'content'=>'密码输错3次,强制封禁IP'
                ]);
                return $this->jsonRertun(400,'IP已被永久封禁,请更换IP池重新尝试爆破','IP已被永久封禁,请更换IP池重新尝试爆破');
            }
            //登录次数过多，锁定账户
            return $this->jsonRertun(400,'请在'.$time.'秒后重试','爆破的应该');
        }else{
            if(!$token=auth('admin')->attempt(['name'=>request('username'),'password'=>request('password')])){
                //密码错误就加次数
                $this->incrementLoginAttempts($request);
                return $this->jsonRertun(400,'账号或密码错误','账号或密码错误');
            }
            $data['token']="Bearer ".$token;
            $user=auth('admin')->user();
            Log::create([
                'content'=>$user->name.'登录',
                'ip'=>$ip,
                'address'=>$address[1].$address[2].$address[3],
            ]);
            return $this->jsonRertun(200,'登录成功',$data);
        }
    }
    public function getUserInfo(){
        $user=auth('admin')->user();
        $user->hasAllRoles(Role::all());
        $data['id']=$user->id;
        $data['name']=$user->name;
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
        for ($i = 0; $i < count($permission) ; $i++) {
            // 第二层为从$i+1的地方循环到数组最后
            for ($a = $i+1; $a < count($permission); $a++) {
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
        foreach ($arrays as $k=>$v){
            $array=[];
            foreach ($v as $j=>$val){
                if($val!==null){
                    if($j==='title'||$j==='icon'||$j==='noCache'||$j==='activeMenu'||$j==='affix'){
                        $array['meta'][$j]=$v[$j];
                    }else{
                        $array[$j]=$v[$j];
                    }
                }
            }
            $arrays[$k]=$array;
        }
        $data['menus']=$this->make_tree($arrays);
        return $this->jsonRertun(200,'获取信息成功',$data);
    }
    public function getRoutes($permission=[]){
        $permission=Permission::all()->toArray();
        foreach ($permission as $k=>$v){
            $array=[];
            foreach ($v as $j=>$val){
                if($val!==null){
                    if($j==='title'||$j==='icon'||$j==='noCache'||$j==='activeMenu'||$j==='affix'){
                        $array['meta'][$j]=$v[$j];
                    }else{
                        $array[$j]=$v[$j];
                    }
                }
            }
            $permission[$k]=$array;
        }
        return $this->jsonRertun(200,'获取路由成功',$this->make_tree($permission));
    }
    public function logout(){
        auth('admin')->logout();
        return $this->jsonRertun(200,'退出成功','退出成功');
    }
    public function refresh(){
        //中间件已经设置了。随便return
        return ;
    }
}
