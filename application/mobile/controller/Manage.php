<?php
namespace app\mobile\controller;
use app\mobile\controller\Base;
use think\View;//视图
use think\Controller;//控制器
use think\Validate;
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库
use app\common\model\WxOrder;

class Manage extends Base {
    public function index() {
        $session = session('mob_user');
        if(session('mob_user.is_contact')==1||session('mob_user.is_contact')==2){
            $contact_number = session('mob_user.contact_number');
            $member  = DB::name('contactMember')
                ->field('id,name,number,cCategory,cCategoryName')
                ->where('contactNumber',$contact_number)
                ->where('isDelete',0)
                ->count();
            $contact = DB::name('Contact')
                ->field('name,logoUrl,bgImageUrl')
                ->where('number',$contact_number)
                ->where('isDelete',0)
                ->find();
        }else{
            $member  = 0;
            $contact_number = '';
            $contact = DB::name('Contact')
                ->field('name,logoUrl,bgImageUrl')
                ->where('number',$contact_number)
                ->where('isDelete',0)
                ->find();
        }
        $id = session('mob_user.zid');
        $user = DB::name('user')->field('nick,head')->where('zid',$id)->find();
        $this->assign('head',$user['head']);
        $this->assign('member',$member);
        $this->assign('contact',$contact);
        if(session('mob_user.is_contact')==2){
            return $this->fetch('indexStaff');
        }else{
            return $this->fetch('index');
        }
    }

    //廣告設置
    public function bannerSet(){
        return $this->fetch();
    }


    //餐桌列表
    public function membercategory(){
        if(session('mob_user.is_contact')==0){
            $member_category = DB::name('category')->where('isDelete',0)->where('status',1)->where('typeNumber','contactMember')->select();
        }else{
            // 商家可以看到自己创建的分类(或管理员建立的)
            $contact_number = session('mob_user.contact_number');
            $member_category = DB::name('category')->where('isDelete',0)->where('status',1)->where('typeNumber','contactMember')->where('contactNumber',$contact_number)->select();
        }
        $this->assign('category',$member_category);
        return $this->fetch();
    }
    //餐桌列表
    public function addmembercate(){
        if( Request::instance()->isPost() ) {
            $name = input('param.name');
            if(session('mob_user.is_contact')!=0){
                $contact_number = session('mob_user.contact_number');
                $save = ['name'=>$name,'level'=>1,'typeNumber'=>'contactMember','status'=>1,'isDelete'=>0,'contactNumber'=>$contact_number];
            }else{
                $save = ['name'=>$name,'level'=>1,'typeNumber'=>'contactMember','status'=>1,'isDelete'=>0];
            }
            $res = DB::name('category')->insert($save);
            if($res){
                $this->success('添加成功', Url::build('manage/membercategory'));
            }else{
                $this->success('添加失敗');
            }
        }else{
            return $this->fetch();
        }

    }
    //餐桌列表
    public function editmembercate(){
        if( Request::instance()->isPost() ) {
            $id   = input('param.id');
            $name = input('param.name');
            if(session('mob_user.is_contact')!=0){
                $contact_number = session('mob_user.contact_number');
                $save = ['name'=>$name,'level'=>1,'typeNumber'=>'contactMember','status'=>1,'isDelete'=>0,'contactNumber'=>$contact_number];
            }else{
                $save = ['name'=>$name,'level'=>1,'typeNumber'=>'contactMember','status'=>1,'isDelete'=>0];
            }
            $res = DB::name('category')->where('id',$id)->update($save);
            if($res!==false){
                $this->success('修改成功', Url::build('manage/membercategory'));
            }else{
                $this->success('修改失敗');
            }
        }else{
            $id   = input('param.id');
            if(session('mob_user.is_contact')!=0){
                $contact_number = session('mob_user.contact_number');
                $res = DB::name('category')->where('contactNumber',$contact_number)->where('id',$id)->find();
            }else{
                $res = DB::name('category')->where('id',$id)->find();
            }
            $this->assign('category',$res);
            return $this->fetch();
        }

    }

    /**
    进入菜品分类页面（店员和店长只能查看属于自己餐厅的分类内容）
     */
    public function goodsCategory(){

        if(session('mob_user.is_contact')==0){
            $category = DB::name('category')->where('isDelete',0)->where('status',1)->where('typeNumber','trade')->order('id desc')->select();
        }else{
            // 商家可以看到自己创建的分类(或管理员建立的)
            $contact_number = session('mob_user.contact_number');
            $category = DB::name('category')->where('isDelete',0)->where('status',1)->where('typeNumber','trade')->order('id desc')->where('contactNumber',$contact_number)->order('ordnum asc')->select();
        }
        $this->assign('category',$category);
        return $this->fetch('goodsCategory');

    }	// public function getGoodsCategory()

