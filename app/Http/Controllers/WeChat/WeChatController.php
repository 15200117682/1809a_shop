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
                $userInfo=$this->userInfo($FromUserName);//获取用户昵称

                $one=UserModel::where(['openid'=>$FromUserName])->first();//查询数据库
                if($one){
                    $xml="<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎".$userInfo['nickname']."回归]]></Content>
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

    public function customize(){

        $access=$this->getAccessToken();
        $url=" https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access";
        $data = '{
            "button":[
     {
         "type":"click",
          "name":"今日歌曲",
          "key":"V1001_TODAY_MUSIC"
      },
      {
          "name":"菜单",
           "sub_button":[
           {
               "type":"view",
               "name":"搜索",
               "url":"http://www.soso.com/"
            },
            {
                "type":"click",
               "name":"赞一下我们",
               "key":"V1001_GOOD"
            }]
       }]
 }';

//        $data = json_decode($data,true);
        $json = $this->curlRequest($url,$data);
        echo $json;
    }


    public function getPost($url,$vars){

        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 5.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1';

        $postfields = '';
        foreach ($vars as $key => $value){
            $postfields .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $postfields;

        //解决方案一 禁用证书验证
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;

        curl_setopt_array($ch, $params); //传入curl参数
        return  curl_exec($ch); //执行
    }

    public function curlRequest($url,$data = ''){
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_TIMEOUT] = 30; //超时时间
        if(!empty($data)){
            $params[CURLOPT_POST] = true;
            $params[CURLOPT_POSTFIELDS] = $data;
        }
        $params[CURLOPT_SSL_VERIFYPEER] = false;//请求https时设置,还有其他解决方案
        $params[CURLOPT_SSL_VERIFYHOST] = false;//请求https时,其他方案查看其他博文
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }
}
