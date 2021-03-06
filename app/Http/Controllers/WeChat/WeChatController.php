<?php

namespace App\Http\Controllers\WeChat;

use App\Model\Goods\GoodsModel;
use App\Model\TmpUsersModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Model\User\UserModel;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

use App\Model\User\MaterialModel;
use Monolog\Handler\SyslogUdp\UdpSocket;


class WeChatController extends Controller
{
    //首次接入微信
    public function getWechat(){
        echo $_GET['echostr'];//首次接入返回信息
    }

    //post接入微xin
    public function WXEvent()
    {
        $data = file_get_contents("php://input");//通过流的方式接受post数据
        $time = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";//存入时间
        file_put_contents("logs/wx_event.log", $time, FILE_APPEND);//存到public日志文件
        $obj = simplexml_load_string($data);//将xml数据转换成对象格式的数据
        $ToUserName = $obj->ToUserName;//获取开发者微信号
        $FromUserName = $obj->FromUserName;//获取用户id（openid）
        $CreateTime = $obj->CreateTime;//获取时间
        $MsgType = $obj->MsgType;//获取数据类型
        $Event = $obj->Event;//获取时间类型
        if ($MsgType == "event") {//判断数据类型
            if ($Event == "subscribe") {//判断事件类型

                $userInfo = $this->userInfo($FromUserName);//获取用户昵称

                $one = UserModel::where(['openid' => $FromUserName])->first();//查询数据库
                if ($one) {//判断用户是否是第一次关注
                    $arr=[
                        "status"=>1
                    ];//修改数据
                    $res=UserModel::where(['openid' => $FromUserName])->update($arr);//执行sql
                    if($res){
                        $xml = "<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎" . $userInfo['nickname'] . "回归]]></Content>
                            </xml>";//设置发送的xml格式
                        echo $xml;//返回结果
                    }
                } else {//如果是第一次关注
                    $array = array(
                        "openid" => $userInfo['openid'],//用户id
                        "nickname" => $userInfo['nickname'],//用户名称
                        "city" => $userInfo['city'],//用户所在城市
                        "province" => $userInfo['province'],//用户所在区
                        "country" => $userInfo['country'],//用户所在国家
                        "headimgurl" => $userInfo['headimgurl'],//用户头像
                        "subscribe_time" => $userInfo['subscribe_time'],//用户时间
                        "sex" => $userInfo['sex'],//用户性别
                        "status"=>1
                    );//设置数组形式的数据类型
                    $res = UserModel::insertGetId($array);//存入数据库
                    if ($res) {//判断是否入库成功
                        $xml = "<xml>
                              <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                              <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[你好,欢迎" . $userInfo['nickname'] . "]]></Content>
                            </xml>";//设置xml格式的数据
                        echo $xml;//返回结果
                    }
                }
            }else if($Event=="unsubscribe"){//用户取消关注删除库
                $userInfo = $this->userInfo($FromUserName);//获取用户昵称
                $arr=[
                    "status"=>0
                ];//修改数据
                UserModel::where(['openid' => $FromUserName])->update($arr);//执行sql
                echo "SUCCESS";
            }else if($Event=="SCAN"){//扫描带参数的二维码

                $userInfo = $this->userInfo($FromUserName);//获取用户昵称

                $array = array(
                    "openid" => $userInfo['openid'],//用户id
                    "nickname" => $userInfo['nickname'],//用户名称
                    "city" => $userInfo['city'],//用户所在城市
                    "province" => $userInfo['province'],//用户所在区
                    "country" => $userInfo['country'],//用户所在国家
                    "headimgurl" => $userInfo['headimgurl'],//用户头像
                    "subscribe_time" => $userInfo['subscribe_time'],//用户时间
                    "sex" => $userInfo['sex'],//用户性别
                    "status"=>1
                );//设置数组形式的数据类型
                $res=TmpUsersModel::insertGetId($array);
                if($res){
                    $title = "宝贝儿，欢迎啊";//图文主题
                    $titles="宝儿，你知道吗？臣妾一直在等你啊！！！";//图文详细描述
                    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
                    $picurl = "$url/image/商品.jpg";//图文图片
                    $str = "<xml>
                          <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                          <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                          <CreateTime>$CreateTime</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA[$title]]></Title>
                              <Description><![CDATA[$titles]]></Description>
                              <PicUrl><![CDATA[$picurl]]></PicUrl>
                              <Url><![CDATA[$url]]></Url>
                            </item>
                          </Articles>
                        </xml>";
                    echo $str;
                }


            }
        } else if ($MsgType == 'text') {//用户回复文字消息
            $Content = $obj->Content;//获取文字内容

                if(strpos($Content,"+天气")){//回复天气
                    $city=mb_substr($Content,0,2);//截取城市名称
                    $url="https://free-api.heweather.net/s6/weather/now?key=HE1904161039381186&location=$city";//调接口
                    $json=file_get_contents($url);//获取数据
                    $arr=json_decode($json,true);//变化数组形式数据
                    $status=$arr['HeWeather6'][0]['status'];//判断城市是否正确的条件
                    if($status=="ok"){//城市正确返回天气情况
                        $fl = $arr['HeWeather6'][0]['now']['fl'];//温度
                        $admin_area = $arr['HeWeather6'][0]['basic']['admin_area'];//城市
                        $wind_dir = $arr['HeWeather6'][0]['now']['wind_dir'];//风力
                        $cond_txt = $arr['HeWeather6'][0]['now']['cond_txt'];//天气情况
                        $str = "温度：".$fl."\n"."风力：".$wind_dir."\n"."所在城市:".$admin_area."\n"."天气实时情况:".$cond_txt;
                        $xml = "<xml>
                    <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                    <CreateTime>time()</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[$str]]></Content>
                </xml>";//返回xml格式数据
                        echo $xml;//回复给用户
                    }else{//城市错误提示
                        $xml = "<xml>
                    <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                    <CreateTime>time()</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[原谅不能为小主找到城市]]></Content>
                </xml>";//返回xml格式数据
                        echo $xml;//回复给用户
                    }


                }else if($Content=="最新商品"){
                    $current_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].'/wechat/imgwen';
                    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
                    $img="$url/image/商品.jpg";
                      $xml='
                       <xml>
                      <ToUserName><![CDATA['.$FromUserName.']]></ToUserName>
                      <FromUserName><![CDATA['.$ToUserName.']]></FromUserName>
                      <CreateTime>'.time().'</CreateTime>
                      <MsgType><![CDATA[news]]></MsgType>
                      <ArticleCount>1</ArticleCount>
                      <Articles>
                        <item>
                          <Title><![CDATA[商品中心]]></Title>
                          <Description><![CDATA[这里有你需要的最全的新品，最实惠的价格，让您的购物更舒心]]></Description>
                          <PicUrl><![CDATA['.$img.']]></PicUrl>
                          <Url><![CDATA['.$current_url.']]></Url>
                        </item>
                      </Articles>
                    </xml>';
                      echo $xml;
                }else {//消息入库
                    $arr = [
                        "type" => $Content,//用户发送的消息内容
                        "FromUserName" => $FromUserName,//用户的id
                        "time" => time()//入库的时间
                    ];//存成数组格式，等待入库
                    $res = MaterialModel::insert($arr);//存入数据库
                    if ($res) {//成功返回给用户结果
                        $xml = "<xml>
                    <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                    <CreateTime>time()</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[已收到]]></Content>
                </xml>";//返回xml格式数据
                        echo $xml;//回复给用户
                    }
                }

        } else if ($MsgType == "image") {//图片存项目，存库
            $media_id = $obj->MediaId;//获取图片传输的间名意
            $access = $this->getAccessToken();//获取access_token
            $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=$access&media_id=$media_id";//接口

            $client = new Client();//实例化Guzzle
            $response = $client->get($url);//调用方法
            $headers = $response->getHeaders();//获取响应头
            $file_info = $headers['Content-disposition'][0];//获取图片名
            $file_name = rtrim(substr($file_info, -20), '"');//取文件名后20位
            $img_name = 'weixin/img/' . substr(md5(time() . mt_rand()), 10, 8) . '_' . $file_name;//最后的文件名;
            $res = Storage::put($img_name, $response->getBody());//使用Storage把图片存入laravel框架中
            if ($res) {
                $arr = [
                    "type" => "storage/app/" . $file_name,//图片的路径
                    "FromUserName" => $FromUserName,//用户的id
                    "time" => time()//添加的时间
                ];
                $res = MaterialModel::insert($arr);//存入数据库
                if ($res) {
                    $xml = "<xml>
                    <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                    <CreateTime>time()</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[图片很完美]]></Content>
                </xml>";//返回xml格式数据
                    echo $xml;//回复给用户
                }
            }
        } else if ($MsgType == "voice") {
            $media_id = $obj->MediaId;//语音的间名意
            $access = $this->getAccessToken();//获取access_token
            $url = "https://api.weixin.qq.com/cgi-bin/media/get/jssdk?access_token=$access&media_id=$media_id";//调接口
            $mp3 = file_get_contents($url);//存入
            $file_name = time() . mt_rand(11111, 99999) . ".amr";//语音文件名
            $res = file_put_contents('weixin/voice/' . $file_name, $mp3);//存入框架
            if ($res) {
                $arr = [
                    "type" => "public/weixin/voice" . $file_name,//语音文件路径
                    "FromUserName" => $FromUserName,//用户的id
                    "time" => time()//添加的时间
                ];
                $res = MaterialModel::insert($arr);//存入数据库
                if ($res) {
                    $xml = "<xml>
                    <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                    <CreateTime>time()</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[您的声音真好听]]></Content>
                </xml>";//返回xml格式数据
                    echo $xml;//回复给用户
                }
            }
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

    //自定义菜单
    public function customize(){
        $access=$this->getAccessToken();//获取access_token
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access";//调用接口
        $url2 = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].'/wechat/code';
        $data = [
        'button'=> [
                [
                    "type"=>"click",
                    "name"=>"最差缘分",
                    "key"=>"V1001_TODAY_MUSIC"
                ],
                [
                    'type'=>'view',
                    'name'=>'最新福利',
                    'url'=>"$url2"
                ],

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

    //群发消息
    public function sendMsg($openid_arr,$content){
        $access=$this->getAccessToken();//获取access_token
        $msg=[
            "touser"=>$openid_arr,
            "msgtype"=>"text",
            "text"=>[
                "content"=>$content
            ]
        ];//群发的内容
        $data=json_encode($msg,JSON_UNESCAPED_UNICODE);//转换json格式

        $url="https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=$access";//调接口
        $client=new Client();
        $response=$client->request("POST",$url,[
            'body'=>$data
        ]);
        $res=$response->getBody();
        return $res;
    }

    public function send(){
        $data=UserModel::where(['status'=>1])->get()->toArray();
        $openid=array_column($data,'openid');

        $content="全场的朋友，尖叫声儿";
        $res=$this->sendMsg($openid,$content);
        echo $res;
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

    //微信授权
    public function code(){
        $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.env("WX_APPID").'&redirect_uri=urlEncode&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
        var_dump($url);
    }
}
