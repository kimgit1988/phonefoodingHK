<?php
// 应用公共文件
use think\Db;
use think\Lang;
if(empty(\think\Cookie::get('think_var'))){
    \think\Cookie::set('think_var','zh-tw');
}
// 设置允许的语言，如果需要多种，在这里自行添加
Lang::setAllowLangList(think\Config::get("lang_list"));
header('Content-Type:text/html;charset=utf-8');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
/**
 * excel表格导出
 * @param string $fileName 文件名称
 * @param array $headArr 表头名称
 * @param array $data 要导出的数据
 * @param intergal $type 输出方式 1在浏览器 2在后台
 * @param string $filePath 输出地址(后台)
 * @author ki-yang  */
function excelExport($fileName = '', $headArr = [], $data = [], $type=1, $filePath='/') {
    $temp = ROOT_PATH.'public/static/assets/mobile/xls/temp1.xls';
    //读取文件
    if (!file_exists($temp)) {
        exit("找不到该文件");
    }
    vendor("PHPExcel.PHPExcel");
    // $objPHPExcel = new \PHPExcel();
    $objReader = \PHPExcel_IOFactory::createReader ( 'Excel5' );
    $objPHPExcel = $objReader->load ($temp);
    $objActSheet = $objPHPExcel->getActiveSheet ();
    $objActSheet->setCellValue('B2',$headArr['name']);
    $objActSheet->setCellValue('D2',$headArr['number']);
    $objActSheet->setCellValue('F2',$headArr['time']);
    $fileName .= "-" . date("Ymd",time()) . ".xls";
    $line = 65;
    $rank = 5;
    $theline = chr($line);
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                //'color' => array('argb' => 'FFFF0000'),
            ),
        ),
    );
    // 输出内容
    foreach($data as $key => $val) {
        foreach($val as $k => $v){
            $objActSheet->setCellValue($theline.$rank,$k);
            if($v==1){
                $objActSheet->getStyle($theline.$rank)->applyFromArray($styleArray);
            }else{
                $end = chr($line+$v);
                $objActSheet->getStyle($theline.$rank.':'.$end.$rank)->applyFromArray($styleArray);
            }
            $line += $v;
            $theline = chr($line);
            $objActSheet->getStyle('F'.$rank)->applyFromArray($styleArray);
        }
        $rank++;
        $line = 65;
        $theline = chr($line);
    }
    $fileName = iconv("utf-8", "gb2312", $fileName); // 重命名表
    if($type==1){
        // 下载到浏览器开始
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$fileName);
        header('Cache-Control: max-age=0');
        //下载到浏览器结束
    }
    // // $elx->getProperties()->setTitle("Office 2007 XLSX Test Document");
    // // header('Content-Type: application/vnd.ms-excel');
    // // header("Content-Disposition: attachment;filename=".$fileName);
    // // header('Cache-Control: max-age=0');
    ob_end_clean();//清除缓冲区,避免乱码

    // // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');
    // // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0
    $objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' ); //在内存中准备一个excel2003文件
    if($type==1){
        $objWriter->save('php://output');
    }else{
        if(!is_dir(ROOT_PATH.'public'.$filePath)){
            mkdirs(ROOT_PATH.'public'.$filePath);
        }
        // 尝试保持在服务器
        $objWriter->save(ROOT_PATH.'public'.$filePath.$fileName);
        $res = ROOT_PATH.'public'.$filePath.$fileName;
        return $res;
    }
    exit();
}

function add_wx_web_qrcode($url,$logo,$path){
    Vendor('phpqrcode.phpqrcode');
    if(empty($logo)||!file_exists($logo)){
        $logo=ROOT_PATH . 'public/static/assets/img/head.png';
    }
    //带LOGO
    $errorCorrectionLevel = 'M';//容错级别
    $matrixPointSize = 10;//生成图片大小
    //生成二维码图片
    $object = new \QRcode();
    $picture_name=md5(date('YmdHis').rand(0000,9999));
    //临时文件夹
    if(!is_dir(ROOT_PATH.'public/uploads/tmp/'.$path.'/'.date('Ymd'))){
        mkdirs(ROOT_PATH.'public/uploads/tmp/'.$path.'/'.date('Ymd'));
    }
    if(!is_dir(ROOT_PATH.'public/uploads/'.$path.'/'.date('Ymd'))){
        mkdirs(ROOT_PATH.'public/uploads/'.$path.'/'.date('Ymd'));
    }
    $nologo = ROOT_PATH . 'public/uploads/tmp/'.$path.'/'.date('Ymd').'/'.$picture_name.'.png';
    // $ad = 'erweima/'.$users_id.'.jpg';
    $object->png($url, $nologo, $errorCorrectionLevel, $matrixPointSize, 0);
    // $logo = ROOT_PATH . 'public/2.jpg';//准备好的logo图片
    $QR = ROOT_PATH . 'public/uploads/tmp/'.$path.'/'.date('Ymd').'/'.$picture_name.'.png';//已经生成的原始二维码图

    if ($logo !== FALSE) {
        $QR = imagecreatefromstring(file_get_contents($QR));
        $logo = imagecreatefromstring(file_get_contents($logo));
        if (imageistruecolor($logo))
        {
            imagetruecolortopalette($logo, false, 65535);//添加这行代码来解决颜色失真问题
        }
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width/$logo_qr_width;
        $logo_qr_height = $logo_height/$scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        $from_height = ($QR_height - $logo_qr_height) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_height, 0, 0, $logo_qr_width,
            $logo_qr_height, $logo_width, $logo_height);
    }
    // 输出图片  带logo图片
    imagepng($QR, ROOT_PATH.'public/uploads/'.$path.'/'.date('Ymd').'/'.$picture_name.'.png');
    unlink(ROOT_PATH.'public/uploads/tmp/'.$path.'/'.date('Ymd').'/'.$picture_name.'.png');
    // 读取根路径配置
    $root_path = config('Rootpath');
    $return['msg'] = $root_path.'/uploads/'.$path.'/'.date('Ymd').'/'.$picture_name.'.png';
    $return['code'] = 1;
    return $return;


    //不带LOGO
    // Vendor('phpqrcode.phpqrcode');
    // //生成二维码图片
    // $object = new \QRcode();
    // $url='http://www.shouce.ren/';//网址或者是文本内容
    // $level=3;
    // $size=4;
    // $ad = 'erweima/'.$users_id.'.jpg';
    // $errorCorrectionLevel =intval($level) ;//容错级别
    // $matrixPointSize = intval($size);//生成图片大小
    // $object->png($url,  $ad, $errorCorrectionLevel, $matrixPointSize, 2);
}

// 创建文件夹方法
function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
    if (!mkdirs(dirname($dir), $mode)) return FALSE;
    return @mkdir($dir, $mode);
}

/**
 * @param $attachment
 * @param $email
 * @param $userName
 * @param $title
 * @param $body
 * @return mixed
 * @throws Exception
 */
function sendEmail($attachment, $email, $userName, $title, $body){
    $mail = new sendmail\PHPMailer(); //建立邮件发送类
    $mail->CharSet = config("smtp_config.smtp_charset");
    $mail->IsSMTP(); // 使用SMTP方式发送
    $mail->Host = config("smtp_config.smtp_host");//SMTP服务器
    $mail->SMTPAuth = true; // 启用SMTP验证功能
    $mail->Username =  config("smtp_config.smtp_user");//SMTP服务器的用户帐号
    $mail->Password = config("smtp_config.smtp_pass"); // 密码
    $mail->SMTPSecure = "ssl";// 使用ssl协议方式
    $mail->Port = 465;// 163邮箱的ssl协议方式端口号是465/994
    $mail->From = config("smtp_config.smtp_user"); //邮件发送者email地址
    $mail->FromName = config("smtp_config.smtp_name"); //发件人名称
    $mail->AddAddress($email,$userName);//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
    if(!empty($attachment)){
        $mail->AddAttachment($attachment); // 添加附件
    }
    $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
    $mail->Subject = $title; //邮件标题
    $mail->Body = $body; //邮件内容，上面设置HTML，则可以是HTML
    if(!$mail->Send())
    {
        $return['code'] = 0;
        $return['msg']  = "错误原因: " . $mail->ErrorInfo;
        //return $return;
        throw new \Exception("错误原因: " . $mail->ErrorInfo);
    }else{
        $return['code'] = 1;
        $return['msg']  = "发送成功";
        return $return;
        exit;
    }
}

// 文件名 收件地址 邮件标题 邮件内容 表头 表内容 收件人姓名
/**
 * @param $fileName
 * @param $email
 * @param string $title
 * @param string $body
 * @param array $header
 * @param array $data
 * @param string $userName
 * @return mixed
 * @throws Exception
 */
function sendExl($fileName,$email,$title='',$body='',$header=[],$data=[],$userName=''){
    $exl = excelExport($fileName, $header, $data, 2, '/uploads/tmp/'.date("Ymd").DS);
    $send = sendEmail($exl, $email, $userName, $title, $body);
    return $send;
}

function getcommission($price){
    $zid=session('mar_user.zid');
    $user = DB::name('user')->where('zid',$zid)->find();
    $commission = floor($price*$user['commission'])/100;
    return $commission;
}

//生成base64格式图片的二维码
function create_qrcode($code = 'asdasd'){
    Vendor('phpqrcode.phpqrcode');
    $level = 'M';// 纠错级别：L、M、Q、H
    $size = 5;// 点的大小：1到10,用于手机端4就可以了
    $QRcode = new \QRcode();
    ob_start();
    $QRcode->png($code,false,$level,$size,1);
    $imageString = base64_encode(ob_get_contents());
    ob_end_clean();
    return "data:image/jpg;base64,".$imageString;
}

/**
 * @param $files
 * @param $down
 */
function zippic($files,$down){
    $zip = new \ZipArchive;
    // $files = array('/mealOrderingSys/public/uploads/qrcode/20180705/1495b25f652113acb12bef1f211fb0fb.jpg','/mealOrderingSys/public/uploads/qrcode/20180705/36ec352fbd3b4ed6678ccae14ed7dc51.jpg');
    $zipName = ROOT_PATH . 'public/down/qrcode/'.$down;
    $create = $zip->open($zipName, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE);
    if ($create!==TRUE) {
        exit('无法打开文件，或者文件创建失败');
    }
    // 是否有二维码添加进zip中 没有会报错所以不输出
    $addFile = false;
    foreach($files as $value){
        $val = iconv('utf-8','GB2312', $value['name']);
        $root_path = config('Rootpath');
        $val = str_replace($root_path,ROOT_PATH . 'public',$val);
        // $path = explode('/',  dirname($val));
        // $ymd = end($path);
        // $val = ROOT_PATH . 'public/uploads/qrcode/' .$ymd .'/' .basename($val);
        //$attachfile = $attachmentDir . $val['filepath']; //获取原始文件路径
        if(file_exists($val)){
            //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
            //若不希望带有路径，则需要该函数的第二个参数
            $zip->addFile($val, $value['save']);//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
            //有文件被添加 可以下载
            $addFile = true;
        }

    }
    if($addFile){
        $zip->close();//关闭
        if(!file_exists($zipName)){
            exit("无法找到文件"); //即使创建，仍有可能失败
        }
        //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Type: text/html; charset=UTF-8");
        header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
        ob_clean();
        // flush();
        @readfile($zipName);
        unlink($zipName);
    }else{
        exit("二维码丢失请重新获取");
    }

}

