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

    //带参数的二维码生成
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

    //商品详情加二维码
    public function goodsDetail($id){
        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].'/goods/detail/$id';
        $data=GoodsModel::where(['goods_id'=>$id])->where(['is_up'=>1])->first();
        if($data==''){
            return "查无商品";
        }
        $info=[
            "url"=>$url,
            "data"=>$data
        ];
        return view("goods.detail",$info);
    }
}
