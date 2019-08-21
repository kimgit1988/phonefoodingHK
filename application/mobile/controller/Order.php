<?php
namespace app\mobile\controller;
use app\mobile\controller\Base;
use app\service\Foodlist;
use app\service\Orderlist;
use think\View;//视图
use think\Controller;//控制器
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库

class Order extends Base {
    public function index() {
        $action = input('action');
        $table = input('param.table');
        $action = !empty($action)?$action:2;
        $name   = session('mob_user.nick');
        $staff_contact = session('mob_user.is_contact');
        $where  = array();
        // 获取餐厅编号
        $contact_number = session('mob_user.contact_number');
        $where['contactNumber'] = $contact_number;
        //餐桌点进来时进行餐桌号过滤
        if(!empty($table)) $where['contactMemberNumber'] = $table;
        $neworder = DB::name('wxOrder')
                    ->field('orderStatus,count(id) as order_count')
                    ->where($where)
                    ->group('orderStatus')
                    ->select();
        $addNewOrder = DB::name('wxOrder')
                      ->field('orderStatus,count(id) as order_count')
                      ->where($where)
                      ->where('addStatus=1 or orderStatus=2')
                      ->where('isDelete',0)
                      ->find();
        $contact_member = DB::name('ContactMember')
            ->where('contactNumber',$contact_number)
            ->where('isDelete',0)
            ->select();
        $contact = DB::name('Contact')
            ->field('autoOrder,offpaytype')
            ->where('number',$contact_number)
            ->where('isDelete',0)
            ->find();
        $newNum = [];
        foreach($neworder as $oc)
        {
            $newNum[$oc['orderStatus']] = intval($oc['order_count']);
        }
        //该订单状态没数据时设置一个默认值
        foreach([2,3,4,5,0] as $item)
        {
            if($item==2){
                $newNum[$item] = $addNewOrder['order_count'];
            }
        }
        //确认收款埋单
        $paytype_ids = empty(json_decode($contact['offpaytype'],true))?[9999]:json_decode($contact['offpaytype'],true);
        $payment_data = Db::name('PayMethod')
                          ->where('online',0)
                          ->where('id','not in',$paytype_ids)
                          ->select();
        $this->assign('payment_data',$payment_data);

        $this->assign('staff_contact',$staff_contact);
        $this->assign('contact_member',$contact_member);
        $this->assign('name',$name);
        $this->assign('num',$newNum);
        $this->assign('action',$action);
        $this->assign('contact',$contact);
        $this->assign('table',$table);
        return $this->fetch();
    }

    //商家买单
    public function getOrderinfo() {
        $orderSN = input('param.orderSN');
        $contact_number = session('mob_user.contact_number');
        $orderdata = [];
        if(!empty($orderSN)){
            $foodObject = new Orderlist();
            $orderdata = $foodObject->getOrdersDetail($contact_number,$orderSN);
        }
        return $orderdata;
    }

    public function orderDetail() {
        $request = Request::instance();
        $orderSN = input('param.ordersn');
        $order = input('param.order');
        $orderdata = [];
        $contact_number = session('mob_user.contact_number');
        if ($request->isPost()) {
            $params = $request->param();
            $orderdata = json_decode($params['order'],true);
            $foodObject = new Orderlist();
            $order_data = $foodObject->getOrdersDetail($orderdata['0']['contactNumber'],$orderdata['0']['orderSN']);
            $order_data = $order_data['data']['goods_info'];
            $ordera_mount = 0;
            $foodsids = [];
            $res = false;
            //开启事务
            DB::startTrans();
            try{
                $foodsids = array_column($orderdata,'id');
                foreach($orderdata as $key=>$item){
                    $ordera_mount += ($item['num']*$order_data[$key]['goodsPrice']);
                    DB::name('wxOrderGoods')->where('orderSN',$orderdata['0']['orderSN'])->where('id',$item['id'])->update(['num'=>$item['num']]);
                    if(isset($item['_food'])){
                        foreach($item['_food'] as $food){
                            array_push($foodsids,$food['id']);
                            DB::name('wxOrderGoods')->where('orderSN',$orderdata['0']['orderSN'])->where('id',$food['id'])->update(['num'=>$item['num']]);
                        }
                    }
                }
                $ordera_mount = round($ordera_mount, 2);
                DB::name('wxOrder')->where('orderSN',$orderdata['0']['orderSN'])->update(['goodsAmount'=>$ordera_mount,'moneyPaid'=>$ordera_mount]);
                Db::name('wxOrderGoods')->where('orderSN',$orderdata['0']['orderSN'])->where('id','not in',$foodsids)->delete();
                Db::commit();
                $res = true;
            } catch (\Exception $e) {
                $res = false;
                // 回滚事务
                Db::rollback();
            }

            if($res!==false){
                return $this->success('修改成功');
            }else{
                return $this->error('修改失敗');
            }
        }else{
            if(!empty($orderSN)){
                $order = DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('isDelete',0)->find();
                $foods = Db::name('wxOrderGoods')->where('orderSN',$orderSN)->select();
            }else{
                $this->error('訂單不存在!',url('order/index'));
            }
            $foodObject = new Orderlist();
            $orderdata = $foodObject->getOrdersDetail($contact_number,$orderSN);
            //结束商家点餐删除状态
            if(session('contacttype')==2) session::delete('contacttype');
            $contact_info = DB::name('Contact')->where('number',$contact_number)->find();
            $paytype_ids = empty(json_decode($contact_info['offpaytype'],true))?[9999]:json_decode($contact_info['offpaytype'],true);
            $payment_data = Db::name('PayMethod')
                ->where('online',0)
                ->where('id','not in',$paytype_ids)
                ->select();

            $this->assign('payment_data',$payment_data);
            $this->assign('contact_info',$contact_info);
            $this->assign('order',$order);
            $this->assign('orderdata',$orderdata);
            $this->assign('foods',$foods);
//         var_dump($orderdata);
            return $this->fetch();
        }
    }

