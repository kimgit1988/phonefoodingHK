<?php
namespace app\mobile\controller;
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
        if (!session('mob_user')) {
            return $this->redirect('login/index');
        }
        //利用当前的session，获取用户uid，读取数据分配到页面。
        $zid=session('mob_user.zid');
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
            case 'owner':
                $click = array('owner' => 1, 'order'=>0, 'foods'=>0, 'manage'=>0);
                break;
            case 'order':
                $click = array('owner' => 0, 'order'=>1, 'foods'=>0, 'manage'=>0);
                break;
            case 'foods':
                $click = array('owner' => 0, 'order'=>0, 'foods'=>1, 'manage'=>0);
                break;
            case 'manage':
                $click = array('owner' => 0, 'order'=>0, 'foods'=>0, 'manage'=>1);
                break;
            default:
                $click = array('owner' => 0, 'order'=>0, 'foods'=>0, 'manage'=>0);
                break;
        }
        if(session('mob_user.is_contact')==2){
            $menu           = array(
                /*array('name'=>'訂單','url'=>url('order/index'),'icon'=>__ROOT__.'/static/assets/mobile/images/order-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/order-icon-click.png','click'=>$click['order']),
                array('name'=>'菜式','url'=>url('foods/index'),'icon'=>__ROOT__.'/static/assets/mobile/images/foods-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/foods-icon-click.png','click'=>$click['foods']),
                array('name'=>'註銷','url'=>url('Manage/loginout',['type'=>2]),'icon'=>__ROOT__.'/static/assets/mobile/images/login-out-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/logo-out-icon-click.png','click'=>0),*/
            );
        }else{
            $menu           = array(
                array('name'=>'店主','url'=>url('owner/index'),'icon'=>__ROOT__.'/static/assets/mobile/images/owner-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/owner-icon-click.png','click'=>$click['owner']),
                array('name'=>'訂單','url'=>url('order/index'),'icon'=>__ROOT__.'/static/assets/mobile/images/order-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/order-icon-click.png','click'=>$click['order']),
                array('name'=>'菜式','url'=>url('foods/index'),'icon'=>__ROOT__.'/static/assets/mobile/images/foods-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/foods-icon-click.png','click'=>$click['foods']),
                array('name'=>'管理','url'=>url('manage/index'),'icon'=>__ROOT__.'/static/assets/mobile/images/manage-icon.png','icon_click'=>__ROOT__.'/static/assets/mobile/images/manage-icon-click.png','click'=>$click['manage']),
            );
        }
        
        $this->assign('menu',$menu);
    }

}