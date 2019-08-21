<?php
namespace app\common\model;
use \app\common\controller\Visitor;
use \think\Config;
use \think\Model;
use \think\Session;
use \think\Db;

class SystemLog extends Model
{
    protected $dateFormat = 'Y/m/d';
    protected $updateTime = false;
    protected $insert     = ['ip', 'user_id'];
    protected $type       = [
        'op_time' => 'timestamp:Y/m/d',
    ];

    /**
     * 设置登录用户的ip
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    [type]                   $ip [description]
     */
    protected function setIpAttr()
    {
        return Visitor::getIP();
    }

    /**
     * [setUserId description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    string                   $value [description]
     */
    protected function setUserIdAttr()
    {
        $user_id = 0;
        if (session('ext_user.uid') !== false) {
            $user_id = session('ext_user.uid');
        }
        return $user_id;
    }

  
/**
     * [name 控制器]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-22
     */
    public static function listlog() {
        return Db::name('systemLog')->alias('a')->join(['user'=>'b', 'mos_'], 'b.uid = a.user_id') ->order('id desc')->field('id,name,op_time,ip,remark');
    }


    /**
     * [record description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    [type]                   $remark [description]
     * @return   [type]                           [description]
     */
    public function record($remark)
    {
        return true;//很多地方调用这个日志方法报错，暂时返回true不往下执行
        $this->save(
        ['remark' => $remark,
         'op_time'=>time()
        ]
        );
    }

}
