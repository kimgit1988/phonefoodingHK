<?php

namespace app\wxweb\controller;

use app\service\Foodlist;
use think\Cookie;
use think\Request;
use think\Db;
use think\Session;
use think\Validate;
use wechat\jssdk;

/**
 * 网站首页控制器
 * @author  kiyang
 */
class Index extends Base
{

    // 登录页面控制器
    public function index()
    {
        $param = input('param.');

        /*从其它地方获取用户信息，微信支付宝等*/
        $contact_info = array();
        $login = Session::has('web_user');
        if ($login) {
            if (!empty($param['contactNo']) && !empty($param['contactMemberNo'])) {
                $contact_info['code'] = 1;
            } else if (!empty($param['courtNumber'])) {
                $contact_info['code'] = 2;
            } else {
                $contact_info['code'] = 0;
            }
        } else {
            if (!empty($param['contactNo']) && !empty($param['contactMemberNo'])) {
                $address = url('index/index', ['contactNo' => $param['contactNo'], 'contactMemberNo' => $param['contactMemberNo']]);
                $reget = 'http://' . $_SERVER['HTTP_HOST'] . $address;
                $contact_info['code'] = 1;
            } else if (!empty($param['courtNumber'])) {
                $contact_info['code'] = 2;
                $address = url('index/index', ['courtNumber' => $param['courtNumber']]);
                $reget = 'http://' . $_SERVER['HTTP_HOST'] . $address;
            } else {
                $address = url('index/index');
                $reget = 'http://' . $_SERVER['HTTP_HOST'] . $address;
                $contact_info['code'] = 0;
            }
            $user_type = get_user_type();
            $user_info = user_login($user_type, $reget);
            // 获取成功
            if (!empty($user_info['openid'])) {
                session('web_user', $user_info);
            } else {
                // 非指定方式登录报错提示,上线前打开
                // $this->redirect('error/login');
            }
        }
        // 普通餐廳
        if ($contact_info['code'] == 1) {
            $user = Session::get('web_user');
            $contact_info = check_contact_and_member($param['contactNo'], $param['contactMemberNo'], $user['user_type']);
            $contacttype = $param['contacttype'];
            //判断是否是商家给用户点餐
            if($contacttype==2) Session::set('contacttype', $contacttype);
            if ($contact_info['code'] == 1) {
                // 查询是否有可以获取卡券
                $session_contact = config('session_contact');
                // 更换餐厅 清空session
                $is_contact = Session::has('contact');
                if ($is_contact) {
                    $old_contact = Session::get('contact');
                    if ($old_contact != $param['contactNo']) {
                        Session::delete('order');
                    }
                }
                Session::set('contact_info', [
                    'is_cover_charge'=>$contact_info['contact']['is_cover_charge'],
                    'is_service_fee'=>$contact_info['contact']['is_service_fee'],
                    'box_fee'=>$contact_info['contact']['box_fee'],
                ]);
                Session::set('contact', $param['contactNo']);
                Session::set('member', $param['contactMemberNo']);
                Session::set('type', 1);
                $cardList = get_received_cards($user['openid'],'distributeType = 4 AND (useType = 1 OR contactNumber = "'.$param['contactNo'].'")');
                if (!empty($cardList)) {
                    foreach ($cardList as $key => $val) {
                        $res = getCardRecord($val['id'],$user['openid']);
                        if($res['code']==1){
                            $getCardList[] = $val;
                        }
                    }
                }
            }
            // 美食廣場
        } else if ($contact_info['code'] == 2) {
            $user = Session::get('web_user');
            $court = check_court($param['courtNumber'], 'number', $user['user_type']);
            if ($court['code']) {
                $contactList = get_court_contact($court['msg']['id'], $user['user_type']);
                $contact_info['court'] = $court['msg'];
                if ($contactList['code']) {
                    Session::set('court', $param['courtNumber']);
                    Session::set('type', 2);
                    Session::delete('member');
                    $contact_info['contactList'] = $contactList['msg'];
                    foreach ($contactList['msg'] as $key => $val) {
                        $contact_number_list[] = "'".$val['number']."'";
                    }
                    $contact_number_list = implode(',', $contact_number_list);
                    $where = 'distributeType = 4 AND (useType = 1 OR contactNumber IN ('.$contact_number_list.'))';
                    $cardList = get_received_cards($user['openid'],$where);
                    if (!empty($cardList)) {
                        foreach ($cardList as $key => $val) {
                            $res = getCardRecord($val['id'],$user['openid']);
                            if($res['code']==1){
                                $getCardList[] = $val;
                            }
                        }
                    }
                } else {
                    $contact_info['tip'] = '該美食廣場該平台下沒有餐廳';
                    $contact_info['code'] = '-1';
                }
            } else {
                $contact_info['tip'] = '二維碼無效';
                $contact_info['code'] = '-1';
            }
        }
        if(!empty($contact_info['memberNo'])&&$contact_info['code'] = 1&&!empty(Session::get('web_user')['openid'])){
            $this->redirect('index/foodlist');
        }
        // 获取广告
        $ad = get_ad(1);
        $this->assign('ad', $ad);
        $this->assign('card_list',$getCardList);
        $this->assign('contact_info', $contact_info);
        return $this->fetch('indexbak');
    }