    public function orderEdit() {
        $request = Request::instance();
        $orderSN = input('param.ordersn');
        $menu_arr = $order_info = [];
        if(!empty($orderSN)){
            $contact_number = session('mob_user.contact_number');
            $order_info = DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('isDelete',0)->find();
            $foods = Db::name('wxOrderGoods')->where('orderSN',$orderSN)->select();
            $contact = Db::name('contact')->where('number',$contact_number)->find();
        }else{
            return '订单不存在';
        }
        if ($request->isPost()) {
            $order = $order_info;
            $order['order'] = $order_info;
            $order['carts'] = json_decode(input('param.order'),true);
            $totalprice = input('param.totalprice');
            //如果开启服务费则设置服务费
            $service_fee = 0.00;
            if($contact['is_service_fee']) {
                $service_fee = price_format($contact['service_fee'] * $totalprice * 0.01);//服务费
            }
            //更新各项费用
            $order['total_foods_amount'] = price_format($totalprice+$order['foodsAmount']);
            $order['total_service_fee'] = price_format($order['service_fees']+$service_fee);
            $order['totalPrice'] = price_format($totalprice+$service_fee+$order['goodsAmount']);

            $res = addOrderFood($order);
            return $res;
        }else{
            if(count($order_info)>0){
                $menulist = Db::name('wxOrderGoods')
                              ->alias('og')
                              ->join('mos_goods g','og.goodsid = g.id','left')
                    //->field('g.categoryId')
                              ->where('og.orderSN',$orderSN)
                              ->select();
                foreach($menulist as $key=>$menuitem){
                    $menu_arr[] = "menu".$menuitem['categoryId'];
                }
                $order_come_arr = $menulist;
                foreach($order_come_arr as &$order_come_item){
                    $order_come_item['orderindex'] = 'food_'.$order_come_item['id'];
                    $order_come_item['_spec'] = getGoodsSpec($order_come_item['id'],$contact_number);
                }
                $contactNo = $order_info['contactNumber'];
                $memberNo = $order_info['contactMemberNumber'];
                /*普通餐厅*/
                $check_contact = check_contact($contactNo);
                $check_member = check_member($memberNo, $contactNo);
                if ($check_contact['code'] && $check_member['code']) {
                    $foodObject = new Foodlist();
                    $foodsAndMeals = $foodObject->getFoodAndMeal($contactNo, $type = 1);
                    $result = get_food_list($contactNo);

                    $order = [
                        'order' => json_encode($order_come_arr),
                        'menu' => json_encode(array_count_values($menu_arr)),
                        'carts' => $foods,
                        'totalPrice' => $order_info['goodsAmount'],
                        'userId' => $order_info['userId'],
                        'userNick' => isset($order_info['nickName']) ? $order_info['nickName'] : '',
                        'userType' => $order_info['userType'],
                        'contactName' => $contact['name'],
                        'printerId' => $contact['printerId'],
                        'contactNumber' => $contact['number'],
                        'contactLogoUrl' => $contact['logoUrl'],
                        'contactMemberName' => $check_contact,
                        'contactMemberNumber' => $check_member,
                        'latitude' => !empty($userlatlng['lat']) ? $userlatlng['lat'] : '',
                        'longitude' => !empty($userlatlng['lng']) ? $userlatlng['lng'] : '',
                        'orderInArea' => !empty($userlatlng['inarea']) ? $userlatlng['inarea'] : '',
                    ];
                    $this->assign('type', 1);
                    $this->assign('order_info', $order_info);
                    $this->assign('order', $order);
                    $this->assign('mealcategory', $result['mealcategory']);
                    $this->assign('mealinfo', $result['mealinfo']);
                    //由$mealWithFood 传递过来
                    $this->assign('meal', $result['meal']);
                    $this->assign('mealMenu', $foodsAndMeals['meal']);
                    //$categoryWithFood 传递过来
                    $this->assign('list', $result['category']);
                    $this->assign('food', $result['foodlist']);
                    $this->assign('contact', $check_contact['msg']);
                    $this->assign('member', $check_member['msg']);
                    return $this->fetch();
                } else {
                    die('獲取餐廳信息失敗!');
                }
            }else{
                $this->error('訂單不存在!',url('order/index'));
            }
        }


    }

    public function getordersuccess() {
        $orderSN = input('param.orderSN');
        $nick = session('mob_user.nick');
        $contact_number = session('mob_user.contact_number');
        DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('orderStatus',2)->where('isDelete',0)->update(['orderStatus'=>3,'printerStatus'=>1]);
        $return = ['code'=>1,'msg'=>'发送成功'];
        $return['tip'] = '接單成功';
        $this->success($return);
    }

