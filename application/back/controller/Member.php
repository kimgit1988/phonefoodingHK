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
class Member extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $where = array();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$param['contact'])->select();
            $where['m.contactNumber'] = $param['contact'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['m.name|m.number'] = ['like','%'.$param['search'].'%'];
        }
        // 正常来说该权限分配给商家需要判断商家
        $Member = Loader::model('contactMember')
        ->alias('m')
        ->field('*,m.id as mid,m.number as mnumber,m.name as mname,m.cCategoryName as category,m.disable as mdisable')
        ->join('mos_contact c','m.contactNumber = c.number','left')
        ->where(['m.isDelete'=>0])
        ->where($where)
        ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('lists', $Member);
        $this->assign('contact',$contact);
        $this->assign('pages',$Member->render());
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
            $params   = $request->param();
            if(session('ext_user.is_contact')==0){
                $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'contactMember'])->where('id',$params['cCategory'])->find();
                $params['contactNumber'] = '';
            }else{
                // 商家可以看到自己创建的分类(或管理员建立的)
                $contact_number = session('ext_user.contact_number');
                $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'contactMember','contactNumber'=>$contact_number])->where('id',$params['cCategory'])->find();
                $params['contactNumber'] = $contact_number;
            }
            if(empty($category)){
                return $this->error('请选择正确的分类');
            }else{
                $params['cCategory']     = $category['id'];
                $params['cCategoryName'] = $category['name'];
            }
            if (loader::validate('Member')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Member')->getError());
            }
            if (($cateId = Loader::model('ContactMember')->memberAdd($params)) === false) {
                return $this->error(Loader::model('ContactMember')->getError());
            }
            Loader::model('SystemLog')->record("添加餐桌,ID:[{$cateId}]");
            return $this->success('添加餐桌成功', Url::build('Member/index'));
        }else{
            if(session('ext_user.is_contact')==0){
                $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'contactMember'])->order('ordnum asc')->select();
            }else{
                // 商家可以看到自己创建的分类(或管理员建立的)
                $contact_number = session('ext_user.contact_number');
                $customertype =DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'contactMember','contactNumber'=>$contact_number])->order('ordnum asc')->select();
            }
            $tree = arrtree($customertype,"child");
            $this->assign('type',$tree);
        }
        return $this->fetch();
    }

    //批量添加餐桌方法:/index.php/back/member/addBatch?contact=商家编号&number=数量
    public function addBatch() {
        $post = input('param.');
        if(!empty($post['contact'])&&!empty($post['number']))
        {
            $contact_info = Db::name('Contact')->where('number',$post['contact'])->find();
            if(empty($contact_info)) $this->error('商家不存在',url::build('member/index'));
            $save = [];
            $contactNumber = Db::name('contactMember')->where('contactNumber',$post['contact'])->count();
            $ii = $contactNumber;
            for($i=1;$i<=$post['number'];$i++)
            {
                $ii++;
                $save[] = ['contactNumber'=>$post['contact'],'name'=>$ii.'號','number'=>$contact_info['id'].'_'.$ii,'disable'=>1,'isDelete'=>0];
            }
            DB::name('contactMember')->insertAll($save);
            $this->success('添加成功',url::build('member/index'));
        }
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
            $params   = $request->param();
            $params['contactNumber'] = $params['contact'];
            if (loader::validate('Member')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Member')->getError());
            }
            if (($cateId = Loader::model('ContactMember')->memberEdit($params)) === false) {
                return $this->error(Loader::model('ContactMember')->getError());
            }
            Loader::model('SystemLog')->record("餐桌编辑,ID:[{$id}]");
            return $this->success('餐桌编辑成功', Url::build('Member/index'));
        }else{
            $member = DB::name('contactMember')->where('isDelete',0)->where('id',$id)->find();
            $contact = DB::name('contact')->field('id,number,name')->where('isDelete',0)->select();
            $this->assign('contact',$contact);
            $this->assign('member',$member);
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
        if (Loader::model('contactMember')->deleteMember($id) === false) {
            return $this->error(Loader::model('contactMember')->getError());
        }
        Loader::model('SystemLog')->record("餐桌删除,ID:[{$id}]");
        return $this->success('餐桌删除成功', Url::build('member/index'));
    }


}