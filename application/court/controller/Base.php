<?php
namespace app\court\controller;
use think\View;
use think\Input;
use think\Loader;
use think\Request;
use think\Controller;
use app\common\model\User;
use Captcha;//extend文件夹。验证码类，你也可以在线composer
use think\Url;
class Base extends Controller{
    public function _initialize()
    {
        if (!session('court_user')) {
            return $this->redirect('login/index');
        }
        //利用当前的session，获取用户uid，读取数据分配到页面。
        $zid=session('court_user.zid');
        $this->menu();
    }

    // 读取菜单
    public function menu()
    {
        $request        = Request::instance();
        //获取当前控制器
        $controller     = strtolower($request->controller());
        //获取当前方法
        $action         = strtolower($request->action());
        //组合url
        $url            = $controller."/".$action;
        switch ($controller) {
            case 'index':
                $click = array('index' => 1, 'more'=>0, 'manage'=>0,);
                break;
            case 'more':
                $click = array('index' => 0, 'more'=>1, 'manage'=>0,);
                break;
            case 'manage':
                $click = array('index' => 0, 'more'=>0, 'manage'=>1,);
                break;
            default:
                $click = array('index' => 0, 'more'=>0, 'manage'=>0,);
                break;
        }
        $menu           = array(
            array('name'=>'首頁','url'=>url('index/index'),'icon'=>__ROOT__.'/static/assets/market/images/index-icon.png','icon_click'=>__ROOT__.'/static/assets/market/images/index-icon-click.png','click'=>$click['index']),
            array('name'=>'添加','url'=>url('more/index'),'icon'=>__ROOT__.'/static/assets/market/images/more-icon.png','icon_click'=>__ROOT__.'/static/assets/market/images/more-icon-click.png','click'=>$click['more']),
            array('name'=>'管理','url'=>url('manage/index'),'icon'=>__ROOT__.'/static/assets/market/images/manage-icon.png','icon_click'=>__ROOT__.'/static/assets/market/images/manage-icon-click.png','click'=>$click['manage']),
        );
        
        $this->assign('menu',$menu);
    }

}