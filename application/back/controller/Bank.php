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
class Bank extends AdminBase {
    
    public function index() {
        $bank = DB::name('bank')->order('id desc')->where('isDelete',0)->paginate(10);
        $this->assign('bank',$bank);
        $this->assign('pages',$bank->render());
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
            $params = $request->param();
            $save['bankname'] = $params['name'];
            $save['bankcode'] = $params['number'];
            if (loader::validate('Bank')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Bank')->getError());
            }
            $id = DB::name('Bank')->insertGetId($save);
            if($id!==false){
                Loader::model('SystemLog')->record("添加銀行,ID:[{$id}]");
                return $this->success('添加銀行成功', Url::build('Bank/index'));
            }else{
                return $this->error('添加銀行失敗');
            }
        }else{
            return $this->fetch();
        }
    }


  /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $save['id']       = $params['id'];
            $save['bankname'] = $params['name'];
            $save['bankcode'] = $params['number'];
            if (loader::validate('Bank')->scene('edit')->check($save) === false) {
                return $this->error(loader::validate('Bank')->getError());
            }
            $id = DB::name('Bank')->where('id',$params['id'])->update($save);
            if($id!==false){
                Loader::model('SystemLog')->record("修改銀行,ID:[{$id}]");
                return $this->success('修改銀行成功', Url::build('Bank/index'));
            }else{
                return $this->error('修改銀行失敗');
            }
        }else{
            $id = $request->param('id');
            $bank = DB::name('bank')->where('id',$id)->find();
            $this->assign('bank',$bank);
            return $this->fetch();
        }
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
        $res = DB::name('bank')->where('id',$id)->update(['isDelete'=>1]);
        Loader::model('SystemLog')->record("銀行删除,ID:[{$id}]");
        return $this->success('銀行删除成功', Url::build('bank/index'));
    }
}