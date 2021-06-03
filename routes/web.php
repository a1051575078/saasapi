<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/aaa','Admin\AdminHandleController@test');
Route::post('/posts',function (){
    $data['msg']="成功";
    return response()->json($data);
});
Route::middleware('tenant.expired')->group(function (){
    Route::get('users', function() {
        dump(app('request')->url());
        dump(App\Models\Tenant\User::all()->pluck('name'));
    });
});
