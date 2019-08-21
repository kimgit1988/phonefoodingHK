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

class Base extends Controller {
    // 不需要權限
    public function getcategory(){
        $contactNumber = input("contactNumber");
        $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$contactNumber)->select();
        $this->success($category);
    }

    public function getspec(){
        $contactNumber = input("contactNumber");
        $showid = array();
        $speclist = array();
        $spec = DB::name('spec')->where('isDelete',0)->where('spec_pid',0)->where('contactNumber',$contactNumber)->select();
        foreach ($spec as $key => $val) {
            $showid[] = $val['id'];
        }
        if(!empty($showid)){
            $showid = implode(',', $showid);
            $speclist = DB::name('spec')->where('isDelete',0)->where('spec_pid','in',$showid)->select();
            foreach ($speclist as $k => $v) {
                $spec[] = $v;
            }
            $speclist = array();
            foreach ($spec as $key => $value) {
                $speclist[$value['id']] = $value;
            }
            foreach ($speclist as $key => $val) {
                if($val['spec_pid']!=0){
                    $speclist[$val['spec_pid']]['_child'][$val['id']] = $val;
                    unset($speclist[$key]);
                }
            }
        }
        $this->success($speclist);
    }

    public function getspeclist(){
        $contactNumber = input("contactNumber");
        $spec = DB::name('spec')->where('isDelete',0)->where('spec_pid',0)->where('contactNumber',$contactNumber)->select();
        $this->success($spec);
    }


    // 圖片上傳
    public function uploadFoodsImg(){
        $request = Request::instance();
        $file = request()->file('image');
        $return = array();
        if($file){
            // 获取缩略图宽高
            $width  = config('Thumwidth');
            $height = config('Thumheight');
            // 保存缩略图
            $thumb = img_create_small($file,$width,$height,"uploads/Thumbnail");
            // 调用上传方法 保存原图
            $uploads = uploadPic($file,'uploads/foods');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($thumb['code']==1){
                $return['thumb'] = $thumb['msg'];
            }else{
                return $this->error($thumb['msg']);
            }
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($uploads['code']==1){
                $return['code'] = 1;
                $return['msg']  = $uploads['msg'];
                return  $return;
            }else{
                return $this->error($uploads['msg']);
            }
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

    public function downtempexl(){
        $type = input('type');
        switch ($type) {
            case 'food':
                $name = '菜品导入模板.xls';
                $filename = ROOT_PATH.'public/static/assets/xls/food-temp.xls';
                break;
            case 'spec':
                $name = '规格导入模板.xls';
                $filename = ROOT_PATH.'public/static/assets/xls/spec-temp.xls';
                break;
            case 'trade':
                $name = '菜品分类导入模板.xls';
                $filename = ROOT_PATH.'public/static/assets/xls/trade-temp.xls';
                break;
            default:
            break;
        }
        if(isset($name)&&isset($filename)){
            header('Content-Disposition: attachment; filename='.$name);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Length: '.filesize($filename));
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($filename); 
        }else{
            echo '请选择需要下载的模板';
        }
    }

    public function downAgreement(){
        vendor('phpword.PHPWord');
        $number = input('number');
        $contact = DB::name('contact')->where('number',$number)->find();
        if(empty($contact['agreement'])){
            $name = 'agreement-'.md5($number).'.docx';
            $PHPWord = new \PHPWord();
            $filename = ROOT_PATH.'public/docTemplate/agreement-temp.docx';
            $tempPlete = $PHPWord->loadTemplate($filename);
            $tempPlete->setValue('CONTACT_NUMBER',$number);
            if(!is_dir(ROOT_PATH.'public/uploads/word')){
                mkdirs(ROOT_PATH.'public/uploads/word');
            }
            $tempPlete->save(ROOT_PATH.'public/uploads/word/'.$name); // 文件通过浏览器下载
            $downName = ROOT_PATH.'public/uploads/word/'.$name;
            $fp=fopen($downName,"r"); 
            $file_size=filesize($downName); 
            // //下载文件需要用到的头 
            Header("Content-type: application/octet-stream"); 
            Header("Accept-Ranges: bytes"); 
            Header("Accept-Length:".$file_size); 
            header('Content-Disposition: attachment; filename='.$name);
            $buffer=1024;  //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）
            $file_count=0; //读取的总字节数
            // //向浏览器返回数据 
            while(!feof($fp) && $file_count<$file_size){ 
            $file_con=fread($fp,$buffer); 
            $file_count+=$buffer; 
            echo $file_con;
            } 
            fclose($fp);
             
            //下载完成后删除压缩包，临时文件夹
            if($file_count >= $file_size)
            {
                unlink($downName);
                // exec("rm -rf ".$fileDir);
            }

        }else{
            $name = basename($contact['agreement']);
            $downName = ROOT_PATH.$contact['agreement'];

        // $name = '餐廳協議模板.docx';
        // $filename = ROOT_PATH.'public/static/assets/doc/agreement-temp.docx';
        header('Content-Disposition: attachment; filename='.$name);
        Header("Content-type: application/octet-stream"); 
        header('Content-Length: '.filesize($downName));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($downName); 
        }
        
    }

    public function uploadAgreement(){
        $file = request()->file('file');
        $number = input('number');
        $return = array('code'=>0,'msg'=>'上傳失敗!');
        if($file){
            $date = date('Ymd');
            if(!is_dir(ROOT_PATH.'public/uploads/agreement/'.date('Ymd'))){
                mkdirs(ROOT_PATH.'public/uploads/agreement/'.date('Ymd'));
            }
            $rand = time().rand(1000,9999);
            $info = $file->move(ROOT_PATH.'public/uploads/agreement/'.date('Ymd'),md5($rand)); 
            $name = $info->getSaveName();
            $savepath = 'public/uploads/agreement/'.date('Ymd').'/'.$name;
            $contact = DB::name('contact')->where('number',$number)->update(['agreement'=>$savepath]);
            if($contact){
                $return['code'] = 1;
                $return['msg'] = '上傳成功';
            }
        }
        return $return;
    }

    public function getgoods(){
        $contactNumber = input("contactNumber");
        $goods = DB::name('goods')->field('id,name,number')->where('isDelete',0)->where('disable',1)->where('contactNumber',$contactNumber)->select();
        $this->success($goods);
    }

    public function getprintinfo(){
        $printer = input("printer");
        $info = DB::name('PrinterBrand')->field('id,type,shopNumber,apiKey')->where('isDelete',0)->where('id',$printer)->find();
        $this->success($info);
    }

    public function getcontactprinter(){
        $contact = input("contact");
        $printer = DB::name('Printer')
        ->alias('p')
        ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
        ->field('p.id,p.deviceNick,b.brand,b.brandNumber,b.fileName')
        ->where('p.contactNumber',$contact)
        ->where('p.isDelete',0)
        ->order('p.id desc')
        ->select();
        $this->success($printer);
    }

    public function getprinterinfo(){
        $contact = input("contact");
        $printer = input("printer");
        $return = array();
        $where = array();
        $list = array();
        $contactinfo = DB::name('Contact')->field('name,printerId')->where('number',$contact)->where('isDelete',0)->find();
        $category = DB::name('Category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$contact)->where('isDelete',0)->select();
        foreach ($category as $key => $val) {
            $list[$val['id']] = $val;
        }
        $foods = DB::name('Goods')->field('id,name,categoryId,thumbnailUrl,printerId')->where('contactNumber',$contact)->where('isDelete',0)->select();
        foreach ($foods as $key => $val) {
            $list[$val['categoryId']]['_food'][] = $val;
        }
        $return['category'] = $category;
        $return['food'] = $foods;
        $return['list'] = $list;
        $return['contact'] = $contactinfo['printerId'];
        $return['jsonlist'] = json_encode($list);
        $return['jsonfood'] = json_encode($foods);
        $this->success($return);
    }

    function selectlist(){
        $params = input('param.');
        $return = array();
        $where = array();
        $cwhere = array();
        $list = array();
        if(!empty($params['category'])){
            $where['categoryId'] = $params['category'];
            $cwhere['id'] = $params['category'];
        }
        $category = DB::name('Category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$params['contact'])->where('isDelete',0)->where($cwhere)->select();
        foreach ($category as $key => $val) {
            $list[$val['id']] = $val;
        }
        if(!empty($params['food'])){
            $where['id'] = $params['food'];
        }
        if($params['relation']!='false'&&$params['relation']!=false){
            $where['printerId'] = $params['printer'];
        }
        $foods = DB::name('Goods')->field('id,name,categoryId,thumbnailUrl,printerId')->where($where)->where('contactNumber',$params['contact'])->where('isDelete',0)->select();
        foreach ($foods as $key => $val) {
            $list[$val['categoryId']]['_food'][] = $val;
        }
        $return['list'] = $list;
        $this->success($return);
    }

    public function getContactDepartment(){
        $contact = input("contact");
        $department = DB::name('ContactDepartment')
        ->field('id,name')
        ->where('contactNumber',$contact)
        ->where('isDelete',0)
        ->order('id desc')
        ->select();
        $this->success($department);
    }
}