    public function selectcontact()
    {
        $param = input('param.');
        if ($param['contactNo']) {
            // 更换餐厅 清空session
            $is_contact = Session::has('contact');
            if ($is_contact) {
                $old_contact = Session::get('contact');
                if ($old_contact != $param['contactNo']) {
                    Session::delete('order');
                }
            }
            Session::set('contact', $param['contactNo']);
            $this->Redirect('index/foodList');
        }

    }

    public function foodList()
    {
        $request = Request::instance();
        $type = Session::get('type');
        if ($type != 2) {
            $this->check_member();
        }
        $this->check_login();
        if ($request->isPost()) {
            $contactNo = input('param.contact');
            $user = Session::get('web_user');
            $check_contact = check_contact($contactNo, 'number', $user['user_type']);
            $contact = $check_contact['msg'];
            if ($type == 2) {
                $courtNumber = Session::get('court');
                $court = check_court($courtNumber);
                $check_member = ['code' => 1];
                $member = ['name' => '', 'number' => ''];
            } else {
                $memberNo = input('param.member');
                $check_member = check_member($memberNo, $contactNo);
                $member = $check_member['msg'];
            }

            if ($check_contact['code'] && $check_member['code']) {
                $menu = input('param.menu');
                $order_come = input('param.order');
                $order = json_decode($order_come, true);
                //$data = ['order'=>$order,'contactNo'=>'testmer002','contactMemberNo'=>'2_2','openId'=>'test','nickName'=>'test','userType'=>1];
                //$food = array();
                $totalPrice = 0;
                //$good_total = 0;
                // 新訂單生成方法 v3.0 2018-11-21 17:51:31
                $food = order_get_food($order);
                $food_list = check_foods($food, $contactNo);
                $meal_list = check_meal($food, $contactNo);
                if (is_array($food_list) && is_array($meal_list)) {
                    // 合并套餐非套餐
                    $food_list = array_merge($food_list, $meal_list);

                } else if (is_array($meal_list)) {
                    $food_list = $meal_list;
                }
                foreach ($food_list as $k => $v) {
                    $good_total = $v['counter'] * $v['salePrice'];
                    $totalPrice += $good_total;
                }
                $userlatlng = Session::get('userlatlng');
                //如果开启茶位费则设置茶位费
                $personCount = 1;
                $tea_fee = $service_fee = 0.00;
                $contact_info = Db::name('contact')->where('number', $contactNo)->find();
                if($contact_info['is_cover_charge']){
                    $personCount = Session::get('personCount');
                    $data_time = intval(strtotime(date("Y-m-d H:i:s")))-86400;
                    //查询是否为加单
                    $hasorder = Db::name('wx_order')
                                  ->where('contactNumber',$contactNo)
                                  ->where('userId',$user['openid'])
                                  ->where('createTime','>=',$data_time)
                                  ->where('orderType',1)
                                  ->where('orderStatus','in',[2,3])
                                  ->order('id desc')
                                  ->find();
                    //加单的已经加过茶位费，则该加单茶位费不再收取
                    $tea_fee = empty($hasorder)?$personCount * $contact_info['fee']:0.00;//茶位费
                }
                //如果开启服务费则设置服务费
                if($contact_info['is_service_fee']) {
                    $service_fee = $contact_info['service_fee'] * $totalPrice * 0.01;//服务费
                }
                //保存费用
                $foodsAmount = $totalPrice;
                $totalPrice = $totalPrice+$service_fee+$tea_fee;

                $data = [
                    'order' => $order_come,
                    'menu' => $menu,
                    'carts' => $food_list,
                    'service_fee' => $service_fee,
                    'tea_fee' => $tea_fee,
                    'foodsAmount' => $foodsAmount,
                    'personCount' => $personCount,
                    'totalPrice' => $totalPrice,
                    'userId' => $user['openid'],
                    'userNick' => isset($user['nickname']) ? $user['nickname'] : '',
                    'userType' => $user['user_type'],
                    'contactName' => $contact['name'],
                    'printerId' => $contact['printerId'],
                    'contactNumber' => $contact['number'],
                    'contactLogoUrl' => $contact['logoUrl'],
                    'contactMemberName' => $member['name'],
                    'contactMemberNumber' => $member['number'],
                    'latitude' => !empty($userlatlng['lat']) ? $userlatlng['lat'] : '',
                    'longitude' => !empty($userlatlng['lng']) ? $userlatlng['lng'] : '',
                    'orderInArea' => !empty($userlatlng['inarea']) ? $userlatlng['inarea'] : '',
                ];

                if ($type == 2 && $court['code']) {
                    $data['courtId'] = $court['msg']['id'];
                } else {
                    $data['courtId'] = 0;
                }
                Session::set('order', $data);
                $this->success('訂單添加成功!', url('index/payorder'));
            } else {
                $this->error('餐廳信息錯誤!');
            }
        } else {
            $type = Session::get('type');
            if ($type == 1) {
                /*普通餐厅*/
                $this->check_member();
                $contactNo = Session::get('contact');
                $memberNo = Session::get('member');
                $check_contact = check_contact($contactNo);
                $check_member = check_member($memberNo, $contactNo);
                $user = Session::get('web_user');
                $user_openid = $user['openid'];
                $add_data = is_add_order($contactNo,$memberNo,$user_openid);
                $addstatus = $add_data['code']?1:0;
                if ($check_contact['code'] && $check_member['code']) {
                    $foodObject = new Foodlist();
                    $foodsAndMeals = $foodObject->getFoodAndMeal($contactNo, $type = 1);
                    $result = get_food_list($contactNo);
                    $default_images = get_contact_images($contactNo);
                    Session::has('order') && $this->assign('order', Session::get('order'));
                    $this->assign('type', 1);
                    $this->assign('addstatus', $addstatus);
                    $this->assign('default_images', $default_images);
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
                    return $this->fetch('list');
                } else {
                    die('獲取餐廳信息失敗!');
                }
            } else if ($type == 2) {
                /*美食广场*/
                $contactNo = Session::get('contact');
                $courtNo = Session::get('court');
                $check_court = check_court($courtNo);
                $check_contact = check_contact($contactNo);
                if ($check_contact['code']) {
                    $result = get_food_list($contactNo);
                    Session::has('order') && $this->assign('order', Session::get('order'));
                    $this->assign('type', 2);
                    $this->assign('mealcategory', $result['mealcategory']);
                    $this->assign('mealinfo', $result['mealinfo']);
                    $this->assign('meal', $result['meal']);
                    $this->assign('list', $result['category']);
                    $this->assign('food', $result['foodlist']);
                    $this->assign('court', $check_court['msg']);
                    $this->assign('contact', $check_contact['msg']);
                    return $this->fetch('list');
                } else {
                    die('獲取餐廳信息失敗!');
                }
            }

        }
    }

