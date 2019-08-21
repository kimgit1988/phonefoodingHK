<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/4/9
 * Time: 10:03
 */

namespace app\service;


use think\Db;

class Orderlist
{
    public function getOrdersDetail($contact_number,$ordersn){
        // 訂單編號集合
        $orderSn = array();
        // 新訂單集合
        $newOrder = array();
        // 重新排序后的訂單
        $orderlist = array();
        // 重新排序后的訂單菜品
        $orderFoodslist = array();
        // 把訂單號寫入集合
        $order = DB::name('wxOrder')
                   ->alias('o')
                   ->join('mos_contact c','o.contactNumber = c.number','left')
                   ->join('mos_printer p','c.printerId = p.id','left')
                   ->join('mos_printer_brand b','p.brandId = b.id','left')
                   ->field('o.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                   ->where('o.contactNumber',$contact_number)
                   ->where('o.orderSN',$ordersn)
                   ->where('o.isDelete',0)
                   ->order('o.orderStatus=2 desc,o.id desc')
                   ->limit(5)
                   ->select();
        if(!empty($order)){
            foreach ($order as $key => $val) {
                $orderSn[] = $val['orderSN'];
            }
            //获取默认打印机
            $default = DB::name('printer')
                         ->alias('p')
                         ->join('mos_printer_brand b','p.brandId = b.id','left')
                         ->field('p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                         ->where('p.contactNumber',$contact_number)
                         ->where('p.defaultPrint',1)
                         ->where('p.isDelete',0)
                         ->find();
            // 查詢訂單菜品
            $orderFoods = DB::name('wxOrderGoods')
                            ->alias('g')
                            ->join('mos_goods s','g.goodsId = s.id','left')
                            ->join('mos_contact_department d','s.departmentId = d.id','left')
                            ->join('mos_printer p','d.printerId = p.id','left')
                            ->join('mos_printer_brand b','p.brandId = b.id','left')
                            ->field('g.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                            ->where('g.contactNumber',$contact_number)
                            ->where('g.orderSN','in',$orderSn)
                            ->order('g.id asc')
                            ->select();
            foreach ($order as $key => $val) {
                if(empty($val['fileName'])){
                    $order[$key]['deviceNick']=$default['deviceNick'];
                    $order[$key]['deviceNumber']=$default['deviceNumber'];
                    $order[$key]['shopNumber']=$default['shopNumber'];
                    $order[$key]['apiKey']=$default['apiKey'];
                    $order[$key]['fileName']=$default['fileName'];
                    $order[$key]['type']=$default['type'];
                }
            }
            foreach ($orderFoods as $key => $value) {
                if(empty($value['fileName'])){
                    $orderFoods[$key]['deviceNick']=$default['deviceNick'];
                    $orderFoods[$key]['deviceNumber']=$default['deviceNumber'];
                    $orderFoods[$key]['shopNumber']=$default['shopNumber'];
                    $orderFoods[$key]['apiKey']=$default['apiKey'];
                    $orderFoods[$key]['fileName']=$default['fileName'];
                    $orderFoods[$key]['type']=$default['type'];
                }
            }
            $orderinfo = array();
            foreach($orderFoods as &$food) {
                if($food['goodsId']!=0){
                    $food['spec'] = $this->getGoodSpec($contact_number,$food['goodsId']);
                }
            }
            foreach ($orderFoods as $key => $val) {
                if($val['goodsType']<3){
                    $orderinfo['food_'.$val['id']] = $val;
                }else if($val['goodsType']==3){
                    if(!empty($orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']]['_food'])){
                        $val['_food'] = $orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']]['_food'];
                    }
                    $orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']] = $val;
                }else{
                    $orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']]['_food'][] = $val;
                }
            }
            $orderFoods = $orderinfo;
            // 把菜品按訂單放入數組
            foreach ($orderFoods as $k => $v) {
                if(isset($v['orderSN'])){
                    $orderFoodslist[$v['orderSN']][] = $v;
                }
            }
            // 把菜品放入新訂單集合中
            foreach ($order as $key => $val) {
                $orderlist['order_info'] = $val;
                $orderlist['order_info']['createTime'] = date('Y-m-d H:i',$val['createTime']);
                if(!empty($orderFoodslist[$val['orderSN']])){
                    $orderlist['goods_info'] = $orderFoodslist[$val['orderSN']];
                }else{
                    $orderlist['goods_info'] =[];
                }
            }
            return ['code'=>true,'msg'=>'success','data'=>$orderlist];
        }else{
            return ['code'=>false,'msg'=>'找不到该订单'];
        }
    }

    public function getGoodSpec($contact_number,$goodsid){
        $goodsSpecs = DB::name('GoodsSpec')
                        ->where('isDelete', 0)
                        ->where('gs_disable', 1)
                        ->where('contactNumber', $contact_number)
                        ->where('gs_good_id',$goodsid)
                        ->group('gs_spec_pid')
                        ->select();
        if(count($goodsSpecs)>0){
            $commenSpecsP = DB::name('spec')
                              ->where('isDelete', 0)
                              ->where('contactNumber', $contact_number)
                              ->where('id', 'in', array_column($goodsSpecs,'gs_spec_pid'))
                              ->select();

            foreach($commenSpecsP as &$specs){
                $commenSpecs = DB::name('spec')
                                 ->where('isDelete', 0)
                                 ->where('contactNumber', $contact_number)
                                 ->where('spec_pid', $specs['id'])
                                 ->select();
                $specs['spec'] =$commenSpecs;
            }
            return $commenSpecsP;
        }else{
            return [];
        }
    }
}