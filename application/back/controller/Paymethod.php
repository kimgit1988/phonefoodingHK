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
class Paymethod extends AdminBase {
    
    public function index() {
        $method = DB::name('PayMethod')->order('id asc')->where('isDelete',0)->paginate(10);
        $this->assign('method', $method);
        $this->assign('pages',$method->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            // 由於config命名問題不使用驗證器
            $file = request()->file('image');
            // 调用上传方法 保存原图
            $uploads = uploadPic($file,'uploads/icon');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($uploads['code']==1){
                $params['icon']  = $uploads['msg'];
            }else{
                return $this->error($uploads['msg']);
            }
            $save   = array(
                'name'  => $params['name'],
                'code'  => $params['code'],
                'rate'  => $params['rate'],
                'icon'  => $params['icon'],
                'cycle' => $params['cycle'],
                'disable' => $params['status'],
                'minLimit'=> $params['minMoney'],
                'online'=> $params['online'],
                'bn_id'  => $params['banknumber'],
                'fileName'=> ucfirst(strtolower($params['file'])),
                'settlementTime' => $params['settlementTime'],
                'ctime' => time(),
                'utime' => time(),
            );
            $rule = [
                'name'  => 'require',
                'code'  => 'require|unique:PayMethod',
                'rate'  => 'require',
                'cycle' => 'require|number|egt:0',
                'disable' => 'in:0,1,2',
                'minLimit' => 'require|float|egt:0',
                'settlementTime' => 'dateFormat:H:i',
                'fileName' => 'require',
                //'bn_id'    => 'require',
            ];
            $msg = [
                'name.require'      => '請輸入渠道名稱',
                'code.require'      => '請輸入渠道編號',
                'code.unique'       => '渠道編號已存在',
                'rate.require'      => '請輸入收款費率',
                'cycle.require'     => '請輸入結算週期',
                'cycle.number'      => '結算週期必须是正整數',
                'cycle.egt'         => '結算週期必须是正整數',
                'disable.in'        => '狀態選值不正確',
                'minLimit.require'  => '請輸入最小提現金額',
                'minLimit.float'    => '最小提現金額必须是數字',
                'minLimit.egt'      => '最小提現金額必须大於零',
                'settlementTime.dateFormat' => '結算時間不正確',
                'fileName.require'  => '請輸入文件名稱',
                //'fileName.alpha'    => '文件名稱只能由字母構成',
                //'bn_id.require'     => '请选择所属银行账号',
            ];
            
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            if (($id = DB::name('PayMethod')->insertGetId($save)) === false) {
                return $this->error('添加渠道失败');
            }
            Loader::model('SystemLog')->record("添加渠道,ID:[{$id}]");
            return $this->success('添加渠道成功', Url::build('Paymethod/index'));
        }else{
            $bn = DB::name('BankNumber')->field('id,bank_number,owner')->where('isDelete',0)->select();
            $this->assign('bn',$bn);
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('image');
            if($file){
                // 调用上传方法 保存原图
                $uploads = uploadPic($file,'uploads/icon');
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($uploads['code']==1){
                    $params['icon']  = $uploads['msg'];
                }else{
                    return $this->error($uploads['msg']);
                }
            }
            // 由於config命名問題不使用驗證器
            $save   = array(
                'id'    => $params['id'],
                'name'  => $params['name'],
                'code'  => $params['code'],
                'rate'  => $params['rate'],
                'cycle' => $params['cycle'],
                'disable' => $params['status'],
                'minLimit'=> $params['minMoney'],
                'online'=> $params['online'],
                'bn_id'  => $params['banknumber'],
                'fileName'=> ucfirst(strtolower($params['file'])),
                'settlementTime' => $params['settlementTime'],
                'utime' => time(),
            );
            if(isset($params['icon'])){
                $save['icon'] = $params['icon'];
            }
            $rule = [
                'id'    => 'require',
                'name'  => 'require',
                'code'  => 'require',
                'rate'  => 'require',
                'cycle' => 'require|number|egt:0',
                'disable' => 'in:0,1,2',
                'minLimit' => 'require|float|egt:0',
                'settlementTime' => 'dateFormat:H:i',
                'fileName' => 'require',
                //'bn_id'    => 'require',
            ];
            $msg = [
                'id.require'        => '頁面錯誤',
                'name.require'      => '請輸入渠道名稱',
                'code.require'      => '請輸入渠道編號',
                'rate.require'      => '請輸入收款費率',
                'cycle.require'     => '請輸入結算週期',
                'cycle.number'      => '結算週期是正整數',
                'cycle.egt'         => '結算週期是正整數',
                'disable.in'        => '狀態選值不正確',
                'minLimit.require'  => '請輸入最小提现金額',
                'minLimit.float'    => '最小提现金額必须是數字',
                'minLimit.egt'      => '最小提现金額必须大於零',
                'settlementTime.dateFormat' => '結算時間不正確',
                'fileName.require' => '請輸入文件名稱',
                //'fileName.alpha' => '文件名稱只能由字母構成',
                //'bn_id.require'     => '请选择所属银行账号',
            ];
            $code = DB::name('PayMethod')->where('id','neq',$save['id'])->where('code',$save['code'])->where('isDelete',0)->find();
            if(!empty($key)){
                return $this->error('編號已存在');
            }
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            if ((DB::name('PayMethod')->where('id',$save['id'])->update($save)) === false) {
                return $this->error('修改渠道失败');
            }
            //Loader::model('SystemLog')->record("修改渠道,ID:[{$save['id']}]");
            return $this->success('修改渠道成功', Url::build('Paymethod/index'));
        }else{
            $id = $request->param('id');
            $method = DB::name('PayMethod')->where('id',$id)->where('isDelete',0)->find();
            $bn = DB::name('BankNumber')->field('id,bank_number,owner')->where('isDelete',0)->select();
            $this->assign('bn',$bn);
            $this->assign('method',$method);
            return $this->fetch();
        }
    }

    public function del() {
        $request = Request::instance();
        $id = $request->param('id');
        if (DB::name('PayMethod')->where('id',$id)->update(['isDelete'=>1]) === false) {
            return $this->error('渠道删除失败');
        }else{
            Loader::model('SystemLog')->record("渠道删除,ID:[{$id}]");
            return $this->success('渠道删除成功', Url::build('Paymethod/index'));
        }
    }

}