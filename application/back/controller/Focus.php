<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Db;
use think\Loader;
use think\Request;
use think\Url;

class Focus extends AdminBase
{
    /**
     * 列表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-）
     * @return [type] [description]
     */
    public function index()
    {
        $focusModel = Loader::model('Focus');
        $focusRows  = $focusModel::paginate(2);
        $this->assign('focusRows', $focusRows);
        $this->assign('pages', $focusRows->render());

        return $this->fetch();
    }

    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-06-10
     */
    public function add()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
              $file = request()->file('image');
             $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'focus');
           if($info){
             $params['image']= '/uploads/focus/'.date("Ymd")."/".$info->getFilename();
            }else{
          // 上传失败获取错误信息
          echo $file->getError();
      }
            if (loader::validate('Focus')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Focus')->getError());
            }

            if (($focusId = Loader::model('Focus')->focusAdd($params)) === false) {
                return $this->error(loader::model('Focus')->getError());
            }
             Loader::model('SystemLog')->record("焦点图添加,ID:[{$focusId}]");
            return $this->success('焦点图添加成功', Url::build('back/focus/index'));
        }
        $this->assign('positionRows', Loader::model('FocusPosition')->select());

        return $this->fetch();
    }

    /**
     * 修改
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function edit($id)
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();

            $params['id'] = $id;
            if (loader::validate('Focus')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Focus')->getError());
            }
            if (($linksId = Loader::model('Focus')->focusEdit($params)) === false) {
                return $this->error(loader::model('Focus')->getError());
            }
            Loader::model('SystemLog')->record("焦点图修改,ID:[{$id}]");
            return $this->success('焦点图修改成功', Url::build('back/focus/index'));
        }
        $focusRow = Db::name('focus')->find($id);
        $this->assign('focusRow', $focusRow);
        $this->assign('positionRows', Loader::model('FocusPosition')->select());
        return $this->fetch();
    }

    /**
     * 删除
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function destroy($id)
    {
        if (Loader::model('Focus')->deleteFocus($id) === false) {
            return $this->error(loader::model('Focus')->getError());
        }
         Loader::model('SystemLog')->record("焦点图删除,ID:[{$id}]");
        return $this->success('焦点图删除成功', Url::build('back/focus/index'));
    }
}
