<?php
namespace app\court\controller;
use think\Db;
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
        $session = session('court_user');;
        if(!empty($session)){
            $this->redirect('index/index'); 
        }
        return $this->fetch();
    }

    public function register() {
        if( Request::instance()->isPost() ) {
            $post = input('post.');
            $contact = array(
                'name'=>$post['contact'],
                'number'=>$post['number'],
                'cCategory'=>$post['categoryId'],
                'disable'=>1,
                'cCategoryName'=>$post['categoryName'],
                'logoUrl'=>$post['pic_path'],
                'bgImageUrl'=>$post['img_path'],
                'remark'=>$post['detail'],
                'linkMans'=>$post['phone'],
            );
            $user = array(
                'name'=>$post['username'],
                'nick'=>$post['person'],
                'password'=>$post['password'],
                'repassword'=>$post['repassword'],
                'is_contact'=>1,
                'contact_number'=>$post['number'],
                'email'=>$post['mail'],
                'status'=>1,
                'create_time'=>time(),
            );
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
            $category = DB::name('category')->where('typeNumber','customertype')->where('isDelete',0)->select();
            $this->assign('category',$category);
            return $this->fetch();
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
        $check =User::courtlogin($name, $pass,'court_user');
        if ($check==0) {
            return $this->success('登陆成功', Url::build('index/index'));
        } else if ($check==1){
            return $this->error("用户名不存在！");
        } else if ($check==2){
            return $this->error("用户被禁用！");
        } else if ($check==3){
            return $this->error("密码错误！");
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

    //验证码类
    function captcha_img($id = "") {
        return '<img src="'.captcha_src($id).'"alt="captcha" />';
    }

    public function reset(){
        if( Request::instance()->isPost() ) {
            $param = input('param.');
            if(!empty($param['email'])&&!empty($param['username'])){
                $user = DB::name('user')->where('is_contact','4')->where('name',$param['username'])->find();
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
                            $this->error('账号邮箱不能发送,请聯繫管理員!');

                        }
                    }else{
                        return $this->error('用戶名郵箱不一致');
                    }
                }else{
                    return $this->error('用戶名錯誤');
                }
            }else if(empty($param['username'])){
                return $this->error('用戶名不能為空');
            }else{
                return $this->error('郵箱不能為空');
            }
        }else{
            return $this->fetch();
        }
    }
    
}