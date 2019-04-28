<?php

namespace App\Http\Controllers\WeChat;

use App\Model\Goods\GoodsModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

class ImgWenController extends Controller
{
    //商品页
    public function imgwen(){
        $data=GoodsModel::get()->toArray();
        return view("WxPay.goods",["data"=>$data]);
    }

    public function code(){
        $access=getAccessToken();
        $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access";
        $data=[
            "expire_seconds"=>604800,
            "action_name"=>"QR_SCENE",
            "action_info"=>[
                "scene"=>[
                    "scene_id"=>123123
                ]
            ]
        ];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $client=new Client();
        $response=$client->request("POST",$url,[
            'body'=>$data
        ]);
        $res=$response->getBody();
        $dataInfo=json_decode($res,true);
        if($dataInfo['ticket']!=""){
            $ticket=$dataInfo['ticket'];
            $url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
            header("Location: $url");
        }
    }
}
