<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Printerbrand extends AdminBase {
    
    public function index() {
        $brand = DB::name('PrinterBrand')->order('id desc')->where('isDelete',0)->paginate(10);
        $this->assign('brand',$brand);
        $this->assign('pages',$brand->render());
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
            $time = time();
            $save = array(
                'brand'       => $params['name'],
                'brandNumber' => $params['number'],
                'shopNumber'  => $params['shop'],
                'apiKey'      => $params['api'],
                'fileName'    => ucfirst(strtolower($params['file'])),
                'type'        => $params['type'],
                'disable'     => $params['disable'],
                'ctime'       => $time,
                'utime'       => $time,
            );
            if (loader::validate('PrinterBrand')->scene('adminAdd')->check($save) === false) {
                return $this->error(loader::validate('PrinterBrand')->getError());
            }
            $id = DB::name('PrinterBrand')->insertGetId($save);
            if($id!==false){
                Loader::model('SystemLog')->record("添加打印机型号,ID:[{$id}]");
                return $this->success('添加打印机型号成功', Url::build('Printerbrand/index'));
            }else{
                return $this->error('添加打印机型号失敗');
            }
        }else{
            return $this->fetch();
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
        if ($request->isPost()) {
            $params = $request->param();
            $time = time();
            $save = array(
                'id'          => $params['id'],
                'brand'       => $params['name'],
                'brandNumber' => $params['number'],
                'shopNumber'  => $params['shop'],
                'apiKey'      => $params['api'],
                'fileName'    => ucfirst(strtolower($params['file'])),
                'type'        => $params['type'],
                'disable'     => $params['disable'],
                'utime'       => $time,
            );
            if (loader::validate('PrinterBrand')->scene('adminEdit')->check($save) === false) {
                return $this->error(loader::validate('PrinterBrand')->getError());
            }
            $id = DB::name('PrinterBrand')->where('id',$params['id'])->update($save);
            if($id!==false){
                Loader::model('SystemLog')->record("修改打印机型号,ID:[{$id}]");
                return $this->success('修改打印机型号成功', Url::build('Printerbrand/index'));
            }else{
                return $this->error('修改打印机型号失敗');
            }
        }else{
            $id = $request->param('id');
            $brand = DB::name('PrinterBrand')->where('id',$id)->find();
            $this->assign('brand',$brand);
            return $this->fetch();
        }
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
        $res = DB::name('PrinterBrand')->where('id',$id)->update(['utime'=>time(),'isDelete'=>1]);
        Loader::model('SystemLog')->record("打印机型号删除,ID:[{$id}]");
        return $this->success('打印机型号删除成功', Url::build('Printerbrand/index'));
    }
}