    public function messageList()
    {
        $request = Request::instance();
        $this->check_login();
        $contactNo = Session::get('contact');
        $memberNo = Session::get('member');
        $user = Session::get('web_user');
        $list = DB::name('message')
            ->alias('m')
            ->field('m.*,count(r.reply_mid) as unread,c.name')
            ->join('mos_message_reply r', 'm.id = r.reply_mid and r.reply_status <> 1 and r.reply_type = 2', 'left')
            ->join('mos_contact c', 'm.message_contact_number = c.number', 'left')
            ->where('m.message_uid', $user['openid'])
            ->where('m.isDelete', 0)
            ->order('m.id desc')
            ->group('m.id')
            ->select();
        $this->assign('message', $list);
        $this->assign('prevurl', url('index/index', ['contactNo' => $contactNo, 'contactMemberNo' => $memberNo]));
        return $this->fetch('message');
    }

    public function replyList()
    {
        $request = Request::instance();
        $this->check_login();
        if ($request->isPost()) {
        } else {
            $id = input('messageNo');
            $user = Session::get('web_user');
            $message = DB::name('message')
                ->alias('m')
                ->field('m.*,c.name')
                ->join('mos_contact c', 'm.message_contact_number = c.number', 'left')
                ->where(['m.isDelete' => 0])
                ->where('m.id', $id)
                ->where('m.isDelete', 0)
                ->order('m.id desc')
                ->find();
            if (!empty($message)) {
                $update = DB::name('messageReply')
                    ->where('reply_mid', $id)
                    ->where('reply_status', 'neq', 1)
                    ->where('reply_type', 2)
                    ->where('isDelete', 0)
                    ->order('id asc')
                    ->update(['reply_status' => 1]);
                $list = DB::name('messageReply')
                    ->where('reply_mid', $id)
                    ->where('isDelete', 0)
                    ->order('id asc')
                    ->select();
                $this->assign('memberNo', $id);
                $this->assign('list', $list);
                $this->assign('message', $message);
                $this->assign('prevurl', url('index/messageList'));
                return $this->fetch('reply');
            } else {
                $this->redirect('index/index');
            }
        }
    }

