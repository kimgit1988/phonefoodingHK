<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * pay功能通用-總控制器
 * @author  kiyang
 */
class Index
{

    public function payOrder($orderNo,$method)
    {
    	// 支付方式
    	$paymethod = DB::name('payMethod')->where('id',$method)->where('isDelete',0)->find();
    	// 订单详情
    	$order = DB::name('wx_order')->where('orderSN',$orderNo)->where('isDelete',0)->find();
    	// 获取支付方式所在控制器
        // 关键参数是否为空
        if(!empty($paymethod)&&!empty($order)){
            $className = '\app\pay\controller\\'.$paymethod['fileName'];
            // 类是否存在
            if(class_exists($className)){
                // 实例化支付方式
                $pay = new $className;
                // 类方法是否存在
                if(method_exists($pay,'PayPost')){
                    // 调用接口生成发送报文获取回复信息
                    $return = $pay->PayPost($order);
                    return $return;
                }else{
                    return ['code'=>0,'msg'=>'該支付方法錯誤'];
                }
            }else{
                return ['code'=>0,'msg'=>'該支付方式無法調期支付'];
            }
        }else{
            return ['code'=>0,'msg'=>'調起支付失敗'];
        }
    }

    public function payQuery()
    {
        $orderNo = input('orderNo');
        // 订单详情
        $order = DB::name('wx_order')->where('orderSN',$orderNo)->where('isDelete',0)->find();
        if(!empty($order)&&!empty($order['payMethodId'])){
            // 支付方式
            $paymethod = DB::name('payMethod')->where('id',$order['payMethodId'])->where('isDelete',0)->find();
            if(!empty($paymethod)){
                $className = '\app\pay\controller\\'.$paymethod['fileName'];
                // 实例化支付方式
                $pay = new $className;
                // 调用接口生成发送报文获取回复信息
                $return = $pay->payQuery($order);
                return $return;
            }else{
                return ['code'=>0,'msg'=>'未能找到對應的訂單渠道'];
            }
        }else{
            return ['code'=>0,'msg'=>'未能找到對應的訂單'];
        }
        // 获取支付方式所在控制器
        
    }
}