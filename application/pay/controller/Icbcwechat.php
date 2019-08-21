<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * Icbcwechat支付控制器
 * @author  kiyang
 */
class Icbcwechat extends Controller
{
    private $mid = '011901200001001';
    private $formUrl = 'https://mobilepaytest.icbc.com.mo/eftpay_api/EFTWXPayAPI.aspx';
    private $queryUrl = 'https://mobilepaytest.icbc.com.mo/eftpay_api/EFTWXPayQuery.aspx';
    private $signKey = '123654abcdicbc';
    public function PayPost($order)
    {
        date_default_timezone_set('PRC');
        $time = date('YmdHis');
        $notifyUrl = "http://".$_SERVER['SERVER_NAME'].url('pay/Icbcwechat/paynotify');
        $returnUrl = "http://".$_SERVER['SERVER_NAME'].url('wxweb/index/paysuccess',['orderNo'=>$order['orderSN'],'status'=>1,'price'=>$order['moneyPaid']*100]);
        //"http://".$_SERVER['SERVER_NAME'].url('wxweb/index/paysuccess',['orderNo'=>$order['orderSN'],'status'=>1,'price'=>$order['moneyPaid']]);
        // 签名加密用字符串(盐值)
        $signKey = $this->signKey;
        $param = array(
            'mid'=>$this->mid,
            'tid'=>'00000001',
            'trade_type'=>'JSAPI',
            'merchant_order_no' => $order['orderSN'],
            'total_fee' => $order['moneyPaid']*100,
            'price_fee_type'=>'HKD',
            'body'=>'豐富點智能点餐',
            'notify_url'=>$notifyUrl,
            'callback_url'=>$returnUrl,
            'custom_display_code'=>'Y',
        );
        // 生成签名
        // 将数组的ascii码排序
        ksort($param);
        // 设定未加密sign字符串
        $signStr = '';
        // 按规则将键值写入字符串
        foreach ($param as $key => $val) {
            $signStr .= '&'.$key.'='.iconv("UTF-8","gb2312//IGNORE",$val);
        }
        // 在字符串后面拼接盐值
        $signStr = $signStr.$signKey;
        // var_dump($signStr);
        // md5加密获得最终sign
        $sign = md5($signStr);
        // 将sign放入数组
        $param['sign'] = $sign;
        // 按照数组生成form表单
        $form = $this->create_form($param);
        // var_dump($return);
        return $form;
    }

