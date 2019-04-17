<?php

namespace App\Http\Controllers\WxPay;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Weixin\WXBizDataCryptController;

use Illuminate\Support\Str;

class WxPayController extends Controller
{
    public $url="https://api.mch.weixin.qq.com/pay/unifiedorder";//调用统一下单接口
    public $notify_url='http://1809abc.comcto.com/weixin/pay/notify';     // 支付回调

    public function WxPay(){
        $fee=1;//用户支付的总金额
        $order_id=time().mt_rand(11111,99999);//订单号
        $info=[
            'appid'         =>  env('WEIXIN_APPID_0'),      //微信分配的公众账号ID
            'mch_id'        =>  env('WEIXIN_MCH_ID'),       // 商家id
            'nonce_str'     =>  Str::random(),
            'sign_type'     =>'MD5',
            'body'           =>'测试订单_'.mt_rand(1111,9999).Str::random(6),
            'out_trade_no' =>$order_id,   //本地订单号
            'total_fee'    =>$fee,    //支付金额
            'spbill_create_ip'=> $_SERVER['REMOTE_ADDR'],  //客户端ip
            'notify_url'   =>$this->notify_url,    //通知回调地址
            'trade_type'  =>'NATIVE'
        ];

        $this->values=[];//设置一个空的数组
        $this->values=$info;//把订单数组赋值到这个空数组

        $qw=$this->getSign();// 签名


        $XMLInfo = $this-> ToXml();// 数据转化成XML格式


        $arr = $this-> postXmlCurl($XMLInfo,$this->url);// 请求支付接口


        $data = simplexml_load_string($arr);// XML数据转化成对象

        return view("WxPay.pay",['code_url' => $data->code_url]);// 将 code_url 返回给前端，前端生成 支付二维码

    }

    /**
     * 设计签名
     */
    public function getSign(){

        ksort($this->values);// 一、参数名ASCII码从小到大排序（字典序 A-Z排序）；


        $str = "";
        foreach($this->values as $k => $v){
            if($k != 'sign' && $v != '' && !is_array($v)){
                $str .= $k . "=" . $v . "&";
            }
        }// 二、签名拼接


        $sign = strtoupper(md5($str."key=".env('MCH_KEY')));// 三、MD5加密并全部转化成大写

        $this->values['sign'] = $sign; // 四、追加到$info里边

    }

    /**
     * 数据转化成XML格式
     * @return string
     */
    protected function ToXml()
    {
        if(!is_array($this->values)
            || count($this->values) <= 0)
        {
            die("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";//拼接xml数据
        return $xml;//返回结果
    }
    /**
     * 请求支付接口
     * @param $xml
     * @param $url
     * @param bool $useCert
     * @param int $second
     * @return mixed
     */
    private  function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//		if($useCert == true){
//			//设置证书
//			//使用证书：cert 与 key 分别属于两个.pem文件
//			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
//			curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
//			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
//			curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
//		}
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            die("curl出错，错误码:$error");
        }
    }

}