    public function sendMessage()
    {
        $request = Request::instance();
        $this->check_login();
        if ($request->isPost()) {
            $param = input('param.');
            $user = Session::get('web_user');
            $contactNo = Session::get('contact');
            $message = [
                'message_uid' => $user['openid'],
                'message_title' => !empty($param['title']) ? $param['title'] : '',
                'message_contact_number' => !empty($contactNo) ? $contactNo : '',
                'message_name' => !empty($param['name']) ? $param['name'] : '',
                'message_sex' => !empty($param['sexId']) ? $param['sexId'] : '',
                'message_phone' => !empty($param['mobile']) ? $param['mobile'] : '',
                'message_email' => !empty($param['mail']) ? $param['mail'] : '',
                'message_status' => 1,
                'message_ctime' => time(),
            ];
            $reply = [
                'reply_type' => 1,
                'reply_content' => !empty($param['content']) ? $param['content'] : '',
                'reply_ctime' => time(),
            ];
            $rule1 = [
                'message_uid' => 'require',
                'message_title' => 'require|max:100',
                'message_name' => 'max:50',
                'message_sex' => 'in:1,2',
                'message_phone' => 'regex:/^[\d\+]?\d+[\d\s\-]+\d+$/|max:50',
                'message_email' => 'email|max:100',
            ];
            $msg1 = [
                'message_uid.require' => '頁面錯誤',
                'message_title.require' => '請輸入問題簡述',
                'message_title.max' => '問題簡述不能超過100字',
                'message_name.max' => '名字不能超過50字',
                'message_sex.in' => '性別的值不正確',
                'message_phone.regex' => '電話格式不正確',
                'message_phone.max' => '電話不能超過50位',
                'message_email.email' => '郵箱格式不正確',
                'message_email.max' => '郵箱不能超過100位',
            ];
            $rule2 = [
                'reply_content' => 'require|max:50000',
            ];
            $msg2 = [
                'reply_content.require' => '請輸入問題',
                'reply_content.max' => '問題太長了',
            ];
            $validate1 = new Validate($rule1, $msg1);
            if (!$validate1->check($message)) {
                $this->error($validate1->getError());
            }
            $validate2 = new Validate($rule2, $msg2);
            if (!$validate2->check($reply)) {
                $this->error($validate2->getError());
            }
            $mid = DB::name('message')->insertGetId($message);
            if ($mid) {
                $reply['reply_mid'] = $mid;
                $res = DB::name('messageReply')->insertGetId($reply);
                if ($res) {
                    $this->success('提交問題成功', url('index/messageList'));
                } else {
                    $this->error('提交問題失敗');
                }
            } else {
                $this->error('提交問題失敗');
            }

        } else {
            $contactNo = Session::get('contact');
            $memberNo = Session::get('member');
            if (!empty($contactNo)) {
                $contact = DB::name('contact')->where('number', $contactNo)->where('isDelete', 0)->find();
                $this->assign('contact', $contact);
                $this->assign('contactNo', $contactNo);
                $this->assign('prevurl', url('index/messagelist'));
                $this->fetch('sendMessage');
            } else {
                $this->redirect('index/index');
            }
        }
    }

