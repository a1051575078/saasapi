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
//移动端
Route::get('/ishttp','Phone\NewController@getIsHttp')->middleware('throttle:10,1');

Route::post('logout', 'Tenant\LoginController@logout');//ok
Route::post('logout1', 'Tenant\LoginController@logout1');//ok
Route::post('close-client','Tenant\ClientController@closeClient')->middleware('throttle:30,1');
Route::group(['middleware'=>['tenant.expired','black']], function () {//'jwt.role:user','auth:api',
    /*Route::group(['middleware'=>['throttle:30|60,1']],function(){
    });*/
    Route::post('nihaoya', 'Tenant\AdminController@nihaoya');//ok
    Route::post('is-uid-online', 'Tenant\AdminController@isUidOnline');//ok
    Route::post('login', 'Tenant\LoginController@login');//ok
    Route::post('get-current-info', 'Tenant\ClientController@getCurrentInfo');//ok
    Route::post('client-scroll', 'Tenant\ClientController@infiniteScroll');//ok
    Route::get('choose-online-customer-service', 'Tenant\ClientController@chooseOnlineCustomerService');//ok
    Route::post('tell','Tenant\ClientController@tell');//ok
    Route::post('update-access','Tenant\ClientController@updateAccess');//ok
    Route::post('is-black','Tenant\ClientController@isBlack');//ok
    Route::post('add-evaluation','Tenant\ClientController@addEvaluation');//ok
    Route::post('bind','Tenant\ServerController@bind');//ok
    Route::post('sendPic','Tenant\ServerController@sendPic');//ok
    Route::post('infinite-scroll','Tenant\ServerController@infiniteScroll');//ok
    Route::post('send-msg','Tenant\ServerController@sendMsg');//ok
    Route::group(['middleware'=>['auth:api']],function(){
        Route::get('statistics', 'Tenant\WorkController@index');//ok
        Route::get('detailed', 'Tenant\WorkController@detailed');//ok
        Route::get('visitor', 'Tenant\WorkController@visitor');//ok
        Route::get('black', 'Tenant\WorkController@black');//ok
        Route::get('evaluation', 'Tenant\WorkController@evaluation');//ok


        Route::post('del-vip', 'Tenant\AdminController@delVip');//ok
        Route::post('del-all-vip', 'Tenant\AdminController@delAllVip');//ok
        Route::post('add-vip', 'Tenant\AdminController@addVip');//ok
        Route::get('vip', 'Tenant\AdminController@vip');//ok
        Route::post('translation', 'Tenant\AdminController@translation');//ok
        Route::post('imclick', 'Tenant\AdminController@imclick');//ok
        Route::post('online', 'Tenant\AdminController@online');//ok
        Route::post('black-end', 'Tenant\AdminController@blackEnd');//ok
        Route::post('find-online', 'Tenant\AdminController@findOnline');//ok
        Route::post('transfer', 'Tenant\AdminController@transfer');//ok
        Route::post('withdraw', 'Tenant\AdminController@withdraw');//ok
        Route::post('translation-record', 'Tenant\AdminController@translationRecord');//ok
        Route::post('send-me-client-give-other','Tenant\AdminController@sendMeClientGiveOther');//ok
        Route::post('tool-hang', 'Tenant\ToolController@toolHang');//ok

        Route::post('del-many-shortcut', 'Tenant\ShortcutController@delManyShortcut');//ok
        Route::post('upload-xsl', 'Tenant\ShortcutController@uploadXsl');//ok
        Route::resource('shortcut', 'Tenant\ShortcutController');//ok

        Route::resource('log', 'Tenant\LogController');//ok

        Route::post('user-del-many', 'Tenant\UserController@userDelMany');//ok
        Route::post('reset-pwd', 'Tenant\UserController@resetPwd');//ok
        Route::post('user-update', 'Tenant\UserController@updateUser');//ok
        Route::get('get-user', 'Tenant\UserController@getUser');//ok
        Route::resource('user', 'Tenant\UserController');//ok

        Route::post('del-add-black', 'Tenant\ContactController@delAddBlack');//ok
        Route::get('get-customer-info', 'Tenant\ContactController@getCustomerInfo');//ok
        Route::resource('contact', 'Tenant\ContactController');//ok
        Route::get('kcontact', 'Tenant\ContactController@kindex');//ok

        Route::get('user-info', 'Tenant\LoginController@getUserInfo');//ok

    });
    Route::post('refresh', 'Tenant\LoginController@refresh');
});
