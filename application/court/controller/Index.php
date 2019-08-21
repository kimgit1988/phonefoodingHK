<?php
namespace app\court\controller;
use app\court\controller\Base;
// use app\common\model\User;
use think\View;//视图
use think\Controller;//控制器
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库

class Index extends Base {

    public function index() {
        $zid=session('court_user.zid');
        $courtId = session('court_user.courtId');
        // $zid=$user['zid'];
        if(!empty($zid)){
            // 獲取本日 本月 上月 時間戳區間
            $todayTimeStart = strtotime(date("Y-m-d"));
            $todayTimeEnd   = strtotime(date("Y-m-d",strtotime("+1 day")))-1;
            $thisMonthStart = strtotime(date("Y-m").'-1');
            $thisMonthEnd   = strtotime(date("Y-m",strtotime("+1 month")).'-1')-1;
            $prevMonthStart = strtotime(date("Y-m",strtotime("-1 month")).'-1');
            $prevMonthEnd   = strtotime(date("Y-m").'-1')-1;
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            // 獲取本美食廣場
            $contact = DB::name('contact')->field('number')->where('isCourt','1')->where('courtId',$courtId)->select();
            if(!empty($contact)){
                $array = array();
                foreach ($contact as $key => $val) {
                    $array[] = $val['number'];
                }
                $number = implode(',', $array);
                // 訂單信息集合
                $info = array();
                // 獲取今日訂單
                $today = DB::name('WxOrder')->where('contactNumber','in',$number)->where('createTime','between',[$todayTimeStart,$todayTimeEnd])->where('orderStatus','>','1')->select();
                // 今日訂單信息(金額 佣金 訂單數)
                $info['today'] = array('ordernumber'=>count($today),'price'=>0,'commission'=>0);
                foreach ($today as $k => $v) {
                    $info['today']['price'] += $v['moneyPaid'];
                    $info['today']['commission'] += (floor($v['moneyPaid']*$user['commission'])/100);
                }
                // 獲取本月訂單
                $thismonth = DB::name('WxOrder')->where('contactNumber','in',$number)->where('createTime','between',[$thisMonthStart,$thisMonthEnd])->where('orderStatus','>','1')->select();
                // 本月訂單信息(金額 佣金 訂單數)
                $info['thismonth'] = array('ordernumber'=>count($thismonth),'price'=>0,'commission'=>0);
                foreach ($thismonth as $k => $v) {
                    $info['thismonth']['price'] += $v['moneyPaid'];
                    $info['thismonth']['commission'] += (floor($v['moneyPaid']*$user['commission'])/100);
                }
                // 獲取上月訂單
                $prevmonth = DB::name('wx_order')->where('contactNumber','in',$number)->where('createTime','between',[$prevMonthStart,$prevMonthEnd])->where('orderStatus','>','1')->select();
                // 上月訂單信息(金額 佣金 訂單數)
                $info['prevmonth'] = array('ordernumber'=>count($prevmonth),'price'=>0,'commission'=>0);
                foreach ($prevmonth as $k => $v) {
                    $info['prevmonth']['price'] += $v['moneyPaid'];
                    $info['prevmonth']['commission'] += (floor($v['moneyPaid']*$user['commission'])/100);
                }
                $contactNumber = count($contact);
            }else{
                $info = array(
                    'today'=>array('price'=>0,'commission'=>0,'ordernumber'=>0),
                    'thismonth'=>array('price'=>0,'commission'=>0,'ordernumber'=>0),
                    'prevmonth'=>array('price'=>0,'commission'=>0,'ordernumber'=>0),

                );
                $contactNumber = 0;
            }
            $this->assign('info',$info);
            $this->assign('contactNumber',$contactNumber);
            return $this->fetch();
        }
    }

    public function userList(){
        $zid=session('court_user.zid');
        $user = DB::name('user')->where('zid',$zid)->find();
        if($user['mechanismAdmin']==1){
            $staff = DB::name('user')->where('mechanismId',$user['mechanismId'])->select();
            $this->assign('staff',$staff);
            return $this->fetch('userList');
        }else{
            $this->redirect('index/contactlist');
        }
    }

