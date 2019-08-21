<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * 餐后支付控制器
 */
class Laterpay extends Controller
{

    public function PayPost($orderSN)
    {
        $update['orderStatus'] = 2;
	    $update['payStatus'] = 0;
	    $update['payType'] = '';
	    $update['payName'] = '';
	    $update['payTime'] = time();
	    $order = DB::name('wx_order')->where('orderSN',$orderSN)->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
	    $return = ['code'=>1,'msg'=>'訂單提交成功!'];
        return $return;
    }
}