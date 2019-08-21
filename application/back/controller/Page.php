<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Loader;
use think\Request;
use think\Url;

class Page extends AdminBase {
    /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index() {
        $pageModel = Loader::model('Page');
        $pageRows = $pageModel::listField()->where(['parent_id' => 0])->order('sort asc')->select();
        $this->assign('pageRows', $pageRows);
        return $this->fetch();
    }

    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            if (loader::validate('Page')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Page')->getError());
            }
            if (($pageId = Loader::model('Page')->pageAdd($params)) === false) {
                return $this->error(Loader::model('Page')->getError());
            }
            Loader::model('SystemLog')->record("添加单页面：[{$pageId}]");
           return $this->success('单页面添加成功', Url::build('back/page/index'));
        }
        $pageModel = Loader::model('Page');
        $pageRows = $pageModel::selectField()->where(['parent_id' => 0])->select();
        $this->assign('pageRows', $pageRows);

        return $this->fetch();
    }


    /**
     * [edit description]
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
            if (loader::validate('Page')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Page')->getError());
            }
            if (($pageId = Loader::model('Page')->pageEdit($params)) === false) {
                return $this->error(Loader::model('Page')->getError());
            }
               Loader::model('SystemLog')->record("修改单页面：[{$id}]");
            return $this->success('单页面修改成功', Url::build('back/page/index'));
        }
        $pageModel = Loader::model('Page');
        $pageRow = $pageModel::get($id);
        $pageRows = $pageModel::selectField()->where(['parent_id' => 0])->select();
        $this->assign('pageRows', $pageRows);
        $this->assign('pageRow', $pageRow);

        return $this->fetch();
    }


    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function destroy($id) {
        $pageModel = Loader::model('Page');
        if ($pageModel->deletePage($id) === false) {
            return $this->error($pageModel->getError());
        }
         Loader::model('SystemLog')->record("删除单页面,ID:[{$id}]");
        return $this->success('单页面删除成功', Url::build('back/page/index'));
    }
}