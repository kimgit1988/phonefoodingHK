<?php
namespace app\mobile\controller;
use app\mobile\controller\Base;
use think\View;//视图
use think\Controller;//控制器
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库
use think\Validate;//验证

class Owner extends Base {

    public function index() {
        $id = session('mob_user.zid');
    	// 設置今天單數初始值
    	$info['todayOrder'] = 0;
    	// 設置今天金額初始值
    	$info['todayPrice'] = 0;
    	// 設置總單數初始值
    	$info['allOrder']   = 0;
    	// 設置總金額初始值
    	$info['allPrice']   = 0;
    	// 今天0点时间戳
    	$today = strtotime(date('Y-m-d'));
    	if(session('mob_user.is_contact')!=0){
            $contact_number = session('mob_user.contact_number');
        }else{
            $contact_number = '';
        }
        $contact = DB::name('contact')->where('number',$contact_number)->where('isDelete',0)->find();
        $head = $contact['logoUrl'];
        $todayOrder = DB::name('wxOrder')
            ->field('count(*) as orderNumber,sum(moneyPaid) as orderPrice')
            ->where('contactNumber',$contact_number)
            ->where('payStatus','>=','1')
            ->where('isDelete',0)
            ->where(['createTime'=>['egt',$today]])
            ->find();
        $newOrder = DB::name('wxOrder')
                      ->field('count(*) as orderNumber,sum(moneyPaid) as orderPrice')
                      ->where('contactNumber',$contact_number)
                      ->where('addStatus=1 or orderStatus=2')
                      ->where('isDelete',0)
                      ->find();
        $allOrder = DB::name('wxOrder')
            ->field('count(*) as orderNumber,sum(moneyPaid) as orderPrice')
            ->where('contactNumber',$contact_number)
            ->where('payStatus','>=','1')
            ->where('isDelete',0)
            ->find();
        $info['allPrice']   = !empty($allOrder['orderPrice'])?$allOrder['orderPrice']:'0.00';
        $info['allOrder']   = $allOrder['orderNumber'];
        $info['newOrder']   = $newOrder['orderNumber'];
        $info['todayPrice'] = !empty($todayOrder['orderPrice'])?$todayOrder['orderPrice']:'0.00';
        $info['todayOrder'] = $todayOrder['orderNumber'];
        $info['countGoods'] = DB::name('goods')->where('contactNumber',$contact_number)->where('isDelete',0)->count();
        $info['countMember'] = DB::name('contactMember')->where('contactNumber',$contact_number)->where('isDelete',0)->count();
        $this->assign('head',$head);
        $this->assign('nick',$contact['name']);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function sendMonExl(){
        return $this->fetch('sendMonExl');
    }

    public function sendDayExl(){
        if( Request::instance()->isPost() ) {
            $contact_number = session('mob_user.contact_number');
            $time = input('time');
            $time = strtotime($time);
            $endtime = $time+86400-1;
            $contact = DB::name('contact')->where('number',$contact_number)->where('isDelete',0)->find();
            $user = DB::name('user')->where('contact_number',$contact_number)->find();
            $order = DB::name('wx_order')->where('contactNumber',$contact_number)->where('createTime','between',[$time,$endtime])->where('orderStatus','>','1')->select();
            // 验证用户邮箱是否能发送
            $result = $this->validate(['email' => $user['email']],['email'   => 'email']);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->error('賬號郵箱不能發送,請修改!');
            }
            $i = 1;
            $data = [];
            foreach($order as $key =>$val){
                $data[] = [$i=>1,$val['orderSN']=>2,date('Y-m-d',$val['createTime'])=>1,$val['moneyPaid']=>1];
                $i++;
            }
            $header=['name'=>$contact['name'],'number'=>$contact['number'],'time'=>date('Y-m-d',$time)];
            $fileName = $contact['id'].'-'.date('Y-m-d',$time);
            $email = $user['email'];
            $title = $contact['name'];
            $body = $contact['name'].'日結單';
            $userName = $user['nick'];
            $res = sendExl($fileName,$email,$title,$body,$header,$data,$userName);
            if ($res['code']==1) {
                $this->success($res['msg'],url('owner/index'));
            }else{
                $this->error($res['msg']);
            }
        }else{
            return $this->fetch('sendDayExl');
        }
    }