function ziptick($files,$down){
    $zip = new \ZipArchive;
    if(!is_dir( ROOT_PATH . 'public/down/tick/')){
        mkdirs( ROOT_PATH . 'public/down/tick/');
    }
    // $files = array('/mealOrderingSys/public/uploads/qrcode/20180705/1495b25f652113acb12bef1f211fb0fb.jpg','/mealOrderingSys/public/uploads/qrcode/20180705/36ec352fbd3b4ed6678ccae14ed7dc51.jpg');
    $zipName = ROOT_PATH . 'public/down/tick/'.$down;
    $create = $zip->open($zipName, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE);
    if ($create!==TRUE) {
        exit('无法打开文件，或者文件创建失败');
    }
    // 是否有二维码添加进zip中 没有会报错所以不输出
    $addFile = false;
    foreach($files as $value){
        $val = iconv('utf-8','GB2312', $value['name']);
        // $root_path = config('Rootpath');
        // $val = str_replace($root_path,ROOT_PATH . 'public',$val);
        // $path = explode('/',  dirname($val));
        // $ymd = end($path);
        // $val = ROOT_PATH . 'public/uploads/qrcode/' .$ymd .'/' .basename($val);
        //$attachfile = $attachmentDir . $val['filepath']; //获取原始文件路径
        if(file_exists($val)){
            //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
            //若不希望带有路径，则需要该函数的第二个参数
            $zip->addFile($val, $value['save']);//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
            //有文件被添加 可以下载
            $addFile = true;
        }

    }
    if($addFile){
        $zip->close();//关闭
        if(!file_exists($zipName)){
            exit("无法找到文件"); //即使创建，仍有可能失败
        }
        //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Type: text/html; charset=UTF-8");
        header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
        ob_clean();
        // flush();
        @readfile($zipName);
        unlink($zipName);
    }else{
        exit("二维码丢失请重新获取");
    }

}