    public function getOrder() {
        $orderSN = input('param.orderSN');
        $nick = session('mob_user.nick');
        $contact_number = session('mob_user.contact_number');
        $print = new \app\printer\controller\Index;
        $return = $print->printOrder($orderSN,$nick);
        if($return['code']&&$return['code'] !== 'false'){
            // 打印全部成功 将订单和所有菜品修改为打印成功
            DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('orderStatus',2)->where('isDelete',0)->update(['orderStatus'=>3,'printerStatus'=>1]);
            DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->update(['printerStatus'=>1]);
            $return['tip'] = '接單成功';
            $this->success($return);
        }else{
            // 云打印有失败的部分
            if(isset($return['main']['code'])&&$return['main']['code']&&$return['main']['code']!=='false'){
                // 主订单打印成功
                DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('orderStatus',2)->where('isDelete',0)->update(['printerStatus'=>1]);
            }else if(isset($return['main']['code'])){
                // 主订单打印失败
                DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('orderStatus',2)->where('isDelete',0)->update(['printerStatus'=>2,'printerDescribe'=>$return['main']['msg']]);
            }
            $errorFoodId = array();
            if(!empty($return['food'])){
                foreach ($return['food'] as $key => $val) {
                    if(!$val['code']||$val['code']=='false'){
                        $errorFoodId[] = $key;
                        DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('id',$key)->update(['printerStatus'=>2,'printerDescribe'=>$val['msg']]);
                    }
                    // 将非错误id中的订单菜品都改为打印成功
                    DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('id','not in',$errorFoodId)->update(['printerStatus'=>1]);
                }
            }
            $this->error('接單失敗');
        }
    }

    public function getError(){
        $param = input('param.');
        $orderSN = $param['orderSN'];
        $printerMsg = $param['printerMsg'];
        $contact_number = session('mob_user.contact_number');
        if(!empty($orderSN)&&!empty($printerMsg)){
            if(!empty($printerMsg['main']['code'])&&!empty($printerMsg['main']['msg'])){
                if($printerMsg['main']['code']&&$printerMsg['main']['code']!=='false'){
                    $res = DB::name('wxOrder')
                        ->where('orderSN',$orderSN)
                        ->where('contactNumber',$contact_number)
                        ->where('isDelete',0)
                        ->update(['printerStatus'=>1]);
                }else{
                    $res = DB::name('wxOrder')
                        ->where('orderSN',$orderSN)
                        ->where('contactNumber',$contact_number)
                        ->where('isDelete',0)
                        ->update(['printerStatus'=>2,'printerDescribe'=>$printerMsg['main']['msg']]);
                }
            }
            if(!empty($printerMsg['food'])){
                foreach ($printerMsg['main'] as $key => $val) {
                    if(!empty($val['code'])&&!empty($val['msg'])){
                        if($val['code']&&$val['code']!=='false'){
                            $res = DB::name('wxOrderGoods')
                                ->where('id',$key)
                                ->where('contactNumber',$contact_number)
                                ->update(['printerStatus'=>1]);
                        }else{
                            $res = DB::name('wxOrderGoods')
                                ->where('id',$key)
                                ->where('contactNumber',$contact_number)
                                ->update(['printerStatus'=>2,'printerDescribe'=>$val['msg']]);
                        }
                    }
                }
            }
            if($res){
                $this->success('保存信息成功');
            }
        }else{
            $this->error('無效請求');
        }
    }

    //更改桌号
    public function changeContactMember() {
        $orderSN = input('param.orderSN');
        $contactMemberNumber = input('param.contactMemberNumber');
        $contactMemberName = input('param.contactMemberName');
        $res = DB::name('wxOrder')->where('orderSN',$orderSN)->update(['contactMemberNumber'=>$contactMemberNumber,'contactMemberName'=>$contactMemberName]);
        if($res>0){
            $return['msg'] = '更改成功';
            $return['code'] = true;

        }else{
            $return['msg'] = '更改出錯';
            $return['code'] = false;
        }
        return $return;
    }

    //确认收款和埋单
    public function confirmPay() {
        $orderSN = input('param.orderSN');
        $payType = input('param.paytype');
        $box_count = input('param.box_count');
        $discount = input('param.discount');
        $return = [];
        $res = 1;
        $save = [];
        if(!empty($orderSN)&&!empty($payType)) {
            $paytype_info =DB::name('payMethod')->where('id',$payType)->find();
            $order_info = DB::name('wxOrder')->where('orderSN', $orderSN)->find();
            $save = ['payStatus' => 1, 'orderStatus' => 4, 'payType' => $payType, 'payName' => $paytype_info['name']];
            $orderAmount = $order_info['goodsAmount'];
            //如果有餐数：计算餐盒费
            if(!empty($box_count)){
                $save['box_frees']=$box_count*session('contact_info.box_fee');
                $orderAmount = $orderAmount+$save['box_frees'];
            }
            $save['orderAmount'] = $orderAmount;
            //如果有折扣：重新计算支付总价
            if(!empty($discount)){
                if($discount<=0||$discount>=10){
                    return ['code'=>0,'msg'=>'折扣輸入錯誤'];
                }
                $save['discount'] = $discount;
                $orderAmount = $orderAmount*$discount/10;
            }
            $save['goodsAmount'] = $orderAmount;
            $save['moneyPaid'] = $orderAmount;
            //事务开始
            Db::startTrans();
            try {
                if(stripos($orderSN, ',') !== false) {
                    //多个订单
                    $orderSNS = explode(',', $orderSN);
                    DB::name('wxOrder')->where('orderSN', 'in', $orderSNS)
                                       ->where('orderStatus', 'in', [2,3])
                                       ->update($save);
                } else {
                    DB::name('wxOrder')->where('orderSN', $orderSN)
                                       ->where('orderStatus', 'in', [2,3])
                                       ->update($save);
                }
                // 提交事务
                Db::commit();
            } catch(\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res) {
                $return['msg']  = lang('付款成功');
                $return['code'] = 1;
            } else {
                $return['msg']  = lang('付款失敗');
                $return['code'] = 0;
            }
        }
        return $return;
    }