    public function showStatements(){
        if( Request::instance()->isPost() ) {
            $params = input('param.');
            $contact_number = session('mob_user.contact_number');
            $dbcs = DB::name('contactStatements')->where('contactNumber',$contact_number);
            if(!empty($params['minStatements'])){
                $dbcs->where('id','<',$params['minStatements']);
            }
            if(!empty($params['start'])&&!empty($params['end'])){
                $dbcs->where('merAccountDate','between',$params['start'].','.$params['end']);
            }
            $contactStatements = $dbcs->limit(5)->order('id desc')->select();
            $this->success($contactStatements);
        }else{
            $start = input('start');
            $end   = input('end');
            $this->assign('start',$start);
            $this->assign('end',$end);
            return $this->fetch('showStatements');
        }
    }

    public function showBalance(){
        if( Request::instance()->isPost() ) {
            $params = input('param.');
            $contact_number = session('mob_user.contact_number');
            if(empty($params['No'])){
                $this->error('頁面錯誤!');
            }
            $statements = DB::name('contactStatements')->where('contactNumber',$contact_number)->where('id',$params['No'])->find();
            $dbcb = DB::name('contactBalance')->where('contactNumber',$contact_number)->where('merAccountDate',$statements['merAccountDate']);
            if(!empty($params['maxBalance'])){
                $dbcb->where('id','>',$params['maxBalance']);
            }
            $contactBalance = $dbcb->limit(5)->order('id asc')->select();
            $this->success($contactBalance);
        }else{
            $id = input('No');
            $contact_number = session('mob_user.contact_number');
            $statements = DB::name('contactStatements')->where('contactNumber',$contact_number)->where('id',$id)->find();
            // 结算单不能为空 否则跳回结算单列表页面
            if(empty($statements)){
                $this->redirect('owner/showStatements');
            }
            $this->assign('No',$id);
            $this->assign('statements',$statements);
            return $this->fetch('showBalance');
        }
    }

    public function downBalance(){
        $id = input('No');
        $statements = DB::name('contactStatements')
            ->alias('s')
            ->join('mos_contact c','s.contactNumber = c.number','left')
            ->join('mos_user u','s.contactNumber = u.contact_number','left')
            ->field('s.*,c.name,u.email,u.nick as nick,s.id as id,c.name as name,s.ctime as ctime')
            ->where('u.is_contact',1)
            // ->fetchsql(true)
            ->find($id);
        $statements['_balance'] = array();
        $statements['xlsname']  = md5($statements['contactNumber'].date('YmdHis').mt_rand(000000,999999)).'.xls';
        $statements['path']     = ROOT_PATH.'public/create/xls/tmp/'.date("Ymd").'/';
        $balance = DB::name('contactBalance')
            ->where('merAccountDate',$statements['merAccountDate'])
            ->where('contactNumber',$statements['contactNumber'])
            ->select();
        foreach ($balance as $k => $v) {
            $statements['_balance'][] = $v;
        }
        $res = excelXls($statements,1);
    }

    public function todayOrder() {
        $name   = session('mob_user.nick');
        $this->assign('name',$name);
        return $this->fetch('todayOrder');
    }

    public function showorder() {
        $name   = session('mob_user.nick');
        $this->assign('name',$name);
        return $this->fetch('showOrder');
    }

    public function nextTodayOrders(){
        $post = input('post.');
        // 获取餐厅编号
        if(session('mob_user.is_contact')!=0){
            $contact_number = session('mob_user.contact_number');
        }else{
            $contact_number = '';
        }
        $where = [];
        $where['createTime'] = ['>=',strtotime(date('Y-m-d'))];
        // 获取当前显示的最小id
        if(isset($post['minOrder'])){
            $where['id'] = ['<',$post['minOrder']];
        }
        $order = DB::name('wxOrder')
            ->field('id,orderSN,orderStatus,createTime,moneyPaid,contactMemberName')
            ->where('contactNumber',$contact_number)
            ->where($where)
            ->where('payStatus','>=',1)
            ->where('isDelete',0)
            ->order('id desc')
            ->limit(5)
            ->select();
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
            // 查詢訂單菜品
            $orderFoods = DB::name('wxOrderGoods')->field('id,orderSN,goodsName,goodsPrice,num,goodsThumbnailUrl,goodsType,groupNumber')->where('contactNumber',$contact_number)->where('orderSN','in',$orderSn)->order('id asc')->select();
            // 把菜品按訂單放入數組
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
            foreach ($orderFoods as $k => $v) {
                $orderFoodslist[$v['orderSN']][] = $v;
            }
            // 把菜品放入新訂單集合中
            foreach ($order as $key => $val) {
                $orderlist[$key] = $val;
                $orderlist[$key]['createTime'] = date('Y-m-d H:i',$val['createTime']);
                if(isset($orderFoodslist[$val['orderSN']])){
                    $orderlist[$key]['_goods'] = $orderFoodslist[$val['orderSN']];
                }
            }
            $this->success($orderlist);
        }else{
            $this->error('沒有更多訂單');
        }
    }


}