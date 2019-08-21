<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Department extends AdminBase {
    
    public function index() {
        $param = input('param.');
        $where = array();
        // 餐厅列表
        $contact = DB::name('Contact')->field('id,number,name')->where('isDelete',0)->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            $where['d.contactNumber'] = $param['contact'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['p.deviceNick'] = ['like','%'.$param['search'].'%'];
        }
        //打印機首页查询打印機
        $department = DB::name('ContactDepartment')
        ->alias('d')
        ->join('mos_contact c','d.contactNumber = c.number and c.isDelete = 0','left')
        ->join('mos_printer p','d.printerId = p.id and p.isDelete = 0','left')
        ->field('d.*,c.name as contactName,p.deviceNick,p.deviceNumber')
        ->where($where)
        ->where('d.isDelete',0)
        ->order('d.id desc')
        ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('department',$department);
        $this->assign('pages',$department->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $time = time();
            $save = array(
                'name'          => $params['name'],
                'printerId'     => $params['printer'],
                'reprinterId'   => $params['reprinter'],
                'contactNumber' => $params['contact'],
                'disable'       => $params['disable'],
                'ctime'         => $time,
                'utime'         => $time,
            );
            if (loader::validate('ContactDepartment')->scene('adminAdd')->check($save) === false) {
                return $this->error(loader::validate('ContactDepartment')->getError());
            }
            $id = DB::name('ContactDepartment')->insertGetId($save);
            if($id!==false){
                Loader::model('SystemLog')->record("添加部門,ID:[{$id}]");
                return $this->success('添加部門成功', Url::build('Department/index'));
            }else{
                return $this->error('添加部門失敗');
            }
        }else{
            $contact = DB::name('Contact')->field('id,number,name')->where('isDelete',0)->select();
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $time = time();
            $save = array(
                'id'            => $params['id'],
                'name'          => $params['name'],
                'printerId'     => $params['printer'],
                'reprinterId'   => $params['reprinter'],
                'contactNumber' => $params['contact'],
                'disable'       => $params['disable'],
                'ctime'         => $time,
                'utime'         => $time,
            );
            if (loader::validate('ContactDepartment')->scene('adminEdit')->check($save) === false) {
                return $this->error(loader::validate('ContactDepartment')->getError());
            }
            $res = DB::name('ContactDepartment')->where('id',$params['id'])->update($save);
            if($res!==false){
                Loader::model('SystemLog')->record("修改部門,ID:[{$params['id']}]");
                return $this->success('修改部門成功', Url::build('Department/index'));
            }else{
                return $this->error('修改部門失敗');
            }
        }else{
            $id = $request->param('id');
            $department = DB::name('ContactDepartment')->where('id',$id)->find();
            $contact = DB::name('Contact')->field('id,number,name')->where('isDelete',0)->select();
            $printer = DB::name('Printer')
            ->alias('p')
            ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
            ->field('p.id,p.deviceNick,b.brand,b.brandNumber,b.fileName')
            ->where('p.contactNumber',$department['contactNumber'])
            ->where('p.isDelete',0)
            ->order('p.id desc')
            ->select();
            $this->assign('department',$department);
            $this->assign('contact',$contact);
            $this->assign('printer',$printer);
            return $this->fetch();
        }
    }


    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
        $res = DB::name('ContactDepartment')->where('id',$id)->update(['isDelete'=>1]);
        Loader::model('SystemLog')->record("部門删除,ID:[{$id}]");
        return $this->success('部門删除成功', Url::build('Department/index'));
    }
}