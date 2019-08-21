<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * Icbcmacau支付控制器
 * @author  kiyang
 */
class Icbcmacau extends Controller
{
    private $merCode = '0119EC20010241';
    public function PayPost($order)
    {
        date_default_timezone_set('PRC');
        $time = date('YmdHis');
        $notifyUrl = "http://".$_SERVER['SERVER_NAME'].url('pay/Icbcmacau/paynotify');
        $returnUrl = "http://".$_SERVER['SERVER_NAME'].url('pay/Icbcmacau/payreturn');
        $param = array(
            'interfaceName'=>'ICBC_MYEBANK_B2C',
            'interfaceVersion'=>'3.0.0.0',
            'areaCode'=>'0119',
            'orderid' => $order['orderSN'],
            'amount' => $order['moneyPaid']*100,
            'curType'=>'HKD',
            'merID'=>$this->merCode,
            'tmerID'=>'011924010009',
            'merAcct'=>'0119100500002784344',
            'notifyType'=>'HS',
            'merURL'=>$notifyUrl,
            'orderDate'=>$time,
            'resultType'=>1,
            'ieu'=>'zh-CN',
            'goodsID'=>$order['contactNumber'],
            'goodsName'=>$order['contactName'],
            'goodsNum'=>'1',
            'carriageAmt'=>$order['moneyPaid']*100,
            'carriageCurr'=>'HKD',
            'merHint'=>'',
            'upopType'=>'1',
            'specialListName'=>'',
            'specialList'=>'',
            'remark1'=>'',
            'remark2'=>'',
            'thisUseLastPay'=>'1',
            'thisUserId'=>'A001',
            'thisModifyCardNum'=>'',
            'thisCardNum'=>'',
            'deductCur'=>'',
            'deductAmt'=>'',
        );
        $data = json_encode($param);
        $post = 'parameter='.$data;
        // 线上http://localhost:7777/wangyin/payIcbc.do 本地:http://192.168.20.235:8080/wangyin/payIcbc.do
        // $return = $this->post_curl('http://192.168.20.235:8080/wangyin/payIcbc.do',$post);
        $return = $this->post_curl('http://localhost:7777/wangyin/payIcbc.do',$post);
        $form = $this->create_form($return);
        // 修改提交状态为已提交
        DB::name('WxOrder')->where('orderSN',$order['orderSN'])->where('methodPayStatus',0)->where('isDelete',0)->update(['methodPayStatus'=>1]);
        return $form;
    }

    public function Paynotify(){
        /* // 接受
            $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];//这里在php7下不能获取数据，使用 php://input 代替  
            if(!$postStr){  
                $postStr = file_get_contents("php://input");  
            }
            $return = $this->post_curl('http://notifyurl',$postStr);
        */
        $post = input('param.');
        //确认返回的数据是否完整
        if(!empty($post['interfaceName'])&&!empty($post['interfaceVersion'])&&!empty($post['areaCode'])&&!empty($post['orderid'])&&!empty($post['TranSerialNo'])&&!empty($post['amount'])&&!empty($post['curType'])&&!empty($post['merID'])&&!empty($post['merAcct'])&&!empty($post['resultType'])&&!empty($post['orderDate'])&&!empty($post['notifyDate'])&&!empty($post['tranStat'])&&!empty($post['signMsg'])){
            // 修改提交状态为已返回
            DB::name('WxOrder')->where('orderSN',$post['orderid'])->where('methodPayStatus',1)->where('isDelete',0)->update(['methodPayStatus'=>2]);
            // 将post数据封装给接口验签
            foreach ($post as $k => $v) {
                $postData[$k] = urlencode($v);
            }
            $data = json_encode($postData);
            $send = 'parameter='.$data;
            file_put_contents('./test2.txt', json_encode($postData));
            // 接口问罗杰拿
            $return = $this->post_curl('http://localhost:7777/wangyin/notifySignIcbc.do',$send);
            $return = json_decode($return,true);
            file_put_contents('./test3.txt', json_encode($return));
            // 还不知道返回的验签结果字段后续修改
            if($return['resp_code']=='000000'){
            // file_put_contents('./test4.txt', '验签成功');
                // 获取订单信息
                $order = DB::name('WxOrder')->where('orderSN',$post['orderid'])->find();
                $price = $order['moneyPaid']*100;
                // 判断支付金额与订单金额是否相等
                if($price==$post['amount']){
                    // 判断支付状态
                    if($post['tranStat']==1){
                        // 修改订单状态为已支付
                        $update['orderStatus'] = 2;
                        $update['payStatus'] = 1;
                        $update['payTime'] = time();
                        $update['methodPayStatus'] = 3;
                        $update['payTransaction'] = $post['TranSerialNo'];
                        $order = DB::name('WxOrder')->where('orderSN',$post['orderid'])->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
                        // 返回地址
                        // redirect url
                        $jumpUrl = "http://".$_SERVER['SERVER_NAME'].url('wxweb/index/paysuccess',['orderNo'=>$post['orderid'],'status'=>$post['tranStat'],'price'=>$post['amount']]);
                        echo $jumpUrl;
                        // echo "HTTP/1.1 200 OK\nServer: Apache/1.39\nContent-Length: ".strlen($jumpUrl)."\nContent-type: text/html\n\n$jumpUrl";
                    }
                    
                }
            }
        }
    }

