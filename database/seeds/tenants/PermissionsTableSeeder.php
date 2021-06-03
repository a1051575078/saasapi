<?php

use Illuminate\Database\Seeder;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        $_SERVER['SERVER_NAME']='';
        // 重置角色和权限缓存
        app()['cache']->forget('spatie.permission.cache');
        //创建权限
        Permission::create([
            'name'=>'Home',
            'path'=>'/home',
            'component'=>'Layout',
            'redirect'=>'/home/index',
            'father_id'=>0,
            'title'=>'首页',
            'icon'=>'dashboard',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'HomeIndex',
            'path'=>'/home/index',
            'component'=>'/home/index',
            'father_id'=>1,
            'affix'=>1,
            'title'=>'聊天',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'Dashboard',
            'path'=>'/dashboard',
            'component'=>'Layout',
            'redirect'=>'/dashboard/statistics/index',
            'father_id'=>0,
            'title'=>'工作台',
            'icon'=>'dashboard',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'DashboardStatistics',
            'path'=>'/dashboard/statistics/index',
            'component'=>'/dashboard/statistics/index',
            'father_id'=>3,
            'affix'=>1,
            'title'=>'主页',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'DashboardReport',
            'path'=>'/dashboard/report/index',
            'component'=>'/dashboard/report/index',
            'father_id'=>3,
            'title'=>'会员信息',
            'guard_name'=>'api'
        ]);

        Permission::create([
            'name'=>'Info',
            'path'=>'/info',
            'component'=>'Layout',
            'redirect'=>'/info/settings-info',
            'father_id'=>0,
            'title'=>'信息中心',
            'icon'=>'table',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'InfoSettingsInfo',
            'path'=>'/info/settings-info',
            'component'=>'/info/settings-info/index',
            'father_id'=>6,
            'title'=>'个人信息',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'InfoDataManagement',
            'path'=>'/info/data-management',
            'component'=>'/info/customer-management/datamanagement/index',
            'father_id'=>6,
            'title'=>'客服信息',
            'guard_name'=>'api'
        ]);
        Permission::create([
            'name'=>'InfoLog',
            'path'=>'/info/log',
            'component'=>'/info/log/index',
            'father_id'=>6,
            'title'=>'操作日志',
            'guard_name'=>'api'
        ]);

        // 创建角色并赋予已创建的权限
        $role =Role::create(['name' => '管理员', 'guard_name'=>'api']);
        $role->givePermissionTo('Info');
        $role->givePermissionTo('InfoSettingsInfo');
        $role->givePermissionTo('InfoDataManagement');
        $role->givePermissionTo('InfoLog');
        $role->givePermissionTo('Dashboard');
        $role->givePermissionTo('DashboardStatistics');
        $role->givePermissionTo('DashboardReport');
        $role1=Role::create(['name' => '客服', 'guard_name'=>'api']);
        $role1->givePermissionTo('Home');
        $role1->givePermissionTo('HomeIndex');
        $role1->givePermissionTo('Info');
        $role1->givePermissionTo('InfoSettingsInfo');
    }
}
