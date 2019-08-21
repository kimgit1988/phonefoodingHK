<?php
 
namespace app\printer\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * Gainscha打印机控制器
 * @author  kiyang
 */
class Gainscha extends Controller
{
    // 主订单打印方法
    public function PostOrder($order,$foods,$nick)
    {
        $text = $this->addOrderText($order,$foods,$nick);
        $info = array(
            'orderSN'=>$order['orderSN'],
            'deviceNumber'=>$order['deviceNumber'],
            'shopNumber'=>$order['shopNumber'],
            'apiKey'=>$order['apiKey'],
        );
        $res = $this->postPrint($text,$info);
        if($res){
            $return = ['code'=>1,'msg'=>'打印成功'];
        }else{
            $return = ['code'=>0,'msg'=>$res['msg']];
        }
        return $return;
    }

    // 菜品打印方法
    public function PostFood($order,$food,$nick,$number='0'){
        $text = $this->addFoodText($food,$nick,$order);
        $info = array(
            'orderSN'=>$food['orderSN'].'-'.$number,
            'deviceNumber'=>$food['deviceNumber'],
            'shopNumber'=>$food['shopNumber'],
            'apiKey'=>$food['apiKey'],
        );
        $res = $this->postPrint($text,$info);
        if($res){
            $return = ['code'=>1,'msg'=>'打印成功'];
        }else{
            $return = ['code'=>0,'msg'=>$res['msg']];
        }
        return $return;
    }

    // 主订单重新打印方法
    public function AgainOrder($order,$foods,$nick)
    {
        $text = $this->addOrderText($order,$foods,$nick,1);
        $info = array(
            'orderSN'=>$order['orderSN'],
            'deviceNumber'=>$order['deviceNumber'],
            'shopNumber'=>$order['shopNumber'],
            'apiKey'=>$order['apiKey'],
        );
        $res = $this->postPrint($text,$info,1);
        if(isset($res)&&$res['code']==1){
            $return = ['code'=>1,'msg'=>'打印成功'];
        }else{
            $return = ['code'=>0,'msg'=>$res['msg']];
        }
        return $return;
    }

