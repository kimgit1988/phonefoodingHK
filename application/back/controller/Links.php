<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Db;
use think\Loader;
use think\Request;
use think\Url;
use think\Controller;


class Links extends AdminBase {
    /**
     * 友情链接列表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index() {
        $linksModel = Loader::model('Links');
        $linksRows = $linksModel::paginate(1);
        $this->assign('linksRows', $linksRows);
        $this->assign('pages', $linksRows->render());
        
        return $this->fetch();
    }


    /**
     * 添加友情链接
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('image');
            $info = $file->move(ROOT_PATH.'public'.DS.'uploads'.DS.'linklogo');
            if ($info) {
                $params['logo'] = '/uploads/linklogo/'.date("Ymd")."/".$info->getFilename();
            } else {
                // 上传失败获取错误信息
                echo $file->getError();
            }
            if (loader::validate('Links')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Links')->getError());
            }
            if (($linksId = Loader::model('Links')->linksAdd($params)) === false) {
                return $this->error(loader::model('Links')->getError());
            }
            Loader::model('SystemLog')->record("友情链接添加:[{$linksId}]");
            return $this->success('友情链接添加成功', Url::build('back/links/index'));
        }
        return $this->fetch();
    }

    /**
     * 修改友情链接
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function edit($id) {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $params['id'] = $id;
            if (loader::validate('Links')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Links')->getError());
            }
            if (($linksId = Loader::model('Links')->linksEdit($params)) === false) {
                return $this->error(loader::model('Links')->getError());
            }
            Loader::model('SystemLog')->record("友情链接修改:[{$id}]");
            return $this->success('友情链接修改成功', Url::build('back/links/index'));
        }
        $linksRow = Db::name('links')->find($id);
        $this->assign('linksRow', $linksRow);
        return $this->fetch();
    }

    /**
     * 删除友情链接
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function destroy($id) {
        $linksModel = Loader::model('Links');
        if ($linksModel->deleteLinks($id) === false) {
            return $this->error($linksModel->getError());
        }
         Loader::model('SystemLog')->record("友情链接删除:[{$id}]");
        return $this->success('友情链接删除成功', Url::build('back/links/index'));
    }
}