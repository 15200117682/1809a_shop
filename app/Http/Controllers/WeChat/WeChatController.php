<?php

namespace App\Http\Controllers\WeChat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WeChatController extends Controller
{
    public function getWechat(){
        echo $_GET['echostr'];
    }

    public function WXEvent(){
        $data = file_get_contents("php://input");//通过流的方式接受post数据
        $xml = simplexml_load_string($data);//把xml转化成对象
        $time = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents("logs/wx_event.log",$time,FILE_APPEND);
        echo "SUCCESS";
   }
}
