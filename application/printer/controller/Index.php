<?php

namespace app\printer\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;

/**
 * printer功能通用-總控制器
 * @author  kiyang
 */
class Index extends Controller
{

    public function printOrder($orderSN,$nick)
    {
        // 订单详情
        $order = DB::name('wxOrder')
            ->alias('o')
            ->join('mos_contact c','o.contactNumber = c.number','left')
            ->join('mos_printer p','c.printerId = p.id','left')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('o.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            ->where('o.orderSN',$orderSN)
            ->where('o.isDelete',0)
            ->find();
        //获取默认打印机
        $default = DB::name('printer')
            ->alias('p')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            ->where('p.contactNumber',$order['contactNumber'])
            ->where('p.defaultPrint',1)
            ->where('p.isDelete',0)
            ->find();
        $smprinter = [];
        if(!empty($order['contactNumber'])){
            $smprinter = DB::name('printer')
                           ->alias('p')
                           ->join('mos_printer_brand b','p.brandId = b.id','left')
                           ->field('p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                           ->where('p.contactNumber',$order['contactNumber'])
                           ->where('b.fileName','Sunmi')
                           ->where('p.isDelete',0)
                           ->find();
        }
        //修改为关联部门 部门关联打印机 2019-01-10
        $foods = DB::name('wxOrderGoods')
            ->alias('g')
            ->join('mos_goods s','g.goodsId = s.id','left')
            ->join('mos_contact_department d','s.departmentId = d.id','left')
            ->join('mos_printer p','d.printerId = p.id','left')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('g.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            ->where('orderSN',$orderSN)
            ->select();
        // MSG返回的消息 text返回的打印二进制编码 code打印格式,printerStatus打印状态//code开始为0成功一张就改为1 status开始为1失败一张就为0
        $return = array('code'=>true);
        // 获取支付方式所在控制器
        if(!empty($order)&&!empty($foods)){
            //是否有打印机信息获取默认打印机信息
            if(!empty($order['fileName'])||!empty($default['fileName'])){
                //如果有默认,而订单本身没有信息,使用默认打印机信息
                if(empty($order['fileName'])){
                    $order['deviceNick']=$default['deviceNick'];
                    $order['deviceNumber']=$default['deviceNumber'];
                    $order['shopNumber']=$default['shopNumber'];
                    $order['apiKey']=$default['apiKey'];
                    $order['fileName']=$default['fileName'];
                    $order['type']=$default['type'];
                }
                if(!empty($smprinter)&&session('mchbrand')=='SUNMI'){
                    $order['deviceNick']=$smprinter['deviceNick'];
                    $order['deviceNumber']=$smprinter['deviceNumber'];
                    $order['shopNumber']=$smprinter['shopNumber'];
                    $order['apiKey']=$smprinter['apiKey'];
                    $order['fileName']=$smprinter['fileName'];
                    $order['type']=$smprinter['type'];
                }
                // 先发起主订单打印
                $className = '\app\printer\controller\\'.$order['fileName'];
                // 判断类是否存在
                if(class_exists($className)){
                    // 实例化支付方式
                    $printer = new $className;
                    // 判断方法是否存在
                    if(method_exists($printer,'PostOrder')){
                        // 调用接口生成发送报文获取回复信息

                        if($order['type']==1&&$order['printerStatus']!=1){
                            $res = $printer->PostOrder($order,$foods,$nick);
                            // 根据打印机类型处理返回的值
                            if($res['code']==1){
                                $return['main']['code'] = true;
                            }else{
                                $return['code'] = false;
                                $return['main']['code'] = false;
                                $return['main']['msg'] = $res['msg'];
                            }
                        }
                        //云打印已提前处理
                        // else if($order['type']==2){
                        //     if($res['code']==1){
                        //         $return['code'] = 1;
                        //         $return['text'] = $return['text'].$res['text'];
                        //     }else{
                        //         $return['printerStatus'] = 0;
                        //     }
                        // }
                    }
                }
            }
            $food_number = 1;
            foreach ($foods as $key => $val) {
                // 套餐名不打印
                if($val['goodsType']!=3){
                    if(!empty($val['fileName'])||!empty($default['fileName'])){
                        //如果有默认,而菜品本身没有信息,使用默认打印机信息
                        if(empty($val['fileName'])){
                            $val['deviceNick']=$default['deviceNick'];
                            $val['deviceNumber']=$default['deviceNumber'];
                            $val['shopNumber']=$default['shopNumber'];
                            $val['apiKey']=$default['apiKey'];
                            $val['fileName']=$default['fileName'];
                            $val['type']=$default['type'];
                        }
                        // 获取菜品对应类
                        $className = '\app\printer\controller\\'.$val['fileName'];
                        if(class_exists($className)){
                            // 实例化
                            $printer = new $className;
                            if(method_exists($printer,'PostOrder')){
                                // 调用接口生成发送报文获取回复信息
                                if($val['type']==1&&$val['printerStatus']!=1){
                                    $res = $printer->PostFood($order,$val,$nick,$food_number);
                                    if($res['code']==1){
                                        $return['main']['food'][$val['id']]['code'] = true;
                                    }else{
                                        $return['code'] = false;
                                        $return['main']['food'][$val['id']]['code'] = false;
                                        $return['main']['food'][$val['id']]['msg'] = $res['msg'];
                                    }
                                }
                                // 云打印已提前处理
                                // else if($val['type']==2){
                                //     if($res['code']==1){
                                //         $return['code'] = 1;
                                //         $return['text'] = $return['text'].$res['text'];
                                //     }else{
                                //         $return['printerStatus'] = 0;
                                //     }
                                // }
                            }
                        }
                    }
                    $food_number++;
                }

            }
            return $return;
        }else{
            return ['code'=>0,'msg'=>'调起打印失败'];
        }
    }

    //重打控制器参数：
    //$orderSN 单号，
    //$nick 餐厅名字，
    //$addprint 加菜时小票是否只有加菜部分(0加菜部分 1全部),
    //$errorPrint 失败重打:只打印失败的部分
    public function againOrder($orderSN,$nick,$addprint=1,$errorPrint=0)
    {
        // 订单详情
        $order = DB::name('wxOrder')
            ->alias('o')
            ->join('mos_contact c','o.contactNumber = c.number','left')
            ->join('mos_printer p','c.printerId = p.id','left')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('o.*,c.printerId,c.smprinterId,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            ->where('o.orderSN',$orderSN)
            ->where('o.isDelete',0)
            ->find();
        //获取默认打印机
        $default = DB::name('printer')
            ->alias('p')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            ->where('p.contactNumber',$order['contactNumber'])
            ->where('p.defaultPrint',1)
            ->where('p.isDelete',0)
            ->find();
        $smprinter = [];
        if(!empty($order['contactNumber'])){
        $smprinter = DB::name('printer')
                       ->alias('p')
                       ->join('mos_printer_brand b','p.brandId = b.id','left')
                       ->field('p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                       ->where('p.contactNumber',$order['contactNumber'])
                       ->where('b.fileName','Sunmi')
                       ->where('p.isDelete',0)
                       ->find();
        }
        //修改为关联部门 部门关联打印机 2019-01-10
        $foods = DB::name('wxOrderGoods')
            ->alias('g')
            ->join('mos_goods s','g.goodsId = s.id','left')
            ->join('mos_contact_department d','s.departmentId = d.id','left')
            ->join('mos_printer p','d.printerId = p.id','left')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('g.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type,d.reprinterId')
            ->where('orderSN',$orderSN)
            ->select();
        // MSG返回的消息 text返回的打印二进制编码 code打印格式,printerStatus打印状态//code开始为0成功一张就改为1 status开始为1失败一张就为0
        $return = array('msg'=>[],'text'=>'','code'=>0,'printerStatus'=>1);
        $pstatus = 1;
        // 获取支付方式所在控制器
        if(!empty($order)&&!empty($foods)){
            if($errorPrint==1&&$order['printerStatus']!=2){
                //错误重打并且订单没有打印失败，不做处理
            }else {
                if(!empty($order['fileName']) || !empty($default['fileName'])) {
                    //如果有默认,而订单本身没有信息,使用默认打印机信息
                    if(empty($order['fileName'])) {
                        $order['deviceNick']   = $default['deviceNick'];
                        $order['deviceNumber'] = $default['deviceNumber'];
                        $order['shopNumber']   = $default['shopNumber'];
                        $order['apiKey']       = $default['apiKey'];
                        $order['fileName']     = $default['fileName'];
                        $order['type']         = $default['type'];
                    }
                    if(!empty($smprinter) && session('mchbrand') == 'SUNMI') {
                        $order['deviceNick']   = $smprinter['deviceNick'];
                        $order['deviceNumber'] = $smprinter['deviceNumber'];
                        $order['shopNumber']   = $smprinter['shopNumber'];
                        $order['apiKey']       = $smprinter['apiKey'];
                        $order['fileName']     = $smprinter['fileName'];
                        $order['type']         = $smprinter['type'];
                    }
                    $return['main']['order']['ptintname'] = $order['deviceNick'];
                    // 先发起主订单打印
                    $className = '\app\printer\controller\\'.$order['fileName'];
                    // 判断类是否存在
                    if(class_exists($className)) {
                        // 实例化支付方式
                        $printer = new $className;
                        // 判断方法是否存在
                        if(method_exists($printer, 'AgainOrder')) {
                            //加单打印时客户小票是否包含全部菜品：1全部菜品，0加单菜品
                            $order_foods_array = [];
                            if($addprint == 0) {
                                foreach($foods as $val) {
                                    if($val['addStatus'] == 1) {
                                        $order_foods_array[] = $val;
                                    }
                                }
                            } else {
                                $order_foods_array = $foods;
                            }
                            // 调用接口生成发送报文获取回复信息
                            $res = $printer->AgainOrder($order, $order_foods_array, $nick);
                            // 根据打印机类型处理返回的值
                            if($order['type'] == 1) {
                                if($res['code'] == 1) {
                                    $return['code']                  = 1;
                                    $return['main']['order']['code'] = true;
                                    $return['main']['order']['msg']  = $res['msg'];
                                    $return['msg'][]                 = $res['msg'];
                                } else {
                                    $pstatus = 0;
                                    $return['code'] = 0;
                                    $return['printerStatus']         = 0;
                                    $return['main']['order']['code'] = false;
                                    $return['main']['order']['msg']  = $res['msg'];
                                    $return['msg'][]                 = $res['msg'];
                                }
                            } else {
                                if($order['type'] == 2) {
                                    if($res['code'] == 1) {
                                        $return['code'] = 1;
                                        $return['text'] = $return['text'].$res['text'];
                                    } else {
                                        $return['printerStatus'] = 0;
                                    }
                                }
                            }
                        } else {
                            $return['printerStatus'] = 0;
                            $return['code']                  = false;
                            $return['main']['order']['code'] = false;
                            $return['main']['order']['msg']  = '收银台订单打印失败-打印机打印失败';
                            $return['msg'][]                 = '收银台订单打印失败-打印机打印失败';
                        }
                    } else {
                        $return['printerStatus'] = 0;
                        $return['code']                  = false;
                        $return['main']['order']['code'] = false;
                        $return['main']['order']['msg']  = '收银台订单打印失败-暂不支持该打印机型号';
                        $return['msg'][]         = '收银台订单打印失败-暂不支持该打印机型号';
                    }
                } else {
                    $return['printerStatus'] = 0;
                    $return['code']                  = false;
                    $return['main']['order']['code'] = false;
                    $return['main']['order']['msg']  = '收银台订单打印失败-收银台未设置打印机或者暂不支持该打印机型号';
                    $return['msg'][]         = '收银台订单打印失败-收银台未设置打印机或者暂不支持该打印机型号';
                }
                //加单打印时后厨菜品只打印加单的菜品
                $foods_array = [];
                if($order['addStatus'] == 1) {
                    foreach($foods as $val) {
                        if($val['addStatus'] == 1) {
                            $foods_array[] = $val;
                        }
                    }
                } else {
                    $foods_array = $foods;
                }
            }
            $food_number = 1;
            foreach ($foods_array as $key => $val) {
                if($errorPrint == 1 && $val['printerStatus'] != 2) {
                    //错误重打并且菜品没有打印失败，不做处理
                } else {
                    // 非套餐名才打印
                    if($val['goodsType'] != 3) {
                        if(!empty($val['fileName']) || !empty($default['fileName'])) {
                            //如果有默认,而菜品本身没有信息,使用默认打印机信息
                            if(empty($val['fileName'])) {
                                $val['deviceNick']   = $default['deviceNick'];
                                $val['deviceNumber'] = $default['deviceNumber'];
                                $val['shopNumber']   = $default['shopNumber'];
                                $val['apiKey']       = $default['apiKey'];
                                $val['fileName']     = $default['fileName'];
                                $val['type']         = $default['type'];
                            }
                            $return['main']['food'][$val['id']]['ptintname'] = $val['deviceNick'];
                            // 获取菜品对应类
                            $className = '\app\printer\controller\\'.$val['fileName'];
                            if(class_exists($className)) {
                                // 实例化
                                $printer = new $className;
                                if(method_exists($printer, 'AgainOrder')) {
                                    // 调用接口生成发送报文获取回复信息
                                    $res = $printer->AgainFood($order, $val, $nick, $food_number);
                                    log_output($res);
                                    if($val['type'] == 1) {
                                        //type 1 为云打印机
                                        if($res['code'] == 1) {
                                            $return['code']                                  = 1;
                                            $return['main']['food'][$val['id']]['code']      = true;
                                            $return['main']['food'][$val['id']]['msg']       = $res['msg'];
                                            $return['main']['food'][$val['id']]['goodsname'] = $val['goodsName'];
                                            $return['msg'][]                                 = $res['msg'];
                                        } else {
                                            $return['printerStatus']                         = 0;
                                            $return['code']                                  = false;
                                            $return['main']['food'][$val['id']]['code']      = false;
                                            $return['main']['food'][$val['id']]['msg']       = $res['msg'];
                                            $return['main']['food'][$val['id']]['goodsname'] = $val['goodsName'];
                                            $return['msg'][]                                 = $res['msg'];
                                        }
                                    } else {
                                        if($val['type'] == 2) {
                                            if($res['code'] == 1) {
                                                $return['code'] = 1;
                                                $return['text'] = $return['text'].$res['text'];
                                            } else {
                                                $return['printerStatus'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $return['printerStatus'] = 0;
                                    $return['code']                                  = false;
                                    $return['main']['food'][$val['id']]['code']      = false;
                                    $return['main']['food'][$val['id']]['msg']       = $val['goodsName'].'打印失败';
                                    $return['main']['food'][$val['id']]['goodsname'] = $val['goodsName'];
                                    $return['msg'][]                                 = '菜品订单'.$val['goodsName'].'失败-打印机打印失败';
                                }
                            } else {
                                $return['printerStatus'] = 0;
                                $return['code']                                  = false;
                                $return['main']['food'][$val['id']]['code']      = false;
                                $return['main']['food'][$val['id']]['msg']       = $val['goodsName'].'打印失败';
                                $return['main']['food'][$val['id']]['goodsname'] = $val['goodsName'];
                                $return['msg'][]                                 = '菜品订单'.$val['goodsName'].'失败-暂不支持该打印机型号';
                            }
                        } else {
                            $return['printerStatus'] = 0;
                            $return['code']                                  = false;
                            $return['main']['food'][$val['id']]['code']      = false;
                            $return['main']['food'][$val['id']]['msg']       = $val['goodsName'].'打印失败';
                            $return['main']['food'][$val['id']]['goodsname'] = $val['goodsName'];
                            $return['msg'][]                                 = '菜品订单'.$val['goodsName'].'失败-菜品未设置打印机或者暂不支持该打印机型号';
                        }
                        $food_number++;
                    }
                }
            }
            if($pstatus == 0){
                $return['code'] = 0;
            }
            if(!empty($return['msg'])){
                $return['msg'] = implode(',', $return['msg']);
            }else{
                $return['msg'] = '没有打印数据';
            }
            return $return;
        }else{
            return ['code'=>0,'msg'=>'调起打印失败'];
        }
    }

    //打印统计日结数据
    public function printData($data){
        $className = '\app\printer\controller\\'.'Gainscha';
        // 判断类是否存在
        if(class_exists($className)){
            // 实例化支付方式
            $printer = new $className;
            // 判断方法是否存在
            if(method_exists($printer,'PostPrintData')){
                // 调用接口生成发送报文获取回复信息
                $res = $printer->PostPrintData($data);
                // 根据打印机类型处理返回的值
                return $res;
            }
        }
    }

    public function getNotYumPrint($orders,$foods,$nick){
        // 存储各订单的非云打印数据并返回
        $msg = array();
        // 存储订单菜品
        $foodlist = array();
        if(!empty($orders)&&!empty($foods)){
            foreach ($foods as $key => $val) {
                // 根据订单放入二维数组
                $foodlist[$val['orderSN']][$val['id']] = $val;
            }
            // 对foodlist排序
            foreach($foodlist as $k => $v){
                ksort($foodlist[$k]);
            }
            foreach ($orders as $order) {
                if($order['type']==2&&$order['printerStatus']!=1){
                    if(!empty($order['fileName'])){
                        // 先发起主订单打印
                        $className = '\app\printer\controller\\'.$order['fileName'];
                        // 判断类是否存在
                        if(class_exists($className)){
                            // 实例化支付方式
                            $printer = new $className;
                            // 判断方法是否存在
                            if(method_exists($printer,'PostOrder')){
                                // 调用接口生成发送报文获取回复信息
                                $res = $printer->PostOrder($order,$foodlist[$order['orderSN']],$nick);
                                // 根据打印机类型处理返回的值 
                                if($res['code']==1){
                                    $msg[$order['orderSN']]['main'] = $res['text'];
                                }
                            }
                        }

                    }
                }
                if(!empty($foodlist[$order['orderSN']])){
                    $food_number = 1;
                    foreach ($foodlist[$order['orderSN']] as $key => $val) {
                        if($val['type']==2&&$val['printerStatus']!=1){
                            // 非套餐名才打印
                            if($val['goodsType']!=3){
                                if(!empty($val['fileName'])){
                                    // 获取菜品对应类
                                    $className = '\app\printer\controller\\'.$val['fileName'];
                                    if(class_exists($className)){
                                        // 实例化
                                        $printer = new $className;
                                        if(method_exists($printer,'PostOrder')){
                                            // 调用接口生成发送报文获取回复信息
                                            $res = $printer->PostOrder($order,$foods,$nick);
                                            // 根据打印机类型处理返回的值 
                                            if($res['code']==1){
                                                $msg[$order['orderSN']]['food'][$val['id']] = $res['text'];
                                            }
                                        }
                                    }
                                }
                                $food_number++;
                            }
                        }
                    }
                }

            }
            return $msg;
        }else{
            return ['code'=>0,'msg'=>'无需要打印的值'];
        }
    }

    public function addPrinter($printer)
    {
        $return = array();
        if(!empty($printer['fileName'])){
            // 获取菜品对应类
            $className = '\app\printer\controller\\'.$printer['fileName'];
            if(class_exists($className)){
                // 实例化
                $print = new $className;
                if(method_exists($print,'addPrinter')){
                    $res = $print->addPrinter($printer);
                    if($res['code']==1){
                        $return = ['msg'=>'添加成功','code'=>1];
                    }else{
                        $return = ['msg'=>'添加失败','code'=>0];
                    }
                }else{
                    $return = ['msg'=>'未找到该打印机线上添加方法','code'=>0];
                }
            }else{
                $return = ['msg'=>'未找到该打印机线上添加方法','code'=>0];
            }
        }else{
            $return = ['msg'=>'未找到该打印机线上添加方法','code'=>0];
        }
        return $return;
    }

    public function delPrinter($printer)
    {
        $return = array();
        if(!empty($printer['fileName'])){
            // 获取菜品对应类
            $className = '\app\printer\controller\\'.$printer['fileName'];
            if(class_exists($className)){
                // 实例化
                $print = new $className;
                if(method_exists($print,'delPrinter')){
                    $res = $print->delPrinter($printer);
                    if($res['code']==1){
                        $return = ['msg'=>'删除成功','code'=>1];
                    }else{
                        $return = ['msg'=>'删除失败','code'=>0];
                    }
                }else{
                    $return = ['msg'=>'未找到该打印机线上删除方法','code'=>0];
                }
            }else{
                $return = ['msg'=>'未找到该打印机线上删除方法','code'=>0];
            }
        }else{
            $return = ['msg'=>'未找到该打印机线上删除方法','code'=>0];
        }
        return $return;
    }


}