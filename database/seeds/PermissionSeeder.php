<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Admin\Admin;
class PermissionSeeder extends Seeder{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $_SERVER['SERVER_NAME']=config('app.httphost');
        // 重置角色和权限缓存
        app()['cache']->forget('spatie.permission.cache');
        $user=Admin::create([
            'name'=>'admin',
            'password'=>bcrypt(123456)
        ]);
        // 创建权限
        Permission::create([
            'name'=>"Tenant",
            'path'=>'/tenant',
            'component'=>'Layout',
            'redirect'=>'/tenant/index',
            'father_id'=>0,
            'title'=>'租户管理',
            'icon'=>'example',
            'guard_name'=>'admin'
        ]);
        Permission::create([
            'name'=>'TenantIndex',
            'path'=>'/tenant/index',
            'component'=>'/tenant/index',
            'father_id'=>1,
            'title'=>'租户列表',
            'guard_name'=>'admin'
        ]);
        /*Permission::create([
            'name'=>'godaddy编辑',
            'path'=>'/godaddy/index',
            'component'=>'/godaddy/index',
            'father_id'=>1,
            'title'=>'godaddy编辑',
            'guard_name'=>'admin'
        ]);*/
        Permission::create([
            'name'=>'Role',
            'path'=>'/role',
            'component'=>'Layout',
            'redirect'=>'/role/index',
            'father_id'=>0,
            'title'=>'系统管理',
            'icon'=>'lock',
            'guard_name'=>'admin'
        ]);
        Permission::create([
            'name'=>'RoleIndex',
            'path'=>'/role/index',
            'component'=>'/role/index',
            'father_id'=>3,
            'title'=>'角色权限',
            'guard_name'=>'admin'
        ]);
        Permission::create([
            'name'=>'RolePermission',
            'path'=>'/permission/index',
            'component'=>'/permission/index',
            'father_id'=>3,
            'title'=>'路由权限',
            'guard_name'=>'admin'
        ]);
        Permission::create([
            'name'=>'RoleUser',
            'path'=>'/user/index',
            'component'=>'/user/index',
            'father_id'=>3,
            'title'=>'用户列表',
            'guard_name'=>'admin'
        ]);

        // 创建角色并分配创建的权限
        $role = Role::create([
            'name'=>'超级管理员',
            'guard_name'=>'admin'
        ]);
        $role->givePermissionTo(Permission::all());

        $user->assignRole('超级管理员');
    }
}
