<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller{
    public function index(){
        //
        return $this->jsonRertun(200,'获取角色成功', Role::get());
    }
    public function create(){
        //
    }
    public function digui($routes,$permission_id){
        foreach ($routes as $route){
            $permission_id[]=$route['id'];
            if(!empty($route['children'])){
                $permission_id=$this->digui($route['children'],$permission_id);
            }
        }
        return $permission_id;
    }
    public function store(Request $request){
        //验证 name 和 permissions 字段
        $this->validate($request, [
                'name'=>'required|unique:roles|max:10'
            ]
        );
        $name = $request['name'];
        $role = new Role();
        $role->name = $name;
        $permission_id=[];
        $routes = $request['routes'];
        $routes=$this->digui($routes,$permission_id);
        $role->save();
        $p_all = Permission::all()->pluck('id');//获取所有权限

        $p = Permission::whereIn('id', $routes)->pluck('id');
        //移除
        $role->revokePermissionTo($p_all);
        //附加
        $role->givePermissionTo($p);
        return $this->jsonRertun(200,'添加角色','添加角色');
    }
    public function show($id){
        //
    }

    public function edit($id){
        //
        $role = Role::findOrFail($id);
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
        return $this->jsonRertun(200,'获取信息成功',$this->make_tree($permission));
    }

    public function update(Request $request,$id){
        $role = Role::findOrFail($id); // 通过给定id获取角色
        // 验证 name 和 permission 字段
        $this->validate($request, [
            'name'=>'required|max:10|unique:roles,name,'.$id
        ]);
        $permission_id=[];
        $route = $request->routes;
        $permissions=$this->digui($route,$permission_id);
        $array=[];
        foreach ($permissions as $v){
            $p = Permission::where('id', '=', $v)->firstOrFail();
            $data['name']=$p->name;
            $array[]=$data;
        }
        $name['name']=$request->name;
        $role->fill($name)->save();
        $role->revokePermissionTo(Permission::all());
        $role->givePermissionTo($array);
        return $this->jsonRertun(200,'更新角色权限成功','更新角色权限成功');
    }
    public function destroy($id){
        //
        DB::table('roles')->where('id',$id)->delete();
        return $this->jsonRertun(200,'删除角色成功','删除角色成功');
    }
}
