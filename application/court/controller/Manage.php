<?php
namespace app\court\controller;
use app\court\controller\Base;
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
        $session = session('court_user');
        return $this->fetch('index');
    }

    public function repass(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $zid = session('court_user.zid');
            $contact_number = session('court_user.contact_number');
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
            $zid = session('court_user.zid');
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
                Session::set('court_user.nick',$post['nick']);
                Session::set('court_user.email',$post['mail']);
                Session::set('court_user.head',$post['pic_path']);
                return $this->success('修改資料成功', Url::build('manage/index'));
            }else{
                return $this->success('修改資料失敗');
            }
        }else{
            $zid = session('court_user.zid');
            $res = Db::name('User')->where('zid',$zid)->find();
            $this->assign('user',$res);
            return $this->fetch();
        }
    }

    public function court(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $courtId = session('court_user.courtId');
            $court = array(
                'id'=>$courtId,
                'name'=>trim($post['name']),
                'logoUrl'=>trim($post['pic_path']),
                'bgImageUrl'=>trim($post['img_path']),
                'linkMans'=>trim($post['phone']),
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
            );
            // true为base64图片需处理 false为本地图片直接保存
            if(!empty($court['logoUrl'])){
                $isbase['logoUrl'] = is_base64_picture($court['logoUrl']);
            }
            if(!empty($court['bgImageUrl'])){
                $isbase['bgImageUrl'] = is_base64_picture($court['bgImageUrl']);
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
            if (loader::validate('FoodCourt')->scene('edit')->check($court) === false) {
                return $this->error(loader::validate('FoodCourt')->getError());
            }
            $res = Db::name('FoodCourt')->where('id',$courtId)->update($court);
            if($res!==false){
                return $this->success('美食廣場修改成功',Url::build('manage/index'));
            }else{
                return $this->error('美食廣場修改失敗');
            }
        }else{
            $courtId = session('court_user.courtId');
            $res = Db::name('FoodCourt')->where('id',$courtId)->find();
            $this->assign('court',$res);
            return $this->fetch();
        }
    }

    // 退出
    public function loginout() {
        $zid = session('court_user.zid');
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

    public function getKey(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $courtId = session('court_user.courtId');
            $court = array(
                'secretKey'=>isset($post['key'])?$post['key']:'',
            );
            if (loader::validate('Contact')->scene('secretKey')->check($court) === false) {
                return $this->error(loader::validate('Contact')->getError());
            }
            $res = Db::name('FoodCourt')->where('id',$courtId)->update($court);
            if($res!==false){
                return $this->success('修改成功',Url::build('manage/index'));
            }else{
                return $this->error('修改失敗');
            }
        }else{
            $session = session('court_user');
            // 商家的编号
            $courtId = session('court_user.courtId');
            $court = DB::name('FoodCourt')
                ->field('secretKey,number')
                ->where('id',$courtId)
                ->where('isDelete',0)
                ->find();
            $this->assign('court',$court);
            return $this->fetch('getKey');
        }
    }


}