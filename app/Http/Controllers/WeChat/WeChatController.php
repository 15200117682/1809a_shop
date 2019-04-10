<?php

namespace App\Http\Controllers\WeChat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Model\User\UserModel;

class WeChatController extends Controller
{
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
        $ToUserName=$obj->ToUserName;
        $FromUserName=$obj->FromUserName;
        $CreateTime=$obj->CreateTime;
        $MsgType=$obj->MsgType;
        $Event=$obj->Event;
        if($MsgType=="event"){
            if($Event=="subscribe"){
                $userInfo=$this->userInfo($FromUserName);

                $one=UserModel::where(['openid'=>$FromUserName])->first();
                if($one){
                    $xml="<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎".$userInfo['nickname']."回来]]></Content>
                            </xml>";
                    echo $xml;
                }else{
                    $array=array(
                        "openid"=>$userInfo['openid'],
                        "nickname"=>$userInfo['nickname'],
                        "city"=>$userInfo['city'],
                        "province"=>$userInfo['province'],
                        "country"=>$userInfo['country'],
                        "headimgurl"=>$userInfo['headimgurl'],
                        "subscribe_time"=>$userInfo['subscribe_time'],
                        "sex"=>$userInfo['sex'],
                    );
                    $res=UserModel::insertGetId($array);
                    if($res){
                        $xml="<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎".$userInfo['nickname']."]]></Content>
                            </xml>";
                        echo $xml;
                    }
                }
            }
        }

        var_dump($obj);
        echo "SUCCESS";
   }

   //获取access_token
    public function getAccessToken(){
        // 检测是否有缓存
        $key = 'access_token';
        $token = Redis::get($key);
        if($token){
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            $response = file_get_contents($url);
            $arr = json_decode($response,true);
            Redis::set($key,$arr['access_token']);// 存缓存
            Redis::expire($key,3600);// 缓存存储事件1小时
            $token = $arr['access_token'];
        }
        return $token;
    }

    //获取用户信息
    public function userInfo($openid){
//        $openid="oA-ON5vYTG_YQT-omvb5dgawPFkc";
        $access=$this->getAccessToken();
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access&openid=$openid&lang=zh_CN";
        $count=file_get_contents($url);
        $u=json_decode($count,true);
        return $u;
    }
}
