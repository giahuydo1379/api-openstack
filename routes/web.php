<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('zz/{userId}', ['uses' => 'ServerController@zz']);
Route::post('zz', ['uses' => 'ServerController@zzpost']);



Route::group(['prefix' => 'api'], function () {
    // api server
    Route   ::group(['prefix' => 'server'], function () {
        Route::post('create', 'ServerController@Create');
        Route::get('list/{userId}', 'ServerController@List');
        Route::get('checkstatus/{vmId}/{userId}', 'ServerController@CheckStatus');
        Route::get('action/{action}/{vmId}/{userId}', 'ServerController@Action');
//        Route::get('detail/{vmId}', 'ServerController@Detail');
        Route::get('schedulecheckstatus', 'ServerController@ScheduleCheckStatus');

        //these api use for test
        Route::get('checkallstatus', 'ServerController@TestCheckAllStatus');
        Route::get('teststatus/{vmId}/{userId}', 'ServerController@TestStatus');

        Route::get('search/', 'ServerInsightController@Search');
        Route::get('detail/{vmId}', 'ServerInsightController@Detail');
    });
    // api package
    Route::group(['prefix' => 'package'], function () {
        Route::get('list', 'PackageController@List');
        Route::get('listdetailos', 'PackageController@ListDetailOS');
        Route::get('get/{cpu}/{ram}/{disk}/{day}', 'PackageController@Get');
    });
    // api users
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', ['uses' => 'UserController@List']);
        Route::get('/search', ['uses' => 'UserController@Search']);
//        Route::get('/{id}/server', ['uses' => 'UserController@ListServer']);
        Route::post('/synchronous', ['uses' => 'UserController@Synchronous']);
        Route::post('/user-info/{id}', ['uses' => 'UserController@postUserInfo']);
    });


    Route::group(['prefix' => 'customer'], function () {
        Route::get('detail/{id}', 'ServerInsightController@DetailCustomer');
    });
    // api identity
    Route::group(['prefix' => 'identity'], function () {
        Route::post('/access-token', ['uses' => 'IdentityController@getAccessToken']);
    });
    // api mail
    Route::group(['prefix' => 'mail'], function () {
        Route::get('/send/{mail}', ['uses' => 'MailController@SendMail']);
        Route::get('/save/{mail}', ['uses' => 'ApiController@SaveMailTest']);
        Route::get('/schedulecheckmail', ['uses' => 'MailController@ScheduleCheckMail']);
//        Route::get('/test', ['uses' => 'MailController@Test']);
    });

    // api test server
    Route   ::group(['prefix' => 'server/test'], function () {
        Route::get('getaccesstoken/{userid}', 'TestController@TestGetAccessOpenstack');
    });
});


