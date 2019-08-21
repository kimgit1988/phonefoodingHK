<?php
namespace app\common\model;
use think\Model;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use think\Redirect;

class Spec extends Model {


    // 菜品分类方法
    public function parents()
    {
       return $this->hasMany('Spec', 'spec_pid', 'id')->where(array('isDelete' => 0))->order('spec_order asc,id desc');
    }


}