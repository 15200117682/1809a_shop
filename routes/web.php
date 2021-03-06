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
Route::get("/wechat/getWechat","WeChat\WeChatController@getWechat");//首次接入微信
Route::post("/wechat/getWechat","WeChat\WeChatController@WXEvent");//post接入微信
Route::any("/wechat/getAccessToken","WeChat\WeChatController@getAccessToken");//获取access_token
Route::any("/wechat/userInfo","WeChat\WeChatController@userInfo");//获取用户详细信息
Route::any("/wechat/customize","WeChat\WeChatController@customize");//自定义菜单
Route::any("/wechat/code","WeChat\WeChatController@code");//微信网页授权

Route::any("/wechat/send","WeChat\WeChatController@send");//用户群发消息

Route::any("/wechat/imgwen","WeChat\ImgWenController@imgwen");//图文消息回复

Route::any("/wechat/code","WeChat\ImgWenController@code");//创建二维码
Route::get('/goods/detail/{goods_id?}', 'WeChat\ImgWenController@goodsDetail');      //商品详情加二维码


Route::any("/wechat/pay","WxPay\WxPayController@WxPay");//用户群发消息

