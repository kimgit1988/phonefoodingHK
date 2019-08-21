<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Loader;
use think\Request;
use think\Url;
use think\Controller;
class Rule extends AdminBase {
    /**
     * 菜单列表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index() {
        $param = input('get.');
        $where = array();
        $ruleModel = Loader::model('Rule');
        if(isset($param['rid'])&&$param['rid']!==''){
            $where['id'] = $param['rid'];
        }
        if(!empty($where)){
            // 查询时自动调用model中parent()
            $lists = $ruleModel::where($where)->order('sort asc')->paginate(2,false,['query'=>$param]); 
        }else{
            $lists = $ruleModel::where(array('parent_id' => 0))->order('sort asc')->paginate(2,false,['query'=>$param]); 
        }
        $select = $ruleModel::field('id,title')->where(array('parent_id' => 0))->order('sort asc')->select();
        $this->assign('param', $param);
        $this->assign('select', $select);
        $this->assign('lists', $lists);
        $this->assign('pages', $lists->render());
        return $this->fetch();
    }

    /**
     * 添加菜单
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param string $value [description]
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            if (loader::validate('Rule')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Rule')->getError());
            }
            if (($ruleId = Loader::model('Rule')->save($params)) === false) {
                return $this->error(loader::model('Rule')->getError());
            }
             Loader::model('SystemLog')->record("添加权限菜单,ID:[{$ruleId}]");
            return $this->success('菜单添加成功', Url::build('back/rule/index'));
        }
        $ruleModel = Loader::model('Rule');
        $ruleRows = $ruleModel::where(array('status' => 1, 'parent_id' => 0))->order('sort asc')->select();;

        $this->assign('ruleRows', $ruleRows);

        return $this->fetch();

    }

    /**
     * 编辑
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function edit() {
        $id = input('id');
        $ruleModel = Loader::model('Rule');
        $ruleRow = $ruleModel::get($id);
        if ($ruleRow === false) {
            $this->error('没有找到对应的数据');
        }

        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $params['id'] = $id;

            if (loader::validate('Rule')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Rule')->getError());
            }
            if (Loader::model('Rule')->save($params, ['id' => $id]) === false) {
                return $this->error(loader::model('Rule')->getError());
            }
              Loader::model('SystemLog')->record("编辑权限菜单,ID:[{$id}]");
            return $this->success('菜单修改成功', Url::build('back/rule/index'));
        }

        $ruleModel = Loader::model('Rule');
        $ruleRows = $ruleModel::where(array('status' => 1, 'parent_id' => 0))->order('sort asc')->select();;
        $this->assign('ruleRows', $ruleRows);
        $this->assign('ruleRow', $ruleRow);
        return $this->fetch();
    }
    /**
     * 删除
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function delete($id) {
        $RuleModel = Loader::model('Rule');
        if ($RuleModel->deleteRule($id) === false) {
            return $this->error($RuleModel->getError());
        }
          Loader::model('SystemLog')->record("删除权限菜单,ID:[{$id}]");
        return $this->success('菜单列表删除成功', Url::build('back/rule/index'));
    }
}