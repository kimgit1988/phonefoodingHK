<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Validate;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Adlist extends AdminBase {
    
    public function index() {
        $ad = DB::name('Ad')->order('id desc')->where('isDelete',0)->paginate(10);
        $position = config('ad_position');
        $disable = [0=>'禁用',1=>'啟用'];
        $type = [1=>'圖片'];
        $this->assign('ad', $ad);
        $this->assign('pages',$ad->render());
        $this->assign('position',$position);
        $this->assign('disable', $disable);
        $this->assign('type', $type);
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $savelist = array();
            if(empty($params['upload'])){
                $this->error('請上傳文件');
            }
            foreach ($params['upload'] as $key => $val) {
                if(!empty($val['pic'])){
                    $isbase = is_base64_picture($val['pic']);
                }else{
                    $this->error('获取图片地址失败');die;
                }
                if($isbase){
                    // base64转图片
                    $img = save_base_img($val['pic'],'uploads/banner');
                    // 图片地址保存
                    if(!empty($val['sort'])){
                        $sort = $val['sort'];
                    }else{
                        $sort = 1;
                    }
                    $savelist[] = ['path'=>$img['path'],'sort'=>$val['sort']];
                    // 生成缩略图 这里不需要
                    // $thumb = img_create_small($img['root'],80,80,"uploads/Thumb");
                }
            }
            $save   = array(
                'adName'     => $params['name'],
                'adType'     => 1,
                'adPosition' => $params['position'],
                'adLink'     => $params['link'],
                'adUrl'      => json_encode($savelist),
                'adStart'    => $params['start'],
                'adEnd'      => $params['end'],
                'adMan'      => $params['man'],
                'adPhone'    => $params['phone'],
                'adEmail'    => $params['email'],
                'adClick'    => 0,
                'disable'    => $params['disable'],
            );
            $rule = [
                'adName'     => 'require|max:30',
                'adType'     => 'require|number|max:2',
                'adPosition' => 'require|number|max:4',
                'adLink'     => 'max:200',
                'adUrl'      => 'require',
                'adStart'    => 'dateFormat:Y-m-d H:i:s',
                'adEnd'      => 'dateFormat:Y-m-d H:i:s',
                'adMan'      => 'max:50',
                'adPhone'    => 'max:50|regex:/^[\d\+]?\d+[\d\s\-]+\d+$/',
                'adEmail'    => 'max:100|email',
                'adClick'    => 'number',
                'disable'    => 'require|in:0,1,2',
            ];
            $msg = [
                'adName.require'        => '請輸入廣告名稱',
                'adName.max'            => '廣告名稱不能超過30字',
                'adType.require'        => '請選擇廣告類型',
                'adType.number'         => '廣告類型值不正確',
                'adType.max'            => '廣告類型值不正確',
                'adPosition.require'    => '請選擇廣告位置',
                'adPosition.number'     => '廣告位置值不正確',
                'adPosition.max'        => '廣告位置值不正確',
                'adLink.max'            => '鏈接太長,請控制在200字符內',
                'adUrl.require'         => '沒有找到上傳的文件',
                'adStart.dateFormat'    => '開始時間不正確',
                'adEnd.dateFormat'      => '結束時間不正確',
                'adMan.max'             => '聯繫人姓名不能超過50字',
                'adPhone.max'           => '聯繫人電話不能超過50字',
                'adPhone.regex'         => '聯繫人電話不正確',
                'adEmail.max'           => '聯繫人郵箱不能超過100字',
                'adEmail.emial'         => '聯繫人郵箱不正確',
                'adClick.number'        => '點擊量的值不正確',
                'disable.require'       => '請選擇狀態',
                'disable.in'            => '狀態不正確',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            $save['adStart'] = strtotime($save['adStart']);
            $save['adEnd'] = strtotime($save['adEnd']);
            if (($id = DB::name('Ad')->insertGetId($save)) === false) {
                return $this->error('添加广告失败');
            }
            Loader::model('SystemLog')->record("添加广告,ID:[{$id}]");
            return $this->success('添加广告成功', Url::build('Adlist/index'));
        }else{
            $position = config('ad_position');
            $this->assign('position',$position);
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $savelist = array();
            if(empty($params['upload'])){
                $this->error('請上傳文件');
            }
            foreach ($params['upload'] as $key => $val) {
                if(!empty($val['pic'])){
                    $isbase = is_base64_picture($val['pic']);
                }else{
                    $this->error('获取图片地址失败');die;
                }
                if($isbase){
                    // base64转图片
                    $img = save_base_img($val['pic'],'uploads/banner');
                    // 图片地址保存
                    if(!empty($val['sort'])){
                        $sort = $val['sort'];
                    }else{
                        $sort = 1;
                    }
                    $savelist[] = ['path'=>$img['path'],'sort'=>$val['sort']];
                    // 生成缩略图 这里不需要
                    // $thumb = img_create_small($img['root'],80,80,"uploads/Thumb");
                }else{
                    // 非base64直接保存
                    $img['path'] = $val['pic'];
                    // 图片地址保存
                    if(!empty($val['sort'])){
                        $sort = $val['sort'];
                    }else{
                        $sort = 1;
                    }
                    $savelist[] = ['path'=>$img['path'],'sort'=>$val['sort']];
                }
            }
            $save   = array(
                'id'         => $params['id'],
                'adName'     => $params['name'],
                'adType'     => 1,
                'adPosition' => $params['position'],
                'adLink'     => $params['link'],
                'adUrl'      => json_encode($savelist),
                'adStart'    => $params['start'],
                'adEnd'      => $params['end'],
                'adMan'      => $params['man'],
                'adPhone'    => $params['phone'],
                'adEmail'    => $params['email'],
                'adClick'    => 0,
                'disable'    => $params['disable'],
            );
            $rule = [
                'id'         => 'require',
                'adName'     => 'require|max:30',
                'adType'     => 'require|number|max:2',
                'adPosition' => 'require|number|max:4',
                'adLink'     => 'max:200',
                'adUrl'      => 'require',
                'adStart'    => 'dateFormat:Y-m-d H:i:s',
                'adEnd'      => 'dateFormat:Y-m-d H:i:s',
                'adMan'      => 'max:50',
                'adPhone'    => 'max:50|regex:/^[\d\+]?\d+[\d\s\-]+\d+$/',
                'adEmail'    => 'max:100|email',
                'adClick'    => 'number',
                'disable'    => 'require|in:0,1,2',
            ];
            $msg = [
                'id.require'            => '頁面錯誤',
                'adName.require'        => '請輸入廣告名稱',
                'adName.max'            => '廣告名稱不能超過30字',
                'adType.require'        => '請選擇廣告類型',
                'adType.number'         => '廣告類型值不正確',
                'adType.max'            => '廣告類型值不正確',
                'adPosition.require'    => '請選擇廣告位置',
                'adPosition.number'     => '廣告位置值不正確',
                'adPosition.max'        => '廣告位置值不正確',
                'adLink.max'            => '鏈接太長,請控制在200字符內',
                'adUrl.require'         => '沒有找到上傳的文件',
                'adStart.dateFormat'    => '開始時間不正確',
                'adEnd.dateFormat'      => '結束時間不正確',
                'adMan.max'             => '聯繫人姓名不能超過50字',
                'adPhone.max'           => '聯繫人電話不能超過50字',
                'adPhone.regex'         => '聯繫人電話不正確',
                'adEmail.max'           => '聯繫人郵箱不能超過100字',
                'adEmail.emial'         => '聯繫人郵箱不正確',
                'adClick.number'        => '點擊量的值不正確',
                'disable.require'       => '請選擇狀態',
                'disable.in'            => '狀態不正確',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            $save['adStart'] = strtotime($save['adStart']);
            $save['adEnd'] = strtotime($save['adEnd']);
            if ((DB::name('Ad')->where('id',$save['id'])->update($save)) === false) {
                return $this->error('修改广告失败');
            }
            Loader::model('SystemLog')->record("修改广告,ID:[{$save['id']}]");
            return $this->success('修改广告成功', Url::build('Adlist/index'));
        }else{
            $id = $request->param('id');
            $position = config('ad_position');
            $ad = DB::name('Ad')->where('id',$id)->find();
            $url = json_decode($ad['adUrl'],true);
            $this->assign('ad',$ad);
            $this->assign('url',$url);
            $this->assign('position',$position);
            return $this->fetch();
        }
    }

}