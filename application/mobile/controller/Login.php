<?php
namespace app\mobile\controller;
use think\Db;
use think\Session;
use think\Cookie;
use think\View;
use think\Input;
use think\Loader;
use think\Request;//请求
use think\Controller;
use app\common\model\User;
use Captcha;//extend文件夹。验证码类，你也可以在线composer
use think\Url;
class Login extends controller {
    public function index() {
        $session = session('mob_user');
        $brand = input('param.brand');
        if(!empty($brand)) {
            Session::set('mchbrand',$brand);
        }
        if(!empty($session)){
            $this->redirect('order/index'); 
        }
        return $this->fetch();
    }

    public function register() {
        if( Request::instance()->isPost() ) {
            $post = input('post.');
            $default_config = getDefaultConfig();
            $contact = array(
                'name'=>trim($post['contact']),
                'number'=>trim($post['number']),
                'cCategory'=>trim($post['categoryId']),
                'disable'=>0,
                'cCategoryName'=>trim($post['categoryName']),
                'logoUrl'=>trim($post['pic_path']),
                'bgImageUrl'=>trim($post['img_path']),
                'remark'=>trim($post['detail']),
                'linkMans'=>trim($post['phone']),
                'member'=>trim($post['member']),
                //'contactType'=>trim($post['typeId']),
                'contactType'=>1,
                'laterPay'=>$post['paytype']==1?1:0,
                'bank_id'=>trim($post['bankId']),
                'bank_name'=>trim($post['bankName']),
                'account_number'=>trim($post['bankNumber']),
                'account_name'=>trim($post['bankUser']),
                'market'=>!empty(trim($post['market']))?trim($post['market']):0,
                'rate'=>trim($default_config['payments_contact_default_rate']),
                'cycle'=>trim($default_config['payments_contact_default_cycle']),
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
                'ctime'=>time(),
            );
            $user = array(
                'name'=>trim($post['username']),
                'nick'=>trim($post['person']),
                'password'=>trim($post['password']),
                'repassword'=>trim($post['repassword']),
                'is_contact'=>1,
                'uid'=>2,
                'contact_number'=>trim($post['number']),
                'email'=>trim($post['mail']),
                'status'=>0,
                'create_time'=>time(),
                'update_time'=>time(),
            );
            if((empty($user['password'])||empty($user['repassword']))){
                $this->error('請輸入密碼');die;
            }elseif($user['password']!=$user['repassword']){
                $this->error('兩次輸入密碼不一致');die;
            }
            if(!empty($contact['logoUrl'])){
                $isbase['logoUrl'] = is_base64_picture($contact['logoUrl']);
            }else{
                $this->error('請上傳頭像');die;
            }
            if(!empty($contact['bgImageUrl'])){
                $isbase['bgImageUrl'] = is_base64_picture($contact['bgImageUrl']);
            }else{
                $this->error('請上傳背景圖');die;
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
            if (loader::validate('Contact')->scene('add')->check($contact) === false) {
                return $this->error(loader::validate('Contact')->getError());
            }
            if (loader::validate('User')->scene('register')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            $user['password']   = md5($post['password']);
            $user['repassword'] = md5($post['repassword']);
            $res = 1;
            Db::startTrans();
            try{
                Db::name('User')->strict(false)->insert($user);
                Db::name('Contact')->strict(false)->insert($contact);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res){
                return $this->success('註冊成功',url('login/index'));
            }else{
                return $this->error('註冊失敗');
            }
        }else{
            $market = input('market');
            $market = empty($market)?'':$market;
            $type = config('contact_type');
            $category = DB::name('category')->where('typeNumber','customertype')->where('isDelete',0)->select();
            $bank = DB::name('bank')->select();
            $this->assign('bank',$bank);
            $this->assign('type',$type);
            $this->assign('market',$market);
            $this->assign('category',$category);
            return $this->fetch();
        }
    }

    public function review(){
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
            $isbase = array();
            if(!empty($contact['logoUrl'])){
                $isbase['logoUrl'] = is_base64_picture($contact['logoUrl']);
            }else{
                $this->error('請上傳頭像');die;
            }
            if(!empty($contact['bgImageUrl'])){
                $isbase['bgImageUrl'] = is_base64_picture($contact['bgImageUrl']);
            }else{
                $this->error('請上傳背景圖');die;
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
                return $this->success('提交成功',url('login/index'));
            }else{
                return $this->error('提交失敗');
            }
        }else{
            $userid = input('userid');
            $user    = DB::name('User')->where('zid',$userid)->find();
            $contact = DB::name('Contact')->where('number',$user['contact_number'])->find();
            $type = config('contact_type');
            if($contact['disable']==0){
                $this->assign('type',$type);
                $this->assign('user',$user);
                $this->assign('contact',$contact);
                return $this->fetch();
            }else if($contact['disable']==2){
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

    /**
     * [login description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-19
     */
    public function login() {
        $name = input('request.user');
        $pass = input('request.pass');
        $check =User::mobileLogin($name, $pass,'mob_user');
        if ($check['code']==1) {
            return $this->success('登錄成功', Url::build('order/index'));
        } else if ($check['code']==2){
            return $this->error("用戶名稱不存在！");
        } else if ($check['code']==0){
            if($check['number']==1){
                return ['msg'=>'用戶審核未通過！','code'=>2,'url'=>Url::build('login/review',['userid'=>$check['userid']])];
            }else{
                return $this->error("用戶審核未通過！");
            }
        } else if ($check['code']==3){
            return $this->error("密碼錯誤！");
        }
          
    }

    public function printerlogin() {
        $code = input('code');
        $printer = DB::name('printer')->where('deviceNumber',$code)->where('disable',1)->where('isDelete',0)->find();
        $check = User::printerLogin($printer,'mob_user');
        // User::mobileLogin($name, $pass,'mob_user');
        if ($check['code']==1) {
            return $this->success('登陆成功', Url::build('order/index'));
        } else if ($check['code']==2){
            return $this->error("用戶名稱不存在！");
        } else if ($check['code']==0){
            if($check['number']==1){
                return ['msg'=>'用户審核中！','code'=>2,'url'=>Url::build('login/review',['userid'=>$check['userid']])];
            }else{
                return $this->error("用户審核中！");
            }
        }
          
    }

    // 圖片上傳
    public function uploadImg(){
        $request = Request::instance();
        $type = input('type');
        $file = request()->file('image');
        if($file){
            if($type=="logo"){
                // 调用上传方法 保存原图
                $uploads = uploadPic($file,'uploads/head');
            }else if($type=="img"){
                $uploads = uploadPic($file,'uploads/big');
            }
            
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($uploads['code']==1){
                return  $this->success($uploads['msg']);
            }else{
                return $this->error($uploads['msg']);
            }
        }
    }

    public function reset(){
        if( Request::instance()->isPost() ) {
            $param = input('param.');
            if(!empty($param['email'])&&!empty($param['username'])){
                $user = DB::name('user')->where('is_contact','1|2')->where('name',$param['username'])->find();
                if(!empty($user)){
                    if($user['email']==$param['email']){
                        $result = $this->validate(['email' => $user['email']],['email'   => 'email']);
                        if(true == $result){
                            $newpass = mt_rand(1000,9999);
                            $send = sendEmail('', $user['email'], $user['nick'], '豐富點-您的賬號'.$user['name'].'密碼重置', '新的臨時密碼為'.$newpass);
                            $res = DB::name('user')->where('zid',$user['zid'])->update(['password'=>md5($newpass)]);
                            if($res){
                                return $this->success('臨時密碼已發送至您的郵箱',url('login/index'));
                            }else{
                                return $this->success('臨時密碼重置失敗');
                            }
                        }else{
                            // 验证失败 输出错误信息
                            $this->error('賬號郵箱不能發送,请聯繫管理員!');

                        }
                    }else{
                        return $this->error('用戶名稱郵箱不一致');
                    }
                }else{
                    return $this->error('用戶名稱錯誤');
                }
            }else if(empty($param['username'])){
                return $this->error('用戶名稱不能為空');
            }else{
                return $this->error('郵箱不能為空');
            }
        }else{
            return $this->fetch();
        }
    }

    public function postEamil(){
        
    }

    public function ted(){
        $address = input('post.address');
        $mapUrl  = config('QQLbs.Url');
        $mapKey  = config('QQLbs.Key');
        if(!empty($address)){
            $url = $mapUrl.'?key='.$mapKey.'&address='.$address;
            $res = curl($url);
            $res = json_decode($res);
        }else{
            $this->error(['message'=>'請輸入地址','status'=>'-1']);
        }
        return $this->success($res);
    }

    //验证码类
    function captcha_img($id = "") {
        return '<img src="'.captcha_src($id).'"alt="captcha" />';
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
            default:
                break;
        }
        return ['msg'=>cookie::get('think_var'),'code'=>1];
    }

}