<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Market extends AdminBase {
    
    public function index() {
        $where = array();
        $mechanism = DB::name('user')
        ->alias('u')
        ->join('mos_mechanism m','u.mechanismId = m.id','left')
        ->join('mos_commission c','u.commissionId = c.id','left')
        ->field('*,u.zid as id,m.id as mid,u.status as status,m.disable as disable,u.name as name,m.name as mname,u.nick as nick,c.name as cname')
        ->where('u.mechanismAdmin = 1 OR u.mechanismId is null OR u.mechanismId = 0')
        ->where('u.is_contact',3)
        ->order('u.zid desc')
        // ->fetchsql(true)
        // ->select();var_dump($mechanism);die;
        ->paginate(10);
        $commission = DB::name('commission')->where('isDelete',0)->where('disable',1)->select();
        $this->assign('commission',$commission);
        $this->assign('mechanism', $mechanism);
        $this->assign('pages',$mechanism->render());
        return $this->fetch();
    }

    public function commission(){
        $request = Request::instance();
        if ($request->isPost()) {
            $params   = $request->param();
            $user = DB::name('user')->where('zid',$params['id'])->find();
            $commission = DB::name('commission')->where('id',$params['commission'])->where('isDelete',0)->where('disable',1)->find();
            if(empty($user)){
                return $this->error('用户账号或机构主账号不存在');
            }
            if(empty($commission)){
                return $this->error('佣金分组不存在');
            }
            $update['commission'] = $commission['percent'];
            $update['commissionId'] = $commission['id'];
            if($user['mechanismAdmin']==1){
                $res = 1;
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('user')->where('mechanismId',$user['mechanismId'])->update($update);
                    Db::name('mechanism')->where('id',$user['mechanismId'])->update($update);
                    // 提交事务
                    Db::commit();    
                } catch (\Exception $e) {
                    $res = 0;
                    // 回滚事务
                    Db::rollback();
                }
            }else{
                $res = Db::name('user')->where('zid',$params['id'])->update($update);
            }

            if($res){
                return $this->success('修改成功！',url('Market/index'));
            }else{
                return $this->error('修改失败！');
            }
        }else{
            $id = $request->param('id');
            $mechanism = DB::name('user')
            ->alias('u')
            ->join('mos_mechanism m','u.mechanismId = m.id','left')
            ->join('mos_commission c','u.commissionId = c.id','left')
            ->field('*,u.zid as id,m.id as mid,u.status as status,m.disable as disable,u.name as name,m.name as mname,u.nick as nick,c.name as cname')
            ->where('u.mechanismAdmin = 1 OR u.mechanismId is null OR u.mechanismId = 0')
            ->where('u.is_contact',3)
            ->order('u.zid desc')
            ->find($id);
            $commission = DB::name('commission')->where('isDelete',0)->select();
            $this->assign('commission',$commission);
            $this->assign('mechanism',$mechanism);
            return $this->fetch();
        }

    }

}