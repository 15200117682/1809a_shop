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
Route::get("/info",function (){
    phpinfo();
});//查看php配置

//接受微信的时间驱动
Route::get("/wechat/getWechat","WeChat\WeChatController@getWechat");
Route::post("/wechat/WXEvent","WeChat\WeChatController@WXEvent");