    //取消订单
    public function orderCancel() {
        $orderSN = input('param.orderSN');
        $res = DB::name('wxOrder')->where('orderSN',$orderSN)->update(['orderStatus'=>0]);
        if($res>0){
            $return['msg'] = '已取消';
            $return['code'] = true;

        }else{
            $return['msg'] = '出錯了';
            $return['code'] = false;
        }
        return $return;
    }

    //记录回调到日志
    public function consolelog() {
        $log = input('param.log');
        log_output($log);
        return true;
    }

    //重新打印，打印统一用这个方法
    public function printAgain($order_sn=null) {
        $orderSN = input('param.orderSN');
        $orderSN = empty($orderSN)?$order_sn:$orderSN;//自动打印调用
        $errorPrint=input('param.errorPrint');//失败部分重打
        $addOrderPrint = input('param.addOrder');//加菜时打印
        $nick = session('mob_user.nick');
        $contact_number = session('mob_user.contact_number');
        $print = new \app\printer\controller\Index;
        //加菜时打印小票是否包含全部菜品，默认1全部
        if($addOrderPrint==1) {
            $return = $errorPrint?$print->againOrder($orderSN,$nick,1):$print->againOrder($orderSN,$nick,1);
        }else {
            $return = $errorPrint?$print->againOrder($orderSN,$nick,1,1):$print->againOrder($orderSN,$nick,1);
        }
        //if($return['code']){
        //    $return['tip'] = '打印成功';
        //    $this->success($return);
        //}else{
        //    $this->error('打印失败');
        //}
        if($return['code']){
            // 打印全部成功 将订单和所有菜品修改为打印成功
            DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('orderStatus',2)->where('isDelete',0)->update(['orderStatus'=>3,'printerStatus'=>1]);
            DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->update(['printerStatus'=>1]);
            $return['tip'] = $errorPrint?'':'接單成功 ';
            //$this->success($return);
        }else{
            if($return['msg']=='调起打印失败'){
                $return['tip'] = '订单异常：订单或菜品为空';
                return $return;
            }
            $return['tip'] = $errorPrint?'':'接單成功 ';
            $printname = [];
            // 云打印有失败的部分
            if(isset($return['main']['order']['code'])&&$return['main']['order']['code']){
                // 主订单打印成功
                DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('isDelete',0)->update(['orderStatus'=>3,'printerStatus'=>1]);
            }else if(isset($return['main']['order']['code'])){
                // 主订单打印失败
                DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('isDelete',0)->update(['orderStatus'=>3,'printerStatus'=>2,'printerDescribe'=>$return['main']['order']['msg']]);
                $printname[] = $return['main']['order']['ptintname'];
                //$return['tip'] .= '订单打印失败 ';
            }
            $errorFoodId = array();
            if(!empty($return['main']['food'])){
                foreach ($return['main']['food'] as $key => $val) {
                    if(!$val['code']){
                        $errorFoodId[] = $key;
                        DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('id',$key)->update(['printerStatus'=>2,'printerDescribe'=>$val['msg']]);
                        $printname[] = $val['ptintname'];
                        //$return['tip'] .= $val['goodsname'].'打印失败 ';
                    }else{
                        DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('id',$key)->update(['printerStatus'=>1]);
                    }
                }
            }
            //没有打印数据
            if(empty($return['main']['order'])&&empty($return['main']['food'])){
                $return['tip'] .= $return['msg'];
            }
            $return['tip'] .= implode(',',array_unique($printname)).'打印失败';
        }
        //
        DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->update(['addStatus'=>0]);
        DB::name('wxOrderGoods')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->update(['addStatus'=>0]);
        return $return;
    }

    public function newOrders(){
        $id = input('maxorder');
        if(session('mob_user.is_contact')!=0){
            $contact_number = session('mob_user.contact_number');
        }else{
            $contact_number = '';
        }
        $newNumber = DB::name('wxOrder')
            ->where('id','>',$id)
            ->where('contactNumber',$contact_number)
            ->where('addStatus=1 or orderStatus=2')
            ->where('isDelete',0)
            ->count();
        $maxid = DB::name('wxOrder')
            ->where('id','>',$id)
            ->where('contactNumber',$contact_number)
            ->where('orderStatus',2)
            ->where('isDelete',0)
            ->find();
        if($newNumber>0){
            return ['code'=>1,'msg'=>'您有新的訂單（'.$newNumber.'）','data'=>$maxid['id']];
        }else{
            $this->error('暫時沒有新訂單');
        }
    }

