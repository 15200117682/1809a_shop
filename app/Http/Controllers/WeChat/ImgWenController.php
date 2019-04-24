<?php

namespace App\Http\Controllers\WeChat;

use App\Model\Goods\GoodsModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ImgWenController extends Controller
{
    //商品页
    public function imgwen(){
        $data=GoodsModel::get()->toArray();
        return view("WxPay.goods",["data"=>$data]);
    }
}
