<?php
namespace app\wxweb\controller;

use think\Controller;

use think\Url;
class Banner extends Controller{

    public function index() {
        return $this->fetch();
    }


}