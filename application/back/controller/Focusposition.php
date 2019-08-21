<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Loader;
use think\Request;
use think\Url;
class Focusposition extends AdminBase
{
    /**
     * 位置列表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index()
    {
        $positionModel = Loader::model('FocusPosition');
        $positionRows  = $positionModel::paginate(25);
        $this->assign('positionRows', $positionRows);
        $this->assign('pages', $positionRows->render());

        return $this->fetch();
    }

    /**
     * 添加位置
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     */
    public function add()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            if (loader::validate('FocusPosition')->check($params) === false) {
                return $this->error(loader::validate('FocusPosition')->getError());
            }
            if (($positionId = Loader::model('FocusPosition')->positionAdd($params)) === false) {
                return $this->error(loader::model('FocusPosition')->getError());
            }
               Loader::model('SystemLog')->record("焦点图位置添加,ID:[{$positionId}]");
            return $this->success('焦点图位置添加成功', Url::build('back/focusposition/index'));
        }
    }

    /**
     * 修改位置
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function edit($id)
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params       = $request->param();
            $params['id'] = $id;

            if (loader::validate('FocusPosition')->check($params) === false) {
                return $this->error(loader::validate('FocusPosition')->getError());
            }
            if (($positionId = Loader::model('FocusPosition')->positionEdit($params)) === false) {
                return $this->error(loader::model('FocusPosition')->getError());
            }
             Loader::model('SystemLog')->record("编辑焦点图位置,ID:[{$id}]");
            return $this->success('焦点图位置修改成功', Url::build('back/focusposition/index'));
        }
        $positionModel = loader::model('FocusPosition');
        $positionRow   = $positionModel::get($id);
        $this->assign('positionRow', $positionRow);
        return $this->fetch();
    }

    /**
     * 删除位置
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function destroy($id)
    {
        if (Loader::model('FocusPosition')->deletePosition($id) === false) {
            return $this->error(loader::model('FocusPosition')->getError());
        }
           Loader::model('SystemLog')->record("删除焦点图位置,ID:[{$id}]");
        return $this->success('焦点图位置删除成功', Url::build('back/focusposition/index'));
    }
}
