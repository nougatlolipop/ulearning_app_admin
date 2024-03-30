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

Route::group(['namespace'=>'Api'],function () {   
    Route::post('/login', 'AuthController@createUser');

    Route::group(['middleware' => ['auth:sanctum']], function(){
        Route::any('/courseList','CourseController@courseList');
        Route::any('/courseDetail','CourseController@courseDetail');
        Route::any('/checkout','PayController@checkout');
    });

    Route::any('/web_go_hooks','PayController@web_go_hooks');

    
});