    public function sendReply()
    {
        $request = Request::instance();
        $this->check_login();
        if ($request->isPost()) {
            $param = input("param.");
            $user = Session::get('web_user');
            $check = DB::name('message')
                ->where('id', $param['id'])
                ->where('message_uid', $user['openid'])
                ->where('isDelete', 0)
                ->find();
            if (empty($check)) {
                $this->error('沒有找到該對話記錄', url('index/messageList'));
                die;
            }
            $reply = [
                'reply_mid' => $param['id'],
                'reply_type' => 1,
                'reply_content' => !empty($param['content']) ? $param['content'] : '',
                'reply_ctime' => time(),
            ];
            $rule = [
                'reply_mid' => 'require|number',
                'reply_content' => 'require|max:250',
            ];
            $msg = [
                'reply_mid.require' => '頁面錯誤',
                'reply_mid.number' => '頁面錯誤',
                'reply_content.require' => '請輸入問題',
                'reply_content.max' => '問題不能超過250字',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($reply)) {
                $this->error($validate->getError());
            }
            $res = 1;
            // 启动事务
            Db::startTrans();
            try {
                DB::name('messageReply')->insert($reply);
                Db::name('message')->where('id', $param['id'])->update(['message_status' => 1]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if ($res) {
                $this->success('提交問題成功', url('index/replyList', ['messageNo' => $param['id']]));
            } else {
                $this->error('提交問題失敗');
            }
        } else {
            $id = input('messageNo');
            $contactNo = Session::get('contact');
            $memberNo = Session::get('member');
            if (!empty($contactNo)) {
                $this->assign('id', $id);
                $this->assign('prevurl', url('index/replyList', ['messageNo' => $id]));
                return $this->fetch('sendReply');
            } else {
                $this->redirect('index/index');
            }
        }
    }

    //设置茶位费页面
    public function personCount(){
        $request = Request::instance();
        if ($request->isPost()) {
            $param = input("param.");
            if(isset($param['selnum'])) {
                Session::set('personCount', $param['selnum']);
            }
            return ['code'=>1,'msg'=>''];
        }else{
            return ['code'=>0,'msg'=>''];
        }
    }

    public function payOrder()
    {

        $this->check_login();
        $request = Request::instance();
        if ($request->isPost()) {
            $method = input('method');
            // 获取使用的优惠券
            $cardCode = input('code');
            // 下单部分
            $order = Session::get('order');
            $return = addOrder($order, $method,$cardCode);
            // 根据所选支付类型调起支付
            $contactNo = Session::get('contact');
            $orderNo = $return['orderNo'];
            $pay = new \app\pay\controller\Index;
            //99为餐后支付，直接返回提交成功
            if ($method == 99) {
                $laterpay = new \app\pay\controller\Laterpay();
                $return = $laterpay->PayPost($orderNo);
            } else {
                $return = $pay->payOrder($orderNo, $method);
            }
            if (isset($return['code']) && $return['code'] == 1) {
                // 支付成功 删除session中订单信息(不在下单之后删除是避免取消支付导致购物车清空)
                Session::delete('order');
                Session::delete('personCount');
                $this->success('支付成功!', url('index/orderdetail', ['ordersn' => $orderNo]));
            } else if (isset($return['code']) && $return['code'] == 0) {
                $this->error($return['msg']);
            } else if (isset($return['code']) && $return['code'] == 6) {
                return $return;
            } else {
                return ['code' => 3, 'msg' => $return];
            }
        } else {
            $contact = Session::get('contact');
            $member = Session::get('member');
            $contact_info = Db::name('contact')->where('number', $contact)->find();
            $order = Session::get('order');
            if (empty($order)) {
                $this->redirect('index/foodlist');
            }
            $show_order = show_order($order['carts']);
            //log_output($order);

            /*获取用户类型配置，微信支付宝或者其它*/
            $user_type = config('user_type');
            $user = Session::get('web_user');
            //微信，支付宝用户才能看到这两种支付方式
            $paymethod = ($user['user_type']==1||$user['user_type']==2)?DB::name('payMethod')->where('disable',1)->where('isDelete',0)->select():DB::name('payMethod')->where('disable',1)->where('isDelete',0)->where('id','not in','3,4,5,6')->select();
            // 获取优惠券
            $cardlist = getUserCardList($user['openid'],$order);

            $this->assign('contact_info', $contact_info);
            $this->assign('paymethod', $paymethod);
            $this->assign('user', $user);
            $this->assign('personCount',$personCount);
            $this->assign('tea_fee',$tea_fee);
            $this->assign('service_fee',$service_fee);
            $this->assign('user_type', $user_type);
            $this->assign('order', $order);
            $this->assign('cardlist',$cardlist);
            $this->assign('show_order', $show_order);
            return $this->fetch('payment');
        }
    }

    public function coupon()
    {
        return $this->fetch("coupon");
    }

    // 保存订单
    public function saveOrder()
    {
        $this->check_login();
        $type = Session::get('type');
        if ($type != 2) {
            $this->check_member();
        }
        $request = Request::instance();
        if ($request->isPost()) {
            $user = Session::get('web_user');
            $contactNo = Session::get('contact');
            $check_contact = check_contact($contactNo, 'number', $user['user_type']);
            $contact = $check_contact['msg'];
            if ($type == 2) {
                $courtNumber = Session::get('court');
                $court = check_court($courtNumber);
                $check_member = ['code' => 1];
                $member = ['name' => '', 'number' => ''];
            } else {
                $memberNo = Session::get('member');
                $check_member = check_member($memberNo, $contactNo);
                $member = $check_member['msg'];
            }

            if ($check_contact['code'] && $check_member['code']) {
                $order_come = input('param.order');
                $order = json_decode($order_come, true);
                $food = array();
                $totalPrice = 0;
                $good_total = 0;
                // 新訂單生成方法 v3.0 2018-11-21 17:51:31
                foreach ($order as $key => $val) {
                    if ($val['type'] == 3) {
                        $mealspecids = '';
                        if (!empty($val['specIds'])) {
                            $mealspecids = $val['specIds'];
                        }
                        $specids = array();
                        $meal = array();
                        foreach ($val['foods'] as $k => $v) {
                            if ($v['type'] == 2) {
                                if (!empty($v['specIds'])) {
                                    // 分割字符串
                                    $specids = explode(',', $v['specIds']);
                                    // 排序
                                    sort($specids);
                                    $meal[] = array('id' => $v['id'], 'counter' => $v['counter'], 'spec' => $specids, 'type' => 5, 'weight' => $v['weight'], 'cid' => $v['cid']);
                                } else {
                                    $meal[] = array('id' => $v['id'], 'counter' => $v['counter'], 'spec' => array(), 'type' => 5, 'weight' => $v['weight'], 'cid' => $v['cid']);
                                }
                            } else {
                                if (!empty($v['specIds'])) {
                                    // 分割字符串
                                    $specids = explode(',', $v['specIds']);
                                    // 排序
                                    sort($specids);
                                    $meal[] = array('id' => $v['id'], 'counter' => $v['counter'], 'spec' => $specids, 'type' => 4, 'cid' => $v['cid']);
                                } else {
                                    $meal[] = array('id' => $v['id'], 'counter' => $v['counter'], 'spec' => array(), 'type' => 4, 'cid' => $v['cid']);
                                }
                            }
                        }
                        $food[] = array('id' => $val['id'], 'counter' => $val['counter'], 'specCounts' => $val['specCounts'], 'spec' => $mealspecids, 'type' => 3, 'meal' => $meal);
                    } else {
                        if ($val['type'] == 2) {
                            if (!empty($val['specIds'])) {
                                // 分割字符串
                                $specids = explode(',', $val['specIds']);
                                // 排序
                                sort($specids);
                                $food[] = array('id' => $val['id'], 'counter' => $val['counter'], 'spec' => $specids, 'type' => 2, 'specCounts' => $val['specCounts'], 'weight' => $val['weight']);
                            } else {
                                $food[] = array('id' => $val['id'], 'counter' => $val['counter'], 'spec' => array(), 'type' => 2, 'specCounts' => $val['specCounts'], 'weight' => $val['weight']);
                            }
                        } else {
                            if (!empty($val['specIds'])) {
                                // 分割字符串
                                $specids = explode(',', $val['specIds']);
                                // 排序
                                sort($specids);
                                $food[] = array('id' => $val['id'], 'counter' => $val['counter'], 'specCounts' => $val['specCounts'], 'spec' => $specids, 'type' => 1);
                            } else {
                                $food[] = array('id' => $val['id'], 'counter' => $val['counter'], 'specCounts' => $val['specCounts'], 'spec' => array(), 'type' => 1);
                            }
                        }
                    }
                };
                $food_list = check_foods($food, $contactNo);
                $meal_list = check_meal($food, $contactNo);
                if (is_array($food_list) && is_array($meal_list)) {
                    // 合并套餐非套餐
                    $food_list = array_merge($food_list, $meal_list);
                } else if (is_array($meal_list)) {
                    $food_list = $meal_list;
                }
                foreach ($food_list as $k => $v) {
                    $good_total = $v['counter'] * $v['salePrice'];
                    $totalPrice += $good_total;
                }
                //end
                if ($type == 2 && $court['code']) {
                    $data['courtId'] = $court['msg']['id'];
                } else {
                    $data['courtId'] = 0;
                }
                !is_null(input('param.menu')) && ($data['menu'] = input('param.menu'));
                $data['carts'] = $food_list;
                $data['order'] = $order_come;
                $data['totalPrice'] = $totalPrice;
                $data['userId'] = $user['openid'];
                $data['contactName'] = $contact['name'];
                $data['contactNumber'] = $contact['number'];
                $data['contactLogoUrl'] = $contact['logoUrl'];
                $data['contactMemberName'] = $member['name'];
                $data['contactMemberNumber'] = $member['number'];
                Session::set('order', $data);
            }
            if ($type != 2) {
                $this->success('保存成功!', url('/wxweb/index/index', ['contactNo' => $contactNo, 'contactMemberNo' => $memberNo]));
            } else {
                $this->success('保存成功!', url('/wxweb/index/index', ['courtNumber' => $courtNumber]));
            }
        }
    }

    // 清除订单
    public function clearOrder()
    {
        //$wx_config = config('wx_web_config');
        $type = Session::get('type');
        $court = Session::has('court');
        $contact = Session::has('contact');
        $member = Session::has('member');
        if ($type == 1 && $contact && $member) {
            $contactNo = Session::get('contact');
            $memberNo = Session::get('member');
            $url = url('index/index', ['contactNo' => $contactNo, 'contactMemberNo' => $memberNo]);
        } else if ($type == 2 && $court) {
            $court = Session::get('court');
            $url = url('index/index', ['courtNumber' => $court]);
        } else {
            $url = url('index/index');
        }

        Session::delete('order');
        $this->success('清除成功!', $url);
    }

    public function getOrder()
    {
        $this->check_login();
        $page = input('param.page');
        $ordersn = input('param.ordersn');
        $size = 5;
        $user = Session::get('web_user');
        $orderlist = get_user_order($user['user_type'],$user['openid'],$page,$size,$ordersn);
        if($orderlist['code']){
            $this->success($orderlist['msg']);
        }else{
            $this->error($orderlist['msg']);
        }
    }

    public function orderDetail()
    {
        $this->check_login();
        $type = Session::get('type');
        $courtNo = Session::get('court');
        $contactNo = Session::get('contact');
        $memberNo = Session::get('member');
        $orderSN = input('param.ordersn');
        //商家员工帮助点餐
        if(session('contacttype')==2) $this->redirect(url('mobile/order/orderDetail',['ordersn'=>$orderSN]));
        $this->assign('ordersn', $orderSN);
        $ad = get_ad(2);
        $this->assign('ad', $ad);
        if ($type == 1) {
            $this->assign('prevurl', url('index/index', ['contactNo' => $contactNo, 'contactMemberNo' => $memberNo]));
        } else if ($type == 2) {
            $this->assign('prevurl', url('index/index', ['courtNumber' => $courtNo]));
        }
        return $this->fetch("orderDetail");
    }


    //訂單詳情
    public function allOrder(){
        return $this->fetch("allOrder");
    }

    public function paySuccess()
    {
        $this->check_login();
        $orderNo = input('param.orderNo');
        $status = input('param.status');
        if(!empty($orderNo)&&!empty($status)) {
            // 修改订单状态为已支付
            $update['orderStatus'] = 2;
            $update['payStatus'] = 1;
            $update['payTime'] = time();
            DB::name('WxOrder')->where('orderSN',$orderNo)->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
        }
        // 清除购物车
        Session::delete('order');
        $order = find_order($orderNo, 2);
        $courtNumber = '';
        if (empty($order)) {
            $this->error("訂單不存在", url('wxweb/index/allorder'));
        } else {
            $order['order_status_name'] = $order['payStatus'] == 0 ? "未支付" : model('common/WxOrder')->getPayStatusAttr($order['payStatus']);
            $order['payTime'] = date('Y-m-d H:i:s', $order['payTime']);
            $courtNumber = '';
            if ($order['courtId'] != 0) {
                $court = check_court($order['courtId'], 'id');
                $courtNumber = $court['msg']['number'];
            }
        }
        $this->assign('courtNumber', $courtNumber);
        $this->assign('order', $order);
        return $this->fetch('complete');
    }

    public function wx()
    {
        $url = input('url');
        /*获取微信appId，appSecret的配置*/
        $config = config('wx_web_config');
        $wx = new Jssdk($config);
        $data = $wx->sign(urldecode($url));
        return json(['code' => 1, 'data' => $data]);
    }

    public function wxNotify()
    {
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];//这里在php7下不能获取数据，使用 php://input 代替
        if (!$postStr) {
            $postStr = file_get_contents("php://input");
        }
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        file_put_contents('test.txt', json_encode($postObj, JSON_UNESCAPED_UNICODE));
        // 回复给微信的成功
        echo 'ok';
    }

    public function clickbanner()
    {
        $id = input('id');
        $ad = DB::name('ad')->field('adLink')->where('id', $id)->where('isDelete', 0)->find();
        $click = DB::name('ad')->where('id', $id)->where('isDelete', 0)->update(['adClick' => ['exp', 'adClick+1']]);
        if (!empty($ad) && !empty($ad['adLink'])) {
            $this->success($ad['adLink']);
        } else {
            $this->error('沒有可跳轉的鏈接');
        }
    }

    public function showdetail()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $contactNo = Session::get('contact');
            $detail = DB::name('Goods')->field('id,name,detail')->where('contactNumber', $contactNo)->where('id', $params['number'])->where('disable', 1)->where('isDelete', 0)->find();
            $this->success($detail);
        }
    }

    // 圖片上傳
    public function uploadImg()
    {
        $request = Request::instance();
        $file = request()->file('image');
        $return = array();
        if ($file) {
            // 获取缩略图宽高
            $width = config('Thumwidth');
            $height = config('Thumheight');
            // 调用上传方法 保存原图
            $uploads = uploadPic($file, 'uploads/question');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if ($uploads['code'] == 1) {
                $return['code'] = 1;
                $return['msg'] = $uploads['msg'];
                return $return;
            } else {
                $this->error($uploads['msg']);
            }
        }
        return '';
    }

    // 判断用户是否在可下单范围
    public function inarea()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $contactNo = Session::get('contact');
            $check_contact = check_contact($contactNo);
            $lat1 = $params['latitude'];
            $lng1 = $params['longitude'];
            // 转换坐标微信坐标->腾讯地图坐标
            $latlng = wxlatlngtomap($lat1, $lng1, 2);
            $lat1 = $latlng['lat'];
            $lng1 = $latlng['lng'];
            if ($check_contact['code']) {
                $contact = $check_contact['msg'];
                $lat2 = $contact['latitude'];
                $lng2 = $contact['longitude'];
                $default_config = getDefaultConfig();
                $distance = getdistance($lng1, $lat1, $lng2, $lat2);
                if ($distance <= $default_config['order_max_distance']) {
                    Session::set('userlatlng', ['lat' => $lat1, 'lng' => $lng1, 'inarea' => 1]);
                    $this->success('可以下單');
                } else {
                    Session::set('userlatlng', ['lat' => $lat1, 'lng' => $lng1, 'inarea' => 0]);
                    $this->error('獲取餐廳信息錯誤1');
                }
            } else {
                Session::set('userlatlng', ['lat' => $lat1, 'lng' => $lng1, 'inarea' => 0]);
                $this->error('獲取餐廳信息錯誤2');
            }

        }
    }

    public function getqrcodecard()
    {
        $cardSN = input('cardno');
        // 创建当前领取方式的领取条件,领取时间、状态等公共条件不用传入自动判断
        $where = array(
            // 卡券编号
            'cardSN' => $cardSN,
            // 二维码领取类型
            'distributeType' => 1,
        );
        $user = Session::get('web_user');

        // 调用函数获取能领取的卡券
        $cards = get_received_cards($user['openid'], $where);
    }

    public function concurrent()
    {
        $insert = ['testId' => 123, 'ctime' => time()];
        Db::startTrans(); //启动事务
        // 先尝试插入
        $id = DB::name('TestConcurrent')->insertGetId($insert);
        // 休眠模拟高并发
        sleep(5);
        if (DB::name('TestConcurrent')->where('testId', 123)->where('id', 'elt', $id)->count() < 9) {
            sleep(5);
            Db::commit();
        } else {
            sleep(5);
            Db::rollback();
        }
    }

    public function userprotocol()
    {

        return $this->fetch('protocol');
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

    //切换语言
    public function changeLang() {
        $lang = input('param.lang');
        switch($lang){
            case 'en-us':
                cookie('think_var','en-us');
                break;
            case 'zh-cn':
                cookie('think_var','zh-cn');
                break;
            case 'zh-tw':
                cookie('think_var','zh-tw');
                break;
            case 'other':
                cookie('think_var','other');
                break;
            default:
                break;
        }
        return ['msg'=>cookie::get('think_var'),'code'=>1];
    }
}