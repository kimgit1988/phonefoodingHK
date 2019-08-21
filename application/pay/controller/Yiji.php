<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * Yiji支付控制器
 * @author  kiyang
 */
class Yiji extends Controller
{

    public function PayPost($order)
    {
        $update['orderStatus'] = 2;
	    $update['payStatus'] = 1;
	    $update['payTime'] = time();
	    $order = DB::name('wx_order')->where('orderSN',$order['orderSN'])->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
	    $return = ['code'=>1,'msg'=>'支付成功!'];
        // $return = 'Yiji支付功能尚未完成,敬请期待';
        return $return;
    }
}