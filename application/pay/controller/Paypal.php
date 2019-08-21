<?php
 
namespace app\pay\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use PayPal\Api\PaymentExecution;

/*
 * Paypal支付控制器
 * @author kevin
 */
class Paypal extends Controller
{
    const clientId = 'AZbxMhm-De9k3PcMSQex2sLLGe1ae-r-RJHdL6slyaK4DeEMiOPC6-GZP6uYBKKXSd8MSYrjM9QTDsnD';//ID
    const clientSecret = 'EP_6tjE2U39eMeamv__YeV2UKlhie8MpXMKieACMBs_bZkmwaglWxqHWSB6WYv6WxqOPkIS7Y2zclAHV';//秘钥
    const accept_url = 'http://food.vbus.hk/pay/paypal/paynotify';//通知回调地址
    const Currency = 'USD';//币种
    const error_log = 'PayPal-error.log';//错误日志
    const success_log = 'PayPal-success.log';//成功日志
    protected $PayPal;

    public function __construct()
    {
        $this->PayPal = new ApiContext(
            new OAuthTokenCredential(
                self::clientId,
                self::clientSecret
            )
        );
        $this->PayPal->setConfig(
            array(
                'mode' => 'sandbox',
                'http.ConnectionTimeOut' => 30,
            )
        );
    }

    public function PayPost($order)
    {
        $product = $order['orderSN'];
        if (empty($product)) {
            return ajax_return(400, '商品不能为空');
        }

        $price = $order['moneyPaid'];
        if (empty($price)) {
            return ajax_return(400, '价格不能为空');
        }

        $shipping = input('shipping', 0);

        $description = "豐富點智能點餐系统-Paypal支付测试";
        if (empty($description)) {
            return ajax_return(400, '描述内容不能为空');
        }
        $return = $this->pay($product, $price, $shipping, $description);
        return $return;
    }

    /**
     * @param
     * $product 商品
     * $price 价钱
     * $shipping 运费
     * $description 描述内容
     */
    public function pay($product, $price, $shipping = 0, $description)
    {
        $paypal = $this->PayPal;
        $total = $price + $shipping;//总价

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Item();
        $item->setName($product)->setCurrency(self::Currency)->setQuantity(1)->setPrice($price);

        $itemList = new ItemList();
        $itemList->setItems([$item]);

        $details = new Details();
        $details->setShipping($shipping)->setSubtotal($price);

        $amount = new Amount();
        $amount->setCurrency(self::Currency)->setTotal($total)->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription($description)->setInvoiceNumber(uniqid());

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(self::accept_url . '?success=true')->setCancelUrl(self::accept_url . '/?success=false');

        $payment = new Payment();
        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);

        try {
            $payment->create($paypal);
        } catch (PayPalConnectionException $e) {
            echo $e->getData();
            die();
        }
        $approvalUrl = $payment->getApprovalLink();
        $parse_arr = convertUrlQuery($approvalUrl);
        DB::name('wxOrder')->where('orderSN',$product)->where('payStatus',0)->where('isDelete',0)->update(['payToken'=>$parse_arr['token']]);
        return ['code'=>6,'msg'=>'跳轉中','url'=>$approvalUrl];
    }

    /**
     * 通知
     */
    public function PayNotify()
    {
        $success = trim($_GET['success']);
        if ($success == 'false' && !isset($_GET['paymentId']) && !isset($_GET['PayerID'])) {
            log_output('取消付款', self::error_log);
            $this->redirect(url('index/payorder'));
            exit();
        }
        $paymentId = trim($_GET['paymentId']);
        $PayerID = trim($_GET['PayerID']);
        if (!isset($success, $paymentId, $PayerID)) {
            log_output('支付失败', self::error_log);
            $this->redirect(url('index/payorder'));
            exit();
        }
        if ((bool)$_GET['success'] === 'false') {
            log_output('支付失败，支付ID【' . $paymentId . '】,支付人ID【' . $PayerID . '】',self::error_log);
            $this->redirect(url('index/payorder'));
            exit();
        }
        $payment = Payment::get($paymentId, $this->PayPal);
        $execute = new PaymentExecution();
        $execute->setPayerId($PayerID);
        try {
            $payment->execute($execute, $this->PayPal);
        } catch (Exception $e) {
            log_output($e . ',支付失败，支付ID【' . $paymentId . '】,支付人ID【' . $PayerID . '】', self::error_log);
            $this->redirect(url('index/payorder'));
            exit();
        }
        log_output('支付成功，支付ID【' . $paymentId . '】,支付人ID【' . $PayerID . '】', self::error_log);
        log_output($_GET, self::error_log);
        if($success == 'true' && !empty($_GET['token'])){
            $order_info = DB::name('wxOrder')->where('payToken',$_GET['token'])->where('payStatus',0)->where('isDelete',0)->find();
            // 修改订单状态为已支付
            $update['orderStatus'] = 2;
            $update['payStatus'] = 1;
            $update['payTime'] = time();
            $update['methodPayStatus'] = 3;
            DB::name('wxOrder')->where('orderSN',$order_info['orderSN'])->where('payStatus',0)->where('isDelete',0)->update($update);
            // 返回地址
            $jumpUrl = "http://".$_SERVER['SERVER_NAME'].url('wxweb/index/orderdetail',['ordersn'=>$order_info['orderSN']]);
            $this->redirect($jumpUrl);
        }

    }

    /*
     * 回调
     */
    public function Callback(){
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];//这里在php7下不能获取数据，使用 php://input 代替
        if(!$postStr){
            $postStr = file_get_contents("php://input");
        }
        log_output($postStr);
    }

}