    public function Paynotify(){
        $post = input('param.');
        //确认返回的数据是否完整
        if(!empty($post['eft_notify_result'])&&!empty($post['eft_notify_desc'])&&!empty($post['transaction_id'])&&!empty($post['mid'])&&!empty($post['tid'])&&!empty($post['amount'])&&!empty($post['currency'])&&!empty($post['time_end'])&&!empty($post['sign'])){
            $signKey = $this->signKey;
            $param = array(
                'eft_notify_result' => $post['eft_notify_result'],
                'eft_notify_desc'   => $post['eft_notify_desc'],
                'transaction_id'    => $post['transaction_id'],
                'mid' => $post['mid'],
                'tid' => $post['tid'],
                'amount' => $post['amount'],
                'currency' => $post['currency'],
                'time_end' => $post['time_end'],
            );
            ksort($param);
            $signStr = '';
            // 验签方法
            foreach ($param as $key => $val) {
                $signStr .= '&'.$key.'='.iconv("UTF-8","gb2312//IGNORE",$val);
            }
            // 在字符串后面拼接盐值
            $signStr = $signStr.$signKey;
            // var_dump($signStr);
            // md5加密获得最终sign
            $sign = md5($signStr);
            // 验签通过
            if($sign==$post['sign']){
                // 获取订单信息
                $res = $this->Payquery($param['transaction_id'],2);
                if($res['code']==1){
                    // 修改订单状态为已支付
                    $update['orderStatus'] = 2;
                    $update['payStatus'] = 1;
                    $update['payTime'] = time();
                    $update['methodPayStatus'] = 3;
                    // 保存流水
                    $update['payTransaction'] = $param['transaction_id'];
                    $update = DB::name('WxOrder')->where('orderSN',$res['msg']['merchant_order_no'])->where('isDelete',0)->update($update);
                }else if($res['code']==0){
                    DB::name('WxOrder')->where('orderSN',$res['msg']['merchant_oder_no'])->where('methodPayStatus',1)->where('isDelete',0)->update(['methodPayStatus'=>2]);
                }
            }
        }
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    public function Payquery($data,$type=1){
        if($type==1){
            $param = array(
                'mid'=>$this->mid,
                'merchant_order_no'=>$data['orderSN'],
            );
        }else if ($type==2) {
            $param = array(
                'mid'=>$this->mid,
                'transaction_id'=>$data,
            );
        }
        $signKey = $this->signKey;
        ksort($param);
        $signStr = '';
        // 验签方法
        foreach ($param as $key => $val) {
            $signStr .= '&'.$key.'='.iconv("UTF-8","gb2312//IGNORE",$val);
        }
        // 在字符串后面拼接盐值
        $signStr = $signStr.$signKey;
        // md5加密获得最终sign
        $sign = md5($signStr);
        $queryUrl = $this->queryUrl;
        if($type==1){
            $url = $queryUrl.'?mid='.$param['mid'].'&merchant_order_no='.$param['merchant_order_no'].'&sign='.$sign;
        }else if($type==2){
            $url = $queryUrl.'?mid='.$param['mid'].'&transaction_id='.$param['transaction_id'].'&sign='.$sign;
        }
        $return = $this->get_curl($url);
        $return = json_decode($return,true);
        if($return['trade_state']=='SUCCESS'){
            $returnArray = array(
                'trade_state'=>$return['trade_state'],
                'trade_state_desc'=>$return['trade_state_desc'],
                'trade_type'=>$return['trade_type'],
                'merchant_order_no'=>$return['merchant_order_no'],
                'eft_order_no'=>$return['eft_order_no'],
                'transaction_id'=>$return['transaction_id'],
                'time_end'=>$return['time_end'],
                'price_fee_type'=>$return['price_fee_type'],
                'price_total_fee'=>$return['price_total_fee'],
                'bank_type'=>$return['bank_type'],
                'cash_total_fee'=>$return['cash_total_fee'],
                'cash_fee_type'=>$return['cash_fee_type'],
            ); 
            ksort($returnArray);
            $returnSignStr = '';
            // 验签方法
            foreach ($returnArray as $k => $v) {
                $returnSignStr .= '&'.$k.'='.iconv("UTF-8","gb2312//IGNORE",$v);
            }
            // 在字符串后面拼接盐值
            $returnSignStr = $returnSignStr.$signKey;
            // md5加密获得最终sign
            $returnSign = md5($returnSignStr);
            if($returnSign==$return['sign']){
                if($type==1){
                    $order = DB::name('WxOrder')->where('orderSN',$returnArray['merchant_order_no'])->find();
                    $price = $order['moneyPaid']*100;
                    // 转换类型避免比较报错
                    $price = strval($price);
                    $returnArray['price_total_fee'] = strval($price);
                    // 判断支付金额与订单金额是否相等
                    if($price==$returnArray['price_total_fee']){
                        // 订单已支付执行修改操作
                        if($order['orderStatus']<2){
                            $update['orderStatus'] = 2;
                        }
                        if($order['payStatus']<1){
                            $update['payStatus'] = 1;
                            // 保存流水
                            $update['payTransaction'] = $returnArray['transaction_id'];
                            $update['payTime'] = strtotime($returnArray['time_end']);
                        }
                        $update['methodNotifyStatus'] = 3;
                        $reset = DB::name('WxOrder')->where('orderSN',$returnArray['merchant_order_no'])->where('isDelete',0)->update($update);
                        return ['code'=>1,'msg'=>'订单已支付!'];
                    }else{
                        $update['methodNotifyStatus'] = 2;
                        $reset = DB::name('WxOrder')->where('orderSN',$returnArray['merchant_order_no'])->where('isDelete',0)->update($update);
                        $price = $price/100;
                        $orderAmt = $return['price_total_fee']/100;
                        return ['code'=>0,'msg'=>'訂單與金額不符!訂單金額:'.$price.',查詢金額:'.$orderAmt];
                    }
                }else if ($type==2) {
                    return ['code'=>1,'msg'=>$return];
                }
            }else{
                return ['code'=>0,'msg'=>'驗簽失敗'];
            }
        }else{
            if($type==1){
                $update['methodNotifyStatus'] = 2;
                $reset = DB::name('WxOrder')->where('orderSN',$data['orderSN'])->where('isDelete',0)->update($update);
            }
            return ['code'=>0,'msg'=>$return['trade_state_desc']];
        }
    }

    public function get_curl($url){
        //初始化
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $data;
    }

    public function post_curl($url,$data)
    {
        $ch = curl_init();
        $timeout1 = 60;
        $timeout2 = 30;
        
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        // (PHP 5 >= 5.1.3, PHP 7)
        // curl_setopt_array — 为cURL传输会话批量设置选项
        curl_setopt($ch, CURLOPT_URL, $url);
        // return web page 
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // timeout on response
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout2); 
        // timeout on connect
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // i am sending post data
        curl_setopt($ch, CURLOPT_POST, 1);
        // this are my post vars , .e.g $data = "var1=60&var2=test"; 
        if ($SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查  
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在  
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);            // 指定SSL版本
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
        die;
    }

