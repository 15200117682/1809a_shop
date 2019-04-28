<?php
use Illuminate\Support\Facades\Redis;
    function getAccessToken(){
        $key = 'getAccessToken';       // 1809a_wx_access_token
        //判断是否有缓存
        $access_token = Redis::get($key);
        if($access_token){
            return $access_token;
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $response = json_decode(file_get_contents($url),true);
            if(isset($response['access_token'])){
                Redis::set($key,$response['access_token']);
                Redis::expire($key,3600);
                return $response['access_token'];
            }else{
                return false;
            }
        }
    }