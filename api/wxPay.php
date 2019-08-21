<?php  

require_once('WxpayAPI/lib/WxPay.Api.php');  
  
class WXPay  {  
    function index($Body,$Out_trade_no,$Total_fee,$Notify_url,$Openid){ 
        //         初始化值对象  
        $input = new WxPayUnifiedOrder();
        $input->SetBody($Body); // 商家名称
        $input->SetOut_trade_no($Out_trade_no);//订单号
        $input->SetTotal_fee($Total_fee);//金额 单位分
        $input->SetNotify_url($Notify_url);//后台处理结果api
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($Openid);//Openid
        //$input->SetOpenid($this->getSession()->openid);  
        //         向微信统一下单，并返回order，它是一个array数组  
        $order = WxPayApi::unifiedOrder($input);  
        //         json化返回给小程序端  
        header("Content-Type: application/json");
				
        return $this->getJsApiParameters($order);  
    }  
  
    private function getJsApiParameters($UnifiedOrderResult)  
    {    //判断是否统一下单返回了prepay_id  
        if(!array_key_exists("appid", $UnifiedOrderResult)  
            || !array_key_exists("prepay_id", $UnifiedOrderResult)  
            || $UnifiedOrderResult['prepay_id'] == "")  
        {  
            throw new WxPayException("参数错误");  
        }  
        $jsapi = new WxPayJsApiPay();  
        $jsapi->SetAppid($UnifiedOrderResult["appid"]);  
        $timeStamp = time();  
        $jsapi->SetTimeStamp("$timeStamp");  
        $jsapi->SetNonceStr(WxPayApi::getNonceStr());  
        $jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);  
        $jsapi->SetSignType("MD5");  
        $jsapi->SetPaySign($jsapi->MakeSign());  
        $parameters = json_encode($jsapi->GetValues());  
        return $parameters;  
    }  
//这里是服务器端获取openid的函数  
//    private function getSession() {  
//        $code = $this->input->post('code');  
//        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.WxPayConfig::APPID.'&secret='.WxPayConfig::APPSECRET.'&js_code='.$code.'&grant_type=authorization_code';  
//        $response = json_decode(file_get_contents($url));  
//        return $response;  
//    }
 /* 
		$input->SetBody("优易购"); // 商家名称
        $input->SetOut_trade_no(time().'');//订单号
        $input->SetTotal_fee("1");//金额 单位分
        $input->SetNotify_url("https://...com/notify.php");//后台处理结果api
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid('oYlMR0Sxd9IEc9HSLV58N3VK2U8I');//Openid
*/
}  
 