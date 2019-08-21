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

class More extends Base {
    public function index() {
        if( Request::instance()->isPost() ) {
            $post = input('post.');
            $zid = session('mar_user.zid');
            $default_config = getDefaultConfig();
            $contact = array(
                'name'=>trim($post['contact']),
                'number'=>trim($post['number']),
                'cCategory'=>trim($post['categoryId']),
                'disable'=>0,
                'market'=>trim($zid),
                'cCategoryName'=>trim($post['categoryName']),
                'logoUrl'=>trim($post['pic_path']),
                'bgImageUrl'=>trim($post['img_path']),
                'remark'=>trim($post['detail']),
                'linkMans'=>trim($post['phone']),
                'member'=>trim($post['member']),
                'contactType'=>trim($post['typeId']),
                'bank_id'=>trim($post['bankId']),
                'bank_name'=>trim($post['bankName']),
                'account_number'=>trim($post['bankNumber']),
                'account_name'=>trim($post['bankUser']),
                'rate'=>$default_config['payments_contact_default_rate'],
                'cycle'=>$default_config['payments_contact_default_cycle'],
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
                'ctime'=>time(),
            );
            $user = array(
                'uid'=>2,
                'name'=>trim($post['username']),
                'nick'=>trim($post['person']),
                'password'=>trim($post['password']),
                'repassword'=>trim($post['repassword']),
                'is_contact'=>1,
                'contact_number'=>trim($post['number']),
                'email'=>trim($post['mail']),
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
                return $this->success('添加成功',url('index/contactlist',['action'=>0]));
            }else{
                return $this->error('添加失敗');
            }
        }else{
            $type = config('contact_type');
            $category = DB::name('category')->where('typeNumber','customertype')->where('isDelete',0)->select();
            $bank = DB::name('bank')->select();
            $this->assign('bank',$bank);
            $this->assign('type',$type);
            $this->assign('category',$category);
            return $this->fetch();
        }
    }

    public function court() {
        if( Request::instance()->isPost() ) {
            $post = input('post.');
            $zid = session('mar_user.zid');
            $default_config = getDefaultConfig();
            $contact = array(
                'name'=>trim($post['contact']),
                'number'=>trim($post['number']),
                'cCategory'=>trim($post['categoryId']),
                'disable'=>0,
                'market'=>trim($zid),
                'cCategoryName'=>trim($post['categoryName']),
                'logoUrl'=>trim($post['pic_path']),
                'bgImageUrl'=>trim($post['img_path']),
                'remark'=>trim($post['detail']),
                'linkMans'=>trim($post['phone']),
                'member'=>0,
                'contactType'=>trim($post['typeId']),
                'bank_id'=>trim($post['bankId']),
                'bank_name'=>trim($post['bankName']),
                'account_number'=>trim($post['bankNumber']),
                'account_name'=>trim($post['bankUser']),
                'rate'=>$default_config['payments_contact_default_rate'],
                'cycle'=>$default_config['payments_contact_default_cycle'],
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
                'ctime'=>time(),
            );
            $user = array(
                'uid'=>7,
                'name'=>trim($post['username']),
                'nick'=>trim($post['person']),
                'password'=>trim($post['password']),
                'repassword'=>trim($post['repassword']),
                'is_contact'=>4,
                'contact_number'=>trim($post['number']),
                'email'=>trim($post['mail']),
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
            if (loader::validate('FoodCourt')->scene('add')->check($contact) === false) {
                return $this->error(loader::validate('FoodCourt')->getError());
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
                Db::name('FoodCourt')->strict(false)->insert($contact);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res){
                return $this->success('添加成功',url('index/contactlist',['action'=>0]));
            }else{
                return $this->error('添加失敗');
            }
        }else{
            $type = config('contact_type');
            $category = DB::name('category')->where('typeNumber','customertype')->where('isDelete',0)->select();
            $bank = DB::name('bank')->select();
            $this->assign('bank',$bank);
            $this->assign('type',$type);
            $this->assign('category',$category);
            return $this->fetch();
        }
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
            $this->error(['message'=>'请输入地址','status'=>'-1']);
        }
        return $this->success($res);
    }


}