    // 支付查询
    public function PayQuery($order){
        // 获取订单信息
        // $order = DB::name('WxOrder')->where('orderSN',$order['orderSN'])->find();
        $update['methodNotifyStatus'] = 1;
        DB::name('WxOrder')->where('orderSN',$order['orderSN'])->where('isDelete',0)->update($update);
        $post = array(
            'apiName'  => 'ICBC_QueryOderInfo',
            'shopCode' => $this->merCode,
            'orderNo'  => $order['orderSN'],
        );
        // 将post数据封装给接口签名
        foreach ($post as $k => $v) {
            $postData[$k] = urlencode($v);
        }
        $data = json_encode($postData);
        $send = 'parameter='.$data;
        // 接口问罗杰拿
        $curl = $this->post_curl('http://localhost:7777/wangyin/queryIcbc.do',$send);
        $curl = json_decode($curl,true);
        $curl = http_build_query($curl);
        // 不知道传给他的是数组还是json
        $return = $this->post_curl('https://corpebankc3.dccnet.com.cn/servlet/enquiry',$curl);
        $return = json_decode($return,true);
        $data = array();
        if($return['errorCode']=="0"){
            foreach ($return as $k => $v) {
                $postData[$k] = urlencode($v);
            }
            $post = json_encode($postData);
            $send = 'parameter='.$post;
            // file_put_contents('./test2.txt', json_encode($postData));
            // 接口问罗杰拿
            $sign = $this->post_curl('http://localhost:7777/wangyin/querySignIcbc.do',$send);
            $sign = json_decode($sign,true);
            // 判断验签是否成功
            if($sign['resp_code']=='000000'){
                $price = $order['moneyPaid']*100;
                // 转换类型避免比较报错
                $price = strval($price);
                if($return['orderAmt']==$price){
                    if($return['InjunctState']==1||$return['InjunctState']==2){
                        // 订单已支付执行修改操作
                        if($order['orderStatus']<2){
                            $update['orderStatus'] = 2;
                        }
                        if($order['payStatus']<1){
                            $update['payTransaction'] = $return['trxSerialNo'];
                            $update['payStatus'] = 1;
                            $update['payTime'] = strtotime($return['tranDate']);
                        }
                        $update['methodNotifyStatus'] = 3;
                        $reset = DB::name('WxOrder')->where('orderSN',$return['orderNo'])->where('isDelete',0)->update($update);
                        $data['code'] = 1;
                        $data['msg'] = '訂單已支付!';
                    }else{
                        $update['methodNotifyStatus'] = 3;
                        $reset = DB::name('WxOrder')->where('orderSN',$return['orderNo'])->where('isDelete',0)->update($update);
                        $data['code'] = 0;
                        $data['msg'] = '訂單未支付!';
                    }
                }else{
                    $update['methodNotifyStatus'] = 2;
                    $reset = DB::name('WxOrder')->where('orderSN',$return['orderNo'])->where('isDelete',0)->update($update);
                    $data['code'] = 0;
                    $price = $price/100;
                    $orderAmt = $return['orderAmt']/100;
                    $data['msg'] = '訂單與查詢金額不符!訂單金額:'.$price.',查詢金額:'.$orderAmt;
                }
            }else{
                $data['code'] = 0;
                $data['msg'] = '驗簽失敗!';
            }
        }else{
            if(isset($return['errorMsg'])){
                $data['code'] = 0;
                // $data['curl'] = $curl;
                $data['msg'] = $return['errorCode']."|".$return['errorMsg'];
            }else{
                $data['code'] = 0;
                $data['msg'] = $return['errorCode']."|".'通訊失敗請聯繫系統管理員';
            }
        }
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
        $data = json_decode($data,true);
        $form = '';
        //1、订单只能使用POST方式提交；使用https协议通讯；
        //2、银行地址：如果是生产则为“https://opay.icbc.com.cn/servlet/ICBCEBusinessServlet”，若为模拟测试环境则为“https://ebankpfovaopay3.dccnet.com.cn/servlet/ICBCEBusinessServlet”
        $form .='<div id="payForm"><FORM  name="order" METHOD="POST" ACTION="https://ebankpfovaopay3.dccnet.com.cn/servlet/ICBCEBusinessServlet">';
        $form .='<label style="display:none;">interfaceName<INPUT NAME="interfaceName" TYPE="TEXT" value="'.$data['interfaceName'].'"></label>';
        $form .='<label style="display:none;">interfaceVersion<INPUT NAME="interfaceVersion" TYPE="TEXT" value="'.$data['interfaceVersion'].'"></label>';
        $form .='<label style="display:none;">areaCode<INPUT NAME="areaCode" TYPE="TEXT" value="'.$data['areaCode'].'"></label>';
        $form .='<label style="display:none;">orderid<INPUT NAME="orderid" TYPE="TEXT" value="'.$data['orderid'].'"></label>';
        $form .='<label style="display:none;">amount<INPUT NAME="amount" TYPE="TEXT" value="'.$data['amount'].'"></label>';
        $form .='<label style="display:none;">curType<INPUT NAME="curType" TYPE="TEXT" value="'.$data['curType'].'"></label>';
        $form .='<label style="display:none;">merID<INPUT NAME="merID" TYPE="TEXT" value="'.$data['merID'].'"></label>';
        $form .='<label style="display:none;">tmerID<INPUT NAME="tmerID" TYPE="TEXT" value="'.$data['tmerID'].'"></label>';
        $form .='<label style="display:none;">merAcct<INPUT NAME="merAcct" TYPE="TEXT" value="'.$data['merAcct'].'"></label>';
        $form .='<label style="display:none;">notifyType<INPUT NAME="notifyType" TYPE="TEXT" value="'.$data['notifyType'].'"></label>';
        $form .='<label style="display:none;">merURL<INPUT NAME="merURL" TYPE="TEXT" value="'.$data['merURL'].'"></label>';
        $form .='<label style="display:none;">resultType<INPUT NAME="resultType" TYPE="TEXT" value="'.$data['resultType'].'"></label>';
		$form .='<label style="display:none;">ieu<INPUT NAME="ieu" TYPE="TEXT" value="'.$data['ieu'].'"></label>';
		$form .='<label style="display:none;">goodsID<INPUT NAME="goodsID" TYPE="TEXT" value="'.$data['goodsID'].'"></label>';
		$form .='<label style="display:none;">goodsName<INPUT NAME="goodsName" TYPE="TEXT" value="'.$data['goodsName'].'"></label>';
		$form .='<label style="display:none;">goodsNum<INPUT NAME="goodsNum" TYPE="TEXT" value="'.$data['goodsNum'].'"></label>';
		$form .='<label style="display:none;">carriageAmt<INPUT NAME="carriageAmt" TYPE="TEXT" value="'.$data['carriageAmt'].'"></label>';
		$form .='<label style="display:none;">carriageCurr<INPUT NAME="carriageCurr" TYPE="TEXT" value="'.$data['carriageCurr'].'"></label>';
		$form .='<label style="display:none;">merHint<INPUT NAME="merHint" TYPE="TEXT" value="'.$data['merHint'].'"></label>';
        $form .='<label style="display:none;">orderDate<INPUT NAME="orderDate" TYPE="TEXT" value="'.$data['orderDate'].'"></label>';
		$form .='<label style="display:none;">upopType<INPUT NAME="upopType" TYPE="TEXT" value="'.$data['upopType'].'"></label>';
        $form .='<label style="display:none;">merSignMsg<INPUT NAME="merSignMsg" TYPE="TEXT" value="'.$data['merSignMsg'].'"></label>';
        $form .='<label style="display:none;">merCert<INPUT NAME="merCert" TYPE="TEXT" value="'.$data['merCert'].'"></label>';
		$form .='<label style="display:none;">specialListName<INPUT NAME="specialListName" TYPE="TEXT" value="'.$data['specialListName'].'"></label>';
		$form .='<label style="display:none;">specialList<INPUT NAME="specialList" TYPE="TEXT" value="'.$data['specialList'].'"></label>';
		$form .='<label style="display:none;">remark1<INPUT NAME="remark1" TYPE="TEXT" value="'.$data['remark1'].'"></label>';
		$form .='<label style="display:none;">remark2<INPUT NAME="remark2" TYPE="TEXT" value="'.$data['remark2'].'"></label>';
		$form .='<label style="display:none;">thisUseLastPay<INPUT NAME="thisUseLastPay" TYPE="TEXT" value="'.$data['thisUseLastPay'].'"></label>';
		$form .='<label style="display:none;">thisUserId<INPUT NAME="thisUserId" TYPE="TEXT" value="'.$data['thisUserId'].'"></label>';
		$form .='<label style="display:none;">thisModifyCardNum<INPUT NAME="thisModifyCardNum" TYPE="TEXT" value="'.$data['thisModifyCardNum'].'"></label>';
		$form .='<label style="display:none;">thisCardNum<INPUT NAME="thisCardNum" TYPE="TEXT" value="'.$data['thisCardNum'].'"></label>';
		$form .='<label style="display:none;">deductCur<INPUT NAME="deductCur" TYPE="TEXT" value="'.$data['deductCur'].'"></label>';
		$form .='<label style="display:none;">deductAmt<INPUT NAME="deductAmt" TYPE="TEXT" value="'.$data['deductAmt'].'"></label>';
        $form .='<label style="display:none;"><INPUT TYPE="submit" value="提 交 訂 單" ></label>';
        $form .='</form>';
        $form .='<script language=javascript>setTimeout("document.order.submit()",100)</script></div>';
        return $form;
    }
}