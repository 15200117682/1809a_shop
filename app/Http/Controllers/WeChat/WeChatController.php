<?php

namespace App\Http\Controllers\WeChat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Model\User\UserModel;

use GuzzleHttp\Client;


class WeChatController extends Controller
{
    //首次接入微信
    public function getWechat(){
        echo $_GET['echostr'];
    }

    //post接入微
    public function WXEvent(){
        $data = file_get_contents("php://input");//通过流的方式接受post数据
        $time = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";//存入时间
        file_put_contents("logs/wx_event.log",$time,FILE_APPEND);//存到public日志文件
        $obj=simplexml_load_string($data);//将xml数据转换成对象格式的数据
        $ToUserName=$obj->ToUserName;//获取开发者微信号
        $FromUserName=$obj->FromUserName;//获取用户id（openid）
        $CreateTime=$obj->CreateTime;//获取时间
        $MsgType=$obj->MsgType;//获取数据类型
        $Event=$obj->Event;//获取时间类型
        if($MsgType=="event"){//判断数据类型
            if($Event=="subscribe"){//判断事件类型

                $userInfo=$this->userInfo($FromUserName);//获取用户昵称

                $one=UserModel::where(['openid'=>$FromUserName])->first();//查询数据库
                if($one){//判断用户是否是第一次关注
                    $xml="<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎".$userInfo['nickname']."回归]]></Content>
                            </xml>";//设置发送的xml格式
                    echo $xml;//返回结果
                }else{//如果是第一次关注
                    $array=array(
                        "openid"=>$userInfo['openid'],
                        "nickname"=>$userInfo['nickname'],
                        "city"=>$userInfo['city'],
                        "province"=>$userInfo['province'],
                        "country"=>$userInfo['country'],
                        "headimgurl"=>$userInfo['headimgurl'],
                        "subscribe_time"=>$userInfo['subscribe_time'],
                        "sex"=>$userInfo['sex'],
                    );//设置数组形式的数据类型
                    $res=UserModel::insertGetId($array);//存入数据库
                    if($res){//判断是否入库成功
                        $xml="<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎".$userInfo['nickname']."]]></Content>
                            </xml>";//设置xml格式的数据
                        echo $xml;//返回结果
                    }
                }
            }
        }else if($Event == 'text'){
            $xml = "<xml>
                    <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                    <CreateTime>time()</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[已收到]]></Content>
                </xml>";
            echo $xml;
        }

   }

   //获取access_token
    public function getAccessToken(){
        // 检测是否有缓存
        $key = 'access_token';//设置缓存下表
        $token = Redis::get($key);//查看缓存是否存在
        if($token){
            //如果有的话直接返回缓存的access_token值
        }else{
            //没有调用接口获取access_token
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');//调接口
            $response = file_get_contents($url);//流接受token数据
            $arr = json_decode($response,true);//转换为数组类型数据
            Redis::set($key,$arr['access_token']);// 存缓存
            Redis::expire($key,3600);// 缓存存储事件1小时
            $token = $arr['access_token'];
        }
        return $token;
    }


    //获取用户信息
    public function userInfo($openid){
//        $openid="oA-ON5vYTG_YQT-omvb5dgawPFkc";
        $access=$this->getAccessToken();//获取access_token
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access&openid=$openid&lang=zh_CN";//调用接口
        $count=file_get_contents($url);//流接受数据
        $u=json_decode($count,true);//转换数据为数组类型
        return $u;//返回数据
    }

    public function customize(){
        $access=$this->getAccessToken();//获取access_token
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access";//调用接口
        $data = [
        'button'=> [
            [
                'type'=>'click',
                'name'=>'缘分',
                'key'=>'name_yuan'
            ],
            [
                'name'=>'菜单',
                'sub_button'=>[
                    [
                        'type'=>'click',
                        'name'=>'最差缘分',
                        'key'=>'view_yuan'
                    ],
                    [
                        'type'=>'view',
                        'name'=>'最美相遇',
                        'url'=>'http://www.soso.com/'
                    ]
                ]
            ]
            ]
        ];//设置自定义菜单参数
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $Clinet=new Client();
        $response=$Clinet->request("POST",$url,[
                'body'=>$data
            ]);
        $res=$response->getBody();
        echo $res;
        //$json = $this->curlRequest($url,$data);//调用第三方post请求后生成自定义菜单
        //echo $json;//返回结果
    }


    //发送post请求，创建菜单
    function curlRequest($url,$data = ''){
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
