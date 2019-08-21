<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Rule;
use think\Loader;
use think\Request;
use think\Url;
use think\Controller;

class groupdata extends AdminBase
{
    /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index()
    {
        if(session('ext_user.is_contact')==0){
            $lists = Loader::model('groupdata')->paginate(5);
        }else{
            $contact_number = session('ext_user.contact_number');
            $lists = Loader::model('groupdata')->where('contact_number',$contact_number)->paginate(5);
        }

        $this->assign('lists', $lists);
        $this->assign('pages', $lists->render());

        return $this->fetch();
    }

    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     */
    public function add()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $data      = $request->param();
            $data['rules']=implode(',', $data['rules']);
            $roleModel = Loader::model('Groupdata');
            if (loader::validate('Groupdata')->scene('add')->check($data) === false) {
                return $this->error(loader::validate('Groupdata')->getError());
            }

            if (($id = $roleModel->addRole($data)) !== false) {
            Loader::model('SystemLog')->record("用户组添加:[{$id}]");
                return $this->success('用户组添加成功', Url::build('back/groupdata/index'));
            }

            return $this->error($roleModel->getError());

        }else{
            if(session('ext_user.is_contact')==0){
                $this->assign('ruleRows', Loader::model('Rule')->where(array('status'=>1,'parent_id' => 0))->order('sort asc')->select());
            }else{
                $this->assign('ruleRows', Loader::model('Rule')->where(array('contactHas'=>1,'status'=>1,'parent_id' => 0))->order('sort asc')->select());
            }
            return $this->fetch();
        }
     
    }

    /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function edit($id)
    {
        $ruleRow = Loader::model('groupdata')->find($id);
        $zcc=$ruleRow->rules;
        $ids = array();
        $ids = array_merge($ids, explode(',', trim($zcc, ',')));
        $request = Request::instance();
        if ($request->isPost()) {
            $data = $request->param();
            $data['rules']=implode(',', $data['rules']);
            // dump($data);exit();
            if (loader::validate('Groupdata')->scene('edit')->check($data) === false) {
                return $this->error(loader::validate('Groupdata')->getError());
            }
            if (loader::model('Groupdata')->editRule($data) !== false) {
                 Loader::model('SystemLog')->record("用户组修改:[{$id}]");
                return $this->success('用户组修改成功', Url::build('back/groupdata/index'));
            }
            return $this->error(loader::model('Groupdata')->getError());
        }
        // 用户组所有权限
        if(session('ext_user.is_contact')==0){
            $this->assign('ruleRows', Loader::model('Rule')->where(array('status'=>1,'parent_id' => 0))->order('sort asc')->select());
        }else{
            $this->assign('ruleRows', Loader::model('Rule')->where(array('contactHas'=>1,'status'=>1,'parent_id' => 0))->order('sort asc')->select());
        }
        $this->assign('ruleRow', $ruleRow);
        $this->assign('myRuleRows', $ids);

        return $this->fetch();
    }

    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function destroy($id)
    {
        $ruleModel = Loader::model('Groupdata');
        if ($ruleModel->deleteRule($id) === false) {
            return $this->error($ruleModel->getError());
        }
        Loader::model('SystemLog')->record("用户组删除:[{$id}]");
        return $this->success('用户组删除成功', Url::build('back/groupdata/index'));
    }
}
