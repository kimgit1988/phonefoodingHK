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
class Contact extends AdminBase {

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
        $contact = Loader::model('Contact')->where($where)->where(['isDelete'=>0])->order('id desc')->paginate(10,false,['query'=>$param]);
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
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->where('id',$params['cCategory'])->find();
            $params['cCategory']     = $category['id'];
            $params['cCategoryName'] = $category['name'];
            if($params['method']==0){
                $params['number'] = $params['selectnumber'];
            }else if($params['method']==1){
                $params['number'] = $params['setnumber'];
            }
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
            if (loader::validate('Contact')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Contact')->getError());
            }
            $default_config = getDefaultConfig();
            $params['rate']  = $default_config['payments_contact_default_rate'];
            $params['cycle'] = $default_config['payments_contact_default_cycle'];
            if (($cateId = Loader::model('Contact')->contactAdd($params)) === false) {
                return $this->error(Loader::model('Contact')->getError());
            }
            Loader::model('SystemLog')->record("添加餐厅,ID:[{$cateId}]");
            return $this->success('添加餐厅成功', Url::build('Contact/index'));
        }else{
            $user_list = DB::name('user')->field('nick,contact_number')->where(['status'=>1,'is_contact'=>1])->select();
            $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->order('ordnum asc')->select();
            $tree = arrtree($customertype,"child");
            $this->assign('list',$user_list);
            $this->assign('type',$tree);
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
            $printer = DB::name('Printer')
                ->alias('p')
                ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
                ->field('p.id,p.deviceNick,b.brand,b.brandNumber,b.fileName')
                ->where('p.contactNumber',$contact['number'])
                ->where('p.isDelete',0)
                ->order('p.id desc')
                ->select();
            $smprinter = DB::name('Printer')
                         ->alias('p')
                         ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
                         ->field('p.id,p.deviceNick,b.brand,b.brandNumber,b.fileName')
                         ->where('p.contactNumber',$contact['number'])
                         ->where('b.fileName','Sunmi')
                         ->where('p.isDelete',0)
                         ->order('p.id desc')
                         ->select();
            $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'customertype'])->order('ordnum asc')->select();
            $tree = arrtree($customertype,"child");
            if(empty($contact)){
                return $this->error('请选择正确的餐厅');
            }
            $this->assign('type',$tree);
            $this->assign('contact',$contact);
            $this->assign('printer',$printer);
            $this->assign('smprinter',$smprinter);
            return $this->fetch();
        }
    }

    // 餐厅审核
    public function review() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params = $request->param();
            if($params['disable']==1){
                // var_dump($params);
                if($params['member']>0){
                    $member = array();
                    for ($i=0; $i < $params['member']; $i++) {
                        //$membername   = ($i+1).'號';
                        $membername   = ($i+1);
                        $membernumber = $params["id"].'_'.($i+1);
                        $member[] = ['name'=>$membername,'number'=>$membernumber,'contactNumber'=>$params['number']];
                    }
                }
                $res = 1;
                Db::startTrans();
                try{
                    Db::name('contactMember')->insertAll($member);
                    Db::name('contact')->where('id',$params["id"])->update(['disable'=>$params['disable'],'retime'=>time()]);
                    Db::name('user')->where('contact_number',$params["number"])->update(['status'=>1]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    $res = 0;
                    // 回滚事务
                    Db::rollback();
                }
                if($res){
                    return $this->success('審核成功!',url('contact/index'));
                }else{
                    return $this->error('審核失敗!');
                }
            }else if($params['disable']==2){
                if(empty($params['reason'])){
                    return $this->error('请输入拒绝原因!');
                }else{
                    $res = Db::name('contact')->where('id',$params["id"])->update(['disable'=>$params['disable'],'reason'=>$params['reason'],'retime'=>time()]);
                    if($res){
                        return $this->success('申请已拒绝!',url('contact/index'));
                    }else{
                        return $this->error('審核失敗!');
                    }
                }
            }
        }else{
            $contact = DB::name('contact')->where('isDelete',0)->where('id',$id)->find();
            if(empty($contact)){
                return $this->error('请选择正确的餐厅');
            }
            $type = config('contact_type');
            $contact_number = $contact['number'];
            $user    = DB::name('user')->where('is_contact',1)->where('contact_number',$contact_number)->find();
            $this->assign('type',$type);
            $this->assign('user',$user);
            $this->assign('contact',$contact);
            return $this->fetch('contact/review');
        }
    }

    public function contactQrcode(){
        $request = Request::instance();
        $params = $request->param();
        if($params['code']==0){
            $member = Db::name('contactMember')->field('id')->where('contactNumber',$params['number'])->select();
            $save = array();
            $res = 0;
            foreach ($member as $key => $val) {
                $return = add_qrcode($params['id'],$val['id']);
                $save   = DB::name('contactMember')->where('id',$val['id'])->where('contactNumber',$params['number'])->update(['qrcode'=>$return['path']]);
                if($save===false){
                    $res = $res+1;
                }
            }
            $member = Db::name('contactMember')->where('contactNumber',$params['number'])->select();
            // $file = array();
            // foreach ($member as $k => $v) {
            //     $file[] = array('name'=>$v['qrcode'],'save'=>$v['contactNumber'].'_'.$v['name'].'.jpg');
            //     $down = $v['contactNumber'].'.zip';
            // }
            $code = $params['code']+1;
            $contact = DB::name('contact')->where('id',$params['id'])->update(['codeStatus'=>$code,'qrtime'=>time()]);
            // zippic($file,$down);
        }else if($params['code']==1||$params['code']==2){
            $code = $params['code']+1;
            $contact = DB::name('contact')->where('id',$params['id'])->update(['codeStatus'=>$code,'qrtime'=>time()]);
        }
        if($contact){
            return $this->success('修改成功');
        }else{
            return $this->error('修改失敗');
        }
    }

    public function contactWxQrcode(){
        $request = Request::instance();
        $params = $request->param();
        if($params['code']==0){
            $root_path = config('Rootpath');
            $contact = Db::name('contact')->field('id,number,logoUrl')->where('number',$params['number'])->find();
            $member = Db::name('contactMember')->field('id,number')->where('contactNumber',$params['number'])->select();
            $logo = str_replace($root_path,ROOT_PATH . 'public',$contact['logoUrl']);
            // $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
            $save = array();
            $res = 0;
            foreach ($member as $key => $val) {
                $suffix = url('wxweb/index/index',['contactNo'=>$params['number'],'contactMemberNo'=>$val['number']]);
                $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
                $return = add_wx_web_qrcode($url,$logo,'webcode');
                $save   = DB::name('contactMember')->where('id',$val['id'])->where('contactNumber',$params['number'])->update(['qrcode'=>$return['msg']]);
                if($save===false){
                    $res = $res+1;
                }
            }
            // $member = Db::name('contactMember')->where('contactNumber',$params['number'])->select();
            // $file = array();
            // foreach ($member as $k => $v) {
            //     $file[] = array('name'=>$v['qrcode'],'save'=>$v['contactNumber'].'_'.$v['name'].'.jpg');
            //     $down = $v['contactNumber'].'.zip';
            // }
            $code = $params['code']+1;
            $contact = DB::name('contact')->where('id',$params['id'])->update(['codeStatus'=>$code]);
            // zippic($file,$down);
        }else if($params['code']==1||$params['code']==2){
            $code = $params['code']+1;
            $contact = DB::name('contact')->where('id',$params['id'])->update(['codeStatus'=>$code]);
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
        $member = Db::name('contactMember')->where('contactNumber',$params['number'])->select();
        $file = array();
        $down = array();
        if(!empty($member)){
            foreach ($member as $k => $v) {
                $file[] = array('name'=>$v['qrcode'],'save'=>$v['contactNumber'].'_'.$v['number'].'.jpg');
                $down = $v['contactNumber'].'.zip';
            }
            if(!empty($file)){
                zippic($file,$down);
            }else{
                return $this->error('没有找到该餐厅的二维码信息');
            }
        }else{
            return $this->error('没有找到该餐厅的餐桌信息');
        }

    }

    public function downsticker(){
        //设置最大内存和超时时间，支持150张餐桌二维码打印
        ini_set('memory_limit','300M');
        set_time_limit(120);
        $request = Request::instance();
        $params = $request->param();
        $member = Db::name('contactMember')->where('contactNumber',$params['number'])->select();
        $contact = Db::name('contact')->where('number',$params['number'])->find();
        $file = array();
        $down = array();
        if(!empty($member)){
            $root_path = config('Rootpath');
            // foreach ($member as $k => $v) {
            $file = array();
            $down = md5(date('YmdHis').mt_rand(000000,999999)).'.zip';
            $contact_logo = str_replace($root_path,ROOT_PATH . 'public',$contact['logoUrl']);
            //因为商家logo大小不一，这里需要生成一个180大小带qr的别名文件，用来生成二维码
            $file_info = pathinfo(ROOT_PATH . 'public' . $contact_logo);
            $contact_logo_qr =$file_info['dirname'].DS.$file_info['filename'].'_qr.'.$file_info['extension'];
            resize_image(ROOT_PATH . 'public' . $contact_logo, $contact_logo_qr, 180, 180);
            foreach ($member as $k => $v) {
                $tick = '';
                $name = md5(date('YmdHis').mt_rand(000000,999999));
                $qrname = $v['name'];
                $qrpath = str_replace($root_path,ROOT_PATH . 'public',$v['qrcode']);
                if(!file_exists(ROOT_PATH . 'public' . $qrpath)){
                    return $this->error('請先生成公眾號二維碼');break;
                }
                $back = ROOT_PATH . 'public/static/houtai/img/tick-bg.png';
                $floor = ROOT_PATH . 'public/static/houtai/img/tick-floor.png';
                $ttf = ROOT_PATH . 'public/static/houtai/fonts/msyh.ttf';
                if($contact['id']==33) resize_image(ROOT_PATH . 'public'.$v['qrcode'], ROOT_PATH . 'public'.$v['qrcode'], 410, 410);
                // 添加二維碼到floor
                $water = new WaterMask();
                $water->setImg($floor);
                $water->horizontal = 270;
                $water->vertical = 360;
                $water->waterType = 1;
                $water->transparent = 100;
                $water->waterImg = ROOT_PATH . 'public' . $qrpath;
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
                $water->vertical = 825;
                $water->waterType = 0;
                $water->transparent = 100;
                // iconv("UTF-8","GB2312",$qrname);
                $water->waterStr = '枱號 '.$qrname;
                $water->fontSize = 40;
                $water->fontColor = array(255,255,255);
                $water->fontFile = $ttf;
                if(!is_dir(ROOT_PATH.'public/create/tmp/create2/'.date('Ymd'))){
                    mkdirs(ROOT_PATH.'public/create/tmp/create2/'.date('Ymd'));
                }
                $water->saveImg = 'public/create/tmp/create2/'.date('Ymd').'/'.$name;
                $res = $water->output();
                $tick2 = $res['path'];
                // 添加商家logo到floor
                $water->setImg($tick2);
                $water->horizontal = 161;
                $water->vertical = 927;
                $water->waterType = 1;
                $water->transparent = 100;
                $water->waterImg = $contact_logo_qr;
                if(!is_dir(ROOT_PATH.'public/create/tmp/create/'.date('Ymd'))){
                    mkdirs(ROOT_PATH.'public/create/tmp/create/'.date('Ymd'));
                }
                $water->saveImg = 'public/create/tmp/create/'.date('Ymd').'/'.$name;
                $res = $water->output();
                $tick = $res['path'];
                // 把图片合成到背景上
                //$water->setImg($back);
                //$water->horizontal = 'center';
                ////$water->vertical = 240;
                //$water->waterType = 1;
                //$water->transparent = 100;
                //$water->waterImg = $tick3;
                //if(!is_dir(ROOT_PATH.'public/create/tmp/create/'.date('Ymd'))){
                //    mkdirs(ROOT_PATH.'public/create/tmp/create/'.date('Ymd'));
                //}
                //$water->saveImg = 'public/create/tmp/create/'.date('Ymd').'/'.$name;
                //$res = $water->output();
                //$tick = $res['path'];
                // echo $tick;die;
                $file[] = array('name'=>$tick,'save'=>basename($tick));
                unlink($tick1);
                unlink($tick2);
            }
            if(!empty($file)){
                ziptick($file,$down);
            }
        }else{
            return $this->error('没有找到该餐厅的餐桌信息');
        }

    }

    public function rewebqrcode(){
        $request = Request::instance();
        $params = $request->param();
        $root_path = config('Rootpath');
        $suffix = config('web_qrcode.suffix');
        $contact = Db::name('contact')->field('id,number,logoUrl')->where('number',$params['number'])->find();
        $member = Db::name('contactMember')->field('id,number')->where('contactNumber',$params['number'])->select();
        //把html图片地址改成php可用
        if(preg_match('/^http:\/\//',$contact['logoUrl'])){
            $logo = $contact['logoUrl'];
        }else{
            $logo = str_replace($root_path,ROOT_PATH . 'public',$contact['logoUrl']);
        }
        $save = array();
        $res = 0;
        $logo = stripos($logo,'/public/')!==false?str_replace("/public/","/",$logo):$logo;
        foreach ($member as $key => $val) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix.'/contactNo/'.$params['number'].'/contactMemberNo/'.$val['number'];
            $url = stripos($url,'/public/')!==false?str_replace("/public/","/",$url):$url;
            $return = add_wx_web_qrcode($url,$logo,'webcode');
            if($return['code']==1){
                $save   = DB::name('contactMember')->where('id',$val['id'])->where('contactNumber',$params['number'])->update(['qrcode'=>$return['msg']]);
                if($save===false){
                    $res = $res+1;
                }
            }else{
                $res = $res+1;
            }
        }
        $this->success('生成二維碼成功,生成失敗個數'.$res);
    }

    public function reqrcode(){
        $request = Request::instance();
        $params = $request->param();
        $member = Db::name('contactMember')->field('id')->where('contactNumber',$params['number'])->select();
        $save = array();
        $res = 0;
        foreach ($member as $key => $val) {
            $return = add_qrcode($params['id'],$val['id']);
            if($return['code']==1){
                $save   = DB::name('contactMember')->where('id',$val['id'])->where('contactNumber',$params['number'])->update(['qrcode'=>$return['path']]);
                if($save===false){
                    $res = $res+1;
                }
            }else{
                $res = $res+1;
            }
        }
        $this->success('生成二維碼成功,獲取失敗個數'.$res);
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
        if (Loader::model('Contact')->deleteContact($id) === false) {
            return $this->error(Loader::model('Contact')->getError());
        }
        Loader::model('SystemLog')->record("餐厅删除,ID:[{$id}]");
        return $this->success('餐厅删除成功', Url::build('contact/index'));
    }


}