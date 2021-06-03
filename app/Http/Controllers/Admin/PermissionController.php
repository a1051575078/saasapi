<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller{
    public function index(){
        //
        $roles=Role::all();
        foreach ($roles as $role){
            $array=$role->permissions()->pluck('name');
            $str='';
            foreach ($array as $v){
                $str.=$v." &";
            }
            $role['role']=substr($str, 0,-1);
            $permission=$role->permissions->toArray();
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
            $role['routes']=$this->make_tree($permission);
        }
        return $this->jsonRertun(200,'获取所有的角色',$roles);
    }
    public function create(){
        //
        return $this->jsonRertun(200,'获取角色成功', Role::get());
    }
    public function store(Request $request){
        $this->validate($request, [
            'title'=>'required|max:40',
        ]);
        $name = $request['title'];
        $permission = new Permission();
        if($request->father_id===0){
            $permission->component='Layout';
        }else{
            $permission->component=$request->path;
        }
        $permission->name = $name;
        $permission->path = $request->path;
        $permission->father_id=$request->father_id;
        $permission->title = $name;
        $roles = $request['roles'];
        $permission->save();
        if (!empty($request['roles'])) { // 如果选择了角色
            foreach ($roles as $role) {
                $r = Role::where('id', '=', $role)->firstOrFail(); // 将输入角色和数据库记录进行匹配
                $permission = Permission::where('name', '=', $name)->first(); // 将输入权限与数据库记录进行匹配
                $r->givePermissionTo($permission);
            }
        }
        return $this->jsonRertun(200,'添加权限成功','添加权限成功');
    }
    public function show($id){
        //
    }
    public function edit($id){
        //
    }
    public function update(Request $request, $id){
        $permission = Permission::findOrFail($id);
        $this->validate($request, [
            'title'=>'required|max:40',
        ]);
        $input = $request->all();
        if($request->father_id!==0){
            $input['component']=$request->path;
        }else{
            $input['component']='Layout';
        }
        $permission->fill($input)->save();
        return $this->jsonRertun(200,'修改权限成功','修改权限成功');
    }
    public function destroy($id){
        //
        DB::table('permissions')->where('id',$id)->delete();
        return $this->jsonRertun(200,'删除权限成功','删除权限成功');
    }
}
