<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Category;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Contactmember extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $where = array();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            // $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'contactmember'])->where('contactNumber',$param['contact'])->select();
            $where['t.contactNumber'] = $param['contact'];
        }
        $cateModel = Loader::model('Category');
        $contactmember =$cateModel->alias('t')
        ->field('*,t.id as id,t.name as name,c.name as cname')
        ->join('mos_contact c','t.contactNumber = c.number','left')
        ->where(array('t.isDelete' => 0,'t.parentId' => 0,'t.typeNumber'=>'contactmember'))
        ->where($where)
        ->order('t.ordnum asc')
        ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('lists', $contactmember);
        $this->assign('pages',$contactmember->render());
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
            $parent = explode(',', $params['parent']);
            $params['parentId'] = $parent[0];
            $params['level'] = ($parent[1]+1);
            if (loader::validate('Category')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Category')->getError());
            }

            if (($cateId = Loader::model('Category')->contactmemberAdd($params)) === false) {
                return $this->error(Loader::model('Category')->getError());
            }
            Loader::model('SystemLog')->record("添加餐桌分类,ID:[{$cateId}]");
            return $this->success('添加餐桌分类成功', Url::build('contactmember/index'));
        }else{
            if(session('ext_user.is_contact')==0){
                // 系统管理员可以看到全部分类
                $cateModel = Loader::model('Category');
                $contactmember =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'contactmember'))->order('ordnum asc')->select();
                
            }else{
                // 商家可以看到自己创建的分类(或管理员建立的)
                $contact_number = session('ext_user.contact_number');
                $cateModel = Loader::model('Category');
                $contactmember =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'contactmember'))->where('contactNumber',$contact_number)->order('ordnum asc')->select();
            }
            $this->assign('lists',$contactmember);
        }
        return $this->fetch();
    }


  /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */

    public function edit() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params = $request->param();
            $parent = explode(',', $params['parent']);
            $params['parentId'] = $parent[0];
            $params['level'] = ($parent[1]+1);
            if (loader::validate('Category')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Category')->getError());
            }
            if (($edit = Loader::model('Category')->contactmemberEdit($params)) === false) {
                return $this->error(Loader::model('Category')->getError());
            }
            Loader::model('SystemLog')->record("餐桌分类编辑,ID:[{$id}]");
            return $this->success('餐桌分类编辑成功', Url::build('contactmember/index'));
        }else{
            $cateModel = Loader::model('Category');
            if(session('ext_user.is_contact')==0){
                // 系统管理员修改全部分类
                $cateModel = Loader::model('Category');
                $contactmember = $cateModel::where(array('id'=>$id,'isDelete' => 0,'typeNumber'=>'contactmember'))->find();
                $lists =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'contactmember'))->order('ordnum asc')->select();
            }else{
                // 商家可以修改自己创建的分类
                $contact_number = session('ext_user.contact_number');
                $cateModel = Loader::model('Category');
                $contactmember =$cateModel::where(array('id'=>$id,'isDelete' => 0,'typeNumber'=>'contactmember'))->where('contactNumber',$contact_number)->find();
                $lists =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'contactmember'))->where('contactNumber',$contact_number)->order('ordnum asc')->select();
            }
            if(empty($contactmember)){
                return $this->error('该分类不存在或你无权修改！');
            }else{
                $this->assign('contactmember', $contactmember);
            }
            $this->assign('lists',$lists);
        }
        return $this->fetch();
    }

  /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
         if(session('ext_user.is_contact')!=0){
            $contact_number = session('ext_user.contact_number');
            $cateModel = Loader::model('Category');
            $contactmember =$cateModel::where(array('id'=>$id,'isDelete' => 0,'typeNumber'=>'contactmember'))->where('contactNumber',$contact_number)->find();
            if(empty($contactmember)){
                return $this->error('该分类不存在或你无权删除！');
            }
        }
        if (Loader::model('Category')->deleteAttr($id) === false) {
            return $this->error(Loader::model('Attr')->getError());
        }
        Loader::model('SystemLog')->record("属性删除,ID:[{$id}]");
        return $this->success('属性删除成功', Url::build('contactmember/index'));
    }


}