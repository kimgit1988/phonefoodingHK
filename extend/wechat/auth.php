<?php
/**
* 功能说明：微信菜单类，实现菜单创建，这里不实现查和改主要是因为用后台统一发布的方式
* @author zwf 
* @version 1.0
*/
namespace wechat;
use think\Request;
use wechat\core;
class Auth extends Core{
	  
  	/**
    * 构造函数
    * @author 郑伟锋
    */
  	public function __construct($config = array()) {
        //默认为zwf123456，实际根据用户填写
        parent::__construct($config);
    }

    /**
    * 网页授权2种方式结合
    * @author 郑伟锋
    * @param $redirect_url重定向链接,$scope授权类型,$state自定义参数,$lang语言
    */
    public function oauth($redirect_url,$scope,$state="STATE",$lang="zh_CN"){
        $code = input('get.code');
        if(empty($code)){
            //获取code
            header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=$this->appId&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=$scope&state=STATE#wechat_redirect");
            exit();
        }
        //获取access_token
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=$this->appId&secret=$this->appSecret&code=$code&grant_type=authorization_code";
        $res = json_decode($this->curl($url),true);
        //隐式授权，只获取openid
        if($scope=='snsapi_base'){
            if(isset($res['errcode'])){
                $this->setError($res);
                return false;
            }else{
                return $res;
            }
        //授权，获取用户信息
        }else if($scope=='snsapi_userinfo'){
            if(isset($res['errcode'])){
                $this->setError($res);
                return false;
            }else{
                //获取用户信息
                $url="https://api.weixin.qq.com/sns/userinfo?access_token=".$res['access_token']."&openid=".$res['openid']."&lang=".$lang;
                $resl = json_decode($this->curl($url),true);
                if(isset($resl['errcode'])){
                    $this->setError($resl);
                    return false;
                }else{
                    return $resl;
                }
            }
        }else{
            $this->setError("参数错误！");
            return false;
        }
    }

}
?>