    // 菜品重新打印方法
    public function AgainFood($order,$food,$nick,$number='0'){
        $text = $this->addFoodText($food,$nick,$order,1);
        //同时打印的关联打印机
        if($food['reprinterId']>0) {
            $reprint = DB::name('printer')
                         ->alias('p')
                         ->join('mos_printer_brand b', 'p.brandId = b.id', 'left')
                         ->field('p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                         ->where('p.contactNumber', $food['contactNumber'])
                         ->where('p.id', $food['reprinterId'])
                         ->where('p.isDelete', 0)
                         ->find();
            $reinfo = array(
                'orderSN'=>$food['orderSN'].'-'.$number.$food['reprinterId'],
                'deviceNumber'=>$reprint['deviceNumber'],
                'shopNumber'=>$reprint['shopNumber'],
                'apiKey'=>$reprint['apiKey'],
            );
            $res = $this->postPrint($text,$reinfo,1);
        }
        $info = array(
            'orderSN'=>$food['orderSN'].'-'.$number,
            'deviceNumber'=>$food['deviceNumber'],
            'shopNumber'=>$food['shopNumber'],
            'apiKey'=>$food['apiKey'],
        );
        $res = $this->postPrint($text,$info,1);
        if(isset($res)&&$res['code']==1){
            $return = ['code'=>1,'msg'=>'打印成功'];
        }else{
            $return = ['code'=>0,'msg'=>$res['msg']];
        }
        return $return;
    }

    // 打印商家统计数据中的营业结算单
    public function PostPrintData($data)
    {
        $text = $this->addDataText($data);
        $info = array(
            'orderSN'=>time(),
            'deviceNumber'=>$data['contact']['deviceNumber'],
            'shopNumber'=>$data['contact']['shopNumber'],
            'apiKey'=>$data['contact']['apiKey'],
        );
        $res = $this->postPrint($text,$info,0,2,1);
        //$res = ['code'=>1,'msg'=>'打印成功'];
        if(isset($res)&&$res['code']==1){
            $return = ['code'=>1,'msg'=>'打印成功'];
        }else{
            $return = ['code'=>0,'msg'=>$res['msg']];
        }
        return $return;
    }

    public function addOrderText($order,$foods,$nick,$again=0){
        $contact_content = '1B40';
        $contact_content .= '0A1B45001B61011D2111';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$order['contactName']));
        $contact_content .= '0A1B61001D21000A';
        //if($again==1){
        //    $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'重打订单'));
        //    $contact_content .= '0A';
        //}
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'訂單號：'.$order['orderSN']));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'会员名：'.$order['userNick']));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'餐枱號：'.$order['contactMemberName']));
        $contact_content .= '0A';
        //$contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'點餐號：'.$order['orderAssignedNumber']));
        //$contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'時  間：'.date('Y-m-d H:i:s',$order['createTime'])));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'菜品   單價   數量   小計'));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        foreach($foods as $key => $val){
            $contact_content .= '0A';
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['goodsName']));
            $contact_content .= '0A';
            $totle = $val['goodsPrice']*$val['num'];
            $length_price = strlen($val['goodsPrice']);
            $length_number = strlen($val['num']);
            $length_totle = strlen($totle);
            for ($i=$length_price; $i < 11 ; $i++) { 
                $val['goodsPrice'] = ' '.$val['goodsPrice'];
            }
            for ($i=$length_number; $i < 5 ; $i++) { 
                $val['num'] = $val['num'].' ';
            }
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['goodsPrice'].'    '));
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['num']));
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$totle));
        }
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '0A';
        if($order['tea_fees']>0){
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'茶位費：HKD '.$order['tea_fees']));
            $contact_content .= '0A';
        }
        if($order['service_fees']>0){
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'服務費：HKD '.$order['service_fees']));
            $contact_content .= '0A';
        }
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'合計金額：HKD '.$order['goodsAmount']));
        $contact_content .= '0A';
        $discount = $order['moneyPaid']-$order['moneyPaid'];
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'優惠金額：HKD '.$discount));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'實收金額：HKD '.$order['moneyPaid']));
        $contact_content .= '0A';
        //二维码四个十六进制数字：1 容错等级，2 尺寸大小，3 位数和二维码内容 4 作用打印QRCode 条码
        $contact_content .= '0A1B45001B61011D2111';
        $contact_content .= '1D286B0300314531'.'1D286B0300314308'.'1D286B1500315030'.bin2hex($order['orderSN']).'1D286B0300315130';
        $contact_content .= '0A1B61001D21000A';
        $contact_content .= '0A1B61020A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",date('Y-m-d H:i:s').'/'.$order['contactNumber'].'/'.$nick));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'豐富點智能點餐'));
        $contact_content .= '0A1D564200';
        return $contact_content;
    }

    public function addFoodText($val,$nick,$order,$again=0){
        $food_content = '1B400A1B45001B61011D2111';
        if($again==1){
            $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'重打訂單'));
            $food_content .= '0A';
        }
        $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'餐枱號：'.$order['contactMemberName']));
        $food_content .= '0A1B61001D2100';
        $food_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $specArr = array();
        preg_match_all('/\[.*?\]/i', $val['goodsName'],$specArr);
        if(empty($specArr[0])){
            // 无规格
            $food_content .= '0A1D2122';
            $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['goodsName'].'   '));
            $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'X'.$val['num']));
        }else{
            $spec = '';
            $name = $val['goodsName'];
            foreach ($specArr[0] as $k => $v) {
                $name = str_replace($v,"",$name);
                $spec .= $v;
            }
            $food_content .= '0A1D2122';
            $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$name.'   '));
            $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'X'.$val['num']));
            $food_content .= '0A1D2111';
            $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$spec));
        }
        
        $food_content .= '0A1D2100';
        $food_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $food_content .= '0A1B61020A';
        $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",date('Y-m-d H:i:s').'/'.$order['contactNumber'].'/'.$nick));
        $food_content .= '0A';
        $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'豐富點智能點餐'));
        $food_content .= '0A1D564200';
        return $food_content;
    }

    //打印商家统计数据中的营业结算单
    public function addDataText($data){
        $contact_content = '<gpWord Align=1 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>'.$data['contact']['name'].'營業結算單</gpWord>';
        $contact_content .= '<gpBr/>';
        $contact_content .= '結算日期：'.date('Y-m-d',$data['datetime']['startDate']).' - '.date('Y-m-d',$data['datetime']['endDate']);
        $contact_content .= '<gpBr/>';
        $contact_content .= '打印日期：'.date('Y-m-d H:i:s',time());
        $contact_content .= '<gpBr/>';
        $contact_content .= '------------------------------------------------';
        $contact_content .= '<gpBr/>';
        $contact_content .= '<gpWord Align=0 Bold=1 Wsize=0 Hsize=0 Reverse=0 Underline=0>營業額詳情</gpWord>';
        $contact_content .= '<gpTR3 Type=1><td>總營業金額:</td><td> </td><td>'.$data['allData']['order_amount'].'</td></gpTR3>';
        $contact_content .= '<gpTR3 Type=1><td>堂食營業金額:</td><td> </td><td>'.$data['groupData'][1]['order_amount'].'</td></gpTR3>';
        $contact_content .= '<gpTR3 Type=1><td>外帶營業金額:</td><td> </td><td>'.$data['groupData'][2]['order_amount'].'</td></gpTR3>';
        $contact_content .= '------------------------------------------------';
        $contact_content .= '<gpBr/>';
        $contact_content .= '<gpWord Align=0 Bold=1 Wsize=0 Hsize=0 Reverse=0 Underline=0>收銀詳情</gpWord>';
        foreach($data['orderdata'] as $order)
        {
            $contact_content .= '<gpTR3 Type=1><td>'.$order['name'].'</td><td>'.$order['order_count'].'</td><td>'.$order['order_amount'].'</td></gpTR3>';
        }
        $contact_content .= '------------------------------------------------';
        $contact_content .= '<gpBr/>';
        $contact_content .= '<gpWord Align=0 Bold=1 Wsize=0 Hsize=0 Reverse=0 Underline=0>消費詳情</gpWord>';
        $contact_content .= '<gpTR3 Type=1><td>堂食總金額:</td><td> </td><td> '.$data['groupData'][1]['order_amount'].'</td></gpTR3>';
        $customer_price = $data['groupData'][1]['order_count']==0?'0.00':number_format($data['groupData'][1]['order_amount']/$data['groupData'][1]['order_count'],2);
        $contact_content .= '<gpTR3 Type=1><td>堂食客單價:</td><td> </td><td> '.$customer_price.'</td></gpTR3>';
        $contact_content .= '------------------------------------------------';
        $contact_content .= '<gpBr/>';
        $contact_content .= '<gpWord Align=0 Bold=1 Wsize=0 Hsize=0 Reverse=0 Underline=0>訂單詳情</gpWord>';
        $contact_content .= '<gpTR3 Type=1><td>堂食總訂單:</td><td> </td><td>'.$data['groupData'][1]['order_count'].'</td></gpTR3>';
        $contact_content .= '<gpTR3 Type=1><td>已支付訂單:</td><td> </td><td>'.$data['allData']['order_count'].'</td></gpTR3>';
        $contact_content .= '<gpTR3 Type=1><td>未支付訂單:</td><td> </td><td>'.$data['nopayData']['order_count'].'</td></gpTR3>';
        $contact_content .= '<gpTR3 Type=1><td>現金結賬金額:</td><td> </td><td>'.$data['moneyData']['order_amount'].'</td></gpTR3>';
        $contact_content .= '<gpTR3 Type=1><td>线上结账金额:</td><td> </td><td>'.$data['onlineData']['order_amount'].'</td></gpTR3>';
        $contact_content .= '------------------------------------------------';
        $contact_content .= '<gpBr/>';
        $contact_content .= '<gpWord Align=0 Bold=1 Wsize=0 Hsize=0 Reverse=0 Underline=0>營業賬項</gpWord>';
        foreach($data['fooddata'] as $food)
        {
            $contact_content .= '<gpTR3 Type=1><td>'.$food['categoryName'].'</td><td> '.$food['food_count'].'</td><td>'.$food['food_amount'].'</td></gpTR3>';
        }
        $contact_content .= '<gpCut/>';
        return $contact_content;
    }

    //毫秒时间戳
    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    public function postPrint($text,$info,$reprint=0,$mode=3,$charset=4){
        $reqTime = $this->getMillisecond();
        $errorCode = [
            '-1'=>'IP 地址不允许',
            '-2'=>'关键参数为空或请求方式不对',
            '-3'=>'客户编码不对',
            '-4'=>'安全校验码不正确',
            '-5'=>'请求时间失效',
            '-6'=>'订单内容格式不对',
            '-7'=>'消息号（msgNo）重复',
            '-8'=>'消息模式不对',
            '-9'=>'服务器错误 ',
            '-10'=>'服务器内部错误',
            '-111'=>'云打印机不属于该账号',
        ];
        //api密钥
        $apiKey = $info['apiKey'];
        //商户编码
        $memberCode = $info['shopNumber'];
        //设备编码
        $deviceNo = $info['deviceNumber'];
        //订单编号
        $msgNo = $info['orderSN'];
        $securityCode = md5($memberCode.$deviceNo.$msgNo.$reqTime.$apiKey);
        $url = 'http://printerapi.mod-softs.com:7777/SmarnetWebAPI/apisc/sendMsg';
        $content['charset'] = $charset;
        $content['reqTime'] = $reqTime;
        $content['memberCode'] = $memberCode;
        $content['deviceNo'] = $deviceNo;
        $content['securityCode'] = $securityCode;
        $content['msgDetail'] = $text;
        $content['msgNo'] = $msgNo;
        $content['mode'] = $mode;
        $content['reprint'] = $reprint;
        $res = $this->curl($url, $content);
        $res = json_decode($res,true);
        if($res['code']==0&&$res['msg']=='正常'){
            $return = ['code'=>1,'msg'=>'打印成功'];
        }else{
            //if(!empty($errorCode[$res['code']])){
            //    $return = ['code'=>0,'msg'=>$errorCode[$res['code']]];
            //}else{
            //    $return = ['code'=>0,'msg'=>'未知错误'];
            //}
            $return = ['code'=>0,'msg'=>$res['msg']];
        }
        return $return;
    }

    public function addPrinter($info){
        $reqTime = $this->getMillisecond();
        //api密钥
        $apiKey = $info['apiKey'];
        //商户编码
        $memberCode = $info['shopNumber'];
        //设备编码
        $deviceNo = $info['deviceNumber'];
        // 设备名称
        $devName = $info['contactNumber'].'-'.$info['deviceNick'];
        $securityCode = md5($memberCode.$reqTime.$apiKey.$deviceNo);
        $url = 'http://printerapi.mod-softs.com:7777/SmarnetWebAPI/apisc/adddev';
        $content['reqTime'] = $reqTime;
        $content['memberCode'] = $memberCode;
        $content['deviceID'] = $deviceNo;
        $content['devName'] = $devName;
        $content['securityCode'] = $securityCode;
        $res = $this->curl($url, $content);
        $res = json_decode($res,true);
        if($res['code']==1){
            $return['code']=1;
        }else{
            $return['code']=0;
        }
        return $return;
    }

    public function delPrinter($info){
        $reqTime = $this->getMillisecond();
        //api密钥
        $apiKey = $info['apiKey'];
        //商户编码
        $memberCode = $info['shopNumber'];
        //设备编码
        $deviceNo = $info['deviceNumber'];
        $securityCode = md5($memberCode.$reqTime.$apiKey.$deviceNo);
        $url = 'http://printerapi.mod-softs.com:7777/SmarnetWebAPI/apisc/deldev';
        $content['reqTime'] = $reqTime;
        $content['memberCode'] = $memberCode;
        $content['deviceID'] = $deviceNo;
        $content['securityCode'] = $securityCode;
        $res = $this->curl($url, $content);
        $res = json_decode($res,true);
        if($res['code']==1){
            $return['code']=1;
        }else{
            $return['code']=0;
        }
        return $return;
    }

    public function curl($url,$data=null){
        if (empty($url)) {
            return false;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        if(!empty($data)){
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        if (curl_errno($curl)) {
            return false;
        }else{
            return $output;
            curl_close($curl);
        }
    }
}