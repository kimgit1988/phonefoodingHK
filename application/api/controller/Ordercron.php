<?php
namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Ordercron extends Controller
{
    //清机时间段执行，未付款未完成的单都取消:http://food.vbus.hk/index.php/api/ordercron
    public function index()
    {
        $startTime = date('H:i:s',time());
        $contacts = Db::name('contact')->where('cleanStartTime','<',$startTime)->where('cleanEndTime','>',$startTime)->select();
        log_output('Ordercron任务执行时间：'.date('Y-m-d H:i:s',time()));
        if(count($contacts)>0) {
            foreach($contacts as $contact) {
                DB::name('wxOrder')->where('contactNumber',$contact['number'])->where('payStatus',0)->where('orderStatus=2 or orderStatus=3')->update(['orderStatus'=>0,'addStatus'=>0]);
                DB::name('wxOrder')->where('contactNumber',$contact['number'])->where('payStatus',1)->where('orderStatus=2 or orderStatus=3')->update(['orderStatus'=>4,'addStatus'=>0]);
            }
        }
    }
}
