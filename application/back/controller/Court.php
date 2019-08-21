<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Category;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
use watermask\watermask;
class Court extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $where = array();
        if(isset($param['status'])&&$param['status']!==''){
            $where['disable'] = $param['status'];
        }
        if(isset($param['category'])&&$param['category']!==''){
            $where['cCategory'] = $param['category'];
        }
        if(isset($param['method'])&&$param['method']!==''){
            $where['contactType'] = $param['method'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['name|number'] = ['like','%'.$param['search'].'%'];
        }
        $type = config('contact_type');
        $contact = Loader::model('FoodCourt')->where($where)->where(['isDelete'=>0])->paginate(10,false,['query'=>$param]);
        $category = DB::name('Category')->field('id,name')->where('typeNumber','customertype')->where(['typeNumber'=>'customertype','isDelete'=>0])->select();
        $this->assign('type',$type);
        $this->assign('param',$param);
        $this->assign('lists',$contact);
        $this->assign('category',$category);
        $this->assign('pages',$contact->render());
        return $this->fetch();
    }

  /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params   = $request->param();
            // 獲取默認配置
            $default_config = getDefaultConfig();
            $category = array();
            if(!empty($params['cCategory'])){
                $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->where('id',$params['cCategory'])->find();
            }
            $save = array(
                'name'          => !empty($params['name'])?$params['name']:'',
                'number'        => !empty($params['number'])?$params['number']:'',
                'cCategory'     => !empty($category['id'])?$category['id']:'',
                'cCategoryName' => !empty($category['name'])?$category['name']:'',
                'contactType'   => !empty($params['type'])?$params['type']:'',
                'rate'          => !empty($params['rate'])?$params['rate']:$default_config['payments_contact_default_rate'],
                'cycle'         => !empty($params['cycle'])?$params['cycle']:$default_config['payments_contact_default_cycle'],
                'address'       => !empty($params['address'])?$params['address']:'',
                'latitude'      => !empty($params['Latitude'])?$params['Latitude']:'',
                'longitude'     => !empty($params['Longitude'])?$params['Longitude']:'',
                'remark'        => !empty($params['remark'])?$params['remark']:'',
                'disable'       => !empty($params['disable'])?$params['disable']:'',
                'linkMans'      => !empty($params['linkMans'])?$params['linkMans']:'',
                // 美食廣場不需要餐桌
                'member'        => 0,
                'isDelete'      => 0,
                'ctime'         => time(),
            );
            $user = array(
                'name'=>trim($params['username']),
                'nick'=>trim($params['nick']),
                'password'=>trim($params['password']),
                'repassword'=>trim($params['repassword']),
                'is_contact'=>4,
                'uid'=>7,
                'contact_number'=>trim($params['number']),
                'email'=>trim($params['mail']),
                'status'=>1,
                'create_time'=>time(),
            );
            // 图片上传
            $file = request()->file('image');
            $bg = request()->file('bg');
            if($file){
                // 调用上传方法
                $width  = config('Thumwidth');
                $height = config('Thumheight');
                $upload = img_create_small($file,$width,$height,"uploads/Thumbnail");
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $save['logoUrl'] = $upload['msg'];
                    $user['head'] = $upload['msg'];
                }else{
                    return $this->error($upload['msg']);
                }
            }
            if($bg){
                // 调用上传方法 保存原图
                $uploads = uploadPic($bg,'uploads/big');
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($uploads['code']==1){
                    $save['bgImageUrl'] = $uploads['msg'];
                }else{
                    return $this->error($uploads['msg']);
                }
            }
            if (loader::validate('FoodCourt')->scene('adminAdd')->check($save) === false) {
                return $this->error(loader::validate('FoodCourt')->getError());
            }
            if (loader::validate('User')->scene('register')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            $user['password']   = md5($params['password']);
            $user['repassword'] = md5($params['repassword']);
            $res = 1;
            Db::startTrans();
            try{
                Db::name('User')->strict(false)->insert($user);
                $Id = Loader::model('FoodCourt')->strict(false)->insertGetId($save);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            Loader::model('SystemLog')->record("添加美食广场,ID:[{$Id}]");
            return $this->success('添加美食广场成功', Url::build('Court/index'));
        }else{
            $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->order('ordnum asc')->select();
            $type = config('contact_type');
            $category = arrtree($customertype,"child");
            $this->assign('type',$type);
            $this->assign('category',$category);
        }
        return $this->fetch();
    }


  /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */

    public function edit() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params = $request->param();
            $params['id'] = $id;
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->where('id',$params['cCategory'])->find();
            $save = array(
                'id'            => !empty($id)?$id:'',
                'name'          => !empty($params['name'])?$params['name']:'',
                'cCategory'     => !empty($category['id'])?$category['id']:'',
                'cCategoryName' => !empty($category['name'])?$category['name']:'',
                'contactType'   => !empty($params['type'])?$params['type']:'',
                'rate'          => !empty($params['rate'])?$params['rate']:$default_config['payments_contact_default_rate'],
                'cycle'         => !empty($params['cycle'])?$params['cycle']:$default_config['payments_contact_default_cycle'],
                'address'       => !empty($params['address'])?$params['address']:'',
                'latitude'      => !empty($params['Latitude'])?$params['Latitude']:'',
                'longitude'     => !empty($params['Longitude'])?$params['Longitude']:'',
                'remark'        => !empty($params['remark'])?$params['remark']:'',
                'disable'       => !empty($params['disable'])?$params['disable']:'',
                'linkMans'      => !empty($params['linkMans'])?$params['linkMans']:'',
                'isDelete'      => 0,
            );
            // 图片上传
            $file = request()->file('image');
            if($file){
                // 调用上传方法
                $width  = config('Thumwidth');
                $height = config('Thumheight');
                $upload = img_create_small($file,$width,$height,"uploads/Thumbnail");
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $params['logoUrl'] = $upload['msg'];
                }else{
                    return $this->error($upload['msg']);
                }
            }
            if (loader::validate('FoodCourt')->scene('adminEdit')->check($save) === false) {
                return $this->error(loader::validate('FoodCourt')->getError());
            }
            if ((Loader::model('FoodCourt')->where('id',$id)->update($save)) === false) {
                return $this->error(Loader::model('FoodCourt')->getError());
            }
            Loader::model('SystemLog')->record("美食广场编辑,ID:[{$id}]");
            return $this->success('美食广场编辑成功', Url::build('court/index'));
        }else{
            $court = DB::name('FoodCourt')->where('isDelete',0)->where('id',$id)->find();
            $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->order('ordnum asc')->select();
            $category = arrtree($customertype,"child");
            if(empty($court)){
                return $this->error('请选择正确的美食广场');
            }
            $type = config('contact_type');
            $this->assign('type',$type);
            $this->assign('category',$category);
            $this->assign('court',$court);
            return $this->fetch();
        }
    }

    public function contact() {
        $id = input('id');
        $court = DB::name('FoodCourt')->where('isDelete',0)->where('id',$id)->find();
        $contact = Loader::model('contact')->where('isCourt',1)->where('courtId',$id)->where(['isDelete'=>0])->paginate(10);
        $type = config('contact_type');
        $this->assign('type',$type);
        $this->assign('lists',$contact);
        $this->assign('court',$court);
        $this->assign('pages',$contact->render());
        return $this->fetch();
    }

    public function contactadd() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params   = $request->param();
            // 獲取默認配置
            $default_config = getDefaultConfig();
            $category = array();
            if(!empty($params['cCategory'])){
                $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->where('id',$params['cCategory'])->find();
            }
            $save = array(
                'name'          => !empty($params['name'])?$params['name']:'',
                'number'        => !empty($params['number'])?$params['number']:'',
                'market'        => !empty($params['market'])?$params['market']:'',
                'cCategory'     => !empty($category['id'])?$category['id']:'',
                'cCategoryName' => !empty($category['name'])?$category['name']:'',
                'contactType'   => !empty($params['type'])?$params['type']:'',
                'rate'          => !empty($params['rate'])?$params['rate']:$default_config['payments_contact_default_rate'],
                'cycle'         => !empty($params['cycle'])?$params['cycle']:$default_config['payments_contact_default_cycle'],
                'address'       => !empty($params['address'])?$params['address']:'',
                'latitude'      => !empty($params['Latitude'])?$params['Latitude']:'',
                'longitude'     => !empty($params['Longitude'])?$params['Longitude']:'',
                'remark'        => !empty($params['remark'])?$params['remark']:'',
                'disable'       => !empty($params['disable'])?$params['disable']:'',
                'linkMans'      => !empty($params['linkMans'])?$params['linkMans']:'',
                'isCourt'       => 1,
                'courtId'       => !empty($params['court'])?$params['court']:'',
                // 美食廣場不需要餐桌
                'member'        => 0,
                'disable'       => 1,
                'isDelete'      => 0,
                'ctime'         => time(),
            );
            $user = array(
                'name'=>trim($params['username']),
                'nick'=>trim($params['nick']),
                'password'=>trim($params['password']),
                'repassword'=>trim($params['repassword']),
                'is_contact'=>1,
                'uid'=>2,
                'contact_number'=>trim($params['number']),
                'email'=>trim($params['mail']),
                'status'=>1,
                'create_time'=>time(),
            );
            // 图片上传
            $file = request()->file('image');
            $bg = request()->file('bg');
            if($file){
                // 调用上传方法
                $width  = config('Thumwidth');
                $height = config('Thumheight');
                $upload = img_create_small($file,$width,$height,"uploads/Thumbnail");
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $save['logoUrl'] = $upload['msg'];
                }else{
                    return $this->error($upload['msg']);
                }
            }
            if($bg){
                // 调用上传方法 保存原图
                $uploads = uploadPic($bg,'uploads/big');
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($uploads['code']==1){
                    $save['bgImageUrl'] = $uploads['msg'];
                }else{
                    return $this->error($uploads['msg']);
                }
            }
            if (loader::validate('Contact')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Contact')->getError());
            }
            if (loader::validate('User')->scene('register')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            $user['password']   = md5($params['password']);
            $user['repassword'] = md5($params['repassword']);
            $res = 1;
            Db::startTrans();
            try{
                Db::name('User')->strict(false)->insert($user);
                $Id = Loader::model('Contact')->strict(false)->insertGetId($save);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            Loader::model('SystemLog')->record("美食广场[".$params['court']."],添加餐廳ID:[{$Id}]");
            return $this->success('添加餐廳成功', Url::build('Court/index'));
        }else{
            $id = input('id');
            $court = DB::name('FoodCourt')->where('isDelete',0)->where('id',$id)->find();
            $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->order('ordnum asc')->select();
            $type = config('contact_type');
            $category = arrtree($customertype,"child");
            $this->assign('type',$type);
            $this->assign('category',$category);
            $this->assign('court',$court);
        }
        return $this->fetch();
    }

    public function contactedit() {
        $request = Request::instance();
        $id = $request->param('cid');
        if ($request->isPost()) {
            $params = $request->param();
            $params['id'] = $id;
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->where('id',$params['cCategory'])->find();
            $params['cCategory']     = $category['id'];
            $params['cCategoryName'] = $category['name'];
            $params['address']       = trim($params['address']);
            $params['latitude']      = trim($params['Latitude']);
            $params['longitude']     = trim($params['Longitude']);
            // 图片上传
            $file = request()->file('image');
            if($file){
                // 调用上传方法
                $width  = config('Thumwidth');
                $height = config('Thumheight');
                $upload = img_create_small($file,$width,$height,"uploads/Thumbnail");
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $params['logoUrl'] = $upload['msg'];
                }else{
                    return $this->error($upload['msg']);
                }
            }
            if (loader::validate('Contact')->scene('reset')->check($params) === false) {
                return $this->error(loader::validate('Contact')->getError());
            }

            if (($cateId = Loader::model('Contact')->contactEdit($params)) === false) {
                return $this->error(Loader::model('Contact')->getError());
            }
            Loader::model('SystemLog')->record("餐厅编辑,ID:[{$id}]");
            return $this->success('餐厅编辑成功', Url::build('contact/index'));
        }else{
            $contact = DB::name('contact')->where('isDelete',0)->where('id',$id)->find();
            $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->order('ordnum asc')->select();
            $tree = arrtree($customertype,"child");
            if(empty($contact)){
                return $this->error('请选择正确的餐厅');
            }
            $this->assign('type',$tree);
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }

    // 美食广场审核
    public function review() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params = $request->param();
            if($params['disable']==1){
                $res = 1;
                Db::startTrans();
                try{
                    Db::name('FoodCourt')->where('id',$params["id"])->update(['disable'=>$params['disable'],'retime'=>time()]);
                    Db::name('user')->where('contact_number',$params["number"])->update(['status'=>1]);
                    // 提交事务
                    Db::commit();    
                } catch (\Exception $e) {
                    $res = 0;
                    // 回滚事务
                    Db::rollback();
                }
                if($res){
                    return $this->success('審核成功!',url('court/index'));
                }else{
                    return $this->error('審核失敗!');
                }
            }else if($params['disable']==2){
                if(empty($params['reason'])){
                    return $this->error('请输入拒绝原因!');
                }else{
                    $res = Db::name('FoodCourt')->where('id',$params["id"])->update(['disable'=>$params['disable'],'reason'=>$params['reason'],'retime'=>time()]);
                    if($res){
                        return $this->success('申请已拒绝!',url('court/index'));
                    }else{
                        return $this->error('審核失敗!');
                    }
                }
            }
        }else{
            $court = DB::name('FoodCourt')->where('isDelete',0)->where('id',$id)->find();
            if(empty($court)){
                return $this->error('请选择正确的美食广场');
            }
            $type = config('contact_type');
            $court_number = $court['number'];
            $user    = DB::name('user')->where('is_contact',1)->where('contact_number',$court_number)->find();
            $this->assign('type',$type);
            $this->assign('user',$user);
            $this->assign('court',$court);
            return $this->fetch('court/review');
        }
    }

    public function courtWxQrcode(){
        $request = Request::instance();
        $params = $request->param();
        if($params['code']==0){
            $root_path = config('Rootpath');
            $court = Db::name('FoodCourt')->field('id,number,logoUrl')->where('number',$params['number'])->find();
            $logo = str_replace($root_path,ROOT_PATH . 'public',$court['logoUrl']);
            // $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
            $save = array();
            $res = 0;
            $suffix = url('wxweb/index/index',['courtNumber'=>$params['number']]);
            $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
            $return = add_wx_web_qrcode($url,$logo,'webcode');
            $code = $params['code']+1;
            $contact = DB::name('FoodCourt')->where('id',$params['id'])->update(['codeStatus'=>$code,'qrcode'=>$return['msg']]);
        }else if($params['code']==1||$params['code']==2){
            $code = $params['code']+1;
            $contact = DB::name('FoodCourt')->where('id',$params['id'])->update(['codeStatus'=>$code]);
        }
        if($contact){
            return $this->success('修改成功');
        }else{
            return $this->error('修改失敗');
        }
    }

    public function downzip(){
        $request = Request::instance();
        $params = $request->param();
        $member = DB::name('FoodCourt')->where('number',$params['number'])->select();
        $file = array();
        $down = array();
        if(!empty($member)){
            foreach ($member as $k => $v) {
                $file[] = array('name'=>$v['qrcode'],'save'=>$v['number'].'.jpg');
                $down = $v['number'].'.zip';
            }
            if(!empty($file)){
                zippic($file,$down);
            }else{
                return $this->error('没有找到该美食广场的二维码信息');
            }
        }else{
            return $this->error('没有找到该美食广场的餐桌信息');
        }
        
    }

    public function downsticker(){
        $request = Request::instance();
        $params = $request->param();
        $member = Db::name('FoodCourt')->where('number',$params['number'])->select();
        $file = array();
        $down = array();
        if(!empty($member)){
            $root_path = config('Rootpath');
            // foreach ($member as $k => $v) {
            $file = array();
            $down = md5(date('YmdHis').mt_rand(000000,999999)).'.zip';
            foreach ($member as $k => $v) {
                $tick = '';
                $name = md5(date('YmdHis').mt_rand(000000,999999));
                $qrname = $v['name'];
                $qrpath = str_replace($root_path,ROOT_PATH . 'public',$v['qrcode']);
                $back = ROOT_PATH . 'public/static/houtai/img/tick-bg.png';
                $floor = ROOT_PATH . 'public/static/houtai/img/tick-floor.png';
                $ttf = ROOT_PATH . 'public/static/houtai/fonts/msyh.ttf';
                resize_image($qrpath, $qrpath, 500, 500);
                // 添加二維碼到floor
                $water = new WaterMask();
                $water->setImg($floor);
                $water->horizontal = 'center';
                $water->vertical = 'middle';
                $water->waterType = 1;
                $water->transparent = 100;
                $water->waterImg = ROOT_PATH . 'public' .$qrpath;
                if(!is_dir(ROOT_PATH.'public/create/tmp/create1/'.date('Ymd'))){
                    mkdirs(ROOT_PATH.'public/create/tmp/create1/'.date('Ymd'));
                }
                $water->saveImg = 'public/create/tmp/create1/'.date('Ymd').'/'.$name;
                $res = $water->output();
                $tick1 = $res['path'];
                // 在floor加餐桌名
                // $swater = new WaterMask($tick1);
                $water->setImg($tick1);
                $water->horizontal = 'center';
                $water->vertical = 50;
                $water->waterType = 0;
                $water->transparent = 100;
                // iconv("UTF-8","GB2312",$qrname);
                $water->waterStr = $qrname;    
                $water->fontSize = 40;                  
                $water->fontColor = array(255,255,255); 
                $water->fontFile = $ttf;
                if(!is_dir(ROOT_PATH.'public/create/tmp/create2/'.date('Ymd'))){
                    mkdirs(ROOT_PATH.'public/create/tmp/create2/'.date('Ymd'));
                }
                $water->saveImg = 'public/create/tmp/create2/'.date('Ymd').'/'.$name;
                $res = $water->output();
                $tick2 = $res['path'];
                // 把图片合成到背景上
                // $water = new WaterMask($back);
                $water->setImg($back);
                $water->horizontal = 'center';
                $water->vertical = 240;
                $water->waterType = 1;
                $water->transparent = 100;
                $water->waterImg = $tick2;
                if(!is_dir(ROOT_PATH.'public/create/tmp/create3/'.date('Ymd'))){
                    mkdirs(ROOT_PATH.'public/create/tmp/create3/'.date('Ymd'));
                }
                $water->saveImg = 'public/create/tmp/create3/'.date('Ymd').'/'.$name;
                $res = $water->output();
                $tick = $res['path'];
                // echo $tick;die;
                $file[] = array('name'=>$tick,'save'=>basename($tick));
                unlink($tick1);
                unlink($tick2);
            }
            if(!empty($file)){
                ziptick($file,$down);
            }
        }else{
            return $this->error('没有找到该美食广场的餐桌信息');
        }
        
    }

    public function rewebqrcode(){
        $request = Request::instance();
        $params = $request->param();
        $root_path = config('Rootpath');
        $court = Db::name('FoodCourt')->field('id,number,logoUrl')->where('number',$params['number'])->find();
        $logo = str_replace($root_path,ROOT_PATH . 'public',$court['logoUrl']);
        $save = array();
        $res = 0;
        $suffix = url('wxweb/index/index',['courtNumber'=>$params['number']]);
        $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
        $return = add_wx_web_qrcode($url,$logo,'webcode');
        $contact = DB::name('FoodCourt')->where('id',$params['id'])->update(['qrcode'=>$return['msg']]);
        $this->success('生成二維碼成功,生成失敗個數'.$res);
    }

    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
        if (Loader::model('FoodCourt')->deleteCourt($id) === false) {
            return $this->error(Loader::model('FoodCourt')->getError());
        }
        Loader::model('SystemLog')->record("美食广场删除,ID:[{$id}]");
        return $this->success('美食广场删除成功', Url::build('Court/index'));
    }

    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function contactdestroy() {
        $request = Request::instance();
        $id = $request->param('id');
        $cid = $request->param('cid');
        if (Loader::model('Contact')->deleteContact($cid) === false) {
            return $this->error(Loader::model('Contact')->getError());
        }
        Loader::model('SystemLog')->record("餐厅删除,ID:[{$cid}]");
        return $this->success('餐厅删除成功', Url::build('court/contact',['id'=>$id]));
    }


}