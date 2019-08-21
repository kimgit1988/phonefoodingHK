<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * Alipay支付控制器
 * @author  kiyang
 */
class Alipay extends Controller
{

    public function PayPost($order)
    {
        $config = config('alipay_web_config');
        $notifyUrl = "http://".$_SERVER['SERVER_NAME'].url('Alipay/PayNotify');
        //引入sdk
        vendor('aop.AopClient');
        vendor('aop.request.AlipayTradeAppPayRequest');
        $aop                     = new \AopClient();
        $aop->gatewayUrl         = "https://openapi.alipay.com/gateway.do";
        $aop->appId              = $config['app_id'];
        $aop->rsaPrivateKey      = $config['RSA_PRIVATE_KEY']; //私钥
        $aop->format             = "json";
        $aop->charset            = "UTF-8";
        $aop->signType           = "RSA2";
        $aop->alipayrsaPublicKey = $config['ALIPAY_RSA_PBULIC_KEY']; //公钥
        $request                 = new \AlipayTradeAppPayRequest();
        $bizcontent = json_encode([
              'body'=>'豐富點智能點餐系统-支付宝支付',
              'subject'=>'豐富點智能點餐系统-支付宝支付',
              'out_trade_no'=> $order['orderSN'],
              'total_amount'=> $order['moneyPaid'],
              'timeout_express'=>'30m',
              'product_code'=>'QUICK_MSECURITY_PAY'
        ]);
        $request->setNotifyUrl($notifyUrl);
        $request->setBizContent($bizcontent);
        $response = $aop->sdkExecute($request);
        if($response){
            return json_encode(['status'=>1,'msg'=>'success','orderid'=>$orderid,'data'=>$response]);
        }else{
            return json_encode(['status'=>0,'msg'=>'false','orderid'=>$orderid]);
        }
    }

    public function PayNotify(){
        $post = input('post.');
        //引入sdk
        vendor('aop.AopClient');
        vendor('aop.request.AlipayTradeAppPayRequest');
        $config = config('alipay_web_config');
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $config['ALIPAY_RSA_PBULIC_KEY'];
        // 验签
        $flag = $aop->rsaCheckV1($post, NULL, "RSA2");
        if($flag){
            // 验签成功修改订单
        }
    }

}