//保存blog格式的原图
function img_create($file, $path) {
    $return = array();
    if($file){
        // 读取根路径配置
        $root_path = config('Rootpath');
        //得到原始大图片
        $image = \think\Image::open($file);
        // 返回图片的宽度
        $imgwidth = $image->width();
        // 返回图片的高度
        $imgheight = $image->height();
        // 返回图片类型
        $imgtype   = $image->type();
        switch ($imgtype) {
            // 图像类型判断，获得后缀名
            case 'jpeg':
                $type = '.jpg';
                break;

            case 'png':
                $type = '.png';
                break;

            case 'gif':
                $type = '.gif';
                break;
        }
        // 保持缩略图不能自动创建路径(TP的原因),需要手动判断路径缺少则创建
        if(!is_dir(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd"))){
            mkdirs(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd"));
        }
        // 获取缩略图名
        $name = getTumbName();
        // 保持缩略图
        $info = $image->thumb($imgwidth, $imgheight)->save(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd").'/'.$name.$type);
        $return['code'] = 1;
        $return['msg'] = $root_path.'/'.$path.'/'.date("Ymd")."/".$name.$type;
        return $return;
    }else{
        $return['code'] = 0;
        $return['msg']  = '未找到图片';
        return $return;
    }
}


// 缩略图方法 $img = img_create_small($file,150,100,"Thumbnail");(已测试)
function img_create_small($file, $width, $height, $path) {//原始大图地址，缩略图宽度，高度，缩略图名称 比例不变放大到宽高其中一个达到设置
    $return = array();
    if($file){
        // 读取根路径配置
        $root_path = config('Rootpath');
        //得到原始大图片
        $image = \think\Image::open($file);
        // 返回图片的宽度
        $imgwidth = $image->width();
        // 返回图片的高度
        $imgheight = $image->height();
        // 返回图片类型
        $imgtype   = $image->type();
        switch ($imgtype) {
            // 图像类型判断，获得后缀名
            case 'jpeg':
                $type = '.jpg';
                break;

            case 'png':
                $type = '.png';
                break;

            case 'gif':
                $type = '.gif';
                break;
        }
        // 保持缩略图不能自动创建路径(TP的原因),需要手动判断路径缺少则创建
        if(!is_dir(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd"))){
            mkdirs(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd"));
        }
        // 获取缩略图名
        $name = getTumbName();
        // 保持缩略图
        $info = $image->thumb($width, $height)->save(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd").'/'.$name.$type);
        $return['code'] = 1;
        $return['msg'] = $root_path.'/'.$path.'/'.date("Ymd")."/".$name.$type;
        return $return;
    }else{
        $return['code'] = 0;
        $return['msg']  = '未找到图片';
        return $return;
    }
}

// 生成图片名
function getTumbName(){
    return $name = md5(date('His').rand(100,999));
}

/**
 * 判断字符串是否base64编码
 */
function is_base64_picture($str){
    // 尝试获取后缀名
    $suffix = substr(strrchr($str, '.'), 1);
    // 如果能获取且为jpg,gif,png则为本地图片,不需要保存
    if($suffix=='png'||$suffix=='jpeg'||$suffix=='png'||$suffix=='gif'||$suffix=='jpg'){
        return false;
    }else{
        return true;
    }
}

function save_base_img($str,$path){
    // 读取根路径配置
    $root_path = config('Rootpath');
    // 保持缩略图不能自动创建路径(TP的原因),需要手动判断路径缺少则创建
    if(!is_dir(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd"))){
        mkdirs(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd"));
    }
    $name = getTumbName();
    if (strstr($str,",")){
        $str = explode(',',$str);
        $str = $str[1];
        $str  = base64_decode($str);
    }
    $jpg = file_put_contents(ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd").'/'.$name.'.jpg', $str);//返回的是字节数
    $return['code'] = $str;
    $return['root'] = ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd").'/'.$name.'.jpg';
    $return['path'] = $root_path.'/'.$path.'/'.date("Ymd")."/".$name.'.jpg';
    return $return;
}

/**
 * 改变图片的宽高
 *
 * @param string $img_src 原图片的存放地址或url
 * @param string $new_img_path  新图片的存放地址
 * @param int $new_width  新图片的宽度
 * @param int $new_height 新图片的高度
 * @return bool  成功true, 失败false
 */
function resize_image($img_src, $new_img_path, $new_width, $new_height)
{
    $img_info = @getimagesize($img_src);
    if (!$img_info || $new_width < 1 || $new_height < 1 || empty($new_img_path)) {
        return false;
    }
    if (strpos($img_info['mime'], 'jpeg') !== false) {
        $pic_obj = imagecreatefromjpeg($img_src);
    } else if (strpos($img_info['mime'], 'gif') !== false) {
        $pic_obj = imagecreatefromgif($img_src);
    } else if (strpos($img_info['mime'], 'png') !== false) {
        $pic_obj = imagecreatefrompng($img_src);
    } else {
        return false;
    }
    $pic_width = imagesx($pic_obj);
    $pic_height = imagesy($pic_obj);
    if (function_exists("imagecopyresampled")) {
        $new_img = imagecreatetruecolor($new_width,$new_height);
        imagecopyresampled($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);
    } else {
        $new_img = imagecreate($new_width, $new_height);
        imagecopyresized($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);
    }
    if (preg_match('~.([^.]+)$~', $new_img_path, $match)) {
        $new_type = strtolower($match[1]);
        switch ($new_type) {
            case 'jpg':
                imagejpeg($new_img, $new_img_path);
                break;
            case 'gif':
                imagegif($new_img, $new_img_path);
                break;
            case 'png':
                imagepng($new_img, $new_img_path);
                break;
            default:
                imagejpeg($new_img, $new_img_path);
        }
    } else {
        imagejpeg($new_img, $new_img_path);
    }
    imagedestroy($pic_obj);
    imagedestroy($new_img);
    return true;
}
// $type 1機構id 2userid 獲取該id下推廣店鋪數
function countContact($id,$type=1){
    if($type==1){
        $user = DB::name('user')->field('zid')->where('mechanismId',$id)->select();
        $id = array();
        foreach ($user as $key => $val) {
            $id[] = $val['zid'];
        }
        if (empty($id)) {
            return 0;
        }else{
            $id = implode(',', $id);
            $contact = DB::name('contact')->where('market','in',$id)->count();
            return $contact;
        }
    }else if($type==2){
        $contact = DB::name('contact')->where('market',$id)->count();
        return $contact;
    }
}

function updateCommission($commission,$number){
    $return = array('name'=>'无','percent'=>0,'startNum'=>0,'endNum'=>0);
    foreach ($commission as $k => $v) {
        if($number>=$v['startNum']&&$number<=$v['endNum']){
            $return = $v;
        }
    }
    return $return;
}

function getDefaultConfig(){
    $default_config = DB::name('config')->select();
    $array = array();
    foreach ($default_config as $key => $val) {
        $array[$val['key']] = $val['value'];
    }
    $default_config = $array;
    return $default_config;
}

function excelXls($data,$type=2) {
    $balanceType = array('1'=>'交易','2'=>'提现','3'=>'手续费');
    $balanceChange = array('1'=>'收入','2'=>'支出');
    $temp = ROOT_PATH.'public/static/houtai/xls/temp1.xls';
    //读取文件
    if (!file_exists($temp)) {
        exit("找不到该文件");
    }
    vendor("PHPExcel.PHPExcel");
    // $objPHPExcel = new \PHPExcel();
    $objReader = \PHPExcel_IOFactory::createReader ( 'Excel5' );
    $objPHPExcel = $objReader->load ($temp);
    $objActSheet = $objPHPExcel->getActiveSheet ();
    $objActSheet->setCellValue('B2',$data['name']);
    $objActSheet->setCellValue('D2',$data['contactNumber']);
    $objActSheet->setCellValue('F2',$data['merAccountDate']);
    $objActSheet->setCellValue('B3',$data['balanceMoney']);
    $objActSheet->setCellValue('D3',$data['balancePoundage']);
    $objActSheet->setCellValue('F3',$data['balanceTotal']);
    $rank = 6;
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                //'color' => array('argb' => 'FFFF0000'),
                'style'=>PHPExcel_Style_NumberFormat::FORMAT_TEXT, //不使用科学计数法
            ),
        ),
    );
    // 输出内容
    foreach($data['_balance'] as $key => $val) {
        $objActSheet->setCellValue('A'.$rank,$rank-4);
        $objActSheet->getStyle('A'.$rank)->applyFromArray($styleArray);
        $objActSheet->setCellValue('B'.$rank,$val['balanceNumber']);
        $objActSheet->getStyle('B'.$rank)->applyFromArray($styleArray);
        $objActSheet->setCellValue('C'.$rank,$balanceType[$val['balanceType']]);
        $objActSheet->getStyle('C'.$rank)->applyFromArray($styleArray);
        $objActSheet->setCellValue('D'.$rank,$balanceChange[$val['balanceChange']]);
        $objActSheet->getStyle('D'.$rank)->applyFromArray($styleArray);
        $objActSheet->setCellValue('E'.$rank,$val['balanceMoney']);
        $objActSheet->getStyle('E'.$rank)->applyFromArray($styleArray);
        $objActSheet->setCellValue('F'.$rank,$val['payTime']);
        $objActSheet->getStyle('F'.$rank)->applyFromArray($styleArray);
        $objActSheet->setCellValue('G'.$rank,'');
        $objActSheet->getStyle('G'.$rank)->applyFromArray($styleArray);
        $rank++;
    }
    $fileName = iconv("utf-8", "gb2312", $data['xlsname']); // 重命名表
    if($type==1){
        // 下载到浏览器开始
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$fileName);
        header('Cache-Control: max-age=0');
        //下载到浏览器结束
    }
    @ob_end_clean();//清除缓冲区,避免乱码

    // // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');
    // // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0
    $objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' ); //在内存中准备一个excel2003文件
    if($type==1){
        $objWriter->save('php://output');
    }else{
        if(!is_dir($data['path'])){
            mkdirs($data['path']);
        }
        // 尝试保持在服务器
        $objWriter->save($data['path'].$fileName);
        $res = $data['path'].$fileName;
        return $res;
    }
    exit();
}

function wxlatlngtomap($lat,$lng,$type="1"){
    $latitude = $lat;
    $longitude = $lng;
    $mapKey  = config('QQLbs.Key');
    //腾讯地图坐标转换官网：http://lbs.qq.com/webservice_v1/guide-convert.html
    $q = "https://apis.map.qq.com/ws/coord/v1/translate?locations=".$latitude.",".$longitude."&type=".$type."&key=".$mapKey;
    $resultQ = json_decode(file_get_contents($q),true);
    return $resultQ["locations"][0];
}
/**
 * 求两个已知经纬度之间的距离,单位为米
 *
 * @param lng1 $ ,lng2 经度
 * @param lat1 $ ,lat2 纬度
 * @return float 距离，单位米
 */
function getdistance($lng1, $lat1, $lng2, $lat2) {
    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $distance = $EARTH_RADIUS*2*asin(sqrt(pow(sin( ($lat1*pi()/180-$lat2*pi()/180)/2),2)+cos($lat1*pi()/180)*cos($lat2*pi()/180)* pow(sin( ($lng1*pi()/180-$lng2*pi()/180)/2),2)))*1000;
    return round($distance,2);
}

function get_food_list($contactNo,$type=1,$goodsid="",$wherearr=array()){
    // 规格列表
    $speclist = array();
    // 現在時間點
    $time = date('H:i:s');

    $goodsSpecs = DB::name('GoodsSpec')->where('isDelete',0)->where('gs_disable',1)->where('contactNumber',$contactNo)->select();

    $specs = DB::name('spec')->where('isDelete',0)->where('contactNumber',$contactNo)->select();

    foreach($specs as $spec){
        $speclist[$spec['id']] = $spec;
    }

    $goodSpecList = [];

    foreach($goodsSpecs as $goodsSpec){

        if(isset($goodSpecList[$goodsSpec['gs_good_id']])){

            if(isset($goodSpecList[$goodsSpec['gs_good_id']][$goodsSpec['gs_spec_pid']])){

                $goodSpecList[$goodsSpec['gs_good_id']][$goodsSpec['gs_spec_pid']]['_child'][$goodsSpec['gs_spec_id']]=[
                    'id'=>$goodsSpec['gs_spec_id'],
                    'price'=>$goodsSpec['gs_price'],
                    'name'=>__($speclist[$goodsSpec['gs_spec_id']]['spec_name'],$speclist[$goodsSpec['gs_spec_id']]['spec_name_en'],$speclist[$goodsSpec['gs_spec_id']]['spec_name_other']),
                    'is_repeat'=>$goodsSpec['is_repeat'],
                    'is_default'=>$goodsSpec['is_default']
                ];

            }else{
                $goodSpecList[$goodsSpec['gs_good_id']][$goodsSpec['gs_spec_pid']] = [
                    'id'=>$goodsSpec['gs_spec_pid'],
                    'fid'=>$goodsSpec['gs_good_id'],
                    'name'=>__($speclist[$goodsSpec['gs_spec_pid']]['spec_name'],$speclist[$goodsSpec['gs_spec_pid']]['spec_name_en'],$speclist[$goodsSpec['gs_spec_pid']]['spec_name_other']),
                    'min'=>$speclist[$goodsSpec['gs_spec_pid']]['minselect'],
                    'max'=>$speclist[$goodsSpec['gs_spec_pid']]['maxselect'],
                    'spec_order'=>$goodsSpec['gs_spec_order'],
                    '_child'=>[
                        $goodsSpec['gs_spec_id']=>[
                            'id'=>$goodsSpec['gs_spec_id'],
                            'price'=>$goodsSpec['gs_price'],
                            'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                            'is_repeat'=>$goodsSpec['is_repeat'],
                            'is_default'=>$goodsSpec['is_default']
                        ]
                    ]
                ];
            }
        }else{
            $goodSpecList[$goodsSpec['gs_good_id']] = [
                $goodsSpec['gs_spec_pid'] => [
                    'id'=>$goodsSpec['gs_spec_pid'],
                    'fid'=>$goodsSpec['gs_good_id'],
                    'name'=>__($speclist[$goodsSpec['gs_spec_pid']]['spec_name'],$speclist[$goodsSpec['gs_spec_pid']]['spec_name_en'],$speclist[$goodsSpec['gs_spec_pid']]['spec_name_other']),
                    'min'=>$speclist[$goodsSpec['gs_spec_pid']]['minselect'],
                    'max'=>$speclist[$goodsSpec['gs_spec_pid']]['maxselect'],
                    'spec_order'=>$goodsSpec['gs_spec_order'],
                    '_child'=>[
                        $goodsSpec['gs_spec_id']=>[
                            'id'=>$goodsSpec['gs_spec_id'],
                            'price'=>$goodsSpec['gs_price'],
                            'name'=>__($speclist[$goodsSpec['gs_spec_id']]['spec_name'],$speclist[$goodsSpec['gs_spec_id']]['spec_name_en'],$speclist[$goodsSpec['gs_spec_id']]['spec_name_other']),
                            'is_repeat'=>$goodsSpec['is_repeat'],
                            'is_default'=>$goodsSpec['is_default']
                        ]
                    ]
                ]
            ];
        }
    }

    $goodswhere = [];
    if(!empty($goodsid)){
        $goodswhere['id'] = $goodsid;
    }
    if(!empty($wherearr)){
        if(!empty($wherearr['keyword'])) $search = $wherearr['keyword'];$goodswhere['name']=array('like',"%$search%");
        if(!empty($wherearr['addon_foods_ids'])) $goodswhere['id']=array('not in',$wherearr['addon_foods_ids']);
    }
    $foodList = DB::name('Goods')
        ->field('id,name,name_en,name_other,payType,payUnit,number,categoryId,categoryName,salePrice,remark,remark_en,remark_other,detail,detail_en,detail_other,imgUrl,thumbnailUrl,sort')
        ->where('contactNumber',$contactNo)
        ->where('disable',1)
        ->where('isDelete',0)
        ->order('sort asc')
        ->where($goodswhere)
        ->select();

    /****这里的type为2代表美食广场****/
    if($type==2){
        //需要在图片前加域名
        foreach ($foodList as $key => $food) {
            $picArr = array();
            $foodList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$food['imgUrl'];
            $foodList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$food['thumbnailUrl'];
            preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $food['detail'], $picArr);
            $src = $picArr[1];
            foreach($src as $k => $v){
                // 不带http和https,默认为本地图片 拼接域名
                if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                    $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                    $foodList[$key]['detail'] = str_replace($v,$sv,$foodList[$key]['detail']);
                }
            }
        }
    }else{
        foreach ($foodList as $key => $food) {
            $foodList[$key]['name'] = __($food['name'],$food['name_en'],$food['name_other']);
            $foodList[$key]['remark'] = __($food['remark'],$food['remark_en'],$food['remark_other']);
            $foodList[$key]['detail'] = __($food['detail'],$food['detail_en'],$food['detail_other']);
        }
    }

    // 分类
    $categoryWithFood = array();

    if($type==1){
        $categoryList = DB::name('Category')->field('id,name,name_en,name_other,startTime,endTime')->where('typeNumber','trade')->where('contactNumber',$contactNo)->where('isDelete',0)->where('("'.$time.'" BETWEEN startTime AND endTime) OR startTime is NULL OR endTime iS NULL')->order('ordnum asc')->select();
    }else{
        $categoryList = DB::name('Category')->field('id,name,name_en,name_other,startTime,endTime')->where('typeNumber','trade')->where('contactNumber',$contactNo)->where('isDelete',0)->order('ordnum asc')->select();
    }

    foreach ($categoryList as $key => $categoryOne) {
        $categoryWithFood['category'.$categoryOne['id']] = ['categoryId' => $categoryOne['id'], 'categoryName' => __($categoryOne['name'],$categoryOne['name_en'],$categoryOne['name_other'])];
    }

    //跟餐数据
    $addon_goods = DB::name('addonGoods')->field("*,group_concat(aid) as aids")->where('contactNumber',$contactNo)->group('gid')->select();
    $addons = DB::name('addon')->where('contactNumber',$contactNo)->where('status',1)->select();
    $addon_foods = DB::name('addonFoods')->where('contactNumber',$contactNo)->select();
    $addon_foods_group = DB::name('addonFoodsGroup')->where('contactNumber',$contactNo)->select();

    $foodlist_ids = array_column($foodList,null,'id');
    //组装跟餐数据
    $addon_data = [];
    if(!empty($addon_foods)) {
        //开始遍历跟餐
        foreach($addons as $addon) {
            $addon_data[$addon['id']] = $addon;
            //开始遍历跟餐分组
            foreach($addon_foods_group as $foodgroup) {
                if($foodgroup['aid'] == $addon['id']) {
                    $addon_data[$addon['id']]['_foodsgroup'][$foodgroup['id']] = $foodgroup;
                    //开始遍历跟餐分组的菜品
                    foreach($addon_foods as $addonfood) {
                        if($addonfood['groupid'] == $foodgroup['id']) {
                            $addon_data[$addon['id']]['_foodsgroup'][$foodgroup['id']]['_foods'][$addonfood['id']] = is_array($foodlist_ids[$addonfood['gid']])?array_merge($foodlist_ids[$addonfood['gid']],$addonfood):$addonfood;
                            if(!empty($goodSpecList[$addonfood['gid']])){
                                $addonfood_spec = $goodSpecList[$addonfood['gid']];
                                if(!empty($addonfood['spec_price'])){
                                    //有跟餐规格的价格
                                    $addon_price_data = json_decode($addonfood['spec_price'],true);
                                    if(!empty($addonfood_spec)&&is_array($addonfood_spec)) {
                                        //开始遍历跟餐分组的菜品父类规格
                                        foreach($addonfood_spec as $key => $addon_spec) {
                                            //开始遍历跟餐分组的菜品子类规格
                                            foreach($addon_spec['_child'] as $k => $spec) {
                                                //获得跟餐分组菜品规格设置的价格
                                                $addonfood_spec[$key]['_child'][$k]['addon_price'] =$addon_price_data[$k];
                                            }
                                        }
                                    }
                                }
                                $addon_data[$addon['id']]['_foodsgroup'][$foodgroup['id']]['_foods'][$addonfood['id']]['_spec'] = $addonfood_spec;
                            }else{
                                $addon_data[$addon['id']]['_foodsgroup'][$foodgroup['id']]['_foods'][$addonfood['id']]['_spec'] = [];
                            }
                            if(isset($addon_data[$addon['id']]['_foodsgroup'][$foodgroup['id']]['_foods'][$addonfood['id']]['spec_price']))
                            {
                                $addon_data[$addon['id']]['_foodsgroup'][$foodgroup['id']]['_foods'][$addonfood['id']]['spec_price']='json';
                            }
                        }
                    }
                }
            }
        }
    }

    //关联跟餐数据到应用菜品
    $addon_goods_data=[];
    if(!empty($addon_goods)){
        foreach($addon_goods as $addon_good)
        {
            if(strpos($addon_good['aids'],',')!==false){
                $addon_goods_data[$addon_good['gid']]=explode(',',$addon_good['aids']);
            }else{
                $addon_goods_data[$addon_good['gid']] = $addon_good['aid'];
            }
        }
    }
    $adddata=[];
    if(!empty($addon_goods_data)){
        foreach($addon_goods_data as $key=>$addongood){
            if(is_array($addongood)){
                foreach($addongood as $item){
                    if(isset($addon_data[$item])){
                        $adddata[$key][]=$addon_data[$item];
                    }
                }
            }else{
                if(isset($addon_data[$addongood]))
                {
                    $adddata[$key] = $addon_data[$addongood];
                }
            }
        }
    }

    foreach ($foodList as $key => $food) {

        if(isset($goodSpecList[$food['id']])){

            $food['_spec'] = $goodSpecList[$food['id']];
            $foodList[$key]['_spec'] = $goodSpecList[$food['id']];

        }else{

            $food['_spec'] = [];
            $foodList[$key]['_spec'] = [];
        }
        //跟餐数据
        if(isset($adddata[$food['id']])){
            $food['_addon'] = $adddata[$food['id']];
            $foodList[$key]['_addon'] = $adddata[$food['id']];
        }

        $idkey = 'category'.$food['categoryId'];

        if(isset($categoryWithFood[$idkey])){

            $categoryWithFood[$idkey]['_food'][] = $food;
        }
    }
    $mealList = DB::name('SetMeal')
        ->field('id,name,name_en,name_other,totlePrice as price,imgUrl,thumbnailUrl,remark,detail')
        ->where('contactNumber',$contactNo)
        ->where('status',1)
        ->where('isDelete',0)
        ->select();

    $mealWithFood = array();
    $mealCategory = array();
    $mealInfoList = array();

    if(!empty($mealList)){

        foreach($mealList as $key => $mealSingle){
            $mealWithFood[$mealSingle['id']] = $mealSingle;
            $mealWithFood[$mealSingle['id']]['_category'] = array();
        }

        if($type==2){
            //需要在图片前加域名
            foreach ($mealList as $key => $value) {
                $picArr = array();
                $mealList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$value['imgUrl'];
                $mealList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$value['thumbnailUrl'];
                preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $value['detail'], $picArr);
                $src = $picArr[1];
                foreach($src as $k => $v){
                    // 不带http和https,默认为本地图片 拼接域名
                    if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                        $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                        $mealList[$key]['detail'] = str_replace($v,$sv,$mealList[$key]['detail']);
                    }
                }
            }
        }

        $mealIdList = array_column((array)$mealList,'id');

        $mealCategory = DB::name('SetMealCategory')
            ->field('id,mid,name,name_en,name_other,categoryMaxNumber,goodsMaxNumber,sort')
            ->where('mid','in',$mealIdList)
            ->where('isDelete',0)
            ->order('sort asc,id desc')
            ->select();

        foreach($mealCategory as $key => $mealCate){

            $mealWithFood[$mealCate['mid']]['_category'][$mealCate['id']] = $mealCate;
        }
        /*后面mos前缀省略，set_meal_info表关系mid->set_meal.id,cid->set_meal_category,gid->goods.id*/
        $mealInfoList = DB::name('SetMealInfo')
            ->alias('s')
            ->join('mos_goods g','s.gid = g.id','left')
            ->field('s.id,s.mid,s.cid,s.gid,g.salePrice,g.name,g.payType,g.payUnit,g.number,g.remark,g.detail,g.imgUrl,g.thumbnailUrl,g.sort')
            ->where('s.mid','in',$mealIdList)
            ->where('s.isDelete',0)
            ->where('g.isDelete',0)
            ->where('g.disable',1)
            ->order('s.sort asc,s.id desc')
            ->select();

        foreach($mealInfoList as $key => $mealInfo){

            if ($type==2) {

                $pictureList = array();
                $mealInfoList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$mealInfo['imgUrl'];
                $mealInfoList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$mealInfo['thumbnailUrl'];
                preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $mealInfo['detail'], $pictureList);
                $src = $pictureList[1];
                foreach($src as $k => $v){
                    // 不带http和https,默认为本地图片 拼接域名
                    if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                        $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                        $mealInfoList[$key]['detail'] = str_replace($v,$sv,$mealInfoList[$key]['detail']);
                    }
                }
            }
            /*
             * 单个mealInfo就是一个菜品信息，因为套餐的菜品都是在原来的菜品里面添加的，
             * 所以如果这个菜品在规格表里有关联的话，就在这个菜品中把规格添加进来；
            */
            if(!empty($goodSpecList[$mealInfo['gid']])){
                $mealInfo['_spec'] = $goodSpecList[$mealInfo['gid']];
            }

            //避免分類被刪除出錯
            if(isset($mealWithFood[$mealInfo['mid']]['_category'][$mealInfo['cid']])){
                /*把已经添加规格的mealInfo添加到完整的$mealWithFood中*/
                $mealWithFood[$mealInfo['mid']]['_category'][$mealInfo['cid']]['_food'][] = $mealInfo;
            }
        }
    }

    // 接口不需要輸出這兩個樹狀
    if($type==1){
        $return['meal'] = $mealWithFood;
        $return['category'] = $categoryWithFood;
    }
    $return['foodlist'] = $foodList;
    //$return['categorylist'] = $categoryList;
    $return['meallist'] = $mealList;
    $return['mealinfo'] = $mealInfoList;
    $return['mealcategory'] = $mealCategory;
    return $return;
}