    public function create_form($data){
        $form = '';
        //1、订单只能使用POST方式提交；使用https协议通讯；
        //2、银行地址：如果是生产则为“https://opay.icbc.com.cn/servlet/ICBCEBusinessServlet”，若为模拟测试环境则为“https://ebankpfovaopay3.dccnet.com.cn/servlet/ICBCEBusinessServlet”
        $form .='<div id="payForm"><FORM name="order" METHOD="GET" ACTION="'.$this->formUrl.'">';
        $form .='<label style="display:none;">mid<INPUT NAME="mid" TYPE="TEXT" value="'.$data['mid'].'"></label>';
        $form .='<label style="display:none;">tid<INPUT NAME="tid" TYPE="TEXT" value="'.$data['tid'].'"></label>';
        $form .='<label style="display:none;">trade_type<INPUT NAME="trade_type" TYPE="TEXT" value="'.$data['trade_type'].'"></label>';
        $form .='<label style="display:none;">merchant_order_no<INPUT NAME="merchant_order_no" TYPE="TEXT" value="'.$data['merchant_order_no'].'"></label>';
        $form .='<label style="display:none;">total_fee<INPUT NAME="total_fee" TYPE="TEXT" value="'.$data['total_fee'].'"></label>';
        $form .='<label style="display:none;">price_fee_type<INPUT NAME="price_fee_type" TYPE="TEXT" value="'.$data['price_fee_type'].'"></label>';
        $form .='<label style="display:none;">body<INPUT NAME="body" TYPE="TEXT" value="'.$data['body'].'"></label>';
        $form .='<label style="display:none;">notify_url<INPUT NAME="notify_url" TYPE="TEXT" value="'.$data['notify_url'].'"></label>';
        $form .='<label style="display:none;">callback_url<INPUT NAME="callback_url" TYPE="TEXT" value="'.$data['callback_url'].'"></label>';
        $form .='<label style="display:none;">custom_display_code<INPUT NAME="custom_display_code" TYPE="TEXT" value="'.$data['custom_display_code'].'"></label>';
        $form .='<label style="display:none;">sign<INPUT NAME="sign" TYPE="TEXT" value="'.$data['sign'].'"></label>';
        $form .='<label style="display:none;"><INPUT TYPE="submit" value="提 交 訂 單" ></label>';
        $form .='</form>';
        $form .='<script language=javascript>setTimeout("document.order.submit()",100);</script></div>';
        return $form;
    }
}