<?php
 
namespace app\padweb\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Loader;
use think\Session;
use wechat\Oauth;
use wechat\jssdk;

/**
 * 网站首页控制器
 * @author  kiyang
 */
class Base extends Controller
{

    public function _initialize(){
    }

    public function check_login(){

    	$login = Session::has('web_user');
    	if(!$login){
            $court = Session::has('court');
    		$contact = Session::has('contact');
    		$member  = Session::has('member');
    		//未有餐厅&餐桌session跳到未扫码
    		if(!$contact||!$member){
    			$this->redirect('index/index');
    		}else if(!$court){
                $this->redirect('index/index',['courtNumber'=>$court]);
            }else{
    			$contactNo = Session::get('contact');
    			$memberNo  = Session::get('member');
    			$this->redirect('index/index',['contactNo'=>$contactNo,'contactMemberNo'=>$memberNo]);
    		}
    	}
    }

    public function check_member(){
    	$contact = Session::has('contact');
    	$member  = Session::has('member');
    	//未有餐厅&餐桌session跳到未扫码
    	if(!$contact||!$member){
    		$this->redirect('index/index');
    	}
    }

}