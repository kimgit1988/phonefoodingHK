<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Loader;
use think\Request;
use think\Url;
use think\Controller;
class Articlecat extends AdminBase
{
    /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @@dateTime 2016-10-26
     * @return [type] [description]
     */
    public function index()
    {
        $Articlecat = Loader::model('Articlecat');
        $Articlecat  = $Articlecat::listField()->order('sort asc')->where(['parent_id' => 0])->select();
        $this->assign('Articlecat', $Articlecat);

        return $this->fetch();
    }

    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     */
    public function add()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();

            if (loader::validate('Articlecat')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Articlecat')->getError());
            }

            if (($ArticlecatId = Loader::model('Articlecat')->ArticlecatAdd($params)) === false) {
                return $this->error(Loader::model('Articlecat')->getError());
            }
               Loader::model('SystemLog')->record("文章分类添加,ID:[{$ArticlecatId}]");
            return $this->success('文章分类添加成功', Url::build('back/Articlecat/index'));
        }

        $ArticlecatModel = Loader::model('Articlecat');
        $ArticlecatRows  = $ArticlecatModel::selectField()->where(['parent_id' => 0])->select();
        $this->assign('articlecatRows', $ArticlecatRows);

        return $this->fetch();
    }

    /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function edit($id)
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params       = $request->param();
            $params['id'] = $id;
            if (loader::validate('Articlecat')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Articlecat')->getError());
            }

            if (($ArticlecatId = Loader::model('Articlecat')->articlecatEdit($params)) === false) {
                return $this->error(Loader::model('Articlecat')->getError());
            }
            Loader::model('SystemLog')->record("文章分类修改,ID:[{$id}]");
            return $this->success('文章分类修改成功', Url::build('back/articlecat/index'));
        }
        $articlecatModel = Loader::model('Articlecat');
        $articlecatRow  = $articlecatModel::get($id);
        $articlecatRows = $articlecatModel::selectField()->where(['parent_id' => 0])->select();
        $this->assign('articlecatRows', $articlecatRows);
        $this->assign('articlecatRow', $articlecatRow);
        return $this->fetch();
    }

    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function destroy($id)
    {
        $ArticlecatModel = Loader::model('Articlecat');
        if ($ArticlecatModel->deleteArticlecat($id) === false) {
            return $this->error($ArticlecatModel->getError());
        }
          Loader::model('SystemLog')->record("文章分类删除,ID:[{$id}]");
        return $this->success('文章分类删除成功', Url::build('back/Articlecat/index'));
    }

   
}
