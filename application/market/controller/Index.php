<?php
namespace app\market\controller;
use app\market\controller\Base;
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
        $zid=session('mar_user.zid');
        if(!empty($zid)){
            // 獲取年月日
            $year  = date('Y');
            $month = date('m');
            $day   = date('d');
            // 獲取本日 本月 上月 時間戳區間
            $todayTimeStart = strtotime($year.'-'.$month.'-'.$day);
            $todayTimeEnd   = strtotime($year.'-'.$month.'-'.($day+1))-1;
            $thisMonthStart = strtotime($year.'-'.$month.'-1');
            $thisMonthEnd   = strtotime($year.'-'.($month+1).'-1')-1;
            $prevMonthStart = strtotime($year.'-'.($month-1).'-1');
            $prevMonthEnd   = strtotime($year.'-'.$month.'-1')-1;
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            // 獲取本市場人員餐廳
            if($user['mechanismAdmin']==1){
                $uid = array();
                $users = DB::name('user')->where('mechanismId',$user['mechanismId'])->select();
                foreach ($users as $key => $value) {
                    $uid[] = $value['zid'];
                }
                $uid = implode(',', $uid);
                $contact = DB::name('contact')->field('number')->where('market','in',$uid)->select();
                $subQuery = Db::name('contact')
                ->field('id,number,market')
                ->union('select id,number,market from mos_food_court')
                ->buildSql();
                $contact = Db::table($subQuery.' tb')->where('market','in',$uid)->select();
            }else{
                $subQuery = Db::name('contact')
                ->field('id,number,market')
                ->union('select id,number,market from mos_food_court')
                ->buildSql();
                $contact = Db::table($subQuery.' tb')->where('market',$zid)->select(); 
            }
            if(!empty($contact)){
                $array = array();
                foreach ($contact as $key => $val) {
                    $array[] = $val['number'];
                }
                $number = implode(',', $array);
                // 訂單信息集合
                $info = array();
                // 獲取今日訂單
                $today = DB::name('wx_order')->where('contactNumber','in',$number)->where('createTime','between',[$todayTimeStart,$todayTimeEnd])->where('orderStatus','>','1')->select();
                // 今日訂單信息(金額 佣金 訂單數)
                $info['today'] = array('ordernumber'=>count($today),'price'=>0,'commission'=>0);
                foreach ($today as $k => $v) {
                    $info['today']['price'] += $v['moneyPaid'];
                    $info['today']['commission'] += (floor($v['moneyPaid']*$user['commission'])/100);
                }
                // 獲取本月訂單
                $thismonth = DB::name('wx_order')->where('contactNumber','in',$number)->where('createTime','between',[$thisMonthStart,$thisMonthEnd])->where('orderStatus','>','1')->select();
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
        $zid=session('mar_user.zid');
        $user = DB::name('user')->where('zid',$zid)->find();
        if($user['mechanismAdmin']==1){

        $activitySign = Db::name('contact')
            ->alias('contact')
            ->field('count(id) as contactNumber,market')
            ->group('market')
            ->buildSql();
        $activityPar = Db::name('FoodCourt')
            ->alias('court')
            ->field('count(id) as courtNumber,market')
            ->group('market')
            ->buildSql();
        $staff = Db::name('user')
            ->alias('u')
            ->where('u.mechanismId',$user['mechanismId'])
            ->join($activitySign .' as contact', 'u.zid = contact.market','left')
            ->join($activityPar .' as court', 'u.zid = court.market','left')
            ->select();
            // var_dump($list);

            // $staff = DB::name('user')
            // ->alias('u')
            // ->field('u.*,group_concat(contact.id) as contactNumber,group_concat(court.id) as courtNumber')
            // ->join('mos_contact contact','u.zid = contact.market and contact.isDelete = 0','left')
            // ->join('mos_food_court court','u.zid = court.market and court.isDelete = 0','left')
            // ->where('mechanismId',$user['mechanismId'])
            // ->group('u.zid')
            // ->select();

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
        $staffID = input('user');
        $zid  = session('mar_user.zid');
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
            if($user['mechanismAdmin']==1){
                if(empty($staffID)){
                    $staffID = $user['zid'];
                }
                $staff = DB::name('user')->where('zid',$staffID)->find();
                if($staff['mechanismId']==$user['mechanismId']){
                    $user = $staff;
                    $zid = $staffID;
                    $this->assign('userid',$staffID);
                }
            }
            // 獲取本市場人員餐廳
            $contact = DB::name('contact')->field('id,logoUrl,bgImageUrl,name,reason,disable,number,contactType')->where('market',$zid)->select();
            $subQuery = Db::name('contact')
                ->field('id,logoUrl,bgImageUrl,name,reason,disable,number,contactType,market,1 as jumpType')
                ->union('select id,logoUrl,bgImageUrl,name,reason,disable,number,contactType,market,2 as jumpType from mos_food_court')
                ->buildSql();
            $contact = Db::table($subQuery.' tb')->where('market',$zid)->select(); 
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
                        $money[$v['contactNumber']]['commission'] += (floor($v['moneyPaid']*$user['commission'])/100);
                    }else{
                        $money[$v['contactNumber']]['money'] = $v['moneyPaid'];
                        $money[$v['contactNumber']]['commission'] = (floor($v['moneyPaid']*$user['commission'])/100);
                    }
                    
                }
                $this->assign('money',$money);
            }
            $type = config('contact_type');
            $this->assign('type',$type);
            $this->assign('action',$action);
            $this->assign('audit',$audit);
            $this->assign('complete',$complete);
            $this->assign('refuse',$refuse);
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
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
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
                $mechanismAdmin  = session('mar_user.mechanismAdmin');
                if($mechanismAdmin==1){
                    return $this->success('提交成功',url('index/contactlist',['action'=>0,'user'=>$post['market']]));
                }else{
                    return $this->success('提交成功',url('index/contactlist',['action'=>0]));
                }
            }else{
                return $this->error('提交失敗');
            }
        }else{
            $zid  = session('mar_user.zid');
            $id   = input('id');
            $contact = DB::name('Contact')->where('id',$id)->find();
            $user  = DB::name('User')->where('zid',$zid)->find();
            if($user['mechanismAdmin']==1){
                $staffID = input('user');
                if(empty($staffID)){
                    $staffID = $user['zid'];
                }
                $staff = DB::name('user')->where('zid',$staffID)->find();
                if($staff['mechanismId']==$user['mechanismId']){
                    $user = $staff;
                    $zid = $staffID;
                    $this->assign('userid',$staffID);
                }
            }
            if($contact['market']==$zid){
                if($contact['disable']==1){
                    $user  = DB::name('User')->where('zid',$zid)->find();
                    $money = array('money'=>0,'commission'=>0);
                    // 獲取市場人員信息
                    $order = DB::name('wx_order')->field('sum(`moneyPaid`) as money,sum(truncate(`moneyPaid`*'.$user["commission"].'/100,2)) as commission')->where('contactNumber',$contact['number'])->where('orderStatus','>','1')->find();
                    if(isset($order['money'])){
                        $money['money'] = $order['money'];
                    }else{
                        $money['money'] = 0;
                    }
                    if(isset($order['commission'])){
                        $money['commission'] = $order['commission'];
                    }else{
                        $money['commission'] = 0;
                    }
                    $this->assign('money',$money);
                    $this->assign('contact',$contact);
                    $this->assign('commission',$user["commission"]);
                    return $this->fetch('contactOrder');
                }else if($contact['disable']==0){
                    $type = config('contact_type');
                    $user    = DB::name('User')->where('contact_number',$contact['number'])->find();
                    $this->assign('type',$type);
                    $this->assign('user',$user);
                    $this->assign('contact',$contact);
                    return $this->fetch('review');
                }else if($contact['disable']==2){
                    $type = config('contact_type');
                    $user    = DB::name('User')->where('contact_number',$contact['number'])->find();
                    $category = DB::name('category')->where('typeNumber','customertype')->where('isDelete',0)->select();
                    $bank = DB::name('bank')->select();
                    $this->assign('bank',$bank);
                    $this->assign('type',$type);
                    $this->assign('user',$user);
                    $this->assign('contact',$contact);
                    $this->assign('category',$category);
                    return $this->fetch('submission');
                }
            }
        }
    }

    public function courtOrder() {
        if( Request::instance()->isPost() ) {
            $post = input('post.');
            $court = array(
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
                'member'=>0,
                'contactType'=>$post['typeId'],
                'bank_id'=>$post['bankId'],
                'bank_name'=>$post['bankName'],
                'account_number'=>$post['bankNumber'],
                'account_name'=>$post['bankUser'],
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
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
            if(!empty($court['logoUrl'])){
                $isbase['logoUrl'] = is_base64_picture($court['logoUrl']);
            }else{
                $this->error('请上传头像');die;
            }
            if(!empty($court['bgImageUrl'])){
                $isbase['bgImageUrl'] = is_base64_picture($court['bgImageUrl']);
            }else{
                $this->error('请上传背景圖');die;
            }
            if($isbase['logoUrl']){
                // base64转图片
                $img = save_base_img($court['logoUrl'],'uploads/head');
                // 图片地址保存
                $court['logoUrl'] = $img['path'];
                // 生成缩略图 这里不需要
                // $thumb = img_create_small($img['root'],80,80,"uploads/Thumb");
            }
            if($isbase['bgImageUrl']){
                $img = save_base_img($court['bgImageUrl'],'uploads/big');
                $court['bgImageUrl'] = $img['path'];
            }
            if (loader::validate('FoodCourt')->scene('review')->check($court) === false) {
                return $this->error(loader::validate('FoodCourt')->getError());
            }
            if (loader::validate('User')->scene('review')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            $res = 1;
            Db::startTrans();
            try{
                Db::name('User')->where('zid',$user['id'])->strict(false)->update($user);
                Db::name('FoodCourt')->where('id',$court['id'])->strict(false)->update($court);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res){
                $mechanismAdmin  = session('mar_user.mechanismAdmin');
                if($mechanismAdmin==1){
                    return $this->success('提交成功',url('index/contactlist',['action'=>0,'user'=>$post['market']]));
                }else{
                    return $this->success('提交成功',url('index/contactlist',['action'=>0]));
                }
            }else{
                return $this->error('提交失敗');
            }
        }else{
            $zid  = session('mar_user.zid');
            $id   = input('id');
            $court = DB::name('FoodCourt')->where('id',$id)->find();
            $user  = DB::name('User')->where('zid',$zid)->find();
            if($user['mechanismAdmin']==1){
                $staffID = input('user');
                if(empty($staffID)){
                    $staffID = $user['zid'];
                }
                $staff = DB::name('user')->where('zid',$staffID)->find();
                if($staff['mechanismId']==$user['mechanismId']){
                    $user = $staff;
                    $zid = $staffID;
                    $this->assign('userid',$staffID);
                }
            }
            if($court['market']==$zid){
                if($court['disable']==1){
                    $user  = DB::name('User')->where('zid',$zid)->find();
                    $money = array('money'=>0,'commission'=>0);
                    echo '美食广场无订单';
                    // // 獲取市場人員信息
                    // $order = DB::name('wx_order')->field('sum(`moneyPaid`) as money,sum(truncate(`moneyPaid`*'.$user["commission"].'/100,2)) as commission')->where('contactNumber',$court['number'])->where('orderStatus','>','1')->find();
                    // if(isset($order['money'])){
                    //     $money['money'] = $order['money'];
                    // }else{
                    //     $money['money'] = 0;
                    // }
                    // if(isset($order['commission'])){
                    //     $money['commission'] = $order['commission'];
                    // }else{
                    //     $money['commission'] = 0;
                    // }
                    // $this->assign('money',$money);
                    // $this->assign('contact',$contact);
                    // $this->assign('commission',$user["commission"]);
                    // return $this->fetch('contactOrder');
                }else if($court['disable']==0){
                    $type = config('contact_type');
                    $user    = DB::name('User')->where('contact_number',$court['number'])->find();
                    $this->assign('type',$type);
                    $this->assign('user',$user);
                    $this->assign('court',$court);
                    return $this->fetch('courtreview');
                }else if($court['disable']==2){
                    $type = config('contact_type');
                    $user    = DB::name('User')->where('contact_number',$court['number'])->find();
                    $category = DB::name('category')->where('typeNumber','customertype')->where('isDelete',0)->select();
                    $bank = DB::name('bank')->select();
                    $this->assign('bank',$bank);
                    $this->assign('type',$type);
                    $this->assign('user',$user);
                    $this->assign('court',$court);
                    $this->assign('category',$category);
                    return $this->fetch('courtsubmission');
                }
            }
        }
    }

    public function allOrder() {
        $zid  = session('mar_user.zid');
        $type = input('type');
        // 訂單信息集合
        $info = array();
        $orderList=array();
        if(!empty($zid)){
            // 獲取年月日
            $year  = date('Y');
            $month = date('m');
            $day   = date('d');
            if($type==1){
                // 獲取本日 時間戳區間
                $timeStart = strtotime($year.'-'.$month.'-'.$day);
                $timeEnd   = strtotime($year.'-'.$month.'-'.($day+1))-1;
                $info['name'] = '今日';
            }elseif($type==2){
                // 獲取本月 時間戳區間
                $timeStart = strtotime($year.'-'.$month.'-1');
                $timeEnd   = strtotime($year.'-'.($month+1).'-1')-1;
                $info['name']  = '本月';
            }elseif($type==3){
                // 獲取上月 時間戳區間
                $timeStart = strtotime($year.'-'.($month-1).'-1');
                $timeEnd   = strtotime($year.'-'.$month.'-1')-1;
                $info['name']  = '上月';
            }else{
                $timeStart = '';
                $timeEnd   = '';
            }
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            // 獲取本市場人員或机构餐廳
            if($user['mechanismAdmin']==1){
                $uid = array();
                $users = DB::name('user')->where('mechanismId',$user['mechanismId'])->select();
                foreach ($users as $key => $value) {
                    $uid[] = $value['zid'];
                }
                $uid = implode(',', $uid);
                $contact = DB::name('contact')->field('number')->where('market','in',$uid)->select();
            }else{
                $contact = DB::name('contact')->field('number')->where('market',$zid)->select();
            }
            if(!empty($contact)){
                $array = array();
                foreach ($contact as $key => $val) {
                    $array[] = $val['number'];
                }
                $number = implode(',', $array);
                // 獲取今日訂單
                $news = DB::name('wx_order')->field('sum(`moneyPaid`) as price,sum(truncate(`moneyPaid`*'.$user["commission"].'/100,2)) as commission')->where('contactNumber','in',$number)->where('createTime','between',[$timeStart,$timeEnd])->where('orderStatus','>','1')->find();
                if(isset($news['price'])){
                    $info['price'] = $news['price'];
                }else{
                    $info['price'] = 0;
                }
                if(isset($news['commission'])){
                    $info['commission'] = $news['commission'];
                }else{
                    $info['commission'] = 0;
                }
            }else{
                $info['price'] = 0;
                $info['commission'] = 0;
            }
            
        }
        $this->assign('commission',$user["commission"]);
        $this->assign('type',$type);
        $this->assign('info',$info);
    	return $this->fetch('allOrder');
    }

    public function nextAllOrders(){
        $zid  = session('mar_user.zid');
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
            // 獲取年月日
            $year  = date('Y');
            $month = date('m');
            $day   = date('d');
            if($type==1){
                // 獲取本日 時間戳區間
                $timeStart = strtotime($year.'-'.$month.'-'.$day);
                $timeEnd   = strtotime($year.'-'.$month.'-'.($day+1))-1;
            }elseif($type==2){
                // 獲取本月 時間戳區間
                $timeStart = strtotime($year.'-'.$month.'-1');
                $timeEnd   = strtotime($year.'-'.($month+1).'-1')-1;
            }elseif($type==3){
                // 獲取上月 時間戳區間
                $timeStart = strtotime($year.'-'.($month-1).'-1');
                $timeEnd   = strtotime($year.'-'.$month.'-1')-1;
            }else{
                $timeStart = '';
                $timeEnd   = '';
            }
            // 獲取市場人員信息
            $user = DB::name('user')->where('zid',$zid)->find();
            // 獲取本市場人員餐廳
            // 獲取本市場人員或机构餐廳
            if($user['mechanismAdmin']==1){
                $uid = array();
                $users = DB::name('user')->where('mechanismId',$user['mechanismId'])->select();
                foreach ($users as $key => $value) {
                    $uid[] = $value['zid'];
                }
                $uid = implode(',', $uid);
                $contact = DB::name('contact')->field('number')->where('market','in',$uid)->select();
            }else{
                $contact = DB::name('contact')->field('number')->where('market',$zid)->select();
            }
            if(!empty($contact)){
                $array = array();
                foreach ($contact as $key => $val) {
                    $array[] = $val['number'];
                }
                $number = implode(',', $array);
                // 獲取今日訂單
                $order = DB::name('wx_order')->where('contactNumber','in',$number)->where('createTime','between',[$timeStart,$timeEnd])->where('orderStatus','>','1')->where($where)->where('isDelete',0)->order('id desc')->limit(5)->select();;
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
        $zid  = session('mar_user.zid');
        $post = input('post.');
        $id   = $post['id'];
        $contact = DB::name('Contact')->where('id',$id)->find();
        $where = array();
        // 获取当前显示的最小id
        if(isset($post['minOrder'])){
            $where['id'] = ['<',$post['minOrder']];
        }
        $user  = DB::name('User')->where('zid',$zid)->find();
        if($user['mechanismAdmin']==1){
            $staffID = input('user');
            if(empty($staffID)){
                $staffID = $user['zid'];
            }
            $staff = DB::name('user')->where('zid',$staffID)->find();
            if($staff['mechanismId']==$user['mechanismId']){
                $user = $staff;
                $zid = $staffID;
                $this->assign('userid',$staffID);
            }
        }
        if($contact['market']==$zid){
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

    public function review(){
        return $this->fetch();
    }

    public function submission(){
        return $this->fetch();
    }

}