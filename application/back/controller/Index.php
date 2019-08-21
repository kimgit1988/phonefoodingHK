<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
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

class Index extends AdminBase {
    public function index() {
        if (!session('?ext_user')) {
            return $this->Redirect('back/login/index');
        }
        if(session('ext_user.is_contact')==0){
            $contact = array('logoUrl'=>'','bgImageUrl'=>'','name'=>'管理员','remark'=>'');
        }else{
            $contact_number = session('ext_user.contact_number');
            $contact = DB::name('contact')->where('number',$contact_number)->where('isDelete',0)->find();
        }
        $todaytime = strtotime(date('Y-m-d'));
        $monthtime = strtotime(date('Y-m-1 00:00:00'));
        $count['contact'] = DB::name('contact')->where('disable',1)->where('isDelete',0)->count();
        $count['allNum'] = DB::name('WxOrder')->where('payStatus',1)->where('isDelete',0)->count();
        $count['allPrice'] = DB::name('WxOrder')->where('payStatus',1)->where('isDelete',0)->sum('moneyPaid');
        $count['monthNum'] = DB::name('WxOrder')->where('createTime','>=',$monthtime)->where('payStatus',1)->where('isDelete',0)->count();
        $count['monthPrice'] = DB::name('WxOrder')->where('createTime','>=',$monthtime)->where('payStatus',1)->where('isDelete',0)->sum('moneyPaid');
        $count['todayNum'] = DB::name('WxOrder')->where('createTime','>=',$todaytime)->where('payStatus',1)->where('isDelete',0)->count();
        $count['todayPrice'] = DB::name('WxOrder')->where('createTime','>=',$todaytime)->where('payStatus',1)->where('isDelete',0)->sum('moneyPaid');
        $this->assign('count',$count);
        $this->assign('contact',$contact);
        return $this->fetch('index');
    }

    //个人信息
    public function profile() {
        $zid = session('ext_user.zid');
        $userModel = Loader::model('User');
        $userRow = $userModel::row($zid);
		
        $this->assign('userRow', $userRow);
        return $this->fetch();
    }
     /**
     * 编辑个人信息
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-16
     * @return   [type]   [description]
     */
    
    public function proedit() {
        $zid = session('ext_user.zid');
        $userModel = Loader::model('User');
        $userRow = $userModel::row($zid);
        $this->assign('userRow', $userRow);
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $params['zid'] = $zid;
            $file = request()->file('images');
            // 调用上传方法
            $upload = uploadPic($file,'head');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($upload['code']==1){
                $params['head'] = $upload['msg'];
            }else{
                return $this->error($upload['msg']);
            }
            
            //引入app\common\validate,里面有很多字段的验证规则！但是我选这个proedit场景来验证，每个场景自定义验证不同的字段！
            if (loader::validate('User')->scene('proedit')->check($params) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            //实例化对象，然后自定义封装方法userrow。具体看源码。
            if (Loader::model('User')->userrow($params) === false) {
                return $this->error(loader::model('User')->getError());
            }
              Loader::model('SystemLog')->record("个人信息修改:[{$zid}]");
            return $this->success('个人信息修改成功', Url::build('back/index/index'));
        }
        return $this->fetch();
    }

  //没有权限
    public function auth() {
        $this->error("您没有权限,请和管理员联系吧！");
    }
    /**
     * 注销登录
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-16
     * @return   [type]    [description]
     */
    public function logout() {
    	$zid = session('ext_user.zid');
        Db::name('system_log')->insert(['remark' => "退出登录:[{$zid}]", 'op_time'=>time()]);
    	//Loader::model('SystemLog')->record("退出登录:[{$zid}]");
        Session::clear();
        return $this->success('注销成功！', 'back/login/index');
    }

    public function newOrders(){
        $request = Request::instance();
        $NewId = $request->param('NewId');
        if(session('ext_user.is_contact')==0){
            $Order = Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>['>',$NewId]])->order('id desc')->select();
        }else{
            $contact_number = session('ext_user.contact_number');
            $Order = Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>['>',$NewId]])->where('contactNumber',$contact_number)->order('id desc')->select();
        }
        // 有新订单
        if(!empty($Order)){
            // 获取最新订单id
            $return['NewId'] = $Order[0]['id'];
            $return['Order'] = forTableStr($Order);
            return $this->success($return);
        }else{
            $return = '没有新订单';
            return $this->error($return);
        }
        
    }

    public function analogorder(){
        // 取15间有效餐厅进行模拟
        $contact = DB::name('contact')->field('id,number,name,logoUrl,bgImageUrl')->where('disable',1)->where('isDelete',0)->limit(2)->order('id asc')->select();
        $paymethod = DB::name('payMethod')->field('id,name')->where('disable',1)->where('isDelete',0)->order('id asc')->select();
        // 支付渠道数
        $paynumber = count($paymethod);
        // 设置模拟开始时间
        $start = '2018-08-19';
        // 设置模拟天数
        $day = 5;
        // 设置订单总数(数量越大速度越慢)
        $number = 100;
        // 开始日期时间戳化
        $otime = strtotime($start);
        // 计算订单间隔
        $interval = ceil($day*86400/$number);

        // 开始循环
        for ($i=0; $i < $number ; $i++) { 
            
            // 随机选择餐厅
            $c_rand = mt_rand(0,1);
            // 随机支付渠道
            $p_rand = mt_rand(0,$paynumber-1);
            // 设置随机间隔差值使间隔略有差异更接近真实数据+值小于减值尽量确保订单不会超出预期时间
            $ointerval = mt_rand($interval-5,$interval+4);
            $otime = $otime + $ointerval;
            $ordertime = date('Y-m-d H:i:s',$otime);
            $order['orderSN'] = date('YmdHis',$otime).rand(1000,9999);
            $order['userId'] = 'testorder';
            $order['orderStatus'] = 2;
            $order['payStatus'] = 1;
            $order['payType'] = 9999;
            $order['payName'] = '测试方式';
            $order['goodsAmount'] = mt_rand(1,8000)/100;
            $order['moneyPaid'] = $order['goodsAmount'];
            $order['payMethodId'] = $paymethod[$p_rand]['id'];
            $order['payMethodName'] = $paymethod[$p_rand]['name'];
            $order['createTime'] = $otime;
            $order['payTime'] = $otime+2;
            $order['contactNumber'] = $contact[$c_rand]['number'];
            $order['contactName'] = $contact[$c_rand]['name'];
            $order['contactLogoUrl'] = $contact[$c_rand]['logoUrl'];
            $order['contactMemberNumber'] = $contact[$c_rand]['id'].'_1';
            $order['contactMemberName'] = '1号';
            $order['userType'] = 9999;
            Db::name('wx_order')->insert($order);
        }

    }

    public function nopay(){
        // 起始id
        $start = 1;
        // 修改条数
        $number = 9;
        // 开始叠加
        $id = $start;
        $order['orderStatus'] = 1;
        $order['payStatus'] = 0;
        for ($i=0; $i < $number ; $i++) { 
            // 间隔
            $interval = mt_rand(1,10);
            $id = $id + $interval;
            $res = Db::name('wx_order')->where('id',$id)->update($order);
        }
        var_dump($res);
    }

}