    public function contactList() {
        $action = input('action');
        if($action===NULL){
            $action = -1;
        }
        $zid  = session('court_user.zid');
        $courtId = session('court_user.courtId');
        // 全部餐廳
        $contact  = array();
        // 審核中
        $audit    = array();
        // 已通過
        $complete = array();
        // 已拒絕
        $refuse   = array();
        if(!empty($zid)){
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            // 獲取本市場人員餐廳
            $contact = DB::name('contact')->field('id,logoUrl,bgImageUrl,name,reason,disable,number,contactType')->where('isCourt','1')->where('courtId',$courtId)->select();
            $contactNumber = array();
            foreach ($contact as $key => $val) {
                if($val['disable']==0){
                    $audit[] = $val;
                }else if($val['disable']==1){
                    $complete[] = $val;
                    $contactNumber[] = $val['number'];
                }else if($val['disable']==2){
                    $refuse[] = $val;
                }
            }
            if(!empty($contactNumber)){
                $contactNumber = implode(',', $contactNumber);
                $order = DB::name('wx_order')->field('moneyPaid,contactNumber')->where('contactNumber','in',$contactNumber)->where('orderStatus','>','1')->select();
                $money = array();
                foreach($order as $k => $v){
                    if(!empty($money[$v['contactNumber']])){
                        $money[$v['contactNumber']]['money'] += $v['moneyPaid'];
                        $money[$v['contactNumber']]['number'] += 1;
                    }else{
                        $money[$v['contactNumber']]['money'] = $v['moneyPaid'];
                        $money[$v['contactNumber']]['number'] = 1;
                    }
                    
                }
                $this->assign('money',$money);
            }
            $type = config('contact_type');
            $this->assign('type',$type);
            $this->assign('contact',$contact);
            return $this->fetch('contactList');
        }
    	
    }