    /**
    新增菜品分类
     */
    public function addGoodsCategory(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            if(empty($post['name'])){
                $this->error('請輸入分類名');
            }else if(empty($post['starttime'])||empty($post['endtime'])){
                $this->error('請選擇有效期');
            }
            $contact_number = session('mob_user.contact_number');
            $save = [
                'name'=>$post['name'],
                'name_en'=>$post['name_en'],
                'name_other'=>$post['name_other'],
                'ordnum'=>empty($post['ordnum'])?100:$post['ordnum'],
                'startTime'=>$post['starttime'],
                'endTime'=>$post['endtime'],
                'level'=>1,
                'typeNumber'=>'trade',
                'status'=>1,
                'isDelete'=>0,
                'contactNumber'=>$contact_number,
            ];
            $res = DB::name('category')->insert($save);
            if($res){
                $this->success('添加成功', Url::build('manage/goodsCategory'));
            }else{
                $this->error('添加失敗');
            }
        }else{
            return $this->fetch('addGoodsCategory');
        }

    }	// public function addGoodsCategory()

    /**
    编辑菜品分类
     */
    public function editGoodsCategory(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $id   = $post['id'];
            if(empty($post['name'])){
                $this->error('請輸入分類名');
            }else if(empty($post['starttime'])||empty($post['endtime'])){
                $this->error('請選擇有效期');
            }
            $save = [
                'name'=>$post['name'],
                'name_en'=>$post['name_en'],
                'name_other'=>$post['name_other'],
                'ordnum'=>empty($post['ordnum'])?100:$post['ordnum'],
                'startTime'=>$post['starttime'],
                'endTime'=>$post['endtime'],
                'level'=>1,
                'typeNumber'=>'trade',
                'status'=>1,
                'isDelete'=>0,
            ];
            $res = DB::name('category')->where('id',$id)->update($save);
            if($res!==false){
                $this->success('修改成功', Url::build('manage/goodsCategory'));
            }else{
                $this->error('修改失敗');
            }
        }else{
            $id   = input('param.id');
            if(session('mob_user.is_contact')!=0){
                $contact_number = session('mob_user.contact_number');
                $res = DB::name('category')->where('contactNumber',$contact_number)->where('id',$id)->find();
            }else{
                $res = DB::name('category')->where('id',$id)->find();
            }
            $this->assign('category',$res);
            return $this->fetch('editGoodsCategory');
        }

    }	//public function editGoodsCategory()

    public function contact(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact = array(
                'name'=>trim($post['contact']),
                'cCategory'=>trim($post['categoryId']),
                'cCategoryName'=>trim($post['categoryName']),
                'logoUrl'=>trim($post['pic_path']),
                'bgImageUrl'=>trim($post['img_path']),
                'remark'=>trim($post['detail']),
                'linkMans'=>trim($post['phone']),
                'bank_id'=>trim($post['bankId']),
                'bank_name'=>trim($post['bankName']),
                'account_number'=>trim($post['bankNumber']),
                'account_name'=>trim($post['bankUser']),
                'address'=>trim($post['address']),
                'latitude'=>trim($post['Latitude']),
                'longitude'=>trim($post['Longitude']),
            );
            // true为base64图片需处理 false为本地图片直接保存
            if(!empty($contact['logoUrl'])){
                $isbase['logoUrl'] = is_base64_picture($contact['logoUrl']);
            }else{
                $this->error('請上傳頭像');die;
            }
            //if(!empty($contact['bgImageUrl'])){
            //    $isbase['bgImageUrl'] = is_base64_picture($contact['bgImageUrl']);
            //}else{
            //    //$this->error('請上傳背景圖');die;
            //}
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
            if(session('mob_user.is_contact')!=0){
                $contact_number = session('mob_user.contact_number');
            }
            if (loader::validate('Contact')->scene('contact')->check($contact) === false) {
                $this->error(loader::validate('Contact')->getError());
            }
            $res = Db::name('Contact')->where('number',$contact_number)->update($contact);
            if($res!==false){
                $this->success('餐廳修改成功',Url::build('manage/index'));
            }else{
                $this->error('餐廳修改失敗');
            }
        }else{
            // 商家的编号
            $contact_number = session('mob_user.contact_number');
            $category = DB::name('Category')
                ->where('typeNumber','customertype')
                ->where('isDelete',0)
                ->select();

            $contact = DB::name('Contact')
                ->field('name,logoUrl,bgImageUrl,linkMans,remark,cCategory,cCategoryName,contactType,bank_id,bank_name,account_number,account_name,rate,cycle,address,longitude,latitude')
                ->where('number',$contact_number)
                ->where('isDelete',0)
                ->find();
            $bank = DB::name('bank')->select();
            $type = config('contact_type');
            $this->assign('bank',$bank);
            $this->assign('type',$type);
            $this->assign('contact',$contact);
            $this->assign('category',$category);
            return $this->fetch();
        }
    }
    //餐桌列表
    public function member(){
        if(session('mob_user.is_contact')==0){
            $member = DB::name('contactMember')->where('isDelete',0)->select();
            $category = DB::name('Category')->where('typeNumber','customertype')->where('isDelete',0)->select();
        }else{
            // 商家可以看到自己创建的分类
            $contact_number = session('mob_user.contact_number');
            $member = DB::name('contactMember')->field('id,name,number,cCategory,cCategoryName')->where('contactNumber',$contact_number)->where('isDelete',0)->select();
            $category = DB::name('Category')->field('id,name')->where('typeNumber','contactMember')->where('contactNumber',$contact_number)->where('isDelete',0)->select();
        }
        $this->assign('member',$member);
        $this->assign('category',$category);
        return $this->fetch();
    }

    public function addmember(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }
            $member = array(
                'name'=>$post['name'],
                'number'=>$post['number'],
                'cCategory'=>$post['categoryId'],
                'cCategoryName'=>$post['categoryName'],
                'contactNumber'=>$contact_number,
            );
            $res = DB::name('contactMember')->insert($member);
            if($res!==false){
                return $this->success('餐檯添加成功', Url::build('manage/member'));
            }else{
                return $this->success('餐檯添加失敗');
            }
        }else{
            if(session('mob_user.is_contact')==0){
                $category = DB::name('Category')->where('typeNumber','customertype')->where('isDelete',0)->select();
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
                $category = DB::name('Category')->field('id,name')->where('typeNumber','contactMember')->where('contactNumber',$contact_number)->where('isDelete',0)->select();
            }
            $this->assign('category',$category);
            return $this->fetch();
        }
    }

    public function editmember(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $member = array(
                'name'=>$post['name'],
                'number'=>$post['number'],
                'cCategory'=>$post['categoryId'],
                'cCategoryName'=>$post['categoryName'],
            );
            $res = DB::name('contactMember')->where('id',$post['id'])->update($member);
            if($res!==false){
                $this->success('餐枱編輯成功', Url::build('manage/member'));
            }else{
                $this->success('餐枱編輯失敗');
            }
        }else{
            $id = input("id");
            if(empty($id)){
                $this->error('餐枱不存在');
            }
            if(session('mob_user.is_contact')==0){
                $member = DB::name('contactMember')->field('id,name,number,cCategory,cCategoryName')->where('isDelete',0)->where('id',$id)->find();
                $category = DB::name('Category')->where('typeNumber','customertype')->where('isDelete',0)->select();
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
                $member = DB::name('contactMember')->field('id,name,number,cCategory,cCategoryName')->where('contactNumber',$contact_number)->where('isDelete',0)->where('id',$id)->find();
                $category = DB::name('Category')->field('id,name')->where('typeNumber','contactMember')->where('contactNumber',$contact_number)->where('isDelete',0)->select();
            }
            $this->assign('member',$member);
            $this->assign('category',$category);
            return $this->fetch();
        }
    }

    //餐桌列表
    public function staff(){
        if(session('mob_user.is_contact')==0){
            $staff = DB::name('user')->where('is_contact',2)->select();
        }else{
            // 商家可以看到自己的员工
            $contact_number = session('mob_user.contact_number');
            $staff = DB::name('user')->where('contact_number',$contact_number)->where('is_contact',2)->select();
        }
        $this->assign('user',$staff);
        return $this->fetch();
    }

    public function addstaff(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }
            $user = array(
                'uid'=>5,
                'name'=>$post['name'],
                'nick'=>$post['nick'],
                'password'=>$post['password'],
                'repassword'=>$post['repassword'],
                'email'=>$post['mail'],
                'is_contact'=>2,
                'status'=>1,
                'contact_number'=>$contact_number,
                'create_time'=>time(),
                'update_time'=>time(),
            );

            if(!$post['nick']){
                $this->error('員工名稱必須填寫');
            }

            if(!$post['password']){
                $this->error('請輸入密碼');
            }

            if(!$post['repassword']){
                $this->error('無確認密碼');
            }

            if($post['password']!==$post['repassword']){
                $this->error('確認密碼不正確');
            }

            if (loader::validate('User')->scene('addstaff')->check($user) === false) {
                $this->error(loader::validate('User')->getError());
            }
            $user['password']   = md5($post['password']);
            $user['repassword'] = md5($post['repassword']);
            $res = Db::name('User')->strict(false)->insert($user);
            if($res!==false){
                $this->success('員工添加成功', Url::build('manage/staff'));
            }else{
                $this->success('員工添加失敗');
            }
        }else{
            return $this->fetch();
        }
    }

    public function editstaff(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }
            $user = array(
                'nick'=>$post['nick'],
                'email'=>$post['mail'],
                'update_time'=>time(),
            );
            $res = Db::name('User')->where('zid',$post['id'])->strict(false)->update($user);
            if($res!==false){
                $this->success('員工修改成功', Url::build('manage/staff'));
            }else{
                $this->success('員工修改失敗');
            }
        }else{
            $id = input("id");
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }
            $user = DB::name('user')->field('zid,nick,email')->where('zid',$id)->where('contact_number',$contact_number)->find();
            $this->assign('staff',$user);
            return $this->fetch();
        }
    }

    public function repass(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $zid = session('mob_user.zid');
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }

            if (empty($post['password'])||empty($post['repassword'])||$post['password']!=$post['repassword']) {
                $this->error('確認密碼錯誤');
            }
            $user = array();
            $user['password']   = md5($post['password']);
            $user['repassword'] = md5($post['repassword']);
            $res = Db::name('User')->where('zid',$zid)->strict(false)->update($user);
            if($res!==false){
                $this->success('修改密碼成功', Url::build('manage/index'));
            }else{
                $this->success('修改密碼失敗');
            }
        }else{
            return $this->fetch();
        }
    }

    public function reset(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $zid = session('mob_user.zid');
            $user = array(
                'name'=>$post['nick'],
                'email'=>$post['mail'],
                'head'=>$post['pic_path'],
            );

            if(!empty($user['head'])){
                $isbase['head'] = is_base64_picture($user['head']);
            }else{
                $this->error('請上傳頭像');die;
            }
            if($isbase['head']){
                // base64转图片
                $img = save_base_img($post['pic_path'],'uploads/head');
                // 图片地址保存
                $user['head'] = $img['path'];
            }

            if (loader::validate('User')->scene('reset')->check($user) === false) {
                $this->error(loader::validate('User')->getError());
            }
            $res = Db::name('User')->where('zid',$zid)->update($user);
            if($res!==false){
                $newUser = Db::name('User')->where('zid',$zid)->find();
                Session::set('mob_user',$newUser);
                $this->success('修改資料成功', Url::build('manage/index'));
            }else{
                $this->success('修改資料失敗');
            }
        }else{
            $zid = session('mob_user.zid');
            $res = Db::name('User')->where('zid',$zid)->find();
            $this->assign('user',$res);
            return $this->fetch();
        }
    }

    // 退出
    public function loginout() {
        $zid = session('mob_user.zid');
        $type = input('type');
        Session::clear();
        if($type!=2){
            $this->success('退出成功！', 'login/index');
        }else{
            $this->redirect('login/index');
        }
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
                $this->success($uploads['msg']);
            }else{
                $this->error($uploads['msg']);
            }
        }
    }

    // 删除分类
    public function delCategory(){
        if( Request::instance()->isPost() ) {
            $id = input('param.id');
            $type = input('param.type');
            if(session('mob_user.is_contact')!=0){
                $contact_number = session('mob_user.contact_number');
            }else{
                $contact_number = '';
            }
            $hasfoods = Db::name('goods')->where('categoryId',$id)->where('isDelete',0)->select();
            $hasmeal = Db::name('set_meal')->where('categoryId',$id)->where('isDelete',0)->select();
            if(count($hasfoods)>0||count($hasmeal)>0){
                $this->success('刪除失敗：'.$id.'有在售菜式或套餐', Url::build('manage/goodsCategory'));
            }
            $res = DB::name('category')->where('id',$id)->where('typeNumber',$type)->where('contactNumber',$contact_number)->update(['isDelete'=>1]);
            if($res!==false){
                if($type=='trade'){
                    $this->success('删除成功', Url::build('manage/goodsCategory'));
                }else if($type=='contactMember'){
                    $this->success('删除成功', Url::build('manage/membercategory'));
                }
            }else{
                $this->success('删除失敗');
            }
        }
    }

    public function delStaff(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }
            $update = array(
                'isDelete'=>1,
                'update_time'=>time(),
            );
            $res = Db::name('User')->where('zid',$post['id'])->where('contact_number',$contact_number)->where('is_contact',2)->delete();
            if($res!==false){
                $this->success('刪除成功', Url::build('manage/staff'));
            }else{
                $this->success('刪除失敗');
            }
        }
    }

    public function delMember(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            if(session('mob_user.is_contact')==0){
                $contact_number = '';
            }else{
                // 商家可以看到自己创建的分类
                $contact_number = session('mob_user.contact_number');
            }
            $update = array(
                'isDelete'=>1,
            );
            $res = Db::name('contactMember')->where('id',$post['id'])->where('contactNumber',$contact_number)->update($update);
            if($res!==false){
                $this->success('刪除成功', Url::build('manage/member'));
            }else{
                $this->success('刪除失敗');
            }
        }
    }

    public function spec(){
        $contact_number = session('mob_user.contact_number');
        $spec = array();
        $list = DB::name('Spec')
            ->where('isDelete',0)
            ->where('contactNumber',$contact_number)
            ->where('spec_pid',0)
            ->order('spec_order asc,id desc')
            ->select();
        $childid = array();
        $child = array();
        foreach ($list as $key => $val) {
            $childid[] = $val['id'];
            $spec[$val['id']] = $val;
        }
        if(!empty($childid)){
            $child = DB::name('Spec')
                ->where('spec_pid','in',$childid)
                ->where('isDelete',0)
                ->order('spec_order asc,id desc')
                ->select();
            foreach ($child as $k => $v) {
                $spec[$v['spec_pid']]['_child'][] = $v;
            }
        }
        $this->assign('spec', $spec);
        return $this->fetch();

    }

    public function addSpec(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $contact_number = session('mob_user.contact_number');
            $spec = array(
                'spec_name'=>$post['name'],
                'spec_name_en'=>$post['name_en'],
                'spec_name_other'=>$post['name_other'],
                'spec_pid'=>$post['specId'],
                'spec_disable'=>$post['status'],
                'spec_price'=>$post['price'],
                'minselect'=>!empty($post['min'])?$post['min']:1,
                'maxselect'=>!empty($post['max'])?$post['max']:1,
                'contactNumber'=>$contact_number,
            );
            $spec['is_default']= array_key_exists('is_default',$post)?1:0;
            $spec['is_repeat'] = array_key_exists('is_repeat',$post)?1:0;
            if($spec['spec_pid']==0){
                $rule = [
                    'spec_name'    => 'require|max:50',
                    'spec_name_en' => 'max:255',
                    'spec_name_other' => 'max:255',
                    'spec_pid'     => 'require|number',
                    'spec_price'   => 'require|number|egt:0',
                    'spec_disable' => 'require|in:0,1,2',
                    'minselect'    => 'require|number|egt:0',
                    'maxselect'    => 'require|number|gt:0',
                    'is_default'    => 'number',
                    'is_repeat'    => 'number',
                ];
            }else{
                $rule = [
                    'spec_name'    => 'require|max:50',
                    'spec_name_en' => 'max:255',
                    'spec_name_other' => 'max:255',
                    'spec_pid'     => 'require|number',
                    'spec_price'   => 'require|number|egt:0',
                    'spec_disable' => 'require|in:0,1,2',
                    'minselect'    => 'number|egt:0',
                    'maxselect'    => 'number|gt:0',
                    'is_default'    => 'number',
                    'is_repeat'    => 'number',
                ];
            }
            $msg = [
                'spec_name.require'     => '請輸入名稱',
                'spec_name.max'         => '名稱不能超過50個值',
                'spec_name_en.max'      => '名稱不能超過255個值',
                'spec_name_other.max'   => '名稱不能超過255個值',
                'spec_pid.require'      => '請選中父級規格',
                'spec_pid.number'       => '父級規格不正確',
                'spec_price.require'    => '規格價格未填寫',
                'spec_price.number'     => '規格價格格式錯誤',
                'spec_disable.require'  => '請選擇規格狀態',
                'spec_disable.number'   => '規格狀態不正確',
                'minselect'             => '請設置正確的最小值',
                'maxselect'             => '請設置正確的最大值',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($spec)) {
                $this->error($validate->getError());
            }
            if (($id = DB::name('Spec')->insertGetId($spec)) === false) {
                $this->error('添加失敗');
            }
            $spec['is_default']==1&&DB::name('Spec')->where('spec_pid',$spec['spec_pid'])->where('id','<>',$id)->update(['is_default'=>0]);
            $this->success('添加成功',url('manage/spec'));
        }else{
            $contact_number = session('mob_user.contact_number');
            $list = DB::name('Spec')
                ->where('isDelete',0)
                ->where('contactNumber',$contact_number)
                ->where('spec_pid',0)
                ->order('spec_order asc,id desc')
                ->select();
            $this->assign('list', $list);
            return $this->fetch('manage/addSpec');
        }

    }

    public function editSpec(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $contact_number = session('mob_user.contact_number');
            $spec = array(
                'id'       =>$post['id'],
                'spec_name'=>$post['name'],
                'spec_name_en'=>$post['name_en'],
                'spec_name_other'=>$post['name_other'],
                'spec_pid'=>$post['specId'],
                'spec_price'=>isset($post['price'])?$post['price']:0,
                'spec_disable'=>$post['status'],
                'contactNumber'=>$contact_number,
                'minselect'=>!empty($post['min'])?$post['min']:1,
                'maxselect'=>!empty($post['max'])?$post['max']:1,
            );
            $spec['is_default']= array_key_exists('is_default',$post)?1:0;
            $spec['is_repeat'] = array_key_exists('is_repeat',$post)?1:0;
            if($spec['spec_pid']==0){
                $rule = [
                    'id'           => 'require|number',
                    'spec_name'    => 'require|max:50',
                    'spec_name_en' => 'max:255',
                    'spec_name_other' => 'max:255',
                    'spec_pid'     => 'require|number',
                    'spec_disable' => 'require|in:0,1,2',
                    'minselect'    => 'require|number|egt:0',
                    'maxselect'    => 'require|number|gt:0',
                    'is_default'   => 'number',
                    'is_repeat'    => 'number',
                ];
            }else{
                $rule = [
                    'id'           => 'require|number',
                    'spec_name'    => 'require|max:50',
                    'spec_name_en' => 'max:255',
                    'spec_name_other' => 'max:255',
                    'spec_pid'     => 'require|number',
                    'spec_disable' => 'require|in:0,1,2',
                    'minselect'    => 'number|egt:0',
                    'maxselect'    => 'number|gt:0',
                    'is_default'    => 'number',
                    'is_repeat'    => 'number',
                ];
            }

            $msg = [
                'id.require'            => '頁面錯誤',
                'id.number'             => '頁面錯誤',
                'spec_name.require'     => '請輸入名稱',
                'spec_name.max'         => '名稱不能超過50個值',
                'spec_name_en.max'      => '名稱不能超過255個值',
                'spec_name_other.max'   => '名稱不能超過255個值',
                'spec_pid.require'      => '請選中父級規格',
                'spec_pid.number'       => '父級規格不正確',
                'spec_disable.require'  => '請選擇規格狀態',
                'spec_disable.number'   => '規格狀態不正確',
                'minselect'             => '請設置正確的最小值',
                'maxselect'             => '請設置正確的最大值',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($spec)) {
                $this->error($validate->getError());
            }
            if (($id = DB::name('Spec')->update($spec)) === false) {
                $this->error('修改失敗');
            }
            $spec['is_default']==1&&DB::name('Spec')->where('spec_pid',$spec['spec_pid'])->where('id','<>',$spec['id'])->update(['is_default'=>0]);
            $this->success('修改成功',url('manage/spec'));
        }else{
            $id = input('id');
            $contact_number = session('mob_user.contact_number');
            $spec = DB::name('Spec')->where('isDelete',0)->where('contactNumber',['eq',$contact_number],['eq',''],['EXP','IS NULL'],'or')->where('id',$id)->find();
            $list = DB::name('Spec')
                ->where('id','neq',$id)
                ->where('isDelete',0)
                ->where('contactNumber',$contact_number)
                ->where('spec_pid',0)
                ->order('spec_order asc,id desc')
                ->select();
            $this->assign('spec', $spec);
            $this->assign('list', $list);
            return $this->fetch('editSpec');
        }
    }

    public function delSpec(){
        if( Request::instance()->isPost() ) {
            $post = input("post.");
            $contact_number = session('mob_user.contact_number');
            $update = array(
                'isDelete'=>1,
            );
            $res = Db::name('Spec')->where('id',$post['id'])->where('contactNumber',$contact_number)->update($update);
            if($res!==false){
                $this->success('刪除成功', Url::build('manage/Spec'));
            }else{
                $this->success('刪除失敗');
            }
        }
    }

    public function autoOrder(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            $contact = array(
                'autoOrder'=>isset($post['auto'])?$post['auto']:0,
            );
            if (loader::validate('Contact')->scene('autoOrder')->check($contact) === false) {
                $this->error(loader::validate('Contact')->getError());
            }
            $res = Db::name('Contact')->where('number',$contact_number)->update($contact);
            if($res!==false){
                $this->success('修改成功',Url::build('manage/index'));
            }else{
                $this->error('修改失敗');
            }
        }else{
            $session = session('mob_user');
            // 商家的编号
            $contact_number = session('mob_user.contact_number');
            $contact = DB::name('Contact')
                ->field('autoOrder')
                ->where('number',$contact_number)
                ->where('isDelete',0)
                ->find();
            $this->assign('contact',$contact);
            return $this->fetch('autoOrder');
        }
    }

    //预支付设置：允许先下单后付款
    public function laterPay(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            $contact = array(
                'laterPay'=>isset($post['laterpay'])?$post['laterpay']:0,
            );
            if (loader::validate('Contact')->scene('laterPay')->check($contact) === false) {
                $this->error(loader::validate('Contact')->getError());
            }
            $res = Db::name('Contact')->where('number',$contact_number)->update($contact);
            if($res!==false){
                $this->success('修改成功',Url::build('manage/index'));
            }else{
                $this->error('修改失敗');
            }
        }else{
            $session = session('mob_user');
            // 商家的编号
            $contact_number = session('mob_user.contact_number');
            $contact = DB::name('Contact')
                         ->field('laterPay')
                         ->where('number',$contact_number)
                         ->where('isDelete',0)
                         ->find();
            $this->assign('contact',$contact);
            return $this->fetch('laterPay');
        }
    }

    public function getKey(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            $contact = array(
                'secretKey'=>isset($post['key'])?$post['key']:'',
            );
            if (loader::validate('Contact')->scene('secretKey')->check($contact) === false) {
                $this->error(loader::validate('Contact')->getError());
            }
            $res = Db::name('Contact')->where('number',$contact_number)->update($contact);
            if($res!==false){
                $this->success('修改成功',Url::build('manage/index'));
            }else{
                $this->error('修改失敗');
            }
        }else{
            $session = session('mob_user');
            // 商家的编号
            $contact_number = session('mob_user.contact_number');
            $contact = DB::name('Contact')
                ->field('secretKey,number')
                ->where('number',$contact_number)
                ->where('isDelete',0)
                ->find();
            $this->assign('contact',$contact);
            return $this->fetch('getKey');
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
            $this->error(['message'=>'請輸入地址','status'=>'-1']);
        }
        return $this->success($res);
    }


    public function setMainPrinter(){
        if( Request::instance()->isPost() ) {}else{
            // 獲取餐廳信息
            // 商家的编号
            $contact_number = session('mob_user.contact_number');
            $contact = DB::name('Contact')
                ->field('name,logoUrl,bgImageUrl,linkMans,remark,cCategory,cCategoryName,contactType,bank_id,bank_name,account_number,account_name,rate,cycle,address,longitude,latitude')
                ->where('number',$contact_number)
                ->where('isDelete',0)
                ->find();
            // 獲取餐廳打印機列表
            $printer = DB::name('Printer')
                ->alias('p')
                ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
                ->field('p.id,p.deviceNick,b.brand,b.brandNumber,b.fileName')
                ->where('p.contactNumber',$contact_number)
                ->where('p.isDelete',0)
                ->order('p.id desc')
                ->select();
            // 輸出到頁面
            $this->assign('contact',$contact);
            $this->assign('printer',$printer);
            // 顯示頁面
            return $this->fetch('setMainPrinter');
        }
    }

    public function editCoverCharge(){

        $contactNumber = session('mob_user.contact_number');

        $contact = Db::name('contact')
            ->field('fee,is_cover_charge')
            ->where('number','=',$contactNumber)
            ->where('isDelete','=',0)
            ->find();

        $this->assign('contact',$contact);
        return $this->fetch('edit_cover_charge');
    }

    public function saveCoverCharge(){

        $request = Request::instance();
        $fee = $request->post('fee',0);
        $isCoverCharge = $request->post('is_cover_charge',0);
        $validate = new Validate([
            'fee'=>'require|number',
            'is_cover_charge'=>'require|number'
        ]);
        $data = [
            'fee' => $fee,
            'is_cover_charge' => $isCoverCharge
        ];

        if(!$validate->check($data)){
            $this->redirect('manage/editCoverCharge');
            exit;
        }

        $contact = session('mob_user');
        Db::name('contact')->where('number','=',$contact['contact_number'])->update($data);

        $this->redirect('manage/index');
    }

    //桌台管理
    public function tableManage(){
        $contact_number = session('mob_user.contact_number');
        $DayDate=strtotime(date('Y-m-d 00:00:01', strtotime(date("Y-m-d"))));
        if(isset($post['action'])){
            if($post['action']==1){
                $where['o.createTime'] = ['>',$MouthDate];
            }elseif($post['action']==2){
                $where['o.createTime'] = ['>',$DayDate];
            }else{
                $where['o.createTime'] = ['>',1];
            }
        }
        $contact_member = DB::name('ContactMember')
                            ->where('contactNumber',$contact_number)
                            ->where('isDelete',0)
                            ->select();
        $orders = DB::name('wxOrder')
                   ->where('contactNumber',$contact_number)
                   ->where('createTime','>',$DayDate)
                   ->where('orderStatus=2 or orderStatus=3')
                   ->where('isDelete',0)
                   ->select();
        $order_number = [];
        foreach($contact_member as $number){
            $order_number[$number['number']] = $number;
            foreach($orders as $order)
            {
                $order_number[$order['contactMemberNumber']]['order'] = $order;
            }
            if(!empty($order_number[$number['number']]['order'])){
                $order_number[$number['number']]['sname'] = WxOrder::getOrderStatusAttr($order_number[$number['number']]['order']['orderStatus']);
                $order_number[$number['number']]['orderStatus'] = $order_number[$number['number']]['order']['orderStatus'];
                $order_number[$number['number']]['payStatus'] = $order_number[$number['number']]['order']['payStatus'];
            }else{
                $order_number[$number['number']]['sname'] = lang('空閒中');
                $order_number[$number['number']]['orderStatus'] = 0;
                $order_number[$number['number']]['payStatus'] = 0;
            }
        }
        //按餐桌id排序
        $asc_order = array_column($order_number,'id');
        array_multisort($asc_order,SORT_ASC,$order_number);
        $this->assign('contact_member', $order_number);
        return $this->fetch();
    }

    //菜品设置
    public function foodManage(){
        return $this->fetch();
    }

    //跟餐管理
    public function addmealManage()
    {
        $number = session('mob_user.contact_number');
        $addon  = DB::name('addon')
                    ->where('contactNumber', $number)
                    ->select();
        //跟餐菜品统计
        $foods  = DB::name('addonFoods')
                    ->field("aid,count(DISTINCT gid) foodscount")
                    ->where('contactNumber', $number)
                    ->group('aid')
                    ->select();
        $foods = array_column($foods,null,'aid');
        //跟餐应用菜品统计
        $goods  = DB::name('addonGoods')
                    ->field("aid,count(DISTINCT gid) goodscount")
                    ->where('contactNumber', $number)
                    ->group('aid')
                    ->select();
        $goods = array_column($goods,null,'aid');
        //合并统计到跟餐集合
        if(!empty($addon))
        {
            foreach($addon as &$a)
            {
                //合并跟餐菜品统计
                if(!empty($foods[$a['id']]))
                {
                    $a['foodscount'] = $foods[$a['id']]['foodscount'];
                }else{
                    $a['foodscount'] = 0;
                }

                //合并跟餐应用菜品统计
                if(!empty($goods[$a['id']]))
                {
                    $a['goodscount'] = $goods[$a['id']]['goodscount'];
                }else{
                    $a['goodscount'] = 0;
                }
            }
        }
        $this->assign('addon_data', $addon);
        return $this->fetch();
    }

    //設置菜品
    public function setMeal(){
        $id = input('param.id');
        $this->assign('aid', $id);
        $contact_number = session('mob_user.contact_number');
        $addon_foods_group = DB::name('addonFoodsGroup')->where('aid',$id)->where('contactNumber',$contact_number)->select();
        $addon_foods = DB::name('addonFoods')->where('aid',$id)->where('contactNumber',$contact_number)->select();
        $result = get_food_list($contact_number);//商家所有菜品带规格
        $foodlist = $result['foodlist'];
        //过滤已经选择的菜品
        //$foodlist = [];
        //foreach($result['foodlist'] as $key=>$fitem)
        //{
        //    if(!in_array($fitem['id'],array_column($addon_foods,'gid')))
        //    {
        //        $foodlist[] = $fitem;
        //    }
        //}
        //已经选择的菜品带规格
        if(!empty($addon_foods)){
            foreach($addon_foods as &$item)
            {
                foreach($result['foodlist'] as $fooditem)
                {
                    if($item['gid']==$fooditem['id'])
                    {
                        $item = array_merge($fooditem,$item);
                    }
                }
            }
        }
        $addon_foods_data = [];
        if(!empty($addon_foods_group)){
            foreach($addon_foods_group as $group){
                $addon_foods_data[$group['id']] = $group;
                if(!empty($addon_foods)){
                    foreach($addon_foods as $food)
                    {
                        if($group['id']==$food['groupid']){
                            $addon_foods_data[$group['id']]['_foods'][] = $food;
                        }
                    }
                }
            }
        }
        $this->assign('foodlist', $foodlist);
        $this->assign('addon_foods_data', $addon_foods_data);
        return $this->fetch();
    }

    //设置跟餐分组菜品价格
    public function setMealFoodPrice(){
        //if( Request::instance()->isPost() ) {
            $contact_number = session('mob_user.contact_number');
            $post           = input('param.');
            //$post['price_data'] = array(
            //        '497_214_1.0',
            //        '497_215_2.0',
            //        '497_216_3.0',
            //        '497_217_4.0',
            //        '497_218_5.0',
            //        '509_6.0',
            //);
            $data = [];
            foreach($post['price_data'] as $k=>$fdata){
                $arr =  explode('_',$fdata);
                if(count($arr)==3) $data[$arr[0]][$arr[1]] = $arr[2];
                if(count($arr)==2) $data[$arr[0]] = [$arr[1]];
            }
            if(!empty($data)) {
                foreach($data as $key=>$price_data)
                {
                    if(!empty($price_data)&&count($price_data)>1)
                    {
                        $res = DB::name('addonFoods')->where('contactNumber',$contact_number)->where('id',$key)->update(['spec_price'=>json_encode($price_data)]);
                    }else{
                        $res = DB::name('addonFoods')->where('contactNumber',$contact_number)->where('id',$key)->update(['addon_price'=>$price_data[0]]);
                    }

                }
                return ['code'=>1,'msg'=>lang('價格修改成功')];
            } else {
                return ['code'=>0,'msg'=>lang('價格修改失败')];
            }
        //}
    }

    //添加跟餐分组菜品的post方法
    public function addMealFood(){
        if( Request::instance()->isPost() ) {
            $contact_number = session('mob_user.contact_number');
            $post           = input('param.');
            if(!empty($post['gid']) && !empty($post['groupid']) && !empty($post['aid'])) {
                foreach($post['gid'] as $value) {
                    $save[] = [
                        'contactNumber' => $contact_number,
                        'groupid'       => $post['groupid'],
                        'aid'           => $post['aid'],
                        'gid'           => $value,
                    ];
                }
                DB::name('addonFoods')->insertAll($save);
                $this->success('添加成功');
            } else {
                $this->error('請選擇菜品');
            }
        }
    }

    //删除跟餐分组菜品
    public function delMealFood(){
        $contact_number = session('mob_user.contact_number');
        $id = input('param.id');
        if(!empty($id)) {
            $res = DB::name('addonFoods')->where('id',$id)->where('contactNumber',$contact_number)->delete();
            $this->success('刪除成功');
        }else{
            $this->error('缺少id');
        }
    }

    //获取跟餐菜品
    public function getMealFood(){
        $contact_number = session('mob_user.contact_number');
        $post = input('param.');
        $keyword = empty($post['keyword']) ? null : $post['keyword'];
        $addon_foods_ids = [];
        if(!empty($post['groupid'])){
            $addon_foods = DB::name('addonFoods')->where('groupid',$post['groupid'])->where('contactNumber',$contact_number)->select();
            $addon_foods_ids = array_column($addon_foods,'gid');
        }
        $where = ['keyword'=>$keyword,'addon_foods_ids'=>$addon_foods_ids];
        $result = get_food_list($contact_number,null,null,$where);//商家所有菜品带规格
        $foodlist = $result['foodlist'];

        return json($foodlist);
    }

    //應用菜品管理
    public function mealManage(){
        $id = input('param.id');
        $this->assign('aid', $id);
        $contact_number = session('mob_user.contact_number');
        $addgoods = DB::name('addonGoods')->where('aid',$id)->where('contactNumber',$contact_number)->select();
        $goodlist = DB::name('Goods')->where('contactNumber',$contact_number)->select();
        $good_data = [];
        foreach($addgoods as $agood)
        {
            foreach($goodlist as $good) {
                if($good['id'] == $agood['gid']){
                    $good_data[$agood['id']] = $good;
                }
            }
        }
        $this->assign('good_data', $good_data);
        return $this->fetch();
    }

    //跟餐分類
    public function mealCategory(){
        $gid = input('param.gid');
        $aid = input('param.aid');
        $this->assign('gid', $gid);
        $this->assign('aid', $aid);
        $contact_number = session('mob_user.contact_number');
        $has_addon_data = DB::name('addonGoods')->where('gid',$gid)->where('contactNumber',$contact_number)->select();
        $addon_data = DB::name('addon')->where('contactNumber',$contact_number)->select();
        if(!empty($addon_data)){
            foreach($addon_data as &$addon)
            {
                if(!empty($has_addon_data)&&in_array($addon['id'],array_column($has_addon_data,'aid'))){
                    $addon['has_addon'] = 1;
                }else{
                    $addon['has_addon'] = 0;
                }
            }
            unset($addon);
        }
        $this->assign('addon_data', $addon_data);
        return $this->fetch();
    }

    //获取跟餐应用菜品
    public function getMealGood()
    {
        $contact_number  = session('mob_user.contact_number');
        $post            = input('param.');
        $keyword         = empty($post['keyword']) ? null : $post['keyword'];
        $addon_goods_ids = [];
        if(!empty($post['id'])) {
            $addon_goods     = DB::name('addonGoods')->where('aid', $post['id'])->where('contactNumber', $contact_number)->select();
            $addon_goods_ids = array_column($addon_goods, 'gid');
        }
        $goodswhere = [];
        if(!empty($keyword)) {
            $goodswhere['name'] = ['like', "%$keyword%"];
        }
        if(!empty($addon_goods_ids)) {
            $goodswhere['id'] = ['not in', $addon_goods_ids];
        }
        if(!empty($goodswhere)) {
            $goodlist = DB::name('Goods')->where('contactNumber', $contact_number)->where($goodswhere)->select();
        }else{
            $goodlist = DB::name('Goods')->where('contactNumber', $contact_number)->select();
        }
        return json($goodlist);
    }

    //跟餐应用到菜品
    public function addMealGood(){
        if( Request::instance()->isPost() ) {
            $contact_number = session('mob_user.contact_number');
            $post           = input('param.');
            if(!empty($post['gid']) && !empty($post['aid'])) {
                foreach($post['gid'] as $value) {
                    $save[] = [
                        'contactNumber' => $contact_number,
                        'aid'           => $post['aid'],
                        'gid'           => $value,
                    ];
                }
                DB::name('addonGoods')->insertAll($save);
                $this->success('添加成功');
            } else {
                $this->error('請選擇菜品');
            }
        }
    }

    //菜品应用到多个跟餐
    public function setMealGood(){
        if( Request::instance()->isPost() ) {
            $contact_number = session('mob_user.contact_number');
            $post           = input('param.');
            if(!empty($post['gid']) && !empty($post['aid'])) {
                $addon_goods     = DB::name('addonGoods')->where('gid', $post['gid'])->where('contactNumber', $contact_number)->select();
                $addon_goods_ids = array_column($addon_goods, 'aid');
                //已存在但是不在这次选择的菜品中的，需要去除
                $noselect = array_diff($addon_goods_ids,$post['aid']);log_output($noselect);
                if(!empty($noselect)) DB::name('addonGoods')->where('aid','in',$noselect)->where('gid',$post['gid'])->delete();
                foreach($post['aid'] as $value) {
                    if(!in_array($value,$addon_goods_ids)) {
                        $save[] = [
                            'contactNumber' => $contact_number,
                            'gid'           => $post['gid'],
                            'aid'           => $value,
                        ];
                    }
                }
                $res = empty($save) ?false: DB::name('addonGoods')->insertAll($save);
                $this->success('修改成功');
            } else {
                $this->error('請選擇跟餐');
            }
        }
    }

    //删除跟餐应用菜品
    public function delMealGood(){
        if( Request::instance()->isPost() ) {
            $contact_number = session('mob_user.contact_number');
            $post           = input('param.');
            if(!empty($post['gid']) && !empty($post['aid']) && is_array($post['gid'])) {
                DB::name('addonGoods')->where('aid',$post['aid'])->where('gid','in',$post['gid'])->where('contactNumber',$contact_number)->delete();
                $this->success('刪除成功');
            } else {
                $this->error('請選擇要刪除的菜品');
            }
        }
    }

    //添加跟餐
    public function addMeal(){
        $contact_number = session('mob_user.contact_number');
        if(Request::instance()->isPost()) {
            $post = input('param.');
            $save = [
                'contactNumber' => $contact_number,
                'name'          => $post['name'],
                'name_en'       => $post['name_en'],
                'name_other'    => $post['name_other'],
                'status'       => empty($post['status'])?0:$post['status'],
            ];
            if (loader::validate('Addon')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Addon')->getError());
            }
            $res = DB::name('addon')->insert($save);
            $this->success('添加成功', Url::build('manage/addmeal'));
        }
        return $this->fetch();
    }

    //修改跟餐
    public function editMeal(){
        $contact_number = session('mob_user.contact_number');
        $id = input('param.id');
        if(empty($id)||$id<1)
        {
            return $this->error('缺少参数',url('addmealmanage'));
        }
        $addon = DB::name('addon')->where('id',$id)->where('contactNumber',$contact_number)->find();
        $this->assign('addon_data', $addon);
        if(Request::instance()->isPost()) {
            $post = input('param.');
            $save = [
                'contactNumber' => $contact_number,
                'name'          => $post['name'],
                'name_en'       => $post['name_en'],
                'name_other'    => $post['name_other'],
                'status'       => empty($post['status'])?0:$post['status'],
            ];
            if (loader::validate('Addon')->scene('edit')->check($save) === false) {
                return $this->error(loader::validate('Addon')->getError());
            }
            $res = DB::name('addon')->where('id',$id)->where('contactNumber',$contact_number)->update($save);
            $this->success('修改成功', Url::build('manage/addmeal'));
        }
        return $this->fetch();
    }

    //删除跟餐
    public function delMeal()
    {
        $contact_number = session('mob_user.contact_number');
        $id = input('param.id');
        $res = false;
        if(!empty($id)) {
            //开启事务
            DB::startTrans();
            try{
                DB::name('addon')->where('id',$id)->where('contactNumber',$contact_number)->delete();
                DB::name('addonFoods')->where('aid',$id)->where('contactNumber',$contact_number)->delete();
                DB::name('addonFoodsGroup')->where('aid',$id)->where('contactNumber',$contact_number)->delete();
                DB::name('addonGoods')->where('aid',$id)->where('contactNumber',$contact_number)->delete();
                $res = true;
            } catch (\Exception $e) {
                $res = false;
                // 回滚事务
                Db::rollback();
            }
        } else {
            return $this->error('缺少参数');
        }
        return $res?$this->success('删除成功'):$this->error('删除失敗');
    }

    //添加跟餐分組
    public function addmealGroup(){
        $aid = input('param.aid');
        $contact_number = session('mob_user.contact_number');
        if(Request::instance()->isPost()) {
            $post = input('param.');
            $save = [
                'contactNumber'    => $contact_number,
                'aid'              => $post['aid'],
                'name'             => $post['name'],
                'name_en'          => $post['name_en'],
                'name_other'       => $post['name_other'],
                'group_max_number' => $post['group_max_number'],
                'is_require'       => empty($post['is_require'])?0:$post['is_require'],
                'is_repeat'        => empty($post['is_repeat'])?0:$post['is_repeat'],
            ];
            if (loader::validate('Addongroup')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Addongroup')->getError());
            }
            $res  = DB::name('addonFoodsGroup')->insert($save);
            $this->success('添加成功', Url::build('manage/addmealgroup'));
        }
        $this->assign('aid', $aid);
        return $this->fetch();
    }

    //编辑跟餐分組
    public function editmealGroup(){
        $contact_number = session('mob_user.contact_number');
        $aid = input('param.aid');//跟餐id
        $id = input('param.id');//跟餐分组id
        if(empty($id)||$id<1)
        {
            return $this->error('缺少参数',url('setmeal'));
        }
        $addon_group = DB::name('addonFoodsGroup')->where('id',$id)->where('contactNumber',$contact_number)->find();
        $this->assign('addon_group_data', $addon_group);
        if(Request::instance()->isPost()) {
            $post = input('param.');
            $save = [
                'contactNumber'    => $contact_number,
                'aid'              => $post['aid'],
                'name'             => $post['name'],
                'name_en'          => $post['name_en'],
                'name_other'       => $post['name_other'],
                'group_max_number' => $post['group_max_number'],
                'is_require'       => empty($post['is_require'])?0:$post['is_require'],
                'is_repeat'        => empty($post['is_repeat'])?0:$post['is_repeat'],
            ];
            if (loader::validate('Addongroup')->scene('edit')->check($save) === false) {
                return $this->error(loader::validate('Addongroup')->getError());
            }
            $res  = DB::name('addonFoodsGroup')->where('id',$id)->where('contactNumber',$contact_number)->update($save);
            $this->success('修改成功', Url::build('manage/addmealgroup'));
        }
        $this->assign('aid', $aid);
        $this->assign('id', $id);
        return $this->fetch();
    }

    //删除跟餐分组
    public function delMealGroup()
    {
        $contact_number = session('mob_user.contact_number');
        $id = input('param.id');
        $res = false;
        if(!empty($id)) {
            //开启事务
            DB::startTrans();
            try{
                DB::name('addonFoodsGroup')->where('id',$id)->where('contactNumber',$contact_number)->delete();
                DB::name('addonFoods')->where('groupid',$id)->where('contactNumber',$contact_number)->delete();
                $res = true;
            } catch (\Exception $e) {
                $res = false;
                // 回滚事务
                Db::rollback();
            }
        } else {
            return $this->error('缺少参数');
        }
        return $res?$this->success('删除成功'):$this->error('删除失敗');
    }

    //餐厅设置
    public function contactSet()
    {
        $contact_number = session('mob_user.contact_number');
        if(Request::instance()->isPost()) {
            $post = input('param.');
            $save = [
                'number'          => $contact_number,
                //'laterPay'        => trim($post['laterpay']),
                'laterPay'        => 0, //当前不允许更改线上线下支付
                'is_cover_charge' => empty($post['is_cover_charge']) ? 0 : $post['is_cover_charge'],
                'fee'             => trim($post['fee']),
                'is_service_fee'  => empty($post['is_service_fee']) ? 0 : $post['is_service_fee'],
                'service_fee'     => trim($post['service_fee']),
                'box_fee'         => trim($post['box_fee']),
                'autoOrder'       => empty($post['autoOrder']) ? 0 : $post['autoOrder'],
                'addOrderPrint'   => empty($post['addOrderPrint']) ? 0 : $post['addOrderPrint'],
            ];
            //同时修session用户判断
            session('contact_info.is_cover_charge',$save['is_cover_charge']);
            session('contact_info.service_fee',$save['service_fee']);
            $res  = DB::name('contact')->where('number', $contact_number)->update($save);
            $this->success('更新成功', Url::build('manage/index'));
        } else {
            $contact = Db::name('contact')->where('number', $contact_number)->find();
            $this->assign('contact', $contact);
            return $this->fetch();
        }
        return $this->fetch();
    }

    //第三方支付设置
    public function paySet(){
        $contact_number = session('mob_user.contact_number');
        $contact_paytype = DB::name('Contact')->field('offpaytype')->where('number',$contact_number)->find();
        if(Request::instance()->isPost()) {
            $post   = input('param.');
            if(isset($post['paytype'])){
                //offpaytype字段为商家已关闭的支付方式id集合
                $contact_paytype = DB::name('Contact')->field('offpaytype')->where('number',$contact_number)->find();
                $offpaytype = $contact_paytype['offpaytype'];
                $data = json_decode($offpaytype,true);
                if(empty($data))
                {
                    $data = [];
                    array_push($data,$post['paytype']);
                    $res = DB::name('Contact')->where('number',$contact_number)->update(['offpaytype'=>json_encode($data)]);
                }
                elseif(in_array($post['paytype'],json_decode($offpaytype,true)))
                {
                    foreach($data as $k=>$v){
                        if($v == $post['paytype']) unset($data[$k]);
                    }
                    $res = DB::name('Contact')->where('number',$contact_number)->update(['offpaytype'=>json_encode($data)]);
                }
                elseif(!in_array($post['paytype'],json_decode($offpaytype,true)))
                {
                    array_push($data,$post['paytype']);
                    $res = DB::name('Contact')->where('number',$contact_number)->update(['offpaytype'=>json_encode($data)]);
                }
                return json(['code'=>$res,'msg'=>'']);
            }
        }else {
            $payment_data = Db::name('PayMethod')
                              ->where('online', 0)
                              ->select();
            $paytype_ids = empty(json_decode($contact_paytype['offpaytype'],true))?[]:json_decode($contact_paytype['offpaytype'],true);
            foreach($payment_data as &$item){
                $item['status'] = in_array($item['id'],$paytype_ids)?0:1;
            }
            $this->assign('payment_data', $payment_data);
            return $this->fetch();
        }
    }

    //公告设置
    public function noticeSet()
    {
        $contact_number = session('mob_user.contact_number');
        if(Request::instance()->isPost()) {
            $post   = input('param.');
            if(!empty($post['notice-input-more'])){
                $content = implode('|@o@|',$post['notice-input-more']);
                if(!empty($post['notice-input'])){
                    $content .= '|@o@|'.trim($post['notice-input']);
                }
            }else{
                $content = trim($post['notice-input']);
            }
            $save   = [
                'number'     => $contact_number,
                'content'    => $content,
                'background' => trim($post['bg-color']),
                'updatetime' => time(),
            ];
            $notice = Db::name('notice')->where('number', $contact_number)->find();
            if(!empty($notice)) {
                $res = DB::name('notice')->where('number', $contact_number)->update($save);
            } else {
                $res = DB::name('notice')->where('number', $contact_number)->insert($save);
            }
            if($res > 0) {
                $this->success('保存成功', Url::build('manage/index'));
            } else {
                $this->error('保存失敗');
            }
        } else {
            $notice = Db::name('notice')->where('number', $contact_number)->find();
            if(!empty($notice)){
                $notice['content'] = strpos($notice['content'],'|@o@|')!==false?explode('|@o@|',$notice['content']):$notice['content'];
            }
            $this->assign('notice', $notice);
            return $this->fetch();
        }
    }
}