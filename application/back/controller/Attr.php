<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Attr as shuxin;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
class Attr extends AdminBase {
    
    public function index() {
        $attr = shuxin::read()->select();
        $this->assign('attr', $attr);
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
            if (loader::validate('Attr')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Attr')->getError());
            }

            if (($attraId = Loader::model('Attr')->attrAdd($params)) === false) {
                return $this->error(Loader::model('Attr')->getError());
            }
             Loader::model('SystemLog')->record("添加属性,ID:[{$attraId}]");
            return $this->success('添加属性成功', Url::build('back/attr/index'));
        }
        return $this->fetch();
    }


  /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */

    public function edit($aid) {
        $request = Request::instance();
        $attr = shuxin::get($aid);
        if ($request->isPost()) {
            $params = $request->param();
            if (loader::validate('Attr')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Attr')->getError());
            }
            if (($attraId = Loader::model('Attr')->attrEdit($params)) === false) {
                return $this->error(Loader::model('Attr')->getError());
            }
             Loader::model('SystemLog')->record("属性编辑,ID:[{$aid}]");
            return $this->success('属性编辑成功', Url::build('back/attr/index'));
        }

        $this->assign('attr', $attr);
        return $this->fetch();
    }

  /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function destroy($aid) {
        if (Loader::model('Attr')->deleteAttr($aid) === false) {
            return $this->error(Loader::model('Attr')->getError());
        }
        Loader::model('SystemLog')->record("属性删除,ID:[{$aid}]");
        return $this->success('属性删除成功', Url::build('back/attr/index'));
    }


}