    public function contactOrder() {
        if( Request::instance()->isPost() ) {
            $post = input('post.');
            $contact = array(
                'id'=>$post['contactid'],
                'name'=>$post['contact'],
                'number'=>$post['number'],
                'cCategory'=>$post['categoryId'],
                'disable'=>0,
                'cCategoryName'=>$post['categoryName'],
                'logoUrl'=>$post['pic_path'],
                'bgImageUrl'=>$post['img_path'],
                'remark'=>$post['detail'],
                'linkMans'=>$post['phone'],
                'member'=>$post['member'],
                'contactType'=>$post['typeId'],
                'bank_id'=>$post['bankId'],
                'bank_name'=>$post['bankName'],
                'account_number'=>$post['bankNumber'],
                'account_name'=>$post['bankUser'],
            );
            $user = array(
                'id'=>$post['userid'],
                'name'=>$post['username'],
                'nick'=>$post['person'],
                'is_contact'=>1,
                'contact_number'=>$post['number'],
                'email'=>$post['mail'],
                'status'=>0,
                'create_time'=>time(),
            );
            if(!empty($contact['logoUrl'])){
                $isbase['logoUrl'] = is_base64_picture($contact['logoUrl']);
            }else{
                $this->error('请上传头像');die;
            }
            if(!empty($contact['bgImageUrl'])){
                $isbase['bgImageUrl'] = is_base64_picture($contact['bgImageUrl']);
            }else{
                $this->error('请上传背景圖');die;
            }
            if($isbase['logoUrl']){
                // base64转图片
                $img = save_base_img($contact['logoUrl'],'uploads/head');
                // 图片地址保存
                $contact['logoUrl'] = $img['path'];
                // 生成缩略图 这里不需要
                // $thumb = img_create_small($img['root'],80,80,"uploads/Thumb");
            }
            if($isbase['bgImageUrl']){
                $img = save_base_img($contact['bgImageUrl'],'uploads/big');
                $contact['bgImageUrl'] = $img['path'];
            }
            if (loader::validate('Contact')->scene('review')->check($contact) === false) {
                return $this->error(loader::validate('Contact')->getError());
            }
            if (loader::validate('User')->scene('review')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            $res = 1;
            Db::startTrans();
            try{
                Db::name('User')->where('zid',$user['id'])->strict(false)->update($user);
                Db::name('Contact')->where('id',$contact['id'])->strict(false)->update($contact);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res){
                $mechanismAdmin  = session('court_user.mechanismAdmin');
                if($mechanismAdmin==1){
                    return $this->success('提交成功',url('index/contactlist',['action'=>0,'user'=>$post['market']]));
                }else{
                    return $this->success('提交成功',url('index/contactlist',['action'=>0]));
                }
            }else{
                return $this->error('提交失敗');
            }
        }else{
            $zid  = session('court_user.zid');
            $courtId  = session('court_user.courtId');
            $id   = input('id');
            $contact = DB::name('Contact')->where('id',$id)->find();
            $user  = DB::name('User')->where('zid',$zid)->find();
            if($contact['courtId']==$courtId){
                if($contact['disable']==1){
                    $money = array('money'=>0,'number'=>0);
                    // 獲取市場人員信息
                    $order = DB::name('WxOrder')->field('sum(`moneyPaid`) as money,count(`id`) as number')->where('contactNumber',$contact['number'])->where('orderStatus','>','1')->find();
                    if(isset($order['money'])){
                        $money['money'] = $order['money'];
                    }else{
                        $money['money'] = 0;
                    }
                    if(isset($order['number'])){
                        $money['number'] = $order['number'];
                    }else{
                        $money['number'] = 0;
                    }
                    $this->assign('money',$money);
                    $this->assign('contact',$contact);
                    $this->assign('commission',$user["commission"]);
                    return $this->fetch('contactOrder');
                }
            }
        }
    }

    public function allOrder() {
        $zid  = session('court_user.zid');
        $courtId  = session('court_user.courtId');
        $type = input('type');
        // 訂單信息集合
        $info = array();
        $orderList=array();
        if(!empty($zid)){
            if($type==1){
                // 獲取本日 時間戳區間
                $timeStart = strtotime(date("Y-m-d"));
                $timeEnd   = strtotime(date("Y-m-d",strtotime("+1 day")))-1;
                $info['name'] = '今日';
            }elseif($type==2){
                // 獲取本月 時間戳區間
                $timeStart = strtotime(date("Y-m").'-1');
                $timeEnd   = strtotime(date("Y-m",strtotime("+1 month")).'-1')-1;
                $info['name']  = '本月';
            }elseif($type==3){
                // 獲取上月 時間戳區間
                $timeStart = strtotime(date("Y-m",strtotime("-1 month")).'-1');
                $timeEnd   = strtotime(date("Y-m").'-1')-1;
                $info['name']  = '上月';
            }else{
                $timeStart = '';
                $timeEnd   = '';
            }
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            $contact = DB::name('contact')->field('number')->where('isCourt',1)->where('courtId',$courtId)->select();
            if(!empty($contact)){
                $array = array();
                foreach ($contact as $key => $val) {
                    $array[] = $val['number'];
                }
                $number = implode(',', $array);
                // 獲取今日訂單
                $news = DB::name('wx_order')->field('sum(`moneyPaid`) as price,count(id) as number')->where('contactNumber','in',$number)->where('createTime','between',[$timeStart,$timeEnd])->where('orderStatus','>','1')->find();
                if(isset($news['price'])){
                    $info['price'] = $news['price'];
                }else{
                    $info['price'] = 0;
                }
                if(isset($news['number'])){
                    $info['number'] = $news['number'];
                }else{
                    $info['number'] = 0;
                }
            }else{
                $info['price'] = 0;
                $info['number'] = 0;
            }
            
        }
        $this->assign('type',$type);
        $this->assign('info',$info);
    	return $this->fetch('allOrder');
    }

    public function nextAllOrders(){
        $zid  = session('court_user.zid');
        $courtId  = session('court_user.courtId');
        $post = input('post.');
        $type = $post['type'];
        $where = array();
        // 获取当前显示的最小id
        if(isset($post['minOrder'])){
            $where['id'] = ['<',$post['minOrder']];
        }
        // 訂單信息集合
        $orderList=array();
        if(!empty($zid)){
            if($type==1){
                // 獲取本日 時間戳區間
                $timeStart = strtotime(date("Y-m-d"));
                $timeEnd   = strtotime(date("Y-m-d",strtotime("+1 day")))-1;
                $info['name'] = '今日';
            }elseif($type==2){
                // 獲取本月 時間戳區間
                $timeStart = strtotime(date("Y-m").'-1');
                $timeEnd   = strtotime(date("Y-m",strtotime("+1 month")).'-1')-1;
                $info['name']  = '本月';
            }elseif($type==3){
                // 獲取上月 時間戳區間
                $timeStart = strtotime(date("Y-m",strtotime("-1 month")).'-1');
                $timeEnd   = strtotime(date("Y-m").'-1')-1;
                $info['name']  = '上月';
            }else{
                $timeStart = '';
                $timeEnd   = '';
            }
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            $contact = DB::name('contact')->field('number')->where('isCourt',1)->where('courtId',$courtId)->select();
            if(!empty($contact)){
                $array = array();
                foreach ($contact as $key => $val) {
                    $array[] = $val['number'];
                }
                $number = implode(',', $array);
                // 獲取今日訂單
                $order = DB::name('WxOrder')->where('contactNumber','in',$number)->where('createTime','between',[$timeStart,$timeEnd])->where('orderStatus','>','1')->where($where)->where('isDelete',0)->order('id desc')->limit(5)->select();;
                if(!empty($order)){
                    $orderSN = array();
                    $orderList = array();
                    foreach ($order as $k => $v) {
                        $orderSN[] = $v['orderSN'];
                        $v['createTime'] = date('Y-m-d',$v['createTime']);
                        $orderList[$v['orderSN']] = $v;
                    }
                    $orderSN = implode(',', $orderSN);
                    $foods = DB::name('wx_order_goods')->where('orderSN','in',$orderSN)->order('id desc')->select();
                    // 把菜品按訂單放入數組
                    $orderinfo = array();
                    foreach ($foods as $key => $val) {
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
                    $foods = $orderinfo;
                    foreach ($foods as $key => $value) {
                        $orderList[$value['orderSN']]['foods'][] = $value;
                    }
                    $orderList = array_values($orderList);
                    return $this->success($orderList);
                }else{
                    return $this->error('沒有更多訂單');
                }
            }else{
                return $this->error('您還沒有添加商戶，請繼續努力。');
            }
            
        }
    }

    public function nextContactOrders(){
        $zid  = session('court_user.zid');
        $post = input('post.');
        $id   = $post['id'];
        $contact = DB::name('Contact')->where('id',$id)->find();
        $courtId  = session('court_user.courtId');
        $where = array();
        // 获取当前显示的最小id
        if(isset($post['minOrder'])){
            $where['id'] = ['<',$post['minOrder']];
        }
        $user  = DB::name('User')->where('zid',$zid)->find();
        if($contact['courtId']==$courtId){
            if($contact['disable']==1){
                // 獲取市場人員信息
                $order = DB::name('wx_order')->field('id,orderSN,contactMemberNumber,createTime,moneyPaid,contactNumber')->where('contactNumber',$contact['number'])->where('orderStatus','>','1')->where($where)->where('isDelete',0)->order('id desc')->limit(5)->select();
                $orderSN = array();
                $orderList = array();
                if(!empty($order)){
                    foreach ($order as $k => $v) {
                        $orderSN[] = $v['orderSN'];
                        $v['createTime'] = date('Y-m-d',$v['createTime']);
                        $orderList[$v['orderSN']] = $v;
                    }
                    $orderSN = implode(',', $orderSN);
                    $foods = DB::name('wx_order_goods')->where('orderSN','in',$orderSN)->order('id asc')->select();
                    // 查詢訂單菜品
                    // 把菜品按訂單放入數組
                    $orderinfo = array();
                    foreach ($foods as $key => $val) {
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
                    $foods = $orderinfo;
                    foreach ($foods as $key => $value) {
                        $orderList[$value['orderSN']]['foods'][] = $value;
                    }
                    $orderList = array_values($orderList);
                    return $this->success($orderList);
                }else{
                    return $this->error('没有更多订单');
                }
            }
        }
    }

}