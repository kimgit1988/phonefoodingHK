<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Printer extends AdminBase {

    public function index() {
        $param = input('param.');
        $where = array();
        // 餐厅列表
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        // 打印机型号列表
        $PrinterBrand = DB::name('PrinterBrand')->field('id,brand,brandNumber')->where(['isDelete'=>0])->select();
        // 所属餐厅
        if(isset($param['contact'])&&$param['contact']!==''){
            $where['p.contactNumber'] = $param['contact'];
        }
        if(isset($param['PrinterBrand'])&&$param['PrinterBrand']!==''){
            $where['p.brandId'] = $param['PrinterBrand'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['p.deviceNick'] = ['like','%'.$param['search'].'%'];
        }
        //打印機首页查询打印機
        $printer = DB::name('Printer')
            ->alias('p')
            ->join('mos_contact c','p.contactNumber = c.number and c.isDelete = 0','left')
            ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
            ->field('p.*,c.name as contactName,b.brand,b.brandNumber,b.fileName')
            ->where($where)
            ->where('p.isDelete',0)
            ->order('p.id desc')
            ->paginate(10,false,['query'=>$param]);
        $this->assign('printer',$printer);
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('PrinterBrand',$PrinterBrand);
        $this->assign('pages',$printer->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $time = time();
            $save = array(
                'deviceNick'    => $params['nick'],
                'deviceNumber'  => $params['number'],
                'shopNumber'    => $params['shop'],
                'apiKey'        => $params['api'],
                'contactNumber' => $params['contact'],
                'brandId'       => $params['PrinterBrand'],
                'disable'       => $params['disable'],
                'ctime'         => $time,
                'utime'         => $time,
            );
            //查詢是否有默認打印機沒有添加的打印機自動設為默認
            $default = DB::name('Printer')->where('defaultPrint',1)->where('contactNumber',$save['contactNumber'])->where('isDelete',0)->find();
            if(empty($default)){
                $save['defaultPrint'] = 1;
            }else{
                $save['defaultPrint'] = 0;
            }
            $number = DB::name('Printer')->where('deviceNumber',$save['deviceNumber'])->where('isDelete',0)->find();
            if(!empty($number)){
                $this->error('該設備編號已註冊');
            }
            if ($params['type']==1) {
                if (loader::validate('Printer')->scene('adminYAdd')->check($save) === false) {
                    return $this->error(loader::validate('Printer')->getError());
                }
            }else{
                if (loader::validate('Printer')->scene('adminAdd')->check($save) === false) {
                    return $this->error(loader::validate('Printer')->getError());
                }
            }
            if($params['type']==1){
                $brand = DB::name('PrinterBrand')->where('id',$save['brandId'])->where('isDelete',0)->find();
                Db::startTrans();
                $code = 1;
                $id = DB::name('Printer')->insertGetId($save);
                if($id!==false){
                    $printer = $save;
                    $printer['fileName'] = $brand['fileName'];
                    $print = new \app\printer\controller\Index;
                    $return = $print->addPrinter($printer);
                    if($return['code']==1){
                        // 提交事务
                        Db::commit();
                    }else{
                        $id = false;
                        //删除云端失败回滚
                        Db::rollback();
                    }
                }else{
                    // 删除数据库失败回滚
                    Db::rollback();
                }
            }else{
                $id = DB::name('Printer')->insertGetId($save);
            }
            if($id!==false){
                Loader::model('SystemLog')->record("添加打印機,ID:[{$id}]");
                return $this->success('添加打印機成功', Url::build('Printer/index'));
            }else{
                return $this->error('添加打印機失敗');
            }
        }else{
            $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
            $PrinterBrand = DB::name('PrinterBrand')->field('id,type,brand,brandNumber')->where(['isDelete'=>0])->select();
            $this->assign('contact',$contact);
            $this->assign('PrinterBrand',$PrinterBrand);
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
                'deviceNick'    => $params['nick'],
                'deviceNumber'  => $params['number'],
                'shopNumber'    => $params['shop'],
                'apiKey'        => $params['api'],
                'contactNumber' => $params['contact'],
                'brandId'       => $params['PrinterBrand'],
                'disable'       => $params['disable'],
                'utime'         => $time,
            );
            if (loader::validate('Printer')->scene('adminEdit')->check($save) === false) {
                return $this->error(loader::validate('Printer')->getError());
            }
            $res = true;
            DB::startTrans();
            try{
                DB::name('contact')->where('printerId',$params['id'])->where('number','<>',$params['contact'])->update(['printerId'=>null]);
                DB::name('Printer')->where('id',$params['id'])->update($save);
                Db::commit();
            } catch (\Exception $e) {
                $res = false;
                DB::rollback();
            }
            if($res!==false){
                Loader::model('SystemLog')->record("修改打印機,ID:[{$params['id']}]");
                return $this->success('修改打印機成功', Url::build('Printer/index'));
            }else{
                return $this->error('修改打印機失敗');
            }
        }else{
            $id = $request->param('id');
            $printer = DB::name('Printer')->where('id',$id)->where('isDelete',0)->find();
            $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
            $PrinterBrand = DB::name('PrinterBrand')->field('id,brand,brandNumber')->where(['isDelete'=>0])->select();
            $this->assign('PrinterBrand',$PrinterBrand);
            $this->assign('contact',$contact);
            $this->assign('printer',$printer);
            return $this->fetch();
        }
    }

    public function food() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = input('param.');
            if (isset($params['food'])) {
                foreach ($params['food'] as $key => $val) {
                    $gid[] = $key;
                }
            }else{
                $gid[] = 0;
            }
            if($params['type']=='add'){
                if(isset($params['mainPrinter'])){
                    $res = true;
                    // 启动事务
                    DB::startTrans();
                    try{
                        DB::name('goods')->where('id','in',$gid)->update(['printerId'=>$params['printer']]);
                        DB::name('contact')->where('number',$params['contact'])->update(['printerId'=>$params['printer']]);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        $res = false;
                        // 回滚事务
                        DB::rollback();
                        //注意：我们做了回滚处理，所以id为1039的数据还在
                    }
                }else{
                    $res = DB::name('goods')->where('id','in',$gid)->update(['printerId'=>$params['printer']]);
                }
            }else{
                if(isset($params['mainPrinter'])){
                    $res = true;
                    // 启动事务
                    DB::startTrans();
                    try{
                        DB::name('goods')->where('id','not in',$gid)->where('printerId',$params['printer'])->update(['printerId'=>'']);
                        DB::name('goods')->where('id','in',$gid)->update(['printerId'=>$params['printer']]);
                        DB::name('contact')->where('number',$params['contact'])->update(['printerId'=>$params['printer']]);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        $res = false;
                        // 回滚事务
                        DB::rollback();
                        //注意：我们做了回滚处理，所以id为1039的数据还在
                    }
                }else{
                    $res = true;
                    // 启动事务
                    DB::startTrans();
                    try{
                        DB::name('goods')->where('id','not in',$gid)->where('printerId',$params['printer'])->update(['printerId'=>'']);
                        DB::name('goods')->where('id','in',$gid)->update(['printerId'=>$params['printer']]);
                        DB::name('contact')->where('number',$params['contact'])->where('printerId',$params['printer'])->update(['printerId'=>'']);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        $res = false;
                        // 回滚事务
                        DB::rollback();
                        //注意：我们做了回滚处理，所以id为1039的数据还在
                    }
                }
            }
            if($res!==false){
                $this->success('设置成功');
            }else{
                $this->error('设置失败');
            }
        }else{
            $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }

    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
        $printer = DB::name('Printer')
            ->alias('p')
            ->join('mos_printer_brand b','p.brandId = b.id and b.isDelete = 0','left')
            ->field('p.*,b.fileName')
            ->where('p.id',$id)
            ->where('p.isDelete',0)
            ->find();
        // 启动事务
        Db::startTrans();
        $code = 1;
        $res = DB::name('Printer')->where('id',$id)->update(['utime'=>time(),'isDelete'=>1]);
        if($res){
            $print = new \app\printer\controller\Index;
            $return = $print->delPrinter($printer);
            if($return['code']==1){
                // 提交事务
                Db::commit();
            }else{
                $code = 0;
                //删除云端失败回滚
                Db::rollback();
            }
        }else{
            $code = 0;
            // 删除数据库失败回滚
            Db::rollback();
        }
        if($code){
            Loader::model('SystemLog')->record("打印機删除,ID:[{$id}]");
            return $this->success('打印機删除成功', Url::build('Printer/index'));
        }else{
            return $this->error('打印機删除失败');
        }

    }

    public function setDefault(){
        $request = Request::instance();
        $id = $request->param('id');
        $printer = DB::name('Printer')->where('id',$id)->where('isDelete',0)->find();
        Db::startTrans();
        $code = 1;
        try{
            Db::name('Printer')->where('contactNumber',$printer['contactNumber'])->where('id','neq',$id)->update(['defaultPrint'=>0]);
            Db::name('Printer')->where('contactNumber',$printer['contactNumber'])->where('id',$id)->update(['defaultPrint'=>1]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $code = 0;
            // 回滚事务
            Db::rollback();
        }
        if($code){
            Loader::model('SystemLog')->record("打印機設置默認,ID:[{$id}]");
            return $this->success('打印機設置默認成功', Url::build('Printer/index'));
        }else{
            return $this->error('打印機設置默認失败');
        }
    }
}