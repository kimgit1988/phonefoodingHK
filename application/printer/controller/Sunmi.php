<?php
 
namespace app\printer\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * Sunmi打印机控制器
 * @author  kiyang
 */
class Sunmi extends Controller
{
    // 主订单打印方法
    public function PostOrder($order,$foods,$nick)
    {
        $text = $this->addOrderText($order,$foods,$nick);
        $return = ['code'=>1,'text'=>$text];
        return $return;
    }

    // 菜品打印方法
    public function PostFood($order,$food,$nick,$number='0'){
        $text = $this->addFoodText($food,$nick,$order);
        $return = ['code'=>1,'text'=>$text];
        return $return;
    }

    // 主订单打印方法
    public function AgainOrder($order,$foods,$nick)
    {
        $text = $this->addOrderText($order,$foods,$nick,1);
        $return = ['code'=>1,'text'=>$text];
        return $return;
    }

    // 菜品打印方法
    public function AgainFood($order,$food,$nick,$number='0'){
        $text = $this->addFoodText($food,$nick,$order,1);
        $return = ['code'=>1,'text'=>$text];
        return $return;
    }

    public function addOrderText($order,$foods,$nick,$again=0){
        $contact_content = '1B40';
        $contact_content .= '0A1B45001B61011D2111';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$order['contactName']));
        $contact_content .= '0A1B61001D21000A';
        if($again==1){
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'重打订单'));
            $contact_content .= '0A';
        }
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
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'豐富點點餐'));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '1B4A50';
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
        $food_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'豐富點點餐'));
        $food_content .= '0A';
        $food_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $food_content .= '1B4A50';
        return $food_content;
    }
}