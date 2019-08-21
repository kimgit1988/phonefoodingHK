<?php
namespace app\common\controller;
use think\Config;//加载读取config的类
use think\Controller;
use think\Db;//操作数据库
use think\Request;
use think\Session;
use think\Loader;
use think\Url;


/**
 *基础控制器    所有控制器继承它
 */
class AdminBase extends Controller{
    /**
     * [__construct description]
      * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-18
     */
    public function __construct(){
        parent::__construct();
        // 当前位置
    $this->getBreadcrumb();//面包屑导航对象给继承他的页面
    $this->nav();//导航对象给继承他的页面
        
   if (!session('ext_user')) {
         return $this->success('您未登錄！請馬上登錄','login/index');}
      //利用当前的session，获取用户uid，读取数据分配到页面。
      $zid=session('ext_user.zid');
      $user= Loader::model('User')->find($zid);
      $this->assign('user',$user);
   }

    /**
     * 获取当前位置  面包屑导航
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-18
     * @return   [type] [description]
     */
    protected function getBreadcrumb(){
        $breadcrumb = [];
        $request = Request::instance();
        $rule    = $request->controller() . '/' . $request->action();//拼接控制器和方法
        
        $isHere = Db::name('rule') ->field('parent_id,title,name')->where('name', $rule)->find();
        if (empty($isHere)) {
            return false;
        }
        //如果没有父类就自己存到数组，如果有再取一次存到数组
        if ($isHere['parent_id'] !== 0) {
            $breadcrumb[] = Db::name('rule')->field('parent_id,title,name')->where('id', $isHere['parent_id'])->find();
        }
        $breadcrumb[] = $isHere;
        $this->assign('breadcrumb', $breadcrumb);
    }
    
    
    protected function nav(){
      if(session('ext_user.super')==1){
        $model=Loader::model('Rule');
        $ruledata =$model::where(array('status' => 1, 'parent_id' => 0,'show'=>1))->order('sort asc')->select();
      }else{
        $uid=session('ext_user.uid');
        $model=Loader::model('Rule');
        $ruleRow =Loader::model('Groupdata')->get($uid);
        $zcc=$ruleRow['rules'];
        $ids = array();
        $ids = array_merge($ids, explode(',', trim($zcc, ',')));
        $map['id']  = array('in',$ids);
        $ruledata =$model::where($map)->where(array('status' => 1, 'parent_id' => 0,'show'=>1))->order('sort asc')->select();
        // ext_user不为空才更新nav
        $user = session("ext_user");
        if(!empty($user)){
          session("ext_user.map", $map);
        }
      }
      $this->assign('ruledata',$ruledata);
    }
      
        
        
}
