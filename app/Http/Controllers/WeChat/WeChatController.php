<?php

namespace App\Http\Controllers\WeChat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class WeChatController extends Controller
{
    protected $redis_weixin_access_token = 'str:weixin_access_token';     //微信 access_token
    //首次接入微信
    public function getWechat(){
        echo $_GET['echostr'];
    }

    //post接入微
    public function WXEvent(){
        $data = file_get_contents("php://input");//通过流的方式接受post数据
        $time = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents("logs/wx_event.log",$time,FILE_APPEND);

        $obj=simplexml_load_string($data);
        var_dump($obj);
        echo "SUCCESS";
   }

   //获取access_token
    public function getAccessToken(){
        $token = Redis::get($this->redis_weixin_access_token);
        if(!$token){        // 无缓存 请求微信接口
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $data = json_decode(file_get_contents($url),true);
            //记录缓存
            $token = $data['access_token'];
            Redis::set($this->redis_weixin_access_token,$token);
            Redis::setTimeout($this->redis_weixin_access_token,3600);
        }
        return $token;
    }

    //获取用户信息
    public function openid(){

    }
}
