<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
//Route::match(['post', 'get'], '/user/info', 'AdminController@getUserInfo');
Route::post('nihaoya', function (){
    $data['hello']="world";
    return response()->json($data);
});//ok
Route::get('test','Admin\AdminHandleController@test');
Route::post('login', 'Admin\LoginController@login')->middleware(['admin.black','throttle:30,1']);
Route::post('register', 'Admin\LoginController@register');
Route::post('logout', 'Admin\LoginController@logout');
/*Route::middleware('refresh')->group(function($router) {
    $router->get('refresh','Admin\LoginController@refresh');
});*/
Route::group(['middleware'=>['jwt.role:admin', 'auth:admin','admin.black','throttle:30,1']],function(){
    Route::get('user-info', 'Admin\LoginController@getUserInfo');
    Route::get('get-routes', 'Admin\LoginController@getRoutes');
    Route::post('delete-all-tenant', 'Admin\TenantController@deleteAllTenant');
    Route::post('store-user', 'Admin\TenantController@storeUser');
    Route::post('del-user', 'Admin\TenantController@delUser');
    Route::post('edit-user', 'Admin\TenantController@editUser');

    Route::resource('admins', 'Admin\AdminHandleController');
    Route::resource('permissions', 'Admin\PermissionController');
    Route::resource('roles', 'Admin\RoleController');
    Route::post('tenant-update','Admin\TenantController@updateTenant');
    Route::post('add-tenancy-whitelist','Admin\TenantController@addTenancyWhitelist');
    Route::post('delete-black','Admin\TenantController@deleteBlack');
    Route::resource('tenant','Admin\TenantController');
});

