<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 20/2/2019
 * Time: 8:27 PM
 */

namespace app\api\service;


use think\Db;

class OrderHandler
{
    /**
     * 保存订单，同时也保存订单详细；
     * @param $orderDetail
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function saveOrderGoods($orderDetail){

        $OrderNo = date('YmdHis').rand(1000,9999);

        /*
        $Notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/api'.'/wxPayNotify.php';//后台通知地址
        */
        if($orderDetail['userId']==""){
            $return = array('code'=>0,'error'=>'Openid 为空');
            json($return)->send();
            exit;
        }

        if(!empty($method)){
            $channle = DB::name('payMethod')->where('id',$method)->order('id asc')->find();
        }else{
            $channle = array('id'=>'','name'=>'');
        }

        $order = [
            'orderSN' => $OrderNo,
            'userId' => $orderDetail['userId'],
            'userNick' => $orderDetail['userNick'],
            'orderStatus' => 1,
            'payStatus' => 0,
            'payName' => $channle['name'],
            'goodsAmount' => $orderDetail['totalPrice'],
            'moneyPaid' => 0,
            'createTime' => time(),
            'contactNumber' => $orderDetail['contactNumber'],
            'contactName' => $orderDetail['contactName'],
            'contactLogoUrl' => $orderDetail['contactLogoUrl'],
            'contactMemberNumber' => $orderDetail['contactMemberNumber'],
            'contactMemberName' => $orderDetail['contactMemberName'],
            'userType' => $orderDetail['userType'],
            'orderLongitude' => $orderDetail['longitude'],
            'orderLatitude' => $orderDetail['latitude'],
            'printerId' => $orderDetail['printerId'],
            'courtId' => !empty($orderDetail['courtId'])?$orderDetail['courtId']:0
        ];

        $id = Db::name('wx_order')->insertGetId($order);

        //$order['payType'] = $method;
        //$order['payMethodId'] = $channle['id'];
        //$order['payMethodName'] = $channle['name'];
        //$order['orderInArea'] = $orderDetail['orderInArea'];
        $code = 1;

        Db::startTrans();
        try{
            foreach($orderDetail['foodList'] as $foodDetail){
                $save = [
                    'orderSN'=>$OrderNo,
                    'contactNumber'=>$orderDetail['contactNumber'],
                    'contactMemberNumber'=>$orderDetail['contactMemberNumber'],
                    'goodsThumbnailUrl'=>$foodDetail['thumbnailUrl'],
                    'goodsNumber'=>$foodDetail['number'],
                    'num'=>$foodDetail['counter'],
                    'goodsPrice'=>$foodDetail['salePrice'],
                    'goodsType'=>$foodDetail['payType'],
                    'unitName'=>$foodDetail['payUnit'],
                    'printerId' => $orderDetail['printerId'],
                    'goodsId'=>isset($foodDetail['id'])?$foodDetail['id']:'',
                    'goodsName'=>$foodDetail['name']
                ];
                Db::name('wx_order_goods')->insert($save);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $code = 0;
            // 回滚事务
            Db::rollback();
            throw $e;
        }
        if($code==1){
            // 根据id执行插入编号操作
            // 获取今天下单后下单餐厅的总下单数(自己及自己之前)
            $count = Db::name('wx_order')
                ->where('contactNumber',$orderDetail['contactNumber'])
                ->where('id','ELT',$id)
                ->where('createTime','EGT',strtotime(date('Ymd')))
                ->count();
            // 如果订单超过9999取9999的余数
            if ($count>9999) {
                $count = $count%9999;
            }
            $count=sprintf("%04d", $count);
            $update['orderAssignedNumber'] = $count;
            DB::name('wx_order')->where('id',$id)->update($update);

            $return['orderNo'] = $OrderNo;
            $return['msg'] = '插入成功';
            $return['code'] = 1;
        }else{
            $return['msg'] = '插入失败';
            $return['code'] = 0;
        }
        return $return;
    }
}