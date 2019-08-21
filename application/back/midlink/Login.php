<?php
namespace app\back\midlink;
use think\Request;
use think\Response\Redirect;
use think\Config;
use think\Loader;
use think\Session;
 /**
     * 设置了action—begin,所有方法的入口都会从这里过，过滤一下权限
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-16
     * @return [type] [description]
     */
class Login {
    public function run(&$params) {
        $request = Request::instance();
        /* 判断登陆，有session下一步*/
        if (session('ext_user')) {
        	/*后台首页的控制器Index,没必要验证,让通过*/
            if ($request -> controller() == "Index") {
                Loader::action('back/index/index');
            } else {
                // 是否超级管理员
                $super = session('ext_user.super');
                // 非超级管理员-需验证权限
                if(!$super){
                	/* 有session，除了首页控制器，全部要从数据查看字段，获取有没有当前Contrllor/action*/
                    $uid = session('ext_user.uid');
                    if ($nima = Loader::model('Rule')->checkRule($uid) === false) {
                        Loader::action('back/index/auth');
                    }
                }
            }
        }
    }
}