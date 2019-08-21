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
class Cfglist extends AdminBase {
    
    public function index() {
        $config = DB::name('Config')->order('id desc')->select();
        $this->assign('config', $config);
        // $this->assign('pages',$mechanism->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            // 由於config命名問題不使用驗證器
            $save   = array(
                'name'  => $params['name'],
                'key'   => $params['key'],
                'value' => $params['value'],
                'unit'  => $params['unit'],
                'ctime' => time(),
                'utime' => time(),
            );
            $rule = [
                'name'  => 'require',
                'key'   => 'require|unique:config',
                'value' => 'require',
            ];
            $msg = [
                'name.require'  => '請輸入名稱',
                'key.require'   => '請輸入鍵名',
                'key.unique'    => '鍵名已存在',
                'value.require' => '請輸入鍵值',
            ];
            
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            if (($id = DB::name('config')->insertGetId($save)) === false) {
                return $this->error('添加配置失败');
            }
            Loader::model('SystemLog')->record("添加配置,ID:[{$id}]");
            return $this->success('添加配置成功', Url::build('Cfglist/index'));
        }else{
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            // 由於config命名問題不使用驗證器
            $save   = array(
                'id'    => $params['id'],
                'name'  => $params['name'],
                'key'   => $params['key'],
                'value' => $params['value'],
                'unit'  => $params['unit'],
                'utime' => time(),
            );
            $rule = [
                'id'    => 'require',
                'name'  => 'require',
                'key'   => 'require',
                'value' => 'require',
            ];
            $msg = [
                'id.require'    => '頁面錯誤',
                'name.require'  => '請輸入名稱',
                'key.require'   => '請輸入鍵名',
                'value.require' => '請輸入鍵值',
            ];
            $key = DB::name('config')->where('id','neq',$save['id'])->where('key',$save['key'])->find();
            if(!empty($key)){
                return $this->error('鍵名已存在');
            }
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            if ((DB::name('config')->where('id',$save['id'])->update($save)) === false) {
                return $this->error('修改配置失败');
            }
            Loader::model('SystemLog')->record("修改配置,ID:[{$save['id']}]");
            return $this->success('修改配置成功', Url::build('Cfglist/index'));
        }else{
            $id = $request->param('id');
            $config = DB::name('Config')->where('id',$id)->find();
            $this->assign('config',$config);
            return $this->fetch();
        }
    }

}