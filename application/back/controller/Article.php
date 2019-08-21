<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Articlecat;
use app\common\model\Attr;
use app\common\model\Article as Art;
use think\File;
use think\Db;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
class Article extends AdminBase {
	
    public function index() {
        $article = Art::field("id,title,create_time,description,thumbnail")->paginate(1);
        $page = $article->render();
        $this->assign('article', $article);
        $this->assign('page', $page);
        return $this->fetch();
    }

  /**
     * [aricleAdd description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016
     * @param  array  $params [description]
     * @return [type] [description]
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('image');
            $info = $file->move(ROOT_PATH.'public'.DS.'uploads');
            if ($info) {
                $params['thumbnail'] = '/uploads/'.date("Ymd")."/".$info->getFilename();
            } else {
                // 上传失败获取错误信息
                return $this->getError();
            }
            if (loader::validate('Article')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Article')->getError());
            }

            if (($aricleId = Loader::model('Article')->articleAdd($params)) === false) {
                return $this->error(Loader::model('Article')->getError());
            }
            Loader::model('SystemLog')->record("文章添加,ID:[{$aricleId}]");
            return $this->success('文章添加成功', Url::build('back/article/index'));
        }
        $catrows = Articlecat::selectField()->where(['parent_id' => 0])->select();
        $attr = Attr::read()->select();
        $this->assign('catrows', $catrows);
        $this->assign('attr', $attr);
        return $this->fetch();
    }



/**
     * [aricleedit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016
     * @param  array  $params [description]
     * @return [type] [description]
     */
    public function edit($id) {
        $request = Request::instance();
        $aricle = Art::get($id);
        if ($request->isPost()) {
            $params = $request->param();
            if (loader::validate('Article')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Article')->getError());
            }
            if (($aricleId = Loader::model('Article')->articleEdit($params, $id)) === false) {
                return $this->error(Loader::model('Article')->getError());
            }
            Loader::model('SystemLog')->record("文章编辑,ID:[{$id}]");
            return $this->success('文章编辑成功', Url::build('back/article/index'));
        }
        $catrows = Articlecat::selectField()->where(['parent_id' => 0])->select();
        $attr = Attr::read()->select();
        $this->assign('catrows', $catrows);
        $this->assign('attr', $attr);
        $this->assign('article', $aricle);
        return $this->fetch();
    }

/**
     * [aricledelete description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016
     * @param  array  $params [description]
     * @return [type] [description]
     */
    public function destroy($id) {
        if (Loader::model('Article')->deleteArticle($id) === false) {
            return $this->error(Loader::model('Article')->getError());
        }
        Loader::model('SystemLog')->record("文章删除,ID:[{$id}]");
        return $this->success('文章删除成功', Url::build('back/article/index'));
    }


/**
     * [栏目所述文章 description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016
     * @param  array  $params [description]
     * @return [type] [description]
     */
    public function searcharticle($id) {
        $searchArticle = Art::searchArticle()->where(['category_id' => $id])->paginate(1);
        $page = $searchArticle->render();
        $this->assign('page', $page);
        $this->assign("searchArticle", $searchArticle);
        return $this->fetch();
    }


}