    public function nextOrders(){
        $post = input('param.');
        $nick = session('mob_user.nick');
        $table = $post['table'];
        // 获取餐厅编号
        if(session('mob_user.is_contact')!=0){
            $contact_number = session('mob_user.contact_number');
        }else{
            $contact_number = '';
        }
        // 获取当前显示的最小id
        if(isset($post['minOrder'])){
            $where['o.id'] = ['<',$post['minOrder']];
        }
        // 获取显示订单类型
        if(isset($post['action'])){
            if($post['action']==3){
                $where['o.orderStatus'] = 3;
            }elseif($post['action']==4){
                $where['o.orderStatus'] = 4;
            }elseif($post['action']==5){
                $where['o.orderStatus'] = 5;
            }elseif($post['action']==0){
                $where['o.orderStatus'] = 0;
            }else{
                $where['o.orderStatus'] = 2;
            }
        }else{
            $where['o.orderStatus'] = 2;
        }
        //餐枱订单
        if(!empty($table)){
            $where['o.contactMemberNumber'] = $table;
        }
        if($post['action']==2) {
            unset($where['o.orderStatus']);
            $order = DB::name('wxOrder')
                       ->alias('o')
                       ->join('mos_contact c', 'o.contactNumber = c.number', 'left')
                       ->join('mos_printer p', 'c.printerId = p.id', 'left')
                       ->join('mos_printer_brand b', 'p.brandId = b.id', 'left')
                       ->field('o.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                       ->where('o.contactNumber', $contact_number)
                       ->where($where)
                       ->where('o.addStatus=1 or o.orderStatus=2')
                       ->where('o.isDelete', 0)
                       ->order('o.id desc')
                       ->limit(5)
                       ->select();
        }else{
            $order = DB::name('wxOrder')
                       ->alias('o')
                       ->join('mos_contact c', 'o.contactNumber = c.number', 'left')
                       ->join('mos_printer p', 'c.printerId = p.id', 'left')
                       ->join('mos_printer_brand b', 'p.brandId = b.id', 'left')
                       ->field('o.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
                       ->where('o.contactNumber', $contact_number)
                       ->where($where)
                       ->where('o.isDelete', 0)
                       ->order('o.id desc')
                       ->limit(5)
                       ->select();
        }
        if(!empty($order)){
            // 訂單編號集合
            $orderSn = array();
            // 新訂單集合
            $newOrder = array();
            // 重新排序后的訂單
            $orderlist = array();
            // 重新排序后的訂單菜品
            $orderFoodslist = array();
            // 把訂單號寫入集合
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
            $print = new \app\printer\controller\Index;
            $printArray = $print->getNotYumPrint($order,$orderFoods,$nick);
            $orderinfo = array();
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
                //当前餐枱订单时候不显示取消和完成订单
                if(!empty($table)&&($val['orderStatus']==4||$val['orderStatus']==0)){
                    continue;
                }
                $orderlist[$key] = $val;
                $orderlist[$key]['createTime'] = date('Y-m-d H:i',$val['createTime']);
                $orderlist[$key]['orderTatol'] = $val['goodsAmount']-$val['moneyPaid'];
                //打印机为设置显示名字为‘未设置’
                $order_deviceNick = empty($val['deviceNick'])?lang('未设置'):$val['deviceNick'];
                $orderlist[$key]['ptintInfo'][] = $val['printerStatus']==2?$deviceNick:'';
                if(isset($orderFoodslist[$val['orderSN']])){
                    if($post['action']==2&&$val['addStatus']==1){
                        foreach($orderFoodslist[$val['orderSN']] as $kk=>$items){
                            if($items['addStatus']!=1) {
                                unset($orderFoodslist[$val['orderSN']][$kk]);
                            }
                        }
                    }
                    $orderlist[$key]['_goods'] = $orderFoodslist[$val['orderSN']];
                }
                //遍历检查订单菜品是否有打印失败
                foreach($orderFoodslist[$val['orderSN']] as $orderFoodList){
                    if($orderFoodList['printerStatus']==2){
                        //打印机为设置显示名字为‘未设置’
                        $food_deviceNick = empty($orderFoodList['deviceNick'])?lang('未設置打印機'):$orderFoodList['deviceNick'];
                        $orderlist[$key]['ptintInfo'][] = $food_deviceNick;
                    }
                }
                $orderlist[$key]['ptintInfos'] = implode(',',array_filter(array_unique($orderlist[$key]['ptintInfo'])));
            }
            $return['order'] = $orderlist;
            $return['print'] = json_encode($printArray);
            // $this->assign($return);
            // return $this->fetch('test/test');
            $this->success($return);
        }else{
            $this->error('沒有更多訂單');
        }
    }

    public function nextOrdersAmount(){
        $post = input('param.');
        $nick = session('mob_user.nick');
        // 获取餐厅编号
        if(session('mob_user.is_contact')!=0){
            $contact_number = session('mob_user.contact_number');
        }else{
            $contact_number = '';
        }
        // 获取当前显示的最小id
        if(isset($post['minOrder'])){
            $where['o.id'] = ['<',$post['minOrder']];
        }
        if(isset($post['action'])){
            $where['p.online'] = ['=',$post['action']];
        }
        $startDate = session::get('startDate');
        $endDate   = session::get('endDate');
        if(isset($post['ptype'])){
            $payinfo = Db::name('payMethod')->where('id',$post['ptype'])->find();
            $payids = Db::name('payMethod')->field('id')->where('name',$payinfo['name'])->select();
            $where['p.id'] = ['in',array_column($payids,'id')];
        }
        $order = DB::name('wxOrder')
                   ->alias('o')
                   ->join('mos_pay_method p','p.id = o.payType','left')
                   ->field('o.*')
                   ->where('o.contactNumber',$contact_number)
                   ->where($where)
                   ->where('o.orderStatus',4)
                   ->where('o.payStatus',1)
                   ->where('o.isDelete',0)
                   ->where('o.createTime', 'between', [$startDate, $endDate])
                   ->group('orderSN')
                   ->order('o.id desc')
                   ->limit(5)
                   ->select();
        foreach($order as &$val){
            $val['createTime'] = date('Y-m-d H:i',$val['createTime']);
        }
        if(!empty($order)){
            $return['order'] = $order;
            $this->success($return);
        }else{
            $this->error('沒有更多訂單');
        }
    }

    // 自動接單 獲取全部訂單編號及打印數據
    public function getAllOrder(){
        $nick = session('mob_user.nick');
        $contact_number = session('mob_user.contact_number');
        $table = input('param.table');
        if(!empty($table)){
            $where['o.contactMemberNumber'] =  $table;
        }else{
            $where = '1=1';
        }
        $order = DB::name('wxOrder')
            ->alias('o')
            ->join('mos_printer p','o.printerId = p.id','left')
            ->join('mos_printer_brand b','p.brandId = b.id','left')
            ->field('o.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            ->where('o.contactNumber',$contact_number)
            ->where('o.orderStatus=2 or o.addStatus=1')
            ->where($where)
            ->where('o.isDelete',0)
            ->order('o.id desc')
            ->select();
        if(!empty($order)){
            // 訂單編號集合
            $orderSn = array();
            // 把訂單號寫入集合
            foreach ($order as $key => $val) {
                $orderSn[] = $val['orderSN'];
                //$resarr  = $this->printAgain($val['orderSN']);
            }
            $return['order'] = $orderSn;
            $this->success($return);

            // 查詢訂單菜品
            //$orderFoods = DB::name('wxOrderGoods')
            //    ->alias('g')
            //    ->join('mos_printer p','g.printerId = p.id','left')
            //    ->join('mos_printer_brand b','p.brandId = b.id','left')
            //    ->field('g.*,p.deviceNick,p.deviceNumber,p.shopNumber,p.apiKey,b.fileName,b.type')
            //    ->where('g.contactNumber',$contact_number)
            //    ->where('g.orderSN','in',$orderSn)
            //    ->order('g.id asc')
            //    ->select();
            //$print = new \app\printer\controller\Index;
            //$printArray = $print->getNotYumPrint($order,$orderFoods,$nick);
            //$return['order'] = $orderSn;
            //$return['msg'] = '自動接取訂單（'.count($orderSn).'）';
            //$return['print'] = json_encode($printArray);
            //$this->success($return);
        }else{
            $this->error('沒有未接訂單');
        }
    }

    public function orderAmount(){
        $contact_number = session('mob_user.contact_number');
        $startDate = session::get('startDate');
        $endDate   = session::get('endDate');
        $data = Db::name('WxOrder')
            ->alias('o')
            ->join('mos_pay_method m ','o.payType= m.id','LEFT')
            ->field('payType,m.name,m.icon,m.filename,count(o.id) as order_count,sum(o.goodsAmount ) as order_amount')
            ->where('o.contactNumber',$contact_number)
            ->where('o.isDelete',0)
            ->where('o.payType','>',0)
            ->where('o.payStatus',1)
            ->where('o.orderStatus',4)
            ->where('o.createTime', 'between', [$startDate, $endDate])
            ->group('m.name')
            ->select();
        //log_output(Db::getLastSQL());
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function orderPaytype(){
        //$contact_number = session('mob_user.contact_number');
        $ptype = input('param.type');
        $data = Db::name('PayMethod')
                  ->where('id',$ptype)
                  ->find();
        $this->assign('data',$data);
        $this->assign('ptype',$ptype);
        return $this->fetch();
    }

    //埋单
    public function payBill(){
        $contact_number = session('mob_user.contact_number');
        $request = Request::instance();
        if($request->isPost()) {
            $orderSN = input('param.orderSN');
            $orderdata = [];
            if(!empty($orderSN)) {
                $foodObject = new Orderlist();
                $orderdata  = $foodObject->getOrdersDetail($contact_number, $orderSN);
            }
            return $orderdata;
        }else {
            $contact_paytype = DB::name('Contact')->field('offpaytype')->where('number',$contact_number)->find();
            $paytype_ids = empty(json_decode($contact_paytype['offpaytype'],true))?[9999]:json_decode($contact_paytype['offpaytype'],true);
            $payment_data = Db::name('PayMethod')
                      ->where('online',0)
                      ->where('id','not in',$paytype_ids)
                      ->select();
            $this->assign('payment_data',$payment_data);
            return $this->fetch();
        }
    }

    //订单搜索
    public function search() {
        $contact_number = session('mob_user.contact_number');
        $request = Request::instance();
        if($request->isPost()) {
            $orderSN = input('param.orderSN');
            $orderdata = [];
            if(!empty($orderSN)) {
                $foodObject = new Orderlist();
                $orderdata  = $foodObject->getOrdersDetail($contact_number, $orderSN);
            }
            return $orderdata;
        }else {
            return $this->fetch();
        }
    }

    //数据统计
    public function orderStatis(){
        $contact_number = session('mob_user.contact_number');
        $contact_name = session('mob_user.name');
        //按照设置的清单时间 比如设置的是2点 今日订单应该是今日2点 到明天2点的订单额
        $contact_info = Db::name('contact')->where('number',$contact_number)->find();
        $clean_time = $contact_info['cleanStartTime'];
        $hour = substr ($clean_time, 0, 2);
        $minute = substr ($clean_time, -5, 2);
        $second   = substr ($clean_time, -2);
        $request = Request::instance();
        if($request->isPost()) {
            $post    = input('param.');
            $request = $post['request'];
            if($request=='print') {
                $startDate = session::get('startDate');
                $endDate   = session::get('endDate');
            }else{
                if(empty($post['startDate']) && empty($post['endState']) && !empty($request)) {
                    $data_arr  = getStartAndEndData($request,$clean_time);
                    $startDate = strtotime($data_arr['startDate']);
                    $endDate   = strtotime($data_arr['endState']);
                } elseif(!empty($post['startDate']) && !empty($post['endState'])) {
                    $startDate = strtotime($post['startDate'].' '.$hour.':'.$minute.':'.$second);
                    $endDate   = strtotime($post['endState'].' '.$hour.':'.$minute.':'.$second)+86399;
                } else {
                    //默认当日数据
                    $startDate = mktime($hour,$minute,$second,date('m'),date('d'),date('Y'));
                    $endDate   = mktime($hour,$minute,$second,date('m'),date('d')+1,date('Y'))-1;
                }
                session::set('startDate',$startDate);
                session::set('endDate',$endDate);
            }

            //总销售额统计
            $allData = Db::name('WxOrder')
                         ->field('count(id) as order_count,COALESCE(sum(goodsAmount), 0.00) as order_amount')
                         ->where('contactNumber', $contact_number)
                         ->where('isDelete', 0)
                         ->where('orderStatus', 4)
                         ->where('payStatus', 1)
                         ->where('payType', '>', 0)
                         ->where('createTime', 'between', [$startDate, $endDate])
                         ->find();
            //线下支付统计
            $moneyData = Db::name('WxOrder')
                           ->field('count(id) as order_count,COALESCE(sum(goodsAmount), 0.00) as order_amount')
                           ->where('contactNumber', $contact_number)
                           ->where('isDelete', 0)
                           ->where('orderStatus', 4)
                           ->where('payStatus', 1)
                           ->where('payType', '>', 100)
                           ->where('createTime', 'between', [$startDate, $endDate])
                           ->find();
            //线上支付统计
            $onlineData = Db::name('WxOrder')
                            ->field('count(id) as order_count,COALESCE(sum(goodsAmount), 0.00) as order_amount')
                            ->where('contactNumber', $contact_number)
                            ->where('isDelete', 0)
                            ->where('orderStatus', 4)
                            ->where('payStatus', 1)
                            ->where('payType', 'between', [1,100])
                            ->where('createTime', 'between', [$startDate, $endDate])
                            ->find();
            //未支付统计
            $nopayData = Db::name('WxOrder')
                           ->field('count(id) as order_count,COALESCE(sum(goodsAmount), 0.00) as order_amount')
                           ->where('contactNumber', $contact_number)
                           ->where('isDelete', 0)
                           ->where('orderStatus', '>', 0)
                           ->where('payStatus', 0)
                           ->where('createTime', 'between', [$startDate, $endDate])
                           ->find();
            //订单类型分组统计:堂食，外带，外卖
            $grou_pData = Db::name('WxOrder')
                            ->alias('o')
                            ->join('mos_pay_method m ', 'o.payType= m.id', 'LEFT')
                            ->field('o.orderType,payType,m.name,count(o.id) as order_count,COALESCE(sum(o.goodsAmount), 0.00) as order_amount')
                            ->where('o.contactNumber', $contact_number)
                            ->where('o.isDelete', 0)
                            ->where('o.orderStatus', 4)
                            ->where('o.payStatus', 1)
                            ->where('o.payType', '>', 0)
                            ->where('o.createTime', 'between', [$startDate, $endDate])
                            ->group('o.orderType')
                            ->select();
            $groupData  = [];
            foreach($grou_pData as $gdata) {
                $groupData[$gdata['orderType']] = $gdata;
            }
            for($i = 1;$i <= 3;$i++) {
                if(empty($groupData[$i])) {
                    $groupData[$i] = [
                        'order_count'  => 0,
                        'order_amount' => '0.00',
                    ];
                }
            }
            $data_arr = [
                'allData'    => $allData,
                'moneyData'  => $moneyData,
                'onlineData' => $onlineData,
                'nopayData'  => $nopayData,
                'groupData'  => $groupData
            ];
            if($request == 'print') {
                $contact = Db::name('contact')
                             ->alias('c')
                             ->join('mos_printer p', 'c.printerId = p.id', 'left')
                             ->where('c.number', $contact_number)
                             ->find();
                $orderdata = Db::name('WxOrder')
                          ->alias('o')
                          ->join('mos_pay_method m ','o.payType= m.id','LEFT')
                          ->field('payType,m.name,m.icon,count(o.id) as order_count,sum(o.goodsAmount ) as order_amount')
                          ->where('o.contactNumber',$contact_number)
                          ->where('o.isDelete',0)
                          ->where('o.orderStatus', 4)
                          ->where('o.payStatus', 1)
                          ->where('o.payType', '>', 0)
                          ->where('o.createTime', 'between', [$startDate, $endDate])
                          ->group('o.payType')
                          ->select();
                $fooddata    = Db::name('goods')
                             ->alias('g')
                             ->join('mos_wx_order_goods og ', 'og.goodsId= g.id', 'LEFT')
                             ->join('mos_wx_order o', 'o.orderSN= og.orderSN', 'LEFT')
                             ->field('og.goodsId,g.categoryName,sum(og.num) as food_count,COALESCE(sum(og.num*og.goodsPrice), 0.00) as food_amount')
                             ->where('og.contactNumber', $contact_number)
                             ->where('o.createTime', 'between', [$startDate, $endDate])
                             ->where('o.orderStatus', 4)
                             ->where('o.payStatus', 1)
                             ->where('o.payType', '>', 0)
                             ->where('og.goodsType', '<>', 3)
                             ->order('food_count desc')
                             ->group('g.categoryId')
                             ->select();
                $data_arr['orderdata']  = $orderdata;
                $data_arr['fooddata']  = $fooddata;
                $data_arr['contact']  = $contact;
                $data_arr['datetime'] = ['startDate' => $startDate, 'endDate' => $endDate];
                if(empty($orderdata)){
                    $return = ['code'=>0,'msg'=>'没有数据'];
                }else {
                    $print  = new \app\printer\controller\Index;
                    $return = $print->printData($data_arr);
                }
                return json($return);
            }
            else
                {
                return json($data_arr);
            }
        }else{
            return $this->fetch();
        }
    }

    //数据图表分析C
    public function orderChart(){
        $request = Request::instance();
        if($request->isPost()) {
            $post = input('param.');
            $starttime = $post['starttime'];
            $endtime = $post['endtime'];
            if(!empty($starttime)){
                session::set('table_starttime',$starttime);
            }
            if(!empty($endtime)){
                session::set('table_endtime',$endtime);
            }
            return ['code'=>1,'msg'=>'success'];
        }else{
            $starttime = !empty(session::get('table_starttime'))?session::get('table_starttime'):date('Y-m-d',time()-86400);
            $endtime = !empty(session::get('table_endtime'))?session::get('table_endtime'):date('Y-m-d',time());
            $contact_number = session('mob_user.contact_number');
            //按照设置的营业时间统计分组 比如设置的是2点 每一组应该是当日2点 到第二天2点的订单额
            $contact_info = Db::name('contact')->where('number',$contact_number)->find();
            $clean_time = $contact_info['cleanStartTime'];
            $hour = substr ($clean_time, 0, 2);
            $minute = substr ($clean_time, -5, 2);
            $second   = substr ($clean_time, -2);
            $orders = DB::name('wxOrder')
                        ->field("createTime,SUM(DISTINCT goodsAmount) orderpaid,FROM_UNIXTIME(createTime,'%Y-%m-%d $hour:$minute:$second') AS hours")
                        ->where('contactNumber',$contact_number)
                        ->where('isDelete',0)
                        ->where('orderStatus', 4)
                        ->where('payStatus', 1)
                        ->where('payType','>', 1)
                        ->group('hours')
                        ->select();
            $order_data = [];
            foreach($orders as $order)
            {
                $order_data[date('Y-m-d',$order['createTime'])]=[date('Y-m-d',$order['createTime']),intval($order['orderpaid'])];
            }
            $hours = getDateFromRange(date('Y-m-d',reset($orders)['createTime']),date('Y-m-d',time()));
            $data=[];
            foreach($hours as $hour)
            {
                if(!empty($order_data[$hour])){
                    $data[] = $order_data[$hour];
                }else{
                    $data[] = [$hour,0];
                }
            }
            if(strtotime($starttime)<reset($orders)['createTime']||strtotime($starttime)>time()){
                $startdata = date('Y-m-d',reset($orders)['createTime']);
            }else{
                $startdata = date('Y-m-d',strtotime($starttime));
            }
            if(strtotime($endtime)<reset($orders)['createTime']||strtotime($endtime)>time()){
                $enddata = date('Y-m-d',time()+86400);
            }else{
                $enddata = date('Y-m-d',strtotime($endtime)+86400);
            }
            $this->assign('data',json_encode($data));
            $this->assign('starttime',$starttime);
            $this->assign('endtime',$endtime);
            $this->assign('startdata',$startdata);
            $this->assign('enddata',$enddata);
            return $this->fetch();
        }
    }
}