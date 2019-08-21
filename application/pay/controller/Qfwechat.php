<?php

namespace app\pay\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;

/**
 * 钱方支付控制器
 * @author  kiyang
 */
class Qfwechat extends Controller
{
    //测试环境 https://openapi-test.qfpay.com
    //正式环境 https://openapi.qfpay.com
    private $url_prefix = 'https://openapi.qfpay.com';
    private $app_code = '38FE26AB808B4893A90486137DE1D60B';
    private $sign_key = '0CBC71F1B6DC448FB2422C58479AFCEB';
    private $appid = 'wxfe9cbe1ba53f8f87';
    private $appsecret = 'fb36ea12d4354a4f473f6a081b43c778';
    private $mchid = '277286403';

    // 传入配置,失败-重新获取地址,授权类型
    public function get_wx_info($config,$reget,$scope='snsapi_userinfo'){
        //授权类初始化
        $oauth   = new Oauth($config);
        //授权回调地址
        $url     = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //授权
        $info = $oauth->auth($scope,$url);
        $error = $oauth->getError();
        if(!empty($error['errcode'])){
            echo "<script> alert('獲取用戶信息失敗,重新加載!');parent.location.href='".$reget."'; </script>"; die;
            header("Location:".$reget);die;
        }
        if($scope == "snsapi_userinfo"){
            //获取用户信息
            $info = $oauth->userinfo($info['access_token'], $info['openid']);
        }
        return $info;
    }

    public function PayPost($order=array())
    {
        $codeReturn = 'http://'.$_SERVER['HTTP_HOST'].url('pay/qfwechat/payget',['txamt'=>$order['moneyPaid'],'ordersn'=>$order['orderSN']]);

        $codeData = [
            'app_code'=>$this->app_code,
            'redirect_uri'=>$codeReturn,
        ];
        $sign = $this->getSign($codeData);
        $codeReturn = urlencode($codeReturn);
        $codeUrl = $this->url_prefix."/tool/v1/get_weixin_oauth_code?app_code=".$codeData['app_code']."&redirect_uri=$codeReturn&sign=$sign";
        return ['code'=>6,'msg'=>'钱方获取权限','url'=>$codeUrl];
    }

    public function PayGet()
    {
        $post = input('param.');
        if($post){
            $returnUrl = "http://".$_SERVER['SERVER_NAME'].url('wxweb/index/paysuccess',['orderNo'=>$post['ordersn'],'status'=>1,'price'=>$post['txamt']*100]);

            $payUrl = $this->url_prefix.'/trade/v1/payment';
            $openidUrl = $this->url_prefix.'/tool/v1/get_weixin_openid';

            $openidData = [
                'code'=>$post['code'],
            ];
            $openid_res = $this->wcurl($openidUrl,$openidData);
            $openid_res = json_decode($openid_res,true);

            $payData = [
                'txamt'      => $post['txamt']*100,
                'txcurrcd'   => 'HKD',
                'pay_type'   => '800207',
                'out_trade_no' => $post['ordersn'],
                'txdtm'      => date('Y-m-d H:i:s'),
                'sub_openid' => $openid_res['openid'],
                'goods_name' => '豐富點订单',
                //'mchid'      => $this->mchid,
                'return_url' => "http://".$_SERVER['SERVER_NAME'].url('Pay/Qfwechat/PayNotify'),
            ];

            $curl = $this->curlPost($payUrl,$payData);
            $curl = json_decode($curl,true);

            if(!empty($curl['pay_params'])){
                //$curl['txamt'] = date('Y-m-d H:i:s');
                $pay_params = $curl['pay_params'];

                $headerUrl = 'https://o2.qfpay.com/q/direct?mchntnm='.urlencode('豐富點智能点餐').'&txamt='.$payData['txamt'].'&goods_name='.urlencode('豐富點智能點餐订单').'&redirect_url='.urlencode($returnUrl).'&package='.$pay_params['package'].'&timeStamp='.$pay_params['timeStamp'].'&signType='.$pay_params['signType'].'&paySign='.urlencode($pay_params['paySign']).'&appId='.$pay_params['appId'].'&nonceStr='.$pay_params['nonceStr'];
                DB::name('WxOrder')->where('orderSN',$payData['out_trade_no'])->where('methodPayStatus',0)->where('isDelete',0)->update(['methodPayStatus'=>1]);
                return $this->redirect($headerUrl);
            }else{
                return ['code'=>0,'msg'=>'调起支付失败'];
            }
        }
    }