//获得商家菜品图片集
function get_contact_images($contactNo)
{
    $contact_images_list = [];
    $contact_images = DB::name('Goods')
                  ->field('imgUrl,thumbnailUrl')
                  ->where('contactNumber',$contactNo)
                  ->where('disable',1)
                  ->where('isDelete',0)
                  ->select();
    foreach($contact_images as $image)
    {
        $contact_images_list[] = empty($image['thumbnailUrl'])?$image['imgUrl']:$image['thumbnailUrl'];
    }
    if(count($contact_images_list)<1)
    {
        $contact_images_list[] = '/static/assets/wxweb/images/lazyloadImg.png';
    }
    return $contact_images_list;

}

function check_contact($contact,$type="number",$userType="",$field="*"){
    $where[$type] = $contact;
    if(!empty($userType)){
        $contactType = get_in_contact_type($userType);
        $where['contactType'] = ['in',$contactType];
    }
    $res = DB::name('contact')->field($field)->where($where)->where('disable',1)->where('isDelete',0)->find();
    if(!empty($res)){
        $return['code'] = true;
        $return['msg'] = $res;
    }else{
        $return['code'] = false;
    }
    return $return;
}

function check_court($court,$type="number",$userType="",$field="*"){

    $where[$type] = $court;

    if(!empty($userType)){

        $courtType = get_in_contact_type($userType);
        $where['contactType'] = ['in',$courtType];
    }
    $foodCourt = DB::name('FoodCourt')->field($field)->where($where)->where('disable',1)->where('isDelete',0)->find();

    if(!empty($foodCourt)){
        $return['code'] = true;
        $return['msg'] = $foodCourt;
    }else{
        $return['code'] = false;
    }
    return $return;
}

function get_court_contact($courtId,$userType=""){

    $field = "name,number,logoUrl,cCategoryName,address,longitude,latitude,bgImageUrl";

    $where['isCourt'] = '1';

    $where['courtId'] = $courtId;

    if(!empty($userType)){

        $courtType = get_in_contact_type($userType);
        $where['contactType'] = ['in',$courtType];
    }

    $contact = DB::name('contact')->field($field)->where($where)->where('disable',1)->where('isDelete',0)->select();
    if($contact!==false){

        $return['code'] = true;
        $return['msg'] = $contact;
    }else{

        $return['code'] = false;
    }
    return $return;
}

