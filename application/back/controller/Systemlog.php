<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Db;
use think\Loader;
use think\Request;
use think\Url;
use think\helper\Time;
class SystemLog extends AdminBase
{
    /**
     * 日志列表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index()
    {
        //$LogModel = Loader::model('SystemLog');
        $sysModel = DB::name('system_log');
        $time=Time::daysAgo(7);
       //一星期前的系统日志删除
        $sysModel->where('op_time','<',$time)->delete();
        $sysModel->insert(['remark' => '定时清理一星期前系统日志', 'op_time'=>time()]);
       //读取数据库系统日志
        $LogRows  = $sysModel->order('op_time','desc')->paginate(15);
        $this->assign('LogRows', $LogRows);
        
        $this->assign('pages', $LogRows->render());
        return $this->fetch();
    }

}
