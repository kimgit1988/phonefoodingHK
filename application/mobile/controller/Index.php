<?php
namespace app\mobile\controller;
use app\mobile\controller\Base;
// use app\common\model\User;
use think\View;//视图
use think\Controller;//控制器
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\DB;//数据库

class Index extends Base {
    public function index() {
        return $this->redirect('login/index');
        //return $this->fetch();
    }

}