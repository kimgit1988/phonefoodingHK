<?php
namespace app\market\controller;
use app\market\controller\Base;
use think\View;//视图
use think\Controller;//控制器
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库

class Manage extends Base {
    public function index() {
        $session = session('mar_user');
        return $this->fetch('index');
    }

    public function qrcode() {
        if( Request::instance()->isPost() ) {
            $userId = session('mar_user.zid');
            $suffix = url('mobile/login/register',['market'=>$userId]);
            $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
            $res = add_wx_web_qrcode($url,'','market/qrcode');
            if($res['code']==1){
                $return = DB::name('user')->where('zid',$userId)->update(['qrcode'=>$res['msg']]);
                if($return!==false){
                    return ['msg'=>'生成二維碼成功','pic'=>$res['msg'],'code'=>1];
                }else{
                    return ['msg'=>'生成二維碼失敗','code'=>0];
                }
            }else{
                return ['msg'=>'生成二維碼失敗','code'=>0];
            }
        }else{
            $session = session('mar_user');
            $user = DB::name('user')->where('zid',$session['zid'])->find();
            $this->assign('user',$user);
            return $this->fetch(); 
        }
    }

    public function repass(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $zid = session('mar_user.zid');
            if(session('mar_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mar_user.contact_number');
            }
            $user = array(
                'password'=>$post['password'],
                'repassword'=>$post['repassword'],
            );
            if (loader::validate('User')->scene('repass')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            $user['password']   = md5($post['password']);
            $user['repassword'] = md5($post['repassword']);
            $res = Db::name('User')->where('zid',$zid)->strict(false)->update($user);
            if($res!==false){
                return $this->success('修改密碼成功', Url::build('manage/index'));
            }else{
                return $this->success('修改密碼失敗');
            }
        }else{
            return $this->fetch();
        }
    }

    public function reset(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $zid = session('mar_user.zid');
            $user = array(
                'nick'=>$post['nick'],
                'email'=>$post['mail'],
                'head'=>$post['pic_path'],
            );
            if(!empty($user['head'])){
                $isbase['head'] = is_base64_picture($user['head']);
            }else{
                $this->error('请上传头像');die;
            }
            if($isbase['head']){
                // base64转图片
                $img = save_base_img($post['pic_path'],'uploads/head');
                // 图片地址保存
                $user['head'] = $img['path'];
            }
            $res = Db::name('User')->where('zid',$zid)->update($user);
            if($res!==false){
                Session::set('mar_user.nick',$post['nick']);
                Session::set('mar_user.email',$post['mail']);
                Session::set('mar_user.head',$post['pic_path']);
                return $this->success('修改資料成功', Url::build('manage/index'));
            }else{
                return $this->success('修改資料失敗');
            }
        }else{
            $zid = session('mar_user.zid');
            $res = Db::name('User')->where('zid',$zid)->find();
            $this->assign('user',$res);
            return $this->fetch();
        }
    }

    // 退出
    public function loginout() {
    	$zid = session('mar_user.zid');
        Session::clear();
        return $this->success('注销成功！', 'login/index');
    }

    // 圖片上傳
    public function uploadImg(){
        $request = Request::instance();
        $file = request()->file('image');
        if($file){
            // 调用上传方法 保存原图
            $uploads = uploadPic($file,'uploads/head');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($uploads['code']==1){
                return  $this->success($uploads['msg']);
            }else{
                return $this->error($uploads['msg']);
            }
        }
    }

}