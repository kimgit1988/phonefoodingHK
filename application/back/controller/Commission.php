<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Commission extends AdminBase {
    
    public function index() {
        $commission = DB::name('commission')->where(['isDelete'=>0])->order('id desc')->paginate(10);
        $this->assign('commission', $commission);
        $this->assign('pages',$commission->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $save   = array(
                'name'      =>  $params['name'],
                'percent'   =>  $params['market'],
                'startNum'  =>  $params['start'],
                'endNum'    =>  $params['end'],
                'disable'   =>  $params['status'],
            );
            if (loader::validate('Commission')->scene('backadd')->check($save) === false) {
                return $this->error(loader::validate('Commission')->getError());
            }
            if (($id = DB::name('Commission')->insertGetId($save)) === false) {
                return $this->error('添加佣金分组失败');
            }
            Loader::model('SystemLog')->record("添加佣金分组,ID:[{$id}]");
            return $this->success('添加佣金分组成功', Url::build('Commission/index'));
        }else{
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params = $request->param();
            $save   = array(
                'id'        =>  $params['id'],
                'name'      =>  $params['name'],
                'percent'   =>  $params['market'],
                'startNum'  =>  $params['start'],
                'endNum'    =>  $params['end'],
                'disable'   =>  $params['status'],
            );
            if (loader::validate('Commission')->scene('backedit')->check($save) === false) {
                return $this->error(loader::validate('Commission')->getError());
            }
            if ((DB::name('Commission')->where('id',$save['id'])->update($save)) === false) {
                return $this->error('修改佣金分组失败');
            }
            Loader::model('SystemLog')->record("修改佣金分组,ID:[{$save['id']}]");
            return $this->success('修改佣金分组成功', Url::build('Commission/index'));
        }else{
            $commission = DB::name('Commission')->where('isDelete',0)->where('id',$id)->find();
            $this->assign('commission',$commission);
            return $this->fetch();
        }
    }

    public function del() {
        $request = Request::instance();
        $id = $request->param('id');
        $del = array('isDelete' => 1);
        if (DB::name('Commission')->where('id',$id)->update($del) === false) {
            return $this->error('佣金分组删除失败');
        }
        Loader::model('SystemLog')->record("删除佣金分组,ID:[{$id}]");
        return $this->success('佣金分组删除成功', Url::build('Commission/index'));
    }

}