function get_in_contact_type($userType){

    $contactType = array();
    /*
     'contact_type' => [
        '1' => [
            //餐厅的类型(和键值相同)
            'contact_type'  =>'1',
            // 可以登录该商家的用户类型
            'user_type'     =>[1,2,9999],
            // select等显示的名称
            'name'          =>'微信&支付寶',
            'icon'          =>[__ROOT__.'/static/assets/img/wechat.png',__ROOT__.'/static/assets/img/alipay.png'],
        ],
        '2' => [
            'contact_type'  =>'2',
            'user_type'     =>[1,9999],
            'name'          =>'微信',
            'icon'          =>[__ROOT__.'/static/assets/img/wechat.png'],
        ],
        '3' => [
            'contact_type'  =>'3',
            'user_type'     =>[2,9999],
            'name'          =>'支付寶',
            'icon'          =>[__ROOT__.'/static/assets/img/alipay.png'],
        ],
    ],
     */
    $type = config('contact_type');
    foreach ($type as $key => $val) {
        if(in_array($userType, $val['user_type'])){
            $contactType[] = $val['contact_type'];
        }
    }
    $contactType = implode(',', $contactType);
    return $contactType;
}
// 接口獲取訂單 類型 1獲取美食廣場訂單 2獲取普通餐廳訂單
function get_user_contact_order($type,$number,$userId="",$page=1,$size=5){
    if($type==1){
        // 普通餐廳
        $where['contactNumber'] = $number;
    }else{
        // 美食廣場
        $where['courtId'] = $number;
    }
    if(!empty($userId)){
        $where['userId'] = $userId;
    }
    $order = Db::name('wx_order')
        ->field('contactLogoUrl,contactName,contactMemberName,contactMemberNumber,contactNumber,orderAssignedNumber,payTime,createTime,payStatus,orderStatus,goodsAmount,orderSN,userId as openId')
        ->where($where)
        ->limit($size)
        ->page($page)
        ->order('id desc')
        ->select();
    foreach ($order as $key => $value) {
        $order[$key]['contactLogoUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$value['contactLogoUrl'];
    }
    if(!empty($order)){
        $orderSN = array();
        $orderList = array();
        foreach ($order as $key => $val) {
            $orderSN[] = $val['orderSN'];
            $orderList[$val['orderSN']] = $val;
            $orderList[$val['orderSN']]['create_time'] = date("Y-m-d H:i:s",$val['createTime']);
        }
        $foods = Db::name('wx_order_goods')->field('goodsThumbnailUrl,orderSN,goodsType,goodsName,num,goodsPrice,groupNumber')->where('orderSN','in',$orderSN)->select();
        foreach ($foods as $key => $val) {
            $val['goodsThumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$val['goodsThumbnailUrl'];
            if($val['goodsType']<3){
                $orderList[$val['orderSN']]['_food'][] = $val;
            }else if($val['goodsType']==3){
                $orderList[$val['orderSN']]['_food'][] = $val;
            }else{
                $orderList[$val['orderSN']]['_food'][] = $val;
            }
        }

        $return['code'] = 1;
        $return['msg'] = array_values($orderList);
    }else{
        $return['code'] = 0;
        $return['msg'] = '没有更多订单了';
    }
    return $return;
}

//用户端获取订单
function get_user_order($userType,$userId,$page,$size,$order_sn=''){
    //24小时内的时间戳
    $data_time = intval(strtotime(date("Y-m-d H:i:s")))-86400;
    $contactNo = \think\session::get('contact');
    $memberNo = \think\session::get('member');
    if(!empty($order_sn)){
        $map['orderSN'] = $order_sn;
    }else{
        $map = '1=1';
    }
    $order = Db::name('wx_order')
        ->field('*,userId as openId')
        ->where('userType',$userType)
        ->where('userId',$userId)
        ->where('contactNumber',$contactNo)
        ->where('contactMemberNumber',$memberNo)
        ->where($map)
        ->where('createTime','>=',$data_time)
        ->limit($size)
        ->page($page)
        ->order('id desc')
        ->select();
    if(!empty($order)){
        $orderSN = array();
        $orderList = array();
        $orderinfo = array();
        foreach ($order as $key => $val) {
            $orderSN[] = $val['orderSN'];
            $orderList[$val['orderSN']] = $val;
            $orderList[$val['orderSN']]['create_time'] = date("Y-m-d H:i:s",$val['createTime']);
        }
        $orderSN = implode(',', $orderSN);
        $foods = Db::name('wx_order_goods')->where('orderSN','in',$orderSN)->select();
        foreach ($foods as $key => $val) {
            if($val['goodsType']<3){
                $orderinfo['food_'.$val['id']] = $val;
            }else if($val['goodsType']==3){
                if(!empty($orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']]['_food'])){
                    $val['_food'] = $orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']]['_food'];
                }
                if(empty($val['goodsThumbnailUrl'])){
                    $currentMealId = intval(str_replace('meal_','',$val['goodsNumber']));
                    $currentMealThumbnailUrl = Db::name('SetMeal')->where(['id'=>$currentMealId])->value('thumbnailUrl');
                    $val['goodsThumbnailUrl'] = $currentMealThumbnailUrl;
                }
                $orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']] = $val;
            }else{
                $orderinfo['meal_'.$val['orderSN'].'_'.$val['groupNumber']]['_food'][] = $val;
            }
        }
        $foods = $orderinfo;
        foreach ($foods as $k => $v) {
            $orderList[$v['orderSN']]['_food'][] = $v;
            $orderList[$v['orderSN']]['qrcode'] = create_qrcode($v['orderSN']);
        }
        $return['code'] = 1;
        $return['msg'] = $orderList;
    }else{
        $return['code'] = 0;
        $return['msg'] = '没有更多订单了';
    }
    return $return;
}

function order_get_food($order){
    $food = array();
    foreach ($order as $key => $val) {
        if($val['type']==3){
            $mealspecids = '';
            if(!empty($val['specIds'])){
                $mealspecids = $val['specIds'];
            }
            $meal = array();
            foreach ($val['foods'] as $k => $v) {
                if($v['type']==2){
                    if(!empty($v['specIds'])){
                        // 分割字符串
                        $specids = explode(',', $v['specIds']);
                        $meal[] = array('id'=>$v['id'],'counter'=>$v['counter'],'spec'=>$specids,'type'=>5,'weight'=>$v['weight'],'cid'=>$v['cid']);
                    }else{
                        $meal[] = array('id'=>$v['id'],'counter'=>$v['counter'],'spec'=>array(),'type'=>5,'weight'=>$v['weight'],'cid'=>$v['cid']);
                    }
                }else{
                    if(!empty($v['specIds'])){
                        // 分割字符串
                        $specids = explode(',', $v['specIds']);
                        $meal[] = array('id'=>$v['id'],'counter'=>$v['counter'],'spec'=>$specids,'type'=>4,'cid'=>$v['cid']);
                    }else{
                        $meal[] = array('id'=>$v['id'],'counter'=>$v['counter'],'spec'=>array(),'type'=>4,'cid'=>$v['cid']);
                    }
                }
            }
            $food[] = array('id'=>$val['id'],'orderIndex'=>$val['orderIndex'],'counter'=>$val['counter'],'spec'=>$mealspecids,'type'=>3,'meal'=>$meal);
        }else{
            if($val['type']==2){
                if(!empty($val['specIds'])){
                    // 分割字符串
                    $specids = explode(',', $val['specIds']);
                    $food[] = array('id'=>$val['id'],'orderIndex'=>$val['orderIndex'],'counter'=>$val['counter'],'spec'=>$specids,'type'=>2,'weight'=>$val['weight'],'specNames'=>$val['specNames'],'specCounts'=>$val['specCounts']);
                }else{
                    $food[] = array('id'=>$val['id'],'orderIndex'=>$val['orderIndex'],'counter'=>$val['counter'],'spec'=>array(),'type'=>2,'weight'=>$val['weight'],'specNames'=>$val['specNames'],'specCounts'=>$val['specCounts']);
                }
            }else{
                if(!empty($val['specIds'])){
                    // 分割字符串
                    $specids = explode(',', $val['specIds']);
                    $food[] = array('id'=>$val['id'],'orderIndex'=>$val['orderIndex'],'counter'=>$val['counter'],'spec'=>$specids,'type'=>1,'specNames'=>$val['specNames'],'specCounts'=>$val['specCounts']);
                }else{
                    $food[] = array('id'=>$val['id'],'orderIndex'=>$val['orderIndex'],'counter'=>$val['counter'],'spec'=>array(),'type'=>1,'specNames'=>$val['specNames'],'specCounts'=>$val['specCounts']);
                }
            }
        }
    }
    return $food;
}
// 非套餐菜品轉訂單格式
function check_foods($food,$contact,$type="contactNumber"){
    $fid = array();
    $specid = array();
    $foodlist = array();
    $speclist = array();
    $return = array();
    $spec = [];
    foreach ($food as $key => $val) {
        // 不是套餐
        if($val['type']!=3){
            $fid[] = $val['id'];
            !empty($val['spec'])&&$specid = array_merge($specid,$val['spec']);
        }
    }
    // 有非套餐菜品
    if(!empty($fid)){
        // 數組去重
        $specid = array_unique($specid);
        $good = DB::name('goods')->field('id,name,payType,payUnit,number,thumbnailUrl,salePrice,remark,printerId,departmentId')
            ->where('id','in',$fid)
            ->where($type,$contact)
            ->where('disable',1)
            ->where('isDelete',0)
            ->select();
        if (!empty($specid)) {
            $spec = DB::name('GoodsSpec')
                ->alias('g')
                ->field('g.*,s.spec_name,s.spec_disable')
                ->join('mos_spec s','g.gs_spec_id = s.id','left')
                ->where('g.gs_good_id','in',$fid)
                ->where('g.gs_spec_id','in',$specid)
                ->where('g.gs_disable',1)
                ->where('g.isDelete',0)
                ->where('s.isDelete',0)
                ->select();
        }
        foreach ($good as $key => $val) {
            $foodlist[$val['id']] = $val;
        }
        foreach ($spec as $k => $v) {
            if(!empty($v['gs_good_id'])&&!empty($v['gs_spec_id'])){
                $speclist[$v['gs_good_id']][$v['gs_spec_id']] = $v;
            }
        }
        // 校验菜品 确认规格和数量
        foreach ($food as $key => $vo) {
            if($vo['type']==3||empty($foodlist[$vo['id']]))continue;
            $add = $foodlist[$vo['id']];
            $add['specIds'] = array();
            $add['counter'] = $vo['counter'];
            if($add['payType']==2){
                $add['weight'] = $vo['weight'];
                $add['weightNames'] = $vo['weight'].$add['payUnit'];
                $add['salePrice'] = sprintf("%01.2f",$add['salePrice']*$vo['weight']);
            }
            if(!empty($vo['spec'])){
                $specCounts = array();
                $specCnt = explode(',',$vo['specCounts']);
                foreach($specCnt as $scnt){
                    $abs = explode('_',$scnt);
                    $specCounts[$abs[0]] = $abs[1];
                };
                foreach ($vo['spec'] as $k => $sid) {
                    if(!empty($speclist[$vo['id']][$sid])){
                        $add['salePrice'] = floatval($add['salePrice']) + floatval($speclist[$vo['id']][$sid]['gs_price']) * intval($specCounts[$sid]?:1);
                        $add['specIds'][] = $sid;
                    }
                }
            };
            $add['orderIndex'] = $vo['orderIndex'];
            $add['specIds'] = implode(',', $add['specIds']);
            $add['specNames'] = isset($vo['specNames'])?$vo['specNames']:'';
            $return[] = $add;
        }
    }
    return $return;
}

// 套餐菜品轉訂單格式
function check_meal($food,$contact){
    $mid = array();
    $fid = array();
    $specid = array();
    $foodlist = array();
    $speclist = array();
    $return = array();
    $spec = [];
    $mealNumber = 1;
    $arrayKey = array();
    foreach ($food as $key => $val) {
        // 套餐
        if($val['type']==3){
            $mid[] = $val['id'];
            if(!empty($val['meal'])){
                foreach ($val['meal'] as $k => $v) {
                    $fid[] = $v['id'];
                    if(!empty($v['spec'])){
                        // 数组合并
                        $specid = array_merge($specid,$v['spec']);
                    }
                }
            }
        }
    }
    // 有非套餐菜品
    if(empty($mid)||empty($fid)){return '';};
    // 數組去重
    $fid = array_unique($fid);
    $specid = array_unique($specid);
    // 排序
    sort($fid);
    sort($specid);
    $meal = DB::name('SetMeal')
        ->where('id','in',$mid)
        ->where('contactNumber',$contact)
        ->where('status',1)
        ->where('isDelete',0)
        ->select();
    $mealCategory = DB::name('SetMealCategory')
        ->alias('c')
        ->field('c.*,group_concat(i.gid ORDER BY i.gid asc) as foodsid')
        ->join('mos_set_meal_info i','c.id = i.cid and i.isDelete = 0','left')
        ->where('c.mid','in',$mid)
        ->where('c.isDelete',0)
        ->group('c.id')
        ->select();
    $good = DB::name('goods')->field('id,name,payType,payUnit,number,thumbnailUrl,salePrice,remark,printerId,departmentId')
        ->where('id','in',$fid)
        ->where('contactNumber',$contact)
        ->where('disable',1)
        ->where('isDelete',0)
        ->select();
    if (!empty($specid)) {
        $spec = DB::name('GoodsSpec')
            ->alias('g')
            ->field('g.*,s.spec_name,s.spec_disable')
            ->join('mos_spec s','g.gs_spec_id = s.id','left')
            ->where('g.gs_good_id','in',$fid)
            ->where('g.gs_spec_id','in',$specid)
            ->where('g.gs_disable',1)
            ->where('g.isDelete',0)
            ->where('s.isDelete',0)
            ->select();
    }
    // 将对应id的key保存
    foreach ($meal as $key => $val) {
        $arrayKey['meal'][$val['id']] = $key;
    }
    foreach ($mealCategory as $key => $val) {
        $mealCategory[$key]['foodsid'] = explode(',',$val['foodsid']);
        $mealCategory[$key]['foodsid'] = explode(',',$val['foodsid']);
        $arrayKey['category'][$val['mid']][$val['id']] = $key;
    }
    foreach ($good as $key => $val) {
        $foodlist[$val['id']] = $val;
    }
    foreach ($spec as $k => $v) {
        if(!empty($v['gs_good_id'])&&!empty($v['gs_spec_id'])){
            $speclist[$v['gs_good_id']][$v['gs_spec_id']] = $v;
        }
    }
    // 校验套餐菜品及数量 生成订单转为订单格式准备插入订单
    foreach ($food as $k => $vo) {
        // 将套餐基本信息写入订单预设表中
        if($vo['type']!=3)continue;
        $foodmeal = $meal[$arrayKey['meal'][$vo['id']]];
        foreach ($arrayKey['category'][$vo['id']] as $ck => $cv) {
            $foodcategory[$ck] = $mealCategory[$cv];
            // 设置该分类已选择菜品为0
            $foodcategory[$ck]['selectfood'] = 0;
        }
        $add = [
            'name' => $foodmeal['name'],
            'number' => 'meal_'.$vo['id'],
            'counter' => $vo['counter'],
            'orderIndex' => $vo['orderIndex'],
            'salePrice'=>$foodmeal['totlePrice'],
            'thumbnailUrl' => $foodmeal['thumbnailUrl'],
            'payType' => 3,
            'groupNumber' => $mealNumber,
            'payUnit'=>'',
            'specIds' => $vo['spec'],
            'specNames' => array(),
            'printerId' => ''
        ];
        $return[] = $add;
        foreach ($vo['meal'] as $fk => $fv) {
            // 菜品id是否在分类中(确认数据没有被篡改)以及没有超过数量
            $categoryAll = $fv['counter']+$foodcategory[$fv['cid']]['selectfood'];
            $thiscategory = $foodcategory[$fv['cid']]['foodsid'];
            if(in_array($fv['id'], $foodcategory[$fv['cid']]['foodsid'])&&$categoryAll<=$foodcategory[$fv['cid']]['categoryMaxNumber']){
                if(!empty($foodlist[$fv['id']])){
                    $add = $foodlist[$fv['id']];
                    $add = array_merge($add,[
                        'specIds' => array(),
                        'specNames' => array(),
                        // 一份套餐菜品乘套餐数量
                        'counter' => $fv['counter']*$vo['counter'],
                        'payType' => $fv['type'],
                        'salePrice' => 0,
                        'groupNumber' => $mealNumber
                    ]);

                    if($add['payType']==5){

                        $add = array_merge($add,[
                            'weight' => $fv['weight'],
                            'weightNames' => $fv['weight'].$add['payUnit']
                        ]);
                    }
                    if(!empty($fv['spec'])){
                        foreach ($fv['spec'] as $k => $sid) {
                            if(!empty($speclist[$fv['id']][$sid])){
                                $add['specIds'][] = $sid;
                                $add['specNames'][] = $speclist[$fv['id']][$sid]['spec_name'];
                            }
                        }
                    }
                    $add['specIds'] = implode(',', $add['specIds']);
                    $add['specNames'] = implode(',', $add['specNames'])?'['.implode(',', $add['specNames']).']':'';
                    $foodcategory[$fv['cid']]['selectfood'] = $categoryAll;
                    $return[] = $add;
                }
            }
        }
        // 将套餐菜品加入
        $mealNumber++;
    }
    return $return;
}

//判断是否为用户加单，条件：该用户在该商家的当日订单是否已埋单(付款，完成)
function is_add_order($contactNumber,$contactMemberNumber,$userId){
    $data_time = intval(strtotime(date("Y-m-d H:i:s")))-86400;
    $hasorder = Db::name('wx_order')
                  ->where('contactNumber',$contactNumber)
                  ->where('userId',$userId)
                  ->where('contactMemberNumber',$contactMemberNumber)
                  ->where('createTime','>=',$data_time)
                  ->where('orderType',1)
                  ->where('orderStatus','in',[2,3])
                  ->order('id desc')
                  ->find();
    if(!empty($hasorder)){
        $return['code'] = 1;
        $return['data'] = $hasorder;
    }else{
        $return['code'] = 0;
        $return['data'] = [];
    }
    return $return;
}

/*
    新增订单<插入主表&子表>
    输入：json{全部订单信息插入主表&子表}
    输出：json串：{success：0/1 ；remark：失败原因}
    */

function addOrder($data,$method='',$cardCode=''){
    $time=time();//初始化一个时间
    $OrderNo = date('YmdHis',$time).rand(1000,9999);
    $Total_fee = $data['totalPrice'];//订单金额
    //$Body    = $data['contactName']; //商家名称
    //$Notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/api'.'/wxPayNotify.php';//后台通知地址
    if($data['userId']==""){
        $return = array('success'=>0,'error'=>'Openid 为空');
        json($return)->send();
        exit;
    }
    //判断是否为用户加单，条件：该用户在该商家的当日订单是否已埋单(付款，完成)
    $addstatus = 0;
    $add_data = is_add_order($data['contactNumber'],$data['contactMemberNumber'],$data['userId']);
    $contact_info = Db::name('contact')->where('number', $data['contactNumber'])->find();
    if($add_data['code']){
        $addstatus = 1;
        $hasorder = $add_data['data'];
        $OrderNo = $hasorder['orderSN'];
    }
    if(!empty($cardCode)){
        $record = DB::name('cardRecord')
                    ->where('cardCode',$cardCode)
                    ->where('openid',$data['userId'])
                    ->where('status',1)
                    ->find();
        // 找不到卡券
        if(empty($record))
        {
            $return = array('code'=>0,'msg'=>'未找到优惠券');
            return $return;die;
        }
        // 判断有效期 1为有有效期 且不在有效期内的 过期券
        else if($record['useTimeType']==1&&($record['useStartTime']>$times||$record['useEndTime']<$times))
        {
            $return = array('code'=>0,'msg'=>'优惠券已过期请重新选择');
            return $return;die;
        }
        // 不满足最低消费金额
        else if($record['cMinDiscountPaid']>$Total_fee){
            $return = array('code'=>0,'msg'=>'该优惠券有最低消费金额要求');
            return $return;die;
        }
        // 指定餐厅卡券 餐厅不对
        else if($record['cUseType']==2&&$record['cContactNumber']!=$data['contactNumber'])
        {
            $return = array('code'=>0,'msg'=>'该优惠券不能再该餐厅使用');
            return $return;die;
        }
    }
    if(!empty($method)&&$contact_info['laterPay']){
        $channle = DB::name('payMethod')->where('id',$method)->order('id asc')->find();
    }else{
        $channle = array('id'=>'','name'=>'');
    }
    $service_fees = $addstatus?($data['service_fee']+$hasorder['service_fees']):$data['service_fee'];
    $tea_fees = $addstatus?$hasorder['tea_fees']:$data['tea_fee'];
    $personCount = $addstatus?$hasorder['personCount']:$data['personCount'];
    $foodsAmount = $addstatus?($data['foodsAmount']+$hasorder['foodsAmount']):$data['foodsAmount'];
    $totalPrice = $addstatus?($data['totalPrice']+$hasorder['goodsAmount']):$data['totalPrice'];
    $order = [
        'orderSN' => $OrderNo,
        'userId' => $data['userId'],
        'userNick' => $data['userNick'],
        'orderStatus' => 1,
        'payStatus' => 0,
        'payType' => $channle['id'],
        'payName' => $channle['name'],
        'goodsAmount' => $totalPrice,
        'moneyPaid' => $totalPrice,
        'orderAmount' => $totalPrice,
        'service_fees' => $service_fees,
        'tea_fees' => $tea_fees,
        'personCount' => $personCount,
        'foodsAmount' => $foodsAmount,
        'createTime' => $time,
        'contactNumber' => $data['contactNumber'],
        'contactName' => $data['contactName'],
        'contactLogoUrl' => $data['contactLogoUrl'],
        'contactMemberNumber' => $data['contactMemberNumber'],
        'contactMemberName' => $data['contactMemberName'],
        'userType' => $data['userType'],
        'orderLongitude' => $data['longitude'],
        'orderLatitude' => $data['latitude'],
        'printerId' => $data['printerId'],
        'courtId' => !empty($data['courtId'])?$data['courtId']:0
    ];
    // 如果是指定商品优惠券 需要确定是否有优惠商品
    if(!empty($record)&&$record['cCardType']==3){
        // 设置一个判断值
        $cardSuccess = false;
    }

    //$order['payType'] = $method;
    //$order['payMethodId'] = $channle['id'];
    //$order['payMethodName'] = $channle['name'];
    //$order['orderInArea'] = $data['orderInArea'];

    $food = array();
    foreach($data['carts'] as $v){

        $cart = [
            'orderSN'=>$OrderNo,
            'contactNumber'=>$data['contactNumber'],
            'contactMemberNumber'=>$data['contactMemberNumber'],
            'goodsThumbnailUrl'=>$v['thumbnailUrl'],
            'goodsNumber'=>$v['number'],
            'num'=>$v['counter'],
            'goodsPrice'=>$v['salePrice'],
            'goodsType'=>$v['payType'],
            'addStatus'=>$addstatus?1:0,
            'unitName'=>$v['payUnit'],
            'printerId' => $data['printerId'],
            'goodsId'=>isset($v['id'])?$v['id']:0,
        ];

        if($v['payType']==2||$v['payType']==5){
            $v['weightNames'] = $v['weight'].$v['payUnit'];
            $v['name'] = $v['name'].$v['weightNames'];
        }
        if(!empty($v['specNames'])){
            $v['name'] = $v['name'].$v['specNames'];
        }
        if($v['payType']>=3&&!empty($v['groupNumber'])){
            if($addstatus) {
                $info= DB::name('wx_order_goods')->where('orderSN', $OrderNo)->order('groupNumber desc')->find();
                $groupnumber = $info['groupNumber'];
                $cart['groupNumber'] = $v['groupNumber']+$groupnumber;
            }else{
                $cart['groupNumber'] = $v['groupNumber'];
            }
        }

        if(isset($v['weight'])&&!empty($v['weight'])){
            $cart['goodsWeight']=$v['weight'];
        }
        // 如果菜品编号 为优惠券菜品编号 优惠券可用
        if(!empty($record)&&$record['cCardType']==3&&$record['cGoodsNumber']==$v['number']){
            $cardSuccess = true;
        }

        $cart['goodsName'] = $v['name'];
        $food[] = $cart;
    }
    // 使用优惠券
    if(!empty($record)){
        // 没有指定商品
        if($record['cCardType']==3&&$cardSuccess==false){
            $return = array('code'=>0,'msg'=>'该优惠券只能对指定商品使用');
            return $return;die;
        }else{
            $saveRecord = ['status'=>0,'utime'=>$times,'orderSN'=>$OrderNo];
            // 可用正常使用 修改数据
            // 折扣券
            if($record['cCardType']==1){
                $discount = ceil($order['goodsAmount'] * $record['cDiscountRate']) / 100;
                // 如果设置了上限金额 且超过上限金额 则优惠金额为上限金额
                if(!empty($record['cMaxDiscountRateMoney'])&&$discount>$record['cMaxDiscountRateMoney']){
                    $discount = $record['cMaxDiscountRateMoney'];
                }
                $order['moneyPaid'] = $order['goodsAmount'] - $discount;
            }else{
                // 减免金额大于等于 支付金额 则支付金额为0
                if($record['cDiscountMoney']>=$order['goodsAmount']){
                    $order['moneyPaid'] = 0;
                }else{
                    $order['moneyPaid'] = $order['goodsAmount'] - $record['cDiscountMoney'];
                }
            }
        }
    }
    $code = 1;
    Db::startTrans();
    try{
        if($addstatus){
            //用户加单时,当前订单不是加单待接单状态，则跟新id为最新用作新单提醒
            $maxOrder = Db::name('wx_order')->where('1=1')->order('id desc')->find();
            if($hasorder['addStatus']==0) {
                $order['id'] = $maxOrder['id']+1;
            }
            //用户加单时订单状态为原来的订单状态
            $order['orderStatus'] = $hasorder['orderStatus'];
            //用户加单时,如果已接单则增加一个加单状态
            $order['addStatus'] = $hasorder['orderStatus']==3?1:0;
            Db::name('wx_order')->where('orderSN',$OrderNo)->update($order);
        }else{
            $id = Db::name('wx_order')->insertGetId($order);
        }
        foreach ($food as $key => $val) {
            Db::name('wx_order_goods')->insert($val);
        }
        //记录使用优惠券
        if(!empty($record)){
            DB::name('cardRecord')->where('cardCode',$cardCode)->update($saveRecord);
        }
        // 提交事务
        Db::commit();
    } catch (\Exception $e) {
        $code = 0;
        // 回滚事务
        Db::rollback();
    }
    if($code==1){
        // 根据id执行插入编号操作
        // 获取今天下单后下单餐厅的总下单数(自己及自己之前)
        $count = Db::name('wx_order')
            ->where('contactNumber',$data['contactNumber'])
            ->where('id','ELT',$id)
            //->where('createTime','>',time()-86400)
            ->count();
        $count++;
        // 如果订单超过9999取9999的余数
        if ($count>9999) {
            $count = $count%9999;
        }
        $count=sprintf("%04d", $count);
        $update['orderAssignedNumber'] = $count;
        DB::name('wx_order')->where('id',$id)->update($update);
        $return['orderNo'] = $OrderNo;
        $return['msg'] = '插入成功';
        $return['code'] = 1;
    }else{
        $return['msg'] = '插入失败';
        $return['code'] = 0;
    }
    return $return;
}

//订单增加菜品
function addOrderFood($data,$method=''){
    $time=time();//初始化一个时间
    //$Body    = $data['contactName']; //商家名称
    //$Notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/api'.'/wxPayNotify.php';//后台通知地址
    $OrderNo = $data['orderSN'];
    if($data['userId']==""){
        $return = array('success'=>0,'error'=>'Openid 为空');
        json($return)->send();
        exit;
    }
    if(!empty($method)){
        $channle = DB::name('payMethod')->where('id',$method)->order('id asc')->find();
    }else{
        $channle = array('id'=>'','name'=>'');
    }
    //$order = [
    //    'orderSN' => $OrderNo,
    //    'userId' => $data['userId'],
    //    'userNick' => $data['userNick'],
    //    'orderStatus' => 1,
    //    'payStatus' => 0,
    //    'payName' => $channle['name'],
    //    'goodsAmount' => $data['totalPrice'],
    //    'moneyPaid' => $data['totalPrice'],
    //    'createTime' => $time,
    //    'contactNumber' => $data['contactNumber'],
    //    'contactName' => $data['contactName'],
    //    'contactLogoUrl' => $data['contactLogoUrl'],
    //    'contactMemberNumber' => $data['contactMemberNumber'],
    //    'contactMemberName' => $data['contactMemberName'],
    //    'userType' => $data['userType'],
    //    'orderLongitude' => '',
    //    'orderLatitude' => '',
    //    'printerId' => $data['printerId'],
    //    'courtId' => !empty($data['courtId'])?$data['courtId']:0
    //];
    $order_data = [
        'goodsAmount'=>$data['totalPrice'],
        'moneyPaid'=>$data['totalPrice'],
        'service_fees'=>$data['total_service_fee'],
        'foodsAmount'=>$data['total_foods_amount'],
    ];
    $food = array();
    $data['carts'] = setMealFood($data['carts'],$OrderNo);
    foreach($data['carts'] as $v){
        $cart = [
            'orderSN'=>$OrderNo,
            'contactNumber'=>$data['contactNumber'],
            'contactMemberNumber'=>$data['contactMemberNumber'],
            'goodsThumbnailUrl'=>$v['image'],
            'goodsNumber'=>$v['number'],
            'num'=>$v['counter'],
            'goodsPrice'=>$v['salePrice'],
            'goodsType'=>$v['payType'],
            'unitName'=>isset($v['unit'])?$v['unit']:'',
            'printerId' => $data['printerId'],
            'goodsId'=>isset($v['id'])?$v['id']:0,
        ];

        if($v['payType']==2||$v['payType']==5){
            $v['weightNames'] = $v['weight'].$v['payUnit'];
            $v['name'] = $v['name'].$v['weightNames'];
        }
        if(!empty($v['specNames'])&&$v['payType']!=3){
            $v['name'] = $v['name'].$v['specNames'];
        }

        if($v['payType']>=3&&!empty($v['groupNumber'])){
            $cart['groupNumber'] = $v['groupNumber'];
        }

        if(isset($v['weight'])&&!empty($v['weight'])){
            $cart['goodsWeight']=$v['weight'];
        }

        $cart['goodsName'] = $v['name'];
        $food[] = $cart;
    }
    $code = 1;
    Db::startTrans();
    try{
        DB::name('wx_order')->where('orderSN',$OrderNo)->update($order_data);
        foreach ($food as $key => $val) {
            $hasfood = DB::name('wx_order_goods')->where('orderSN',$OrderNo)->where('goodsId',$val['goodsId'])->where('goodsName',$val['goodsName'])->where('goodsType',$val['goodsType'])->find();
            //非套餐菜品数量合并
            if($hasfood>0&&!in_array($val['goodsType'],[3,4])){
                DB::name('wx_order_goods')->where('orderSN',$OrderNo)->where('goodsId',$val['goodsId'])->where('goodsName',$val['goodsName'])->where('goodsType',$val['goodsType'])->update(['num'=>$val['num']+$hasfood['num']]);
            }else{
                Db::name('wx_order_goods')->insert($val);
            }
        }
        // 提交事务
        Db::commit();
    } catch (\Exception $e) {
        $code = 0;
        // 回滚事务
        Db::rollback();
    }
    if($code==1){
        $return['msg'] = '插入成功';
        $return['code'] = 1;
    }else{
        $return['msg'] = '插入失败';
        $return['code'] = 0;
    }
    return $return;
}

//重构和补充：购物车菜品和套餐信息
function setMealFood($foodcart,$OrderNo){
    $data =[];
    $info = DB::name('wx_order_goods')->where('orderSN',$OrderNo)->order('groupNumber desc')->find();
    $groupnumber = $info['groupNumber'];
    foreach($foodcart as $key=>$v){
        $groupnumber++;
        if(stripos($key,'meal')!==false){
            //套餐
            $v['number'] = 'meal_'.$v['id'];
            $v['mealid'] = $v['id'];
            $foodinfo = Db::name('SetMeal')->where('id',$v['mealid'])->find();
            $v = array_merge($foodinfo,$v);
            $v['image'] = $v['thumbnailUrl'];
            $v['id'] = 0;
            $v['payType'] = 3;
            $v['groupNumber'] = $groupnumber;
            $v['salePrice'] = $v['price'];
            $foods = $v['foods'];
            unset($v['foods']);
            unset($v['category']);

            $data[] = $v;
            //套餐菜品
            foreach($foods as $food){
                $foodinfo = Db::name('Goods')->where('id',$food['id'])->find();
                $food = array_merge($foodinfo,$food);
                $food['groupNumber'] = $groupnumber;
                $food['payType'] = 4;
                $food['salePrice'] = 0;
                if(isset($food['_spec'])) unset($food['_spec']);
                $data[] = $food;
            }
        }else{
            //普通菜品
            $v['salePrice'] = $v['price'];
            if(isset($v['_spec'])) unset($v['_spec']);
            $foodinfo = Db::name('Goods')->where('id',$v['id'])->find();
            $v = array_merge($foodinfo,$v);
            $data[] = $v;
        }
    }
    return $data;
}

/**
 * 获取可领取卡券
 *
 * @param openid string 用户openid用于区分用户
 * @param where array 卡券规则限制条件
 * @param orderby array 各个调用方法的卡券限制
 * @return array code 1:成功 0:失败 msg:成功时为卡券数组 失败为错误信息
 */
function get_received_cards($openid,$where=array(),$orderby=''){
    // 获取现在时间
    $now = time();
    $cardListDB = DB::name('CardInfo')
        // 方法条件
                    ->where($where)
        // 未领取完毕
                    ->where('cardNumber','>',0)
        // 时间在领取时间内
                    ->where('receiptStartTime','elt',$now)
                    ->where('receiptEndTime','egt',$now)
        // 状态为可用
                    ->where('status',1)
        // 未删除
                    ->where('isDelete',0);
    // 排序方式
    if(!empty($orderby)){
        $cardListDB->order($orderby);
    }
    // 获取一张还是多张
    $cardList = $cardListDB->select();
    // var_dump($cardList);die;
    // 卡券id集合
    $cardID = array();
    // 将cardlist转为一个以id为键的数组 存到list中
    $list = array();
    // 获取卡券列表的id
    if (!empty($cardList)) {
        foreach ($cardList as $key => $val) {
            $cardID[] = $val['id'];
            $list[$val['id']] = $val;
        }
        // 查询列表的卡券已领取数量
        $cardRecord = DB::name('CardRecord')
                        ->field("count(*) as getNumber,cardId")
                        ->where('cardId','in',$cardID)
                        ->where('openid',$openid)
                        ->group('cardId')
                        ->select();
        // 循环判断已领取是否上限 将上限的卡券删除
        foreach ($cardRecord as $key => $val) {
            // 已领取次数大于等于上限
            if ($val['getNumber']>=$list[$val['cardId']]['limitNumber']) {
                unset($list[$val['cardId']]);
            }
        }
    }

    return $list;
    // 返回数组


}

/**
 * 领取卡券
 *
 * @param cardId int 卡券id
 * @param openId string 用户openid用于区分用户
 * @param where array 其他限制条件
 * @return array code 1:成功 0:失败 msg:成功时为卡券数组 失败为错误信息
 */
function getCardRecord($cardId,$openId,$where=array()){
    $times = time();
    // 查询卡券
    $card = DB::name('CardInfo')
              ->where($where)
              ->where('id',$cardId)
        // 状态为可用
              ->where('status',1)
        // 未删除
              ->where('isDelete',0)
        // ->fetchsql(true)
              ->find();
    if(empty($card)){
        $return = ['code'=>'-1','msg'=>'未找到卡券!'];
    }
    else
    {
        // 查询列表的卡券已领取数量
        $cardRecord = DB::name('CardRecord')
                        ->field("count(*) as getNumber,cardId")
                        ->where('cardId',$cardId)
                        ->where('openid',$openId)
                        ->find();
        if($times<$card['receiptStartTime']||$times>$card['receiptEndTime'])
        {
            // 是否在可领取时间
            $return = ['code'=>'-2','msg'=>'不在可领取时间!'];
        }
        else if($cardRecord['getNumber']>=$card['limitNumber'])
        {
            // 领取是否上限
            $return = ['code'=>'-2','msg'=>'领取次数已达到上限!'];
        }
        else if($card['cardNumber']<=0){
            // 是否还有卡券
            $return = ['code'=>'-3','msg'=>'卡券被抢光了!'];
        }else{
            // 开始领取卡券
            $res = 1;
            $insert = array(
                'cardId'=>$card['id'],
                'cardCode'=>md5($card['cardSN'].$times.mt_rand(1000,9999)),
                'cName'=>$card['name'],
                'cNotice'=>$card['notice'],
                'cCustom'=>$card['custom'],
                'cUseType'=>$card['useType'],
                'cContactNumber'=>$card['contactNumber'],
                'cContactName'=>$card['contactName'],
                'cCardType'=>$card['cardType'],
                'cMinDiscountPaid'=>$card['minDiscountPaid'],
                'cDiscountRate'=>$card['discountRate'],
                'cMaxDiscountRateMoney'=>$card['maxDiscountRateMoney'],
                'cDiscountMoney'=>$card['discountMoney'],
                'cGoodsNumber'=>$card['goodsNumber'],
                'cGoodsName'=>$card['goodsName'],
                'openid'=>$openId,
                'status'=>1,
                'ctime'=>$times,
            );
            // 计算有效期
            if($card['timeType']==3){
                $insert['useTimeType'] = 2;
            }else if($card['timeType']==2){
                $insert['useTimeType'] = 1;
                $insert['useStartTime'] = $card['validStartTime'];
                $insert['useEndTime'] = $card['validEndTime'];
            }else if($card['timeType']==1){
                $endTime = '';
                $insert['useTimeType'] = 1;
                $insert['useStartTime'] = $times;
                $timeLength = explode('-', $card['timeLength']);
                if(!empty($timeLength[0])){
                    $endTime .= ' +'.$timeLength[0].'year';
                }
                if(!empty($timeLength[1])){
                    $endTime .= ' +'.$timeLength[1].'month';
                }
                if(!empty($timeLength[2])){
                    $endTime .= ' +'.$timeLength[2].'day';
                }
                if(!empty($timeLength[3])){
                    $endTime .= ' +'.$timeLength[3].'hours';
                }
                if(!empty($timeLength[4])){
                    $endTime .= ' +'.$timeLength[4].'minute';
                }
                if(!empty($timeLength[5])){
                    $endTime .= ' +'.$timeLength[5].'second';
                }
                $insert['useEndTime'] = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s',$times).$endTime)));
            }
            Db::startTrans();
            try{
                // 修改卡券数量和更新时间
                $update = Db::name('CardInfo')->where('id',$cardId)->where('cardNumber','>',0)->update(['cardNumber'=>['exp','cardNumber-1'],'utime'=>time()]);
                // 成功且影响条数大于0
                if($update){
                    Db::name('CardRecord')->insert($insert);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res){
                $return = ['code'=>'1','msg'=>'领取成功!'];
            }else{
                $return = ['code'=>'0','msg'=>'领取失败!'];
            }

        }


    }
    return $return;
}

/**
 * 获取用户订单可用卡券
 *
 * @param openid string 用户openid
 * @param order array 订单信息
 * @return array code 1:成功 0:失败 msg:成功时为卡券数组 失败为错误信息
 */
function getUserCardList($openid,$order){
    // var_dump($openid);
    // var_dump($order);
    $times = time();
    // 商户条件
    if(!empty($order['contactNumber'])){
        $whereContact = 'cUseType = 1 OR (cUseType = 2 AND cContactNumber = "'.$order['contactNumber'].'")';
    }else{
        $whereContact = 'cUseType = 1';
    }
    // 有效期条件
    $whereTime = 'useTimeType = 2 OR (useStartTime <= "'.$times.'" AND useEndTime >= "'.$times.'")';
    $cardRecord = DB::name('CardRecord')
                    ->where($whereTime)
                    ->where($whereContact)
                    ->where('openid',$openid)
                    ->where('status',1)
                    ->order('useTimeType desc,useEndTime asc')
                    ->select();
    // 可用卡券集合
    $useCardList = array();
    $orderFoodsNumber = array();
    if(!empty($order['carts'])){
        foreach ($order['carts'] as $key => $val) {
            $orderFoodsNumber[] = $val['number'];
        }
    }
    foreach ($cardRecord as $key => $val) {
        if($val['cCardType']==3){
            // 价格符合最低价格标准且有指定商品
            if($order['totalPrice']>=$val['cMinDiscountPaid']&&in_array($val['cGoodsNumber'], $orderFoodsNumber)){
                $useCardList[] = $val;
            }
        }else if($val['cCardType']==2){
            // 价格符合最低价格标准或不设置最低金额
            if(!empty($val['cMinDiscountPaid'])){
                if($order['totalPrice']>=$val['cMinDiscountPaid']){
                    $useCardList[] = $val;
                }
            }else{
                $useCardList[] = $val;
            }

        }else{
            // 价格符合最低价格标准
            if($order['totalPrice']>=$val['cMinDiscountPaid']){
                $useCardList[] = $val;
            }
        }
    }
    return $useCardList;
}

/**
 * 获取支付满额赠券
 *
 * @param openid string 用户openid
 * @param contactNumber string 餐厅编号
 * @param price float 支付金额
 * @return
 */
function get_pay_card($openid,$contactNumber,$price){
    $where = 'distributeMoney <='.$price.' AND distributeType = 2 AND (useType = 1 OR contactNumber = "'.$contactNumber.'")';
    $cardList = get_received_cards($openid,$where);
    if (!empty($cardList)) {
        foreach ($cardList as $key => $val) {
            $res = getCardRecord($val['id'],$openid);
            if($res['code']==1){
                $getCardList[] = $val;
            }
        }
    }
}

/**
 * 卡券从锁定改为已使用
 *
 * @param orderSN string 订单编号
 * @return
 */
function use_order_card($orderSN){
    $card =  DB::name('cardRecord')->where('orderSN',$orderSN)->where('status',0)->update(['status'=>2,'utime'=>time()]);
}

//自定义记录日志方法
function log_output($logData = '', $filename = '')
{
    $logStr = PHP_EOL.'------------'.date('Y-m-d H:i:s').'-------------'.PHP_EOL;
    $logStr .= var_export($logData,true);
    $logFileName = empty($filename)?'runLog'.date('y-m-d').'.log':$filename.'.log';
    $logPath = $_SERVER['DOCUMENT_ROOT'].'/runtime/log/run/';
    if(!is_dir($logPath))
    {
        mkdir($logPath,0777,true);
    }
    file_put_contents($logPath.$logFileName, $logStr, FILE_APPEND);
}

//获取当前语言
function get_now_lang(){
    $lang = lang::range();
    return $lang;
}

//获取一段时间内的每天日期数组
function getDateFromRange($startdate, $enddate){
    $stimestamp = strtotime($startdate);
    $etimestamp = strtotime($enddate);
    // 计算日期段内有多少天
    $days = ($etimestamp-$stimestamp)/86400+1;
    // 保存每天日期
    $date = array();
    for($i=0; $i<$days; $i++){
        $date[] = date('Y-m-d', $stimestamp+(86400*$i));
    }
    return $date;
}

//返回指定的开始日期，结束日期
function getStartAndEndData($str,$clean_time){
    $second = substr ($clean_time, -2);
    $minute = substr ($clean_time, -5, 2);
    $hour   = substr ($clean_time, 0, 2);
    switch($str)
    {
        case 'all':
            $startDate = '1969-01-01 00:00:00';
            $endState =date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')+1,date('Y'))-1);
            break;
        case 'today':
            $startDate = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d'),date('Y')));
            $endState = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')+1,date('Y'))-1);
            break;
        case 'yesterday':
            $startDate= date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')-1,date('Y')));
            $endState= date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d'),date('Y'))-1);
            break;
        case 'week':
            $startDate = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date("m"),date("d")-date("w")+1,date("Y")));
            $endState = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")));
            break;
        case 'lastweek':
            $startDate= date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')-date('w')+1-7,date('Y')));
            $endState= date("Y-m-d H:i:s",mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y')));
            break;
        case 'month':
            $startDate = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),1,date('Y')));
            $endState = date("Y-m-d H:i:s",mktime(23,59,59,date('m'),date('t'),date('Y')));
            break;
        case 'lastmonth':
            $startDate = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date("m")-1,1,date("Y")));
            $endState = date("Y-m-d H:i:s",mktime(23,59,59,date("m") ,0,date("Y")));
            break;
        case 'seven':
            $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $endState = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')+1,date('Y'))-1);
            break;
        case 'thirty':
            $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
            $endState = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')+1,date('Y'))-1);
            break;
        default:
            $startDate = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d'),date('Y')));
            $endState = date("Y-m-d H:i:s",mktime($hour,$minute,$second,date('m'),date('d')+1,date('Y'))-1);
            break;
    }
    return ['startDate'=>$startDate,'endState'=>$endState];
}

