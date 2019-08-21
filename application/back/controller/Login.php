<?php
namespace app\back\controller;
use think\Db;
use think\View;
use think\Input;
use think\Loader;
use think\Controller;
use app\common\model\User;
use Captcha;//extend文件夹。验证码类，你也可以在线composer
use think\Url;
class Login extends controller {
    public function index() {
        $session = session('ext_user');;
        if(!empty($session)){
            $this->redirect('index/index'); 
        }
        return $this->fetch();
    }
  /**
     * [login description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-19
     */
    public function logining() {
        $name = input('request.name');
        $password = input('request.password');
        $data = input('request.captcha');
        if (!captcha_check($data)) {
            //验证失败
            return $this->error("验证码错误");
        }
        $check =User::login($name, $password,'ext_user');
        if ($check==0) {
            $user = loader::model("user")->where('is_contact',0)->where(['name'=>$name])->find();
            Db::name('system_log')->insert(['remark' => "登陆成功:[{$name}]",'user_id'=>$user->zid ,'op_time'=>time()]);
        	//Loader::model('SystemLog')->record("登陆成功:[{$name}]");
            return $this->success('登陆成功', Url::build('back/Index/index'));
        } else if ($check==1){
            return $this->error("用户名不存在！");
        } else if ($check==2){
            return $this->error("用户账号被锁定！");
        } else if ($check==3){
            return $this->error("密码错误！");
        }
          
    }
        //验证码类
        function captcha_img($id = "") {
            return '<img src="'.captcha_src($id).'"alt="captcha" />';
        }
    

}