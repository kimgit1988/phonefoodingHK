<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class WxOrder extends Model
{

    public function deleteOrder($id)
    {
        $delete['isDelete'] = 1;
        return $this->where('id',$id)->update($delete);  
    }

    /**
     * 获取商家订单，默认为当天
     */
    public function getOrderList($contact_number,$where=[],$startTime=0,$endTime=0)
    {
        $where     = empty($where) ? '1=1' : $where;
        $startTime = empty($startTime) ? getStartAndEndData('today')['startDate'] : $startTime;
        $endTime   = empty($endTime) ? getStartAndEndData('today')['endState'] : $endTime;
        $orderList = Db::name('WxOrder')
                       ->where('contactNumber', $contact_number)
                       ->where('isDelete', 0)
                       ->where($where)
                       ->where('createTime', 'between', [$startTime, $endTime])
                       ->select();
        return $orderList;
    }

    /**
     * 返回订单状态名
     * @param     $value  订单状态OrderStatus
     * @param int $payMode  支付模式LaterPay：0线下  1线上
     * @return mixed
     */
    public function getOrderStatusAttr($value,$payMode=0)
    {
        if($payMode){
            $order_status = [0=>'已取消', 1=>'未付款', 2=>'待接單', 3=>'已確認', 4=>'已完成', 5=>'退款訂單'];
        }else{
            $order_status = [0=>'已取消', 1=>'未付款', 2=>'待接單', 3=>'待支付', 4=>'已完成'];
        }
        return $order_status[$value];
    }

    public function getPayStatusAttr($value)
    {
        $pay_status = [0=>'未付款', 1=>'已付款', 2=>'已退款'];
        return $pay_status[$value];
    }

    protected function getCreateTimeAttr($value)
    {
        if(!empty($value)){
            $ctime = date('Y-m-d H:i:s',$value);
        }else{
            $ctime = '';
        }
        return $ctime;
    }

    protected function getPayTimeAttr($value)
    {
        if(!empty($value)){
            $ptime = date('Y-m-d H:i:s',$value);
        }else{
            $ptime = '';
        }
        return $ptime;
    }
}
