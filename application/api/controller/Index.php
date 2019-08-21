<?php

namespace app\api\controller;

use app\api\service\CourtContact;
use app\api\service\OrderHandler;
use think\Controller;
use think\Request;
use think\Db;

/**
 * 网站首页控制器
 * @author  kiyang
 */
class Index extends Controller
{
    public function index(){
        return $this->fetch('index');
    }

    // 登录页面控制器
    public function getcontact()
    {
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        $token = input('post.token');
        $number = input('post.number');
        $time = time();

        if(empty($number)||empty($token)){
            $result = [
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ];
            return json($result);
        }

        $field = "name,number,logoUrl,cCategoryName,address,longitude,courtId,latitude,member,bgImageUrl,isCourt,token,tokenExpiredTime";
        $res = check_contact($number,"number","",$field);
        if(!$res['code']){
            $result = [
                'errorcode' => '-2',
                'errormsg'  => '沒有找到該餐廳'
            ];
            return json($result);
        };

        if($res['msg']['isCourt']==1){
            // 美食广场餐厅
            $court = DB::name('FoodCourt')->where('id',$res['msg']['courtId'])->where('isDelete',0)->find();
            if(!$court){
                $result = [
                    'errorcode' => '-4',
                    'errormsg'  => '沒有找到該餐廳關聯的美食廣場信息'
                ];
                return json($result);
            }

            if($token!=$court['token']||$court['tokenExpiredTime']<$time){
                $result = [
                    'errorcode' => '-3',
                    'errormsg'  => 'token無效'
                ];
                return json($result);
            }

            $res['msg']['logoUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$res['msg']['logoUrl'];
            $res['msg']['bgImageUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$res['msg']['bgImageUrl'];
            unset($res['msg']['token'],$res['msg']['tokenExpiredTime']);
            $result = [
                'contactInfo' => $res['msg'],
                'errorcode' => '0',
                'errormsg'  => ''
            ];
        }else{
            if($token!=$res['msg']['token']||$res['msg']['tokenExpiredTime']<$time){
                $result = [
                    'errorcode' => '-3',
                    'errormsg'  => 'token無效'
                ];
                return json($result);
            }

            $res['msg']['logoUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$res['msg']['logoUrl'];
            $res['msg']['bgImageUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$res['msg']['bgImageUrl'];
            unset($res['msg']['token'],$res['msg']['tokenExpiredTime']);
            $result = [
                'contactInfo' => $res['msg'],
                'errorcode' => '0',
                'errormsg'  => ''
            ];
        }

        return json($result);
    }

    // 登录页面控制器
    public function getfoodlist()
    {
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        $token = input('post.token');
        $number = input('post.number');
        $time = time();
        if(empty($number)||empty($token)){
            return json([
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ]);
        }

        $contact = DB::name('contact')
            ->where('number',$number)
            ->where('isDelete',0)
            ->find();
        if(empty($contact)){
            return json([
                'errorcode' => '-2',
                'errormsg'  => '餐厅不存在'
            ]);
        }

        if($contact['isCourt']==1){
            $court = DB::name('FoodCourt')
                ->where('id',$contact['courtId'])
                ->where('isDelete',0)
                ->find();
            if(!$court){
                return json([
                    'errorcode' => '-4',
                    'errormsg'  => '沒有找到美食廣場'
                ]);
            }

            if($token!=$court['token']||$court['tokenExpiredTime']<$time){
                return json([
                    'errorcode' => '-3',
                    'errormsg'  => 'token無效'
                ]);
            }

            $result = CourtContact::contactFoodList($number,2);
            $result['errorcode'] = '0';
            $result['errormsg']  = '';
        }else{
            if($token!=$contact['token']||$contact['tokenExpiredTime']<$time){
                return json([
                    'errorcode' => '-3',
                    'errormsg'  => 'token無效'
                ]);
            }
            $result = CourtContact::contactFoodList($number,2);
            $result['errorcode'] = '0';
            $result['errormsg']  = '';
        }
        return json($result);
    }

    // 登录页面控制器
    public function getuserorder()
    {
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        $post = input('post.');
        $time = time();

        if(empty($post['number'])||empty($post['token'])){
            $result = [
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ];
            return json($result);
        }
        $post['page'] = !empty($post['page'])?$post['page']:1;
        $post['size'] = !empty($post['size'])?$post['size']:5;
        ($post['size']>500)&&$post['size'] = 500;

        $contact = DB::name('contact')
            ->where('number',$post['number'])
            ->where('isDelete',0)
            ->where('isCourt',0)
            ->find();
        $court = DB::name('FoodCourt')
            ->where('number',$post['number'])
            ->where('isDelete',0)
            ->find();

        if(empty($contact)&&empty($court)){
            $result = [
                'errorcode' => '-4',
                'errormsg'  => '編號錯誤'
            ];
            return json($result);
        }

        if(!empty($contact)){
            $number = $contact['number'];
            $type = 1;
        }else{
            $number = $court['id'];
            $type = 2;
        }

        if($type==1){

            if($post['token']!=$contact['token']||$contact['tokenExpiredTime']<$time){
                $result = [
                    'errorcode' => '-3',
                    'errormsg'  => 'token無效'
                ];
                return json($result);
            }
            $res = get_user_contact_order($type,$number,$post['openId'],$post['page'],$post['size']);

            $result = $res['code']?['orderlist' => $res['msg'], 'errorcode' => '0', 'errormsg'  => '']
                :['errorcode' => '-2', 'errormsg'  => '沒有訂單了'];

        }else{

            if($post['token']!=$court['token']||$court['tokenExpiredTime']<$time){
                $result = [
                    'errorcode' => '-3',
                    'errormsg'  => 'token無效'
                ];
                return json($result);
            }
            $res = get_user_contact_order($type,$number,$post['openId'],$post['page'],$post['size']);

            $result = $res['code']?['orderlist' => $res['msg'], 'errorcode' => '0', 'errormsg'  => '']
                :['errorcode' => '-2', 'errormsg'  => '沒有訂單了'];
        }
        return json($result);
    }

    public function postorder()
    {
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        $post = input('post.');
        $time = time();
        if(empty($post['_food'])||empty($post['contactNo'])||empty($post['openId'])||empty($post['nickName'])){
            $result = [
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ];
            return json($result);
        }
        $contact = DB::name('contact')
            ->where('number',$post['contactNo'])
            ->where('disable',1)
            ->where('isDelete',0)
            ->find();
        if ($contact['isCourt']==1) {
            $member = ['name'=>'','number'=>''];
            $court = DB::name('FoodCourt')->where('id',$contact['courtId'])->where('isDelete',0)->find();
            if($court){
                if($post['token']!=$court['token']||$court['tokenExpiredTime']<$time){
                    return json(['errorcode' => '-6', 'errormsg'  => 'token無效']);
                }
            }else{
                return json(['errorcode' => '-7', 'errormsg'  => '沒有找到美食廣場']);
            }
        }else{
            if($post['token']!=$contact['token']||$contact['tokenExpiredTime']<$time){

                return json(['errorcode' => '-6', 'errormsg'  => 'token無效']);
            }
            $member = DB::name('contact_member')
                ->where('number',$post['contactMemberNo'])
                ->where('contactNumber',$post['contactNo'])
                ->where('disable',1)
                ->where('isDelete',0)
                ->find();
        }
        if(empty($contact)){
            return json(['errorcode' => '-4', 'errormsg'  => '餐廳不存在']);
        }else if(empty($member)){
            return json(['errorcode' => '-5', 'errormsg'  => '餐檯不存在']);
        }
        $_food = json_decode($post['_food'],true);
        if(empty($_food)){
            return json(['errorcode' => '-3', 'errormsg'  => '訂單詳情不能為空']);
        }else{
            $foodList = CourtContact::foodFromOrder($_food,$post['contactNo']);
            $totalPrice = 0;
            foreach ($foodList as $key => $food) {
                $totalPrice += $food['counter'] * $food['salePrice'];
            };

            $orderDetail = [
                'foodList' => $foodList,
                'totalPrice' => $totalPrice,
                'userId' => $post['openId'],
                'userNick' => $post['nickName'],
                'userType' => 0,
                'contactName' => $contact['name'],
                'printerId' => $contact['printerId']?:7,
                'contactNumber' => $contact['number'],
                'contactLogoUrl' => $contact['logoUrl'],
                'contactMemberName' => $member['name'],
                'contactMemberNumber' => $member['number'],
                'latitude' => '',
                'longitude' => '',
                'orderInArea' => ''
            ];

            $contact['isCourt']==1&&$orderDetail['courtId'] = $contact['courtId'];
            $orderHandler = new OrderHandler();
            $saveResult = $orderHandler->saveOrderGoods($orderDetail);
            if($saveResult['code']){
                $result = ['orderSN' => $saveResult['orderNo'], 'errorcode' => 0, 'errormsg' => ''];
            }else{
                $result = ['errorcode' => '-2', 'errormsg'  => '下單失敗,請校驗參數的值'];
            }
        }
        return json($result);
    }

    public function getcourtcontact(){
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        $number = input('post.number');
        $token = input('post.token');
        $time = time();

        if(empty($number)){
            $result = [
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ];
            return json($result);
        }

        $field = "id,name,number,logoUrl,cCategoryName,address,longitude,latitude,bgImageUrl,token,tokenExpiredTime";
        $court = check_court($number,'number','',$field);
        if(!$court['code']){
            $result = [
                'errorcode' => '-2',
                'errormsg'  => '没有找到美食广场'
            ];
            return json($result);
        }

        $res = get_court_contact($court['msg']['id']);
        if(!$res['code']){
            $result = [
                'errorcode' => '-3',
                'errormsg'  => '获取餐厅列表失败'
            ];
            return json($result);
        }

        if($token!=$court['msg']['token']||$court['msg']['tokenExpiredTime']<$time){
            $result = [
                'errorcode' => '-4',
                'errormsg'  => 'token無效'
            ];
            return json($result);
        }

        $court['msg']['logoUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$court['msg']['logoUrl'];
        $court['msg']['bgImageUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$court['msg']['bgImageUrl'];
        foreach ($res['msg'] as $key => $val) {
            $res['msg'][$key]['logoUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$val['logoUrl'];
            $res['msg'][$key]['bgImageUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$val['bgImageUrl'];
        }
        unset($court['msg']['id'],$court['msg']['token'],$court['msg']['tokenExpiredTime']);
        $result = [
            'courtInfo'=>$court['msg'],
            'contactList'=>$res['msg'],
            'errorcode'=>'0',
            'errormsg'=>'',
        ];
        return json($result);
    }

    public function addToken(){
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        $post = input('param.');
        $time = time();

        if(empty($post['number'])||empty($post['secretKey'])){
            $result = [
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ];
            return json($result);
        }

        $contact = DB::name('contact')
            ->where('number',$post['number'])
            ->where('isDelete',0)
            ->where('isCourt',0)
            ->find();
        $court = DB::name('FoodCourt')
            ->where('number',$post['number'])
            ->where('isDelete',0)
            ->find();
        //区分大小写验证secretKey是否正确

        if(!empty($contact)&&strcmp($contact['secretKey'],$post['secretKey'])==0){

            $update['token'] = md5($post['number'].$post['secretKey'].$time);
            $update['tokenCreateTime'] = $time;
            $update['tokenExpiredTime'] = $time + 2*60*60;
            $set = DB::name('contact')->where('id',$contact['id'])->update($update);
            if(!$set){
                $result = [
                    'errorcode' => '-3',
                    'errormsg'  => '获取失败'
                ];
                return json($result);
            }
            $result = [
                'token' => $update['token'],
                'errorcode' => '0',
                'errormsg' => '',
            ];

        }else if(!empty($court)&&strcmp($court['secretKey'],$post['secretKey'])==0){
            $update['token'] = md5($post['number'].$post['secretKey'].$time);
            $update['tokenCreateTime'] = $time;
            $update['tokenExpiredTime'] = $time + 2*60*60;
            $set = DB::name('FoodCourt')
                ->where('id',$court['id'])
                ->where('isDelete',0)
                ->update($update);
            if(!$set){
                $result = [
                    'errorcode' => '-3',
                    'errormsg'  => '获取失败'
                ];
                return json($result);
            }
            $result = [
                'token' => $update['token'],
                'errorcode' => '0',
                'errormsg' => '',
            ];
        }else{
            $result = [
                'errorcode' => '-2',
                'errormsg'  => '參數值不正確'
            ];
        }
        return json($result);
    }

    public function payQueryOrder(){
        $request = Request::instance();
        if (!$request->isPost()) {return '';}
        //接受數據
        $post = input('param.');
        // 獲取當前時間
        $time = time();

        if(empty($post['orderNo'])||empty($post['token'])){

            $result = [
                'errorcode' => '-1',
                'errormsg'  => '缺少必要參數'
            ];
            return json($result);
        }

        $order = DB::name('wx_order')
            ->where('orderSN',$post['orderNo'])
            ->find();

        if(empty($order)){
            $result = [
                'errorcode' => '-2',
                'errormsg'  => '訂單不存在'
            ];
            return json($result);
        }

        if($order['payStatus']!=0){
            $result = [
                'errorcode' => '-3',
                'errormsg'  => '訂單狀態不能修改'
            ];
            return json($result);
        }

        $res = ($order['courtId']!=0)?(DB::name('FoodCourt')->where('id',$order['courtId'])->where('isDelete',0)->find())
            :(DB::name('contact')->where('number',$order['contactNumber'])->where('isDelete',0)->find());

        if(empty($res)){
            $result = [
                'errorcode' => '-4',
                'errormsg'  => '無法修改該訂單'
            ];
            return json($result);
        }

        if($res['token']!=$post['token']||$res['tokenExpiredTime']<$time){
            $result = [
                'errorcode' => '-5',
                'errormsg'  => 'token無效'
            ];
            return json($result);
        }

        $update = array(
            'payStatus'   => 1,
            'orderStatus' => 2,
            'payTime'=>time(),
        );
        $res = DB::name('wx_order')
            ->where('id',$order['id'])
            ->where('isDelete',0)
            ->update($update);

        if($res){
            $result = [
                'errorcode' => '0',
                'errormsg'  => ''
            ];
        }else{
            $result = [
                'errorcode' => '-6',
                'errormsg'  => '修改失敗'
            ];
        }
        return json($result);
    }

    /*这里是用于公众号验证服务器配置的*/
    public function wxToken(){
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $token = "hellojerry";
        $signature = $_GET['signature'];
        $array = array($timestamp,$nonce,$token);
        sort($array);

//2.将排序后的三个参数拼接后用sha1加密
        $tmpstr = implode('',$array);
        $tmpstr = sha1($tmpstr);

//3. 将加密后的字符串与 signature 进行对比, 判断该请求是否来自微信
        if($tmpstr == $signature)
        {
            return $_GET['echostr'];
        }
        return '';
    }

    // 圖片上傳
    public function uploadOriginImg(){
        $request = Request::instance();
        $file = request()->file('imageName');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'origin');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                //echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo '/uploads/origin/'.date('Ymd',time()).'/'.$info->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
}