//根据当前cookie的语言显示对应语言
function __($str_cn,$str_en,$str_other)
{
    $str = null;
    $lang = \think\Cookie::get('think_var');
    if($lang == 'en-us')
    {
        $str = $str_en;
    }elseif($lang == 'zh-tw')
    {
        $str = $str_cn;
    }else{
        $str = $str_other;
    }
    if(empty($str)){
        //$str = $str_cn;
    }
    return $str;
}

//返回对应英语言名称
function get_lang_name($lang)
{
    $lang_arr = ['zh-tw','zh-cn','en-us','other'];
    $langname_arr = [
        'zh-tw'=>'繁體中文',
        'zh-cn'=>'简体中文',
        'en-us'=>'English',
        'other'=>'Other',
    ];
    return !in_array($lang,$lang_arr)?'繁體中文':$langname_arr[$lang];
}

//价格格式化：四舍五入,保留2位小数
function price_format($price)
{
    return number_format($price,2);
}

//价格不四舍五入处理
function floatprice($price)
{
    return sprintf("%.2f",floor($price * 100)/100);
}

/**
 * 解析url中参数信息，返回参数数组
 */
function convertUrlQuery($url)
{
    $arr = parse_url($url);
    $query = $arr['query'];
    $queryParts = explode('&', $query);
    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
    return $params;
}
?>