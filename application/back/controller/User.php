<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Groupname;
use\think\Db;
use\think\Loader;
use\think\Request;
use\think\Url;
use think\Controller;

class User extends AdminBase {
    /**
     * 后台主面板
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-16
     * @return [type] [description]
     */
    public function index() {
        $param = input('get.');
        $where = array();
        if(isset($param['group'])&&$param['group']!==''){
            $where['a.uid'] = $param['group'];
        }

        if(isset($param['search'])&&$param['search']!==''){
            $where['a.name'] = ['like','%'.$param['search'].'%'];
        }
        $group = DB::name('Groupdata')->field('id,title')->select();
        $userRows = Loader::model('User')
            ->alias('a')
            ->join(['groupdata'=>'b', 'mos_'], 'a.uid = b.id','left')
            ->join(['contact'=>'c', 'mos_'], 'a.contact_number = c.number','left')
            ->field('a.uid,a.name,a.nick,a.zid,a.status,b.title,b.rules,c.name as cname')
            ->where($where)
            ->order('a.zid desc')
            ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('group', $group);
        $this->assign('userRows', $userRows);
        $this->assign('pages',$userRows->render());
        return $this->fetch();
    }


    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-27
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('images');
            // 调用上传方法
            $upload = uploadPic($file,'head');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($upload['code']==1){
                $params['head'] = $upload['msg'];
            }else{
                return $this->error($upload['msg']);
            }
            if($params['contact']==3){
                if(empty($params['commission'])){
                    return $this->error('请设置佣金分组!');
                }
                if(empty($params['paynumber'])){
                    return $this->error('请选择发款账号!');
                }
                $params['payable_bn_id'] = $params['paynumber'];
                $commission = DB::name('commission')->where('id',$params['commission'])->find();
                if(!empty($commission)){
                    $params['commissionId'] = $commission['id'];
                    $params['commission']   = $commission['percent'];
                }else{
                    return $this->error('查詢分組失敗!');
                }
            }
            if (loader::validate('User')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('User')->getError());
            }
            if (($userId = Loader::model('User')->userInc($params)) === false) {
                return $this->error(loader::model('User')->getError());
            }
            Loader::model('SystemLog')->record("用户添加,ID:[{$userId}]");
            if($params['contact']==3){
                $suffix = url('mobile/login/register',['market'=>$userId]);
                $url = 'http://'.$_SERVER['HTTP_HOST'].$suffix;
                $res = add_wx_web_qrcode($url,'','market/qrcode');
                if($res['code']==1){
                    $return = DB::name('user')->where('zid',$userId)->update(['qrcode'=>$res['msg']]);
                    if($return!==false){
                        return $this->success('市場人員添加成功', url('user/index'));
                    }else{
                        return $this->success('市場人員添加成功,二維碼保存失敗', url('user/index'));
                    }
                }else{
                    return $this->success('市場人員添加成功,二維碼生成失敗', url('user/index'));
                }
            }else{
                return $this->success('后台用户添加成功', url('user/index'));
            }
        }else{
            //用户属于哪个用户组(商家只能将员工放在自己建立的分组)
            if(session('ext_user.is_contact')==0){
                $groupRows = Loader::model('Groupdata')->select();
                // 查询出商家方便进行绑定
                $contact_list = DB::name('contact')->field('name,number')->where(['disable'=>1,'isDelete'=>0])->select();
                $this->assign('list',$contact_list);
            }else{
                $contact_number = session('ext_user.contact_number');
                $groupRows = Loader::model('Groupdata')->where('contact_number',$contact_number)->select();
            }
            $commission = DB::name('commission')->where(['disable'=>1,'isDelete'=>0])->select();
            $bn = DB::name('BankNumber')->field('id,bank_number,owner')->where('isDelete',0)->select();
            $this->assign('bn',$bn);
            $this->assign('commission',$commission);
            $this->assign('groupRows', $groupRows);
            return $this->fetch();
        }
    }

    /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-27
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function edit($zid) {
        $request = Request::instance();
        $userRow = Db::name('user')->where(['zid' => $zid])->find();
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
            if($userRow['is_contact']==3){
                if(empty($params['commission'])){
                    return $this->error('请选择佣金分组!');
                }
                if(empty($params['paynumber'])){
                    return $this->error('请选择发款账号!');
                }
                $params['payable_bn_id'] = $params['paynumber'];
                $commission = DB::name('commission')->where('id',$params['commission'])->find();
                if(!empty($commission)){
                    $params['commissionId'] = $commission['id'];
                    $params['commission']   = $commission['percent'];
                }else{
                    return $this->error('查詢分組失敗!');
                }
            }
            if (loader::validate('User')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('User')->getError());
            }

            if (Loader::model('User')->userUpe($params) === false) {
                return $this->error(loader::model('User')->getError());
            }
            Loader::model('SystemLog')->record("编辑用户,ID:[{$zid}]");
            return $this->success('后台用户修改成功', Url::build('back/user/index'));


        }
        // dump($userRow);exit();
        $groupRows = Loader::model('Groupdata')->select();
        if($userRow['is_contact']==3){
            $bn = DB::name('BankNumber')->field('id,bank_number,owner')->where('isDelete',0)->select();
            $commission = DB::name('commission')->where(['disable'=>1,'isDelete'=>0])->select();
            $this->assign('bn',$bn);
            $this->assign('commission',$commission);
        }
        $this->assign('groupRows', $groupRows);
        $this->assign('userRow', $userRow);
        return $this->fetch();
    }

    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-27
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function destroy($zid) {
        if (Loader::model('User')->deleteuser($zid) === false) {
            return $this->error(Loader::model('User')->getError());
        }
        Loader::model('SystemLog')->record("删除用户,ID:[{$zid}]");
        return $this->success('用户删除成功', Url::build('back/user/index'));
    }
}