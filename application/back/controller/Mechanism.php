<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Mechanism extends AdminBase {
    
    public function index() {
        $mechanism = DB::name('Mechanism')
        ->alias('m')
        ->join('mos_user u','m.id = u.mechanismId AND u.mechanismAdmin = 1','left')
        ->field('*,m.id as id,m.name as name,u.name as uname,u.nick as nick')
        ->where('m.isDelete',0)
        ->order('m.id desc')
        ->paginate(10);
        $this->assign('mechanism', $mechanism);
        $this->assign('pages',$mechanism->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $bank = array();
            $params = $request->param();
            $commission = DB::name('commission')->where('id',$params['commission'])->find();
            $save   = array(
                'name'              =>  $params['name'],
                'commissionId'      =>  $params['commission'],
                'commission'        =>  $commission['percent'],
                'disable'           =>  $params['status'],
            );
            $file = request()->file('images');
            // 调用上传方法
            $upload = uploadPic($file,'head');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($upload['code']==1){
                $params['head'] = $upload['msg'];
            }else{
                return $this->error($upload['msg']);
            }
            $user = $params;
            if(!empty($params['bank'])){
                $bank = explode(',', $params['bank']);
            }
            if(!empty($bank[0])&&!empty($bank[1])){
                $user['bank_id']   = $bank[0];
                $user['bank_name'] = $bank[1];
            }
            if(!empty($params['paynumber'])){
                return $this->error('请选择放款账号');
            }
            $user['id']             = $params['user_group'];
            $user['name']           = $params['user'];
            $user['contact']        = 3;
            $user['commission']     = $commission['percent'];
            $user['account_number'] = $params['bankNumber'];
            $user['account_name']   = $params['bankUser'];
            $user['payable_bn_id']  = $params['paynumber'];
            if (loader::validate('Mechanism')->scene('backadd')->check($save) === false) {
                return $this->error(loader::validate('Mechanism')->getError());
            }
            if (loader::validate('User')->scene('add')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }

            if (($id = DB::name('Mechanism')->insertGetId($save)) === false) {
                return $this->error('添加机构失败');
            }
            $user['commissionId'] = $params['commission'];
            $user['mechanismId'] = $id;
            $user['mechanismAdmin'] = 1;
            if (($userId = Loader::model('User')->userInc($user)) === false) {
                return $this->error(loader::model('User')->getError());
            }
            Loader::model('SystemLog')->record("添加机构,ID:[{$id}]");
            Loader::model('SystemLog')->record("用户添加,ID:[{$userId}]");
            return $this->success('添加机构成功', Url::build('Mechanism/index'));
        }else{
            $commission = DB::name('commission')->where(['disable'=>1,'isDelete'=>0])->select();
            $groupRows = Loader::model('Groupdata')->select();
            $bank = DB::name('bank')->select();
            $bn = DB::name('BankNumber')->field('id,bank_number,owner')->where('isDelete',0)->select();
            $this->assign('bn',$bn);
            $this->assign('bank',$bank);
            $this->assign('groupRows', $groupRows);
            $this->assign('commission',$commission);
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params = $request->param();
            $commission = DB::name('commission')->where('id',$params['commission'])->find();
            $save   = array(
                'id'        =>  $params['id'],
                'name'          =>  $params['name'],
                'commissionId'  =>  $params['commission'],
                'commission'    =>  $commission['percent'],
                'disable'       =>  $params['status'],
            );
            $user = array('commissionId'=>$params['commission'],'commission'=>$commission['percent']);
            if (loader::validate('Mechanism')->scene('backedit')->check($save) === false) {
                return $this->error(loader::validate('Mechanism')->getError());
            }
            if ((DB::name('Mechanism')->where('id',$save['id'])->update($save)) === false) {
                return $this->error('修改机构失败');
            }
            DB::name('user')->where('mechanismId',$save['id'])->update($user);
            Loader::model('SystemLog')->record("修改机构,ID:[{$save['id']}]");
            return $this->success('修改机构成功', Url::build('Mechanism/index'));
        }else{
            $mechanism = DB::name('mechanism')
            ->alias('m')
            ->join('mos_user u','m.id = u.mechanismId AND u.mechanismAdmin = 1','left')
            ->field('m.*,u.zid')
            ->where('m.isDelete',0)
            ->where('m.id',$id)
            ->find();
            $commission = DB::name('commission')->where(['disable'=>1,'isDelete'=>0])->select();
            $this->assign('commission',$commission);
            $this->assign('mechanism',$mechanism);
            return $this->fetch();
        }
    }

    public function del() {
        $request = Request::instance();
        $id = $request->param('id');
        $del = array('isDelete' => 1);
        if (DB::name('mechanism')->where('id',$id)->update($del) === false) {
            return $this->error('机构删除失败');
        }
        Loader::model('SystemLog')->record("删除机构,ID:[{$id}]");
        return $this->success('机构删除成功', Url::build('mechanism/index'));
    }

    public function userlist(){
        $id = input('mechanism');
        $mechanism = DB::name('mechanism')->where('id',$id)->find();
        if(empty($mechanism)){
            $this->error('獲取機構信息失敗!',url('Mechanism/index'));
        }
        $users = DB::name('User')->where('mechanismId',$id)->paginate(10);
        $this->assign('users', $users);
        $this->assign('mechanism', $mechanism);
        $this->assign('pages',$users->render());
        return $this->fetch();
    }

    public function useradd() {
        $request = Request::instance();
        if ($request->isPost()) {
            $bank = array();
            $params = $request->param();
            $mechanism = DB::name('mechanism')->where('id',$params['mechanism'])->find();
            $file = request()->file('images');
            // 调用上传方法
            $upload = uploadPic($file,'head');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($upload['code']==1){
                $params['head'] = $upload['msg'];
            }else{
                return $this->error($upload['msg']);
            }
            $user = $params;
            $user['contact'] = 3;
            $user['commission'] = $mechanism['commission'];
            $user['commissionId'] = $mechanism['commissionId'];
            $user['mechanismId'] = $params['mechanism'];
            if(!empty($params['bank'])){
                $bank = explode(',', $params['bank']);
            }
            if(!empty($bank[0])&&!empty($bank[1])){
                $user['bank_id']   = $bank[0];
                $user['bank_name'] = $bank[1];
            }

            $user['account_number'] =  $params['bankNumber'];
            $user['account_name']   =  $params['bankUser'];
            if (loader::validate('User')->scene('add')->check($user) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            if (($userId = Loader::model('User')->userInc($user)) === false) {
                return $this->error(loader::model('User')->getError());
            }
            Loader::model('SystemLog')->record("机构成員添加,ID:[{$userId}]");
            return $this->success('添加机构成員成功', Url::build('Mechanism/userlist',['mechanism'=>$params['mechanism']]));
        }else{
            $id = input('mechanism');
            $groupRows = Loader::model('Groupdata')->select();
            $mechanism = DB::name('mechanism')->where('id',$id)->find();
            if(empty($mechanism)){
                $this->error('獲取機構信息失敗!',url('Mechanism/index'));
            }
            $bank = DB::name('bank')->select();
            $this->assign('bank',$bank);
            $this->assign('mechanism', $mechanism);
            $this->assign('groupRows', $groupRows);
            return $this->fetch();
        }
    }

    public function useredit($zid) {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('images');
            if($file){
                // 调用上传方法
                $upload = uploadPic($file,'head');
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $params['head'] = $upload['msg'];
                }
            }
            $userModel = Loader::model('User');
            $params['zid'] = $zid;
            if(!empty($params['bank'])){
                $bank = explode(',', $params['bank']);
            }
            if(!empty($bank[0])&&!empty($bank[1])){
                $params['bank_id']   = $bank[0];
                $params['bank_name'] = $bank[1];
            }
            if(!empty($params['paynumber'])){
                return $this->error('请选择放款账号');
            }
            $params['account_number'] =  $params['bankNumber'];
            $params['account_name']   =  $params['bankUser'];
            $params['payable_bn_id']  =  $params['paynumber'];
            if (loader::validate('User')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('User')->getError());
            }

            if (Loader::model('User')->userUpe($params) === false) {
                return $this->error(loader::model('User')->getError());
            }
            Loader::model('SystemLog')->record("编辑成員,ID:[{$zid}]");
            return $this->success('成員修改成功', Url::build('Mechanism/userlist',['mechanism'=>$params['mechanism']]));


        }
        $id = input('mechanism');
        $mechanism = DB::name('mechanism')->where('id',$id)->find();
        $userRow = Db::name('user')->where(['zid' => $zid])->find();
        // dump($userRow);exit();
        $groupRows = Loader::model('Groupdata')->select();
        $bank = DB::name('bank')->select();
        $bn = DB::name('BankNumber')->field('id,bank_number,owner')->where('isDelete',0)->select();
        $this->assign('bn',$bn);
        $this->assign('bank',$bank);
        $this->assign('mechanism', $mechanism);
        $this->assign('groupRows', $groupRows);
        $this->assign('userRow', $userRow);
        return $this->fetch();
    }

    public function userdel() {
        $zid = input('zid');
        if (Loader::model('User')->deleteuser($zid) === false) {
            return $this->error(Loader::model('User')->getError());
        }
        Loader::model('SystemLog')->record("删除成員,ID:[{$zid}]");
        return $this->success('成員删除成功');
    }

}