    //处理钱方异步通知
    public function PayNotify(){
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];//这里在php7下不能获取数据，使用 php://input 代替
        if(!$postStr){
            $postStr = file_get_contents("php://input");
        }
        log_output($postStr);
        if(!empty($postStr)){
            $post = json_decode($postStr,true);
            if(!empty($post['notify_type'])&&!empty($post['out_trade_no'])&&!empty($post['pay_type'])&&!empty($post['status'])){
                // 修改提交状态为已返回
                DB::name('WxOrder')->where('orderSN',$post['out_trade_no'])->where('methodPayStatus',1)->where('isDelete',0)->update(['methodPayStatus'=>2]);
                if($post['respcd']=='000000'){
                    // 获取订单信息
                    $order = DB::name('WxOrder')->where('orderSN',$post['out_trade_no'])->find();
                    $price = $order['moneyPaid']*100;
                    // 判断支付金额与订单金额是否相等
                    if($price==$post['txamt']){
                        // 判断支付状态
                        if($post['status']==1){
                            // 修改订单状态为已支付
                            $update['orderStatus'] = 2;
                            $update['payStatus'] = 1;
                            $update['payTime'] = time();
                            $update['methodPayStatus'] = 3;
                            $update['payTransaction'] = $post['syssn'];
                            DB::name('wxOrder')->where('orderSN',$post['out_trade_no'])->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
                            // 返回地址
                            $jumpUrl = "http://".$_SERVER['SERVER_NAME'].url('wxweb/index/orderdetail',['ordersn'=>$post['out_trade_no'],'status'=>$post['status'],'price'=>$post['txamt']]);
                            echo $jumpUrl;
                        }

                    }
                }
            }
        }
    }

    public function getSign($data){
        $fields_string = '';
        ksort($data); //字典排序A-Z升序方式
        foreach($data as $key=>$value) {
            $fields_string .= $key.'='.$value.'&' ;
        }
        $fields_string = substr($fields_string , 0 , strlen($fields_string) - 1); //刪除最後一個 & 符號
        $sign = strtoupper(md5($fields_string . $this->sign_key));

        return $sign;
    }

    public function curlPost($url,$data,$post=true)
    {
        $fields_string = '';
        ksort($data); //字典排序A-Z升序方式
        foreach($data as $key=>$value) {
            $fields_string .= $key.'='.$value.'&' ;
        }
        $fields_string = substr($fields_string , 0 , strlen($fields_string) - 1); //刪除最後一個 & 符號
        $sign = strtoupper(md5($fields_string . $this->sign_key));

        //// 設置Header ////
        $header = array();
        $header[] = 'X-QF-APPCODE: ' . $this->app_code;
        $header[] = 'X-QF-SIGN: ' . $sign;

        $ch = curl_init();
        $timeout1 = 60;
        $timeout2 = 30;
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        // (PHP 5 >= 5.1.3, PHP 7)
        // curl_setopt_array — 为cURL传输会话批量设置选项
        curl_setopt($ch, CURLOPT_URL, $url);
        // return web page

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // timeout on response
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout2);
        // timeout on connect
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // i am sending post data
        if($post){
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        // this are my post vars , .e.g $data = "var1=60&var2=test";
        if ($SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            // curl_setopt($ch, CURLOPT_SSLVERSION, 1);            // 指定SSL版本
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // 执行一个cURL会话
        $contents = curl_exec($ch);
        // 返回最后一次的错误号
        $err_code = curl_errno($ch);
        $error = curl_error($ch);
        // 关闭一个cURL会话
        curl_close($ch);
        if($err_code)
        {
            return  "error:".$err_code.",".$error;
        }
        //转码[转码解释](http://blog.csdn.net/qq_22253823/article/details/53655743)
        $contents = iconv(mb_detect_encoding($contents, array('ASCII','GB2312','GBK','UTF-8')),'UTF-8',$contents);
        return $contents;
    }

    public function wcurl($url,$data=null){
        $fields_string = '';
        ksort($data); //字典排序A-Z升序方式
        foreach($data as $key=>$value) {
            $fields_string .= $key.'='.$value.'&' ;
        }
        $fields_string = substr($fields_string , 0 , strlen($fields_string) - 1); //刪除最後一個 & 符號
        $sign = strtoupper(md5($fields_string . $this->sign_key));

        //// 設置Header ////
        $header = array();
        $header[] = 'X-QF-APPCODE: ' . $this->app_code;
        $header[] = 'X-QF-SIGN: ' . $sign;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        if(!empty($data)){
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;

    }

}